<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Disables automatic updates for WordPress core, plugins, and themes.
 *
 * This script defines constants and applies filters to disable automatic updates for WordPress core,
 * plugins, and themes. This can be useful for sites where updates are managed manually or through
 * a version control system.
 */

// Disable automatic updates for WordPress core.
define('WP_AUTO_UPDATE_CORE', false);

// Disable all automatic updates (including minor updates).
define('AUTOMATIC_UPDATER_DISABLED', false);

// Disable automatic updates for plugins.
add_filter('auto_update_plugin', '__return_false');

// Disable automatic updates for themes.
add_filter('auto_update_theme', '__return_false');

