<?php if (!defined('ABSPATH')) exit; ?>
<?php if (WpPopPop_Pro_Elements::available('progress')) : ?>
<button type="button" class="wppoppop-ribbon-btn wppoppop-ribbon-tool wppoppop-draggable-tool" data-type="progress" title="<?php esc_attr_e('8. Progress Bar (progress)', 'wppoppop-pro'); ?>"><span class="dashicons dashicons-chart-bar"></span><span class="btn-label"><?php esc_html_e('Progress', 'wppoppop-pro'); ?></span></button>
<?php endif; ?>
<?php if (WpPopPop_Pro_Elements::available('imageselect')) : ?>
<button type="button" class="wppoppop-ribbon-btn wppoppop-ribbon-tool wppoppop-draggable-tool" data-type="imageselect" title="<?php esc_attr_e('19. Image Select (imageselect)', 'wppoppop-pro'); ?>"><span class="dashicons dashicons-images-alt2"></span><span class="btn-label"><?php esc_html_e('ImgSel', 'wppoppop-pro'); ?></span></button>
<?php endif; ?>
<?php if (WpPopPop_Pro_Elements::available('tile')) : ?>
<button type="button" class="wppoppop-ribbon-btn wppoppop-ribbon-tool wppoppop-draggable-tool" data-type="tile" title="<?php esc_attr_e('20. Tile (tile)', 'wppoppop-pro'); ?>"><span class="dashicons dashicons-grid-view"></span><span class="btn-label"><?php esc_html_e('Tile', 'wppoppop-pro'); ?></span></button>
<?php endif; ?>
<?php if (WpPopPop_Pro_Elements::available('rating')) : ?>
<button type="button" class="wppoppop-ribbon-btn wppoppop-ribbon-tool wppoppop-draggable-tool" data-type="rating" title="<?php esc_attr_e('25. Rating (rating)', 'wppoppop-pro'); ?>"><span class="dashicons dashicons-star-half"></span><span class="btn-label"><?php esc_html_e('Rating', 'wppoppop-pro'); ?></span></button>
<?php endif; ?>
