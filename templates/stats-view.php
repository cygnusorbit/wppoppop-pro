<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$range = isset($_GET['range']) ? sanitize_key($_GET['range']) : '7days';

// 1. Fetch Popups & Aggregate Totals
$items_table = $wpdb->prefix . 'wppoppop_items';
// $ranked_popups is built (paginated) in templates/stats/data.php

$total_impr = (int)$wpdb->get_var("SELECT SUM(impressions) FROM {$items_table} WHERE status <> 'trash'");
$total_subs = (int)$wpdb->get_var("SELECT SUM(submissions) FROM {$items_table} WHERE status <> 'trash'");
$total_conf = (int)$wpdb->get_var("SELECT SUM(confirmations) FROM {$items_table} WHERE status <> 'trash'");
$conversion_rate = $total_impr > 0 ? round(($total_subs / $total_impr) * 100, 1) : 0;

// 2. Generate Timeline Points
$days_count = ($range === '30days') ? 30 : (($range === 'all') ? 60 : 7);
$timeline = [];

for ($i = $days_count - 1; $i >= 0; $i--) {
    $date_str = date('Y-m-d', strtotime("-{$i} days"));
    $timeline[$date_str] = [
        'date'        => date('M j', strtotime($date_str)),
        'impressions' => 0,
        'submissions' => 0,
    ];
}

// Populate Submissions by Date
$subs_table = $wpdb->prefix . 'wppoppop_submissions';
$start_date = date('Y-m-d 00:00:00', strtotime("-{$days_count} days"));
$db_counts = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE(created_at) as sub_date, COUNT(id) as count 
     FROM {$subs_table} 
     WHERE created_at >= %s AND popup_uid NOT IN (SELECT uid FROM {$items_table} WHERE status = 'trash') GROUP BY DATE(created_at)",
    $start_date
));

if (!empty($db_counts)) {
    foreach ($db_counts as $row) {
        if (isset($timeline[$row->sub_date])) {
            $timeline[$row->sub_date]['submissions'] = (int)$row->count;
        }
    }
}
/* WPPOPPOP_REAL_DAILY_IMPR: real per-day impressions */
$daily_tbl = WpPopPop_Pro_Stats::table();
if ($wpdb->get_var("SHOW TABLES LIKE '{$daily_tbl}'") === $daily_tbl) {
    $daily_rows = $wpdb->get_results($wpdb->prepare("SELECT stat_date, SUM(impressions) AS n FROM {$daily_tbl} WHERE stat_date >= %s AND popup_uid NOT IN (SELECT uid FROM {$items_table} WHERE status = 'trash') GROUP BY stat_date", gmdate('Y-m-d', strtotime("-{$days_count} days"))));
    foreach ((array) $daily_rows as $dr) {
        if (isset($timeline[$dr->stat_date])) {
            $timeline[$dr->stat_date]['impressions'] = (int) $dr->n;
        }
    }
}
include WPPOPPOP_PRO_PATH . 'templates/stats/data.php';
?>
<div class="wrap wppoppop-stats-wrap">
    <?php
    $bar_title   = __('Statistics', 'wppoppop-pro');
    $bar_icon    = 'dashicons-chart-bar';
    $bar_actions = '';
    ?>
    <h1 class="screen-reader-text"><?php esc_html_e('Statistics', 'wppoppop-pro'); ?></h1>
    <div class="wppoppop-dash" style="margin:0 0 20px;max-width:none;">
        <?php include WPPOPPOP_PATH . 'templates/partials/dash-bar.php'; ?>
    </div>
    <!-- Timeframe Filter & Header -->
    <?php include WPPOPPOP_PRO_PATH . 'templates/stats/header.php'; ?>

    <!-- KPI Totals Summary Cards -->
    <?php include WPPOPPOP_PRO_PATH . 'templates/stats/kpi-summary.php'; ?>

    <!-- Scalable SVG Chart Visualizer -->
    <?php include WPPOPPOP_PRO_PATH . 'templates/stats/chart.php'; ?>

    <!-- Top Performing Campaigns Ranked Table -->
    <?php include WPPOPPOP_PRO_PATH . 'templates/stats/table-top-campaigns.php'; ?>
</div>
