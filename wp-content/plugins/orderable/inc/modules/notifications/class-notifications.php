<?php
/**
 * Module: Notifications.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tip module class.
 */
class Orderable_Notifications {
	/**
	 * Init.
	 */
	public static function run() {
		add_filter( 'wpsf_register_settings_orderable', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Register settings.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function register_settings( $settings = array() ) {
		$settings['tabs'][] = array(
			'id'       => 'notifications',
			'title'    => __( 'Notifications', 'orderable-pro' ),
			'priority' => 50,
		);

		$settings['sections'][] = array(
			'tab_id'              => 'notifications',
			'section_id'          => 'notification',
			'section_title'       => __( 'Notification Settings', 'orderable' ),
			'section_description' => '',
			'section_order'       => 0,
			'fields'              => array(
				array(
					'id'       => 'pro',
					'title'    => __( 'Enable Notifications', 'orderable' ),
					'subtitle' => __( 'Enable SMS/WhatsApp notifications for order statuses.', 'orderable' ),
					'type'     => 'custom',
					'output'   => Orderable_Helpers::get_pro_button( 'notifications' ),
				),
			),
		);

		return $settings;
	}
}
