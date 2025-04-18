<?php
/**
 * Compatiblity with Astra Pro plugin.
 *
 * @see https://wpastra.com/
 *
 * @package Orderable/Classes
 */

/**
 * Compatiblity with Astra Pro plugin.
 */
class Orderable_Pro_Compat_Astra_Pro {
	/**
	 * Initialize.
	 */
	public static function run() {
		if ( ! defined( 'ASTRA_EXT_VER' ) ) {
			return;
		}

		add_filter( 'astra_get_option_modern-ecommerce-setup', [ __CLASS__, 'disable_modern_checkout_layout' ], 50 );
	}

	/**
	 * Disable `Modern` checkout layout option.
	 *
	 * Set to the default option in
	 * WooCommerce -> Checkout -> Checkout Layout.
	 *
	 * @param bool $modern_ecommerce_setup_enabled Whether if modern layout is enabled.
	 * @return bool
	 */
	public static function disable_modern_checkout_layout( $modern_ecommerce_setup_enabled ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $modern_ecommerce_setup_enabled;
		}

		if ( ! $modern_ecommerce_setup_enabled ) {
			return $modern_ecommerce_setup_enabled;
		}

		if ( ! Orderable_Checkout_Pro_Settings::is_override_checkout() ) {
			return $modern_ecommerce_setup_enabled;
		}

		return false;
	}
}
