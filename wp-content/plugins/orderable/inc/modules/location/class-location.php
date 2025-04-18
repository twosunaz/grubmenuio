<?php
/**
 * Module: Location.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Addons module class.
 */
class Orderable_Location {
	/**
	 * Init.
	 */
	public static function run() {
		self::load_classes();

		add_filter( 'orderable_settings', array( __CLASS__, 'add_location_settings_data' ), 20, 2 );
	}

	/**
	 * Load classes.
	 *
	 * @return void
	 */
	public static function load_classes() {
		$classes = array(
			'admin'    => array(
				'location-admin' => 'Orderable_Location_Admin',
			),
			'frontend' => array(
				'location-single' => 'Orderable_Location_Single',
			),
			'zones'    => array(
				'location-zones'              => 'Orderable_Location_Zones',
				'location-zones-admin'        => 'Orderable_Location_Zones_Admin',
				'location-zones-crud-handler' => 'Orderable_Location_Zones_CRUD_Handler',
			),
		);

		Orderable_Helpers::load_classes( $classes['admin'], 'location/admin', ORDERABLE_MODULES_PATH );
		Orderable_Helpers::load_classes( $classes['zones'], 'location/zones', ORDERABLE_MODULES_PATH );
		Orderable_Helpers::load_classes( $classes['frontend'], 'location', ORDERABLE_MODULES_PATH );
	}

	/**
	 * Append location data to Orderable settings.
	 *
	 * This function is used to intercept Orderable_Settings::get_setting
	 * and add the location settings retrieved from the Orderable custom
	 * tables. That way, we keep the compatibility with
	 * `Orderable_Settings::get_setting()` function.
	 *
	 * @param false|array $settings     The orderable settings.
	 * @param string      $setting_name The setting name to be retrieved.
	 *
	 * @return false|array
	 */
	public static function add_location_settings_data( $settings, $setting_name ) {
		$location_setting_keys = array(
			'store_general_service_hours_delivery',
			'store_general_service_hours_pickup',
			'store_general_services',
			'store_general_service_hours_pickup_same',
			'store_general_asap',
			'store_general_lead_time',
			'store_general_preorder',
			'store_general_calculation_method',
			'orderable_override_open_hours',
		);

		if ( ! in_array( $setting_name, $location_setting_keys, true ) ) {
			return $settings;
		}

		$location = self::get_main_location();

		$settings['store_general_service_hours_delivery']    = $location->get_service_hours( 'delivery' );
		$settings['store_general_service_hours_pickup']      = $location->get_service_hours( 'pickup' );
		$settings['store_general_services']                  = $location->get_services();
		$settings['store_general_service_hours_pickup_same'] = $location->get_pickup_hours_same_as_delivery();
		$settings['store_general_asap']                      = $location->get_asap_settings();
		$settings['store_general_lead_time']                 = $location->get_lead_time();
		$settings['store_general_preorder']                  = $location->get_preorder_days();
		$settings['store_general_calculation_method']        = $location->get_delivery_calculation_method();
		$settings['orderable_override_open_hours']           = $location->get_override_default_open_hours();

		return $settings;
	}

