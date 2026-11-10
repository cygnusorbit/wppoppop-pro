<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tile Select: palette button, inspector panel, layer-settings row and visitor markup.
 */
class WpPopPop_Pro_Element_Tile {

    const TYPE = 'tile';

    public static function label() {
        return __('Tile Select', 'wppoppop-pro');
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
        return 'formModule alignCard bg typo hover';
    }

    public static function panel_file() {
        return 'elem-20-tile.php';
    }

    protected static function inner(array $el) {
        $t = 'tile';
        $field_name = !empty($el['fieldName']) ? sanitize_key($el['fieldName']) : (!empty($el['token']) ? sanitize_key($el['token']) : $t);
        $field_id = !empty($el['fieldId']) ? sanitize_html_class($el['fieldId']) : 'wppoppop-field-' . esc_attr($el['id'] ?? uniqid());
        $default_val = isset($el['defaultValue']) ? (string) $el['defaultValue'] : '';
        $placeholder = isset($el['placeholder']) ? (string) $el['placeholder'] : '';
        $data_attrs = WpPopPop_Front_Decomposed_26::build_data_attrs($el);

        /* WPPOPPOP_TILE_FRONT: panel keys, own click class, per-tile border/typography/hover, required-safe value */
        $tl_hex = function ($v, $d) { return (is_string($v) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $v)) ? $v : $d; };
        $tl_raw = isset($el['optionsList']) ? (string) $el['optionsList'] : (isset($el['options']) ? (is_array($el['options']) ? implode("\n", $el['options']) : (string) $el['options']) : '');
        $tl_items = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $tl_raw)), 'strlen'));
        if (count($tl_items) === 1 && strpos($tl_items[0], ',') !== false) { $tl_items = array_values(array_filter(array_map('trim', explode(',', $tl_items[0])), 'strlen')); }
        if (empty($tl_items)) { $tl_items = array('$10 / mo', '$25 / mo', '$50 / mo'); }
        $tl_cols = !empty($el['gridCols']) ? intval($el['gridCols']) : (!empty($el['columns']) ? intval($el['columns']) : 3);
        $tl_cols = max(1, min(4, $tl_cols));
        $tl_acc = $tl_hex(isset($el['activeColor']) ? $el['activeColor'] : '', '#38bdf8');
        $tl_multi = !empty($el['multiSelect']) && $el['multiSelect'] !== 'false' && $el['multiSelect'] !== '0';
        $tl_defs = array_values(array_filter(array_map('trim', explode(',', $default_val)), 'strlen'));
        if (!$tl_multi && count($tl_defs) > 1) { $tl_defs = array($tl_defs[0]); }
        $tl_al = (isset($el['textAlign']) && in_array($el['textAlign'], array('left', 'center', 'right'), true)) ? $el['textAlign'] : 'center';
        $tl_j = ($tl_al === 'left') ? 'flex-start' : (($tl_al === 'right') ? 'flex-end' : 'center');
        $tl_fg = $tl_hex(isset($el['color']) ? $el['color'] : '', '#334155');
        $tl_bg = (!empty($el['transparentFill']) || (isset($el['bgColor']) && $el['bgColor'] === 'transparent')) ? 'transparent' : $tl_hex(isset($el['bgColor']) ? $el['bgColor'] : '', '#f1f5f9'); /* WPPOPPOP_TILE_BG */
        $tl_ty = 'font-size:' . ((!empty($el['fontSize']) && is_numeric($el['fontSize'])) ? intval($el['fontSize']) : 12) . 'px;';
        $tl_ty .= 'font-weight:' . ((!empty($el['fontWeight']) && preg_match('/^[0-9a-z]{3,10}$/i', (string) $el['fontWeight'])) ? $el['fontWeight'] : '700') . ';';
        if (!empty($el['lineHeight']) && is_numeric($el['lineHeight'])) { $tl_ty .= 'line-height:' . floatval($el['lineHeight']) . ';'; }
        if (isset($el['letterSpacing']) && is_numeric($el['letterSpacing']) && floatval($el['letterSpacing']) != 0) { $tl_ty .= 'letter-spacing:' . floatval($el['letterSpacing']) . 'px;'; }
        $tl_bw = (isset($el['borderWidth']) && is_numeric($el['borderWidth']) && intval($el['borderWidth']) > 0) ? intval($el['borderWidth']) : 1;
        $tl_bs = (!empty($el['borderStyle']) && in_array($el['borderStyle'], array('solid', 'dashed', 'dotted', 'double', 'none'), true)) ? $el['borderStyle'] : 'solid';
        $tl_bc = $tl_hex(isset($el['borderColor']) ? $el['borderColor'] : '', '#cbd5e1');
        $tl_rad = (isset($el['borderRadius']) && is_numeric($el['borderRadius'])) ? max(0, intval($el['borderRadius'])) : 6;
        $tl_h = array(
            'background-color' => $tl_hex(isset($el['hoverBgColor']) ? $el['hoverBgColor'] : (isset($el['hoverBg']) ? $el['hoverBg'] : ''), ''),
            'color' => $tl_hex(isset($el['hoverTextColor']) ? $el['hoverTextColor'] : (isset($el['hoverColor']) ? $el['hoverColor'] : ''), ''),
            'border-color' => $tl_hex(isset($el['hoverBorderColor']) ? $el['hoverBorderColor'] : (isset($el['hoverBorder']) ? $el['hoverBorder'] : ''), ''),
        );
        $tl_rules = '';
        foreach ($tl_h as $tl_prop => $tl_val) { if ($tl_val !== '') { $tl_rules .= $tl_prop . ':' . $tl_val . ' !important;'; } }
        $tl_style = ($tl_rules !== '') ? '<style>#' . $field_id . '-grid .wppoppop-tl-card[aria-pressed="false"]:hover{' . $tl_rules . '}</style>' : '';
        $tl_cards = '';
        $tl_vals = array();
        foreach ($tl_items as $tl_lbl) {
            $tl_on = in_array($tl_lbl, $tl_defs, true);
            if ($tl_on) { $tl_vals[] = $tl_lbl; }
            $tl_border = ($tl_bs === 'none') ? 'none' : ($tl_bw . 'px ' . $tl_bs . ' ' . ($tl_on ? $tl_acc : $tl_bc));
            $tl_cards .= '<div class="wppoppop-tl-card" role="button" tabindex="0" data-value="' . esc_attr($tl_lbl) . '" data-bg="' . esc_attr($tl_bg) . '" data-fg="' . esc_attr($tl_fg) . '" data-bc="' . esc_attr($tl_bc) . '" aria-pressed="' . ($tl_on ? 'true' : 'false') . '" style="min-width:0;box-sizing:border-box;display:flex;align-items:center;justify-content:' . $tl_j . ';padding:6px;border-radius:' . $tl_rad . 'px;border:' . $tl_border . ';background:' . ($tl_on ? $tl_acc : $tl_bg) . ';color:' . ($tl_on ? '#ffffff' : $tl_fg) . ';cursor:pointer;transition:background-color .15s ease,color .15s ease,border-color .15s ease;-webkit-user-select:none;user-select:none;' . $tl_ty . '">'
                . '<span style="max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' . esc_html($tl_lbl) . '</span></div>';
        }
        $tl_data = preg_replace('/\s(data-input-mask|data-filters|maxlength|minlength|autocomplete|readonly="readonly")(="[^"]*")?/', '', $data_attrs);
        return $tl_style . '<div class="wppoppop-tl-wrap" style="position:relative;width:100%;height:100%;pointer-events:auto;">'
            . '<div id="' . $field_id . '-grid" class="wppoppop-tl-grid" data-multi="' . ($tl_multi ? '1' : '0') . '" data-active-color="' . esc_attr($tl_acc) . '" style="display:grid;grid-template-columns:repeat(' . $tl_cols . ',minmax(0,1fr));grid-auto-rows:minmax(0,1fr);gap:6px;width:100%;height:100%;box-sizing:border-box;">' . $tl_cards . '</div>'
            . '<input type="text" id="' . $field_id . '" name="fields[' . esc_attr($field_name) . ']" data-field-name="' . esc_attr($field_name) . '" value="' . esc_attr(implode(', ', $tl_vals)) . '" readonly tabindex="-1" aria-hidden="true"' . $tl_data . ' style="position:absolute;left:0;top:0;width:100%;height:100%;opacity:0;pointer-events:none;border:0;margin:0;padding:0;" />'
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
