<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WpPopPop Double Opt-In: defaults, merge tags and confirmation email dispatch.
 * Config lives in popup data -> settings.double_optin (whitelisted in the builder save).
 */
class WpPopPop_Double_Optin {

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

        $plain = array_merge(self::flat($fields), array(
            'email'             => (string) $email,
            'site-name'         => get_bloginfo('name'),
            'global-from-email' => (string) $g_email,
            'global-from-name'  => (string) $g_name,
            'confirmation-url'  => $confirm_url,
        ));
        $html = array_map('esc_html', $plain);
        $html['confirmation-url'] = esc_url($confirm_url);

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
