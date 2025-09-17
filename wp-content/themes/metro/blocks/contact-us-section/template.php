<?php

/**
 * Base Block Template.
 *
 * @param array $block The block settings and attributes.
 * @param string $content The block inner HTML (empty).
 * @param bool $is_preview True during backend preview render.
 * @param int $post_id The post ID the block is rendering content against.
 *          This is either the post ID currently being displayed inside a query loop,
 *          or the post ID of the post hosting this block.
 * @param array $context The context provided to the block by the post or its parent block.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$anchor = '';
if ( ! empty( $block['anchor'] ) ) {
	$anchor = 'id="' . esc_attr( $block['anchor'] ) . '" ';
}

$class_name = 'contact-form';
if ( ! empty( $block['className'] ) ) {
	$class_name .= ' ' . $block['className'];
}

if ( ! empty( $block['align'] ) ) {
	$class_name .= ' align' . $block['align'];
}

$title                 = get_field( 'title' );
$text                  = get_field( 'text' );
$shortcode             = get_field( 'shortcode' );
$background            = get_field( 'background' );
$background_image_png  = 'https://www.metro-manhattan.com/wp-content/uploads/2023/12/bg.png';
$background_image_webp = 'https://www.metro-manhattan.com/wp-content/uploads/2023/12/bg.webp';

$class_name .= ' ' . $background;

?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr( $class_name ); ?>"
                                data-bg-webp="<?php echo $background_image_webp; ?>"
                                data-bg-png="<?php echo $background_image_png; ?>">
    <div class="container">
        <div class="content">
					<?php if ( $title ): ?>
              <h2><?php echo $title ?></h2>
					<?php endif; ?>
					<?php if ( $text ): ?>
              <p><?php echo $text ?></p>
					<?php endif; ?>
					<?php echo do_shortcode( $shortcode ) ?>
        </div>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        function supportsFormat(url, callback) {
            var image = new Image();
            image.onload = image.onerror = function () {
                callback(image.width === 1);
            };
            image.src = url;
        }

        function setBackgroundImage(section, avifUrl, webpUrl, pngUrl) {
            supportsFormat(avifUrl, function (supported) {
                if (supported) {
                    section.style.backgroundImage = "url('" + avifUrl + "')";
                } else {
                    supportsFormat(webpUrl, function (supported) {
                        if (supported) {
                            section.style.backgroundImage = "url('" + webpUrl + "')";
                        } else {
                            section.style.backgroundImage = "url('" + pngUrl + "')";
                        }
                    });
                }
            });
        }

        function lazyLoadBackgroundImages() {
            var sections = document.querySelectorAll('.contact-form[data-bg-avif]');
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var section = entry.target;
                        var avifUrl = section.getAttribute('data-bg-avif');
                        var webpUrl = section.getAttribute('data-bg-webp');
                        var pngUrl = section.getAttribute('data-bg-png');
                        setBackgroundImage(section, avifUrl, webpUrl, pngUrl);
                        observer.unobserve(section);
                    }
                });
            });

            sections.forEach(function (section) {
                observer.observe(section);
            });
        }

        lazyLoadBackgroundImages();
    });
</script>
