<?php
/**
 * Gemini API integration for generating paragraph suggestions
 */

class CPD_Gemini_Suggestions {
    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    private $max_content_length = 2000; // Maximum characters to send to Gemini

    public function __construct() {
        $this->api_key = 'AIzaSyCLnlTKFq5AAUo_3zS_b5uOcXhAdETfS0E';
    }

    public function generate_suggestions($post_content, $cornerstone_title, $cornerstone_url) {
        // Truncate content if needed
        $truncated_content = $this->truncate_content($post_content);
        
        $prompt = $this->build_prompt($truncated_content, $cornerstone_title, $cornerstone_url);
        
        $response = wp_remote_post($this->api_url . '?key=' . $this->api_key, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 8192,
                ]
            ]),
            'timeout' => 3000
        ]);

        if (is_wp_error($response)) {
            return [
                'error' => $response->get_error_message()
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return [
                'error' => $body['error']['message'] ?? 'Unknown error occurred'
            ];
        }

        // Check for token limit error
        if (isset($body['candidates'][0]['finishReason']) && $body['candidates'][0]['finishReason'] === 'MAX_TOKENS') {
            return [
                'error' => 'The content is too long for the AI to process. Please try with a shorter post.'
            ];
        }

        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'error' => 'No suggestions generated'
            ];
        }

        $suggestions_text = $body['candidates'][0]['content']['parts'][0]['text'];
        return $this->parse_suggestions($suggestions_text);
    }

    private function truncate_content($content) {
        // Remove HTML tags
        $content = wp_strip_all_tags($content);
        
        // If content is too long, truncate it
        if (strlen($content) > $this->max_content_length) {
            // Try to find a good breaking point (end of sentence)
            $truncated = substr($content, 0, $this->max_content_length);
            $last_period = strrpos($truncated, '.');
            
            if ($last_period !== false) {
                $content = substr($truncated, 0, $last_period + 1);
            } else {
                $content = $truncated;
            }
            
            $content .= ' [Content truncated for AI processing]';
        }
        
        return $content;
    }

    private function build_prompt($post_content, $cornerstone_title, $cornerstone_url) {
        return <<<PROMPT
Create 3 natural paragraphs that can be inserted into this blog post to link to a cornerstone article.

For each suggestion, provide:
- A placement note in the format: NOTE: [where the paragraph should be inserted]
- The paragraph itself, on a new line after the note.

Cornerstone article: "{$cornerstone_title}"
URL to link: {$cornerstone_url}

Blog post content:
{$post_content}

Requirements:
- Each paragraph should be 2-4 sentences
- Include a natural link to the cornerstone article
- Be contextually relevant to both articles
- Add value to the reader
- Use professional, engaging tone

Format:
SUGGESTION 1:
NOTE: [placement note]
[paragraph]

SUGGESTION 2:
NOTE: [placement note]
[paragraph]

SUGGESTION 3:
NOTE: [placement note]
[paragraph]
PROMPT;
    }

    private function parse_suggestions($text) {
        $suggestions = [];
        $pattern = '/SUGGESTION\s+\d+:\s*NOTE:\s*(.*?)\n(.*?)(?=SUGGESTION\s+\d+:|$)/s';
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $suggestions[] = [
                    'note' => trim($match[1]),
                    'paragraph' => trim($match[2])
                ];
            }
        }
        return $suggestions;
    }

    public function get_cached_suggestions($post_id, $cornerstone_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cpd_gemini_suggestions';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE post_id = %d AND cornerstone_id = %d", $post_id, $cornerstone_id));
        if ($row && !empty($row->suggestions_json)) {
            return json_decode($row->suggestions_json, true);
        }
        return false;
    }

    public function save_suggestions_cache($post_id, $cornerstone_id, $suggestions) {
        global $wpdb;
        $table = $wpdb->prefix . 'cpd_gemini_suggestions';
        $wpdb->replace($table, [
            'post_id' => $post_id,
            'cornerstone_id' => $cornerstone_id,
            'suggestions_json' => wp_json_encode($suggestions),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
    }

    public function get_or_generate_suggestions($post_id, $cornerstone_id, $post_content, $cornerstone_title, $cornerstone_url) {
        $cached = $this->get_cached_suggestions($post_id, $cornerstone_id);
        if ($cached) {
            return $cached;
        }
        $suggestions = $this->generate_suggestions($post_content, $cornerstone_title, $cornerstone_url);
        if (!isset($suggestions['error'])) {
            $this->save_suggestions_cache($post_id, $cornerstone_id, $suggestions);
        }
        return $suggestions;
    }

    public function clear_suggestions_cache($post_id = null, $cornerstone_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'cpd_gemini_suggestions';
        
        if ($post_id && $cornerstone_id) {
            // Clear specific post-cornerstone combination
            return $wpdb->delete($table, [
                'post_id' => $post_id,
                'cornerstone_id' => $cornerstone_id
            ]);
        } elseif ($post_id) {
            // Clear all suggestions for a specific post
            return $wpdb->delete($table, ['post_id' => $post_id]);
        } elseif ($cornerstone_id) {
            // Clear all suggestions for a specific cornerstone
            return $wpdb->delete($table, ['cornerstone_id' => $cornerstone_id]);
        } else {
            // Clear all suggestions (use with caution!)
            return $wpdb->query("TRUNCATE TABLE $table");
        }
    }

    public function get_suggestions_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'cpd_gemini_suggestions';
        
        $stats = [
            'total_suggestions' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'unique_posts' => $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM $table"),
            'unique_cornerstones' => $wpdb->get_var("SELECT COUNT(DISTINCT cornerstone_id) FROM $table"),
            'latest_update' => $wpdb->get_var("SELECT MAX(updated_at) FROM $table")
        ];
        
        return $stats;
    }
} 