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
$class_name = 'mm-address';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$map = get_field('map');
$title = get_field('title');
$address =  get_field('address');
$links = get_field('contact_links');
$background = get_field('background');
$coordinate = get_field('map');
$coordinate = explode(', ', $coordinate);
$class_name = ($background) ? $class_name . ' ' . $background : $class_name;
$upload_dir = wp_upload_dir();
$marker_url = $upload_dir['baseurl'] . '/2023/04/Marker-Office.png';
?>
<section <?php echo $anchor ?> data-target="mm-address" class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <div data-target="address_map" data-marker="<?php echo esc_url($marker_url); ?>" class="address-map">
                <div class="address-map__wrap">
                    <div id="address-map" data-lat="<?php echo ($coordinate[0]) ? $coordinate[0] : '40.75868980615162' ?>" data-lng="<?php echo ($coordinate[1]) ? $coordinate[1] : '-73.97462037401917' ?>"></div>
                </div>
            </div>

            <div class="mm-address__info">
                <?php if ($title) : ?>
                    <h2><?php echo $title ?></h2>
                <?php endif; ?>

                <address>
                    <?php echo $address ?>

                    <?php if ($links) :
                        foreach ($links as $link) :
                            if ($link['link'] && $link['link_or_image'] == 'link') :
                                $link_url = $link['link']['url'];
                                $link_title = $link['link']['title'];
                                $link_target = $link['link']['target'] ? $link['link']['target'] : '_self'; ?>
                                <br><?php echo ($link['label']) ? $link['label'] . ': ' : '' ?>
                                <a aria-label="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                            <?php endif;
                            if ($link['image'] && $link['link_or_image'] == 'image') : ?>
                                <br><?php echo ($link['label']) ? $link['label'] . ': ' : '' ?>
                                <?php echo wp_get_attachment_image($link['image']['id'], 'full', '', ['loading' => 'lazy']) ?>
                    <?php endif;
                        endforeach;
                    endif; ?>
                </address>
            </div>
        </div>
    </div>
</section>