<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wppoppop-stats-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-top:10px;">
    <div>
        <h2 class="wppoppop-stats-title" style="margin:0;font-size:20px;font-weight:700;color:#1e293b;"><?php esc_html_e('Visual Statistics & Analytics', 'wppoppop-pro'); ?></h2>
        <p style="margin:4px 0 0;color:#64748b;font-size:13px;"><?php esc_html_e('Track campaign performance, conversion velocity, and audience engagement trends.', 'wppoppop-pro'); ?></p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;background:#ffffff;padding:4px;border-radius:6px;border:1px solid #cbd5e1;">
        <a href="<?php echo esc_url(add_query_arg('range', '7days')); ?>" 
           class="button <?php echo $range === '7days' ? 'button-primary' : ''; ?>" 
           style="font-size:12px;border:none;box-shadow:none;<?php echo $range === '7days' ? 'background:#2563eb;color:#fff;' : 'background:transparent;color:#475569;'; ?>">
           <?php esc_html_e('Last 7 Days', 'wppoppop-pro'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('range', '30days')); ?>" 
           class="button <?php echo $range === '30days' ? 'button-primary' : ''; ?>" 
           style="font-size:12px;border:none;box-shadow:none;<?php echo $range === '30days' ? 'background:#2563eb;color:#fff;' : 'background:transparent;color:#475569;'; ?>">
           <?php esc_html_e('Last 30 Days', 'wppoppop-pro'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('range', 'all')); ?>" 
           class="button <?php echo $range === 'all' ? 'button-primary' : ''; ?>" 
           style="font-size:12px;border:none;box-shadow:none;<?php echo $range === 'all' ? 'background:#2563eb;color:#fff;' : 'background:transparent;color:#475569;'; ?>">
           <?php esc_html_e('All Time', 'wppoppop-pro'); ?>
        </a>
    </div>
</div>
