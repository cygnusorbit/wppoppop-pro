<?php
/**
 * Pro Element Color & Background Opacity Bridge.
 *
 * Hooks cleanly into Core WpPopPop filters to enable independent Color Opacity
 * and Background Opacity across all Pro builder elements.
 *
 * @package WpPopPop_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WpPopPop_Pro_Element_Opacity' ) ) {

	class WpPopPop_Pro_Element_Opacity {

		public static function init() {
			add_filter( 'wppoppop_element_opacity_supported_types', array( __CLASS__, 'register_pro_element_types' ), 10, 1 );
			add_filter( 'wppoppop_render_element_html', array( __CLASS__, 'render_pro_element_opacity' ), 25, 2 );
		}

		/**
		 * Register Pro builder element slugs for opacity support.
		 *
		 * @param array $types Supported element types.
		 * @return array
		 */
		public static function register_pro_element_types( $types ) {
			if ( ! is_array( $types ) ) {
				$types = array();
			}
			$pro_types = array(
				'progress_bar',
				'image_select',
				'title_select',
				'star_rating',
			);
			foreach ( $pro_types as $pt ) {
				if ( ! in_array( $pt, $types, true ) ) {
					$types[] = $pt;
				}
			}
			return $types;
		}

		/**
		 * Apply RGBA color & background opacity to Pro inner elements (stars, progress fill, option cards).
		 *
		 * @param string $html  Rendered HTML.
		 * @param array  $layer Layer configuration.
		 * @return string
		 */
		public static function render_pro_element_opacity( $html, $layer ) {
			if ( ! is_string( $html ) || '' === $html || ! is_array( $layer ) ) {
				return $html;
			}
			if ( ! class_exists( 'WpPopPop_Element_Opacity' ) ) {
				return $html;
			}

			$type = isset( $layer['type'] ) ? sanitize_key( $layer['type'] ) : '';
			if ( ! in_array( $type, array( 'progress_bar', 'image_select', 'title_select', 'star_rating' ), true ) ) {
				return $html;
			}

			$layer    = WpPopPop_Element_Opacity::sanitize_layer_opacity( $layer, $type );
			$color    = isset( $layer['color'] ) ? $layer['color'] : '';
			$bg_color = isset( $layer['background_color'] ) ? $layer['background_color'] : '';

			if ( '' !== $color && 'progress_bar' === $type ) {
				$rgba_fill = esc_attr( WpPopPop_Element_Opacity::color_with_opacity( $color, $layer['color_opacity'] ) );
				$html      = preg_replace(
					'/(class=["\'][^"\']*wppoppop-progress-fill[^"\']*["\'][^>]*?)style=(["\'])(.*?)\2/i',
					'$1style=$2$3;background-color:' . $rgba_fill . ';$2',
					$html
				);
			}

			if ( '' !== $bg_color && in_array( $type, array( 'image_select', 'title_select' ), true ) ) {
				$rgba_card = esc_attr( WpPopPop_Element_Opacity::color_with_opacity( $bg_color, $layer['background_opacity'] ) );
				$html      = preg_replace(
					'/(class=["\'][^"\']*wppoppop-(?:image|title)-select-item[^"\']*["\'][^>]*?)style=(["\'])(.*?)\2/i',
					'$1style=$2$3;background-color:' . $rgba_card . ';$2',
					$html
				);
			}

			return $html;
		}
	}

	WpPopPop_Pro_Element_Opacity::init();
}
