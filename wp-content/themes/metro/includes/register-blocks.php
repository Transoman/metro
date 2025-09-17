<?php

namespace ProfiDev\Blocks;

if (!defined('ABSPATH') or !function_exists('acf_register_block_type')) {
	exit;
}

/**
 * Includes the specified file if it exists.
 *
 * @param string $file The file path to include.
 *
 * @return mixed The result of the include operation, or false if the file does not exist.
 */
function includeIfExists($file) {
	return file_exists($file) ? include $file : false;
}

/**
 * Retrieves all block directories within the theme's blocks folder.
 *
 * @return array An array of block directories.
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
 * Outputs the block preview, loading the necessary assets based on the environment.
 *
 * @param string $dir The block directory.
 */
function preview($dir) {
	$path = get_template_directory_uri() . str_replace(get_template_directory(), '', $dir) . '/';
	if (profidev_env("SITE_ENV", "production") === "production") {
		$assets = [];
		$only_path = ltrim(str_replace(get_option('siteurl'), '', $path), '/');

		foreach (array_keys(\ProfiDev\Vite\Data::$manifest) as $asset) {
			error_log($asset . ' ' . $only_path);
			if (str_starts_with($asset, $only_path)) {
				$src = get_theme_file_uri('/dist/' . \ProfiDev\Vite\Data::$manifest[$asset]['file']);
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

/**
 * Registers blocks and includes their associated functions.
 */
add_action('init', function () {
	foreach (get_blocks() as $block) {
		@register_block_type($block . DIRECTORY_SEPARATOR . 'block.json');
		includeIfExists($block . DIRECTORY_SEPARATOR . 'functions.php');
	}
});

/**
 * Adds custom paths to load ACF JSON files from block directories.
 */
add_filter('acf/settings/load_json', function ($paths) {
	return array_merge($paths, [get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'acf-json'], get_blocks());
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

/**
 * Ensures blocks have a unique anchor attribute.
 *
 * @param array $attributes The block attributes.
 *
 * @return array The modified attributes.
 */
add_filter(
	'acf/pre_save_block',
	function ($attributes) {
		if (!array_key_exists('anchor', $attributes) or $attributes['ahcnor'] === '') {
			$attributes['anchor'] = 'block-' . uniqid();
		}
		return $attributes;
	}
);
