<?php
if (!defined('ABSPATH')) {
    exit;
}

class WpPopPop_Addon_Downloads {
    public function __construct() {
        if (!WpPopPop_Downloads::module_active()) {
            return; /* WPPOPPOP_MODULE_GATE */
        }
        add_action('template_redirect', [$this, 'handle_secure_download']);
    }

    public function generate_download_token($file_url, $popup_uid = '') {
        return WpPopPop_Downloads::generate_token($file_url, $popup_uid);
    }

    public function handle_secure_download() {
        if (!isset($_GET['wppoppop_download'])) {
            return;
        }

        $token = sanitize_key(wp_unslash($_GET['wppoppop_download']));
        $transient_key = 'wppoppop_dl_' . $token;
        $data = get_transient($transient_key);

        if (!$data || empty($data['file_url'])) {
            wp_die(__('Download link has expired or is invalid.', 'wppoppop'), __('Invalid Download', 'wppoppop'), ['response' => 403]);
        }

        // Delete transient to enforce single-use protection
        delete_transient($transient_key);

        $file_url   = $data['file_url'];
        $upload_dir = wp_upload_dir();

        // A file in the uploads folder is streamed; the real path is checked so the link can never leave that folder
        if (strpos(preg_replace('#^https?:#i', '', $file_url), preg_replace('#^https?:#i', '', rtrim($upload_dir['baseurl'], '/')) . '/') === 0) {
            $file_path = WpPopPop_Downloads::resolve_local($file_url, $upload_dir['basedir'], $upload_dir['baseurl']);
            if ($file_path === '') {
                wp_die(__('The file is no longer available.', 'wppoppop'), __('Invalid Download', 'wppoppop'), ['response' => 404]);
            }
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . sanitize_file_name(basename($file_path)) . '"');
            header('X-Content-Type-Options: nosniff');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
            readfile($file_path);
            exit;
        }

        // External file: the address was set by an administrator, so a plain redirect is intended
        wp_redirect(esc_url_raw($file_url)); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
        exit;
    }
}
