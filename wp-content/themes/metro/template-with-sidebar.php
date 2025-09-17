<?php
/* Template Name: Template with Sidebar */
get_header('search');


$choose_header = get_field('choose_header_of_page', get_the_ID());
$search_page = get_field('choose_search_page', 'option');

$hero_title = get_field('title_hero');
$hero_text = get_field('text_hero');
$show_breadcrumbs = get_field('breadbcrumbs');

$menu_title = get_field('title_sidebar');
$menu_type = get_field('select_type_of_side_menu');
$menu_first_item = get_field('first_menu_item_text');
$menu_mobile_placeholder = (get_field('mobile_placeholder')) ? get_field('mobile_placeholder') : 'View All';
$parent_page_id = get_field('select_parent_page');
$first_level_menu = get_field('first_level_menu');
$second_level_menu = get_field('second_level_menu');

$bottom_section = get_field('choose_section');
$mode = get_field('show_listings_by');
$location = get_field('choose_location');
$numberposts_featured_listings = get_field('numberposts_featured_listings');
// $featured_listings = ($bottom_section == 'recommended') ? MetroManhattanHelpers::get_posts_by_mode('nearby', ['post_type' => 'listings', 'numberposts' => 12, 'location' => $location]) : MetroManhattanHelpers::get_posts_by_mode($mode, ['post_type' => 'listings', 'numberposts' => $numberposts_featured_listings, 'location' => $location]);
$featured_listings = MetroManhattanHelpers::get_generated_featured_listings( get_the_ID() );
$button_featured_listings = get_field('button_featured-listings');


$posts_more_news = MetroManhattanHelpers::get_posts_by_mode('featured', ['post_type' => 'post']);
$background_more_news = get_field('background_more-news');

$map_image = get_field('map_image-with-map');
$hero_image = get_field('image_image-with-map');


$title_listings = get_field('title_listings');
$button_or_pagination = get_field('button_or_pagination');
$button_listings = get_field('button_listings');
$numberposts_listings = get_field('numberposts');
$listings_type = get_field('type_term');
$listings_location = get_field('location_term');
$filter_by = get_field('filter_listing_by');
$current_page = (get_query_var('paged') == 0) ? 1 : get_query_var('paged');
$offset = ($current_page * $numberposts_listings) - $numberposts_listings;
$result = ($filter_by == 'location') ? MetroManhattanHelpers::get_listings_by_taxonomy($filter_by, $listings_location, $offset, $numberposts_listings) : MetroManhattanHelpers::get_listings_by_taxonomy($filter_by, $listings_type, $offset, $numberposts_listings);
$pagination = MetroManhattanHelpers::get_pagination_of_search_result($result['total'], $current_page, $numberposts_listings, get_permalink(get_the_ID()));

$title_types_blocks = get_field('title_types-blocks');
$which_taxonomies = get_field('blocks_select_types-blocks');
$types_blocks = ($which_taxonomies === 'type') ? get_field('use_type_types-blocks') : get_field('neighborhoods_types-blocks');
$search_page = get_field('choose_search_page', 'option');
$search_page_link = get_permalink($search_page);

