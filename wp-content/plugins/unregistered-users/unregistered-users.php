<?php
  /**
   * Plugin Name:       Unregistered Users
   * Plugin URI:        https://www.metro-manhattan.com/
   * Description:       Displays unregistered users (session_id is not null, user_id < 1) and provides links to their Search Stats & Uncompleted Forms
   * Version:           1.0.3
   * Author:            Metro
   * Author URI:        https://www.metro-manhattan.com/
   */
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
  }
  
  /**
   * Add a new top-level admin menu page called "Unregistered Users".
   */
  add_action( 'admin_menu', 'uu_add_unregistered_users_menu' );
  function uu_add_unregistered_users_menu() {
    add_menu_page(
      'Unregistered Users',
      'Unregistered Users',
      'manage_options',
      'unregistered-users',
      'uu_render_unregistered_users',
      'dashicons-admin-users',
      70
    );
  }
  
  /**
   * Render the "Unregistered Users" page content.
   */
  function uu_render_unregistered_users() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'search_stats';
    
    // We only want rows where session_id is not null AND user_id < 1
    // but we also want only the *latest* row per session_id (similar to a GROUP BY session_id).
    // One approach is a self-LEFT-JOIN trick that returns only the “most recent” row for each session_id.
    // The condition s2.id IS NULL means no row with a bigger ID for that same session_id exists,
    // i.e. s is the row with the highest ID for that session_id.
    $query = $wpdb->prepare( "
        SELECT s.*
        FROM $table_name s
        LEFT JOIN $table_name s2
            ON (s.session_id = s2.session_id AND s.id < s2.id)
        WHERE s2.id IS NULL
          AND s.session_id IS NOT NULL
          AND s.session_id != ''
          AND s.email IS NOT NULL
          AND s.email != ''
          AND s.user_id < %d
        ORDER BY s.created_at DESC
    ", 1 );
    
    $rows = $wpdb->get_results( $query );
    
    ?>
      <div class="wrap">
          <h1>Unregistered Users</h1>
          <table class="widefat fixed striped">
              <thead>
              <tr>
                  <th>Session ID</th>
                  <th>Email</th>
                  <th>Search Stats</th>
                  <th>Uncompleted Forms</th>
                  <th>Completed Forms</th>
              </tr>
              </thead>
              <tbody>
              <?php
                if ( ! empty( $rows ) ) {
                  foreach ( $rows as $row ) {
                    $session_id = esc_html( $row->session_id );
                    $email      = ! empty( $row->email ) ? esc_html( $row->email ) : '';
                    
                    // Link to the existing Search Stats page, but pass session_id
                    $search_stats_url = add_query_arg(
                      [
                        'page'       => 'search-stats',
                        'session_id' => urlencode( $session_id ),
                      ],
                      admin_url( 'admin.php' )
                    );
                    
                    // Link to Uncompleted Forms page with session_id
                    $uncompleted_forms_url = add_query_arg(
                      [
                        'page'       => 'cf7-partial-submissions',
                        'session_id' => urlencode( $session_id ),
                      ],
                      admin_url( 'admin.php' )
                    );
                    
                    // Link to Completed Forms page with email_id
                    $completed_forms_url = add_query_arg(
                      [
                        'page'     => 'cf7-submissions',
                        'email' => $email,
                      ],
                      admin_url( 'admin.php' )
                    );
                    ?>
                      <tr>
                          <td><?php echo $session_id; ?></td>
                          <td><?php echo $email; ?></td>
                          <td>
                              <a class="button" href="<?php echo esc_url( $search_stats_url ); ?>">
                                  View Search Stats
                              </a>
                          </td>
                          <td>
                              <a class="button" href="<?php echo esc_url( $uncompleted_forms_url ); ?>">
                                  View Uncompleted Forms
                              </a>
                          </td>
                          <td>
                              <a class="button" href="<?php echo esc_url( $completed_forms_url ); ?>">
                                  View Completed Forms
                              </a>
                          </td>
                      </tr>
                    <?php
                  }
                } else {
                  ?>
                    <tr>
                        <td colspan="4">No unregistered users found.</td>
                    </tr>
                  <?php
                }
              ?>
              </tbody>
          </table>
      </div>
    <?php
  }
