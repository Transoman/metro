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
$class_name = 'all-buildings';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$post_type = get_field('choose_post_type');
$articles = get_field('articles');
$step = (get_field('step')) ? get_field('step') : 2;
$is_random = get_field('random');

$list = get_posts(['post_type' => $post_type, 'numberposts' => -1]);
?>

<div <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
	<div class="container">
        <?php echo MetroManhattanHelpers::list_with_articles($list, $articles, $step, $is_random); ?>
	</div>
</div>