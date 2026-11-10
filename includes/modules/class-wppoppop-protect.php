<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security & Protections module: sanitize (save) + read (submission) + apply.
 * Shape: settings.security = {
 *   save_ip, save_ua        : keep the visitor IP-address / User-Agent with the submission (technical info)
 *   email_tech              : add the technical info to {{form-data}} in notification emails
 *   record_tech             : show the technical info on the submission details
 *   disable_scroll          : lock the page scrollbar while the popup is visible
 *   personal_keys[]         : field names that hold the personal data key (usually the email field), used by the WordPress privacy tools
 *   antibot, antibot_delay, antibot_form, antibot_ip, antibot_error : repeated submission protection
 * }
 * A popup without this setting uses the defaults, as long as the module is installed and switched on.
 */
class WpPopPop_Protect {

    const MODULE_KEY    = 'acc-security';
    const DEFAULT_ERROR = 'Thank you. We have already got your request.';
    const MAX_KEYS      = 20;

    public static function defaults() {
        return array(
            'save_ip'        => true,
            'save_ua'        => true,
            'email_tech'     => true,
            'record_tech'    => true,
            'disable_scroll' => false,
            'personal_keys'  => array(),
            'antibot'        => true,
            'antibot_delay'  => 10,
            'antibot_form'   => true,
            'antibot_ip'     => true,
            'antibot_error'  => self::DEFAULT_ERROR,
        );
    }

