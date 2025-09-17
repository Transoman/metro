<?php
if (defined('WP_CLI') && WP_CLI) {
    /**
     * Generate and cache Gemini suggestions for a batch of posts.
     *
     * ## OPTIONS
     *
     * [--limit=<number>]
     * : Number of posts to process (default: all missing)
     *
     * [--offset=<number>]
     * : Number of posts to skip (default: 0)
     *
     * [--force]
     * : Force regeneration of suggestions even if cached
     *
     * ## EXAMPLES
     *
     *     # Process all posts missing suggestions
     *     $ wp cpd generate_suggestions
     *
     *     # Process 50 missing posts
     *     $ wp cpd generate_suggestions --limit=50
     *
     *     # Process next 50 missing posts
     *     $ wp cpd generate_suggestions --limit=50 --offset=50
     *
     *     # Force regenerate suggestions for 10 posts (by batch)
     *     $ wp cpd generate_suggestions --limit=10 --force
     */
    WP_CLI::add_command('cpd generate_suggestions', function($args, $assoc_args) {
        $limit = isset($assoc_args['limit']) ? intval($assoc_args['limit']) : null;
        $offset = isset($assoc_args['offset']) ? intval($assoc_args['offset']) : 0;
        $force = isset($assoc_args['force']) ? true : false;

        require_once __DIR__ . '/includes/gemini-suggestions.php';
        $gemini = new CPD_Gemini_Suggestions();
        global $wpdb;

        // Get all published post IDs
        $all_post_ids = get_posts([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish',
        ]);
        // Get all post IDs with suggestions
        $table_name = $wpdb->prefix . 'cpd_gemini_suggestions';
        $posts_with_suggestions = $wpdb->get_col("SELECT DISTINCT post_id FROM $table_name");
        // Find posts missing suggestions
        $missing_post_ids = array_values(array_diff($all_post_ids, $posts_with_suggestions));

        if ($limit !== null) {
            $missing_post_ids = array_slice($missing_post_ids, $offset, $limit);
        }

        if (empty($missing_post_ids)) {
            WP_CLI::success('No posts missing Gemini suggestions.');
            return;
        }

        $cornerstone_posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'meta_key' => '_yoast_wpseo_is_cornerstone',
            'meta_value' => '1',
            'fields' => 'ids',
        ]);

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

        $processed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($missing_post_ids as $post_id) {
            $post = get_post($post_id);
            $post_embedding = get_post_meta($post_id, 'openai_embedding', true);
            if (is_string($post_embedding)) {
                $post_embedding = json_decode($post_embedding, true);
            }
            if (empty($post_embedding) || empty($cornerstone_embeddings)) {
                WP_CLI::log("Skipping post #$post_id (missing embedding)");
                $skipped++;
                continue;
            }

            // Find top 3 cornerstone suggestions by similarity
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

            foreach ($top_suggestions as $cornerstone_id => $score) {
                $cornerstone_title = get_the_title($cornerstone_id);
                $cornerstone_url = get_permalink($cornerstone_id);
                
                if (!$force) {
                    $cached = $gemini->get_cached_suggestions($post_id, $cornerstone_id);
                    if ($cached) {
                        WP_CLI::log("[CACHED] Post #$post_id - Cornerstone #$cornerstone_id");
                        continue;
                    }
                }

                WP_CLI::log("Generating suggestions for Post #$post_id - Cornerstone #$cornerstone_id...");
                $suggestions = $gemini->generate_suggestions($post->post_content, $cornerstone_title, $cornerstone_url);
                if (!isset($suggestions['error'])) {
                    $gemini->save_suggestions_cache($post_id, $cornerstone_id, $suggestions);
                    WP_CLI::log("[SAVED] Suggestions for Post #$post_id - Cornerstone #$cornerstone_id");
                } else {
                    WP_CLI::warning("[ERROR] Post #$post_id - Cornerstone #$cornerstone_id: " . $suggestions['error']);
                    $errors++;
                }
            }
            $processed++;
        }

        WP_CLI::success(sprintf(
            "Batch complete. Processed %d posts, skipped %d, errors: %d",
            $processed,
            $skipped,
            $errors
        ));
    });

    /**
     * Regenerate Gemini suggestions for posts.
     *
     * ## OPTIONS
     *
     * [--post-ids=<post-ids>]
     * : Comma-separated list of post IDs to regenerate (default: all posts with suggestions)
     *
     * [--cornerstone-ids=<cornerstone-ids>]
     * : Comma-separated list of cornerstone IDs to regenerate (optional)
     *
     * [--clear-cache-only]
     * : Only clear cache without regenerating
     *
     * [--force]
     * : Force regeneration even if suggestions exist
     *
     * ## EXAMPLES
     *
     *     # Regenerate all existing suggestions
     *     $ wp cpd regenerate_suggestions
     *
     *     # Regenerate suggestions for specific posts
     *     $ wp cpd regenerate_suggestions --post-ids=123,456,789
     *
     *     # Clear cache only for specific posts
     *     $ wp cpd regenerate_suggestions --post-ids=123,456 --clear-cache-only
     *
     *     # Regenerate suggestions for specific cornerstone posts
     *     $ wp cpd regenerate_suggestions --cornerstone-ids=10,20,30
     */
    WP_CLI::add_command('cpd regenerate_suggestions', function($args, $assoc_args) {
        $post_ids = isset($assoc_args['post-ids']) ? array_map('intval', explode(',', $assoc_args['post-ids'])) : null;
        $cornerstone_ids = isset($assoc_args['cornerstone-ids']) ? array_map('intval', explode(',', $assoc_args['cornerstone-ids'])) : null;
        $clear_cache_only = isset($assoc_args['clear-cache-only']) ? true : false;
        $force = isset($assoc_args['force']) ? true : false;

        require_once __DIR__ . '/includes/gemini-suggestions.php';
        $gemini = new CPD_Gemini_Suggestions();
        global $wpdb;

        // Clear cache based on parameters
        if ($post_ids && $cornerstone_ids) {
            foreach ($post_ids as $post_id) {
                foreach ($cornerstone_ids as $cornerstone_id) {
                    $gemini->clear_suggestions_cache($post_id, $cornerstone_id);
                    WP_CLI::log("Cleared cache for Post #$post_id - Cornerstone #$cornerstone_id");
                }
            }
        } elseif ($post_ids) {
            foreach ($post_ids as $post_id) {
                $gemini->clear_suggestions_cache($post_id);
                WP_CLI::log("Cleared cache for Post #$post_id");
            }
        } elseif ($cornerstone_ids) {
            foreach ($cornerstone_ids as $cornerstone_id) {
                $gemini->clear_suggestions_cache(null, $cornerstone_id);
                WP_CLI::log("Cleared cache for Cornerstone #$cornerstone_id");
            }
        } else {
            // Clear all cache
            $gemini->clear_suggestions_cache();
            WP_CLI::log("Cleared all suggestion cache");
        }

        if ($clear_cache_only) {
            WP_CLI::success("Cache clearing complete!");
            return;
        }

        // Determine which posts to process
        if ($post_ids) {
            $posts_to_process = $post_ids;
        } else {
            // Get all posts with existing suggestions
            $table_name = $wpdb->prefix . 'cpd_gemini_suggestions';
            $posts_to_process = $wpdb->get_col("SELECT DISTINCT post_id FROM $table_name");
        }

        if (empty($posts_to_process)) {
            WP_CLI::success('No posts to regenerate suggestions for.');
            return;
        }

        // Get cornerstone posts
        $cornerstone_posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'meta_key' => '_yoast_wpseo_is_cornerstone',
            'meta_value' => '1',
            'fields' => 'ids',
        ]);

        if (empty($cornerstone_posts)) {
            WP_CLI::error('No cornerstone posts found.');
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

        if (empty($cornerstone_embeddings)) {
            WP_CLI::error('No cornerstone posts with embeddings found.');
            return;
        }

        $processed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($posts_to_process as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                WP_CLI::log("Skipping post #$post_id (not found)");
                $skipped++;
                continue;
            }

            $post_embedding = get_post_meta($post_id, 'openai_embedding', true);
            if (is_string($post_embedding)) {
                $post_embedding = json_decode($post_embedding, true);
            }
            
            if (empty($post_embedding)) {
                WP_CLI::log("Skipping post #$post_id (missing embedding)");
                $skipped++;
                continue;
            }

            // Find top 3 cornerstone suggestions by similarity
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

            foreach ($top_suggestions as $cornerstone_id => $score) {
                $cornerstone_title = get_the_title($cornerstone_id);
                $cornerstone_url = get_permalink($cornerstone_id);
                
                if (!$force) {
                    $cached = $gemini->get_cached_suggestions($post_id, $cornerstone_id);
                    if ($cached) {
                        WP_CLI::log("[CACHED] Post #$post_id - Cornerstone #$cornerstone_id");
                        continue;
                    }
                }

                WP_CLI::log("Generating suggestions for Post #$post_id - Cornerstone #$cornerstone_id...");
                $suggestions = $gemini->generate_suggestions($post->post_content, $cornerstone_title, $cornerstone_url);
                if (!isset($suggestions['error'])) {
                    $gemini->save_suggestions_cache($post_id, $cornerstone_id, $suggestions);
                    WP_CLI::log("[SAVED] Suggestions for Post #$post_id - Cornerstone #$cornerstone_id");
                } else {
                    WP_CLI::warning("[ERROR] Post #$post_id - Cornerstone #$cornerstone_id: " . $suggestions['error']);
                    $errors++;
                }
            }
            $processed++;
        }

        WP_CLI::success(sprintf(
            "Regeneration complete. Processed %d posts, skipped %d, errors: %d",
            $processed,
            $skipped,
            $errors
        ));
    });

    /**
     * Show statistics about Gemini suggestions.
     *
     * ## EXAMPLES
     *
     *     # Show general statistics
     *     $ wp cpd suggestions_stats
     */
    WP_CLI::add_command('cpd suggestions_stats', function($args, $assoc_args) {
        require_once __DIR__ . '/includes/gemini-suggestions.php';
        $gemini = new CPD_Gemini_Suggestions();
        
        $stats = $gemini->get_suggestions_stats();
        
        WP_CLI::log("Gemini Suggestions Statistics:");
        WP_CLI::log("Total suggestions: " . $stats['total_suggestions']);
        WP_CLI::log("Unique posts: " . $stats['unique_posts']);
        WP_CLI::log("Unique cornerstones: " . $stats['unique_cornerstones']);
        WP_CLI::log("Latest update: " . ($stats['latest_update'] ?: 'N/A'));
    });

    /**
     * Process the next pending post in the processing queue (or optionally all pending posts).
     *
     * ## OPTIONS
     *
     * [--all]
     * : Process all pending posts (default: only one)
     *
     * ## EXAMPLES
     *
     *     # Process one pending post
     *     $ wp cpd process_queue
     *
     *     # Process all pending posts
     *     $ wp cpd process_queue --all
     */
    WP_CLI::add_command('cpd process_queue', function($args, $assoc_args) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cpd_processing_queue';
        $processed = 0;
        $errors = 0;
        $all = isset($assoc_args['all']);
        do {
            $wpdb->query('START TRANSACTION');
            $pending_post = $wpdb->get_row("SELECT * FROM $table_name WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1 FOR UPDATE");
            if (!$pending_post) {
                $wpdb->query('COMMIT');
                if ($processed === 0) {
                    WP_CLI::success('No pending posts in the queue.');
                } else {
                    WP_CLI::success("Queue processing complete. Processed: $processed, Errors: $errors");
                }
                return;
            }
            // Mark as processing
            $wpdb->update($table_name, [
                'status' => 'processing',
                'last_attempt' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ], ['id' => $pending_post->id]);
            $wpdb->query('COMMIT');

            // Process the post (reuse plugin logic)
            if (function_exists('cpd_process_single_post')) {
                $result = cpd_process_single_post($pending_post->post_id);
            } else {
                WP_CLI::error('cpd_process_single_post() not found.');
                return;
            }
            if ($result['success']) {
                $wpdb->update($table_name, [
                    'status' => 'done',
                    'updated_at' => current_time('mysql'),
                ], ['id' => $pending_post->id]);
                WP_CLI::log('Processed post ID ' . $pending_post->post_id . ' ✔️');
                $processed++;
            } else {
                $wpdb->update($table_name, [
                    'status' => 'error',
                    'error_message' => $result['error'],
                    'updated_at' => current_time('mysql'),
                ], ['id' => $pending_post->id]);
                WP_CLI::warning('Error processing post ID ' . $pending_post->post_id . ': ' . $result['error']);
                $errors++;
            }
        } while ($all);
        WP_CLI::success("Queue processing complete. Processed: $processed, Errors: $errors");
    });
} 