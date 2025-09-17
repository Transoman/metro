<?php
  // metro/includes/registration-login.php
  
  class MMRegistrationUser {
    // Class properties for tracking verification status and codes
    static bool $verified = false;
    
    static ?string $email_verification_code = null;
    static ?string $password_verification_code = null;
    
    public function __construct() {
      // Register WordPress hooks and actions
      add_action( 'init', [ $this, 'rewrite_rule' ] );
      add_action( 'wp', [ $this, 'verifying_email' ], 99 );
      add_action( 'wp', [ $this, 'verifying_password_code' ], 98 );
      add_filter( 'do_parse_request', [ $this, 'parse_request' ], 1, 3 );
      add_action( 'wp_ajax_mm_registration_user', [ $this, 'register_new_user' ] );
      add_action( 'wp_ajax_nopriv_mm_registration_user', [ $this, 'register_new_user' ] );
      add_action( 'wp_ajax_mm_authorization_user', [ $this, 'authorization_user' ] );
      add_action( 'wp_ajax_nopriv_mm_authorization_user', [ $this, 'authorization_user' ] );
      add_action( 'wp_ajax_mm_resend_email', [ $this, 'resend_email' ] );
      add_action( 'wp_ajax_nopriv_mm_resend_email', [ $this, 'resend_email' ] );
      add_action( 'wp_ajax_mm_reset_password_user', [ $this, 'reset_password' ] );
      add_action( 'wp_ajax_nopriv_mm_reset_password_user', [ $this, 'reset_password' ] );
      add_action( 'wp_ajax_mm_set_password', [ $this, 'set_password' ] );
      add_action( 'wp_ajax_nopriv_mm_set_password', [ $this, 'set_password' ] );
      add_action( 'template_redirect', [ $this, 'checking_notification' ], 99 );
      
      // More hooks for integrations and redirects
      // add_action( 'nsl_register_new_user', function ( $user_id ) {
      //   MMZohoIntegration::generate_new_lead( $user_id );
      // } );
      
      // Handle redirect after registration and login
      add_action( 'template_redirect', function () {
        if ( ! is_user_logged_in() ) {
          global $wp;
          $_SESSION['nsl_registration_redirect'] = home_url() . '/' . $wp->request;
        }
      }, PHP_INT_MAX );
      
      add_filter( 'google_register_redirect_url', function ( $redirectUrl, $provider ) {
        $redirectUrl          = ( ! empty( $_SESSION['nsl_registration_redirect'] ) ) ? $_SESSION['nsl_registration_redirect'] : home_url();
        $_SESSION['verified'] = true;
        $redirectUrl          = $redirectUrl . '/?' . http_build_query( [ 'registration_provider' => 'google' ] );
        
        return $redirectUrl;
      }, PHP_INT_MAX, 2 );
      
      add_filter( 'facebook_register_redirect_url', function ( $redirectUrl, $provider ) {
        $redirectUrl          = ( ! empty( $_SESSION['nsl_registration_redirect'] ) ) ? $_SESSION['nsl_registration_redirect'] : home_url();
        $_SESSION['verified'] = true;
        $redirectUrl          = $redirectUrl . '/?' . http_build_query( [ 'registration_provider' => 'facebook' ] );
        $_SESSION['verified'] = true;
        
        return $redirectUrl;
      }, PHP_INT_MAX, 2 );
      
      add_filter( 'linkedin_register_redirect_url', function ( $redirectUrl, $provider ) {
        $redirectUrl          = ( ! empty( $_SESSION['nsl_registration_redirect'] ) ) ? $_SESSION['nsl_registration_redirect'] : home_url();
        $_SESSION['verified'] = true;
        $redirectUrl          = $redirectUrl . '/?' . http_build_query( [ 'registration_provider' => 'linkedin' ] );
        
        return $redirectUrl;
      }, PHP_INT_MAX, 2 );
      
      add_action( 'user_register', function ( $user_id ) {
        $ip_user = MetroManhattanHelpers::get_real_ip_address();
        if ( empty( $ip_user ) ) {
          error_log( 'IP Address not found for user ' . $user_id );
          
          return;
        }
        
        $geo_data = MetroManhattanHelpers::get_geolocation_by_ip( $ip_user );
        
        update_user_meta( $user_id, 'user_geo_data', $geo_data );
        update_user_meta( $user_id, 'timestamp_register', time() );
      }, PHP_INT_MAX, 1 );
      
      add_action( 'nsl_login', function ( $user_id ) {
        $user_email = get_userdata( $user_id )->user_email;
        self::update_search_stats_with_user_id( $user_id, $user_email );
        
        $is_user_verified = get_user_meta( $user_id, 'has_to_be_activated', true );
        if ( $is_user_verified !== 'verified' ) {
          update_user_meta( $user_id, 'has_to_be_activated', 'verified' );
          
          self::push_data_to_zoho_crm( $user_id );
          
          $_SESSION['mm_notification'] = [
            'title'   => 'Welcome to Metro-Manhattan!',
            'status'  => 'success',
            'message' => 'You have signed in, and your account is ready to use.'
          ];
        }
      }, PHP_INT_MAX, 1 );
      
      add_action( 'google_login_redirect_url', function ( $redirectUrl ) {
        if ( wp_get_referer() === get_permalink( get_field( 'choose_resend_email_page', 'option' ) ) ) {
          $redirectUrl = get_home_url();
        }
        
        return $redirectUrl;
      }, PHP_INT_MAX, 1 );
    }
    
    public static function update_search_stats_with_user_id( $user_id, $user_email ) {
      global $wpdb;
      
      if ( empty( $user_email ) ) {
        error_log( "No email provided for user ID: {$user_id}" );
        
        return;
      }
      
      $search_stats_table = $wpdb->prefix . 'search_stats';
      
      // Fetch records where the email matches the user's email
      $records = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT * FROM {$search_stats_table} WHERE email = %s",
          $user_email
        )
      );
      
      // Update each record with the user_id
      if ( ! empty( $records ) ) {
        foreach ( $records as $record ) {
          $update_result = $wpdb->update(
            $search_stats_table,
            [ 'user_id' => $user_id ], // Data to update
            [ 'id' => $record->id ],   // Where condition
            [ '%d' ],                  // Format for data
            [ '%d' ]                   // Format for where condition
          );
          
          if ( $update_result === false ) {
            error_log( "Failed to update record ID: {$record->id} for user email: {$user_email}" );
          } else {
            error_log( "Successfully updated record ID: {$record->id} for user email: {$user_email}" );
          }
        }
      } else {
        error_log( "No matching records found in {$search_stats_table} for email: {$user_email}" );
      }
    }
    
    /**
     * Parses the request URI to extract email verification code.
     *
     * @param bool $do_parse_request Whether to parse the request.
     * @param WP $wp The WordPress object.
     * @param array $extra_query_vars Additional query variables.
     *
     * @return bool Whether to continue parsing the request.
     */
    public static function parse_request( $do_parse_request, $wp, $extra_query_vars ) {
      preg_match(
        '#\/verification\/([0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12})\/#',
        $_SERVER['REQUEST_URI'],
        $matches
      );
      if ( count( $matches ) == 2 ) {
        $_SERVER['REQUEST_URI']        = str_replace( $matches[0], '/', $_SERVER['REQUEST_URI'] );
        self::$email_verification_code = $matches[1];
      }
      
      return $do_parse_request;
    }
    
    /**
     * Registers custom rewrite rules for the site.
     */
    public static function rewrite_rule() {
      $rewrite_slugs = [ 'reset-password' ];
      add_filter( 'query_vars', function ( array $vars ) {
        array_push( $vars, 'reset-password' );
        
        return $vars;
      } );
      foreach ( $rewrite_slugs as $slug ) {
        add_rewrite_rule( '^(.?.+?)/' . $slug . '/([0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12})/?$', 'index.php?pagename=$matches[1]&' . $slug . '=$matches[2]', 'top' );
      }
    }
    
    /**
     * Handles new user registration via AJAX.
     */
    public static function register_new_user() {
      if ( isset( $_POST['mm_registration_email'] ) && isset( $_POST['mm_registration_password'] ) && wp_verify_nonce( $_POST['mm_registration_nonce'], 'mm-registration-nonce' ) ) {
        $user_email          = $_POST['mm_registration_email'];
        $user_password       = $_POST['mm_registration_password'];
        $user_first_name     = $_POST['mm_registration_first_name'];
        $user_last_name      = $_POST['mm_registration_last_name'];
        $user_company        = ( ! empty( $_POST['mm_registration_company'] ) ) ? $_POST['mm_registration_company'] : null;
        $email_link          = $_POST['mm_registration_redirect_to'] ?? home_url();
        $response            = [];
        $response['invalid'] = [];
        
        if ( $user_email == '' ) {
          self::errors()->add( 'email', __( 'Enter email' ) );
          array_push( $response['invalid'], 'email' );
        }
        if ( email_exists( $user_email ) ) {
          self::errors()->add( 'email', __( 'Email already registered' ) );
          array_push( $response['invalid'], 'email' );
        }
        if ( ! is_email( $user_email ) && $user_email !== '' ) {
          self::errors()->add( 'email', __( 'Invalid email' ) );
          array_push( $response['invalid'], 'email' );
        }
        if ( is_email( $user_email ) && ! email_exists( $user_email ) ) {
          if ( ! self::is_password_valid( $user_password ) ) {
            self::errors()->add( 'password', __( 'The password doesn’t look right. Please use at least 8 characters: a mix of letters and numbers.' ) );
            array_push( $response['invalid'], 'password' );
          }
        }
        if ( $user_first_name == '' || ! self::is_valid_registration_input( $user_first_name ) ) {
          self::errors()->add( 'first_name', __( 'First name should be 2-50 characters long and use only Latin letters, spaces, hyphens, or apostrophes.' ) );
          array_push( $response['invalid'], 'first_name' );
        }
        if ( $user_last_name == '' || ! self::is_valid_registration_input( $user_last_name ) ) {
          self::errors()->add( 'last_name', __( 'Last name should be 2-50 characters long and use only Latin letters, spaces, hyphens, or apostrophes.' ) );
          array_push( $response['invalid'], 'last_name' );
        }
        
        $errors = self::errors()->get_error_messages();
        
        if ( empty( $errors ) ) {
          $new_user_args = [
            'user_pass'       => $user_password,
            'user_email'      => $user_email,
            'user_login'      => $user_email,
            'first_name'      => $user_first_name,
            'last_name'       => $user_last_name,
            'user_registered' => date( 'o-m-d h:i:s' ),
            'role'            => get_option( 'default_role' )
          ];
          if ( ! empty( $user_company ) ) {
            $new_user_args['meta_input'] = [ 'company' => $user_company ];
          }
          $new_user_id = wp_insert_user( $new_user_args );
          
          if ( is_numeric( $new_user_id ) ) {
            $verification_code = self::guidv4();
            $verification_link = self::link_generation( [
              'code'     => $verification_code,
              'page'     => $email_link,
              'url_part' => '/verification/'
            ] );
            update_user_meta( $new_user_id, 'timestamp_register', time() );
            add_user_meta( $new_user_id, 'has_to_be_activated', $verification_code );
            $redirect_page                  = get_permalink( get_field( 'choose_resend_email_page', 'option' ) );
            $response['redirect']           = $redirect_page;
            $_SESSION['registration_email'] = $user_email;
            
            self::push_data_to_zoho_crm( $new_user_id );
            self::update_search_stats_with_user_id( $new_user_id, $user_email );
            self::send_mail_to_user( $user_email, [ 'status' => 'verification', 'link' => $verification_link ] );
          }
        } else {
          $response['invalid']  = array_values( array_unique( $response['invalid'] ) );
          $response['messages'] = self::errors_message();
        }
        
        echo json_encode( $response );
        wp_die();
      }
    }
    
    private static function push_data_to_zoho_crm( $user_id ) {
        $user_data = get_userdata( $user_id );
        $geo_data  = get_user_meta( $user_id, 'user_geo_data', true ) ?? 'No information';
        $geo_data  = preg_replace( [ '/^<br>/', '/<br>/' ], [ '', ',' ], $geo_data );
        $geo_data  = trim( $geo_data, ',' );
        $role      = self::get_user_role( $user_id );
    
        // Handle registration time (create if missing)
        $registration_timestamp = get_user_meta( $user_id, 'timestamp_register', true );
        if ( empty( $registration_timestamp ) ) {
            $registration_timestamp = time();
            update_user_meta( $user_id, 'timestamp_register', $registration_timestamp );
        }
        
        // Convert registration time to NY timezone
        $registration_time = '';
        if ($registration_timestamp) {
            $datetime = new DateTime("@$registration_timestamp");
            $datetime->setTimezone(new DateTimeZone('America/New_York'));
            $registration_time = $datetime->format('Y-m-d H:i:s');
        }
    
        // Handle last login time (no creation if missing)
        $last_login_timestamp = get_user_meta( $user_id, 'wfls_last_login', true );
        $last_login_time = '';
        if ($last_login_timestamp) {
            $datetime = new DateTime("@$last_login_timestamp");
            $datetime->setTimezone(new DateTimeZone('America/New_York'));
            $last_login_time = $datetime->format('Y-m-d H:i:s');
        }
    
        $request_body = json_encode( [
            'user_id'           => $user_id,
            'username'          => $user_data->user_login,
            'name'              => $user_data->first_name . ' ' . $user_data->last_name,
            'email'             => $user_data->user_email,
            'geo_info'          => $geo_data,
            'registration_time' => $registration_time,
            'last_login'        => $last_login_time,
            'role'              => $role,
        ]);
    
        $zoho_data = [
            'Email'        => $user_data->user_email,
            'Request_Body' => $request_body,
            'Request_Type' => 'User_Registration',
        ];
    
        $zoho_response = self::send_to_zoho_crm( $zoho_data );
        if ( $zoho_response ) {
            error_log( 'Zoho CRM integration successful for user: ' . $user_id );
        } else {
            error_log( 'Zoho CRM integration failed for user: ' . $user_id );
        }
    }
    
    private static function send_to_zoho_crm( $data ) {
      $access_token = self::get_access_token();
      $api_url      = "https://www.zohoapis.com/crm/v3/API_Requests";
      $payload      = [ 'data' => [ $data ], 'trigger' => [ 'workflow' ] ];
      
      $attempts     = 0;
      $max_attempts = 2;
      
      while ( $attempts < $max_attempts ) {
        $response = wp_remote_post( $api_url, [
          'method'  => 'POST',
          'headers' => [
            "Authorization" => "Zoho-oauthtoken $access_token",
            'Content-Type'  => 'application/json'
          ],
          'body'    => json_encode( $payload )
        ] );
        
        if ( is_wp_error( $response ) ) {
          error_log( 'Zoho CRM API request failed: ' . $response->get_error_message() );
          
          return null;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_data = json_decode( $response_body, true );
        
        if ( $response_code === 401 && isset( $response_data['code'] ) && $response_data['code'] === 'INVALID_TOKEN' ) {
          $access_token = self::get_access_token( true ); // Refresh the token
          $attempts ++;
        } elseif ( $response_code === 201 ) {
          return $response_data;
        } else {
          error_log( 'Zoho CRM API error: ' . $response_body );
        }
      }
      
      error_log( 'Failed to send data to Zoho CRM after ' . $max_attempts . ' attempts.' );
      
      return null;
    }
    
    private static function get_user_role( $user_id ) {
      $user = get_userdata( $user_id );
      
      return ! empty( $user->roles ) ? ucfirst( $user->roles[0] ) : 'No Role Assigned';
    }
    
    private static function get_access_token( $force_refresh = false ) {
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
    
    private static function errors() {
      static $wp_error;
      
      return $wp_error ?? ( $wp_error = new WP_Error( null, null, null ) );
    }
    
    private static function is_password_valid( $password ): bool {
      $condition = preg_match( '/^(?=.*[a-zA-Z])(?=.*[0-9]).{8,}$/', $password );
      
      return $condition;
    }
    
    private static function guidv4(): string {
      $data    = random_bytes( 16 );
      $data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
      $data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );
      
      return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
    }
    
    private static function link_generation( $args ): string {
      $url_part = $args['url_part'];
      $page     = $args['page'];
      $code     = $args['code'];
      
      return $page . $url_part . $code;
    }
    
    private static function send_mail_to_user( $email, $args ) {
      switch ( $args['status'] ) {
        case 'verification':
          $subject = 'Metro Manhattan email verification';
          $link    = $args['link'] . '/?' . http_build_query( [ 'registration_provider' => 'email' ] );
          $message = self::get_message_body( [ 'link' => $link, 'email' => $email ], 'verify' );
          break;
        case 'welcome':
          $subject = 'Welcome to Metro Manhattan';
          $message = self::get_message_body( [ 'email' => $email ], 'welcome' );
          break;
        case 'resend':
          $subject = 'Metro Manhattan password reset';
          $message = self::get_message_body( [ 'link' => $args['link'], 'email' => $email ], 'reset' );
          break;
        default:
          error_log( "Unknown email status: {$args['status']}" );
          
          return false;
      }
      
      // ZeptoMail API setup
      $from_email = 'info@metro-manhattan.com';
      $from_name  = 'Metro Manhattan';
      
      // Base64 encode the image for inline embedding
      $upload_dir = wp_get_upload_dir();
      $image_path = $upload_dir['basedir'] . '/2024/10/mm_logo1.jpg';
      if ( file_exists( $image_path ) ) {
        $image_data = base64_encode( file_get_contents( $image_path ) );
        $image_cid  = 'logo';
      } else {
        error_log( "Image file not found at path: $image_path" );
        $image_data = '';
        $image_cid  = '';
      }
      
      // Replace cid in message if the image exists
      if ( ! empty( $image_data ) ) {
        $message = str_replace( 'cid:logo', 'cid:' . $image_cid, $message );
      }
      
      // Prepare the email payload
      $user           = get_user_by( 'email', $email );
      $recipient_name = ( ! empty( $user->first_name ) ) ? $user->first_name . ' ' . $user->last_name : $email;
      
      $body = [
        'from'         => [
          'address' => $from_email,
          'name'    => $from_name
        ],
        'to'           => [
          [
            'email_address' => [
              'address' => $email,
              'name'    => $recipient_name
            ]
          ]
        ],
        'subject'      => $subject,
        'htmlbody'     => $message,
        'track_opens'  => true,
        'track_clicks' => true,
      ];
      
      // Include inline image if available
      if ( ! empty( $image_data ) ) {
        $body['inline_images'] = [
          [
            'cid'       => $image_cid,
            'content'   => $image_data,
            'mime_type' => 'image/jpeg'
          ]
        ];
      }
      
      // Send request to ZeptoMail API
      $response = wp_remote_post( ZEPTO_BASE_URL, [
        'method'  => 'POST',
        'headers' => [
          'Authorization' => 'Zoho-enczapikey ' . ZEPTO_TOKEN,
          'Content-Type'  => 'application/json'
        ],
        'body'    => json_encode( $body )
      ] );
      
      if ( is_wp_error( $response ) ) {
        error_log( 'Email API request failed: ' . $response->get_error_message() );
        
        return false;
      }
      
      $response_code = wp_remote_retrieve_response_code( $response );
      if ( $response_code == 200 ) {
        error_log( 'Email sent successfully to: ' . $email );
        
        return true;
      } else {
        $response_body = wp_remote_retrieve_body( $response );
        error_log( 'Email API request failed with response code: ' . $response_code . '. Response body: ' . $response_body );
        
        return false;
      }
    }
    
    private static function get_message_body( $args, $template = 'verify' ) {
      switch ( $template ) {
        case 'verify':
          $user          = get_user_by( 'email', $args['email'] );
          $message       = get_option( 'verify-email-template' );
          $message       = str_replace( '{{verify_link}}', $args['link'], $message );
          $greeting_text = ( ! empty( $user->first_name ) ) ? $user->first_name : '';
          $message       = str_replace( '[greetings]', $greeting_text, $message );
          break;
        case 'welcome':
          $user          = get_user_by( 'email', $args['email'] );
          $message       = get_option( 'welcome-email-template' );
          $greeting_text = ( ! empty( $user->first_name ) ) ? $user->first_name : '';
          $message       = str_replace( '[greetings]', $greeting_text, $message );
          break;
        case 'reset':
          $user          = get_user_by( 'email', $args['email'] );
          $greeting_text = ( ! empty( $user->first_name ) ) ? $user->first_name : $user->display_name;
          $message       = get_option( 'reset-password-email-template' );
          $message       = str_replace( '{{reset_password_link}}', $args['link'], $message );
          $message       = str_replace( '[greetings]', $greeting_text, $message );
          break;
      }
      
      return $message;
    }
    
    private static function errors_message() {
      if ( $codes = self::errors()->get_error_codes() ) {
        $output = [];
        foreach ( $codes as $code ) {
          $output[ $code ] = self::errors()->get_error_message( $code );
        }
        
        return $output;
      }
    }
    
    /**
     * Handles user authorization/login via AJAX.
     */
    public static function authorization_user() {
      if ( isset( $_POST['mm_authorization_email'] ) && isset( $_POST['mm_authorization_password'] ) && wp_verify_nonce( $_POST['mm_authorization_nonce'], 'mm-authorization-nonce' ) ) {
        $user_email          = $_POST['mm_authorization_email'];
        $user_password       = $_POST['mm_authorization_password'];
        $redirect_to         = $_POST['mm_authorization_redirect_to'] . '?siq_email=' . $user_email;
        $response            = [];
        $response['invalid'] = [];
        $founded_user        = get_user_by( 'email', $user_email );
        
        if ( $user_email == '' && empty( $founded_user ) ) {
          self::errors()->add( 'email', __( 'Enter email' ) );
          array_push( $response['invalid'], 'email' );
        }
        if ( ! is_email( $user_email ) ) {
          self::errors()->add( 'email', __( 'Invalid email' ) );
          array_push( $response['invalid'], 'email' );
        }
        if ( ! email_exists( $user_email ) ) {
          self::errors()->add( 'email', __( 'This email doesn’t exist' ) );
          array_push( $response['invalid'], 'email' );
        }
        if ( ! empty( $founded_user ) ) {
          $is_user_verified = get_user_meta( $founded_user->ID, 'has_to_be_activated', true );
          if ( ! wp_check_password( $user_password, $founded_user->data->user_pass, $founded_user->ID ) ) {
            self::errors()->add( 'password', __( 'The password doesn’t look right.' ) );
            array_push( $response['invalid'], 'password' );
          }
          if ( $is_user_verified !== 'verified' ) {
            self::errors()->add( 'email', __( 'Your account is not verified. Please check your email for the verification link.' ) );
            array_push( $response['invalid'], 'email' );
          }
        }
        
        $errors = self::errors()->get_error_messages();
        
        if ( empty( $errors ) ) {
          self::authorize_user( $founded_user->ID );
          $username = $founded_user->user_login;
          $response['redirect'] = $redirect_to . '&siq_name=' . $username;
        } else {
          $response['invalid']  = array_values( array_unique( $response['invalid'] ) );
          $response['messages'] = self::errors_message();
        }
        
        echo json_encode( $response );
        wp_die();
      }
    }
    
    private static function authorize_user( $user_id ) {
      $user = get_user_by( 'ID', $user_id );
      wp_set_auth_cookie( $user_id, true );
      wp_set_current_user( $user_id );
      do_action( 'wp_login', $user->user_login, $user );
    }
    
    /**
     * Handles password reset via AJAX.
     */
    public static function reset_password() {
      if ( isset( $_POST['mm_reset_password_email'] ) && wp_verify_nonce( $_POST['mm_reset_password_nonce'], 'mm-reset-password-nonce' ) ) {
        $user_email          = $_POST['mm_reset_password_email'];
        $response            = [];
        $response['invalid'] = [];
        
        if ( $user_email == '' ) {
          self::errors()->add( 'email', __( 'Enter email' ) );
          array_push( $response['invalid'], 'email' );
        }
        if ( ! email_exists( $user_email ) ) {
          self::errors()->add( 'email', __( 'We don’t have an account associated with this email address.' ) );
          array_push( $response['invalid'], 'email' );
        }
        if ( ! is_email( $user_email ) ) {
          self::errors()->add( 'email', __( 'Invalid email' ) );
          array_push( $response['invalid'], 'email' );
        }
        
        $errors = self::errors()->get_error_messages();
        
        if ( empty( $errors ) ) {
          $founded_user         = get_user_by( 'email', $user_email )->ID;
          $reset_code           = self::guidv4();
          $link_args            = [
            'code'     => $reset_code,
            'url_part' => 'reset-password/',
            'page'     => get_permalink( get_field( 'choose_reset_password_page', 'option' ) )
          ];
          $reset_link           = self::link_generation( $link_args );
          $is_reset_code_exists = get_user_meta( $founded_user, 'reset_password_code', true );
          if ( ! empty( $is_reset_code_exists ) ) {
            update_user_meta( $founded_user, 'reset_password_code', $reset_code );
          } else {
            add_user_meta( $founded_user, 'reset_password_code', $reset_code );
          }
          $_SESSION['reset_password_email'] = $user_email;
          $response['status']               = true;
          
          self::send_mail_to_user( $user_email, [ 'status' => 'resend', 'link' => $reset_link ] );
        } else {
          $response['invalid']  = array_values( array_unique( $response['invalid'] ) );
          $response['messages'] = self::errors_message();
        }
        
        echo json_encode( $response );
        wp_die();
      }
    }
    
    /**
     * Sets a new password for the user.
     */
    public static function set_password() {
      if ( isset( $_POST['new_password'] ) && isset( $_POST['mm_set_password_query_var'] ) && wp_verify_nonce( $_POST['mm_set_password_nonce'], 'mm-set-password-nonce' ) ) {
        $user_password       = $_POST['new_password'];
        $reset_code          = $_POST['mm_set_password_query_var'];
        $template            = ( ! empty( $_POST['mm_set_password_template'] ) ) ? $_POST['mm_set_password_template'] : '';
        $response            = [];
        $response['invalid'] = [];
        
        if ( $user_password == '' ) {
          self::errors()->add( 'password', __( 'Enter password' ) );
          array_push( $response['invalid'], 'password' );
        }
        if ( ! self::is_password_valid( $user_password ) ) {
          self::errors()->add( 'password', __( 'The password doesn’t look right. Please use at least 8 characters: a mix of letters and numbers.' ) );
          array_push( $response['invalid'], 'password' );
        }
        
        $errors = self::errors()->get_error_messages();
        if ( empty( $errors ) ) {
          if ( ! is_null( $reset_code ) ) {
            if ( ! is_null( $user_id = self::find_user_by_key( $reset_code, 'reset_password_code' ) ) ) {
              $is_reset_code_exists = get_user_meta( $user_id, 'reset_password_code', true );
              if ( ! empty( $is_reset_code_exists ) ) {
                delete_user_meta( $user_id, 'reset_password_code', $is_reset_code_exists );
                wp_set_password( $user_password, $user_id );
                self::authorize_user( $user_id );
                $redirect_page        = ( empty( $template ) ) ? get_permalink( get_field( 'choose_successful_resent_password_page', 'option' ) ) : get_permalink( get_field( 'choose_search_page', 'option' ) );
                $response['redirect'] = $redirect_page;
              }
            }
          }
        } else {
          $response['invalid']  = array_values( array_unique( $response['invalid'] ) );
          $response['messages'] = self::errors_message();
        }
        
        echo json_encode( $response );
        wp_die();
      }
    }
    
    private static function find_user_by_key( $value, $key = 'has_to_be_activated' ) {
      global $wpdb;
      $sql    = "SELECT * FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1";
      $result = $wpdb->get_row( $wpdb->prepare( $sql, $key, $value ) );
      
      return ( is_object( $result ) ) ? $result->user_id : null;
    }
    
    public static function resend_email() {
      if ( isset( $_POST['resend_email'] ) && wp_verify_nonce( $_POST['mm_resend_nonce'], 'mm-resend-nonce' ) ) {
        $user_mail = $_POST['resend_email'];
        if ( email_exists( $user_mail ) ) {
          $founded_user = get_user_by( 'email', $user_mail )->ID;
          if ( $_SESSION['registration_email'] ) {
            $verification_code = get_user_meta( $founded_user, 'has_to_be_activated', true );
            if ( $verification_code !== 'verified' ) {
              $verification_link = self::link_generation( [
                'code'     => $verification_code,
                'page'     => get_permalink( get_field( 'choose_successful_registration_page', 'option' ) ),
                'url_part' => 'verification/'
              ] );
              self::send_mail_to_user( $user_mail, [ 'status' => 'verification', 'link' => $verification_link ] );
              echo json_encode( [ 'success' => true ] );
            }
          } else {
            $reset_password_code = get_user_meta( $founded_user, 'reset_password_code', true );
            if ( isset( $reset_password_code ) ) {
              $reset_password_link = self::link_generation( [
                'code'     => $reset_password_code,
                'page'     => get_permalink( get_field( 'choose_reset_password_page', 'option' ) ),
                'url_part' => 'reset-password/'
              ] );
              self::send_mail_to_user( $user_mail, [ 'status' => 'resend', 'link' => $reset_password_link ] );
              echo json_encode( [ 'resend_email' => true ] );
            }
          }
        }
        wp_die();
      }
    }
    
    public static function verifying_email() {
      if ( ! is_null( self::$email_verification_code ) ) {
        $verification_key = self::$email_verification_code;
        
        // Check if the user has been redirected
        $is_redirected = isset( $_GET['redirected'] ) && $_GET['redirected'] === 'true';
        
        if ( ! $is_redirected ) {
          if ( ! is_null( $user_id = self::find_user_by_key( $verification_key ) ) ) {
            self::$verified = true;
            $user_data      = get_user_by( 'ID', $user_id );
            update_user_meta( $user_id, 'has_to_be_activated', 'verified' );
            update_user_meta( $user_id, 'verification_key', $verification_key );
            update_user_meta( $user_id, 'verification_key_used_once', $verification_key );
            self::send_mail_to_user( $user_data->user_email, [ 'status' => 'welcome' ] );
            // MMZohoIntegration::update_lead( $user_id );
            self::authorize_user( $user_id );
            
            // Use full URL with the original path
            $current_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $redirect_url = add_query_arg( [
              'siq_email'  => urlencode( $user_data->user_email ),
              'siq_name'   => urlencode( $user_data->user_login ),
              'redirected' => 'true' // Indicate the user has been redirected
            ], $current_url );
            
            // Redirect the user
            wp_redirect( $redirect_url );
            exit;
          } else {
            self::show_warning_notification();
          }
        } else {
          if ( self::$verified ) {
            self::show_success_notification();
          } else {
            self::show_warning_notification();
          }
        }
      }
    }
    
    /**
     * Show success notification.
     */
    private static function show_success_notification() {
      MMNotification::set_notification( true, [
        'title'   => 'Welcome to Metro-Manhattan!',
        'status'  => 'success',
        'message' => 'You have signed in, and your account is ready to use.'
      ] );
    }
    
    /**
     * Show warning notification.
     */
    private static function show_warning_notification() {
      MMNotification::set_notification( true, [
        'title'   => 'Welcome to Metro-Manhattan!',
        'status'  => 'warning',
        'message' => 'This verification link is invalid or has already been used.'
      ] );
    }
    
    public static function verifying_password_code() {
      if ( ! empty( get_query_var( 'reset-password' ) ) && is_null( self::find_user_by_key( get_query_var( 'reset-password' ), 'reset_password_code' ) ) ) {
        $_SESSION['mm_notification'] = [
          'status'  => 'warning',
          'message' => 'This reset password link is invalid or has already been used.'
        ];
        wp_redirect( get_home_url(), 302 );
      }
    }
    
    public static function get_verification_status() {
      $result = ( self::$verified || ( array_key_exists( 'verified', $_SESSION ) && $_SESSION['verified'] ) );
      unset( $_SESSION['verified'] );
      
      return $result;
    }
    
    public static function checking_notification() {
      if ( array_key_exists( 'mm_notification', $_SESSION ) ) {
        $notification = ( ! empty( $_SESSION['mm_notification'] ) ) ? $_SESSION['mm_notification'] : false;
        MMNotification::set_notification( true, $notification );
      }
    }
    
    private static function is_valid_registration_input( $input ) {
      return preg_match( '/^[a-zA-Z\'\-\s]{2,50}$/u', $input );
    }
  }
  
  $instance = new MMRegistrationUser();
