<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Conditional Logic & Math module: sanitize (save) + front attributes (render).
 * Shape: settings.logic_math = { formula: string, target: string }
 * The formula (for example "{qty} * 25") is calculated in the browser by the package's module.js while the visitor fills the form;
 * {name} is the value of the form field with that name, target is the field or layer that shows the result.
 */
class WpPopPop_Logic_Math {

    const MODULE_KEY  = 'logic-math';
    const MAX_FORMULA = 200;
    const MAX_TARGET  = 64;

    public static function defaults() {
        return array('formula' => '', 'target' => '');
    }

    private static function clean($v, $pattern, $max) {
        if (!is_scalar($v)) {
            return '';
        }
        $s = preg_replace($pattern, '', (string) $v);
        if (!is_string($s)) {
            return '';
        }
        $s = trim(preg_replace('/ {2,}/', ' ', $s));
        return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). Only calculation characters and {field} names are kept. */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        return array(
            'formula' => self::clean(isset($raw['formula']) ? $raw['formula'] : '', '/[^\p{L}\p{N}_ .,+\-*\/%^(){}]/u', self::MAX_FORMULA),
            'target'  => self::clean(isset($raw['target']) ? $raw['target'] : '', '/[^\p{L}\p{N}_ \-]/u', self::MAX_TARGET),
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
            $h = 'wppoppop-ext-' . WpPopPop_Logic_Math::MODULE_KEY . '-front';
            if (wp_script_is($h, 'enqueued') && !wp_script_is($h, 'done')) {
                wp_print_scripts($h);
            }
        }, 100);
    }

    /** Escaped data attributes for the popup wrapper, or '' (module off / no formula / no target). */
    public static function frontend($settings) {
        if (!is_array($settings) || !isset($settings['logic_math']) || !is_array($settings['logic_math'])) {
            return '';
        }
        $c = self::sanitize($settings['logic_math']);
        if ($c['formula'] === '' || $c['target'] === '' || !self::module_active()) {
            return '';
        }
        self::print_late_script();
        return ' data-math-formula="' . esc_attr($c['formula']) . '"'
            . ' data-math-target="' . esc_attr($c['target']) . '"';
    }
}
