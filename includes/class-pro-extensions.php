<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pro runtime extensions: Google Analytics events, AdBlock detection and the Canvas Confetti,
 * Canvas Fireworks, Signature Pad and Math Formula Parser libraries.
 *
 * Values are stored in core's wppoppop_settings option under the same keys core always used,
 * so settings made before these features moved to Pro keep working.
 */
class WpPopPop_Pro_Extensions {

    /** Settings keys owned by this class (option key => default). */
    public static function defaults_map() {
        return array(
            'ga_tracking'           => 0,
            'adblock_detector'      => 0,
            'load_canvas_confetti'  => 0,
            'load_canvas_fireworks' => 0,
            'signature_pad'         => 0,
            'load_signature_pad'    => 0,
            'js_parser'             => 0,
            'load_math_parser'      => 0,
        );
    }

    /** Script handle => array(setting keys, file in assets/lib, version). */
    protected static function libraries() {
        return array(
            'wppoppop-canvas-confetti'  => array(array('load_canvas_confetti'), 'confetti.browser.min.js', '1.9.2'),
            'wppoppop-canvas-fireworks' => array(array('load_canvas_fireworks'), 'index.umd.js', '2.10.7'),
            'wppoppop-signature-pad'    => array(array('signature_pad', 'load_signature_pad'), 'signature_pad.umd.min.js', '4.1.7'),
            'wppoppop-math-parser'      => array(array('js_parser', 'load_math_parser'), 'bundle.min.js', '2.0.2'),
        );
    }

    public static function hooks() {
        add_filter('wppoppop_factory_defaults', array(__CLASS__, 'factory_defaults'));
        add_filter('wppoppop_sanitize_global_settings', array(__CLASS__, 'sanitize'), 10, 2);
        add_filter('wppoppop_settings_tab_keys', array(__CLASS__, 'tab_keys'));
        add_action('wppoppop_settings_performance_rows', array(__CLASS__, 'performance_rows'));
        add_action('wppoppop_settings_library_extensions', array(__CLASS__, 'library_rows'));
        add_action('wppoppop_front_enqueue', array(__CLASS__, 'enqueue'));
        add_filter('wppoppop_front_vars', array(__CLASS__, 'front_vars'));
        add_action('wppoppop_front_footer', array(__CLASS__, 'footer'));
    }

    public static function unlocked() {
        return WpPopPop_Pro_License::is_unlocked();
    }

    protected static function on($keys) {
        foreach ((array) $keys as $key) {
            if (wppoppop_get_setting($key, 0)) {
                return true;
            }
        }
        return false;
    }

    /* ------------------------------------------------------------- settings data */

    public static function factory_defaults($defaults) {
        return (array) $defaults + self::defaults_map();
    }

    /** A key posted from the settings form wins; otherwise the value core already kept is retained. */
    public static function sanitize($o, $in) {
        $in = (array) $in;
        foreach (array('ga_tracking', 'adblock_detector', 'load_canvas_confetti', 'load_canvas_fireworks') as $key) {
            if (array_key_exists($key, $in)) {
                $o[$key] = empty($in[$key]) ? 0 : 1;
            } elseif (!isset($o[$key])) {
                $o[$key] = 0;
            }
        }
        foreach (array(array('signature_pad', 'load_signature_pad'), array('js_parser', 'load_math_parser')) as $pair) {
            if (array_key_exists($pair[0], $in) || array_key_exists($pair[1], $in)) {
                $val = (!empty($in[$pair[0]]) || !empty($in[$pair[1]])) ? 1 : 0;
            } else {
                $val = (!empty($o[$pair[0]]) || !empty($o[$pair[1]])) ? 1 : 0;
            }
            $o[$pair[0]] = $val;
            $o[$pair[1]] = $val;
        }
        return $o;
    }

    public static function tab_keys($map) {
        $map = (array) $map;
        $map['performance'] = array_merge(isset($map['performance']) ? $map['performance'] : array(), array('ga_tracking', 'adblock_detector'));
        $map['libraries']   = array_merge(isset($map['libraries']) ? $map['libraries'] : array(), array(
            'load_canvas_confetti', 'load_canvas_fireworks', 'signature_pad', 'load_signature_pad', 'js_parser', 'load_math_parser',
        ));
        return $map;
    }

    /* ------------------------------------------------------------------ admin UI */

    public static function locked_notice() {
        if (self::unlocked()) {
            return;
        }
        printf(
            '<p class="description">%s <a href="%s">%s</a></p>',
            esc_html__('Needs an active WpPopPop Pro licence. Your saved choice is kept.', 'wppoppop-pro'),
            esc_url(WpPopPop_Pro_License::activate_url()),
            esc_html__('Activate licence', 'wppoppop-pro')
        );
    }

    public static function performance_rows($settings) {
        $s = is_array($settings) ? $settings : array();
        include WPPOPPOP_PRO_PATH . 'templates/settings-performance-rows.php';
    }

    public static function library_rows($settings) {
        $s = is_array($settings) ? $settings : array();
        include WPPOPPOP_PRO_PATH . 'templates/settings-library-rows.php';
    }

    /* ------------------------------------------------------------------ frontend */

    public static function enqueue() {
        if (!self::unlocked()) {
            return;
        }
        foreach (self::libraries() as $handle => $lib) {
            if (self::on($lib[0])) {
                wp_enqueue_script($handle, WPPOPPOP_PRO_URL . 'assets/lib/' . $lib[1], array(), $lib[2], true);
            }
        }
        if (self::on(array('ga_tracking', 'adblock_detector'))) {
            wp_enqueue_script('wppoppop-pro-runtime', WPPOPPOP_PRO_URL . 'assets/js/front/pro-runtime.js', array('jquery', 'wppoppop-front-js'), WPPOPPOP_PRO_VERSION, true);
        }
    }

    public static function front_vars($vars) {
        $vars = (array) $vars;
        $on   = self::unlocked();
        $vars['ga_tracking']      = $on && self::on('ga_tracking');
        $vars['adblock_detector'] = $on && self::on('adblock_detector');
        return $vars;
    }

    public static function footer() {
        if (!self::unlocked() || !self::on('adblock_detector')) {
            return;
        }
        ?>
        <div id="wppoppop-ad-bait" class="pub_300x250 pub_728x90 text-ad textAd text_ad text_ads text-ads ad-server" style="position:absolute!important;left:-9999px!important;top:-9999px!important;width:1px!important;height:1px!important;pointer-events:none!important;" aria-hidden="true">&nbsp;</div>
        <script>
        (function() {
            var probe = document.getElementById('wppoppop-ad-bait');
            window.wppoppop_adblock_detected = false;
            if (!probe || probe.offsetHeight === 0 || probe.clientHeight === 0 || window.getComputedStyle(probe).display === 'none') {
                window.wppoppop_adblock_detected = true;
            }
        })();
        </script>
        <?php
    }
}
