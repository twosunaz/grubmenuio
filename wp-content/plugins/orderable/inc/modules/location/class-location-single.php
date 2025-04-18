<?php
/**
 * Single location class.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Single location class.
 */
class Orderable_Location_Single {
	/**
	 * Location data.
	 *
	 * @var array
	 */
	public $location_data = array(
		'location_id'                   => 0,
		'override_default_open_hours'   => false,
		'asap_date'                     => '',
		'asap_time'                     => '',
		'address_line_1'                => '',
		'address_line_2'                => '',
		'city'                          => '',
		'country_state'                 => '',
		'postcode_zip'                  => '',
		'open_hours'                    => array(),
		'enable_default_holidays'       => true,
		'pickup_hours_same_as_delivery' => true,
	);

	/**
	 * Constructor.
	 *
	 * @param int|array|null $location Location ID or row data.
	 */
	public function __construct( $location = null ) {
		$location_data = array();

		// Get selected location ID if none is passed in.
		if ( empty( $location ) ) {
			$location = Orderable_Location::get_selected_location_id();
		}

		// Get the main location data.
		if ( empty( $location ) ) {
			$location_data['open_hours'] = Orderable_Location::get_default_open_hours();
		} else {
			$location_data = is_numeric( $location ) ? Orderable_Location::get_location_data( $location ) : $location;
		}

		$this->location_data = wp_parse_args( $location_data, $this->location_data );

		/**
		 * Action to run after the location object is initialized.
		 *
		 * @since 1.13.0
		 */
		do_action( 'orderable_location_object_init', $this );
	}

	/**
	 * Get location ID.
	 *
	 * @return int
	 */
	public function get_location_id() {
		return absint( $this->location_data['location_id'] );
	}

	/**
	 * Get location name.
	 *
	 * @return int
	 */
	public function get_title() {
		return $this->location_data['title'];
	}

	/**
	 * Is service enabled?
	 *
	 * @param string $service_type Service type (delivery/pickup).
	 *
	 * @return bool
	 */
	public function is_service_enabled( $service_type ) {
		return ! empty( $this->location_data[ $service_type ] );
	}

	/**
	 * Get services.
	 *
	 * @return array
	 */
	public function get_services() {
		$services = array();

		if ( $this->is_service_enabled( 'delivery' ) ) {
			$services[] = 'delivery';
		}

		if ( $this->is_service_enabled( 'pickup' ) ) {
			$services[] = 'pickup';
		}

		return $services;
	}

	/**
	 * Does the location have any services enabled.
	 *
	 * @return bool
	 */
	public function has_services() {
		return ! empty( $this->get_services() );
	}

	/**
	 * Get lead time period.
	 *
	 * @return string
	 */
	public function get_lead_time_period() {
		return ! empty( $this->location_data['lead_time_period'] ) ? $this->location_data['lead_time_period'] : 'days';
	}

	/**
	 * Get lead time.
	 *
	 * @param bool $in_seconds In seconds.
	 *
	 * @return int
	 */
	public function get_lead_time( $in_seconds = false ) {
		$lead_time = ! empty( $this->location_data['lead_time'] ) ? absint( $this->location_data['lead_time'] ) : 0;

		if ( 0 === $lead_time || ! $in_seconds ) {
			/**
			 * Filter to modify the lead time.
			 *
			 * @since 1.9.0
			 */
			return apply_filters( 'orderable_location_get_lead_time', $lead_time );
		}

		$lead_time_period = $this->get_lead_time_period();

		if ( 'days' === $lead_time_period ) {
			$lead_time *= 86400;
		} elseif ( 'hours' === $lead_time_period ) {
			$lead_time *= 3600;
		} elseif ( 'minutes' === $lead_time_period ) {
			$lead_time *= 60;
		}

		/**
		 * Filter to modify the lead time.
		 *
		 * @since 1.9.0
		 */
		return apply_filters( 'orderable_location_get_lead_time', $lead_time );
	}

	/**
	 * Get preorder days.
	 *
	 * @return int
	 */
	public function get_preorder_days() {
		return isset( $this->location_data['preorder'] ) ? absint( $this->location_data['preorder'] ) : 7;
	}

	/**
	 * Get delivery days calculation method.
	 *
	 * @return string
	 */
	public function get_delivery_calculation_method() {
		return ! empty( $this->location_data['delivery_days_calculation_method'] ) ? $this->location_data['delivery_days_calculation_method'] : 'all';
	}

	/**
	 * Get override default open hours setting.
	 *
	 * @return bool
	 */
	public function get_override_default_open_hours() {
		return ! in_array( $this->location_data['override_default_open_hours'], array( false, '0' ), true );
	}

