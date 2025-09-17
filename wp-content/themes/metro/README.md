## Project Title: MetroManhattan WordPress Site

### Overview

The MetroManhattan WordPress site is a custom-built solution tailored for real estate listings and business space
management. It integrates various functionalities including user management, custom blocks, AJAX handling, Zoho CRM
integration, and performance optimization hooks. This README provides detailed documentation of the core features,
hooks, and includes that power the website.

### Installation

1. Clone the repository to your local machine.
3. Upload the theme to your WordPress installation.
4. Activate the theme in the WordPress admin panel.
5. Import the required plugins (ACF, WooCommerce, etc.).
6. Configure the necessary settings in the WordPress admin.

## Hooks

### Cleanup Hook

- **File:** `hooks/cleanup.php`
- **Description:** This file contains various functions that remove unnecessary WordPress features, styles, and meta
  tags to optimize site performance and security.

### Disable Authors Archive

- **File:** `hooks/disable-authors-archive.php`
- **Description:** Redirects author archive pages to the homepage to prevent access, useful for sites that do not
  utilize author archives.

### Disable Auto Update

- **File:** `hooks/disable-auto-update.php`
- **Description:** Disables automatic updates for WordPress core, plugins, and themes, giving full control over when
  updates are applied.

### Disable Comments

- **File:** `hooks/disable-comments.php`
- **Description:** Disables comments across the entire site, both in the admin area and on the front-end. This includes
  redirecting attempts to access the comments page, removing comments metaboxes, disabling support for comments and
  trackbacks, closing comments, hiding existing comments, and removing comments links from the admin bar.

### Disable Emoji

- **File:** `hooks/disable-emoji.php`
- **Description:** Disables the default WordPress emoji script and related functionality. This includes removing emoji
  detection scripts, styles, and filters, as well as disabling the TinyMCE emoji plugin and DNS prefetching for the
  emoji CDN, improving site performance.

### Disable jQuery

- **File:** `hooks/disable-jquery.php`
- **Description:** Deregisters the default jQuery and jQuery Migrate scripts on the front-end, improving performance if
  jQuery is not needed by the theme or plugins. The admin area is unaffected to ensure WordPress functionality remains
  intact.

### Disable JSON API

- **File:** `hooks/disable-json-api.php`
- **Description:** Disables the WordPress JSON REST API for non-authenticated users, removes REST API links from the
  WordPress head, and prevents unauthorized access, improving security.

### Disable oEmbed

- **File:** `hooks/disable-oembed.php`
- **Description:** Disables WordPress oEmbed functionality, including the removal of related scripts, filters, and
  TinyMCE plugins. This reduces external requests and improves performance and security.

### Disable Search

- **File:** `hooks/disable-search.php`
- **Description:** Disables the WordPress search functionality by blocking search queries, removing the search form, and
  unregistering the search widget. This is useful for sites that do not need a search feature.

### Enable CLS Reporter

- **File:** `hooks/enable-cls-reporter.php`
- **Description:** Injects JavaScript into the page that tracks and logs the Cumulative Layout Shift (CLS) values in the
  browser console, useful for development and performance monitoring.

### Debug Functions

- **File:** `hooks/debug-functions.php`
- **Description:** Provides additional debugging capabilities, including enhanced error reporting, redirect trace
  dumping, action hook logging, and frequency tracking of action hooks.

### Enable SMTP

- **File:** `hooks/enable-smtp.php`
- **Description:** Adds SMTP settings to WordPress, configures PHPMailer to use SMTP for sending emails, logs email
  failures, and ensures emails are sent as HTML.

### Enable WooCommerce Gutenberg

- **File:** `hooks/enable-woocommerce-guttenberg.php`
- **Description:** Enables the Gutenberg block editor for WooCommerce products and integrates product categories and
  tags with the REST API, enhancing compatibility with modern block-based themes.

### Preload Fonts

- **File:** `hooks/preload-fonts.php`
- **Description:** Preloads font files (TTF, WOFF, WOFF2) identified from a Vite manifest to improve load times and
  reduce render-blocking, enhancing performance.

### Profi.dev Footer Schema

- **File:** `hooks/profi.dev.php`
- **Description:** Injects Schema.org structured data into the footer for SEO purposes, describing the website as a web
  page authored by the "metro" organization.

### Register Blocks

- **File:** `hooks/register-blocks.php`
- **Description:** Registers custom Gutenberg blocks, loads their configurations, and ensures unique block attributes
  using ACF. It also handles previewing block assets during development.

### Reusable Blocks

- **File:** `hooks/reusable-blocks.php`
- **Description:** Adds a function to retrieve reusable blocks by title and a menu item in the WordPress admin for
  managing reusable blocks.

### Setup

- **File:** `hooks/setup.php`
- **Description:** Contains theme setup functions, such as enabling theme support, registering scripts and styles,
  managing custom post types, taxonomies, and navigation menus, and other WordPress configurations.

