<?php
  get_header( 'search' );
  $helper = new MetroManhattanHelpers();
  
  $search_page = get_field( 'choose_search_page', 'option' );
  $search_page = get_permalink( $search_page );
  
  $google_map_api_key     = get_field( 'google_map_api_key', 'option' );
  $listing_images         = get_field( 'images' );
  $listing_types          = get_the_terms( get_the_ID(), 'listing-type' );
  $listing_month_rent     = get_field( 'monthly_rent' );
  $listing_square_feet    = get_field( 'square_feet' );
  $listing_matterport     = get_field( 'matterport' );
  $listing_id             = get_field( 'listing_id' );
  $listing_available      = get_field( 'available' );
  $listing_suite_floor    = get_field( 'suite_floor' );
  $listing_rent_sf        = get_field( 'rent_sf' );
  $listing_lease_type     = get_field( 'lease_type' );
  $listing_lease_term     = get_field( 'lease_term' );
  $listing_address        = get_field( 'address' );
  $listing_features       = get_the_terms( get_the_ID(), 'feature' );
  $listing_coordinates    = get_field( 'map' );
  $listing_transportation = get_field( 'transportation' );
  $listing_call_request   = get_field( 'call_request' );
  
  $phone_button = get_field( 'phone_button', 'option' );
  
  $filter_prices = $helper->get_filters_fields( 'enter_prices' );
  $filter_sizes  = $helper->get_filters_fields( 'enter_sizes' );
  $current_price = $helper->compare_price_values( $listing_month_rent, $filter_prices );
  $current_size  = $helper->compare_size_values( $listing_square_feet, $filter_sizes );
  
  $breadcrumbs = $helper->listing_breadcrumbs( get_the_ID() );
  
  $listing_neighborhood       = $helper->listing_parent_term( get_the_ID(), 'location' );
  $listing_child_neighborhood = $helper->listing_child_neighborhood( get_the_ID(), $listing_neighborhood );
  
  $listing_nearby_offices = [];
  if ( isset( $listing_child_neighborhood ) && is_object( $listing_child_neighborhood ) && isset( $listing_child_neighborhood->term_id ) ) {
    $listing_nearby_offices = $helper->get_posts_by_mode( 'nearby', [
      'post_type' => 'listings',
      'location'  => $listing_child_neighborhood->term_id,
      'exclude'   => get_the_ID()
    ] );
  }
  $listing_featured_listings = $helper->get_posts_by_mode( 'featured', [
    'post_type'   => 'listings',
    'exclude'     => get_the_ID(),
    'numberposts' => 6
  ] );
  
  $listing_floorplan = get_field( 'floorplan' );
  
  
  $global_form_title     = get_field( 'global_sidebar_title', 'option' );
  if ( $global_form_title == 'Listing Inquiry' ) {
    $global_form_title = 'Listing Inquiry';
  }
  $global_form_text      = get_field( 'global_sidebar_text', 'option' );
  if ( $global_form_text == 'Call us at 1 (212) 444-2241 or connect with us to learn more or book a tour.' ) {
    $global_form_text = 'Call us at +1 (917) 292-9171 for pricing, availability, or to book a tour.';
  }
  $global_form_shortcode = get_field( 'global_sidebar_shortcode', 'option' );
  
  $global_aside_thank_you_title = get_field( 'global_thanks_title', 'option' );
  $global_aside_thank_you_text  = get_field( 'global_thanks_text', 'option' );
  
  $global_schedule_title     = get_field( 'global_schedule_title', 'option' );
  $global_schedule_text      = get_field( 'global_schedule_text', 'option' );
  $global_schedule_shortcode = get_field( 'global_schedule_shortcode', 'option' );
  
  $global_featured_listings_button = get_field( 'featured_listings_button', 'option' );
  $global_offices_nearby_button    = get_field( 'offices_nearby_button', 'option' );
  
  
  $nearby_transport = ( ! empty( $listing_coordinates['lat'] ) && ! empty( $listing_coordinates['lng'] ) ) ? $helper::get_nearby_transport( $listing_coordinates['lat'], $listing_coordinates['lng'] ) : [];
  
  
  $current_user_id = wp_get_current_user()->ID;
  $target          = '';
  if ( is_user_logged_in() ) {
    $target = 'add_to_favourites';
  } else {
    $target = 'authorization_button';
  }
  
  $schema_product = [
    '@context'    => 'https://schema.org/',
    '@type'       => 'Product',
    'name'        => get_the_title(),
    'image'       => [],
    'description' => get_the_excerpt(),
    'offers'      => [
      '@type'         => 'Offer',
      'url'           => get_the_permalink(),
      'priceCurrency' => 'USD',
      'price'         => round( floatval( $listing_month_rent ), 2 )
    ]
  ];
  
  foreach ( $listing_images as $image ) {
    $schema_product['image'][] = wp_get_attachment_url( $image['id'] );
  }
  
  $schema_real_estate = [
    '@context'    => 'https://schema.org/',
    '@type'       => 'RealEstateListing',
    'url'         => get_the_permalink(),
    'name'        => get_the_title(),
    'description' => get_the_excerpt(),
    'datePosted'  => get_the_date( 'c' ),
    'leaseLength' => [
      '@type'    => 'QuantitativeValue',
      'value'    => 1,
      'unitText' => 'month'
    ]
  ];
  
  // Retrieve the displayed listing types from post meta
    $displayed_listing_types = get_post_meta( get_the_ID(), 'listing_type_shown_on_post', true );
    if ( is_serialized( $displayed_listing_types ) ) {
        $displayed_listing_types = unserialize( $displayed_listing_types );
    } elseif ( ! is_array( $displayed_listing_types ) ) {
        $displayed_listing_types = [];
    }
  
  // Initialize an array to store final sorted listing types
  $final_listing_types = [];
  
  if ( ! empty( $displayed_listing_types ) && is_array( $displayed_listing_types ) ) {
    // Get the terms for the listing types in the order defined by $displayed_listing_types
    $final_listing_types = get_terms( [
      'taxonomy'   => 'listing-type',
      'include'    => $displayed_listing_types,
      'orderby'    => 'include', // Ensures terms are returned in the same order as in $displayed_listing_types
      'hide_empty' => false,
    ] );
  } elseif ( ! empty( $listing_types ) && is_array( $listing_types ) ) {
    // Fallback: Use the existing $listing_types if no specific ordering is defined
    $final_listing_types = $listing_types;
  }
?>

