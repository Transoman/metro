<?php
// File location: app/public/wp-content/themes/metro/templates/parts/listing-card.php

$current_user_id = wp_get_current_user()->ID;
$target = '';
if (is_user_logged_in() && array_key_exists('favourites_template', $args) && $args['favourites_template'] === false) {
    $target = 'add_to_favourites';
} elseif (is_user_logged_in() && array_key_exists('favourites_template', $args) && $args['favourites_template'] === true) {
    $target = 'fetch_favourites';
} else {
    $target = 'authorization_button';
}
$matterport = get_field('matterport', $args['id']);

$additional_attributes = '';

$class_name = (array_key_exists('class', $args)) ? 'block ' . $args['class'] : 'block';
if (!array_key_exists('building', $args)) {
    $listing_coordinate = get_field('map', $args['id']);
    if ( $listing_coordinate && is_array( $listing_coordinate ) ) {
        $additional_attributes = "data-lat=\"" . $listing_coordinate['lat'] . "\" data-lng=\"" . $listing_coordinate['lng'] . "\" data-id=\"" . $args['id'] . "\"";
    }
}

?>

<!--swiper-zoom-target class has been commented out-->
<div data-target="custom_post" <?php echo $additional_attributes ?> class="<?php echo esc_attr($class_name) ?>">
    <a class="link" aria-label="<?php echo esc_attr(get_the_title($args['id'])) ?>"
        href="<?php echo esc_url(get_permalink($args['id'])) ?>">
        <div class="inner">
            <div class="top">
                <?php if (!array_key_exists('building', $args)): ?>
                    <button aria-label="add to favorites" data-target="<?php echo esc_attr($target) ?>"
                        class="like-btn <?php echo (MMFavourites::is_listing_favourite($current_user_id, $args['id'])) ? 'liked' : '' ?>">
                        <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g>
                                <mask style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="20"
                                    height="18">
                                    <path d="M20 0H0V18H20V0Z" fill="white" />
                                </mask>
                                <g>
                                    <path data-target="background"
                                        d="M10.001 17L17.7906 8.25728C18.5651 7.38621 19 6.20594 19 4.97541C19 3.74488 18.5652 2.56458 17.7908 1.69346L17.4931 1.35942C17.1091 0.928431 16.6533 0.586546 16.1515 0.3533C15.6498 0.120053 15.112 0 14.5689 0C14.0258 0 13.4881 0.120053 12.9864 0.3533C12.4847 0.586546 12.0288 0.928431 11.6448 1.35942L9.99603 3.2322L8.3551 1.35695C7.57935 0.487634 6.5278 -0.000442583 5.43157 2.33272e-05C4.33532 0.000489238 3.2841 0.489439 2.50895 1.35942L2.21124 1.6935C1.4357 2.56392 1 3.74444 1 4.9754C1 6.20635 1.4357 7.38687 2.21124 8.25728L10.001 17Z"
                                        fill="white" />
                                    <path
                                        d="M18.1596 1.55613C17.666 1.06254 17.08 0.671051 16.435 0.404043C15.79 0.137034 15.0988 -0.000261748 14.4008 3.74622e-07C13.7027 0.000262497 13.0116 0.138078 12.3668 0.40557C11.722 0.673063 11.1363 1.06499 10.6431 1.55895L9.99927 2.21062L9.3608 1.56036L9.35665 1.55621C8.86329 1.06285 8.27759 0.671497 7.63299 0.404493C6.98839 0.137489 6.29751 6.32352e-05 5.5998 6.32352e-05C4.90208 6.32352e-05 4.2112 0.137489 3.5666 0.404493C2.922 0.671497 2.3363 1.06285 1.84294 1.55621L1.55613 1.84303C0.559755 2.8394 0 4.19077 0 5.59986C0 7.00894 0.559755 8.36031 1.55613 9.35669L9.12599 16.9265L9.98084 17.8221L10.0012 17.8017L10.0233 17.8238L10.8243 16.9788L18.4464 9.35657C19.4413 8.3594 20 7.00832 20 5.59973C20 4.19114 19.4413 2.84007 18.4464 1.8429L18.1596 1.55613ZM17.507 8.41737L10.0012 15.9233L2.49528 8.41737C1.748 7.67009 1.32819 6.65657 1.32819 5.59976C1.32819 4.54294 1.748 3.52942 2.49528 2.78214L2.78214 2.49532C3.52905 1.74841 4.54197 1.32863 5.59826 1.32823C6.65455 1.32783 7.66778 1.74686 8.41526 2.4932L9.9964 4.10316L11.5851 2.49532C11.9551 2.1253 12.3944 1.83178 12.8778 1.63153C13.3613 1.43128 13.8794 1.32821 14.4027 1.32821C14.926 1.32821 15.4442 1.43128 15.9276 1.63153C16.4111 1.83178 16.8503 2.1253 17.2203 2.49532L17.5072 2.7821C18.2533 3.52999 18.6723 4.54332 18.6723 5.59977C18.6723 6.65622 18.2532 7.66952 17.507 8.41737Z"
                                        fill="white" />
                                </g>
                            </g>
                            <defs>
                                <clipPath>
                                    <rect width="20" height="18" fill="white" />
                                </clipPath>
                            </defs>
                        </svg>
                    </button>
		<?php endif; ?>
	            <?php if ( get_post_thumbnail_id( $args['id'] ) ): ?>
		            <?php
		            $thumbnail_id    = get_post_thumbnail_id( $args['id'] );
		            $image_url       = wp_get_attachment_url( $thumbnail_id );
		            $image_base_url  = preg_replace( '/\.[^.]+$/', '', $image_url );
		            $image_extension = pathinfo( $image_url, PATHINFO_EXTENSION );

		            $upload_dir = wp_upload_dir();

		            $image_url_full = $image_base_url . '.' . $image_extension;
		            $image_url_800  = $image_base_url . '-800x542.' . $image_extension;
		            $image_url_600  = $image_base_url . '-600x406.' . $image_extension;
		            $image_url_400  = $image_base_url . '-400x271.' . $image_extension;

		            $image_url_webp_full = $image_base_url . '.webp';
		            $image_url_webp_800  = $image_base_url . '-800x542.webp';
		            $image_url_webp_600  = $image_base_url . '-600x406.webp';
		            $image_url_webp_400  = $image_base_url . '-400x271.webp';

		            // Helper function to build srcset entries with proper spacing
		            $build_srcset_entry = function($url, $width) {
			            // Ensure proper URL encoding and spacing
			            $clean_url = esc_url($url);
			            // Use explicit spacing to prevent URL parsing issues
			            return $clean_url . ' ' . $width . 'w';
		            };

		            // Alternative approach: Use data attributes for SEO crawlers
		            $srcset_webp = [];
		            $srcset_jpeg = [];
		            
		            // Build arrays with URLs only (for SEO crawlers)
		            $urls_webp = [];
		            $urls_jpeg = [];
		            
		            if ( file_exists( str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url_webp_400 ) ) ) {
			            $srcset_webp[] = $build_srcset_entry( $image_url_webp_400, '400' );
			            $urls_webp[] = esc_url( $image_url_webp_400 );
		            }
		            if ( file_exists( str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url_webp_600 ) ) ) {
			            $srcset_webp[] = $build_srcset_entry( $image_url_webp_600, '600' );
			            $urls_webp[] = esc_url( $image_url_webp_600 );
		            }
		            if ( file_exists( str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url_webp_800 ) ) ) {
			            $srcset_webp[] = $build_srcset_entry( $image_url_webp_800, '800' );
			            $urls_webp[] = esc_url( $image_url_webp_800 );
		            }
		            if ( file_exists( str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url_webp_full ) ) ) {
			            $srcset_webp[] = $build_srcset_entry( $image_url_webp_full, '1000' );
			            $urls_webp[] = esc_url( $image_url_webp_full );
		            }

		            if ( file_exists( str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url_400 ) ) ) {
			            $srcset_jpeg[] = $build_srcset_entry( $image_url_400, '400' );
			            $urls_jpeg[] = esc_url( $image_url_400 );
		            }
		            if ( file_exists( str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url_600 ) ) ) {
			            $srcset_jpeg[] = $build_srcset_entry( $image_url_600, '600' );
			            $urls_jpeg[] = esc_url( $image_url_600 );
		            }
		            if ( file_exists( str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url_800 ) ) ) {
			            $srcset_jpeg[] = $build_srcset_entry( $image_url_800, '800' );
			            $urls_jpeg[] = esc_url( $image_url_800 );
		            }
		            if ( file_exists( str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url_full ) ) ) {
			            $srcset_jpeg[] = $build_srcset_entry( $image_url_full, '1000' );
			            $urls_jpeg[] = esc_url( $image_url_full );
		            }
		            ?>
		            <!--class="swiper-zoom-container"-->
                  <picture style="display:contents;">
	                  <?php 
	                  // SIMPLE SOLUTION: Just use the first image URL for all cases
	                  // This eliminates the srcset issue completely
	                  $primary_webp_url = !empty($urls_webp) ? $urls_webp[0] : '';
	                  $primary_jpeg_url = !empty($urls_jpeg) ? $urls_jpeg[0] : '';
	                  
	                  if ( ! empty( $primary_webp_url ) ): ?>
                        <source srcset="<?php echo esc_attr( $primary_webp_url ); ?>"
                                type="image/webp">
	                  <?php endif; ?>
	                  <?php if ( ! empty( $primary_jpeg_url ) ): ?>
                        <source srcset="<?php echo esc_attr( $primary_jpeg_url ); ?>"
                                type="image/jpeg">
	                  <?php endif; ?>
	                  <img src="<?php echo esc_url(wp_get_attachment_image_url($thumbnail_id, 'medium')); ?>" 
	                       alt="<?php echo esc_attr(get_the_title($args['id'])); ?>" 
	                       loading="lazy" 
	                       width="360" 
	                       height="240">
                  </picture>
	            <?php else: ?>
		            <img src="<?php echo esc_url(wp_get_attachment_image_url(6243, 'medium')); ?>" 
		                 alt="Default listing image" 
		                 loading="lazy" 
		                 width="360" 
		                 height="240">
	            <?php endif; ?>
                <div class="image-tags">
                    <?php if (get_field('matterport', $args['id'])): ?>
                        <div class="tour-btn">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M1.01728 9.30594L5.4903 11.8884C5.61736 11.9615 5.76138 12 5.90798 12C6.05457 12 6.19859 11.9615 6.32565 11.8884L10.7987 9.30591C10.9255 9.23241 11.0309 9.12691 11.1042 8.99997C11.1774 8.87302 11.2161 8.72906 11.2164 8.58248V3.41751C11.2162 3.27092 11.1775 3.12696 11.1042 3.00001C11.0309 2.87307 10.9255 2.76757 10.7987 2.69407L6.32567 0.111569C6.19861 0.0384712 6.05459 0 5.908 0C5.76141 0 5.61739 0.0384712 5.49033 0.111569L1.01728 2.69407C0.890453 2.76757 0.78513 2.87306 0.711837 3.00001C0.638544 3.12696 0.599846 3.27092 0.599609 3.41751V8.58251C0.599846 8.72909 0.638544 8.87305 0.711837 9C0.78513 9.12695 0.890453 9.23244 1.01728 9.30594ZM6.32565 1.07616L10.381 3.41751V8.04788L6.32565 5.75572V1.07616ZM5.90664 6.47842L10.0091 8.79724L5.90808 11.1649L1.80554 8.79646L5.90664 6.47842ZM1.43496 3.41751L5.4903 1.07616V5.7541L1.43496 8.04634V3.41751Z"
                                    fill="white" />
                            </svg>
                            <span>3D</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
                <div class="bottom">
                <?php
                if (!array_key_exists('heading', $args)): ?>
                    <h2 class="title"><?php echo esc_html(get_the_title($args['id'])) ?></h2>
                <?php else:
                    switch ($args['heading']):
                        case 'h3': ?>
                            <h3 class="title"><?php echo esc_html(get_the_title($args['id'])); ?></h3>
                            <?php break;
                        case 'h4': ?>
                            <h4 class="title"><?php echo esc_html(get_the_title($args['id'])) ?></h4>
                            <?php break;
                        default: ?>
                            <h2 class="title"><?php echo esc_html(get_the_title($args['id'])) ?></h2>
                    <?php endswitch; ?>
                <?php endif; ?>
                <?php
                  if (!array_key_exists('building', $args)):
                      $listing_main_location  = MetroManhattanHelpers::listing_parent_term( $args['id'], 'location' );
                      $listing_child_location = MetroManhattanHelpers::listing_child_neighborhood( $args['id'], $listing_main_location );
                      if ( $listing_main_location || $listing_child_location ): ?>
                          <ul class="tags">
                            <?php if ( $listing_main_location ):
                              $string = ( empty( $args['class'] ) || $args['class'] !== 'horizontal' ) ? $listing_main_location->name : str_replace( 'Manhattan', '', $listing_main_location->name );
                              ?>
                                <li><?php echo esc_html( $string ) ?></li>
                            <?php endif; ?>
                            <?php if ( $listing_child_location ): ?>
                                <li><?php echo esc_html( $listing_child_location->name ) ?></li>
                            <?php endif; ?>
                          </ul>
                      <?php endif; ?>
                  <?php endif; ?>
                <?php if (!array_key_exists('building', $args)):
                    $listing_monthly_rent = number_format(floatval(get_field('monthly_rent', $args['id'])));
                    $listing_call_request = get_field('call_request', $args['id']);
                    if ($listing_monthly_rent && !$listing_call_request): ?>
                        <p class="price"> $<?php echo esc_html($listing_monthly_rent) ?>/month</p>
                    <?php else: ?>
                        <p class="price">Call for pricing</p>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!array_key_exists('building', $args)):
                    $listing_size = get_field('square_feet', $args['id']);
                    $listing_lease_type = get_field('lease_type', $args['id']);
                    $listing_main_type = current(wp_get_object_terms($args['id'], 'listing-type', ['orderby' => 'parent', 'parent' => 0]));
                    if ($listing_size || $listing_lease_type): ?>
                        <ul class="size-sublease">
                            <?php if ($listing_size): ?>
                                <li>
                                    <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M15.0566 0H0.792453C0.582356 0.000244685 0.380935 0.0838135 0.232374 0.232374C0.0838136 0.380935 0.000244685 0.582356 0 0.792453V13.2075C0.000244685 13.4176 0.0838136 13.6191 0.232374 13.7676C0.380935 13.9162 0.582356 13.9998 0.792453 14H15.0566C15.2667 13.9998 15.4681 13.9162 15.6167 13.7676C15.7652 13.6191 15.8488 13.4176 15.8491 13.2075V0.792453C15.8488 0.582356 15.7652 0.380935 15.6167 0.232374C15.4681 0.0838135 15.2667 0.000244685 15.0566 0ZM14.7925 1.0566V3.16981H1.0566V1.0566H14.7925ZM1.0566 4.22641H5.56025V12.9434H1.0566V4.22641ZM6.61685 12.9434V4.22641H14.7925V12.9434H6.61685Z"
                                            fill="#AFAFAF" />
                                    </svg>
                                    <span><?php echo esc_html(number_format($listing_size)) ?> SF</span>
                                </li>
                            <?php endif; ?>
                            <?php if ($listing_lease_type): ?>
                                <li>
                                    <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clip-path="url(#clip0_1631_14467)">
                                            <path
                                                d="M3.29395 10.7998C3.29395 11.3752 3.76015 11.8428 4.33695 11.8428C4.91235 11.8428 5.37995 11.3766 5.37995 10.7998C5.37995 10.2244 4.91375 9.75684 4.33695 9.75684C3.76155 9.75824 3.29395 10.2244 3.29395 10.7998Z"
                                                fill="#AFAFAF" />
                                            <path
                                                d="M11.1421 9.18694V12.2137C11.1421 12.6687 10.7739 13.0369 10.3189 13.0369H2.63566C2.18066 13.0369 1.81246 12.6687 1.81246 12.2137V1.78654C1.81246 1.33154 2.18066 0.963337 2.63566 0.963337H10.3189C10.7739 0.963337 11.1421 1.33154 11.1421 1.78654V4.67474L11.9653 3.50294V1.23774C11.9653 0.631537 11.4739 0.140137 10.8677 0.140137H2.08686C1.48066 0.140137 0.989258 0.631537 0.989258 1.23774V12.7625C0.989258 13.3687 1.48066 13.8601 2.08686 13.8601H10.8677C11.4739 13.8601 11.9653 13.3687 11.9653 12.7625V8.01654L11.1421 9.18694Z"
                                                fill="#AFAFAF" />
                                            <path d="M2.91016 2.85742H10.0446V3.51542H2.91016V2.85742Z" fill="#AFAFAF" />
                                            <path d="M2.91016 6.97196H9.52656L9.98996 6.31396H2.91016V6.97196Z" fill="#AFAFAF" />
                                            <path d="M2.91016 4.58496H10.0446V5.24296H2.91016V4.58496Z" fill="#AFAFAF" />
                                            <path
                                                d="M14.3105 3.99428L14.6213 3.55048C14.7669 3.34188 14.7291 3.06328 14.5219 2.91768L13.7715 2.39268C13.5629 2.24708 13.2689 2.29328 13.1233 2.50188L12.8125 2.94568L14.3105 3.99428Z"
                                                fill="#AFAFAF" />
                                            <path d="M7.99486 9.81836L7.98926 11.4312L9.50406 10.874L7.99486 9.81836Z"
                                                fill="#AFAFAF" />
                                            <path
                                                d="M12.6861 3.12207L8.10254 9.66567L9.60194 10.7157L14.1855 4.17207L12.6861 3.12207ZM9.24074 9.31427L9.11474 9.22607L12.6343 4.20287L12.7603 4.29107L9.24074 9.31427ZM9.69994 9.62647L9.57814 9.54107L13.0893 4.52907L13.2111 4.61447L9.69994 9.62647Z"
                                                fill="#AFAFAF" />
                                        </g>
                                        <defs>
                                            <clipPath>
                                                <rect width="14" height="14" fill="white" transform="translate(0.849609)" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                    <span><?php echo esc_html($listing_lease_type) ?></span>
                                </li>
                            <?php endif; ?>
                            <?php if (array_key_exists('map_card', $args) && $listing_main_type): ?>
                                <li class="type">
                                    <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M6.1933 1.16406H9.50494C9.81244 1.16405 10.0777 1.16404 10.2962 1.1819C10.5268 1.20074 10.7562 1.24234 10.9769 1.3548C11.3062 1.52258 11.5739 1.7903 11.7417 2.11958C11.8542 2.3403 11.8958 2.56968 11.9146 2.8003C11.9325 3.0188 11.9325 3.28408 11.9325 3.59159V11.6641H12.5158C12.838 11.6641 13.0991 11.9252 13.0991 12.2474C13.0991 12.5696 12.838 12.8307 12.5158 12.8307H3.18245C2.86029 12.8307 2.59912 12.5696 2.59912 12.2474C2.59912 11.9252 2.86029 11.6641 3.18245 11.6641H3.76579L3.76579 3.59158C3.76578 3.28408 3.76577 3.0188 3.78362 2.8003C3.80246 2.56968 3.84406 2.3403 3.95653 2.11958C4.1243 1.7903 4.39202 1.52258 4.7213 1.3548C4.94203 1.24234 5.1714 1.20074 5.40202 1.1819C5.62052 1.16404 5.8858 1.16405 6.1933 1.16406ZM4.93245 11.6641H10.7658V3.61406C10.7658 3.27774 10.7653 3.06058 10.7518 2.8953C10.7389 2.73683 10.7169 2.67808 10.7022 2.64924C10.6463 2.53947 10.557 2.45024 10.4473 2.39431C10.4184 2.37961 10.3597 2.35764 10.2012 2.34469C10.0359 2.33118 9.81878 2.33073 9.48245 2.33073H6.21579C5.87947 2.33073 5.66231 2.33118 5.49703 2.34469C5.33856 2.35764 5.2798 2.37961 5.25096 2.39431C5.1412 2.45024 5.05196 2.53947 4.99603 2.64924C4.98134 2.67808 4.95936 2.73683 4.94641 2.8953C4.93291 3.06058 4.93245 3.27774 4.93245 3.61406V11.6641ZM5.80745 4.08073C5.80745 3.75856 6.06862 3.4974 6.39079 3.4974H9.30745C9.62962 3.4974 9.89079 3.75856 9.89079 4.08073C9.89079 4.4029 9.62962 4.66406 9.30745 4.66406H6.39079C6.06862 4.66406 5.80745 4.4029 5.80745 4.08073ZM5.80745 6.41406C5.80745 6.0919 6.06862 5.83073 6.39079 5.83073H9.30745C9.62962 5.83073 9.89079 6.0919 9.89079 6.41406C9.89079 6.73623 9.62962 6.9974 9.30745 6.9974H6.39079C6.06862 6.9974 5.80745 6.73623 5.80745 6.41406ZM5.80745 8.7474C5.80745 8.42523 6.06862 8.16406 6.39079 8.16406H9.30745C9.62962 8.16406 9.89079 8.42523 9.89079 8.7474C9.89079 9.06956 9.62962 9.33073 9.30745 9.33073H6.39079C6.06862 9.33073 5.80745 9.06956 5.80745 8.7474Z"
                                            fill="#4A4D51" />
                                    </svg>
                                      <?php
                                        $primary_listing_type_id = get_post_meta( $args['id'], 'primary_listing_type', true );
                                        if ( $primary_listing_type_id ) {
                                          $primary_term      = get_term( $primary_listing_type_id, 'listing-type' );
                                          $listing_type_text = ( ! empty( get_field( 'short_title', 'term_' . $primary_term->term_id ) ) )
                                            ? get_field( 'short_title', 'term_' . $primary_term->term_id )
                                            : strtok( $primary_term->name, ' ' );
                                        } else {
                                          $listing_type_text = ( ! empty( get_field( 'short_title', 'term_' . $listing_main_type->term_id ) ) )
                                            ? get_field( 'short_title', 'term_' . $listing_main_type->term_id )
                                            : strtok( $listing_main_type->name, ' ' );
                                        }
                                      ?>
                                    <span><?php echo esc_html($listing_type_text); ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </a>
</div>
