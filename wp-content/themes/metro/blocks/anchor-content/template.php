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
$class_name = 'anchor-block';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}
$title = get_field('title');
$link = get_field('link');
$anchor = get_field('anchor');
$map = get_field('map');
$image = get_field('image');
$allowed_blocks = ['core/image', 'core/paragraph', 'core/heading', 'core/list', 'acf/banner', 'acf/blocks-with-hover', 'acf/single-youtube-video', 'acf/building-blocks'];


?>

<div id="<?php echo $anchor ?>" class="<?php echo esc_attr($class_name); ?>">
    <?php if ($title || $link) : ?>
        <div class="heading">
            <?php if ($title) : ?>
                <h2><?php echo $title ?></h2>
            <?php endif; ?>
            <?php if ($link) :
                $link_url = $link['url'];
                $link_title = $link['title'];
                $link_target = $link['target'] ? $link['target'] : '_self';
            ?>
                <a aria-label="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($map || $image) : ?>
        <div class="image-map">
            <?php if ($image) : ?>
                <div class="image">
                    <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => 'lazy']); ?>
                </div>
            <?php endif; ?>
            <?php if ($map) : ?>
                <div class="map-wrapper">
                    <?php echo wp_get_attachment_image($map['id'], 'full', '', ['loading' => 'lazy']) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="content">
        <?php echo '<InnerBlocks allowedBlocks="' . esc_attr(wp_json_encode($allowed_blocks)) . '" />'; ?>
    </div>
</div>