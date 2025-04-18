<?php
/**
 * Shortcodes
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcodes class.
 */
class Orderable_Shortcodes {

	/**
	 * Init.
	 */
	public static function run() {
		add_shortcode( 'orderable_add_to_cart', array( __CLASS__, 'orderable_add_to_cart' ) );
	}

	/**
	 * Add to cart shortcode.
	 *
	 * @param array $args Shortcode arguments.
	 *
	 * @return stirng
	 */
	public static function orderable_add_to_cart( $args ) {
		$defaults = array(
			'product_id' => -1,
		);

		$args    = wp_parse_args( $args, $defaults );
		$product = $args['product_id'];

		if ( $product < 0 ) {
			global $product;
		}

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( empty( $product ) ) {
			return;
		}

		ob_start();
		?>
		<div class="orderable-product__actions-button">
			<?php
			 	// Phpcs:ignore -- WordPress.Security.EscapeOutput.OutputNotEscaped.
				echo Orderable_Products::get_add_to_cart_button( $product, 'orderable-product__add-to-order' );
			?>
		</div>
		<?php

		return ob_get_clean();
	}
}
