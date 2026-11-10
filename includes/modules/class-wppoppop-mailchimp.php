<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MailChimp module (acc-mailchimp): subscribe the visitor to a MailChimp audience after a popup submission.
 * Shape: settings.mailchimp = [ { id, name, enable, send, api_key, list_id, list_name, fields{TAG:value},
 *        field_labels{TAG:label}, groups{cid-iid:true}, group_defs[{id,label}], tags, double, logic_enable, logic_match, rules[] } ]
 * Events: after_submission, after_confirmation (double opt-in confirmed).
 * The API keys are private: redact() removes the whole list before a popup config is printed or served publicly.
 */
class WpPopPop_Mailchimp {

    const MODULE_KEY = 'acc-mailchimp';
    const MAX_ITEMS  = 20;
    const MAX_RULES  = 20;
    const TIMEOUT    = 15;
    const HOURLY_CAP = 300;

    public static function defaults() {
        return array(
            'id'           => '',
            'name'         => 'MailChimp',
            'enable'       => true,
            'send'         => 'after_submission',
            'api_key'      => '',
            'list_id'      => '',
            'list_name'    => '',
            'fields'       => array('EMAIL' => '{{email}}'),
            'field_labels' => array(),
            'groups'       => array(),
            'group_defs'   => array(),
            'tags'         => '',
            'double'       => false,
            'logic_enable' => false,
            'logic_match'  => 'all',
            'rules'        => array(),
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

    /** A MailChimp API key looks like "<hex>-us21"; anything else is dropped (it also decides the API host). */
    public static function clean_key($key) {
        $key = is_scalar($key) ? trim((string) $key) : '';
        return preg_match('/^[A-Za-z0-9]{8,64}-[a-z]{2}[0-9]{1,2}$/', $key) ? $key : '';
    }

    public static function clean_id($id) {
        $id = is_scalar($id) ? trim((string) $id) : '';
        return preg_match('/^[A-Za-z0-9]{1,40}$/', $id) ? $id : '';
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
            $d['id']        = $id !== '' ? self::cut($id, 40) : 'mc_' . substr(md5(uniqid('', true)), 0, 6);
            $name           = self::text($in, 'name', 120);
            $d['name']      = $name !== '' ? $name : 'MailChimp';
            $d['enable']    = !isset($in['enable']) || self::is_true($in['enable']);
            $d['send']      = (isset($in['send']) && $in['send'] === 'after_confirmation') ? 'after_confirmation' : 'after_submission';
            $d['api_key']   = self::clean_key(isset($in['api_key']) ? $in['api_key'] : '');
            $d['list_id']   = self::clean_id(isset($in['list_id']) ? $in['list_id'] : '');
            $d['list_name'] = $d['list_id'] !== '' ? self::text($in, 'list_name', 200) : '';
            $d['tags']      = self::text($in, 'tags', 500);
            $d['double']    = !empty($in['double']) && self::is_true($in['double']);

            $labels = array();
            foreach (array_slice(self::to_array(isset($in['field_labels']) ? $in['field_labels'] : array()), 0, 60, true) as $tag => $label) {
                $tag = strtoupper((string) $tag);
                if (preg_match('/^[A-Z0-9_]{1,20}$/', $tag) && is_scalar($label)) { $labels[$tag] = self::cut(sanitize_text_field((string) $label), 120); }
            }
            $d['field_labels'] = $labels;

            $fields = array('EMAIL' => '{{email}}');
            foreach (array_slice(self::to_array(isset($in['fields']) ? $in['fields'] : array()), 0, 60, true) as $tag => $val) {
                $tag = strtoupper((string) $tag);
                if (!preg_match('/^[A-Z0-9_]{1,20}$/', $tag) || !is_scalar($val)) { continue; }
                $val = self::cut(sanitize_text_field((string) $val), 500);
                if ($tag === 'EMAIL' && $val === '') { $val = '{{email}}'; }
                $fields[$tag] = $val;
            }
            $d['fields'] = $fields;

            $defs = array();
            foreach (array_slice(array_values(self::to_array(isset($in['group_defs']) ? $in['group_defs'] : array())), 0, 200) as $g) {
                $g = self::to_array($g);
                $gid = isset($g['id']) && is_scalar($g['id']) ? (string) $g['id'] : '';
                if (preg_match('/^[A-Za-z0-9]{1,40}-[A-Za-z0-9]{1,40}$/', $gid)) {
                    $defs[] = array('id' => $gid, 'label' => self::text($g, 'label', 200));
                }
            }
            $d['group_defs'] = $defs;

            $groups = array();
            foreach (array_slice(self::to_array(isset($in['groups']) ? $in['groups'] : array()), 0, 200, true) as $gid => $on) {
                if (preg_match('/^[A-Za-z0-9]{1,40}-[A-Za-z0-9]{1,40}$/', (string) $gid) && self::is_true($on)) { $groups[(string) $gid] = true; }
            }
            $d['groups'] = $groups;

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
        return (isset($s['mailchimp']) && is_array($s['mailchimp'])) ? self::sanitize($s['mailchimp']) : array();
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
            unset($config['settings']['mailchimp']);
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
     * One MailChimp API v3 call. The data centre comes from the key suffix (validated, so the host cannot be injected).
     * Returns array('code' => int, 'data' => array) or array('code' => 0, 'error' => string).
     */
    public static function api($key, $method, $path, $body = null) {
        $key = self::clean_key($key);
        if ($key === '') { return array('code' => 0, 'error' => __('Invalid API key.', 'wppoppop')); }
        $dc  = substr($key, strrpos($key, '-') + 1);
        $url = 'https://' . $dc . '.api.mailchimp.com/3.0/' . ltrim($path, '/');
        $args = array(
            'method'  => $method,
            'timeout' => self::TIMEOUT,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('wppoppop:' . $key),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
        );
        if ($body !== null) { $args['body'] = wp_json_encode($body); }
        $res = wp_remote_request($url, $args);
        if (is_wp_error($res)) { return array('code' => 0, 'error' => $res->get_error_message()); }
        $code = (int) wp_remote_retrieve_response_code($res);
        $data = json_decode((string) wp_remote_retrieve_body($res), true);
        return array('code' => $code, 'data' => is_array($data) ? $data : array());
    }

    private static function api_error($r) {
        if (!empty($r['error'])) { return (string) $r['error']; }
        if (!empty($r['data']['detail'])) { return sanitize_text_field((string) $r['data']['detail']); }
        if (!empty($r['data']['title'])) { return sanitize_text_field((string) $r['data']['title']); }
        return sprintf(__('MailChimp returned HTTP %d.', 'wppoppop'), (int) $r['code']);
    }

    /** Audiences of the account: array(id => name), or a WP_Error. */
    public static function fetch_lists($key) {
        $r = self::api($key, 'GET', 'lists?count=100&fields=lists.id,lists.name');
        if ($r['code'] !== 200) { return new WP_Error('wppoppop_mc', self::api_error($r)); }
        $out = array();
        foreach ((isset($r['data']['lists']) && is_array($r['data']['lists']) ? $r['data']['lists'] : array()) as $l) {
            if (!empty($l['id']) && self::clean_id($l['id']) !== '') { $out[(string) $l['id']] = isset($l['name']) ? sanitize_text_field((string) $l['name']) : (string) $l['id']; }
        }
        return $out;
    }

    /** Merge fields of an audience: array(TAG => label) with EMAIL first, or a WP_Error. */
    public static function fetch_fields($key, $list) {
        $list = self::clean_id($list);
        if ($list === '') { return new WP_Error('wppoppop_mc', __('Select an audience first.', 'wppoppop')); }
        $r = self::api($key, 'GET', 'lists/' . $list . '/merge-fields?count=100&fields=merge_fields.tag,merge_fields.name');
        if ($r['code'] !== 200) { return new WP_Error('wppoppop_mc', self::api_error($r)); }
        $out = array('EMAIL' => __('Email address', 'wppoppop'));
        foreach ((isset($r['data']['merge_fields']) && is_array($r['data']['merge_fields']) ? $r['data']['merge_fields'] : array()) as $f) {
            $tag = isset($f['tag']) ? strtoupper((string) $f['tag']) : '';
            if (preg_match('/^[A-Z0-9_]{1,20}$/', $tag) && $tag !== 'EMAIL') { $out[$tag] = isset($f['name']) ? sanitize_text_field((string) $f['name']) : $tag; }
        }
        return $out;
    }

    /** Interest groups of an audience: list of array(id => "cid-iid", label => "Category: Interest"), or a WP_Error. */
    public static function fetch_groups($key, $list) {
        $list = self::clean_id($list);
        if ($list === '') { return new WP_Error('wppoppop_mc', __('Select an audience first.', 'wppoppop')); }
        $r = self::api($key, 'GET', 'lists/' . $list . '/interest-categories?count=100&fields=categories.id,categories.title');
        if ($r['code'] !== 200) { return new WP_Error('wppoppop_mc', self::api_error($r)); }
        $out = array();
        foreach ((isset($r['data']['categories']) && is_array($r['data']['categories']) ? $r['data']['categories'] : array()) as $c) {
            $cid = isset($c['id']) ? self::clean_id($c['id']) : '';
            if ($cid === '') { continue; }
            $ri = self::api($key, 'GET', 'lists/' . $list . '/interest-categories/' . $cid . '/interests?count=100&fields=interests.id,interests.name');
            if ($ri['code'] !== 200) { return new WP_Error('wppoppop_mc', self::api_error($ri)); }
            foreach ((isset($ri['data']['interests']) && is_array($ri['data']['interests']) ? $ri['data']['interests'] : array()) as $i) {
                $iid = isset($i['id']) ? self::clean_id($i['id']) : '';
                if ($iid === '') { continue; }
                $out[] = array(
                    'id'    => $cid . '-' . $iid,
                    'label' => (isset($c['title']) ? sanitize_text_field((string) $c['title']) : $cid) . ': ' . (isset($i['name']) ? sanitize_text_field((string) $i['name']) : $iid),
                );
            }
        }
        return $out;
    }

    /** Subscribe (or update) one visitor in the item's audience. Returns true or an error string. */
    public static function subscribe($item, $email, $merge_values) {
        $key  = $item['api_key'];
        $list = $item['list_id'];
        if ($key === '' || $list === '') { return __('API key or audience is missing.', 'wppoppop'); }
        $email = sanitize_email((string) $email);
        if ($email === '' || !is_email($email)) { return __('Invalid email address.', 'wppoppop'); }

        $base = 'lists/' . $list . '/members/' . md5(strtolower($email));
        $cur  = self::api($key, 'GET', $base);
        $existing = ($cur['code'] === 200);
        $status   = $existing && isset($cur['data']['status']) ? (string) $cur['data']['status'] : '';
        if ($existing && $status === 'pending') {
            self::api($key, 'DELETE', $base);
            $existing = false;
        }

        $merge = array();
        foreach ($merge_values as $tag => $val) {
            if ($tag !== 'EMAIL' && $val !== '') { $merge[$tag] = $val; }
        }
        $interests = array();
        foreach ((array) $item['groups'] as $gid => $on) {
            $p = explode('-', (string) $gid, 2);
            if (!empty($on) && count($p) === 2) { $interests[$p[1]] = true; }
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $body = array('email_address' => $email, 'status_if_new' => 'subscribed', 'status' => 'subscribed');
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) { $body['ip_signup'] = $ip; }
        if (!empty($item['double'])) {
            $body['status_if_new'] = 'pending';
            $body['status']        = $existing ? 'subscribed' : 'pending';
        }
        if (!empty($merge))     { $body['merge_fields'] = $merge; }
        if (!empty($interests)) { $body['interests']    = $interests; }

        $r = self::api($key, 'PUT', $base, $body);
        if ($r['code'] < 200 || $r['code'] > 299) { return self::api_error($r); }

        $tags = array();
        foreach (explode(',', (string) $item['tags']) as $t) {
            $t = trim($t);
            if ($t !== '') { $tags[] = array('name' => self::cut($t, 100), 'status' => 'active'); }
        }
        if (!empty($tags)) {
            $rt = self::api($key, 'POST', $base . '/tags', array('tags' => array_slice($tags, 0, 20)));
            if ($rt['code'] < 200 || $rt['code'] > 299) { return self::api_error($rt); }
        }
        return true;
    }

    /** Hourly cap per popup, so a bot posting the form cannot burn the account's API quota. */
    private static function allow($uid) {
        $k = 'wppoppop_mc_' . md5((string) $uid);
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
            if ($item['api_key'] === '' || $item['list_id'] === '') { continue; }
            if (!self::passes($item, $flat)) { continue; }

            $values = array();
            foreach ($item['fields'] as $tag => $tpl) {
                $values[$tag] = trim(self::merge($tpl, $plain));
            }
            $to = isset($values['EMAIL']) ? $values['EMAIL'] : (string) $email;
            if (!self::allow($uid)) {
                if (function_exists('wppoppop_log_event')) { wppoppop_log_event('mailchimp_failed', 'MailChimp "' . $item['name'] . '" skipped: hourly limit reached', array('popup_uid' => $uid, 'submission_id' => $submission_id)); }
                continue;
            }
            $res = self::subscribe($item, $to, $values);
            if ($res === true) { $done++; }
            if (function_exists('wppoppop_log_event')) {
                wppoppop_log_event($res === true ? 'mailchimp_subscribed' : 'mailchimp_failed', 'MailChimp "' . $item['name'] . '" ' . ($res === true ? 'subscribed ' . $to : 'FAILED for ' . $to . ': ' . $res), array('popup_uid' => $uid, 'submission_id' => $submission_id));
            }
        }
        return $done;
    }
}
