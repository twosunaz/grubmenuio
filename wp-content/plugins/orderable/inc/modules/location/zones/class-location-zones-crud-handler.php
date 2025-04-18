<?php
/**
 * Module: Location (Zones).
 *
 * @since   1.18.0
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable_Location_Zones_CRUD_Handler class.
 */
class Orderable_Location_Zones_CRUD_Handler {
	/**
	 * Create a new zone.
	 *
	 * @param array $data Data for this request.
	 * @return array
	 */
	public static function add_new( $data ) {
		try {
			// Create the basic zone.
			$data_store = WC_Data_Store::load( 'shipping-zone' );
			$zone       = new WC_Shipping_Zone();

			$zone->set_zone_name( $data['zone_name'] );
			$zone->set_zone_order( 100 );

			// Add locations.
			$normalized_postcodes = array_map( 'wc_normalize_postcode', explode( ',', $data['zone_postcodes'] ) );
			$locations            = ( ! empty( $data['zone_postcodes'] ) ) ? self::convert_postcodes_to_locations( $normalized_postcodes ) : array();

			$default_country = get_option( 'woocommerce_default_country', false );

			if ( $default_country ) {
				$default_country_arr = explode( ':', $default_country );

				$locations[] = array(
					'type' => 'country',
					'code' => $default_country_arr[0],
				);

				if ( count( $default_country_arr ) === 2 ) {
					$locations[] = array(
						'type' => 'state',
						'code' => $default_country,
					);
				}
			}

			$zone->set_locations( $locations );

			// Add fee meta data, defaulting to free.
			$fee = ! empty( $data['zone_fee'] ) ? preg_replace( '/[^0-9.]/m', '', $data['zone_fee'] ) : 0.00;
			$zone->add_meta_data( 'delivery_fee', floatval( number_format( $fee, 2 ) ) );

			// Create the zone and get the ID.
			$data_store->create( $zone );
			$new_zone_id = $zone->get_id();

			// Add shipping methods.
			$data_store->add_method( $new_zone_id, 'orderable_delivery', 100 );
			$data_store->add_method( $new_zone_id, 'orderable_pickup', 100 );

			self::create_lookup_entry( $data['location_id'], $new_zone_id, $data['time_slot_id'] );

			return array(
				'status'         => true,
				'zone_id'        => $new_zone_id,
				'zone_name'      => $data['zone_name'],
				'zone_postcodes' => join( ',', $normalized_postcodes ),
				'zone_fee'       => floatval( number_format( $fee, 2 ) ),
				'msg'            => esc_html(
					sprintf(
						/* translators: zone name */
						__( '%s succcessfully created!', 'orderable' ),
						$data['zone_name']
					)
				),
			);
		} catch ( Exception $ex ) {
			return array(
				'status'         => false,
				'zone_id'        => null,
				'zone_name'      => null,
				'zone_postcodes' => null,
				'zone_fee'       => null,
				'msg'            => $ex->getMessage(),
			);
		}
	}

	/**
	 * Add an existing zone to a location
	 *
	 * @param array $data Data for this request.
	 * @return array
	 */
	public static function add_existing( $data ) {
		try {
			// Create the lookup table entries (zones, time slots).
			self::create_lookup_entry( $data['location_id'], $data['zone_id'], $data['time_slot_id'] );

			// Send back the same data provided in the request.
			return array(
				'status'         => true,
				'zone_id'        => $data['zone_id'],
				'zone_name'      => $data['zone_name'],
				'zone_postcodes' => $data['zone_postcodes'],
				'zone_fee'       => $data['zone_fee'],
				'msg'            => esc_html(
					sprintf(
						/* translators: zone name */
						__( '%s succcessfully added!', 'orderable' ),
						$data['zone_name']
					)
				),
			);
		} catch ( Exception $ex ) {
			return array(
				'status'         => false,
				'zone_id'        => $data['zone_id'],
				'zone_name'      => $data['zone_name'],
				'zone_postcodes' => $data['zone_postcodes'],
				'zone_fee'       => $data['zone_fee'],
				'msg'            => $ex->getMessage(),
			);
		}
	}

	/**
	 * Update a zone.
	 *
	 * @param array $data Data for this request.
	 * @return array
	 */
	public static function edit( $data ) {
		try {
			$data_store = WC_Data_Store::load( 'shipping-zone' );
			$zone       = new WC_Shipping_Zone( $data['zone_id'] );

			$zone->set_zone_name( $data['zone_name'] );
			$zone->update_meta_data( 'delivery_fee', $data['zone_fee'] );

			$normalized_postcodes = array_map( 'wc_normalize_postcode', explode( ',', $data['zone_postcodes'] ) );
			// clear postcodes but keep other locations type e.g. state, country.
			$zone->clear_locations( ['postcode'] );

			// NOTE: Shipping methods are added in class-location-zones.php;
			// see the `on_shipping_zone_save` method.
			foreach ( $normalized_postcodes as $postcode ) {
				$zone->add_location( $postcode, 'postcode' );
			}

			$data_store->update( $zone );

			return array(
				'status'         => true,
				'zone_id'        => $data['zone_id'],
				'zone_name'      => $data['zone_name'],
				'zone_postcodes' => $data['zone_postcodes'],
				'zone_fee'       => $data['zone_fee'],
				'msg'            => esc_html(
					sprintf(
						/* translators: zone name */
						__( '%s succcessfully updated!', 'orderable' ),
						$data['zone_name']
					)
				),
			);
		} catch ( Exception $ex ) {
			return array(
				'status'         => false,
				'zone_id'        => $data['zone_id'],
				'zone_name'      => $data['zone_name'],
				'zone_postcodes' => $data['zone_postcodes'],
				'zone_fee'       => $data['zone_fee'],
				'msg'            => $ex->getMessage(),
			);
		}
	}

