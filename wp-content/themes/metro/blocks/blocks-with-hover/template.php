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
$class_name = 'types-blocks columns-2';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');
$which_taxonomies = get_field('blocks_select');
$blocks = ($which_taxonomies === 'type') ? get_field('use_type') : get_field('neighborhoods');
$search_page = get_field('choose_search_page', 'option');
$search_page_link = get_permalink($search_page);

$class_name = (is_admin()) ? $class_name . ' no-margin' : $class_name;

?>
<div <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="inner-content">
        <?php if ($title) : ?>
            <h2 class="title">
                <?php echo $title ?>
            </h2>
        <?php endif; ?>
        <?php if (isset($blocks) && sizeof($blocks) > 0) : ?>
            <div class="blocks">
                <?php foreach ($blocks as $block) :
                    $key = ($which_taxonomies === 'type') ? 'listing-type_' . $block : 'location_' . $block;
                    $image_field = ( $which_taxonomies === 'type' ) ? get_field( 'image', $key ) : get_field( 'images', $key );
		            $image = ($image_field && is_array($image_field) && isset($image_field[0])) ? $image_field[0] : null;
		            $title = get_term($block)->name;
                    $page_text = ($which_taxonomies === 'type') ? get_field('page_of_type', $key) : get_field('page_id', $key);
                    $text = preg_match_all('/^.*?(<p[^>]*>.*?<\/p>).*$/m', get_post_field('post_content', $page_text), $matches, PREG_UNMATCHED_AS_NULL);
                    $text = ($page_text) ? wp_strip_all_tags(join(' ', $matches[0])) : '';
                    $boolean = (strlen($text) > 105);
                    $text = ($boolean) ? substr($text, 0, 105) . '...' : $text
                ?>
                    <div class="block">
                        <div class="inner">
                            <?php if ($image) : ?>
                                <div class="top">
                                    <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => 'lazy']); ?>
                                </div>
                            <?php else : ?>
                                <div class="top">
                                    <?php echo wp_get_attachment_image(37633, 'full', '', ['loading' => 'lazy']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="bottom">
                                <?php if ($title) : ?>
                                    <h3 class="title">
                                        <?php echo $title ?>
                                    </h3>
                                <?php endif; ?>
                                <div class="text">
                                    <?php if ($text) : ?>
                                        <p><?php echo $text ?></p>
					<?php if ($boolean && $page_text) : ?>
		                             <?php if ( is_page( 'neighborhoods' ) ) : ?>
                                                <a href="<?php echo get_permalink( $page_text ) ?>"
                                                   aria-label="Learn more about <?php echo esc_html( $title ) ?>">Learn
                                                    more about <?php echo esc_html( $title ) ?></a>
		                                    <?php else: ?>
                                                <a href="<?php echo get_permalink( $page_text ) ?>"
                                                   aria-label="Read more">Read more
                                                </a>
		                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="button">
                                        <form method="post" action="<?php echo $search_page_link ?>">
                                            <input type="hidden" name="filter[<?php echo ($which_taxonomies === 'type') ? 'uses' : 'locations' ?>][<?php echo $block ?>]" value="<?php echo $title ?>">
                                            <button aria-label="See listings" type="submit">See Listings
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
