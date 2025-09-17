<?php
  
  class MMZohoIntegration {
    public static function get_access_token( $force_refresh = false ) {
      global $wpdb;
      $table_name = $wpdb->prefix . 'zoho_api_keys';
      
      // Check for a valid token in the database
      if ( ! $force_refresh ) {
        $row = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE expires_at > NOW() ORDER BY id DESC LIMIT 1" );
        
        if ( $row ) {
          error_log( "Valid token retrieved from database: " . $row->access_token );
          
          return $row->access_token;
        }
      }
      
      // Refresh the token
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
        
        error_log( "New access token generated: " . $response->access_token );
        
        return $response->access_token;
      }
      
      error_log( "Failed to refresh token: " . json_encode( $response ) );
      
      return null;
    }
    
    /**
     * Creates a new lead in Zoho CRM based on the provided user ID.
     *
     * @param int $user_id The ID of the user to create a lead for.
     *
     * @return bool True on success, false on failure.
     */
    public static function generate_new_lead( $user_id ) {
      $access_token = self::get_access_token();
      
      if ( ! $access_token ) {
        error_log( "Failed to get access token." );
        
        return false;
      }
      
      $author_obj = get_user_by( 'id', $user_id );
      $first_name = $author_obj->user_firstname ?: 'TBC';
      $last_name  = $author_obj->user_lastname ?: $author_obj->user_email;
      $email      = $author_obj->user_email;
      $status     = ( get_user_meta( $user_id, 'has_to_be_activated', true ) === 'verified' ) ? 'Verified' : 'Not Verified';
      
      $lead_data = [
        'data'    => [
          [
            'First_Name' => $first_name,
            'Last_Name'  => $last_name,
            'Email'      => $email,
            'stage'      => $status,
          ],
        ],
        'trigger' => [ 'workflow' ],
      ];
      
      $ch = curl_init();
      curl_setopt( $ch, CURLOPT_URL, "https://www.zohoapis.com/crm/v3/Leads" );
      curl_setopt( $ch, CURLOPT_POST, 1 );
      curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $lead_data ) );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_HTTPHEADER, [
        "Authorization: Zoho-oauthtoken $access_token",
        'Content-Type: application/json',
      ] );
      
      $server_output = curl_exec( $ch );
      $response      = json_decode( $server_output );
      curl_close( $ch );
      
      if ( isset( $response->data ) ) {
        return true;
      }
      
      error_log( "Error creating lead: " . json_encode( $response ) );
      
      return false;
    }
    
    /**
     * Retrieves the social user information from the database for the given user ID.
     *
     * @param int $user_id The ID of the user to retrieve social data for.
     *
     * @return object|null The social user data or null if none found.
     */
    public static function get_social_user( $user_id ) {
      global $wpdb;
      $table       = $wpdb->prefix . 'social_users';
      $social_user = $wpdb->get_results( "SELECT * FROM $table where ID = $user_id" );
      
      return $social_user;
    }
    
    public static function update_lead( $user_id, $size_1000 = null, $rent_4999 = null, $neighborhoods = [] ) {
      $access_token = self::get_access_token();
      
      if ( ! $access_token ) {
        error_log( "Failed to get access token in update_lead." );
        
        return false;
      }
      
      $author_obj = get_user_by( 'id', $user_id );
      $first_name = $author_obj->user_firstname ?: 'TBC';
      $last_name  = $author_obj->user_lastname ?: $author_obj->user_email;
      $email      = $author_obj->user_email;
      $status     = ( get_user_meta( $user_id, 'has_to_be_activated', true ) === 'verified' ) ? 'Verified' : 'Not Verified';
      
      // Log user data
      error_log( "Attempting to update lead for user: $email, Status: $status" );
      
      // Search for the lead by email
      $search_url = "https://www.zohoapis.com/crm/v3/Leads/search?email=" . urlencode( $email );
      $ch         = curl_init();
      curl_setopt( $ch, CURLOPT_URL, $search_url );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_HTTPHEADER, [
        "Authorization: Zoho-oauthtoken $access_token",
        'Content-Type: application/json',
      ] );
      $search_response = curl_exec( $ch );
      
      if ( curl_errno( $ch ) ) {
        error_log( "CURL error during lead search: " . curl_error( $ch ) );
        curl_close( $ch );
        
        return false;
      }
      
      $lead_data = json_decode( $search_response );
      curl_close( $ch );
      
      // Log search response
      error_log( "Lead search response: " . json_encode( $lead_data ) );
      
      if ( isset( $lead_data->data ) && ! empty( $lead_data->data[0]->id ) ) {
        $leadID = $lead_data->data[0]->id;
        
        // Prepare update data
        $update_data = [
          'data'    => [
            [
              'stage' => $status,
            ],
          ],
          'trigger' => [ 'workflow' ],
        ];
        
        if ( $size_1000 !== null ) {
          $update_data['data'][0]['Size_1000'] = $size_1000;
        }
        
        if ( $rent_4999 !== null ) {
          $update_data['data'][0]['Rent_4999'] = $rent_4999;
        }
        
        if ( ! empty( $neighborhoods ) ) {
          $update_data['data'][0]['Neighborhoods'] = implode( ';', $neighborhoods );
        }
        
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, "https://www.zohoapis.com/crm/v3/Leads/$leadID" );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $update_data ) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
          "Authorization: Zoho-oauthtoken $access_token",
          'Content-Type: application/json',
        ] );
        
        $update_response = curl_exec( $ch );
        
        if ( curl_errno( $ch ) ) {
          error_log( "CURL error during lead update: " . curl_error( $ch ) );
          curl_close( $ch );
          
          return false;
        }
        
        $update_result = json_decode( $update_response );
        curl_close( $ch );
        
        // Log update response
        error_log( "Lead update response: " . json_encode( $update_result ) );
        
        if ( isset( $update_result->data ) ) {
          error_log( "Lead successfully updated for user: $email, Lead ID: $leadID" );
          
          return $update_result;
        }
        
        error_log( "Error in lead update response for user: $email, Response: " . json_encode( $update_result ) );
      } else {
        error_log( "Lead not found for email: $email, Response: " . json_encode( $lead_data ) );
      }
      
      return false;
    }
    
    public static function batch_upsert_leads( $data ) {
      $access_token = self::get_access_token();
      
      if ( ! $access_token ) {
        return [ 'success' => false, 'message' => 'Failed to retrieve access token.' ];
      }
      
      $api_url = "https://www.zohoapis.com/crm/v3/Leads/upsert";
      $payload = [
        'data'                   => $data,
        'duplicate_check_fields' => [ 'Email' ],
        'trigger'                => [ 'workflow' ]
      ];
      
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
        return [ 'success' => false, 'message' => "CURL Error: $error" ];
      }
      
      $result = json_decode( $response, true );
      if ( isset( $result['data'] ) ) {
        return [ 'success' => true, 'message' => $result['data'] ];
      }
      
      return [ 'success' => false, 'message' => $result['message'] ?? 'Unknown error.' ];
    }
    
  }