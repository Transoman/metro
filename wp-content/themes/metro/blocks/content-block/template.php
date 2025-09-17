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
$class_name = 'mm-content';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$image = get_field('image');
$head = get_field('head');
$title = get_field('title');
$sub_title = get_field('subtitle');
$text = get_field('text');
$background = get_field('background');
$image_position =  get_field('image_position');
$image_radius =  get_field('image_radius');
$class_name .= ' ' . $background;
$template = get_field('template');
$class_name .= ' ' . $template;
?>
<<?php echo ($head) ? 'section' : 'div' ?> <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
<div class="container">
    <div class="content">
	    <?php if ($image) : ?>
          <div class="mm-content__img" data-radius="<?php echo ($image_radius) ? 'true' : 'false' ?>" data-position="<?php echo $image_position ?>">
				    <?php echo wp_get_attachment_image($image, 'full', '', ['loading' => 'eager']) ?>
          </div>
	    <?php endif; ?>
        <div class="mm-content__description">
			    <?php if ( $head ) : ?>
              <div class="mm-content__head">
						    <?php if ( $title ) : ?>
                    <h2><?php echo esc_html( $title ); ?></h2>
						    <?php endif; ?>

						    <?php if ( $sub_title ) : ?>
                    <p><?php echo esc_html( $sub_title ); ?></p>
						    <?php endif; ?>
              </div>
			    <?php endif; ?>

            <div class="mm-content__text">
					    <?php echo wp_kses_post( $text ); ?>
            </div>
        </div>
    </div>
</div>
</<?php echo ($head) ? 'section' : 'div' ?>>
