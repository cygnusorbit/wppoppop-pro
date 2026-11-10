<?php
if (!defined('ABSPATH')) {
    exit;
}

$lic_data       = WpPopPop_Pro_License::data();
$lic_status     = $lic_data['status'];
$lic_key        = WpPopPop_Pro_License::key();
$lic_from_const = defined('WPPOPPOP_PRO_LICENSE_KEY') && WPPOPPOP_PRO_LICENSE_KEY !== '';
$lic_active     = in_array($lic_status, array('valid', 'expired'), true);
$lic_conf       = WpPopPop_Pro_License::is_configured();
$lic_expires    = $lic_data['expires'];
if ($lic_expires !== '' && $lic_expires !== 'lifetime' && strtotime($lic_expires)) {
    $lic_expires = wp_date(get_option('date_format'), strtotime($lic_expires));
} elseif ($lic_expires === 'lifetime') {
    $lic_expires = __('Lifetime', 'wppoppop-pro');
}
$lic_checked = !empty($lic_data['checked']) ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $lic_data['checked']) : '';
$lic_class   = $lic_status === 'valid' ? 'is-ok' : ($lic_status === 'expired' ? 'is-warn' : 'is-off');

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

    <?php if (is_array($notice) && !empty($notice['message'])) : ?>
        <div class="notice notice-<?php echo !empty($notice['ok']) ? 'success' : 'error'; ?> is-dismissible"><p><?php echo esc_html($notice['message']); ?></p></div>
    <?php endif; ?>

    <?php if (!$lic_conf) : ?>
        <div class="notice notice-warning"><p><?php esc_html_e('The licence store URL is not set yet. Edit WPPOPPOP_PRO_STORE_URL in wppoppop-pro.php (or define it in wp-config.php).', 'wppoppop-pro'); ?></p></div>
    <?php endif; ?>

    <div class="wpp-lic-layout">
        <div class="wpp-lic-main">
            <div class="wpp-lic-card">
                <div class="wpp-lic-head">
                    <span class="dashicons dashicons-admin-network"></span>
                    <h3><?php esc_html_e('Licence key', 'wppoppop-pro'); ?></h3>
                </div>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="<?php echo esc_attr(WpPopPop_Pro_Admin::ACTION); ?>">
                    <?php wp_nonce_field(WpPopPop_Pro_Admin::ACTION, 'wppoppop_pro_nonce'); ?>

                    <label class="wpp-lic-label" for="wpp-license-key"><?php esc_html_e('Enter the key from your purchase receipt', 'wppoppop-pro'); ?></label>
                    <?php if ($lic_active) : ?>
                        <input type="text" id="wpp-license-key" class="regular-text wpp-lic-input" value="<?php echo esc_attr(WpPopPop_Pro_License::masked_key()); ?>" readonly>
                    <?php else : ?>
                        <input type="text" id="wpp-license-key" name="wpp_license_key" class="regular-text wpp-lic-input" value="<?php echo esc_attr($lic_from_const ? '' : $lic_key); ?>" autocomplete="off" spellcheck="false" placeholder="<?php echo esc_attr__('XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX', 'wppoppop-pro'); ?>"<?php echo $lic_from_const ? ' disabled' : ''; ?>>
                    <?php endif; ?>
                    <?php if ($lic_from_const) : ?>
                        <p class="wpp-lic-help"><?php esc_html_e('The key is set by WPPOPPOP_PRO_LICENSE_KEY in wp-config.php.', 'wppoppop-pro'); ?></p>
                    <?php endif; ?>

                    <div class="wpp-lic-actions">
                        <?php if ($lic_active) : ?>
                            <button type="submit" name="wpp_license_op" value="check" class="button wpp-lic-btn"><span class="dashicons dashicons-update"></span><?php esc_html_e('Refresh status', 'wppoppop-pro'); ?></button>
                            <button type="submit" name="wpp_license_op" value="deactivate" class="button wpp-lic-btn"><span class="dashicons dashicons-dismiss"></span><?php esc_html_e('Deactivate on this site', 'wppoppop-pro'); ?></button>
                        <?php else : ?>
                            <button type="submit" name="wpp_license_op" value="activate" class="button button-primary wpp-lic-btn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Activate licence', 'wppoppop-pro'); ?></button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="wpp-lic-card">
                <div class="wpp-lic-head">
                    <span class="dashicons dashicons-info-outline"></span>
                    <h3><?php esc_html_e('What the licence controls', 'wppoppop-pro'); ?></h3>
                </div>
                <ul class="wpp-lic-list">
                    <li><?php esc_html_e('Pro templates, modules and elements are available while the licence is active.', 'wppoppop-pro'); ?></li>
                    <li><?php esc_html_e('Automatic updates and support need an unexpired licence. An expired licence keeps everything working.', 'wppoppop-pro'); ?></li>
                    <li><?php esc_html_e('Deactivate the licence here before moving the site to another domain.', 'wppoppop-pro'); ?></li>
                </ul>
            </div>
        </div>

        <aside class="wpp-lic-side">
            <div class="wpp-lic-card">
                <div class="wpp-lic-head">
                    <span class="dashicons dashicons-shield"></span>
                    <h3><?php esc_html_e('Status', 'wppoppop-pro'); ?></h3>
                </div>
                <p><span class="wpp-lic-pill <?php echo esc_attr($lic_class); ?>"><?php echo esc_html(WpPopPop_Pro_License::status_label()); ?></span></p>
                <?php if ($lic_data['message'] !== '' && !$lic_active) : ?>
                    <p class="wpp-lic-help"><?php echo esc_html($lic_data['message']); ?></p>
                <?php endif; ?>
                <dl class="wpp-lic-meta">
                    <dt><?php esc_html_e('Key', 'wppoppop-pro'); ?></dt>
                    <dd><?php echo $lic_key !== '' ? esc_html(WpPopPop_Pro_License::masked_key()) : '&mdash;'; ?></dd>
                    <dt><?php esc_html_e('Expires', 'wppoppop-pro'); ?></dt>
                    <dd><?php echo $lic_expires !== '' ? esc_html($lic_expires) : '&mdash;'; ?></dd>
                    <dt><?php esc_html_e('Last check', 'wppoppop-pro'); ?></dt>
                    <dd><?php echo $lic_checked !== '' ? esc_html($lic_checked) : '&mdash;'; ?></dd>
                    <dt><?php esc_html_e('Pro version', 'wppoppop-pro'); ?></dt>
                    <dd><?php echo esc_html(WPPOPPOP_PRO_VERSION); ?></dd>
                </dl>
            </div>
        </aside>
    </div>
</div>
