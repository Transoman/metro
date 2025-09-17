<?php
  
  /**
   * File: metro/blocks/search-results-section/template.php
   *
   * Base Block Template.
   *
   * @param array $block The block settings and attributes.
   * @param string $content The block inner HTML (empty).
   * @param bool $is_preview True during backend preview render.
   * @param int $post_id The post ID the block is rendering content against.
   *          This is either the post ID currently being displayed inside a query loop,
   *          or the post ID of the post hosting this block.
   * @param array $context The context provided to the block by the post or its parent block.
   */
  
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  
  // Support custom "anchor" values.
  $anchor = '';
  if ( ! empty( $block['anchor'] ) ) {
    $anchor = 'id="' . esc_attr( $block['anchor'] ) . '" ';
  }
  
  // Create class attribute allowing for custom "className" and "align" values.
  $class_name = 'search-results';
  if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
  }
  
  if ( ! empty( $block['align'] ) ) {
    $class_name .= ' align' . $block['align'];
  }
  
  $helper                 = new MetroManhattanHelpers();
  $toggle_view            = get_field( 'toggle_view' );
  $unauthorized_condition = ( is_admin() ) ? ! empty( $toggle_view ) : ( ! is_user_logged_in() && ! empty( $_POST['is_search_form_submit'] ) );
  $type_taxonomy          = $helper->get_hierarchically_taxonomy( 'listing-type' );
  $location_taxonomy      = $helper->get_hierarchically_taxonomy( 'location', 70 );
  $numberposts            = ( $unauthorized_condition ) ? 6 : (int) get_field( 'numberposts' );
  $filters                = ( ! empty( $_POST['filter'] ) ) ? $_POST['filter'] : ( ! empty( $_SESSION['filter'] ) ? $_SESSION['filter'] : null );
  $filter_string          = $helper::build_filters( $filters );
  $current_page           = ( ! is_admin() ) ? ( ( ! empty( get_query_var( 'paged' ) ) ) ? get_query_var( 'paged' ) : 1 ) : (int) get_query_var( 'paged' );
  $offset                 = ( $current_page * $numberposts ) - $numberposts;
  $result                 = $helper->get_listings_search_result( $filters, $offset, $numberposts );
  $pagination             = $helper->get_pagination_of_search_result( $result['total'], $current_page, $numberposts, get_permalink( get_the_ID() ) );
  $unauthorized_list      = get_field( 'list' );
  $set_password           = get_query_var( 'reset-password' );
  $class_name             = ( $unauthorized_condition ) ? $class_name . ' unauthorized' : $class_name;
