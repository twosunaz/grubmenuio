<?php
/**
 * Module: Location (Zones).
 *
 * @since   1.18.0
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable_Location_Zones class.
 */
class Orderable_Location_Zones {
	/**
	 * Init.
	 */
	public static function run() {
		add_filter( 'woocommerce_package_rates', array( __CLASS__, 'modify_package_rates' ), 20, 2 );
		add_filter( 'woocommerce_no_shipping_available_html', array( __CLASS__, 'no_shipping_available_html' ) );
	}

	/**
	 * Modify the package rates for a matching zone.
	 *
	 * @param array $rates   Package rates.
	 * @param array $package Package of cart items.
	 * @return array
	 */
	public static function modify_package_rates( $rates, $package ) {
		$selected_location = Orderable_Location::get_selected_location();
		$zone_id           = self::get_zone_id_matching_package( $package );

		// Set shipping zone ID here so we can capture it later.
		WC()->session->set( 'orderable_chosen_zone_id', $zone_id );

		if ( ! $selected_location || ! $selected_location->has_services() ) {
			return $rates;
		}

		$pickup_rates   = array();
		$delivery_rates = array();

		foreach ( $rates as $rate_id => $rate ) {
			// If is a delivery rate.
			if ( false === strpos( $rate_id, 'pickup' ) ) {
				$delivery_rates[ $rate_id ] = $rate;

				if ( ! $selected_location->is_service_enabled( 'delivery' ) || ! $selected_location->has_service_dates( 'delivery' ) ) {
					unset( $rates[ $rate_id ] );
				}
			} else {
				$pickup_rates[ $rate_id ] = $rate;

				if ( ! $selected_location->is_service_enabled( 'pickup' ) || ! $selected_location->has_service_dates( 'pickup' ) ) {
					unset( $rates[ $rate_id ] );
				}
			}
		}

		// Add delivery rates if none exist. Use matched zone ID so time slot lookup is correct.
		// Don't add delivery method to "Locations not covered by your other zones" (Zone 0).
		if ( $zone_id > 0 && $selected_location->is_service_enabled( 'delivery' ) && $selected_location->has_service_dates( 'delivery' ) && empty( $delivery_rates ) ) {
			$zone_fee = 0;

			if ( $selected_location->has_zone( $zone_id ) ) {
				$zone     = new WC_Shipping_Zone( $zone_id );
				$zone_fee = floatval( $zone->get_meta( 'delivery_fee' ) );

				if ( 0 > $zone_fee ) {
					$zone_fee = 0;
				}
			}

			$rates[ 'orderable_delivery:' . $zone_id ] = new WC_Shipping_Rate(
				'orderable_delivery:' . $zone_id,
				__( 'Delivery', 'orderable' ),
				$zone_fee,
				array(),
				'orderable_delivery',
				$zone_id
			);
		}

		// Add pickup rates if none exist. Use matched zone ID so time slot lookup is correct.
		if ( $selected_location->is_service_enabled( 'pickup' ) && empty( $pickup_rates ) && $selected_location->has_service_dates( 'pickup' ) ) {
			$rates[ 'orderable_pickup:' . $zone_id ] = new WC_Shipping_Rate(
				'orderable_pickup:' . $zone_id,
				__( 'Pickup', 'orderable' ),
				0,
				array(),
				'orderable_pickup',
				$zone_id
			);
		}

		return $rates;
	}

	/**
	 * Get zone id which matches package.
	 *
	 * @param array $package Shipping package.
	 *
	 * @return int
	 */
	public static function get_zone_id_matching_package( $package ) {
		$zone = WC_Shipping_Zones::get_zone_matching_package( $package );

		return $zone ? $zone->get_id() : 0;
	}

