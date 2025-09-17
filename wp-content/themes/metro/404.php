<?php get_header(); 
$image = get_field('image_error_page', 'option');
$title = get_field('title_error_page', 'option');
$button_text = get_field('button_text_error_page', 'option');
$text = get_field('text_error_page', 'option');
?>

<main>
    <section class="error-section">
        <div class="container">
            <div class="content">
                <?php if ($image) : ?>
                    <div class="image">
                        <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => 'lazy']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($title) : ?>
                    <h1><?php echo $title ?></h1>
                <?php endif; ?>
                <?php if ($text) : ?>
                    <p><?php echo $text ?></p>
                <?php endif; ?>
                <?php if ($button_text) : ?>
                    <a aria-label="Home" href="/"><?php echo $button_text ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>


<?php
get_footer();
