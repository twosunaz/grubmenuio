<?php
/**
 * Compatiblity with WooPayments plugin.
 *
 * @see https://woocommerce.com/payments/
 *
 * @package Orderable/Classes
 */

/**
 * Compatiblity with WooPayments plugin.
 */
class Orderable_Pro_Compat_WooCommerce_Payments {
	/**
	 * Initialize.
	 */
	public static function run() {
		if ( ! defined( 'WCPAY_VERSION_NUMBER' ) ) {
			return;
		}

		add_action( 'wp_print_footer_scripts', [ self::class, 'set_has_block_setting_to_false' ] );

	}

	/**
	 * Set `wcpayExpressCheckoutParams.has_block` to `false` when
	 * `Enable Custom Checkout` is activated.
	 *
	 * Since Orderable replaces the Checkout block for the shortcode,
	 * it's necessary to set `wcpayExpressCheckoutParams.has_block` to false.
	 *
	 * @return void
	 */
	public static function set_has_block_setting_to_false() {
		if ( ! Orderable_Checkout_Pro::is_checkout_page() || ! Orderable_Checkout_Pro_Settings::is_override_checkout() ) {
			return;
		}

		?>
		<script>
			(function(){
				if (typeof wcpayExpressCheckoutParams === 'undefined') {
					return;
				}

				wcpayExpressCheckoutParams.has_block = false;
			})()
		</script>
		<?php
	}
}
