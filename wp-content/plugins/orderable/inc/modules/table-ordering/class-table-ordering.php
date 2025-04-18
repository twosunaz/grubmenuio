<?php
/**
 * Module: Table Ordering.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Addons module class.
 */
class Orderable_Table_Ordering {
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
		$pages[] = 'orderable-table-ordering';

		return $pages;
	}

	/**
	 * Add settings page.
	 */
	public static function add_settings_page() {
		add_submenu_page( 'orderable', __( 'Table Ordering', 'orderable' ), sprintf( '%s <span class="update-plugins" style="background-color: #ffffff1c"><span class="plugin-count">%s</span></span>', __( 'Table Ordering', 'orderable' ), __( 'Pro', 'orderable' ) ), 'manage_options', 'orderable-table-ordering', array( __CLASS__, 'page_content' ), 10 );
	}

	/**
	 * Page content.
	 */
	public static function page_content() {
		Orderable_Settings::upgrade_page_content( __( 'Table Ordering', 'orderable' ) );
	}
}
