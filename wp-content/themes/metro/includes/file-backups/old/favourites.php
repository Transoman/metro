<?php
  
  /**
   * Class MMFavourites
   *
   * Handles user favorite listings functionality. This includes adding/removing favorites, pagination for favorite listings,
   * and providing an admin interface to view users' favorites.
   */
  class MMFavourites {
    
    /**
     * Syncs the saved listings of a user with Zoho CRM.
     *
     * @param int $user_id The ID of the user whose saved listings need to be synced.
     */
    public static function sync_saved_listings_with_zoho( $user_id ) {
      global $wpdb;
      
      // Fetch all saved listings for the user
      $saved_listings = get_user_meta( $user_id, 'user_favorites', false );
      
      if ( empty( $saved_listings ) ) {
        error_log( "No saved listings found for user ID: $user_id" );
        return;
      }
      
      $placeholders = implode( ',', array_fill( 0, count( $saved_listings ), '%d' ) );
      $query        = $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE ID IN ($placeholders)", $saved_listings );
      $listings     = $wpdb->get_results( $query, ARRAY_A );
      
      if ( empty( $listings ) ) {
        error_log( "No matching listings found for user ID: $user_id" );
        return;
      }
      
      // Construct the request body
      $listing_data = array_map( function ( $listing ) use ( $wpdb ) {
        $listing_id = $listing['ID'];
        
        // Fetch additional metadata
        $square_feet      = get_post_meta( $listing_id, 'square_feet', true );
        $monthly_rent     = get_post_meta( $listing_id, 'monthly_rent', true );
        $primary_type_id  = get_post_meta( $listing_id, 'primary_listing_type', true );
        
        // Format square feet
        $formatted_square_feet = $square_feet ? number_format( $square_feet ) . ' SF' : null;
        
        // Format monthly rent
        $formatted_monthly_rent = $monthly_rent ? '$' . number_format( $monthly_rent, 2 ) : null;
        
        // Fetch term name for primary listing type
        $primary_type_name = null;
        if ( $primary_type_id ) {
          $term = get_term( $primary_type_id );
          if ( $term && ! is_wp_error( $term ) ) {
            $primary_type_name = $term->name;
          }
        }
        
        return [
          'title'               => $listing['post_title'],
          'url'                 => get_permalink( $listing_id ),
          'square_feet'         => $formatted_square_feet,
          'monthly_rent'        => $formatted_monthly_rent,
          'primary_listing_type' => $primary_type_name,
        ];
      }, $listings );
      
      $current_nyc_time = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );
      
      $request_body = [
        'user_id'        => $user_id,
        'saved_listings' => $listing_data,
        'timestamp'      => $current_nyc_time->format( 'Y-m-d H:i:s' ),
      ];
      
      $payload = [
        'Request_Body' => json_encode($request_body, JSON_UNESCAPED_SLASHES),
        'Request_Type' => 'Saved_Listings',
        'Email'        => wp_get_current_user()->user_email,
      ];
      
      // Send data to Zoho API
      $response = self::send_to_zoho( $payload );
      
      if ( $response['success'] ) {
        error_log( "Successfully synced saved listings for user ID: $user_id" );
      } else {
        error_log( "Failed to sync saved listings for user ID: $user_id. Error: " . json_encode( $response ) );
      }
    }
    
    /**
     * Sends the payload to Zoho CRM API.
     *
     * @param array $payload The data to send to Zoho.
     *
     * @return array The API response.
     */
    private static function send_to_zoho( $payload ) {
      $access_token = MMZohoIntegration::get_access_token();
      
      if ( ! $access_token ) {
        error_log( "Failed to retrieve Zoho access token." );
        
        return [ 'success' => false, 'message' => 'Failed to retrieve Zoho access token.' ];
      }
      
      $api_url      = "https://www.zohoapis.com/crm/v3/API_Requests";
      $max_attempts = 2; // Allow retry on INVALID_TOKEN
      $attempts     = 0;
      
      while ( $attempts < $max_attempts ) {
        $final_payload = [
          'data'    => [ $payload ],
          'trigger' => [ 'workflow' ],
        ];
        
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $api_url );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $final_payload ) );
        
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
          "Authorization: Zoho-oauthtoken $access_token",
          'Content-Type: application/json',
        ] );
        
        $response = curl_exec( $ch );
        $error    = curl_error( $ch );
        curl_close( $ch );
        
        if ( $error ) {
          return [ 'success' => false, 'message' => $error ];
        }
        
        $response_data = json_decode( $response, true );
        
        if ( isset( $response_data['code'] ) && $response_data['code'] === 'INVALID_TOKEN' ) {
          error_log( "INVALID_TOKEN encountered. Refreshing access token..." );
          $access_token = MMZohoIntegration::get_access_token( true ); // Force refresh token
          if ( ! $access_token ) {
            error_log( "Failed to refresh token after INVALID_TOKEN. Aborting." );
            
            return [ 'success' => false, 'message' => 'Failed to refresh token after INVALID_TOKEN.' ];
          }
          $attempts ++;
        } else {
          // Check if response contains success or error codes
          if ( isset( $response_data['data'] ) ) {
            return [ 'success' => true, 'response' => $response_data['data'] ];
          } else {
            return [
              'success' => false,
              'message' => $response_data['message'] ?? 'Unknown error.',
              'details' => $response_data
            ];
          }
        }
      }
      
      return [ 'success' => false, 'message' => 'Persistent INVALID_TOKEN error.' ];
    }
    
    /**
     * MMFavourites constructor.
     *
     * Registers the necessary AJAX actions for managing user favorites, sets up the admin menu for viewing favorites,
     * and includes a function to refactor old favorites.
     */
    public function __construct() {
      add_action( 'wp_ajax_mm_add_to_favourites', [ $this, 'toggle_favourite' ] );
      add_action( 'wp_ajax_nopriv_mm_add_to_favourites', [ $this, 'toggle_favourite' ] );
      add_action( 'wp_ajax_pagination_for_favourites', [ $this, 'get_favourites_with_pagination' ] );
      add_action( 'wp_ajax_nopriv_pagination_for_favourites', [ $this, 'get_favourites_with_pagination' ] );
      add_action( 'admin_menu', function () {
        add_menu_page( 'Users Favorites', 'Users Favorites', 'manage_options', 'users_favorites', [
          $this,
          'user_favorites_template'
        ] );
      } );
      add_action( 'init', [ $this, 'refactor_old_favorites' ] );
    }
    
    /**
     * Toggles a listing as a favorite for the logged-in user.
     */
    public static function toggle_favourite() {
      if ( is_user_logged_in() ) {
        $listing_id      = $_POST['listing_id'];
        $current_user_id = wp_get_current_user()->ID;
        
        $response           = [];
        $response['status'] = self::change_state( $current_user_id, $listing_id );
        
        // Sync saved listings with Zoho
        self::sync_saved_listings_with_zoho( $current_user_id );
        
        echo json_encode( $response );
        wp_die();
      }
    }
    
    /**
     * Adds or removes a listing from the user's favorites.
     *
     * @param int $current_user_id
     * @param int $listing_id
     *
     * @return bool True if the listing was added, false if removed.
     */
    private static function change_state( $current_user_id, $listing_id ) {
      if ( ! self::is_listing_favourite( $current_user_id, $listing_id ) ) {
        self::add_favourite( $current_user_id, $listing_id );
        
        return true;
      } else {
        self::remove_favourite( $current_user_id, $listing_id );
        
        return false;
      }
    }
    
    /**
     * Checks if a string contains combined favorites.
     *
     * @param string $string
     *
     * @return bool
     */
    private static function is_combined_favourites( $string ): bool {
      $searchString = ',';
      
      return ( strpos( $string, $searchString ) !== false );
    }
    
    /**
     * Refactors old favorites by splitting combined favorites into separate entries.
     */
    public static function refactor_old_favorites() {
      if ( is_user_logged_in() ) {
        $current_user_id = wp_get_current_user()->ID;
        $favourites      = self::get_favourites( $current_user_id );
        foreach ( $favourites as $favourite ) {
          if ( self::is_combined_favourites( $favourite ) ) {
            $old_favourites = self::split_combined_favourites( $favourite );
            foreach ( $old_favourites as $old_favourite ) {
              self::add_favourite( $current_user_id, $old_favourite );
            }
            self::remove_favourite( $current_user_id, $favourite );
          }
        }
      }
    }
    
    /**
     * Splits a string of combined favorites into an array.
     *
     * @param string $string
     *
     * @return array
     */
    private static function split_combined_favourites( $string ): array {
      return array_map( 'trim', explode( ',', $string ) );
    }
    
    /**
     * Adds a listing to the user's favorites.
     *
     * @param int $user_id
     * @param int $listing_id
     */
    private static function add_favourite( $user_id, $listing_id ) {
      add_user_meta( $user_id, 'user_favorites', $listing_id );
    }
    
    /**
     * Removes a listing from the user's favorites.
     *
     * @param int $user_id
     * @param int $listing_id
     */
    private static function remove_favourite( $user_id, $listing_id ) {
      delete_user_meta( $user_id, 'user_favorites', $listing_id );
    }
    
    /**
     * Checks if a listing is marked as a favorite by the user.
     *
     * @param int $user_id
     * @param int $listing_id
     *
     * @return bool
     */
    public static function is_listing_favourite( $user_id, $listing_id ): bool {
      if ( is_user_logged_in() ) {
        $favourites = self::get_favourites( $user_id );
        
        return in_array( $listing_id, $favourites );
      } else {
        return false;
      }
    }
    
    /**
     * Retrieves all favorite listings for a user.
     *
     * @param int $user_id
     *
     * @return array
     */
    public static function get_favourites( $user_id ) {
      return get_user_meta( $user_id, 'user_favorites', false );
    }
    
    /**
     * Retrieves paginated favorite listings for a user.
     *
     * @param int $user_id
     * @param int $offset
     * @param int $numberposts
     *
     * @return array
     */
    public static function get_paginated_favourites( $user_id, $offset, $numberposts ): array {
      global $wpdb;
      $sql    = "SELECT meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s LIMIT %d OFFSET %d";
      $result = $wpdb->get_results( $wpdb->prepare( $sql, [
        $user_id,
        'user_favorites',
        $numberposts,
        $offset
      ] ), ARRAY_N );
      $result = array_map( function ( $item ) {
        return (object) [
          'ID' => $item[0]
        ];
      }, $result );
      
      return $result;
    }
    
    /**
     * Handles AJAX request for paginated favorite listings.
     */
    public static function get_favourites_with_pagination() {
      $numberposts              = $_POST['numberposts'];
      $page                     = $_POST['page'];
      $current_page             = $_POST['current_page'];
      $current_user_id          = wp_get_current_user()->ID;
      $liked_favourites_listing = $_POST['listing_id'];
      if ( is_numeric( $liked_favourites_listing ) ) {
        self::change_state( $current_user_id, $liked_favourites_listing );
      }
      $offset           = ( $page * $numberposts ) - $numberposts;
      $total_favourites = self::get_favourites( $current_user_id );
      $favourites       = MMFavourites::get_paginated_favourites( $current_user_id, $offset, $numberposts );
      $pagination       = MetroManhattanHelpers::get_pagination_of_search_result( sizeof( $total_favourites ), $_POST['page'], $numberposts, $current_page );
      $output['cards']  = MetroManhattanHelpers::get_listings_cards( $favourites, false, false, true );
      if ( sizeof( $total_favourites ) > $numberposts ) {
        $output['pagination'] = ( ! isset( $pagination ) ) ? [] : $pagination;
      }
      $output['total'] = sizeof( $total_favourites );
      echo json_encode( $output );
      
      wp_die();
    }
    
    /**
     * Displays the user favorites admin page template.
     */
    public function user_favorites_template() {
      $search = array_key_exists( 's', $_REQUEST ) ? $_REQUEST['s'] : '';
      $table  = new MMFavouritesList();
      $table->prepare_items();
      ?>

        <div class="wrap">
            <h2>Users Favorites</h2>
            <form method="GET">
                <input type="hidden" name="page" value="users_favorites">
            </form>
            <form method="POST">
              <?php $table->display() ?>
            </form>
        </div>
    <?php }
  }
  
  if ( is_user_logged_in() ) {
    $instance = new MMFavourites();
  }
  
  if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
  }
  
  /**
   * Class MMFavouritesList
   *
   * Provides a custom table for displaying user favorites in the WordPress admin area.
   */
  class MMFavouritesList extends WP_List_Table {
    /**
     * MMFavouritesList constructor.
     *
     * Sets up the custom list table by defining the singular and plural labels.
     */
    public function __construct() {
      parent::__construct( [
        'singular' => 'user_favorites',
        'plural'   => 'users_favorites',
        'ajax'     => false,
      ] );
    }
    
    /**
     * Prepares the items for display in the table.
     *
     * Handles sorting, filtering, and pagination.
     */
    public function prepare_items() {
      $order_by = isset( $_GET['orderby'] ) ? $_GET['orderby'] : '';
      $order    = isset( $_GET['order'] ) ? $_GET['order'] : '';
      $search   = isset( $_GET['s'] ) ? $_GET['s'] : '';
      
      $prepare_where = $this->prepare_where();
      
      $this->_column_headers = [ $this->get_columns() ];
      $this->items           = $this->get_table_data( $order_by, $order, $search );
    }
    
    /**
     * Retrieves the data to be displayed in the table.
     *
     * @param string $order_by Column to order by.
     * @param string $order Order direction (ASC/DESC).
     * @param string $search Search query.
     *
     * @return array The table data.
     */
    public function get_table_data( $order_by = '', $order = '', $search = '' ) {
      global $wpdb;
      $sql   = "SELECT u.user_login, um.meta_value AS user_favorites, u.user_email
        FROM $wpdb->usermeta um
        JOIN $wpdb->users u ON um.user_id = u.ID
        WHERE um.meta_key = 'user_favorites'; ";
      $query = $wpdb->get_results( $sql, ARRAY_A );
      
      return $this->combine_favorites( $query );
    }
    
    /**
     * Combines favorites into a structured array by user.
     *
     * @param array $array The raw data array.
     *
     * @return array The structured data array.
     */
    private function combine_favorites( $array ) {
      $output = [];
      
      foreach ( $array as $item ) {
        $username       = $item['user_login'];
        $user_email     = $item['user_email'];
        $user_favorites = $item['user_favorites'];
        
        if ( ! isset( $output[ $username ] ) ) {
          $output[ $username ] = [
            'username'       => $username,
            'user_email'     => $user_email,
            'user_favorites' => []
          ];
        }
        
        $output[ $username ]['user_favorites'][] = $user_favorites;
      }
      
      $output = array_values( $output );
      $output = $this->get_favorites_titles( $output );
      
      return $output;
    }
    
    /**
     * Converts the favorite IDs to their corresponding post titles.
     *
     * @param array $array The array of user favorites.
     *
     * @return array The array with post titles instead of IDs.
     */
    private function get_favorites_titles( $array ) {
      $output = [];
      
      foreach ( $array as $item ) {
        $item['user_favorites'] = join( ', ', array_map( function ( $favourite ) {
          return get_the_title( $favourite );
        }, $item['user_favorites'] ) );
        $output[]               = $item;
      }
      
      return $output;
    }
    
    /**
     * Defines the columns for the list table.
     *
     * @return array The column names.
     */
    public function get_columns() {
      return [
        'username'       => 'Username',
        'user_email'     => 'User email',
        'user_favorites' => 'User favorites'
      ];
    }
    
    /**
     * Handles default column output.
     *
     * @param array $item The current item.
     * @param string $column_name The name of the column.
     *
     * @return string The content for the column.
     */
    public function column_default( $item, $column_name ) {
      switch ( $column_name ) {
        case 'username':
          // Link to the user's profile in the WordPress admin area
          $user_profile_url = admin_url( 'user-edit.php?user_id=' . get_user_by( 'login', $item['username'] )->ID );
          
          return '<a target="_blank" href="' . esc_url( $user_profile_url ) . '">' . esc_html( $item['username'] ) . '</a>';
        
        case 'user_email':
        case 'user_favorites':
          return $item[ $column_name ];
        
        default:
          return 'No Post found';
      }
    }
    
  }

