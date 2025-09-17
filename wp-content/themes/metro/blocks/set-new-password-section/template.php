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
$class_name = 'set-password';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');
$subtitle = get_field('subtitle');
?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <div class="form">
                <?php if (!empty($title)) : ?>
                    <div class="text">
                        <h2><?php echo esc_html($title) ?></h2>
                    </div>
                <?php endif; ?>
                <form data-target="set_password" action="#">
                    <input name="action" value="mm_set_password" type="hidden">
                    <input name="mm_set_password_nonce" type="hidden" value="<?php echo wp_create_nonce('mm-set-password-nonce') ?>">
                    <input name="mm_set_password_query_var" value="<?php echo get_query_var('reset-password', null) ?>" type="hidden">
                    <div data-input="password" class="input">
                        <div data-target="password_input" class="field">
                            <input data-field="password" placeholder="New Password" name="new_password" type="password">
                            <button aria-label="show password" class="hide-button" type="button">
                            </button>
                        </div>
                    </div>
                    <div class="message">
                        <span>At least 8 characters: a mix of letters and numbers.</span>
                    </div>
                    <button class="primary-button" type="submit">Set New Password</button>
                </form>
            </div>
        </div>
    </div>
</section>