<?php
/**
 * Module methods.
 *
 * Load individual modules of Orderable plugin, if they exist.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Modules class.
 */
class Orderable_Modules {
	/**
	 * Init.
	 */
	public static function run() {
		self::load_modules();
	}

	/**
	 * Load free modules.
	 */
	private static function load_modules() {
		$modules = array(
			'layouts',
			'drawer',
			'services',
			'timings',
			'addons',
			'live-view',
			'location',
			'tip',
			'checkout',
			'timed-products',
			'custom-order-status',
			'notifications',
			'order-reminders',
			'table-ordering',
			'product-labels',
			'receipt-layouts',
		);

		foreach ( $modules as $module ) {
			$path = ORDERABLE_MODULES_PATH . $module . '/class-' . $module . '.php';

			if ( ! file_exists( $path ) ) {
				continue;
			}

			$class_name = self::get_module_class_name( $module );

			require_once $path;

			if ( ! class_exists( $class_name ) ) {
				continue;
			}

			$class_name::run();
		}
	}

	/**
	 * Get module class name.
	 *
	 * @param string $module Module name.
	 *
	 * @return string
	 */
	public static function get_module_class_name( $module ) {
		$module = ucwords( str_replace( '-', ' ', $module ) );
		$module = str_replace( ' ', '_', $module );

		return apply_filters( 'orderable_get_module_class_name', 'Orderable_' . $module, $module );
	}
}
