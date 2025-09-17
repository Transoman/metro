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
$class_name = 'faq-block';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}

if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$title = get_field('title');
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => []
];

?>
<section <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
    <div class="container">
        <div class="content">
            <?php if ($title): ?>
                <h2><?php echo $title ?></h2>
            <?php endif; ?>

            <?php if (have_rows('faq_list')): ?>
                <ul class="faq-block__list">
                    <?php while (have_rows('faq_list')): the_row();
                        $question = get_sub_field('question_text');
                        $answer = get_sub_field('answer_text');
                        if ($question && $answer):
                            $schema['mainEntity'][] = [
                                '@type' => 'Question',
                                'name' => $question,
                                'acceptedAnswer' => [
                                    '@type' => 'Answer',
                                    'text' => $answer
                                ]
                            ];
                            ?>
                            <li>
                                <div class="faq-block__icon">
                                    <svg width="9" height="13" viewBox="0 0 9 13" fill="none"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5.3431 6.86365L5.34166 6.86459C5.01467 7.07617 4.8125 7.4413 4.8125 7.83672V8.25C4.8125 8.45784 4.64534 8.625 4.4375 8.625C4.22966 8.625 4.0625 8.45784 4.0625 8.25V7.83398C4.0625 7.18635 4.39177 6.582 4.93757 6.23114L6.09042 5.49079C6.0906 5.49068 6.09077 5.49057 6.09094 5.49046C6.73523 5.07829 7.125 4.36431 7.125 3.59844V3.5C7.125 2.25862 6.11638 1.25 4.875 1.25H4C2.75862 1.25 1.75 2.25862 1.75 3.5C1.75 3.70784 1.58284 3.875 1.375 3.875C1.16716 3.875 1 3.70784 1 3.5C1 1.84294 2.34294 0.5 4 0.5H4.875C6.53206 0.5 7.875 1.84294 7.875 3.5V3.59844C7.875 4.61969 7.35514 5.5691 6.49666 6.12287C6.49651 6.12296 6.49637 6.12305 6.49623 6.12314L5.3431 6.86365ZM4.85734 11.5761C4.74599 11.6874 4.59497 11.75 4.4375 11.75C4.28003 11.75 4.129 11.6874 4.01765 11.5761C3.90631 11.4647 3.84375 11.3137 3.84375 11.1562C3.84375 10.9988 3.90631 10.8478 4.01765 10.7364C4.129 10.6251 4.28003 10.5625 4.4375 10.5625C4.59497 10.5625 4.74599 10.6251 4.85734 10.7364C4.96869 10.8478 5.03125 10.9988 5.03125 11.1562C5.03125 11.3137 4.96869 11.4647 4.85734 11.5761Z"
                                              fill="var(--mm-placeholder-grey-color)"
                                              stroke="var(--mm-placeholder-grey-color)"/>
                                    </svg>
                                </div>
                                <div class="faq-block__content">
                                    <h3 class="title"><?php echo $question ?></h3>
                                    <div class="faq-block__descr">
                                        <?php echo $answer ?>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php if(!empty($schema['mainEntity'])): ?>
    <script type="application/ld+json"><?php echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></script>
<?php endif; ?>