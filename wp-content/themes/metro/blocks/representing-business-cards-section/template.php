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
$class_name = 'representing-cards';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');
$link = get_field('link');
$background = get_field('background');
$is_home_page = get_field('is_home_page');
$helpers = new MetroManhattanHelpers();
$class_name = ($is_home_page) ? $class_name . ' not-home' : $class_name;

?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?> <?php echo $background ?>">
    <div class="container">
        <div class="content">
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
                        <a aria-label="<?php echo esc_attr($link_title) ?>" class="default-link" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (have_rows('cards')) : ?>
                <div class="cards">
                    <?php while (have_rows('cards')) : the_row();
                        $icon = get_sub_field('icon');
                        $title = get_sub_field('title');
                        $text = get_sub_field('text');
                        $link = get_sub_field('link');
                    ?>
                        <div class="card">
                            <?php if ($link) :
                                $target = ($link['target']) ? $link['target'] : '_self' ?>
                                <a aria-label="<?php echo esc_attr($title) ?>" target="<?php echo esc_attr($target) ?>" href="<?php echo esc_url($link['url']) ?>">
                                    <div class="inner">
                                        <?php if ($icon) : ?>
                                            <div class="image">
                                                <?php echo wp_get_attachment_image($icon['ID'], 'full', '', ['loading' => 'lazy']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($title || $text) : ?>
                                            <div class="text">
                                                <?php if ($title) : ?>
                                                    <h3 class="title"><?php echo $title ?></h3>
                                                <?php endif; ?>
                                                <?php if ($text) : ?>
                                                    <p><?php echo $text ?></p>
                                                <?php endif; ?>
                                            </div>

                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php else : ?>
                                <div class="inner">
                                    <?php if ($icon) : ?>
                                        <div class="image">
                                            <?php echo wp_get_attachment_image($icon['ID'], 'full', '', ['loading' => 'lazy']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($title || $text) : ?>
                                        <div class="text">
                                            <?php if ($title) : ?>
                                                <h3 class="title"><?php echo $title ?></h3>
                                            <?php endif; ?>
                                            <?php if ($text) : ?>
                                                <p><?php echo $text ?></p>
                                            <?php endif; ?>
                                        </div>

                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>