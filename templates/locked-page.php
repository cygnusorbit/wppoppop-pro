<?php
if (!defined('ABSPATH')) {
    exit;
}

$bar_title   = $locked_title;
$bar_icon    = $locked_icon;
$bar_actions = '';
?>
<div class="wrap wppoppop-dash wppoppop-license-wrap">
    <h1 class="screen-reader-text"><?php echo esc_html($locked_title); ?></h1>
    <?php
    if (file_exists(WPPOPPOP_PATH . 'templates/partials/dash-bar.php')) {
        include WPPOPPOP_PATH . 'templates/partials/dash-bar.php';
    }
    ?>
    <div class="wpp-lic-card">
        <div class="wpp-lic-head">
            <span class="dashicons dashicons-lock"></span>
            <h3><?php echo esc_html($locked_title); ?></h3>
        </div>
        <p><?php echo esc_html($locked_desc); ?></p>
        <p class="wpp-lic-help"><?php esc_html_e('Your campaigns, statistics and settings are safe. Everything you created stays stored and keeps working on your site.', 'wppoppop-pro'); ?></p>
        <div class="wpp-lic-actions">
            <a class="button button-primary wpp-lic-btn" href="<?php echo esc_url(admin_url('admin.php?page=' . WpPopPop_Pro_Admin::PAGE)); ?>"><span class="dashicons dashicons-admin-network"></span> <?php esc_html_e('Activate licence', 'wppoppop-pro'); ?></a>
        </div>
    </div>
</div>
