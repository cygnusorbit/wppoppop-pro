<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Removes Pro's data when the plugin is deleted. This replaces uninstall.php: WordPress ignores a plugin's
 * uninstall hook when uninstall.php exists, and the Freemius SDK relies on that hook. With Freemius on, the SDK's
 * hook calls run() through its after_uninstall action; without it, register() registers run() directly.
 * On a network every site is cleaned in turn. Delete Pro before WpPopPop: WpPopPop's own cleanup removes the
 * "Clean on uninstall" setting that Pro reads here.
 */
class WpPopPop_Pro_Uninstall {

    public static function register() {
        if (WpPopPop_Pro_Freemius::is_configured()) {
            return;
        }
        register_uninstall_hook(WPPOPPOP_PRO_FILE, array(__CLASS__, 'run'));
    }

    public static function run() {
        delete_site_transient('wppoppop_pro_update_cache');
        WpPopPop_Pro_Multisite::each_site(array(__CLASS__, 'run_site'));
    }

    /** Cleans the current site. Tables and data go only when "clean on uninstall" is enabled in WpPopPop settings. */
    public static function run_site() {
        wp_clear_scheduled_hook('wppoppop_pro_license_check');
        wp_clear_scheduled_hook('wppoppop_tracking_daily');
        wp_clear_scheduled_hook('wppoppop_weekly_report');
        delete_option('wppoppop_pro_license');
        delete_option('wppoppop_pro_seed_ver');

        $cfg = get_option('wppoppop_settings', array());
        if (is_array($cfg) && !empty($cfg['clean_on_uninstall'])) {
            global $wpdb;
            $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . 'wppoppop_campaigns`');
            $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . 'wppoppop_stats_daily`');
            delete_option('wppoppop_pro_db');
            delete_option('wppoppop_stats_db');
            delete_option('wppoppop_stats_labels');
        }
    }
}
