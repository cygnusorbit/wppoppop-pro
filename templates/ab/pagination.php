<?php
/* WPPOPPOP_AB_FILTERS */
if (!defined('ABSPATH')) {
    exit;
}
$ab_pos   = isset($ab_pos) ? $ab_pos : 'top';
$ab_pages = (int) $ab_state['pages'];
$ab_paged = (int) $ab_state['paged'];
$ab_nav   = function ($target, $label, $glyph, $enabled, $class) use ($ab_state) {
    if (!$enabled) {
        return '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">' . $glyph . '</span>';
    }
    return sprintf(
        '<a class="%1$s button" href="%2$s"><span class="screen-reader-text">%3$s</span><span aria-hidden="true">%4$s</span></a>',
        esc_attr($class),
        esc_url(WpPopPop_Admin_AB_Query::url($ab_state, ['paged' => $target])),
        esc_html($label),
        $glyph
    );
};
?>
<div class="tablenav <?php echo esc_attr($ab_pos); ?>">
    <div class="tablenav-pages<?php echo $ab_pages > 1 ? '' : ' one-page'; ?>">
        <span class="displaying-num"><?php echo esc_html(sprintf(/* translators: %s: number of A/B campaigns */
            _n('%s item', '%s items', (int) $ab_state['total'], 'wppoppop-pro'), number_format_i18n((int) $ab_state['total']))); ?></span>
        <?php if ($ab_pages > 1) : ?>
            <span class="pagination-links">
                <?php
                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $ab_nav(1, __('First page', 'wppoppop-pro'), '&laquo;', $ab_paged > 1, 'first-page');
                echo $ab_nav($ab_paged - 1, __('Previous page', 'wppoppop-pro'), '&lsaquo;', $ab_paged > 1, 'prev-page');
                ?>
                <span class="paging-input"><span class="tablenav-paging-text"><?php echo esc_html(sprintf(/* translators: 1: current page, 2: total pages */ __('%1$s of %2$s', 'wppoppop-pro'), number_format_i18n($ab_paged), number_format_i18n($ab_pages))); ?></span></span>
                <?php
                echo $ab_nav($ab_paged + 1, __('Next page', 'wppoppop-pro'), '&rsaquo;', $ab_paged < $ab_pages, 'next-page');
                echo $ab_nav($ab_pages, __('Last page', 'wppoppop-pro'), '&raquo;', $ab_paged < $ab_pages, 'last-page');
                // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>
            </span>
        <?php endif; ?>
    </div>
    <br class="clear">
</div>
