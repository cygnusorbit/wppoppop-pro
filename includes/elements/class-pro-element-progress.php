<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Progress Bar: palette button, inspector panel, layer-settings row and visitor markup.
 */
class WpPopPop_Pro_Element_Progress {

    const TYPE = 'progress';

    public static function label() {
        return __('Progress Bar', 'wppoppop-pro');
    }

    /** Core element the entry is placed after in the builder lists. */
    public static function after() {
        return 'icon';
    }

    public static function is_form() {
        return false;
    }

    /** Style tab layout, same format as core's wppoppop_layer_style_layout. */
    public static function style() {
        return 'opacity bg typo padding border shadow css';
    }

    public static function panel_file() {
        return 'elem-08-progress.php';
    }

    /** @param array $ctx style_attr / anim_class / content from the core renderer. */
    public static function render(array $el, array $ctx) {
        $anim_class = $ctx['anim_class'];
        $style_attr = $ctx['style_attr'];
        /* WPPOPPOP_PROGRESS_CASE: real value / colours / stripes / label */
        $pg_pct = 65;
        foreach (array('progressValue', 'progressPercent', 'progressVal') as $pg_k) {
            if (isset($el[$pg_k]) && $el[$pg_k] !== '' && is_numeric($el[$pg_k])) { $pg_pct = max(0, min(100, (int) round((float) $el[$pg_k]))); break; }
        }
        $pg_on = function ($keys, $def) use ($el) {
            foreach ($keys as $k) { if (isset($el[$k])) { return !empty($el[$k]) && $el[$k] !== 'false' && $el[$k] !== '0'; } }
            return $def;
        };
        $pg_hex = function ($v, $d) { return (is_string($v) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $v)) ? $v : $d; };
        $pg_fill = $pg_hex(!empty($el['fillColor']) ? $el['fillColor'] : (!empty($el['progressColor']) ? $el['progressColor'] : ''), '#10b981');
        $pg_track = $pg_hex(!empty($el['trackColor']) ? $el['trackColor'] : '', '#334155');
        $pg_striped = $pg_on(array('progressStriped', 'striped'), false);
        $pg_anim = $pg_striped && $pg_on(array('progressAnimated'), false);
        $pg_showpct = $pg_on(array('showPercentLabel'), true);
        $pg_lbl = isset($el['progressLabel']) ? trim((string) $el['progressLabel']) : '';
        /* WPPOPPOP_PROGRESS_MODE */
        $pg_mode = (isset($el['progressMode']) && in_array($el['progressMode'], array('form', 'steps'), true)) ? $el['progressMode'] : 'manual';
        if ($pg_mode === 'form') { $pg_pct = 0; }
        $pg_badge = $pg_lbl !== '' ? ($pg_lbl . ($pg_showpct ? ' (' . $pg_pct . '%)' : '')) : ($pg_showpct ? ($pg_pct . '%') : '');
        $pg_stripe = $pg_striped ? 'background-image:linear-gradient(45deg,rgba(255,255,255,.2) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.2) 50%,rgba(255,255,255,.2) 75%,transparent 75%,transparent);background-size:20px 20px;' : '';
        static $pg_css_done = false;
        $pg_css = '';
        if (!$pg_css_done) {
            $pg_css_done = true;
            $pg_css = '<style>@-webkit-keyframes wppProgStripe{from{background-position:20px 0}to{background-position:0 0}}@keyframes wppProgStripe{from{background-position:20px 0}to{background-position:0 0}}.wppoppop-prog-anim{-webkit-animation:wppProgStripe 1s linear infinite;animation:wppProgStripe 1s linear infinite}</style>';
        }
        return $pg_css . '<div class="wppoppop-layer-item wppoppop-element wppoppop-el-progress ' . $anim_class . '" data-progress-mode="' . esc_attr($pg_mode) . '" data-progress-label="' . esc_attr($pg_lbl) . '" data-progress-showpct="' . ($pg_showpct ? '1' : '0') . '" style="' . $style_attr . ';overflow:hidden;">'
            . '<div style="width:100%;height:100%;background:' . esc_attr($pg_track) . ';border-radius:inherit;overflow:hidden;position:relative;display:flex;align-items:center;">'
            . '<div class="wppoppop-progress-fill' . ($pg_anim ? ' wppoppop-prog-anim' : '') . '" style="width:' . $pg_pct . '%;height:100%;background-color:' . esc_attr($pg_fill) . ';' . $pg_stripe . 'transition:width .3s ease;"></div>'
            . ($pg_badge !== '' ? '<span class="wppoppop-progress-badge" style="position:absolute;left:0;right:0;text-align:center;font-size:11px;font-weight:700;color:#fff;text-shadow:0 1px 2px rgba(0,0,0,.45);">' . esc_html($pg_badge) . '</span>' : '')
            . '</div></div>';
    }
}
