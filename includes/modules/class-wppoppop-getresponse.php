<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * GetResponse module (acc-getresponse): add the visitor to a GetResponse campaign after a popup submission.
 * Shape: settings.getresponse = [ { id, name, enable, send, api_key, campaign_id, campaign_name, fields{TAG:value},
 *        field_labels{TAG:label}, logic_enable, logic_match, rules[] } ]
 * Field tags: EMAIL, NAME and cf_<customFieldId> (a GetResponse custom field).
 * Events: after_submission, after_confirmation (WpPopPop double opt-in confirmed).
 * The API keys are private: redact() removes the whole list before a popup config is printed or served publicly.
 */
class WpPopPop_Getresponse {

    const MODULE_KEY = 'acc-getresponse';
    const API_BASE   = 'https://api.getresponse.com/v3/';
    const MAX_ITEMS  = 20;
    const MAX_RULES  = 20;
    const TIMEOUT    = 15;
    const HOURLY_CAP = 300;
    const TAG_RE     = '/^(?:EMAIL|NAME|cf_[A-Za-z0-9]{1,40})$/';

    public static function defaults() {
        return array(
            'id'            => '',
            'name'          => 'GetResponse',
            'enable'        => true,
            'send'          => 'after_submission',
            'api_key'       => '',
            'campaign_id'   => '',
            'campaign_name' => '',
            'fields'        => array('EMAIL' => '{{email}}', 'NAME' => '{{name}}'),
            'field_labels'  => array(),
            'logic_enable'  => false,
            'logic_match'   => 'all',
            'rules'         => array(),
        );
    }

    private static function cut($s, $max) {
        return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
    }

    private static function to_array($v) {
        if (is_object($v)) { $v = get_object_vars($v); }
        return is_array($v) ? $v : array();
    }

    private static function is_true($v) {
        return in_array($v, array(true, 1, '1', 'true', 'on'), true);
    }

    private static function text($a, $k, $max = 200) {
        return (isset($a[$k]) && is_scalar($a[$k])) ? self::cut(sanitize_text_field((string) $a[$k]), $max) : '';
    }

    public static function clean_key($key) {
        $key = is_scalar($key) ? trim((string) $key) : '';
        return preg_match('/^[A-Za-z0-9]{8,64}$/', $key) ? $key : '';
    }

    public static function clean_id($id) {
        $id = is_scalar($id) ? trim((string) $id) : '';
        return preg_match('/^[A-Za-z0-9]{1,40}$/', $id) ? $id : '';
    }

    /** EMAIL / NAME are case-insensitive, custom field ids are case-sensitive. Returns '' for an unusable tag. */
    public static function clean_tag($tag) {
        $tag = is_scalar($tag) ? trim((string) $tag) : '';
        $up  = strtoupper($tag);
        if ($up === 'EMAIL' || $up === 'NAME') { return $up; }
        return preg_match(self::TAG_RE, $tag) ? $tag : '';
    }

