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
$class_name = 'resources-section mm-section';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');
$button = get_field('button');
$background = get_field('background');
$range = get_field('range');
$resources = get_field('resources');
$helpers = new MetroManhattanHelpers();

$class_name .= ' ' . $background;
?>

<section <?php echo $anchor; ?> data-target="swiper" data-swiper-slides="<?php echo $range ?>" data-swiper-tablet-slides="2" class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <div class="heading">
                <?php if ($title) : ?>
                    <h2><?php echo $title ?></h2>
                <?php endif; ?>
                <div class="heading-part">
                    <?php if (sizeof($resources) > $range) : ?>
                        <div class="controllers">
                            <button aria-label="Previous slide" data-target="swiper_left" class="btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                    <path d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z" fill="#0961AF" />
                                </svg>
                            </button>
                            <button aria-label="Next slide" data-target="swiper_right" class="btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                    <path d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z" fill="#0961AF" />
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <?php if ($button) :
                        $link_url = $button['url'];
                        $link_title = $button['title'];
                        $link_target = $button['target'] ? $button['target'] : '_self';
                    ?>
                        <a aria-label="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (have_rows('resources')) : ?>
                <div class="swiper blocks">
                    <div class="swiper-wrapper">
                        <?php while (have_rows('resources')) : the_row();
                            $icon = get_sub_field('icon');
                            $title = get_sub_field('title');
                            $text = get_sub_field('text');
                            $link = get_sub_field('link');
                        ?>
                            <div class="swiper-slide block">
                                <?php if ($link) : ?>
                                    <a aria-label="<?php echo $title ?>" href="<?php echo $link ?>" class="link">
                                        <div class="inner">
                                            <?php if ($icon) : ?>
                                                <div class="icon">
                                                    <?php echo wp_get_attachment_image($icon['id'], 'full', '', ['loading' => 'lazy']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="text">
                                                <?php if ($title) : ?>
                                                    <h3 class="title"><?php echo $title ?></h3>
                                                <?php endif; ?>
                                                <?php if ($text) : ?>
                                                    <p><?php echo $text ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>