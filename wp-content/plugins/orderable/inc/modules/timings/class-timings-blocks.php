<?php
/**
 * Module: Timings Blocks.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Layouts blocks class.
 */
class Orderable_Timings_Blocks {
	/**
	 * Init.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );

		add_filter( 'orderable_upcoming_open_hours', array( __CLASS__, 'check_services_enabled' ), 20, 2 );
	}

	/**
	 * Register blocks.
	 */
	public static function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'orderable-timings',
			ORDERABLE_URL . 'inc/modules/timings/assets/admin/js/block-timings.js',
			array(
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-components',
				'wp-editor',
			),
			ORDERABLE_VERSION,
			array(
				'in_footer' => false,
			)
		);

		$suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$suffix_css = ( is_rtl() ? '-rtl' : '' ) . $suffix;

		wp_enqueue_style( 'orderable-timings-admin', ORDERABLE_URL . 'inc/modules/timings/assets/admin/css/timings' . $suffix_css . '.css', array(), ORDERABLE_VERSION );

		$locations = Orderable_Location::store_has_multi_locations() ? Orderable_Multi_Location_Pro_Helper::get_all_locations() : array();

		$locations = array_filter(
			array_map(
				function( $location ) {
					if ( empty( $location->location_data['location_id'] ) ) {
						return false;
					}

					return array(
						'label' => sprintf(
							// translators: %1$s - location name, %2$d - location ID.
							__( '%1$s (ID: %2$d)', 'orderable' ),
							$location->location_data['title'],
							$location->location_data['location_id']
						),
						'value' => $location->location_data['location_id'],
					);
				},
				$locations
			)
		);

		wp_localize_script(
			'orderable-timings',
			'orderable_timings_block_vars',
			array(
				'admin_url' => get_admin_url(),
				'locations' => $locations,
			)
		);

		register_block_type(
			'orderable/open-hours',
			array(
				'editor_script'   => 'orderable-timings',
				'render_callback' => array( __CLASS__, 'open_hours_block_handler' ),
				'attributes'      => array(
					'location_id' => array(
						'default' => '',
						'type'    => 'string',
					),
				),
			)
		);
	}

	/**
	 * Handle block: Layout.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function open_hours_block_handler( $attributes = array() ) {
		return Orderable_Timings::orderable_open_hours_shortcode( $attributes );
	}

	/**
	 * Check if the services are enabled for the open hours days.
	 *
	 * Since we use the the [orderable-open-hours] to render the
	 * block and the shortcode has a different behaviour when we
	 * try to render it on the block editor (admin), we need to
	 * check again to get the correct services enabled.
	 *
	 * @param array                     $open_hours Location upcoming open hours.
	 * @param Orderable_Location_Single $location   Location object.
	 * @return array
	 */
	public static function check_services_enabled( $open_hours, $location ) {
		$is_delivery_enabled = empty( $location->location_data['delivery'] ) ? false : $location->location_data['delivery'];
		$is_pickup_enabled   = empty( $location->location_data['pickup'] ) ? false : $location->location_data['pickup'];

		if ( ! $is_delivery_enabled && ! $is_pickup_enabled ) {
			return $open_hours;
		}

		$is_pickup_hours_same_as_delivery = (bool) $location->location_data['pickup_hours_same_as_delivery'];

		if ( $is_delivery_enabled ) {
			$service_hours_delivery      = $location->get_service_hours( 'delivery', true, true );
			$service_hours_delivery_days = empty( $service_hours_delivery[0]['days'] ) ? array() : $service_hours_delivery[0]['days'];
		}

		foreach ( $service_hours_delivery_days as $day ) {
			$day = (int) $day;
			if ( empty( $open_hours[ $day ] ) ) {
				continue;
			}

			$open_hours[ $day ]['services']['delivery'] = (bool) $is_delivery_enabled;

			if ( ! $is_pickup_hours_same_as_delivery ) {
				continue;
			}

			$open_hours[ $day ]['services']['pickup'] = (bool) $is_pickup_enabled;

		}

		if ( ! $is_pickup_enabled || $is_pickup_hours_same_as_delivery ) {
			return $open_hours;
		}

		$service_hours_pickup      = $location->get_service_hours( 'pickup', true, true );
		$service_hours_pickup_days = empty( $service_hours_pickup[0]['days'] ) ? array() : $service_hours_pickup[0]['days'];

		foreach ( $service_hours_pickup_days as $day ) {
			$day = (int) $day;
			if ( empty( $open_hours[ $day ] ) ) {
				continue;
			}

			$open_hours[ $day ]['services']['pickup'] = (bool) $is_pickup_enabled;
		}

		return $open_hours;
	}
}
