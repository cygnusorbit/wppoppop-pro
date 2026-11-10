<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MailChimp module admin endpoints (builder only): load audiences, merge fields and groups for the Inspector cards.
 * Every call needs manage_options and the builder (or admin) nonce; the API key is only forwarded to MailChimp.
 */
class WpPopPop_Ajax_Mailchimp {

    public function __construct() {
        add_action('wp_ajax_wppoppop_mailchimp_lists',  array($this, 'lists'));
        add_action('wp_ajax_wppoppop_mailchimp_fields', array($this, 'fields'));
        add_action('wp_ajax_wppoppop_mailchimp_groups', array($this, 'groups'));
    }

    private function guard() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        $ok = $nonce !== '' && (wp_verify_nonce($nonce, 'wppoppop_builder_nonce') || wp_verify_nonce($nonce, 'wppoppop_admin_nonce'));
        if (!$ok || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wppoppop')), 403);
        }
        $key = WpPopPop_Mailchimp::clean_key(isset($_POST['api_key']) ? wp_unslash($_POST['api_key']) : '');
        if ($key === '') {
            wp_send_json_error(array('message' => __('Enter a valid MailChimp API key (for example 0123456789abcdef-us21).', 'wppoppop')));
        }
        return $key;
    }

    private function reply($data) {
        if (is_wp_error($data)) { wp_send_json_error(array('message' => $data->get_error_message())); }
        wp_send_json_success($data);
    }

    public function lists() {
        $key = $this->guard();
        $res = WpPopPop_Mailchimp::fetch_lists($key);
        if (!is_wp_error($res)) {
            $rows = array();
            foreach ($res as $id => $name) { $rows[] = array('id' => (string) $id, 'name' => $name); }
            $res = array('lists' => $rows);
        }
        $this->reply($res);
    }

    public function fields() {
        $key = $this->guard();
        $res = WpPopPop_Mailchimp::fetch_fields($key, isset($_POST['list_id']) ? wp_unslash($_POST['list_id']) : '');
        if (!is_wp_error($res)) {
            $rows = array();
            foreach ($res as $tag => $label) { $rows[] = array('tag' => (string) $tag, 'label' => $label); }
            $res = array('fields' => $rows);
        }
        $this->reply($res);
    }

    public function groups() {
        $key = $this->guard();
        $res = WpPopPop_Mailchimp::fetch_groups($key, isset($_POST['list_id']) ? wp_unslash($_POST['list_id']) : '');
        if (!is_wp_error($res)) { $res = array('groups' => $res); }
        $this->reply($res);
    }
}
