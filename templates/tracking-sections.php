<?php
if (!defined('ABSPATH')) {
    exit;
}
/* Printed inside Settings > Tracking. $t, $chk and $num are the helpers passed by core. */
?>
    <h3><?php esc_html_e('Statistics & Reports', 'wppoppop-pro'); ?></h3>
    <table class="form-table">
        <?php $num('track_retention_days', __('Daily Stats Retention', 'wppoppop-pro'), 0, 3650, __('days (0 = keep forever)', 'wppoppop-pro')); ?>
        <?php $chk('report_enabled', __('Weekly Email Report', 'wppoppop-pro'), __('Email impressions, leads and CR per campaign every week', 'wppoppop-pro')); ?>
        <tr>
            <th scope="row"><label for="wpp-report_email"><?php esc_html_e('Report Recipient', 'wppoppop-pro'); ?></label></th>
            <td><input type="email" id="wpp-report_email" name="settings[report_email]" value="<?php echo esc_attr($t('report_email')); ?>" class="regular-text" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>"></td>
        </tr>
    </table>

    <h3><?php esc_html_e('A/B Testing', 'wppoppop-pro'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="wpp-ab_default_mode"><?php esc_html_e('Default Embed Type', 'wppoppop-pro'); ?></label></th>
            <td>
                <select id="wpp-ab_default_mode" name="settings[ab_default_mode]">
                    <option value="inline" <?php selected($t('ab_default_mode'), 'inline'); ?>><?php esc_html_e('Inline Popup', 'wppoppop-pro'); ?></option>
                    <option value="onload" <?php selected($t('ab_default_mode'), 'onload'); ?>><?php esc_html_e('On-Load Popup', 'wppoppop-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Used by [wppoppop_ab] when the shortcode has no mode attribute.', 'wppoppop-pro'); ?></p>
            </td>
        </tr>
        <?php $num('ab_cookie_days', __('Variant Cookie Lifetime', 'wppoppop-pro'), 1, 365, __('days', 'wppoppop-pro')); ?>
        <?php $chk('ab_sticky_user', __('Sticky Variant by User', 'wppoppop-pro'), __('Logged-in visitors always see the same variant on every device', 'wppoppop-pro')); ?>
        <?php $chk('ab_auto_promote', __('Auto-Promote Winner', 'wppoppop-pro'), __('Keep only the best variant once the result is statistically significant', 'wppoppop-pro'), __('Uses each popup\'s lifetime impressions and leads. Checked daily.', 'wppoppop-pro')); ?>
        <?php $num('ab_promote_min_impr', __('Minimum Impressions per Variant', 'wppoppop-pro'), 50, 100000); ?>
        <tr>
            <th scope="row"><label for="wpp-ab_promote_confidence"><?php esc_html_e('Confidence Level', 'wppoppop-pro'); ?></label></th>
            <td>
                <select id="wpp-ab_promote_confidence" name="settings[ab_promote_confidence]">
                    <?php foreach ([90, 95, 99] as $c) : ?>
                        <option value="<?php echo (int) $c; ?>" <?php selected((int) $t('ab_promote_confidence'), $c); ?>><?php echo (int) $c; ?>%</option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>

    <h3><?php esc_html_e('Cache Compatibility', 'wppoppop-pro'); ?></h3>
    <table class="form-table">
        <?php $chk('cache_nocache_ab', __('Exclude A/B Pages from Page Cache', 'wppoppop-pro'), __('Send the DONOTCACHEPAGE flag on pages that contain [wppoppop_ab]', 'wppoppop-pro'), __('Honoured by WP Rocket, W3 Total Cache, LiteSpeed and WP Super Cache.', 'wppoppop-pro')); ?>
    </table>

