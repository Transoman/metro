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
$class_name = 'featured-listings mm-section';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');
$button = get_field('button');
$background = get_field('background');
$template = get_field('choose_template');
$border_bottom = get_field('border_bottom');
$number = ($template === 'standard') ? 3 : 4;
$numberposts = get_field('numberposts');
$location = get_field('choose_location');
$mode = get_field('show_listings_by');

$featured_listings = MetroManhattanHelpers::get_posts_by_mode($mode, ['post_type' => 'listings', 'numberposts' => $numberposts, 'location' => $location]);
$class_name = ($border_bottom) ? $class_name . ' border-bottom' : $class_name;
$class_name .= ' ' . $background;
$class_name .= ' ' . $template;
?>
<section <?php echo $anchor; ?> data-target="featured_listings" class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <div class="heading">
                <?php if ($title): ?>
                    <h2><?php echo $title ?></h2>
                <?php endif; ?>
                <div class="heading-part">
                    <?php if ($featured_listings && sizeof($featured_listings) > $number): ?>
                        <div class="controllers">
                            <button aria-label="Previous slide" data-target="swiper_left" class="btn swiper-button-disabled"
                                disabled="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24"
                                    fill="none">
                                    <path d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z"
                                        fill="#0961AF"></path>
                                </svg>
                            </button>
                            <button aria-label="Next slide" data-target="swiper_right" class="btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24"
                                    fill="none">
                                    <path d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z"
                                        fill="#0961AF"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <?php
                    if ($button):
                        $link_url = $button['url'];
                        $link_title = $button['title'];
                        $link_target = $button['target'] ? $button['target'] : '_self';
                        ?>
                        <a aria-label="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url($link_url); ?>"
                            target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($featured_listings): ?>
                <div class="swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($featured_listings as $listing): ?>
                            <div class="swiper-slide">
                                <?php
                                $args = [
                                    'id' => $listing,
                                    'heading' => 'h3',
                                    'class' => ($template == 'default') ? 'horizontal' : '',
                                    'favourites_template' => false
                                ];
                                if ($template != 'default') {
                                    $args['map_card'] = false;
                                }
                                get_template_part('templates/parts/listing', 'card', $args); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>