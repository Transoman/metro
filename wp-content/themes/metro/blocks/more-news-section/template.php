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
$class_name = 'more-posts';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}
$title = get_field('title');
$mode = get_field('mode');
$category = get_field('choose_category');
$specific_articles = get_field('specific_articles');
$more_from_metro_manhattan = MetroManhattanHelpers::get_posts_by_mode($mode, ['category' => $category, 'post_type' => 'post', 'numberposts' => 3, 'include' => $specific_articles]);
$background = get_field('background');

$class_name .= ' '. $background;
?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <?php if ($title): ?>
                <h2><?php echo $title ?></h2>
            <?php endif; ?>
            <?php if ($more_from_metro_manhattan): ?>
                <div class="posts">
                    <?php foreach ($more_from_metro_manhattan as $post):
                        get_template_part('templates/parts/blog', 'post', ['id' => $post, 'author' => true, 'date' => true, 'type_of_articles' => 'more-new-section']);
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>