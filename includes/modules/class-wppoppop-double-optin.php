<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WpPopPop Double Opt-In: defaults, merge tags and confirmation email dispatch.
 * Config lives in popup data -> settings.double_optin (whitelisted in the builder save).
 */
class WpPopPop_Double_Optin {

    const MODULE_KEY = 'acc-double-optin';

    public static function defaults() {
        return array(
            'enable'        => false,
            'recipient'     => '',
            'subject'       => __('Please confirm your email address', 'wppoppop'),
            'message'       => __("Dear visitor!\n\nPlease confirm your email address by clicking the following link:\n<a href=\"{{confirmation-url}}\">{{confirmation-url}}</a>\n\nThanks.", 'wppoppop'),
            'from_email'    => '{{global-from-email}}',
            'from_name'     => '{{global-from-name}}',
            'thank_message' => '<h4 style="text-align: center;">' . __('Thank you!', 'wppoppop') . '</h4><p style="text-align: center;">' . __('Your email address successfully confirmed.', 'wppoppop') . '</p>',
            'thank_url'     => '',
        );
    }

    public static function get_config($config) {
        $s = (is_array($config) && isset($config['settings']) && is_array($config['settings'])) ? $config['settings'] : array();
        $d = (isset($s['double_optin']) && is_array($s['double_optin'])) ? $s['double_optin'] : array();
        return array_merge(self::defaults(), $d);
    }

    /** WPPOPPOP_DOPT_MODULE: Settings > Modules switch. A module that is switched off or deleted never affects the front end. */
    public static function module_active() {
        if (!class_exists('WpPopPop_Modules')) {
            return true;
        }
        if (!WpPopPop_Modules::get(self::MODULE_KEY)) {
            $orphans = WpPopPop_Modules::orphans();
            return !isset($orphans[self::MODULE_KEY]);
        }
        if (!class_exists('WpPopPop_Admin_Settings')) {
            require_once WPPOPPOP_PATH . 'includes/admin/class-admin-settings.php';
        }
        return WpPopPop_Admin_Settings::module_enabled(self::MODULE_KEY);
    }

    /** Title and decoded config of a popup ({{form-name}} and the Layer Names of {{form-data}}). */
    private static function popup_row($uid) {
        global $wpdb;
        $out = array('title' => '', 'config' => array());
        if (!is_string($uid) || $uid === '') {
            return $out;
        }
        /* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table name is built from $wpdb->prefix and every value is prepared */ $row = $wpdb->get_row($wpdb->prepare("SELECT title, data FROM {$wpdb->prefix}wppoppop_items WHERE uid = %s", $uid), ARRAY_A);
        if (is_array($row)) {
            $out['title'] = (string) $row['title'];
            $cfg = json_decode((string) $row['data'], true);
            $out['config'] = is_array($cfg) ? $cfg : array();
        }
        return $out;
    }

    /** {{form-data}} rows labelled with the Layer Name (falls back to the field key): array(html table, plain text). */
    private static function form_rows($fields, $config) {
        $labels = is_callable(array('WpPopPop_Notifications', 'field_labels')) ? WpPopPop_Notifications::field_labels($config) : array();
        if (!is_array($labels)) {
            $labels = array();
        }
        $html = '';
        $text = '';
        foreach (self::flat($fields) as $k => $v) {
            if ($k === '_name' || $k === '_phone' || $k === '_ip' || $k === '_ua' || substr((string) $k, -5) === '_path') {
                continue;
            }
            $label = (isset($labels[$k]) && is_string($labels[$k]) && $labels[$k] !== '') ? $labels[$k] : $k;
            $html .= '<tr><td style="padding:4px 10px;border:1px solid #e2e8f0;"><strong>' . esc_html($label) . '</strong></td><td style="padding:4px 10px;border:1px solid #e2e8f0;">' . esc_html($v) . '</td></tr>';
            $text .= $label . ': ' . $v . "\n";
        }
        return array($html === '' ? '' : '<table style="border-collapse:collapse;">' . $html . '</table>', trim($text));
    }

    /** Replace {{tag}} placeholders. Unknown tags become empty. */
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

    public static function global_sender() {
        $g = get_option('wppoppop_settings', array());
        if (!is_array($g)) { $g = array(); }
        $email = !empty($g['sender_email']) ? $g['sender_email'] : get_option('admin_email');
        $name  = !empty($g['sender_name']) ? $g['sender_name'] : get_bloginfo('name');
        return array($email, $name);
    }

    public static function send_confirmation($uid, $cfg, $email, $fields, $token, $submission_id = 0) {
        list($g_email, $g_name) = self::global_sender();
        $confirm_url = add_query_arg('wppoppop_confirm', rawurlencode($token), home_url('/'));

        $wppoppop_row  = self::popup_row($uid);
        $wppoppop_rows = self::form_rows($fields, $wppoppop_row['config']);
        $plain = array_merge(self::flat($fields), array(
            'form-name'         => (string) $wppoppop_row['title'],
            'submission-id'     => (string) $submission_id,
            'form-data'         => $wppoppop_rows[1],
            'email'             => (string) $email,
            'site-name'         => get_bloginfo('name'),
            'global-from-email' => (string) $g_email,
            'global-from-name'  => (string) $g_name,
            'confirmation-url'  => $confirm_url,
        ));
        $html = array_map('esc_html', $plain);
        $html['confirmation-url'] = esc_url($confirm_url);
        $html['form-data'] = $wppoppop_rows[0];

        $to_raw = trim((string) self::merge($cfg['recipient'], $plain));
        $to = array();
        foreach (explode(',', $to_raw) as $addr) {
            $addr = sanitize_email(trim($addr));
            if ($addr !== '' && is_email($addr)) { $to[] = $addr; }
        }
        if (empty($to) && $email !== '' && is_email($email)) { $to[] = $email; }
        if (empty($to)) { return false; }

        $subject = sanitize_text_field(self::merge($cfg['subject'], $plain));
        $body    = wpautop(wp_kses_post(self::merge($cfg['message'], $html)));

        $from_email = sanitize_email(trim(self::merge($cfg['from_email'], $plain)));
        if (!is_email($from_email)) { $from_email = sanitize_email($g_email); }
        $from_name = preg_replace('/[<>"\r\n]+/', '', trim(self::merge($cfg['from_name'], $plain)));
        if ($from_name === '') { $from_name = preg_replace('/[<>"\r\n]+/', '', (string) $g_name); }

        $headers = array('Content-Type: text/html; charset=UTF-8');
        if (is_email($from_email)) { $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>'; }

        $ok = wp_mail($to, $subject, $body, $headers);
        if (function_exists('wppoppop_log_event')) {
            wppoppop_log_event($ok ? 'double_optin_sent' : 'double_optin_failed', 'Double opt-in email ' . ($ok ? 'sent to ' : 'FAILED for ') . implode(', ', $to), array('popup_uid' => $uid, 'submission_id' => $submission_id));
        }
        return $ok;
    }
}
