<?php
if (!defined('ABSPATH')) {
    exit;
}

class WpPopPop_Addon_WooCommerce {
    public function __construct() {
        if (!class_exists('WooCommerce')) {
            return;
        }
        if (!WpPopPop_Woocommerce::module_active()) {
            return; /* WPPOPPOP_MODULE_GATE */
        }
        add_action('wp_ajax_wppoppop_apply_wc_coupon', [$this, 'ajax_apply_wc_coupon']);
        add_action('wp_ajax_nopriv_wppoppop_apply_wc_coupon', [$this, 'ajax_apply_wc_coupon']);
    }

    public function generate_wc_coupon(array $args) {
        return WpPopPop_Woocommerce::generate($args);
    }

    public function auto_apply_wc_coupon($coupon_code) {
        if (!class_exists('WooCommerce') || !function_exists('WC') || !WC()->cart) {
            return false;
        }

        /* Guests need a session cookie, or the cart (and the coupon) is lost on the next page. */
        if (WC()->session && method_exists(WC()->session, 'has_session') && !WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }

        if (!WC()->cart->has_discount($coupon_code)) {
            return WC()->cart->add_discount(sanitize_text_field($coupon_code));
        }
        return true;
    }

    public function ajax_apply_wc_coupon() {
        check_ajax_referer('wppoppop_front_nonce', 'nonce');
        $code = isset($_POST['coupon_code']) ? sanitize_text_field(wp_unslash($_POST['coupon_code'])) : '';
        if (empty($code)) {
            wp_send_json_error(['message' => __('Missing coupon code.', 'wppoppop')]);
        }

        $applied = $this->auto_apply_wc_coupon($code);
        if ($applied) {
            wp_send_json_success(['message' => __('Coupon applied to cart successfully!', 'wppoppop')]);
        } else {
            wp_send_json_error(['message' => __('Unable to apply coupon to cart.', 'wppoppop')]);
        }
    }

    public function matches_cart_conditions(array $woo_rules) {
        if (!class_exists('WooCommerce') || !function_exists('WC') || !WC()->cart) {
            return true;
        }

        $cart_subtotal = floatval(WC()->cart->get_subtotal());
        if (!empty($woo_rules['min_cart_total']) && $cart_subtotal < floatval($woo_rules['min_cart_total'])) {
            return false;
        }
        if (!empty($woo_rules['max_cart_total']) && $cart_subtotal > floatval($woo_rules['max_cart_total'])) {
            return false;
        }

        return true;
    }
}
