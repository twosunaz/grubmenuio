<?php
/**
 * Iconic_Flux_Compat_Germanized.
 *
 * Compatibility with Germanized.
 *
 * @package Iconic_Flux
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Iconic_Flux_Compat_Germanized' ) ) {
	return;
}

/**
 * Iconic_Flux_Compat_Germanized.
 *
 * @class    Iconic_Flux_Compat_Flatsome.
 * @version  2.0.0.0
 * @package  Iconic_Flux
 */
class Iconic_Flux_Compat_Germanized {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'compat_germanized' ) );
	}

	/**
	 * Germanized compatibility.
	 */
	public static function compat_germanized() {
		if ( ! class_exists( 'WooCommerce_Germanized' ) ) {
			return;
		}

		remove_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_back_to_cart' );
		add_filter( 'woocommerce_gzd_allow_disabling_checkout_adjustments', '__return_true' );
		add_action( 'wp_footer', array( __CLASS__, 'add_custom_css' ) );
		add_filter( 'option_woocommerce_gzd_display_checkout_thumbnails', array( __CLASS__, 'disable_checkout_thumbnails_modifications' ) );

		self::fix_double_payment_section();
	}

	/**
	 * Fix double Payment section issue.
	 *
	 * @return void
	 */
	public static function fix_double_payment_section() {
		$priorities = WC_GZD_Hook_Priorities::instance()->priorities;

		global $wp_filter;

		if ( ! empty( $priorities['woocommerce_checkout_order_review']['woocommerce_checkout_payment'] ) ) {
			remove_filter( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', $priorities['woocommerce_checkout_order_review']['woocommerce_checkout_payment'] );
		}
	}

	/**
	 * Add custom CSS.
	 *
	 * @return void
	 */
	public static function add_custom_css() {
		if ( ! is_checkout() ) {
			return;
		}

		?>
		<!-- Compatiblity between Flux checkout and Germanized -->
		<style id="flux-compat-germanized">
			body.woocommerce-checkout input[name="terms"] {
				display: none !important;
			}
		</style>
		<?php
	}

	/**
	 * Disable checkout thumbnails modifications.
	 *
	 * @param string $value Option value.
	 *
	 * @return string
	 */
	public static function disable_checkout_thumbnails_modifications( $value ) {
		return 'no';
	}
}
