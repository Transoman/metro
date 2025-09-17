<?php

namespace Profidev\Blocks;

if (!defined('ABSPATH') or !function_exists('acf_register_block_type')) {
	exit;
}

/**
 * Includes a file if it exists.
 *
 * @param string $file The file path.
 *
 * @return mixed The result of the include operation or false if the file does not exist.
 */
function includeIfExists($file) {
	return file_exists($file) ? include $file : false;
}

/**
 * Retrieves all block directories.
 *
 * @return array An array of block directory paths.
 */
function get_blocks()
{
	static $blocks;

	if (isset($blocks)) {
		return $blocks;
	}

	$blocks_root_folder = get_template_directory() . '/blocks/';

	foreach (scandir($blocks_root_folder) as $entity) {
		if ($entity === '.' or $entity === '..' or !is_dir($blocks_root_folder . $entity)) {
			continue;
		}

		$blocks[] = $blocks_root_folder . $entity;
	}

	return $blocks;
}

/**
 * Previews block assets.
 *
 * This function generates HTML to include block-specific styles and scripts for previewing the block.
 *
 * @param string $dir The directory path of the block.
 */
function preview($dir) {
	$path = get_template_directory_uri() . str_replace(get_template_directory(), '', $dir) . '/';
	if (profidev_env("SITE_ENV", "production") === "production") {
		$assets = [];
		$only_path = ltrim(str_replace(get_option('siteurl'), '', $path), '/');

		foreach (array_keys(\Profidev\Vite\Data::$manifest) as $asset) {
			error_log($asset . ' ' . $only_path);
			if (str_starts_with($asset, $only_path)) {
				$src = get_theme_file_uri('/dist/' . \Profidev\Vite\Data::$manifest[$asset]['file']);
				if (str_ends_with($asset, '.js')) {
					echo '<script type="module" src="' . esc_attr($src) . '"></script>';
				} else {
					echo '<link rel="stylesheet" href="' . esc_attr($src) . '" media="all" />';
				}
			}
		}
	} else {
		$path = str_replace(get_option('siteurl'), 'https://' . getenv('SITE_URL') . ':' . getenv('DOCKER_VITE_PORT'), $path);
		$assets = [
			$path . 'styles.scss',
			$path . 'script.js',
			$path . 'script-editor.js',
		];
		echo '<script type="module">' . join('', array_map(function ($a) {
			return 'import("' . esc_attr($a) . '");';
		}, $assets)) . '</script>';
	}
}

// Register blocks and include their functions.
add_action('init', function () {
	foreach (get_blocks() as $block) {
		@register_block_type($block . '/block.json');
		includeIfExists($block . DIRECTORY_SEPARATOR . 'functions.php');
	}
});

// Load ACF JSON configurations from block directories.
add_filter('acf/settings/load_json', function ($paths) {
	return array_merge($paths, [get_stylesheet_directory() . '/acf-json'], get_blocks());
});

// add_filter('acf/settings/save_json', function ($path) {
// 	if (array_key_exists('acf_field_group', $_REQUEST) && array_key_exists('key', $_REQUEST['acf_field_group'])) {
// 		$paths = acf_get_setting( 'load_json' );
// 		$file = sprintf('/%s.json', $_REQUEST['acf_field_group']['key']);
// 		foreach($paths as $_path) {
// 			if (file_exists($_path . $file)) {
// 				return $_path;
// 			}
// 		}
// 	}

// 	return $path;
// });

// Ensure blocks have a unique anchor if not provided.
add_filter(
	'acf/pre_save_block',
	function ($attributes) {
		if (!array_key_exists('anchor', $attributes) or $attributes['anchor'] === '') {
			$attributes['anchor'] = 'block-' . uniqid();
		}
		return $attributes;
	}
);

