<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Licence status for the rest of the plugin. Everything is answered by Freemius (see WpPopPop_Pro_Freemius).
 * Policy: an active or an expired licence keeps every feature working; expiry only stops updates and support.
 * Until Freemius is configured in wppoppop-pro.php, Pro stays locked.
 */
class WpPopPop_Pro_License {

    public static function is_configured() {
        return WpPopPop_Pro_Freemius::is_configured();
    }

    public static function is_unlocked() {
        return WpPopPop_Pro_Freemius::is_unlocked();
    }

    /** Callback for the core filter wppoppop_pro_licensed. */
    public static function filter_licensed($licensed) {
        return self::is_unlocked();
    }

    /** Where "Activate licence" buttons lead. */
    public static function activate_url() {
        if (self::is_configured() && WpPopPop_Pro_Freemius::account_url() !== '') {
            return WpPopPop_Pro_Freemius::account_url();
        }
        return admin_url('admin.php?page=' . WpPopPop_Pro_Admin::PAGE);
    }
}
