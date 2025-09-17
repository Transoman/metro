<?php
add_action('init', function(){
    if ( session_status() === PHP_SESSION_NONE ) {
        session_start();
    }
}, 1);

require_once __DIR__ . '/hooks/setup.php'; // Setting up theme hooks.

// require_once __DIR__ . '/includes/acf-field-menu.php'; // ACF Menu select field.
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/walker.php';
require_once __DIR__ . '/includes/favourites.php';
require_once __DIR__ . '/includes/requests.php';
require_once __DIR__ . '/includes/zoho.php';
require_once __DIR__ . '/includes/notification.php';
require_once __DIR__ . '/includes/edit-user.php';
require_once __DIR__ . '/includes/listings-available-update.php';
require_once __DIR__ . '/includes/registration-login.php';
require_once __DIR__ . '/includes/email-templates.php';
require_once __DIR__ . '/includes/user-company-column.php';
require_once __DIR__ . '/includes/login-metadata.php';
require_once __DIR__ . '/includes/user-sessions.php';
// require_once __DIR__ . '/includes/openai-integration.php';
if ( is_admin() ) {
  // require_once __DIR__ . '/includes/admin-recommended-articles.php';
  require_once __DIR__ . '/includes/admin-featured-listings.php';
}
// require_once __DIR__ . '/includes/image-cleaner.php';

function dequeue_cf7_scripts_styles() {
    $cf7_pages = array( 32, 38193, 38572 );

    if ( ! is_page( $cf7_pages ) ) {
        wp_dequeue_script( 'contact-form-7' );
        wp_dequeue_style( 'contact-form-7' );
    }
}

add_action( 'wp_enqueue_scripts', 'dequeue_cf7_scripts_styles' );

function add_noindex_meta_tags() {
    $request_uri   = $_SERVER['REQUEST_URI'];
    $noindex_pages = array(
        '/successful/',
        '/send-email/',
        '/set-password/',
        '/profile-settings/',
        '/verification/',
        '/?registration_provider=email'
    );
    foreach ( $noindex_pages as $page ) {
        if ( strpos( $request_uri, $page ) !== false ) {
            echo '<meta name="robots" content="noindex, nofollow">';

            return;
        }
    }
}

add_action( 'wp_head', 'add_noindex_meta_tags' );

add_action( 'wp_ajax_nopriv_generate_print_template', 'generate_print_template' );
add_action( 'wp_ajax_generate_print_template', 'generate_print_template' );
function generate_print_template() {
    global $post;
    $listing_id = intval( $_POST['listing_id'] );
    if ( ! $listing_id ) {
        wp_die( 'Invalid listing ID' );
    }
    ob_start();
    get_header( 'no-index', array( 'listing_id' => $listing_id ) );
    $post = get_post( $listing_id );
    setup_postdata( $post );
    include locate_template( 'print-template.php' );
    wp_reset_postdata();
    wp_die();
}

function exclude_urls_from_yoast_sitemap( $url, $type, $post ) {
    $base_url       = get_home_url();
    $excluded_paths = array(
        '/send-email/',
        '/successful/',
        '/profile-settings/',
        '/favorites/',
        '/set-password/',
        '/blog/author/bobbysamuels/'
    );

    $loc = isset( $url['loc'] ) ? $url['loc'] : '';
    foreach ( $excluded_paths as $path ) {
        if ( strpos( $loc, $base_url . $path ) !== false ) {
            return false;
        }
    }

    return $url;
}

add_filter( 'wpseo_sitemap_entry', 'exclude_urls_from_yoast_sitemap', 10, 3 );

function remove_print_query_param() {
    if ( is_singular( 'listings' ) && isset( $_GET['print'] ) ) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        get_template_part( 404 );
        exit;
    }
}

add_action( 'template_redirect', 'remove_print_query_param' );

function replace_with_webp_image( $attr, $attachment, $size ) {
    if ( ! is_front_page() ) {
        return $attr;
    }

    $image_url = wp_get_attachment_url( $attachment->ID );
    $image_ext = pathinfo( $image_url, PATHINFO_EXTENSION );

    if ( in_array( $image_ext, [ 'jpg', 'jpeg', 'png' ] ) ) {
        $webp_image_url = str_replace( "." . $image_ext, ".webp", $image_url );

        $upload_dir      = wp_upload_dir();
        $webp_image_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $webp_image_url );

        if ( file_exists( $webp_image_path ) ) {
            $attr['src'] = $webp_image_url;
        }

        if ( isset( $attr['srcset'] ) ) {
            $srcset       = $attr['srcset'];
            $srcset_items = explode( ',', $srcset );
            $new_srcset   = [];

            foreach ( $srcset_items as $item ) {
                $item  = trim( $item );
                $parts = explode( ' ', $item );
                $src   = $parts[0];
                $size  = $parts[1];

                $src_ext = pathinfo( $src, PATHINFO_EXTENSION );
                if ( in_array( $src_ext, [ 'jpg', 'jpeg', 'png' ] ) ) {
                    $webp_src      = str_replace( "." . $src_ext, ".webp", $src );
                    $webp_src_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $webp_src );

                    if ( file_exists( $webp_src_path ) ) {
                        $new_srcset[] = $webp_src . ' ' . $size;
                    } else {
                        $new_srcset[] = $src . ' ' . $size;
                    }
                } else {
                    $new_srcset[] = $src . ' ' . $size;
                }
            }

            $attr['srcset'] = implode( ', ', $new_srcset );
        }
    }

    return $attr;
}

// add_filter( 'wp_get_attachment_image_attributes', 'replace_with_webp_image', 10, 3 );

add_action( 'template_redirect', 'redirect_old_image_to_new' );
function redirect_old_image_to_new() {
    if ( strpos( $_SERVER['REQUEST_URI'], '/wp-content/uploads/2019/05/daryan-shamkhali-109777-unsplash.jpg' ) !== false ) {
        wp_redirect( '/wp-content/uploads/2019/07/Screenshot-2020-01-28-at-10.23.46.png', 301 );
        exit;
    }
}

/**
 * Validates phone number input fields in Contact Form 7 forms.
 *
 * This function adds custom validation for specific phone fields (`contact-phone`, `phone`, `phone-schedule`)
 * in Contact Form 7 forms. It uses a regular expression to ensure the phone number is in one of the valid formats.
 *
 * @param WPCF7_Validation $result The validation result object that holds the success or failure of the validation.
 * @param WPCF7_FormTag $tag The form tag object representing the current form field.
 *
 * @return WPCF7_Validation The modified validation result object.
 */
function custom_phone_validation_filter( $result, $tag ) {
    $tag          = new WPCF7_FormTag( $tag );
    $phone_fields = [ 'contact-phone', 'phone', 'phone-schedule' ];

    if ( in_array( $tag->name, $phone_fields ) ) {
        $phone_number = isset( $_POST[ $tag->name ] ) ? sanitize_text_field( $_POST[ $tag->name ] ) : '';

        // Regex for phone verification
        $pattern = '/^\+?1?[-. ]?(\()?([0-9]{3})(?(1)\))[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/';

        // Throw an error if validation fails
        if ( ! preg_match( $pattern, $phone_number ) ) {
            $result->invalidate( $tag, "Please enter a valid phone number in one of the following formats: 555-555-5555, (555) 555-5555, +1 (555) 555-5555, 5555555555, or +15555555555." );
        }
    }

    return $result;
}

add_filter( 'wpcf7_validate_tel*', 'custom_phone_validation_filter', 20, 2 );

// if ( ! function_exists( 'enqueue_user_phone_scripts' ) ) {
//     function enqueue_user_phone_scripts() {
//     	wp_enqueue_script( 'user_phone-js', get_template_directory_uri() . '/assets/js/user_phone.js', array( 'jquery' ), null, true );

//     	if ( is_user_logged_in() ) {
//     		$user_phone = get_user_meta( get_current_user_id(), 'phone_number', true );
//     	} else {
//     		$user_phone = '';
//     	}

//     	wp_localize_script( 'user_phone-js', 'user_phone_ajax_object', array(
//     		'user_phone' => $user_phone,
//     	) );
//     }
// }

// add_action( 'wp_enqueue_scripts', 'enqueue_user_phone_scripts' );

/**
 * Starts a session if it's not already started.
 *
 * This function checks the session status and starts a new session if none exists.
 * It's hooked to the `init` action to ensure the session starts early in the request cycle.
 *
 * @return void
 */
// function start_session_if_not_started() {
// 	if ( session_status() === PHP_SESSION_NONE ) {
// 		session_start();
// 	}
// }

// add_action( 'init', 'start_session_if_not_started' );

/**
 * Handles the clearing of the notification and refreshes the page.
 *
 * This function checks if a POST request to clear the notification is made. If so, it starts the session,
 * removes the notification from the session, sets `MMNotification::$show` to `false`, and redirects the user
 * to refresh the page, effectively clearing the notification.
 *
 * @return void
 */
add_action( 'template_redirect', 'mm_clear_notification_handler' );
function mm_clear_notification_handler() {
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        return;
    }

    if ( empty( $_POST['clear_notification'] ) || '1' !== $_POST['clear_notification'] ) {
        return;
    }

    if ( empty( $_POST['_wpnonce'] )
    || ! wp_verify_nonce( $_POST['_wpnonce'], 'mm_clear_notification' )
    ) {
        return;
    }

    if ( session_status() === PHP_SESSION_NONE ) {
        session_start();
    }

    if ( isset( $_SESSION['mm_notification'] ) ) {
        unset( $_SESSION['mm_notification'] );
    }

    MMNotification::$show = false;
    // wp_redirect( $_SERVER['REQUEST_URI'] );
    // exit;
}

// TODO NEMANJA REMOVE IF IT DOESN'T WORK
/**
 * Disable WP’s canonical redirect on any URL ending in a number.
 */
