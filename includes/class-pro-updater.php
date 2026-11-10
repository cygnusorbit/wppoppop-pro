<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin updates through EDD Software Licensing: shows the update in Dashboard > Updates and the plugins list,
 * and serves the changelog popup. The store only returns a download package for a valid licence.
 */
class WpPopPop_Pro_Updater {

    const CACHE = 'wppoppop_pro_update_cache';

    public static function hooks() {
        add_filter('pre_set_site_transient_update_plugins', array(__CLASS__, 'inject'));
        add_filter('plugins_api', array(__CLASS__, 'info'), 10, 3);
    }

    protected static function to_array($v) {
        if (is_object($v)) {
            $v = get_object_vars($v);
        }
        if (is_string($v) && $v !== '') {
            $j = json_decode($v, true);
            if (is_array($j)) {
                return $j;
            }
            if (is_serialized($v)) {
                $u = unserialize($v, array('allowed_classes' => false));
                return is_array($u) ? $u : array();
            }
            return array();
        }
        return is_array($v) ? $v : array();
    }

    /** Latest release as reported by the store (cached 3 hours), or null. */
    public static function remote($force = false) {
        $key = WpPopPop_Pro_License::key();
        if ($key === '' || !WpPopPop_Pro_License::is_configured()) {
            return null;
        }
        if (!$force) {
            $c = get_site_transient(self::CACHE);
            if (is_array($c)) {
                return isset($c['release']) && is_object($c['release']) ? $c['release'] : null;
            }
        }
        $r = WpPopPop_Pro_License::remote('get_version', $key, array(
            'slug'    => dirname(WPPOPPOP_PRO_BASENAME),
            'version' => WPPOPPOP_PRO_VERSION,
            'beta'    => false,
        ));
        if (is_wp_error($r) || empty($r->new_version) || !is_string($r->new_version)) {
            set_site_transient(self::CACHE, array('release' => null), HOUR_IN_SECONDS);
            return null;
        }
        $r->slug        = dirname(WPPOPPOP_PRO_BASENAME);
        $r->plugin      = WPPOPPOP_PRO_BASENAME;
        $r->sections    = self::to_array(isset($r->sections) ? $r->sections : array());
        $r->banners     = self::to_array(isset($r->banners) ? $r->banners : array());
        $r->package     = !empty($r->package) ? esc_url_raw((string) $r->package) : '';
        $r->download_link = !empty($r->download_link) ? esc_url_raw((string) $r->download_link) : $r->package;
        set_site_transient(self::CACHE, array('release' => $r), 3 * HOUR_IN_SECONDS);
        return $r;
    }

    public static function inject($transient) {
        if (!is_object($transient)) {
            return $transient;
        }
        $r = self::remote();
        if ($r && version_compare(WPPOPPOP_PRO_VERSION, $r->new_version, '<')) {
            $transient->response[WPPOPPOP_PRO_BASENAME] = $r;
        }
        return $transient;
    }

    public static function info($result, $action, $args) {
        if ($action !== 'plugin_information' || !is_object($args) || empty($args->slug) || $args->slug !== dirname(WPPOPPOP_PRO_BASENAME)) {
            return $result;
        }
        $r = self::remote();
        return $r ? $r : $result;
    }
}
