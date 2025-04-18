<?php
/**
 * Module: Addons.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Addons module class.
 */
class Orderable_Addons {
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
		$pages[] = 'orderable-product-addons';

		return $pages;
	}

	/**
	 * Add settings page.
	 */
	public static function add_settings_page() {
		add_submenu_page( 'orderable', __( 'Product Addons', 'orderable' ), sprintf( '%s <span class="update-plugins" style="background-color: #ffffff1c"><span class="plugin-count">%s</span></span>', __( 'Addons', 'orderable' ), __( 'Pro', 'orderable' ) ), 'manage_options', 'orderable-product-addons', array( __CLASS__, 'product_addons_page' ), 10 );
	}

	/**
	 * Product addons page.
	 */
	public static function product_addons_page() {
		Orderable_Settings::upgrade_page_content( __( 'Product Addons', 'orderable' ) );
	}
}
