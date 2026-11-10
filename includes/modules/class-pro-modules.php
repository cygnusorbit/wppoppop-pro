<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pro popup-settings modules: Popup Overlay, Success Celebration, Popup Positions, Targeting Triggers, Side Tabs,
 * Conditional Logic & Math, Payments, Video Listeners, Targeting, Quiz, Frequency and Custom Code.
 *
 * Core owns the save / template / render / eligibility pipeline and only calls the filters this class answers.
 * Saved values and the visitor-facing behaviour keep working whenever this plugin is active, so a lapsed licence
 * never breaks a live popup. Installing and configuring the modules is gated by the licence (see class-pro-plugin.php).
 */
class WpPopPop_Pro_Modules {

    /** Popup-settings keys sanitised here (core lists the same keys in WpPopPop_Module_Bridge::EXT_KEYS). */
    const KEYS = array(
        'overlay', 'celebration', 'popup_position', 'targeting_triggers', 'sidetab', 'logic_math',
        'payments', 'video', 'targeting', 'quiz', 'frequency', 'custom_code',
    );

    const CELEBRATION_EFFECTS = array('default', 'none', 'burst', 'cannon', 'fireworks', 'rain', 'stars', 'both');

    protected static $booted = false;

    /** Loads the moved module classes (class names unchanged) from includes/modules/. */
    public static function autoload($class) {
        if (strpos($class, 'WpPopPop_') !== 0 || strpos($class, 'WpPopPop_Pro_') === 0) {
            return;
        }
        $file = WPPOPPOP_PRO_PATH . 'includes/modules/class-wppoppop-' . strtolower(str_replace('_', '-', substr($class, 9))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }

    public static function boot() {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
        spl_autoload_register(array(__CLASS__, 'autoload'));

        add_filter('wppoppop_handled_settings_keys', array(__CLASS__, 'handled_keys'));
        add_filter('wppoppop_sanitize_popup_settings', array(__CLASS__, 'sanitize'), 10, 2);
        add_filter('wppoppop_popup_wrapper_attrs', array(__CLASS__, 'wrapper_attrs'), 10, 3);
        add_filter('wppoppop_popup_inline_css', array(__CLASS__, 'inline_css'), 10, 3);
        add_filter('wppoppop_popup_overlay', array(__CLASS__, 'overlay'), 10, 2);
        add_filter('wppoppop_popup_position', array(__CLASS__, 'position'), 10, 2);
        add_filter('wppoppop_pay_button_attrs', array(__CLASS__, 'pay_button_attrs'), 10, 2);
        add_filter('wppoppop_popup_eligible', array(__CLASS__, 'eligible'), 10, 3);
        add_action('wppoppop_admin_builder_assets', array(__CLASS__, 'builder_assets'), 20);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'front_assets'), 20);
    }

    /* ---------------------------------------------------------------- save / template */

    public static function handled_keys($keys) {
        return array_merge((array) $keys, self::KEYS);
    }

    /** Allow-lists every key in KEYS. $ctx = array(context => save|template, raw, prev, uid). */
    public static function sanitize($settings, $ctx) {
        if (!is_object($settings)) {
            return $settings;
        }
        $raw     = isset($ctx['raw']) && is_array($ctx['raw']) ? $ctx['raw'] : array();
        $prev    = isset($ctx['prev']) && is_array($ctx['prev']) ? $ctx['prev'] : array();
        $get     = function ($key) use ($raw) {
            return isset($raw[$key]) ? $raw[$key] : array();
        };
        $json = function ($v) {
            return json_decode(wp_json_encode($v));
        };

        $settings->overlay = $json(WpPopPop_Overlay::sanitize($get('overlay')));

        $cel = (isset($raw['celebration']) && is_object($raw['celebration']) && isset($raw['celebration']->effect)) ? (string) $raw['celebration']->effect : 'default';
        $settings->celebration = (object) array('effect' => in_array($cel, self::CELEBRATION_EFFECTS, true) ? $cel : 'default');

        $settings->popup_position     = $json(WpPopPop_Popup_Position::sanitize($get('popup_position')));
        $settings->targeting_triggers = $json(WpPopPop_Targeting_Triggers::sanitize($get('targeting_triggers')));
        $settings->sidetab            = $json(WpPopPop_Side_Tabs::sanitize($get('sidetab')));

        $lm = WpPopPop_Logic_Math::sanitize($get('logic_math'));
        if ($lm['formula'] !== '' || $lm['target'] !== '') {
            $settings->logic_math = $json($lm);
        }

        /* Defaults are never stored. */
        foreach (array('payments' => 'WpPopPop_Logic_Payments', 'video' => 'WpPopPop_Video_Listeners', 'targeting' => 'WpPopPop_Targeting', 'quiz' => 'WpPopPop_Quiz') as $key => $class) {
            $clean = call_user_func(array($class, 'sanitize'), $get($key));
            if ($clean !== call_user_func(array($class, 'defaults'))) {
                $settings->$key = $json($clean);
            }
        }

        if (isset($raw['frequency'])) {
            $settings->frequency = $json(WpPopPop_Frequency::sanitize($raw['frequency']));
        }

        /* Custom JS needs unfiltered_html; without it the stored JS is kept. */
        if (isset($raw['custom_code'])) {
            $prev_cc = (isset($prev['custom_code']) && is_array($prev['custom_code'])) ? $prev['custom_code'] : array();
            $cc      = WpPopPop_Custom_Code::sanitize($raw['custom_code'], $prev_cc, current_user_can('unfiltered_html'));
            if ($cc !== WpPopPop_Custom_Code::defaults()) {
                $settings->custom_code = $json($cc);
            }
        }

        return $settings;
    }

    /* ---------------------------------------------------------------- front end */

    public static function wrapper_attrs($attrs, $settings, $args) {
        $settings = is_array($settings) ? $settings : array();

        /* Active Targeting Triggers own load / exit / scroll / idle, so the default trigger attributes are dropped. */
        $tt = WpPopPop_Targeting_Triggers::frontend($settings);
        if ($tt['attrs'] !== '') {
            $attrs  = preg_replace('/\sdata-(?:trigger-load-delay|trigger-load|trigger-exit|exit-sens|trigger-scroll|trigger-idle|trigger-always|repeat-hours)="[^"]*"/', '', $attrs);
            $attrs .= $tt['attrs'];
        }
        if (empty($args['is_inline'])) {
            $attrs .= WpPopPop_Side_Tabs::frontend($settings);
        }
        $attrs .= WpPopPop_Logic_Math::frontend($settings);
        WpPopPop_Logic_Payments::context($settings);
        $attrs .= WpPopPop_Video_Listeners::frontend($settings);
        $attrs .= WpPopPop_Quiz::frontend($settings);
        $attrs .= WpPopPop_Frequency::frontend($settings);
        return $attrs;
    }

    public static function inline_css($css, $settings, $uid) {
        return $css . WpPopPop_Custom_Code::frontend(is_array($settings) ? $settings : array(), $uid);
    }

    public static function overlay($default, $settings) {
        return WpPopPop_Overlay::frontend(is_array($settings) ? $settings : array());
    }

    public static function position($default, $settings) {
        return WpPopPop_Popup_Position::frontend(is_array($settings) ? $settings : array());
    }

    public static function pay_button_attrs($attrs, $el) {
        return WpPopPop_Logic_Payments::button_attrs(is_array($el) ? $el : array());
    }

    public static function eligible($ok, $popup, $config) {
        if (!$ok) {
            return false;
        }
        $settings = (is_array($config) && isset($config['settings']) && is_array($config['settings'])) ? $config['settings'] : array();
        return WpPopPop_Targeting::is_eligible($settings) && WpPopPop_Targeting_Triggers::is_eligible($settings);
    }

    public static function front_assets() {
        wp_enqueue_script('wppoppop-front-targeting-triggers-js', WPPOPPOP_PRO_URL . 'assets/js/front/front-targeting-triggers.js', array('jquery', 'wppoppop-front-js'), WPPOPPOP_PRO_VERSION, true);
    }

    /* ---------------------------------------------------------------- builder */

    public static function builder_assets() {
        WpPopPop_Targeting_Triggers::localize_builder();
    }
}
