<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image Select: palette button, inspector panel, layer-settings row and visitor markup.
 */
class WpPopPop_Pro_Element_Imageselect {

    const TYPE = 'imageselect';

    public static function label() {
        return __('Image Select', 'wppoppop-pro');
    }

    /** Core element the entry is placed after in the builder lists. */
    public static function after() {
        return 'multiselect';
    }

    public static function is_form() {
        return true;
    }

    /** Style tab layout, same format as core's wppoppop_layer_style_layout. */
    public static function style() {
        return 'formModule alignCard typo';
    }

    public static function panel_file() {
        return 'elem-19-imageselect.php';
    }

    protected static function inner(array $el) {
        $t = 'imageselect';
        $field_name = !empty($el['fieldName']) ? sanitize_key($el['fieldName']) : (!empty($el['token']) ? sanitize_key($el['token']) : $t);
        $field_id = !empty($el['fieldId']) ? sanitize_html_class($el['fieldId']) : 'wppoppop-field-' . esc_attr($el['id'] ?? uniqid());
        $default_val = isset($el['defaultValue']) ? (string) $el['defaultValue'] : '';
        $placeholder = isset($el['placeholder']) ? (string) $el['placeholder'] : '';
        $data_attrs = WpPopPop_Front_Decomposed_26::build_data_attrs($el);

        /* WPPOPPOP_IMAGESELECT_FRONT: panel keys, Label|URL parsing, active border, clickable cards, required-safe value */
        $is_raw = isset($el['optionsList']) ? (string) $el['optionsList'] : (isset($el['options']) ? (is_array($el['options']) ? implode("\n", $el['options']) : (string) $el['options']) : '');
        $is_lines = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $is_raw)), 'strlen'));
        if (count($is_lines) === 1 && strpos($is_lines[0], ',') !== false && !preg_match('#https?:|//|\||::#i', $is_lines[0])) {
            $is_lines = array_values(array_filter(array_map('trim', explode(',', $is_lines[0])), 'strlen'));
        }
        if (empty($is_lines)) { $is_lines = array('Style A', 'Style B', 'Style C'); }
        $is_items = array();
        foreach ($is_lines as $is_ln) {
            $is_lbl = $is_ln; $is_img = '';
            if (strpos($is_ln, '::') !== false) { $is_p = explode('::', $is_ln, 2); $is_lbl = trim($is_p[0]); $is_img = trim($is_p[1]); }
            elseif (strpos($is_ln, '|') !== false) { $is_p = explode('|', $is_ln, 2); $is_lbl = trim($is_p[0]); $is_img = trim($is_p[1]); }
            elseif (preg_match('#^(.*?),\s*((?:https?:)?//\S+|/\S+)$#i', $is_ln, $is_m)) { $is_lbl = trim($is_m[1]); $is_img = trim($is_m[2]); }
            $is_items[] = array($is_lbl, $is_img);
        }
        $is_cols = !empty($el['gridCols']) ? intval($el['gridCols']) : (!empty($el['columns']) ? intval($el['columns']) : 3);
        $is_cols = max(1, min(4, $is_cols));
        $is_acc = (!empty($el['activeColor']) && preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $el['activeColor'])) ? $el['activeColor'] : '#38bdf8';
        $is_multi = !empty($el['multiSelect']) && $el['multiSelect'] !== 'false' && $el['multiSelect'] !== '0';
        $is_defs = array_values(array_filter(array_map('trim', explode(',', $default_val)), 'strlen'));
        if (!$is_multi && count($is_defs) > 1) { $is_defs = array($is_defs[0]); }
        $is_ty = 'font-size:' . ((!empty($el['fontSize']) && is_numeric($el['fontSize'])) ? intval($el['fontSize']) : 12) . 'px;';
        $is_ty .= 'font-weight:' . ((!empty($el['fontWeight']) && preg_match('/^[0-9a-z]{3,10}$/i', (string) $el['fontWeight'])) ? $el['fontWeight'] : '600') . ';';
        if (!empty($el['lineHeight']) && is_numeric($el['lineHeight'])) { $is_ty .= 'line-height:' . floatval($el['lineHeight']) . ';'; }
        if (isset($el['letterSpacing']) && is_numeric($el['letterSpacing']) && floatval($el['letterSpacing']) != 0) { $is_ty .= 'letter-spacing:' . floatval($el['letterSpacing']) . 'px;'; }
        $is_ty .= 'color:' . ((!empty($el['color']) && preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $el['color'])) ? $el['color'] : '#1e293b') . ';';
        $is_cards = '';
        $is_vals = array();
        foreach ($is_items as $is_it) {
            $is_on = in_array($is_it[0], $is_defs, true);
            if ($is_on) { $is_vals[] = $is_it[0]; }
            $is_url = ($is_it[1] !== '') ? esc_url($is_it[1]) : '';
            $is_thumb = ($is_url !== '') ? '<img src="' . $is_url . '" alt="' . esc_attr($is_it[0]) . '" style="width:100%;flex:1 1 auto;min-height:0;object-fit:cover;border-radius:3px;display:block;" />' : '';
            $is_cards .= '<div class="wppoppop-imgsel-card" role="button" tabindex="0" data-value="' . esc_attr($is_it[0]) . '" aria-pressed="' . ($is_on ? 'true' : 'false') . '" style="min-width:0;min-height:0;box-sizing:border-box;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;padding:4px;border:2px solid ' . ($is_on ? $is_acc : '#cbd5e1') . ';border-radius:6px;background:#ffffff;text-align:center;cursor:pointer;">'
                . $is_thumb . '<span style="max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;' . $is_ty . '">' . esc_html($is_it[0]) . '</span></div>';
        }
        $is_data = preg_replace('/\s(data-input-mask|data-filters|maxlength|minlength|autocomplete|readonly="readonly")(="[^"]*")?/', '', $data_attrs);
        return '<div class="wppoppop-imgsel-wrap" style="position:relative;width:100%;height:100%;pointer-events:auto;">'
            . '<div class="wppoppop-imgsel-grid" data-multi="' . ($is_multi ? '1' : '0') . '" data-active-color="' . esc_attr($is_acc) . '" style="display:grid;grid-template-columns:repeat(' . $is_cols . ',minmax(0,1fr));grid-auto-rows:minmax(0,1fr);gap:6px;width:100%;height:100%;box-sizing:border-box;">' . $is_cards . '</div>'
            . '<input type="text" id="' . $field_id . '" name="fields[' . esc_attr($field_name) . ']" data-field-name="' . esc_attr($field_name) . '" value="' . esc_attr(implode(', ', $is_vals)) . '" readonly tabindex="-1" aria-hidden="true"' . $is_data . ' style="position:absolute;left:0;top:0;width:100%;height:100%;opacity:0;pointer-events:none;border:0;margin:0;padding:0;" />'
            . '</div>';
    }

    public static function render(array $el, array $ctx) {
        if (!WpPopPop_Pro_Elements::decomposed()) {
            return '';
        }
        $inner = self::inner($el);
        return '<div class="wppoppop-layer-item wppoppop-element wppoppop-el-text ' . $ctx['anim_class'] . '" style="' . $ctx['style_attr'] . ';padding:0!important">' . $inner . '</div>';
    }
}