add_filter( 'redirect_canonical', 'mm_disable_numeric_canonical', 10, 2 );
function mm_disable_numeric_canonical( $redirect_url, $requested_url ) {
    $path = parse_url( $requested_url, PHP_URL_PATH );
    if ( preg_match( '#/\d+/?$#', $path ) ) {
        return false;
    }
    return $redirect_url;
}


// function add_custom_image_sitemap( $sitemap_index ) {
// 	$custom_sitemap_url = home_url( '/wp-content/uploads/image-sitemap.xml' );
// 	$last_modified      = date( 'Y-m-d\TH:i:sP', filemtime( ABSPATH . 'wp-content/uploads/image-sitemap.xml' ) );
// 	$sitemap_index      .= '<sitemap>';
// 	$sitemap_index      .= '<loc>' . esc_url( $custom_sitemap_url ) . '</loc>';
// 	$sitemap_index      .= '<lastmod>' . $last_modified . '</lastmod>';
// 	$sitemap_index      .= '</sitemap>';

// 	return $sitemap_index;
// }

// add_filter( 'wpseo_sitemap_index', 'add_custom_image_sitemap' );

function generate_image_sitemap() {
    $upload_dir = wp_upload_dir();
    $images     = get_posts( array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => - 1,
    ) );

    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

    foreach ( $images as $image ) {
        $image_url      = wp_get_attachment_url( $image->ID );
        $image_date     = $image->post_date;
        $image_alt      = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
        $image_metadata = wp_get_attachment_metadata( $image->ID );
        $image_width    = isset( $image_metadata['width'] ) ? $image_metadata['width'] : '';
        $image_height   = isset( $image_metadata['height'] ) ? $image_metadata['height'] : '';
        $image_license  = 'https://www.istockphoto.com/legal/license-agreement';

        $sitemap .= '<url>';
        $sitemap .= '<loc>' . esc_url( $image_url ) . '</loc>';
        $sitemap .= '<lastmod>' . esc_html( $image_date ) . '</lastmod>';
        if ( $image_alt ) {
            $sitemap .= '<image:image>';
            $sitemap .= '<image:loc>' . esc_url( $image_url ) . '</image:loc>';
            $sitemap .= '<image:caption>' . esc_html( $image_alt ) . '</image:caption>';
            $sitemap .= '<image:title>' . esc_html( $image_alt ) . '</image:title>';
            if ( $image_width && $image_height ) {
                $sitemap .= '<image:width>' . esc_html( $image_width ) . '</image:width>';
                $sitemap .= '<image:height>' . esc_html( $image_height ) . '</image:height>';
            }
            $sitemap .= '</image:image>';
        }
        $sitemap .= '</url>';
    }

    $sitemap .= '</urlset>';

    $sitemap_path = $upload_dir['basedir'] . '/image-sitemap.xml';
    file_put_contents( $sitemap_path, $sitemap );
}

function add_image_to_sitemap( $attachment_id ) {
    // generate_image_sitemap();
}

add_action( 'add_attachment', 'add_image_to_sitemap' );

function check_and_generate_sitemap() {
    $upload_dir   = wp_upload_dir();
    $sitemap_path = $upload_dir['basedir'] . '/image-sitemap.xml';

    if ( ! file_exists( $sitemap_path ) ) {
        // generate_image_sitemap();
    }
}

add_action( 'admin_init', 'check_and_generate_sitemap' );

// Load the WP-CLI script if WP-CLI is defined
// if (defined('WP_CLI') && WP_CLI) {
// 	require_once get_template_directory() . '/includes/cli-user-timestamps.php';
// }

// if ( defined( 'WP_CLI' ) && WP_CLI ) {
//     WP_CLI::add_command( 'generate_sitemap', 'generate_image_sitemap_cli' );
// }

// function generate_image_sitemap_cli() {
//     generate_image_sitemap();
//     WP_CLI::success( 'Image sitemap has been successfully generated.' );
// }

/**
 * Clears WP Rocket cache after a post is deleted or updated.
 *
 * This function checks if WP Rocket's `rocket_clean_domain` function exists,
 * and if so, it clears the entire site cache after a post is permanently deleted
 * or when a post is updated (created or edited). This helps ensure that the cache
 * reflects the latest changes and prevents serving stale content.
 *
 * @param int $post_id The ID of the post that was deleted or updated.
 *
 * @return void
 */
function clear_wp_rocket_cache_on_post_change( $post_id ) {
    if ( function_exists( 'rocket_clean_domain' ) && ! wp_is_post_revision( $post_id ) && ! wp_is_post_autosave( $post_id ) ) {
        error_log('rocket_clean_domain() function is being called...');
        rocket_clean_domain();
    }
}

// Clear cache after a post is deleted
// add_action( 'after_delete_post', 'clear_wp_rocket_cache_on_post_change' );

// Clear cache after a post is updated or created
// add_action( 'save_post', 'clear_wp_rocket_cache_on_post_change' );

// Stop indexing pages that have ?loginSocial in URL
// function add_noindex_to_login_social_pages() {
//   if ( isset( $_GET['loginSocial'] ) ) {
//     echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
//   }
// }
// add_action( 'wp_head', 'add_noindex_to_login_social_pages' );

// Stop indexing wp login pages
// function add_noindex_to_wp_login() {
//   echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
// }
// add_action( 'login_head', 'add_noindex_to_wp_login' );

add_action('wp_ajax_load_contact_form', 'load_contact_form');
add_action('wp_ajax_nopriv_load_contact_form', 'load_contact_form');

function load_contact_form() {
    // Output the Contact Form 7 form content using the shortcode
    echo do_shortcode('[contact-form-7 id="38584" title="Main contact form"]');
    wp_die(); // End the AJAX request properly
}

add_action('init', 'adjust_vxcf_request');

function adjust_vxcf_request() {
  if (isset($_REQUEST['status']) && $_REQUEST['status'] == 'spam') {
    $_REQUEST['type'] = 99;
  }
}

add_action('admin_footer', 'add_spam_link_script');

function add_spam_link_script() {
  if (isset($_GET['page']) && $_GET['page'] == 'vxcf_leads' && isset($_GET['tab']) && $_GET['tab'] == 'entries') {
    $form_id    = isset($_GET['form_id']) ? $_GET['form_id'] : '';
    $spam_count = get_spam_count($form_id);

    $base_url  = admin_url('admin.php?page=vxcf_leads&tab=entries&form_id=' . $form_id);
    $spam_link = '<li>|</li>
        <li>
            <a href="' . $base_url . '&status=spam" title="Spam">Spam <span class="count">(' . $spam_count . ')</span></a>
        </li>';

    $spam_link_js = json_encode($spam_link);

    echo '<script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                $(".subsubsub").append(' . $spam_link_js . ');
            });
        })(jQuery);
    </script>';
  }
}

function get_spam_count($form_id) {
  global $wpdb;
  $leads_table = $wpdb->prefix . 'vxcf_leads';

  $search   = is_array($form_id) ? " l.form_id IN ('" . implode("','", $form_id) . "')" : " l.form_id='" . esc_sql($form_id) . "'";
  $sql_spam = "SELECT COUNT(DISTINCT l.id) FROM {$leads_table} l WHERE $search AND l.status=0 AND l.type=99";

  $spam_count = $wpdb->get_var($sql_spam);
  return $spam_count ? $spam_count : 0;
}

add_filter( 'wpcf7_before_send_mail', function ( $contact_form ) {
  global $wpdb;

  $leads_table = $wpdb->prefix . 'vxcf_leads';
  $submission  = WPCF7_Submission::get_instance();

  if ( $submission ) {
    $posted_data     = $submission->get_posted_data();
    $contact_message = isset( $posted_data['contact-message'] ) ? $posted_data['contact-message'] : '';
    $spam_keywords   = get_field( 'spam_keywords', 'option' );

    if ( $spam_keywords && $contact_message ) {
      foreach ( $spam_keywords as $keyword_entry ) {
        if ( isset( $keyword_entry['keyword'] ) && stripos( $contact_message, $keyword_entry['keyword'] ) !== false ) {
          $latest_lead_id = $wpdb->get_var( "SELECT `id` FROM {$leads_table} ORDER BY `created` DESC LIMIT 1" );

          if ( $latest_lead_id ) {
            $updated = $wpdb->update(
              $leads_table,
              [ 'type' => 99 ],
              [ 'id'   => $latest_lead_id ],
              [ '%d' ],
              [ '%d' ]
            );

            if ( $updated !== false ) {
              error_log( "Lead ID {$latest_lead_id} type updated to 99 due to spam keyword '{$keyword_entry['keyword']}'." );
            } else {
              error_log( "Failed to update lead ID {$latest_lead_id}." );
            }
          } else {
            error_log( 'No latest lead found to update.' );
          }
          break;
        }
      }
    }
  }

  return $contact_form;
});

add_filter( 'wpcf7_before_send_mail', function ( $contact_form ) {
  global $wpdb;

  $leads_table = $wpdb->prefix . 'vxcf_leads';
  $submission  = WPCF7_Submission::get_instance();

  if ( $submission ) {
    $posted_data   = $submission->get_posted_data();
    $spam_keywords = get_field( 'spam_keywords', 'option' );

    if ( $spam_keywords && $posted_data ) {
      foreach ( $posted_data as $field_name => $field_value ) {
        if ( is_string( $field_value ) && ! empty( $field_value ) ) {
          foreach ( $spam_keywords as $keyword_entry ) {
            if ( isset( $keyword_entry['keyword'] ) && stripos( $field_value, $keyword_entry['keyword'] ) !== false ) {
              $latest_lead_id = $wpdb->get_var( "SELECT `id` FROM {$leads_table} ORDER BY `created` DESC LIMIT 1" );

              if ( $latest_lead_id ) {
                $updated = $wpdb->update(
                  $leads_table,
                  [ 'type' => 99 ],
                  [ 'id'   => $latest_lead_id ],
                  [ '%d' ],
                  [ '%d' ]
                );

                if ( $updated !== false ) {
                  error_log( "Lead ID {$latest_lead_id} type updated to 99 due to spam keyword '{$keyword_entry['keyword']}' in field '{$field_name}'." );
                } else {
                  error_log( "Failed to update lead ID {$latest_lead_id}." );
                }
              } else {
                error_log( 'No latest lead found to update.' );
              }
              break 2;
            }
          }
        }
      }
    }
  }

  return $contact_form;
});