    protected static function truthy($v, $d) {
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v) || is_float($v)) {
            return $v != 0;
        }
        if (is_string($v)) {
            $s = strtolower(trim($v));
            if (in_array($s, array('1', 'true', 'yes', 'on'), true)) {
                return true;
            }
            if (in_array($s, array('0', 'false', 'no', 'off', ''), true)) {
                return false;
            }
        }
        return $d;
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        $d   = self::defaults();
        $out = array();
        foreach (array('save_ip', 'save_ua', 'email_tech', 'record_tech', 'disable_scroll', 'antibot', 'antibot_form', 'antibot_ip') as $k) {
            $out[$k] = self::truthy(isset($raw[$k]) ? $raw[$k] : $d[$k], $d[$k]);
        }
        $keys = array();
        $src  = (isset($raw['personal_keys']) && is_array($raw['personal_keys'])) ? $raw['personal_keys'] : array();
        foreach ($src as $k) {
            if (!is_scalar($k)) {
                continue;
            }
            $k = trim((string) $k);
            if ($k === '' || strlen($k) > 64 || !preg_match('/^[A-Za-z0-9_\-. ]+$/', $k) || in_array($k, $keys, true)) {
                continue;
            }
            $keys[] = $k;
            if (count($keys) >= self::MAX_KEYS) {
                break;
            }
        }
        $out['personal_keys'] = $keys;
        $delay = (isset($raw['antibot_delay']) && is_numeric($raw['antibot_delay'])) ? (int) $raw['antibot_delay'] : $d['antibot_delay'];
        $out['antibot_delay'] = max(1, min(3600, $delay));
        $msg = (isset($raw['antibot_error']) && is_scalar($raw['antibot_error'])) ? sanitize_text_field((string) $raw['antibot_error']) : '';
        $out['antibot_error'] = $msg !== '' ? (function_exists('mb_substr') ? mb_substr($msg, 0, 200) : substr($msg, 0, 200)) : self::DEFAULT_ERROR;
        return array_merge($d, $out);
    }

    public static function get_config($config) {
        $src = (is_array($config) && isset($config['settings']['security']) && is_array($config['settings']['security'])) ? $config['settings']['security'] : array();
        return self::sanitize($src);
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

    /** Technical info to store with a submission: only what the popup is set to save. */
    public static function capture($cfg) {
        $out = array();
        if (!empty($cfg['save_ip'])) {
            $ip = WpPopPop_Security::get_client_ip();
            if ($ip !== '') {
                $out['_ip'] = class_exists('WpPopPop_Tracking') ? WpPopPop_Tracking::anonymize_ip($ip) : $ip;
            }
        }
        if (!empty($cfg['save_ua']) && !empty($_SERVER['HTTP_USER_AGENT'])) {
            $ua = sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT']));
            if ($ua !== '') {
                $out['_ua'] = function_exists('mb_substr') ? mb_substr($ua, 0, 255) : substr($ua, 0, 255);
            }
        }
        return $out;
    }

    /** Antibot: returns the error message when the same data / IP-address was sent within the delay, otherwise '' and the submission is remembered. */
    public static function antibot($uid, $fields, $cfg) {
        if (empty($cfg['antibot'])) {
            return '';
        }
        $form = '';
        if (!empty($cfg['antibot_form'])) {
            $c = is_array($fields) ? $fields : array();
            unset($c['_ip'], $c['_ua']);
            ksort($c);
            $form = (string) wp_json_encode($c);
        }
        $ip  = !empty($cfg['antibot_ip']) ? WpPopPop_Security::get_client_ip() : '';
        $key = 'wppoppop_ab_' . md5((string) $uid . '|' . $form . '|' . $ip);
        if (get_transient($key)) {
            return $cfg['antibot_error'] !== '' ? $cfg['antibot_error'] : self::DEFAULT_ERROR;
        }
        set_transient($key, 1, (int) $cfg['antibot_delay']);
        return '';
    }

    /** Notification emails: take the technical info out of the fields; returns the labelled rows to add to {{form-data}} when allowed. */
    public static function take_tech(&$flat, $cfg) {
        $rows = array();
        $map  = array('_ip' => 'IP address', '_ua' => 'User-Agent');
        foreach ($map as $k => $label) {
            if (isset($flat[$k])) {
                if (!empty($cfg['email_tech']) && $flat[$k] !== '') {
                    $rows[$label] = $flat[$k];
                }
                unset($flat[$k]);
            }
        }
        return $rows;
    }

    /** Submission details: hide the technical info when the popup is set not to show it. */
    public static function record_fields($fields, $uid) {
        if (!is_array($fields) || (!isset($fields['_ip']) && !isset($fields['_ua'])) || !self::module_active()) {
            return $fields;
        }
        $cfg = self::get_config(self::popup_config($uid));
        if (empty($cfg['record_tech'])) {
            unset($fields['_ip'], $fields['_ua']);
        }
        return $fields;
    }

    protected static function popup_config($uid) {
        static $cache = array();
        $uid = (string) $uid;
        if (!isset($cache[$uid])) {
            global $wpdb;
            $raw = $uid === '' ? '' : $wpdb->get_var($wpdb->prepare("SELECT data FROM {$wpdb->prefix}wppoppop_items WHERE uid = %s", $uid));
            $cfg = json_decode((string) $raw, true);
            $cache[$uid] = is_array($cfg) ? $cfg : array();
        }
        return $cache[$uid];
    }

    /** Extra attributes for the popup wrapper (the front end reads them when the popup opens and closes). */
    public static function frontend_attrs($config) {
        if (!self::module_active()) {
            return '';
        }
        $cfg = self::get_config($config);
        return !empty($cfg['disable_scroll']) ? ' data-wpp-noscroll="1"' : '';
    }

    /* ---- WordPress privacy tools (personal data key fields) ---- */

    public static function register_exporter($exporters) {
        $exporters['wppoppop-submissions'] = array(
            'exporter_friendly_name' => __('WpPopPop submissions', 'wppoppop'),
            'callback'               => array(__CLASS__, 'export_personal'),
        );
        return $exporters;
    }

    public static function register_eraser($erasers) {
        $erasers['wppoppop-submissions'] = array(
            'eraser_friendly_name' => __('WpPopPop submissions', 'wppoppop'),
            'callback'             => array(__CLASS__, 'erase_personal'),
        );
        return $erasers;
    }

    /** Submissions of the popups that name personal key fields, where one of those fields holds the e-mail address. */
    protected static function personal_matches($email, $page) {
        global $wpdb;
        $out = array('rows' => array(), 'done' => true);
        if (!is_email($email) || !self::module_active()) {
            return $out;
        }
        $per    = 50;
        $page   = max(1, (int) $page);
        $items  = $wpdb->get_results("SELECT uid, data FROM {$wpdb->prefix}wppoppop_items", ARRAY_A);
        $keymap = array();
        foreach ((array) $items as $it) {
            $c = json_decode((string) $it['data'], true);
            $k = self::get_config($c);
            if (!empty($k['personal_keys'])) {
                $keymap[$it['uid']] = array_map('sanitize_key', $k['personal_keys']);
            }
        }
        if (empty($keymap)) {
            return $out;
        }
        $uids = array_keys($keymap);
        $in   = implode(',', array_fill(0, count($uids), '%s'));
        $like = '%' . $wpdb->esc_like(trim((string) wp_json_encode($email), '"')) . '%';
        $args = array_merge($uids, array($like, $per + 1, ($page - 1) * $per));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, popup_uid, fields_data FROM {$wpdb->prefix}wppoppop_submissions WHERE popup_uid IN ($in) AND fields_data LIKE %s ORDER BY id ASC LIMIT %d OFFSET %d",
            $args
        ), ARRAY_A);
        $out['done'] = count((array) $rows) <= $per;
        foreach (array_slice((array) $rows, 0, $per) as $r) {
            $f = json_decode((string) $r['fields_data'], true);
            if (!is_array($f)) {
                continue;
            }
            $hit = false;
            foreach ($keymap[$r['popup_uid']] as $k) {
                if (isset($f[$k]) && is_scalar($f[$k]) && strtolower(trim((string) $f[$k])) === strtolower($email)) {
                    $hit = true;
                    break;
                }
            }
            if ($hit) {
                $out['rows'][] = array('id' => (int) $r['id'], 'fields' => $f);
            }
        }
        return $out;
    }

    public static function export_personal($email, $page = 1) {
        $m    = self::personal_matches($email, $page);
        $data = array();
        foreach ($m['rows'] as $r) {
            $item = array();
            foreach ($r['fields'] as $k => $v) {
                $item[] = array('name' => (string) $k, 'value' => is_array($v) ? implode(', ', array_map('strval', $v)) : (string) $v);
            }
            $data[] = array('group_id' => 'wppoppop-submissions', 'group_label' => __('WpPopPop submissions', 'wppoppop'), 'item_id' => 'wppoppop-submission-' . $r['id'], 'data' => $item);
        }
        return array('data' => $data, 'done' => $m['done']);
    }

    public static function erase_personal($email, $page = 1) {
        global $wpdb;
        $m   = self::personal_matches($email, 1);
        $res = array('items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true);
        foreach ($m['rows'] as $r) {
            $wpdb->update($wpdb->prefix . 'wppoppop_submissions', array('email' => 'anonymized_' . $r['id'] . '@privacy.local', 'fields_data' => wp_json_encode(array('gdpr' => 'anonymized'))), array('id' => $r['id']));
            $res['items_removed'] = true;
        }
        $res['done'] = $m['done'];
        return $res;
    }
}
