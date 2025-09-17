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
$class_name = 'favorites-list';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$current_user_id = wp_get_current_user()->ID;
$numberposts = (get_field('numberposts')) ? get_field('numberposts') : 6;
$current_page = (get_query_var('paged') == 0 || is_admin()) ? 1 : (int) get_query_var('paged');
$offset = ($current_page * $numberposts) - $numberposts;
$favourites = MMFavourites::get_paginated_favourites($current_user_id, $offset, $numberposts);
$total_favourites = (is_user_logged_in()) ? MMFavourites::get_favourites($current_user_id) : [];
$pagination = MetroManhattanHelpers::get_pagination_of_search_result(sizeof($total_favourites), $current_page, $numberposts, get_permalink(get_the_ID()));

$empty_title = get_field('empty_title');
$empty_text = get_field('empty_text');
$empty_image = get_field('empty_image');
$empty_button = get_field('empty_button');

?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <div class="listings">
                <div data-target="result_wrapper" class="blocks">
                    <?php foreach ($favourites as $favourite):
                        get_template_part('templates/parts/listing', 'card', ['id' => $favourite->ID, 'favourites_template' => true, 'map_card' => false, 'heading' => 'h2']);
                    endforeach; ?>
                </div>
                <div class="pagination-wrapper">
                    <form data-target="form" action="#">
                        <input name="current_page" value="<?php echo get_permalink(get_the_ID()) ?>" type="hidden">
                        <input name="action" value="pagination_for_favourites" type="hidden">
                        <input name="numberposts" value="<?php echo $numberposts ?>" type="hidden">
                        <input name="page" value="<?php echo $current_page ?>" type="hidden">
                    </form>
                    <?php if (sizeof($total_favourites) > $numberposts): ?>
                        <div data-target="pagination_wrapper" class="pagination">
                            <?php echo $pagination ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="empty <?php echo (sizeof($total_favourites) > 0) ? 'hide' : '' ?>">
                    <?php if ($empty_image): ?>
                        <?php echo wp_get_attachment_image($empty_image['id'], 'full', '', ['loading' => 'lazy']); ?>
                    <?php endif; ?>
                    <?php if ($empty_title): ?>
                        <h3><?php echo $empty_title ?></h3>
                    <?php endif; ?>
                    <?php if ($empty_text): ?>
                        <p><?php echo $empty_text ?></p>
                    <?php endif; ?>
                    <?php if ($empty_button):
                        $text = $empty_button['title'];
                        $url = $empty_button['url'];
                        $target = ($empty_button['target']) ? $empty_button['target'] : '_self';
                        ?>
                        <a aria-label="<?php echo esc_attr($text) ?>" target="<?php esc_attr($target); ?>"
                            href="<?php echo esc_url($url) ?>"><?php echo esc_html($text) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>