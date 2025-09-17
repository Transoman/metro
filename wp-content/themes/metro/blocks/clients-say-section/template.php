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
$class_name = 'clients-say';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$fields = get_fields();
$title = get_field('title');


?>
<section data-target="clients_say" class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <?php if ($title): ?>
                <h2><?php echo $title ?></h2>
            <?php endif; ?>
            <?php if (have_rows('testimonials')): ?>
                <div data-target="clients_say_element" class="clients-slider swiper">
                    <div class="swiper-wrapper">
                        <?php while (have_rows('testimonials')): the_row();
                            $image = get_sub_field('image');
                            $author = get_sub_field('author');
                            $rank = get_sub_field('rank');
                            $text = get_sub_field('text');
                            ?>
                            <div class="swiper-slide slide">
                                <?php if ($image): ?>
                                    <div class="image">
                                        <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => 'lazy']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($text || $rank || $author): ?>
                                    <div class="text">
                                        <div class="quote">
                                            <svg width="77" height="70" viewBox="0 0 77 70" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <path d="M0 0V33.5826H14.3121C14.3121 38.9616 13.3002 43.541 11.2762 47.3209C9.25229 50.9554 5.49355 53.7176 0 55.6075V70C4.48158 69.4185 8.60174 68.1101 12.3605 66.0748C16.2638 63.8941 19.5888 61.2046 22.3356 58.0062C25.0824 54.8079 27.1786 51.1007 28.6243 46.8847C30.2145 42.6688 30.9373 38.162 30.7928 33.3645V0H0ZM46.1892 0V33.5826H60.5013C60.5013 38.9616 59.4893 43.541 57.4654 47.3209C55.4414 50.9554 51.6827 53.7176 46.1892 55.6075V70C50.6707 69.4185 54.7909 68.1101 58.5496 66.0748C62.4529 63.8941 65.778 61.2046 68.5248 58.0062C71.2715 54.8079 73.3678 51.1007 74.8134 46.8847C76.4037 42.6688 77.1265 38.162 76.9819 33.3645V0H46.1892Z"
                                                      fill="var(--mm-blue-grey-color)"/>
                                            </svg>
                                        </div>
                                        <?php if ($text): ?>
                                            <p><?php echo $text ?></p>
                                        <?php endif; ?>
                                        <?php if ($author): ?>
                                            <p class="author">
                                                <?php echo $author ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($rank): ?>
                                            <span><?php echo $rank ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="pagination"></div>
                </div>
            <?php endif; ?>
            <div class="google-rating">
                <div class="main">
                    <h3>Our ratings on Google</h3>
                    <div class="content">
                        <div class="image">
                            <img loading="lazy" width="46" height="47"
                                 src="<?php echo get_template_directory_uri() . '/assets/images/google.png' ?>" alt="">
                        </div>
                        <div class="text">
                            <div class="top">
                                <span>4.9</span>
                                <div data-google-rating="4.9" class="stars">
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
                            <span>81 reviews</span>
                        </div>
                    </div>
                </div>
                <?php if (have_rows('reviews')): ?>
                    <div data-target="google_review_element" class="reviews swiper">
                        <div class="swiper-wrapper">
                            <?php while (have_rows('reviews')): the_row();
                                $name = get_sub_field('name');
                                $text = get_sub_field('text');
                                $rating = get_sub_field('rating');
                                ?>
                                <div class="review swiper-slide">
                                    <div data-google-rating="<?php echo $rating ?>" class="stars">
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
                                    <?php if ($name): ?>
                                        <h4><?php echo $name ?></h4>
                                    <?php endif; ?>
                                    <?php if ($text): ?>
                                        <span><?php echo $text ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>