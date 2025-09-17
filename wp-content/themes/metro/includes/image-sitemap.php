<?php

function generate_image_sitemap() {
	$upload_dir = wp_upload_dir();
	$images     = get_posts( array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'posts_per_page' => - 1,
	) );

	$sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
	$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

	foreach ( $images as $image ) {
		$image_url      = wp_get_attachment_url( $image->ID );
		$image_date     = $image->post_date;
		$image_alt      = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
		$image_metadata = wp_get_attachment_metadata( $image->ID );
		$image_width    = isset( $image_metadata['width'] ) ? $image_metadata['width'] : '';
		$image_height   = isset( $image_metadata['height'] ) ? $image_metadata['height'] : '';
		$image_license  = 'https://www.istockphoto.com/legal/license-agreement';

		$sitemap .= '<url>';
		$sitemap .= '<loc>' . esc_url( $image_url ) . '</loc>';
		$sitemap .= '<lastmod>' . esc_html( $image_date ) . '</lastmod>';
		if ( $image_alt ) {
			$sitemap .= '<image:image>';
			$sitemap .= '<image:loc>' . esc_url( $image_url ) . '</image:loc>';
			$sitemap .= '<image:caption>' . esc_html( $image_alt ) . '</image:caption>';
			$sitemap .= '<image:title>' . esc_html( $image_alt ) . '</image:title>';
			if ( $image_width && $image_height ) {
				$sitemap .= '<image:width>' . esc_html( $image_width ) . '</image:width>';
				$sitemap .= '<image:height>' . esc_html( $image_height ) . '</image:height>';
			}
			$sitemap .= '<image:license>' . esc_url( $image_license ) . '</image:license>';
			$sitemap .= '</image:image>';
		}
		$sitemap .= '</url>';
	}

	$sitemap .= '</urlset>';

	$sitemap_path = $upload_dir['basedir'] . '/image-sitemap.xml';
	file_put_contents( $sitemap_path, $sitemap );
}

function add_image_to_sitemap( $attachment_id ) {
	generate_image_sitemap();
}

add_action( 'add_attachment', 'add_image_to_sitemap' );

function check_and_generate_sitemap() {
	$upload_dir   = wp_upload_dir();
	$sitemap_path = $upload_dir['basedir'] . '/image-sitemap.xml';

	if ( ! file_exists( $sitemap_path ) ) {
		generate_image_sitemap();
	}
}

add_action( 'admin_init', 'check_and_generate_sitemap' );