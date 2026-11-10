<?php
if (!defined('ABSPATH')) {
    exit;
}

$bar_title   = __('License', 'wppoppop-pro');
$bar_icon    = 'dashicons-admin-network';
$bar_actions = '';
?>
<div class="wrap wppoppop-dash wppoppop-license-wrap">
    <h1 class="screen-reader-text"><?php esc_html_e('License', 'wppoppop-pro'); ?></h1>
    <?php
    if (file_exists(WPPOPPOP_PATH . 'templates/partials/dash-bar.php')) {
        include WPPOPPOP_PATH . 'templates/partials/dash-bar.php';
    }
    ?>
    <div class="wpp-lic-card">
        <div class="wpp-lic-head">
            <span class="dashicons dashicons-admin-network"></span>
            <h3><?php esc_html_e('Licensing is not set up yet', 'wppoppop-pro'); ?></h3>
        </div>
        <p><?php esc_html_e('WpPopPop Pro uses Freemius for licence keys, updates and your account. Add the product details from your Freemius Developer Dashboard (Products > WpPopPop Pro > SDK Integration) to wppoppop-pro.php, or define them in wp-config.php:', 'wppoppop-pro'); ?></p>
        <ul class="wpp-lic-list">
            <li><code>WPPOPPOP_PRO_FS_ID</code> &mdash; <?php esc_html_e('Product ID', 'wppoppop-pro'); ?></li>
            <li><code>WPPOPPOP_PRO_FS_KEY</code> &mdash; <?php esc_html_e('Public key (starts with pk_)', 'wppoppop-pro'); ?></li>
            <li><code>WPPOPPOP_PRO_FS_SLUG</code> &mdash; <?php esc_html_e('Product slug', 'wppoppop-pro'); ?></li>
        </ul>
        <p class="wpp-lic-help"><?php esc_html_e('Your campaigns, statistics and settings are safe. Everything you created stays stored and keeps working on your site.', 'wppoppop-pro'); ?></p>
    </div>
</div>
