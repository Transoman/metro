<?php
global $wpdb;
$uploads_dir = wp_get_upload_dir()["basedir"];

$years = 	$months = get_folder_content($uploads_dir);

foreach ($years as $year) {
	$months = get_folder_content("{$uploads_dir}/{$year}");

	foreach ($months as $month) {
		$images = get_folder_content("{$uploads_dir}/{$year}/{$month}", "is_file");

		foreach ($images as $image) {
			$r = $wpdb->get_results("SELECT post_id, meta_key, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE '%$image%' LIMIT 1");

			if (empty($r[0])) {
				echo "UNUSED: " . $uploads_dir . "/$year/$month/$image\n";
				// unlink( $uploads_dir . "/$year/$month/$image" );
			}
		}
	}
}

function get_folder_content($folder, $fn = "is_dir", $exclude = [".", "..", "index.php"]) {
	return array_filter(
		array_diff(scandir($folder), $exclude),
		function ($v) use($folder, $fn) {
			if (call_user_func($fn, "{$folder}/{$v}")) {
				return true;
			}

			return false;
		}
	);
}
