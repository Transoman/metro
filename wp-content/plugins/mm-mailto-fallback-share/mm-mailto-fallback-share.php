<?php
/*
 * Plugin Name: MM Email Noscript Fallback
 * Description: Adds a <noscript> mailto fallback for Cloudflare email obfuscation links.
 * Version: 1.0.1
 * Author: Nemanja Tanaskovic
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Inject <noscript> mailto fallback in footer for specified email addresses.
 */
function efsf_inject_noscript_fallback() {
  // Collect emails: WordPress admin + any additional addresses
  $emails = array();
  $admin_email = get_option('admin_email');
  if ($admin_email) {
    $emails[] = $admin_email;
  }
  // Add site info address (customize as needed)
  $emails[] = 'info@metro-manhattan.com';
  // Remove duplicates and sanitize
  $emails = array_unique(array_map('sanitize_email', $emails));
  
  if (empty($emails)) {
    return;
  }
  
  echo "\n<noscript>\n<ul class=\"efsf-email-fallback-list\" style=\"display:none;\">\n";
  foreach ($emails as $email) {
    $email_attr = esc_attr($email);
    echo "    <li><a href=\"mailto:{$email_attr}\">{$email_attr}</a></li>\n";
  }
  echo "</ul>\n</noscript>\n";
}
add_action('wp_footer', 'efsf_inject_noscript_fallback', 5);

/**
 * Fix the "Share via Email" links in single posts by replacing them with proper mailto links.
 *
 * Looks for <a> tags with class "share-via-email" and updates href attribute.
 */
function efsf_fix_share_email_links($content) {
  if (!is_singular('post')) {
    return $content;
  }
  
  global $post;
  $subject = rawurlencode(get_the_title($post));
  $body_text = get_the_title($post) . "\n\n" . get_permalink($post);
  $body = rawurlencode($body_text);
  
  // Regex pattern to match anchor tags with class="share-via-email"
  $pattern = '/<a([^>]*class=["\'][^"\']*share-via-email[^"\']*["\'][^>]*)>(.*?)<\/a>/i';
  $replacement = '<a$1 href="mailto:?subject=' . $subject . '&body=' . $body . '" target="_blank" rel="noopener noreferrer">$2</a>';
  
  return preg_replace($pattern, $replacement, $content);
}
add_filter('the_content', 'efsf_fix_share_email_links', 20);