	/**
	 * Check if should return the location setting default value.
	 *
	 * @return boolean
	 */
	protected static function should_return_default_value() {
		global $post;

		/**
		 * Since we intercept the `Orderable_Settings::get_setting()` function to keep
		 * compatible with Location feature, we need to check it to avoid hitting the database
		 * early.
		 */
		if ( is_admin() && ! wp_doing_ajax() && ! function_exists( 'get_current_screen' ) ) {
			return true;
		}

		// If it's creating a new Location, return the default value.
		if ( ! empty( $post->post_status ) && 'auto-draft' === $post->post_status ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the store has multi locations.
	 *
	 * @return bool
	 */
	public static function store_has_multi_locations() {
		return class_exists( 'Orderable_Multi_Location_Pro' );
	}

	/**
	 * Get the post ID associated with the location.
	 *
	 * @return null|int.
	 */
	protected static function get_post_id() {
		global $post;

		$id = null;

		if ( ! self::store_has_multi_locations() ) {
			return $id;
		}

		if ( is_checkout() || Orderable_Multi_Location_Pro_Search::is_searching() ) {
			$location = Orderable_Multi_Location_Pro::get_selected_location_data_from_session();

			return ! empty( $location['id'] ) ? self::get_location_post_id( $location['id'] ) : $id;
		}

		// We use the post ID when we are editing a location that is not the main one.
		if (
			! empty( $post ) &&
			'orderable_locations' === $post->post_type
		) {
			$id = $post->ID;
		}

		return $id;
	}

	/**
	 * Get the WHERE condition to `location_id` field.
	 *
	 * @param int $location_id The location ID.
	 *
	 * @return string
	 */
	protected static function location_id_where( $location_id ) {
		return empty( $location_id ) ? ' AND locations.is_main_location = 1' : ' AND locations.location_id = %d';
	}

	/**
	 * Get a default open hours.
	 *
	 * This function is useful when the plugin couldn't
	 * retrieve the location open hours from the
	 * database.
	 *
	 * @return array
	 */
	public static function get_default_open_hours() {
		$default_day = array(
			'enabled'    => false,
			'from'       => array(
				'hour'   => 9,
				'minute' => '00',
				'period' => 'AM',
			),
			'to'         => array(
				'hour'   => 5,
				'minute' => '00',
				'period' => 'PM',
			),
			'max_orders' => '',
		);

		$open_hours = array(
			$default_day,
			$default_day,
			$default_day,
			$default_day,
			$default_day,
			$default_day,
			$default_day,
		);

		return apply_filters( 'orderable_location_get_default_open_hours', $open_hours );
	}

	/**
	 * Get the location ID.
	 *
	 * @param int|false $post_id The post ID associated with a Location.
	 *
	 * @return bool|int
	 */
	public static function get_location_id( $post_id = false ) {
		global $wpdb;

		static $location_post_ids = array();

		if ( empty( $post_id ) ) {
			$post_id = self::get_post_id();
		}

		// If it's empty, we should try to find it again.
		if ( ! empty( $location_post_ids[ $post_id ] ) ) {
			return $location_post_ids[ $post_id ];
		}

		if ( empty( $post_id ) ) {
			$location_id = $wpdb->get_var(
				"SELECT
					location_id
				FROM
					{$wpdb->orderable_locations}
				WHERE
					is_main_location = 1
				LIMIT 1
				"
			);
		} else {
			$location_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT
						location_id
					FROM
						{$wpdb->orderable_locations}
					WHERE
						post_id = %d
					LIMIT 1
					",
					$post_id
				)
			);
		}

		$location_post_ids[ $post_id ] = empty( $location_id ) ? false : absint( $location_id );

		return $location_post_ids[ $post_id ];
	}

	/**
	 * Get the post ID associated with a location.
	 *
	 * @return int|false
	 */
	public static function get_main_location_post_id() {
		_deprecated_function( __METHOD__, '1.16.0' );

		global $wpdb;

		$post_id = $wpdb->get_var(
			"SELECT
				post_id
			FROM
				{$wpdb->orderable_locations}
			WHERE
				is_main_location = 1
				AND
				post_id IS NOT NULL
			LIMIT
				1
			"
		);

		return empty( $post_id ) ? false : (int) $post_id;
	}