$prominent_buildings_location = get_field('choose_location_prominent_buildings');
$prominent_buildings = MetroManhattanHelpers::get_posts_by_mode('nearby', ['post_type' => 'buildings', 'numberposts' => 12, 'location' => $prominent_buildings_location]);
$button_prominent_buildings = get_field('button_prominent_buildings');
?>
<main class="template-with-sidebar">
    <?php
    get_template_part('templates/parts/notification', 'template');
    ?>
    <section class="page-hero full-bg">
        <div class="container">
            <div class="content">
                <?php if (!empty($show_breadcrumbs)) : ?>
                    <?php echo MetroManhattanHelpers::breadcrumbs(true) ?>
                <?php endif; ?>
                <?php if (!empty($hero_title)) : ?>
                    <h1><?php echo $hero_title ?></h1>
                <?php endif; ?>
                <?php if (!empty($hero_text)) : ?>
                    <p><?php echo $hero_text ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <div class="mobile-sidebar <?php echo ($menu_type == 'anchor') ? 'scroll' : '' ?>">
        <div class="container">
            <div class="content">
                <?php if ($menu_type == 'default') : ?>
                    <div data-target="mobile_sidebar" class="mobile-selector">
                        <div class="placeholder">
                            <span><?php echo $menu_mobile_placeholder ?></span>
                            <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 1.5L7 7.5L13 1.5" stroke="var(--mm-navy-color)" stroke-width="2" />
                            </svg>
                        </div>
                        <div class="list">
                            <?php echo MetroManhattanHelpers::sidebar(get_the_ID(), $parent_page_id, $menu_first_item) ?>
                        </div>
                    </div>
                <?php elseif ($menu_type == 'anchor') : ?>
                    <?php if (!empty($menu_title)) : ?>
                        <h4><?php echo $menu_title ?></h4>
                    <?php endif; ?>
                    <?php if (have_rows('menu_achor')) : ?>
                        <ul data-target="scroll_menu" class="menu">
                            <?php while (have_rows('menu_achor')) : the_row();
                                $text = get_sub_field('text');
                                $link = get_sub_field('anchor');
                                if ($text && $link) : ?>
                                    <li><a aria-label="<?php echo $text ?>" href="#<?php echo $link ?>"><span><?php echo $text ?></span></a></li>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>
                <?php else : ?>
                    <div data-target="mobile_sidebar" class="mobile-selector">
                        <div class="placeholder">
                            <span><?php echo $menu_mobile_placeholder ?></span>
                            <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 1.5L7 7.5L13 1.5" stroke="var(--mm-navy-color)" stroke-width="2" />
                            </svg>
                        </div>
                        <?php if (!empty($first_level_menu) || !empty($second_level_menu)) : ?>
                            <div class="list">
                                <?php if (!empty($first_level_menu)) : ?>
                                    <ul data-target="levels_menu">
                                        <?php foreach ($first_level_menu as $menu_item) :
                                            $link = $menu_item['page'];
                                            if (!empty($link)) :
                                                $link_url = $link['url'];
                                                $link_title = $link['title'];
                                                $link_target = ($link['target']) ? $link['target'] : "_self";
                                                $class_name = (get_permalink(get_the_ID()) === $link_url) ? 'active' : 'no-active' ?>
                                                <li><a class="<?php echo esc_attr($class_name) ?>" target="<?php echo esc_attr($link_target) ?>" title="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url($link_url) ?>"><span><?php echo esc_html($link_title) ?></span></a></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if (!empty($second_level_menu)) : ?>
                                            <li><button data-target="more_button" aria-label="More" type="button">
                                                    <span>More</span>
                                                    <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M1 1L7 7L13 1" stroke="var(--mm-navy-color)" stroke-width="2"></path>
                                                    </svg>
                                                </button>
                                                <ul class="second-level" data-target="second_level">
                                                    <?php foreach ($second_level_menu as $menu_item) :
                                                        $link = $menu_item['page'];
                                                        if (!empty($link)) :
                                                            $link_url = $link['url'];
                                                            $link_title = $link['title'];
                                                            $link_target = ($link['target']) ? $link['target'] : "_self";
                                                            $class_name = (get_permalink(get_the_ID()) === $link_url) ? 'active' : 'no-active' ?>
                                                            <li><a class="<?php echo esc_attr($class_name) ?>" target="<?php echo esc_attr($link_target) ?>" title="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url($link_url) ?>"><span><?php echo esc_html($link_title) ?></span></a></li>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if (!empty($hero_image) || !empty($map_image)) : ?>
        <section class="image-map section">
            <div class="container">
                <div class="content">
                    <?php if (!empty($hero_image)) : ?>
                        <div class="image">
                            <?php echo wp_get_attachment_image($hero_image['id'], 'full', '', ['loading' => 'eager']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($map_image)) : ?>
                        <div class="map-wrapper">
                            <?php echo wp_get_attachment_image($map_image['id'], 'full', '', ['loading' => 'eager']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <?php if (!empty($title_listings) || !empty($result['listings'])) : ?>
        <section data-target="listings_with_pagination" class="listings-with-pagination <?php echo (!$button_or_pagination) ? 'pagination' : 'button' ?>">
            <div class="container">
                <div class="content">
                    <?php if (!empty($title_listings)) : ?>
                        <h2><?php echo $title_listings ?></h2>
                    <?php endif; ?>
                    <div data-target="result_wrapper" class="blocks">
                        <?php foreach ($result['listings'] as $listing) :
                            get_template_part('/templates/parts/listing', 'card', ['id' => $listing->ID, 'heading' => 'h3', 'map_card' => false, 'favourites_template' => false]);
                        endforeach; ?>
                    </div>
                    <?php if (!$button_or_pagination) : ?>
                        <div class="pagination-wrapper <?php echo ($result['total'] > $numberposts_listings) ? '' : 'hide' ?>">
                            <?php if ($result['total'] > $numberposts_listings) : ?>
                                <form data-target="form" action="#">
                                    <input name="current_page" value="<?php echo get_permalink(get_the_ID()) ?>" type="hidden">
                                    <input name="action" value="pagination_for_listings" type="hidden">
                                    <input name="taxonomy" value="<?php echo $filter_by ?>" type="hidden">
                                    <input name="term" type="hidden" value="<?php echo ($filter_by === 'location') ? $listings_location : $listings_type ?>">
                                    <input name="numberposts" value="<?php echo $numberposts_listings ?>" type="hidden">
                                    <input name="page" value="<?php echo $current_page ?>" type="hidden">
                                </form>
                                <div data-target="pagination_wrapper" class="pagination <?php echo ($result['total'] > $numberposts_listings) ? '' : 'hide' ?>">
                                    <?php echo $pagination ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <?php
                        if (!empty($button_listings)) :
                            $link_url = $button_listings['url'];
                            $link_title = $button_listings['title'];
                            $link_target = $button_listings['target'] ? $button_listings['target'] : '_self';
                        ?>
                            <div class="button-wrapper">
                                <a aria-label="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <section class="main-content <?php echo (!$button_or_pagination) ? 'pagination' : 'button' ?> <?php echo ($title_types_blocks && $types_blocks) ? 'no-border' : '' ?>">
        <div class="container">
            <div class="content">
                <div class="wrapper">
                    <?php the_content(); ?>
                </div>
                <aside class="sidebar <?php echo ($menu_type != 'default') ? 'anchor' : '' ?>">
                    <?php if (!empty($menu_title)) : ?>
                        <h4><?php echo $menu_title ?></h4>
                    <?php endif; ?>
                    <?php if ($menu_type == 'default') : ?>
                        <?php echo MetroManhattanHelpers::sidebar(get_the_ID(), $parent_page_id, $menu_first_item) ?>
                    <?php elseif ($menu_type == 'anchor') : ?>
                        <?php if (have_rows('menu_achor')) : ?>
                            <ul data-target="scroll_menu" class="menu">
                                <?php while (have_rows('menu_achor')) : the_row();
                                    $text = get_sub_field('text');
                                    $link = get_sub_field('anchor');
                                    if ($text && $link) : ?>
                                        <li><a aria-label="<?php echo $text ?>" href="#<?php echo $link ?>"><span><?php echo $text ?></span></a></li>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </ul>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php if (!empty($first_level_menu) || !empty($second_level_menu)) : ?>
                            <?php if (!empty($first_level_menu)) : ?>
                                <ul data-target="levels_menu">
                                    <?php foreach ($first_level_menu as $menu_item) :
                                        $link = $menu_item['page'];
                                        if (!empty($link)) :
                                            $link_url = $link['url'];
                                            $link_title = $link['title'];
                                            $link_target = ($link['target']) ? $link['target'] : "_self";
                                            $class_name = (get_permalink(get_the_ID()) === $link_url) ? 'active' : 'no-active' ?>
                                            <li><a class="<?php echo esc_attr($class_name) ?>" target="<?php echo esc_attr($link_target) ?>" title="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url($link_url) ?>"><span><?php echo esc_html($link_title) ?></span></a></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if (!empty($second_level_menu)) : ?>
                                        <li><button data-target="more_button" aria-label="More" type="button">
                                                <span>More</span>
                                                <svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M1 1L7 7L13 1" stroke="var(--mm-navy-color)" stroke-width="2"></path>
                                                </svg>
                                            </button>
                                            <ul class="second-level" data-target="second_level">
                                                <?php foreach ($second_level_menu as $menu_item) :
                                                    $link = $menu_item['page'];
                                                    if (!empty($link)) :
                                                        $link_url = $link['url'];
                                                        $link_title = $link['title'];
                                                        $link_target = ($link['target']) ? $link['target'] : "_self";
                                                        $class_name = (get_permalink(get_the_ID()) === $link_url) ? 'active' : 'no-active' ?>
                                                        <li><a class="<?php echo esc_attr($class_name) ?>" target="<?php echo esc_attr($link_target) ?>" title="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url($link_url) ?>"><span><?php echo esc_html($link_title) ?></span></a></li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </section>
    <?php if (!empty($prominent_buildings)) : ?>
        <section data-target="prominent_buildings" class="prominent-buildings mm-section">
            <div class="container">
                <div class="content">
                    <div class="heading">
                        <?php if(!empty($term = get_term($prominent_buildings_location))): ?>
                            <h2>Prominent Buildings in <?php echo $term->name ?></h2>
                        <?php else: ?>
                            <h2>Prominent Buildings in this area</h2>
                        <?php endif; ?>
                        <div class="heading-part">
                            <?php if (isset($prominent_buildings) && sizeof($prominent_buildings) > 3) : ?>
                                <div class="controllers">
                                    <button aria-label="Previous slide" data-target="swiper_left" class="btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                            <path d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z" fill="#0961AF"></path>
                                        </svg>
                                    </button>
                                    <button aria-label="Next slide" data-target="swiper_right" class="btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                            <path d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z" fill="#0961AF"></path>
                                        </svg>
                                    </button>
                                </div>
                            <?php endif; ?>
                            <?php
                            if (!empty($button_prominent_buildings)) :
                                $link_url = $button_prominent_buildings['url'];
                                $link_title = $button_prominent_buildings['title'];
                                $link_target = $button_prominent_buildings['target'] ? $button_prominent_buildings['target'] : '_self';
                            ?>
                                <a aria-label="<?php echo $link_title ?>" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($prominent_buildings)) : ?>
                        <div class="swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($prominent_buildings as $building) : ?>
                                    <div class="swiper-slide">
                                        <div class="block">
                                            <?php
                                            $image = get_post_thumbnail_id($building);
                                            $title = get_the_title($building);
                                            $link = get_permalink($building);
                                            ?>
                                            <?php if (!empty($link)) : ?>
                                                <a title="<?php echo esc_attr($title) ?>" href="<?php echo esc_url($link) ?>">
                                                    <?php if (!empty($image)) : ?>
                                                        <div class="image">
                                                            <?php echo wp_get_attachment_image($image, 'full', '', ['loading' => 'lazy']); ?>
                                                        </div>
                                                    <?php else : ?>
                                                        <div class="image">
                                                            <img loading="lazy" width="360" height="240" src="<?php echo get_template_directory_uri() ?>/assets/images/image-placeholder.jpeg" alt="">
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($title)) : ?>
                                                        <div class="title">
                                                            <h3><?php echo esc_html($title) ?></h3>
                                                        </div>
                                                    <?php endif; ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <?php if (!empty($types_blocks) || !empty($title_types_blocks)) : ?>
        <section class="types-blocks columns-3">
            <div class="container">
                <?php if (!empty($title_types_blocks)) : ?>
                    <h2 class="section-title">
                        <?php echo $title_types_blocks ?>
                    </h2>
                <?php endif; ?>
                <?php if (!empty($types_blocks)) : ?>
                    <div class="blocks">
                        <?php foreach ($types_blocks as $block) :
                            $key = ($which_taxonomies === 'type') ? 'listing-type_' . $block : 'location_' . $block;
                            $image = ($which_taxonomies === 'type') ? get_field('image', $key) : get_field('images', $key)[0];
                            $title = get_term($block)->name;
                            $page_text = ($which_taxonomies === 'type') ? get_field('page_of_type', $key) : get_field('page_id', $key);
                            $text = preg_match_all('/^.*?(<p[^>]*>.*?<\/p>).*$/m', get_post_field('post_content', $page_text), $matches, PREG_UNMATCHED_AS_NULL);
                            $text = ($page_text) ? wp_strip_all_tags(join(' ', $matches[0])) : '';
                            $boolean = (strlen($text) > 105);
                            $text = ($boolean) ? substr($text, 0, 105) . '...' : $text
                        ?>
                            <div data-target="type_block" class="block">
                                <div class="inner">
                                    <?php if (!empty($image)) : ?>
                                        <div class="top">
                                            <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => 'lazy']); ?>
                                        </div>
                                    <?php else : ?>
                                        <div class="top">
                                            <?php echo wp_get_attachment_image(37633, 'full', '', ['loading' => 'lazy']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="bottom">
                                        <?php if (!empty($title)) : ?>
                                            <h3 class="title">
                                                <?php echo $title ?>
                                            </h3>
                                        <?php endif; ?>
                                        <div class="text">
                                            <?php if (!empty($text)) : ?>
                                                <p><?php echo $text ?></p>
						<?php if (!empty($boolean) && !empty($page_text)) : ?>
                                                    <a href="<?php echo get_permalink( $page_text ) ?>"
                                                       aria-label="Learn more about <?php echo esc_attr( $title ) ?>">Learn
                                                        more about <?php echo esc_html( $title ) ?>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <div class="button">
                                                <form method="post" action="<?php echo $search_page_link ?>">
                                                    <input type="hidden" name="filter[<?php echo ($which_taxonomies === 'type') ? 'uses' : 'locations' ?>][<?php echo $block ?>]" value="<?php echo $title ?>">
                                                    <button aria-label="See listings" type="submit">See Listings
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
    <?php if (!empty($featured_listings)) : ?>
        <section data-target="featured_listings" data-grid-style="<?php echo ($bottom_section != 'recommended') ? 'true' : 'false' ?>" class="featured-listings mm-section">
            <div class="container">
                <div class="content">
                    <div class="heading">
                        <?php if ($bottom_section == 'featured') : ?>
                            <h2>Featured Listings</h2>
                        <?php else : ?>
                            <h2>Recommended Listings</h2>
                        <?php endif; ?>
                        <div class="heading-part">
                            <?php if (isset($featured_listings) && sizeof($featured_listings) > 3) : ?>
                                <div class="controllers">
                                    <button aria-label="Previous slide" data-target="swiper_left" class="btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                            <path d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z" fill="#0961AF"></path>
                                        </svg>
                                    </button>
                                    <button aria-label="Next slide" data-target="swiper_right" class="btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                            <path d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z" fill="#0961AF"></path>
                                        </svg>
                                    </button>
                                </div>
                            <?php endif; ?>
                            <?php
                            if (!empty($button_featured_listings)) :
                                $link_url = $button_featured_listings['url'];
                                $link_title = $button_featured_listings['title'];
                                $link_target = $button_featured_listings['target'] ? $button_featured_listings['target'] : '_self';
                            ?>
                                <a aria-label="<?php echo $link_title ?>" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($featured_listings)) : ?>
                        <div class="swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($featured_listings as $listing) : ?>
                                    <div class="swiper-slide">
                                        <?php
                                        $args = [
                                            'id' => $listing,
                                            'heading' => 'h3',
                                            'class' => ($bottom_section == 'recommended') ? 'basic' : 'horizontal',
                                            'favourites_template' => false
                                        ];
                                        get_template_part('templates/parts/listing', 'card', $args); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <?php if (!empty($posts_more_news)) : ?>
        <section data-target="more_posts" class="more-posts full-bg">
            <div class="container">
                <div class="content">
                    <h2>More from Metro Manhattan</h2>
                    <?php if (!empty($posts_more_news)) : ?>
                        <div class="posts swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($posts_more_news as $post) : ?>
                                    <div class="swiper-slide">
                                        <?php get_template_part('templates/parts/blog', 'post', ['id' => $post, 'author' => true, 'date' => true, 'type_of_articles' => 'all']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>


<?php get_footer();