	/**
	 * Get the method ID for a given instance ID.
	 *
	 * @param int $instance_id Instance ID.
	 * @return string|bool
	 */
	public static function get_method_id( $instance_id ) {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT
					method_id
				FROM
					{$wpdb->prefix}woocommerce_shipping_zone_methods
				WHERE
					instance_id = %d",
				$instance_id
			)
		);

		return empty( $result ) ? false : $result . ':' . $instance_id;
	}

	/**
	 * Get zones.
	 *
	 * @param int $time_slot_id Time slot ID.
	 * @return array
	 */
	public static function get_zones( $time_slot_id = null ) {
		$cache_key = 'orderable_zones';

		if ( $time_slot_id ) {
			$cache_key .= '_time_slot_' . $time_slot_id;
		}

		$matching_zones = wp_cache_get( $cache_key );

		if ( false !== $matching_zones ) {
			return $matching_zones;
		}

		$zones = WC_Shipping_Zones::get_zones();

		if ( empty( $zones ) ) {
			return array();
		}

		$matching_zones = array();

		foreach ( $zones as $key => $zone ) {
			$matching_zones[ $key ] = self::enrich_zone_data( $zone );
		}

		// If $time_slot_id is set, filter out zones that don't have the time slot.
		if ( $time_slot_id ) {
			foreach ( $matching_zones as $key => $matching_zone ) {
				if ( empty( $matching_zone['time_slots'] ) || ! in_array( $time_slot_id, $matching_zone['time_slots'], true ) ) {
					unset( $matching_zones[ $key ] );
				}
			}
		}

		wp_cache_set( $cache_key, $matching_zones );

		return $matching_zones;
	}

	/**
	 * Enrich zone data with locations and meta.
	 *
	 * @param array $zone Zone data.
	 * @return array
	 */
	public static function enrich_zone_data( $zone ) {
		$zone_instance = new WC_Shipping_Zone( $zone['zone_id'] );

		// Add locations to the zone.
		$locations      = array();
		$zone_locations = $zone_instance->get_zone_locations();

		if ( $zone_locations ) {
			foreach ( $zone_locations as $location ) {
				if ( 'postcode' === $location->type ) {
					$locations[] = $location->code;
				}
			}
		}

		$zone['zone_postcodes'] = join( ',', $locations );

		// Add fee data to the zone.
		$zone['zone_fee']   = floatval( $zone_instance->get_meta( 'delivery_fee' ) );
		$zone['time_slots'] = self::get_time_slots_for_zone( $zone['zone_id'] );

		return $zone;
	}

	/**
	 * Get time slots for a given zone.
	 *
	 * @param int $zone_id Zone ID.
	 *
	 * @return array
	 */
	public static function get_time_slots_for_zone( $zone_id ) {
		$cache_key  = 'orderable_time_slots_for_zone_' . $zone_id;
		$time_slots = wp_cache_get( $cache_key );

		if ( false !== $time_slots ) {
			return apply_filters( 'orderable_get_time_slots_for_zone', $time_slots, $zone_id );
		}

		global $wpdb;

		$zone_id = absint( $zone_id );

		$time_slots_lookup = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
                time_slot_id
            FROM
                {$wpdb->prefix}orderable_location_delivery_zones_lookup
            WHERE
                zone_id = %d",
				$zone_id
			),
			ARRAY_A
		);

		if ( empty( $time_slots_lookup ) ) {
			$time_slots_lookup = array();
		}

		$time_slots = array_map( 'absint', wp_list_pluck( $time_slots_lookup, 'time_slot_id' ) );
		wp_cache_set( $cache_key, $time_slots );

		return apply_filters( 'orderable_get_time_slots_for_zone', $time_slots, $zone_id );
	}

	/**
	 * Get the selected shipping zone ID.
	 *
	 * If this function returns `0`, then the zone ID matched
	 * is the "Rest of the world" zone (created by WooCommerce)
	 *
	 * @return int|false
	 */
	public static function get_selected_shipping_zone_id() {
		return ! empty( WC()->session ) ? WC()->session->get( 'orderable_chosen_zone_id', false ) : false;
	}

	/**
	 * Shows when no shipping options are available.
	 *
	 * @param string $html Text string about no shipping options.
	 *
	 * @return string
	 */
	public static function no_shipping_available_html( $html ) {
		$location        = Orderable_Location::get_selected_location();
		$services        = $location->get_services();
		$services_string = implode( '/', $services );

		// Translators: %s is the service types (delivery/pickup).
		return sprintf( __( 'Sorry, there are no %s options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'orderable' ), $services_string );
	}
}
