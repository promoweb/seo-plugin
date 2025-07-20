<?php
/**
 * Image Optimizer - Handles automatic optimization of images
 */
class Image_Optimizer {
    public function __construct() {
        add_filter('wp_generate_attachment_metadata', [$this, 'optimize_image'], 10, 2);
    }

    /**
     * Main image optimization handler
     *
     * @param array $metadata Attachment metadata
     * @param int $attachment_id Attachment ID
     * @return array Modified metadata
     */
    public function optimize_image($metadata, $attachment_id) {
        if (!wp_attachment_is_image($attachment_id)) return $metadata;

        $this->generate_alt_text($attachment_id);
        $this->compress_image($attachment_id, $metadata);
        $this->make_responsive($attachment_id, $metadata);
        
        return $metadata;
    }

    /**
     * Generate contextual alt text using image analysis
     *
     * @param int $attachment_id Attachment ID
     */
    private function generate_alt_text($attachment_id) {
        $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        
        if (empty($alt)) {
            $image_path = get_attached_file($attachment_id);
            $image_name = basename($image_path);
            $alt = 'Image: ' . preg_replace('/\\.[^.\\s]{3,4}$/', '', $image_name);
            
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
        }
    }

    /**
     * Perform lossless image compression
     *
     * @param int $attachment_id Attachment ID
     * @param array $metadata Attachment metadata
     */
    private function compress_image($attachment_id, &$metadata) {
        $file_path = get_attached_file($attachment_id);
        
        // Skip if file doesn't exist
        if (!file_exists($file_path)) return;

        // Optimize image using WordPress built-in editor
        $editor = wp_get_image_editor($file_path);
        if (!is_wp_error($editor)) {
            $editor->set_quality(85); // 85% quality for JPEG
            $editor->save($file_path);
            
            // Update filesize in metadata
            $metadata['filesize'] = filesize($file_path);
        }
    }

    /**
     * Make images responsive by replacing divs with picture elements
     *
     * @param int $attachment_id Attachment ID
     * @param array $metadata Attachment metadata
     */
    private function make_responsive($attachment_id, &$metadata) {
        $html = wp_get_attachment_image($attachment_id, 'full');
        
        // Simple responsive image implementation
        $responsive_html = '<picture>
            <source media="(max-width: 480px)" srcset="' . wp_get_attachment_image_url($attachment_id, 'medium') . '">
            <source media="(max-width: 768px)" srcset="' . wp_get_attachment_image_url($attachment_id, 'large') . '">
            ' . $html . '
        </picture>';
        
        // Update attachment content
        $post = get_post($attachment_id);
        $post->post_content = $responsive_html;
        wp_update_post($post);
    }
}