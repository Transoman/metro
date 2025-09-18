<?php
  // includes/class-search-list-table.php
  
  /**
   * Handles the WP_List_Table display for Search Stats.
   */
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
  }
  
  class Search_List_Table extends WP_List_Table {
    
    public function __construct() {
      parent::__construct( [
        'singular' => 'stat_item',
        'plural'   => 'stat_item',
        'ajax'     => false
      ] );
    }
    
    public function prepare_items() {
      $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
      $this->process_bulk_action();
      $this->items = $this->get_data();
    }
    
    public function prepare_items_without_username() {
      $this->_column_headers = [ $this->get_columns_without_username(), [], $this->get_sortable_columns() ];
      $this->process_bulk_action();
      $this->items = $this->get_data();
    }
    
    public function get_columns_without_username() {
      return [
        'cb'            => '<input type="checkbox" />',
        'created_at'    => 'Date',
        'user_email'    => 'User Email',
        'uses'          => 'Use',
        'locations'     => 'Locations',
        'sizes'         => 'Size',
        'prices'        => 'Rent',
        'geo'           => 'GEO',
        'ip'            => 'IP',
      ];
    }
    
    public function get_columns() {
      return [
        'cb'            => '<input type="checkbox" />',
        'created_at'    => 'Date',
        'display_name'  => 'User Name',
        'user_email'    => 'User Email',
        'uses'          => 'Use',
        'locations'     => 'Locations',
        'sizes'         => 'Size',
        'prices'        => 'Rent',
        'geo'           => 'GEO',
        'ip'            => 'IP',
        'user_id'       => 'User ID',
        'registered_by' => 'Registered By'
      ];
    }
    
    public function get_sortable_columns() {
      return [
        'user_id'    => [ 'user_id', false ],
        'uses'       => [ 'uses', false ],
        'locations'  => [ 'locations', false ],
        'sizes'      => [ 'sizes', false ],
        'prices'     => [ 'prices', false ],
        'ip'         => [ 'ip', false ],
        'geo'        => [ 'geo', false ],
        'created_at' => [ 'created_at', 'DESC' ],
      ];
    }
    
    public function process_bulk_action() {
      global $wpdb;
      $table_name = $wpdb->prefix . 'search_stats';
      
      if ( 'delete' === $this->current_action() ) {
        $ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : [];
        if ( is_array( $ids ) ) {
          $ids = implode( ',', array_map( 'absint', $ids ) );
        }
        if ( ! empty( $ids ) ) {
          $wpdb->query( "DELETE FROM $table_name WHERE id IN($ids)" ); // phpcs:ignore
        }
      }
    }
    
    public function get_data() {
      global $wpdb;
      $table        = $wpdb->prefix . 'search_stats';
      $table_social = $wpdb->prefix . 'social_users';
      
      $prepare_where = $this->prepare_where();
      
      $total = $wpdb->get_var( "SELECT COUNT(*) FROM `$table` $prepare_where" );
      $this->set_pagination_args( [
        'total_items' => $total,
        'per_page'    => 100,
      ] );
      
      $per_page = $this->get_pagination_arg( 'per_page' );
      // WP_List_Table automatically sets a 'paged' query var, but it’s typically from $_REQUEST['paged'].
      $current_page = $this->get_pagenum();
      $offset       = ( $current_page - 1 ) * $per_page;
      
      $order_by = $this->prepare_order_by();
      $items    = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT $table.*, $table_social.type as registered_by
                 FROM $table
                 LEFT JOIN $table_social ON $table_social.social_users_id = $table.user_id
                 $prepare_where
                 $order_by
                 LIMIT %d, %d",
          $offset,
          $per_page
        ),
        ARRAY_A
      );
      
      return array_map( function ( $item ) {
        $item['state']        = json_decode( $item['state'], true );
        $item['display_name'] = 'Guest';
        // $item['user_email']   = '';
        
        if ( ! empty( $item['user_id'] ) ) {
          $user = get_user_by( 'ID', $item['user_id'] );
          if ( $user ) {
            $item['display_name'] = $user->display_name;
            $item['user_email']   = $user->user_email;
            
            // Check if the email contains "-DELETED" and append (deleted)
            if ( strpos( $user->user_email, '-DELETED' ) !== false ) {
              $item['user_email'] .= ' (deleted)';
            }
          } else {
            $item['display_name'] = 'Unknown';
          }
        }
        
        return $item;
      }, is_array( $items ) ? $items : [] );
    }
    
    public function prepare_where() {
      global $wpdb;
      $table = $wpdb->prefix . 'search_stats';
      $where = [];
      
      // Search query
      if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
        $search = sanitize_text_field( $_REQUEST['s'] );
        
        $where[] = $wpdb->prepare( 'JSON_EXTRACT(' . $table . '.state, "$.uses") LIKE "%%%s%%"', $search );
        $where[] = $wpdb->prepare( 'JSON_EXTRACT(' . $table . '.state, "$.locations") LIKE "%%%s%%"', $search );
        $where[] = $wpdb->prepare( 'JSON_EXTRACT(' . $table . '.state, "$.sizes") LIKE "%%%s%%"', $search );
        $where[] = $wpdb->prepare( 'JSON_EXTRACT(' . $table . '.state, "$.prices") LIKE "%%%s%%"', $search );
        $where[] = $wpdb->prepare( 'JSON_EXTRACT(' . $table . '.state, "$.ip") LIKE "%%%s%%"', $search );
        $where[] = $wpdb->prepare( 'JSON_EXTRACT(' . $table . '.state, "$.geo") LIKE "%%%s%%"', $search );
        $where[] = $wpdb->prepare( $table . '.email LIKE "%%%s%%"', $search );
      }
      
      if ( ! empty( $_GET['session_id'] ) ) {
        $session_id = sanitize_text_field( $_GET['session_id'] );
        $where[]    = $wpdb->prepare( $table . '.session_id = %s', $session_id );
      }
      
      if ( empty( $where ) ) {
        return '';
      }
      
      return 'WHERE ' . join( ' OR ', $where );
    }
    
    public function prepare_order_by() {
      global $wpdb;
      $table = $wpdb->prefix . 'search_stats';
      
      // By default
      $order_by = 'ORDER BY created_at DESC';
      
      if (
        isset( $_REQUEST['orderby'] ) &&
        isset( $this->get_sortable_columns()[ $_REQUEST['orderby'] ] )
      ) {
        $field = sanitize_text_field( $_REQUEST['orderby'] );
        $order = ( isset( $_REQUEST['order'] ) && strtolower( $_REQUEST['order'] ) === 'desc' ) ? 'DESC' : 'ASC';
        
        switch ( $field ) {
          case 'uses':
          case 'locations':
          case 'sizes':
          case 'prices':
          case 'ip':
          case 'geo':
            $order_by = "ORDER BY JSON_EXTRACT($table.state, '$.$field') $order";
            break;
          default:
            $order_by = "ORDER BY $table.$field $order";
            break;
        }
      }
      
      return $order_by;
    }
    
    public function column_cb( $item ) {
      return sprintf(
        '<input type="checkbox" name="id[]" value="%s" />',
        esc_attr( $item['id'] )
      );
    }
    
    public function column_default( $item, $column_name ) {
      switch ( $column_name ) {
        case 'display_name':
          if ( ! empty( $item['user_id'] ) ) {
            return sprintf(
              '<a href="%s">%s</a>',
              esc_url( get_edit_user_link( $item['user_id'] ) ),
              esc_html( $item[ $column_name ] )
            );
          } else {
            return esc_html( $item[ $column_name ] );
          }
        
        case 'user_email':
          if ( ! empty( $item['user_id'] ) ) {
            $email = esc_attr( $item[ $column_name ] );
            
            return "<a href='mailto:$email'>$email</a>";
          } else {
            if ( $column_name === 'user_email' ) {
              return esc_html( $item[ 'email' ] );
            } else {
              return esc_html( $item[ $column_name ] );
            }
          }
        
        case 'uses':
        case 'locations':
        case 'sizes':
        case 'prices':
        case 'ip':
        case 'geo':
          // These come from the JSON state array
          return ! empty( $item['state'][ $column_name ] )
            ? esc_html( htmlspecialchars_decode( $item['state'][ $column_name ] ) )
            : '';
        case 'created_at':
          $stored_time  = $item['created_at'];
          $halifax_tz   = new DateTimeZone('America/Halifax');
          $ny_tz        = new DateTimeZone('America/New_York');
          
          $date_obj = new DateTime($stored_time, $halifax_tz);
          $date_obj->setTimezone($ny_tz);
          
          return $date_obj->format('Y-m-d H:i:s');
        default:
          return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
      }
    }
    
    public function get_bulk_actions() {
      return [
        'delete' => 'Delete'
      ];
    }
  }
