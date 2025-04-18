<?php
/**
 * Module: Timings.
 *
 * Delivery/pickup date and time and lead times.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Timings module class.
 */
class Orderable_Timings {
	/**
	 * Init.
	 */
	public static function run() {
		self::load_classes();
		add_action( 'init', array( __CLASS__, 'add_shortcodes' ) );
	}

	/**
	 * Load classes for this module.
	 */
	public static function load_classes() {
		$classes = array(
			'timings-blocks'   => 'Orderable_Timings_Blocks',
			'timings-settings' => 'Orderable_Timings_Settings',
			'timings-checkout' => 'Orderable_Timings_Checkout',
			'timings-order'    => 'Orderable_Timings_Order',
		);

		foreach ( $classes as $file_name => $class_name ) {
			require_once ORDERABLE_MODULES_PATH . 'timings/class-' . $file_name . '.php';

			$class_name::run();
		}
	}

	/**
	 * Add services shrotcodes.
	 */
	public static function add_shortcodes() {
		add_shortcode( 'orderable-open-hours', array( __CLASS__, 'orderable_open_hours_shortcode' ) );
	}

	/**
	 * Get days of the week.
	 *
	 * @param string $label    Label length 'full' or 'short'.
	 * @param int    $last_day Last day of the week.
	 *
	 * @return array
	 */
	public static function get_days_of_the_week( $label = 'full', $last_day = 6 ) {
		$days = array(
			0 => 'full' === $label ? __( 'Sunday', 'orderable' ) : __( 'Sun', 'orderable' ),
			1 => 'full' === $label ? __( 'Monday', 'orderable' ) : __( 'Mon', 'orderable' ),
			2 => 'full' === $label ? __( 'Tuesday', 'orderable' ) : __( 'Tue', 'orderable' ),
			3 => 'full' === $label ? __( 'Wednesday', 'orderable' ) : __( 'Wed', 'orderable' ),
			4 => 'full' === $label ? __( 'Thursday', 'orderable' ) : __( 'Thu', 'orderable' ),
			5 => 'full' === $label ? __( 'Friday', 'orderable' ) : __( 'Fri', 'orderable' ),
			6 => 'full' === $label ? __( 'Saturday', 'orderable' ) : __( 'Sat', 'orderable' ),
		);

		if ( 6 !== $last_day ) {
			$index = $last_day + 1;
			$start = array_slice( $days, $index, null, true );
			$end   = array_slice( $days, 0, $index, true );

			$days = $start + $end;
		}

		return $days;
	}

	/**
	 * Get formatted date.
	 *
	 * @param int $timestamp GMT timestamp
	 *
	 * @return string|void
	 * @throws Exception
	 */
	public static function get_formatted_date( $timestamp ) {
		$format             = get_option( 'date_format' );
		$timestamp_adjusted = self::get_timestamp_adjusted( $timestamp );
		$date               = date_i18n( $format, $timestamp_adjusted );

		if ( self::is_today( $timestamp ) ) {
			$date = __( 'Today', 'orderable' );
		} elseif ( self::is_tomorrow( $timestamp ) ) {
			$date = __( 'Tomorrow', 'orderable' );
		}

		return apply_filters( 'orderable_get_formatted_date', $date, $timestamp );
	}

	/**
	 * Is this timestamp today?
	 *
	 * @param int $timestamp GMT timestamp.
	 *
	 * @return bool
	 */
	public static function is_today( $timestamp ) {
		$timestamp_date = date( 'Y-m-d', self::get_timestamp_adjusted( $timestamp ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$current_date   = current_time( 'Y-m-d' );

		return $timestamp_date === $current_date;
	}

	/**
	 * Is this timestamp today?
	 *
	 * @param int $timestamp GMT timestamp
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function is_tomorrow( $timestamp ) {
		$timestamp_date = date( 'Y-m-d', self::get_timestamp_adjusted( $timestamp ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$current_date   = current_time( 'timestamp' );
		$tomorrows_date = date( 'Y-m-d', strtotime( '+1 day', $current_date ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		return $timestamp_date === $tomorrows_date;
	}

	/**
	 * Is weekday?
	 *
	 * @param int $timestamp GMT timestamp.
	 *
	 * @return bool
	 */
	public static function is_weekday( $timestamp ) {
		$weekdays       = array( 1, 2, 3, 4, 5 );
		$timestamp_date = absint( date( 'w', self::get_timestamp_adjusted( $timestamp ) ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		return in_array( $timestamp_date, $weekdays, true );
	}

	/**
	 * Get timezone offset for the given timestamp.
	 *
	 * This function is inspired by wc_timezone_offset().
	 * wc_timezone_offset() always returns the offset for today's date,
	 * which is inacurate during daylight savings.
	 * Hence this function, which returns offset for specified date.
	 *
	 * @param int $timestamp Timestamp.
	 *
	 * @return mixed
	 */
	public static function get_timezone_offset( $timestamp ) {
		$timezone = get_option( 'timezone_string' );

		if ( $timezone ) {
			$timezone_object = new DateTimeZone( $timezone );
			$datetime        = new DateTime();

			$datetime->setTimestamp( $timestamp );

			return $timezone_object->getOffset( $datetime );
		} else {
			return floatval( get_option( 'gmt_offset', 0 ) ) * HOUR_IN_SECONDS;
		}
	}

	/**
	 * Adjust GMT timestamp to correct time zone.
	 *
	 * @param int $timestamp Timestamp (GMT).
	 *
	 * @return float|int|mixed
	 */
	public static function get_timestamp_adjusted( $timestamp ) {
		return $timestamp + self::get_timezone_offset( $timestamp );
	}

	/**
	 * Open hours shortcode.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function orderable_open_hours_shortcode( $args = array() ) {
		$defaults = array(
			'location_id' => null,
			'services'    => true,
			'date'        => true,
		);

		$args                        = wp_parse_args( $args, $defaults );
		$args['services']            = (bool) json_decode( strtolower( $args['services'] ) );
		$args['date']                = (bool) json_decode( strtolower( $args['date'] ) );
		$args['location']            = empty( $args['location_id'] ) || ! Orderable_Location::store_has_multi_locations() ? Orderable_Location::get_selected_location() : new Orderable_Location_Single( $args['location_id'] );
		$args['upcoming_open_hours'] = $args['location']->get_upcoming_open_hours();

		ob_start();

		include Orderable_Helpers::get_template_path( 'open-hours.php', 'timings' );

		return ob_get_clean();
	}

	/**
	 * Get date/time by timestamp in correct timezone.
	 *
	 * @param $timestamp
	 *
	 * @return DateTime
	 * @throws Exception
	 */
	public static function get_date_time_by_timestamp( $timestamp ) {
		$date = new DateTime( 'now', wp_timezone() );
		$date->setTimestamp( $timestamp );

		return $date;
	}

	/**
	 * Convert time array to 24 hour.
	 *
	 * @param array $time
	 *
	 * @return array
	 */
	public static function convert_time_to_24_hour( $time ) {
		$time['hour'] = absint( $time['hour'] );

		if ( 'PM' === $time['period'] && 12 !== $time['hour'] ) {
			$time['hour'] = $time['hour'] + 12;
		}

		if ( 'AM' === $time['period'] && 12 === $time['hour'] ) {
			$time['hour'] = 0;
		}

		return $time;
	}
}
