<?php
/**
 * Module: Checkout.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checkout module class.
 */
class Orderable_Checkout {
	/**
	 * Init.
	 */
	public static function run() {
		add_filter( 'wpsf_register_settings_orderable', array( __CLASS__, 'register_settings' ) );

		// @phpstan-ignore-next-line
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'is_order_date_valid' ), 5 );
	}

	/**
	 * Register settings.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function register_settings( $settings = array() ) {
		$settings['tabs'][] = array(
			'id'       => 'checkout',
			'title'    => __( 'Checkout Settings', 'orderable-pro' ),
			'priority' => 20,
		);

		$settings['sections'][] = array(
			'tab_id'              => 'checkout',
			'section_id'          => 'general',
			'section_title'       => __( 'Checkout Settings', 'orderable' ),
			'section_description' => '',
			'section_order'       => 0,
			'fields'              => array(
				array(
					'id'       => 'pro',
					'title'    => __( 'Enable Custom Checkout', 'orderable' ),
					'subtitle' => __( "When enabled, your theme's checkout will be replaced by Orderable's optimized checkout experience.", 'orderable' ),
					'type'     => 'custom',
					'output'   => Orderable_Helpers::get_pro_button( 'checkout' ),
				),
			),
		);

		return $settings;
	}

	/**
	 * Check if the order date is valid.
	 *
	 * The order date is sent via POST.
	 *
	 * @return bool
	 */
	public static function is_order_date_valid() {
		// phpcs:ignore WordPress.Security.NonceVerification
		$order_date = empty( $_POST['orderable_order_date'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['orderable_order_date'] ) );

		if ( empty( $order_date ) || 'asap' === $order_date ) {
			return true;
		}

		if ( self::is_order_date_past( $order_date ) ) {
			wc_add_notice( __( 'Sorry, the date you selected is no longer available. Please choose another.', 'orderable' ), 'error' );

			return false;
		}

		return true;
	}

	/**
	 * Check if the given order date is in the past.
	 *
	 * @param int $order_date The order date to be checked in timestamp format.
	 * @return bool Whether the order date is in the past or not.
	 */
	protected static function is_order_date_past( $order_date ) {
		$now = new DateTime( 'now', wp_timezone() );
		$now->setTime( 0, 0, 0 );

		return $order_date < $now->getTimestamp();
	}
}
