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
$class_name = 'browse-listings mm-section';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');
$button = get_field('button');
$taxonomy = get_field('select');
$blocks = ($taxonomy === 'type') ? get_field('types') : get_field('locations');
$background = get_field('background');
$range = get_field('range');
$search_page = get_field('choose_search_page', 'option');
$search_page_link = get_permalink($search_page);
$space = get_field('space');
$border = get_field('border');

$class_name = ($space) ? $class_name . ' more-space' : $class_name;
$class_name = ($border) ? $class_name . ' border' : $class_name;

?>

<section <?php echo $anchor; ?> data-target="swiper" data-swiper-slides="<?php echo $range ?>" data-swiper-tablet-slides="2" data-swiper-space-between="30" class="<?php echo esc_attr($class_name); ?> <?php echo $background ?>">
    <div class="container">
        <div class="content">
            <div class="heading">
                <?php if ($title) : ?>
                    <h2><?php echo $title ?></h2>
                <?php endif; ?>
                <div class="heading-part">
                    <?php if (is_array($blocks) && sizeof($blocks) > $range) : ?>
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
                    if ($button) :
                        $link_url = $button['url'];
                        $link_title = $button['title'];
                        $link_target = $button['target'] ? $button['target'] : '_self';
                    ?>
                        <a aria-label="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($blocks) : ?>
                <div class="blocks swiper">
                    <div class="swiper-wrapper">
                        <?php
                        foreach ($blocks as $block) :
                            $term = get_term($block['select_taxonomy']);
                            $description = term_description($term->term_id);
                            $image = ($taxonomy === 'type') ? get_field('image', $term->taxonomy . '_' . $term->term_id) : get_field('featured_image', $term->taxonomy . '_' . $term->term_id);
                            $optional_text = $block['alternative_title'];
                        ?>
                            <div class="swiper-slide block">
                                <form method="post" action="<?php echo $search_page_link ?>">
                                    <input name="filter[<?php echo ($taxonomy === 'type') ? 'uses' : 'locations' ?>][<?php echo $term->term_id ?>]" value="<?php echo $term->name ?>" type="hidden">
                                    <?php if ($image) : ?>
                                        <div class="image">
                                            <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => 'lazy']) ?>
                                        </div>
                                    <?php else : ?>
                                        <div class="image">
                                            <?php echo wp_get_attachment_image(37633, 'full', '', ['loading' => 'lazy']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($term)) : ?>
                                        <div class="text">
                                            <h3 class="title"><?php echo (!empty($optional_text)) ? $optional_text : $term->name ?></h3>
                                            <?php if ($description) : ?>
                                                <?php echo $description ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <button aria-label="<?php echo $term->name ?>" type="submit">
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>