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
$class_name = 'commute-calculator';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}
$title_first_step = get_field('title_step_1');
$text_first_step = get_field('text_step_1');
$title_second_step = get_field('title_step_2');
$text_second_step = get_field('text_step_2');
$image_second_step = get_field('image_step_2');
$car_icon = get_field('icon_for_car');
$train_icon = get_field('icon_for_train');
$foot_icon = get_field('icon_for_foot');
$bike_icon = get_field('icon_for_bike');
?>

<section <?php echo $anchor; ?> data-target="commute_calculator" class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div data-target="error_message" class="message">
            <div class="icon">
            </div>
            <div class="text">
                <h4>Something went wrong</h4>
                <p>Please try again.</p>
            </div>
            <div class="button">
                <button aria-label="Close message" type="button">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 0.79244L11.2075 0L6 5.20749L0.79251 0L0 0.79244L5.20753 5.99996L0 11.2075L0.79251 11.9999L6 6.79244L11.2075 11.9999L12 11.2075L6.79248 5.99996L12 0.79244Z"
                              fill="var(--mm-grey-color)"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="content">
            <div class="calculator">
                <div class="heading">
                    <span>step 1</span>
                    <?php if ($title_first_step): ?>
                        <h2><?php echo $title_first_step ?></h2>
                    <?php endif; ?>
                    <?php if ($text_first_step): ?>
                        <p><?php echo $text_first_step ?></p>
                    <?php endif; ?>
                </div>
                <div data-target="form" class="form">
                    <form method="post" action="#">
                        <div class="field">
                            <label for="home">
                                Home Address
                            </label>
                            <input placeholder="E.g 432 Park Avenue, New York, NY" name="home" id="home" type="text">
                        </div>
                        <div class="field">
                            <label for="work">Work Address</label>
                            <input placeholder="E.g 350 Fifth Avenue, New York, NY" name="work" id="work" type="text">
                        </div>
                        <button class="primary-button" aria-label="Get my commute time" type="submit">get my commute time</button>
                    </form>
                </div>
            </div>
            <div class="result">
                <div class="heading">
                    <span>step 2</span>
                    <?php if ($title_second_step): ?>
                        <h2><?php echo $title_second_step ?></h2>
                    <?php endif; ?>
                    <?php if ($text_second_step): ?>
                        <p><?php echo $text_second_step ?></p>
                    <?php endif; ?>
                </div>
                <div class="block">
                    <?php if ($image_second_step): ?>
                        <div class="image">
                            <?php echo wp_get_attachment_image($image_second_step['id'], 'full', '', ['loading' => 'lazy']); ?>
                        </div>
                    <?php endif; ?>
                    <h3 class="title">
                        Your Estimated Commute Time
                    </h3>
                    <div class="inputs">
                        <form data-target="result_form" action="#">
                            <div class="input">
                                <label for="car">
                                    <?php if ($car_icon): ?>
                                        <?php echo wp_get_attachment_image($car_icon['id'], 'full', '', ['loading' => 'lazy']) ?>
                                    <?php endif; ?>
                                    <span>By Car</span>
                                </label>
                                <div class="field">
                                    <input readonly id="car" name="car" type="text">
                                    <span>mins</span>
                                </div>
                            </div>
                            <div class="input">
                                <label for="train">
                                    <?php if ($train_icon): ?>
                                        <?php echo wp_get_attachment_image($train_icon['id'], 'full', '', ['loading' => 'lazy']) ?>
                                    <?php endif; ?>
                                    <span>By Train</span>
                                </label>
                                <div class="field">
                                    <input readonly id="train" name="train" type="text">
                                    <span>mins</span>
                                </div>
                            </div>
                            <div class="input">
                                <label for="foot">
                                    <?php if ($foot_icon): ?>
                                        <?php echo wp_get_attachment_image($foot_icon['id'], 'full', '', ['loading' => 'lazy']) ?>
                                    <?php endif; ?>
                                    <span>On Foot</span>
                                </label>
                                <div class="field">
                                    <input readonly id="foot" name="foot" type="text">
                                    <span>mins</span>
                                </div>
                            </div>
                            <div class="input">
                                <label for="bike">
                                    <?php if ($bike_icon): ?>
                                        <?php echo wp_get_attachment_image($bike_icon['id'], 'full', '', ['loading' => 'lazy']) ?>
                                    <?php endif; ?>
                                    <span>On Bike</span>
                                </label>
                                <div class="field">
                                    <input readonly id="bike" name="bike" type="text">
                                    <span>mins</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div data-target="result_map" class="map">
                <h3>Your Commute on a Map</h3>
                <div id="result-map"></div>
            </div>
        </div>
    </div>
</section>
