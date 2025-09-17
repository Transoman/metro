<?php

/**
 * Adds SMTP settings to WordPress and configures custom email handling.
 *
 * This script provides a WordPress options page for SMTP settings using ACF (Advanced Custom Fields) and
 * customizes WordPress email behavior to use SMTP, log email failures, and send emails as HTML.
 */

// Add an options page for SMTP settings using ACF.
acf_add_options_page(array(
	'page_title'    => 'SMTP Settings',
	'menu_title'    => 'SMTP Settings',
	'parent_slug'   => 'options-general.php',
	'menu_slug'     => 'smtp-settings',
	'capability'    => 'manage_options',
	'redirect'      => false
));

/**
 * Logs any email sending failures to a log file.
 *
 * @param WP_Error $wp_error The error object containing details of the email failure.
 */
add_action(
    'wp_mail_failed', function ($wp_error) {
        file_put_contents(WP_CONTENT_DIR . '/mail.log', $wp_error->get_error_message() . "\n", FILE_APPEND);
    }, 10, 1
);

/**
 * Sets the email content type to HTML.
 *
 * @return string The content type for emails.
 */
add_filter(
    'wp_mail_content_type', function () {
        return 'text/html';
    }
);

/**
 * Configures PHPMailer to use SMTP based on environment or ACF fields.
 *
 * @param PHPMailer $phpmailer The PHPMailer instance used to send the email.
 */
add_action(
    'phpmailer_init', function ($phpmailer) {
				$param_fn = 'getenv';
				if (profidev_env("SITE_ENV", "production") === "production") {
					$param_fn = 'get_field';
				}

        $phpmailer->isSMTP();
        $phpmailer->Host = call_user_func($param_fn, 'SMTP_HOST', 'options');
        $phpmailer->SMTPAuth = call_user_func($param_fn, 'SMTP_AUTH', 'options');
        $phpmailer->Port = call_user_func($param_fn, 'SMTP_PORT', 'options');
        $phpmailer->Username = call_user_func($param_fn, 'SMTP_USERNAME', 'options');
        $phpmailer->Password = call_user_func($param_fn, 'SMTP_PASSWORD', 'options');
        $phpmailer->SMTPSecure = call_user_func($param_fn, 'SMTP_SECURE', 'options');
        $phpmailer->From = call_user_func($param_fn, 'SMTP_FROM', 'options');
        $phpmailer->FromName = call_user_func($param_fn, 'SMTP_FROMNAME', 'options');
    }
);

/**
 * Sets the "from" email address to the site admin email.
 *
 * @return string The "from" email address.
 */
add_filter(
    'wp_mail_from', function () {
        return get_bloginfo('admin_email');
    }
);
