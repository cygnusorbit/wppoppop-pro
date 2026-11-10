<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Boot sequence. Everything is attached to core's extension points; this plugin edits no core file.
 */
class WpPopPop_Pro_Plugin {

    protected static $core_ok = false;

    public static function boot() {
        add_action('plugins_loaded', array(__CLASS__, 'load_textdomain'));
        add_action('wppoppop_loaded', array(__CLASS__, 'on_core_loaded'));
        add_action('admin_notices', array(__CLASS__, 'notices'));
        add_action('after_plugin_row_' . WPPOPPOP_PRO_BASENAME, array(__CLASS__, 'plugin_row_notice'));
        add_action('admin_head-plugins.php', array(__CLASS__, 'plugin_row_css'));
        WpPopPop_Pro_License::hooks();
        WpPopPop_Pro_Updater::hooks();
        require_once WPPOPPOP_PRO_PATH . 'includes/class-pro-db.php';
        WpPopPop_Pro_DB::hooks();
        register_activation_hook(WPPOPPOP_PRO_FILE, array('WpPopPop_Pro_DB', 'install'));
        register_deactivation_hook(WPPOPPOP_PRO_FILE, array(__CLASS__, 'deactivate'));

        /* Core reads these while it boots (before wppoppop_loaded fires), so they are attached right away. */
        add_filter('wppoppop_pro_licensed', array('WpPopPop_Pro_License', 'filter_licensed'));
        add_filter('wppoppop_module_roots', array(__CLASS__, 'module_roots'));
        add_filter('wppoppop_preset_roots', array(__CLASS__, 'preset_roots'));
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('wppoppop_tracking_daily');
        wp_clear_scheduled_hook('wppoppop_weekly_report');
    }

    public static function load_textdomain() {
        load_plugin_textdomain('wppoppop-pro', false, dirname(WPPOPPOP_PRO_BASENAME) . '/languages');
    }

    public static function core_ready() {
        return self::$core_ok;
    }

    public static function on_core_loaded() {
        if (!defined('WPPOPPOP_API_VERSION') || (int) WPPOPPOP_API_VERSION < WPPOPPOP_PRO_MIN_API) {
            return;
        }
        self::$core_ok = true;

        foreach (array('class-pro-gate.php', 'class-pro-settings.php', 'ab/class-admin-ab-query.php', 'ab/class-front-ab.php',
                       'ab/class-ajax-campaigns.php', 'ab/class-pro-ab.php', 'stats/class-admin-stats.php', 'stats/class-pro-stats.php', 'elements/class-pro-elements.php', 'modules/class-pro-modules.php', 'modules/class-pro-pipeline.php') as $file) {
            require_once WPPOPPOP_PRO_PATH . 'includes/' . $file;
        }
        WpPopPop_Pro_Settings::hooks();
        WpPopPop_Pro_AB::boot();
        WpPopPop_Pro_Stats::boot();
        WpPopPop_Pro_Elements::boot();
        WpPopPop_Pro_Modules::boot();
        WpPopPop_Pro_Pipeline::boot();

        if (is_admin()) {
            WpPopPop_Pro_Admin::hooks();
            add_action('admin_init', array(__CLASS__, 'seed_modules'));
        }
    }

    /* Pro modules stay registered even without a licence, so saved popup data is never dropped. */
    public static function module_roots($roots) {
        $roots[] = array('path' => WPPOPPOP_PRO_PATH . 'modules', 'url' => WPPOPPOP_PRO_URL . 'modules/');
        return $roots;
    }

    /* Pro templates are offered in the Library only with an active licence. */
    public static function preset_roots($roots) {
        if (WpPopPop_Pro_License::is_unlocked()) {
            $roots[] = array('path' => WPPOPPOP_PRO_PATH . 'library-presets', 'url' => WPPOPPOP_PRO_URL . 'library-presets/');
        }
        return $roots;
    }

    /** Switches on the modules flagged "default_active" once per Pro version, never touching a state the user chose. */
    public static function seed_modules() {
        if (!current_user_can('manage_options') || !WpPopPop_Pro_License::is_unlocked()) {
            return;
        }
        if (get_option('wppoppop_pro_seed_ver') === WPPOPPOP_PRO_VERSION) {
            return;
        }
        if (!class_exists('WpPopPop_Modules')) {
            return;
        }
        $states = WpPopPop_Modules::states();
        foreach (WpPopPop_Modules::all() as $slug => $m) {
            if ($m['source'] === 'pro' && !empty($m['default_active']) && !isset($states[$slug]) && $m['compatible']) {
                WpPopPop_Modules::set_state($slug, 'installed');
            }
        }
        update_option('wppoppop_pro_seed_ver', WPPOPPOP_PRO_VERSION, false);
    }

