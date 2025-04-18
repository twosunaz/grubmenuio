<?php
/**
 * Location Holidays table.
 *
 * @package Orderable/Database
 */

defined( 'ABSPATH' ) || exit;

/**
 * Location Holidays table class.
 */
class Orderable_Location_Holidays_Table {
	/**
	 * Run table operations.
	 *
	 * @return void
	 */
	public static function run() {
	}

	/**
	 * Get the table name without the prefix.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		return 'orderable_location_holidays';
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
  holiday_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id BIGINT UNSIGNED NOT NULL,
  date_from date DEFAULT NULL,
  date_to date DEFAULT NULL,
  services longtext NULL,
  repeat_yearly boolean DEFAULT NULL,
  PRIMARY KEY  (holiday_id),
  KEY location_id (location_id)
)';

		return $schema;
	}
}
