<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frequency Capping module: sanitize (save) + front attributes (render).
 * Shape: settings.frequency = { mode: always|session|days, days: int }
 * Decides how long a closed popup stays hidden: always = it comes back on the next page view, session = until the browser
 * session ends, days = for that many days. The browser side is the package's module.js.
 */
class WpPopPop_Frequency {

    const MODULE_KEY = 'acc-frequency';
    const MAX_DAYS   = 365;

    public static function modes() {
        return array('always', 'session', 'days');
    }

    public static function defaults() {
        return array('mode' => 'always', 'days' => 7);
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        $d    = self::defaults();
        $mode = (isset($raw['mode']) && is_string($raw['mode']) && in_array($raw['mode'], self::modes(), true)) ? $raw['mode'] : $d['mode'];
        $days = $d['days'];
        if (isset($raw['days']) && is_scalar($raw['days']) && is_numeric($raw['days'])) {
            $days = max(1, min(self::MAX_DAYS, (int) floor((float) $raw['days'])));
        }
        return array('mode' => $mode, 'days' => $days);
    }

    /** Settings > Module switch. A module that is switched off or deleted never affects the front end. */
    public static function module_active() {
        if (!class_exists('WpPopPop_Modules')) {
            return true;
        }
        /* Not registered: the bundled package is seeded on the first admin load, so stay on until then. A deleted module is off. */
        if (!WpPopPop_Modules::get(self::MODULE_KEY)) {
            $orphans = WpPopPop_Modules::orphans();
            return !isset($orphans[self::MODULE_KEY]);
        }
        if (!class_exists('WpPopPop_Admin_Settings')) {
            require_once WPPOPPOP_PATH . 'includes/admin/class-admin-settings.php';
        }
        return WpPopPop_Admin_Settings::module_enabled(self::MODULE_KEY);
    }

    /**
     * The popup is rendered at wp_footer priority 50, after WordPress printed the footer scripts (priority 20),
     * so the module.js queued by the engine would never reach the page. Print it once, after the popup markup.
     */
    protected static function print_late_script() {
        static $hooked = false;
        if ($hooked) {
            return;
        }
        $hooked = true;
        add_action('wp_footer', function () {
            $h = 'wppoppop-ext-' . WpPopPop_Frequency::MODULE_KEY . '-front';
            if (wp_script_is($h, 'enqueued') && !wp_script_is($h, 'done')) {
                wp_print_scripts($h);
            }
        }, 100);
    }

    /** Escaped data attributes for the popup wrapper, or '' (module off / nothing saved). */
    public static function frontend($settings) {
        if (!is_array($settings) || !isset($settings['frequency']) || !is_array($settings['frequency'])) {
            return '';
        }
        if (!self::module_active()) {
            return '';
        }
        $c = self::sanitize($settings['frequency']);
        self::print_late_script();
        return ' data-freq-mode="' . esc_attr($c['mode']) . '" data-freq-days="' . (int) $c['days'] . '"';
    }
}
