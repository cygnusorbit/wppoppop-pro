<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Licence page (WpPopPop > License). Reuses core's admin bar, theme variables and card look.
 */
class WpPopPop_Pro_Admin {

    const PAGE   = 'wppoppop-license';
    const ACTION = 'wppoppop_pro_license';

    public static function hooks() {
        add_action('wppoppop_admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_' . self::ACTION, array(__CLASS__, 'handle'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
    }

    public static function menu($parent) {
        add_submenu_page($parent, __('License', 'wppoppop-pro'), __('License', 'wppoppop-pro'), 'manage_options', self::PAGE, array(__CLASS__, 'render'));
    }

    public static function assets($hook) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page !== self::PAGE) {
            return;
        }
        self::enqueue_shell();
    }

    /** Styles and scripts shared by the License page and the locked Pro pages. */
    public static function enqueue_shell() {
        wp_enqueue_style('dashicons');
        wp_enqueue_style('wppoppop-dashboard-css', WPPOPPOP_URL . 'admin/css/dashboard.css', array(), WPPOPPOP_VERSION);
        wp_enqueue_style('wppoppop-overview-css', WPPOPPOP_URL . 'admin/css/overview.css', array(), WPPOPPOP_VERSION);
        wp_enqueue_style('wppoppop-bar-actions-css', WPPOPPOP_URL . 'admin/css/bar-actions.css', array('wppoppop-dashboard-css', 'wppoppop-overview-css'), WPPOPPOP_VERSION);
        wp_enqueue_style('wppoppop-pro-license-css', WPPOPPOP_PRO_URL . 'assets/css/license.css', array('wppoppop-bar-actions-css'), WPPOPPOP_PRO_VERSION);
        wp_enqueue_script('wppoppop-overview-js', WPPOPPOP_URL . 'admin/js/overview.js', array('jquery'), WPPOPPOP_VERSION, true);
    }

    public static function handle() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wppoppop-pro'));
        }
        check_admin_referer(self::ACTION, 'wppoppop_pro_nonce');

        $op = isset($_POST['wpp_license_op']) ? sanitize_key(wp_unslash($_POST['wpp_license_op'])) : '';
        $r  = array('ok' => false, 'message' => '');
        if ($op === 'activate') {
            $key = isset($_POST['wpp_license_key']) ? sanitize_text_field(wp_unslash($_POST['wpp_license_key'])) : '';
            $r   = WpPopPop_Pro_License::activate($key);
        } elseif ($op === 'deactivate') {
            $r = WpPopPop_Pro_License::deactivate();
        } elseif ($op === 'check') {
            $r = WpPopPop_Pro_License::check();
        }
        set_transient('wppoppop_pro_msg_' . get_current_user_id(), $r, 60);
        wp_safe_redirect(add_query_arg('wpp_status', $r['ok'] ? 'ok' : 'error', admin_url('admin.php?page=' . self::PAGE)));
        exit;
    }

    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wppoppop-pro'));
        }
        $notice = null;
        if (isset($_GET['wpp_status'])) {
            $tk     = 'wppoppop_pro_msg_' . get_current_user_id();
            $notice = get_transient($tk);
            delete_transient($tk);
        }
        include WPPOPPOP_PRO_PATH . 'templates/license-page.php';
    }
}
