<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pro submission pipeline: Security & Protections, Double Opt-In, Autoresponder, Webhooks, MailChimp, GetResponse,
 * Twilio SMS alerts, WooCommerce coupons, Downloads, GeoIP, the module admin endpoints and the privacy tools.
 *
 * Core owns the lead capture (REST / AJAX), the confirmation link and the Notifications module, and only calls the
 * filters and actions answered here (see WpPopPop_Module_Bridge). Without this plugin leads are stored as confirmed
 * and the stored settings of these modules are kept untouched, so nothing is lost when the plugin is deactivated.
 */
class WpPopPop_Pro_Pipeline {

    /** Popup-settings keys sanitised here (core lists the same keys in WpPopPop_Module_Bridge::EXT_KEYS). */
    const KEYS = array(
        'double_optin', 'autoresponder', 'webhooks', 'woocommerce', 'downloads', 'sms', 'mailchimp', 'getresponse', 'security',
    );

    protected static $booted = false;

    public static function boot() {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        add_filter('wppoppop_handled_settings_keys', array(__CLASS__, 'handled_keys'));
        add_filter('wppoppop_sanitize_popup_settings', array(__CLASS__, 'sanitize'), 10, 2);

        add_filter('wppoppop_submission_precheck', array(__CLASS__, 'precheck'), 10, 4);
        add_filter('wppoppop_double_optin_config', array(__CLASS__, 'double_optin_config'), 10, 2);
        add_filter('wppoppop_double_optin_send', array(__CLASS__, 'double_optin_send'), 10, 2);
        add_action('wppoppop_submission_dispatch', array(__CLASS__, 'dispatch'));
        add_filter('wppoppop_submission_extras', array(__CLASS__, 'extras'), 10, 2);
        add_filter('wppoppop_confirmation_thank', array(__CLASS__, 'confirmation_thank'), 10, 3);
        add_action('wppoppop_after_confirmation', array(__CLASS__, 'after_confirmation'));
        add_filter('wppoppop_resend_user_email', array(__CLASS__, 'resend_user_email'), 10, 5);

        add_filter('wppoppop_notification_tech_rows', array(__CLASS__, 'notification_tech_rows'), 10, 3);
        add_filter('wppoppop_submission_record_fields', array(__CLASS__, 'record_fields'), 10, 2);
        add_filter('wppoppop_popup_wrapper_attrs', array(__CLASS__, 'wrapper_attrs'), 11, 3);
        add_filter('wppoppop_redact_config', array(__CLASS__, 'redact'));
        add_filter('wppoppop_diagnostic_sms', array(__CLASS__, 'diagnostic_sms'), 10, 3);
        add_filter('wppoppop_visitor_country', array(__CLASS__, 'visitor_country'));

        add_filter('wp_privacy_personal_data_exporters', array('WpPopPop_Protect', 'register_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array('WpPopPop_Protect', 'register_eraser'));

        new WpPopPop_Addon_Downloads();
        new WpPopPop_Addon_WooCommerce();
        if (is_admin()) {
            new WpPopPop_Ajax_Mailchimp();
            new WpPopPop_Ajax_Getresponse();
        }
    }

    /* ---------------------------------------------------------------- save / template */

    public static function handled_keys($keys) {
        return array_merge((array) $keys, self::KEYS);
    }

    /** Allow-lists every key in KEYS. $ctx = array(context => save|template, raw, prev, uid). */
    public static function sanitize($settings, $ctx) {
        if (!is_object($settings)) {
            return $settings;
        }
        $raw = isset($ctx['raw']) && is_array($ctx['raw']) ? $ctx['raw'] : array();
        $tpl = isset($ctx['context']) && $ctx['context'] === 'template';
        $get = function ($key) use ($raw) {
            return isset($raw[$key]) ? $raw[$key] : array();
        };
        $json = function ($v) {
            return json_decode(wp_json_encode($v));
        };

        /* Double Opt-In: strict allow-list of text fields. */
        $do = (isset($raw['double_optin']) && is_object($raw['double_optin'])) ? get_object_vars($raw['double_optin']) : array();
        $txt = function ($k) use ($do) {
            return (isset($do[$k]) && !is_array($do[$k]) && !is_object($do[$k])) ? sanitize_text_field((string) $do[$k]) : '';
        };
        $html = function ($k) use ($do) {
            return (isset($do[$k]) && is_string($do[$k])) ? wp_kses_post($do[$k]) : '';
        };
        $settings->double_optin = (object) array(
            'enable'        => isset($do['enable']) && in_array($do['enable'], array(true, 1, '1', 'true'), true),
            'recipient'     => $txt('recipient'),
            'subject'       => $txt('subject'),
            'message'       => $html('message'),
            'from_email'    => $txt('from_email'),
            'from_name'     => $txt('from_name'),
            'thank_message' => $html('thank_message'),
            'thank_url'     => (isset($do['thank_url']) && is_string($do['thank_url'])) ? esc_url_raw($do['thank_url']) : '',
        );

        foreach (array('autoresponder' => 'WpPopPop_Autoresponder', 'webhooks' => 'WpPopPop_Webhooks', 'woocommerce' => 'WpPopPop_Woocommerce', 'downloads' => 'WpPopPop_Downloads') as $key => $class) {
            $settings->$key = $json(call_user_func(array($class, 'sanitize'), $get($key)));
        }

        /* Defaults are never stored. */
        $sms = WpPopPop_Sms::sanitize($get('sms'));
        if ($sms !== WpPopPop_Sms::defaults()) {
            $settings->sms = $json($sms);
        }

        /* Integration lists are stored only when posted; templates never carry account keys or list ids. */
        $blank = array(
            'mailchimp'   => array('class' => 'WpPopPop_Mailchimp', 'clear' => array('api_key', 'list_id', 'list_name')),
            'getresponse' => array('class' => 'WpPopPop_Getresponse', 'clear' => array('api_key', 'campaign_id', 'campaign_name')),
        );
        foreach ($blank as $key => $def) {
            if (!isset($raw[$key])) {
                continue;
            }
            $items = call_user_func(array($def['class'], 'sanitize'), $raw[$key]);
            if ($tpl && is_array($items)) {
                foreach ($items as $i => $it) {
                    foreach ($def['clear'] as $field) {
                        $items[$i][$field] = '';
                    }
                }
            }
            $clean = $json($items);
            $settings->$key = is_array($clean) ? $clean : array();
        }

        if (isset($raw['security'])) {
            $sp = WpPopPop_Protect::sanitize($raw['security']);
            if ($sp !== WpPopPop_Protect::defaults()) {
                $settings->security = $json($sp);
            }
        }

        return $settings;
    }

    /* ---------------------------------------------------------------- submission pipeline */

    /** Antibot before anything is stored; the technical info (IP-address, User-Agent) is captured only when allowed. */
    public static function precheck($res, $uid, $fields, $config) {
        if (!WpPopPop_Protect::module_active()) {
            return $res;
        }
        $sp   = WpPopPop_Protect::get_config($config);
        $deny = WpPopPop_Protect::antibot($uid, $fields, $sp);
        $res['deny'] = (string) $deny;
        if ($deny === '') {
            $res['tech']      = WpPopPop_Protect::capture($sp);
            $res['mail_skip'] = empty($sp['email_tech']) ? array('_ip' => 1, '_ua' => 1) : array();
        }
        return $res;
    }

    /** Double Opt-In is on with the module setting or the Notifications "enable_double_optin" flag. */
    public static function double_optin_config($res, $config) {
        $cfg   = WpPopPop_Double_Optin::get_config($config);
        $notif = (is_array($config) && isset($config['notifications']) && is_array($config['notifications'])) ? $config['notifications'] : array();
        return array(
            'active' => !empty($cfg['enable']) || !empty($notif['enable_double_optin']),
            'cfg'    => $cfg,
        );
    }

    public static function double_optin_send($sent, $a) {
        return (bool) WpPopPop_Double_Optin::send_confirmation($a['uid'], $a['cfg'], $a['email'], $a['fields'], $a['token'], $a['submission_id']);
    }

    /** Runs after a lead is stored and the Notifications module has been dispatched. */
    public static function dispatch($ctx) {
        $uid    = $ctx['uid'];
        $config = $ctx['config'];
        $email  = $ctx['email'];
        $fields = $ctx['fields'];
        $sid    = $ctx['submission_id'];
        $doi    = !empty($ctx['double_optin']);

        if ($doi) {
            WpPopPop_Double_Optin::send_confirmation($uid, $ctx['double_optin_cfg'], $email, $fields, $ctx['confirm_token'], $sid);
        }

        $ar = WpPopPop_Autoresponder::get_config($config);
        if (!empty($ar['enable']) && !$doi && $email !== '' && WpPopPop_Autoresponder::module_active()) {
            WpPopPop_Autoresponder::send($email, $fields, $ar);
        }

        $wh = WpPopPop_Webhooks::get_config($config);
        if (!empty($wh['url']) && WpPopPop_Webhooks::module_active()) {
            WpPopPop_Webhooks::send($uid, $sid, $email, $fields, $ctx['country'], $wh);
        }

        if (WpPopPop_Mailchimp::module_active()) {
            WpPopPop_Mailchimp::dispatch('after_submission', $uid, $ctx['title'], $config, $email, $fields, $sid, $ctx['country']);
        }
        if (WpPopPop_Getresponse::module_active()) {
            WpPopPop_Getresponse::dispatch('after_submission', $uid, $ctx['title'], $config, $email, $fields, $sid, $ctx['country']);
        }

        $sms = WpPopPop_Sms::get_config($config, $uid);
        if (!empty($sms['enable']) && WpPopPop_Sms::module_active()) {
            WpPopPop_Sms::notify($uid, $ctx['title'], $email, $sms);
        }
    }

    /** Coupon and one-time download link; never before the email is confirmed. */
    public static function extras($extras, $ctx) {
        if (!empty($ctx['double_optin'])) {
            return $extras;
        }
        $extras['coupon_code']  = (string) WpPopPop_Woocommerce::issue($ctx['uid'], $ctx['email'], WpPopPop_Woocommerce::get_config($ctx['config']));
        $extras['download_url'] = (string) WpPopPop_Downloads::issue($ctx['uid'], WpPopPop_Downloads::get_config($ctx['config']));
        return $extras;
    }

    public static function confirmation_thank($res, $config, $email) {
        $cfg = WpPopPop_Double_Optin::get_config($config);
        return array(
            'message' => trim((string) WpPopPop_Double_Optin::merge($cfg['thank_message'], array('email' => esc_html($email)))),
            'url'     => (string) $cfg['thank_url'],
        );
    }

    public static function after_confirmation($ctx) {
        foreach (array('WpPopPop_Mailchimp', 'WpPopPop_Getresponse') as $class) {
            if (call_user_func(array($class, 'module_active'))) {
                call_user_func(array($class, 'dispatch'), 'after_confirmation', $ctx['uid'], $ctx['title'], $ctx['config'], $ctx['email'], $ctx['fields'], $ctx['submission_id'], $ctx['country']);
            }
        }
    }

    /** Submissions > Resend: confirmation link for a pending lead, otherwise the Autoresponder. */
    public static function resend_user_email($sent, $sub, $config, $email, $fields) {
        $dopt = WpPopPop_Double_Optin::get_config($config);
        $ar   = WpPopPop_Autoresponder::get_config($config);
        if ($sub['status'] === 'pending' && !empty($dopt['enable']) && !empty($sub['confirm_token'])) {
            return (bool) WpPopPop_Double_Optin::send_confirmation($sub['popup_uid'], $dopt, $email, $fields, $sub['confirm_token'], (int) $sub['id']);
        }
        if (!empty($ar['enable']) && WpPopPop_Autoresponder::module_active()) {
            return (bool) WpPopPop_Autoresponder::send($email, $fields, $ar);
        }
        return false;
    }

    /* ---------------------------------------------------------------- display / privacy */

    /** Notification emails: the technical info joins {{form-data}} only when Security & Protections allows it. */
    public static function notification_tech_rows($rows, $tech, $config) {
        if (!WpPopPop_Protect::module_active()) {
            return $rows;
        }
        return WpPopPop_Protect::take_tech($tech, WpPopPop_Protect::get_config($config));
    }

    public static function record_fields($fields, $uid) {
        return WpPopPop_Protect::record_fields($fields, $uid);
    }

    public static function wrapper_attrs($attrs, $settings, $args) {
        return $attrs . WpPopPop_Protect::frontend_attrs(isset($args['config']) ? $args['config'] : array());
    }

    /** API keys and credentials never leave the server inside a popup config. */
    public static function redact($config) {
        foreach (array('WpPopPop_Webhooks', 'WpPopPop_Mailchimp', 'WpPopPop_Getresponse', 'WpPopPop_Sms', 'WpPopPop_Downloads') as $class) {
            $config = call_user_func(array($class, 'redact'), $config);
        }
        return $config;
    }

    public static function diagnostic_sms($result, $to, $creds) {
        $sms = new WpPopPop_Addon_Sms();
        return $sms->dispatch_twilio_sms($to, 'WpPopPop diagnostic test SMS ping.', $creds);
    }

    /** Visitor country through the GeoIP service chosen in Settings > Security. */
    public static function visitor_country($country) {
        if (is_string($country) && $country !== '') {
            return $country;
        }
        $service = wppoppop_get_setting('geoip_service', wppoppop_get_setting('geoip_api_service', 'none'));
        if ($service === 'none') {
            return '';
        }
        return (string) WpPopPop_GeoIP::get_country_code();
    }
}
