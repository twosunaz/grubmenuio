<?php
/**
 * Orderable database.
 *
 * @package Orderable/Database
 */

defined( 'ABSPATH' ) || exit;

/**
 * Addons module class.
 */
class Orderable_Database {

	/**
	 * Run database related operations.
	 *
	 * @return void
	 */
	public static function run() {
		self::load_table_classes();

		if ( is_admin() && ! wp_doing_ajax() ) {
			add_action( 'orderable_after_create_custom_tables', array( __CLASS__, 'upgrades' ) );
			self::create_tables();
		}

		self::append_custom_tables_to_wpdb();
	}

	/**
	 * Load table classes.
	 *
	 * @return void
	 */
	protected static function load_table_classes() {
		Orderable_Helpers::load_classes(
			self::get_table_classes(),
			'database/tables',
			ORDERABLE_INC_PATH
		);
	}

	/**
	 * Get table classes name.
	 *
	 * @return array
	 */
	protected static function get_table_classes() {
		return array(
			'location-holidays-table'              => 'Orderable_Location_Holidays_Table',
			'location-time-slots-table'            => 'Orderable_Location_Time_Slots_Table',
			'location-locations-table'             => 'Orderable_Location_Locations_Table',
			'location-delivery-zones-lookup-table' => 'Orderable_Location_Delivery_Zones_Lookup_Table',
		);
	}

	/**
	 * Create Orderable custom tables.
	 *
	 * @return void
	 */
	protected static function create_tables() {
		global $wpdb;

		$current_db_version = get_option( '_orderable_db_version' );

		if ( ! empty( $current_db_version ) ) {
			return;
		}

		if ( 'yes' === get_transient( 'orderable_creating_database' ) ) {
			return;
		}

		$wpdb->hide_errors();

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		set_transient( 'orderable_creating_database', 'yes', MINUTE_IN_SECONDS * 10 );

		/**
		 * Fires before creating Orderable custom tables.
		 *
		 * @since 1.8.0
		 * @hook orderable_before_create_custom_tables
		 */
		do_action( 'orderable_before_create_custom_tables' );

		$schema             = '';
		$create_table_query = '';
		foreach ( self::get_table_classes() as $table_class ) {
			if ( ! class_exists( $table_class ) ) {
				continue;
			}

			$table_name = $wpdb->prefix . $table_class::get_table_name();
			$schema     = $table_class::get_schema();

			$create_table_query .= "CREATE TABLE $table_name $schema $collate;";
		}

		dbDelta( $create_table_query );

		delete_transient( 'orderable_creating_database' );

		/**
		 * Fires after creating Orderable custom tables.
		 *
		 * @since 1.8.0
		 * @hook orderable_after_create_custom_tables
		 */
		do_action( 'orderable_after_create_custom_tables' );

		update_option( '_orderable_db_version', ORDERABLE_VERSION );
	}

	/**
	 * Append Orderable custom tables names to $wpdb object.
	 *
	 * That way, Orderable custom tables name can be accessed
	 * like that `$wpdb->orderable_location_holidays`.
	 *
	 * @return void
	 */
	protected static function append_custom_tables_to_wpdb() {
		global $wpdb;

		if ( ! get_option( '_orderable_db_version' ) ) {
			return;
		}

		foreach ( self::get_table_classes() as $table_class ) {
			if ( ! class_exists( $table_class ) ) {
				continue;
			}

			$table_name = $table_class::get_table_name();

			$wpdb->$table_name = $wpdb->prefix . $table_name;
			$wpdb->tables[]    = $table_name;
		}
	}

	/**
	 * Run database upgrades.
	 *
	 * @return void
	 */
	public static function upgrades() {
		$routines = array(
			'1.8.0' => 'upgrade_1_8_0',
		);

		array_walk( $routines, array( __CLASS__, 'run_upgrade_routine' ) );
	}

	/**
	 * Run a upgrade routine
	 *
	 * @param string $routine The function name.
	 * @param string $version The version number to be tested.
	 * @return void
	 */
	protected static function run_upgrade_routine( $routine, $version ) {
		$orderable_db_version = get_option( '_orderable_db_version' );

		if ( ! empty( $orderable_db_version ) && version_compare( $orderable_db_version, $version, '>=' ) ) {
			return;
		}

		self::$routine();
	}

	/**
	 * Upgrade routine to v1.8.0.
	 *
	 * @return void
	 */
	public static function upgrade_1_8_0() {
		/**
		 * Fires on the database upgrade routine.
		 *
		 * @since 1.8.0
		 * @hook orderable_upgrade_database_routine
		 * @param  string $version The upgrade version routine. E.g.: 1.8.0.
		 */
		do_action( 'orderable_upgrade_database_routine', '1.8.0' );
	}
}