### Vite Integration

- **File:** `hooks/vite.php`
- **Description:** Integrates the Vite build tool with WordPress, handling both development and production asset
  delivery. This includes processing and enqueueing scripts and styles, ensuring that the correct assets are loaded
  based on the environment.

## Includes

### Edit User

- **File:** `includes/edit-user.php`
- **Description:** Manages the user profile editing and deletion through a WordPress shortcode and AJAX requests. It
  includes form validation, nonce verification, and user data updates.

### Email Templates

- **File:** `includes/email-templates.php`
- **Description:** Manages the admin interface for configuring email templates. Provides a settings page for defining
  content for various email templates, such as verification emails, welcome emails, and password reset emails.

### Emoji

- **File:** `includes/emoji.php`
- **Description:** Disables WordPress's default emoji support by removing related scripts, styles, and filters. This
  reduces unnecessary external requests and improves performance.

### Favourites

- **File:** `includes/favourites.php`
- **Description:** Handles the addition, removal, and display of user favorites. Provides AJAX endpoints for toggling
  favorites, pagination, and refactoring old favorites. Also includes an admin interface for viewing user favorites in
  the WordPress admin.

### Helpers

- **File:** `includes/helpers.php`
- **Description:** Contains utility functions used across the theme. These functions include data processing, template
  rendering, meta data management, and other helper methods that enhance the functionality and performance of the theme.
  This file serves as a central location for reusable functions that are not tied to a specific feature.

### Image Cleaner

- **File:** `includes/image-cleaner.php`
- **Description:** Provides a WordPress admin interface for finding and deleting unused images that do not have alt
  tags. This helps in cleaning up the media library by identifying and removing orphaned images.

### Listings Available Update

- **File:** `includes/listings-available-update.php`
- **Description:** Provides an admin interface to update the "Available" date for listings in bulk. The admin can
  replace old "Available" dates with a new date for listings that have a date older than one week from the current date.

### MMNotification

- **File:** `includes/notifications.php`
- **Description:** Handles the management of notifications within the WordPress site, including setting and clearing
  notifications through AJAX requests.

### Register Blocks

- **File:** `includes/register-blocks.php`
- **Description:** Handles the registration of custom blocks and their associated assets for Advanced Custom Fields (
  ACF) within the theme.

### Registration and Login

- **File:** `includes/registration-login.php`
- **Description:** Manages user registration, login, email verification, and password reset functionality, including
  support for social logins (Google, Facebook, LinkedIn).

### AJAX Requests

- **File:** `includes/requests.php`
- **Description:** Handles various AJAX requests for the MetroManhattan WordPress site, including fetching featured
  spaces, paginated listings, map listings, blog posts, and verifying email registration.

### Custom Navigation Walker

- **File:** `includes/walker.php`
- **Description:** Defines a custom walker class `ProfiDev_Walker_Nav_Menu` that extends the `Walker_Nav_Menu` class.
  This custom walker adds depth-based classes to menu items and customizes the structure and attributes of the menu
  output.

### Zoho Integration

- **File:** `includes/zoho.php`
- **Description:** This class handles the integration with the Zoho CRM API. It manages the retrieval of access tokens,
  creation of new leads, and updating of existing leads in Zoho CRM.
- **Key Methods:**
    - `get_access_token()`: Retrieves an OAuth2 access token using a refresh token.
    - `generate_new_lead($user_id)`: Creates a new lead in Zoho CRM based on the provided WordPress user ID.
    - `update_lead($user_id)`: Updates an existing lead in Zoho CRM based on the provided WordPress user ID.
    - `get_social_user($user_id)`: Retrieves social user data associated with the given user ID.

### Cron Task: Cache Clearing
- **File**: `wp-cli-clear-cache.php`
- **Location**: Root theme directory
- **Description**: This file contains a custom WP-CLI command to clear the cache for all published posts, pages, and 
other content types. It ensures that the site's cache is always up-to-date, especially after a post is deleted or 
updated.

#### Features:
- Clears the cache for all published content types (post, page, listings, buildings).
- Logs the cache-clearing process for individual posts and the entire site.
- Provides error handling if the required WP Rocket plugin functions are unavailable.

#### Cron Job Configuration
The script is designed to be executed twice daily using a cron job. This ensures that Google and other crawlers always access the latest version of the site.
- **Frequency**: Every 12 hours (twice per day)
- **Environment**: Production
- **Configuration**: The cron job is set up via the Cron Job section in cPanel, which is accessible here:
https://205.209.120.74:2083/cpsess1888300746/frontend/jupiter/cron/index.html

#### Example Cron Command:
```bash
/usr/local/bin/php /usr/local/bin/wp --path=/home/nemanja/public_html clear-wp-rocket-cache >> /home/nemanja/logs/wp-cli-clear-cache.log 2>&1
```
