<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Webhooks module: sanitize (save) + read (submission) + send + keep the credentials off the public page.
 * Shape: settings.webhooks = { url: string, secret: string }  (a non-empty url switches the webhook on)
 * Popups saved before the module existed may hold the dispatcher's old keys { marketing: { webhook_url, webhook_secret } };
 * get_config() reads both shapes so nothing already stored is lost.
 */
class WpPopPop_Webhooks {

    const MODULE_KEY = 'acc-webhooks';
    const MAX_URL    = 2000;
    const MAX_SECRET = 200;

    public static function defaults() {
        return array('url' => '', 'secret' => '');
    }

    private static function cut($s, $max) {
        return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). Only http(s) URLs are kept. */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        $url = '';
        if (isset($raw['url']) && is_scalar($raw['url'])) {
            $url = esc_url_raw(trim((string) $raw['url']), array('http', 'https'));
            if (strlen($url) > self::MAX_URL) {
                $url = '';
            }
        }
        $secret = '';
        if (isset($raw['secret']) && is_scalar($raw['secret'])) {
            $secret = trim(preg_replace('/[\x00-\x1F\x7F]/', '', (string) $raw['secret']));
            $secret = self::cut($secret, self::MAX_SECRET);
        }
        return array('url' => $url, 'secret' => $secret);
    }

    /** Config of a decoded popup: settings.webhooks first, then the dispatcher's legacy marketing keys. */
    public static function get_config($config) {
        $config = is_array($config) ? $config : array();
        $src    = array();
        if (isset($config['settings']['webhooks']) && is_array($config['settings']['webhooks']) && !empty($config['settings']['webhooks']['url'])) {
            $src = $config['settings']['webhooks'];
        } elseif (isset($config['marketing']) && is_array($config['marketing'])) {
            $src = array(
                'url'    => isset($config['marketing']['webhook_url']) ? $config['marketing']['webhook_url'] : '',
                'secret' => isset($config['marketing']['webhook_secret']) ? $config['marketing']['webhook_secret'] : '',
            );
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

    /**
     * The webhook URL and secret are private. Remove them from a popup config before it is printed on a page
     * (data-config) or returned by the public popup REST route.
     */
    public static function redact($config) {
        if (!is_array($config)) {
            return $config;
        }
        if (isset($config['settings']) && is_array($config['settings'])) {
            unset($config['settings']['webhooks']);
        }
        if (isset($config['marketing']) && is_array($config['marketing'])) {
            unset($config['marketing']['webhook_url'], $config['marketing']['webhook_secret']);
        }
        return $config;
    }

    /** POST the submission to the configured URL (non-blocking, JSON, optional HMAC-SHA256 signature header). */
    public static function send($uid, $submission_id, $email, $fields, $country, $cfg) {
        if (!is_array($cfg) || empty($cfg['url'])) {
            return false;
        }
        $payload = array(
            'event'         => 'submission',
            'popup_uid'     => $uid,
            'submission_id' => $submission_id,
            'email'         => $email,
            'fields'        => $fields,
            'country'       => $country,
            'timestamp'     => current_time('mysql'),
        );
        $payload = apply_filters('wppoppop_webhook_payload', $payload, $uid, $cfg['url']);
        if (!is_array($payload)) {
            return false;
        }
        $body = wp_json_encode($payload);
        if (!is_string($body) || $body === '') {
            return false;
        }
        $headers = array('Content-Type' => 'application/json');
        if ($cfg['secret'] !== '') {
            $headers['X-WpPopPop-Signature'] = hash_hmac('sha256', $body, $cfg['secret']);
        }
        $response = wp_remote_post($cfg['url'], array(
            'headers'  => $headers,
            'body'     => $body,
            'timeout'  => 10,
            'blocking' => false,
        ));
        do_action('wppoppop_webhook_dispatched', $response, $payload, $uid);
        return !is_wp_error($response);
    }
}
