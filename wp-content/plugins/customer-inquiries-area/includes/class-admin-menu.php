<?php
  // includes/class-admin-menu.php
  
  /**
   * Register Admin Menu and the Search Stats page.
   */
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  add_action( 'admin_menu', 'cia_register_admin_menu' );
  
  function cia_register_admin_menu() {
    add_menu_page(
      'Search Log',
      'Search Stats',
      'activate_plugins',
      'search-stats',
      'cia_render_search_stats_page',
      '',
      70
    );
  }
  
  function cia_render_search_stats_page() {
    // Instantiate our list table class
    $searchListTable = new Search_List_Table();
    
    if ( isset( $_GET['session_id'] ) && ! empty( $_GET['session_id'] ) ) {
      $searchListTable->prepare_items_without_username();
    } else {
      $searchListTable->prepare_items();
    }
    
    $message = '';
    if ( 'delete' === $searchListTable->current_action() ) {
      $delete_count = isset( $_REQUEST['id'] ) ? count( (array) $_REQUEST['id'] ) : 0;
      $message      = '<div class="updated below-h2" id="message"><p>'
                      . sprintf( __( 'Items deleted: %d', 'cltd_example' ), $delete_count )
                      . '</p></div>';
    }
    $base_url = admin_url( 'admin.php?page=unregistered-users' );
    ?>
      <div class="wrap">
          <h2>Search Log</h2>
        <?php echo $message; ?>
        <?php if ( isset( $_GET['session_id'] ) && ! empty( $_GET['session_id'] ) ) : ?>
            <a href="<?php echo esc_url( $base_url ); ?>" class="button button-secondary" style="margin-bottom: 10px;">Back</a>
        <?php endif; ?>
          <form method="GET">
            <?php
              // Ensure we keep the "page" hidden input for the correct screen
              $searchListTable->search_box( __( 'Search' ), 'search-box-id' );
            ?>
              <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>"/>
          </form>

          <button class="btn-generate button-primary js-csv">Download Filtered CSV</button>
          <form id="search-log" method="get">
              <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>"/>
            <?php $searchListTable->display(); ?>
          </form>

          <script>
            jQuery(document).ready(function ($) {
              $('.js-csv').on('click', function () {
                jQuery.ajax({
                  method: "POST",
                  url: ajaxurl,
                  data: {
                    action: 'generate-search-stats-csv',
                    filter: <?php echo json_encode( $_GET ); ?>,
                  },
                  success: function (data) {
                    const link = document.createElement("a");
                    link.download = 'search-stats.csv';
                    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(data);
                    
                    document.body.appendChild(link);
                    link.click();
                    
                    document.body.removeChild(link);
                    link.remove();
                  }
                });
              });
            });
          </script>

          <style>
              .widefat tfoot tr td, .widefat tfoot tr th, .widefat thead tr td, .widefat thead tr th {
                  width: auto !important;
              }

              .widefat .check-column {
                  width: 2.2em !important;
              }
          </style>
      </div>
    <?php
  }
