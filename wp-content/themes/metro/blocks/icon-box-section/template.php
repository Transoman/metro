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
$class_name = 'icon-box';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}
$title = get_field('title');
$background = get_field('background');
$title_alignment = get_field('title_alignment');
$box_alignment = get_field('box_alignment');
$height = get_field('height');
$helpers = new MetroManhattanHelpers();

$class_name .= ' ' . $background;
?>
<section data-target="icon-box" class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <?php if ($title) : ?>
                <h2 data-align="<?php echo $title_alignment; ?>"><?php echo $title ?></h2>
            <?php endif; ?>

            <?php if (have_rows('icon_box_list')) : ?>
                <div class="swiper">
                    <div class="swiper-wrapper" data-height="<?php echo $height ? 'true' : 'false' ?>" data-box_alignment="<?php echo
                                                                                                                            $box_alignment ?>">
                        <?php while (have_rows('icon_box_list')) :
                            the_row();
                            $icon = get_sub_field('icon');
                            $title = get_sub_field('title');
                            $text = get_sub_field('text');
                            $link = get_sub_field('link');
                        ?>
                            <div class="swiper-slide">
                                <?php if ($link) : ?>
                                    <a class="icon-box__item" aria-label="<?php echo $title ?>" href="<?php echo $link['url'] ?>">
                                    <?php else : ?>
                                        <div class="icon-box__item">
                                        <?php endif; ?>

                                        <div class="icon-box__icon">
                                            <?php echo wp_get_attachment_image($icon['id'], 'full', '', ['loading' => 'lazy']) ?>
                                        </div>
                                        <div class="icon-box__descr">
                                            <?php if ($title) : ?>
                                                <h3 class="title"><?php echo $title ?></h3>
                                            <?php endif; ?>
                                            <?php if ($text) : ?>
                                                <p><?php echo $text ?></p>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($link) : ?>
                                    </a>
                                <?php else : ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
                </div>
        </div>
    <?php endif; ?>
    </div>
    </div>
</section>