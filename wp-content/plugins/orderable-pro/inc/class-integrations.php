<?php
/**
 * Integrations.
 *
 * Load integrations classes of Orderable Pro plugin.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Modules class.
 */
class Orderable_Pro_Integrations {
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
		require_once ORDERABLE_PRO_INC_PATH . 'integrations/woocommerce-points-and-rewards/class-integration-woocommerce-points-and-rewards.php';
		require_once ORDERABLE_PRO_INC_PATH . 'integrations/dokan/dokan.php';
		require_once ORDERABLE_PRO_INC_PATH . 'integrations/wcfm/wcfm.php';

		Orderable_Pro_Integration_WooCommerce_Points_And_Rewards::init();
		Orderable_Pro_Integration_Dokan::get_instance();
		Orderable_Pro_Integration_Wcfm::get_instance();
	}
}
