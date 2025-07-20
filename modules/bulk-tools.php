<?php
/**
 * Bulk Tools - Provides tools for bulk SEO operations
 */
class Bulk_Tools {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_bulk_tools_menu']);
        require_once __DIR__ . '/../templates/dashboard.php';
        new Performance_Dashboard();
    }

    /**
     * Register admin menu for bulk tools
     */
    public function register_bulk_tools_menu() {
        add_menu_page(
            'SEO Bulk Tools',
            'SEO Bulk Tools',
            'manage_options',
            'seo-bulk-tools',
            [$this, 'render_bulk_tools_page'],
            'dashicons-performance'
        );
    }

    /**
     * Render the bulk tools dashboard
     */
    public function render_bulk_tools_page() {
        // Render the bulk tools dashboard
        echo '<div class="wrap"><h1>SEO Bulk Tools</h1>';
        echo '<div class="seo-bulk-tools-container">';
        
        // Audit section
        echo '<div class="card">';
        echo '<h2>Content Audit</h2>';
        echo '<p>Analyze all content for SEO improvements</p>';
        echo '<button class="button button-primary" onclick="runSEOAudit()">Run SEO Audit</button>';
        echo '</div>';
        
        // Meta descriptions section
        echo '<div class="card">';
        echo '<h2>Meta Descriptions</h2>';
        echo '<p>Update meta descriptions for all content</p>';
        echo '<button class="button button-primary" onclick="updateMetaDescriptions()">Update Meta Descriptions</button>';
        echo '</div>';
        
        // Link fixing section
        echo '<div class="card">';
        echo '<h2>Internal Links</h2>';
        echo '<p>Fix broken internal links</p>';
        echo '<button class="button button-primary" onclick="fixInternalLinks()">Fix Internal Links</button>';
        echo '</div>';
        
        echo '</div>'; // .seo-bulk-tools-container
        echo '</div>'; // .wrap
        
        // Add JavaScript for AJAX actions
        echo '<script>
        function runSEOAudit() {
            jQuery.post(ajaxurl, {action: "run_seo_audit"}, function(response) {
                alert("SEO Audit completed: " + response.message);
            });
        }
        function updateMetaDescriptions() {
            jQuery.post(ajaxurl, {action: "update_meta_descriptions"}, function(response) {
                alert("Meta descriptions updated: " + response.message);
            });
        }
        function fixInternalLinks() {
            jQuery.post(ajaxurl, {action: "fix_internal_links"}, function(response) {
                alert("Internal links fixed: " + response.message);
            });
        }
        </script>';
    }

    /**
     * Run bulk SEO audit
     *
     * @return array Results of the audit
     */
    public function run_seo_audit() {
        $results = [
            'total_posts' => 0,
            'optimized' => 0,
            'needs_attention' => 0,
            'issues' => []
        ];
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        foreach ($posts as $post) {
            $results['total_posts']++;
            
            // Check if post has focus keyword
            if (!get_post_meta($post->ID, '_focus_keyword', true)) {
                $results['needs_attention']++;
                $results['issues'][] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'issue' => 'Missing focus keyword'
                ];
            } else {
                $results['optimized']++;
            }
        }
        
        return $results;
    }

    /**
     * Update meta descriptions in bulk
     *
     * @return array Update results
     */
    public function update_meta_descriptions() {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        $updated = 0;
        foreach ($posts as $post) {
            // Skip if manual override exists
            if (get_post_meta($post->ID, '_seo_override', true)) continue;
            
            $excerpt = wp_trim_words($post->post_content, 25);
            update_post_meta($post->ID, '_meta_description', $excerpt);
            $updated++;
        }
        
        return [
            'total_posts' => count($posts),
            'updated' => $updated
        ];
    }

    /**
     * Fix internal links in content
     *
     * @return array Fix results
     */
    public function fix_internal_links() {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        $fixed = 0;
        foreach ($posts as $post) {
            $content = $post->post_content;
            $original = $content;
            
            // Replace /page.html with /page/
            $content = preg_replace('/(href="[^"]+)\.html(")/i', '$1/$2', $content);
            
            if ($content !== $original) {
                wp_update_post(['ID' => $post->ID, 'post_content' => $content]);
                $fixed++;
            }
        }
        
        return [
            'total_posts' => count($posts),
            'fixed' => $fixed
        ];
    }
}