if (defined('WP_CLI') && WP_CLI) {
  require_once get_template_directory() . '/wp-cli-clear-cache.php';
  require_once get_template_directory() . '/wp-cli-zoho-upsert.php';
  require_once get_template_directory() . '/wp-cli-upsert-visits.php';
  require_once get_template_directory() . '/wp-cli-user-sessions.php';
  require_once get_template_directory() . '/wp-cli-latest-records.php';
  require_once get_template_directory() . '/wp-cli-update-search-stats.php';
  require_once get_template_directory() . '/wp-cli-remove-stats-duplicate.php';
  require_once get_template_directory() . '/wp-cli-updating-search-stats.php';
  // require_once get_template_directory() . '/wp-cli-analyze-listings-meta.php';
  require_once get_template_directory() . '/wp-cli-update-listings-meta.php';
  require_once get_template_directory() . '/wp-cli-upsert-forms.php';
  require_once get_template_directory() . '/wp-cli-ip-fix.php';
}

/**
 * Automatically clicks the "410" button in the WordPress admin post list.
 *
 * The script attempts to click the button earlier (e.g., 100ms) and retries after 2 seconds
 * to ensure the click is registered even if the button loads late.
 */
function add_inline_js_for_410_click() {
  global $pagenow;
  if ( $pagenow !== 'edit.php' ) {
    return;
  }

  echo "
  <script>
  document.addEventListener('DOMContentLoaded', function () {
      function click410Button() {
          const button = document.querySelector(\"button[onclick*='410']\");
          if (button) {
              button.click();
              console.log('410 Content Deleted header button clicked!');
              return true;
          } else {
              console.log('410 button not found. Retrying...');
              return false;
          }
      }

      // Attempt click sooner
      setTimeout(function () {
          if (!click410Button()) {
              // Retry after 2 seconds if the button wasn't found
              setTimeout(click410Button, 2000);
          }
      }, 100);
  });
  </script>
  ";
}

add_action( 'admin_footer', 'add_inline_js_for_410_click' );

function get_logged_in_user_data() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        wp_send_json_success([
            'email' => $current_user->user_email,
            'name' => $current_user->display_name,
        ]);
    } else {
        wp_send_json_error('User not logged in');
    }
}
add_action('wp_ajax_get_logged_in_user_data', 'get_logged_in_user_data');
add_action('wp_ajax_nopriv_get_logged_in_user_data', 'get_logged_in_user_data');

function enqueue_custom_heartbeat_script() {
  wp_enqueue_script(
    'custom-heartbeat',
    get_template_directory_uri() . '/js/custom-heartbeat.js',
    [],
    null,
    true
  );

  wp_localize_script( 'custom-heartbeat', 'heartbeat_object', [
    'ajax_url' => admin_url( 'admin-ajax.php' ),
  ] );
}

add_action( 'wp_enqueue_scripts', 'enqueue_custom_heartbeat_script' );

