<?php
$class_name = 'no-index';
$listing_id = isset( $args['listing_id'] ) ? $args['listing_id'] : null;
?>
<!doctype html>
<html lang="<?php echo get_bloginfo( 'language' ) ?>">

<head>
    <meta charset="<?php echo get_bloginfo( 'charset' ) ?>">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="robots" content="noindex">
	<?php wp_head(); ?>
</head>

<body <?php body_class( $class_name ); ?>>
<header class="header">
    <nav>
        <a href="<?php echo get_permalink( $listing_id ) ?>">« Back to Listing</a>
        <button onclick="window.print(); return false" type="button">Print now</button>
    </nav>
</header>
