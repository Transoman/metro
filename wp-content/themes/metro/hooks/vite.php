<?php

namespace Profidev\Vite;

if (!defined('ABSPATH')) {
	exit;
}

if (defined('DOING_CRON') && DOING_CRON) {
	return;
}

/**
 * Class to hold data for Vite integration.
 */
class Data
{
	public static $blocks_to_move = [];
	public static $manifest;
	public static $tmp_cssfile;
}

\Profidev\Vite\Data::$tmp_cssfile = tmpfile();

/**
 * Handles development assets for Vite.
 *
 * @param WP_Dependencies $assets The WP_Scripts or WP_Styles object.
 * @param string $handle The handle of the enqueued asset.
 */
function handle_dev_asset($assets, $handle)
{
	$asset_type = 'script';
	if (get_class($assets) === 'WP_Styles') {
		$asset_type = 'style';
	}

	$src = strtok($assets->registered[$handle]->src, '?');
	if (str_contains($src, 'wp-content/themes/') === false) {
		return;
	}

	$src = preg_replace('/.*wp-content\/([^\?]+)/', '/wp-content/\1', $src);
	if (@key_exists('data', $assets->registered[$handle]->extra) && '' !== $assets->registered[$handle]->extra['data']) {
		echo '<script>' . $assets->registered[$handle]->extra['data'] . '</script>';
	}

	call_user_func('wp_dequeue_' . $asset_type, $handle);
	call_user_func('wp_deregister_' . $asset_type, $handle);

	if (@filesize(ABSPATH . $src) < 1) {
		return;
	}

	echo '<script type="module" crossorigin src="https://' . esc_attr(getenv('SITE_URL') . ':' . getenv('DOCKER_VITE_PORT') . $src) . '"></script>' . "\n";
}

/**
 * Handles production assets for Vite.
 *
 * @param WP_Dependencies $assets The WP_Scripts or WP_Styles object.
 * @param string $handle The handle of the enqueued asset.
 */
function handle_asset($assets, $handle)
{
	if (is_null(\Profidev\Vite\Data::$manifest)) {
		return;
	}

	$asset_type = 'script';
	if (get_class($assets) === 'WP_Styles') {
		$asset_type = 'style';
	}

	$src = strtok($assets->registered[$handle]->src, '?');
	if (strpos($src, 'wp-content/themes/') === false) {
		return;
	}

	$src = preg_replace('/.*wp-content\/([^\?]+)/', '\1', $src);

	$found = 0;
	foreach (array_keys(\Profidev\Vite\Data::$manifest) as $asset) {
		if (str_ends_with($asset, $src)) {
			if (@key_exists('data', $assets->registered[$handle]->extra) && $assets->registered[$handle]->extra['data'] !==  '') {
				echo '<script>' . $assets->registered[$handle]->extra['data'] . '</script>';
			}

			$deps = $assets->registered[$handle]->deps;
			$ver = $assets->registered[$handle]->ver;

			call_user_func('wp_dequeue_' . $asset_type, $handle);
			call_user_func('wp_deregister_' . $asset_type, $handle);

			$filesize = @filesize(get_theme_file_path('/dist/' . \Profidev\Vite\Data::$manifest[$asset]["file"]));
			if ($filesize < 1) {
				return;
			}

			if (!is_admin() && \Profidev\Vite\Data::$tmp_cssfile && $asset_type == 'style') {
				fwrite(\Profidev\Vite\Data::$tmp_cssfile, file_get_contents(get_theme_file_path('/dist/' . \Profidev\Vite\Data::$manifest[$asset]["file"])));
				return;
			}

			if (is_front_page() && $handle === 'partytown') {
				continue;
			}

			call_user_func('wp_register_' . $asset_type, $handle, get_theme_file_uri('/dist/' . \Profidev\Vite\Data::$manifest[$asset]["file"]), $deps, $ver, ($asset_type === 'script'));
			call_user_func('wp_enqueue_' . $asset_type, $handle);

			if ($asset_type === 'style' && !is_admin()) {
				ob_start();
				$assets->do_item($handle);
				\Profidev\Vite\Data::$blocks_to_move[] = ob_get_clean();
				call_user_func('wp_dequeue_' . $asset_type, $handle);
				call_user_func('wp_deregister_' . $asset_type, $handle);
			}

			$found = 1;
			break;
		}
	}

	if ($found === 0) {
		if (@filesize(ABSPATH . 'wp-content/' . $src) < 1) {
			call_user_func('wp_dequeue_' . $asset_type, $handle);
			call_user_func('wp_deregister_' . $asset_type, $handle);
		}
	}
}

/**
 * Processes assets, determining whether to use development or production assets.
 *
 * @param string $action The action hook that triggered the asset processing.
 */
function process_assets($action)
{
	global $wp_scripts, $wp_styles;

	if (profidev_env("SITE_ENV", "production") === "production") {
		foreach ($wp_styles->queue as $handle) {
			handle_asset($wp_styles, $handle);
		}
		foreach ($wp_scripts->queue as $handle) {
			handle_asset($wp_scripts, $handle);
		}
	} else {
		foreach ($wp_styles->queue as $handle) {
			handle_dev_asset($wp_styles, $handle);
		}
		foreach ($wp_scripts->queue as $handle) {
			handle_dev_asset($wp_scripts, $handle);
		}
	}
}

/**
 * Hook for modifying the output buffer during template redirects.
 *
 * @param string $buffer The current output buffer content.
 *
 * @return string The modified output buffer content.
 */
