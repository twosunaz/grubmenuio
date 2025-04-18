<?php
/**
 * Locations table.
 *
 * @package Orderable/Database
 */

defined( 'ABSPATH' ) || exit;

/**
 * Locations table class.
 */
class Orderable_Location_Locations_Table {
	/**
	 * Run table operations.
	 *
	 * @return void
	 */
	public static function run() {
		add_action( 'orderable_upgrade_database_routine', array( __CLASS__, 'upgrades' ), 5 );
	}

	/**
	 * Get the table name without the prefix.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		return 'orderable_locations';
	}

	/**
	 * Get the table schema.
	 *
	 * The schema returned is used as input to
	 * dbDelta() function to create or update the
	 * table structure.
	 *
	 * dbDelta has some rules that need to be followed:
	 * https://codex.wordpress.org/Creating_Tables_with_Plugins
	 *
	 * @return string
	 */
	public static function get_schema() {
		$schema = '(
  location_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id BIGINT UNSIGNED NULL,
  title text NULL,
  address_line_1 text NULL,
  address_line_2 text NULL,
  city text NULL,
  country_state text NULL,
  postcode_zip text NULL,
  override_default_open_hours boolean DEFAULT NULL,
  open_hours longtext NULL,
  delivery boolean DEFAULT NULL,
  pickup boolean DEFAULT NULL,
  pickup_hours_same_as_delivery boolean DEFAULT NULL,
  asap_date boolean DEFAULT NULL,
  asap_time boolean DEFAULT NULL,
  lead_time tinytext NULL,
  lead_time_period tinytext NULL,
  preorder tinytext NULL,
  delivery_days_calculation_method tinytext NULL,
  enable_default_holidays boolean DEFAULT NULL,
  is_main_location boolean DEFAULT NULL,
  image_id BIGINT NULL,
  menu_order int(11) NULL,
  PRIMARY KEY  (location_id),
  UNIQUE KEY post_id (post_id)
)';

