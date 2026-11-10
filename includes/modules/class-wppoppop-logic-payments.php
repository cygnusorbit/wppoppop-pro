<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payments module: sanitize (save) + default values for the Pay layer (render).
 * Shape: settings.payments = { gateway: 'stripe'|'paypal', amount: float >= 0 }
 * The values are the popup defaults: a Pay layer without its own gateway / amount uses them.
 * This module only configures the buttons; a payment is not charged or recorded by it.
 */
class WpPopPop_Logic_Payments {

    const MODULE_KEY   = 'logic-payments';
    const GATEWAYS     = array('stripe', 'paypal');
    const MAX_AMOUNT   = 9999999.99;
    const FALLBACK_AMT = 19.99;

    protected static $ctx = array();

    public static function defaults() {
        return array('gateway' => 'stripe', 'amount' => 0.0);
    }

    protected static function gateway($v) {
        $v = is_scalar($v) ? strtolower(trim((string) $v)) : '';
        return in_array($v, self::GATEWAYS, true) ? $v : '';
    }

    protected static function amount($v) {
        if (!is_scalar($v) || !is_numeric($v)) {
            return 0.0;
        }
        $f = (float) $v;
        if (!is_finite($f) || $f <= 0) {
            return 0.0;
        }
        return round(min($f, self::MAX_AMOUNT), 2);
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        $g = self::gateway(isset($raw['gateway']) ? $raw['gateway'] : '');
        return array(
            'gateway' => $g !== '' ? $g : 'stripe',
            'amount'  => self::amount(isset($raw['amount']) ? $raw['amount'] : 0),
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

    /** Module defaults of the popup that is being rendered (empty while the module is off or unused). */
    public static function context($settings) {
        self::$ctx = array();
        if (is_array($settings) && isset($settings['payments']) && is_array($settings['payments']) && self::module_active()) {
            self::$ctx = self::sanitize($settings['payments']);
        }
    }

    /** Escaped data attributes for a Pay layer: the layer's own value first, then the module default, then the built-in one. */
    public static function button_attrs(array $el) {
        $g = self::gateway(isset($el['gateway']) ? $el['gateway'] : '');
        if ($g === '' && !empty(self::$ctx['gateway'])) {
            $g = self::$ctx['gateway'];
        }
        if ($g === '') {
            $g = 'stripe';
        }
        $a = self::amount(isset($el['amount']) ? $el['amount'] : 0);
        if ($a <= 0 && !empty(self::$ctx['amount'])) {
            $a = (float) self::$ctx['amount'];
        }
        if ($a <= 0) {
            $a = self::FALLBACK_AMT;
        }
        $c = (isset($el['currency']) && is_string($el['currency']) && preg_match('/^[A-Za-z]{3}$/', $el['currency'])) ? strtoupper($el['currency']) : 'USD';
        return ' data-gateway="' . esc_attr($g) . '" data-amount="' . esc_attr(number_format($a, 2, '.', '')) . '" data-currency="' . esc_attr($c) . '"';
    }
}