add_filter( 'wpcf7_before_send_mail', 'mark_cf7_partial_as_completed' );
function mark_cf7_partial_as_completed( $contact_form ) {
  global $wpdb;
  $table_name = $wpdb->prefix . 'cf7_partial_submissions';

  // CF7 form ID
  $form_id = $contact_form->id();

  // Attempt to get session ID from the form data
  $session_id = isset( $_POST['cf7_session_id'] ) ? sanitize_text_field( $_POST['cf7_session_id'] ) : '';

  // If session ID exists, update the partial record as usual
  if ( ! empty( $session_id ) ) {
    $wpdb->update(
      $table_name,
      array(
        'status'       => 'completed',
        'last_updated' => current_time( 'mysql' ),
      ),
      array(
        'session_id' => $session_id,
        'form_id'    => $form_id,
        'status'     => 'partial',
      ),
      array( '%s', '%s' ),
      array( '%s', '%s', '%s' )
    );
  } // Fallback: If no session ID, try matching by email in the JSON field_data
  else {
    // 1. Figure out which email field your CF7 form uses.
    //    For example, maybe it's "your-email", or "contact-email", or "email"...
    $possible_email_fields = array( 'contact-email', 'your-email', 'email' );

    // 2. Loop to find whichever is present in $_POST
    $submitted_email = '';
    foreach ( $possible_email_fields as $fld ) {
      if ( ! empty( $_POST[ $fld ] ) ) {
        $submitted_email = sanitize_text_field( $_POST[ $fld ] );
        break;
      }
    }

    // 3. If we found an email, attempt to match an existing partial record
    if ( ! empty( $submitted_email ) ) {
      // We'll search in field_data JSON for this email
      // Order by last_updated DESC in case multiple partial records exist for that email
      $query = $wpdb->prepare( "
            SELECT id
            FROM $table_name
            WHERE form_id = %s
              AND status = 'partial'
              AND field_data LIKE %s
            ORDER BY last_updated DESC
            LIMIT 1
        ", $form_id, '%' . $wpdb->esc_like( $submitted_email ) . '%' );

      $row_id = $wpdb->get_var( $query );

      // If found, set it to completed
      if ( $row_id ) {
        $wpdb->update(
          $table_name,
          array(
            'status'       => 'completed',
            'last_updated' => current_time( 'mysql' ),
          ),
          array( 'id' => $row_id ),
          array( '%s', '%s' ),
          array( '%d' )
        );
      }
    }
  }

  return $contact_form;
}

function customize_wpseo_schema_article( $data ) {
    if ( is_single() ) {
        $site_icon_id = get_option( 'site_icon' );
        $site_icon_url = $site_icon_id ? wp_get_attachment_url( $site_icon_id ) : '';

        $data['publisher'] = [
            '@type' => 'Organization',
            'name'  => 'Metro Manhattan Office Space',
            'logo'  => [
                '@type' => 'ImageObject',
                'url'   => $site_icon_url,
            ],
        ];

        $data['mainEntityOfPage'] = [
            '@type' => 'WebPage',
            '@id'   => get_permalink(),
        ];

        if ( empty( $data['description'] ) ) {
            $data['description'] = get_the_excerpt();
        }

        if ( empty( $data['image'] ) ) {
            $featured_image = get_the_post_thumbnail_url( get_the_ID(), 'full' );
            if ( $featured_image ) {
                $data['image'] = [
                    '@type' => 'ImageObject',
                    'url'   => $featured_image,
                ];
            }
        }

        if ( isset( $data['author'] ) && is_array( $data['author'] ) ) {
            foreach ( $data['author'] as &$author ) {
                if ( isset( $author['name'] ) ) {
                    $name = strtolower( trim( $author['name'] ) );
                    if ( $name === 'alan rosinsky' ) {
                        $author['url'] = 'https://www.metro-manhattan.com/principal-broker/';
                    }
                    // elseif ( $name === 'bobby samuels' ) {
                    //     $author['url'] = 'https://www.metro-manhattan.com/blog/author/bobby-samuels/';
                    // }
                }
            }
        }
    }
    return $data;
}
add_filter( 'wpseo_schema_article', 'customize_wpseo_schema_article' );

if (defined('WP_CLI') && WP_CLI) {
    class Get_Imagify_Data {
        public function __invoke($args) {
            if (empty($args[0])) {
                WP_CLI::error('Please provide a post ID');
                return;
            }

            $post_id = intval($args[0]);
            $imagify_data = get_post_meta($post_id, '_imagify_data', true);

            if (empty($imagify_data)) {
                WP_CLI::error("No Imagify data found for post ID: $post_id");
                return;
            }

            $unserialized_data = maybe_unserialize($imagify_data);
            WP_CLI::log("Imagify data for post ID $post_id:");
            WP_CLI::log(print_r($unserialized_data, true));
        }
    }

    WP_CLI::add_command('get-imagify-data', 'Get_Imagify_Data');
}

if (defined('WP_CLI') && WP_CLI) {
    class Check_Imagify_Data {
        /**
         * Fix Imagify compression data for a specific attachment
         *
         * ## OPTIONS
         *
         * <post_id>
         * : The ID of the attachment to fix
         *
         * ## EXAMPLES
         *
         *     wp fix-imagify-compression 123
         *
         * @when after_wp_load
         */
        public function __invoke($args) {
            global $wpdb;

            list($post_id) = $args;

            // Verify post exists and is an attachment
            if (!get_post_type($post_id) === 'attachment') {
                WP_CLI::error("Post ID $post_id is not a valid attachment");
                return;
            }

            // Get attachment metadata
            $attachment_metadata = wp_get_attachment_metadata($post_id);
            if (!$attachment_metadata) {
                WP_CLI::error("No metadata found for attachment ID: $post_id");
                return;
            }

            $upload_dir = wp_upload_dir();
            $base_path = $upload_dir['basedir'] . '/' . dirname($attachment_metadata['file']);

            // Initialize sizes array
            $sizes = [];
            $total_original_size = 0;
            $total_optimized_size = 0;

            // Process full size
            $full_path = $upload_dir['basedir'] . '/' . $attachment_metadata['file'];
            if (file_exists($full_path)) {
                $original_size = filesize($full_path);
                $sizes['full'] = [
                    'success' => 1,
                    'original_size' => $original_size,
                    'optimized_size' => $original_size,
                    'percent' => 0
                ];
                $total_original_size += $original_size;
                $total_optimized_size += $original_size;
            }

            // Process all other sizes
            if (isset($attachment_metadata['sizes']) && is_array($attachment_metadata['sizes'])) {
                foreach ($attachment_metadata['sizes'] as $size => $size_data) {
                    if (isset($size_data['file'])) {
                        $file_path = $base_path . '/' . $size_data['file'];
                        if (file_exists($file_path)) {
                            $original_size = filesize($file_path);
                            $sizes[$size] = [
                                'success' => 1,
                                'original_size' => $original_size,
                                'optimized_size' => $original_size,
                                'percent' => 0
                            ];
                            $total_original_size += $original_size;
                            $total_optimized_size += $original_size;

                            // Check for WebP version
                            $webp_path = $file_path . '.webp';
                            if (file_exists($webp_path)) {
                                $webp_size = filesize($webp_path);
                                $sizes[$size . '@imagify-webp'] = [
                                    'success' => 1,
                                    'original_size' => $original_size,
                                    'optimized_size' => $webp_size,
                                    'percent' => round(($original_size - $webp_size) / $original_size * 100, 2)
                                ];
                                $total_optimized_size += $webp_size;
                            }
                        }
                    }
                }
            }

            // Check for full size WebP
            $full_webp_path = $full_path . '.webp';
            if (file_exists($full_webp_path)) {
                $webp_size = filesize($full_webp_path);
                $sizes['full@imagify-webp'] = [
                    'success' => 1,
                    'original_size' => $sizes['full']['original_size'],
                    'optimized_size' => $webp_size,
                    'percent' => round(($sizes['full']['original_size'] - $webp_size) / $sizes['full']['original_size'] * 100, 2)
                ];
                $total_optimized_size += $webp_size;
            }

            // Calculate overall stats
            $stats = [
                'original_size' => $total_original_size,
                'optimized_size' => $total_optimized_size,
                'percent' => $total_original_size > 0 ?
                    round(($total_original_size - $total_optimized_size) / $total_original_size * 100, 2) : 0,
                'message' => ''
            ];

            // Create the final data structure
            $imagify_data = [
                'sizes' => $sizes,
                'stats' => $stats
            ];

            // Update the post meta
            update_post_meta($post_id, '_imagify_data', $imagify_data);
            WP_CLI::success("Updated imagify data for post ID: $post_id with proper compression data");

            // Output the updated data structure
            WP_CLI::log("Updated data structure:");
            WP_CLI::log(print_r($imagify_data, true));
        }
    }

    WP_CLI::add_command('fix-imagify-compression', 'Check_Imagify_Data');
}

add_action( 'init', function() {
  add_post_type_support( 'listings', 'revisions' );
} );

add_action( 'init', function() {
  add_post_type_support( 'buildings', 'revisions' );
} );

// Register WP-CLI commands
if (defined('WP_CLI') && WP_CLI) {
    require_once get_template_directory() . '/wp-cli-generate-embeddings.php';
}

if ( class_exists( '\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece' ) ) {
    // Create a custom schema piece class for listings
    class Listing_Schema_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {
        /**
         * Determines whether or not this piece should be added to the graph.
         *
         * @return bool
         */
        public function is_needed() {
            return is_singular('listings');
        }

        /**
         * Generates the schema data.
         *
         * @return array The schema data.
         */
        public function generate() {
            global $post;
            $listing_id = $post->ID;
            $google_map_api_key = get_field('google_map_api_key', 'option');

            // Create RealEstateListing schema as the primary schema
            $listing_schema = array(
                '@type' => 'RealEstateListing',
                '@id' => $this->context->canonical . '#realestatelisting',
                'url' => $this->context->canonical,
                'name' => get_the_title($listing_id),
                'description' => get_the_excerpt($listing_id),
                'datePosted' => get_the_date('c', $listing_id),
                'inLanguage' => 'en-US',
                'mainEntityOfPage' => array(
                    '@type' => 'WebPage',
                    '@id' => $this->context->canonical,
                    'dateCreated' => get_the_date('c', $listing_id),
                    'dateModified' => get_the_modified_date('c', $listing_id)
                )
            );

            // Add images with more detailed information
            $images = get_field('images', $listing_id);
            if ($images && is_array($images)) {
                $listing_schema['image'] = array();
                foreach ($images as $index => $image) {
                    if (isset($image['url'])) {
                        $listing_schema['image'][] = array(
                            '@type' => 'ImageObject',
                            'url' => $image['url'],
                            'caption' => isset($image['caption']) ? $image['caption'] : get_the_title($listing_id) . ' - Image ' . ($index + 1),
                            'representativeOfPage' => $index === 0 ? true : false,
                            'contentUrl' => $image['url'],
                            'width' => isset($image['width']) ? $image['width'] : 1200,
                            'height' => isset($image['height']) ? $image['height'] : 900
                        );
                    }
                }
            }

            // Add property details under additionalProperty
            $listing_schema['additionalProperty'] = array();

            // Add lease term
            $lease_term = get_field('lease_term', $listing_id);
            if ($lease_term) {
                $listing_schema['additionalProperty'][] = array(
                    '@type' => 'PropertyValue',
                    'name' => 'Lease Term',
                    'value' => $lease_term . ' years'
                );
            }

            // Add square footage
            $square_feet = get_field('square_feet', $listing_id);
            if ($square_feet) {
                $listing_schema['additionalProperty'][] = array(
                    '@type' => 'PropertyValue',
                    'name' => 'Square Footage',
                    'value' => $square_feet . ' sqft'
                );
            }

            // Add property type
            $property_type = get_field('property_type', $listing_id);
            if ($property_type) {
                $listing_schema['additionalProperty'][] = array(
                    '@type' => 'PropertyValue',
                    'name' => 'Property Type',
                    'value' => $property_type
                );
            }

            // Add amenities
            $amenities = get_field('amenities', $listing_id);
            if ($amenities && is_array($amenities)) {
                foreach ($amenities as $amenity) {
                    $listing_schema['additionalProperty'][] = array(
                        '@type' => 'PropertyValue',
                        'name' => 'Amenity',
                        'value' => $amenity
                    );
                }
            }

            // Add broker information
            $broker = get_field('broker', $listing_id);
            if ($broker) {
                $listing_schema['broker'] = array(
                    '@type' => 'RealEstateAgent',
                    'name' => $broker['name'],
                    'email' => $broker['email'],
                    'telephone' => $broker['phone'],
                    'url' => isset($broker['url']) ? $broker['url'] : 'https://www.metro-manhattan.com/principal-broker/',
                    'image' => isset($broker['image']) ? array(
                        '@type' => 'ImageObject',
                        'url' => $broker['image']
                    ) : null,
                    'worksFor' => array(
                        '@type' => 'Organization',
                        'name' => 'Metro Manhattan Office Space'
                    )
                );
            }

            $site_icon_id = get_option( 'site_icon' );
            $site_icon_url = $site_icon_id ? wp_get_attachment_url( $site_icon_id ) : '';

            // Add organization information
            $listing_schema['provider'] = array(
                '@type' => 'Organization',
                'name' => 'Metro Manhattan Office Space',
                'url' => 'https://www.metro-manhattan.com',
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $site_icon_url
                ),
                'sameAs' => array(
                    'https://www.facebook.com/MetroManhattanOfficeSpace',
                    'https://x.com/MetroManhattan',
                    'https://www.youtube.com/@MetroManhattanOfficeSpace',
                    'https://www.youtube.com/@MetroManhattanForTenants',
                    'https://www.linkedin.com/company/metro-manhattan-office-space'
                ),
                'contactPoint' => array(
                    '@type' => 'ContactPoint',
                    'telephone' => '+1-212-444-2241',
                    'contactType' => 'sales',
                    'areaServed' => 'US',
                    'availableLanguage' => 'English'
                )
            );

            // Add breadcrumb schema
            $breadcrumb_schema = array(
                '@type' => 'BreadcrumbList',
                '@id' => $this->context->canonical . '#breadcrumb',
                'itemListElement' => array(
                    array(
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'New York City',
                        'item' => home_url()
                    ),
                    array(
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Manhattan',
                        'item' => home_url('manhattan/')
                    )
                )
            );

            // Add location terms to breadcrumb schema
            $terms = wp_get_object_terms($listing_id, 'location', ['orderby' => 'parent']);
            if (!empty($terms) && isset($terms[0]) && $terms[0]->parent == 0) {
                array_shift($terms);
            }
            if (!empty($terms)) {
                $position = 3;
                foreach ($terms as $term) {
                    $page_location = get_field('page_id', $term);
                    if ($page_location) {
                        $breadcrumb_schema['itemListElement'][] = array(
                            '@type' => 'ListItem',
                            'position' => $position++,
                            'name' => $term->name,
                            'item' => get_permalink($page_location)
                        );
                    }
                }
            }

            // Add current listing to breadcrumb schema
            $listing_title = get_the_title($listing_id);
            $address = get_field('address', $listing_id);
            $breadcrumb_name = !empty($listing_title) ? $listing_title : $address;

            $breadcrumb_schema['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => count($breadcrumb_schema['itemListElement']) + 1,
                'name' => $breadcrumb_name,
                'item' => $this->context->canonical
            );

            return array($listing_schema, $breadcrumb_schema);
        }
    }

    /**
    * Adds the listing schema piece to the schema collector.
    *
    * @param array  $pieces  The current graph pieces.
    * @param string $context The current context.
    *
    * @return array The graph pieces.
    */
    function add_listing_schema_to_yoast($pieces, $context) {
    $pieces[] = new Listing_Schema_Piece($context);
    return $pieces;
    }
    add_filter('wpseo_schema_graph_pieces', 'add_listing_schema_to_yoast', 11, 2);

    // Create a custom schema piece class for buildings
    class Building_Schema_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {
        /**
         * Determines whether or not this piece should be added to the graph.
         *
         * @return bool
         */
        public function is_needed() {
            return is_singular('buildings');
        }

        /**
         * Generates the schema data.
         *
         * @return array The schema data.
         */
        public function generate() {
            global $post;
            $building_id = $post->ID;
            $google_map_api_key = get_field('google_map_api_key', 'option');
            $helper = new MetroManhattanHelpers();

            // Create Building schema as the primary schema
            $building_schema = array(
                '@type' => ['Building', 'CommercialBuilding'],
                '@id' => $this->context->canonical . '#building',
                'url' => $this->context->canonical,
                'name' => get_field('building_name', $building_id),
                'description' => get_the_excerpt($building_id),
                'inLanguage' => 'en-US',
                'mainEntityOfPage' => array(
                    '@type' => 'WebPage',
                    '@id' => $this->context->canonical,
                    'dateCreated' => get_the_date('c', $building_id),
                    'dateModified' => get_the_modified_date('c', $building_id)
                ),
                'potentialAction' => array(
                    array(
                        '@type' => 'ViewAction',
                        'target' => $this->context->canonical
                    )
                ),
                'knowsAbout' => array(
                    'Commercial Office Space',
                    'Midtown Manhattan',
                    'Class B Office Buildings',
                    'Murray Hill',
                    'Fifth Avenue',
                    'Office Space Leasing',
                    'Commercial Real Estate'
                ),
                'about' => array(
                    '@type' => 'Thing',
                    'name' => 'Commercial Office Building',
                    'description' => 'Class B office building in Midtown Manhattan'
                ),
                'additionalType' => array(
                    'https://schema.org/OfficeBuilding',
                    'https://schema.org/CommercialProperty',
                    'https://schema.org/HistoricBuilding'
                )
            );

            // Add building details from stored fields
            $year_built = get_field('year_built', $building_id);
            if ($year_built) {
                $building_schema['yearBuilt'] = $year_built;
                $building_schema['additionalProperty'][] = array(
                    '@type' => 'PropertyValue',
                    'name' => 'Historical Significance',
                    'value' => 'Built in ' . $year_built . ', this building represents the architectural heritage of Midtown Manhattan'
                );
            }

            $building_class = get_field('class', $building_id);
            if ($building_class) {
                $building_schema['buildingClass'] = $building_class;
                $building_schema['additionalProperty'][] = array(
                    '@type' => 'PropertyValue',
                    'name' => 'Building Class',
                    'value' => 'Class ' . $building_class . ' office building'
                );
            }

            // Add number of floors
            $floors = get_field('floors', $building_id);
            if ($floors) {
                $building_schema['numberOfFloors'] = array(
                    '@type' => 'QuantitativeValue',
                    'value' => intval($floors),
                    'unitText' => 'floors'
                );
            }

            // Add floor size range
            $size_range = get_field('size_range', $building_id);
            if ($size_range) {
                // Extract numbers from the size range string (e.g., "900-4,792 SF")
                preg_match('/(\d+(?:,\d+)?)\s*-\s*(\d+(?:,\d+)?)/', $size_range, $matches);
                if (count($matches) === 3) {
                    $min_size = str_replace(',', '', $matches[1]);
                    $max_size = str_replace(',', '', $matches[2]);
                    $building_schema['floorSizeRange'] = array(
                        '@type' => 'QuantitativeValue',
                        'minValue' => floatval($min_size),
                        'maxValue' => floatval($max_size),
                        'unitText' => 'sqft',
                        'description' => 'Available floor sizes'
                    );
                }
            }

            // Add total building size
            $size = get_field('size', $building_id);
            if ($size) {
                // Remove any non-numeric characters except decimal point
                $numeric_size = preg_replace('/[^0-9.]/', '', $size);
                $building_schema['floorSize'] = array(
                    '@type' => 'QuantitativeValue',
                    'value' => floatval($numeric_size),
                    'unitText' => 'sqft',
                    'description' => 'Total building size'
                );
            }

            // Add location with address
            $address = get_field('address', $building_id);
            $cross_streets = get_field('cross_streets', $building_id);
            if ($address) {
                $building_schema['location'] = array(
                    '@type' => 'Place',
                    'name' => $address,
                    'address' => array(
                        '@type' => 'PostalAddress',
                        'streetAddress' => $address . ($cross_streets ? ' (' . $cross_streets . ')' : ''),
                        'addressLocality' => 'New York',
                        'addressRegion' => 'NY',
                        'addressCountry' => 'US'
                    ),
                    'geo' => array(
                        '@type' => 'GeoCoordinates',
                        'latitude' => 40.724361,
                        'longitude' => -73.997484
                    ),
                    'hasMap' => array(
                        '@type' => 'Map',
                        'url' => 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address) . '&key=' . $google_map_api_key
                    )
                );

                if ($google_map_api_key) {
                    $building_schema['location']['hasMap'] = array(
                        '@type' => 'Map',
                        'url' => 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address) . '&key=' . $google_map_api_key
                    );
                }
            }

            // Add images with enhanced metadata
            $images = get_field('images', $building_id);
            if ($images && is_array($images)) {
                $building_schema['image'] = array();
                foreach ($images as $index => $image) {
                    if (isset($image['url'])) {
                        $building_schema['image'][] = array(
                            '@type' => 'ImageObject',
                            'url' => $image['url'],
                            'caption' => isset($image['caption']) ? $image['caption'] : get_the_title($building_id) . ' - Image ' . ($index + 1),
                            'representativeOfPage' => $index === 0 ? true : false,
                            'contentUrl' => $image['url'],
                            'width' => isset($image['width']) ? $image['width'] : 1200,
                            'height' => isset($image['height']) ? $image['height'] : 900,
                            'potentialAction' => array(
                                array(
                                    '@type' => 'ViewAction',
                                    'target' => $image['url']
                                )
                            )
                        );
                    }
                }
            }

            // Add highlights if available
            $highlights = get_field('highlights', $building_id);
            if ($highlights && is_array($highlights)) {
                foreach ($highlights as $highlight) {
                    $building_schema['additionalProperty'][] = array(
                        '@type' => 'PropertyValue',
                        'name' => 'Building Feature',
                        'value' => $highlight['highlight']
                    );
                }
            }

            $site_icon_id = get_option( 'site_icon' );
            $site_icon_url = $site_icon_id ? wp_get_attachment_url( $site_icon_id ) : '';

            // Add provider information with enhanced details
            $building_schema['provider'] = array(
                '@type' => 'Organization',
                'name' => 'Metro Manhattan Office Space',
                'url' => 'https://www.metro-manhattan.com',
                'description' => 'Metro Manhattan Office Space, Inc. is a tenant representation brokerage specializing in office leasing in Midtown Manhattan and Downtown NYC.',
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $site_icon_url
                ),
                'founder' => array(
                    '@type' => 'Person',
                    'name' => 'Alan Rosinsky',
                    'jobTitle' => 'Principal Broker',
                    'url' => 'https://www.linkedin.com/in/alanrosinsky',
                    'sameAs' => array('https://www.linkedin.com/in/alanrosinsky')
                ),
                'sameAs' => array(
                    'https://www.facebook.com/MetroManhattanOfficeSpace',
                    'https://x.com/MetroManhattan',
                    'https://www.youtube.com/@MetroManhattanOfficeSpace',
                    'https://www.youtube.com/@MetroManhattanForTenants',
                    'https://www.linkedin.com/company/metro-manhattan-office-space'
                ),
                'contactPoint' => array(
                    '@type' => 'ContactPoint',
                    'telephone' => '+1-212-444-2241',
                    'contactType' => 'sales',
                    'areaServed' => 'US',
                    'availableLanguage' => 'English'
                ),
                'knowsAbout' => array(
                    'Commercial Real Estate',
                    'Office Space Leasing',
                    'Midtown Manhattan',
                    'Class A Office Buildings',
                    'Commercial Property Management'
                )
            );

            // Get location terms for breadcrumb schema
            $terms = wp_get_object_terms($building_id, 'location', ['orderby' => 'parent']);
            if (!empty($terms) && isset($terms[0]) && $terms[0]->parent == 0) {
                array_shift($terms);
            }

            // Create breadcrumb schema
            $breadcrumb_schema = array(
                '@type' => 'BreadcrumbList',
                '@id' => $this->context->canonical . '#breadcrumb',
                'itemListElement' => array(
                    array(
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => home_url()
                    ),
                    array(
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Neighborhoods',
                        'item' => home_url('neighborhoods/')
                    )
                )
            );

            // Add location terms to breadcrumb schema
            if (!empty($terms)) {
                $position = 3;
                foreach ($terms as $term) {
                    $page_location = get_field('page_id', $term);
                    if ($page_location) {
                        $breadcrumb_schema['itemListElement'][] = array(
                            '@type' => 'ListItem',
                            'position' => $position++,
                            'name' => $term->name,
                            'item' => get_permalink($page_location)
                        );
                    }
                }
            }

            // Add current building to breadcrumb schema
            $building_name = get_field('building_name', $building_id);
            $address = get_field('address', $building_id);
            $breadcrumb_name = !empty($building_name) ? $building_name : $address;

            $breadcrumb_schema['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => count($breadcrumb_schema['itemListElement']) + 1,
                'name' => $breadcrumb_name,
                'item' => $this->context->canonical
            );

            return array($building_schema, $breadcrumb_schema);
        }
    }

    /**
     * Adds the building schema piece to the schema collector.
     *
     * @param array  $pieces  The current graph pieces.
     * @param string $context The current context.
     *
     * @return array The graph pieces.
     */
    function add_building_schema_to_yoast($pieces, $context) {
        $pieces[] = new Building_Schema_Piece($context);
        return $pieces;
    }
    add_filter('wpseo_schema_graph_pieces', 'add_building_schema_to_yoast', 11, 2);

    // Create a custom schema piece class for blog posts
    class BlogPost_Schema_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {
        /**
         * Determines whether or not this piece should be added to the graph.
         *
         * @return bool
         */
        public function is_needed() {
            return is_singular('post');
        }

        /**
         * Generates the schema data.
         *
         * @return array The schema data.
         */
        public function generate() {
            global $post;
            $post_id = $post->ID;

            // Get post data
            $post_title = get_the_title($post_id);
            $post_excerpt = get_the_excerpt($post_id);
            $post_date = get_the_date('c', $post_id);
            $post_modified = get_the_modified_date('c', $post_id);
            $post_url = get_permalink($post_id);

            // Get featured image data
            $featured_image_id = get_post_thumbnail_id($post_id);
            $featured_image_url = get_the_post_thumbnail_url($post_id, 'full');
            $featured_image_meta = wp_get_attachment_metadata($featured_image_id);

            // Get author data
            $author_id = get_post_field('post_author', $post_id);
            $author_name = get_the_author_meta('display_name', $author_id);
            $author_url = get_author_posts_url($author_id);

            // Get primary category
            $categories = get_the_category($post_id);
            $primary_category = !empty($categories) ? $categories[0] : null;

            $site_icon_id = get_option( 'site_icon' );
            $site_icon_url = $site_icon_id ? wp_get_attachment_url( $site_icon_id ) : '';

            // Create BlogPosting schema
            $article_schema = array(
                '@type' => 'BlogPosting',
                '@id' => $post_url . '#blogposting',
                'headline' => $post_title,
                'description' => $post_excerpt,
                'datePublished' => $post_date,
                'dateModified' => $post_modified,
                'isAccessibleForFree' => true,
                'author' => array(
                    array(
                        '@type' => 'Person',
                        'name' => $author_name,
                        'url' => $author_url
                    )
                ),
                'publisher' => array(
                    '@type' => 'Organization',
                    'name' => 'Metro Manhattan Office Space',
                    'url' => home_url(),
                    'logo' => array(
                        '@type' => 'ImageObject',
                        'url' => $site_icon_url
                    ),
                    'sameAs' => array(
                        'https://www.facebook.com/MetroManhattanOfficeSpace',
                        'https://x.com/MetroManhattan',
                        'https://www.youtube.com/@MetroManhattanOfficeSpace',
                        'https://www.youtube.com/@MetroManhattanForTenants',
                        'https://www.linkedin.com/company/metro-manhattan-office-space'
                    )
                ),
                'mainEntityOfPage' => array(
                    '@type' => 'WebPage',
                    '@id' => $post_url
                ),
                'inLanguage' => 'en-US'
            );

            // Add featured image if exists
            if ($featured_image_url) {
                $article_schema['image'] = array(
                    '@type' => 'ImageObject',
                    '@id' => $post_url . '#primaryimage',
                    'url' => $featured_image_url,
                    'contentUrl' => $featured_image_url,
                    'width' => isset($featured_image_meta['width']) ? $featured_image_meta['width'] : 1200,
                    'height' => isset($featured_image_meta['height']) ? $featured_image_meta['height'] : 900,
                    'caption' => get_the_title($featured_image_id)
                );
            }

            // Add word count
            $article_schema['wordCount'] = str_word_count(strip_tags(get_the_content()));

            // Return only the article schema, letting Yoast handle the breadcrumb
            return array($article_schema);
        }
    }

    /**
     * Adds the blog page schema pieces to the schema collector.
     *
     * @param array  $pieces  The current graph pieces.
     * @param string $context The current context.
     *
     * @return array The graph pieces.
     */
    function add_blog_schemas_to_yoast($pieces, $context) {
        $pieces[] = new BlogPost_Schema_Piece($context);
        return $pieces;
    }
    add_filter('wpseo_schema_graph_pieces', 'add_blog_schemas_to_yoast', 11, 2);

    // Create a custom schema piece class for the main blog page
    class BlogPage_Schema_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {
        /**
         * Determines whether or not this piece should be added to the graph.
         *
         * @return bool
         */
        public function is_needed() {
            return is_home() || is_front_page();
        }

        /**
         * Generates the schema data.
         *
         * @return array The schema data.
         */
        public function generate() {
            $site_icon_id = get_option( 'site_icon' );
            $site_icon_url = $site_icon_id ? wp_get_attachment_url( $site_icon_id ) : '';

            $blog_schema = array(
                '@type' => 'Blog',
                '@id' => home_url('/blog/') . '#blog',
                'name' => 'Metro Manhattan Blog',
                'description' => 'Insightful articles on office leasing, market trends, and commercial real estate in NYC.',
                'url' => home_url('/blog/'),
                'publisher' => array(
                    '@type' => 'Organization',
                    'name' => 'Metro Manhattan Office Space',
                    'url' => home_url(),
                    'logo' => array(
                        '@type' => 'ImageObject',
                        'url' => $site_icon_url,
                        'width' => 240,
                        'height' => 76,
                        'caption' => 'Metro Manhattan Office Space, Inc.'
                    ),
                    'sameAs' => array(
                        'https://www.facebook.com/MetroManhattanOfficeSpace',
                        'https://x.com/MetroManhattan',
                        'https://www.youtube.com/@MetroManhattanOfficeSpace',
                        'https://www.youtube.com/@MetroManhattanForTenants',
                        'https://www.linkedin.com/company/metro-manhattan-office-space'
                    )
                ),
                'about' => array(
                    '@type' => 'Thing',
                    'name' => 'NYC Commercial Real Estate',
                    'description' => 'Commercial real estate and office space leasing in New York City'
                ),
                'inLanguage' => 'en-US'
            );

            return array($blog_schema);
        }
    }

    // Create a custom schema piece class for category pages
    class CategoryPage_Schema_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {
        /**
         * Determines whether or not this piece should be added to the graph.
         *
         * @return bool
         */
        public function is_needed() {
            return is_category();
        }

        /**
         * Generates the schema data.
         *
         * @return array The schema data.
         */
        public function generate() {
            global $wp_query;
            $category = get_queried_object();
            $category_url = get_category_link($category->term_id);

            // Get posts in this category
            $posts = $wp_query->posts;
            $blog_posts = array();

            $site_icon_id = get_option( 'site_icon' );
            $site_icon_url = $site_icon_id ? wp_get_attachment_url( $site_icon_id ) : '';

            foreach ($posts as $post) {
                $blog_posts[] = array(
                    '@type' => 'BlogPosting',
                    '@id' => get_permalink($post->ID) . '#blogposting',
                    'headline' => get_the_title($post->ID),
                    'description' => get_the_excerpt($post->ID),
                    'datePublished' => get_the_date('c', $post->ID),
                    'dateModified' => get_the_modified_date('c', $post->ID),
                    'url' => get_permalink($post->ID),
                    'author' => array(
                        array(
                            '@type' => 'Person',
                            'name' => get_the_author_meta('display_name', $post->post_author),
                            'url' => get_author_posts_url($post->post_author)
                        )
                    )
                );
            }

            // Create Blog schema
            $category_schema = array(
                '@type' => 'Blog',
                '@id' => $category_url . '#blog',
                'name' => $category->name,
                'description' => $category->description ?: 'News and updates on New York City\'s office leasing and property market.',
                'url' => $category_url,
                'publisher' => array(
                    '@type' => 'Organization',
                    'name' => 'Metro Manhattan Office Space',
                    'url' => home_url(),
                    'logo' => array(
                        '@type' => 'ImageObject',
                        'url' => $site_icon_url,
                        'width' => 240,
                        'height' => 76,
                        'caption' => 'Metro Manhattan Office Space, Inc.'
                    ),
                    'sameAs' => array(
                        'https://www.facebook.com/MetroManhattanOfficeSpace',
                        'https://x.com/MetroManhattan',
                        'https://www.youtube.com/@MetroManhattanOfficeSpace',
                        'https://www.youtube.com/@MetroManhattanForTenants'
                    )
                ),
                'inLanguage' => 'en-US'
            );

            // Add blog posts if we have them
            if (!empty($blog_posts)) {
                $category_schema['blogPost'] = $blog_posts;
            }

            // Create breadcrumb schema
            $breadcrumb_schema = array(
                '@type' => 'BreadcrumbList',
                '@id' => $category_url . '#breadcrumb',
                'itemListElement' => array(
                    array(
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => home_url()
                    ),
                    array(
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'News & Articles',
                        'item' => home_url('blog/')
                    ),
                    array(
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $category->name,
                        'item' => $category_url
                    )
                )
            );

            return array($category_schema, $breadcrumb_schema);
        }
    }

    /**
     * Adds the category page schema piece to the schema collector.
     *
     * @param array  $pieces  The current graph pieces.
     * @param string $context The current context.
     *
     * @return array The graph pieces.
     */
    function add_category_schema_to_yoast($pieces, $context) {
        $pieces[] = new CategoryPage_Schema_Piece($context);
        return $pieces;
    }
    add_filter('wpseo_schema_graph_pieces', 'add_category_schema_to_yoast', 11, 2);

    // Create a custom schema piece class for resource pages
    class ResourcePage_Schema_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {
        /**
         * Determines whether or not this piece should be added to the graph.
         *
         * @return bool
         */
        public function is_needed() {
            if (!is_page()) return false;
            $slug = basename(get_permalink());
            $resource_slugs = [
                'office-space-calculator',
                'commute-calculator',
                'listyourspace',
                'good-to-know',
            ];
            return in_array($slug, $resource_slugs, true);
        }

        /**
         * Generates the schema data.
         *
         * @return array The schema data.
         */
        public function generate() {
            global $post;
            $page_id = $post->ID;
            $page_url = get_permalink($page_id);
            $slug = basename($page_url);
            $site_icon_id = get_option( 'site_icon' );
            $site_icon_url = $site_icon_id ? wp_get_attachment_url( $site_icon_id ) : '';

            $org = [
                '@type' => 'Organization',
                'name' => 'Metro Manhattan Office Space, Inc.',
                'url' => home_url(),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $site_icon_url,
                    'width' => 240,
                    'height' => 76,
                    'caption' => 'Metro Manhattan Office Space, Inc.'
                ],
                'sameAs' => [
                    'https://www.facebook.com/MetroManhattanOfficeSpace',
                    'https://x.com/MetroManhattan',
                    'https://www.youtube.com/@MetroManhattanOfficeSpace',
                    'https://www.youtube.com/@MetroManhattanForTenants',
                    'https://www.linkedin.com/company/metro-manhattan-office-space'
                ]
            ];
            $common = [
                'publisher' => $org,
                'author' => $org,
                'isAccessibleForFree' => true,
                'url' => $page_url,
                'inLanguage' => 'en-US',
            ];
            if ($slug === 'office-space-calculator') {
                $schema = array_merge([
                    '@type' => 'SoftwareApplication',
                    'name' => 'Office Space Calculator',
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'All',
                    'description' => 'Calculate your office space needs based on number of employees, offices, and common areas.'
                ], $common);
            } elseif ($slug === 'commute-calculator') {
                $schema = array_merge([
                    '@type' => 'SoftwareApplication',
                    'name' => 'Commute Calculator',
                    'applicationCategory' => 'BusinessApplication',
                    'applicationSubCategory' => 'Commute Estimator',
                    'operatingSystem' => 'All',
                    'description' => 'Estimate your daily commute time by entering addresses and transportation method. Compare locations and optimize your office search.'
                ], $common);
            } elseif ($slug === 'listyourspace') {
                $schema = array_merge([
                    '@type' => 'WebPage',
                    'name' => 'List Your Space',
                    'about' => 'Commercial Property Listing Submission',
                    'potentialAction' => [
                        '@type' => 'SendAction',
                        'target' => $page_url . '#form',
                        'description' => 'Submit your commercial property for listing consideration.'
                    ]
                ], $common);
            } elseif ($slug === 'good-to-know') {
                $schema = array_merge([
                    '@type' => 'CollectionPage',
                    'name' => 'Good to Know',
                    'about' => 'NYC Commercial Leasing Educational Content',
                    // Optionally, add mainEntity with key articles or services here
                ], $common);
            } else {
                // fallback
                $schema = array_merge([
                    '@type' => 'WebPage',
                    'name' => get_the_title($page_id),
                    'description' => get_the_excerpt($page_id)
                ], $common);
            }
            return [$schema];
        }
    }

    /**
     * Adds the resource page schema piece to the schema collector.
     *
     * @param array  $pieces  The current graph pieces.
     * @param string $context The current context.
     *
     * @return array The graph pieces.
     */
    function add_resource_schema_to_yoast($pieces, $context) {
        $pieces[] = new ResourcePage_Schema_Piece($context);
        return $pieces;
    }
    add_filter('wpseo_schema_graph_pieces', 'add_resource_schema_to_yoast', 11, 2);
}

