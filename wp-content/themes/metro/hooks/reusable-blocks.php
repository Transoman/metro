<?php

/**
 * Retrieves a reusable block by its title.
 *
 * This function queries the WordPress database for a reusable block (post type `wp_block`) with a specific title.
 * It returns the first matching block found or null if no block is found.
 *
 * @param string $title The title of the reusable block to retrieve.
 *
 * @return WP_Post|null The found reusable block post object, or null if not found.
 */
function get_reusable_block_by_title($title)
{
	$query = new WP_Query(
		[
			'post_type'              => 'wp_block',
			'title'                  => $title,
			'post_status'            => 'all',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'orderby'                => 'post_date ID',
			'order'                  => 'ASC',
		]
	);

	if (!empty($query->post)) {
		return $query->post;
	}
	return null;
}

/**
 * Adds a menu item for managing reusable blocks in the WordPress admin.
 *
 * This action adds a new "Reusable Blocks" menu item under the WordPress admin menu, which links directly
 * to the reusable blocks management screen.
 */
add_action('admin_menu', function () {
	add_menu_page('Reusable Blocks', 'Reusable Blocks', 'edit_posts', 'edit.php?post_type=wp_block', '', 'dashicons-editor-table', 33);
});

