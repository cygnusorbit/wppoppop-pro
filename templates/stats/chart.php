<?php
if (!defined('ABSPATH')) {
    exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound


$chart_width  = 900;
$chart_height = 260;
$padding      = 40;
$plot_width   = $chart_width - ($padding * 2);
$plot_height  = $chart_height - ($padding * 2);

$max_val = 1;
foreach ($chart_series as $s) {
    foreach ($s['impr'] as $v) { if ($v > $max_val) { $max_val = $v; } }
    foreach ($s['subs'] as $v) { if ($v > $max_val) { $max_val = $v; } }
}
$point_count = count($timeline);
$step_x = $point_count > 1 ? $plot_width / ($point_count - 1) : $plot_width;
$pt = function ($i, $v) use ($padding, $step_x, $chart_height, $plot_height, $max_val) {
    return round($padding + ($i * $step_x), 1) . ',' . round($chart_height - $padding - (($v / $max_val) * $plot_height), 1);
};
?>
<div class="wppoppop-chart-box" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.05);margin-bottom:24px;">
    <div class="wpp-chart-head">
        <h3 style="margin:0;font-size:15px;font-weight:700;color:#1e293b;"><?php esc_html_e('Performance Timeline', 'wppoppop-pro'); ?></h3>
        <div class="wpp-chart-tools">
            <span class="wpp-chart-key"><i class="wpp-key-solid"></i><?php esc_html_e('Impressions', 'wppoppop-pro'); ?></span>
            <span class="wpp-chart-key"><i class="wpp-key-dash"></i><?php esc_html_e('Leads', 'wppoppop-pro'); ?></span>
            <details class="wpp-pick" id="wpp-chart-pick">
                <summary class="button button-small"><span class="dashicons dashicons-filter" style="font-size:14px;width:14px;height:14px;line-height:18px;"></span> <?php echo esc_html(sprintf(/* translators: number of selected series. */ __('Chart data (%d)', 'wppoppop-pro'), count($sel))); ?></summary>
                <div class="wpp-pick-panel">
                    <label class="wpp-pick-row"><input type="checkbox" class="wpp-chart-cb" value="__all__" <?php checked(in_array('__all__', $sel, true)); ?>> <strong><?php esc_html_e('All popups (total)', 'wppoppop-pro'); ?></strong></label>
                    <div class="wpp-pick-list">
                        <?php foreach ((array) $chart_pool as $cp) : $ct = isset($title_map[$cp->uid]) ? $title_map[$cp->uid] : ['t' => $cp->uid, 'trash' => false]; ?>
                            <label class="wpp-pick-row">
                                <input type="checkbox" class="wpp-chart-cb" value="<?php echo esc_attr($cp->uid); ?>" <?php checked(in_array($cp->uid, $sel, true)); ?>>
                                <span class="wpp-dot" style="background:<?php echo esc_attr(isset($stat_labels[$cp->uid]) ? $stat_labels[$cp->uid] : '#cbd5e1'); ?>"></span>
                                <span class="wpp-pick-name"><?php echo esc_html($ct['t']); ?></span>
                                <?php if ($ct['trash']) : ?><em class="wppoppop-badge-trash"><?php esc_html_e('Trash', 'wppoppop-pro'); ?></em><?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="wpp-pick-foot">
                        <small class="wpp-pick-msg"><?php echo esc_html(sprintf(/* translators: maximum number of chart series. */ __('Max %d series', 'wppoppop-pro'), WpPopPop_Admin_Stats::MAX_SERIES)); ?></small>
                        <button type="button" class="button button-primary button-small" id="wpp-chart-apply"><?php esc_html_e('Apply', 'wppoppop-pro'); ?></button>
                    </div>
                </div>
            </details>
        </div>
    </div>

    <div class="wppoppop-chart-names">
        <?php foreach ($chart_series as $s) : ?>
            <div class="wppoppop-chart-name">
                <span class="wpp-dot" style="background:<?php echo esc_attr($s['color']); ?>"></span>
                <span><?php echo esc_html($s['name']); ?></span>
                <?php if ($s['trash']) : ?><em class="wppoppop-badge-trash"><?php esc_html_e('Trash', 'wppoppop-pro'); ?></em><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="width:100%;overflow-x:auto;">
        <svg viewBox="0 0 <?php echo (int) $chart_width; ?> <?php echo (int) $chart_height; ?>" style="width:100%;height:auto;display:block;">
            <line x1="<?php echo esc_attr( $padding ); ?>" y1="<?php echo esc_attr( $padding ); ?>" x2="<?php echo $chart_width - $padding; ?>" y2="<?php echo esc_attr( $padding ); ?>" stroke="#f1f5f9" stroke-width="1" />
            <line x1="<?php echo esc_attr( $padding ); ?>" y1="<?php echo $padding + ($plot_height / 2); ?>" x2="<?php echo $chart_width - $padding; ?>" y2="<?php echo $padding + ($plot_height / 2); ?>" stroke="#f1f5f9" stroke-width="1" />
            <line x1="<?php echo esc_attr( $padding ); ?>" y1="<?php echo $chart_height - $padding; ?>" x2="<?php echo $chart_width - $padding; ?>" y2="<?php echo $chart_height - $padding; ?>" stroke="#cbd5e1" stroke-width="1" />
            <text x="<?php echo $padding - 10; ?>" y="<?php echo $padding + 4; ?>" font-size="10" fill="#94a3b8" text-anchor="end"><?php echo number_format($max_val); ?></text>
            <text x="<?php echo $padding - 10; ?>" y="<?php echo $chart_height - $padding + 4; ?>" font-size="10" fill="#94a3b8" text-anchor="end">0</text>

            <?php foreach ($chart_series as $si => $s) :
                $pi = []; $ps = [];
                foreach ($s['impr'] as $i => $v) { $pi[] = $pt($i, $v); }
                foreach ($s['subs'] as $i => $v) { $ps[] = $pt($i, $v); }
                $last = !empty($pi) ? explode(',', end($pi)) : [$chart_width - $padding, $chart_height - $padding];
                $label = $s['name'];
                if (function_exists('mb_strimwidth')) { $label = mb_strimwidth($label, 0, 24, '...'); }
                ?>
                <g data-uid="<?php echo esc_attr($s['uid']); ?>">
                    <polyline points="<?php echo esc_attr(implode(' ', $pi)); ?>" fill="none" stroke="<?php echo esc_attr($s['color']); ?>" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    <polyline points="<?php echo esc_attr(implode(' ', $ps)); ?>" fill="none" stroke="<?php echo esc_attr($s['color']); ?>" stroke-width="2" stroke-dasharray="5 4" stroke-linecap="round" stroke-linejoin="round" opacity="0.85" />
                    <text x="<?php echo esc_attr($chart_width - $padding); ?>" y="<?php echo esc_attr(max(12, (float) $last[1] - 6 - ($si % 3) * 10)); ?>" font-size="10" font-weight="700" fill="<?php echo esc_attr($s['color']); ?>" text-anchor="end"><?php echo esc_html($label); ?></text>
                </g>
            <?php endforeach; ?>

            <?php
            $skip = max(1, (int) ceil($point_count / 7));
            $n_idx = 0;
            foreach ($timeline as $day) :
                $x = $padding + ($n_idx * $step_x);
                if ($n_idx % $skip === 0 || $n_idx === $point_count - 1) : ?>
                    <text x="<?php echo esc_attr( round($x, 1) ); ?>" y="<?php echo $chart_height - $padding + 16; ?>" font-size="10" fill="#64748b" text-anchor="middle"><?php echo esc_html($day['date']); ?></text>
                <?php endif;
                $n_idx++;
            endforeach; ?>
        </svg>
    </div>
</div>
