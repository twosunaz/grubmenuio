<?php
/**
 * Checkout Order Date Blocks Integration.
 *
 * @package orderable
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks
 */
class Checkout_Order_Date_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'orderable';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->register_checkout_block_frontend_scripts();
		$this->register_checkout_block_editor_scripts();
		$this->register_main_integration();
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'orderable-order-date-blocks-integration', 'orderable-order-date-block-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'orderable-order-date-blocks-integration', 'checkout-order-date-block-editor' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$data = array();

		return $data;
	}

	/**
	 * Registers the frontend block scripts.
	 */
	public function register_checkout_block_frontend_scripts() {
		$script_path       = '/build/checkout-order-date-block-frontend.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/checkout-order-date-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => ORDERABLE_VERSION,
			);

		wp_register_script(
			'orderable-order-date-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	/**
	 * Registers the editor block scripts.
	 */
	public function register_checkout_block_editor_scripts() {
		$script_path       = '/build/checkout-order-date-block.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/checkout-order-date-block.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => ORDERABLE_VERSION,
			);

		wp_register_script(
			'checkout-order-date-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	/**
	 * Registers the main integration JS files.
	 */
	private function register_main_integration() {
		$script_path = '/build/index.js';
		$script_url  = plugins_url( $script_path, __FILE__ );

		$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => ORDERABLE_VERSION,
			);

		wp_register_script(
			'orderable-order-date-blocks-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	/**
	 * Save the order service date fields.
	 *
	 * @param WC_Order        $order The order object.
	 * @param WP_REST_Request $request The request object.
	 */
	public static function save_order_service_date_fields( $order, $request ) {
		$orderable_request_data = $request['extensions']['orderable/order-service-date'] ?? false;

		if ( empty( $orderable_request_data['timestamp'] ) ) {
			return;
		}

		if ( 'asap' === $orderable_request_data['timestamp'] ) {
			$order->update_meta_data( 'orderable_order_date', __( 'As soon as possible', 'orderable' ) );

			$order->save();

			return;
		}

		$date_format          = get_option( 'date_format' );
		$timestamp_adjusted   = Orderable_Timings::get_timestamp_adjusted( $orderable_request_data['timestamp'] );
		$order_date_formatted = date_i18n( $date_format, $timestamp_adjusted );

		$order->update_meta_data( '_orderable_order_timestamp', $timestamp_adjusted );
		$order->update_meta_data( 'orderable_order_date', $order_date_formatted );

		$order->save();
	}
}
