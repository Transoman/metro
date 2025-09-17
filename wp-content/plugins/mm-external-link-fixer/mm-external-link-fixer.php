<?php
/**
 * Plugin Name: MM External Link Finder
 * Description: Scans posts for external links and lists them
 * Version: 1.1.8
 * Author: Nemanja Tanaskovic
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'mm_external_link_finder_menu');

function mm_external_link_finder_menu() {
    add_menu_page(
        'External Link Finder',
        'External Links',
        'manage_options',
        'mm-external-link-finder',
        'mm_external_link_finder_page',
        'dashicons-admin-links'
    );

    add_submenu_page(
        'mm-external-link-finder',
        'Trusted Domains',
        'Trusted Domains',
        'manage_options',
        'mm-trusted-domains',
        'mm_trusted_domains_page'
    );
}

// Register settings
add_action('admin_init', 'mm_register_settings');

function mm_register_settings() {
    register_setting('mm_trusted_domains', 'mm_trusted_domains');
}

// Get trusted domains
function mm_get_trusted_domains() {
    $default_domains = array(
        'nytimes.com',
        'gsa.gov',
        'forbes.com'
    );
    
    $saved_domains = get_option('mm_trusted_domains', $default_domains);
    return array_filter(array_map('trim', $saved_domains));
}

// Trusted Domains settings page
function mm_trusted_domains_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['mm_trusted_domains'])) {
        $domains = array_filter(array_map('trim', explode("\n", $_POST['mm_trusted_domains'])));
        update_option('mm_trusted_domains', $domains);
        echo '<div class="notice notice-success"><p>Trusted domains updated successfully!</p></div>';
    }

    $trusted_domains = mm_get_trusted_domains();
    ?>
    <div class="wrap">
        <h1>Trusted Domains</h1>
        <p>Enter one domain per line. These domains will not receive nofollow/noopener attributes.</p>
        <form method="post">
            <?php wp_nonce_field('mm_trusted_domains'); ?>
            <textarea name="mm_trusted_domains" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(implode("\n", $trusted_domains)); ?></textarea>
            <p class="submit">
                <input type="submit" class="button button-primary" value="Save Domains">
            </p>
        </form>
    </div>
    <?php
}

// Create the admin page
function mm_external_link_finder_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle link updates
    if (isset($_POST['update_links']) && isset($_POST['post_id'])) {
        $post_id = intval($_POST['post_id']);
        $result = mm_update_post_links($post_id);
        if ($result) {
            echo '<div class="notice notice-success"><p>Links updated successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>No links were updated.</p></div>';
        }
    }

    // Add scan button
    if (isset($_POST['scan_links'])) {
        $external_links = mm_scan_for_external_links();
        ?>
        <div class="wrap">
            <h1>External Link Finder</h1>
            <p class="description">This plugin scans your posts for external links and helps you manage them. It automatically adds <code>rel="nofollow noopener"</code> and <code>target="_blank"</code> attributes to external links, except for trusted domains. You can manage trusted domains in the <a href="<?php echo admin_url('admin.php?page=mm-trusted-domains'); ?>">Trusted Domains</a> settings.</p>
            <form method="post">
                <input type="submit" name="scan_links" class="button button-primary" value="Scan for External Links">
            </form>
            
            <?php if (!empty($external_links)): ?>
                <h2>Found External Links</h2>
                <form method="post" id="bulk-update-form">
                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <input type="submit" name="bulk_update_links" class="button action" value="Update Selected Links">
                        </div>
                    </div>
                    <div class="mm-table-responsive">
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th class="check-column">
                                        <input type="checkbox" id="select-all-posts">
                                    </th>
                                    <th>Post Title</th>
                                    <th>External Links</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Group links by post
                                $grouped_links = array();
                                foreach ($external_links as $link) {
                                    $post_id = $link['post_id'];
                                    if (!isset($grouped_links[$post_id])) {
                                        $grouped_links[$post_id] = array(
                                            'post_title' => $link['post_title'],
                                            'links' => array(),
                                            'needs_update' => false
                                        );
                                    }
                                    $grouped_links[$post_id]['links'][] = $link;
                                    
                                    // Check if any link needs update
                                    $missing_attrs = array();
                                    $unnecessary_attrs = array();
                                    
                                    if ($link['is_trusted']) {
                                        // For trusted domains, check for unnecessary attributes
                                        if ($link['has_nofollow'] || $link['has_noopener']) {
                                            $unnecessary_attrs[] = 'rel="nofollow noopener"';
                                        }
                                    } else {
                                        // For non-trusted domains, check for missing attributes
                                        if (!$link['has_nofollow']) $missing_attrs[] = 'nofollow';
                                        if (!$link['has_noopener']) $missing_attrs[] = 'noopener';
                                    }
                                    
                                    if (!$link['has_target_blank']) $missing_attrs[] = 'target="_blank"';
                                    
                                    if (!empty($missing_attrs) || !empty($unnecessary_attrs)) {
                                        $grouped_links[$post_id]['needs_update'] = true;
                                    }
                                }

                                foreach ($grouped_links as $post_id => $group): 
                                ?>
                                    <tr>
                                        <td>
                                            <?php if ($group['needs_update']): ?>
                                                <input type="checkbox" name="post_ids[]" value="<?php echo esc_attr($post_id); ?>" class="post-checkbox">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo get_edit_post_link($post_id); ?>" target="_blank">
                                                <?php echo esc_html($group['post_title']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <ul class="mm-link-list">
                                                <?php foreach ($group['links'] as $link): 
                                                    $missing_attrs = array();
                                                    $unnecessary_attrs = array();
                                                    
                                                    if ($link['is_trusted']) {
                                                        // For trusted domains, check for unnecessary attributes
                                                        if ($link['has_nofollow'] || $link['has_noopener']) {
                                                            $unnecessary_attrs[] = 'rel="nofollow noopener"';
                                                        }
                                                    } else {
                                                        // For non-trusted domains, check for missing attributes
                                                        if (!$link['has_nofollow']) $missing_attrs[] = 'nofollow';
                                                        if (!$link['has_noopener']) $missing_attrs[] = 'noopener';
                                                    }
                                                    
                                                    if (!$link['has_target_blank']) $missing_attrs[] = 'target="_blank"';
                                                ?>
                                                    <li>
                                                        <a href="<?php echo esc_url($link['url']); ?>" target="_blank">
                                                            <?php echo esc_html($link['url']); ?>
                                                        </a>
                                                        <?php if (!empty($missing_attrs)): ?>
                                                            <span class="mm-missing-attrs">
                                                                Missing: <?php echo esc_html(implode(', ', $missing_attrs)); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($unnecessary_attrs)): ?>
                                                            <span class="mm-unnecessary-attrs">
                                                                Unnecessary: <?php echo esc_html(implode(', ', $unnecessary_attrs)); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                        <td>
                                            <?php if ($group['needs_update']): ?>
                                                <span style="color: #d63638;">⚠ Needs Update</span>
                                            <?php else: ?>
                                                <span style="color: #00a32a;">✓ Updated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($group['needs_update']): ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">
                                                    <input type="submit" name="update_links" class="button button-secondary" value="Update Links">
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
                <script>
                jQuery(document).ready(function($) {
                    // Select all checkbox functionality
                    $('#select-all-posts').on('change', function() {
                        $('.post-checkbox').prop('checked', $(this).prop('checked'));
                    });

                    // Update select all checkbox state when individual checkboxes change
                    $('.post-checkbox').on('change', function() {
                        var allChecked = $('.post-checkbox:checked').length === $('.post-checkbox').length;
                        $('#select-all-posts').prop('checked', allChecked);
                    });
                });
                </script>
                <style>
                    .mm-table-responsive {
                        overflow-x: auto;
                        margin: 20px 0;
                        max-width: 100%;
                    }
                    .mm-table-responsive table {
                        width: 100%;
                        border-collapse: collapse;
                        table-layout: fixed;
                    }
                    .mm-table-responsive th,
                    .mm-table-responsive td {
                        padding: 12px;
                        border: 1px solid #e5e5e5;
                        vertical-align: top;
                    }
                    .mm-table-responsive th.check-column,
                    .mm-table-responsive td.check-column {
                        width: 30px;
                        text-align: center;
                    }
                    .mm-table-responsive th:nth-child(2),
                    .mm-table-responsive td:nth-child(2) {
                        width: 25%;
                    }
                    .mm-table-responsive th:nth-child(3),
                    .mm-table-responsive td:nth-child(3) {
                        width: 55%;
                    }
                    .mm-table-responsive th:nth-child(4),
                    .mm-table-responsive td:nth-child(4) {
                        width: 10%;
                    }
                    .mm-table-responsive th:nth-child(5),
                    .mm-table-responsive td:nth-child(5) {
                        width: 10%;
                        padding-right: 16px;
                    }
                    .tablenav {
                        margin: 6px 0 4px;
                        padding: 4px;
                    }
                    .tablenav .actions {
                        padding: 0 8px 0 0;
                    }
                    .tablenav .button {
                        margin-right: 8px;
                    }
                </style>
            <?php else: ?>
                <p>No external links found.</p>
            <?php endif; ?>
        </div>
        <?php
    } else {
        ?>
        <div class="wrap">
            <h1>External Link Finder</h1>
            <p class="description">This plugin scans your posts for external links and helps you manage them. It automatically adds <code>rel="nofollow noopener"</code> and <code>target="_blank"</code> attributes to external links, except for trusted domains. You can manage trusted domains in the <a href="<?php echo admin_url('admin.php?page=mm-trusted-domains'); ?>">Trusted Domains</a> settings.</p>
            <form method="post">
                <input type="submit" name="scan_links" class="button button-primary" value="Scan for External Links">
            </form>
        </div>
        <?php
    }
}

// Function to update links in a post
function mm_update_post_links($post_id) {
    $post = get_post($post_id);
    if (!$post) return false;

    $content = $post->post_content;
    $site_url = get_site_url();
    $site_domain = parse_url($site_url, PHP_URL_HOST);
    $trusted_domains = mm_get_trusted_domains();
    $updated = false;

    // Find all links in the content
    preg_match_all('/<a[^>]+href=([\'"])(.*?)\1[^>]*>/i', $content, $matches, PREG_SET_ORDER);

    if (!empty($matches)) {
        foreach ($matches as $match) {
            $full_tag = $match[0];
            $url = $match[2];
            $url_domain = parse_url($url, PHP_URL_HOST);
            
            // Check if the link is external
            if ($url_domain && $url_domain !== $site_domain) {
                $is_trusted = false;
                foreach ($trusted_domains as $trusted_domain) {
                    if (strpos($url_domain, $trusted_domain) !== false) {
                        $is_trusted = true;
                        break;
                    }
                }

                // Create new tag
                if ($is_trusted) {
                    // For trusted domains, only add target="_blank"
                    $new_tag = preg_replace('/\s+rel=["\']nofollow(?:\s+noopener)?["\']/', '', $full_tag);
                    $new_tag = preg_replace('/\s+rel=["\']noopener(?:\s+nofollow)?["\']/', '', $new_tag);
                    $new_tag = str_replace('<a', '<a target="_blank"', $new_tag);
                } else {
                    // For non-trusted domains, add both nofollow and noopener
                    $new_tag = preg_replace('/\s+rel=["\']nofollow(?:\s+noopener)?["\']/', '', $full_tag);
                    $new_tag = preg_replace('/\s+rel=["\']noopener(?:\s+nofollow)?["\']/', '', $full_tag);
                    $new_tag = str_replace('<a', '<a rel="nofollow noopener" target="_blank"', $new_tag);
                }
                
                // Replace the old tag with the new one
                $content = str_replace($full_tag, $new_tag, $content);
                $updated = true;
            }
        }
    }

    if ($updated) {
        // Temporarily disable post revisions
        remove_action('post_updated', 'wp_save_post_revision');
        
        // Update the post
        $update_args = array(
            'ID' => $post_id,
            'post_content' => $content
        );
        
        // Add post type if it's not a standard post
        if ($post->post_type !== 'post') {
            $update_args['post_type'] = $post->post_type;
        }
        
        $result = wp_update_post($update_args, true);
        
        // Re-enable post revisions
        add_action('post_updated', 'wp_save_post_revision');
        
        if (is_wp_error($result)) {
            return false;
        }
        return true;
    }

    return false;
}

// Function to scan posts for external links
function mm_scan_for_external_links() {
    $external_links = array();
    $post_types = array('listings', 'buildings', 'post', 'page');
    $site_url = get_site_url();
    $site_domain = parse_url($site_url, PHP_URL_HOST);
    $trusted_domains = mm_get_trusted_domains();

    // Get all posts of specified types
    $args = array(
        'post_type' => $post_types,
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post_title = get_the_title();
            $post_type = get_post_type();
            $content = get_the_content();

            // Find all links in the content
            preg_match_all('/<a[^>]+href=([\'"])(.*?)\1[^>]*>/i', $content, $matches, PREG_SET_ORDER);

            if (!empty($matches)) {
                foreach ($matches as $match) {
                    $full_tag = $match[0];
                    $url = $match[2];
                    $url_domain = parse_url($url, PHP_URL_HOST);
                    
                    // Check if the link is external
                    if ($url_domain && $url_domain !== $site_domain) {
                        // Check if domain is trusted
                        $is_trusted = false;
                        foreach ($trusted_domains as $trusted_domain) {
                            if (strpos($url_domain, $trusted_domain) !== false) {
                                $is_trusted = true;
                                break;
                            }
                        }

                        // Check for required attributes
                        $has_nofollow = strpos($full_tag, 'rel="nofollow"') !== false || 
                                      strpos($full_tag, "rel='nofollow'") !== false ||
                                      strpos($full_tag, 'rel="nofollow noopener"') !== false ||
                                      strpos($full_tag, "rel='nofollow noopener'") !== false;
                        $has_noopener = strpos($full_tag, 'rel="noopener"') !== false || 
                                      strpos($full_tag, "rel='noopener'") !== false ||
                                      strpos($full_tag, 'rel="nofollow noopener"') !== false ||
                                      strpos($full_tag, "rel='nofollow noopener'") !== false;
                        $has_target_blank = strpos($full_tag, 'target="_blank"') !== false || strpos($full_tag, "target='_blank'") !== false;

                        $external_links[] = array(
                            'post_id' => $post_id,
                            'post_title' => $post_title,
                            'post_type' => $post_type,
                            'url' => $url,
                            'has_nofollow' => $has_nofollow,
                            'has_noopener' => $has_noopener,
                            'has_target_blank' => $has_target_blank,
                            'is_trusted' => $is_trusted
                        );
                    }
                }
            }
        }
    }

    wp_reset_postdata();
    return $external_links;
}

// Handle bulk updates
if (isset($_POST['bulk_update_links']) && !empty($_POST['post_ids'])) {
    $post_ids = array_map('intval', $_POST['post_ids']);
    $updated_count = 0;
    
    foreach ($post_ids as $post_id) {
        if (mm_update_post_links($post_id)) {
            $updated_count++;
        }
    }
    
    if ($updated_count > 0) {
        echo '<div class="notice notice-success"><p>' . 
             sprintf(_n('%d post updated successfully.', '%d posts updated successfully.', $updated_count, 'mm-external-link-finder'), $updated_count) . 
             '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>No posts were updated.</p></div>';
    }
}
