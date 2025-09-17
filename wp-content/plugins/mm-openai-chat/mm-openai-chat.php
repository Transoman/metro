<?php
/**
 * Plugin Name: OpenAI Embedder & Chat
 * Description: Generate and store OpenAI embeddings for posts, listings, and buildings, and chat with context from stored embeddings.
 * Version: 1.0.4
 * Author: Nemanja Tanaskovic
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Require user to define OpenAI API key in wp-config.php
if ( ! defined( 'OPENAI_API_KEY' ) ) {
    define( 'OPENAI_API_KEY', '' ); // Set your OpenAI API key here or in wp-config.php
}

/**
 * Register meta box for embedding generation
 */
function oec_add_meta_box() {
    $types = array( 'post', 'page', 'listings', 'buildings' );
    foreach ( $types as $type ) {
        add_meta_box(
            'oec-embed-meta',
            'OpenAI Embedding',
            'oec_render_meta_box',
            $type,
            'side',
            'default'
        );
    }
}
add_action( 'add_meta_boxes', 'oec_add_meta_box' );

/**
 * Render the meta box content
 */
function oec_render_meta_box( $post ) {
    wp_nonce_field( 'oec_embed_nonce', 'oec_embed_nonce_field' );
    $existing = get_post_meta( $post->ID, 'openai_embedding', true );
    echo '<p><strong>Status:</strong> ' . ( $existing ? 'Generated' : 'Not generated' ) . '</p>';
    echo '<button type="button" class="button" id="oec-gen-embed" data-post-id="' . esc_attr( $post->ID ) . '">Generate Embedding</button>';
}

/**
 * Enqueue admin scripts and styles
 */
function oec_admin_assets( $hook ) {
    // Only enqueue on post editor pages and our AI Chat page
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'tools_page_oec-ai-chat' ), true ) ) {
        return;
    }

    // Enqueue admin JS
    $js_path = plugin_dir_path( __FILE__ ) . 'js/admin.js';
    if ( file_exists( $js_path ) ) {
        wp_enqueue_script(
            'oec-admin-js',
            plugin_dir_url( __FILE__ ) . 'js/admin.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );
        wp_localize_script(
            'oec-admin-js',
            'OEC',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'oec-ajax-nonce' ),
            )
        );
    } else {
        wp_die( 'Required JavaScript file js/admin.js is missing. Please check the plugin installation.' );
    }

    // Enqueue admin CSS (optional)
    $css_path = plugin_dir_path( __FILE__ ) . 'css/admin.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_style(
            'oec-admin-css',
            plugin_dir_url( __FILE__ ) . 'css/admin.css',
            array(),
            '1.0.0'
        );
    }
}
add_action( 'admin_enqueue_scripts', 'oec_admin_assets' );

/**
 * AJAX handler: generate embedding
 */
function oec_ajax_generate_embedding() {
    check_ajax_referer( 'oec-ajax-nonce', 'nonce' );
    $post_id = intval( $_POST['post_id'] );
    $post    = get_post( $post_id );
    if ( ! $post ) {
        wp_send_json_error( 'Invalid post.' );
    }
    // Prepare content
    $content = wp_strip_all_tags( $post->post_content );
    // Call OpenAI Embeddings API
    $response = wp_remote_post( 'https://api.openai.com/v1/embeddings', array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . OPENAI_API_KEY,
        ),
        'body'    => wp_json_encode( array(
            'model' => 'text-embedding-ada-002',
            'input' => $content,
        ) ),
    ) );
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'API request failed.' );
    }
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['data'][0]['embedding'] ) ) {
        wp_send_json_error( 'No embedding returned.' );
    }
    $embedding = $body['data'][0]['embedding'];
    update_post_meta( $post_id, 'openai_embedding', wp_json_encode( $embedding ) );
    wp_send_json_success( 'Embedding saved.' );
}
add_action( 'wp_ajax_oec_generate_embedding', 'oec_ajax_generate_embedding' );

/**
 * Add admin menu page for AI Chat
 */
function oec_add_chat_page() {
    add_management_page(
        'AI Chat',
        'AI Chat',
        'manage_options',
        'oec-ai-chat',
        'oec_render_chat_page'
    );
}
add_action( 'admin_menu', 'oec_add_chat_page' );

/**
 * Render chat interface
 */
function oec_render_chat_page() {
    ?>
    <div class="wrap">
        <h1>AI Chat</h1>
        <div id="oec-chat-log" style="border:1px solid #ddd;padding:10px;height:400px;overflow:auto;background:#fff;"></div>
        <textarea id="oec-chat-input" rows="3" style="width:100%;"></textarea>
        <button class="button button-primary" id="oec-chat-send">Send</button>
    </div>
    <?php
}

/**
 * Check if OpenAI API key is configured
 */
function oec_check_api_key() {
    if ( empty( OPENAI_API_KEY ) ) {
        return false;
    }
    return true;
}

/**
 * Debug logging function
 */
function oec_log_error($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('OEC Error: ' . $message);
        if ($data !== null) {
            error_log('OEC Data: ' . print_r($data, true));
        }
    }
}

/**
 * Get OpenAI embedding with proper error handling
 */
