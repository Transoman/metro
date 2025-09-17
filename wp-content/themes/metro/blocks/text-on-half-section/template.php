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
$class_name = 'text-on-half';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');
$list = get_field('list');

?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <div class="text">
                <?php if ($title): ?>
                    <h2><?php echo $title ?></h2>
                <?php endif; ?>
                <?php if ($list): ?>
                    <ul>
                        <?php foreach ($list as $item): ?>
                            <li>
                                <?php echo $item['text']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>