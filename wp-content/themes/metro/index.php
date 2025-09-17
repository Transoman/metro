<?php
get_header('search');
?>
<main>
    <?php
    get_template_part('templates/parts/notification', 'template');
    the_content(); ?>
</main>
<?php get_footer();
