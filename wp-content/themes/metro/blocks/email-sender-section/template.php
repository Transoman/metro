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
$class_name = 'email-sender';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');
$subtitle = get_field('subtitle');
$button_text = get_field('buttons_text') ?? 'Resend Email';

$email = '';
if ( ! empty( $_SESSION['registration_email'] ) ) {
    $email = $_SESSION['registration_email'];
} elseif ( ! empty( $_SESSION['reset_password_email'] ) ) {
    $email = $_SESSION['reset_password_email'];
}
?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <?php if ($title || $subtitle): ?>
                <div class="text">
                    <h2><?php echo $title ?></h2>
                    <p><?php echo $subtitle ?></p>
                </div>
            <?php endif; ?>
            <div class="form">
                <form data-target="resend_form" action="#">
                    <input name="action" value="mm_resend_email" type="hidden">
                    <input name="resend_email" value="<?php echo $email ?>" id="email" type="hidden">
                    <input name="mm_resend_nonce" type="hidden"
                           value="<?php echo wp_create_nonce('mm-resend-nonce') ?>">
                    <label for="email">
                        We have sent an activation email to:
                    </label>
                    <div class="no-field">
                        <p><?php echo $email ?></p>
                    </div>
                    <!-- <div class="field">
                        <input id="email" value="<?php echo $email ?>" name="resend_email"
                               readonly type="email">
                    </div> -->
                    <div class="success-message" style="display: none; text-align: center;">
                        <p>Your email has been re-sent successfully!</p>
                    </div>
                </form>
            </div>
            <div class="additional-info">
                <?php while (have_rows('paragraphs')) : the_row();
                    $text = get_sub_field('text');
                    ?>
                    <p><?php echo $text ?></p>
                <?php endwhile; ?>
                <button aria-label="<?php echo $button_text ?>" data-target="resend_submit" type="button"><?php echo $button_text ?></button>
            </div>
        </div>
    </div>
</section>
