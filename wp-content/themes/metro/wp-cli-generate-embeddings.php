<?php
/**
 * WP-CLI command to generate missing OpenAI embeddings for posts
 *
 * ## EXAMPLES
 *
 *     wp generate-embeddings
 *
 * @when after_wp_load
 */

if (!defined('WP_CLI')) {
    return;
}

class Generate_Embeddings_Command {
    /**
     * Generates OpenAI embeddings for posts that don't have them
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Whether to perform a dry run (no actual updates)
     *
     * ## EXAMPLES
     *
     *     wp generate-embeddings
     *     wp generate-embeddings --dry-run
     */
    public function __invoke($args, $assoc_args) {
        // Check if OpenAI API key is defined
        if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === 'YOUR_OPENAI_API_KEY_HERE') {
            WP_CLI::error('OpenAI API key is not defined or is using the default value.');
            return;
        }

        $dry_run = isset($assoc_args['dry-run']);

        // Get all posts that need embeddings
        $posts = get_posts([
            'post_type' => ['post', 'page', 'listings', 'buildings'],
            'numberposts' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'openai_embedding',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);

        if (empty($posts)) {
            WP_CLI::success('No posts found missing embeddings.');
            return;
        }

        WP_CLI::log(sprintf('Found %d posts missing embeddings.', count($posts)));

        $progress = \WP_CLI\Utils\make_progress_bar('Generating embeddings', count($posts));

        foreach ($posts as $post_id) {
            $post = get_post($post_id);
            
            // Skip if post doesn't exist
            if (!$post) {
                $progress->tick();
                continue;
            }

            // Prepare text for embedding
            $text = $post->post_title . "\n" . wp_strip_all_tags($post->post_content);

            // Get embedding
            $embedding = $this->get_openai_embedding($text);

            if (is_wp_error($embedding)) {
                WP_CLI::warning(sprintf(
                    'Failed to generate embedding for post %d (%s): %s',
                    $post_id,
                    $post->post_title,
                    $embedding->get_error_message()
                ));
                $progress->tick();
                continue;
            }

            if (!$dry_run) {
                update_post_meta($post_id, 'openai_embedding', $embedding);
            }

            $progress->tick();
        }

        $progress->finish();

        if ($dry_run) {
            WP_CLI::success('Dry run completed. No changes were made.');
        } else {
            WP_CLI::success('Embeddings generation completed.');
        }
    }

    /**
     * Get OpenAI embedding for text
     *
     * @param string $input Text to get embedding for
     * @return array|WP_Error Embedding array or WP_Error on failure
     */
    private function get_openai_embedding($input) {
        $endpoint = 'https://api.openai.com/v1/embeddings';
        
        // We don't need the instructions for this use case since we're just generating
        // embeddings for content matching, not for recommendation purposes
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . OPENAI_API_KEY,
            ],
            'body' => wp_json_encode([
                'model' => 'text-embedding-ada-002',
                'input' => $input,
            ]),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['data'][0]['embedding'])) {
            return $data['data'][0]['embedding'];
        }

        return new WP_Error('no_embedding', 'No embedding found in response.');
    }
}

WP_CLI::add_command('generate-embeddings', 'Generate_Embeddings_Command'); 