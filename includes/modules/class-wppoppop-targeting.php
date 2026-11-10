<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Targeting module: sanitize (save) + server gate (front end).
 * Shape: settings.targeting = { auth: 'all' | 'guest' | 'user' }
 *   all = every visitor, guest = visitors who are not logged in, user = logged-in users only.
 */
class WpPopPop_Targeting {

    const MODULE_KEY = 'acc-targeting';

    public static function defaults() {
        return array('auth' => 'all');
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        $auth = (is_array($raw) && isset($raw['auth']) && is_string($raw['auth'])) ? $raw['auth'] : 'all';
        return array('auth' => in_array($auth, array('all', 'guest', 'user'), true) ? $auth : 'all');
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

    /** Server gate for the popup's settings array. Popups without the rule are untouched. */
    public static function is_eligible($settings) {
        $c = self::sanitize(is_array($settings) && isset($settings['targeting']) ? $settings['targeting'] : array());
        if ($c['auth'] === 'all' || !self::module_active()) {
            return true;
        }
        return $c['auth'] === 'user' ? is_user_logged_in() : !is_user_logged_in();
    }
}
