<?php
/**
 * Plugin Name: Ultimate SEO Optimizer Suite
 * Description: Ottimizzazione SEO avanzata con automazione non-invasiva per contenuti nuovi ed esistenti
 * Version: 1.0.0
 * Requires PHP: 7.4
 */

// Include modules
require_once __DIR__ . '/modules/content-optimizer.php';
require_once __DIR__ . '/modules/image-optimizer.php';
require_once __DIR__ . '/modules/bulk-tools.php';

class Ultimate_SEO_Optimizer {
    public function __construct() {
        new Content_Optimizer();
        new Image_Optimizer();
        new Bulk_Tools();
    }
}
new Ultimate_SEO_Optimizer();