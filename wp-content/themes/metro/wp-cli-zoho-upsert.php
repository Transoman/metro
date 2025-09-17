<?php
  // File: wp-cli-zoho-upsert.php
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class ProcessZohoRecords {
      public static function get_access_token( $force_refresh = false ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zoho_api_keys';
        
        // Check for a valid token in the database
        if ( ! $force_refresh ) {
          $row = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE expires_at > NOW() ORDER BY id DESC LIMIT 1" );
          if ( $row ) {
            error_log( "[" . date('Y-m-d H:i:s') . "] Valid token retrieved from database: " . $row->access_token );
            
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
          
          error_log( "[" . date('Y-m-d H:i:s') . "] New access token generated: " . $response->access_token );
          
          return $response->access_token;
        }
        
        error_log( "[" . date('Y-m-d H:i:s') . "] Failed to refresh token: " . json_encode( $response ) );
        
        return null;
      }
      
      public function process_unprocessed_records() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'search_stats';
        $records    = $wpdb->get_results(
          "SELECT * FROM $table_name WHERE user_id > 0 AND zoho_processed = 0 ORDER BY created_at DESC LIMIT 1000",
          ARRAY_A
        );
        
        if ( empty( $records ) ) {
          WP_CLI::log( "[" . date('Y-m-d H:i:s') . "] No unprocessed records found." );
          
          return;
        }
        
        $data          = [];
        $processed_ids = [];
        foreach ( $records as $record ) {
          $author_obj = get_user_by( 'id', $record['user_id'] );
          $email      = $author_obj->user_email;
          $state      = json_decode( $record['state'], true );
          
          // Assuming the DB timestamp is in Atlantic Standard Time (e.g. America/Halifax)
          $date = new DateTime( $record['created_at'], new DateTimeZone( 'America/Halifax' ) );
          $date->setTimezone( new DateTimeZone( 'America/New_York' ) );
          $created_at_nyc = $date->format( 'Y-m-d H:i:s' );
          
          $request_body = json_encode( [
            'id'         => $record['id'],
            'user_id'    => $record['user_id'],
            'email'      => $email,
            'state'      => $state,
            'created_at' => $created_at_nyc,
          ] );
          
          $data[]          = [
            'Email'        => $email,
            'Request_Body' => $request_body,
            'Request_Type' => 'Searches',
          ];
          $processed_ids[] = $record['id'];
        }
        
        $chunks      = array_chunk( $data, 100 );
        $ids_chunked = array_chunk( $processed_ids, 100 );
        
        foreach ( $chunks as $index => $chunk ) {
          try {
            $result = $this->send_to_zoho( $chunk );
            
            $current_ids  = $ids_chunked[ $index ];
            $ids_imploded = implode( ',', array_map( 'intval', $current_ids ) );
            $wpdb->query( "UPDATE {$table_name} SET zoho_processed = 1 WHERE id IN ({$ids_imploded})" );
            
            WP_CLI::success( "[" . date('Y-m-d H:i:s') . "] Processed " . count( $chunk ) . " records successfully." );
          } catch ( Exception $e ) {
            WP_CLI::error( "[" . date('Y-m-d H:i:s') . "] Failed to process chunk: " . $e->getMessage() );
          }
        }
        
        gc_collect_cycles();
      }
      
      private function send_to_zoho( $data ) {
        $access_token = self::get_access_token();
        $api_url      = "https://www.zohoapis.com/crm/v3/API_Requests";
        $payload      = [ 'data' => $data, 'trigger' => [ 'workflow' ] ];
        
        $attempts     = 0;
        $max_attempts = 2;
        
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
            throw new Exception( "CURL Error: $error" );
          }
          
          $response_data = json_decode( $response, true );
          
          if ( isset( $response_data['code'] ) && $response_data['code'] === 'INVALID_TOKEN' ) {
            $access_token = self::get_access_token( true ); // Refresh token
            $attempts ++;
          } else {
            return $response_data;
          }
        }
        
        throw new Exception( "Persistent INVALID_TOKEN error after $max_attempts attempts." );
      }
    }
    
    WP_CLI::add_command( 'process-zoho-records', 'ProcessZohoRecords' );
  }