	/**
	 * Get enable default holidays setting.
	 *
	 * @return bool
	 */
	public function get_enable_default_holidays() {
		return ! in_array( $this->location_data['enable_default_holidays'], array( false, '0' ), true );
	}

	/**
	 * Get pickup hours same as delivery setting.
	 *
	 * @return bool
	 */
	public function get_pickup_hours_same_as_delivery() {
		return ! in_array( $this->location_data['pickup_hours_same_as_delivery'], array( false, '0' ), true );
	}

	/**
	 * Get ASAP settings
	 *
	 * @return array
	 */
	public function get_asap_settings() {
		return array(
			'date' => '1' === $this->location_data['asap_date'],
			'time' => '1' === $this->location_data['asap_time'],
		);
	}

	/**
	 * Get service days.
	 *
	 * @param string $service_type Service type (delivery/pickup).
	 *
	 * @return array
	 */
	public function get_service_days( $service_type = 'delivery' ) {
		$days = Orderable_Timings::get_days_of_the_week();

		$settings     = $this->get_service_hours( $service_type, false, true );
		$service_days = array();

		if ( empty( $settings ) ) {
			return $service_days;
		}

		foreach ( $settings as $setting_row ) {
			if ( empty( $setting_row['days'] ) ) {
				continue;
			}

			foreach ( $setting_row['days'] as $day_number ) {
				$service_days[ $day_number ] = $days[ $day_number ];
			}
		}

		return $service_days;
	}

	/**
	 * Get service hours.
	 *
	 * @param null $service_type Service type (delivery/pickup).
	 * @param bool $is_admin     Is this an admin request? If so, collect all data for location.
	 * @param bool $skip_zone    Skip the zone ID.
	 *
	 * @return array
	 */
	public function get_service_hours( $service_type = null, $is_admin = false, $skip_zone = false ) {
		$zone_id = Orderable_Location_Zones::get_selected_shipping_zone_id();

		if ( false === $zone_id && ! $is_admin ) {
			/**
			 * Filter to modify the service hours.
			 *
			 * @param array                     $service_hours The service hours.
			 * @param Orderable_Location_Single $location      Current location object.
			 * @param string|null               $service_type  The service type.
			 * @param bool                      $is_admin      Is this an admin request?
			 * @param bool                      $skip_zone     Skip the zone ID.
			 *
			 * @since 1.14.0
			 */
			return apply_filters( 'orderable_get_service_hours', array(), $this, $service_type, $is_admin, $skip_zone );
		}

		// Switch service type to 'delivery' if pickup hours are the same as delivery.
		$original_service_type = $service_type;
		$service_type          = ! $is_admin && 'pickup' === $service_type && $this->get_pickup_hours_same_as_delivery() ? 'delivery' : $service_type;

		$location_id = $this->get_location_id();
		$cache_key   = "orderable_time_slots_{$location_id}";

		if ( $original_service_type ) {
			$cache_key .= "_{$original_service_type}";
		}

		if ( ! $skip_zone && false !== $zone_id ) {
			$cache_key .= "_{$zone_id}";
		}

		$cached_service_hours = wp_cache_get( $cache_key );

		if ( false !== $cached_service_hours ) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			return apply_filters( 'orderable_get_service_hours', $cached_service_hours, $this, $service_type, $is_admin, $skip_zone );
		}

		global $wpdb;

		$query = "SELECT DISTINCT
            ts.*
        FROM
            {$wpdb->prefix}orderable_location_time_slots ts
        LEFT JOIN
            {$wpdb->prefix}orderable_location_delivery_zones_lookup l
            ON ts.location_id = l.location_id AND ts.time_slot_id = l.time_slot_id
        WHERE
            ts.location_id = %d";

		$query_params = array(
			$location_id,
		);

		if ( $service_type ) {
			$query         .= ' AND ts.service_type = %s';
			$query_params[] = $service_type;
		}

		// Zone doesn't matter for pickup.
		if ( 'pickup' !== $original_service_type && ! empty( $zone_id ) && ! $skip_zone ) {
			$query         .= ' AND (l.zone_id = %d OR ts.has_zones = 0)';
			$query_params[] = $zone_id;
		}

		$service_hours = $wpdb->get_results(
			$wpdb->prepare(
				$query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$query_params
			),
			ARRAY_A
		);

