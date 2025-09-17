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
$class_name = 'default-banner';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$calculator_builder = get_field('calculator_builder');
$first_add_on = get_field('first_add_on');
$second_add_on = get_field('second_add_on');
$range_result = get_field('range_of_result');
$result_title = get_field('title_result');
$result_button = get_field('button_result');

?>
<section class="office-space-calculator">
    <div class="container">
        <div class="content">
            <div class="calculator">
                <div class="blocks-part">
                    <form data-target="office_space_calculator" action="#">
                        <input name="first_add" value="<?php echo $first_add_on ?>" type="hidden">
                        <input name="second_add" value="<?php echo $second_add_on ?>" type="hidden">
                        <input name="range" value="<?php echo $range_result ?>" type="hidden">
                        <?php
                        $index = 1;
                        foreach ($calculator_builder as $step) :
                            $rows = $step['rows_layout'];
                        ?>
                            <div class="step">
                                <span class="title-step">Step <?php echo $index ?></span>
                                <div class="block">
                                    <?php
                                    $row_index = 1;
                                    foreach ($rows as $row) :
                                        $title = $row['title'];
                                        if ($row['acf_fc_layout'] === 'employees') :
                                            $image = $row['icon']; ?>
                                            <div class="row">
                                                <?php if ($title) : ?>
                                                    <h3><?php echo $title ?></h3>
                                                <?php endif; ?>
                                                <div class="input">
                                                    <?php if ($image) : ?>
                                                        <label for="employees">
                                                            <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => ($row_index == 1 && $index == 1) ? 'eager' : 'lazy']); ?>
                                                        </label>
                                                    <?php endif; ?>
                                                    <input id="employees" name="employees" type="number">
                                                </div>
                                            </div>
                                        <?php elseif ($row['acf_fc_layout'] === 'private_offices') : ?>
                                            <div class="row">
                                                <?php if ($title) : ?>
                                                    <h3><?php echo $title ?></h3>
                                                <?php endif; ?>
                                                <?php $blocks = $row['blocks'];
                                                if ($blocks) : ?>
                                                    <div class="inner-blocks">
                                                        <?php
                                                        $blocks_index = 1;
                                                        foreach ($blocks as $block) :
                                                            $title = $block['title'];
                                                            $image = $block['image'];
                                                            $size = $block['size'];
                                                            $square = $block['square'];
                                                            $text = $block['text_result'];
                                                        ?>
                                                            <div class="inner-block">
                                                                <div class="inner">
                                                                    <?php if ($image) : ?>
                                                                        <div class="image">
                                                                            <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => ($row_index == 2 && $index == 1 && $blocks_index == 1) ? 'eager' : 'lazy']); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="bottom">
                                                                        <div class="text">
                                                                            <?php if ($title) : ?>
                                                                                <label for="private_offices[<?php echo $blocks_index ?>]">
                                                                                    <?php echo $title ?>
                                                                                </label>
                                                                            <?php endif; ?>
                                                                            <?php if ($size) : ?>
                                                                                <span><?php echo $size ?></span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="input">
                                                                            <input placeholder="0" data-text="<?php echo $text; ?>" data-square="<?php echo $square ?>" id="private_offices[<?php echo $blocks_index ?>]" name="private_offices[<?php echo $blocks_index ?>]" type="number">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php
                                                            $blocks_index++;
                                                        endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($row['acf_fc_layout'] === 'meeting_rooms') : ?>
                                            <div class="row">
                                                <?php if ($title) : ?>
                                                    <h3><?php echo $title ?></h3>
                                                <?php endif; ?>
                                                <?php $blocks = $row['blocks'];
                                                if ($blocks) : ?>
                                                    <div class="inner-blocks">
                                                        <?php
                                                        $blocks_index = 1;
                                                        foreach ($blocks as $block) :
                                                            $title = $block['title'];
                                                            $image = $block['image'];
                                                            $size = $block['size'];
                                                            $square = $block['square'];
                                                            $text = $block['text_result'];
                                                        ?>
                                                            <div class="inner-block">
                                                                <div class="inner">
                                                                    <?php if ($image) : ?>
                                                                        <div class="image">
                                                                            <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => ($row_index == 2 && $index == 1 && $blocks_index == 1) ? 'eager' : 'lazy']); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="bottom">
                                                                        <div class="text">
                                                                            <?php if ($title) : ?>
                                                                                <label for="meeting_rooms[<?php echo $blocks_index ?>]">
                                                                                    <?php echo $title ?>
                                                                                </label>
                                                                            <?php endif; ?>
                                                                            <?php if ($size) : ?>
                                                                                <span><?php echo $size ?></span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="input">
                                                                            <input placeholder="0" data-text="<?php echo $text ?>" data-square="<?php echo $square ?>" id="meeting_rooms[<?php echo $blocks_index ?>]" name="meeting_rooms[<?php echo $blocks_index ?>]" type="number">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php
                                                            $blocks_index++;
                                                        endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($row['acf_fc_layout'] === 'reception_areas') : ?>
                                            <div class="row">
                                                <?php if ($title) : ?>
                                                    <h3><?php echo $title ?></h3>
                                                <?php endif; ?>
                                                <?php $blocks = $row['blocks'];
                                                if ($blocks) : ?>
                                                    <div class="inner-blocks">
                                                        <?php
                                                        $blocks_index = 1;
                                                        foreach ($blocks as $block) :
                                                            $title = $block['title'];
                                                            $image = $block['image'];
                                                            $size = $block['size'];
                                                            $square = $block['square'];
                                                            $text = $block['text_result'];
                                                        ?>
                                                            <div class="inner-block">
                                                                <div class="inner">
                                                                    <?php if ($image) : ?>
                                                                        <div class="image">
                                                                            <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => ($row_index == 2 && $index == 1 && $blocks_index == 1) ? 'eager' : 'lazy']); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="bottom">
                                                                        <div class="text">
                                                                            <?php if ($title) : ?>
                                                                                <label for="reception_areas[<?php echo $blocks_index ?>]">
                                                                                    <?php echo $title ?>
                                                                                </label>
                                                                            <?php endif; ?>
                                                                            <?php if ($size) : ?>
                                                                                <span><?php echo $size ?></span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="input">
                                                                            <input placeholder="0" data-text="<?php echo $text ?>" data-square="<?php echo $square ?>" id="reception_areas[<?php echo $blocks_index ?>]" name="reception_areas[<?php echo $blocks_index ?>]" type="number">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php
                                                            $blocks_index++;
                                                        endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($row['acf_fc_layout'] === 'utility_rooms') : ?>
                                            <div class="row">
                                                <?php if ($title) : ?>
                                                    <h3><?php echo $title ?></h3>
                                                <?php endif; ?>
                                                <?php $blocks = $row['blocks'];
                                                if ($blocks) : ?>
                                                    <div class="inner-blocks">
                                                        <?php
                                                        $blocks_index = 1;
                                                        foreach ($blocks as $block) :
                                                            $title = $block['title'];
                                                            $image = $block['image'];
                                                            $size = $block['size'];
                                                            $square = $block['square'];
                                                            $text = $block['text_result'];
                                                        ?>
                                                            <div class="inner-block">
                                                                <div class="inner">
                                                                    <?php if ($image) : ?>
                                                                        <div class="image">
                                                                            <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => ($row_index == 2 && $index == 1 && $blocks_index == 1) ? 'eager' : 'lazy']); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="bottom">
                                                                        <div class="text">
                                                                            <?php if ($title) : ?>
                                                                                <label for="utility_rooms[<?php echo $blocks_index ?>]">
                                                                                    <?php echo $title ?>
                                                                                </label>
                                                                            <?php endif; ?>
                                                                            <?php if ($size) : ?>
                                                                                <span><?php echo $size ?></span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="input">
                                                                            <input placeholder="0" data-text="<?php echo $text ?>" data-square="<?php echo $square ?>" id="utility_rooms[<?php echo $blocks_index ?>]" name="utility_rooms[<?php echo $blocks_index ?>]" type="number">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php
                                                            $blocks_index++;
                                                        endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else : ?>
                                            <div class="row">
                                                <?php if ($title) : ?>
                                                    <h3><?php echo $title ?></h3>
                                                <?php endif; ?>
                                                <?php $blocks = $row['blocks'];
                                                if ($blocks) : ?>
                                                    <div class="inner-blocks">
                                                        <?php
                                                        $blocks_index = 1;
                                                        foreach ($blocks as $block) :
                                                            $title = $block['title'];
                                                            $image = $block['image'];
                                                            $square = $block['square'];
                                                            $text = $block['text_result'];
                                                        ?>
                                                            <div class="inner-block radio">
                                                                <div class="inner">
                                                                    <?php if ($image) : ?>
                                                                        <div class="image">
                                                                            <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => ($row_index == 2 && $index == 1 && $blocks_index == 1) ? 'eager' : 'lazy']); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="bottom">
                                                                        <div class="input">
                                                                            <input data-square="<?php echo $square ?>" data-text="<?php echo $text ?>" value="<?php echo $title ?>" id="seating_places[<?php echo $blocks_index ?>]" name="seating_places" type="radio">
                                                                            <?php if ($title) : ?>
                                                                                <label for="seating_places[<?php echo $blocks_index ?>]">
                                                                                    <?php echo $title ?>
                                                                                </label>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php
                                                            $blocks_index++;
                                                        endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php
                                        $row_index++;
                                    endforeach; ?>
                                </div>
                            </div>
                        <?php
                            $index++;
                        endforeach; ?>
                    </form>
                </div>
                <div class="result-part">
                    <div class="content">
                        <h3>Your Square Footage</h3>
                        <p class="show" data-target="without_list">Add no of employees to see your estimate.</p>
                        <p data-target="with_list">Added space considerations</p>
                        <ul data-target="result_list"></ul>
                        <div data-target="range_result" class="range">
                            <p><span data-target="from"></span> - <span data-target="to"></span> SF</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section data-target="slider_section" class="calculated-listings mm-section hide">
    <div class="container">
        <div class="content">
            <div class="heading">
                <?php if ($result_title) : ?>
                    <h2><?php echo $result_title ?></h2>
                <?php endif; ?>
                <div class="heading-part">
                    <div class="controllers">
                        <button aria-label="Previous slide" data-target="swiper_left" class="btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                <path d="M12.7982 24L15 21.7982L5.202 12L15 2.20181L12.7982 0L0.798089 12L12.7982 24Z" fill="#0961AF" />
                            </svg>
                        </button>
                        <button aria-label="Next slide" data-target="swiper_right" class="btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="24" viewBox="0 0 15 24" fill="none">
                                <path d="M2.20181 24L0 21.7982L9.798 12L0 2.20181L2.20181 0L14.2019 12L2.20181 24Z" fill="#0961AF" />
                            </svg>
                        </button>
                    </div>
                    <?php if ($result_button) :
                        $text = $result_button['title'];
                        $url = $result_button['url'];
                        $target = ($result_button['target']) ? $result_button['target'] : '_self';
                    ?>
                        <a aria-label="<?php echo esc_attr($text) ?>" target="<?php echo esc_attr($target); ?>" href="<?php echo esc_url($url) ?>"><?php echo esc_html($text) ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <div data-target="slider_element" class="swiper">
                <div class="swiper-wrapper">
                </div>
            </div>
        </div>
    </div>
</section>