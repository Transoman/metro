<?php
  // File: wp-cli-upsert-forms.php
  
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class Upsert_Forms_Command {
      public static function get_access_token( $force_refresh = false ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zoho_api_keys';
        
        if ( ! $force_refresh ) {
          $row = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE expires_at > NOW() ORDER BY id DESC LIMIT 1" );
          if ( $row ) {
            error_log( "[" . date('Y-m-d H:i:s') . "] Valid token retrieved from database: " . $row->access_token );
            
            return $row->access_token;
          }
        }
        
        $post_data = [
          'refresh_token' => defined( 'ZOHO_REFRESH_TOKEN' ) ? ZOHO_REFRESH_TOKEN : '',
          'client_id'     => defined( 'ZOHO_CLIENT_ID' ) ? ZOHO_CLIENT_ID : '',
          'client_secret' => defined( 'ZOHO_CLIENT_SECRET' ) ? ZOHO_CLIENT_SECRET : '',
          'grant_type'    => 'refresh_token',
        ];
        
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, defined( 'ZOHO_TOKEN_URL' ) ? ZOHO_TOKEN_URL : '' );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post_data ) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/x-www-form-urlencoded' ] );
        
        $server_output = curl_exec( $ch );
        $response      = json_decode( $server_output );
        curl_close( $ch );
        
        if ( isset( $response->access_token ) ) {
          $expires_at = date( 'Y-m-d H:i:s', time() + (int) $response->expires_in );
          $wpdb->query( "TRUNCATE TABLE {$table_name}" );
          $wpdb->insert(
            $table_name,
            [
              'access_token' => $response->access_token,
              'expires_at'   => $expires_at,
            ],
            [ '%s', '%s' ]
          );
          
          error_log( "[" . date('Y-m-d H:i:s') . "] New access token generated: " . $response->access_token );
          
          return $response->access_token;
        }
        
        error_log( "[" . date('Y-m-d H:i:s') . "] Failed to refresh token: " . json_encode( $response ) );
        
        return null;
      }
      
      private static function fix_url( $url ) {
        if ( strpos( $url, '//' ) === 0 ) {
          return 'https:' . $url;
        } elseif ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
          return null;
        }
        
        return $url;
      }
      
      public function __invoke() {
        global $wpdb;
        try {
          $details_table = $wpdb->prefix . 'vxcf_leads_detail';
          $leads_table   = $wpdb->prefix . 'vxcf_leads';
          
          $query = "SELECT ld.lead_id,
                                 l.id as lead_table_id,
                                 l.form_id,
                                 p.post_title AS form_name,
                                 l.url as url,
                                 JSON_OBJECTAGG(ld.name, ld.value) AS lead_data
                          FROM {$details_table} ld
                          JOIN {$leads_table} l ON ld.lead_id = l.id
                          JOIN {$wpdb->prefix}posts p ON CAST(SUBSTRING(l.form_id, 4) AS UNSIGNED) = p.ID
                          WHERE p.post_type = 'wpcf7_contact_form'
                            AND l.zoho_processed = 0
                            AND l.status = 0
                          GROUP BY ld.lead_id, l.form_id, p.post_title";
          
          $records = $wpdb->get_results( $query );
          
          if ( empty( $records ) ) {
            WP_CLI::log( '[' . date('Y-m-d H:i:s') . '] No records to process.' );
            
            return;
          }
          
          $batches      = array_chunk( $records, 50 );
          $access_token = self::get_access_token();
          if ( ! $access_token ) {
            return;
          }
          
          $api_url = "https://www.zohoapis.com/crm/v3/API_Requests";
          
          foreach ( $batches as $batch ) {
            $payload = [
              'data'    => [],
              'trigger' => [ 'workflow' ],
            ];
            
            foreach ( $batch as $record ) {
              $lead_data = json_decode( $record->lead_data, true );
              
              $email_keys = [ 'email', 'simple-form-email', 'contact-email', 'your-email' ];
              $email      = null;
              
              foreach ( $email_keys as $key ) {
                if ( isset( $lead_data[ $key ] ) && ! empty( $lead_data[ $key ] ) ) {
                  $email = $lead_data[ $key ];
                  break;
                }
              }
              
              if ( ! $email ) {
                WP_CLI::log( "[" . date('Y-m-d H:i:s') . "] Skipping record without email: lead_id {$record->lead_id}" );
                continue;
              }
              
              // Fetch user_id based on email
              $user_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->prefix}users WHERE user_email = %s LIMIT 1",
                $email
              ) );
              
              $field_map = [
                'first-name'         => 'first_name',
                'contact-firstname'  => 'first_name',
                'last-name'          => 'last_name',
                'contact-lastname'   => 'last_name',
                'contact-phone'      => 'phone',
                'your-tel'           => 'phone',
                'phone'              => 'phone',
                'email'              => 'email',
                'simple-form-email'  => 'email',
                'contact-email'      => 'email',
                'your-email'         => 'email',
                'message'            => 'message',
                'contact-message'    => 'message',
                'user-name'          => 'username',
                'page-name'          => 'page',
                'address'            => 'address',
                'square-feet'        => 'square_feet',
                'suite-floor'        => 'suite_floor',
                'checkbox-type'      => 'checkbox_type',
                'checkbox-available' => 'checkbox_available',
                'your-company'       => 'company_name',
              ];
              
              $standardized_data = [];
              foreach ( $lead_data as $key => $value ) {
                if ( isset( $field_map[ $key ] ) ) {
                  $standard_key                       = $field_map[ $key ];
                  $standardized_data[ $standard_key ] = $value;
                }
              }
              
              if ( isset( $standardized_data['username'] ) && ! empty( $standardized_data['username'] ) ) {
                $name_parts                      = explode( ' ', $standardized_data['username'], 2 );
                $standardized_data['first_name'] = $name_parts[0];
                $standardized_data['last_name']  = isset( $name_parts[1] ) ? $name_parts[1] : '';
                unset( $standardized_data['username'] );
              }
              
              if ( $user_id ) {
                $standardized_data['user_id'] = $user_id;
                
                // Retrieve the user's first and last name
                $user_info = $wpdb->get_row(
                  $wpdb->prepare(
                    "SELECT meta1.meta_value AS first_name, meta2.meta_value AS last_name
                                     FROM {$wpdb->prefix}usermeta AS meta1
                                     JOIN {$wpdb->prefix}usermeta AS meta2 ON meta1.user_id = meta2.user_id
                                     WHERE meta1.user_id = %d
                                       AND meta1.meta_key = 'first_name'
                                       AND meta2.meta_key = 'last_name'",
                    $user_id
                  )
                );
                
                if ( $user_info ) {
                  if ( isset( $user_info->first_name ) ) {
                    $standardized_data['first_name'] = $user_info->first_name;
                  }
                  if ( isset( $user_info->last_name ) ) {
                    $standardized_data['last_name'] = $user_info->last_name;
                  }
                }
              }
              
              $created_at = $wpdb->get_var(
                $wpdb->prepare(
                  "SELECT created FROM {$leads_table} WHERE id = %d LIMIT 1",
                  $record->lead_id
                )
              );
              
              if ( $created_at ) {
                // Parse the created_at time as UTC and convert to New York timezone
                $date = new DateTime( $created_at, new DateTimeZone( 'UTC' ) );
                $date->setTimezone( new DateTimeZone( 'America/New_York' ) );
                $formatted_created_at = $date->format( 'Y-m-d\TH:i:sP' );
              } else {
                $formatted_created_at = ( new DateTime( 'now', new DateTimeZone( 'America/New_York' ) ) )->format( 'Y-m-d\TH:i:sP' );
              }
              
              $standardized_data['form_name'] = $record->form_name;
              if ( str_contains( $standardized_data['form_name'], 'Listing Schedule' ) ) {
                if ( str_contains( $record->url, '/listing' ) ) {
                  $standardized_data['form_name'] = 'Listing-Schedule a Tour';
                } else {
                  $standardized_data['form_name'] = 'Building-Schedule a Tour';
                }
              } else if ( str_contains( $standardized_data['form_name'], 'Listing inqury' ) || str_contains( $standardized_data['form_name'], 'Listing inquiry' ) ) {
                if ( str_contains( $record->url, '/listing' ) ) {
                  $standardized_data['form_name'] = 'Listing-Inquiry';
                } else {
                  $standardized_data['form_name'] = 'Building-Listing Inquiry';
                }
              }
              
              $standardized_data['form_submission_id'] = $record->lead_table_id;
              $standardized_data['form_id']            = $record->form_id;
              
              $request_body = json_encode( $standardized_data );
              
              $payload_item = [
                'Request_Body' => $request_body,
                'Email'        => $email,
                'Request_Type' => 'Forms',
                'Created_At'   => $formatted_created_at,
                'Form_name'    => $standardized_data['form_name'],
              ];
              
              if ( ! str_contains( $record->url, 'wp-json' ) ) {
                if ( filter_var( $record->url, FILTER_VALIDATE_URL ) ) {
                  $payload_item['URL'] = $record->url;
                } else {
                  $fixed_url = self::fix_url( $record->url );
                  if ( $fixed_url !== null ) {
                    $payload_item['URL'] = $fixed_url;
                  }
                }
              }
              
              $payload['data'][] = $payload_item;
            }
            
            $attempts      = 0;
            $max_attempts  = 2;
            $response_data = null;
            
            while ( $attempts < $max_attempts ) {
              $ch = curl_init();
              curl_setopt( $ch, CURLOPT_URL, $api_url );
              curl_setopt( $ch, CURLOPT_POST, true );
              curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $payload ) );
              curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
              curl_setopt( $ch, CURLOPT_HTTPHEADER, [
                "Authorization: Zoho-oauthtoken $access_token",
                'Content-Type: application/json',
              ] );
              
              $response = curl_exec( $ch );
              $error    = curl_error( $ch );
              curl_close( $ch );
              
              if ( $error ) {
                break;
              }
              
              $response_data = json_decode( $response, true );
              if ( isset( $response_data['code'] ) && $response_data['code'] === 'INVALID_TOKEN' ) {
                $access_token = self::get_access_token( true );
                if ( ! $access_token ) {
                  break;
                }
                $attempts ++;
              } else {
                break;
              }
            }
            
            if ( ! $response_data || ( isset( $response_data['code'] ) && $response_data['code'] === 'INVALID_TOKEN' ) ) {
              continue;
            }
            
            if ( isset( $response_data['data'] ) ) {
              $processed_ids   = array_map( fn( $record ) => $record->lead_id, $batch );
              $ids_placeholder = implode( ',', array_fill( 0, count( $processed_ids ), '%d' ) );
              $wpdb->query(
                $wpdb->prepare( "UPDATE $leads_table SET zoho_processed = 1 WHERE id IN ($ids_placeholder)", $processed_ids )
              );
            }
          }
          
          gc_collect_cycles();
        } catch ( Exception $e ) {
          WP_CLI::error( "[" . date('Y-m-d H:i:s') . "] An error occurred during the upsert operation. Check the log file for details." );
          
          return;
        }
      }
    }
    
    WP_CLI::add_command( 'upsert-forms', 'Upsert_Forms_Command' );
  }
