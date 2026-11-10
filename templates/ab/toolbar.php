<?php
/* WPPOPPOP_AB_FILTERS */
if (!defined('ABSPATH')) {
    exit;
}
$ab_views = [
    'all'      => __('All', 'wppoppop-pro'),
    'active'   => __('Active', 'wppoppop-pro'),
    'inactive' => __('Inactive', 'wppoppop-pro'),
    'trash'    => __('Trash', 'wppoppop-pro'),
];
?>
<div class="wppoppop-list-toolbar wppoppop-ab-toolbar">
    <ul class="subsubsub">
        <?php foreach ($ab_views as $ab_key => $ab_label) :
            $ab_is_cur = ($ab_state['status'] === $ab_key);
            ?>
            <li class="<?php echo esc_attr($ab_key); ?>"><a href="<?php echo esc_url(WpPopPop_Admin_AB_Query::url($ab_state, ['status' => $ab_key])); ?>" class="wppoppop-view-btn<?php echo $ab_is_cur ? ' current' : ''; ?>"<?php echo $ab_is_cur ? ' aria-current="page"' : ''; ?>><?php echo esc_html($ab_label); ?> <span class="count">(<?php echo (int) $ab_state['counts'][$ab_key]; ?>)</span></a></li>
        <?php endforeach; ?>
    </ul>
    <form method="get" class="search-box" action="<?php echo esc_url(admin_url('admin.php')); ?>" role="search">
        <input type="hidden" name="page" value="wppoppop-ab" />
        <?php if ($ab_state['status'] !== 'all') : ?>
            <input type="hidden" name="status" value="<?php echo esc_attr($ab_state['status']); ?>" />
        <?php endif; ?>
        <label class="screen-reader-text" for="wppoppop-ab-search-input"><?php esc_html_e('Search campaigns:', 'wppoppop-pro'); ?></label>
        <input type="search" id="wppoppop-ab-search-input" name="s" value="<?php echo esc_attr($ab_state['search']); ?>" placeholder="<?php esc_attr_e('Title or UID', 'wppoppop-pro'); ?>" />
        <input type="submit" class="button" value="<?php esc_attr_e('Search Campaigns', 'wppoppop-pro'); ?>" />
        <?php if ($ab_state['search'] !== '') : ?>
            <a class="wppoppop-ab-search-clear" href="<?php echo esc_url(WpPopPop_Admin_AB_Query::url($ab_state, ['s' => ''])); ?>"><?php esc_html_e('Clear', 'wppoppop-pro'); ?></a>
        <?php endif; ?>
    </form>
</div>
