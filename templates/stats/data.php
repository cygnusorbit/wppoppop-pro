<?php
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
if (!class_exists('WpPopPop_Admin_Stats')) {
    require_once WPPOPPOP_PRO_PATH . 'includes/stats/class-admin-stats.php';
}
$items_table = $wpdb->prefix . 'wppoppop_items';
$subs_table  = $wpdb->prefix . 'wppoppop_submissions';

$stats_prefs = WpPopPop_Admin_Stats::prefs();
$stat_labels = WpPopPop_Admin_Stats::labels();
$has_device  = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$subs_table} LIKE %s", 'device'));

/* ---- pagination (default 5) ---- */
$per_opts = [5, 10, 25, 50];
$per = isset($_GET['per']) ? (int) $_GET['per'] : 5;
if (!in_array($per, $per_opts, true)) { $per = 5; }
$total_popups = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$items_table} WHERE status <> 'trash'");
$pages = max(1, (int) ceil($total_popups / $per));
$pg = isset($_GET['pg']) ? max(1, (int) $_GET['pg']) : 1;
$pg = min($pg, $pages);
$ranked_popups = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$items_table} WHERE status <> 'trash' ORDER BY submissions DESC, impressions DESC, id DESC LIMIT %d OFFSET %d",
    $per, ($pg - 1) * $per
));

/* ---- country / device per popup on this page ---- */
$pop_geo = [];
$pop_dev = [];
$page_uids = wp_list_pluck((array) $ranked_popups, 'uid');
if (!empty($page_uids)) {
    $ph = implode(',', array_fill(0, count($page_uids), '%s'));
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT popup_uid, country_code AS k, COUNT(*) AS c FROM {$subs_table} WHERE popup_uid IN ($ph) AND country_code <> '' GROUP BY popup_uid, country_code ORDER BY c DESC",
        $page_uids
    ));
    foreach ((array) $rows as $r) {
        if (!isset($pop_geo[$r->popup_uid])) { $pop_geo[$r->popup_uid] = []; }
        if (count($pop_geo[$r->popup_uid]) < 3) { $pop_geo[$r->popup_uid][] = ['k' => strtoupper($r->k), 'c' => (int) $r->c]; }
    }
    if ($has_device) {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT popup_uid, device AS k, COUNT(*) AS c FROM {$subs_table} WHERE popup_uid IN ($ph) AND device <> '' GROUP BY popup_uid, device ORDER BY c DESC",
            $page_uids
        ));
        foreach ((array) $rows as $r) {
            if (!isset($pop_dev[$r->popup_uid])) { $pop_dev[$r->popup_uid] = []; }
            if (count($pop_dev[$r->popup_uid]) < 3) { $pop_dev[$r->popup_uid][] = ['k' => strtolower($r->k), 'c' => (int) $r->c]; }
        }
    }
}

/* ---- chart series (selected popups) ---- */
$chart_pool = $wpdb->get_results("SELECT uid, title, status FROM {$items_table} WHERE status <> 'trash' ORDER BY id DESC LIMIT 200");
$valid_uids = ['__all__'];
$title_map  = [];
foreach ((array) $chart_pool as $cp) {
    $valid_uids[] = $cp->uid;
    $title_map[$cp->uid] = [
        't'     => ($cp->title !== '' ? $cp->title : __('Untitled Popup', 'wppoppop-pro')),
        'trash' => ($cp->status === 'trash'),
    ];
}
$sel = $stats_prefs['chart_uids'];
if ($sel === null) { $sel = ['__all__']; }
$sel = array_values(array_intersect($sel, $valid_uids));
if (empty($sel)) { $sel = ['__all__']; }
$sel = array_slice($sel, 0, WpPopPop_Admin_Stats::MAX_SERIES);

$dates = array_keys($timeline);
$pick  = array_values(array_diff($sel, ['__all__']));
$per_subs = [];
$per_impr = [];
if (!empty($pick)) {
    $ph2 = implode(',', array_fill(0, count($pick), '%s'));
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT popup_uid, DATE(created_at) AS d, COUNT(id) AS c FROM {$subs_table} WHERE created_at >= %s AND popup_uid IN ($ph2) GROUP BY popup_uid, DATE(created_at)",
        array_merge([$start_date], $pick)
    ));
    foreach ((array) $rows as $r) { $per_subs[$r->popup_uid][$r->d] = (int) $r->c; }
    if ($wpdb->get_var("SHOW TABLES LIKE '{$daily_tbl}'") === $daily_tbl) {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT popup_uid, stat_date, impressions FROM {$daily_tbl} WHERE stat_date >= %s AND popup_uid IN ($ph2)",
            array_merge([gmdate('Y-m-d', strtotime("-{$days_count} days"))], $pick)
        ));
        foreach ((array) $rows as $r) { $per_impr[$r->popup_uid][$r->stat_date] = (int) $r->impressions; }
    }
}

$palette = WpPopPop_Admin_Stats::palette();
$chart_series = [];
$ci = 0;
foreach ($sel as $uid) {
    $impr = []; $subs = [];
    if ($uid === '__all__') {
        foreach ($timeline as $day) { $impr[] = (int) $day['impressions']; $subs[] = (int) $day['submissions']; }
        $name = __('All popups (total)', 'wppoppop-pro'); $trash = false; $color = '#64748b';
    } else {
        foreach ($dates as $d) {
            $impr[] = isset($per_impr[$uid][$d]) ? $per_impr[$uid][$d] : 0;
            $subs[] = isset($per_subs[$uid][$d]) ? $per_subs[$uid][$d] : 0;
        }
        $name  = $title_map[$uid]['t'];
        $trash = $title_map[$uid]['trash'];
        $color = isset($stat_labels[$uid]) ? $stat_labels[$uid] : $palette[$ci % count($palette)];
        $ci++;
    }
    $chart_series[] = ['uid' => $uid, 'name' => $name, 'trash' => $trash, 'color' => $color, 'impr' => $impr, 'subs' => $subs];
}
