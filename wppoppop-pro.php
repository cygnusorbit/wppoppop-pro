<?php
/**
 * Plugin Name: WpPopPop Pro
 * Plugin URI: https://github.com/cygnusorbit/wppoppop
 * Description: Pro add-on for WpPopPop: extra builder elements, modules, templates, A/B Campaigns and Statistics.
 * Version: 1.0.0
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

define('WPPOPPOP_PRO_VERSION', '1.0.0');
define('WPPOPPOP_PRO_FILE', __FILE__);
define('WPPOPPOP_PRO_PATH', plugin_dir_path(__FILE__));
define('WPPOPPOP_PRO_URL', plugin_dir_url(__FILE__));
define('WPPOPPOP_PRO_BASENAME', plugin_basename(__FILE__));
/* Lowest WpPopPop core extension API this add-on can run on. */
define('WPPOPPOP_PRO_MIN_API', 1);
/* Lowest WpPopPop release that ships that API (shown on the Plugins screen). */
define('WPPOPPOP_PRO_MIN_CORE', '3.0.914');

/*
 * ============================ EDD SOFTWARE LICENSING ============================
 * TEMPORARY GENERIC VALUES - replace the three values below with your store's data
 * before you release Pro. Nothing else in the plugin needs to change.
 *
 *   1. WPPOPPOP_PRO_STORE_URL  Home URL of the site that runs EDD Software Licensing,
 *                              with https:// and no trailing slash.
 *   2. WPPOPPOP_PRO_ITEM_NAME  The exact download title in EDD (used while ITEM_ID is 0).
 *   3. WPPOPPOP_PRO_ITEM_ID    The download ID from EDD (Downloads > Edit, the number in
 *                              the address bar "post=123"). Set it to 0 to match by name.
 *
 * The values can also be defined in wp-config.php; those win over the ones below.
 * While the store URL is still "example.com" the License page shows a setup notice and
 * no request is sent to a store.
 * ================================================================================
 */
if (!defined('WPPOPPOP_PRO_STORE_URL')) {
    define('WPPOPPOP_PRO_STORE_URL', 'https://wppoppop.com/products/');
}
if (!defined('WPPOPPOP_PRO_ITEM_NAME')) {
    define('WPPOPPOP_PRO_ITEM_NAME', 'WpPopPop Pro');
}
if (!defined('WPPOPPOP_PRO_ITEM_ID')) {
    define('WPPOPPOP_PRO_ITEM_ID', 111);
}

require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-license.php';
require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-updater.php';
require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-admin.php';
require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-plugin.php';

register_activation_hook(__FILE__, array('WpPopPop_Pro_License', 'schedule'));
register_deactivation_hook(__FILE__, array('WpPopPop_Pro_License', 'unschedule'));

WpPopPop_Pro_Plugin::boot();
