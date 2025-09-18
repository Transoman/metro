<?php
/**
 * Plugin Name: OpenAI Internal Linking Recommender
 * Description: Uses OpenAI embeddings to suggest relevant internal links for posts
 * Version: 1.0.8
 * Author: Nemanja Tanaskovic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define OpenAI API key constant if not already defined
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY_HERE');
}

// Add admin menu item under Tools
add_action('admin_menu', function() {
    add_management_page(
        'Internal Link Recommendations', 
        'Internal Links AI',
        'manage_options',
        'internal-links-ai',
        'render_internal_links_page'
    );
});

// Define supported post types
function get_supported_post_types() {
    return apply_filters('internal_links_supported_post_types', array(
        'post',
        'buildings',
        'listings',
        'page'
    ));
}

// Add meta box to post editor
add_action('add_meta_boxes', function() {
    $post_types = get_supported_post_types();
    foreach ($post_types as $post_type) {
        add_meta_box(
            'internal_links_recommender',
            'Internal Link Recommendations',
            'render_internal_links_meta_box',
            $post_type,
            'side',
            'high'
        );
    }
});

// Enqueue admin scripts and styles
function internal_links_ai_admin_assets($hook) {
    // Only enqueue on post editor and our custom page
    if (!in_array($hook, array('post.php', 'post-new.php', 'tools_page_internal-links-ai'), true)) {
        return;
    }
    
    // Get plugin directory URL
    $plugin_url = plugin_dir_url(__FILE__);
    
    // Enqueue admin JS
    wp_enqueue_script(
        'internal-links-ai',
        $plugin_url . 'js/admin.js',
        array('jquery'),
        filemtime(plugin_dir_path(__FILE__) . 'js/admin.js'),
        true
    );
    
    // Localize the script with new data
    wp_localize_script(
        'internal-links-ai',
        'internalLinksAi',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('internal-links-ai-nonce')
        )
    );

    // Enqueue admin CSS
    wp_enqueue_style(
        'internal-links-ai',
        $plugin_url . 'css/admin.css',
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'css/admin.css')
    );
}
add_action('admin_enqueue_scripts', 'internal_links_ai_admin_assets');

// Render admin page
function render_internal_links_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get all posts excluding recommended and high quality articles
    $excluded_posts = get_excluded_post_ids();
    
    $posts = get_posts(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'post__not_in' => $excluded_posts
    ));
    
    ?>
    <div class="wrap">
        <h1>Internal Link Recommendations</h1>
        
        <select id="post-selector">
            <option value="">Select a post...</option>
            <?php foreach ($posts as $post): ?>
                <option value="<?php echo esc_attr($post->ID); ?>">
                    <?php echo esc_html($post->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button id="generate-links" class="button button-primary">
            Generate Internal Links
        </button>
        
        <div id="recommendations-container">
            <div id="loading" style="display:none;">
                <p>Generating recommendations...</p>
            </div>
            <div id="results">
                <h3>Recommended Links:</h3>
                <ul id="recommendation-list"></ul>
            </div>
        </div>
    </div>
    <?php
}

// Render meta box content
function render_internal_links_meta_box($post) {
    ?>
    <div id="internal-links-recommender">
        <div id="loading" style="display:none;">
            <p>Generating recommendations...</p>
        </div>
        <div id="results">
            <ul id="recommendation-list"></ul>
        </div>
    </div>
    <?php
}

// Get excluded post IDs from recommended and high quality articles
function get_excluded_post_ids() {
    global $wpdb;
    
    $excluded_posts = array();
    
    // Get recommended articles
    $recommended_results = $wpdb->get_col($wpdb->prepare(
        "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s",
        '_generated_recommended_articles'
    ));
    
    // Get high quality articles
    $high_quality_results = $wpdb->get_col($wpdb->prepare(
        "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s",
        '_generated_high_quality_articles'
    ));
    
    // Process results
    foreach (array_merge($recommended_results, $high_quality_results) as $meta_value) {
        $post_ids = maybe_unserialize($meta_value);
        if (is_array($post_ids)) {
            $excluded_posts = array_merge($excluded_posts, $post_ids);
        }
    }
    
    return array_unique($excluded_posts);
}

// Function to get existing internal links from post content
function get_existing_internal_links($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return array();
    }
    $content = $post->post_content;
    $existing_links = array();
    $site_url = home_url();
    // Match all links in the content
    if (preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>/i', $content, $matches)) {
        foreach ($matches[1] as $url) {
            // Only consider internal links
            if (strpos($url, $site_url) === 0) {
                $existing_links[] = $url;
            } elseif (strpos($url, '/') === 0) {
                // Convert relative to absolute
                $existing_links[] = rtrim($site_url, '/') . $url;
            }
        }
    }
    return array_unique($existing_links);
}

// Function to get post type specific rules
function get_post_type_rules($post_type) {
    $rules = array(
        'buildings' => array(
            'priority_links' => array('page', 'listings'), // Prioritize neighborhood pages and listings
            'min_links' => 3,
            'max_links' => 8
        ),
        'listings' => array(
            'priority_links' => array('buildings', 'page'), // Prioritize buildings and neighborhood pages
            'min_links' => 2,
            'max_links' => 6
        ),
        'page' => array(
            'priority_links' => array('buildings', 'listings'), // Prioritize buildings and listings
            'min_links' => 2,
            'max_links' => 6
        ),
        'post' => array(
            'priority_links' => array('buildings', 'listings', 'page'), // Prioritize all content types
            'min_links' => 3,
            'max_links' => 10
        )
    );
    
    return isset($rules[$post_type]) ? $rules[$post_type] : $rules['post'];
}

// Function to get page type (for pages that are resources or neighborhoods)
function get_page_type($page_id) {
    $page_type = get_post_meta($page_id, '_page_type', true);
    if (!$page_type) {
        // Try to determine from content or title
        $post = get_post($page_id);
        if (stripos($post->post_title, 'neighborhood') !== false || 
            stripos($post->post_content, 'neighborhood') !== false) {
            return 'neighborhood';
        } elseif (stripos($post->post_title, 'resource') !== false || 
                 stripos($post->post_content, 'resource') !== false) {
            return 'resource';
        }
    }
    return $page_type;
}

// Function to calculate recommended number of internal links
function calculate_recommended_links($content, $post_type) {
    $rules = get_post_type_rules($post_type);
    
    // Get word count based recommendation
    $word_count = str_word_count(strip_tags($content));
    $word_based = ceil($word_count / 250);
    
    // Use post type specific limits
    return max($rules['min_links'], min($rules['max_links'], $word_based));
}

// Function to analyze anchor text distribution
function analyze_anchor_text_distribution($content) {
    $distribution = array(
        'exact_match' => 0,
        'partial_match' => 0,
        'branded' => 0,
        'generic' => 0
    );
    
    if (preg_match_all('/<a[^>]+>(.*?)<\/a>/i', $content, $matches)) {
        foreach ($matches[1] as $anchor) {
            $anchor = trim($anchor);
            
            // Check for exact match (contains property name or exact location)
            if (preg_match('/\b(manhattan|building|apartment|condo|coop)\b/i', $anchor)) {
                $distribution['exact_match']++;
            }
            // Check for partial match (contains location or property type)
            elseif (preg_match('/\b(real estate|property|residential|commercial)\b/i', $anchor)) {
                $distribution['partial_match']++;
            }
            // Check for branded (contains Metro Manhattan)
            elseif (preg_match('/metro manhattan/i', $anchor)) {
                $distribution['branded']++;
            }
            // Generic anchors
            else {
                $distribution['generic']++;
            }
        }
    }
    
    return $distribution;
}

// Function to get recommended anchor text distribution
function get_recommended_anchor_distribution() {
    return array(
        'exact_match' => 0.4,  // 40%
        'partial_match' => 0.3, // 30%
        'branded' => 0.2,       // 20%
        'generic' => 0.1        // 10%
    );
}

// Function to check if a post is cornerstone content
function is_cornerstone_content($post_id) {
    // Check Yoast SEO cornerstone flag if available
    if (class_exists('WPSEO_Meta')) {
        $is_cornerstone = WPSEO_Meta::get_value('is_cornerstone', $post_id);
        if ($is_cornerstone === '1') {
            return true;
        }
    }
    
    // Check custom meta field as fallback
    return get_post_meta($post_id, '_is_cornerstone_content', true) === '1';
}

// Function to get cornerstone content
function get_cornerstone_content($post_type = null) {
    $args = array(
        'post_type' => $post_type ? $post_type : get_supported_post_types(),
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_is_cornerstone_content',
                'value' => '1'
            )
        )
    );
    
    // Add Yoast SEO cornerstone query if available
    if (class_exists('WPSEO_Meta')) {
        $args['meta_query'][] = array(
            'key' => WPSEO_Meta::$meta_prefix . 'is_cornerstone',
            'value' => '1'
        );
    }
    
    return get_posts($args);
}

// Function to prioritize cornerstone content in recommendations
function prioritize_cornerstone_content($posts, $current_post_id) {
    $cornerstone_posts = array();
    $regular_posts = array();
    
    foreach ($posts as $post) {
        if ($post->ID === $current_post_id) {
            continue;
        }
        
        if (is_cornerstone_content($post->ID)) {
            $cornerstone_posts[] = $post;
        } else {
            $regular_posts[] = $post;
        }
    }
    
    return array_merge($cornerstone_posts, $regular_posts);
}

// AJAX handler for generating recommendations
add_action('wp_ajax_generate_internal_links', function() {
    check_ajax_referer('internal-links-ai-nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }
    
    // Get current post
    $current_post = get_post($post_id);
    if (!$current_post) {
        wp_send_json_error('Post not found');
    }
    
    $post_type = get_post_type($post_id);
    $rules = get_post_type_rules($post_type);
    
    // For pages, get the specific type
    if ($post_type === 'page') {
        $page_type = get_page_type($post_id);
        if ($page_type === 'neighborhood') {
            $rules['priority_links'] = array('buildings', 'listings');
            $rules['min_links'] = 4;
            $rules['max_links'] = 10;
        } elseif ($page_type === 'resource') {
            $rules['priority_links'] = array('buildings', 'listings', 'page');
            $rules['min_links'] = 2;
            $rules['max_links'] = 6;
        }
    }
    
    // Calculate recommended links based on post type
    $recommended_links = calculate_recommended_links($current_post->post_content, $post_type);
    
    // Get existing links and analyze distribution
    $existing_links = get_existing_internal_links($post_id);
    $current_links = count($existing_links);
    $anchor_distribution = analyze_anchor_text_distribution($current_post->post_content);
    
    // Get current post embedding
    $current_embedding = get_post_meta($post_id, 'openai_embedding', true);
    
    if (!$current_embedding) {
        wp_send_json_error('No embedding found for this post');
    }
    
    // Get excluded post IDs
    $excluded_posts = get_excluded_post_ids();
    $excluded_posts[] = $post_id; // Add current post to exclusions
    
    // Get all posts with embeddings
    $args = array(
        'post_type' => get_supported_post_types(),
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'post__not_in' => array_merge($excluded_posts, array($post_id)),
        'meta_query' => array(
            array(
                'key' => 'openai_embedding',
                'compare' => 'EXISTS'
            )
        )
    );
    
    $posts = get_posts($args);
    
    // Prioritize cornerstone content
    $posts = prioritize_cornerstone_content($posts, $post_id);
    
    $highest_similarity = 0;
    $best_post = null;
    
    // Find the most similar post
    foreach ($posts as $post) {
        // Skip if this post is already linked
        $post_url = get_permalink($post->ID); // Always absolute
        if (in_array($post_url, $existing_links)) {
            continue;
        }
        
        $embedding = get_post_meta($post->ID, 'openai_embedding', true);
        $similarity = calculate_cosine_similarity($current_embedding, $embedding);
        
        if ($similarity > $highest_similarity) {
            $highest_similarity = $similarity;
            $best_post = $post;
        }
    }
    
    $similarities = array();
    
    // Only process the best post if it has relevant content
    if ($best_post && preg_match('/(neighborhood|square\s+foot(age)?)/i', $best_post->post_content)) {
        // Find the best paragraph to insert the link
        $paragraphs = explode("\n", $current_post->post_content);
        
        // Filter out empty paragraphs and normalize content
        $filtered_paragraphs = array();
        foreach ($paragraphs as $index => $paragraph) {
            $clean_paragraph = trim(strip_tags($paragraph));
            if (strlen($clean_paragraph) >= 50) {
                $filtered_paragraphs[] = array(
                    'original_index' => $index,
                    'content' => $paragraph,
                    'clean_content' => $clean_paragraph
                );
            }
        }
        
        $best_paragraph = '';
        $best_paragraph_index = -1;
        $max_keyword_density = 0;
        
        foreach ($filtered_paragraphs as $item) {
            $keyword_count = preg_match_all('/(neighborhood|square\s+foot(age)?)/i', $item['clean_content']);
            $density = $keyword_count / (str_word_count($item['clean_content']) ?: 1);
            
            if ($density > $max_keyword_density) {
                $max_keyword_density = $density;
                $best_paragraph = $item['content'];
                $best_paragraph_index = $item['original_index'];
            }
        }
        
        // Get context paragraphs
        $context = array();
        $context_paragraphs = array();
        for ($i = max(0, $best_paragraph_index - 1); $i <= min(count($paragraphs) - 1, $best_paragraph_index + 1); $i++) {
            $context[] = $paragraphs[$i];
            $context_paragraphs[] = array(
                'text' => $paragraphs[$i],
                'index' => $i,
                'is_target' => ($i === $best_paragraph_index)
            );
        }
        
        // Create a more natural link placement using AI
        $paragraph_text = $best_paragraph;
        $link_title = $best_post->post_title;
        $link_url = get_permalink($best_post->ID);
        
        // Modify the OpenAI prompt to include anchor text guidelines
        $prompt = "Given the following content from a " . $post_type . ":\n\n" . 
                 $paragraph_text . "\n\n" .
                 "Here is the full content for context:\n\n" .
                 $best_post->post_content . "\n\n" .
                 "And this content that should be linked: " . $link_title . "\n\n" .
                 "Your task is to:\n" .
                 "1. Analyze the paragraph and identify opportunities to make it more natural and engaging\n" .
                 "2. Make subtle improvements to the text while maintaining the core message\n" .
                 "3. Insert a natural, contextual link to the content\n" .
                 "4. Ensure the link placement feels organic and enhances the reader's experience\n" .
                 "5. Use appropriate anchor text following these guidelines:\n" .
                 "   - 40% exact match (e.g., 'Manhattan real estate')\n" .
                 "   - 30% partial match (e.g., 'luxury apartments in Manhattan')\n" .
                 "   - 20% branded (e.g., 'Metro Manhattan')\n" .
                 "   - 10% generic (e.g., 'learn more')\n\n" .
                 "Return only the HTML with your improved paragraph and naturally integrated link.\n" .
                 "The changes should be subtle and preserve the original meaning and tone.";
        
        // Call OpenAI to generate natural link placement
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . OPENAI_API_KEY,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-4',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are an expert in natural language writing and internal linking. ' .
                                    'Your task is to create natural, human-like internal links that flow ' .
                                    'seamlessly with the content. The links should feel organic and relevant.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.7,
                'max_tokens' => 150
            )),
            'timeout' => 30,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            error_log('OpenAI API Error: ' . $response->get_error_message());
            $html_snippet = sprintf(
                '<p>%s <a href="%s" title="%s">%s</a></p>',
                $paragraph_text,
                esc_url($link_url),
                esc_attr($link_title),
                esc_html($link_title)
            );
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                
                if (isset($body['choices'][0]['message']['content'])) {
                    $html_snippet = $body['choices'][0]['message']['content'];
                    if (strpos($html_snippet, '<p>') === false) {
                        $html_snippet = '<p>' . $html_snippet . '</p>';
                    }
                } else {
                    $html_snippet = sprintf(
                        '<p>%s <a href="%s" title="%s">%s</a></p>',
                        $paragraph_text,
                        esc_url($link_url),
                        esc_attr($link_title),
                        esc_html($link_title)
                    );
                }
            } else {
                $html_snippet = sprintf(
                    '<p>%s <a href="%s" title="%s">%s</a></p>',
                    $paragraph_text,
                    esc_url($link_url),
                    esc_attr($link_title),
                    esc_html($link_title)
                );
            }
        }
        // Remove rel, target attributes for internal links
        $html_snippet = preg_replace('/<a([^>]+)( rel="[^"]*")([^>]*)>/i', '<a$1$3>', $html_snippet);
        $html_snippet = preg_replace('/<a([^>]+)( target="[^"]*")([^>]*)>/i', '<a$1$3>', $html_snippet);
        $html_snippet = preg_replace('/<a([^>]+)( rel=[^ >]+)([^>]*)>/i', '<a$1$3>', $html_snippet);
        
        $similarities[] = array(
            'post_id' => $best_post->ID,
            'title' => $best_post->post_title,
            'url' => get_permalink($best_post->ID),
            'score' => $highest_similarity,
            'excerpt' => wp_trim_words($best_post->post_content, 20),
            'context' => implode("\n\n", $context),
            'context_paragraphs' => $context_paragraphs,
            'target_paragraph_index' => $best_paragraph_index,
            'html_snippet' => $html_snippet,
            'should_replace' => $max_keyword_density > 0.3
        );
    }
    
    // Sort by similarity score
    usort($similarities, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    // Get top 5 recommendations
    $recommendations = array_slice($similarities, 0, 5);
    
    // Store recommendations in post meta for future reference
    update_post_meta($post_id, '_internal_link_recommendations', $recommendations);
    
    // Format recommendations for display
    $formatted_recommendations = array_map(function($rec) use ($current_post) {
        // Determine the type of change
        $change_type = $rec['should_replace'] ? 'replace' : 'insert';
        
        // Get the target paragraph text from the current post
        $current_paragraphs = explode("\n", $current_post->post_content);
        $target_paragraph = '';
        
        if (isset($current_paragraphs[$rec['target_paragraph_index']])) {
            $target_paragraph = $current_paragraphs[$rec['target_paragraph_index']];
        }
        
        // Create a clear description of the change
        // $change_description = $rec['should_replace']
        //     ? sprintf('Replace the following paragraph with new content including a link to "%s"', $rec['title'])
        //     : sprintf('Add new paragraph with link to "%s" after the following paragraph', $rec['title']);
        
        return array(
            'title' => $rec['title'],
            'url' => $rec['url'],
            'score' => round($rec['score'] * 100, 2) . '% match',
            'excerpt' => $rec['excerpt'],
            'context' => $rec['context'],
            'context_paragraphs' => $rec['context_paragraphs'],
            'target_paragraph_index' => $rec['target_paragraph_index'],
            'html_snippet' => $rec['html_snippet'],
            // 'change_description' => $change_description,
            'change_type' => $change_type,
            'target_paragraph_preview' => $target_paragraph
        );
    }, $recommendations);
    
    wp_send_json_success(array(
        'recommendations' => $formatted_recommendations,
        'link_stats' => array(
            'current' => $current_links,
            'recommended' => $recommended_links,
            'is_complete' => $current_links >= $recommended_links
        )
    ));
});

// Helper function to calculate cosine similarity
function calculate_cosine_similarity($vec1, $vec2) {
    $dot_product = 0;
    $norm1 = 0;
    $norm2 = 0;

    if ( is_string( $vec1 ) ) {
        $vec1 = json_decode( $vec1 );

        if ( ! is_array( $vec1 ) ) {
            $vec1 = (array) $vec1;
        }
    }

    if ( is_string( $vec2 ) ) {
        $vec2 = json_decode( $vec2 );

        if ( ! is_array( $vec2 ) ) {
            $vec2 = (array) $vec2;
        }
    }
    
    foreach ($vec1 as $i => $val1) {
        $dot_product += $val1 * $vec2[$i];
        $norm1 += $val1 * $val1;
        $norm2 += $vec2[$i] * $vec2[$i];
    }
    
    return $dot_product / (sqrt($norm1) * sqrt($norm2));
}

// AJAX handler for accepting recommendations
add_action('wp_ajax_accept_internal_link', function() {
    check_ajax_referer('internal-links-ai-nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    $snippet = wp_kses_post($_POST['snippet']);
    $paragraph_index = intval($_POST['paragraph_index']);
    
    if (!$post_id || !$snippet) {
        wp_send_json_error('Invalid parameters');
    }
    
    // Get current post content
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error('Post not found');
    }
    
    $content = $post->post_content;
    $paragraphs = explode("\n", $content);
    
    if ($paragraph_index >= 0 && $paragraph_index < count($paragraphs)) {
        // Replace the target paragraph
        $paragraphs[$paragraph_index] = $snippet;
    } else {
        // Append after the target paragraph
        array_splice($paragraphs, $paragraph_index, 0, $snippet);
    }
    
    // Update post content
    $updated = wp_update_post(array(
        'ID' => $post_id,
        'post_content' => implode("\n", $paragraphs)
    ));
    
    if (is_wp_error($updated)) {
        wp_send_json_error($updated->get_error_message());
    }
    
    wp_send_json_success('Content updated successfully');
});

// Add cornerstone content meta box
add_action('add_meta_boxes', function() {
    $post_types = get_supported_post_types();
    foreach ($post_types as $post_type) {
        add_meta_box(
            'cornerstone_content',
            'Cornerstone Content',
            'render_cornerstone_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
});

// Render cornerstone meta box
function render_cornerstone_meta_box($post) {
    $is_cornerstone = is_cornerstone_content($post->ID);
    ?>
    <p>
        <label>
            <input type="checkbox" name="is_cornerstone_content" value="1" <?php checked($is_cornerstone); ?>>
            Mark as cornerstone content
        </label>
    </p>
    <p class="description">
        Cornerstone content is prioritized in internal linking recommendations.
    </p>
    <?php
}

// Save cornerstone content status
// add_action('save_post', function($post_id) {
//     if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
//         return;
//     }
    
//     if (!current_user_can('edit_post', $post_id)) {
//         return;
//     }
    
//     $is_cornerstone = isset($_POST['is_cornerstone_content']) ? '1' : '0';
//     update_post_meta($post_id, '_is_cornerstone_content', $is_cornerstone);
// });
