<?php
/**
 * Delivery Zones Lookup table.
 *
 * @package Orderable/Database
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable_Location_Delivery_Zones_Lookup_Table class.
 */
class Orderable_Location_Delivery_Zones_Lookup_Table {
	/**
	 * Run table operations.
	 *
	 * @return void
	 */
	public static function run() {}

	/**
	 * Get the table name without the prefix.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		return 'orderable_location_delivery_zones_lookup';
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
  location_id bigint(20) NOT NULL,
  time_slot_id bigint(20) NOT NULL,
  zone_id bigint(20) NOT NULL,
  PRIMARY KEY  ( `location_id`, `time_slot_id`, `zone_id` )
)';

		return $schema;
	}
}
