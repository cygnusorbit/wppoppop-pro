<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wppoppop-kpi-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-bottom:24px;">
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:18px 20px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;"><?php esc_html_e('Period Impressions', 'wppoppop-pro'); ?></span>
        <div style="font-size:28px;font-weight:700;color:#2563eb;margin-top:6px;"><?php echo number_format($total_impr); ?></div>
        <span style="font-size:12px;color:#94a3b8;margin-top:4px;display:block;"><?php esc_html_e('Views recorded', 'wppoppop-pro'); ?></span>
    </div>
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:18px 20px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;"><?php esc_html_e('Leads Captured', 'wppoppop-pro'); ?></span>
        <div style="font-size:28px;font-weight:700;color:#10b981;margin-top:6px;"><?php echo number_format($total_subs); ?></div>
        <span style="font-size:12px;color:#94a3b8;margin-top:4px;display:block;"><?php esc_html_e('Form completions', 'wppoppop-pro'); ?></span>
    </div>
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:18px 20px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;"><?php esc_html_e('Confirmed Leads', 'wppoppop-pro'); ?></span>
        <div style="font-size:28px;font-weight:700;color:#6366f1;margin-top:6px;"><?php echo number_format($total_conf); ?></div>
        <span style="font-size:12px;color:#94a3b8;margin-top:4px;display:block;"><?php esc_html_e('Verified double opt-in', 'wppoppop-pro'); ?></span>
    </div>
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:18px 20px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;"><?php esc_html_e('Conversion Rate', 'wppoppop-pro'); ?></span>
        <div style="font-size:28px;font-weight:700;color:#f59e0b;margin-top:6px;"><?php echo esc_html($conversion_rate); ?>%</div>
        <span style="font-size:12px;color:#94a3b8;margin-top:4px;display:block;"><?php esc_html_e('Submissions / Views', 'wppoppop-pro'); ?></span>
    </div>
</div>