<main>
  <?php
    get_template_part( 'templates/parts/notification', 'template' );
  ?>
    <article class="single-custom-post">
        <div class="container">
            <div class="wrapper">
                <section class="single-custom-post-section">
                    <header class="single-header">
                      <?php echo $breadcrumbs ?>
                        <h1>
                          <?php echo get_the_title() ?>
                        </h1>
                        <div class="buttons">
                            <button aria-label="Save" data-id="<?php echo get_the_ID() ?>"
                                    data-target="<?php echo $target ?>"
                                    class="like <?php echo ( MMFavourites::is_listing_favourite( $current_user_id, get_the_ID() ) ) ? 'liked' : '' ?>">
                                <svg width="20" height="18" viewBox="0 0 20 18" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <g>
                                        <g>
                                            <path data-target="background"
                                                  d="M10.001 17L17.7906 8.25728C18.5651 7.38621 19 6.20594 19 4.97541C19 3.74488 18.5652 2.56458 17.7908 1.69346L17.4931 1.35942C17.1091 0.928431 16.6533 0.586546 16.1515 0.3533C15.6498 0.120053 15.112 0 14.5689 0C14.0258 0 13.4881 0.120053 12.9864 0.3533C12.4847 0.586546 12.0288 0.928431 11.6448 1.35942L9.99603 3.2322L8.3551 1.35695C7.57935 0.487634 6.5278 -0.000442583 5.43157 2.33272e-05C4.33532 0.000489238 3.2841 0.489439 2.50895 1.35942L2.21124 1.6935C1.4357 2.56392 1 3.74444 1 4.9754C1 6.20635 1.4357 7.38687 2.21124 8.25728L10.001 17Z"
                                                  fill="white"/>
                                            <path
                                                    d="M18.1596 1.55613C17.666 1.06254 17.08 0.671051 16.435 0.404043C15.79 0.137034 15.0988 -0.000261748 14.4008 3.74622e-07C13.7027 0.000262497 13.0116 0.138078 12.3668 0.40557C11.722 0.673063 11.1363 1.06499 10.6431 1.55895L9.99927 2.21062L9.3608 1.56036L9.35665 1.55621C8.86329 1.06285 8.27759 0.671497 7.63299 0.404493C6.98839 0.137489 6.29751 6.32352e-05 5.5998 6.32352e-05C4.90208 6.32352e-05 4.2112 0.137489 3.5666 0.404493C2.922 0.671497 2.3363 1.06285 1.84294 1.55621L1.55613 1.84303C0.559755 2.8394 0 4.19077 0 5.59986C0 7.00894 0.559755 8.36031 1.55613 9.35669L9.12599 16.9265L9.98084 17.8221L10.0012 17.8017L10.0233 17.8238L10.8243 16.9788L18.4464 9.35657C19.4413 8.3594 20 7.00832 20 5.59973C20 4.19114 19.4413 2.84007 18.4464 1.8429L18.1596 1.55613ZM17.507 8.41737L10.0012 15.9233L2.49528 8.41737C1.748 7.67009 1.32819 6.65657 1.32819 5.59976C1.32819 4.54294 1.748 3.52942 2.49528 2.78214L2.78214 2.49532C3.52905 1.74841 4.54197 1.32863 5.59826 1.32823C6.65455 1.32783 7.66778 1.74686 8.41526 2.4932L9.9964 4.10316L11.5851 2.49532C11.9551 2.1253 12.3944 1.83178 12.8778 1.63153C13.3613 1.43128 13.8794 1.32821 14.4027 1.32821C14.926 1.32821 15.4442 1.43128 15.9276 1.63153C16.4111 1.83178 16.8503 2.1253 17.2203 2.49532L17.5072 2.7821C18.2533 3.52999 18.6723 4.54332 18.6723 5.59977C18.6723 6.65622 18.2532 7.66952 17.507 8.41737Z"
                                                    fill="white"/>
                                        </g>
                                    </g>
                                    <defs>
                                        <clipPath>
                                            <rect width="20" height="18" fill="white"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                                <span>Save</span>
                            </button>
                            <button class="print-button" data-id="<?php echo get_the_ID(); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                          d="M9.16155 1H14.8386C15.3658 0.999983 15.8206 0.999967 16.1951 1.03057C16.5905 1.06287 16.9837 1.13419 17.3621 1.32698C17.9266 1.6146 18.3855 2.07354 18.6731 2.63803C18.8659 3.01641 18.9372 3.40963 18.9695 3.80497C19.0001 4.17954 19.0001 4.6343 19.0001 5.16144V6.02714C19.0868 6.03193 19.1708 6.03757 19.2519 6.0442C19.814 6.09012 20.3307 6.18869 20.8161 6.43598C21.5687 6.81947 22.1806 7.43139 22.5641 8.18404C22.8114 8.66938 22.91 9.18608 22.9559 9.74818C23.0001 10.2894 23.0001 10.9537 23.0001 11.7587V14C23.0001 14.0465 23.0001 14.0924 23.0001 14.1376C23.0006 14.933 23.0009 15.5236 22.8638 16.0353C22.4939 17.4156 21.4157 18.4938 20.0354 18.8637C19.7279 18.9461 19.3919 18.9789 19.0001 18.9918C18.9998 19.4543 18.9971 19.8573 18.9695 20.195C18.9372 20.5904 18.8659 20.9836 18.6731 21.362C18.3855 21.9265 17.9266 22.3854 17.3621 22.673C16.9837 22.8658 16.5905 22.9371 16.1951 22.9694C15.8206 23 15.3658 23 14.8387 23H9.16153C8.63439 23 8.17964 23 7.80507 22.9694C7.40972 22.9371 7.0165 22.8658 6.63812 22.673C6.07364 22.3854 5.6147 21.9265 5.32708 21.362C5.13428 20.9836 5.06297 20.5904 5.03067 20.195C5.00307 19.8573 5.00037 19.4543 5.00012 18.9918C4.6083 18.9789 4.27229 18.9461 3.96482 18.8637C2.58445 18.4938 1.50626 17.4156 1.13639 16.0353C0.999297 15.5236 0.999617 14.933 1.00005 14.1376C1.00007 14.0924 1.0001 14.0465 1.0001 14L1.0001 11.7587C1.00008 10.9537 1.00007 10.2894 1.04429 9.74818C1.09022 9.18608 1.18878 8.66938 1.43607 8.18404C1.81956 7.43139 2.43149 6.81947 3.18413 6.43598C3.66947 6.18869 4.18617 6.09012 4.74827 6.0442C4.82942 6.03757 4.91335 6.03193 5.0001 6.02714L5.0001 5.16146C5.00008 4.63431 5.00006 4.17955 5.03067 3.80497C5.06297 3.40963 5.13428 3.01641 5.32708 2.63803C5.6147 2.07354 6.07364 1.6146 6.63812 1.32698C7.0165 1.13419 7.40972 1.06287 7.80507 1.03057C8.17964 0.999967 8.6344 0.999983 9.16155 1ZM7.0001 6H17.0001V5.2C17.0001 4.62345 16.9993 4.25117 16.9762 3.96784C16.954 3.69617 16.9163 3.59546 16.8911 3.54601C16.7952 3.35785 16.6422 3.20487 16.4541 3.109C16.4046 3.0838 16.3039 3.04612 16.0323 3.02393C15.7489 3.00078 15.3766 3 14.8001 3H9.2001C8.62354 3 8.25127 3.00078 7.96793 3.02393C7.69627 3.04612 7.59555 3.0838 7.54611 3.109C7.35794 3.20487 7.20496 3.35785 7.10909 3.54601C7.0839 3.59546 7.04622 3.69617 7.02402 3.96784C7.00087 4.25118 7.0001 4.62345 7.0001 5.2V6ZM7.0001 18.8C7.0001 19.3766 7.00087 19.7488 7.02402 20.0322C7.04622 20.3038 7.0839 20.4045 7.10909 20.454C7.20496 20.6422 7.35794 20.7951 7.54611 20.891C7.59555 20.9162 7.69627 20.9539 7.96793 20.9761C8.25127 20.9992 8.62354 21 9.2001 21H14.8001C15.3766 21 15.7489 20.9992 16.0323 20.9761C16.3039 20.9539 16.4046 20.9162 16.4541 20.891C16.6423 20.7951 16.7952 20.6422 16.8911 20.454C16.9163 20.4046 16.954 20.3038 16.9762 20.0322C16.9993 19.7488 17.0001 19.3766 17.0001 18.8V17.2C17.0001 16.6234 16.9993 16.2512 16.9762 15.9678C16.954 15.6962 16.9163 15.5955 16.8911 15.546C16.7952 15.3578 16.6423 15.2049 16.4541 15.109C16.4046 15.0838 16.3039 15.0461 16.0323 15.0239C15.7489 15.0008 15.3766 15 14.8001 15H9.2001C8.62354 15 8.25127 15.0008 7.96793 15.0239C7.69627 15.0461 7.59555 15.0838 7.54611 15.109C7.35794 15.2049 7.20496 15.3578 7.10909 15.546C7.0839 15.5955 7.04622 15.6962 7.02402 15.9678C7.00087 16.2512 7.0001 16.6234 7.0001 17.2V18.8ZM19.0001 16.9901C18.9998 16.5352 18.9968 16.1383 18.9695 15.805C18.9372 15.4096 18.8659 15.0164 18.6731 14.638C18.3855 14.0735 17.9266 13.6146 17.3621 13.327C16.9837 13.1342 16.5905 13.0629 16.1951 13.0306C15.8205 13 15.3658 13 14.8386 13H9.16157C8.63442 13 8.17965 13 7.80507 13.0306C7.40972 13.0629 7.0165 13.1342 6.63812 13.327C6.07364 13.6146 5.6147 14.0735 5.32708 14.638C5.13428 15.0164 5.06297 15.4096 5.03067 15.805C5.00343 16.1383 5.00044 16.5352 5.00013 16.9901C4.74254 16.981 4.60125 16.9637 4.48246 16.9319C3.79227 16.7469 3.25318 16.2078 3.06824 15.5176C3.00869 15.2954 3.0001 14.9944 3.0001 14V11.8C3.0001 10.9434 3.00087 10.3611 3.03765 9.91104C3.07347 9.47262 3.13839 9.24842 3.21808 9.09202C3.40983 8.7157 3.71579 8.40974 4.09212 8.21799C4.24852 8.1383 4.47272 8.07337 4.91113 8.03755C5.36122 8.00078 5.94352 8 6.8001 8H17.2001C18.0567 8 18.639 8.00078 19.0891 8.03755C19.5275 8.07337 19.7517 8.1383 19.9081 8.21799C20.2844 8.40974 20.5904 8.7157 20.7821 9.09202C20.8618 9.24842 20.9267 9.47262 20.9625 9.91104C20.9993 10.3611 21.0001 10.9434 21.0001 11.8V14C21.0001 14.9944 20.9915 15.2954 20.9319 15.5176C20.747 16.2078 20.2079 16.7469 19.5177 16.9319C19.3989 16.9637 19.2577 16.981 19.0001 16.9901ZM14.0001 10.5C14.0001 9.94772 14.4478 9.5 15.0001 9.5H18.0001C18.5524 9.5 19.0001 9.94772 19.0001 10.5C19.0001 11.0523 18.5524 11.5 18.0001 11.5H15.0001C14.4478 11.5 14.0001 11.0523 14.0001 10.5Z"
                                          fill="#0961AF"/>
                                </svg>
                                <span>Print</span>
                            </button>
                            <div class="share-wrapper">
                                <button aria-label="Share listing" type="button" data-target="share_listing">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                              d="M7.7587 2L10 2C10.5523 2 11 2.44772 11 3C11 3.55229 10.5523 4 10 4H7.8C6.94342 4 6.36113 4.00078 5.91104 4.03755C5.47262 4.07337 5.24842 4.1383 5.09202 4.21799C4.7157 4.40973 4.40973 4.7157 4.21799 5.09202C4.1383 5.24842 4.07337 5.47262 4.03755 5.91104C4.00078 6.36113 4 6.94342 4 7.8V16.2C4 17.0566 4.00078 17.6389 4.03755 18.089C4.07337 18.5274 4.1383 18.7516 4.21799 18.908C4.40973 19.2843 4.7157 19.5903 5.09202 19.782C5.24842 19.8617 5.47262 19.9266 5.91104 19.9624C6.36113 19.9992 6.94342 20 7.8 20H16.2C17.0566 20 17.6389 19.9992 18.089 19.9624C18.5274 19.9266 18.7516 19.8617 18.908 19.782C19.2843 19.5903 19.5903 19.2843 19.782 18.908C19.8617 18.7516 19.9266 18.5274 19.9624 18.089C19.9992 17.6389 20 17.0566 20 16.2V14C20 13.4477 20.4477 13 21 13C21.5523 13 22 13.4477 22 14V16.2413C22 17.0463 22 17.7106 21.9558 18.2518C21.9099 18.8139 21.8113 19.3306 21.564 19.816C21.1805 20.5686 20.5686 21.1805 19.816 21.564C19.3306 21.8113 18.8139 21.9099 18.2518 21.9558C17.7106 22 17.0463 22 16.2413 22H7.75868C6.95372 22 6.28936 22 5.74817 21.9558C5.18608 21.9099 4.66937 21.8113 4.18404 21.564C3.43139 21.1805 2.81947 20.5686 2.43598 19.816C2.18868 19.3306 2.09012 18.8139 2.04419 18.2518C1.99998 17.7106 1.99999 17.0463 2 16.2413V7.7587C1.99999 6.95373 1.99998 6.28937 2.04419 5.74817C2.09012 5.18608 2.18868 4.66937 2.43597 4.18404C2.81947 3.43139 3.43139 2.81947 4.18404 2.43597C4.66937 2.18868 5.18608 2.09012 5.74817 2.04419C6.28937 1.99998 6.95373 1.99999 7.7587 2ZM17.2929 2.29289C17.6834 1.90237 18.3166 1.90237 18.7071 2.29289L21.7071 5.29289C22.0976 5.68342 22.0976 6.31658 21.7071 6.70711L18.7071 9.70711C18.3166 10.0976 17.6834 10.0976 17.2929 9.70711C16.9024 9.31658 16.9024 8.68342 17.2929 8.29289L18.5858 7H17.8C16.9434 7 16.3611 7.00078 15.911 7.03755C15.4726 7.07337 15.2484 7.1383 15.092 7.21799C14.7157 7.40973 14.4097 7.7157 14.218 8.09202C14.1383 8.24842 14.0734 8.47262 14.0376 8.91104C14.0008 9.36113 14 9.94342 14 10.8V12C14 12.5523 13.5523 13 13 13C12.4477 13 12 12.5523 12 12V10.7587C12 9.95374 12 9.28938 12.0442 8.74818C12.0901 8.18608 12.1887 7.66937 12.436 7.18404C12.8195 6.43139 13.4314 5.81947 14.184 5.43597C14.6694 5.18868 15.1861 5.09012 15.7482 5.04419C16.2894 4.99998 16.9537 4.99999 17.7587 5L18.5858 5L17.2929 3.70711C16.9024 3.31658 16.9024 2.68342 17.2929 2.29289Z"
                                              fill="#0961AF"/>
                                    </svg>
                                    <span>Share</span>
                                </button>
                                <ul data-target="share_list">
                                    <li>
                                        <a aria-label="Share listing via email" href="#simple_contact_form">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                        d="M22 7L13.03 12.7C12.7213 12.8934 12.3643 12.996 12 12.996C11.6357 12.996 11.2787 12.8934 10.97 12.7L2 7M4 4H20C21.1046 4 22 4.89543 22 6V18C22 19.1046 21.1046 20 20 20H4C2.89543 20 2 19.1046 2 18V6C2 4.89543 2.89543 4 4 4Z"
                                                        stroke="#0961AF" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round"/>
                                            </svg>
                                            <span>Email</span>
                                        </a>
                                    </li>
                                    <li>
                                        <button aria-label="Copy link" type="button" data-target="copy_link">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                        d="M5 15H4C3.46957 15 2.96086 14.7893 2.58579 14.4142C2.21071 14.0391 2 13.5304 2 13V4C2 3.46957 2.21071 2.96086 2.58579 2.58579C2.96086 2.21071 3.46957 2 4 2H13C13.5304 2 14.0391 2.21071 14.4142 2.58579C14.7893 2.96086 15 3.46957 15 4V5M11 9H20C21.1046 9 22 9.89543 22 11V20C22 21.1046 21.1046 22 20 22H11C9.89543 22 9 21.1046 9 20V11C9 9.89543 9.89543 9 11 9Z"
                                                        stroke="#0961AF" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round"/>
                                            </svg>
                                            <span>Copy Link</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </header>
                    <div class="main-content">
                      <?php if ( is_array( $listing_images ) && sizeof( $listing_images ) > 0 ): ?>
                          <div class="listing-sliders">
                              <div class="close-btn">
                                  <button aria-label="Close full size mode" data-target="close_full_size" type="button">
                                      <svg width="25" height="25" viewBox="0 0 25 25" fill="none"
                                           xmlns="http://www.w3.org/2000/svg">
                                          <path
                                                  d="M24.5 2.08488L22.915 0.5L12.5 10.915L2.08502 0.5L0.5 2.08488L10.9151 12.4999L0.5 22.915L2.08502 24.4999L12.5 14.0849L22.915 24.4999L24.5 22.915L14.085 12.4999L24.5 2.08488Z"
                                                  fill="var(--mm-blue-color)"/>
                                      </svg>
                                  </button>
                              </div>
                              <div class="main-slider-wrapper">
                                  <div data-target="single_main_slider" class="main-slider swiper">
                                      <div class="swiper-wrapper">
                                        <?php
                                          $idx = 0;
                                          foreach ( $listing_images as $image ): ?>
                                              <div class="swiper-slide">
                                                  <div class="swiper-zoom-container" style="width:100%;">
                                                    <?php echo wp_get_attachment_image( $image['ID'], 'full', '', [ 'loading' => ( $idx == 0 ) ? 'eager' : 'lazy' ] ) ?>
                                                  </div>
                                              </div>
                                            <?php
                                            $idx ++;
                                          endforeach; ?>
                                      </div>
                                  </div>
                                <?php if ( sizeof( $listing_images ) > 1 ): ?>
                                    <button aria-label="Next slide" type="button" class="button next"
                                            data-target="main_slider_next_button">
                                        <svg width="15" height="24" viewBox="0 0 15 24" fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                    d="M2.51761 24L0.324219 21.7982L10.0847 12L0.324219 2.20181L2.51761 0L14.4718 12L2.51761 24Z"
                                                    fill="var(--mm-blue-color)"/>
                                        </svg>
                                    </button>
                                    <button aria-label="Previous slide" type="button" class="button prev"
                                            data-target="main_slider_prev_button">
                                        <svg width="15" height="24" viewBox="0 0 15 24" fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                    d="M12.6816 24L14.875 21.7982L5.1145 12L14.875 2.20181L12.6816 0L0.727434 12L12.6816 24Z"
                                                    fill="var(--mm-blue-color)"/>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                                  <button aria-label="Full size" data-target="full_size_button" class="button-full-size"
                                          type="button">
                                      <svg width="33" height="31" viewBox="0 0 33 31" fill="none"
                                           xmlns="http://www.w3.org/2000/svg">
                                          <path
                                                  d="M13.4338 2.93634V0.98877H0.6875V12.6742H2.81189V4.31345L13.4795 14.093L14.9815 12.716L4.31403 2.93634H13.4338Z"
                                                  fill="#2D6292"/>
                                          <path
                                                  d="M30.4313 18.5168V26.8775L19.4982 16.8545L17.9961 18.2315L28.9291 28.2546H19.8093V30.2022H32.5557V18.5168H30.4313Z"
                                                  fill="#2D6292"/>
                                      </svg>
                                  </button>
                              </div>
                              <div class="thumbs-slider-wrapper">
                                  <div data-target="single_thumbs_slider" class="thumbs-sliders swiper">
                                      <div class="swiper-wrapper">
                                        <?php foreach ( $listing_images as $image ): ?>
                                            <div class="swiper-slide">
                                              <?php echo wp_get_attachment_image( $image['ID'], 'full', '', [ 'loading' => 'lazy' ] ) ?>
                                            </div>
                                        <?php endforeach; ?>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      <?php endif; ?>
                        <div class="listing-infos">
                            <div class="listing-infos-wrapper">
                              <?php if ( ! empty( $final_listing_types ) ): ?>
                                  <div class="info">
                                      <h2 class="flex">
                                        <?php
                                        foreach ( $final_listing_types as $index => $type ) {
                                            echo '<span>' . esc_html( is_object( $type ) ? $type->name : $type->name ) . '</span>';
                                        }
                                        ?>
                                      </h2>
                                      <form method="post" action="<?php echo esc_url( $search_page ); ?>">
                                        <?php foreach ( $final_listing_types as $type ): ?>
                                            <input name="filter[uses][<?php echo esc_attr( is_object( $type ) ? $type->term_id : $type->term_id ); ?>]"
                                                   value="<?php echo esc_attr( is_object( $type ) ? $type->name : $type->name ); ?>"
                                                   type="hidden">
                                        <?php endforeach; ?>
                                          <button class="hide-button" aria-label="See similar" type="submit">See
                                              similar
                                          </button>
                                      </form>
                                  </div>
                              <?php endif; ?>
                              
                              <?php if ( $listing_month_rent && ! $listing_call_request ): ?>
                                  <div class="info">
                                      <h2>$
                                        <?php echo ( is_numeric( $listing_month_rent ) ) ? number_format( $listing_month_rent ) : $listing_month_rent ?>
                                          /month
                                      </h2>
                                      <form method="post" action="<?php echo $search_page ?>">
                                        <?php
                                          foreach ( $current_price as $index => $price ) {
                                            $index += 1;
                                            echo '<input name="filter[prices][' . $index . ']" value="' . $price['type'] . $price['value'] . '" type="hidden">';
                                          }
                                        ?>
                                          <button class="hide-button" aria-label="See similar" type="submit">See
                                              similar
                                          </button>
                                      </form>
                                  </div>
                              <?php else: ?>
                                  <div class="info">
                                      <h2>Call for request</h2>
                                      <form method="post" action="<?php echo $search_page ?>">
                                        <?php
                                          foreach ( $current_price as $index => $price ) {
                                            $index += 1;
                                            echo '<input name="filter[prices][' . $index . ']" value="' . $price['type'] . $price['value'] . '" type="hidden">';
                                          }
                                        ?>
                                          <button class="hide-button" aria-label="See similar" type="submit">See
                                              similar
                                          </button>
                                      </form>
                                  </div>
                              <?php endif; ?>
                              <?php if ( ! empty( $listing_square_feet ) ): ?>
                                  <div class="info">
                                      <h2>
                                        <?php echo ( is_numeric( $listing_square_feet ) ) ? number_format( $listing_square_feet ) : $listing_square_feet ?>
                                          SF
                                      </h2>
                                      <form method="post" action="<?php echo $search_page ?>">
                                        <?php
                                          foreach ( $current_size as $index => $size ) {
                                            $index += 1;
                                            echo '<input name="filter[sizes][' . $index . ']" value="' . $size['type'] . $size['value'] . '" type="hidden">';
                                          }
                                        ?>
                                          <button class="hide-button" aria-label="See similar" type="submit">See
                                              similar
                                          </button>
                                      </form>
                                  </div>
                              <?php endif; ?>
                            </div>
                        </div>
                        <div class="listing-buttons">
                            <div class="content">
                                <div class="button">
                                    <button aria-label="Contact Agent" data-target="open_contact_button" type="button">
                                        Contact Agent
                                    </button>
                                </div>
                              <?php if ( $phone_button ): ?>
                                  <div class="button call-button">
                                      <a aria-label="Call <?php echo esc_attr( $phone_button['title'] ) ?>"
                                         href="<?php echo $phone_button['url'] ?>">Call
                                        <?php echo $phone_button['title'] ?>
                                      </a>
                                  </div>
                              <?php endif; ?>
                                <div class="button schedule-button">
                                    <button aria-label="Schedule a tour" data-target="schedule_button" type="button">
                                        Schedule a tour
                                    </button>
                                </div>
                            </div>
                        </div>
                      <?php if ( ! empty( get_the_content() ) || ! empty( $listing_floorplan ) ): ?>
                          <div class="listing-description">
                              <p>
                                <?php echo wpautop( get_the_content() ); ?>
                              </p>
                            <?php
                              if ( ! empty( $listing_floorplan ) ): ?>
                                  <p class="floorplan">Click <a target="_blank"
                                                                title="<?php echo esc_attr( get_the_title() . ' floorplan' ) ?>"
                                                                href="<?php echo esc_url( wp_get_attachment_url( $listing_floorplan ) ) ?>">here
                                          to view
                                          floor plan</a>
                                      for this space.</p>
                              <?php endif; ?>
                          </div>
                      <?php endif; ?>
                      <?php if ( ! empty( $listing_matterport ) ): ?>
                          <div class="listing-tour">
                              <h3>3D tour</h3>
                              <div data-url="<?php echo $listing_matterport ?>" id="matterport"></div>
                              <div class="how-to-use">
                                  <div class="content">
                                      <div class="icon">
                                          <svg width="24" height="25" viewBox="0 0 24 25" fill="none"
                                               xmlns="http://www.w3.org/2000/svg">
                                              <path
                                                      d="M2.03457 19.2925L10.9806 24.4575C11.2347 24.6037 11.5228 24.6807 11.816 24.6807C12.1091 24.6807 12.3972 24.6037 12.6513 24.4575L21.5974 19.2925C21.8511 19.1455 22.0617 18.9345 22.2083 18.6806C22.3549 18.4267 22.4323 18.1388 22.4328 17.8456V7.51567C22.4323 7.2225 22.3549 6.93459 22.2083 6.68069C22.0617 6.4268 21.8511 6.21581 21.5974 6.0688L12.6513 0.903802C12.3972 0.757606 12.1092 0.680664 11.816 0.680664C11.5228 0.680664 11.2348 0.757606 10.9807 0.903802L2.03457 6.0688C1.78091 6.2158 1.57026 6.42679 1.42367 6.68068C1.27709 6.93458 1.19969 7.2225 1.19922 7.51567V17.8457C1.19969 18.1388 1.27709 18.4268 1.42367 18.6807C1.57026 18.9346 1.78091 19.1455 2.03457 19.2925V19.2925ZM12.6513 2.83298L20.762 7.51567V16.7764L12.6513 12.1921V2.83298ZM11.8133 13.6375L20.0182 18.2751L11.8162 23.0105L3.61107 18.2736L11.8133 13.6375ZM2.86991 7.51567L10.9806 2.83298V12.1889L2.86991 16.7734V7.51567Z"
                                                      fill="#0961AF"/>
                                          </svg>
                                      </div>
                                      <p>To navigate, click areas of the floor where you would like to move. Click and
                                          drag to
                                          rotate
                                          view. If available, you may use arrow keys to navigate the space. Please
                                          note
                                          the
                                          ruler
                                          feature, "Dollhouse" and "Floorplan" views (bottom left).</p>
                                  </div>
                              </div>
                          </div>
                      <?php endif; ?>
                        <div class="listing-detail">
                            <h3>Listing Details</h3>
                            <div class="tables">
                              <?php if ( ! empty( $listing_id ) ): ?>
                                  <div class="table-header">
                                      <h4>Listing
                                        <?php echo $listing_id ?>
                                      </h4>
                                  </div>
                              <?php endif; ?>
                                <table>
                                    <tbody>
                                    <?php if ( ! empty( $listing_square_feet ) || ! empty( $listing_rent_sf ) ): ?>
                                        <tr>
                                          <?php if ( ! empty( $listing_square_feet ) ): ?>
                                              <td class="heading">Size:</td>
                                              <td>
                                                <?php echo ( is_numeric( $listing_square_feet ) ) ? number_format( $listing_square_feet ) : $listing_square_feet ?>
                                                  SF
                                              </td>
                                          <?php endif; ?>
                                          <?php if ( ! empty( $listing_rent_sf ) ): ?>
                                              <td class="heading">Rent/SF:</td>
                                              <td>$
                                                <?php echo $listing_rent_sf ?>
                                              </td>
                                          <?php endif; ?>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $listing_month_rent ) || ! empty( $listing_lease_type ) ): ?>
                                        <tr>
                                          <?php if ( ! empty( $listing_month_rent ) ): ?>
                                              <td class="heading">Monthly Rent:</td>
                                              <td>$
                                                <?php echo number_format( $listing_month_rent, 0 ) ?>
                                              </td>
                                          <?php endif; ?>
                                          <?php if ( ! empty( $listing_lease_type ) ): ?>
                                              <td class="heading">Lease Type:</td>
                                              <td>
                                                <?php echo $listing_lease_type ?>
                                              </td>
                                          <?php endif; ?>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $listing_available ) || ! empty( $listing_lease_term ) ): ?>
                                        <tr>
                                          <?php if ( ! empty( $listing_available ) ):
                                            $str_time = str_replace( '-', '/', $listing_available );
                                            $time = strtotime( $str_time );
                                            $date = date( 'm/d/Y', $time );
                                            ?>
                                              <td class="heading">Available:</td>
                                              <td>
                                                <?php echo $date ?>
                                              </td>
                                          <?php endif; ?>
                                          <?php if ( ! empty( $listing_lease_term ) ): ?>
                                              <td class="heading">Lease Term:</td>
                                              <td>
                                                <?php echo $listing_lease_term ?>
                                              </td>
                                          <?php endif; ?>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $listing_suite_floor ) || ! empty( $listing_address ) ): ?>
                                        <tr>
                                          <?php if ( ! empty( $listing_suite_floor ) ): ?>
                                              <td class="heading">Suite/Floor:</td>
                                              <td>
                                                <?php echo $listing_suite_floor ?>
                                              </td>
                                          <?php endif; ?>
                                          <?php if ( ! empty( $listing_address ) ): ?>
                                              <td class="heading">Address:</td>
                                              <td>
                                                <?php echo $listing_address ?>
                                              </td>
                                          <?php endif; ?>
                                        </tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                              <?php if ( is_array( $listing_features ) && sizeof( $listing_features ) > 0 ): ?>
                                  <div class="table-header">
                                      <h4>Features</h4>
                                  </div>
                                  <table class="no-values">
                                      <tbody>
                                      <?php foreach ( array_chunk( $listing_features, 2 ) as $chunk ): ?>
                                          <tr>
                                            <?php foreach ( $chunk as $feature ): ?>
                                                <td>
                                                  <?php echo $feature->name ?>
                                                </td>
                                            <?php endforeach; ?>
                                          </tr>
                                      <?php endforeach; ?>
                                      </tbody>
                                  </table>
                              <?php endif; ?>
                            </div>
                        </div>
                      <?php if ( ! empty( $listing_coordinates ) ): ?>
                          <div class="listing-map">
                              <h3>Listing Location & Nearby Public Transportation</h3>
                              <div data-api-key="<?php echo $google_map_api_key ?>"
                                   data-lat="<?php echo $listing_coordinates['lat'] ?>"
                                   data-lng="<?php echo $listing_coordinates['lng'] ?>" data-target="google_map"></div>
                          </div>
                      <?php endif; ?>
                      <?php if ( ! empty( $nearby_transport ) ): ?>
                          <div class="listing-transport">
                              <h3>Nearby Transportation</h3>
                              <div class="table">
                                <?php if ( ! empty( $nearby_transport['subway'] ) ):
                                  $string = '';
                                  for ( $i = 0; $i < sizeof( $nearby_transport['subway'] ); $i ++ ) {
                                    $minutes = ( $nearby_transport['subway'][ $i ]['distance'] > 1 ) ? $nearby_transport['subway'][ $i ]['distance'] . ' minutes' : $nearby_transport['subway'][ $i ]['distance'] . ' minute';
                                    $string  .= $nearby_transport['subway'][ $i ]['name'] . ' - ' . $minutes . ' Walk';
                                    if ( $i < sizeof( $nearby_transport['subway'] ) - 1 ) {
                                      $string .= '</br>';
                                    }
                                  }
                                  ?>
                                    <div class="row">
                                        <span>Subway</span>
                                        <p>
                                          <?php echo $string ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $nearby_transport['bus'] ) ):
                                  $string = '';
                                  for ( $i = 0; $i < sizeof( $nearby_transport['bus'] ); $i ++ ) {
                                    $minutes = ( $nearby_transport['bus'][ $i ]['distance'] > 1 ) ? $nearby_transport['bus'][ $i ]['distance'] . ' minutes' : $nearby_transport['bus'][ $i ]['distance'] . ' minute';
                                    $string  .= $nearby_transport['bus'][ $i ]['name'] . ' - ' . $minutes . ' Walk';
                                    if ( $i < sizeof( $nearby_transport['bus'] ) - 1 ) {
                                      $string .= '</br>';
                                    }
                                  } ?>
                                    <div class="row">
                                        <span>Bus</span>
                                        <p>
                                          <?php echo $string ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $nearby_transport['parking'] ) ):
                                  $string = '';
                                  for ( $i = 0; $i < sizeof( $nearby_transport['parking'] ); $i ++ ) {
                                    $minutes = ( $nearby_transport['parking'][ $i ]['distance'] > 1 ) ? $nearby_transport['parking'][ $i ]['distance'] . ' minutes' : $nearby_transport['parking'][ $i ]['distance'] . ' minute';
                                    $string  .= $nearby_transport['parking'][ $i ]['name'] . ' - ' . $minutes . ' Walk';
                                    if ( $i < sizeof( $nearby_transport['parking'] ) - 1 ) {
                                      $string .= ', ';
                                    }
                                  } ?>
                                    <div class="row">
                                        <span>Parking</span>
                                        <p>
                                          <?php echo $string ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                              </div>
                          </div>
                      <?php endif; ?>
                        <div class="listing-important">
                            <h3>Important Information</h3>
                            <div class="text">
                                <div class="icon">
                                    <svg width="24" height="25" viewBox="0 0 24 25" fill="none"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12.7969" r="11.5" stroke="var(--mm-navy-color)"/>
                                        <path
                                                d="M13.25 4.79688C12.2875 4.79688 11.5 5.58437 11.5 6.54688C11.5 7.50938 12.2875 8.29688 13.25 8.29688C14.2125 8.29688 15 7.50938 15 6.54688C15 5.58437 14.2125 4.79688 13.25 4.79688ZM10.625 9.17188C9.1725 9.17188 8 10.3444 8 11.7969H9.75C9.75 11.3069 10.135 10.9219 10.625 10.9219C11.115 10.9219 11.5 11.3069 11.5 11.7969C11.5 12.2869 9.75 14.6669 9.75 16.1719C9.75 17.6769 10.9225 18.7969 12.375 18.7969C13.8275 18.7969 15 17.6244 15 16.1719H13.25C13.25 16.6619 12.865 17.0469 12.375 17.0469C11.885 17.0469 11.5 16.6619 11.5 16.1719C11.5 15.5419 13.25 12.9519 13.25 11.7969C13.25 10.3794 12.0775 9.17188 10.625 9.17188Z"
                                                fill="var(--mm-blue-color)"/>
                                    </svg>
                                </div>
                                <p>Listings are presented for illustrative purposes only; they may no longer be
                                    available and are provided merely as an exemplary representation of the types of
                                    spaces in a given neighborhood for a given price.</p>
                            </div>
                        </div>
                    </div>
                  <?php if ( ! empty( $global_form_title ) || ! empty( $global_form_text ) || ! empty( $global_form_shortcode ) ): ?>
                      <aside class="sidebar">
                          <div data-target="inqury" class="inqury custom-scroll">
                              <div class="close-btn">
                                  <button aria-label="Close popup" data-target="close_contact_button" type="button">
                                      <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                           xmlns="http://www.w3.org/2000/svg">
                                          <path
                                                  d="M20.0001 1.32074L18.6793 0L10.0001 8.6792L1.32086 0L0 1.32074L8.67926 10L0 18.6793L1.32086 20L10.0001 11.3208L18.6793 20L20.0001 18.6793L11.3209 10L20.0001 1.32074Z"
                                                  fill="var(--mm-navy-color)"/>
                                      </svg>
                                  </button>
                              </div>
                              <div data-target="inqury_form" class="form">
                                  <div class="content">
                                    <?php if ( ! empty( $global_form_title ) ):
                                      $global_form_title = ( str_contains( $global_form_title, '{{post_title}}' ) ) ? str_replace( '{{post_title}}', get_the_title(), $global_form_title ) : $global_form_title; ?>
                                        <h2 class="office-space-inquiry-heading">
                                          <?php echo $global_form_title ?>
                                        </h2>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $global_form_text ) ): ?>
                                        <p>
                                          <?php echo $global_form_text ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $global_form_shortcode ) ): ?>
                                        <div class="shortcode">
                                          <?php echo do_shortcode( $global_form_shortcode ); ?>
                                        </div>
                                    <?php endif; ?>
                                      <button aria-label="Schedule a tour" class="schedule-button"
                                              data-target="schedule_button" type="button">
                                          Schedule
                                          a
                                          tour
                                      </button>
                                  </div>
                              </div>
                              <div data-target="inqury_message" class="message">
                                  <div class="content">
                                    <?php if ( ! empty( $global_aside_thank_you_title ) ): ?>
                                        <h2>
                                          <?php echo $global_aside_thank_you_title ?>
                                        </h2>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $global_aside_thank_you_text ) ): ?>
                                        <p>
                                          <?php echo $global_aside_thank_you_text ?>
                                        </p>
                                    <?php endif; ?>
                                  </div>
                              </div>
                          </div>
                      </aside>
                  <?php endif; ?>
                </section>
              <?php if ( is_object( $listing_neighborhood ) ):
                $listing_neighborhood_images = get_field( 'images', 'location_' . $listing_neighborhood->term_id );
                $listing_neighborhood_link = get_field( 'page_id', 'location_' . $listing_neighborhood->term_id );
                ?>
                  <section
                          class="single-custom-post-location <?php echo ( ! $listing_neighborhood_images ) ? 'no-image' : '' ?>">
                      <div class="content">
                        <?php if ( ! empty( $listing_neighborhood_images ) ): ?>
                            <div class="images">
                              <?php
                                foreach ( $listing_neighborhood_images as $image ):
                                  echo wp_get_attachment_image( $image['ID'], 'full', '', [ 'loading' => 'lazy' ] );
                                endforeach; ?>
                            </div>
                        <?php endif; ?>
                          <div class="text">
                              <h3>
                                <?php echo $listing_neighborhood->name ?>
                              </h3>
                            <?php if ( ! empty( $listing_neighborhood_link ) ): ?>
                              <?php
                              preg_match_all( '/^.*?(<p[^>]*>.*?<\/p>).*$/m', get_post_field( 'post_content', $listing_neighborhood_link ), $matches, PREG_UNMATCHED_AS_NULL );
                              ?>
                                <p>
                                  <?php echo wp_strip_all_tags( join( ' ', $matches[0] ) ); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ( ! empty( $listing_neighborhood_link ) ): ?>
                                <a aria-label="Learn more about <?php echo esc_attr( $listing_neighborhood->name ) ?>"
                                   href="<?php echo get_permalink( $listing_neighborhood_link ) ?>" class="link">Learn
                                    more about
                                  <?php echo esc_html( $listing_neighborhood->name ) ?>
                                </a>
                            <?php endif; ?>
                          </div>
                      </div>
                  </section>
              <?php endif; ?>
              <?php if ( ! empty( $listing_nearby_offices ) ): ?>
                  <section data-target="similar_listings" class="single-custom-post-similar-listings mm-section">
                      <div class="content">
                          <div class="heading">
                              <h2>Nearby Office Rentals</h2>
                              <div class="heading-part">
                                <?php if ( ! empty( $listing_nearby_offices ) && sizeof( $listing_nearby_offices ) > 3 ): ?>
                                    <div class="controllers">
                                        <button aria-label="Previous slide" data-target="swiper_left"
                                                class="btn swiper-button-disabled" disabled="">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24"
                                                 viewBox="0 0 15 24" fill="none">
                                                <path
                                                        d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z"
                                                        fill="#0961AF"></path>
                                            </svg>
                                        </button>
                                        <button aria-label="Next slide" data-target="swiper_right" class="btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24"
                                                 viewBox="0 0 15 24" fill="none">
                                                <path
                                                        d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z"
                                                        fill="#0961AF"></path>
                                            </svg>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php
                                  if ( ! empty( $global_offices_nearby_button ) ):
                                    $link_url = $global_offices_nearby_button['url'];
                                    $link_title = $global_offices_nearby_button['title'];
                                    $link_target = $global_offices_nearby_button['target'] ? $global_offices_nearby_button['target'] : '_self';
                                    ?>
                                      <a aria-label="<?php echo esc_attr( $link_title ) ?>"
                                         href="<?php echo esc_url( $link_url ); ?>"
                                         target="<?php echo esc_attr( $link_target ); ?>">
                                        <?php echo esc_html( $link_title ); ?>
                                      </a>
                                  <?php endif; ?>
                              </div>
                          </div>
                          <div class="swiper">
                              <div class="swiper-wrapper blocks">
                                <?php foreach ( $listing_nearby_offices as $listing_nearby_office ): ?>
                                    <div class="swiper-slide block">
                                      <?php get_template_part( 'templates/parts/listing', 'card', [
                                        'id'                  => $listing_nearby_office,
                                        'map_card'            => false,
                                        'heading'             => 'h2',
                                        'favourites_template' => false
                                      ] ) ?>
                                    </div>
                                <?php endforeach; ?>
                              </div>
                          </div>
                      </div>
                  </section>
              <?php endif; ?>
              <?php if ( ! empty( $listing_featured_listings ) ): ?>
                  <section data-target="similar_listings" class="single-custom-post-similar-listings mm-section">
                      <div class="content">
                          <div class="heading">
                              <h2>Featured Listings</h2>
                              <div class="heading-part">
                                <?php if ( $listing_featured_listings && sizeof( $listing_featured_listings ) > 3 ): ?>
                                    <div class="controllers">
                                        <button aria-label="Previous slide" data-target="swiper_left" class="btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24"
                                                 viewBox="0 0 15 24" fill="none">
                                                <path
                                                        d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z"
                                                        fill="#0961AF"></path>
                                            </svg>
                                        </button>
                                        <button aria-label="Next slide" data-target="swiper_right" class="btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24"
                                                 viewBox="0 0 15 24" fill="none">
                                                <path
                                                        d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z"
                                                        fill="#0961AF"></path>
                                            </svg>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php
                                  if ( ! empty( $global_featured_listings_button ) ):
                                    $link_url = $global_featured_listings_button['url'];
                                    $link_title = $global_featured_listings_button['title'];
                                    $link_target = $global_featured_listings_button['target'] ? $global_featured_listings_button['target'] : '_self';
                                    ?>
                                      <a aria-label="<?php echo esc_attr( $link_title ) ?>"
                                         href="<?php echo esc_url( $link_url ); ?>"
                                         target="<?php echo esc_attr( $link_target ); ?>">
                                        <?php echo esc_html( $link_title ); ?>
                                      </a>
                                  <?php endif; ?>
                              </div>
                          </div>
                          <div class="swiper">
                              <div class="swiper-wrapper blocks">
                                <?php foreach ( $listing_featured_listings as $listing_featured_listing ): ?>
                                    <div class="swiper-slide block">
                                      <?php get_template_part( 'templates/parts/listing', 'card', [
                                        'id'                  => $listing_featured_listing,
                                        'heading'             => 'h2',
                                        'favourites_template' => false
                                      ] ) ?>
                                    </div>
                                <?php endforeach; ?>
                              </div>
                          </div>

                      </div>
                  </section>
              <?php endif; ?>
                <div class="back-to-top">
                    <button aria-label="Back to top" data-target="back_to_top" type="button">
                        <svg width="18" height="11" viewBox="0 0 18 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2 9.29688L9 2.29687L16 9.29687" stroke="#2D6292" stroke-width="3"
                                  stroke-linecap="round"/>
                        </svg>
                        <span>Back to top</span>
                    </button>
                </div>
                <div data-target="schedule_wrapper" class="schedule">
                    <div data-target="schedule_message" class="schedule-message success-message">
                        <div class="icon">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <rect width="40" height="40" rx="20" fill="#308C05" fill-opacity="0.15"/>
                                <path
                                        d="M17.6605 26.0009C17.5106 26.0004 17.3623 25.9718 17.2249 25.9168C17.0876 25.8617 16.9641 25.7814 16.8621 25.6809L11.5465 20.5109C11.3478 20.3173 11.2413 20.0594 11.2506 19.7941C11.2598 19.5287 11.3839 19.2776 11.5957 19.0959C11.8075 18.9142 12.0895 18.8169 12.3797 18.8253C12.67 18.8338 12.9446 18.9473 13.1433 19.1409L17.6496 23.5309L26.848 14.3309C26.9414 14.2246 27.0575 14.1369 27.1893 14.0732C27.3211 14.0094 27.4657 13.971 27.6143 13.9602C27.7629 13.9494 27.9124 13.9665 28.0535 14.0104C28.1946 14.0544 28.3244 14.1242 28.4349 14.2157C28.5455 14.3072 28.6343 14.4184 28.6962 14.5424C28.758 14.6665 28.7914 14.8007 28.7944 14.937C28.7974 15.0733 28.7698 15.2087 28.7135 15.3349C28.6572 15.4611 28.5732 15.5754 28.4668 15.6709L18.4699 25.6709C18.3689 25.7732 18.2458 25.8554 18.1084 25.9122C17.971 25.969 17.8223 25.9992 17.6715 26.0009H17.6605Z"
                                        fill="#308C05"/>
                            </svg>
                        </div>
                        <div class="text">
                          <?php if ( ! empty( $global_aside_thank_you_title ) ): ?>
                              <h2>
                                <?php echo $global_aside_thank_you_title ?>
                              </h2>
                          <?php endif; ?>
                          <?php if ( ! empty( $global_aside_thank_you_text ) ): ?>
                              <p>
                                <?php echo $global_aside_thank_you_text ?>
                              </p>
                          <?php endif; ?>
                        </div>
                        <div class="close-btn">
                            <button aria-label="Close message" data-target="close_schedule_message" type="button">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path
                                            d="M12 0.79244L11.2075 0L6 5.20749L0.79251 0L0 0.79244L5.20753 5.99996L0 11.2075L0.79251 11.9999L6 6.79244L11.2075 11.9999L12 11.2075L6.79248 5.99996L12 0.79244Z"
                                            fill="var(--mm-grey-color)"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div data-target="schedule_popup" class="schedule-popup">
                        <div class="close-btn">
                            <button aria-label="Close popup" data-target="close_schedule_popup" type="button">
                                <svg width="25" height="25" viewBox="0 0 25 25" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path
                                            d="M24.5 2.08488L22.915 0.5L12.5 10.915L2.08502 0.5L0.5 2.08488L10.9151 12.4999L0.5 22.915L2.08502 24.4999L12.5 14.0849L22.915 24.4999L24.5 22.915L14.085 12.4999L24.5 2.08488Z"
                                            fill="var(--mm-blue-color)"/>
                                </svg>
                            </button>
                        </div>
                        <div class="schedule-form">
                            <div class="content">
                              <?php if ( ! empty( $global_schedule_title ) ): ?>
                                  <h2>
                                    <?php echo $global_schedule_title ?>
                                  </h2>
                              <?php endif; ?>
                              <?php if ( ! empty( $global_schedule_text ) ): ?>
                                  <p>
                                    <?php echo $global_schedule_text ?>
                                  </p>
                              <?php endif; ?>
                              <?php if ( ! empty( $global_schedule_shortcode ) ): ?>
                                  <div class="form">
                                    <?php echo do_shortcode( $global_schedule_shortcode ) ?>
                                  </div>
                              <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>
</main>
<?php get_footer() ?>
