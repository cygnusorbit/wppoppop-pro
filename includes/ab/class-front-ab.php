<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WpPopPop Front: [wppoppop_ab uid="..."] shortcode.
 * Picks one variation of an A/B campaign and renders it through [wppoppop].
 * Visitors keep their variant via a 30-day cookie.
 */
class WpPopPop_Front_Ab {
    protected static $members = null;

    public static function reset_cache() {
        self::$members = null;
    }

    protected static function ab_default_mode() {
        return WpPopPop_Tracking::get('ab_default_mode') === 'onload' ? 'onload' : 'inline';
    }

    protected static function ab_cookie_days() {
        return max(1, (int) WpPopPop_Tracking::get('ab_cookie_days'));
    }

    protected static function ab_no_cache() {
        if (!WpPopPop_Tracking::get('cache_nocache_ab')) {
            return;
        }
        if (!defined('DONOTCACHEPAGE')) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
            define('DONOTCACHEPAGE', true);
        }
        if (!headers_sent()) {
            nocache_headers();
        }
    }

    protected static function ab_sticky_get($uid, $chosen, $variants) {
        if (WpPopPop_Tracking::get('ab_sticky_user') && is_user_logged_in()) {
            $m = sanitize_key((string) get_user_meta(get_current_user_id(), 'wppoppop_ab_' . $uid, true));
            if ($m !== '' && in_array($m, (array) $variants, true)) {
                return $m;
            }
        }
        return $chosen;
    }

    protected static function ab_sticky_set($uid, $chosen) {
        if (WpPopPop_Tracking::get('ab_sticky_user') && is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'wppoppop_ab_' . $uid, $chosen);
        }
    }

    /* True when the popup belongs to any active A/B campaign (must only render via [wppoppop_ab]) */
    public static function is_ab_member($uid) {
        global $wpdb;
        if (self::$members === null) {
            self::$members = [];
            $rows = $wpdb->get_col("SELECT popup_uids FROM {$wpdb->prefix}wppoppop_campaigns WHERE LOWER(status) = 'active'");
            foreach ((array) $rows as $json) {
                foreach ((array) json_decode($json, true) as $m) {
                    self::$members[sanitize_key($m)] = true;
                }
            }
        }
        return isset(self::$members[$uid]);
    }

    public function __construct() {
        add_shortcode('wppoppop_ab', [$this, 'render']);
    }

    protected function get_campaign($uid) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare("SELECT uid, popup_uids, status FROM {$wpdb->prefix}wppoppop_campaigns WHERE uid = %s", $uid),
            ARRAY_A
        );
    }

    protected function published_variants($uids) {
        global $wpdb;
        $uids = array_values(array_filter(array_map('sanitize_key', (array) $uids)));
        if (empty($uids)) {
            return [];
        }
        $ph   = implode(',', array_fill(0, count($uids), '%s'));
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT uid FROM {$wpdb->prefix}wppoppop_items WHERE status = 'publish' AND uid IN ($ph)",
            $uids
        ));
        // keep the campaign's original order
        return array_values(array_intersect($uids, (array) $rows));
    }

    public function render($atts) {
        $atts = shortcode_atts([
            'uid'   => '',
            'mode'  => self::ab_default_mode(),
            'text'  => __('Open Popup', 'wppoppop-pro'),
            'class' => '',
        ], $atts, 'wppoppop_ab');

        $uid = sanitize_key($atts['uid']);
        if ($uid === '') {
            return current_user_can('manage_options') ? '<!-- WpPopPop A/B: missing uid -->' : '';
        }

        $camp = $this->get_campaign($uid);
        if (!$camp || strtolower((string) $camp['status']) !== 'active') {
            return current_user_can('manage_options') ? '<!-- WpPopPop A/B: campaign not found or not active -->' : '';
        }

        $variants = $this->published_variants(json_decode($camp['popup_uids'], true));
        if (empty($variants)) {
            return current_user_can('manage_options') ? '<!-- WpPopPop A/B: no published variations -->' : '';
        }

        self::ab_no_cache();
        $ab_set_js = false; /* WPPOPPOP_AB_JS_COOKIE */
        $cookie = 'wppoppop_ab_' . $uid;
        $chosen = isset($_COOKIE[$cookie]) ? sanitize_key(wp_unslash($_COOKIE[$cookie])) : '';
        $chosen = self::ab_sticky_get($uid, $chosen, $variants);

        /* WPPOPPOP_AB_FORCE: admin-only preview, e.g. ?wppoppop_ab_force=B or =<popup uid>. Never writes the cookie. */
        if (current_user_can('manage_options') && isset($_GET['wppoppop_ab_force'])) {
            $force = sanitize_key(wp_unslash($_GET['wppoppop_ab_force']));
            $order = array_values(array_filter(array_map('sanitize_key', (array) json_decode($camp['popup_uids'], true))));
            if (strlen($force) === 1 && ctype_alpha($force)) {
                $idx = ord(strtoupper($force)) - 65;
                $force = isset($order[$idx]) ? $order[$idx] : '';
            }
            if ($force !== '' && in_array($force, $variants, true)) {
                $chosen = $force;
            }
        }
        if ($chosen === '' || !in_array($chosen, $variants, true)) {
            $chosen = $variants[array_rand($variants)];
            $ab_set_js = true;
            $_COOKIE[$cookie] = $chosen;
            self::ab_sticky_set($uid, $chosen);
        }

        $sc = '[wppoppop uid="' . esc_attr($chosen) . '" mode="' . esc_attr($atts['mode']) . '"';
        if ($atts['mode'] === 'button') {
            $sc .= ' text="' . esc_attr($atts['text']) . '" class="' . esc_attr($atts['class']) . '"';
        }
        $sc .= ']';
        $ab_out = do_shortcode($sc);
        if ($ab_set_js) {
            $ab_days = self::ab_cookie_days();
            $ab_path = (defined('COOKIEPATH') && COOKIEPATH) ? COOKIEPATH : '/';
            $ab_out .= '<script>(function(){try{var n=' . wp_json_encode($cookie) . ',v=' . wp_json_encode($chosen)
                . ',d=new Date();d.setTime(d.getTime()+' . (int) $ab_days . '*864e5);'
                . 'document.cookie=n+"="+encodeURIComponent(v)+"; expires="+d.toUTCString()+"; path=' . esc_js($ab_path)
                . '; SameSite=Lax"+(location.protocol==="https:"?"; Secure":"");}catch(e){}})();</script>';
        }
        return $ab_out;
    }
}
