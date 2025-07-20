<?php
/**
 * Content Optimizer - Handles real-time SEO optimization for post content
 */
class Content_Optimizer {
    private $content_versions = [];

    public function __construct() {
        add_action('save_post', [$this, 'smart_content_optimization'], 10, 3);
    }

    /**
     * Main content optimization handler
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an existing post being updated
     */
    public function smart_content_optimization($post_id, $post, $update) {
        if (wp_is_post_autosave($post_id) || !current_user_can('edit_post', $post_id)) return;
        
        $this->backup_content_version($post_id);
        if (!$this->has_manual_seo($post_id)) {
            $this->generate_dynamic_meta($post);
            $this->optimize_content_structure($post);
        }
        $this->inject_schema_markup($post_id);
        
        // Log audit trail
        SEO_Security::log_audit_trail('content_optimized', [
            'post_id' => $post_id,
            'optimizations' => ['meta_generated', 'structure_optimized']
        ]);
    }

    /**
     * Backup current content version before modifications
     *
     * @param int $post_id Post ID
     */
    private function backup_content_version($post_id) {
        $this->content_versions[$post_id] = get_post_field('post_content', $post_id);
    }

    /**
     * Check if manual SEO overrides exist
     *
     * @param int $post_id Post ID
     * @return bool True if manual overrides exist
     */
    private function has_manual_seo($post_id) {
        return metadata_exists('post', $post_id, '_seo_override');
    }

    /**
     * Generate dynamic SEO meta tags
     *
     * @param WP_Post $post Post object
     */
    private function generate_dynamic_meta($post) {
        // Calculate readability score
        $readability = $this->calculate_readability($post->post_content);
        
        // Generate meta description
        $excerpt = wp_trim_words($post->post_content, 25);
        update_post_meta($post->ID, '_meta_description', $excerpt);
        
        // Generate focus keyword
        $keywords = $this->extract_keywords($post->post_content);
        update_post_meta($post->ID, '_focus_keyword', $keywords[0]);
    }

    /**
     * Optimize content structure
     *
     * @param WP_Post $post Post object
     */
    private function optimize_content_structure($post) {
        $content = $post->post_content;
        
        // Ensure single H1
        if (substr_count($content, '<h1') > 1) {
            $content = preg_replace('/<h1(.*?)>(.*?)<\/h1>/', '<h2$1>$2</h2>', $content);
        }
        
        // Add missing alt attributes
        $content = preg_replace_callback('/<img((?:(?!alt=).)*?)>/', function($matches) {
            if (!strpos($matches[1], 'alt=')) {
                return '<img alt="" ' . $matches[1] . '>';
            }
            return $matches[0];
        }, $content);
        
        wp_update_post(['ID' => $post->ID, 'post_content' => $content]);
    }

    /**
     * Inject structured data markup
     *
     * @param int $post_id Post ID
     */
    private function inject_schema_markup($post_id) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title($post_id),
            'datePublished' => get_the_date('c', $post_id),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', get_post_field('post_author', $post_id))
            ]
        ];
        
        update_post_meta($post_id, '_schema_markup', json_encode($schema));
    }

    /**
     * Calculate Flesch-Kincaid readability score
     *
     * @param string $content Post content
     * @return float Readability score
     */
    private function calculate_readability($content) {
        $words = str_word_count(strip_tags($content));
        $sentences = preg_match_all('/[.!?]+/', $content);
        $syllables = preg_match_all('/[aeiouy]+/i', $content);
        
        return 206.835 - 1.015 * ($words/$sentences) - 84.6 * ($syllables/$words);
    }

    /**
     * Extract keywords from content
     *
     * @param string $content Post content
     * @return array Top keywords
     */
    private function extract_keywords($content) {
        $content = strip_tags($content);
        $words = str_word_count($content, 1);
        $freq = array_count_values($words);
        arsort($freq);
        return array_slice(array_keys($freq), 0, 5);
    }
}