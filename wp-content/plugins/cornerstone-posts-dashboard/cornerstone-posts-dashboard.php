<?php
  /**
   * Plugin Name: Cornerstone Posts Dashboard
   * Description: Adds an admin page listing all Yoast-flagged cornerstone posts.
   * Version:     3.1
   * Author:      Nemanja Tanaskovic
   */
  add_action( 'admin_menu', 'cpd_add_admin_menu' );

  // Include Gemini suggestions handler
  require_once plugin_dir_path(__FILE__) . 'includes/gemini-suggestions.php';

  function cpd_add_admin_menu() {
    add_menu_page(
      'Cornerstone Posts',
      'Cornerstone Posts',
      'manage_options',
      'cornerstone-posts',
      'cpd_render_cornerstone_page',
      'dashicons-star-filled',
      25
    );
    
    add_submenu_page(
      null, // Hidden from menu
      'Post Suggestions',
      'Post Suggestions',
      'manage_options',
      'cornerstone-post-suggestions',
      'cpd_render_suggestions_page'
    );

    add_submenu_page(
      'cornerstone-posts',
      'Regenerate Suggestions',
      'Regenerate Suggestions',
      'manage_options',
      'cornerstone-regenerate-suggestions',
      'cpd_render_regenerate_page'
    );
  }
  
  function cpd_render_cornerstone_page() {
    echo '<div class="wrap"><h1>Cornerstone Posts</h1>';
    
    // Add regeneration link
    echo '<p><a href="' . admin_url('admin.php?page=cornerstone-regenerate-suggestions') . '" class="button button-primary">🔄 Manage & Regenerate Suggestions</a></p>';
    
    global $wpdb;
    $total_posts = get_posts([
      'post_type' => 'post',
      'posts_per_page' => -1,
      'fields' => 'ids'
    ]);
    $total_count = count($total_posts);
    $table_name = $wpdb->prefix . 'cpd_gemini_suggestions';
    $posts_with_suggestions = $wpdb->get_col("SELECT DISTINCT post_id FROM $table_name");
    $with_suggestions_count = count($posts_with_suggestions);
    $without_suggestions_count = $total_count - $with_suggestions_count;

    // echo '<div class="notice notice-info">';
    // echo '<p>Posts with generated Gemini suggestions: <strong>' . $with_suggestions_count . '</strong></p>';
    // echo '<p>Posts without Gemini suggestions: <strong>' . $without_suggestions_count . '</strong></p>';
    // echo '</div>';
    
    $cornerstone_posts = get_posts([
      'post_type' => 'post',
      'posts_per_page' => -1,
      'meta_key' => '_yoast_wpseo_is_cornerstone',
      'meta_value' => '1',
      'fields' => 'ids'
    ]);

    $regular_posts = get_posts([
      'post_type' => 'post',
      'posts_per_page' => -1,
      'meta_query' => [
        [
          'key' => '_yoast_wpseo_is_cornerstone',
          'compare' => 'NOT EXISTS'
        ]
      ]
    ]);

    $posts_without_cornerstone_links = [];
    $posts_with_cornerstone_links = [];
    $posts_with_suggestions = [];

    $cornerstone_embeddings = [];
    foreach ($cornerstone_posts as $cornerstone_id) {
      $embedding = get_post_meta($cornerstone_id, 'openai_embedding', true);
      if (!empty($embedding)) {
        if (is_string($embedding)) {
          $embedding = json_decode($embedding, true);
        }
        if (is_array($embedding)) {
          $cornerstone_embeddings[$cornerstone_id] = $embedding;
        }
      }
    }

    foreach ($regular_posts as $post) {
      $content = $post->post_content;
      $has_cornerstone_link = false;
      $linked_cornerstones = [];
      $post_embedding = get_post_meta($post->ID, 'openai_embedding', true);
      if (is_string($post_embedding)) {
        $post_embedding = json_decode($post_embedding, true);
      }

      foreach ($cornerstone_posts as $cornerstone_id) {
        $cornerstone_url = get_permalink($cornerstone_id);
        if (strpos($content, $cornerstone_url) !== false) {
          $has_cornerstone_link = true;
          $linked_cornerstones[] = get_the_title($cornerstone_id);
        }
      }

      $similarity_scores = [];
      if (!empty($post_embedding) && !empty($cornerstone_embeddings)) {
        foreach ($cornerstone_embeddings as $cornerstone_id => $cornerstone_embedding) {
          $similarity = orc_compute_cosine_similarity($post_embedding, $cornerstone_embedding);
          if ($similarity > 0.7) {
            $similarity_scores[$cornerstone_id] = $similarity;
          }
        }
        arsort($similarity_scores);
      }

      if ($has_cornerstone_link) {
        $posts_with_cornerstone_links[] = [
          'post' => $post,
          'linked_to' => $linked_cornerstones
        ];
      } else {
        if (!empty($similarity_scores)) {
          $posts_with_suggestions[] = [
            'post' => $post,
            'suggestions' => array_slice($similarity_scores, 0, 3, true)
          ];
        } else {
          $posts_without_cornerstone_links[] = $post;
        }
      }
    }

    if (!empty($posts_without_cornerstone_links) || !empty($posts_with_suggestions)) {
      echo '<div class="notice notice-warning">';
      echo '<h2>Posts Without Cornerstone Links</h2>';
      
      if (!empty($posts_with_suggestions)) {
        // echo '<h3>Posts with AI Suggestions</h3>';
        echo '<ul>';
        foreach ($posts_with_suggestions as $data) {
          $post = $data['post'];
          $edit_link = get_edit_post_link($post->ID);
          echo '<li style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">';
          echo '<div>';
          echo '<a href="' . esc_url($edit_link) . '">' . esc_html($post->post_title) . '</a> <small>(' . get_the_date('', $post) . ')</small>';
          echo '<br><small>Suggested cornerstone posts:</small>';
          echo '<ul style="margin-top:0; background: #f6fafd; border: 1px solid #b3d8f1; border-radius: 6px; padding: 10px 18px;">';
          foreach ($data['suggestions'] as $cornerstone_id => $score) {
            $cornerstone_title = get_the_title($cornerstone_id);
            $cornerstone_public_link = get_permalink($cornerstone_id);
            echo '<li style="margin-bottom: 6px; font-size: 1.05em;">';
            echo '<a href="' . esc_url($cornerstone_public_link) . '" target="_blank" style="color: #21759b; text-decoration: underline; font-weight: 500;">' . esc_html($cornerstone_title) . '</a>';
            echo '</li>';
          }
          echo '</ul>';
          echo '</div>';
          echo '<a href="' . admin_url('admin.php?page=cornerstone-post-suggestions&post_id=' . $post->ID) . '" class="button">See suggestions</a>';
          echo '</li>';
          echo '<hr>';
        }
        echo '</ul>';
      }

      if (!empty($posts_without_cornerstone_links)) {
        echo '<h3>Posts Without Suggestions</h3>';
        echo '<ul>';
        foreach ($posts_without_cornerstone_links as $post) {
          $edit_link = get_edit_post_link($post->ID);
          echo '<li><a href="' . esc_url($edit_link) . '">' . esc_html($post->post_title) . '</a> <small>(' . get_the_date('', $post) . ')</small></li>';
        }
        echo '</ul>';
      }
      echo '</div>';
    }

    if (!empty($posts_with_cornerstone_links)) {
      echo '<div class="notice notice-success">';
      echo '<h2>Posts With Cornerstone Links</h2>';
      echo '<p>The following posts have internal links to cornerstone articles:</p>';
      echo '<ul>';
      foreach ($posts_with_cornerstone_links as $data) {
        $post = $data['post'];
        $edit_link = get_edit_post_link($post->ID);
        echo '<li>';
        echo '<a href="' . esc_url($edit_link) . '">' . esc_html($post->post_title) . '</a> <small>(' . get_the_date('', $post) . ')</small>';
        echo '<br><small>Links to: ' . esc_html(implode(', ', $data['linked_to'])) . '</small>';
        echo '</li>';
      }
      echo '</ul>';
      echo '</div>';
    }
    
    echo '<h2>Cornerstone Posts</h2>';
    $cornerstones = get_posts( [
      'post_type'      => 'post',
      'posts_per_page' => - 1,
      'meta_key'       => '_yoast_wpseo_is_cornerstone',
      'meta_value'     => '1',
      'orderby'        => 'date',
      'order'          => 'DESC',
    ] );
    
    if ( $cornerstones ) {
      echo '<ul>';
      foreach ( $cornerstones as $post ) {
        $title     = get_the_title( $post );
        $edit_link = get_edit_post_link( $post->ID );
        echo '<li><a href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a> <small>(' . get_the_date( '', $post ) . ')</small></li>';
      }
      echo '</ul>';
    }
    
    echo '</div>';
  }

  function cpd_render_suggestions_page() {
    if (!isset($_GET['post_id'])) {
      wp_die('No post ID provided');
    }
    
    $post_id = intval($_GET['post_id']);
    $post = get_post($post_id);
    
    if (!$post) {
      wp_die('Post not found');
    }

    // Handle regeneration request
    if (isset($_POST['regenerate_suggestions']) && wp_verify_nonce($_POST['_wpnonce'], 'regenerate_suggestions_' . $post_id)) {
      $cornerstone_id = intval($_POST['cornerstone_id']);
      $gemini = new CPD_Gemini_Suggestions();
      
      // Clear existing cache for this post-cornerstone combination
      global $wpdb;
      $table_name = $wpdb->prefix . 'cpd_gemini_suggestions';
      $wpdb->delete($table_name, [
        'post_id' => $post_id,
        'cornerstone_id' => $cornerstone_id
      ]);
      
      // Redirect to refresh the page
      wp_redirect(add_query_arg(['regenerated' => '1'], $_SERVER['REQUEST_URI']));
      exit;
    }
    
    echo '<div class="wrap">';
    echo '<h1>Suggestions for: ' . esc_html($post->post_title) . '</h1>';
    echo '<p><a href="' . admin_url('admin.php?page=cornerstone-posts') . '" class="button">← Back to Cornerstone Posts</a></p>';
    
    // Show success message if regenerated
    if (isset($_GET['regenerated'])) {
      echo '<div class="notice notice-success"><p>Suggestions have been regenerated successfully!</p></div>';
    }
    
    $post_embedding = get_post_meta($post_id, 'openai_embedding', true);
    if (is_string($post_embedding)) {
      $post_embedding = json_decode($post_embedding, true);
    }
    
    $cornerstone_posts = get_posts([
      'post_type' => 'post',
      'posts_per_page' => -1,
      'meta_key' => '_yoast_wpseo_is_cornerstone',
      'meta_value' => '1',
      'fields' => 'ids'
    ]);
    
    $cornerstone_embeddings = [];
    foreach ($cornerstone_posts as $cornerstone_id) {
      $embedding = get_post_meta($cornerstone_id, 'openai_embedding', true);
      if (!empty($embedding)) {
        if (is_string($embedding)) {
          $embedding = json_decode($embedding, true);
        }
        if (is_array($embedding)) {
          $cornerstone_embeddings[$cornerstone_id] = $embedding;
        }
      }
    }
    
    if (!empty($post_embedding) && !empty($cornerstone_embeddings)) {
      $similarity_scores = [];
      foreach ($cornerstone_embeddings as $cornerstone_id => $cornerstone_embedding) {
        $similarity = orc_compute_cosine_similarity($post_embedding, $cornerstone_embedding);
        $similarity_scores[$cornerstone_id] = $similarity;
      }
      arsort($similarity_scores);
      
      // Get top 3 suggestions
      $top_suggestions = array_slice($similarity_scores, 0, 3, true);
      
      echo '<div class="notice notice-info">';
      echo '<h2>Top 3 Suggested Cornerstone Posts</h2>';
      echo '<table class="wp-list-table widefat fixed striped">';
      echo '<thead><tr>';
      echo '<th>Cornerstone Post</th>';
      echo '<th>Similarity Score</th>';
      echo '</tr></thead><tbody>';
      
      foreach ($top_suggestions as $cornerstone_id => $score) {
        $cornerstone_title = get_the_title($cornerstone_id);
        $cornerstone_public_link = get_permalink($cornerstone_id);
        $cornerstone_edit_link = get_edit_post_link($cornerstone_id);
        
        echo '<tr>';
        echo '<td><a href="' . esc_url($cornerstone_public_link) . '" target="_blank">' . esc_html($cornerstone_title) . '</a></td>';
        echo '<td>' . number_format($score * 100, 2) . '%</td>';
        echo '</tr>';
      }
      
      echo '</tbody></table>';
      echo '</div>';

      // Generate paragraph suggestions using Gemini
      $gemini = new CPD_Gemini_Suggestions();
      
      echo '<div class="notice notice-info">';
      echo '<h2>AI-Generated Paragraph Suggestions</h2>';
      
      foreach ($top_suggestions as $cornerstone_id => $score) {
        $cornerstone_title = get_the_title($cornerstone_id);
        $cornerstone_url = get_permalink($cornerstone_id);
        
        echo '<div class="cornerstone-suggestions" style="margin-bottom: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
        echo '<h3>Suggestions for linking to: <a href="' . esc_url($cornerstone_url) . '" target="_blank">' . esc_html($cornerstone_title) . '</a></h3>';
        
        // Add regenerate button
        echo '<form method="post" style="margin-bottom: 15px;">';
        echo wp_nonce_field('regenerate_suggestions_' . $post_id, '_wpnonce', true, false);
        echo '<input type="hidden" name="cornerstone_id" value="' . $cornerstone_id . '">';
        echo '<button type="submit" name="regenerate_suggestions" class="button button-secondary" onclick="return confirm(\'Are you sure you want to regenerate suggestions for this cornerstone post?\')">🔄 Regenerate Suggestions</button>';
        echo '</form>';
        
        // Use cache-aware method
        $suggestions = $gemini->get_or_generate_suggestions($post->ID, $cornerstone_id, $post->post_content, $cornerstone_title, $cornerstone_url);
        
        if (isset($suggestions['error'])) {
          echo '<div class="notice notice-error">';
          echo '<p>Error generating suggestions: ' . esc_html($suggestions['error']) . '</p>';
          echo '</div>';
        } else {
          echo '<div class="suggestions-list">';
          foreach ($suggestions as $index => $suggestion) {
            echo '<div class="suggestion-item" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-left: 4px solid #2271b1;">';
            // Display the note as a styled note above the paragraph
            if (!empty($suggestion['note'])) {
              echo '<div class="suggestion-note" style="font-style: italic; color: #2271b1; margin-bottom: 8px;">🛈 ' . esc_html($suggestion['note']) . '</div>';
            }
            echo '<h4 style="margin-top: 0;">Suggestion ' . ($index + 1) . '</h4>';
            $html_paragraph = cpd_markdown_to_html_links($suggestion['paragraph']);
            echo '<div class="suggestion-content" style="margin-bottom: 10px;">' . wpautop($html_paragraph) . '</div>';
            // Use HTML for copy-to-clipboard
            $copy_html = str_replace('"', '&quot;', $html_paragraph); // Escape quotes for attribute
            echo '<button class="button button-primary copy-suggestion" data-suggestion-html="' . $copy_html . '">Copy to Clipboard</button>';
            echo '</div>';
          }
          echo '</div>';
        }
        
        echo '</div>';
      }
      
      echo '</div>';
      
      // Add JavaScript for copy functionality
      ?>
      <script>
      jQuery(document).ready(function($) {
          $('.copy-suggestion').on('click', function() {
              var html = $(this).attr('data-suggestion-html');
              var tempTextarea = $('<textarea>');
              $('body').append(tempTextarea);
              tempTextarea.val(html).select();
              document.execCommand('copy');
              tempTextarea.remove();
              var $button = $(this);
              var originalText = $button.text();
              $button.text('Copied!');
              setTimeout(function() {
                  $button.text(originalText);
              }, 2000);
          });
      });
      </script>
      <?php
    } else {
      echo '<div class="notice notice-warning">';
      echo '<p>No suggestions available for this post. Make sure both the post and cornerstone posts have embeddings.</p>';
      echo '</div>';
    }
    
    echo '</div>';
  }

  function cpd_markdown_to_html_links($text) {
    // Convert [text](url) to <a href="url">text</a>
    return preg_replace_callback('/\[([^\]]+)\]\(([^\)]+)\)/', function($matches) {
        $label = esc_html($matches[1]);
        $url = esc_url($matches[2]);
        return '<a href="' . $url . '" target="_blank">' . $label . '</a>';
    }, $text);
  }

  function cpd_render_regenerate_page() {
    echo '<div class="wrap">';
    echo '<h1>Regenerate Gemini Suggestions</h1>';
    echo '<p><a href="' . admin_url('admin.php?page=cornerstone-posts') . '" class="button">← Back to Cornerstone Posts</a></p>';

    // Background processing trigger button
    echo '<div style="margin: 20px 0;">';
    echo '<button type="button" class="button button-primary" id="start-background-processing">▶️ Start Background Processing</button>';
    echo '<span id="background-processing-message" style="margin-left: 15px; color: #2271b1;"></span>';
    echo '<p style="margin-top: 10px; color: #666; font-style: italic;">💡 Background processing runs via WP-CLI and won\'t affect your website performance. Processing happens server-side in the background.</p>';
    echo '</div>';

    // Queue status section
    echo '<div id="queue-status" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">';
    echo '<h3>📋 Processing Queue Status</h3>';
    echo '<p style="color: #666; margin-bottom: 15px;">Queue status updates automatically every 30 seconds. Only WP-CLI processes posts - no heavy processing happens in your browser.</p>';
    echo '<div id="queue-stats" style="margin: 10px 0;">';
    echo '<p>Loading queue status...</p>';
    echo '</div>';
    echo '<div id="queue-actions" style="margin-top: 15px;">';
    echo '<button type="button" class="button button-secondary" id="refresh-queue">🔄 Refresh Status</button>';
    echo '<button type="button" class="button button-secondary" id="retry-failed" style="margin-left: 10px;">🔄 Retry Failed Posts</button>';
    echo '</div>';
    echo '</div>';

    // Progress bar container (hidden by default)
    echo '<div id="regeneration-progress" style="display: none; margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">';
    echo '<h3>🔄 Regeneration Progress</h3>';
    echo '<div class="progress-bar-container" style="background: #f1f1f1; border-radius: 4px; height: 20px; margin: 10px 0; overflow: hidden;">';
    echo '<div id="progress-bar" style="background: linear-gradient(90deg, #0073aa, #005a87); height: 100%; width: 0%; transition: width 0.3s ease;"></div>';
    echo '</div>';
    echo '<div id="progress-text" style="margin: 10px 0; font-weight: bold;">Starting...</div>';
    echo '<div id="progress-details" style="color: #666; font-size: 13px;"></div>';
    echo '<button id="cancel-regeneration" class="button button-secondary" style="margin-top: 10px;">Cancel</button>';
    echo '</div>';

    // Get posts with existing suggestions
    global $wpdb;
    $table_name = $wpdb->prefix . 'cpd_gemini_suggestions';
    $posts_with_suggestions = $wpdb->get_results("
      SELECT DISTINCT post_id, COUNT(*) as suggestion_count 
      FROM $table_name 
      GROUP BY post_id 
      ORDER BY suggestion_count DESC
    ");

    if (!empty($posts_with_suggestions)) {
      echo '<div class="notice notice-info">';
      echo '<h2>Posts with Existing Suggestions</h2>';
      echo '<table class="wp-list-table widefat fixed striped">';
      echo '<thead><tr>';
      echo '<th><input type="checkbox" id="select-all-posts"></th>';
      echo '<th>Post Title</th>';
      echo '<th>Suggestions Count</th>';
      echo '<th>Last Updated</th>';
      echo '</tr></thead><tbody>';
      
      foreach ($posts_with_suggestions as $row) {
        $post = get_post($row->post_id);
        if (!$post) continue;
        
        $last_updated = $wpdb->get_var($wpdb->prepare(
          "SELECT MAX(updated_at) FROM $table_name WHERE post_id = %d", 
          $row->post_id
        ));
        
        echo '<tr>';
        echo '<td><input type="checkbox" name="post_ids[]" value="' . $row->post_id . '"></td>';
        echo '<td><a href="' . get_edit_post_link($row->post_id) . '">' . esc_html($post->post_title) . '</a></td>';
        echo '<td>' . $row->suggestion_count . '</td>';
        echo '<td>' . ($last_updated ? date('Y-m-d H:i:s', strtotime($last_updated)) : 'N/A') . '</td>';
        echo '</tr>';
      }
      
      echo '</tbody></table>';
      echo '<p style="margin-top: 15px;">';
      echo '<button type="button" class="button button-primary regenerate-selected" data-action="regenerate">🔄 Regenerate Selected Posts</button>';
      echo '</p>';
      echo '</div>';
    }

    // Get posts without suggestions
    $all_post_ids = get_posts([
      'post_type' => 'post',
      'posts_per_page' => -1,
      'fields' => 'ids',
      'post_status' => 'publish',
    ]);
    
    $posts_with_suggestions_ids = wp_list_pluck($posts_with_suggestions, 'post_id');
    $posts_without_suggestions = array_diff($all_post_ids, $posts_with_suggestions_ids);
    
    if (!empty($posts_without_suggestions)) {
      echo '<div class="notice notice-warning">';
      echo '<h2>Posts Without Suggestions</h2>';
      echo '<p>These posts don\'t have any Gemini suggestions yet. You can generate suggestions using the WP-CLI command:</p>';
      echo '<code>wp cpd generate_suggestions --limit=50</code>';
      echo '<p>Or select posts below to generate suggestions for them:</p>';
      echo '<table class="wp-list-table widefat fixed striped">';
      echo '<thead><tr>';
      echo '<th><input type="checkbox" id="select-all-missing"></th>';
      echo '<th>Post Title</th>';
      echo '<th>Date</th>';
      echo '</tr></thead><tbody>';
      
      $count = 0;
      foreach ($posts_without_suggestions as $post_id) {
        if ($count >= 50) break; // Limit to first 50 for performance
        
        $post = get_post($post_id);
        if (!$post) continue;
        
        echo '<tr>';
        echo '<td><input type="checkbox" name="post_ids[]" value="' . $post_id . '"></td>';
        echo '<td><a href="' . get_edit_post_link($post_id) . '">' . esc_html($post->post_title) . '</a></td>';
        echo '<td>' . get_the_date('Y-m-d', $post_id) . '</td>';
        echo '</tr>';
        
        $count++;
      }
      
      echo '</tbody></table>';
      echo '<p style="margin-top: 15px;">';
      echo '<button type="button" class="button button-secondary regenerate-selected" data-action="generate">🚀 Generate Suggestions for Selected Posts</button>';
      echo '</p>';
      echo '</div>';
    }
    
    echo '</div>';
    
    // Add JavaScript for select all functionality and queue-based processing
    ?>
    <script>
    jQuery(document).ready(function($) {
        var isProcessing = false;
        var allPostIds = [];
        var pollingInterval = null;

        // Select all functionality
        $('#select-all-posts').on('change', function() {
            $('input[name="post_ids[]"]').prop('checked', $(this).is(':checked'));
        });
        $('#select-all-missing').on('change', function() {
            $('input[name="post_ids[]"]').prop('checked', $(this).is(':checked'));
        });

        // Queue-based regeneration
        $('.regenerate-selected').on('click', function() {
            if (isProcessing) {
                alert('Regeneration is already in progress. Please wait.');
                return;
            }
            allPostIds = [];
            $('input[name="post_ids[]"]:checked').each(function() {
                allPostIds.push($(this).val());
            });
            if (allPostIds.length === 0) {
                alert('Please select at least one post.');
                return;
            }
            if (!confirm('Are you sure you want to add the selected posts to the processing queue?')) {
                return;
            }
            $('#regeneration-progress').show();
            $('.regenerate-selected').prop('disabled', true);
            isProcessing = true;
            addPostsToQueue();
        });

        function addPostsToQueue() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cpd_chunked_regeneration',
                    all_post_ids: allPostIds,
                    nonce: '<?php echo wp_create_nonce("cpd_regeneration_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        $('#progress-text').text(data.message);
                        $('#progress-bar').css('width', '100%');
                        $('#progress-bar').css('background', 'linear-gradient(90deg, #46b450, #389a43)');
                        setTimeout(function() {
                            hideProgress();
                            refreshQueueStatus();
                            startPolling();
                        }, 2000);
                    } else {
                        alert('Error: ' + response.data);
                        hideProgress();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    alert('An error occurred while adding posts to the queue. Please try again.');
                    hideProgress();
                }
            });
        }

        function refreshQueueStatus() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cpd_get_queue_status',
                    nonce: '<?php echo wp_create_nonce("cpd_regeneration_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        updateQueueDisplay(response.data);
                    }
                }
            });
        }

        function updateQueueDisplay(data) {
            var html = '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 15px 0;">';
            html += '<div style="text-align: center; padding: 10px; background: #e7f3ff; border-radius: 4px;">';
            html += '<div style="font-size: 24px; font-weight: bold; color: #0073aa;">' + data.pending + '</div>';
            html += '<div style="font-size: 12px; color: #666;">Pending</div>';
            html += '</div>';
            html += '<div style="text-align: center; padding: 10px; background: #fff3cd; border-radius: 4px;">';
            html += '<div style="font-size: 24px; font-weight: bold; color: #856404;">' + data.processing + '</div>';
            html += '<div style="font-size: 12px; color: #666;">Processing</div>';
            html += '</div>';
            html += '<div style="text-align: center; padding: 10px; background: #d4edda; border-radius: 4px;">';
            html += '<div style="font-size: 24px; font-weight: bold; color: #155724;">' + data.done + '</div>';
            html += '<div style="font-size: 12px; color: #666;">Completed</div>';
            html += '</div>';
            html += '<div style="text-align: center; padding: 10px; background: #f8d7da; border-radius: 4px;">';
            html += '<div style="font-size: 24px; font-weight: bold; color: #721c24;">' + data.error + '</div>';
            html += '<div style="font-size: 12px; color: #666;">Failed</div>';
            html += '</div>';
            html += '</div>';
            
            if (data.recent_activity && data.recent_activity.length > 0) {
                html += '<div style="margin-top: 15px;"><h4>Recent Activity:</h4><ul style="margin: 0; padding-left: 20px;">';
                data.recent_activity.forEach(function(activity) {
                    var statusClass = activity.status === 'done' ? 'color: #155724;' : 
                                    activity.status === 'error' ? 'color: #721c24;' : 
                                    activity.status === 'processing' ? 'color: #856404;' : 'color: #0073aa;';
                    html += '<li style="' + statusClass + '">' + activity.post_title + ' - ' + activity.status + '</li>';
                });
                html += '</ul></div>';
            }
            
            $('#queue-stats').html(html);
        }

        function startPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
            pollingInterval = setInterval(function() {
                pollQueue();
            }, 30000); // Poll every 30 seconds
        }

        function pollQueue() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cpd_poll_queue',
                    nonce: '<?php echo wp_create_nonce("cpd_regeneration_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Just refresh the queue status display
                        updateQueueDisplay(response.data);
                    }
                }
            });
        }

        function hideProgress() {
            $('#regeneration-progress').hide();
            $('.regenerate-selected').prop('disabled', false);
            isProcessing = false;
            $('#progress-bar').css('width', '0%');
            $('#progress-bar').css('background', 'linear-gradient(90deg, #0073aa, #005a87)');
            $('#progress-text').text('Starting...');
            $('#progress-details').text('');
        }

        // Refresh queue status button
        $('#refresh-queue').on('click', function() {
            refreshQueueStatus();
        });

        // Retry failed posts button
        $('#retry-failed').on('click', function() {
            if (confirm('Are you sure you want to retry all failed posts?')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cpd_retry_failed_posts',
                        nonce: '<?php echo wp_create_nonce("cpd_regeneration_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            refreshQueueStatus();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            }
        });

        // Start background processing button
        $('#start-background-processing').on('click', function() {
            var $button = $(this);
            var $message = $('#background-processing-message');
            
            $button.prop('disabled', true);
            $message.text('Starting background processing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cpd_trigger_queue_processing',
                    nonce: '<?php echo wp_create_nonce("cpd_regeneration_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $message.html('<span style="color: #46b450;">✅ ' + response.data.message + '</span>');
                        // Refresh queue status immediately after starting
                        setTimeout(function() {
                            refreshQueueStatus();
                        }, 1000);
                    } else {
                        $message.html('<span style="color: #dc3232;">❌ Error: ' + response.data + '</span>');
                        $button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    $message.html('<span style="color: #dc3232;">❌ AJAX error: ' + error + '</span>');
                    $button.prop('disabled', false);
                }
            });
        });

        // Initialize queue status and start polling
        refreshQueueStatus();
        startPolling();
    });
    </script>
    <?php
  }

  register_activation_hook(__FILE__, 'cpd_create_gemini_suggestions_table');
  register_activation_hook(__FILE__, 'cpd_create_processing_queue_table');
  function cpd_create_gemini_suggestions_table() {
      global $wpdb;
      $table_name = $wpdb->prefix . 'cpd_gemini_suggestions';
      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE IF NOT EXISTS $table_name (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          post_id BIGINT UNSIGNED NOT NULL,
          cornerstone_id BIGINT UNSIGNED NOT NULL,
          suggestions_json LONGTEXT NOT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY post_cornerstone (post_id, cornerstone_id)
      ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
  }

  function cpd_create_processing_queue_table() {
      global $wpdb;
      $table_name = $wpdb->prefix . 'cpd_processing_queue';
      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE IF NOT EXISTS $table_name (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          post_id BIGINT UNSIGNED NOT NULL,
          status VARCHAR(20) NOT NULL DEFAULT 'pending',
          last_attempt DATETIME DEFAULT NULL,
          error_message TEXT DEFAULT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY post_status (post_id, status)
      ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
  }

  // Register WP-CLI command
  if (defined('WP_CLI') && WP_CLI) {
      require_once plugin_dir_path(__FILE__) . 'cli-generate-suggestions.php';
  }

  // Add AJAX handlers for progress tracking
  add_action('wp_ajax_cpd_regenerate_suggestions', 'cpd_ajax_regenerate_suggestions');
  add_action('wp_ajax_cpd_get_regeneration_progress', 'cpd_ajax_get_regeneration_progress');
  add_action('wp_ajax_cpd_manual_process_batch', 'cpd_ajax_manual_process_batch');

  // Note: These functions are kept for backward compatibility but are no longer used
  // The new queue system uses WP-CLI for processing instead of these AJAX handlers

  function cpd_ajax_regenerate_suggestions() {
    check_ajax_referer('cpd_regeneration_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }
    
    $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
    $action = $_POST['action_type']; // 'regenerate' or 'generate'
    
    if (empty($post_ids)) {
      wp_send_json_error('No posts selected');
    }
    
    // Store progress in transient
    $progress_key = 'cpd_regeneration_progress_' . get_current_user_id();
    $progress = [
      'total' => count($post_ids),
      'processed' => 0,
      'errors' => 0,
      'skipped' => 0,
      'current_post' => '',
      'status' => 'running'
    ];
    set_transient($progress_key, $progress, 3600); // 1 hour expiry
    
    // Try to start background processing
    $scheduled = wp_schedule_single_event(time(), 'cpd_process_regeneration_batch', [
      'post_ids' => $post_ids,
      'user_id' => get_current_user_id(),
      'action_type' => $action
    ]);
    
    if (!$scheduled) {
      // Fallback: process immediately if cron scheduling fails
      wp_send_json_error('Background processing failed to start. Please try again.');
      return;
    }
    
    wp_send_json_success(['message' => 'Regeneration started']);
  }

  function cpd_ajax_get_regeneration_progress() {
    check_ajax_referer('cpd_regeneration_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }
    
    $progress_key = 'cpd_regeneration_progress_' . get_current_user_id();
    $progress = get_transient($progress_key);
    
    if (!$progress) {
      wp_send_json_error('No progress data found');
    }
    
    wp_send_json_success($progress);
  }

  function cpd_ajax_manual_process_batch() {
    check_ajax_referer('cpd_regeneration_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }
    
    $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
    $action = $_POST['action_type'];
    
    if (empty($post_ids)) {
      wp_send_json_error('No posts selected');
    }
    
    // Process immediately (for smaller batches or when cron fails)
    $result = cpd_process_regeneration_batch([
      'post_ids' => $post_ids,
      'user_id' => get_current_user_id(),
      'action_type' => $action
    ]);
    
    wp_send_json_success(['message' => 'Processing completed']);
  }

  // Background processing function
  // (No add_action for cpd_process_regeneration_batch)
  
  function cpd_process_regeneration_batch($args) {
    // WordPress cron passes arguments as the first parameter
    if (is_array($args) && isset($args[0])) {
      $args = $args[0];
    }
    
    // Ensure we have the required parameters
    if (!isset($args['post_ids']) || !isset($args['user_id']) || !isset($args['action_type'])) {
      error_log('CPD: Missing required parameters for regeneration batch');
      return;
    }
    
    $post_ids = $args['post_ids'];
    $user_id = $args['user_id'];
    $action_type = $args['action_type'];
    
    $progress_key = 'cpd_regeneration_progress_' . $user_id;
    
    // Initialize progress if not exists
    $progress = get_transient($progress_key);
    if (!$progress) {
      $progress = [
        'total' => count($post_ids),
        'processed' => 0,
        'errors' => 0,
        'skipped' => 0,
        'current_post' => '',
        'status' => 'running'
      ];
      set_transient($progress_key, $progress, 3600);
    }
    
    $gemini = new CPD_Gemini_Suggestions();
    
    // Get cornerstone posts
    $cornerstone_posts = get_posts([
      'post_type' => 'post',
      'posts_per_page' => -1,
      'meta_key' => '_yoast_wpseo_is_cornerstone',
      'meta_value' => '1',
      'fields' => 'ids',
    ]);
    
    if (empty($cornerstone_posts)) {
      error_log('CPD: No cornerstone posts found for regeneration');
      $progress['status'] = 'completed';
      $progress['errors'] = $progress['total'];
      set_transient($progress_key, $progress, 3600);
      return;
    }
    
    // Preload cornerstone embeddings
    $cornerstone_embeddings = [];
    foreach ($cornerstone_posts as $cornerstone_id) {
      $embedding = get_post_meta($cornerstone_id, 'openai_embedding', true);
      if (!empty($embedding)) {
        if (is_string($embedding)) {
          $embedding = json_decode($embedding, true);
        }
        if (is_array($embedding)) {
          $cornerstone_embeddings[$cornerstone_id] = $embedding;
        }
      }
    }
    
    foreach ($post_ids as $post_id) {
      // Get fresh progress data
      $progress = get_transient($progress_key);
      if (!$progress) {
        error_log('CPD: Progress data lost during regeneration');
        break;
      }
      
      $progress['current_post'] = get_the_title($post_id);
      
      $post = get_post($post_id);
      if (!$post) {
        $progress['skipped']++;
        $progress['processed']++;
        set_transient($progress_key, $progress, 3600);
        continue;
      }
      
      $post_embedding = get_post_meta($post_id, 'openai_embedding', true);
      if (is_string($post_embedding)) {
        $post_embedding = json_decode($post_embedding, true);
      }
      
      if (empty($post_embedding) || empty($cornerstone_embeddings)) {
        $progress['skipped']++;
        $progress['processed']++;
        set_transient($progress_key, $progress, 3600);
        continue;
      }
      
      // Clear existing cache for this post
      global $wpdb;
      $table_name = $wpdb->prefix . 'cpd_gemini_suggestions';
      $wpdb->delete($table_name, ['post_id' => $post_id]);
      
      // Find top 3 cornerstone suggestions
      $similarity_scores = [];
      foreach ($cornerstone_embeddings as $cornerstone_id => $cornerstone_embedding) {
        if (function_exists('orc_compute_cosine_similarity')) {
          $similarity = orc_compute_cosine_similarity($post_embedding, $cornerstone_embedding);
        } else {
          $similarity = 0;
        }
        $similarity_scores[$cornerstone_id] = $similarity;
      }
      arsort($similarity_scores);
      $top_suggestions = array_slice($similarity_scores, 0, 3, true);
      
      // Generate new suggestions
      foreach ($top_suggestions as $cornerstone_id => $score) {
        try {
          $cornerstone_title = get_the_title($cornerstone_id);
          $cornerstone_url = get_permalink($cornerstone_id);
          
          $suggestions = $gemini->generate_suggestions($post->post_content, $cornerstone_title, $cornerstone_url);
          if (!isset($suggestions['error'])) {
            $gemini->save_suggestions_cache($post_id, $cornerstone_id, $suggestions);
          } else {
            $progress['errors']++;
            error_log('CPD: Error generating suggestions for post ' . $post_id . ' - ' . $suggestions['error']);
          }
        } catch (Exception $e) {
          $progress['errors']++;
          error_log('CPD: Exception during suggestion generation for post ' . $post_id . ' - ' . $e->getMessage());
        }
      }
      
      $progress['processed']++;
      set_transient($progress_key, $progress, 3600);
    }
    
    // Mark as complete
    $progress['status'] = 'completed';
    set_transient($progress_key, $progress, 3600);
  }

  // Add AJAX handler for chunked regeneration
  add_action('wp_ajax_cpd_chunked_regeneration', 'cpd_ajax_chunked_regeneration');

  function cpd_ajax_chunked_regeneration() {
    check_ajax_referer('cpd_regeneration_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }
    $all_post_ids = isset($_POST['all_post_ids']) ? array_map('intval', $_POST['all_post_ids']) : [];
    if (empty($all_post_ids)) {
      wp_send_json_error('No posts selected');
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'cpd_processing_queue';
    $added = 0;
    foreach ($all_post_ids as $post_id) {
      // Only insert if not already pending or processing
      $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND status IN ('pending', 'processing')",
        $post_id
      ));
      if (!$exists) {
        $wpdb->insert($table_name, [
          'post_id' => $post_id,
          'status' => 'pending',
          'created_at' => current_time('mysql'),
          'updated_at' => current_time('mysql'),
        ]);
        $added++;
      }
    }
    wp_send_json_success([
      'added' => $added,
      'message' => sprintf('%d posts added to the processing queue.', $added)
    ]);
  }

  // Add AJAX handler for queue polling
  add_action('wp_ajax_cpd_poll_queue', 'cpd_ajax_poll_queue');

  function cpd_ajax_poll_queue() {
    check_ajax_referer('cpd_regeneration_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'cpd_processing_queue';
    
    // Get counts by status
    $pending = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
    $processing = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'processing'");
    $done = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'done'");
    $error = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'error'");
    
    // Get recent activity (last 10 entries)
    $recent_activity = $wpdb->get_results("
      SELECT post_id, status, updated_at 
      FROM $table_name 
      WHERE status IN ('done', 'error', 'processing') 
      ORDER BY updated_at DESC 
      LIMIT 10
    ");
    
    $activity_data = [];
    foreach ($recent_activity as $activity) {
      $activity_data[] = [
        'post_title' => get_the_title($activity->post_id),
        'status' => $activity->status,
        'updated_at' => $activity->updated_at
      ];
    }
    
    wp_send_json_success([
      'pending' => intval($pending),
      'processing' => intval($processing),
      'done' => intval($done),
      'error' => intval($error),
      'recent_activity' => $activity_data,
      'status' => 'status_only' // Indicate this is just status, not processing
    ]);
  }

  function cpd_process_single_post($post_id) {
    $post = get_post($post_id);
    if (!$post) {
      return ['success' => false, 'error' => 'Post not found'];
    }
    
    $post_embedding = get_post_meta($post_id, 'openai_embedding', true);
    if (is_string($post_embedding)) {
      $post_embedding = json_decode($post_embedding, true);
    }
    
    if (empty($post_embedding)) {
      return ['success' => false, 'error' => 'Post has no embedding'];
    }
    
    $cornerstone_posts = get_posts([
      'post_type' => 'post',
      'posts_per_page' => -1,
      'meta_key' => '_yoast_wpseo_is_cornerstone',
      'meta_value' => '1',
      'fields' => 'ids',
    ]);
    
    if (empty($cornerstone_posts)) {
      return ['success' => false, 'error' => 'No cornerstone posts found'];
    }
    
    // Preload cornerstone embeddings
    $cornerstone_embeddings = [];
    foreach ($cornerstone_posts as $cornerstone_id) {
      $embedding = get_post_meta($cornerstone_id, 'openai_embedding', true);
      if (!empty($embedding)) {
        if (is_string($embedding)) {
          $embedding = json_decode($embedding, true);
        }
        if (is_array($embedding)) {
          $cornerstone_embeddings[$cornerstone_id] = $embedding;
        }
      }
    }
    
    if (empty($cornerstone_embeddings)) {
      return ['success' => false, 'error' => 'No cornerstone posts with embeddings found'];
    }
    
    // Clear existing cache for this post
    global $wpdb;
    $suggestions_table = $wpdb->prefix . 'cpd_gemini_suggestions';
    $wpdb->delete($suggestions_table, ['post_id' => $post_id]);
    
    // Find top 3 cornerstone suggestions
    $similarity_scores = [];
    foreach ($cornerstone_embeddings as $cornerstone_id => $cornerstone_embedding) {
      if (function_exists('orc_compute_cosine_similarity')) {
        $similarity = orc_compute_cosine_similarity($post_embedding, $cornerstone_embedding);
      } else {
        $similarity = 0;
      }
      $similarity_scores[$cornerstone_id] = $similarity;
    }
    arsort($similarity_scores);
    $top_suggestions = array_slice($similarity_scores, 0, 3, true);
    
    $gemini = new CPD_Gemini_Suggestions();
    $errors = [];
    
    // Generate new suggestions
    foreach ($top_suggestions as $cornerstone_id => $score) {
      try {
        $cornerstone_title = get_the_title($cornerstone_id);
        $cornerstone_url = get_permalink($cornerstone_id);
        
        $suggestions = $gemini->generate_suggestions($post->post_content, $cornerstone_title, $cornerstone_url);
        if (!isset($suggestions['error'])) {
          $gemini->save_suggestions_cache($post_id, $cornerstone_id, $suggestions);
        } else {
          $errors[] = "Cornerstone #$cornerstone_id: " . $suggestions['error'];
        }
      } catch (Exception $e) {
        $errors[] = "Cornerstone #$cornerstone_id: " . $e->getMessage();
      }
    }
    
    if (!empty($errors)) {
      return ['success' => false, 'error' => implode('; ', $errors)];
    }
    
    return ['success' => true];
  }

  // Add AJAX handler for getting queue status
  add_action('wp_ajax_cpd_get_queue_status', 'cpd_ajax_get_queue_status');

  // Add AJAX handler for retrying failed posts
  add_action('wp_ajax_cpd_retry_failed_posts', 'cpd_ajax_retry_failed_posts');

  function cpd_ajax_get_queue_status() {
    check_ajax_referer('cpd_regeneration_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'cpd_processing_queue';
    
    // Get counts by status
    $pending = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
    $processing = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'processing'");
    $done = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'done'");
    $error = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'error'");
    
    // Get recent activity (last 10 entries)
    $recent_activity = $wpdb->get_results("
      SELECT post_id, status, updated_at 
      FROM $table_name 
      WHERE status IN ('done', 'error', 'processing') 
      ORDER BY updated_at DESC 
      LIMIT 10
    ");
    
    $activity_data = [];
    foreach ($recent_activity as $activity) {
      $activity_data[] = [
        'post_title' => get_the_title($activity->post_id),
        'status' => $activity->status,
        'updated_at' => $activity->updated_at
      ];
    }
    
    wp_send_json_success([
      'pending' => intval($pending),
      'processing' => intval($processing),
      'done' => intval($done),
      'error' => intval($error),
      'recent_activity' => $activity_data
    ]);
  }

  function cpd_ajax_retry_failed_posts() {
    check_ajax_referer('cpd_regeneration_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'cpd_processing_queue';
    
    // Reset all failed posts to pending
    $updated = $wpdb->update($table_name, [
      'status' => 'pending',
      'error_message' => null,
      'updated_at' => current_time('mysql'),
    ], ['status' => 'error']);
    
    wp_send_json_success([
      'message' => sprintf('%d failed posts have been reset to pending status.', $updated)
    ]);
  }

  // AJAX handler to trigger WP-CLI queue processing
  add_action('wp_ajax_cpd_trigger_queue_processing', 'cpd_ajax_trigger_queue_processing');
  function cpd_ajax_trigger_queue_processing() {
    check_ajax_referer('cpd_regeneration_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    // Check if there are pending posts
    global $wpdb;
    $table_name = $wpdb->prefix . 'cpd_processing_queue';
    $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
    
    if ($pending_count == 0) {
        wp_send_json_error('No pending posts in the queue to process.');
        return;
    }
    
    // Try to find the wp binary - try common paths
    $wp_paths = [
        'wp',
        '/usr/local/bin/wp',
        '/usr/bin/wp',
        dirname(ABSPATH) . '/wp-cli.phar'
    ];
    
    $wp_path = null;
    foreach ($wp_paths as $path) {
        if (function_exists('exec')) {
            $output = [];
            $return_var = 0;
            exec("which $path 2>/dev/null", $output, $return_var);
            if ($return_var === 0 && !empty($output)) {
                $wp_path = $path;
                break;
            }
        }
    }
    
    if (!$wp_path) {
        wp_send_json_error('WP-CLI not found. Please ensure WP-CLI is installed and accessible.');
        return;
    }
    
    // Build the command to process all pending posts in the background
    $cmd = sprintf(
        '%s cpd process_queue --all > /dev/null 2>&1 & echo $!',
        escapeshellarg($wp_path)
    );
    
    if (function_exists('exec')) {
        $pid = exec($cmd);
        if ($pid) {
            wp_send_json_success([
                'message' => sprintf('Background processing started! (PID: %s) Processing %d pending posts.', $pid, $pending_count),
                'pid' => $pid,
                'pending_count' => $pending_count
            ]);
        } else {
            wp_send_json_error('Failed to start background processing. Please check server logs.');
        }
    } else {
        wp_send_json_error('exec() function is not available on this server. Please contact your hosting provider.');
    }
  }