	/**
	 * Check if it's the main location.
	 *
	 * @param int $location_id The location ID.
	 *
	 * @return boolean
	 */
	public static function is_main_location( $location_id ) {
		global $wpdb;

		if ( empty( $location_id ) ) {
			return false;
		}

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT
					location_id
				FROM
					{$wpdb->orderable_locations}
				WHERE
					is_main_location = 1
					AND
					location_id = %d
				LIMIT
					1
				",
				$location_id
			)
		);

		return ! empty( $result );
	}

	/**
	 * Get the post ID associated with a location.
	 *
	 * @param string $location_id The location ID.
	 *
	 * @return int|false
	 */
	public static function get_location_post_id( $location_id = '' ) {
		global $wpdb;
		static $location_post_ids = array();

		if ( empty( $location_id ) ) {
			$location_id = self::get_location_id();
		}

		if ( empty( $location_id ) ) {
			return false;
		}

		if ( isset( $location_post_ids[ $location_id ] ) ) {
			return $location_post_ids[ $location_id ];
		}

		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT
					post_id
				FROM
					{$wpdb->orderable_locations}
				WHERE
					location_id = %d
				LIMIT
					1
				",
				$location_id
			)
		);

		$location_post_ids[ $location_id ] = empty( $post_id ) ? false : (int) $post_id;

		return $location_post_ids[ $location_id ];
	}

	/**
	 * Get the main location ID.
	 *
	 * @return int|false
	 */
	public static function get_main_location_id() {
		$cache_key        = 'orderable_main_location_id';
		$main_location_id = wp_cache_get( $cache_key );

		if ( false !== $main_location_id ) {
			return $main_location_id;
		}

		global $wpdb;

		$main_location_id = $wpdb->get_var(
			"SELECT
				location_id
			FROM
				{$wpdb->prefix}orderable_locations
			WHERE
				is_main_location = 1
			LIMIT
				1
			"
		);

		$main_location_id = empty( $main_location_id ) ? false : absint( $main_location_id );

		wp_cache_set( $cache_key, $main_location_id );

		return $main_location_id;
	}

	/**
	 * Get the main location.
	 *
	 * @return Orderable_Location_Single|false
	 */
	public static function get_main_location() {
		$cache_key     = 'orderable_main_location';
		$main_location = wp_cache_get( $cache_key );

		if ( false !== $main_location ) {
			return new Orderable_Location_Single( $main_location );
		}

		global $wpdb;

		$main_location = $wpdb->get_row(
			"SELECT
				*
			FROM
				{$wpdb->prefix}orderable_locations
			WHERE
				is_main_location = 1
			LIMIT
				1
			",
			ARRAY_A
		);

		$main_location = empty( $main_location ) ? false : $main_location;

		wp_cache_set( $cache_key, $main_location );

		return new Orderable_Location_Single( $main_location );
	}

	/**
	 * Get location data.
	 *
	 * @param int $location_id The location ID.
	 *
	 * @return array|false
	 */
	public static function get_location_data( $location_id ) {
		$cache_key = 'orderable_get_location_data_' . $location_id;
		$location  = wp_cache_get( $cache_key );

		if ( false !== $location ) {
			return $location;
		}

		global $wpdb;

		$location = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					*
				FROM
					{$wpdb->prefix}orderable_locations
				WHERE
					location_id = %d
				LIMIT
					1
				",
				$location_id
			),
			ARRAY_A
		);

		$location = empty( $location ) ? false : $location;

		wp_cache_set( $cache_key, $location );

		return $location;
	}

	/**
	 * Get the selected location.
	 *
	 * @return Orderable_Location_Single|false
	 */
	public static function get_selected_location() {
		/**
		 * Filter the selected location.
		 *
		 * @since 1.8.0
		 * @hook orderable_location_get_selected_location
		 * @param Orderable_Location|false $location The location object.
		 */
		return apply_filters( 'orderable_location_get_selected_location', self::get_main_location() );
	}

	/**
	 * Get the selected location ID.
	 *
	 * @return Orderable_Location_Single|false
	 */
	public static function get_selected_location_id() {
		/**
		 * Filter the selected location ID.
		 *
		 * @since 1.8.0
		 * @hook orderable_location_get_selected_location_id
		 * @param int|false $location_id The location ID.
		 */
		return apply_filters( 'orderable_location_get_selected_location_id', self::get_main_location_id() );
	}
}