		if ( empty( $service_hours ) ) {
			$service_hours = array();
		} else {
			foreach ( $service_hours as &$service_hour ) {
				$service_hour['days'] = (array) maybe_unserialize( $service_hour['days'] );
				$service_hour['from'] = maybe_unserialize( $service_hour['time_from'] );
				$service_hour['to']   = maybe_unserialize( $service_hour['time_to'] );
			}
		}

		wp_cache_set( $cache_key, $service_hours, '', ORDERABLE_CACHE_EXPIRATION_TIME );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		return apply_filters( 'orderable_get_service_hours', $service_hours, $this, $service_type, $is_admin, $skip_zone );
	}

	/**
	 * Get services on day.
	 *
	 * @param int $timestamp Timestamp of specific day at 00:00am (GMT).
	 *
	 * @return array
	 */
	public function get_services_on_day( $timestamp ) {
		$timestamp_adjusted = Orderable_Timings::get_timestamp_adjusted( $timestamp );
		$services_on_day    = array();
		$services           = $this->get_services();

		if ( empty( $services ) ) {
			return $services_on_day;
		}

		$day_to_check = absint( date( 'w', $timestamp_adjusted ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		foreach ( $services as $service ) {
			$service_days                = $this->get_service_days( $service );
			$services_on_day[ $service ] = isset( $service_days[ $day_to_check ] ) && ! $this->is_holiday( $timestamp, $service );
		}

		return $services_on_day;
	}

	/**
	 * Get open days.
	 *
	 * @return array
	 */
	public function get_open_days() {
		static $open_days_cache;

		if ( ! empty( $open_days_cache[ $this->get_location_id() ] ) ) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks
			return apply_filters( 'orderable_location_get_open_days', $open_days_cache[ $this->get_location_id() ], $this );
		}

		$open_days           = array();
		$open_hours_settings = $this->get_open_hours();
		$days_of_the_week    = Orderable_Timings::get_days_of_the_week();

		foreach ( $open_hours_settings as $day => $open_hour ) {
			if ( ! empty( $open_hour['enabled'] ) ) {
				$open_days[ $day ] = $days_of_the_week[ $day ];
			}
		}

		/**
		 * Filter location open days.
		 *
		 * @since 1.8.0
		 * @hook orderable_location_get_open_days
		 * @param array                     $open_days Location open days.
		 * @param Orderable_Location_Single $location  Location object.
		 */
		$open_days = apply_filters( 'orderable_location_get_open_days', $open_days, $this );

		$open_days_cache[ $this->get_location_id() ] = $open_days;

		return $open_days;
	}

	/**
	 * Is the location open?
	 *
	 * @param int $timestamp Timestamp (GMT).
	 *
	 * @return bool
	 */
	public function is_open( $timestamp ) {
		$date_time = Orderable_Timings::get_date_time_by_timestamp( $timestamp );
		$open_days = $this->get_open_days();

		return array_key_exists( $date_time->format( 'w' ), $open_days );
	}

	/**
	 * Get open hours.
	 *
	 * @return array
	 */
	public function get_open_hours() {
		static $open_hours_cache;

		if ( ! empty( $open_hours_cache[ $this->get_location_id() ] ) ) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks
			return apply_filters( 'orderable_location_get_open_hours', $open_hours_cache[ $this->get_location_id() ], $this );
		}

		if ( $this->get_override_default_open_hours() ) {
			$open_hours = maybe_unserialize( $this->location_data['open_hours'] );
			$open_hours = ! empty( $open_hours ) ? $open_hours : array();
		} else {
			$open_hours = Orderable_Location::get_default_open_hours();
		}

		/**
		 * Filter location open hours.
		 *
		 * @since 1.8.0
		 * @hook orderable_location_get_open_hours
		 * @param array                     $open_hours Location open hours.
		 * @param Orderable_Location_Single $location   Location object.
		 */
		$open_hours = apply_filters( 'orderable_location_get_open_hours', $open_hours, $this );

		$open_hours_cache[ $this->get_location_id() ] = $open_hours;

		return $open_hours;
	}

	/**
	 * Get upcoming open hours.
	 *
	 * @return array
	 */
	public function get_upcoming_open_hours() {
		$open_hours          = array();
		$days                = Orderable_Timings::get_days_of_the_week( 'full', 0 );
		$open_hours_settings = $this->get_open_hours();
		$current_day         = absint( current_time( 'w', true ) );
		$tense               = 'last';

		foreach ( $days as $index => $day ) {
			$day_settings = isset( $open_hours_settings[ $index ] ) ? $open_hours_settings[ $index ] : null;

			if ( empty( $day_settings ) ) {
				continue;
			}

			if ( $index === $current_day ) {
				$tense = 'this';
			}

			$day_name_en        = date( 'l', strtotime( "Sunday +{$index} days" ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$datetime           = new DateTime( $tense . ' ' . $day_name_en, wp_timezone() );
			$timestamp          = $datetime->getTimestamp();
			$timestamp_adjusted = Orderable_Timings::get_timestamp_adjusted( $timestamp );
			$services_on_day    = $this->get_services_on_day( $timestamp );
			$hours              = __( 'Closed', 'orderable' );
			$is_holiday         = $this->is_holiday( $timestamp );
			$is_holiday         = $is_holiday && empty( array_filter( $services_on_day ) );
			$open               = ! empty( $day_settings['enabled'] ) && $day_settings['enabled'];

			if ( $open && $is_holiday ) {
				$hours = __( 'Holiday', 'orderable' );
			} elseif ( $open && ! $is_holiday ) {
				$from  = sprintf( '%s:%s %s', $day_settings['from']['hour'], $day_settings['from']['minute'], $day_settings['from']['period'] );
				$to    = sprintf( '%s:%s %s', $day_settings['to']['hour'], $day_settings['to']['minute'], $day_settings['to']['period'] );
				$hours = sprintf( '%s &mdash; %s', $from, $to );
			}

			$open_hours[ $index ] = array(
				'day'       => $day,
				'date'      => date( 'd', $timestamp_adjusted ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'hours'     => $hours,
				'is_closed' => ! $open,
				'services'  => $services_on_day,
			);
		}

		/**
		 * Filter location upcoming open hours.
		 *
		 * @since 1.8.0
		 * @hook orderable_upcoming_open_hours
		 * @param array                     $open_hours Location upcoming open hours.
		 * @param Orderable_Location_Single $location   Location object.
		 */
		return apply_filters( 'orderable_upcoming_open_hours', $open_hours, $this );
	}

	/**
	 * Get slots for service type and day of the week.
	 *
	 * @param int    $timestamp Timestamp (GMT).
	 * @param string $type      Service type (delivery|pickup).
	 *
	 * @return array
	 */
	public function get_slots( $timestamp, $type = 'delivery' ) {
		$slots = array();

		if ( empty( $timestamp ) ) {
			return $slots;
		}

		$settings = $this->get_service_hours( $type );

		if ( empty( $settings ) ) {
			return $slots;
		}

		$date_time         = Orderable_Timings::get_date_time_by_timestamp( $timestamp );
		$current_timestamp = time();
		$timestamp         = ! $timestamp ? $current_timestamp : $timestamp;
		$day_number        = (int) $date_time->format( 'w' ); // 0 (Sunday) through 6 (Saturday).

		foreach ( $settings as $setting_key => $setting_row ) {
			$days = array_map( 'absint', $setting_row['days'] );

			if ( ! in_array( $day_number, $days, true ) ) {
				continue;
			}

			$slots = array(
				'all-day' => array(
					'formatted'   => __( 'All Day', 'orderable' ),
					'value'       => 'all-day',
					'timestamp'   => $timestamp,
					'setting_key' => $setting_key,
					'setting_row' => $setting_row,
				),
			);

			break;
		}

		if ( has_filter( 'orderable_get_slots' ) ) {
			_deprecated_hook( 'orderable_get_slots', '1.8.0', 'orderable_location_get_slots' );

			/**
			 * Filter location slots.
			 *
			 * @since 1.0.0
			 * @hook orderable_get_slots
			 * @deprecated 1.8.0 Use orderable_location_get_slots instead.
			 *
			 * @param array                     $slots     Location slots.
			 * @param int                       $timestamp Timestamp (GMT).
			 * @param string                    $type      The service type. Either 'delivery' or 'pickup'.
			 * @param Orderable_Location_Single $location  Location object.
			 */
			$slots = apply_filters( 'orderable_get_slots', $slots, $timestamp, $type, $this );
		}

		/**
		 * Filter location slots.
		 *
		 * @since 1.8.0
		 * @hook orderable_location_get_slots
		 *
		 * @param array                     $slots     Location slots.
		 * @param int                       $timestamp Timestamp (GMT).
		 * @param string                    $type      The service type. Either 'delivery' or 'pickup'.
		 * @param Orderable_Location_Single $location  Location object.
		 */
		return apply_filters( 'orderable_location_get_slots', $slots, $timestamp, $type, $this );
	}

	/**
	 * Get holidays.
	 *
	 * @param null $type             Service type (delivery/pickup).
	 * @param bool $include_defaults Include default holidays.
	 *
	 * @return array
	 */
	public function get_holidays( $type = null, $include_defaults = true ) {
		static $holidays_cache;

		if ( ! empty( $holidays_cache[ $this->get_location_id() ] ) ) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks
			return apply_filters( 'orderable_location_get_holidays', $holidays_cache[ $this->get_location_id() ], $type, $include_defaults, $this );
		}

		global $wpdb;

		$holidays = $include_defaults && $this->get_enable_default_holidays() ? (array) Orderable_Settings::get_setting( 'holidays' ) : array();

		$sql = "SELECT
			holiday_id,
			date_from 'from',
			date_to 'to',
			services,
			repeat_yearly 'repeat'
		FROM
			{$wpdb->orderable_location_holidays} holidays
		INNER JOIN
			{$wpdb->orderable_locations} locations
			ON
				locations.location_id = holidays.location_id 
		WHERE
			locations.location_id = %d";

		$holidays_query = $wpdb->get_results(
			$wpdb->prepare(
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$this->get_location_id()
			),
			ARRAY_A
		);

		/**
		 * Filter location holidays query result.
		 *
		 * @since 1.14.0
		 */
		$holidays_query = apply_filters( 'orderable_location_holidays_query_result', $holidays_query, $this );

		$holidays_query = ! empty( $holidays_query ) ? $holidays_query : array();
		$holidays       = array_merge( $holidays, $holidays_query );

		if ( ! empty( $holidays ) ) {
			$today           = new DateTime( 'now', wp_timezone() );
			$today_timestamp = $today->getTimestamp();

			foreach ( $holidays as $index => $holiday ) {
				if ( empty( $holiday['from'] ) ) {
					continue;
				}

				$holidays[ $index ]['services']   = maybe_unserialize( $holiday['services'] );
				$holidays[ $index ]['timestamps'] = array();

				$from = DateTime::createFromFormat( 'Y-m-d H:i:s', $holiday['from'] . ' 00:00:00', wp_timezone() );
				$to   = ! empty( $holiday['to'] ) ? DateTime::createFromFormat( 'Y-m-d H:i:s', $holiday['to'] . ' 00:00:00', wp_timezone() ) : clone $from;

				// Add one minute so last slot is included.
				$to->modify( '+1 minute' );

				// Add years to from and to if this holiday repeats and is in the past.
				if ( $today_timestamp > $to->getTimestamp() && ! empty( $holiday['repeat'] ) ) {
					// Calculate how many years have past and append 1 additional.
					$today_datetime = new DateTime();
					$today_datetime->setTimestamp( $today_timestamp );

					$interval = $today_datetime->diff( $to );
					$year     = intval( $interval->format( '%Y' ) ) + 1;

					$from->modify( '+' . $year . ' year' );
					$to->modify( '+' . $year . ' year' );

					$holidays[ $index ]['from'] = $from->format( 'Y-m-d' );
					$holidays[ $index ]['to']   = ! empty( $holidays[ $index ]['to'] ) ? $to->format( 'Y-m-d' ) : '';
				}

				$range = new DatePeriod(
					$from,
					new DateInterval( 'P1D' ), // Every 1 day.
					$to
				);

				if ( empty( $range ) ) {
					continue;
				}

				foreach ( $range as $time ) {
					$holidays[ $index ]['timestamps'][] = $time->getTimestamp();
				}
			}
		}

		/**
		 * Filter location holidays.
		 *
		 * @since 1.8.0
		 * @hook orderable_location_get_holidays
		 * @param array                     $holidays         Location holidays.
		 * @param string|null               $type             Service type (delivery/pickup).
		 * @param bool                      $include_defaults Include default holidays.
		 * @param Orderable_Location_Single $location         Location object.
		 */
		$holidays = apply_filters( 'orderable_location_get_holidays', $holidays, $type, $include_defaults, $this );

		$holidays_cache[ $this->get_location_id() ] = $holidays;

		return $holidays;
	}

	/**
	 * Is this timestamp a holiday?
	 *
	 * @param int         $timestamp Timestamp (GMT).
	 * @param string|null $type Service type (delivery/pickup).
	 *
	 * @return bool
	 */
	public function is_holiday( $timestamp, $type = null ) {
		$is_holiday = false;
		$holidays   = $this->get_holidays();

		// If no holidays are set, then this isn't a holiday.
		if ( ! empty( $holidays ) ) {
			foreach ( $holidays as $holiday ) {
				// If no timestamps or not assigned to any service, continue.
				if ( empty( $holiday['timestamps'] ) || empty( $holiday['services'] ) ) {
					continue;
				}

				// If timestamp isn't a holiday, continue.
				if ( ! in_array( $timestamp, $holiday['timestamps'], true ) ) {
					continue;
				}

				// If we want to check a specific delivery type, check that
				// service is assigned to this holiday. Otherwise, continue.
				if ( $type && ! in_array( $type, $holiday['services'], true ) ) {
					continue;
				}

				// If we got here, then this is indeed a holiday.
				$is_holiday = true;
				break;
			}
		}

		/**
		 * Filter if is a holiday.
		 *
		 * @since 1.8.0
		 * @hook orderable_location_is_holiday
		 * @param bool                      $is_holiday Is this timestamp a holiday?
		 * @param int                       $timestamp  Timestamp (GMT).
		 * @param string|null               $type       Service type (delivery/pickup).
		 * @param Orderable_Location_Single $location   Location object.
		 */
		return apply_filters( 'orderable_location_is_holiday', $is_holiday, $timestamp, $type, $this );
	}

	/**
	 * Get dates available for service type.
	 *
	 * @param string $type                  Type. Can be 'delivery' or 'pickup'.
	 * @param bool   $ignore_needs_shipping Ignore check for WC()->cart->needs_shipping().
	 *
	 * @return array|bool Array when dates are available, "true" when no date selection required, "false" when no dates available.
	 */
	public function get_service_dates( $type = false, $ignore_needs_shipping = false ) {
		$cache_key     = 'orderable_service_dates_' . md5( $type . (int) $ignore_needs_shipping . wp_json_encode( WC()->cart ) . $this->get_location_id() );
		$cached_result = wp_cache_get( $cache_key );

		if ( false !== $cached_result ) {
			return $cached_result;
		}

		/**
		 * Filter whether ignoring check for WC()->cart->needs_shipping() when
		 * getting service dates.
		 *
		 * @since 1.14.0
		 * @hook orderable_location_service_dates_ignore_needs_shipping
		 * @param bool   $ignore_needs_shipping Ignore check for WC()->cart->needs_shipping(). Default: false.
		 * @param string $service_type          It can be 'delivery' or 'pickup'.
		 * @return bool New value
		 */
		$ignore_needs_shipping = apply_filters( 'orderable_location_service_dates_ignore_needs_shipping', $ignore_needs_shipping, $type );

		if ( ! $ignore_needs_shipping && ! WC()->cart->needs_shipping() ) {
			// For backwards compatibility.
			$result = apply_filters_deprecated( 'orderable-service-dates', array( true, $type ), '1.8.0', 'orderable_location_service_dates' );
			// Return true. No service is required.

			/**
			 * Filter orderable service dates.
			 *
			 * @since 1.8.0
			 * @hook  orderable_location_service_dates
			 * @see   Orderable_Location_Single::get_service_dates()
			 */
			$service_dates = apply_filters( 'orderable_location_service_dates', $result, $type, $this );

			wp_cache_set( $cache_key, $service_dates, '', ORDERABLE_CACHE_EXPIRATION_TIME );

			return $service_dates;
		}

		$type          = ! $type ? Orderable_Services::get_selected_service( false ) : $type;
		$service_dates = array();

		if ( ! $type ) {
			// For backwards compatibility.
			$result = apply_filters_deprecated( 'orderable-service-dates', array( false, $type ), '1.8.0', 'orderable_location_service_dates' );
			// Return false. A service should be selected.
			// @todo Check if this should be true when no shipping method is selected yet.

			/**
			 * Filter orderable service dates.
			 *
			 * @since 1.8.0
			 * @hook  orderable_location_service_dates
			 * @see   Orderable_Location_Single::get_service_dates()
			 */
			$service_dates = apply_filters( 'orderable_location_service_dates', $result, $type, $this );

			wp_cache_set( $cache_key, $service_dates, '', ORDERABLE_CACHE_EXPIRATION_TIME );

			return $service_dates;
		}

		$services = $this->get_services();

		if ( ! in_array( $type, $services, true ) ) {
			// For backwards compatibility.
			$result = apply_filters_deprecated( 'orderable-service-dates', array( false, $type ), '1.8.0', 'orderable_location_service_dates' );

			/**
			 * Filter orderable service dates.
			 *
			 * @since 1.8.0
			 * @hook  orderable_location_service_dates
			 * @see   Orderable_Location_Single::get_service_dates()
			 */
			$service_dates = apply_filters( 'orderable_location_service_dates', $result, $type, $this );

			wp_cache_set( $cache_key, $service_dates, '', ORDERABLE_CACHE_EXPIRATION_TIME );

			return $service_dates;
		}

		$min_max_method   = $this->get_delivery_calculation_method();
		$lead_time_period = $this->get_lead_time_period();
		$lead_days        = 'days' === $lead_time_period ? $this->get_lead_time() : 0;
		$preorder_days    = $this->get_preorder_days();
		$service_days     = $this->get_service_days( $type );

		$start_date = new DateTime( 'now', wp_timezone() );
		$start_date->setTime( 0, 0 ); // Set time to midnight 00:00:00.

		$date_range = new ArrayIterator( array( $start_date ) );

		$counted_lead_days     = 0;
		$counted_preorder_days = 0;

		if ( ! empty( $service_days ) ) {
			/**
			 * Filter the max index date.
			 *
			 * Since the condition to break the loop that
			 * searchs for service dates relies on the
			 * preorder days limit, we check against $max_index_date
			 * to prevent an infinite loop case preorder days
			 * limit fail multiple times.
			 *
			 * @since 1.8.1
			 * @hook orderable_location_max_index_date
			 * @param  int                       $max_index_date The max index date value. Default: 365.
			 * @param  string                    $type           The type. Can be 'delivery' or 'pickup'.
			 * @param  Orderable_Location_Single $location       The location.
			 * @return int New value
			 */
			$max_index_date = apply_filters( 'orderable_location_max_index_date', 365, $type, $this );

			foreach ( $date_range as $index => $date ) {
				// Check to avoid an infinite loop.
				if ( $index > $max_index_date ) {
					break;
				}

				// If we're at the preorder day limit, break the loop.
				if ( $counted_preorder_days > $preorder_days ) {
					break;
				}

				$timestamp = $date->getTimestamp();
				$week_day  = $date->format( 'w' );
				$date_range->append( clone $date->modify( '+1 day' ) );

				// If calculation method is 'all', we want to increase
				// the counters for all days.
				if ( 'all' === $min_max_method ) {
					$counted_lead_days ++;
					$counted_preorder_days ++;

					// We aren't ready to start serving up dates yet as we
					// haven't counted the number of lead days required.
					if ( $lead_days >= $counted_lead_days ) {
						continue;
					}
				}

				// If calculation method is 'weekdays' (Weekdays), we want to
				// increase the counters here if the day is a weekday. This
				// happens before checking if it's a service day.
				if ( 'weekdays' === $min_max_method ) {
					if ( Orderable_Timings::is_weekday( $timestamp ) ) {
						$counted_lead_days ++;
						$counted_preorder_days ++;
					}

					// We aren't ready to start serving up dates yet as we
					// haven't counted the number of lead days required.
					if ( $lead_days >= $counted_lead_days ) {
						continue;
					}
				}

				// If this date is a holiday, add a date to the array and continue.
				if ( $this->is_holiday( $timestamp, $type ) ) {
					continue;
				}

				// If calculation method is 'open' (Open Days), we want to
				// increase the counters here if the store is open. This
				// happens before checking if it's a service day.
				if ( 'open' === $min_max_method ) {
					if ( $this->is_open( $timestamp ) ) {
						$counted_lead_days ++;
						$counted_preorder_days ++;

						// We aren't ready to start serving up dates yet as we
						// haven't counted the number of lead days required.
						if ( $lead_days >= $counted_lead_days ) {
							continue;
						}
					}
				}

				// If this is not a service day, skip it.
				if ( ! in_array( (int) $week_day, array_keys( $service_days ), true ) ) {
					continue;
				}

				// If calculation method is 'service' (Service Days), we want to increase
				// the counters here, as any date past this point is a service day.
				if ( 'service' === $min_max_method ) {
					$counted_lead_days ++;
					$counted_preorder_days ++;

					// We aren't ready to start serving up dates yet as we
					// haven't counted the number of lead days required.
					if ( $lead_days >= $counted_lead_days ) {
						continue;
					}
				}

				/**
				 * Filter whether a date is available for ordering.
				 *
				 * @since 1.8.0
				 * @hook  orderable_date_available
				 * @see   Orderable_Location_Single::get_service_dates()
				 */
				if ( ! apply_filters( 'orderable_date_available', true, $timestamp, $type, $this ) ) {
					continue;
				}

				$format = get_option( 'date_format' );
				$slots  = $this->get_slots( $timestamp, $type );

				if ( empty( $slots ) ) {
					continue;
				}

				$service_dates[] = array(
					'timestamp' => $timestamp,
					'datetime'  => date( $format, $timestamp ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'formatted' => Orderable_Timings::get_formatted_date( $timestamp ),
					'slots'     => $slots,
				);
			}
		}

		// If empty, return false; no dates available.
		// Otherwise, return dates.
		$service_dates = empty( $service_dates ) ? false : $service_dates;

		// For backwards compatibility.
		$service_dates = apply_filters_deprecated( 'orderable-service-dates', array( $service_dates, $type ), '1.8.0', 'orderable_location_service_dates' );

		/**
		 * Filter orderable service dates.
		 *
		 * @param array|bool                 $service_dates  The service dates available. If true,
		 *                                                   the service doesn't require date/time selection.
		 *                                                   If false, a service should be selected.
		 * @param string                     $type           The type of service. E.g. delivery, pickup.
		 * @param Orderable_Location_Single  $location       The location instance.
		 *
		 * @return array|bool New value
		 * @since 1.8.0
		 * @hook  orderable_location_service_dates
		 */
		$service_dates = apply_filters( 'orderable_location_service_dates', $service_dates, $type, $this );

		wp_cache_set( $cache_key, $service_dates, '', ORDERABLE_CACHE_EXPIRATION_TIME );

		return $service_dates;
	}

	/**
	 * Check if location has service dates for this service type.
	 *
	 * @param string $service_type Service type (delivery/pickup).
	 *
	 * @return bool
	 */
	public function has_service_dates( $service_type ) {
		$service_dates = $this->get_service_dates( $service_type, true );

		return ! empty( $service_dates );
	}

	/**
	 * Get location address.
	 *
	 * @return array
	 */
	public function get_address() {
		$address = array(
			'address_line_1' => $this->location_data['address_line_1'],
			'address_line_2' => $this->location_data['address_line_2'],
			'city'           => $this->location_data['city'],
			'country_state'  => $this->location_data['country_state'],
			'postcode_zip'   => $this->location_data['postcode_zip'],
		);

		return $address;
	}

	/**
	 * Get formatted address.
	 *
	 * @return string
	 */
	public function get_formatted_address() {
		$address = $this->get_address();

		$country_state = $address['country_state'];

		if ( strstr( $country_state, ':' ) ) {
			$country_state = explode( ':', $country_state );
			$country       = current( $country_state );
			$state         = end( $country_state );
		} else {
			$country = $country_state;
			$state   = '';
		}

		$state   = empty( WC()->countries->get_states( $country )[ $state ] ) ? $state : WC()->countries->get_states( $country )[ $state ];
		$country = empty( WC()->countries->get_countries()[ $country ] ) ? $country : WC()->countries->get_countries()[ $country ];

		$data = array(
			'address_1' => $address['address_line_1'],
			'address_2' => $address['address_line_2'],
			'city'      => $address['city'],
			'state'     => $state,
			'country'   => $country,
			'postcode'  => $address['postcode_zip'],
		);

		return WC()->countries->get_formatted_address( $data, ', ' );
	}

	/**
	 * Check if location has a specific zone set.
	 *
	 * @param int  $zone_id     Zone ID.
	 * @param bool $allow_empty Allow slots with no zone set.
	 *
	 * @return bool
	 */
	public function has_zone( $zone_id, $allow_empty = false ) {
		global $wpdb;

		$location_id  = $this->get_location_id();
		$cache_key    = "has_zone_{$location_id}_{$zone_id}_{$allow_empty}";
		$cache_result = wp_cache_get( $cache_key );

		if ( false !== $cache_result ) {
			return (bool) $cache_result;
		}

		$allow_empty_clause = $allow_empty ? 'OR (l.zone_id IS NULL AND ts.has_zones = 0)' : '';

		$query = $wpdb->prepare(
			"SELECT 
    			COUNT(*) 
			FROM 
				{$wpdb->prefix}orderable_location_delivery_zones_lookup l
			LEFT JOIN 
				{$wpdb->prefix}orderable_location_time_slots ts
				ON l.time_slot_id = ts.time_slot_id
			WHERE 
				l.location_id = %d 
			AND 
				( l.zone_id = %d {$allow_empty_clause} )",
			$location_id,
			$zone_id
		);

		$result = $wpdb->get_var( $query );

		$has_zone = $result > 0;

		wp_cache_set( $cache_key, (int) $has_zone );

		return $has_zone;
	}

	/**
	 * Update location title.
	 *
	 * @param string $title New title.
	 */
	public function update_title( $title ) {
		global $wpdb;

		$this->location_data['title'] = $title;

		wp_update_post(
			array(
				'ID'         => $this->location_data['post_id'],
				'post_title' => $title,
			)
		);

		$wpdb->update(
			$wpdb->prefix . 'orderable_locations',
			array(
				'title' => $title,
			),
			array(
				'location_id' => $this->location_data['location_id'],
			)
		);

	}
}
