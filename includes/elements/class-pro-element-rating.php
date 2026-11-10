<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Star Rating: palette button, inspector panel, layer-settings row and visitor markup.
 */
class WpPopPop_Pro_Element_Rating {

    const TYPE = 'rating';

    public static function label() {
        return __('Star Rating', 'wppoppop-pro');
    }

    /** Core element the entry is placed after in the builder lists. */
    public static function after() {
        return 'password';
    }

    public static function is_form() {
        return true;
    }

    /** Style tab layout, same format as core's wppoppop_layer_style_layout. */
    public static function style() {
        return 'formModule alignCard';
    }

    public static function panel_file() {
        return 'elem-25-rating.php';
    }

    protected static function inner(array $el) {
        $t = 'rating';
        $field_name = !empty($el['fieldName']) ? sanitize_key($el['fieldName']) : (!empty($el['token']) ? sanitize_key($el['token']) : $t);
        $field_id = !empty($el['fieldId']) ? sanitize_html_class($el['fieldId']) : 'wppoppop-field-' . esc_attr($el['id'] ?? uniqid());
        $default_val = isset($el['defaultValue']) ? (string) $el['defaultValue'] : '';
        $placeholder = isset($el['placeholder']) ? (string) $el['placeholder'] : '';
        $data_attrs = WpPopPop_Front_Decomposed_26::build_data_attrs($el);

        /* WPPOPPOP_RATING_FRONT_V2: inactive colour, default from Data tab, a11y, matches builder stage */
        $rmax = ((!empty($el['ratingMax']) && intval($el['ratingMax']) === 10) || (empty($el['ratingMax']) && !empty($el['maxStars']) && intval($el['maxStars']) === 10)) ? 10 : 5; /* WPPOPPOP_RATING_MAXSTARS */
        $rcol = (!empty($el['ratingColor']) && preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $el['ratingColor'])) ? $el['ratingColor'] : '#f59e0b';
        $roff = (!empty($el['ratingInactiveColor']) && preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $el['ratingInactiveColor'])) ? $el['ratingInactiveColor'] : '#cbd5e1';
        $rshape = !empty($el['ratingShape']) ? $el['ratingShape'] : 'star';
        $glyph = ($rshape === 'heart') ? '♥' : (($rshape === 'thumb') ? '👍' : '★');
        $init_score = ($default_val !== '' && is_numeric($default_val)) ? max(0, min($rmax, intval($default_val))) : 0;
        $rfs = ($rmax === 10) ? 15 : 20;
        $rt_al = (isset($el['textAlign']) && in_array($el['textAlign'], array('left', 'center', 'right'), true)) ? $el['textAlign'] : 'center';
        $rt_j = ($rt_al === 'left') ? 'flex-start' : (($rt_al === 'right') ? 'flex-end' : 'center');
        $stars_html = '';
        for ($i = 1; $i <= $rmax; $i++) {
            $col = ($i <= $init_score) ? $rcol : $roff;
            $stars_html .= '<span class="wppoppop-rating-glyph" role="radio" tabindex="0" aria-label="' . $i . ' of ' . $rmax . '" data-score="' . $i . '" data-active-color="' . esc_attr($rcol) . '" style="color:' . esc_attr($col) . ';font-size:' . $rfs . 'px;cursor:pointer;line-height:1;user-select:none;-webkit-user-select:none;">' . $glyph . '</span>';
        }
        $rt_data = preg_replace('/\s(data-input-mask|data-filters|maxlength|minlength|autocomplete|readonly="readonly")(="[^"]*")?/', '', $data_attrs);
        return '<div class="wppoppop-interactive-rating" role="radiogroup" data-active-color="' . esc_attr($rcol) . '" data-inactive-color="' . esc_attr($roff) . '" style="width:100%;height:100%;display:flex;align-items:center;justify-content:' . $rt_j . ';gap:4px;pointer-events:auto;">'
             . '<input type="hidden" id="' . $field_id . '" name="fields[' . esc_attr($field_name) . ']" data-field-name="' . esc_attr($field_name) . '" value="' . ($init_score > 0 ? $init_score : '') . '"' . $rt_data . ' />'
             . $stars_html . '</div>';
    }

    public static function render(array $el, array $ctx) {
        if (!WpPopPop_Pro_Elements::decomposed()) {
            return '';
        }
        $inner = self::inner($el);
        if ($inner !== null && $inner !== '') {
            return '<div class="wppoppop-layer-item wppoppop-element wppoppop-field-rating wppoppop-el-rating ' . $ctx['anim_class'] . '" style="' . $ctx['style_attr'] . ';padding:0!important;pointer-events:auto;">' . $inner . '</div>';
        }
        return '<div class="wppoppop-layer-item wppoppop-element wppoppop-field-rating ' . $ctx['anim_class'] . '" style="' . $ctx['style_attr'] . ';display:flex;align-items:center;justify-content:center;color:#f59e0b;font-size:20px;cursor:pointer;"><input type="hidden" name="rating" value="5">★ ★ ★ ★ ★</div>';
    }
}