// in wp-content/themes/metro/functions.php
add_action( 'wp_footer', function() {
    ?>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        if ( typeof window.rocketLazyRender === 'function' ) {
          window.rocketLazyRender();
        }
      });
    </script>
    <?php
  } );
  
  function add_custom_listings_pagination_links( $query ) {
    if ( ! is_admin() && $query->get('custom_listings_query') ) {
      // Get the current page (if not specified - 1)
      $paged = get_query_var('paged') ? intval( get_query_var('paged') ) : 1;
      if ( $paged < 1 ) {
          $paged = 1;
      }

      // Total number of pages
      $max_pages = intval( $query->max_num_pages );
      if ( $max_pages <= 1 ) {
          return; // No pagination — nothing added
      }

      $html = '';
      // Prev
      if ( $paged > 1 ) {
          $html .= '<link rel="prev" href="' . esc_url( get_pagenum_link( $paged - 1 ) ) . '" />' . "\n";
      }
      // Next
      if ( $paged < $max_pages ) {
          $html .= '<link rel="next" href="' . esc_url( get_pagenum_link( $paged + 1 ) ) . '" />' . "\n";
      }

      $GLOBALS['custom_listings_query'] = $html;
    }

    return $query;
  }
  add_action( 'pre_get_posts', 'add_custom_listings_pagination_links' );
  
  add_action( 'wp_head', function() {
    echo '<!--CUSTOM_LISTINGS_LINKS_PLACEHOLDER-->';
  } );

  add_action( 'template_redirect', function () {
    ob_start(function($buffer){
        if (! empty( $GLOBALS['custom_listings_query'] )) {
            return str_replace('<!--CUSTOM_LISTINGS_LINKS_PLACEHOLDER-->', $GLOBALS['custom_listings_query'], $buffer);
        } else {
          return str_replace('<!--CUSTOM_LISTINGS_LINKS_PLACEHOLDER-->', '', $buffer);
        }

        return $buffer;
    });

    if ( is_home() && is_paged() ) {
      add_action( 'wp_head', function () {
        if ( is_home() && is_paged() ) {
          $url = get_pagenum_link( get_query_var( 'paged' ) );
          //   echo '<link rel="canonical" href="' . esc_url( $url ) . "\" />\n";
        }
      }, 5 );

      add_action( 'wp_head', function () {
        global $wp_query;
        $paged     = get_query_var( 'paged' );
        $max_pages = $wp_query->max_num_pages;

        if ( $paged > 1 ) {
          $prev_url = ( $paged - 1 === 1 )
            ? get_permalink( get_option( 'page_for_posts' ) )
            : get_pagenum_link( $paged - 1 );
          //   echo '<link rel="prev" href="' . esc_url( $prev_url ) . "\" />\n";
        }

        if ( $paged < $max_pages ) {
          //   echo '<link rel="next" href="' . esc_url( get_pagenum_link( $paged + 1 ) ) . "\" />\n";
        }
      }, 1 );
    }
  }, 1 );

  add_filter( 'wpseo_opengraph_url', function ( $url ) {
    if ( is_home() && is_paged() ) {
      return get_pagenum_link( get_query_var( 'paged' ) );
    }

    return $url;
  } );

  add_filter( 'wpseo_schema_webpage', function ( $data ) {
    if ( is_home() && is_paged() ) {
      $paged_url   = get_pagenum_link( get_query_var( 'paged' ) );
      $data['@id'] = $paged_url . '#webpage';
      $data['url'] = $paged_url;
    }

    return $data;
  } );

