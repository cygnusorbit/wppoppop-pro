<?php
/**
 * Plugin Name: WpPopPop Pro
 * Plugin URI: https://github.com/cygnusorbit/wppoppop
 * Description: Pro add-on for WpPopPop: extra builder elements, modules, templates, A/B Campaigns and Statistics.
 * Version: 1.0.003
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: cygnusorbit
 * Author URI: https://github.com/cygnusorbit
 * License: GPL-2.0+
 * Text Domain: wppoppop-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

if (defined('WPPOPPOP_PRO_VERSION')) {
    return;
}

define('WPPOPPOP_PRO_VERSION', '1.0.003');
define('WPPOPPOP_PRO_FILE', __FILE__);
define('WPPOPPOP_PRO_PATH', plugin_dir_path(__FILE__));
define('WPPOPPOP_PRO_URL', plugin_dir_url(__FILE__));
define('WPPOPPOP_PRO_BASENAME', plugin_basename(__FILE__));
/* Lowest WpPopPop core extension API this add-on can run on. */
define('WPPOPPOP_PRO_MIN_API', 1);
/* Lowest WpPopPop release that ships that API (shown on the Plugins screen). */
define('WPPOPPOP_PRO_MIN_CORE', '3.0.914');

/*
 * ================================ FREEMIUS LICENSING ================================
 * Pro is sold as a paid-only Freemius product. Fill in the three values below (or define
 * them in wp-config.php; those win) with the data from your Freemius Developer Dashboard:
 * Products > WpPopPop Pro > SDK Integration.
 *
 *   WPPOPPOP_PRO_FS_ID    Product ID (a number).
 *   WPPOPPOP_PRO_FS_KEY   Public key (starts with pk_). Never put the secret key here.
 *   WPPOPPOP_PRO_FS_SLUG  Product slug, exactly as in Freemius.
 *
 * While the ID is 0 the Freemius SDK stays off and Pro stays locked.
 * ================================================================================
 */
if (!defined('WPPOPPOP_PRO_FS_ID')) {
    define('WPPOPPOP_PRO_FS_ID', 0);
}
if (!defined('WPPOPPOP_PRO_FS_KEY')) {
    define('WPPOPPOP_PRO_FS_KEY', '');
}
if (!defined('WPPOPPOP_PRO_FS_SLUG')) {
    define('WPPOPPOP_PRO_FS_SLUG', 'wppoppop-pro');
}

require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-freemius.php';
require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-multisite.php';
require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-uninstall.php';
/*
 * ================================ FREEMIUS LICENSING ================================
 * Pro is sold as a paid-only Freemius product. Fill in the three values below (or define
 * them in wp-config.php; those win) with the data from your Freemius Developer Dashboard:
 * Products > WpPopPop Pro > SDK Integration.
 *
 *   WPPOPPOP_PRO_FS_ID    Product ID (a number).
 *   WPPOPPOP_PRO_FS_KEY   Public key (starts with pk_). Never put the secret key here.
 *   WPPOPPOP_PRO_FS_SLUG  Product slug, exactly as in Freemius.
 *
 * While the ID is 0 the Freemius SDK stays off and Pro stays locked.
 * ================================================================================
 */
if (!defined('WPPOPPOP_PRO_FS_ID')) {
    define('WPPOPPOP_PRO_FS_ID', 0);
}
if (!defined('WPPOPPOP_PRO_FS_KEY')) {
    define('WPPOPPOP_PRO_FS_KEY', '');
}
if (!defined('WPPOPPOP_PRO_FS_SLUG')) {
    define('WPPOPPOP_PRO_FS_SLUG', 'wppoppop-pro');
}

require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-freemius.php';
require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-license.php';
require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-admin.php';
require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-plugin.php';

WpPopPop_Pro_Freemius::init();
WpPopPop_Pro_Plugin::boot();
