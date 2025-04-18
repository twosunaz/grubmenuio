<?php
/**
 * Module: Services.
 *
 * Delivery/pickup services.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Services module class.
 */
class Orderable_Services {
	/**
	 * Init.
	 */
	public static function run() {
		self::load_classes();
	}

	/**
	 * Load classes for this module.
	 */
	public static function load_classes() {
		$classes = array(
			'services-order' => 'Orderable_Services_Order',
		);

		foreach ( $classes as $file_name => $class_name ) {
			require_once ORDERABLE_MODULES_PATH . 'services/class-' . $file_name . '.php';

			$class_name::run();
		}
	}

	/**
	 * Is pickup method?
	 *
	 * @param string|WC_Shipping_Method $shipping_method
	 *
	 * @return bool
	 */
	public static function is_pickup_method( $shipping_method ) {
		if ( ! $shipping_method ) {
			return false;
		}

		if ( is_numeric( $shipping_method ) ) {
			$shipping_method = Orderable_Location_Zones::get_method_id( $shipping_method );
		}

		if ( ! $shipping_method ) {
			return false;
		}

		if ( ! is_string( $shipping_method ) ) {
			$shipping_method = $shipping_method->get_method_id();
		}

		$explode = explode( ':', $shipping_method );

		return false !== strpos( $explode[0], 'pickup' );
	}

	/**
	 * Get selected service.
	 *
	 * @param bool $label Return the label?
	 *
	 * @return bool|WC_Shipping_Method
	 */
	public static function get_selected_service( $label = true ) {
		if ( empty( WC()->session ) ) {
			return false;
		}

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

		if ( empty( $chosen_methods[0] ) ) {
			return false;
		}

		$chosen_method = $chosen_methods[0];
		$is_pickup     = self::is_pickup_method( $chosen_method );
		$type          = $is_pickup ? 'pickup' : 'delivery';

		if ( ! $label ) {
			return $type;
		}

		return self::get_service_label( $type );
	}

	/**
	 * Get service label.
	 *
	 * @param string $type   pickup|delivery
	 * @param bool   $plural Return the plural label?
	 *
	 * @return bool|string
	 */
	public static function get_service_label( $type, $plural = false ) {
		if ( empty( $type ) ) {
			return false;
		}

		$type = $plural ? $type . '_plural' : $type;

		$labels = apply_filters(
			'orderable_service_labels',
			array(
				'pickup'          => __( 'Pickup', 'orderable' ),
				'delivery'        => __( 'Delivery', 'orderable' ),
				'pickup_plural'   => __( 'Pickups', 'orderable' ),
				'delivery_plural' => __( 'Deliveries', 'orderable' ),
			)
		);

		if ( ! isset( $labels[ $type ] ) ) {
			return false;
		}

		return $labels[ $type ];
	}
}
