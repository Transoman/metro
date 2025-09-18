<?php
  /**
   * Plugin Name: CF7 Submissions
   * Description: Monitoring filled Contact Form 7 forms (and one custom registration form) via AJAX
   * Version: 1.0
   * Author: Nemanja Tanaskovic
   * Text Domain: cf7-submissions
   */
  
  // cf7-submissions.php
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  /**
   * Enqueue admin scripts
   */
  function cf7_admin_enqueue_scripts() {
    $script_url = plugin_dir_url( __FILE__ ) . 'cf7-admin.js';
    wp_enqueue_script(
      'cf7-admin-js',
      $script_url,
      array(),
      '1.0',
      true
    );
    
    $style_url = plugin_dir_url( __FILE__ ) . 'cf7-admin.css';
    wp_enqueue_style(
      'cf7-admin-css',
      $style_url,
      array(),
      '1.0'
    );
  }
  
  add_action( 'admin_enqueue_scripts', 'cf7_admin_enqueue_scripts' );
  
  /**
   * Add Admin Menu link
   */
  add_action( 'admin_menu', function () {
    add_menu_page(
      'CF7 Submissions',
      'CF7',
      'manage_options',
      'cf7-submissions',
      'cf7_submissions_admin_page'
    );
  } );
  
  /**
   * Admin page to view partial submissions
   */
  function cf7_submissions_admin_page() {
    global $wpdb;
    
    $vxcf_leads        = $wpdb->prefix . 'vxcf_leads';
    $vxcf_leads_detail = $wpdb->prefix . 'vxcf_leads_detail';
    $email             = isset( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : '';
    $paged             = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $per_page          = 50;
    $offset            = ( $paged - 1 ) * $per_page;
    
    // Step 1: Field Mapping
    $field_mapping = [
      'email'       => [ 'email', 'simple-form-email', 'contact-email', 'your-email' ],
      'phone'       => [ 'phone', 'contact-phone' ],
      'message'     => [ 'message', 'contact-message' ],
      'user-name'   => [ 'user-name', 'contact-firstname', 'first-name' ],
      'page-name'   => [ 'page-name' ],
      'address'     => [ 'address' ],
      'square-feet' => [ 'square-feet' ],
      'suite-floor' => [ 'suite-floor' ],
    ];
    
    // Step 2: Fetch Paginated `lead_id` Values
    $lead_id_query = "
        SELECT id
        FROM $vxcf_leads
        WHERE 1=1
    ";
    
    if ( ! empty( $email ) ) {
      $lead_id_query .= $wpdb->prepare( "
            AND id IN (
                SELECT DISTINCT(lead_id)
                FROM $vxcf_leads_detail
                WHERE value LIKE %s
            )
        ", '%' . $wpdb->esc_like( $email ) . '%' );
    }
    
    // Apply pagination
    $total_items = $wpdb->get_var( str_replace( "SELECT id", "SELECT COUNT(id)", $lead_id_query ) );
    $total_pages = ceil( $total_items / $per_page );
    
    $lead_id_query .= " ORDER BY id ASC LIMIT %d OFFSET %d";
    $lead_ids      = $wpdb->get_col( $wpdb->prepare( $lead_id_query, $per_page, $offset ) );
    
    // Step 3: Fetch Detailed Data for Paginated `lead_id` Values
    $grouped_data = [];
    if ( ! empty( $lead_ids ) ) {
      $placeholders    = implode( ',', array_fill( 0, count( $lead_ids ), '%d' ) );
      $details_query   = "
            SELECT *
            FROM $vxcf_leads_detail
            WHERE lead_id IN ($placeholders)
            ORDER BY lead_id ASC, id ASC
        ";
      $details_results = $wpdb->get_results( $wpdb->prepare( $details_query, ...$lead_ids ), ARRAY_A );
      
      // Group results by `lead_id` and map fields
      foreach ( $details_results as $row ) {
        $lead_id     = $row['lead_id'];
        $field_name  = $row['name'];
        $field_value = $row['value'];
        
        foreach ( $field_mapping as $standard_field => $possible_names ) {
          if ( in_array( $field_name, $possible_names, true ) ) {
            $grouped_data[ $lead_id ][ $standard_field ] = $field_value;
            break;
          }
        }
      }
    }
    
    // Step 6: Render HTML
    ?>
      <div class="wrap">
          <h1>CF7 Submissions</h1>

          <form method="get" style="margin-bottom: 15px;">
              <input type="hidden" name="page" value="cf7-submissions"/>
              <input type="text" name="email" value="<?php echo esc_attr( $email ); ?>"
                     placeholder="Filter by email..."/>
              <input type="submit" class="button button-primary" value="Search"/>
          </form>

          <table class="widefat fixed">
              <thead>
              <tr>
                  <th>Lead ID</th>
                  <th>User Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Message</th>
                  <th>Page Name</th>
                  <th>Address</th>
                  <th>Square Feet</th>
                  <th>Suite Floor</th>
              </tr>
              </thead>
              <tbody>
              <?php if ( ! empty( $grouped_data ) ) : ?>
                <?php foreach ( $grouped_data as $lead_id => $fields ) : ?>
                      <tr>
                          <td><?php echo esc_html( $lead_id ); ?></td>
                          <td><?php echo esc_html( $fields['user-name'] ?? '' ); ?></td>
                          <td><?php echo esc_html( $fields['email'] ?? '' ); ?></td>
                          <td><?php echo esc_html( $fields['phone'] ?? '' ); ?></td>
                          <td><?php echo esc_html( $fields['message'] ?? '' ); ?></td>
                          <td><?php echo esc_html( $fields['page-name'] ?? '' ); ?></td>
                          <td><?php echo esc_html( $fields['address'] ?? '' ); ?></td>
                          <td><?php echo esc_html( $fields['square-feet'] ?? '' ); ?></td>
                          <td><?php echo esc_html( $fields['suite-floor'] ?? '' ); ?></td>
                      </tr>
                <?php endforeach; ?>
              <?php else : ?>
                  <tr>
                      <td colspan="9">No records found.</td>
                  </tr>
              <?php endif; ?>
              </tbody>
          </table>

          <div class="tablenav bottom" style="margin-top:20px;">
              <div class="tablenav-pages">
                <?php
                  $base_url = admin_url( 'admin.php?page=cf7-submissions' );
                  if ( ! empty( $email ) ) {
                    $base_url .= '&email=' . urlencode( $email );
                  }
                  
                  // Pagination links
                  if ( $paged > 1 ) {
                    echo '<a class="button" href="' . esc_url( $base_url . '&paged=' . ( $paged - 1 ) ) . '">&laquo; Previous</a> ';
                  }
                  
                  echo ' Page ' . $paged . ' of ' . $total_pages . ' ';
                  
                  if ( $paged < $total_pages ) {
                    echo '<a class="button" href="' . esc_url( $base_url . '&paged=' . ( $paged + 1 ) ) . '">Next &raquo;</a>';
                  }
                ?>
              </div>
          </div>
      </div>
    <?php
  }
