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
$class_name = 'list-wih-heading-section';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$fields = get_fields();

?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <?php if (!empty($fields['title'])) : ?>
                <h2><?php echo esc_html($fields['title']) ?></h2>
            <?php endif; ?>
            <?php if (!empty($fields['list'])) : ?>
                <ul>
                    <?php foreach ($fields['list'] as $list_item) : ?>
                        <?php if (!empty($list_item['title']) || !empty($list_item['text'])) : ?>
                            <li>
                                <?php if (!empty($list_item['title'])) : ?>
                                    <h3><?php echo esc_html($list_item['title']) ?></h3>
                                <?php endif; ?>
                                <?php if (!empty($list_item['text'])) : ?>
                                    <?php echo $list_item['text']; ?>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>