	/**
	 * Delete a delivery zone.
	 *
	 * @param array $data The data sent in the request.
	 * @return array
	 */
	public static function delete( $data ) {
		global $wpdb;

		if ( empty( $data['zone_id'] ) ) {
			return false;
		}

		$zone_id = $data['zone_id'];

		// Delete delivery zones lookup table entry.
		$delete_zones_lookup = $wpdb->delete(
			$wpdb->prefix . 'orderable_location_delivery_zones_lookup',
			array(
				'zone_id' => $zone_id,
			),
			'%d'
		);

		// Delete delivery zone methods.
		$delete_delivery_zone_methods = $wpdb->delete(
			$wpdb->prefix . 'woocommerce_shipping_zone_methods',
			array(
				'zone_id' => $zone_id,
			),
			'%d'
		);

		// Delete delivery zone locations.
		$delete_delivery_zone_locations = $wpdb->delete(
			$wpdb->prefix . 'woocommerce_shipping_zone_locations',
			array(
				'zone_id' => $zone_id,
			),
			'%d'
		);

		// Delete delivery zones.
		$delete_delivery_zones = $wpdb->delete(
			$wpdb->prefix . 'woocommerce_shipping_zones',
			array(
				'zone_id' => $zone_id,
			),
			'%d'
		);

		$result =
			false !== $delete_zones_lookup &&
			false !== $delete_delivery_zone_methods &&
			false !== $delete_delivery_zone_locations &&
			false !== $delete_delivery_zones;

		return array(
			'status' => $result,
			'msg'    => esc_html__( 'Zone succcessfully deleted!', 'orderable' ),
		);
	}

	/**
	 * Remove a zone.
	 *
	 * This does not delete a zone from the DB, rather,
	 * it removes the association between the zone and
	 * locations/time slots.
	 *
	 * @param array $data Data for this request.
	 * @return array
	 */
	public static function remove( $data ) {
		try {
			$zones_removed   = array();
			$zones_to_remove = json_decode( wp_unslash( $data['request_data'] ) );

			foreach ( $zones_to_remove as $zone_data ) {
				if ( empty( $zone_data->zone_id ) || empty( $zone_data->post_id ) || empty( $zone_data->time_slot_id ) ) {
					continue;
				}

				$location_id = Orderable_Location::get_location_id( $zone_data->post_id );

				if ( ! $location_id ) {
					continue;
				}

				$delete = self::delete_lookup_entry( $location_id, $zone_data->zone_id, $zone_data->time_slot_id );

				if ( $delete ) {
					$zones_removed[] = $zone_data->zone_id;
				}
			}

			return array(
				'status'       => true,
				'zone_ids'     => $zones_removed,
				'time_slot_id' => intval( $data['time_slot_id'] ),
				'msg'          => esc_html__( 'Zone(s) succcessfully removed', 'orderable' ),
			);
		} catch ( Exception $ex ) {
			return array(
				'status'       => false,
				'zone_id'      => null,
				'time_slot_id' => intval( $data['time_slot_id'] ),
				'msg'          => $ex->getMessage(),
			);
		}
	}

	/**
	 * Create lookup table DB entries.
	 *
	 * @param int $location_id  Location ID.
	 * @param int $zone_id      Zone ID.
	 * @param int $time_slot_id Time Slot ID.
	 *
	 * @return void
	 */
	public static function create_lookup_entry( $location_id, $zone_id, $time_slot_id ) {
		global $wpdb;

		// Create delivery zones lookup table entry.
		$wpdb->insert(
			$wpdb->prefix . 'orderable_location_delivery_zones_lookup',
			array(
				'location_id'  => $location_id,
				'time_slot_id' => $time_slot_id,
				'zone_id'      => $zone_id,
			)
		);
	}

	/**
	 * Delete lookup table DB entries.
	 *
	 * @param int      $location_id       Location ID.
	 * @param int|bool $zone_id           Zone ID, or false to delete all entries regardless of zone.
	 * @param int|bool $time_slot_id Time Slot ID, or false to delete all entries regardless of time slot.
	 *
	 * @return bool
	 */
	public static function delete_lookup_entry( $location_id, $zone_id = false, $time_slot_id = false ) {
		global $wpdb;

		// Delete delivery zones lookup table entry.
		$delete_where = array(
			'location_id' => $location_id,
		);

		if ( false !== $time_slot_id ) {
			$delete_where['time_slot_id'] = $time_slot_id;
		}

		if ( false !== $zone_id ) {
			$delete_where['zone_id'] = $zone_id;
		}

		$delete_zones_lookup = $wpdb->delete(
			$wpdb->prefix . 'orderable_location_delivery_zones_lookup',
			$delete_where,
			'%d'
		);

		return ( $delete_zones_lookup );
	}

	/**
	 * Parse raw comma delimited postcode data.
	 *
	 * @param array $normalized_postcodes Postcode data.
	 * @return array
	 */
	public static function convert_postcodes_to_locations( $normalized_postcodes ) {
		$locations = array();

		foreach ( $normalized_postcodes as $postcode ) {
			$locations[] = array(
				'type' => 'postcode',
				'code' => $postcode,
			);
		}

		return $locations;
	}
}
