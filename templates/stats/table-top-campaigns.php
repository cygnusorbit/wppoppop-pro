<?php
if (!defined('ABSPATH')) {
    exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$all_cols  = WpPopPop_Admin_Stats::columns();
$on_cols   = $stats_prefs['columns'];
$order     = isset($stats_prefs['order']) ? $stats_prefs['order'] : array_keys($all_cols);
$cc = function ($k) use ($on_cols) {
    return 'wpp-col wpp-col-' . $k . (in_array($k, $on_cols, true) ? '' : ' wpp-col-off');
};
$dev_names = ['desktop' => __('Desktop', 'wppoppop-pro'), 'mobile' => __('Mobile', 'wppoppop-pro'), 'tablet' => __('Tablet', 'wppoppop-pro')];
$page_links = paginate_links([
    'base'      => add_query_arg('pg', '%#%'),
    'format'    => '',
    'current'   => $pg,
    'total'     => $pages,
    'type'      => 'array',
    'prev_text' => '&lsaquo;',
    'next_text' => '&rsaquo;',
]);
?>
<div class="wppoppop-card-table" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
    <div class="wpp-table-head">
        <h3 style="margin:0;font-size:15px;font-weight:700;color:#1e293b;"><?php esc_html_e('Top Performing Campaigns', 'wppoppop-pro'); ?></h3>
        <div class="wpp-table-tools">
            <form method="get" class="wpp-per-form">
                <input type="hidden" name="page" value="wppoppop-stats">
                <input type="hidden" name="range" value="<?php echo esc_attr($range); ?>">
                <label><?php esc_html_e('Per page', 'wppoppop-pro'); ?>
                    <select name="per" class="wpp-per">
                        <?php foreach ($per_opts as $o) : ?><option value="<?php echo (int) $o; ?>" <?php selected($per, $o); ?>><?php echo (int) $o; ?></option><?php endforeach; ?>
                    </select>
                </label>
            </form>
            <details class="wpp-pick" id="wpp-cols-pick">
                <summary class="button button-small"><span class="dashicons dashicons-columns" style="font-size:14px;width:14px;height:14px;line-height:18px;"></span> <?php esc_html_e('Columns', 'wppoppop-pro'); ?></summary>
                <div class="wpp-pick-panel">
                    <?php foreach ($order as $k) : $label = $all_cols[$k]; ?>
                        <label class="wpp-pick-row" data-col="<?php echo esc_attr($k); ?>" draggable="true" title="<?php echo esc_attr__('Drag to reposition', 'wppoppop-pro'); ?>">
                            <span class="dashicons dashicons-menu wpp-grip" aria-hidden="true"></span>
                            <input type="checkbox" class="wpp-col-cb" value="<?php echo esc_attr($k); ?>" <?php checked(in_array($k, $on_cols, true)); ?> <?php disabled($k === 'popup'); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </details>
        </div>
    </div>

    <div style="overflow-x:auto;">
    <table class="wp-list-table widefat striped table-view-list" style="border:none;" id="wpp-stats-table">
        <thead>
            <tr>
                <?php foreach ($order as $k) :
                    $label = $all_cols[$k];
                    $al = in_array($k, ['impressions', 'leads', 'conversion'], true) ? 'right' : (in_array($k, ['rank', 'status'], true) ? 'center' : 'left'); ?>
                    <th class="<?php echo esc_attr($cc($k)); ?>" data-col="<?php echo esc_attr($k); ?>" draggable="true" title="<?php echo esc_attr__('Drag to reposition', 'wppoppop-pro'); ?>" style="text-align:<?php echo esc_html( $al ); ?>;font-weight:600;"><?php echo esc_html($label); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ranked_popups)) : ?>
                <tr><td colspan="<?php echo (int) count($all_cols); ?>" style="text-align:center;padding:30px 10px;color:#64748b;"><?php esc_html_e('No campaign records available to rank.', 'wppoppop-pro'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($ranked_popups as $i => $p) :
                    $p_impr = intval($p->impressions ?? 0);
                    $p_subs = intval($p->submissions ?? 0);
                    $p_cr   = $p_impr > 0 ? round(($p_subs / $p_impr) * 100, 1) : 0;
                    $edit_url = admin_url('admin.php?page=wppoppop-builder&uid=' . $p->uid);
                    $is_trashed = (isset($p->status) && $p->status === 'trash');
                    $lbl = isset($stat_labels[$p->uid]) ? $stat_labels[$p->uid] : '';
                    $rank = (($pg - 1) * $per) + $i + 1;
                    $wpp_cells = [];
                    $st_style = $is_trashed ? 'background:#fee2e2;color:#991b1b;' : (in_array($p->status, ['publish', 'published'], true) ? 'background:#dcfce7;color:#166534;' : 'background:#e2e8f0;color:#475569;');
                    ?>
                    <tr class="<?php echo $is_trashed ? 'is-trashed' : ''; ?>" data-uid="<?php echo esc_attr($p->uid); ?>">
                        <?php ob_start(); ?><td class="<?php echo esc_attr($cc('rank')); ?>" style="text-align:center;font-weight:700;color:#64748b;">#<?php echo (int) $rank; ?></td><?php $wpp_cells['rank'] = ob_get_clean(); ?>
                        <?php ob_start(); ?><td class="<?php echo esc_attr($cc('popup')); ?>">
                            <span class="wpp-dot wpp-name-dot" data-uid="<?php echo esc_attr($p->uid); ?>" style="background:<?php echo esc_attr($lbl ? $lbl : 'transparent'); ?>;<?php echo $lbl ? '' : 'display:none;'; ?>"></span>
                            <strong><a href="<?php echo esc_url($edit_url); ?>" style="color:#1e293b;text-decoration:none;font-size:14px;"><?php echo esc_html($p->title); ?></a></strong><?php if ($is_trashed) : ?><span class="wppoppop-badge-trash"><?php esc_html_e('Trash', 'wppoppop-pro'); ?></span><?php endif; ?>
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px;"><code><?php echo esc_html($p->uid); ?></code></div>
                        </td><?php $wpp_cells['popup'] = ob_get_clean(); ?>
                        <?php ob_start(); ?><td class="<?php echo esc_attr($cc('label')); ?>">
                            <span class="wpp-lbl <?php echo $lbl ? 'has-label' : 'is-empty'; ?>" data-uid="<?php echo esc_attr($p->uid); ?>">
                                <input type="color" class="wpp-lbl-input" value="<?php echo esc_attr($lbl ? $lbl : '#94a3b8'); ?>" aria-label="<?php echo esc_attr__('Label color', 'wppoppop-pro'); ?>" title="<?php echo esc_attr__('Pick a label color', 'wppoppop-pro'); ?>">
                                <button type="button" class="wpp-lbl-del" title="<?php echo esc_attr__('Delete label', 'wppoppop-pro'); ?>" <?php echo $lbl ? '' : 'hidden'; ?>>&times;</button>
                            </span>
                        </td><?php $wpp_cells['label'] = ob_get_clean(); ?>
                        <?php ob_start(); ?><td class="<?php echo esc_attr($cc('country')); ?>">
                            <?php if (!empty($pop_geo[$p->uid])) : foreach ($pop_geo[$p->uid] as $g) : ?>
                                <span class="wpp-chip"><?php echo esc_html($g['k']); ?> <small><?php echo (int) $g['c']; ?></small></span>
                            <?php endforeach; else : ?><span style="color:#94a3b8;">&mdash;</span><?php endif; ?>
                        </td><?php $wpp_cells['country'] = ob_get_clean(); ?>
                        <?php ob_start(); ?><td class="<?php echo esc_attr($cc('device')); ?>">
                            <?php if (!empty($pop_dev[$p->uid])) : foreach ($pop_dev[$p->uid] as $d) : ?>
                                <span class="wpp-chip"><?php echo esc_html(isset($dev_names[$d['k']]) ? $dev_names[$d['k']] : ucfirst($d['k'])); ?> <small><?php echo (int) $d['c']; ?></small></span>
                            <?php endforeach; else : ?><span style="color:#94a3b8;">&mdash;</span><?php endif; ?>
                        </td><?php $wpp_cells['device'] = ob_get_clean(); ?>
                        <?php ob_start(); ?><td class="<?php echo esc_attr($cc('impressions')); ?>" style="text-align:right;font-weight:600;color:#1e293b;"><?php echo number_format($p_impr); ?></td><?php $wpp_cells['impressions'] = ob_get_clean(); ?>
                        <?php ob_start(); ?><td class="<?php echo esc_attr($cc('leads')); ?>" style="text-align:right;font-weight:600;color:#10b981;"><?php echo number_format($p_subs); ?></td><?php $wpp_cells['leads'] = ob_get_clean(); ?>
                        <?php ob_start(); ?><td class="<?php echo esc_attr($cc('conversion')); ?>" style="text-align:right;">
                            <div style="display:inline-flex;align-items:center;gap:6px;">
                                <strong style="color:#f59e0b;"><?php echo esc_html($p_cr); ?>%</strong>
                                <div style="width:50px;height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;"><div style="width:<?php echo esc_attr( (string) min( 100, $p_cr * 2 ) ); ?>%;height:100%;background:#f59e0b;"></div></div>
                            </div>
                        </td><?php $wpp_cells['conversion'] = ob_get_clean(); ?>
                        <?php ob_start(); ?><td class="<?php echo esc_attr($cc('status')); ?>" style="text-align:center;">
                            <span class="badge" style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;<?php echo esc_attr( $st_style ); ?>"><?php echo esc_html(ucfirst($p->status)); ?></span>
                        </td><?php $wpp_cells['status'] = ob_get_clean(); ?>
                        <?php foreach ($order as $wpp_k) { echo isset($wpp_cells[$wpp_k]) ? $wpp_cells[$wpp_k] : ''; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cells are escaped where built ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <div class="wpp-table-foot">
        <span class="wpp-foot-count"><?php echo esc_html(sprintf(/* translators: 1: total popups, 2: current page, 3: total pages. */ __('%1$d popups - page %2$d of %3$d', 'wppoppop-pro'), $total_popups, $pg, $pages)); ?></span>
        <?php if (!empty($page_links)) : ?>
            <span class="wpp-pager"><?php echo wp_kses_post( implode( "", array_map(function ($l) { return '<span class="wpp-pg">' . $l . '</span>'; }, $page_links))); ?></span>
        <?php endif; ?>
    </div>
</div>