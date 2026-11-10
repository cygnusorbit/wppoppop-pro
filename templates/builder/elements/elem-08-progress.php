<?php
if (!defined('ABSPATH')) exit;
?>
<!-- DECOMPOSED MODULE 08: PROGRESS BAR (progress) -->
<div class="element-panel wppoppop-panel-specific" id="panel-elem-progress" data-elem-family="media" style="display:none;background:#0f172a;border:1px solid #1e293b;border-radius:8px;padding:12px;margin-bottom:14px;">
    <div style="font-size:10px;font-weight:800;color:#38bdf8;text-transform:uppercase;margin-bottom:10px;letter-spacing:0.5px;"><?php esc_html_e('8. Progress Bar Settings', 'wppoppop-pro'); ?></div>
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;"><?php esc_html_e('PROGRESS MODE', 'wppoppop-pro'); ?></label>
        <select id="prop-progress-mode" class="widefat wppoppop-input wppoppop-gp-bind" data-prop="progressMode" style="font-size:12px;">
            <option value="manual"><?php esc_html_e('Manual (fixed percent)', 'wppoppop-pro'); ?></option>
            <option value="form"><?php esc_html_e('Auto: Form completion', 'wppoppop-pro'); ?></option>
            <option value="steps"><?php esc_html_e('Auto: Canvas steps', 'wppoppop-pro'); ?></option>
        </select>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
        <div id="prop-progress-val-wrap">
            <label style="display:block;font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;"><?php esc_html_e('FILL PERCENT (0–100%)', 'wppoppop-pro'); ?></label>
            <input type="number" id="prop-progress-percent" class="widefat wppoppop-input wppoppop-gp-bind" data-prop="progressPercent" min="0" max="100" value="65" style="font-size:12px;">
        </div>
        <div>
            <label style="display:block;font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;"><?php esc_html_e('FILL BAR COLOR', 'wppoppop-pro'); ?></label>
            <input type="color" id="prop-progress-color" class="widefat wppoppop-gp-bind" data-prop="progressColor" value="#10b981" style="height:34px;padding:2px;background:#090d16;border:1px solid #1e293b;border-radius:6px;cursor:pointer;">
        </div>
    </div>
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;"><?php esc_html_e('TRACK (BACKGROUND) COLOR', 'wppoppop-pro'); ?></label>
        <input type="color" id="prop-progress-track" class="widefat wppoppop-gp-bind" data-prop="trackColor" value="#334155" style="width:100%;height:34px;padding:2px;background:#090d16;border:1px solid #1e293b;border-radius:6px;cursor:pointer;box-sizing:border-box;">
    </div>
    <label style="display:block;font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;"><?php esc_html_e('PROGRESS LABEL TEXT', 'wppoppop-pro'); ?></label>
    <input type="text" id="prop-progress-label" class="widefat wppoppop-input wppoppop-gp-bind" data-prop="content" placeholder="<?php esc_attr_e('e.g. 65% Complete — Almost There!', 'wppoppop-pro'); ?>" style="margin-bottom:10px;font-size:12px;">
    <div style="display:flex;gap:14px;flex-wrap:wrap;">
        <label style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:#cbd5e1;cursor:pointer;">
            <input type="checkbox" id="prop-progress-striped" class="wppoppop-gp-bind" data-prop="progressStriped" value="1" checked> <?php esc_html_e('Striped Bar', 'wppoppop-pro'); ?>
        </label>
        <label style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:#cbd5e1;cursor:pointer;">
            <input type="checkbox" id="prop-progress-animated" class="wppoppop-gp-bind" data-prop="progressAnimated" value="1" checked> <?php esc_html_e('Animated Stripes', 'wppoppop-pro'); ?>
        </label>
        <label style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:#cbd5e1;cursor:pointer;">
            <input type="checkbox" id="prop-progress-showpct" class="wppoppop-gp-bind" data-prop="showPercentLabel" value="1" checked> <?php esc_html_e('Show %', 'wppoppop-pro'); ?>
        </label>
    </div>
</div>

                <!-- PROGRESS BAR -->
                <div class="element-panel" id="panel-elem-progress-legacy" style="display:none;">
                    <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:4px;"><?php esc_html_e('PROGRESS VALUE:', 'wppoppop-pro'); ?> <span id="prop-progress-val-num">50</span>%</label>
                    <input type="range" id="prop-progress-val" min="0" max="100" value="50" style="width:100%;margin-bottom:8px;">
                    <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:4px;"><?php esc_html_e('BAR FILL COLOR', 'wppoppop-pro'); ?></label>
                    <input type="color" id="prop-progress-bar-color" value="#3b82f6" class="widefat" style="height:32px;">
                </div>
