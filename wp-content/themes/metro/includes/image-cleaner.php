<?php

/**
 * Image Cleaner Utility
 *
 * This script provides a WordPress admin interface for finding and deleting unused images that do not have alt tags.
 * It helps in cleaning up the media library by identifying and removing orphaned images.
 */

/**
 * Finds unused images that do not have alt tags.
 *
 * @param int $limit The number of images to retrieve.
 * @param int $offset The offset for pagination.
 *
 * @return array|object|null The list of unused images.
 */
function find_unused_images( $limit = 30, $offset = 0 ) {
	global $wpdb;

	$excluded_images = [
		'/wp-content/uploads/2023/04/Marker-Home.png',
		'/wp-content/uploads/2023/04/Marker-Office.png'
	];

	$excluded_images_placeholders = implode( ',', array_fill( 0, count( $excluded_images ), '%s' ) );

	$query = "
        SELECT p.ID, p.guid, p.post_title, pm.meta_value AS alt_text
        FROM {$wpdb->prefix}posts p
        LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
        WHERE p.post_type = 'attachment'
        AND p.post_mime_type LIKE 'image/%'
        AND p.post_parent = 0
        AND (pm.meta_value IS NULL OR pm.meta_value = '')
        AND p.guid NOT IN ($excluded_images_placeholders)
        LIMIT %d OFFSET %d
    ";

	$params = array_merge( $excluded_images, [ $limit, $offset ] );

	return $wpdb->get_results( $wpdb->prepare( $query, ...$params ) );
}

/**
 * Counts the total number of unused images without alt tags.
 *
 * @return int The total number of unused images.
 */
function get_total_unused_images_count() {
	global $wpdb;

	$excluded_images = [
		'/wp-content/uploads/2023/04/Marker-Home.png',
		'/wp-content/uploads/2023/04/Marker-Office.png'
	];

	$excluded_images_placeholders = implode( ',', array_fill( 0, count( $excluded_images ), '%s' ) );

	$query = "
        SELECT COUNT(*)
        FROM {$wpdb->prefix}posts p
        LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
        WHERE p.post_type = 'attachment'
        AND p.post_mime_type LIKE 'image/%'
        AND p.post_parent = 0
        AND (pm.meta_value IS NULL OR pm.meta_value = '')
        AND p.guid NOT IN ($excluded_images_placeholders)
    ";

	return $wpdb->get_var( $wpdb->prepare( $query, ...$excluded_images ) );
}

/**
 * Displays the admin interface for listing and deleting unused images.
 */
function display_unused_images() {
	global $wpdb;
	$current_page = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
	$limit        = 30;
	$offset       = ( $current_page - 1 ) * $limit;
	$images       = find_unused_images( $limit, $offset );
	$total_images = get_total_unused_images_count();

	echo '<div class="wrap">';
	echo '<h1>Unused Images Without Alt Tags</h1>';
	echo '<h4 style="text-align:center;">Total Images Found: ' . esc_html( $total_images ) . '</h4>';
	echo '<form method="post" action="">';
	echo '<button id="delete-all" class="button button-primary" style="margin-bottom: 20px;">Delete all (' . esc_html( $total_images ) . ') images</button>';
	echo '<table class="wp-list-table widefat fixed striped">';
	echo '<thead><tr><th class="manage-column column-cb check-column"><input type="checkbox"></th><th>Image</th><th>Image Name</th><th>ID</th></tr></thead>';
	echo '<tbody>';

	foreach ( $images as $image ) {
		echo '<tr>';
		echo '<th scope="row" class="check-column"><input type="checkbox" name="image_ids[]" value="' . esc_attr( $image->ID ) . '"></th>';
		echo '<td><img src="' . esc_url( $image->guid ) . '" width="50"></td>';
		echo '<td>' . esc_html( $image->post_title ) . '</td>';
		echo '<td>' . esc_html( $image->ID ) . '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	echo '<input style="margin-top: 20px;" type="submit" name="delete_images" class="button button-primary" value="Delete Selected Images">';
	echo '</form>';

	$total_pages = ceil( $total_images / $limit );

	if ( $total_pages > 1 ) {
		echo '<div class="tablenav"><div class="tablenav-pages">';
		for ( $i = 1; $i <= $total_pages; $i ++ ) {
			$class = ( $i == $current_page ) ? ' class="current"' : '';
			echo '<a href="?page=unused-images&paged=' . $i . '"' . $class . '>' . $i . '</a> ';
		}
		echo '</div></div>';
	}

	echo '</div>';

	echo '<script>
	jQuery(document).ready(function($) {
		$("#delete-all").click(function(e) {
			e.preventDefault();
			if (confirm("Are you sure you want to delete all (' . esc_html( $total_images ) . ') images? This action cannot be undone and may take a few minutes. Please do not close your browser.")) {
				$("<input>").attr({
					type: "hidden",
					name: "delete_all_images",
					value: "1"
				}).appendTo("form");
				$("form").submit();
			}
		});
	});
	</script>';
}

/**
 * Handles the bulk deletion of selected images or all unused images.
 */
function handle_bulk_delete() {
	if ( isset( $_POST['delete_images'] ) && isset( $_POST['image_ids'] ) && is_array( $_POST['image_ids'] ) ) {
		foreach ( $_POST['image_ids'] as $image_id ) {
			wp_delete_attachment( $image_id, true );
		}
		echo '<div class="updated"><p>Selected images deleted successfully.</p></div>';
	}

	if ( isset( $_POST['delete_all_images'] ) ) {
		$images = find_unused_images( PHP_INT_MAX, 0 );
		foreach ( $images as $image ) {
			wp_delete_attachment( $image->ID, true );
		}
		echo '<div class="updated"><p>All unused images deleted successfully. This may take a few minutes, please do not close your browser.</p></div>';
	}
}

/**
 * Adds the Image Cleaner menu item to the WordPress admin menu.
 */
function add_image_cleaner_menu_item() {
	add_menu_page( 'Image Cleaner', 'Image Cleaner', 'manage_options', 'unused-images', 'display_unused_images', 'dashicons-trash', 20 );
}

add_action( 'admin_menu', 'add_image_cleaner_menu_item' );
add_action( 'admin_init', 'handle_bulk_delete' );
?>

