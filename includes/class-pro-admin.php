<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * License sub-page (WpPopPop > License). Once Freemius is configured the SDK shows its own activation screen on this
 * page and, after activation, its Account page. Until then the page explains how to switch licensing on.
 * Reuses core's admin bar, theme variables and card look.
 */
class WpPopPop_Pro_Admin {

    const PAGE = 'wppoppop-license';

    public static function hooks() {
        add_action('wppoppop_admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
    }

    public static function menu($parent) {
        if (WpPopPop_Pro_Freemius::is_configured() && !WpPopPop_Pro_Freemius::in_activation_mode()) {
            return;
        }
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

    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wppoppop-pro'));
        }
        include WPPOPPOP_PRO_PATH . 'templates/license-page.php';
    }
}
