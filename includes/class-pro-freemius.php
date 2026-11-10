<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Freemius licensing bridge. Pro is a paid-only Freemius product; the SDK handles activation, account, checkout and updates.
 * Until the three WPPOPPOP_PRO_FS_* constants are filled in, nothing here runs and the previous licence code keeps working.
 */
class WpPopPop_Pro_Freemius {

    protected static $fs = null;

    public static function is_configured() {
        return (int) WPPOPPOP_PRO_FS_ID > 0
            && strpos((string) WPPOPPOP_PRO_FS_KEY, 'pk_') === 0
            && file_exists(WPPOPPOP_PRO_PATH . 'includes/freemius/start.php');
    }

    /** Starts the SDK. Runs from the main plugin file, as the SDK requires. */
    public static function init() {
        if (self::$fs !== null || !self::is_configured()) {
            return self::$fs;
        }
        require_once WPPOPPOP_PRO_PATH . 'includes/freemius/start.php';
        self::$fs = fs_dynamic_init(array(
            'id'                  => (string) (int) WPPOPPOP_PRO_FS_ID,
            'slug'                => WPPOPPOP_PRO_FS_SLUG,
            'type'                => 'plugin',
            'public_key'          => WPPOPPOP_PRO_FS_KEY,
            'is_premium'          => true,
            'is_premium_only'     => true,
            'has_premium_version' => true,
            'has_addons'          => false,
            'has_paid_plans'      => true,
            'is_org_compliant'    => false,
            /* Pro's own sub-page under core's menu, so the SDK never takes over core's top-level menu or hides its pages. */
            'menu'                => array(
                'slug'        => 'wppoppop-license',
                'parent'      => array('slug' => 'wppoppop-dashboard'),
                'first-path'  => 'admin.php?page=wppoppop-license',
                'account'     => true,
                'contact'     => false,
                'support'     => false,
                'affiliation' => false,
                'pricing'     => false,
                'addons'      => false,
            ),
        ));
        self::$fs->add_filter('redirect_on_activation', array(__CLASS__, 'redirect_on_activation'));
        self::$fs->add_action('after_uninstall', array('WpPopPop_Pro_Uninstall', 'run'));
        do_action('wppoppop_pro_fs_loaded');
        return self::$fs;
    }

    public static function fs() {
        return self::$fs;
    }

    /** True until a licence is activated; then the SDK's screen replaces the License sub-page. */
    public static function in_activation_mode() {
        return self::$fs && self::$fs->is_activation_mode();
    }

    /* The first screen after activation lives in core's menu, so skip the redirect while core is not running. */
    public static function redirect_on_activation($redirect) {
        return $redirect && defined('WPPOPPOP_API_VERSION');
    }

    /**
     * Same policy as before: an active or an expired licence keeps every Pro feature working
     * (expiry only stops updates and support). A cancelled or refunded licence does not.
     */
    public static function is_unlocked() {
        if (!self::$fs) {
            return false;
        }
        if (self::$fs->can_use_premium_code()) {
            return true;
        }
        $license = self::$fs->_get_license();
        return is_object($license) && method_exists($license, 'is_active') && $license->is_active();
    }

    /** Where "Activate licence" leads: the SDK's licence activation screen, or the Account page once connected. */
    public static function account_url() {
        if (!self::$fs) {
            return '';
        }
        return self::$fs->is_activation_mode() ? self::$fs->get_activation_url() : self::$fs->get_account_url();
    }
}
