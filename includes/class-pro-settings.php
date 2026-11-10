<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds the Pro sections (statistics, A/B testing, cache) to Settings > Tracking.
 * Saved values live in core's wppoppop_settings option, so they survive if Pro is switched off.
 */
class WpPopPop_Pro_Settings {

    public static function hooks() {
        add_action('wppoppop_tracking_tab_sections', array(__CLASS__, 'sections'), 10, 3);
    }

    public static function sections($t, $chk, $num) {
        include WPPOPPOP_PRO_PATH . 'templates/tracking-sections.php';
    }
}
