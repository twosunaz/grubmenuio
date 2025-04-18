<?php
/**
 * Module: Custom Order Status.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Custom Order Status module class.
 */
class Orderable_Custom_Order_Status {
	/**
	 * Init.
	 */
	public static function run() {
		add_filter( 'orderable_valid_admin_pages', array( __CLASS__, 'add_valid_admin_pages' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
	}

	/**
	 * Add valid admin page.
	 */
	public static function add_valid_admin_pages( $pages = array() ) {
		$pages[] = 'orderable-custom-order-status';

		return $pages;
	}

	/**
	 * Add settings page.
	 */
	public static function add_settings_page() {
		add_submenu_page( 'orderable', __( 'Order Statuses', 'orderable' ), sprintf( '%s <span class="update-plugins" style="background-color: #ffffff1c"><span class="plugin-count">%s</span></span>', __( 'Order Statuses', 'orderable' ), __( 'Pro', 'orderable' ) ), 'manage_options', 'orderable-custom-order-status', array( __CLASS__, 'custom_order_status_page' ), 10 );
	}

	/**
	 * Custom Order Status page.
	 */
	public static function custom_order_status_page() {
		Orderable_Settings::upgrade_page_content( __( 'Order Statuses', 'orderable' ) );
	}
}