    /** Clean, allow-listed list of items from a saved or posted value (list, object map or a single item). */
    public static function sanitize($raw) {
        $raw = self::to_array($raw);
        if (!empty($raw) && !isset($raw[0]) && isset($raw['api_key'])) { $raw = array($raw); }
        $out = array();
        foreach (array_slice(array_values($raw), 0, self::MAX_ITEMS) as $in) {
            $in = self::to_array($in);
            if (empty($in)) { continue; }
            $d = self::defaults();

            $id = isset($in['id']) && is_scalar($in['id']) ? preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $in['id']) : '';
            $d['id']            = $id !== '' ? self::cut($id, 40) : 'gr_' . substr(md5(uniqid('', true)), 0, 6);
            $name               = self::text($in, 'name', 120);
            $d['name']          = $name !== '' ? $name : 'GetResponse';
            $d['enable']        = !isset($in['enable']) || self::is_true($in['enable']);
            $d['send']          = (isset($in['send']) && $in['send'] === 'after_confirmation') ? 'after_confirmation' : 'after_submission';
            $d['api_key']       = self::clean_key(isset($in['api_key']) ? $in['api_key'] : '');
            $d['campaign_id']   = self::clean_id(isset($in['campaign_id']) ? $in['campaign_id'] : '');
            $d['campaign_name'] = $d['campaign_id'] !== '' ? self::text($in, 'campaign_name', 200) : '';

            $labels = array();
            foreach (array_slice(self::to_array(isset($in['field_labels']) ? $in['field_labels'] : array()), 0, 100, true) as $tag => $label) {
                $tag = self::clean_tag($tag);
                if ($tag !== '' && is_scalar($label)) { $labels[$tag] = self::cut(sanitize_text_field((string) $label), 120); }
            }
            $d['field_labels'] = $labels;

            $fields = array('EMAIL' => '{{email}}', 'NAME' => '');
            foreach (array_slice(self::to_array(isset($in['fields']) ? $in['fields'] : array()), 0, 100, true) as $tag => $val) {
                $tag = self::clean_tag($tag);
                if ($tag === '' || !is_scalar($val)) { continue; }
                $val = self::cut(sanitize_text_field((string) $val), 500);
                if ($tag === 'EMAIL' && $val === '') { $val = '{{email}}'; }
                $fields[$tag] = $val;
            }
            $d['fields'] = $fields;

            $d['logic_enable'] = !empty($in['logic_enable']) && self::is_true($in['logic_enable']);
            $d['logic_match']  = (isset($in['logic_match']) && $in['logic_match'] === 'any') ? 'any' : 'all';
            $rules = array();
            foreach (array_slice(array_values(self::to_array(isset($in['rules']) ? $in['rules'] : array())), 0, self::MAX_RULES) as $r) {
                $r  = self::to_array($r);
                $op = isset($r['operator']) ? (string) $r['operator'] : 'is';
                $rules[] = array(
                    'field'    => self::text($r, 'field', 100),
                    'operator' => in_array($op, array('is', 'is_not', 'contains', 'not_contains', 'empty', 'not_empty'), true) ? $op : 'is',
                    'value'    => self::text($r, 'value', 200),
                );
            }
            $d['rules'] = $rules;
            $out[] = $d;
        }
        return $out;
    }

    public static function get_items($config) {
        $s = (is_array($config) && isset($config['settings']) && is_array($config['settings'])) ? $config['settings'] : array();
        return (isset($s['getresponse']) && is_array($s['getresponse'])) ? self::sanitize($s['getresponse']) : array();
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

    /** The API keys are private: remove the list before a popup config is printed on a page or returned by the public REST route. */
    public static function redact($config) {
        if (is_array($config) && isset($config['settings']) && is_array($config['settings'])) {
            unset($config['settings']['getresponse']);
        }
        return $config;
    }

    public static function merge($tpl, $vars) {
        return preg_replace_callback('/\{\{\s*([A-Za-z0-9_\-]+)\s*\}\}/', function ($m) use ($vars) {
            $k = strtolower($m[1]);
            return isset($vars[$k]) ? $vars[$k] : '';
        }, (string) $tpl);
    }

    private static function flat($fields) {
        $out = array();
        foreach ((array) $fields as $k => $v) {
            $ck = sanitize_key($k);
            if ($ck === '') { continue; }
            $out[$ck] = is_array($v) ? implode(', ', array_map('strval', $v)) : (string) $v;
        }
        return $out;
    }

    private static function rule_ok($rule, $flat) {
        $key = sanitize_key(isset($rule['field']) ? $rule['field'] : '');
        $val = isset($flat[$key]) ? trim((string) $flat[$key]) : '';
        $cmp = isset($rule['value']) ? trim((string) $rule['value']) : '';
        switch (isset($rule['operator']) ? $rule['operator'] : 'is') {
            case 'is_not':       return strcasecmp($val, $cmp) !== 0;
            case 'contains':     return $cmp !== '' && stripos($val, $cmp) !== false;
            case 'not_contains': return $cmp === '' || stripos($val, $cmp) === false;
            case 'empty':        return $val === '';
            case 'not_empty':    return $val !== '';
            case 'is':
            default:             return strcasecmp($val, $cmp) === 0;
        }
    }

    public static function passes($item, $flat) {
        if (empty($item['logic_enable']) || empty($item['rules']) || !is_array($item['rules'])) { return true; }
        $any = (isset($item['logic_match']) && $item['logic_match'] === 'any');
        foreach ($item['rules'] as $r) {
            if (!is_array($r)) { continue; }
            $ok = self::rule_ok($r, $flat);
            if ($any && $ok) { return true; }
            if (!$any && !$ok) { return false; }
        }
        return !$any;
    }

    /**
     * One GetResponse API v3 call (fixed host, the key only travels in the X-Auth-Token header).
     * Returns array('code' => int, 'data' => array) or array('code' => 0, 'error' => string).
     */
    public static function api($key, $method, $path, $body = null) {
        $key = self::clean_key($key);
        if ($key === '') { return array('code' => 0, 'error' => __('Invalid API key.', 'wppoppop')); }
        $args = array(
            'method'  => $method,
            'timeout' => self::TIMEOUT,
            'headers' => array(
                'X-Auth-Token' => 'api-key ' . $key,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
        );
        if ($body !== null) { $args['body'] = wp_json_encode($body); }
        $res = wp_remote_request(self::API_BASE . ltrim($path, '/'), $args);
        if (is_wp_error($res)) { return array('code' => 0, 'error' => $res->get_error_message()); }
        $code = (int) wp_remote_retrieve_response_code($res);
        $data = json_decode((string) wp_remote_retrieve_body($res), true);
        return array('code' => $code, 'data' => is_array($data) ? $data : array());
    }

    private static function api_error($r) {
        if (!empty($r['error'])) { return (string) $r['error']; }
        if (!empty($r['data']['message']) && is_scalar($r['data']['message'])) { return sanitize_text_field((string) $r['data']['message']); }
        if (!empty($r['data']['codeDescription']) && is_scalar($r['data']['codeDescription'])) { return sanitize_text_field((string) $r['data']['codeDescription']); }
        return sprintf(__('GetResponse returned HTTP %d.', 'wppoppop'), (int) $r['code']);
    }

    /** Campaigns of the account: array(campaignId => name), or a WP_Error. */
    public static function fetch_campaigns($key) {
        $r = self::api($key, 'GET', 'campaigns?page=1&perPage=100&fields=campaignId,name');
        if ($r['code'] !== 200) { return new WP_Error('wppoppop_gr', self::api_error($r)); }
        $out = array();
        foreach ($r['data'] as $c) {
            if (is_array($c) && !empty($c['campaignId']) && self::clean_id($c['campaignId']) !== '') {
                $out[(string) $c['campaignId']] = isset($c['name']) ? sanitize_text_field((string) $c['name']) : (string) $c['campaignId'];
            }
        }
        return $out;
    }

    /** Mappable fields: array(tag => label) with EMAIL and NAME first, then the account's custom fields, or a WP_Error. */
    public static function fetch_fields($key) {
        $r = self::api($key, 'GET', 'custom-fields?page=1&perPage=100&fields=customFieldId,name');
        if ($r['code'] !== 200) { return new WP_Error('wppoppop_gr', self::api_error($r)); }
        $out = array('EMAIL' => __('Email Address', 'wppoppop'), 'NAME' => __('Full name', 'wppoppop'));
        foreach ($r['data'] as $f) {
            $id = (is_array($f) && isset($f['customFieldId'])) ? self::clean_id($f['customFieldId']) : '';
            if ($id === '') { continue; }
            $out['cf_' . $id] = isset($f['name']) ? ucfirst(str_replace('_', ' ', sanitize_text_field((string) $f['name']))) : $id;
        }
        return $out;
    }

    /** Add (or update) one visitor in the item's campaign. Returns true or an error string. */
    public static function subscribe($item, $email, $values) {
        $key  = $item['api_key'];
        $camp = $item['campaign_id'];
        if ($key === '' || $camp === '') { return __('API key or campaign is missing.', 'wppoppop'); }
        $email = sanitize_email((string) $email);
        if ($email === '' || !is_email($email)) { return __('Invalid email address.', 'wppoppop'); }

        $custom = array();
        foreach ($values as $tag => $val) {
            if (strpos($tag, 'cf_') === 0 && $val !== '') {
                $custom[] = array('customFieldId' => substr($tag, 3), 'value' => array(self::cut($val, 500)));
            }
        }
        $name = isset($values['NAME']) ? self::cut($values['NAME'], 128) : '';
        $data = array();
        if ($name !== '')    { $data['name'] = $name; }
        if (!empty($custom)) { $data['customFieldValues'] = $custom; }

        $found = self::api($key, 'GET', 'contacts?query[email]=' . rawurlencode($email) . '&query[campaignId]=' . rawurlencode($camp) . '&fields=contactId');
        if ($found['code'] === 200 && !empty($found['data'][0]['contactId']) && self::clean_id($found['data'][0]['contactId']) !== '') {
            if (empty($data)) { return true; }
            $r = self::api($key, 'POST', 'contacts/' . $found['data'][0]['contactId'], $data);
            return ($r['code'] >= 200 && $r['code'] <= 299) ? true : self::api_error($r);
        }
        if ($found['code'] !== 200 && $found['code'] !== 404) { return self::api_error($found); }

        $body = array_merge(array('email' => $email, 'campaign' => array('campaignId' => $camp)), $data);
        $ip   = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) { $body['ipAddress'] = $ip; }

        $r = self::api($key, 'POST', 'contacts', $body);
        if (($r['code'] >= 200 && $r['code'] <= 299) || $r['code'] === 409) { return true; }
        return self::api_error($r);
    }

    /** Hourly cap per popup, so a bot posting the form cannot burn the account's API quota. */
    private static function allow($uid) {
        $k = 'wppoppop_gr_' . md5((string) $uid);
        $n = (int) get_transient($k);
        if ($n >= self::HOURLY_CAP) { return false; }
        set_transient($k, $n + 1, HOUR_IN_SECONDS);
        return true;
    }

    /** Run every enabled item of the popup that listens to $event. Returns the number of successful subscriptions. */
    public static function dispatch($event, $uid, $title, $config, $email, $fields, $submission_id = 0, $country = '') {
        $items = self::get_items($config);
        if (empty($items)) { return 0; }
        $flat  = self::flat($fields);
        $plain = array_merge($flat, array(
            'email'         => (string) $email,
            'form-name'     => (string) $title,
            'site-name'     => get_bloginfo('name'),
            'submission-id' => (string) $submission_id,
            'country'       => (string) $country,
        ));
        $done = 0;
        foreach ($items as $item) {
            if (empty($item['enable']) || $item['send'] !== $event) { continue; }
            if ($item['api_key'] === '' || $item['campaign_id'] === '') { continue; }
            if (!self::passes($item, $flat)) { continue; }

            $values = array();
            foreach ($item['fields'] as $tag => $tpl) {
                $values[$tag] = trim(self::merge($tpl, $plain));
            }
            $to = isset($values['EMAIL']) ? $values['EMAIL'] : (string) $email;
            if (!self::allow($uid)) {
                if (function_exists('wppoppop_log_event')) { wppoppop_log_event('getresponse_failed', 'GetResponse "' . $item['name'] . '" skipped: hourly limit reached', array('popup_uid' => $uid, 'submission_id' => $submission_id)); }
                continue;
            }
            $res = self::subscribe($item, $to, $values);
            if ($res === true) { $done++; }
            if (function_exists('wppoppop_log_event')) {
                wppoppop_log_event($res === true ? 'getresponse_subscribed' : 'getresponse_failed', 'GetResponse "' . $item['name'] . '" ' . ($res === true ? 'added ' . $to : 'FAILED for ' . $to . ': ' . $res), array('popup_uid' => $uid, 'submission_id' => $submission_id));
            }
        }
        return $done;
    }
}
