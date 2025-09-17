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
$class_name = 'banner';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$banner_text = get_field('text');
$banner_button = get_field('button');
$search_page = get_field('choose_search_page', 'option');
$search_page_link = get_permalink($search_page);
$taxonomy = get_field('blocks_select');
$locations_parent = current(MetroManhattanHelpers::get_hierarchically_taxonomy('location'));
$term = ($taxonomy === 'type') ? get_field('use_type') : get_field('neighborhood');
$term = ($taxonomy === 'type' && empty($term) || $taxonomy === 'neighborhoods' && $term === $locations_parent->term_id) ? '-1' : $term;
$term_name = ($taxonomy === 'type' && $term === '-1' || $taxonomy === 'neighborhoods' && $term === '-1') ? 'true' : get_term($term)->name;

?>
<div <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="text">
        <h3><?php echo $banner_text ?></h3>
        <?php
        if ($banner_button): ?>
            <form method="post" action="<?php echo $search_page_link ?>">
                <input type="hidden"
                       name="filter[<?php echo ($taxonomy === 'type') ? 'uses' : 'locations' ?>][<?php echo $term ?>]"
                       value="<?php echo $term_name ?>"
                >
                <button aria-label="<?php echo $banner_button ?>" type="submit"><?php echo $banner_button ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>