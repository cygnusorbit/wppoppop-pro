<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The four Pro builder elements: Progress Bar, Image Select, Tile Select and Star Rating.
 *
 * Visitor markup and the saved popup data keep working whenever this plugin is active, so a lapsed licence never
 * breaks a live popup. The builder (palette, inspector panels, layer-settings rows) needs an active licence.
 * To make an element free later, add its type to the `wppoppop_pro_free_elements` filter.
 */
class WpPopPop_Pro_Elements {

    protected static $booted = false;

    public static function classes() {
        return array(
            'progress'    => 'WpPopPop_Pro_Element_Progress',
            'imageselect' => 'WpPopPop_Pro_Element_Imageselect',
            'tile'        => 'WpPopPop_Pro_Element_Tile',
            'rating'      => 'WpPopPop_Pro_Element_Rating',
        );
    }

    public static function boot() {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
        foreach (array_keys(self::classes()) as $slug) {
            require_once WPPOPPOP_PRO_PATH . 'includes/elements/class-pro-element-' . $slug . '.php';
        }
        add_filter('wppoppop_render_element', array(__CLASS__, 'render'), 10, 4);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'front_assets'), 20);
        add_action('wppoppop_admin_builder_assets', array(__CLASS__, 'builder_assets'));
        add_action('wppoppop_builder_ribbon_tools', array(__CLASS__, 'ribbon'));
        add_action('wppoppop_builder_element_panels', array(__CLASS__, 'panels'));
        add_filter('wppoppop_layer_elements', array(__CLASS__, 'layer_elements'));
        add_filter('wppoppop_layer_form_types', array(__CLASS__, 'form_types'));
        add_filter('wppoppop_layer_style_layout', array(__CLASS__, 'style_layout'));
        add_filter('wppoppop_layer_catalogue', array(__CLASS__, 'catalogue'));
    }

    /** True when the builder may offer the element: licence active, or the type was released as free. */
    public static function available($type) {
        if (WpPopPop_Pro_Gate::unlocked()) {
            return true;
        }
        return in_array($type, (array) apply_filters('wppoppop_pro_free_elements', array()), true);
    }

    public static function decomposed() {
        if (!class_exists('WpPopPop_Front_Decomposed_26')) {
            $file = WPPOPPOP_PATH . 'includes/front/elements/class-front-decomposed-26.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
        return class_exists('WpPopPop_Front_Decomposed_26');
    }

    /* ---------------------------------------------------------------- visitor side */

    /** Core calls this filter for element types it has no case for; the result is already escaped HTML. */
    public static function render($html, $type, $el, $ctx) {
        if (is_string($html) && $html !== '') {
            return $html;
        }
        $classes = self::classes();
        $key = (string) $type;
        if (!isset($classes[$key])) {
            $key = strtolower(trim($key));
            if (!in_array($key, array('imageselect', 'tile'), true)) {
                return $html;
            }
        }
        $cls = $classes[$key];
        return $cls::render((array) $el, (array) $ctx);
    }

    public static function front_assets() {
        wp_enqueue_script('wppoppop-pro-front-elements', WPPOPPOP_PRO_URL . 'assets/js/front/elements.js', array('jquery', 'wppoppop-front-form-js'), WPPOPPOP_PRO_VERSION, true);
    }

    /* ---------------------------------------------------------------- builder side */

    /** Builder-side behaviour of the Pro elements; registers in the core extension store. */
    public static function builder_assets() {
        wp_enqueue_script(
            'wppoppop-pro-builder-elements',
            WPPOPPOP_PRO_URL . 'assets/js/builder/elements.js',
            array('jquery', 'wppoppop-builder-element-ext-js', 'wppoppop-builder-canvas-js', 'wppoppop-builder-inspector-js', 'wppoppop-builder-elements-registry'),
            WPPOPPOP_PRO_VERSION,
            true
        );
    }

    public static function ribbon() {
        include WPPOPPOP_PRO_PATH . 'templates/builder/ribbon-tools.php';
    }

    public static function panels() {
        foreach (self::classes() as $type => $cls) {
            $file = WPPOPPOP_PRO_PATH . 'templates/builder/elements/' . $cls::panel_file();
            if (self::available($type) && file_exists($file)) {
                include $file;
            }
        }
    }

    public static function layer_elements($list) {
        return self::insert($list, function ($type, $cls) {
            return $cls::label();
        });
    }

    public static function form_types($list) {
        $list = (array) $list;
        foreach (self::classes() as $type => $cls) {
            if ($cls::is_form() && self::available($type) && !in_array($type, $list, true)) {
                $list[] = $type;
            }
        }
        return $list;
    }

    public static function style_layout($map) {
        $map = (array) $map;
        foreach (self::classes() as $type => $cls) {
            if (self::available($type)) {
                $map[$type] = $cls::style();
            }
        }
        return $map;
    }

    /** Adds the "Rating Data" row to the Data tab of the layer settings. */
    public static function catalogue($cat) {
        $cat = (array) $cat;
        if (self::available('rating')) {
            $row = array(
                'key'      => 'rating_data',
                'label'    => __('Rating Data', 'wppoppop-pro'),
                'sel'      => '#insp-rating-data',
                'native'   => array('rating'),
                'd'        => 'block',
                'rows'     => array(),
                'per_type' => false,
            );
            $cat['data'] = array_merge(array($row), isset($cat['data']) ? (array) $cat['data'] : array());
        }
        return $cat;
    }

    /** Inserts each available element after its anchor element, appending any whose anchor is missing. */
    protected static function insert($list, $value) {
        $list = (array) $list;
        $add  = array();
        foreach (self::classes() as $type => $cls) {
            if (self::available($type) && !isset($list[$type])) {
                $add[$cls::after()][$type] = call_user_func($value, $type, $cls);
            }
        }
        $out = array();
        foreach ($list as $k => $v) {
            $out[$k] = $v;
            if (isset($add[$k])) {
                $out = array_merge($out, $add[$k]);
                unset($add[$k]);
            }
        }
        foreach ($add as $group) {
            $out = array_merge($out, $group);
        }
        return $out;
    }
}
