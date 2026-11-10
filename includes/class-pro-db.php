<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database tables owned by the Pro plugin: A/B campaigns and daily impression counters.
 * The schema is identical to the one older core versions created, so existing sites keep their data.
 */
class WpPopPop_Pro_DB {

    const VER = '1';
    const OPT = 'wppoppop_pro_db';

    public static function hooks() {
        add_action('init', array(__CLASS__, 'maybe_install'));
        add_action('wppoppop_create_tables', array(__CLASS__, 'install'));
    }

    public static function campaigns() {
        global $wpdb;
        return $wpdb->prefix . 'wppoppop_campaigns';
    }

    public static function daily() {
        global $wpdb;
        return $wpdb->prefix . 'wppoppop_stats_daily';
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $cc = $wpdb->get_charset_collate();

        dbDelta('CREATE TABLE ' . self::campaigns() . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            uid varchar(64) NOT NULL,
            title varchar(255) NOT NULL,
            popup_uids longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY uid (uid)
        ) {$cc};");

        dbDelta('CREATE TABLE ' . self::daily() . " (
            stat_date date NOT NULL,
            popup_uid varchar(64) NOT NULL,
            impressions bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (stat_date,popup_uid)
        ) {$cc};");

        update_option(self::OPT, self::VER, false);
    }

    public static function maybe_install() {
        if (get_option(self::OPT) !== self::VER) {
            self::install();
        }
    }
}