// 30 May 2025
add_action( 'wp_footer', 'metro_email_reveal_script' );
function metro_email_reveal_script() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
      var span = document.getElementById('email-placeholder');
      if (!span) return;
      var addr = span.getAttribute('data-email');
      var a    = document.createElement('a');
      a.href   = 'mailto:' + addr;
      a.textContent = addr;
      span.parentNode.replaceChild(a, span);
    });
    </script>
    <?php
}

function mm_record_gone_and_purge_cache( int $post_id ) {
    if ( get_post_type( $post_id ) !== 'post' ) {
        error_log( "mm_record_gone: skipped non-post (ID={$post_id})" );
        return;
    }

    $permalink = get_permalink( $post_id );
    if ( ! $permalink ) {
        error_log( "mm_record_gone: no permalink for post ID={$post_id}" );
        return;
    }

    $path = untrailingslashit( parse_url( $permalink, PHP_URL_PATH ) );
    error_log( "mm_record_gone: recording gone path '{$path}' for post ID={$post_id}" );

    $gone = (array) get_option( 'mm_gone_paths', [] );
    if ( ! in_array( $path, $gone, true ) ) {
        $gone[] = $path;
        update_option( 'mm_gone_paths', $gone );
        error_log( "mm_record_gone: added '{$path}' to mm_gone_paths" );
    } else {
        error_log( "mm_record_gone: '{$path}' was already in mm_gone_paths" );
    }

    if ( function_exists( 'rocket_clean_post' ) ) {
        error_log( "mm_record_gone: calling rocket_clean_post({$post_id})" );
        rocket_clean_post( $post_id );
    } else {
        error_log( "mm_record_gone: firing rocket_clean_post action for {$post_id}" );
        do_action( 'rocket_clean_post', $post_id );
    }
    error_log( "mm_record_gone: firing rocket_clean_home" );
    do_action( 'rocket_clean_home' );
}
add_action( 'trashed_post', 'mm_record_gone_and_purge_cache' );
add_action( 'before_delete_post', 'mm_record_gone_and_purge_cache' );


