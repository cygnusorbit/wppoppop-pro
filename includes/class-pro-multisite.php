<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Multisite support for Pro. Its tables, options and schedules are per site, so activation, deactivation and
 * uninstall visit every site on a network. This class does not need WpPopPop core to be loaded.
 */
class WpPopPop_Pro_Multisite {

    const BATCH = 100;

    public static function hooks() {
        /* Core fires this for every site it sets up (activation, network activation, new site). */
        add_action('wppoppop_site_setup', array('WpPopPop_Pro_DB', 'maybe_install'));
        add_filter('wppoppop_site_tables', array(__CLASS__, 'site_tables'));
    }

    /** Pro's table suffixes, dropped together with core's when a site is deleted from the network. */
    public static function site_tables($tables) {
        $tables   = (array) $tables;
        $tables[] = 'wppoppop_campaigns';
        $tables[] = 'wppoppop_stats_daily';
        return $tables;
    }

    /** Activation callback: network activation creates the tables on every site. */
    public static function activate($network_wide = false) {
        if (is_multisite() && $network_wide) {
            self::each_site(array('WpPopPop_Pro_DB', 'install'));
            return;
        }
        WpPopPop_Pro_DB::install();
    }

    /** Deactivation callback: network deactivation clears the schedules of every site. */
    public static function deactivate($network_wide = false) {
        if (is_multisite() && $network_wide) {
            self::each_site(array(__CLASS__, 'clear_schedules'));
            return;
        }
        self::clear_schedules();
    }

    public static function clear_schedules() {
        wp_clear_scheduled_hook('wppoppop_tracking_daily');
        wp_clear_scheduled_hook('wppoppop_weekly_report');
    }

    /** Runs $callback(blog_id) inside every site, in batches. $args are passed to get_sites(). */
    public static function each_site($callback, array $args = array()) {
        if (!is_multisite()) {
            call_user_func($callback, get_current_blog_id());
            return;
        }
        $offset = 0;
        do {
            $ids = get_sites(array_merge($args, array(
                'fields'  => 'ids',
                'number'  => self::BATCH,
                'offset'  => $offset,
                'orderby' => 'id',
                'order'   => 'ASC',
            )));
            foreach ($ids as $blog_id) {
                switch_to_blog((int) $blog_id);
                try {
                    call_user_func($callback, (int) $blog_id);
                } finally {
                    restore_current_blog();
                }
            }
            $offset += self::BATCH;
        } while (count($ids) === self::BATCH);
    }
}
