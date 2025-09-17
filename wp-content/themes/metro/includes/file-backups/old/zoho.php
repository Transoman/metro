<?php

/**
 * Class MMZohoIntegration
 *
 * This class provides methods to integrate with the Zoho CRM API.
 * It handles authentication with Zoho's OAuth2 system, creation of new leads, and updating of existing leads.
 */
class MMZohoIntegration {
	/**
	 * Retrieves an access token from Zoho using a refresh token.
	 *
	 * @return string|null The access token or null if an error occurs.
	 */
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
		$author_obj  = get_user_by( 'id', $user_id );
		$token       = self::get_access_token();
		$social_user = self::get_social_user( $user_id );

		if ( get_user_meta( $user_id, 'has_to_be_activated', true ) == 'verified' ) {
			$status = "Verified";
		} elseif ( ! empty( $social_user ) ) {
			$status = "Verified";
		} else {
			$status = "Not Verified";
		}
		$first_name = $author_obj->user_firstname;
		if ( $first_name == "" ) {
			$first_name = "TBC";
		}

		$last_name = $author_obj->user_lastname;

		$emailencoder = html_entity_decode( $author_obj->user_email );
		$email        = preg_replace( "/\s+/", "", $emailencoder );

		if ( $last_name == "" ) {
			$last_name = $email;
		}

		if ( empty( $token ) ) {
			return false;
		}

		if ( $token != null && $token != "" ) {
			$lead_data = [
				'data' => [
					[
						"First_Name" => $first_name,
						"Last_Name"  => $last_name,
						"Email"      => $email,
						"stage"      => $status
					]

				],

				'trigger' => [
					"approval",
					"workflow",
					"blueprint"
				]
			];

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, "https://www.zohoapis.com/crm/v3/Leads" );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $lead_data ) );
			// Receive server response ...
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$headers = array(
				'Authorization: Zoho-oauthtoken ' . $token,
				'Content-type: application/x-www-form-urlencoded'
			);
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

			$server_output2 = curl_exec( $ch );
			$lead           = json_decode( $server_output2 );

			return true;
		}
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

	/**
	 * Updates an existing lead in Zoho CRM based on the provided user ID.
	 *
	 * @param int $user_id The ID of the user to update the lead for.
	 *
	 * @return object|null The response from the Zoho CRM API, or null if an error occurs.
	 */
	public static function update_lead( $user_id ) {
		$author_obj  = get_user_by( 'id', $user_id );
		$token       = self::get_access_token();
		$social_user = self::get_social_user( $user_id );

		if ( get_user_meta( $user_id, 'has_to_be_activated', true ) == 'verified' ) {
			$status = "Verified";
		} elseif ( ! empty( $social_user ) ) {
			$status = "Verified";
		} else {
			$status = "Not Verified";
		}
		$first_name = $author_obj->user_firstname;
		if ( $first_name == null || $first_name == "" ) {
			$first_name = "TBC";
		}

		$last_name = $author_obj->user_lastname;

		$emailencoder = html_entity_decode( $author_obj->user_email );
		$user_email   = preg_replace( "/\s+/", "", $emailencoder );
		if ( $last_name == null || $last_name == "" ) {
			$last_name = $user_email;
		}

		if ( $token != null && $token != "" ) {
			$search_email = curl_init();
			curl_setopt( $search_email, CURLOPT_URL, "https://www.zohoapis.com/crm/v3/Leads/search?email=" . $user_email );

			// Receive server response ...
			curl_setopt( $search_email, CURLOPT_RETURNTRANSFER, true );
			$headers = array(
				'Authorization: Zoho-oauthtoken ' . $token,
				'Content-type:application/json'
			);
			curl_setopt( $search_email, CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $search_email, CURLOPT_CUSTOMREQUEST, "GET" );
			$search_email_output2 = curl_exec( $search_email );
			$lead_data            = json_decode( $search_email_output2 );
			curl_close( $search_email );
			$leadID = $lead_data->data[0]->id;

			if ( $leadID != null && $leadID != "" ) {
				// Update Lead in CRM
				$lead_data = [
					'data' => [
						[
							"stage" => $status
						]

					],

					'trigger' => [
						"approval",
						"workflow",
						"blueprint"
					]
				];

				$update_lead = curl_init();
				curl_setopt( $update_lead, CURLOPT_URL, "https://www.zohoapis.com/crm/v3/Leads/" . $leadID );
				curl_setopt( $update_lead, CURLOPT_POST, 1 );
				curl_setopt( $update_lead, CURLOPT_POSTFIELDS, json_encode( $lead_data ) );

				// Receive server response ...
				curl_setopt( $update_lead, CURLOPT_RETURNTRANSFER, true );
				$headers = array(
					'Authorization: Zoho-oauthtoken ' . $token,
					'Content-type: application/x-www-form-urlencoded'
				);
				curl_setopt( $update_lead, CURLOPT_HTTPHEADER, $headers );
				curl_setopt( $update_lead, CURLOPT_CUSTOMREQUEST, "PUT" );
				$update_lead_output2 = curl_exec( $update_lead );
				$lead                = json_decode( $update_lead_output2 );
				curl_close( $update_lead );

				return $lead;
			}
		}
	}
}

