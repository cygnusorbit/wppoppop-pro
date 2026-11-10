<?php
if (!defined('ABSPATH')) {
    exit;
}

class WpPopPop_Addon_Sms {
    public function dispatch_twilio_sms($to_number, $body_text, array $credentials) {
        $sid   = $credentials['account_sid'] ?? '';
        $token = $credentials['auth_token'] ?? '';
        $from  = $credentials['from_number'] ?? '';

        if (empty($sid) || empty($token) || empty($from) || empty($to_number)) {
            return new WP_Error('missing_params', __('Twilio account SID, auth token, from number, and recipient number are required.', 'wppoppop'));
        }

        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . urlencode($sid) . '/Messages.json';

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($sid . ':' . $token),
                'Content-Type'  => 'application/x-www-form-urlencoded; charset=utf-8'
            ],
            'body' => [
                'From' => sanitize_text_field($from),
                'To'   => sanitize_text_field($to_number),
                'Body' => sanitize_textarea_field($body_text)
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            wppoppop_log_event('sms_failed', 'Twilio error: ' . $response->get_error_message(), ['to' => $to_number]);
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if ($code >= 200 && $code < 300) {
            wppoppop_log_event('sms_dispatched', 'Twilio SMS sent to ' . $to_number, ['sid' => $json['sid'] ?? '']);
            return true;
        }

        $err_msg = $json['message'] ?? 'Twilio API error HTTP ' . $code;
        wppoppop_log_event('sms_failed', 'Twilio error: ' . $err_msg, ['code' => $code]);
        return new WP_Error('twilio_api_error', $err_msg);
    }
}
