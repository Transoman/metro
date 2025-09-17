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
$class_name = 'mm-list';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}
$title = get_field('title');
$list = get_field('list');
$background = get_field('background');
$title_loc = get_field('title_loc');
$list_type = get_field('list_type');
$columns = get_field('columns');

$class_name .= ' '. $background;

?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <?php if ($title): ?>
                <h2 data-title="<?php echo $title_loc ?>"><?php echo $title ?></h2>
            <?php endif; ?>

            <?php if ($list): ?>
            <ul class="list" data-type="<?php echo $list_type ?>" data-columns="<?php echo $columns ?>">
                <?php foreach ($list as $item): ?>

                    <?php if($item['text']): ?>
                    <li>
                        <div class="icon">
                            <svg width="18" height="13" viewBox="0 0 18 13" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.78456 13.0009C6.63459 13.0004 6.48632 12.9718 6.34896 12.9168C6.21161 12.8617 6.0881 12.7814 5.98612 12.6809L0.670498 7.51089C0.471793 7.31728 0.365347 7.05943 0.374577 6.79407C0.383808 6.5287 0.507958 6.27756 0.719717 6.09589C0.931476 5.91421 1.2135 5.81689 1.50374 5.82533C1.79398 5.83377 2.06867 5.94728 2.26737 6.14089L6.77362 10.5309L15.9721 1.33089C16.0654 1.22461 16.1815 1.1369 16.3133 1.07315C16.4451 1.0094 16.5897 0.970951 16.7383 0.960166C16.8869 0.949382 17.0364 0.966489 17.1775 1.01044C17.3186 1.05439 17.4484 1.12425 17.559 1.21573C17.6695 1.30722 17.7584 1.41839 17.8202 1.54243C17.882 1.66647 17.9154 1.80075 17.9184 1.93703C17.9214 2.0733 17.8939 2.2087 17.8375 2.3349C17.7812 2.4611 17.6972 2.57543 17.5908 2.67089L7.59394 12.6709C7.49292 12.7732 7.36984 12.8554 7.23244 12.9122C7.09504 12.969 6.94628 12.9992 6.7955 13.0009H6.78456Z" fill="var(--mm-navy-color)"/></svg>
                        </div>
                        <div class="text">
                            <?php echo $item['text'] ?>
                        </div>
                    </li>
                    <?php endif; ?>

                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</section>