<?php
/**
 * Compatiblity with Flux Checkout for WooCommerce plugin.
 *
 * @see https://iconicwp.com/products/flux-checkout-for-woocommerce/
 *
 * @package Orderable/Classes
 */

/**
 * Compatiblity with Flux Checkout for WooCommerce plugin.
 */
class Orderable_Compat_Flux_Checkout {
	/**
	 * Initialize.
	 */
	public static function run() {
		if ( ! defined( 'ICONIC_FLUX_VERSION' ) ) {
			return;
		}

		if ( wp_is_mobile() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'init', [ __CLASS__, 'update_timing_fields_position' ], 25 );
	}

	/**
	 * Enqueue assets
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		if ( ! is_checkout() ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'orderable-compat-flux-checkout-timings',
			ORDERABLE_URL . 'inc/modules/timings/assets/frontend/css/compat-flux-checkout-timings' . $suffix . '.css',
			[],
			ORDERABLE_VERSION
		);
	}

	/**
	 * Update the timing fields position
	 *
	 * @return void
	 */
	public static function update_timing_fields_position() {
		if ( ! method_exists( 'Iconic_Flux_Helpers', 'is_modern_theme' ) || ! Iconic_Flux_Helpers::is_modern_theme() ) {
			return;
		}

		remove_action( 'woocommerce_review_order_after_shipping', [ 'Orderable_Timings_Checkout', 'output_timing_fields' ] );
		add_action( 'flux_checkout_order_review', [ __CLASS__, 'render_timing_fields' ], 5 );
	}

	/**
	 * Render timing fields
	 *
	 * @return void
	 */
	public static function render_timing_fields() {
		?>
		<table class="orderable-compat-flux-checkout">
			<?php Orderable_Timings_Checkout::output_timing_fields(); ?>
		</table>
		<?php
	}
}
