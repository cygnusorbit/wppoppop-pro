<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

wp_clear_scheduled_hook('wppoppop_pro_license_check');
delete_option('wppoppop_pro_license');
delete_option('wppoppop_pro_seed_ver');
delete_site_transient('wppoppop_pro_update_cache');
wp_clear_scheduled_hook('wppoppop_tracking_daily');
wp_clear_scheduled_hook('wppoppop_weekly_report');

/* Pro tables and data are removed only when "clean on uninstall" is enabled in WpPopPop settings. */
$wppoppop_pro_cfg = get_option('wppoppop_settings', array());
if (is_array($wppoppop_pro_cfg) && !empty($wppoppop_pro_cfg['clean_on_uninstall'])) {
    global $wpdb;
    $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . 'wppoppop_campaigns`');
    $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . 'wppoppop_stats_daily`');
    delete_option('wppoppop_pro_db');
    delete_option('wppoppop_stats_db');
    delete_option('wppoppop_stats_labels');
}
