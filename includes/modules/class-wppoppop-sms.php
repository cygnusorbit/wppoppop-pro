<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Twilio SMS Alerts module: sanitize (save) + read (submission) + send + keep the credentials off the public page.
 * Shape: settings.sms = { enable: bool, phone: '+E.164' (receives the alerts), sid, token, from: '+E.164' (Twilio number) }
 * Each new submission sends one short text (popup title and email) to "phone". The credentials can also come from the
 * wppoppop_sms_credentials filter (array account_sid / auth_token / from_number), for sites that keep them in code.
 */
class WpPopPop_Sms {

    const MODULE_KEY   = 'acc-sms';
    const HOURLY_LIMIT = 20;

    public static function defaults() {
        return array('enable' => false, 'phone' => '', 'sid' => '', 'token' => '', 'from' => '');
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

    /** International number (+ and 6 to 15 digits); anything else is dropped. */
    protected static function phone($v) {
        if (!is_scalar($v)) {
            return '';
        }
        $s = preg_replace('/[\s().\-]+/', '', (string) $v);
        return (is_string($s) && preg_match('/^\+[1-9][0-9]{5,14}$/', $s)) ? $s : '';
    }

    protected static function token($v) {
        return (is_scalar($v) && preg_match('/^[A-Za-z0-9]{1,64}$/', trim((string) $v))) ? trim((string) $v) : '';
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        return array(
            'enable' => self::truthy(isset($raw['enable']) ? $raw['enable'] : false),
            'phone'  => self::phone(isset($raw['phone']) ? $raw['phone'] : ''),
            'sid'    => self::token(isset($raw['sid']) ? $raw['sid'] : ''),
            'token'  => self::token(isset($raw['token']) ? $raw['token'] : ''),
            'from'   => self::phone(isset($raw['from']) ? $raw['from'] : ''),
        );
    }

    public static function get_config($config, $uid = '') {
        $src = (is_array($config) && isset($config['settings']['sms']) && is_array($config['settings']['sms'])) ? $config['settings']['sms'] : array();
        $c   = self::sanitize($src);
        $cr  = apply_filters('wppoppop_sms_credentials', array('account_sid' => $c['sid'], 'auth_token' => $c['token'], 'from_number' => $c['from']), $uid);
        if (is_array($cr)) {
            $c['sid']   = self::token(isset($cr['account_sid']) ? $cr['account_sid'] : '');
            $c['token'] = self::token(isset($cr['auth_token']) ? $cr['auth_token'] : '');
            $c['from']  = self::phone(isset($cr['from_number']) ? $cr['from_number'] : '');
        }
        return $c;
    }

    /** Settings > Module switch. A module that is switched off or deleted never affects submissions. */
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

    /** The credentials and the phone number are private: remove them from a popup config before it is printed on a page or returned by the public popup route. */
    public static function redact($config) {
        if (is_array($config) && isset($config['settings']) && is_array($config['settings'])) {
            unset($config['settings']['sms']);
        }
        return $config;
    }

    /** At most HOURLY_LIMIT texts per popup and hour, so form spam cannot run up the Twilio bill. */
    protected static function allowed($uid) {
        $limit = max(1, (int) apply_filters('wppoppop_sms_hourly_limit', self::HOURLY_LIMIT));
        $key   = 'wpp_sms_' . md5((string) $uid);
        $n     = (int) get_transient($key);
        if ($n >= $limit) {
            return false;
        }
        set_transient($key, $n + 1, HOUR_IN_SECONDS);
        return true;
    }

    protected static function clip($s, $max) {
        return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
    }

    /** Text the new lead to "phone" once the response is sent (the Twilio call can take a few seconds). Returns false when nothing is queued. */
    public static function notify($uid, $title, $email, $cfg) {
        if (!is_array($cfg) || empty($cfg['enable']) || $cfg['phone'] === '' || $cfg['sid'] === '' || $cfg['token'] === '' || $cfg['from'] === '') {
            return false;
        }
        if (!self::allowed($uid)) {
            return false;
        }
        $clean = function ($v) {
            $s = preg_replace('/[\x00-\x1F\x7F]+/', ' ', is_scalar($v) ? (string) $v : '');
            return trim(is_string($s) ? $s : '');
        };
        $body = self::clip(sprintf('New lead on "%s": %s', self::clip($clean($title), 60), $email !== '' ? $clean($email) : '(no email)'), 160);
        $send = function () use ($cfg, $body) {
            $sms = new WpPopPop_Addon_Sms();
            $sms->dispatch_twilio_sms($cfg['phone'], $body, array('account_sid' => $cfg['sid'], 'auth_token' => $cfg['token'], 'from_number' => $cfg['from']));
        };
        add_action('shutdown', $send);
        return true;
    }
}