    /** True when the Plugins screen should flag Pro as only partly active. */
    protected static function row_flagged() {
        return !self::$core_ok
            && current_user_can('activate_plugins')
            && (is_plugin_active(WPPOPPOP_PRO_BASENAME) || is_plugin_active_for_network(WPPOPPOP_PRO_BASENAME));
    }

    /* Row under Pro on the Plugins screen while WpPopPop is missing, inactive or too old. Same columns as the table above it. */
    public static function plugin_row_notice() {
        if (!self::row_flagged()) {
            return;
        }
        $table   = _get_list_table('WP_Plugins_List_Table');
        $columns = $table ? (int) $table->get_column_count() : 4;
        $rest    = max(1, $columns - 2);
        $min     = defined('WPPOPPOP_PRO_MIN_CORE') ? WPPOPPOP_PRO_MIN_CORE : '';
        $label   = did_action('wppoppop_loaded')
            /* translators: 1: plugin name, 2: minimum version number */
            ? __('Outdated %1$s: minimum required %2$s', 'wppoppop-pro')
            /* translators: 1: plugin name, 2: minimum version number */
            : __('Missing %1$s: minimum required %2$s', 'wppoppop-pro');
        $detail  = sprintf($label, '<strong>WpPopPop</strong>', '<strong>' . esc_html($min) . '</strong>');
        printf(
            '<tr class="active wppoppop-pro-flag" data-slug="wppoppop-pro" data-plugin="%1$s"><th scope="row" class="check-column"><span class="dashicons dashicons-warning" aria-hidden="true"></span></th><td class="plugin-title"><span>%2$s</span></td><td class="column-description desc" colspan="%3$d"><p>%4$s</p></td></tr>',
            esc_attr(WPPOPPOP_PRO_BASENAME),
            esc_html__('This plugin is not fully active.', 'wppoppop-pro'),
            $rest,
            wp_kses($detail, array('strong' => array()))
        );
    }

    public static function plugin_row_css() {
        if (!self::row_flagged()) {
            return;
        }
        $r = 'tr[data-plugin="' . esc_attr(WPPOPPOP_PRO_BASENAME) . '"]';
        echo '<style id="wppoppop-pro-row-css">'
            . $r . '.active th,' . $r . '.active td{background-color:#fcf0f1}'
            . $r . '.active:not(.wppoppop-pro-flag) th,' . $r . '.active:not(.wppoppop-pro-flag) td{box-shadow:inset 0 -1px 0 rgba(0,0,0,.06)}'
            . $r . '.active th.check-column,' . $r . '.active td.check-column{border-left-color:#d63638}'
            . $r . '.wppoppop-pro-flag .dashicons-warning{color:#d63638;width:20px;height:20px;font-size:20px;margin-left:5px}'
            . $r . '.wppoppop-pro-flag .desc p{margin:0}'
            . '@media screen and (max-width:782px){' . $r . '.wppoppop-pro-flag td{display:block!important;width:auto;padding:4px 9px 10px 9px}}'
            . '</style>' . "\n";
    }

    public static function notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!did_action('wppoppop_loaded')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('WpPopPop Pro needs the WpPopPop plugin. Install and activate WpPopPop first.', 'wppoppop-pro') . '</p></div>';
            return;
        }
        if (!self::$core_ok) {
            echo '<div class="notice notice-error"><p>' . esc_html__('WpPopPop Pro needs a newer version of WpPopPop. Please update WpPopPop.', 'wppoppop-pro') . '</p></div>';
            return;
        }
        if (!WpPopPop_Pro_License::is_unlocked()) {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if ($screen && strpos((string) $screen->id, 'wppoppop-license') !== false) {
                return;
            }
            printf(
                '<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
                esc_html__('WpPopPop Pro is not activated. Pro templates and modules stay locked until you enter your licence key.', 'wppoppop-pro'),
                esc_url(admin_url('admin.php?page=wppoppop-license')),
                esc_html__('Activate licence', 'wppoppop-pro')
            );
        }
    }
}
