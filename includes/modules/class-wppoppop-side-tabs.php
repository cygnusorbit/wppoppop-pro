<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Side Tabs module: sanitize (save) + front attributes (render).
 * Shape: settings.sidetab = { enable, label, pos: 'left'|'right', icon, bg, radius, anim }
 * The tab itself is built in the browser by the package's module.js from the data-sidetab-* attributes.
 */
class WpPopPop_Side_Tabs {

    const MODULE_KEY = 'acc-side-tabs';
    const MAX_LABEL  = 40;
    const MAX_RADIUS = 50;

    public static function anims() {
        return array('none', 'slide', 'bounce', 'fade');
    }

    public static function defaults() {
        return array('enable' => false, 'label' => '', 'pos' => 'left', 'icon' => '', 'bg' => '#c2185b', 'radius' => 6, 'anim' => 'slide');
    }

    /** Clean, allow-listed config from a saved or posted value (object or array). Missing keys fall back to the defaults. */
    public static function sanitize($raw) {
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            $raw = array();
        }
        $d     = self::defaults();
        $label = (isset($raw['label']) && is_scalar($raw['label'])) ? sanitize_text_field((string) $raw['label']) : '';
        $label = function_exists('mb_substr') ? mb_substr($label, 0, self::MAX_LABEL) : substr($label, 0, self::MAX_LABEL);
        $pos   = (isset($raw['pos']) && is_scalar($raw['pos'])) ? (string) $raw['pos'] : 'left';

        $icon = '';
        if (isset($raw['icon']) && is_scalar($raw['icon'])) {
            $icon = trim(preg_replace('/[[:space:]]+/', ' ', (string) $raw['icon']));
            if (strlen($icon) > 60 || !preg_match('/^(fas|far|fab|fa-solid|fa-regular|fa-brands)( fa-[a-z0-9-]+){1,3}$/', $icon)) {
                $icon = '';
            }
        }

        $bg = (isset($raw['bg']) && is_scalar($raw['bg'])) ? sanitize_hex_color((string) $raw['bg']) : '';
        if (!$bg) {
            $bg = $d['bg'];
        } elseif (strlen($bg) === 4) {
            $bg = '#' . $bg[1] . $bg[1] . $bg[2] . $bg[2] . $bg[3] . $bg[3];
        }

        $radius = (isset($raw['radius']) && is_numeric($raw['radius'])) ? (int) round((float) $raw['radius']) : $d['radius'];
        $radius = max(0, min(self::MAX_RADIUS, $radius));

        $anim = (isset($raw['anim']) && is_scalar($raw['anim'])) ? (string) $raw['anim'] : $d['anim'];
        if (!in_array($anim, self::anims(), true)) {
            $anim = $d['anim'];
        }

        return array(
            'enable' => isset($raw['enable']) && in_array($raw['enable'], array(true, 1, '1', 'true', 'on'), true),
            'label'  => $label,
            'pos'    => $pos === 'right' ? 'right' : 'left',
            'icon'   => $icon,
            'bg'     => strtolower($bg),
            'radius' => $radius,
            'anim'   => $anim,
        );
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

    /**
     * The popup is rendered at wp_footer priority 50, after WordPress printed the footer scripts (priority 20),
     * so the module.js queued by the engine would never reach the page. Print it once, after the popup markup.
     */
    protected static function print_late_script() {
        static $hooked = false;
        if ($hooked) {
            return;
        }
        $hooked = true;
        add_action('wp_footer', function () {
            $h = 'wppoppop-ext-' . WpPopPop_Side_Tabs::MODULE_KEY . '-front';
            if (wp_script_is($h, 'enqueued') && !wp_script_is($h, 'done')) {
                wp_print_scripts($h);
            }
        }, 100);
    }

    /** Escaped data attributes for the popup wrapper, or '' (module off / tab not enabled). */
    public static function frontend($settings) {
        if (!is_array($settings) || !isset($settings['sidetab']) || !is_array($settings['sidetab'])) {
            return '';
        }
        $t = self::sanitize($settings['sidetab']);
        if (!$t['enable'] || !self::module_active()) {
            return '';
        }
        $label = $t['label'];
        if ($label === '' && $t['icon'] === '') {
            $label = __('Offer', 'wppoppop');
        }
        self::print_late_script();
        return ' data-sidetab="' . esc_attr($t['pos']) . '"'
            . ' data-sidetab-label="' . esc_attr($label) . '"'
            . ' data-sidetab-icon="' . esc_attr($t['icon']) . '"'
            . ' data-sidetab-bg="' . esc_attr($t['bg']) . '"'
            . ' data-sidetab-radius="' . (int) $t['radius'] . '"'
            . ' data-sidetab-anim="' . esc_attr($t['anim']) . '"';
    }
}
