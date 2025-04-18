<?php
/**
 * Module: Layouts Blocks.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Layouts blocks class.
 */
class Orderable_Layouts_Blocks {
	/**
	 * Init.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
	}

	/**
	 * Register blocks.
	 */
	public static function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'orderable-layout',
			ORDERABLE_URL . 'inc/modules/layouts/assets/admin/js/block-layout.js',
			array(
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-components',
				'wp-editor',
			),
			ORDERABLE_VERSION
		);

		wp_localize_script(
			'orderable-layout',
			'orderable_vars',
			array(
				'admin_url' => get_admin_url(),
			)
		);

		register_block_type(
			'orderable/layout',
			array(
				'editor_script'   => 'orderable-layout',
				'render_callback' => array( __CLASS__, 'layout_block_handler' ),
				'attributes'      => array(
					'id'        => array(
						'default' => '0',
						'type'    => 'string',
					),
					'layoutIds' => array(
						'default' => new stdClass(),
						'type'    => 'object',
					),
				),
			)
		);
	}

	/**
	 * Handle block: Layout.
	 */
	public static function layout_block_handler( $args = array() ) {
		$layout_settings = Orderable_Layouts::get_layout_settings( $args['id'] );

		return Orderable_Layouts::orderable_shortcode( $layout_settings );
	}
}
