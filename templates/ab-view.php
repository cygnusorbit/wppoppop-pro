<?php
if (!defined('ABSPATH')) {
    exit;
}
$bar_title   = __('A/B Campaigns', 'wppoppop-pro');
$bar_icon    = 'dashicons-randomize';
$bar_actions = WPPOPPOP_PRO_PATH . 'templates/ab/bar-actions.php';
?>
<div class="wrap wppoppop-dashboard-wrap wppoppop-ab-wrap" style="width: 100%; padding-right: 30px; max-width: none; box-sizing: border-box;">
    <h1 class="screen-reader-text"><?php esc_html_e('A/B Campaigns', 'wppoppop-pro'); ?></h1>

    <!-- Top Bar (shared with Popups) -->
    <div class="wppoppop-dash" style="margin:0 0 20px;max-width:none;">
        <?php include WPPOPPOP_PATH . 'templates/partials/dash-bar.php'; ?>
    </div>

    <!-- Notice container -->
    <?php include WPPOPPOP_PRO_PATH . 'templates/ab/header.php'; ?>

    <!-- Experiments List Table -->
    <?php include WPPOPPOP_PRO_PATH . 'templates/ab/table.php'; ?>

    <!-- Create Experiment Modal Dialog -->
    <?php include WPPOPPOP_PRO_PATH . 'templates/ab/modal-create.php'; ?>
</div>
