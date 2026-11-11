<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template variables are local to the method that includes this file
if (!defined('ABSPATH')) {
    exit;
}
/* Printed inside Settings > Speed, Asset Delivery & Preload Engine. $s is the stored settings array. */
$ga_tracking      = !empty($s['ga_tracking']);
$adblock_detector = !empty($s['adblock_detector']);
?>
        <tr>
            <th scope="row"><?php esc_html_e('Google Analytics Integration', 'wppoppop-pro'); ?></th>
            <td>
                <input type="hidden" name="settings[ga_tracking]" value="0">
                <label>
                    <input type="checkbox" name="settings[ga_tracking]" value="1" <?php checked($ga_tracking); ?>>
                    <?php esc_html_e('Automatically dispatch popup impressions, conversions, and close events to gtag() and dataLayer', 'wppoppop-pro'); ?>
                </label>
                <?php WpPopPop_Pro_Extensions::locked_notice(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('AdBlock Detection Engine', 'wppoppop-pro'); ?></th>
            <td>
                <input type="hidden" name="settings[adblock_detector]" value="0">
                <label>
                    <input type="checkbox" name="settings[adblock_detector]" value="1" <?php checked($adblock_detector); ?>>
                    <?php esc_html_e('Enable global AdBlock extension monitoring script to trigger anti-adblock campaigns', 'wppoppop-pro'); ?>
                </label>
            </td>
        </tr>