function oec_get_openai_embedding($input) {
    $endpoint = 'https://api.openai.com/v1/embeddings';
    
    // Log the request
    oec_log_error('Making embedding request', [
        'endpoint' => $endpoint,
        'input_length' => strlen($input)
    ]);
    
    $response = wp_remote_post($endpoint, [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . OPENAI_API_KEY,
        ],
        'body'    => wp_json_encode([
            'model' => 'text-embedding-ada-002',
            'input' => $input,
        ]),
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        oec_log_error('Embedding request failed', $response->get_error_message());
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);
    
    // Log the response
    oec_log_error('Embedding response', [
        'code' => $response_code,
        'body' => $data
    ]);
    
    // Check for API quota error
    if (isset($data['error']['code']) && $data['error']['code'] === 'insufficient_quota') {
        oec_log_error('Quota exceeded', $data['error']);
        return new WP_Error('quota_exceeded', 'OpenAI API quota exceeded. Please check your billing details at https://platform.openai.com/account/billing');
    }
    
    if (isset($data['data'][0]['embedding'])) {
        return $data['data'][0]['embedding'];
    }
    
    oec_log_error('No embedding in response', $data);
    return new WP_Error('no_embedding', isset($data['error']['message']) ? $data['error']['message'] : 'No embedding found in response.');
}

/**
 * AJAX handler: chat with AI
 */
function oec_ajax_chat() {
    try {
        check_ajax_referer('oec-ajax-nonce', 'nonce');
        
        // Check if API key is configured
        if (!oec_check_api_key()) {
            wp_send_json_error('OpenAI API key is not configured. Please add it to your wp-config.php file.');
            return;
        }

        $message = sanitize_text_field(wp_unslash($_POST['message']));
        if (empty($message)) {
            wp_send_json_error('Empty message.');
            return;
        }

        // 1. Embed user message
        $user_embedding = oec_get_openai_embedding($message);
        if (is_wp_error($user_embedding)) {
            oec_log_error('Embedding generation failed', $user_embedding->get_error_message());
            wp_send_json_error($user_embedding->get_error_message());
            return;
        }
        
        // 2. Retrieve stored embeddings
        $args = array(
            'post_type'      => array('post', 'page', 'listings', 'buildings'),
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => 'openai_embedding',
                    'compare' => 'EXISTS',
                ),
            ),
        );
        $posts = get_posts($args);
        
        if (empty($posts)) {
            oec_log_error('No posts with embeddings found');
            wp_send_json_error('No embeddings found in the database. Please generate embeddings for some content first.');
            return;
        }
        
        $sims = array();
        foreach ($posts as $p) {
            $emb = get_post_meta($p->ID, 'openai_embedding', true);
            $vec = json_decode($emb, true);
            if (is_array($vec)) {
                $sims[$p->ID] = _oec_cosine_similarity($user_embedding, $vec);
            }
        }
        
        if (empty($sims)) {
            oec_log_error('No valid similarity scores calculated');
            wp_send_json_error('No valid embeddings found. Please regenerate embeddings for your content.');
            return;
        }
        
        arsort($sims);
        $top_ids = array_slice(array_keys($sims), 0, 5);
        $contexts = "";
        foreach ($top_ids as $pid) {
            $post = get_post($pid);
            $contexts .= "Title: " . $post->post_title . "\n";
            $contexts .= wp_trim_words(wp_strip_all_tags($post->post_content), 50) . "\n---\n";
        }
        
        // 3. ChatCompletion
        $system_prompt = "You are a helpful assistant. Use the provided contexts to answer user questions. Contexts:\n" . $contexts;
        
        oec_log_error('Making chat completion request', [
            'prompt_length' => strlen($system_prompt),
            'message_length' => strlen($message)
        ]);
        
        $chat_resp = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . OPENAI_API_KEY,
            ),
            'body'    => wp_json_encode(array(
                'model'    => 'gpt-3.5-turbo',
                'messages' => array(
                    array('role' => 'system', 'content' => $system_prompt),
                    array('role' => 'user', 'content' => $message),
                ),
            )),
            'timeout' => 30,
        ));
        
        if (is_wp_error($chat_resp)) {
            oec_log_error('Chat completion request failed', $chat_resp->get_error_message());
            wp_send_json_error('Failed to get AI response: ' . $chat_resp->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($chat_resp);
        $response_body = wp_remote_retrieve_body($chat_resp);
        $chat_body = json_decode($response_body, true);
        
        oec_log_error('Chat completion response', [
            'code' => $response_code,
            'body' => $chat_body
        ]);
        
        // Check for API quota error in chat completion
        if (isset($chat_body['error']['code']) && $chat_body['error']['code'] === 'insufficient_quota') {
            oec_log_error('Chat completion quota exceeded', $chat_body['error']);
            wp_send_json_error('OpenAI API quota exceeded. Please check your billing details at https://platform.openai.com/account/billing');
            return;
        }
        
        if (empty($chat_body['choices'][0]['message']['content'])) {
            $error_message = isset($chat_body['error']['message']) ? $chat_body['error']['message'] : 'Unknown error';
            oec_log_error('No content in chat response', $chat_body);
            wp_send_json_error('Failed to get AI response: ' . $error_message);
            return;
        }
        
        $reply = $chat_body['choices'][0]['message']['content'];
        wp_send_json_success(array('reply' => $reply));
        
    } catch (Exception $e) {
        oec_log_error('Unexpected error in chat handler', $e->getMessage());
        wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
    }
}
add_action('wp_ajax_oec_chat', 'oec_ajax_chat');

/**
 * Cosine similarity helper
 */
function _oec_cosine_similarity( $a, $b ) {
    $dot = 0;
    $normA = 0;
    $normB = 0;
    for ( $i = 0; $i < count( $a ); $i++ ) {
        $dot += $a[$i] * $b[$i];
        $normA += $a[$i] * $a[$i];
        $normB += $b[$i] * $b[$i];
    }
    if ( $normA == 0 || $normB == 0 ) {
        return 0;
    }
    return $dot / ( sqrt( $normA ) * sqrt( $normB ) );
}
