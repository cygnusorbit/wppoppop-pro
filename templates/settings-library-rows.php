<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template variables are local to the method that includes this file
if (!defined('ABSPATH')) {
    exit;
}
/* Printed inside Settings > Typography & Component Extensions > Library Extensions. $s is the stored settings array. */
$load_canvas_confetti  = !empty($s['load_canvas_confetti']);
$load_canvas_fireworks = !empty($s['load_canvas_fireworks']);
$signature_pad         = (!empty($s['signature_pad']) || !empty($s['load_signature_pad']));
$js_parser             = (!empty($s['js_parser']) || !empty($s['load_math_parser']));
?>
                    <label style="display:block;margin-bottom:8px;">
                        <input type="hidden" name="settings[load_canvas_confetti]" value="0">
                        <input type="checkbox" name="settings[load_canvas_confetti]" value="1" <?php checked($load_canvas_confetti); ?>>
                        <strong><?php esc_html_e('Canvas Confetti', 'wppoppop-pro'); ?></strong> — <?php esc_html_e('Celebratory confetti particle bursts on lead capture and prize unlocks.', 'wppoppop-pro'); ?>
                    </label>
                    <label style="display:block;margin-bottom:8px;">
                        <input type="hidden" name="settings[load_canvas_fireworks]" value="0">
                        <input type="checkbox" name="settings[load_canvas_fireworks]" value="1" <?php checked($load_canvas_fireworks); ?>>
                        <strong><?php esc_html_e('Canvas Fireworks', 'wppoppop-pro'); ?></strong> — <?php esc_html_e('Particle fireworks display for discount unlock screens.', 'wppoppop-pro'); ?>
                    </label>
                    <label style="display:block;margin-bottom:8px;">
                        <input type="hidden" name="settings[signature_pad]" value="0">
                        <input type="checkbox" name="settings[signature_pad]" value="1" <?php checked($signature_pad); ?>>
                        <strong><?php esc_html_e('Signature Pad', 'wppoppop-pro'); ?></strong> — <?php esc_html_e('HTML5 canvas digital signature capture input fields.', 'wppoppop-pro'); ?>
                    </label>
                    <label style="display:block;">
                        <input type="hidden" name="settings[js_parser]" value="0">
                        <input type="checkbox" name="settings[js_parser]" value="1" <?php checked($js_parser); ?>>
                        <strong><?php esc_html_e('Math Formula Parser', 'wppoppop-pro'); ?></strong> — <?php esc_html_e('Dynamic client-side math evaluation for quote calculators.', 'wppoppop-pro'); ?>
                    </label>
                    <?php WpPopPop_Pro_Extensions::locked_notice(); ?>
