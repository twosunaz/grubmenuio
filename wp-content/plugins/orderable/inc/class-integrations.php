<?php
/**
 * Integrations.
 *
 * Load integrations classes of Orderable plugin.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Modules class.
 */
class Orderable_Integrations {
	/**
	 * Init.
	 */
	public static function run() {
		self::load_integrations();
	}

	/**
	 * Load integrations.
	 */
	private static function load_integrations() {
		require_once ORDERABLE_INC_PATH . 'integrations/woocommerce-points-and-rewards/class-integration-woocommerce-points-and-rewards.php';

		Orderable_Integration_WooCommerce_Points_And_Rewards::init();
	}
}
