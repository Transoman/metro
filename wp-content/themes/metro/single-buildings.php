<?php
get_header('search');
get_template_part('templates/parts/notification', 'template');

$helper = new MetroManhattanHelpers();
$google_map_api_key = get_field('google_map_api_key', 'option');

$building_images = (get_field('images')) ? array_merge([['ID' => get_post_thumbnail_id()]], get_field('images')) : [['ID' => get_post_thumbnail_id()]];
$building_floors = get_field('floors');
$building_size_range = get_field('size_range');
$building_address = get_field('address');
$building_cross_streets = get_field('cross_streets');
$building_year_built = get_field('year_built');
$building_class = get_field('class');
$building_size = get_field('size');
$building_architect = get_field('architect');
$building_highlights = get_field('highlights');
$building_amenities = get_field('amenities');
$building_availability_table = get_field('availability_table');
$building_simplified_listings = get_field('simplified_listings');
$building_coordinates = get_field('map');
$building_transportation = get_field('transportation');
$building_tenants = get_field('tenants');
$building_price = get_field('price');
$type_of_building_tenants = get_field('type_of_notable_tenants');
$building_text_tenants = get_field('text_tenants');

$phone_button = get_field('phone_button', 'option');

$breadcrumbs = $helper->listing_breadcrumbs(get_the_ID());

$building_neighborhood = $helper->listing_parent_term(get_the_ID(), 'location');
$building_child_neighborhood = $helper->listing_child_neighborhood(get_the_ID(), $building_neighborhood);

$building_mode_select = get_field('listings_nearby');

$building_nearby_buildings = $helper->get_posts_by_mode('nearby', ['post_type' => 'buildings', 'location' => $building_child_neighborhood->term_id, 'exclude' => get_the_ID(), 'numberposts' => 12]);
$building_nearby_offices = $helper::get_nearby_listings(get_the_ID());

$global_form_title = get_field('global_sidebar_title', 'option');
if ( $global_form_title == 'Listing Inquiry' ) {
    $global_form_title = 'Commercial Space Inquiry';
}

$global_form_text = get_field('global_sidebar_text', 'option');
if ( $global_form_text == 'Call us at 1 (212) 444-2241 or connect with us to learn more or book a tour.' ) {
    $global_form_text = 'Call us at +1 (917) 292-9171 for pricing, availability, or to book a tour.';
}

$global_form_shortcode = get_field('global_sidebar_shortcode', 'option');

$global_aside_thank_you_title = get_field('global_thanks_title', 'option');
$global_aside_thank_you_text = get_field('global_thanks_text', 'option');

$global_schedule_title = get_field('global_schedule_title', 'option');
$global_schedule_text = get_field('global_schedule_text', 'option');
$global_schedule_shortcode = get_field('global_schedule_shortcode', 'option');

$global_featured_listings_button = get_field('featured_listings_button', 'option');
$global_offices_nearby_button = get_field('offices_nearby_button', 'option');

$global_available_space_message = get_field('global_available_space_message', 'option');

if (strpos($global_available_space_message, '{{building_name}}') != false) {
    $title = get_the_title();
    $unwanted_phrases = ['office space', 'for lease'];
    $title = str_ireplace($unwanted_phrases, '', $title);

    $global_available_space_message = str_replace('{{building_name}}', $title, $global_available_space_message);
}

$nearby_transport = ($building_coordinates) ? $helper::get_nearby_transport($building_coordinates['lat'], $building_coordinates['lng']) : [];

