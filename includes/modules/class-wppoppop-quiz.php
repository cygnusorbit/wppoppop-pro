<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Quiz & Lead Scoring module: sanitize (save) + front attributes (render).
 * Shape: settings.quiz = { enable: bool, pass_threshold: int, pass_canvas: int, fail_canvas: int, confetti: bool }
 * Radio choices written as Label|value:points are added up; the Next button of the last question canvas goes to the pass
 * or the fail canvas, and the total is kept as the submission's quiz score. The browser side is the package's module.js.
 */
class WpPopPop_Quiz {

    const MODULE_KEY    = 'acc-quiz';
    const MAX_THRESHOLD = 100000;
    const MAX_CANVAS    = 99;

    public static function defaults() {
        return array('enable' => false, 'pass_threshold' => 70, 'pass_canvas' => 2, 'fail_canvas' => 3, 'confetti' => false);
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

    protected static function num($raw, $keys, $def, $min, $max) {
        foreach ($keys as $k) {
            if (isset($raw[$k]) && is_scalar($raw[$k]) && is_numeric($raw[$k])) {
                return max($min, min($max, (int) floor((float) $raw[$k])));
            }
        }
        return $def;
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). The old pass_score / *_screen names are still read. */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        $d = self::defaults();
        return array(
            'enable'         => self::truthy(isset($raw['enable']) ? $raw['enable'] : false),
            'pass_threshold' => self::num($raw, array('pass_threshold', 'pass_score'), $d['pass_threshold'], 0, self::MAX_THRESHOLD),
            'pass_canvas'    => self::num($raw, array('pass_canvas', 'pass_screen'), $d['pass_canvas'], 1, self::MAX_CANVAS),
            'fail_canvas'    => self::num($raw, array('fail_canvas', 'fail_screen'), $d['fail_canvas'], 1, self::MAX_CANVAS),
            'confetti'       => self::truthy(isset($raw['confetti']) ? $raw['confetti'] : false),
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
            $h = 'wppoppop-ext-' . WpPopPop_Quiz::MODULE_KEY . '-front';
            if (wp_script_is($h, 'enqueued') && !wp_script_is($h, 'done')) {
                wp_print_scripts($h);
            }
        }, 100);
    }

    /** Escaped data attributes for the popup wrapper, or '' (module off / quiz not enabled). */
    public static function frontend($settings) {
        if (!is_array($settings) || !isset($settings['quiz']) || !is_array($settings['quiz'])) {
            return '';
        }
        $c = self::sanitize($settings['quiz']);
        if (!$c['enable'] || !self::module_active()) {
            return '';
        }
        self::print_late_script();
        return ' data-quiz="1" data-quiz-pass="' . (int) $c['pass_threshold'] . '"'
            . ' data-quiz-pass-canvas="' . (int) $c['pass_canvas'] . '"'
            . ' data-quiz-fail-canvas="' . (int) $c['fail_canvas'] . '"'
            . ' data-quiz-confetti="' . ($c['confetti'] ? '1' : '0') . '"';
    }
}
