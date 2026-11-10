<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EDD Software Licensing client: stores the key and status, activates/deactivates this site, re-checks daily.
 * Policy: "valid" and "expired" keep every feature working (an expired key only stops updates and support).
 * A network error never changes a stored status.
 */
class WpPopPop_Pro_License {

    const OPTION = 'wppoppop_pro_license';
    const CRON   = 'wppoppop_pro_license_check';

    public static function hooks() {
        add_action(self::CRON, array(__CLASS__, 'check'));
        add_action('admin_init', array(__CLASS__, 'schedule'));
    }

    public static function schedule() {
        if (!wp_next_scheduled(self::CRON)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON);
        }
    }

    public static function unschedule() {
        wp_clear_scheduled_hook(self::CRON);
    }

    public static function data() {
        $d = get_option(self::OPTION, array());
        return wp_parse_args(is_array($d) ? $d : array(), array(
            'key'     => '',
            'status'  => 'inactive',
            'expires' => '',
            'checked' => 0,
            'message' => '',
        ));
    }

    protected static function save(array $d) {
        update_option(self::OPTION, $d, false);
        delete_site_transient(WpPopPop_Pro_Updater::CACHE);
        delete_option('wppoppop_pro_seed_ver');
    }

    public static function clean_key($key) {
        return substr(preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $key), 0, 64);
    }

    /** A key defined in wp-config.php wins over the stored one. */
    public static function key() {
        if (defined('WPPOPPOP_PRO_LICENSE_KEY') && WPPOPPOP_PRO_LICENSE_KEY !== '') {
            return self::clean_key(WPPOPPOP_PRO_LICENSE_KEY);
        }
        $d = self::data();
        return self::clean_key($d['key']);
    }

    public static function masked_key() {
        $k = self::key();
        if ($k === '') {
            return '';
        }
        return strlen($k) <= 8 ? str_repeat('•', strlen($k)) : substr($k, 0, 4) . str_repeat('•', max(4, strlen($k) - 8)) . substr($k, -4);
    }

    public static function status() {
        $d = self::data();
        return $d['status'];
    }

    public static function is_configured() {
        $url = (string) WPPOPPOP_PRO_STORE_URL;
        $host = wp_parse_url($url, PHP_URL_HOST);
        return $url !== '' && is_string($host) && $host !== '' && substr($host, -11) !== 'example.com';
    }

    public static function is_unlocked() {
        return in_array(self::status(), array('valid', 'expired'), true);
    }

    /** Callback for the core filter wppoppop_pro_licensed. */
    public static function filter_licensed($licensed) {
        return self::is_unlocked();
    }

    public static function status_label($status = null) {
        $status = $status === null ? self::status() : $status;
        $map = array(
            'valid'    => __('Active', 'wppoppop-pro'),
            'expired'  => __('Expired (updates stopped)', 'wppoppop-pro'),
            'inactive' => __('Not activated', 'wppoppop-pro'),
            'disabled' => __('Disabled', 'wppoppop-pro'),
            'invalid'  => __('Invalid', 'wppoppop-pro'),
        );
        return isset($map[$status]) ? $map[$status] : $map['inactive'];
    }

    /** @return object|WP_Error decoded store response */
    public static function remote($action, $key, $extra = array()) {
        if (!self::is_configured()) {
            return new WP_Error('not_configured', __('The licence store URL is not configured in this plugin.', 'wppoppop-pro'));
        }
        $body = array_merge(array(
            'edd_action' => $action,
            'license'    => $key,
            'item_name'  => WPPOPPOP_PRO_ITEM_NAME,
            'url'        => home_url(),
        ), $extra);
        if ((int) WPPOPPOP_PRO_ITEM_ID > 0) {
            $body['item_id'] = (int) WPPOPPOP_PRO_ITEM_ID;
        }
        $resp = wp_remote_post(esc_url_raw(WPPOPPOP_PRO_STORE_URL), array('timeout' => 15, 'sslverify' => true, 'body' => $body));
        if (is_wp_error($resp)) {
            return $resp;
        }
        if (200 !== (int) wp_remote_retrieve_response_code($resp)) {
            return new WP_Error('http', __('The licence server did not answer. Try again later.', 'wppoppop-pro'));
        }
        $data = json_decode(wp_remote_retrieve_body($resp));
        if (!is_object($data)) {
            return new WP_Error('bad_response', __('The licence server sent an unexpected answer.', 'wppoppop-pro'));
        }
        return $data;
    }

    protected static function error_message($code, $data) {
        switch ($code) {
            case 'expired':
                return __('Your licence key has expired. Renew it to receive updates.', 'wppoppop-pro');
            case 'disabled':
            case 'revoked':
                return __('Your licence key has been disabled.', 'wppoppop-pro');
            case 'missing':
            case 'invalid':
            case 'key_mismatch':
                return __('This licence key is not valid.', 'wppoppop-pro');
            case 'invalid_item_id':
            case 'item_name_mismatch':
                return __('This licence key is for a different product.', 'wppoppop-pro');
            case 'no_activations_left':
                return __('This licence key has reached its activation limit.', 'wppoppop-pro');
            case 'site_inactive':
                return __('This licence key is not active for this site.', 'wppoppop-pro');
        }
        return __('The licence could not be activated.', 'wppoppop-pro');
    }

    /** @return array {ok:bool, message:string} */
    public static function activate($key) {
        $key = self::clean_key($key);
        if ($key === '') {
            return array('ok' => false, 'message' => __('Enter your licence key.', 'wppoppop-pro'));
        }
        $r = self::remote('activate_license', $key);
        if (is_wp_error($r)) {
            return array('ok' => false, 'message' => $r->get_error_message());
        }
        $d = self::data();
        $d['key']     = $key;
        $d['checked'] = time();
        if (!empty($r->success) && isset($r->license) && $r->license === 'valid') {
            $d['status']  = 'valid';
            $d['expires'] = isset($r->expires) ? sanitize_text_field((string) $r->expires) : '';
            $d['message'] = '';
            self::save($d);
            return array('ok' => true, 'message' => __('Licence activated.', 'wppoppop-pro'));
        }
        $code = isset($r->error) ? sanitize_key((string) $r->error) : (isset($r->license) ? sanitize_key((string) $r->license) : '');
        /* A key that fails at activation never unlocks anything, even when the store calls it "expired". */
        $d['status']  = in_array($code, array('disabled', 'revoked'), true) ? 'disabled' : 'invalid';
        $d['message'] = self::error_message($code, $r);
        self::save($d);
        return array('ok' => false, 'message' => $d['message']);
    }

    /** @return array {ok:bool, message:string} */
    public static function deactivate() {
        $key = self::key();
        if ($key !== '') {
            $r = self::remote('deactivate_license', $key);
            if (is_wp_error($r)) {
                return array('ok' => false, 'message' => $r->get_error_message());
            }
        }
        $d = self::data();
        $d['status']  = 'inactive';
        $d['expires'] = '';
        $d['message'] = '';
        $d['checked'] = time();
        self::save($d);
        return array('ok' => true, 'message' => __('Licence deactivated for this site.', 'wppoppop-pro'));
    }

    /** Daily re-check (also behind the "Refresh status" button). A failed request leaves the status untouched. */
    public static function check() {
        $key = self::key();
        $d   = self::data();
        if ($key === '' || !in_array($d['status'], array('valid', 'expired'), true)) {
            return array('ok' => false, 'message' => __('No active licence to check.', 'wppoppop-pro'));
        }
        $r = self::remote('check_license', $key);
        if (is_wp_error($r)) {
            return array('ok' => false, 'message' => $r->get_error_message());
        }
        $license = isset($r->license) ? sanitize_key((string) $r->license) : '';
        $map     = array('valid' => 'valid', 'expired' => 'expired', 'disabled' => 'disabled', 'revoked' => 'disabled', 'inactive' => 'inactive', 'site_inactive' => 'inactive');
        $d['status']  = isset($map[$license]) ? $map[$license] : 'invalid';
        $d['expires'] = isset($r->expires) ? sanitize_text_field((string) $r->expires) : $d['expires'];
        $d['checked'] = time();
        self::save($d);
        return array('ok' => true, 'message' => __('Licence status refreshed.', 'wppoppop-pro'));
    }
}
