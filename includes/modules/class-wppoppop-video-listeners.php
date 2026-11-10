<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Video Playback Listeners module: sanitize (save) + front attributes (render).
 * Shape: settings.video = { enable: bool, time: int seconds >= 0 }
 * When enabled, the popup opens while a video of the page plays: at once (time 0) or when the playback reaches "time" seconds.
 * The browser side is the package's module.js.
 */
class WpPopPop_Video_Listeners {

    const MODULE_KEY = 'acc-video';
    const MAX_TIME   = 86400;

    public static function defaults() {
        return array('enable' => false, 'time' => 0);
    }

    protected static function truthy($v) {
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v) || is_float($v)) {
            return $v != 0;
        }
        return is_string($v) && in_array(strtolower(trim($v)), array('1', 'true', 'yes', 'on'), true);
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        $t = (isset($raw['time']) && is_scalar($raw['time']) && is_numeric($raw['time'])) ? (int) floor((float) $raw['time']) : 0;
        return array(
            'enable' => self::truthy(isset($raw['enable']) ? $raw['enable'] : false),
            'time'   => max(0, min(self::MAX_TIME, $t)),
        );
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
            $h = 'wppoppop-ext-' . WpPopPop_Video_Listeners::MODULE_KEY . '-front';
            if (wp_script_is($h, 'enqueued') && !wp_script_is($h, 'done')) {
                wp_print_scripts($h);
            }
        }, 100);
    }

    /** Escaped data attributes for the popup wrapper, or '' (module off / listener not enabled). */
    public static function frontend($settings) {
        if (!is_array($settings) || !isset($settings['video']) || !is_array($settings['video'])) {
            return '';
        }
        $c = self::sanitize($settings['video']);
        if (!$c['enable'] || !self::module_active()) {
            return '';
        }
        self::print_late_script();
        return ' data-video-listen="1" data-video-time="' . (int) $c['time'] . '"';
    }
}
