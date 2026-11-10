<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom Scoped CSS & JS module: sanitize (save) + front output (render).
 * Shape: settings.custom_code = { css: string, js: string }
 * CSS is limited to this popup (every selector gets the popup id in front) and added to the popup's own style block.
 * JS runs once after the page loads, inside a function with popup (the popup element), uid and $ (jQuery).
 * Only users who may post unfiltered HTML can save JS, the same rule as the global Custom Code tab.
 */
class WpPopPop_Custom_Code {

    const MODULE_KEY = 'acc-custom-code';
    const MAX_CSS    = 20000;
    const MAX_JS     = 50000;

    public static function defaults() {
        return array('css' => '', 'js' => '');
    }

    protected static function cut($s, $max) {
        $s = str_replace("\0", '', (string) $s);
        return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
    }

    /** CSS cannot carry markup, scripts, imports or legacy script hooks. */
    protected static function clean_css($css) {
        $css = self::cut($css, self::MAX_CSS);
        $css = str_replace('<', '', $css);
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        $css = preg_replace('/@import\b[^;{}]*;?/i', '', $css);
        $css = preg_replace('/(?:javascript\s*:|vbscript\s*:|expression\s*\(|-moz-binding|behavior\s*:)/i', '', $css);
        return trim((string) $css);
    }

    /**
     * Clean, allow-listed config from a saved or posted value (object or array).
     * $prev = the stored value, kept for the JS when the user may not save JS ($allow_js false).
     */
    public static function sanitize($raw, $prev = null, $allow_js = true) {
        $raw  = is_object($raw) ? get_object_vars($raw) : $raw;
        $prev = is_object($prev) ? get_object_vars($prev) : $prev;
        $raw  = is_array($raw) ? $raw : array();
        $prev = is_array($prev) ? $prev : array();
        $css  = (isset($raw['css']) && is_string($raw['css'])) ? self::clean_css($raw['css']) : '';
        $src  = $allow_js ? $raw : $prev;
        $js   = (isset($src['js']) && is_string($src['js'])) ? trim(self::cut($src['js'], self::MAX_JS)) : '';
        return array('css' => $css, 'js' => $js);
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

    /* ------------------------------------------------------------------ CSS scoping */

    /** Prefix every selector with $scope. Handles @media / @supports / @container / @layer; keyframes and font-face stay as they are. */
    public static function scope_css($css, $scope) {
        return self::scope_block((string) $css, $scope, 0);
    }

    protected static function scope_block($css, $scope, $depth) {
        $out = '';
        $i   = 0;
        $n   = strlen($css);
        while ($i < $n) {
            $open = strpos($css, '{', $i);
            if ($open === false) {
                break;
            }
            $prelude = trim(substr($css, $i, $open - $i));
            $d = 1;
            $j = $open + 1;
            while ($j < $n && $d > 0) {
                $ch = $css[$j];
                if ($ch === '{') {
                    $d++;
                } elseif ($ch === '}') {
                    $d--;
                }
                $j++;
            }
            $end  = ($d === 0) ? $j - 1 : $n;
            $body = substr($css, $open + 1, $end - ($open + 1));
            $i    = $j;
            if ($prelude === '') {
                continue;
            }
            if ($prelude[0] === '@') {
                if ($depth < 5 && preg_match('/^@(?:media|supports|container|layer)\b/i', $prelude)) {
                    $out .= $prelude . '{' . self::scope_block($body, $scope, $depth + 1) . "}\n";
                } elseif (preg_match('/^@(?:font-face|(?:-webkit-|-moz-)?keyframes|counter-style|property)\b/i', $prelude)) {
                    $out .= $prelude . '{' . $body . "}\n";
                }
                continue;
            }
            $sel = self::scope_selectors($prelude, $scope);
            if ($sel !== '') {
                $out .= $sel . '{' . $body . "}\n";
            }
        }
        return $out;
    }

    protected static function scope_selectors($list, $scope) {
        $parts = array();
        $buf   = '';
        $depth = 0;
        $quote = '';
        $len   = strlen($list);
        for ($k = 0; $k < $len; $k++) {
            $c = $list[$k];
            if ($quote !== '') {
                $buf .= $c;
                if ($c === $quote) {
                    $quote = '';
                }
                continue;
            }
            if ($c === '"' || $c === "'") {
                $quote = $c;
            } elseif ($c === '(' || $c === '[') {
                $depth++;
            } elseif ($c === ')' || $c === ']') {
                $depth = max(0, $depth - 1);
            } elseif ($c === ',' && $depth === 0) {
                $parts[] = $buf;
                $buf = '';
                continue;
            }
            $buf .= $c;
        }
        $parts[] = $buf;
        $out = array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            /* The popup itself: its wrapper classes, html, body and :root all mean the popup element */
            if (preg_match('/^(?:\.wppoppop-popup-wrap|\.wppoppop-overlay|html|body|:root)(?![A-Za-z0-9_-])/i', $p)) {
                $out[] = preg_replace('/^(?:\.wppoppop-popup-wrap|\.wppoppop-overlay|html|body|:root)(?![A-Za-z0-9_-])/i', $scope, $p, 1);
            } else {
                $out[] = $scope . ' ' . $p;
            }
        }
        return implode(', ', $out);
    }

    /* ------------------------------------------------------------------ JS output */

    protected static $js = array();

    /** One script tag per popup: a syntax error in one popup's code never stops another popup's code. */
    public static function js_tag($id, $js) {
        $js = str_ireplace(array('</script', '<!--'), array('<\\/script', '<\\!--'), (string) $js);
        return "\n<script id=\"wppoppop-custom-js-" . $id . "\">\n(function(){var uid=" . json_encode((string) $id) . ";function run(){var popup=document.getElementById('wppoppop-popup-'+uid);if(!popup){return;}(function(popup,uid,\$){\n"
            . $js
            . "\n}).call(popup,popup,uid,window.jQuery);}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',run);}else{run();}})();\n</script>\n";
    }

    protected static function queue_js($id, $js) {
        static $hooked = false;
        self::$js[$id] = $js;
        if ($hooked) {
            return;
        }
        $hooked = true;
        add_action('wp_footer', function () {
            foreach (WpPopPop_Custom_Code::pending() as $id => $js) {
                echo WpPopPop_Custom_Code::js_tag($id, $js); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored code, saved only with unfiltered_html
            }
        }, 100);
    }

    public static function pending() {
        $all = self::$js;
        self::$js = array();
        return $all;
    }

    /** Scoped CSS for the popup's style block (''), and queues the JS. */
    public static function frontend($settings, $uid) {
        if (!is_array($settings) || !isset($settings['custom_code']) || !is_array($settings['custom_code'])) {
            return '';
        }
        $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $uid);
        if ($id === '' || !self::module_active()) {
            return '';
        }
        $c = self::sanitize($settings['custom_code']);
        if ($c['js'] !== '') {
            self::queue_js($id, $c['js']);
        }
        if ($c['css'] === '') {
            return '';
        }
        return "\n" . self::scope_css($c['css'], '#wppoppop-popup-' . $id) . "\n";
    }
}
