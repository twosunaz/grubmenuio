<?php
/**
 * Checkout Order Date Extend Store API.
 *
 * @package orderable
 */

/**
 * Class for extending the Store API.
 */
class Checkout_Order_Date_Extend_Store_API {

	/**
	 * Extend the cart schema.
	 *
	 * @return array
	 */
	public static function extend_cart_schema() {
		return array(
			'endpoint'        => Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
			'namespace'       => 'orderable/order-service-date',
			'data_callback'   => function() {
				$service       = Orderable_Services::get_selected_service();
				$data          = array(
					// Translators: %s Service type.
					'serviceDatesLabel' => sprintf( __( '%s Date', 'orderable' ), esc_html( $service ) ),
				);
				$location      = Orderable_Location::get_selected_location();
				$service_dates = $location->get_service_dates();

				if ( is_bool( $service_dates ) ) {
					$data['serviceDates'] = $service_dates;

					return $data;
				}

				if ( ! is_array( $service_dates ) ) {
					$data['serviceDates'] = array();

					return $data;
				}

				$asap = $location->get_asap_settings();

				foreach ( $service_dates as $key => $service_date ) {
					if ( empty( $service_date['timestamp'] || empty( $service_date['formatted'] ) ) ) {
						unset( $service_dates[ $key ] );
						continue;
					}

					$timestamp = $service_date['timestamp'];
					$slots     = $service_date['slots'] ?? array();

					$slots = array_filter(
						array_map(
							function( $slot ) {
								if ( empty( $slot['value'] ) || 'all-day' === $slot['value'] ) {
									return false;
								}

								return array(
									'label'        => $slot['formatted'],
									'value'        => $slot['value'],
									'time_slot_id' => $slot['setting_row']['time_slot_id'] ?? 0,
								);
							},
							$slots
						)
					);

					if ( ! empty( $asap['time'] ) ) {
						$asap_option = array(
							1 => array(
								'label' => __( 'As soon as possible', 'orderable' ),
								'value' => 'asap',
							),
						);

						$slots = $asap_option + $slots;
					}

					if ( ! empty( $slots ) ) {
						$select_time = array(
							0 => array(
								'label' => __( 'Select a time...', 'orderable' ),
								'value' => '',
							),
						);

						$slots = $select_time + $slots;
					}

					$service_dates[ $timestamp ] = array(
						'label' => $service_date['formatted'],
						'value' => $service_date['timestamp'],
						'slots' => $slots,
					);

					unset( $service_dates[ $key ] );
				}

				if ( ! empty( $asap['date'] ) ) {
					$asap_option = array(
						1 => array(
							'label' => __( 'As soon as possible', 'orderable' ),
							'value' => 'asap',
						),
					);

					$service_dates = $asap_option + $service_dates;
				}

				$select_date = array(
					0 => array(
						'label' => __( 'Select a date...', 'orderable' ),
						'value' => '',
					),
				);
				$service_dates = $select_date + $service_dates;

				$data['serviceDates'] = $service_dates;

				return $data;
			},
			'schema_callback' => function() {
				return array(
					'serviceDatesLabel' => array(
						'description' => __( 'Service date label field', 'orderable' ),
						'type'        => 'string',
						'readonly'    => true,
					),
					'serviceDates'      => array(
						'description' => __( 'Service date options.', 'orderable' ),
						'type'        => array( 'array', 'bool' ),
						'readonly'    => true,
					),
				);
			},
			'schema_type'     => ARRAY_A,
		);
	}

	/**
	 * Extend the checkout schema.
	 *
	 * @return array
	 */
	public static function extend_checkout_schema() {
		return array(
			'endpoint'        => Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::IDENTIFIER,
			'namespace'       => 'orderable/order-service-date',
			'schema_callback' => function() {
				return array(
					'timestamp' => array(
						'description' => 'Order date timestamp',
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'optional'    => true,
					),
				);
			},
			'schema_type'     => ARRAY_A,
		);
	}
}
