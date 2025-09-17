<?php

/**
 * Class EmailTemplates
 *
 * Manages the admin interface for configuring email templates. Provides a settings page where users can define
 * the content of various email templates such as verification emails, welcome emails, and password reset emails.
 */
class EmailTemplates
{
	/**
	 * EmailTemplates constructor.
	 *
	 * Initializes the email templates settings page, registers options, and enqueues the necessary scripts and styles.
	 */
    public function __construct()
    {
	    // Adds the Email Templates submenu under Settings.
        add_action('admin_menu', function () {
            add_submenu_page(
                'options-general.php',
                'Email Templates',
                'Email Templates',
                'manage_options',
                'email-templates',
                [$this, 'email_template_dashboard']
            );
        });

	    // Enqueues scripts and styles for the Email Templates page.
        add_action('admin_enqueue_scripts', function () {
            wp_register_script('email-templates', get_theme_file_uri('assets/js/email-templates.js'), [], null, true);
            wp_register_style('email-templates', get_theme_file_uri('assets/css/email-templates.scss'), [], null);
            if (get_current_screen()->base === 'settings_page_email-templates') {
                wp_enqueue_script('email-templates');
                wp_enqueue_style('email-templates');
            }
        });

	    // Registers the settings for the email templates.
        add_action('admin_init', function () {
            add_option('verify-email-template', '');
            add_option('welcome-email-template', '');
            add_option('reset-password-email-template', '');
            register_setting('email-templates', 'verify-email-template');
            register_setting('email-templates', 'welcome-email-template');
            register_setting('email-templates', 'reset-password-email-template');
        });
    }

	/**
	 * Renders the Email Templates settings page.
	 */
    public function email_template_dashboard()
    {
?>
        <h1>Email Templates</h1>
        <form data-target="email_templates" method="post" action="options.php">
            <?php settings_fields('email-templates'); ?>
            <ul data-target="tabs" class="tabs">
                <li>
                    <button type="button">Verify Template</button>
                </li>
                <li>
                    <button type="button">Welcome Template</button>
                </li>
                <li>
                    <button type="button">Reset Password Template</button>
                </li>
            </ul>
            <div data-target="contents" class="templates">
                <div class="template active">
                    <textarea name="verify-email-template">
                        <?php echo get_option('verify-email-template'); ?>
                    </textarea>
                    <h2>Verify Template</h2>
                    <div id="verify-template"></div>
                </div>
                <div class="template">
                    <textarea name="welcome-email-template">
                        <?php echo get_option('welcome-email-template'); ?>
                    </textarea>
                    <h2>Welcome Template</h2>
                    <div id="welcome-template"></div>
                </div>
                <div class="template">
                    <textarea name="reset-password-email-template">
                        <?php echo get_option('reset-password-email-template') ?>
                    </textarea>
                    <div id="reset-password-template"></div>
                </div>
            </div>
            <?php submit_button('Save templates') ?>
        </form>

<?php
    }
}

new EmailTemplates();

