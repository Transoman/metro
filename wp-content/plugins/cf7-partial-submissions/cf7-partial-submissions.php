<?php
  /**
   * Plugin Name: CF7 Partial Submissions
   * Description: Monitoring incompletely filled Contact Form 7 forms (and one custom registration form) via AJAX
   * Version: 2.7
   * Author: Nemanja Tanaskovic
   * Text Domain: cf7-partial-submissions
   */
  
  // cf7-partial-submissions.php
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  /**
   * On plugin activation: Create DB table if not exists.
   */
  function cf7_partial_submissions_activate() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'cf7_partial_submissions';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id VARCHAR(255) NOT NULL,
        form_id VARCHAR(255) NOT NULL,
        field_data LONGTEXT NOT NULL,
        last_updated DATETIME NOT NULL,
        status VARCHAR(20) DEFAULT 'partial' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
  }
  
  register_activation_hook( __FILE__, 'cf7_partial_submissions_activate' );
  
  /**
   * Enqueue front-end scripts
   */
  function cf7_partial_submissions_enqueue_scripts() {
    $script_url = plugin_dir_url( __FILE__ ) . 'cf7-partial-submissions.js';
    
    wp_enqueue_script(
      'cf7-partial-submissions-js',
      $script_url,
      array(),
      '2.7',
      true
    );
    
    wp_localize_script(
      'cf7-partial-submissions-js',
      'cf7PartialSubmissions',
      array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'cf7_partial_submissions_nonce' ),
      )
    );
  }
  
  add_action( 'wp_enqueue_scripts', 'cf7_partial_submissions_enqueue_scripts' );
  
  /**
   * Enqueue admin scripts
   */
  function cf7_partial_admin_enqueue_scripts() {
    $script_url = plugin_dir_url( __FILE__ ) . 'cf7-partial-admin.js';
    wp_enqueue_script(
      'cf7-partial-admin-js',
      $script_url,
      array(),
      '2.7',
      true
    );
    
    $style_url = plugin_dir_url( __FILE__ ) . 'cf7-partial-admin.css';
    wp_enqueue_style(
      'cf7-partial-admin-css',
      $style_url,
      array(),
      '2.7'
    );
  }
  
  add_action( 'admin_enqueue_scripts', 'cf7_partial_admin_enqueue_scripts' );
  
  /**
   * AJAX handler: Save partial submissions
   * --------------------------------------
   * Skips saving if email is not provided.
   */
  function cf7_partial_submissions_save_data() {
    // Debug log if needed:
    // error_log( 'Request received: ' . print_r( $_POST, true ) );
    
    // Verify nonce
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'cf7_partial_submissions_nonce' ) ) {
      wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
    }
    
    $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( $_POST['session_id'] ) : '';
    $form_id    = isset( $_POST['form_id'] ) ? sanitize_text_field( $_POST['form_id'] ) : '';
    $field_data = isset( $_POST['field_data'] ) ? wp_unslash( $_POST['field_data'] ) : '';
    
    // Must have a session & form ID
    if ( empty( $session_id ) || empty( $form_id ) ) {
      wp_send_json_error( array( 'message' => 'Missing session_id or form_id' ) );
    }
    
    // Decode the JSON to see if we have an email
    $decoded = json_decode( $field_data, true );
    if ( ! is_array( $decoded ) ) {
      // If the field_data is invalid JSON, skip
      wp_send_json_error( array( 'message' => 'Invalid field_data JSON' ) );
    }
    
    // Look for an email in known CF7 fields OR in custom registration fields
    // (We add the 'mm_registration_email' field_name as well to the check)
    $possible_email_fields = array( 'contact-email', 'email', 'your-email', 'mm_registration_email' );
    $email_found           = '';
    foreach ( $possible_email_fields as $field_name ) {
      if ( ! empty( $decoded[ $field_name ] ) ) {
        $email_found = sanitize_email( $decoded[ $field_name ] );
        break;
      }
    }
    
    // If no email found, skip saving
    if ( empty( $email_found ) ) {
      if ( ! isset( $decoded['message'] ) || $decoded['message'] == '' ) {
        if ( ! isset( $decoded['contact-message'] ) || $decoded['contact-message'] == '' ) {
          if ( ! isset( $decoded['phone'] ) || $decoded['phone'] == '' ) {
            if ( ! isset( $decoded['contact-phone'] ) || $decoded['contact-phone'] == '' ) {
              wp_send_json_success( array( 'message' => 'Not enough data; partial submission not saved' ) );
            }
          }
        }
      }
    }
    
    // (Optional) If you also want to update email in some custom table search_stats
    cia_update_email_by_session_id( $session_id, $email_found );
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_partial_submissions';
    
    if ($form_id === 'login_registration_form') {
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name
         WHERE session_id = %s
           AND form_id = %s
           AND status = 'partial'",
        $session_id,
        $form_id
      ));
      
      if ($existing) {
        $wpdb->delete(
          $table_name,
          array(
            'session_id' => $session_id,
            'form_id'    => $form_id,
          ),
          array('%s', '%s')
        );
      } else {
        $wpdb->insert(
          $table_name,
          array(
            'session_id'   => $session_id,
            'form_id'      => $form_id,
            'field_data'   => $field_data,
            'last_updated' => current_time('mysql'),
            'status'       => 'partial',
          ),
          array('%s', '%s', '%s', '%s', '%s')
        );
      }
      
      wp_send_json_success(array('message' => 'Partial data saved for login/registration form.'));
    }
    
    // Check if already exist partial with same session_id + form_id + status=partial
    $existing = $wpdb->get_var( $wpdb->prepare(
      "SELECT id FROM $table_name
         WHERE session_id = %s
           AND form_id = %s
           AND status = 'partial'",
      $session_id,
      $form_id
    ) );
    
    // Update or insert
    if ( $existing ) {
      $wpdb->update(
        $table_name,
        array(
          'field_data'   => $field_data,
          'last_updated' => current_time( 'mysql' ),
        ),
        array( 'id' => $existing ),
        array( '%s', '%s' ),
        array( '%d' )
      );
    } else {
      $wpdb->insert(
        $table_name,
        array(
          'session_id'   => $session_id,
          'form_id'      => $form_id,
          'field_data'   => $field_data,
          'last_updated' => current_time( 'mysql' ),
          'status'       => 'partial',
        ),
        array( '%s', '%s', '%s', '%s', '%s' )
      );
    }
    
    wp_send_json_success( array( 'message' => 'Data saved' ) );
  }
  
  add_action( 'wp_ajax_cf7_partial_submissions_save_data', 'cf7_partial_submissions_save_data' );
  add_action( 'wp_ajax_nopriv_cf7_partial_submissions_save_data', 'cf7_partial_submissions_save_data' );
  
  /**
   * On successful CF7 form submission: mark partial as completed
   */
  add_action( 'wpcf7_mail_sent', function ( $contact_form ) {
    $form_id    = $contact_form->id();
    $session_id = isset( $_POST['cf7_session_id'] ) ? sanitize_text_field( $_POST['cf7_session_id'] ) : '';
    if ( empty( $session_id ) ) {
      return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_partial_submissions';
    
    $wpdb->update(
      $table_name,
      array(
        'status'       => 'completed',
        'last_updated' => current_time( 'mysql' ),
      ),
      array(
        'session_id' => $session_id,
        'form_id'    => $form_id,
        'status'     => 'partial'
      ),
      array( '%s', '%s' ),
      array( '%s', '%s', '%s' )
    );
  } );
  
  
  /**
   * NEW: Mark partial submission as completed when user successfully registers
   * -------------------------------------------------------------------------
   *
   * Ako vaš custom proces registracije *ne* pokreće `user_register` hook,
   * onda ovaj kod možda neće “uhvatiti” dovršenu registraciju.
   * U tom slučaju, pozovite ručno identičan update unutar vaše AJAX callback funkcije
   * posle uspele registracije, npr.:
   *
   *     cf7_partial_mark_custom_registration_completed($_POST['session_id']);
   */
  add_action( 'user_register', 'cf7_partial_mark_custom_registration_completed' );
  function cf7_partial_mark_custom_registration_completed( $user_id ) {
    // Ako vaša forma prosleđuje session_id u $_POST['session_id']:
    if ( ! empty( $_POST['session_id'] ) ) {
      global $wpdb;
      $table_name = $wpdb->prefix . 'cf7_partial_submissions';
      $session_id = sanitize_text_field( $_POST['session_id'] );
      
      // Pretpostavimo da form_id = 'registration_form'
      // (Isto onako kako smo zadali u JS kodu)
      $wpdb->update(
        $table_name,
        array(
          'status'       => 'completed',
          'last_updated' => current_time( 'mysql' ),
        ),
        array(
          'session_id' => $session_id,
          'form_id'    => 'registration_form',
          'status'     => 'partial'
        ),
        array( '%s', '%s' ),
        array( '%s', '%s', '%s' )
      );
    }
  }
  
  /**
   * Add Admin Menu link
   */
  add_action( 'admin_menu', function () {
    add_menu_page(
      'CF7 Partial Submissions',
      'CF7 Partial',
      'manage_options',
      'cf7-partial-submissions',
      'cf7_partial_submissions_admin_page'
    );
  } );
  
  /**
   * Maybe export CSV
   */
  add_action( 'admin_init', 'cf7_partial_submissions_maybe_export_csv' );
  function cf7_partial_submissions_maybe_export_csv() {
    if (
      isset( $_GET['page'] ) && $_GET['page'] === 'cf7-partial-submissions' &&
      isset( $_GET['export'] ) && $_GET['export'] === 'csv'
    ) {
      $search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
      cf7_partial_submissions_export_csv( $search_term );
      exit;
    }
  }
  
  /**
   * Admin page to view partial submissions
   */
  function cf7_partial_submissions_admin_page() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'cf7_partial_submissions';
    
    $search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
    $paged       = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    
    // Grab all CF7 forms for name mapping
    // (If you want to see form titles for the custom "registration_form", you might handle that separately.)
    $forms       = $wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'wpcf7_contact_form'" );
    $form_titles = [];
    foreach ( $forms as $form ) {
      $form_titles[ $form->ID ] = $form->post_title;
    }
    
    // We only show partial
    $where_search = " WHERE status = 'partial' ";
    if ( ! empty( $search_term ) ) {
      $like_term    = '%' . $wpdb->esc_like( $search_term ) . '%';
      $where_search .= $wpdb->prepare( " AND field_data LIKE %s ", $like_term );
    }
    
    if ( ! empty( $_GET['session_id'] ) ) {
      $session_id   = sanitize_text_field( $_GET['session_id'] );
      $where_search .= $wpdb->prepare( ' AND session_id = %s', $session_id );
    }
    
    $per_page     = 50;
    $offset       = ( $paged - 1 ) * $per_page;
    $total_items  = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name $where_search" );
    $total_pages  = ceil( $total_items / $per_page );
    $delete_nonce = wp_create_nonce( 'cf7_bulk_delete_nonce' );
    
    // Admin notices
    if ( isset( $_GET['message'] ) && $_GET['message'] === 'deleted' ) {
      echo '<div class="updated notice is-dismissible"><p>Record deleted successfully.</p></div>';
    } elseif ( isset( $_GET['message'] ) && $_GET['message'] === 'bulk_deleted' ) {
      echo '<div class="updated notice is-dismissible"><p>Selected records have been deleted successfully.</p></div>';
    }
    ?>
      <div class="wrap">
          <h1>CF7 Partial Submissions</h1>

          <form method="get" style="margin-bottom: 15px;">
              <input type="hidden" name="page" value="cf7-partial-submissions"/>
              <input type="text" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="Search..."/>
              <input type="submit" class="button button-primary" value="Search"/>
              <a class="button" style="margin-left:10px;"
                 href="<?php
                   echo esc_url(
                     admin_url(
                       'admin.php?page=cf7-partial-submissions&export=csv&s=' . urlencode( $search_term )
                     )
                   );
                 ?>"
              >Export CSV</a>
          </form>

          <!-- Bulk Deletion Form Start -->
          <form method="post">
              <!-- Nonce for security -->
              <input type="hidden" name="cf7_bulk_delete_nonce" value="<?php echo esc_attr( $delete_nonce ); ?>"/>
              <!-- Keep track of page for reloading the same admin page after post -->
              <input type="hidden" name="page" value="cf7-partial-submissions"/>

              <table class="widefat fixed">
                  <thead>
                  <tr>
                      <th style="width: 2%;">
                          <input type="checkbox" id="cb-select-all"/>
                      </th>
                      <th class="sortable" data-column="id">ID <span class="sort-icon"></span></th>
                      <th class="sortable" data-column="form_name">Form Name <span class="sort-icon"></span></th>
                      <th class="sortable" data-column="email">Email <span class="sort-icon"></span></th>
                      <th class="sortable" data-column="name">Name <span class="sort-icon"></span></th>
                      <th class="sortable" data-column="message">Message <span class="sort-icon"></span></th>
                      <th class="sortable" data-column="phone">Phone <span class="sort-icon"></span></th>
                      <th class="sortable" data-column="square_feet">Square Foot <span class="sort-icon"></span></th>
                      <th class="sortable" data-column="suite_floor">Suite Floor <span class="sort-icon"></span></th>
                      <th class="sortable" data-column="company">Company <span class="sort-icon"></span></th>
                      <th class="sortable" data-column="address">Address <span class="sort-icon"></span></th>
                      <th class="sortable" data-column="last_updated">Last Updated <span class="sort-icon"></span></th>
                      <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
                    // Query partial submissions
                    $results = $wpdb->get_results(
                      $wpdb->prepare(
                        "SELECT * FROM $table_name
                           $where_search
                           ORDER BY last_updated DESC
                           LIMIT %d, %d",
                        $offset,
                        $per_page
                      )
                    );
                    
                    // Helper function to render truncated message
                    if ( ! function_exists( 'cf7_render_truncated_message' ) ) {
                      function cf7_render_truncated_message( $full_message, $max_len = 60 ) {
                        $escaped_full = esc_html( $full_message );
                        if ( mb_strlen( $escaped_full ) <= $max_len ) {
                          return '<span class="message-short">' . $escaped_full . '</span>';
                        }
                        $truncated = mb_substr( $escaped_full, 0, $max_len ) . '...';
                        
                        $html = '<span class="message-short">' . $truncated . '</span>';
                        $html .= '<span class="message-full" style="display:none;">' . $escaped_full . '</span>';
                        $html .= ' <button type="button" class="read-more-button">Read More</button>';
                        
                        return $html;
                      }
                    }
                    
                    foreach ( $results as $row ) :
                      $field_data = json_decode( $row->field_data, true );
                  
                      if ( ! isset( $field_data['email'] ) || $field_data['email'] == '') {
                        if ( ! isset( $field_data['message'] ) || $field_data['message'] == '' ) {
                          if ( ! isset( $field_data['contact-message'] ) || $field_data['contact-message'] == '' ) {
                            if ( ! isset( $field_data['phone'] ) || $field_data['phone'] == '' ) {
                              if ( ! isset( $field_data['contact-phone'] ) || $field_data['contact-phone'] == '' ) {
                                if ( ! isset( $field_data['user-name'] ) || $field_data['user-name'] == '' ) {
                                  continue;
                                }
                              }
                            }
                          }
                        }
                      }
                    
                      // Derive email
                      $email = '';
                      if ( isset( $field_data['contact-email'] ) ) {
                        $email = $field_data['contact-email'];
                      } elseif ( isset( $field_data['email'] ) ) {
                        $email = $field_data['email'];
                      } elseif ( isset( $field_data['your-email'] ) ) {
                        $email = $field_data['your-email'];
                      } elseif ( isset( $field_data['mm_registration_email'] ) ) {
                        $email = $field_data['mm_registration_email'];
                      }
                      
                      // Derive name
                      $first_name = '';
                      if ( isset( $field_data['contact-firstname'] ) ) {
                        $first_name = $field_data['contact-firstname'];
                      } elseif ( isset( $field_data['user-name'] ) ) {
                        $first_name = $field_data['user-name'];
                      } elseif ( isset( $field_data['first-name'] ) ) {
                        $first_name = $field_data['first-name'];
                      } elseif ( isset( $field_data['mm_registration_first_name'] ) ) {
                        $first_name = $field_data['mm_registration_first_name'];
                      }
                      
                      $last_name = '';
                      if ( isset( $field_data['contact-lastname'] ) ) {
                        $last_name = $field_data['contact-lastname'];
                      } elseif ( isset( $field_data['last-name'] ) ) {
                        $last_name = $field_data['last-name'];
                      } elseif ( isset( $field_data['mm_registration_last_name'] ) ) {
                        $last_name = $field_data['mm_registration_last_name'];
                      }
                      
                      $full_name = trim( $first_name . ' ' . $last_name );
                      
                      // Derive message
                      $message = '';
                      if ( isset( $field_data['contact-message'] ) ) {
                        $message = $field_data['contact-message'];
                      } elseif ( isset( $field_data['message'] ) ) {
                        $message = $field_data['message'];
                      }
                      
                      // Derive phone
                      $phone = '';
                      if ( isset( $field_data['phone'] ) ) {
                        $phone = $field_data['phone'];
                      } elseif ( isset( $field_data['your-tel'] ) ) {
                        $phone = $field_data['your-tel'];
                      }
                      
                      // Derive other fields
                      $square_feet = isset( $field_data['square-feet'] ) ? $field_data['square-feet'] : '';
                      $suite_floor = isset( $field_data['suite-floor'] ) ? $field_data['suite-floor'] : '';
                      $company     = '';
                      if ( isset( $field_data['your-company'] ) ) {
                        $company = $field_data['your-company'];
                      } elseif ( isset( $field_data['mm_registration_company'] ) ) {
                        $company = $field_data['mm_registration_company'];
                      }
                      $address = isset( $field_data['address'] ) ? $field_data['address'] : '';
                      
                      // Attempt to get form name if it's CF7, otherwise maybe show the raw form_id
                      $form_id   = $row->form_id;
                      $form_name = ( isset( $form_titles[ $form_id ] ) )
                        ? $form_titles[ $form_id ]
                        : $form_id; // if it's "registration_form", it will show that
                      
                      if ( $form_name === 'registration_form' ) {
                        $form_name = 'Registration form';
                      }
                      
                      // Build truncated message HTML
                      $message_html = cf7_render_truncated_message( $message, 60 );
                      ?>
                        <tr>
                            <!-- Bulk checkbox per row -->
                            <td>
                                <input type="checkbox" class="record-checkbox" name="record_ids[]"
                                       value="<?php echo esc_attr( $row->id ); ?>"/>
                            </td>
                            <td><?php echo esc_html( $row->id ); ?></td>
                            <td><?php echo esc_html( $form_name ); ?></td>
                            <td><?php echo esc_html( $email ); ?></td>
                            <td><?php echo esc_html( $full_name ); ?></td>
                            <td><?php echo $message_html; ?></td>
                            <td><?php echo esc_html( $phone ); ?></td>
                            <td><?php echo esc_html( $square_feet ); ?></td>
                            <td><?php echo esc_html( $suite_floor ); ?></td>
                            <td><?php echo esc_html( $company ); ?></td>
                            <td><?php echo esc_html( $address ); ?></td>
                            <td><?php echo esc_html( $row->last_updated ); ?></td>

                            <!-- Single delete link -->
                            <td>
                                <a
                                        href="<?php echo esc_url(
                                          add_query_arg(
                                            array(
                                              'page'             => 'cf7-partial-submissions',
                                              'action'           => 'cf7_delete_single',
                                              'record_id'        => $row->id,
                                              'cf7_delete_nonce' => wp_create_nonce( 'cf7_delete_nonce_' . $row->id ),
                                            ),
                                            admin_url( 'admin.php' )
                                          )
                                        ); ?>"
                                        class="button button-secondary"
                                        onclick="return confirm('Are you sure you want to delete this record?');"
                                >
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                  </tbody>
              </table>

              <!-- Bulk action controls -->
              <div class="tablenav bottom" style="margin-top:20px;">
                  <div class="alignleft actions">
                      <select name="bulk_action">
                          <option value="">Bulk actions</option>
                          <option value="bulk_delete">Delete</option>
                      </select>
                      <input type="submit" class="button button-primary" value="Apply"
                             onclick="return confirm('Are you sure you want to delete the selected records?');"/>
                  </div>
                  <div class="tablenav-pages">
                    <?php
                      $base_url = admin_url( 'admin.php?page=cf7-partial-submissions' );
                      if ( ! empty( $search_term ) ) {
                        $base_url .= '&s=' . urlencode( $search_term );
                      }
                      
                      // Prev link
                      if ( $paged > 1 ) {
                        $prev_page = $paged - 1;
                        echo '<a class="button" href="' . esc_url( $base_url . '&paged=' . $prev_page ) . '">&laquo; Previous</a> ';
                      }
                      
                      echo ' Page ' . $paged . ' of ' . $total_pages . ' ';
                      
                      // Next link
                      if ( $paged < $total_pages ) {
                        $next_page = $paged + 1;
                        echo '<a class="button" href="' . esc_url( $base_url . '&paged=' . $next_page ) . '">Next &raquo;</a>';
                      }
                    ?>
                  </div>
              </div>
          </form>
      </div>
      <!-- JavaScript for “Select All” checkbox -->
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const selectAll = document.getElementById('cb-select-all');
          if (selectAll) {
            selectAll.addEventListener('change', function () {
              const checkboxes = document.querySelectorAll('.record-checkbox');
              checkboxes.forEach(chk => chk.checked = this.checked);
            });
          }
        });
      </script>
    <?php
  }
  
  /**
   * Export partial submissions to CSV
   */
  function cf7_partial_submissions_export_csv( $search_term = '' ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_partial_submissions';
    
    $where_search = " WHERE status = 'partial' ";
    if ( ! empty( $search_term ) ) {
      $like_term    = '%' . $wpdb->esc_like( $search_term ) . '%';
      $where_search .= $wpdb->prepare( " AND field_data LIKE %s ", $like_term );
    }
    
    $rows = $wpdb->get_results( "SELECT * FROM $table_name $where_search ORDER BY last_updated DESC" );
    
    header( 'Content-Type: text/csv; charset=UTF-8' );
    header( 'Content-Disposition: attachment; filename="cf7-partial-submissions.csv"' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );
    
    $output = fopen( 'php://output', 'w' );
    
    $headers = array(
      'ID',
      'Form ID',
      'Email',
      'Name',
      'Message',
      'Phone',
      'Square Feet',
      'Suite Floor',
      'Company',
      'Address',
      'Last Updated'
    );
    fputcsv( $output, $headers );
    
    foreach ( $rows as $row ) {
      $field_data = json_decode( $row->field_data, true );
      
      // Email
      $email = '';
      if ( isset( $field_data['contact-email'] ) ) {
        $email = $field_data['contact-email'];
      } elseif ( isset( $field_data['email'] ) ) {
        $email = $field_data['email'];
      } elseif ( isset( $field_data['your-email'] ) ) {
        $email = $field_data['your-email'];
      } elseif ( isset( $field_data['mm_registration_email'] ) ) {
        $email = $field_data['mm_registration_email'];
      }
      
      // Name
      $first_name = '';
      if ( isset( $field_data['contact-firstname'] ) ) {
        $first_name = $field_data['contact-firstname'];
      } elseif ( isset( $field_data['user-name'] ) ) {
        $first_name = $field_data['user-name'];
      } elseif ( isset( $field_data['first-name'] ) ) {
        $first_name = $field_data['first-name'];
      } elseif ( isset( $field_data['mm_registration_first_name'] ) ) {
        $first_name = $field_data['mm_registration_first_name'];
      }
      
      $last_name = '';
      if ( isset( $field_data['contact-lastname'] ) ) {
        $last_name = $field_data['contact-lastname'];
      } elseif ( isset( $field_data['last-name'] ) ) {
        $last_name = $field_data['last-name'];
      } elseif ( isset( $field_data['mm_registration_last_name'] ) ) {
        $last_name = $field_data['mm_registration_last_name'];
      }
      
      $full_name = trim( $first_name . ' ' . $last_name );
      
      // Message
      $message = '';
      if ( isset( $field_data['contact-message'] ) ) {
        $message = $field_data['contact-message'];
      } elseif ( isset( $field_data['message'] ) ) {
        $message = $field_data['message'];
      }
      
      // Phone
      $phone = '';
      if ( isset( $field_data['phone'] ) ) {
        $phone = $field_data['phone'];
      } elseif ( isset( $field_data['your-tel'] ) ) {
        $phone = $field_data['your-tel'];
      }
      
      // Others
      $square_feet = isset( $field_data['square-feet'] ) ? $field_data['square-feet'] : '';
      $suite_floor = isset( $field_data['suite-floor'] ) ? $field_data['suite-floor'] : '';
      $company     = '';
      if ( isset( $field_data['your-company'] ) ) {
        $company = $field_data['your-company'];
      } elseif ( isset( $field_data['mm_registration_company'] ) ) {
        $company = $field_data['mm_registration_company'];
      }
      $address = isset( $field_data['address'] ) ? $field_data['address'] : '';
      
      $csv_row = array(
        $row->id,
        $row->form_id,
        $email,
        $full_name,
        $message,
        $phone,
        $square_feet,
        $suite_floor,
        $company,
        $address,
        $row->last_updated,
      );
      fputcsv( $output, $csv_row );
    }
    
    fclose( $output );
    exit;
  }
  
  /**
   * Handle single & bulk deletions
   */
  add_action( 'admin_init', 'cf7_partial_submissions_handle_deletions' );
  function cf7_partial_submissions_handle_deletions() {
    // Single record deletion
    if (
      isset( $_GET['action'] ) &&
      $_GET['action'] === 'cf7_delete_single' &&
      ! empty( $_GET['record_id'] )
    ) {
      $record_id = absint( $_GET['record_id'] );
      // Check nonce
      $nonce = isset( $_GET['cf7_delete_nonce'] ) ? sanitize_text_field( $_GET['cf7_delete_nonce'] ) : '';
      if ( ! wp_verify_nonce( $nonce, 'cf7_delete_nonce_' . $record_id ) ) {
        wp_die( 'Security check failed (invalid nonce).' );
      }
      
      global $wpdb;
      $table_name = $wpdb->prefix . 'cf7_partial_submissions';
      
      $wpdb->delete(
        $table_name,
        array( 'id' => $record_id ),
        array( '%d' )
      );
      
      // Redirect to avoid repeat on reload
      wp_redirect( admin_url( 'admin.php?page=cf7-partial-submissions&message=deleted' ) );
      exit;
    }
    
    // Bulk deletion
    if (
      isset( $_POST['bulk_action'] ) &&
      $_POST['bulk_action'] === 'bulk_delete' &&
      ! empty( $_POST['record_ids'] ) &&
      is_array( $_POST['record_ids'] )
    ) {
      // Check nonce
      $nonce = isset( $_POST['cf7_bulk_delete_nonce'] ) ? sanitize_text_field( $_POST['cf7_bulk_delete_nonce'] ) : '';
      if ( ! wp_verify_nonce( $nonce, 'cf7_bulk_delete_nonce' ) ) {
        wp_die( 'Security check failed (invalid nonce).' );
      }
      
      global $wpdb;
      $table_name = $wpdb->prefix . 'cf7_partial_submissions';
      
      foreach ( $_POST['record_ids'] as $record_id ) {
        $record_id = absint( $record_id );
        $wpdb->delete(
          $table_name,
          array( 'id' => $record_id ),
          array( '%d' )
        );
      }
      
      // Redirect after bulk delete
      wp_redirect( admin_url( 'admin.php?page=cf7-partial-submissions&message=bulk_deleted' ) );
      exit;
    }
  }
  
  /**
   * Example helper: update email in `search_stats` table, if you have it
   */
  function cia_update_email_by_session_id( $session_id, $email ) {
    global $wpdb;
    
    if ( empty( $session_id ) || empty( $email ) ) {
      return;
    }
    
    $wpdb->update(
      $wpdb->prefix . 'search_stats',
      [ 'email' => sanitize_email( $email ) ],
      [ 'session_id' => sanitize_text_field( $session_id ) ],
      [ '%s' ],
      [ '%s' ]
    );
  }
  
  /**
   * Cleanup duplicate records on admin page visit.
   */
  function cf7_cleanup_duplicates_on_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_partial_submissions';
    
    // Query to find session_id, form_id pairs with more than one record
    $duplicates = $wpdb->get_results(
      "SELECT COUNT(*) as count, session_id, form_id
         FROM $table_name
         GROUP BY session_id, form_id
         HAVING COUNT(*) > 1
         ORDER BY count DESC"
    );
    
    foreach ( $duplicates as $duplicate ) {
      $session_id = $duplicate->session_id;
      $form_id    = $duplicate->form_id;
      
      // Check if a completed record exists
      $completed_record = $wpdb->get_row(
        $wpdb->prepare(
          "SELECT id FROM $table_name
                 WHERE session_id = %s
                 AND form_id = %s
                 AND status = 'completed'
                 LIMIT 1",
          $session_id,
          $form_id
        )
      );
      
      if ( $completed_record ) {
        // Delete partial records, keep the completed one
        $wpdb->query(
          $wpdb->prepare(
            "DELETE FROM $table_name
                     WHERE session_id = %s
                     AND form_id = %s
                     AND status = 'partial'",
            $session_id,
            $form_id
          )
        );
      }
    }
  }
  
  add_action( 'admin_init', function () {
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'cf7-partial-submissions' ) {
      cf7_cleanup_duplicates_on_admin_page();
    }
  } );
