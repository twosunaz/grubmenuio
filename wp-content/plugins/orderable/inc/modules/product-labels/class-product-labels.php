<?php
/**
 * Module: Product Labels.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Addons module class.
 */
class Orderable_Product_Labels {
	/**
	 * Init.
	 */
	public static function run() {
		add_filter( 'orderable_valid_admin_pages', array( __CLASS__, 'add_valid_admin_pages' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
	}

	/**
	 * Add valid admin page.
	 *
	 * @param array $pages The admin pages slug.
	 */
	public static function add_valid_admin_pages( $pages = array() ) {
		$pages[] = 'orderable-product-labels';

		return $pages;
	}

	/**
	 * Add settings page.
	 */
	public static function add_settings_page() {
		add_submenu_page( 'orderable', __( 'Product Labels', 'orderable' ), sprintf( '%s <span class="update-plugins" style="background-color: #ffffff1c"><span class="plugin-count">%s</span></span>', __( 'Product Labels', 'orderable' ), __( 'Pro', 'orderable' ) ), 'manage_options', 'orderable-product-labels', array( __CLASS__, 'page_content' ), 10 );
	}

	/**
	 * Page content.
	 */
	public static function page_content() {
		Orderable_Settings::upgrade_page_content( __( 'Product Labels', 'orderable' ) );
	}
}