?>
<section <?php echo $anchor; ?> data-target="search_form_section" class="<?php echo esc_attr( $class_name ); ?>">
    <div class="filters">
        <div class="container">
            <div class="content">
                <h1 data-target="result_string" class="result-title"><?php echo $filter_string ?></h1>
                <div class="controllers">
                    <div class="sort">
                        <span class="sort-by-label">Sort by</span>
                        <style>
                            .custom-select.active .demo-wrapper {
                                display: block;
                                position: absolute;
                                left: 0;
                                top: calc(100% + 8px);
                                width: 100%;
                                min-width: 380px;
                                background: var(--mm-primary-bg-color);
                                border: 1px solid var(--mm-border-color);
                                border-radius: 8px;
                                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
                                z-index: 10;
                                transform-origin: top center;
                                animation: scaleIn 0.15s ease-out;
                            }

                            @keyframes scaleIn {
                                from {
                                    transform: scale(0.95);
                                    opacity: 0;
                                }
                                to {
                                    transform: scale(1);
                                    opacity: 1;
                                }
                            }

                            .demo-wrapper {
                                display: none;
                                padding: 12px 0;
                            }

                            .demo {
                                padding: 8px 0;
                                border-bottom: 1px solid var(--mm-border-color);
                                width: 100%;
                            }

                            .demo:last-child {
                                border-bottom: 0;
                            }

                            .demo:not(:first-child) {
                                margin-top: 4px;
                            }

                            .demo li {
                                list-style-type: none;
                                padding: 12px 12px;
                                margin: 4px 0;
                                color: var(--mm-text-color);
                                font-size: 0.95rem;
                                cursor: pointer;
                                transition: all 0.2s ease;
                                display: flex;
                                align-items: center;
                                gap: 8px;
                            }

                            .demo li:hover {
                                background: #0961afbf;
                                color: white;
                            }

                            .demo li.active {
                                background: #0961afbf;
                                color: white;
                                font-weight: 500;
                            }

                            .demo li[data-value="DESC"]:hover::before,
                            .demo li[data-value="price_desc"]:hover::before,
                            .demo li[data-value="sqft_desc"]:hover::before,
                            .demo li[data-value="monthly_rent_desc"]:hover::before {
                                background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23fff"><path d="M11 5v11.17l-4.88-4.88c-.39-.39-1.03-.39-1.42 0-.39.39-.39 1.02 0 1.41l6.59 6.59c.39.39 1.02.39 1.41 0l6.59-6.59c.39-.39.39-1.02 0-1.41-.39-.39-1.02-.39-1.41 0L13 16.17V5c0-.55-.45-1-1-1s-1 .45-1 1z"/></svg>');
                            }

                            .demo li[data-value="ASC"]:hover::before,
                            .demo li[data-value="price_asc"]:hover::before,
                            .demo li[data-value="sqft_asc"]:hover::before,
                            .demo li[data-value="monthly_rent_asc"]:hover::before {
                                background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23fff"><path d="M13 19v-11.17l4.88 4.88c.39.39 1.03.39 1.42 0 .39-.39.39-1.02 0-1.41l-6.59-6.59c-.39-.39-1.02-.39-1.41 0l-6.59 6.59c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0L11 7.83V19c0 .55.45 1 1 1s1-.45 1-1z"/></svg>');
                            }

                            .demo li::before {
                                content: '';
                                display: inline-block;
                                width: 18px;
                                height: 18px;
                                background-size: contain;
                                background-repeat: no-repeat;
                            }

                            .demo li[data-value="DESC"]::before,
                            .demo li[data-value="price_desc"]::before,
                            .demo li[data-value="sqft_desc"]::before,
                            .demo li[data-value="monthly_rent_desc"]::before {
                                background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11 5v11.17l-4.88-4.88c-.39-.39-1.03-.39-1.42 0-.39.39-.39 1.02 0 1.41l6.59 6.59c.39.39 1.02.39 1.41 0l6.59-6.59c.39-.39.39-1.02 0-1.41-.39-.39-1.02-.39-1.41 0L13 16.17V5c0-.55-.45-1-1-1s-1 .45-1 1z"/></svg>');
                            }

                            .demo li[data-value="ASC"]::before,
                            .demo li[data-value="price_asc"]::before,
                            .demo li[data-value="sqft_asc"]::before,
                            .demo li[data-value="monthly_rent_asc"]::before {
                                background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13 19v-11.17l4.88 4.88c.39.39 1.03.39 1.42 0 .39-.39.39-1.02 0-1.41l-6.59-6.59c-.39-.39-1.02-.39-1.41 0l-6.59 6.59c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0L11 7.83V19c0 .55.45 1 1 1s1-.45 1-1z"/></svg>');
                            }

                            .demo {
                                display: inline-block;
                                white-space: nowrap;
                            }

                            .demo li {
                                display: inline-block;
                                margin: 0 4px;
                            }

                            .demo-table {
                                display: table;
                                width: 100%;
                                border-collapse: collapse;
                            }

                            .demo {
                                display: table-row;
                            }

                            .demo span.label {
                                display: table-cell;
                                vertical-align: middle;
                                padding: 0.5rem;
                            }

                            .demo li {
                                display: table-cell;
                                vertical-align: middle;
                                padding: 0.5rem;
                            }

                            @media (max-width: 600px) {
                                .custom-select.active .demo-wrapper {
                                    position: static;
                                    margin-top: 8px;
                                    width: 100%;
                                    min-width: auto;
                                    box-shadow: none;
                                    border-radius: 0;
                                    border: none;
                                }

                                .sort-by-label {
                                    display: none;
                                }
                            }
                        </style>
                        <div data-target="default_custom_select" class="custom-select">
                            <div class="placeholder">
                                <span>Newest</span>
                                <svg width="14" height="9" viewBox="0 0 14 9" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 1L7 7L13 1" stroke="var(--mm-blue-color)" stroke-width="2"></path>
                                </svg>
                            </div>
                            <div class="demo-wrapper">
                                <div class="demo-table">
                                    <div class="demo">
                                        <span class="label">Date:</span>
                                        <li data-value="ASC">Oldest</li>
                                        <li data-value="DESC">Newest</li>
                                    </div>
                                    <div class="demo">
                                        <span class="label">Price/SF:</span>
                                        <li data-value="price_asc">Low</li>
                                        <li data-value="price_desc">High</li>
                                    </div>
                                    <div class="demo">
                                        <span class="label">Monthly Rent:</span>
                                        <li data-value="monthly_rent_asc">Low</li>
                                        <li data-value="monthly_rent_desc">High</li>
                                    </div>
                                    <div class="demo">
                                        <span class="label">Square Footage:</span>
                                        <li data-value="sqft_asc">Smallest</li>
                                        <li data-value="sqft_desc">Largest</li>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tabs">
                        <button aria-label="List view" type="button" data-target="list_view" class="tab active">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path d="M16.6608 2.53125H6.9375V3.70744H16.6608V2.53125Z"
                                      fill="var(--mm-grey-color)"/>
                                <path
                                        d="M0.75 3.11899C0.75 3.58424 0.888246 4.03905 1.14725 4.4259C1.40626 4.81275 1.7744 5.11426 2.20512 5.29231C2.63584 5.47035 3.10978 5.51694 3.56703 5.42617C4.02428 5.3354 4.44428 5.11136 4.77394 4.78237C5.1036 4.45339 5.3281 4.03423 5.41905 3.57791C5.51 3.1216 5.46332 2.64861 5.28491 2.21877C5.1065 1.78893 4.80438 1.42153 4.41674 1.16305C4.02911 0.904567 3.57337 0.766602 3.10717 0.766602C2.48223 0.767302 1.88308 1.01537 1.44117 1.45637C0.999271 1.89738 0.750702 2.49531 0.75 3.11899ZM3.10717 1.94279C3.34027 1.94279 3.56814 2.01178 3.76196 2.14102C3.95577 2.27026 4.10684 2.45396 4.19604 2.66888C4.28525 2.8838 4.30858 3.12029 4.26311 3.34845C4.21763 3.57661 4.10538 3.78619 3.94056 3.95068C3.77573 4.11517 3.56572 4.22719 3.3371 4.27258C3.10848 4.31796 2.8715 4.29467 2.65614 4.20565C2.44079 4.11662 2.25672 3.96587 2.12721 3.77244C1.99771 3.57902 1.92859 3.35161 1.92859 3.11899C1.92894 2.80715 2.05322 2.50818 2.27417 2.28768C2.49512 2.06718 2.7947 1.94314 3.10717 1.94279Z"
                                        fill="var(--mm-grey-color)"/>
                                <path d="M16.6608 8.41162H6.9375V9.58781H16.6608V8.41162Z"
                                      fill="var(--mm-grey-color)"/>
                                <path
                                        d="M3.10717 11.3522C3.57337 11.3522 4.02911 11.2143 4.41674 10.9558C4.80438 10.6973 5.1065 10.3299 5.28491 9.90006C5.46332 9.47022 5.51 8.99724 5.41905 8.54092C5.3281 8.0846 5.1036 7.66545 4.77394 7.33646C4.44428 7.00747 4.02428 6.78343 3.56703 6.69266C3.10978 6.6019 2.63584 6.64848 2.20512 6.82653C1.7744 7.00457 1.40626 7.30608 1.14725 7.69293C0.888246 8.07978 0.75 8.53459 0.75 8.99985C0.750702 9.62352 0.999271 10.2215 1.44117 10.6625C1.88308 11.1035 2.48223 11.3515 3.10717 11.3522ZM3.10717 7.82365C3.34027 7.82365 3.56814 7.89264 3.76196 8.02188C3.95577 8.15112 4.10684 8.33482 4.19604 8.54974C4.28525 8.76466 4.30858 9.00115 4.26311 9.22931C4.21763 9.45747 4.10538 9.66705 3.94056 9.83154C3.77573 9.99603 3.56572 10.1081 3.3371 10.1534C3.10848 10.1988 2.8715 10.1755 2.65614 10.0865C2.44079 9.99748 2.25672 9.84673 2.12721 9.6533C1.99771 9.45988 1.92859 9.23248 1.92859 8.99985C1.92894 8.68801 2.05322 8.38904 2.27417 8.16854C2.49512 7.94804 2.7947 7.824 3.10717 7.82365Z"
                                        fill="var(--mm-grey-color)"/>
                                <path d="M16.6608 14.293H6.9375V15.4692H16.6608V14.293Z" fill="var(--mm-grey-color)"/>
                                <path
                                        d="M3.10717 17.2331C3.57337 17.2331 4.02911 17.0951 4.41674 16.8366C4.80438 16.5782 5.1065 16.2108 5.28491 15.7809C5.46332 15.3511 5.51 14.8781 5.41905 14.4218C5.3281 13.9655 5.1036 13.5463 4.77394 13.2173C4.44428 12.8883 4.02428 12.6643 3.56703 12.5735C3.10978 12.4828 2.63584 12.5293 2.20512 12.7074C1.7744 12.8854 1.40626 13.1869 1.14725 13.5738C0.888246 13.9606 0.75 14.4154 0.75 14.8807C0.750702 15.5044 0.999271 16.1023 1.44117 16.5433C1.88308 16.9843 2.48223 17.2324 3.10717 17.2331ZM3.10717 13.7045C3.34027 13.7045 3.56814 13.7735 3.76196 13.9027C3.95577 14.032 4.10684 14.2157 4.19604 14.4306C4.28525 14.6455 4.30858 14.882 4.26311 15.1102C4.21763 15.3383 4.10538 15.5479 3.94056 15.7124C3.77573 15.8769 3.56572 15.9889 3.3371 16.0343C3.10848 16.0797 2.8715 16.0564 2.65614 15.9674C2.44079 15.8783 2.25672 15.7276 2.12721 15.5342C1.99771 15.3407 1.92859 15.1133 1.92859 14.8807C1.92894 14.5689 2.05322 14.2699 2.27417 14.0494C2.49512 13.8289 2.7947 13.7049 3.10717 13.7045Z"
                                        fill="var(--mm-grey-color)"/>
                            </svg>
                            <span>List</span>
                        </button>
                        <button aria-label="Map view" type="button" data-target="map_view" class="tab">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                        d="M9.28921 4.21973C8.80226 4.21973 8.32625 4.3637 7.92136 4.63345C7.51648 4.90319 7.20091 5.28659 7.01456 5.73516C6.82822 6.18373 6.77946 6.67732 6.87446 7.15352C6.96946 7.62971 7.20394 8.06713 7.54827 8.41045C7.89259 8.75377 8.33129 8.98757 8.80888 9.08229C9.28648 9.17701 9.78152 9.1284 10.2314 8.9426C10.6813 8.75679 11.0658 8.44215 11.3363 8.03845C11.6069 7.63474 11.7513 7.16012 11.7513 6.67459C11.7505 6.02375 11.4909 5.39978 11.0293 4.93956C10.5678 4.47934 9.94196 4.22047 9.28921 4.21973ZM9.28921 7.90203C9.04573 7.90203 8.80773 7.83004 8.60529 7.69517C8.40284 7.5603 8.24506 7.3686 8.15189 7.14431C8.05871 6.92003 8.03433 6.67323 8.08183 6.43513C8.12933 6.19703 8.24658 5.97833 8.41874 5.80667C8.5909 5.63501 8.81025 5.51811 9.04905 5.47074C9.28784 5.42338 9.53536 5.44769 9.7603 5.54059C9.98524 5.6335 10.1775 5.79082 10.3128 5.99267C10.448 6.19452 10.5202 6.43183 10.5202 6.67459C10.5199 7.00001 10.39 7.312 10.1593 7.54211C9.92848 7.77221 9.61558 7.90165 9.28921 7.90203Z"
                                        fill="var(--mm-grey-color)"/>
                                <path
                                        d="M14.0198 1.95335C12.8501 0.787305 11.2886 0.0946267 9.63674 0.00900306C7.98489 -0.0766206 6.35978 0.450881 5.07506 1.4897C3.79034 2.52852 2.93709 4.00501 2.67998 5.63421C2.42286 7.26341 2.78012 8.92982 3.6828 10.3118L8.33405 17.4314C8.43756 17.5899 8.57908 17.72 8.7458 17.8101C8.91251 17.9002 9.09914 17.9474 9.28877 17.9474C9.4784 17.9474 9.66503 17.9002 9.83175 17.8101C9.99846 17.72 10.14 17.5899 10.2435 17.4314L14.8949 10.3118C15.7325 9.02982 16.1026 7.4997 15.9433 5.97793C15.784 4.45617 15.1049 3.03525 14.0198 1.95335ZM13.8635 9.64184L9.28879 16.644L4.71409 9.64184C3.3138 7.49848 3.61413 4.63012 5.42821 2.82126C5.93518 2.31575 6.53705 1.91476 7.19945 1.64118C7.86185 1.3676 8.57181 1.22679 9.28879 1.22679C10.0058 1.22679 10.7157 1.3676 11.3781 1.64118C12.0405 1.91476 12.6424 2.31575 13.1494 2.82126C14.9635 4.63012 15.2637 7.49848 13.8635 9.64184Z"
                                        fill="var(--mm-grey-color)"/>
                            </svg>
                            <span>Map</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="listings-map">
        <div class="listings">
            <div class="container">
                <div class="content">
                    <div data-target="ajax_wrapper" class="blocks">
                      <?php if ( is_array( $result['listings'] ) && ! empty( $result['listings'] ) ): ?>
                        <?php foreach ( $result['listings'] as $listing ):
                          get_template_part( 'templates/parts/listing', 'card', [
                            'id'                  => $listing->ID,
                            'map_card'            => false,
                            'favourites_template' => false
                          ] );
                        endforeach; ?>
                      <?php else: ?>
                          <span class="no-results">No listings found</span>
                      <?php endif; ?>
                    </div>
                  <?php if ( ! $unauthorized_condition ): ?>
                      <div data-target="pagination_wrapper" class="pagination">
                        <?php if ( $result['total'] > $numberposts ): ?>
                          <?php echo $pagination ?>
                        <?php endif; ?>
                      </div>
                  <?php endif; ?>
                  <?php if ( $unauthorized_condition ): ?>
                      <div class="section-overlay"></div>
                  <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="map">
            <div class="close-btn">
                <button aria-label="Close map" data-target="close_map">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                                d="M20.0001 1.32074L18.6793 0L10.0001 8.6792L1.32086 0L0 1.32074L8.67926 10L0 18.6793L1.32086 20L10.0001 11.3208L18.6793 20L20.0001 18.6793L11.3209 10L20.0001 1.32074Z"
                                fill="var(--mm-blue-color)"/>
                    </svg>
                </button>
            </div>
            <div class="map-wrapper">
                <div data-clustering-marker="<?php echo get_field( 'clustering_marker' )['url'] ?>"
                     data-marker="<?php echo get_field( 'marker_image' )['url'] ?>"
                     data-active-marker="<?php echo get_field( 'active_marker_image' )['url'] ?>"
                     data-info='<?php echo json_encode( $result['coordinates'] ) ?>' data-target="google_map"></div>
                <div data-target="mobile_card" class="mobile-card"></div>
            </div>
        </div>
    </div>
  <?php if ( ! empty( $unauthorized_condition ) ): ?>
      <div class="unauthorized-section">
          <div class="container">
              <div class="content">
                  <div data-target="steps" class="steps">
                      <div data-step="first" class="step <?php echo ( ! empty( $set_password ) ) ? 'hide' : '' ?>">
                          <div class="step-content">
                              <h2>Create Account or Log In to View All Listings</h2>
                            <?php echo get_template_part( 'templates/forms/check', 'registration' ) ?>
                          </div>
                        <?php if ( ! empty( $unauthorized_list ) ): ?>
                            <ul class="list">
                              <?php foreach ( $unauthorized_list as $item ):
                                if ( ! empty( $item['text'] ) ): ?>
                                    <li>
                                        <span><?php echo esc_html( $item['text'] ) ?></span>
                                    </li>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                      </div>
                      <div data-step="login" class="step hide">
                          <div class="step-content">
                              <button class="step-back hide-button" type="button"></button>
                              <h2>Log In to View All Listings</h2>
                              <div class="wrapper">
                                  <form autocomplete="off" data-target="authorization_form" action="#">
                                      <input name="mm_authorization_nonce"
                                             value="<?php echo wp_create_nonce( 'mm-authorization-nonce' ) ?>"
                                             type="hidden">
                                      <input type="hidden" name="mm_authorization_redirect_to"
                                             value="<?php echo get_permalink( get_field( 'choose_search_page', 'option' ) ) ?>">
                                      <input name="action" value="mm_authorization_user" type="hidden">
                                      <div data-input="email" class="input">
                                          <input placeholder="Email" data-field="email" name="mm_authorization_email"
                                                 readonly type="text">
                                      </div>
                                      <div data-input="password" class="input">
                                          <div data-target="password_input" class="input-wrapper">
                                              <input name="mm_authorization_password" data-field="password"
                                                     autocomplete="off" spellcheck="false" readonly
                                                     onfocus="this.removeAttribute('readonly');"
                                                     placeholder="Enter Password"
                                                     type="password">
                                              <button type="button" class="toggle hide-button"></button>
                                          </div>
                                      </div>
                                      <div class="message">
                                          <span>At least 8 characters: a mix of letters and numbers.</span>
                                      </div>
                                      <button type="submit" class="primary-button">Sign in</button>
                                      <div class="bottom">
                                          <button class="hide-button" data-target="forgot_pass" type="button">Forgot
                                              your
                                              password?
                                          </button>
                                      </div>
                                  </form>
                                  <div class="social-buttons">
                                    <?php
                                      if ( class_exists( 'NextendSocialLogin', false ) ) {
                                        echo NextendSocialLogin::renderButtonsWithContainer();
                                      }
                                    ?>
                                  </div>
                              </div>
                          </div>
                        <?php if ( ! empty( $unauthorized_list ) ): ?>
                            <ul class="list">
                              <?php foreach ( $unauthorized_list as $item ):
                                if ( ! empty( $item['text'] ) ): ?>
                                    <li>
                                        <span><?php echo esc_html( $item['text'] ) ?></span>
                                    </li>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                      </div>
                      <div data-step="register" class="step hide">
                          <div class="step-content">
                              <button class="step-back hide-button" type="button"></button>
                              <h2>Create Account to View All Listings</h2>
                              <div class="wrapper">
                                  <form autocomplete="off" data-target="registration_form" method="post" action="#">
                                      <input name="mm_registration_nonce"
                                             value="<?php echo wp_create_nonce( 'mm-registration-nonce' ) ?>"
                                             type="hidden">
                                      <input name="action" value="mm_registration_user" type="hidden">
                                      <div data-input="email" class="input">
                                          <input placeholder="Email" name="mm_registration_email" data-field="email"
                                                 readonly type="text">
                                      </div>
                                      <input name="mm_registration_first_name" placeholder="First Name" class="half"
                                             type="text">
                                      <input name="mm_registration_last_name" placeholder="Last Name" class="half"
                                             type="text">
                                      <div data-input="password" class="input">
                                          <div data-target="password_input" class="input-wrapper">
                                              <input name="mm_registration_password" data-field="password"
                                                     autocomplete="off" spellcheck="false" placeholder="Create Password"
                                                     type="password">
                                              <button type="button" class="toggle hide-button"></button>
                                          </div>
                                      </div>
                                      <div class="message">
                                          <span>At least 8 characters: a mix of letters and numbers.</span>
                                      </div>
                                      <button type="submit" class="primary-button">Create Account</button>
                                  </form>
                                  <div class="social-buttons">
                                    <?php
                                      if ( class_exists( 'NextendSocialLogin', false ) ) {
                                        echo NextendSocialLogin::renderButtonsWithContainer();
                                      }
                                    ?>
                                  </div>
                              </div>
                          </div>
                        <?php if ( ! empty( $unauthorized_list ) ): ?>
                            <ul class="list">
                              <?php foreach ( $unauthorized_list as $item ):
                                if ( ! empty( $item['text'] ) ): ?>
                                    <li>
                                        <span><?php echo esc_html( $item['text'] ) ?></span>
                                    </li>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                      </div>
                      <div data-step="reset" class="step hide">
                          <button class="step-back hide-button" type="button"></button>
                          <h2>Reset Password</h2>
                          <div class="wrapper">
                              <form autocomplete="off" data-target="reset_password_form" method="post" action="#">
                                  <input name="mm_reset_password_nonce"
                                         value="<?php echo wp_create_nonce( 'mm-reset-password-nonce' ) ?>"
                                         type="hidden">
                                  <input name="action" value="mm_reset_password_user" type="hidden">
                                  <input type="hidden" name="mm_reset_password_template" value="search">
                                  <p>Enter your email and we’ll send you a link to reset your password</p>
                                  <div data-input="email" class="input">
                                      <input name="mm_reset_password_email" data-field="email"
                                             placeholder="Email Address*" type="text">
                                  </div>
                                  <button class="primary-button" type="submit">Send Email</button>
                              </form>
                          </div>
                      </div>
                      <div data-step="send_reset" class="step hide">
                          <button class="step-back hide-button" type="button"></button>
                          <h2>Reset Password</h2>
                          <div class="wrapper">
                              <h3>Email sent</h3>
                              <p>Check your email and open the link we sent to continue</p>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  <?php endif; ?>
</section>