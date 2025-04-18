<?php
/**
 * Module: Timed Products.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Timed Products module class.
 */
class Orderable_Timed_Products {
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
		$pages[] = 'orderable-timed-products';

		return $pages;
	}

	/**
	 * Add settings page.
	 */
	public static function add_settings_page() {
		add_submenu_page( 'orderable', __( 'Timed Products', 'orderable' ), sprintf( '%s <span class="update-plugins" style="background-color: #ffffff1c"><span class="plugin-count">%s</span></span>', __( 'Timed Products', 'orderable' ), __( 'Pro', 'orderable' ) ), 'manage_options', 'orderable-timed-products', array( __CLASS__, 'timed_products_page' ), 10 );
	}

	/**
	 * Timed Products page.
	 */
	public static function timed_products_page() {
		Orderable_Settings::upgrade_page_content( __( 'Timed Products', 'orderable' ) );
	}
}
