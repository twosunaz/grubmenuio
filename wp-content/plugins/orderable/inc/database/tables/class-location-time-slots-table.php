<?php
/**
 * Location Time Slots table.
 *
 * @package Orderable/Database
 */

defined( 'ABSPATH' ) || exit;

/**
 * Time Slots table class.
 */
class Orderable_Location_Time_Slots_Table {
	/**
	 * Run table operations.
	 *
	 * @return void
	 */
	public static function run() {
		add_action( 'orderable_upgrade_database_routine', array( __CLASS__, 'upgrades' ) );
	}

	/**
	 * Get the table name without the prefix.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		return 'orderable_location_time_slots';
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
  time_slot_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id BIGINT UNSIGNED NOT NULL,
  service_type varchar(60) NULL,
  days tinytext NULL,
  period tinytext NULL,
  time_from tinytext NULL,
  time_to tinytext NULL,
  frequency tinytext NULL,
  cutoff tinytext NULL,
  max_orders tinytext NULL,
  has_zones tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (time_slot_id),
  KEY location_id (location_id)
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
			self::migrate_time_slots_to_custom_table();
		}
	}

	/**
	 * Migrate time slots data to the Orderable custom table.
	 *
	 * @return void
	 */
	protected static function migrate_time_slots_to_custom_table() {
		global $wpdb;

		$settings = get_option( 'orderable_settings' );

		if ( empty( $settings['store_general_service_hours_delivery'] ) && empty( $settings['store_general_service_hours_pickup'] ) ) {
			return;
		}

		$service_hours_delivery = $settings['store_general_service_hours_delivery'];
		$service_hours_pickup   = $settings['store_general_service_hours_pickup'];

		update_option( '_orderable_main_location_store_general_service_hours_delivery_settings_to_migrate', $service_hours_delivery );
		update_option( '_orderable_main_location_store_general_service_hours_pickup_settings_to_migrate', $service_hours_pickup );

		$main_location_id = Orderable_Location::get_main_location_id();

		$has_time_slots_data_in_custom_table = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					time_slot_id
				FROM
					{$wpdb->prefix}orderable_location_time_slots
				WHERE
					location_id = %d
				LIMIT 1
				",
				$main_location_id
			),
			ARRAY_A
		);

		/**
		 * Filter whether we should skip the migration of the main location time slots
		 * data from `orderable_settings` to the orderable custom table.
		 *
		 * By default, we don't migrate if the custom table already
		 * has the main location time slots data.
		 *
		 * @since 1.8.0
		 * @hook orderable_skip_migrate_main_location_time_slots_data_to_custom_table
		 * @param  bool $skip_migration If we should migrate.
		 * @return bool New value
		 */
		$skip_migration = apply_filters( 'orderable_skip_migrate_main_location_time_slots_data_to_custom_table', ! empty( $has_time_slots_data_in_custom_table ) );

		if ( $skip_migration ) {
			return;
		}

		if ( empty( $main_location_id ) ) {
			return;
		}

		$wpdb->delete(
			$wpdb->prefix . self::get_table_name(),
			array(
				'location_id' => $main_location_id,
			),
			array(
				'location_id' => '%d',
			)
		);

		$default_service_hour = array(
			'days'       => '',
			'period'     => '',
			'from'       => '',
			'to'         => '',
			'frequency'  => '',
			'cutoff'     => '',
			'max_orders' => '',
		);

		if ( is_array( $service_hours_delivery ) ) {
			foreach ( $service_hours_delivery as $service_hour ) {
				$service_hour = wp_parse_args(
					$service_hour,
					$default_service_hour
				);

				$wpdb->insert(
					$wpdb->prefix . self::get_table_name(),
					array(
						'location_id'  => $main_location_id,
						'service_type' => 'delivery',
						'days'         => maybe_serialize( $service_hour['days'] ),
						'period'       => $service_hour['period'],
						'time_from'    => maybe_serialize( $service_hour['from'] ),
						'time_to'      => maybe_serialize( $service_hour['to'] ),
						'frequency'    => $service_hour['frequency'],
						'cutoff'       => $service_hour['cutoff'],
						'max_orders'   => $service_hour['max_orders'],
					)
				);
			}
		}

		if ( is_array( $service_hours_pickup ) ) {
			foreach ( $service_hours_pickup as $service_hour ) {
				$service_hour = wp_parse_args(
					$service_hour,
					$default_service_hour
				);

				$wpdb->insert(
					$wpdb->prefix . self::get_table_name(),
					array(
						'location_id'  => $main_location_id,
						'service_type' => 'pickup',
						'days'         => maybe_serialize( $service_hour['days'] ),
						'period'       => $service_hour['period'],
						'time_from'    => maybe_serialize( $service_hour['from'] ),
						'time_to'      => maybe_serialize( $service_hour['to'] ),
						'frequency'    => $service_hour['frequency'],
						'cutoff'       => $service_hour['cutoff'],
						'max_orders'   => $service_hour['max_orders'],
					)
				);
			}
		}
	}
}
