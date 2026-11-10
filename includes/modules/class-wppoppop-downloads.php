<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Downloads module: sanitize (save) + read (submission) + one-time tokens + keep the file URL off the public page.
 * Shape: settings.downloads = { enable: bool, url: string }
 * A confirmed submission returns a single-use link (?wppoppop_download=TOKEN, valid 24 h); the file URL itself is never sent to the browser.
 */
class WpPopPop_Downloads {

    const MODULE_KEY = 'acc-downloads';
    const MAX_URL    = 2000;
    const TOKEN_TTL  = 86400;

    public static function defaults() {
        return array('enable' => false, 'url' => '');
    }

    private static function truthy($v) {
        return in_array($v, array(true, 1, '1', 'true', 'on'), true);
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). Only http(s) URLs are kept; "/path" means this site. */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        $url = '';
        if (isset($raw['url']) && is_scalar($raw['url'])) {
            $u = trim((string) $raw['url']);
            if ($u !== '' && $u[0] === '/' && substr($u, 0, 2) !== '//') {
                $u = home_url($u);
            }
            $u = esc_url_raw($u, array('http', 'https'));
            if (preg_match('#^https?://#i', $u) && strlen($u) <= self::MAX_URL) {
                $url = $u;
            }
        }
        return array(
            'enable' => !empty($raw['enable']) && self::truthy($raw['enable']),
            'url'    => $url,
        );
    }

    /** Config of a decoded popup: settings.downloads first, then a top-level downloads key. */
    public static function get_config($config) {
        $config = is_array($config) ? $config : array();
        $src    = array();
        if (isset($config['settings']['downloads']) && is_array($config['settings']['downloads'])) {
            $src = $config['settings']['downloads'];
        } elseif (isset($config['downloads']) && is_array($config['downloads'])) {
            $src = $config['downloads'];
        }
        return self::sanitize($src);
    }

    /** Settings > Module switch. A module that is switched off or deleted never affects the front end. */
    public static function module_active() {
        if (!class_exists('WpPopPop_Modules')) {
            return true;
        }
        /* Not registered: the bundled package is seeded on the first admin load, so stay on until then. A deleted module is off. */
        if (!WpPopPop_Modules::get(self::MODULE_KEY)) {
            $orphans = WpPopPop_Modules::orphans();
            return !isset($orphans[self::MODULE_KEY]);
        }
        if (!class_exists('WpPopPop_Admin_Settings')) {
            require_once WPPOPPOP_PATH . 'includes/admin/class-admin-settings.php';
        }
        return WpPopPop_Admin_Settings::module_enabled(self::MODULE_KEY);
    }

    /** The protected file URL is private: remove it from a popup config before it is printed on a page or returned by the public REST route. */
    public static function redact($config) {
        if (is_array($config) && isset($config['settings']) && is_array($config['settings'])) {
            unset($config['settings']['downloads']);
        }
        if (is_array($config)) {
            unset($config['downloads']);
        }
        return $config;
    }

    /** One-time token (lowercase, because the download handler reads it back through sanitize_key). */
    public static function generate_token($file_url, $popup_uid = '') {
        $token = strtolower(wp_generate_password(32, false));
        set_transient('wppoppop_dl_' . $token, array(
            'file_url'  => esc_url_raw($file_url),
            'popup_uid' => sanitize_key($popup_uid),
            'created'   => time(),
        ), self::TOKEN_TTL);
        return $token;
    }

    /** Download link for a new subscriber ('' when nothing is issued). */
    public static function issue($uid, $cfg) {
        if (!is_array($cfg) || empty($cfg['enable']) || empty($cfg['url']) || !self::module_active()) {
            return '';
        }
        return add_query_arg('wppoppop_download', self::generate_token($cfg['url'], $uid), home_url('/'));
    }

    /** Absolute path of a file that lives inside the uploads folder, or '' (outside it, missing, or a script type). */
    public static function resolve_local($file_url, $basedir, $baseurl) {
        $strip = function ($u) { return preg_replace('#^https?:#i', '', (string) $u); };
        $b     = rtrim($strip($baseurl), '/');
        $f     = $strip($file_url);
        if ($b === '' || strpos($f, $b . '/') !== 0) {
            return '';
        }
        $rel = rawurldecode((string) strtok(substr($f, strlen($b) + 1), '?#'));
        if ($rel === '' || strpos($rel, "\0") !== false) {
            return '';
        }
        $root = realpath($basedir);
        $path = realpath(rtrim($basedir, '/\\') . '/' . $rel);
        if (!$root || !$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
            return '';
        }
        if (preg_match('/\.(php\d?|phtml|phar|htaccess|htpasswd|ini)$/i', $path)) {
            return '';
        }
        return $path;
    }
}
