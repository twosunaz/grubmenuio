<?php
/**
 * Module: Tip.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tip module class.
 */
class Orderable_Tip {
	/**
	 * Init.
	 */
	public static function run() {
		add_filter( 'wpsf_register_settings_orderable', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Register settings.
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	public static function register_settings( $settings = array() ) {
		$settings['tabs'][] = array(
			'id'       => 'tip',
			'title'    => __( 'Tip Settings', 'orderable' ),
			'priority' => 20,
		);

		$settings['sections'][] = array(
			'tab_id'              => 'tip',
			'section_id'          => 'general',
			'section_title'       => __( 'Tip Settings', 'orderable' ),
			'section_description' => '',
			'section_order'       => 0,
			'fields'              => array(
				array(
					'id'       => 'pro',
					'title'    => __( 'Enable Tipping', 'orderable' ),
					'subtitle' => __( 'Show tipping options at checkout.', 'orderable' ),
					'type'     => 'custom',
					'output'   => Orderable_Helpers::get_pro_button( 'tip' ),
				),
			),
		);

		return $settings;
	}
}
