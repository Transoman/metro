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
$class_name = 'our-clients-section mm-section';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}
$title = get_field('title');
$background = get_field('background');
$range = get_field('range');
$clients = get_field('clients');

$class_name .= ' ' . $background; 
?>
<section <?php echo $anchor; ?> data-target="swiper" data-swiper-slides="<?php echo $range ?>" data-swiper-tablet-slides="3"
         data-swiper-space-between="30"
         class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <div class="heading">
                <?php if ($title): ?>
                    <h2><?php echo $title ?></h2>
                <?php endif; ?>
                <?php if (sizeof($clients) > $range): ?>
                    <div class="controllers">
                        <button aria-label="Previous slide" data-target="swiper_left" class="btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24"
                                 fill="none">
                                <path d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z"
                                      fill="#0961AF"/>
                            </svg>
                        </button>
                        <button aria-label="Next slide" data-target="swiper_right" class="btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24"
                                 fill="none">
                                <path d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z"
                                      fill="#0961AF"/>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
	    </div>
            <?php if (have_rows('clients')): ?>
                <div class="swiper blocks">
                    <div class="swiper-wrapper">
	                    <?php while (have_rows('clients')): the_row();
		                    $image = get_sub_field('company_logo');
		                    $company_name = get_sub_field('company_name');
		                    $company_sector = get_sub_field('company_sector');
		                    ?>
                          <div class="block swiper-slide">
				            <?php if ($image): ?>
                                <div class="image">
                                    <?php
                                        $image_url = wp_get_attachment_url($image['id']);
                                        $image_ext = pathinfo($image_url, PATHINFO_EXTENSION);

                                        $webp_image_url = str_replace("." . $image_ext, ".webp", $image_url);

                                        $upload_dir = wp_upload_dir();
                                        $webp_image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $webp_image_url);
                                    ?>
                                    <picture>
									    <?php if (file_exists($webp_image_path)): ?>
                                          <source srcset="<?php echo esc_url($webp_image_url); ?>" type="image/webp">
									    <?php endif; ?>
                                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($company_name); ?>" loading="lazy">
                                    </picture>
                                </div>
                                <?php endif; ?>

                                <?php if ($company_name || $company_sector): ?>
                                <div class="text">
							        <?php if ($company_name): ?>
                                      <h3 class="company"><?php echo $company_name ?></h3>
							        <?php endif; ?>
							        <?php if ($company_sector): ?>
                                      <span><?php echo $company_sector ?></span>
                                    <?php endif; ?>
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
