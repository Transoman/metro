<?php

/**
 * Base Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 *          This is either the post ID currently being displayed inside a query loop,
 *          or the post ID of the post hosting this block.
 * @param   array $context The context provided to the block by the post or its parent block.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Support custom "anchor" values.
$anchor = '';
if (!empty($block['anchor'])) {
    $anchor = 'id="' . esc_attr($block['anchor']) . '" ';
}

// Create class attribute allowing for custom "className" and "align" values.
$class_name = 'text-with-listing';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$numberposts = get_field('numberposts');
$location = get_field('choose_location');
$mode = get_field('show_listings_by');
$recommended_listings = MetroManhattanHelpers::get_posts_by_mode($mode, ['post_type' => 'listings', 'numberposts' => $numberposts, 'location' => $location]);
$text = get_field('text');

?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <div class="text">
                <?php echo $text ?>
            </div>
            <?php if ($recommended_listings): ?>
                <aside data-target="recommended_listings" class="sidebar">
                    <h3>Recommended Listings</h3>
                    <div class="swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($recommended_listings as $listing): ?>
                                <div class="swiper-slide">
                                    <?php get_template_part('templates/parts/listing', 'card', ['id' => $listing, 'heading' => 'h4', 'class' => 'horizontal sidebar-item', 'favourites_template' => false]); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>
            <?php endif; ?>
        </div>
    </div>
</section>