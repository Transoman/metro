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
$class_name = 'list-your-property';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');
$text = get_field('text');
$shortcode = get_field('shortcode');
$contact_title = get_field('title_contact');
$contact_text = get_field('text_contact');
$blocks = get_field('blocks');

$class_name = (empty($blocks)) ? $class_name . ' no-banner' : $class_name;
?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <div class="form-part">
                <div class="wrapper">
                    <div class="text">
                        <?php if ($title): ?>
                            <h2><?php echo $title ?></h2>
                        <?php endif; ?>
                        <?php if ($text): ?>
                            <p><?php echo $text ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($shortcode): ?>
                        <div class="form">
                            <?php echo do_shortcode($shortcode); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="contact-part">
                <div class="wrapper">
                    <?php if ($contact_title): ?>
                        <h2><?php echo $contact_title ?></h2>
                    <?php endif; ?>
                    <?php if ($contact_text): ?>
                        <p><?php echo $contact_text ?></p>
                    <?php endif; ?>
                    <?php
                    $phone_button = get_field('phone_button', 'option');
                    if ($phone_button):
                        $link_url = $phone_button['url'];
                        $link_title = $phone_button['title'];
                        $link_target = $phone_button['target'] ? $phone_button['target'] : '_self'; ?>
                        <a class="button" aria-label="phone" href="<?php echo esc_url($link_url); ?>"
                           target="<?php echo esc_attr($link_target); ?>">
                             <span class="icon">
                                <svg width="24" height="25" viewBox="0 0 24 25" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path d="M23.3138 3.1736L23.2741 3.14883L18.2623 0.679688L12.8528 7.89229L15.3416 11.2109C15.2671 12.4624 14.7364 13.6431 13.8499 14.5297C12.9633 15.4162 11.7826 15.9469 10.531 16.0213L7.21255 13.5326L0 18.9419L2.44859 23.9119L2.4692 23.9538L2.49403 23.9934C2.6247 24.204 2.80708 24.3776 3.02381 24.4977C3.24055 24.6178 3.48442 24.6805 3.73222 24.6797H5.02545C7.51722 24.6797 9.9846 24.1889 12.2867 23.2353C14.5888 22.2818 16.6805 20.8841 18.4425 19.1221C20.2044 17.3602 21.6021 15.2684 22.5557 12.9663C23.5092 10.6642 24 8.19685 24 5.70508V4.41179C24.0008 4.164 23.9381 3.92013 23.818 3.70339C23.6979 3.48666 23.5243 3.30427 23.3138 3.1736ZM22.1479 5.70508C22.1479 15.1465 14.4668 22.8276 5.02545 22.8276H3.97901L2.34071 19.5017L7.21289 15.8476L9.92547 17.8819H10.2341C12.0817 17.8799 13.8529 17.145 15.1593 15.8386C16.4658 14.5322 17.2006 12.761 17.2027 10.9134V10.6048L15.1683 7.89223L18.822 3.02034L22.1479 4.65893V5.70508Z"
                                          fill="var(--mm-navy-color)"/>
                                </svg>
                            </span>
                            <span><?php echo esc_html($link_title); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($blocks): ?>
                <div class="banner-part">
                    <div class="blocks">
                        <?php foreach ($blocks as $block): ?>
                            <div class="block">
                                <div class="inner">
                                    <?php if ($block['image']): ?>
                                        <div class="image">
                                            <?php echo wp_get_attachment_image($block['image']['id'], 'full', '', ['loading' => 'lazy']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($block['text']): ?>
                                        <div class="text">
                                            <p><?php echo $block['text'] ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>