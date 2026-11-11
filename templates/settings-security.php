<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template variables are local to the method that includes this file
if (!defined('ABSPATH')) {
    exit;
}
/* Settings > Security & Geolocation. $s is the stored settings array. */
$email_validation       = isset($s['email_validation']) ? $s['email_validation'] : 'basic';
$enable_recaptcha       = !empty($s['enable_recaptcha']);
$recaptcha_site_key     = isset($s['recaptcha_site_key']) ? $s['recaptcha_site_key'] : '';
$recaptcha_secret_key   = isset($s['recaptcha_secret_key']) ? $s['recaptcha_secret_key'] : '';
$recaptcha_threshold    = isset($s['recaptcha_threshold']) ? (float) $s['recaptcha_threshold'] : 0.4;
$max_submissions_per_ip = isset($s['max_submissions_per_ip']) ? (int) $s['max_submissions_per_ip'] : 10;
$ip_geotargeting        = !empty($s['ip_geotargeting']);
$geoip_service          = isset($s['geoip_service']) ? $s['geoip_service'] : (isset($s['geoip_api_service']) ? $s['geoip_api_service'] : 'none');
?>
<div class="wppoppop-settings-section">
    <h3><?php esc_html_e('Bot Protection & Geo-Targeting Rules', 'wppoppop-pro'); ?></h3>
    <?php if (!WpPopPop_Pro_Protection::unlocked()) : ?>
        <p class="description"><?php esc_html_e('These rules need an active WpPopPop Pro licence. Your saved choices are kept.', 'wppoppop-pro'); ?> <a href="<?php echo esc_url(WpPopPop_Pro_License::activate_url()); ?>"><?php esc_html_e('Activate licence', 'wppoppop-pro'); ?></a></p>
    <?php endif; ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="setting-email-val"><?php esc_html_e('Email Validation Mode', 'wppoppop-pro'); ?></label></th>
            <td>
                <select id="setting-email-val" name="settings[email_validation]">
                    <option value="basic" <?php selected($email_validation, 'basic'); ?>><?php esc_html_e('Standard RFC Syntax Check', 'wppoppop-pro'); ?></option>
                    <option value="disposable_filter" <?php selected($email_validation, 'disposable_filter'); ?>><?php esc_html_e('Syntax Check + Block Disposable Domains (10MinuteMail, Mailinator)', 'wppoppop-pro'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Rejects throwaway and temporary email addresses from polluting your CRM.', 'wppoppop-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Google reCAPTCHA', 'wppoppop-pro'); ?></th>
            <td>
                <input type="hidden" name="settings[enable_recaptcha]" value="0">
                <label>
                    <input type="checkbox" name="settings[enable_recaptcha]" value="1" <?php checked($enable_recaptcha); ?>>
                    <?php esc_html_e('Enable Google reCAPTCHA verification on all lead submission forms', 'wppoppop-pro'); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="setting-recaptcha-site"><?php esc_html_e('reCAPTCHA Site Key', 'wppoppop-pro'); ?></label></th>
            <td>
                <input type="text" id="setting-recaptcha-site" name="settings[recaptcha_site_key]" value="<?php echo esc_attr($recaptcha_site_key); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="setting-recaptcha-secret"><?php esc_html_e('reCAPTCHA Secret Key', 'wppoppop-pro'); ?></label></th>
            <td>
                <input type="text" id="setting-recaptcha-secret" name="settings[recaptcha_secret_key]" value="<?php echo esc_attr($recaptcha_secret_key); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="setting-recaptcha-threshold"><?php esc_html_e('reCAPTCHA v3 Minimum Score', 'wppoppop-pro'); ?></label></th>
            <td>
                <input type="number" id="setting-recaptcha-threshold" name="settings[recaptcha_threshold]" value="<?php echo esc_attr($recaptcha_threshold); ?>" min="0.1" max="0.9" step="0.1" class="small-text">
                <p class="description"><?php esc_html_e('Submissions scoring below this are rejected (0.1 = lenient, 0.9 = strict). Default 0.4. Requires a reCAPTCHA v3 key pair.', 'wppoppop-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="setting-ip-limit"><?php esc_html_e('IP Rate Limiting', 'wppoppop-pro'); ?></label></th>
            <td>
                <input type="number" id="setting-ip-limit" name="settings[max_submissions_per_ip]" value="<?php echo esc_attr($max_submissions_per_ip); ?>" min="1" max="100" class="small-text">
                <?php esc_html_e('submissions allowed per IP per hour', 'wppoppop-pro'); ?>
                <p class="description"><?php esc_html_e('Throttles aggressive bot submissions and prevents form spam.', 'wppoppop-pro'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('IP Geo-Targeting', 'wppoppop-pro'); ?></th>
            <td>
                <input type="hidden" name="settings[ip_geotargeting]" value="0">
                <label>
                    <input type="checkbox" name="settings[ip_geotargeting]" value="1" <?php checked($ip_geotargeting); ?>>
                    <?php esc_html_e('Enable IP-based country and regional targeting rules', 'wppoppop-pro'); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="setting-geoip-service"><?php esc_html_e('GeoIP Resolution Service', 'wppoppop-pro'); ?></label></th>
            <td>
                <select id="setting-geoip-service" name="settings[geoip_service]">
                    <option value="none" <?php selected($geoip_service, 'none'); ?>><?php esc_html_e('None (Cloudflare CF-IPCountry header only, no external lookups)', 'wppoppop-pro'); ?></option>
                    <option value="ipapi" <?php selected($geoip_service, 'ipapi'); ?>><?php esc_html_e('ipapi.co (JSON API)', 'wppoppop-pro'); ?></option>
                    <option value="ip-api" <?php selected($geoip_service, 'ip-api'); ?>>ip-api.com</option>
                </select>
                <p class="description"><?php esc_html_e('Provider used to detect visitor countries for geo-whitelisting and blacklisting.', 'wppoppop-pro'); ?></p>
            </td>
        </tr>
    </table>
</div>