function mm_send_410_for_gone_paths() {
    if ( ! is_404() ) {
        return;
    }

    $gone = (array) get_option( 'mm_gone_paths', [] );
    if ( empty( $gone ) ) {
        return;
    }

    $req = untrailingslashit( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );

    if ( in_array( $req, $gone, true ) ) {
        status_header( 410 );
        nocache_headers();

        $template = locate_template( [ '410.php' ] );
        if ( $template ) {
            load_template( $template );
        } else {
            echo '<h1>410 Gone</h1><p>This content has been permanently removed.</p>';
        }

        exit;
    }
}
add_action( 'template_redirect', 'mm_send_410_for_gone_paths' );

/**
 * Handle favorites page access control and SEO
 * Redirects non-logged-in users and ensures proper noindex
 */
function handle_favorites_page_access() {
    if ( is_page( 'favorites' ) || strpos( $_SERVER['REQUEST_URI'], '/favorites/' ) !== false ) {
        echo '<meta name="robots" content="noindex, nofollow">' . "\n";
        if ( ! is_user_logged_in() ) {
            wp_redirect( home_url(), 302 );
            exit;
        }
    }

// 	if ( isset($_GET['loginSocial'])
//       && stripos($_SERVER['HTTP_USER_AGENT'],'Googlebot') !== false ) {
//         require_once ABSPATH . 'wp-login.php';
//         exit;
//     }
}

