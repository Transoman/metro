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
$class_name = 'testimonials';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');
$rate = get_field('rating');
$slides = get_field('slides');
$reviews = get_field('reviews');

?>
<section <?php echo $anchor; ?> data-target="testimonials_slider" class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <div class="heading">
                <?php if ($title): ?>
                    <h2><?php echo $title ?></h2>
                <?php endif; ?>
                <?php if ($slides && sizeof($slides) > 2): ?>
                    <div class="controllers">
                        <button aria-label="Previous slide" data-target="swiper_left" class="btn swiper-button-disabled" disabled="">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24"
                                 fill="none">
                                <path d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z"
                                      fill="#0961AF"></path>
                            </svg>
                        </button>
                        <button aria-label="Next slide" data-target="swiper_right" class="btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24"
                                 fill="none">
                                <path d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z"
                                      fill="#0961AF"></path>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="google-label">
                <div class="image">
                    <img loading="lazy" width="46" height="47"
                         src="<?php echo get_template_directory_uri() . '/assets/images/google.png' ?>" alt="">
                </div>
                <div class="text">
                    <?php if ($rate): ?>
                        <div class="top">
                            <span><?php echo $rate ?></span>
                            <div data-google-rating="<?php echo $rate ?>" class="stars">
                                <div class="star">
                                    <div class="inner"></div>
                                </div>
                                <div class="star">
                                    <div class="inner"></div>
                                </div>
                                <div class="star">
                                    <div class="inner"></div>
                                </div>
                                <div class="star">
                                    <div class="inner"></div>
                                </div>
                                <div class="star">
                                    <div class="inner"></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($reviews): ?>
                        <span><?php echo $reviews ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($slides): ?>
                <div class="blocks swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($slides as $slide): ?>
                            <div class="swiper-slide block">
                                <?php if ($slide['rating']): ?>
                                    <div data-google-rating="<?php echo $slide['rating'] ?>" class="stars">
                                        <div class="star">
                                            <div class="inner"></div>
                                        </div>
                                        <div class="star">
                                            <div class="inner"></div>
                                        </div>
                                        <div class="star">
                                            <div class="inner"></div>
                                        </div>
                                        <div class="star">
                                            <div class="inner"></div>
                                        </div>
                                        <div class="star">
                                            <div class="inner"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php echo $slide['text']; ?>
                                <?php if ($slide['name'] || $slide['date']): ?>
                                    <div class="author">
                                        <?php if ($slide['name']): ?>
                                            <p class="name"><?php echo $slide['name'] ?></p>
                                        <?php endif; ?>
                                        <?php if ($slide['date']): ?>
                                            <span class="time"><?php echo $slide['date'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>