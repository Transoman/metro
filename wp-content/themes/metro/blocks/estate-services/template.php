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
$class_name = 'estate-services';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');

?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
	<div class="container">
		<div class="content">
			<?php if($title): ?>
			<h2><?php echo $title; ?></h2>
			<?php endif;  ?>

            <?php if( have_rows('services_list') ): ?>
			<div class="estate-services__wrap">
                    <?php while( have_rows('services_list') ): the_row();
                        $title = get_sub_field('title');
                        $description = get_sub_field('description');
                        $link = get_sub_field('link');
                        ?>
						<div class="estate-services__item">
                            <?php if($title): ?>
							<h3><?php echo $title; ?></h3>
                            <?php endif;  ?>

                            <?php if($title): ?>
							<p><?php echo $description; ?></p>
                            <?php endif;  ?>

                            <?php if( $link ):
                                $link_url = $link['url'];
                                $link_title = $link['title'];
                                $link_target = $link['target'] ? $link['target'] : '_self';
                                ?>
								<a aria-label="<?php echo esc_attr($link_title) ?>" href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>"><?php echo esc_html( $link_title ); ?></a>
                            <?php endif; ?>
						</div>
                    <?php endwhile; ?>
				</div>
            <?php endif; ?>
		</div>
	</div>
</section>