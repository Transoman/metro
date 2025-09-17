<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Sets additional PHP error reporting and logging properties.
 *
 * This function configures error reporting to display all errors and warnings,
 * and optionally logs them to a `debug.txt` file located in the theme directory.
 */
function profidev_debug_set()
{
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', get_template_directory() . '/debug.txt');
}

/**
 * Allows dumping the debug trace during a redirect to catch what is causing it.
 *
 * This function adds a filter to `wp_redirect` that outputs the debug backtrace
 * and terminates the script, useful for diagnosing unexpected redirects.
 */
function profidev_debug_redirect()
{
    add_filter(
        'wp_redirect',
        function () {
            echo '<pre>';
            var_dump(debug_backtrace());
            exit();
        },
        9999
    );
}


/**
 * Prints out all action hooks processed during the lifecycle.
 *
 * This function logs all `do_action` and `do_action_ref_array` hooks that have been called,
 * and outputs them when the `shutdown` action is triggered.
 */
function profidev_get_all_do_action_hooks()
{
    add_action(
        'all',
        function ($tag) {
            static $hooks = [];

            if (did_action($tag)) {
                $hooks[] = $tag;
            }
            if ($tag === 'shutdown') {
                echo '<pre>';
                print_r($hooks);
            }
        }
    );
}

/**
 * Prints out the frequency of action hooks used during the lifecycle.
 *
 * This function outputs a list of all hooks called during the lifecycle and the number
 * of times each hook has been executed, triggered by the `shutdown` action.
 */
function profidev_get_hooks_freq()
{
    add_action(
        'shutdown',
        function () {
            global $wp_actions;
            echo '<pre>';
            print_r($wp_actions);
        }
    );
}

