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
$class_name = 'page-heading';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = (get_field('title')) ? get_field('title') : 'Insert page title';
$is_favourite_page = get_field('is_favourite_page');
$current_user_id = wp_get_current_user()->ID;
$image = get_field('image');
$mobile_image = get_field('mobile_image');
$template = get_field('template');
$class_name = ($image) ? $class_name . ' with-image' : $class_name;
$class_name .= ' ' . $template;

?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <?php if (!empty($image)) : ?>
                <div class="image">
                    <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => 'eager', 'data-breakpoint' => 'desktop']) ?>
                    <?php echo wp_get_attachment_image($mobile_image['id'], 'full', '', ['loading' => 'eager', 'data-breakpoint' => 'mobile']) ?>
                </div>
            <?php endif; ?>
            <?php echo MetroManhattanHelpers::breadcrumbs(true) ?>
            <h1><?php echo $title ?></h1>
            <?php if ($is_favourite_page && is_user_logged_in()) : ?>
                <p>Saved Listings <span data-target="total">(<?php echo sizeof(MMFavourites::get_favourites($current_user_id)); ?>)</span></p>
            <?php endif; ?>
        </div>
    </div>
</section>