function template_redirect_hook( $buffer ) {
	if ( \Profidev\Vite\Data::$tmp_cssfile ) {
		$tmp_cssfilename = stream_get_meta_data( \Profidev\Vite\Data::$tmp_cssfile )['uri'];
		$md5             = md5_file( $tmp_cssfilename );
		$md5_file        = get_theme_file_path( '/dist/' . $md5 . '.css' );
		$md5_url         = get_theme_file_uri( '/dist/' . $md5 . '.css' );

		if ( ! is_file( $md5_file ) ) {
			@copy( $tmp_cssfilename, $md5_file );
		}

		$current_url = $_SERVER['REQUEST_URI'];
		$commercial_space_base_url    = '/commercial-space/';
		$about_us_base_url    = '/about-us/';
		$blog_base_url    = '/blog/';

		// Check for `/blog/` with no additional `/` occurrences except for the last one
		$is_exact_blog  = preg_match( '#^' . preg_quote( $blog_base_url, '#' ) . '([^/]*?)/?$#', $current_url );
		
		if ( is_page() || is_single() ) {
		    $post_id   = get_the_ID();
		    
		    if ( $post_id == 998 ) {
		        \Profidev\Vite\Data::$blocks_to_move[] = '<link fetchpriority="high" rel="stylesheet" href="' . $md5_url . '" media="all" />';
		    }
		}
		
		if ( str_contains( get_permalink(), home_url( '/blog/' ) ) ) {
            \Profidev\Vite\Data::$blocks_to_move[] = '<link fetchpriority="high" rel="stylesheet" href="' . $md5_url . '" media="all" />';
        }
        
		if (
			is_front_page() ||
			is_singular( 'listings' ) ||
			is_singular( 'buildings' ) ||
			strpos( $current_url, $commercial_space_base_url ) !== false ||
			strpos( $current_url, $about_us_base_url ) !== false
			|| $is_exact_blog
		) {
			$js_loader_script                      = '<script>function loadCSS(){var e=document.createElement("link");e.rel="stylesheet",e.href="' . $md5_url . '",document.head.appendChild(e),document.removeEventListener("mousemove",loadCSS),document.removeEventListener("click",loadCSS),document.removeEventListener("scroll",loadCSS)}document.addEventListener("mousemove",loadCSS,{once:!0}),document.addEventListener("click",loadCSS,{once:!0}),document.addEventListener("scroll",loadCSS,{once:!0});</script>';
			\Profidev\Vite\Data::$blocks_to_move[] = $js_loader_script;
		} else {
			\Profidev\Vite\Data::$blocks_to_move[] = '<link fetchpriority="high" rel="stylesheet" href="' . $md5_url . '" media="all" />';
		}
	}

	return str_replace( '</head>', join( '', \Profidev\Vite\Data::$blocks_to_move ) . '</head>', $buffer );
}

add_action('init', function () {
	\Profidev\Vite\Data::$manifest = get_template_directory()  . '/dist/manifest.json';

	if (is_file(\Profidev\Vite\Data::$manifest)) {
		\Profidev\Vite\Data::$manifest = json_decode(file_get_contents(\Profidev\Vite\Data::$manifest), true);
	} else {
		\Profidev\Vite\Data::$manifest = null;
	}

	// Intercept enqueued assets to deliver via Vite.
	$actions = [
		[
			'action' => is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts',
			'prio' => PHP_INT_MAX,
		],
		[
			'action' => 'wp_print_header_scripts',
			'prio' => PHP_INT_MIN,
		],
		[
			'action' => 'wp_footer',
			'prio' => PHP_INT_MIN,
		],
		[
			'action' => 'wp_print_footer_scripts',
			'prio' => PHP_INT_MIN,
		],
		[
			'action' => 'wp_print_styles',
			'prio' => PHP_INT_MIN,
		],
		[
			'action' => 'wp_head',
			'prio' => PHP_INT_MIN,
		]
	];

	foreach ($actions as $item) {
		add_action(
			$item['action'],
			function () use ($item) {
				process_assets($item['action']);
			},
			$item['prio']
		);
	}

	if (profidev_env("SITE_ENV", "production") === "production") {
		add_filter('script_loader_tag', function ($tag, $handle, $src) {
			if (is_null(\Profidev\Vite\Data::$manifest)) {
				return $tag;
			}

			if (strpos($src, 'wp-content/themes/') === false) {
				return $tag;
			}

			$src = preg_replace('/.*wp-content\/([^\?]+)/', '\1', $src);

			foreach (array_keys(\Profidev\Vite\Data::$manifest) as $asset) {
				if (str_ends_with($src, \Profidev\Vite\Data::$manifest[$asset]['file'])) {
					return str_replace(' src', ' type="module" '.(str_contains($tag, 'id="acf-')?'defer async ':'').' src', $tag);
				}
			}
			return $tag;
		}, 10, 3);

		add_action(
			'template_redirect',
			function () {
				ob_start('\\Profidev\\Vite\\template_redirect_hook');
			},
			PHP_INT_MIN
		);

		add_action("shutdown", function () {
			@ob_end_flush();
		});

		add_filter('styles_inline_size_limit', function() { return 1024; });
	} else {
		// To avoid inline css as we are using Vite.
		add_filter('styles_inline_size_limit', '__return_zero');
	}

	// This filters turning on ability to load only assets for blocks that are being used.
	add_filter('should_load_separate_core_block_assets', '__return_true');
}, PHP_INT_MIN);