?>
<main>
    <?php
    get_template_part('templates/parts/notification', 'template');
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
                    </header>
                    <div class="main-content">
                        <?php if (!empty($building_images)): ?>
                            <div class="listing-sliders">
                                <div class="close-btn">
                                    <button aria-label="Close full size mode" data-target="close_full_size" type="button">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M20.0001 1.32074L18.6793 0L10.0001 8.6792L1.32086 0L0 1.32074L8.67926 10L0 18.6793L1.32086 20L10.0001 11.3208L18.6793 20L20.0001 18.6793L11.3209 10L20.0001 1.32074Z"
                                                fill="var(--mm-navy-color)"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="main-slider-wrapper">
                                    <div data-target="single_main_slider" class="main-slider swiper">
                                        <div class="swiper-wrapper">
                                            <?php
                                            $idx = 0;
                                            foreach ($building_images as $image): ?>
                                                <div class="swiper-slide">
                                                    <?php echo wp_get_attachment_image($image['ID'], 'full', '', ['loading' => ($idx == 0) ? 'lazy' : 'lazy']) ?>
                                                    <p class="google-label">Image via Google Street View</p>
                                                </div>
                                                <?php
                                                $idx++;
                                            endforeach; ?>
                                        </div>
                                        <button aria-label="Full size" data-target="full_size_button"
                                            class="button-full-size" type="button">
                                            <svg width="33" height="31" viewBox="0 0 33 31" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M13.4338 2.93634V0.98877H0.6875V12.6742H2.81189V4.31345L13.4795 14.093L14.9815 12.716L4.31403 2.93634H13.4338Z"
                                                    fill="#2D6292" />
                                                <path
                                                    d="M30.4313 18.5168V26.8775L19.4982 16.8545L17.9961 18.2315L28.9291 28.2546H19.8093V30.2022H32.5557V18.5168H30.4313Z"
                                                    fill="#2D6292" />
                                            </svg>
                                        </button>
                                    </div>
                                    <?php if (sizeof($building_images) > 1): ?>
                                        <button aria-label="Next slide" type="button" class="button next"
                                            data-target="main_slider_next_button">
                                            <svg width="15" height="24" viewBox="0 0 15 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M2.51761 24L0.324219 21.7982L10.0847 12L0.324219 2.20181L2.51761 0L14.4718 12L2.51761 24Z"
                                                    fill="var(--mm-blue-color)" />
                                            </svg>
                                        </button>
                                        <button aria-label="Previous slide" type="button" class="button prev"
                                            data-target="main_slider_prev_button">
                                            <svg width="15" height="24" viewBox="0 0 15 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M12.6816 24L14.875 21.7982L5.1145 12L14.875 2.20181L12.6816 0L0.727434 12L12.6816 24Z"
                                                    fill="var(--mm-blue-color)" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty(get_field('images'))): ?>
                                    <div class="thumbs-slider-wrapper">
                                        <div data-target="single_thumbs_slider" class="thumbs-sliders swiper">
                                            <div class="swiper-wrapper">
                                                <?php foreach ($building_images as $image): ?>
                                                    <div class="swiper-slide">
                                                        <?php echo wp_get_attachment_image($image['ID'], 'full', '', ['loading' => 'lazy']) ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
			<?php endif; ?>
                        <?php if ($building_floors || $building_size_range): ?>
			     <?php
	                        $font_size = '';
	                        if ( strlen($building_size_range) == 15 ) {
		                    $font_size = "smaller-font-size";
	                        } elseif ( strlen($building_size_range) >= 16 ) {
                                    $font_size = "smaller-font-size-18";
                                }
	                      ?>
                              <div class="listing-infos building">
                                <div class="listing-infos-wrapper">
                                    <?php if ($building_floors): ?>
                                        <div class="info">
                                            <h2 class="<?php echo $font_size; ?>">
                                                Total Floors: <?php echo $building_floors ?>
                                            </h2>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($building_size_range): ?>
                                        <div class="info">
                                            <h2 class="<?php echo $font_size; ?>">
                                                Available: <?php echo $building_size_range ?>
                                            </h2>
                                        </div>
                                    <?php endif; ?>
                                    <div class="info">
                                        <?php if ($building_price): ?>
                                            <h2 class="no-bold <?php echo $font_size; ?>">$<?php echo $building_price ?>/SF</h2>
                                        <?php else: ?>
                                            <h2 class="no-bold <?php echo $font_size; ?>">Inquire for Pricing</h2>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="listing-buttons">
                            <div class="content">
                                <div class="button">
                                    <button aria-label="Contact Agent" data-target="open_contact_button" type="button">
                                        Contact Agent
                                    </button>
                                </div>
                                <?php if ($phone_button): ?>
                                    <div class="button call-button">
                                        <a aria-label="Call <?php echo esc_attr($phone_button['title']) ?>"
                                            href="<?php echo esc_url($phone_button['url']) ?>">Call
                                            <?php echo esc_html($phone_button['title']) ?>
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
                        <div class="listing-detail">
                            <div class="tables">
                                <table class="single">
                                    <?php if ($building_address || $building_class): ?>
                                        <tr>
                                            <?php if ($building_address): ?>
                                                <td class="heading">Address: </td>
                                                <td>
                                                    <?php echo $building_address ?>
                                                </td>
                                            <?php endif; ?>
                                            <?php if ($building_class): ?>
                                                <td class="heading">Class: </td>
                                                <td>
                                                    <?php echo $building_class ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($building_cross_streets || $building_size): ?>
                                        <tr>
                                            <?php if ($building_cross_streets): ?>
                                                <td class="heading">Cross Streets: </td>
                                                <td>
                                                    <?php echo $building_cross_streets ?>
                                                </td>
                                            <?php endif; ?>
                                            <?php if ($building_size): ?>
                                                <td class="heading">Size: </td>
                                                <td>
                                                    <?php echo $building_size ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($building_year_built || $building_architect): ?>
                                        <tr>
                                            <?php if ($building_year_built): ?>
                                                <td class="heading">Year built: </td>
                                                <td>
                                                    <?php echo $building_year_built ?>
                                                </td>
                                            <?php endif; ?>
                                            <?php if ($building_architect): ?>
                                                <td class="heading">Architect: </td>
                                                <td>
                                                    <?php echo $building_architect ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        <?php if ($building_highlights && sizeof($building_highlights) > 0): ?>
                            <div class="building-highlights">
                                <h3>Highlights</h3>
                                <ul>
                                    <?php while (have_rows('highlights')):
                                        the_row();
                                        $text = get_sub_field('text'); ?>
                                        <?php if ($text): ?>
                                            <li>
                                                <?php echo $text ?>
                                            </li>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if ($building_amenities && sizeof($building_amenities) > 0): ?>
                            <div class="building-amenities">
                                <h3>Amenities</h3>
                                <ul>
                                    <?php while (have_rows('amenities')):
                                        the_row();
                                        $text = get_sub_field('text');
                                        if ($text): ?>
                                            <li>
                                                <svg width="27" height="24" viewBox="0 0 27 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M11.0336 18.0009C10.8836 18.0004 10.7353 17.9718 10.598 17.9168C10.4606 17.8617 10.3371 17.7814 10.2351 17.6809L4.91952 12.5109C4.72082 12.3173 4.61437 12.0594 4.6236 11.7941C4.63283 11.5287 4.75698 11.2776 4.96874 11.0959C5.1805 10.9142 5.46252 10.8169 5.75276 10.8253C6.043 10.8338 6.31769 10.9473 6.5164 11.1409L11.0226 15.5309L20.2211 6.33089C20.3144 6.22461 20.4306 6.1369 20.5623 6.07315C20.6941 6.0094 20.8387 5.97095 20.9874 5.96017C21.136 5.94938 21.2854 5.96649 21.4265 6.01044C21.5677 6.05439 21.6975 6.12425 21.808 6.21573C21.9185 6.30722 22.0074 6.41839 22.0692 6.54243C22.131 6.66647 22.1644 6.80075 22.1674 6.93703C22.1704 7.0733 22.1429 7.2087 22.0865 7.3349C22.0302 7.4611 21.9463 7.57543 21.8398 7.67089L11.843 17.6709C11.7419 17.7732 11.6189 17.8554 11.4815 17.9122C11.3441 17.969 11.1953 17.9992 11.0445 18.0009H11.0336Z"
                                                        fill="#023A6C" />
                                                </svg>
                                                <span>
                                                    <?php echo $text ?>
                                                </span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty(get_the_content())): ?>
                            <div class="listing-description">
                                <?php the_content() ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!$building_availability_table): ?>
                            <div class="building-availability">
                                <h3>Available Space</h3>
                                <?php if ($global_available_space_message): ?>
                                    <div class="text">
                                        <p>
                                            <?php echo $global_available_space_message ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <script>
                                document.getElementById('contact-us-today-identifier').addEventListener('click', function (event) {
                                  event.preventDefault();
                                  document.getElementById('user-name').focus();
                                });
                                document.getElementById('contact-us-today-identifier').addEventListener('click', function (event) {
                                  event.preventDefault();
                                  document.getElementById('user-name').focus();
                                  if (window.innerWidth <= 992) {
                                    const contactButton = document.querySelector('[data-target="open_contact_button"]');
                                    if (contactButton) {
                                      contactButton.click();
                                    }
                                  }
                                });
                            </script>
                        <?php else: ?>
                            <div class="building-availability-table">
                                <h3>Further available spaces:</h3>
                                <div class="table">
                                    <?php if ($building_availability_table): ?>
                                        <?php foreach ($building_availability_table as $row): ?>
                                            <div class="item">
                                                <div class="row-item">
                                                    <?php if (get_field('suite_floor', $row)): ?>
                                                        <div class="point">
                                                            <span>Suite:</span>
                                                            <p class="bold">
                                                                <?php the_field('suite_floor', $row); ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (get_field('available', $row)): ?>
                                                        <div class="point">
                                                            <span>Available:</span>
                                                            <p>
                                                                <?php the_field('available', $row); ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($phone_button) || !empty($global_form_shortcode)): ?>
                                                        <div class="point buttons">
                                                            <?php if (!empty($phone_button)): ?>
                                                                <a href="<?php echo $phone_button['url']; ?>" class="phone"></a>
                                                            <?php endif; ?>
                                                            <?php if (!empty($global_form_shortcode)): ?>
                                                                <button data-target="table_btn" class="hide-button email"
                                                                    type="button"></button>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="row-item">
                                                    <div class="point">
                                                        <span>Price:</span>
                                                        <?php
                                                        $string = (get_field('call_request', $row)) ? 'Upon request' : '$' . get_field('rent_sf', $row) . '/SF';
                                                        ?>
                                                        <p>
                                                            <?php echo $string ?>
                                                        </p>
                                                    </div>
                                                    <?php if ($terms = wp_get_object_terms($row, 'listing-type', ['orderby' => 'parent'])): ?>
                                                        <div class="point">
                                                            <span>Use:</span>
                                                            <?php
                                                            $array = array_map(function ($item) {
                                                                return $item->name;
                                                            }, $terms);
                                                            ?>
                                                            <p>
                                                                <?php echo join(', ', $array) ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="row-item">
                                                    <?php if ($number = get_field('square_feet', $row)): ?>
                                                        <div class="point">
                                                            <span>Size:</span>
                                                            <p>
                                                                <?php echo number_format($number); ?> SF
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (get_field('lease_type', $row)): ?>
                                                        <div class="point">
                                                            <span>Types:</span>
                                                            <p>
                                                                <?php the_field('lease_type', $row); ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if ($building_simplified_listings): ?>
                                        <?php foreach ($building_simplified_listings as $row): ?>
                                            <div class="item">
                                                <div class="row-item">
                                                    <?php if (!empty($row['suite'])): ?>
                                                        <div class="point">
                                                            <span>Suite</span>
                                                            <p class="bold">
                                                                <?php echo $row['suite']; ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['available'])): ?>
                                                        <div class="point">
                                                            <span>Available:</span>
                                                            <p>
                                                                <?php echo $row['available']; ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($phone_button) || !empty($global_form_shortcode)): ?>
                                                        <div class="point buttons">
                                                            <?php if (!empty($phone_button)): ?>
                                                                <a href="<?php echo $phone_button['url']; ?>" class="phone"></a>
                                                            <?php endif; ?>
                                                            <?php if (!empty($global_form_shortcode)): ?>
                                                                <button data-target="table_btn" class="hide-button email"
                                                                    type="button"></button>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="row-item">
                                                    <?php if ($row['price'] || $row['price_number']): ?>
                                                        <div class="point">
                                                            <span>Price:</span>
                                                            <?php
                                                            $string = '$' . $row['price_number'] . '/SF';
                                                            ?>
                                                            <p>
                                                                <?php echo $string ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['uses'])): ?>
                                                        <div class="point">
                                                            <span>Use:</span>
                                                            <?php
                                                            $terms = [];
                                                            foreach ($row['uses'] as $item) {
                                                                $terms[] = get_term_by('term_id', $item, 'listing-type');
                                                            }
                                                            $array = array_map(function ($item) {
                                                                return $item->name;
                                                            }, $terms);
                                                            ?>
                                                            <p>
                                                                <?php echo join(', ', $array) ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="row-item">
                                                    <?php if (!empty($row['size'])): ?>
                                                        <div class="point">
                                                            <span>Size:</span>
                                                            <p>
                                                                <?php echo number_format($row['size']); ?> SF
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['type'])): ?>
                                                        <div class="point">
                                                            <span>Types:</span>
                                                            <p>
                                                                <?php echo $row['type']; ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="accordions">
                                    <?php foreach ($building_availability_table as $accordion): ?>
                                        <div data-target="accordion" class="accordion">
                                            <div class="header-accordion">
                                                <span>Suite</span>
                                                <p class="bold">
                                                    <?php the_field('suite_floor', $accordion); ?>
                                                </p>
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M7 14L12 9L17 14" stroke="var(--mm-placeholder-grey-2)"
                                                        stroke-width="2" />
                                                    <circle cx="12" cy="12" r="11" stroke="var(--mm-placeholder-grey-2)"
                                                        stroke-width="2" />
                                                </svg>
                                            </div>
                                            <div class="accordion-content">
                                                <ul class="accordion-items">
                                                    <?php if ($number = get_field('square_feet', $accordion)): ?>
                                                        <li>
                                                            <span>Size</span>
                                                            <p>
                                                                <?php echo number_format($number); ?> SF
                                                            </p>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if ($terms = wp_get_object_terms($accordion, 'listing-type', ['orderby' => 'parent'])): ?>
                                                        <li>
                                                            <span>Use</span>
                                                            <?php
                                                            $array = array_map(function ($item) {
                                                                return $item->name;
                                                            }, $terms);
                                                            ?>
                                                            <p>
                                                                <?php echo join(', ', $array) ?>
                                                            </p>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if (get_field('lease_type', $accordion)): ?>
                                                        <li>
                                                            <span>Types</span>
                                                            <p>
                                                                <?php the_field('lease_type', $accordion); ?>
                                                            </p>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if (get_field('call_request', $accordion) || get_field('rent_sf', $accordion)): ?>
                                                        <li>
                                                            <span>Price</span>
                                                            <?php
                                                            $string = (get_field('call_request', $accordion)) ? 'Upon request' : '$' . get_field('rent_sf', $accordion) . '/SF';
                                                            ?>
                                                            <p>
                                                                <?php echo $string ?>
                                                            </p>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if (get_field('available', $accordion)): ?>
                                                        <li>
                                                            <span>Available</span>
                                                            <p>
                                                                <?php the_field('available', $accordion); ?>
                                                            </p>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li class="buttons">
                                                        <ul>
                                                            <li>
                                                                <a href="<?php echo get_permalink($accordion) ?>">
                                                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                                        xmlns="http://www.w3.org/2000/svg">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M12 1C12 0.447715 12.4477 0 13 0H19C19.5523 0 20 0.447715 20 1L20 7C20 7.55228 19.5523 8 19 8C18.4477 8 18 7.55229 18 7L18 3.41421L11.7071 9.70711C11.3166 10.0976 10.6834 10.0976 10.2929 9.70711C9.90237 9.31658 9.90237 8.68342 10.2929 8.29289L16.5858 2H13C12.4477 2 12 1.55228 12 1ZM5.7587 2L8 2C8.55229 2 9 2.44772 9 3C9 3.55228 8.55229 4 8 4H5.8C4.94342 4 4.36113 4.00078 3.91104 4.03755C3.47262 4.07337 3.24842 4.1383 3.09202 4.21799C2.7157 4.40973 2.40973 4.71569 2.21799 5.09202C2.1383 5.24842 2.07337 5.47262 2.03755 5.91104C2.00078 6.36113 2 6.94342 2 7.8V14.2C2 15.0566 2.00078 15.6389 2.03755 16.089C2.07337 16.5274 2.1383 16.7516 2.21799 16.908C2.40973 17.2843 2.7157 17.5903 3.09202 17.782C3.24842 17.8617 3.47262 17.9266 3.91104 17.9624C4.36113 17.9992 4.94342 18 5.8 18H12.2C13.0566 18 13.6389 17.9992 14.089 17.9624C14.5274 17.9266 14.7516 17.8617 14.908 17.782C15.2843 17.5903 15.5903 17.2843 15.782 16.908C15.8617 16.7516 15.9266 16.5274 15.9624 16.089C15.9992 15.6389 16 15.0566 16 14.2V12C16 11.4477 16.4477 11 17 11C17.5523 11 18 11.4477 18 12V14.2413C18 15.0463 18 15.7106 17.9558 16.2518C17.9099 16.8139 17.8113 17.3306 17.564 17.816C17.1805 18.5686 16.5686 19.1805 15.816 19.564C15.3306 19.8113 14.8139 19.9099 14.2518 19.9558C13.7106 20 13.0463 20 12.2413 20H5.75868C4.95372 20 4.28936 20 3.74817 19.9558C3.18608 19.9099 2.66937 19.8113 2.18404 19.564C1.43139 19.1805 0.819469 18.5686 0.435975 17.816C0.188684 17.3306 0.0901197 16.8139 0.0441945 16.2518C-2.28137e-05 15.7106 -1.23241e-05 15.0463 4.31292e-07 14.2413V7.7587C-1.23241e-05 6.95373 -2.28137e-05 6.28937 0.0441945 5.74817C0.0901197 5.18608 0.188684 4.66937 0.435975 4.18404C0.819468 3.43139 1.43139 2.81947 2.18404 2.43597C2.66937 2.18868 3.18608 2.09012 3.74818 2.04419C4.28937 1.99998 4.95373 1.99999 5.7587 2Z"
                                                                            fill="var(--mm-blue-color)" />
                                                                    </svg>
                                                                </a>
                                                            </li>
                                                            <?php if ($phone_button): ?>
                                                                <li>
                                                                    <a href="<?php echo $phone_button['url'] ?>">
                                                                        <svg width="22" height="22" viewBox="0 0 22 22" fill="none"
                                                                            xmlns="http://www.w3.org/2000/svg">
                                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                                d="M5.90502 2.66234C5.62124 2.5188 5.2861 2.5188 5.00232 2.66234C4.8956 2.71632 4.76256 2.83158 4.17554 3.41861L4.0179 3.57624C3.45769 4.13646 3.30668 4.29848 3.18597 4.51336C3.04606 4.76243 2.92884 5.21317 2.92969 5.49884C2.93045 5.75305 2.96798 5.90614 3.13324 6.48839C3.94884 9.36196 5.48744 12.0737 7.75177 14.3381C10.0161 16.6024 12.7279 18.141 15.6014 18.9566C16.1837 19.1219 16.3368 19.1594 16.591 19.1601C16.8767 19.161 17.3274 19.0438 17.5765 18.9039C17.7914 18.7831 17.9534 18.6321 18.5136 18.0719L18.6712 17.9143C19.2582 17.3273 19.3735 17.1942 19.4275 17.0875C19.571 16.8037 19.571 16.4686 19.4275 16.1848C19.3735 16.0781 19.2583 15.9451 18.6712 15.358L19.3783 14.6509L18.6712 15.358L18.4764 15.1632C18.0907 14.7775 18.0039 14.6985 17.9382 14.6557C17.6067 14.4402 17.1795 14.4402 16.848 14.6557C16.7823 14.6985 16.6955 14.7775 16.3098 15.1632C16.3022 15.1708 16.2944 15.1786 16.2864 15.1866C16.1967 15.2767 16.0831 15.3907 15.9466 15.4884L15.3644 14.6754L15.9466 15.4884C15.4596 15.8371 14.7976 15.95 14.2226 15.7824C14.0622 15.7356 13.9302 15.672 13.8275 15.6225C13.8193 15.6186 13.8114 15.6148 13.8037 15.611C12.2537 14.8669 10.802 13.8528 9.51953 12.5703C8.23706 11.2878 7.22297 9.83613 6.4788 8.28617C6.47507 8.27842 6.47125 8.27048 6.46733 8.26236C6.41783 8.15967 6.35419 8.02766 6.30744 7.86728L7.26748 7.58743L6.30744 7.86728C6.13981 7.29225 6.25271 6.63018 6.60141 6.1432L6.60142 6.14319C6.69911 6.00676 6.81314 5.89315 6.9032 5.80341C6.91122 5.79542 6.91906 5.78761 6.92667 5.78C7.31238 5.39429 7.39136 5.30756 7.43409 5.24183L7.4341 5.24183C7.64959 4.91038 7.64959 4.48309 7.4341 4.15165C7.39136 4.08591 7.31238 3.99918 6.92667 3.61347L6.7318 3.4186C6.14478 2.83158 6.01174 2.71632 5.90502 2.66234ZM4.09963 0.877641C4.95097 0.447037 5.95637 0.447036 6.80771 0.877641C7.24031 1.09645 7.61586 1.47296 8.0503 1.90851C8.08188 1.94017 8.11377 1.97215 8.14601 2.00439L8.34088 2.19926C8.36213 2.22051 8.38318 2.24152 8.40402 2.26233C8.69068 2.5485 8.93761 2.79501 9.11085 3.06146L8.27247 3.60655L9.11085 3.06146C9.75734 4.0558 9.75734 5.33767 9.11085 6.33201C8.93761 6.59846 8.69068 6.84497 8.40402 7.13114C8.38318 7.15195 8.36213 7.17296 8.34088 7.19421C8.28328 7.25182 8.25389 7.28136 8.23342 7.30302C8.23304 7.30465 8.23268 7.30645 8.23235 7.3084C8.23198 7.31056 8.23171 7.31257 8.23151 7.31438C8.23481 7.32169 8.23927 7.33136 8.24542 7.34443C8.25503 7.36485 8.26632 7.38839 8.28176 7.42053C8.92958 8.76981 9.81307 10.0354 10.9337 11.1561C12.0544 12.2768 13.32 13.1603 14.6693 13.8081L14.2365 14.7096L14.6693 13.8081C14.7014 13.8235 14.725 13.8348 14.7454 13.8444C14.7585 13.8506 14.7681 13.855 14.7755 13.8583C14.7773 13.8581 14.7793 13.8579 14.7814 13.8575C14.7834 13.8572 14.7852 13.8568 14.7868 13.8564C14.8085 13.8359 14.838 13.8066 14.8956 13.7489C14.9169 13.7277 14.9379 13.7066 14.9587 13.6858C15.2449 13.3991 15.4914 13.1522 15.7578 12.979C16.7522 12.3325 18.034 12.3325 19.0284 12.979C19.2948 13.1522 19.5413 13.3991 19.8275 13.6858C19.8483 13.7066 19.8693 13.7277 19.8906 13.7489L19.1835 14.4561L19.8906 13.749L20.0854 13.9438C20.1177 13.9761 20.1497 14.0079 20.1813 14.0395C20.6169 14.474 20.9934 14.8495 21.2122 15.2821C21.6428 16.1335 21.6428 17.1389 21.2122 17.9902C20.9934 18.4228 20.6169 18.7984 20.1813 19.2328C20.1497 19.2644 20.1177 19.2963 20.0854 19.3285L19.9278 19.4861C19.905 19.509 19.8824 19.5316 19.8601 19.5539C19.3948 20.0196 19.0381 20.3768 18.556 20.6476C18.0061 20.9565 17.2158 21.162 16.585 21.1601C16.0332 21.1585 15.6307 21.0441 15.1117 20.8966C15.0931 20.8913 15.0743 20.886 15.0554 20.8806C11.8621 19.9743 8.84919 18.2639 6.33755 15.7523C3.82592 13.2406 2.11557 10.2277 1.20923 7.03448C1.20386 7.01555 1.19853 6.99678 1.19323 6.97816C1.04573 6.45915 0.931342 6.05668 0.9297 5.5048C0.927823 4.87402 1.13333 4.08378 1.44226 3.53384L1.44226 3.53384C1.71306 3.05177 2.07021 2.69498 2.53594 2.22973C2.55828 2.20742 2.58086 2.18486 2.60369 2.16203L2.76132 2.00439C2.79357 1.97215 2.82546 1.94017 2.85704 1.90851C3.29148 1.47296 3.66703 1.09645 4.09963 0.877642L4.54586 1.75988L4.09963 0.877641Z"
                                                                                fill="var(--mm-blue-color)" />
                                                                        </svg>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <?php if (get_option('admin_email')): ?>
                                                                <li>
                                                                    <span class="js-email" data-email="<?php echo esc_attr( $admin_email ); ?>" aria-label="Email">
                                                                        <svg width="22" height="18" viewBox="0 0 22 18" fill="none"
                                                                            xmlns="http://www.w3.org/2000/svg">
                                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                                d="M5.75879 4.31292e-07H16.2414C17.0464 -1.23241e-05 17.7107 -2.28137e-05 18.2519 0.0441945C18.814 0.0901197 19.3307 0.188684 19.816 0.435975C20.5687 0.819468 21.1806 1.43139 21.5641 2.18404C21.8027 2.65238 21.9029 3.14994 21.9509 3.68931C22.0043 3.8527 22.0137 4.02505 21.9819 4.18959C22.0001 4.63971 22.0001 5.16035 22.0001 5.75868V12.2413C22.0001 13.0463 22.0001 13.7106 21.9559 14.2518C21.91 14.8139 21.8114 15.3306 21.5641 15.816C21.1806 16.5686 20.5687 17.1805 19.816 17.564C19.3307 17.8113 18.814 17.9099 18.2519 17.9558C17.7107 18 17.0464 18 16.2414 18H5.75876C4.9538 18 4.28945 18 3.74826 17.9558C3.18616 17.9099 2.66946 17.8113 2.18412 17.564C1.43148 17.1805 0.819553 16.5686 0.43606 15.816C0.188769 15.3306 0.0902046 14.8139 0.0442794 14.2518C6.2082e-05 13.7106 7.25738e-05 13.0463 8.53292e-05 12.2413V5.7587C7.57925e-05 5.16037 6.75675e-05 4.63972 0.0182226 4.1896C-0.0135336 4.02505 -0.00416067 3.85269 0.0493014 3.6893C0.0972895 3.14993 0.197428 2.65238 0.43606 2.18404C0.819553 1.43139 1.43148 0.819468 2.18412 0.435975C2.66946 0.188684 3.18616 0.0901197 3.74826 0.0441945C4.28946 -2.28137e-05 4.95382 -1.23241e-05 5.75879 4.31292e-07ZM2.00009 5.92066V12.2C2.00009 12.5832 2.00024 12.9115 2.00371 13.1976L6.54106 9.09934L2.00009 5.92066ZM8.69856 8.16827L2.08286 3.53728C2.11858 3.33012 2.16506 3.19607 2.21807 3.09202C2.40982 2.7157 2.71578 2.40973 3.0921 2.21799C3.2485 2.1383 3.47271 2.07337 3.91112 2.03755C4.36121 2.00078 4.94351 2 5.80009 2H16.2001C17.0567 2 17.639 2.00078 18.089 2.03755C18.5275 2.07337 18.7517 2.1383 18.9081 2.21799C19.2844 2.40973 19.5904 2.7157 19.7821 3.09202C19.8351 3.19607 19.8816 3.33012 19.9173 3.53728L13.3016 8.16825C13.2901 8.17599 13.2786 8.18399 13.2673 8.19226L12.2617 8.89621C11.5328 9.40647 11.3783 9.49501 11.242 9.529C11.0831 9.56859 10.917 9.56859 10.7582 9.529C10.6218 9.49501 10.4674 9.40647 9.73847 8.89621L8.73279 8.19224C8.72152 8.18399 8.7101 8.176 8.69856 8.16827ZM8.22208 10.276L2.56121 15.3891C2.71425 15.5476 2.89336 15.6807 3.0921 15.782C3.2485 15.8617 3.47271 15.9266 3.91112 15.9624C4.36121 15.9992 4.94351 16 5.80009 16H16.2001C17.0567 16 17.639 15.9992 18.089 15.9624C18.5275 15.9266 18.7517 15.8617 18.9081 15.782C19.1068 15.6807 19.2859 15.5476 19.439 15.3891L13.7781 10.2761L13.4086 10.5347C13.3698 10.5618 13.3314 10.5888 13.2933 10.6156C12.7486 10.998 12.2704 11.3338 11.7257 11.4696C11.2492 11.5884 10.7509 11.5884 10.2745 11.4696C9.72979 11.3338 9.25154 10.998 8.70689 10.6156C8.66878 10.5888 8.63035 10.5618 8.59154 10.5347L8.22208 10.276ZM15.4591 9.09934L19.9965 13.1976C19.9999 12.9115 20.0001 12.5832 20.0001 12.2V5.92066L15.4591 9.09934Z"
                                                                                fill="var(--mm-blue-color)" />
                                                                        </svg>
                                                                    </span>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
	                                <?php if (!empty($building_simplified_listings) && is_array($building_simplified_listings)): ?>
                                            <?php foreach ($building_simplified_listings as $accordion): ?>
                                            <div data-target="accordion" class="accordion">
                                                <div class="header-accordion">
                                                    <span>Suite</span>
                                                    <p class="bold">
                                                        <?php echo $accordion['suite']; ?>
                                                    </p>
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M7 14L12 9L17 14" stroke="var(--mm-placeholder-grey-2)"
                                                            stroke-width="2" />
                                                        <circle cx="12" cy="12" r="11" stroke="var(--mm-placeholder-grey-2)"
                                                            stroke-width="2" />
                                                    </svg>
                                                </div>
                                                <div class="accordion-content">
                                                    <ul class="accordion-items">
                                                        <?php if ($accordion['size']): ?>
                                                            <li>
                                                                <span>Size</span>
                                                                <p>
                                                                    <?php echo number_format($accordion['size']); ?>
                                                                    SF
                                                                </p>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($accordion['uses']): ?>
                                                            <li>
                                                                <span>Use</span>
                                                                <?php
                                                                $terms = [];
                                                                foreach ($accordion['uses'] as $item) {
                                                                    $terms[] = get_term_by('term_id', $item, 'listing-type');
                                                                }
                                                                $array = array_map(function ($item) {
                                                                    return $item->name;
                                                                }, $terms);
                                                                ?>
                                                                <p>
                                                                    <?php echo join(', ', $array) ?>
                                                                </p>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($accordion['type']): ?>
                                                            <li>
                                                                <span>Types</span>
                                                                <p>
                                                                    <?php echo $accordion['type']; ?>
                                                                </p>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($accordion['price'] || $accordion['price_number']): ?>
                                                            <li>
                                                                <span>Price</span>
                                                                <?php
                                                                $string = ($accordion['price'] == 'text') ? 'Upon request' : '$' . $accordion['price_number'] . '/SF';
                                                                ?>
                                                                <p>
                                                                    <?php echo $string ?>
                                                                </p>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($accordion['available']): ?>
                                                            <li>
                                                                <span>Available</span>
                                                                <p>
                                                                    <?php echo $accordion['available']; ?>
                                                                </p>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li class="buttons">
                                                            <ul>
                                                                <?php if ($phone_button): ?>
                                                                    <li>
                                                                        <a href="<?php echo $phone_button['url'] ?>">
                                                                            <svg width="22" height="22" viewBox="0 0 22 22" fill="none"
                                                                                xmlns="http://www.w3.org/2000/svg">
                                                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                                                    d="M5.90502 2.66234C5.62124 2.5188 5.2861 2.5188 5.00232 2.66234C4.8956 2.71632 4.76256 2.83158 4.17554 3.41861L4.0179 3.57624C3.45769 4.13646 3.30668 4.29848 3.18597 4.51336C3.04606 4.76243 2.92884 5.21317 2.92969 5.49884C2.93045 5.75305 2.96798 5.90614 3.13324 6.48839C3.94884 9.36196 5.48744 12.0737 7.75177 14.3381C10.0161 16.6024 12.7279 18.141 15.6014 18.9566C16.1837 19.1219 16.3368 19.1594 16.591 19.1601C16.8767 19.161 17.3274 19.0438 17.5765 18.9039C17.7914 18.7831 17.9534 18.6321 18.5136 18.0719L18.6712 17.9143C19.2582 17.3273 19.3735 17.1942 19.4275 17.0875C19.571 16.8037 19.571 16.4686 19.4275 16.1848C19.3735 16.0781 19.2583 15.9451 18.6712 15.358L19.3783 14.6509L18.6712 15.358L18.4764 15.1632C18.0907 14.7775 18.0039 14.6985 17.9382 14.6557C17.6067 14.4402 17.1795 14.4402 16.848 14.6557C16.7823 14.6985 16.6955 14.7775 16.3098 15.1632C16.3022 15.1708 16.2944 15.1786 16.2864 15.1866C16.1967 15.2767 16.0831 15.3907 15.9466 15.4884L15.3644 14.6754L15.9466 15.4884C15.4596 15.8371 14.7976 15.95 14.2226 15.7824C14.0622 15.7356 13.9302 15.672 13.8275 15.6225C13.8193 15.6186 13.8114 15.6148 13.8037 15.611C12.2537 14.8669 10.802 13.8528 9.51953 12.5703C8.23706 11.2878 7.22297 9.83613 6.4788 8.28617C6.47507 8.27842 6.47125 8.27048 6.46733 8.26236C6.41783 8.15967 6.35419 8.02766 6.30744 7.86728L7.26748 7.58743L6.30744 7.86728C6.13981 7.29225 6.25271 6.63018 6.60141 6.1432L6.60142 6.14319C6.69911 6.00676 6.81314 5.89315 6.9032 5.80341C6.91122 5.79542 6.91906 5.78761 6.92667 5.78C7.31238 5.39429 7.39136 5.30756 7.43409 5.24183L7.4341 5.24183C7.64959 4.91038 7.64959 4.48309 7.4341 4.15165C7.39136 4.08591 7.31238 3.99918 6.92667 3.61347L6.7318 3.4186C6.14478 2.83158 6.01174 2.71632 5.90502 2.66234ZM4.09963 0.877641C4.95097 0.447037 5.95637 0.447036 6.80771 0.877641C7.24031 1.09645 7.61586 1.47296 8.0503 1.90851C8.08188 1.94017 8.11377 1.97215 8.14601 2.00439L8.34088 2.19926C8.36213 2.22051 8.38318 2.24152 8.40402 2.26233C8.69068 2.5485 8.93761 2.79501 9.11085 3.06146L8.27247 3.60655L9.11085 3.06146C9.75734 4.0558 9.75734 5.33767 9.11085 6.33201C8.93761 6.59846 8.69068 6.84497 8.40402 7.13114C8.38318 7.15195 8.36213 7.17296 8.34088 7.19421C8.28328 7.25182 8.25389 7.28136 8.23342 7.30302C8.23304 7.30465 8.23268 7.30645 8.23235 7.3084C8.23198 7.31056 8.23171 7.31257 8.23151 7.31438C8.23481 7.32169 8.23927 7.33136 8.24542 7.34443C8.25503 7.36485 8.26632 7.38839 8.28176 7.42053C8.92958 8.76981 9.81307 10.0354 10.9337 11.1561C12.0544 12.2768 13.32 13.1603 14.6693 13.8081L14.2365 14.7096L14.6693 13.8081C14.7014 13.8235 14.725 13.8348 14.7454 13.8444C14.7585 13.8506 14.7681 13.855 14.7755 13.8583C14.7773 13.8581 14.7793 13.8579 14.7814 13.8575C14.7834 13.8572 14.7852 13.8568 14.7868 13.8564C14.8085 13.8359 14.838 13.8066 14.8956 13.7489C14.9169 13.7277 14.9379 13.7066 14.9587 13.6858C15.2449 13.3991 15.4914 13.1522 15.7578 12.979C16.7522 12.3325 18.034 12.3325 19.0284 12.979C19.2948 13.1522 19.5413 13.3991 19.8275 13.6858C19.8483 13.7066 19.8693 13.7277 19.8906 13.7489L19.1835 14.4561L19.8906 13.749L20.0854 13.9438C20.1177 13.9761 20.1497 14.0079 20.1813 14.0395C20.6169 14.474 20.9934 14.8495 21.2122 15.2821C21.6428 16.1335 21.6428 17.1389 21.2122 17.9902C20.9934 18.4228 20.6169 18.7984 20.1813 19.2328C20.1497 19.2644 20.1177 19.2963 20.0854 19.3285L19.9278 19.4861C19.905 19.509 19.8824 19.5316 19.8601 19.5539C19.3948 20.0196 19.0381 20.3768 18.556 20.6476C18.0061 20.9565 17.2158 21.162 16.585 21.1601C16.0332 21.1585 15.6307 21.0441 15.1117 20.8966C15.0931 20.8913 15.0743 20.886 15.0554 20.8806C11.8621 19.9743 8.84919 18.2639 6.33755 15.7523C3.82592 13.2406 2.11557 10.2277 1.20923 7.03448C1.20386 7.01555 1.19853 6.99678 1.19323 6.97816C1.04573 6.45915 0.931342 6.05668 0.9297 5.5048C0.927823 4.87402 1.13333 4.08378 1.44226 3.53384L1.44226 3.53384C1.71306 3.05177 2.07021 2.69498 2.53594 2.22973C2.55828 2.20742 2.58086 2.18486 2.60369 2.16203L2.76132 2.00439C2.79357 1.97215 2.82546 1.94017 2.85704 1.90851C3.29148 1.47296 3.66703 1.09645 4.09963 0.877642L4.54586 1.75988L4.09963 0.877641Z"
                                                                                    fill="var(--mm-blue-color)" />
                                                                            </svg>
                                                                        </a>
                                                                    </li>
                                                                <?php endif; ?>
                                                                <?php if (get_option('admin_email')): ?>
                                                                    <li>
                                                                        <span class="js-email" data-email="<?php echo esc_attr( $admin_email ); ?>" aria-label="Email">
                                                                            <svg width="22" height="18" viewBox="0 0 22 18" fill="none"
                                                                                xmlns="http://www.w3.org/2000/svg">
                                                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                                                    d="M5.75879 4.31292e-07H16.2414C17.0464 -1.23241e-05 17.7107 -2.28137e-05 18.2519 0.0441945C18.814 0.0901197 19.3307 0.188684 19.816 0.435975C20.5687 0.819468 21.1806 1.43139 21.5641 2.18404C21.8027 2.65238 21.9029 3.14994 21.9509 3.68931C22.0043 3.8527 22.0137 4.02505 21.9819 4.18959C22.0001 4.63971 22.0001 5.16035 22.0001 5.75868V12.2413C22.0001 13.0463 22.0001 13.7106 21.9559 14.2518C21.91 14.8139 21.8114 15.3306 21.5641 15.816C21.1806 16.5686 20.5687 17.1805 19.816 17.564C19.3307 17.8113 18.814 17.9099 18.2519 17.9558C17.7107 18 17.0464 18 16.2414 18H5.75876C4.9538 18 4.28945 18 3.74826 17.9558C3.18616 17.9099 2.66946 17.8113 2.18412 17.564C1.43148 17.1805 0.819553 16.5686 0.43606 15.816C0.188769 15.3306 0.0902046 14.8139 0.0442794 14.2518C6.2082e-05 13.7106 7.25738e-05 13.0463 8.53292e-05 12.2413V5.7587C7.57925e-05 5.16037 6.75675e-05 4.63972 0.0182226 4.1896C-0.0135336 4.02505 -0.00416067 3.85269 0.0493014 3.6893C0.0972895 3.14993 0.197428 2.65238 0.43606 2.18404C0.819553 1.43139 1.43148 0.819468 2.18412 0.435975C2.66946 0.188684 3.18616 0.0901197 3.74826 0.0441945C4.28946 -2.28137e-05 4.95382 -1.23241e-05 5.75879 4.31292e-07ZM2.00009 5.92066V12.2C2.00009 12.5832 2.00024 12.9115 2.00371 13.1976L6.54106 9.09934L2.00009 5.92066ZM8.69856 8.16827L2.08286 3.53728C2.11858 3.33012 2.16506 3.19607 2.21807 3.09202C2.40982 2.7157 2.71578 2.40973 3.0921 2.21799C3.2485 2.1383 3.47271 2.07337 3.91112 2.03755C4.36121 2.00078 4.94351 2 5.80009 2H16.2001C17.0567 2 17.639 2.00078 18.089 2.03755C18.5275 2.07337 18.7517 2.1383 18.9081 2.21799C19.2844 2.40973 19.5904 2.7157 19.7821 3.09202C19.8351 3.19607 19.8816 3.33012 19.9173 3.53728L13.3016 8.16825C13.2901 8.17599 13.2786 8.18399 13.2673 8.19226L12.2617 8.89621C11.5328 9.40647 11.3783 9.49501 11.242 9.529C11.0831 9.56859 10.917 9.56859 10.7582 9.529C10.6218 9.49501 10.4674 9.40647 9.73847 8.89621L8.73279 8.19224C8.72152 8.18399 8.7101 8.176 8.69856 8.16827ZM8.22208 10.276L2.56121 15.3891C2.71425 15.5476 2.89336 15.6807 3.0921 15.782C3.2485 15.8617 3.47271 15.9266 3.91112 15.9624C4.36121 15.9992 4.94351 16 5.80009 16H16.2001C17.0567 16 17.639 15.9992 18.089 15.9624C18.5275 15.9266 18.7517 15.8617 18.9081 15.782C19.1068 15.6807 19.2859 15.5476 19.439 15.3891L13.7781 10.2761L13.4086 10.5347C13.3698 10.5618 13.3314 10.5888 13.2933 10.6156C12.7486 10.998 12.2704 11.3338 11.7257 11.4696C11.2492 11.5884 10.7509 11.5884 10.2745 11.4696C9.72979 11.3338 9.25154 10.998 8.70689 10.6156C8.66878 10.5888 8.63035 10.5618 8.59154 10.5347L8.22208 10.276ZM15.4591 9.09934L19.9965 13.1976C19.9999 12.9115 20.0001 12.5832 20.0001 12.2V5.92066L15.4591 9.09934Z"
                                                                                    fill="var(--mm-blue-color)" />
                                                                            </svg>
                                                                        </span>
                                                                    </li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($building_coordinates)): ?>
                            <div class="listing-map">
                                <h3>Building Location & Nearby Public Transportation</h3>
                                <div data-api-key="<?php echo $google_map_api_key ?>"
                                    data-lat="<?php echo $building_coordinates['lat'] ?>"
                                    data-lng="<?php echo $building_coordinates['lng'] ?>" data-target="google_map"></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($nearby_transport)): ?>
                            <div class="listing-transport">
                                <h3>Nearby Transportation</h3>
                                <div class="table">
                                    <?php if (!empty($nearby_transport['subway'])):
                                        $string = '';
                                        for ($i = 0; $i < sizeof($nearby_transport['subway']); $i++) {
                                            $minutes = ($nearby_transport['subway'][$i]['distance'] > 1) ? $nearby_transport['subway'][$i]['distance'] . ' minutes' : $nearby_transport['subway'][$i]['distance'] . ' minute';
                                            $string .= $nearby_transport['subway'][$i]['name'] . ' - ' . $minutes . ' Walk';
                                            if ($i < sizeof($nearby_transport['subway']) - 1) {
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
                                    <?php if (!empty($nearby_transport['bus'])):
                                        $string = '';
                                        for ($i = 0; $i < sizeof($nearby_transport['bus']); $i++) {
                                            $minutes = ($nearby_transport['bus'][$i]['distance'] > 1) ? $nearby_transport['bus'][$i]['distance'] . ' minutes' : $nearby_transport['bus'][$i]['distance'] . ' minute';
                                            $string .= $nearby_transport['bus'][$i]['name'] . ' - ' . $minutes . ' Walk';
                                            if ($i < sizeof($nearby_transport['bus']) - 1) {
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
                                    <?php if (!empty($nearby_transport['parking'])):
                                        $string = '';
                                        for ($i = 0; $i < sizeof($nearby_transport['parking']); $i++) {
                                            $minutes = ($nearby_transport['parking'][$i]['distance'] > 1) ? $nearby_transport['parking'][$i]['distance'] . ' minutes' : $nearby_transport['parking'][$i]['distance'] . ' minute';
                                            $string .= $nearby_transport['parking'][$i]['name'] . ' - ' . $minutes . ' Walk';
                                            if ($i < sizeof($nearby_transport['parking']) - 1) {
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
                        <?php if ($building_tenants && sizeof($building_tenants) > 0 && $type_of_building_tenants == 'slider'): ?>
                            <div data-target="building_tenants" class="building-tenants">
                                <div class="heading">
                                    <h3>Notable Tenants</h3>
                                    <?php if (sizeof($building_tenants) > 4): ?>
                                        <div class="controllers">
                                            <button data-target="swiper_left" class="btn" aria-label="Previous slide">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24"
                                                    viewBox="0 0 15 24" fill="none">
                                                    <path
                                                        d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z"
                                                        fill="#0961AF"></path>
                                                </svg>
                                            </button>
                                            <button data-target="swiper_right" class="btn" aria-label="Next slide">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24"
                                                    viewBox="0 0 15 24" fill="none">
                                                    <path
                                                        d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z"
                                                        fill="#0961AF"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="swiper">
                                    <div class="swiper-wrapper blocks">
                                        <?php while (have_rows('tenants')):
                                            the_row();
                                            $image = get_sub_field('company_logo');
                                            $title = get_sub_field('company_name');
                                            $text = get_sub_field('company_activity');
                                            ?>
                                            <div class="block swiper-slide">
                                                <?php if ($image): ?>
                                                    <div class="image">
                                                        <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => 'lazy']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($title || $text): ?>
                                                    <div class="text">
                                                        <?php if ($title): ?>
                                                            <h4 class="title">
                                                                <?php echo $title ?>
                                                            </h4>
                                                        <?php endif; ?>
                                                        <?php if ($text): ?>
                                                            <span>
                                                                <?php echo $text ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($type_of_building_tenants == 'text' && $building_text_tenants): ?>
                            <div class="building-tenants text">
                                <h3>Notable Tenants</h3>
                                <p>
                                    <?php echo $building_text_tenants ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        <div class="listing-important">
                            <h3>Important Information</h3>
                            <div class="text">
                                <div class="icon">
                                    <svg width="24" height="25" viewBox="0 0 24 25" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12.7969" r="11.5" stroke="var(--mm-navy-color)" />
                                        <path
                                            d="M13.25 4.79688C12.2875 4.79688 11.5 5.58437 11.5 6.54688C11.5 7.50938 12.2875 8.29688 13.25 8.29688C14.2125 8.29688 15 7.50938 15 6.54688C15 5.58437 14.2125 4.79688 13.25 4.79688ZM10.625 9.17188C9.1725 9.17188 8 10.3444 8 11.7969H9.75C9.75 11.3069 10.135 10.9219 10.625 10.9219C11.115 10.9219 11.5 11.3069 11.5 11.7969C11.5 12.2869 9.75 14.6669 9.75 16.1719C9.75 17.6769 10.9225 18.7969 12.375 18.7969C13.8275 18.7969 15 17.6244 15 16.1719H13.25C13.25 16.6619 12.865 17.0469 12.375 17.0469C11.885 17.0469 11.5 16.6619 11.5 16.1719C11.5 15.5419 13.25 12.9519 13.25 11.7969C13.25 10.3794 12.0775 9.17188 10.625 9.17188Z"
                                            fill="var(--mm-blue-color)" />
                                    </svg>
                                </div>
                                <p>Listings are presented for illustrative purposes only; they may no longer be
                                    available and are provided merely as an exemplary representation of the types of
                                    spaces in a given neighborhood for a given price.</p>
                            </div>
                        </div>
                    </div>
                    <?php if ($global_form_title || $global_form_text || $global_form_shortcode): ?>
                        <aside class="sidebar">
                            <div data-target="inqury" class="inqury custom-scroll">
                                <div class="close-btn">
                                    <button aria-label="Close contact form" data-target="close_contact_button"
                                        type="button">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M20.0001 1.32074L18.6793 0L10.0001 8.6792L1.32086 0L0 1.32074L8.67926 10L0 18.6793L1.32086 20L10.0001 11.3208L18.6793 20L20.0001 18.6793L11.3209 10L20.0001 1.32074Z"
                                                fill="var(--mm-navy-color)" />
                                        </svg>
                                    </button>
                                </div>
                                <div data-target="inqury_form" class="form">
                                    <div class="content">
                                        <?php if ($global_form_title):
                                            $global_form_title = (str_contains($global_form_title, '{{post_title}}')) ? str_replace('{{post_title}}', get_the_title(), $global_form_title) : $global_form_title; ?>
                                            <h2 class="office-space-inquiry-heading">
                                                <?php echo $global_form_title ?>
                                            </h2>
                                        <?php endif; ?>
                                        <?php if ($global_form_text): ?>
                                            <p>
                                                <?php echo $global_form_text ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($global_form_shortcode): ?>
                                            <div class="shortcode">
                                                <?php echo do_shortcode($global_form_shortcode); ?>
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
                                        <?php if ($global_aside_thank_you_title): ?>
                                            <h2>
                                                <?php echo $global_aside_thank_you_title ?>
                                            </h2>
                                        <?php endif; ?>
                                        <?php if ($global_aside_thank_you_text): ?>
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
                <?php if (is_object($building_neighborhood)):
                    $building_neighborhood_images = get_field('images', 'location_' . $building_neighborhood->term_id);
                    $building_neighborhood_link = get_field('page_id', 'location_' . $building_neighborhood->term_id);
                    ?>
                    <section
                        class="single-custom-post-location <?php echo (!$building_neighborhood_images) ? 'no-image' : '' ?>">
                        <div class="content">
                            <?php if ($building_neighborhood_images): ?>
                                <div class="images">
                                    <?php foreach ($building_neighborhood_images as $image): ?>
                                        <?php echo wp_get_attachment_image($image['ID'], 'full', '', ['loading' => 'lazy']) ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="text">
                                <h3>
                                    <?php echo $building_neighborhood->name ?>
                                </h3>
                                <?php if ($building_neighborhood_link): ?>
                                    <p>
                                        <?php echo wp_trim_words(get_post_field('post_content', $building_neighborhood_link)); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($building_neighborhood_link): ?>
                                    <a aria-label="Learn more about <?php echo esc_attr($building_neighborhood->name) ?>"
                                        href="<?php echo get_permalink($building_neighborhood_link) ?>" class="link">Learn
                                        more about
                                        <?php echo esc_html($building_neighborhood->name) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
                <?php if ($building_nearby_offices): ?>
                    <section data-target="similar_listings" class="single-custom-post-similar-listings mm-section">
                        <div class="content">
                            <div class="heading">
                                <h2>Nearby Office Rentals</h2>
                                <div class="heading-part">
                                    <?php if ($building_nearby_offices && sizeof($building_nearby_offices) > 3): ?>
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
                                    if ($global_offices_nearby_button):
                                        $link_url = $global_offices_nearby_button['url'];
                                        $link_title = $global_offices_nearby_button['title'];
                                        $link_target = $global_offices_nearby_button['target'] ? $global_offices_nearby_button['target'] : '_self';
                                        ?>
                                        <a href="<?php echo esc_url($link_url); ?>"
                                            target="<?php echo esc_attr($link_target); ?>">
                                            <?php echo esc_html($link_title); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="swiper">
                                <div class="swiper-wrapper blocks">
                                    <?php foreach ($building_nearby_offices as $building_nearby_office): ?>
                                        <div class="swiper-slide block">
                                            <?php get_template_part('templates/parts/listing', 'card', ['id' => $building_nearby_office, 'map_card' => false, 'favourites_template' => false]) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
                <?php if ($building_nearby_buildings): ?>
                    <section data-target="similar_listings" class="single-custom-post-similar-listings mm-section">
                        <div class="content">
                            <div class="heading">
                                <h2>Nearby Office Buildings</h2>
                                <div class="heading-part">
                                    <?php if ($building_nearby_buildings && sizeof($building_nearby_buildings) > 3): ?>
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
                                    if ($global_featured_listings_button):
                                        $link_url = $global_featured_listings_button['url'];
                                        $link_title = $global_featured_listings_button['title'];
                                        $link_target = $global_featured_listings_button['target'] ? $global_featured_listings_button['target'] : '_self';
                                        ?>
                                        <a href="<?php echo esc_url($link_url); ?>"
                                            target="<?php echo esc_attr($link_target); ?>">
                                            <?php echo esc_html($link_title); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="swiper">
                                <div class="swiper-wrapper blocks">
                                    <?php foreach ($building_nearby_buildings as $building_nearby_building): ?>
                                        <div class="swiper-slide block">
                                            <?php get_template_part('templates/parts/listing', 'card', ['id' => $building_nearby_building, 'building' => true, 'class' => 'building', 'favourites_template' => false]) ?>
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
                                stroke-linecap="round" />
                        </svg>
                        <span>Back to top</span>
                    </button>
                </div>
                <div data-target="schedule_wrapper" class="schedule">
                    <div data-target="schedule_message" class="schedule-message success-message">
                        <div class="icon">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <rect width="40" height="40" rx="20" fill="#308C05" fill-opacity="0.15" />
                                <path
                                    d="M17.6605 26.0009C17.5106 26.0004 17.3623 25.9718 17.2249 25.9168C17.0876 25.8617 16.9641 25.7814 16.8621 25.6809L11.5465 20.5109C11.3478 20.3173 11.2413 20.0594 11.2506 19.7941C11.2598 19.5287 11.3839 19.2776 11.5957 19.0959C11.8075 18.9142 12.0895 18.8169 12.3797 18.8253C12.67 18.8338 12.9446 18.9473 13.1433 19.1409L17.6496 23.5309L26.848 14.3309C26.9414 14.2246 27.0575 14.1369 27.1893 14.0732C27.3211 14.0094 27.4657 13.971 27.6143 13.9602C27.7629 13.9494 27.9124 13.9665 28.0535 14.0104C28.1946 14.0544 28.3244 14.1242 28.4349 14.2157C28.5455 14.3072 28.6343 14.4184 28.6962 14.5424C28.758 14.6665 28.7914 14.8007 28.7944 14.937C28.7974 15.0733 28.7698 15.2087 28.7135 15.3349C28.6572 15.4611 28.5732 15.5754 28.4668 15.6709L18.4699 25.6709C18.3689 25.7732 18.2458 25.8554 18.1084 25.9122C17.971 25.969 17.8223 25.9992 17.6715 26.0009H17.6605Z"
                                    fill="#308C05" />
                            </svg>
                        </div>
                        <div class="text">
                            <?php if ($global_aside_thank_you_title): ?>
                                <h2>
                                    <?php echo $global_aside_thank_you_title ?>
                                </h2>
                            <?php endif; ?>
                            <?php if ($global_aside_thank_you_text): ?>
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
                                        fill="var(--mm-grey-color)" />
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
                                        fill="var(--mm-blue-color)" />
                                </svg>
                            </button>
                        </div>
                        <div class="schedule-form">
                            <div class="content">
                                <?php if ($global_schedule_title): ?>
                                    <h2>
                                        <?php echo $global_schedule_title ?>
                                    </h2>
                                <?php endif; ?>
                                <?php if ($global_schedule_text): ?>
                                    <p>
                                        <?php echo $global_schedule_text ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($global_schedule_shortcode): ?>
                                    <div class="form">
                                        <?php echo do_shortcode($global_schedule_shortcode) ?>
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