add_action( 'template_redirect', 'handle_favorites_page_access', 1 );

add_action('template_redirect', function() {
    remove_filter('template_redirect', 'redirect_canonical');
}, 0);

// add_action('login_head', function(){
//     echo "<meta name=\"robots\" content=\"noindex, nofollow\" />\n";
//     echo '<link rel="canonical" href="https://www.metro-manhattan.com/wp-login.php" />' . "\n";
// });

add_action( 'template_redirect', function () {
    $uri = $_SERVER['REQUEST_URI'];
    if ( preg_match( '#/page/\d+(/|$)#i', $uri ) ) {
        return;
    }
    if ( preg_match( '#/\d+/?$#', $uri ) ) {
        status_header( 410 );
        nocache_headers();

        $template = locate_template( [ '410.php' ] );
        if ( $template ) {
            load_template( $template );
        } else {
            echo '<h1>410 Gone</h1><p>This content has been permanently removed.</p>';
        }

        exit;
    }
});

add_action( 'wp_head', 'metro_manhattan_preload_fonts', 1 );
function metro_manhattan_preload_fonts() {
  echo '<link rel="preload" href="' . get_stylesheet_directory_uri() . '/dist/assets/Lato-Bold-LiaCKUiW.woff2" as="font" type="font/woff2" crossorigin>';
  echo '<link rel="preload" href="' . get_stylesheet_directory_uri() . '/dist/assets/Merriweather-Bold-B6KNAFHG.woff2" as="font" type="font/woff2" crossorigin>';
}

add_action( 'wp_head', 'metro_manhattan_add_favicon' );

function metro_manhattan_add_favicon() {
    ?>
    <link rel="icon" type="image/png" href="<?php echo get_template_directory_uri(); ?>/assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?php echo get_template_directory_uri(); ?>/assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo get_template_directory_uri(); ?>/assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="MM" />
    <link rel="manifest" href="<?php echo get_template_directory_uri(); ?>/assets/images/favicon/site.webmanifest" />
    <?php
}

add_filter('wpseo_canonical', function ($canonical) {
    if (is_paged()) {
        global $wp;
        return trailingslashit(home_url($wp->request));
    }
    return $canonical;
}, 10, 1);

// Append "– Page N" to the <title> tag
add_filter('wpseo_title', function ($title) {
    if (is_paged()) {
        $obj = get_queried_object();
        $alternative_title = '';

        if ( is_category() ) {
            if ( $obj instanceof WP_Term ) {
                $term_id = $obj->term_id;
                $term_taxonomy = $obj->taxonomy;
                $alternative_title = get_field( 'pagination_page_title', $term_taxonomy . '_' . $term_id );
            }
        } else {
            $blog_page_id = get_option( 'page_for_posts' );
            if ( $blog_page_id == $obj->ID ) {
                $alternative_title = get_field( 'pagination_page_title', $blog_page_id );
            } else {
                $alternative_title = get_field( 'pagination_page_title' );
            }
        }
        
        $paged = get_query_var('paged');
        $title = $alternative_title ?: $title;
        return $title . ' – Page ' . $paged;
    }
    return $title;
});

// Append "– Page N" to the meta description
add_filter('wpseo_metadesc', function ($description) {
    if (is_paged()) {
         $obj = get_queried_object();
        $alternative_description = '';

        if ( is_category() ) {
            if ( $obj instanceof WP_Term ) {
                $term_id = $obj->term_id;
                $term_taxonomy = $obj->taxonomy;
                $alternative_description = get_field( 'pagination_page_description', $term_taxonomy . '_' . $term_id );
            }
        } else {
            $blog_page_id = get_option( 'page_for_posts' );
            if ( $blog_page_id == $obj->ID ) {
                $alternative_description = get_field( 'pagination_page_description', $blog_page_id );
            } else {
                $alternative_description = get_field( 'pagination_page_description' );
            }
        }

        $paged = get_query_var('paged');
        $description = $alternative_description ?: $description;
        return $description . ' – Page ' . $paged;
    }
    return $description;
});


/**
 * Handle login page routing
 * Redirects /login/ URL to a page with the Login Page template
 */
function handle_login_page_routing() {
    $request_uri = $_SERVER['REQUEST_URI'];

    // Check if the request is for /login/ (with or without trailing slash)
    if (preg_match('#^/login/?(?:\?.*)?$#', $request_uri)) {
        // Create a virtual page for login
        global $wp_query;

        // Set up the query as if it's a page
        $wp_query->is_page = true;
        $wp_query->is_single = false;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        $wp_query->is_search = false;
        unset( $wp_query->query['error'] );
        $wp_query->query_vars['error'] = '';
        $wp_query->is_404 = false;

        // Load the login page template
        $template = locate_template('page-login.php');
        if ($template) {
            load_template($template);
            exit;
        } else {
            // Fallback if template not found
            wp_die('Login page template not found. Please contact the administrator.');
        }
    }
}
// add_action('template_redirect', 'handle_login_page_routing', 5);

add_filter( 'generate_rewrite_rules', function ( $wp_rewrite ) {
    $wp_rewrite->rules = array_merge(
        ['login/?$' => 'index.php?custom_login=1'],
        $wp_rewrite->rules
    );
});

add_filter( 'query_vars', function( $query_vars ) {
    $query_vars[] = 'custom_login';
    return $query_vars;
});

add_action( 'template_redirect', function() {
    $custom = intval( get_query_var( 'custom_login' ) );
    if ( $custom ) {
        add_filter( 'body_class', function( $classes ) {
            return array_merge( $classes, ['page-login'] );
        } );
        load_template( locate_template('page-login.php') );
        die;
    }
});

function login_page_robots_noindex( $robots_string ) {
    if ( intval( get_query_var( 'custom_login' ) ) ) {
        $robots_string = 'noindex, nofollow';
    }
    return $robots_string;
}

add_filter( 'wpseo_robots', 'login_page_robots_noindex' );

add_filter( 'pre_get_document_title', function( $title ) {
    if ( intval( get_query_var( 'custom_login' ) ) ) {
        $title = 'Log In / Sign Up';
    }

    return $title;
}, 20 );

/**
 * Check if current environment matches given value(s).
 *
 * @param string|array $env  Environment name or list of names ('production', 'staging', 'local', 'development'.).
 *
 * @return bool
 */
function mm_is_env( string|array $env ): bool {
    static $current = null;

    if ( $current === null ) {
        $current = wp_get_environment_type();
    }

    return is_array( $env )
        ? in_array( $current, $env, true )
        : $current === $env;
}

/**
 * Add pagination pages to the Yoast SEO sitemap.
 *
 * @param string $output The current URL output for the sitemap entry.
 * @param string $url    The original URL before any modifications.
 *
 * @return string Modified URL output for the sitemap entry.
 */
add_filter( 'wpseo_sitemap_url', function ($output, $url) {
    if ( empty( $url['loc'] ) ) {
        return $output;
    }

    $page_id = url_to_postid( $url['loc'] );
    $cat_id = null;

    if ( ! $page_id ) {
        $page_for_posts = get_option( 'page_for_posts' );

        if ( $url['loc'] === get_the_permalink( $page_for_posts ) ) {
            $page_id = $page_for_posts;
        }
        
        $term = get_term_by( 'slug', basename( untrailingslashit( $url['loc'] ) ), 'category' );
        
        if ( $term && ! is_wp_error( $term ) ) {
            $cat_id = $term->term_id;
        }
    }

    $post = get_post( $page_id );
    $total_pages = 0;
    $numberposts = get_field( 'numberposts', $page_id ?: get_option( 'page_for_posts' ) );

    if ( $post && $post->post_type == 'page' ) {
        if ( ! $numberposts ) {
            return $output;
        }

        if ( $page_id !== get_option( 'page_for_posts' ) ) {
            $listings_type = get_field( 'type_term', $page_id );
            $listings_location = get_field( 'location_term', $page_id );
            $filter_by = get_field( 'filter_listing_by', $page_id );
            $result = MetroManhattanHelpers::get_listings_by_taxonomy(
                $filter_by,
                $filter_by === 'location' ? $listings_location : $listings_type,
                0,
                $numberposts
            );
            $total_pages = ceil( $result['total'] / $numberposts );
        } else {
            // Blog
            $query = new WP_Query([
                'posts_per_page' => $numberposts,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]);
            $total_pages = $query->max_num_pages;
        }
    } elseif ( $cat_id ) {
        // Category
        $query = new WP_Query([
            'cat'            => $cat_id,
            'posts_per_page' => $numberposts,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        $total_pages = $query->max_num_pages;
    }

    if ( $total_pages < 2 ) {
        return $output;
    }

    for ( $i = 2; $i <= $total_pages; $i++ ) {
        $page_url = $page_id ? get_permalink( $page_id ) : get_term_link( $cat_id );

        $last_query = new WP_Query([
            'post_type'      => $page_id ? 'page' : 'post',
            'posts_per_page' => 1,
            'paged'          => $i,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ]);

        // Date of last modification of the post on this page
        $lastmod = ! empty( $last_query->posts )
            ? get_post_modified_time( 'c', true, $last_query->posts[0] )
            : current_time( 'c' );
        
        $date = function_exists( 'YoastSEO' )
            ? YoastSEO()->helpers->date->format( $lastmod )
            : $lastmod;

        $output .= "\t<url>\n";
        $output .= "\t\t<loc>" . trailingslashit( $page_url ) . "page/{$i}/</loc>\n";
        $output .= "\t\t<lastmod>" . esc_html( $date ) . "</lastmod>\n";
        $output .= "\t\t<changefreq>weekly</changefreq>\n";
        $output .= "\t\t<priority>0.8</priority>\n";
        $output .= "\t</url>\n";

        wp_reset_postdata();
    }

    return $output;
}, 10, 2 );
