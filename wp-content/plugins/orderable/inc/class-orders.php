<?php
/**
 * Order methods.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Orders class.
 */
class Orderable_Orders {
	/**
	 * Is orders page.
	 *
	 * @return bool
	 */
	public static function is_orders_page() {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';

		$current_screen            = get_current_screen();
		$shop_order_page_screen_id = OrderUtil::custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'edit-shop_order';

		if ( ! $current_screen || $shop_order_page_screen_id !== $current_screen->id ) {
			return false;
		}

		return true;
	}
}
