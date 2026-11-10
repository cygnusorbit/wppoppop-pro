<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Autoresponder module: sanitize (save) + read (submission) + send.
 * Shape: settings.autoresponder = { enable: bool, subject: string, body: string }
 * Popups saved before the module existed may hold { enable_user_email, subject, message } (top level or under
 * settings); get_config() reads both shapes so nothing already stored is lost.
 */
class WpPopPop_Autoresponder {

    const MODULE_KEY  = 'acc-autoresponder';
    const MAX_SUBJECT = 200;
    const MAX_BODY    = 5000;

    public static function defaults() {
        return array('enable' => false, 'subject' => '', 'body' => '');
    }

    private static function cut($s, $max) {
        return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
    }

    private static function truthy($v) {
        return in_array($v, array(true, 1, '1', 'true', 'on'), true);
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        $on   = !empty($raw['enable']) ? self::truthy($raw['enable']) : (!empty($raw['enable_user_email']) && self::truthy($raw['enable_user_email']));
        $subj = (isset($raw['subject']) && is_scalar($raw['subject'])) ? sanitize_text_field((string) $raw['subject']) : '';
        $body = '';
        foreach (array('body', 'message') as $k) {
            if ($body === '' && isset($raw[$k]) && is_scalar($raw[$k])) {
                $body = sanitize_textarea_field((string) $raw[$k]);
            }
        }
        return array(
            'enable'  => $on,
            'subject' => self::cut($subj, self::MAX_SUBJECT),
            'body'    => self::cut($body, self::MAX_BODY),
        );
    }

    /** Config of a decoded popup: settings.autoresponder first, then the legacy top-level key. */
    public static function get_config($config) {
        $config = is_array($config) ? $config : array();
        $src    = array();
        if (isset($config['settings']['autoresponder']) && is_array($config['settings']['autoresponder'])) {
            $src = $config['settings']['autoresponder'];
        } elseif (isset($config['autoresponder']) && is_array($config['autoresponder'])) {
            $src = $config['autoresponder'];
        }
        return self::sanitize($src);
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

    /** Send the welcome email to the subscriber. {email} and {field} tokens are replaced; output is escaped. */
    public static function send($email, $fields, $cfg) {
        $subject = ($cfg['subject'] !== '') ? $cfg['subject'] : __('Welcome! Here is your reward', 'wppoppop');
        $body    = ($cfg['body'] !== '') ? $cfg['body'] : __("Thank you for subscribing!\n\nEnjoy your offer.", 'wppoppop');
        $body    = str_replace('{email}', $email, $body);
        foreach ((array) $fields as $k => $v) {
            $val  = is_array($v) ? implode(', ', array_map('strval', $v)) : (string) $v;
            $body = str_replace('{' . $k . '}', $val, $body);
        }
        $mail = array(
            'to'      => $email,
            'subject' => $subject,
            'body'    => nl2br(esc_html($body)),
            'headers' => array('Content-Type: text/html; charset=UTF-8'),
        );
        $mail = apply_filters('wppoppop_autoresponder_mail', $mail);
        if (!is_array($mail) || empty($mail['to'])) {
            return false;
        }
        return wp_mail($mail['to'], isset($mail['subject']) ? $mail['subject'] : $subject, isset($mail['body']) ? $mail['body'] : '', isset($mail['headers']) ? $mail['headers'] : array());
    }
}