		return $schema;
	}

	/**
	 * Run database upgrades.
	 *
	 * @param string $version The plugin version.
	 * @return void
	 */
	public static function upgrades( $version ) {
		if ( '1.8.0' === $version ) {
			self::migrate_main_location_to_custom_table();
		}
	}

	/**
	 * Migrate the main store data to the Orderable custom table.
	 *
	 * @return void
	 */
	protected static function migrate_main_location_to_custom_table() {
		global $wpdb;

		$settings = get_option( 'orderable_settings' );

		if ( empty( $settings['store_general_services'] ) || ! is_array( $settings['store_general_services'] ) ) {
			$delivery = (int) false;
			$pickup   = (int) false;
		} else {
			$delivery = (int) in_array( 'delivery', $settings['store_general_services'], true );
			$pickup   = (int) in_array( 'pickup', $settings['store_general_services'], true );

			update_option( '_orderable_main_location_store_general_services_settings_to_migrate', $settings['store_general_services'] );
		}

		if ( empty( $settings['store_general_asap'] ) || ! is_array( $settings['store_general_asap'] ) ) {
			$asap_date = (int) false;
			$asap_time = (int) false;
		} else {
			$asap_date = (int) in_array( 'day', $settings['store_general_asap'], true );
			$asap_time = (int) in_array( 'slot', $settings['store_general_asap'], true );

			update_option( '_orderable_main_location_store_general_asap_settings_to_migrate', $settings['store_general_asap'] );
		}

		if ( ! empty( $settings['store_general_open_hours'] ) ) {
			foreach ( $settings['store_general_open_hours'] as $day_number => $open_hour ) {
				$settings['store_general_open_hours'][ $day_number ]['enabled'] = ! empty( $open_hour['enabled'] );
			}

			update_option( '_orderable_main_location_store_general_open_hours_settings_to_migrate', $settings['store_general_open_hours'] );
		}

		if ( ! empty( $settings['store_general_service_hours_pickup_same'] ) ) {
			update_option( '_orderable_main_location_store_general_service_hours_pickup_same_settings_to_migrate', $settings['store_general_service_hours_pickup_same'] );
		}

		if ( ! empty( $settings['store_general_lead_time'] ) ) {
			update_option( '_orderable_main_location_store_general_lead_time_settings_to_migrate', $settings['store_general_lead_time'] );
		}

		if ( ! empty( $settings['store_general_preorder'] ) ) {
			update_option( '_orderable_main_location_store_general_preorder_settings_to_migrate', $settings['store_general_preorder'] );
		}

		if ( ! empty( $settings['store_general_calculation_method'] ) ) {
			update_option( '_orderable_main_location_store_general_calculation_method_settings_to_migrate', $settings['store_general_calculation_method'] );
		}

		$main_location_id = Orderable_Location::get_main_location_id();

		/**
		 * Filter whether we should skip the migration of the main location
		 * data from `orderable_settings` to the orderable custom table.
		 *
		 * By default, we don't migrate if the custom table already
		 * has the main location data.
		 *
		 * @since 1.8.0
		 * @hook orderable_skip_migrate_main_location_data_to_custom_table
		 * @param  bool $skip_migration If we should migrate.
		 * @return bool New value
		 */
		$skip_migration = apply_filters( 'orderable_skip_migrate_main_location_data_to_custom_table', (bool) $main_location_id );

		if ( $skip_migration ) {
			return;
		}

		if ( ! empty( $main_location_id ) ) {
			$wpdb->delete(
				$wpdb->prefix . self::get_table_name(),
				array(
					'location_id' => $main_location_id,
				),
				array(
					'location_id' => '%d',
				)
			);
		}

		$data = array(
			'open_hours'                       => empty( $settings['store_general_open_hours'] ) ? '' : maybe_serialize( $settings['store_general_open_hours'] ),
			'delivery'                         => $delivery,
			'pickup'                           => $pickup,
			'pickup_hours_same_as_delivery'    => empty( $settings['store_general_service_hours_pickup_same'] ) ? '' : (int) $settings['store_general_service_hours_pickup_same'],
			'asap_date'                        => $asap_date,
			'asap_time'                        => $asap_time,
			'lead_time'                        => empty( $settings['store_general_lead_time'] ) ? '' : $settings['store_general_lead_time'],
			'preorder'                         => empty( $settings['store_general_preorder'] ) ? '' : $settings['store_general_preorder'],
			'delivery_days_calculation_method' => empty( $settings['store_general_calculation_method'] ) ? '' : $settings['store_general_calculation_method'],
		);

		$data = wp_parse_args( $data, self::get_default_main_location_data() );

		$wpdb->insert( $wpdb->prefix . self::get_table_name(), $data );
	}

	/**
	 * Get default main location data.
	 *
	 * @return array
	 */
	public static function get_default_main_location_data() {
		$data = array(
			'title'                            => __( 'Main Location', 'orderable' ),
			'address_line_1'                   => get_option( 'woocommerce_store_address', '' ),
			'address_line_2'                   => get_option( 'woocommerce_store_address_2', '' ),
			'city'                             => get_option( 'woocommerce_store_city', '' ),
			'country_state'                    => get_option( 'woocommerce_default_country', '' ),
			'postcode_zip'                     => get_option( 'woocommerce_store_postcode', '' ),
			'override_default_open_hours'      => (int) true,
			'enable_default_holidays'          => (int) true,
			'open_hours'                       => '',
			'delivery'                         => (int) false,
			'pickup'                           => (int) false,
			'pickup_hours_same_as_delivery'    => '',
			'asap_date'                        => (int) false,
			'asap_time'                        => (int) false,
			'lead_time'                        => '',
			'lead_time_period'                 => 'days',
			'preorder'                         => '',
			'delivery_days_calculation_method' => '',
			'is_main_location'                 => 1,
			'image_id'                         => null,
			'menu_order'                       => null,
		);

		return $data;
	}
}
