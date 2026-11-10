<?php
if (!defined('ABSPATH')) {
    exit;
}

class WpPopPop_GeoIP {
    const TRANSIENT_PREFIX = 'wppoppop_geo_';
    const CACHE_TTL        = 86400; // 24 Hours Cache

    /**
     * Resolve visitor ISO-2 country code
     *
     * @param string|null $ip
     * @return string Uppercase 2-letter ISO code or 'XX' on unknown/local
     */
    public static function get_country_code($ip = null) {
        if (!$ip) {
            $ip = wppoppop_get_client_ip();
        }

        // Return local fallback for loopback / private IP ranges
        if (self::is_private_ip($ip)) {
            return apply_filters('wppoppop_geoip_local_country', 'US');
        }

        $service = wppoppop_get_setting('geoip_service', wppoppop_get_setting('geoip_api_service', 'none'));
        if (!in_array($service, ['ipapi', 'ip-api', 'cloudflare'], true)) {
            $service = 'none';
        }

        // 1. Cloudflare CF-IPCountry Header Check (Immediate, 0ms latency)
        if ($service === 'cloudflare' || !empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
                $cf_country = strtoupper(sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY'])));
                if (strlen($cf_country) === 2 && $cf_country !== 'XX' && $cf_country !== 'T1') {
                    return $cf_country;
                }
            }
        }

        // 1b. 'none' = Cloudflare header only. Never contact an external provider.
        if ($service === 'none') {
            return 'XX';
        }

        // 2. Check WordPress Transient Cache
        $cache_key = self::TRANSIENT_PREFIX . md5($ip);
        $cached_country = get_transient($cache_key);
        if ($cached_country && is_string($cached_country) && strlen($cached_country) === 2) {
            return strtoupper($cached_country);
        }

        // 3. Remote Provider Query with Failover
        $country = self::query_remote_provider($ip, $service);

        // Failover to secondary provider if primary failed
        if (empty($country) || strlen($country) !== 2 || $country === 'XX') {
            $fallback_service = ($service === 'ipapi') ? 'ip-api' : 'ipapi';
            $country = self::query_remote_provider($ip, $fallback_service);
        }

        if (!empty($country) && strlen($country) === 2) {
            $country = strtoupper($country);
            set_transient($cache_key, $country, self::CACHE_TTL);
            return $country;
        }

        return 'XX';
    }

    /**
     * Query remote geolocation REST APIs
     *
     * @param string $ip
     * @param string $service
     * @return string
     */
    private static function query_remote_provider($ip, $service) {
        $country = '';

        if ($service === 'ipapi' || $service === 'cloudflare') {
            // ipapi.co JSON endpoint
            $response = wp_remote_get("https://ipapi.co/{$ip}/country/", [
                'timeout'    => 3,
                'user-agent' => 'WordPress/WpPopPop-' . WPPOPPOP_VERSION . '; ' . home_url()
            ]);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = trim(wp_remote_retrieve_body($response));
                if (strlen($body) === 2 && ctype_alpha($body) && strtoupper($body) !== 'UNDEFINED') {
                    $country = strtoupper($body);
                }
            }
        } elseif ($service === 'ip-api') {
            // ip-api.com JSON endpoint
            $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=countryCode", [
                'timeout'    => 3,
                'user-agent' => 'WordPress/WpPopPop-' . WPPOPPOP_VERSION . '; ' . home_url()
            ]);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($data['countryCode']) && strlen($data['countryCode']) === 2) {
                    $country = strtoupper($data['countryCode']);
                }
            }
        }

        return $country;
    }

    /**
     * Check if IP is in private/loopback range
     *
     * @param string $ip
     * @return bool
     */
    public static function is_private_ip($ip) {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Evaluate targeting rules against visitor country
     *
     * @param string $visitor_country
     * @param array $settings
     * @return bool
     */
    public static function is_country_allowed($visitor_country, array $settings) {
        // If geo-targeting is disabled globally, allow all
        if (!wppoppop_get_setting('ip_geotargeting')) {
            return true;
        }

        $geo_enabled = !empty($settings['geo_enabled']) || !empty($settings['target_geo']);
        if (!$geo_enabled) {
            return true;
        }

        $geo_mode = !empty($settings['geo_mode']) ? $settings['geo_mode'] : (!empty($settings['geo_rule']) ? $settings['geo_rule'] : 'allow');
        $raw_countries = !empty($settings['geo_countries']) ? $settings['geo_countries'] : (!empty($settings['target_countries']) ? $settings['target_countries'] : []);

        if (is_string($raw_countries)) {
            $target_countries = array_filter(array_map('trim', explode(',', strtoupper($raw_countries))));
        } elseif (is_array($raw_countries)) {
            $target_countries = array_filter(array_map('strtoupper', array_map('trim', $raw_countries)));
        } else {
            $target_countries = [];
        }

        if (empty($target_countries)) {
            return true;
        }

        $in_list = in_array(strtoupper($visitor_country), $target_countries, true);

        if ($geo_mode === 'deny' || $geo_mode === 'exclude') {
            return !$in_list;
        }

        // Default: 'allow' / 'include'
        return $in_list;
    }
}
