
<?php
if (function_exists('opcache_reset')) { opcache_reset(); }
/**
 * Ajax methods.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ajax class.
 */
class Orderable_Ajax {
	/**
	 * Run.
	 */
	public static function run() {
		$methods = array(
			'get_product_options'    => true,
			'add_to_cart'            => true,
			'get_onboard_woo_fields' => false,
			'get_cart_item_options'  => true,
			'store_time_slot' => true,
		);

		self::add_ajax_methods( $methods, __CLASS__ );
	}

	/**
	 * Add ajax methods helper.
	 *
	 * @param array         $methods
	 * @param object|string $class
	 */

	public static function store_time_slot() {
	error_log('📩 Received AJAX request');
	error_log('🧪 Raw POST: ' . print_r($_POST, true));

	if (!isset($_POST['time']) || !isset($_POST['_ajax_nonce'])) {
		error_log('❌ Missing fields!');
		wp_send_json_error('Missing required fields');
	}

	if (!wp_verify_nonce($_POST['_ajax_nonce'], 'orderable_time_nonce')) {
		error_log('❌ Nonce verification failed: ' . $_POST['_ajax_nonce']);
		wp_send_json_error('Invalid nonce');
	}

	$time = sanitize_text_field($_POST['time']);
	WC()->session->set('orderable_table_number', $time);
	WC()->session->set( 'orderable_multi_location_postcode', $time );
	WC()->session->set( 'orderable_multi_location_id', '' );
	WC()->session->set( 'orderable_multi_location_delivery_type', '' );

	error_log('✅ Time saved to session: ' . $time);
	wp_send_json_success();
	}


	public static function add_ajax_methods( $methods, $class ) {
		if ( empty( $methods ) ) {
			return;
		}

		foreach ( $methods as $method => $nopriv ) {
			add_action( 'wp_ajax_orderable_' . $method, array( $class, $method ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_orderable_' . $method, array( $class, $method ) );
			}
		}
	}

	/**
	 * Get product options for a variable product.
	 */
	public static function get_product_options() {
		$product_id = absint( filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT ) );

		if ( empty( $product_id ) ) {
			wp_send_json_error();
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$focus   = empty( $_POST['focus'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['focus'] ) );
		$product = wc_get_product( $product_id );

		$response = array(
			'product_id' => $product_id,
			'product'    => $product,
		);

		if ( Orderable_Helpers::is_variable_product( $product->get_type() ) ) {
			$attributes           = Orderable_Products::get_available_attributes( $product );
			$available_variations = $product->get_available_variations();
			$variations_json      = wp_json_encode( $available_variations );
		}

		$args = array(
			'images' => true,
			'focus'  => $focus,
		);

		ob_start();

		include Orderable_Helpers::get_template_path( 'templates/product/options.php' );

		$response['html'] = ob_get_clean();

		wp_send_json_success( $response );
	}

	/**
	 * Get cart item options for a variable product.
	 */
	public static function get_cart_item_options() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['cart_item_key'] ) ) {
			wp_send_json_error();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) );

		if ( empty( $cart_item_key ) ) {
			wp_send_json_error();
		}

		$cart_item = WC()->cart->get_cart_item( $cart_item_key );

		if ( empty( $cart_item ) ) {
			wp_send_json_error();
		}

		$product_id = $cart_item['product_id'];
		$product    = wc_get_product( $product_id );

		$response = array(
			'product_id' => $product_id,
			'product'    => $product,
		);

		if ( Orderable_Helpers::is_variable_product( $product->get_type() ) ) {
			$selected             = $cart_item['variation'];
			$attributes           = Orderable_Products::get_available_attributes( $product );
			$available_variations = $product->get_available_variations();
			$variations_json      = html_entity_decode( wp_json_encode( $available_variations ) );
		}

		$args = array(
			'images' => true,
		);

		add_filter(
			'orderable_get_group_data',
			/**
			 * Fill the value of the fields.
			 *
			 * @param array $field_group The field group data.
			 *
			 * @return array
			 */
			function( $field_group ) use ( $cart_item ) {
				foreach ( $field_group as $key => $value ) {
					if ( empty( $cart_item['orderable_fields'][ $value['id'] ] ) ) {
						continue;
					}

					switch ( $value['type'] ) {
						case 'text':
							$field_group[ $key ]['default'] = $cart_item['orderable_fields'][ $value['id'] ]['value'];

							break;

						case 'select':
						case 'visual_radio':
							foreach ( $value['options'] as $key_option => $option ) {
								if ( $option['label'] === $cart_item['orderable_fields'][ $value['id'] ]['value'] ) {
									$field_group[ $key ]['options'][ $key_option ]['selected'] = '1';

									break;
								}
							}

							break;

						case 'visual_checkbox':
							foreach ( $value['options'] as $key_option => $option ) {
								if ( empty( $cart_item['orderable_fields'][ $value['id'] ]['value'] ) ) {
									continue;
								}

								$field_value = $cart_item['orderable_fields'][ $value['id'] ]['value'];

								if ( ! is_array( $field_value ) ) {
									continue;
								}

								if ( ! in_array( $option['label'], $field_value, true ) ) {
									$field_group[ $key ]['options'][ $key_option ]['selected'] = '0';
									continue;
								}

								$field_group[ $key ]['options'][ $key_option ]['selected'] = '1';
							}

							break;
					}
				}

				return $field_group;
			}
		);

		ob_start();

		include ORDERABLE_TEMPLATES_PATH . 'product/options.php';

		$response['html'] = ob_get_clean();

		wp_send_json_success( $response );
	}

	/**
	 * AJAX add to cart.
	 */
	public static function add_to_cart() {
		ob_start();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$product_id = absint( filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT ) );

		if ( empty( $product_id ) ) {
			return;
		}

		$variation_id      = absint( filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_NUMBER_INT ) );
		$quantity          = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_POST['quantity'] ) );
		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
		$product_status    = get_post_status( $product_id );
		$attributes        = empty( $_POST['attributes'] ) ? array() : (array) json_decode( sanitize_text_field( wp_unslash( $_POST['attributes'] ) ), true );
		$attributes        = array_map( 'wp_unslash', array_filter( $attributes ) );

		if ( $passed_validation && false !== WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes ) && 'publish' === $product_status ) {
			do_action( 'woocommerce_ajax_added_to_cart', $product_id );
		}

		WC_AJAX::get_refreshed_fragments();
		// phpcs:enable
	}

	/**
	 * Get countries states.
	 */
	public static function get_onboard_woo_fields() {
		$response = array(
			'default_country'    => self::get_default_country_options(),
			'business_address'   => WC()->countries->get_base_address(),
			'business_address_2' => WC()->countries->get_base_address_2(),
			'business_city'      => WC()->countries->get_base_city(),
			'business_postcode'  => WC()->countries->get_base_postcode(),
		);

		wp_send_json_success( $response );
	}

	/**
	 * Get country/state options.
	 *
	 * @return string
	 */
	public static function get_default_country_options() {
		$countries_states = Orderable_Settings::get_countries_states();

		if ( empty( $countries_states ) ) {
			return false;
		}

		ob_start();

		require ORDERABLE_INC_PATH . '/vendor/iconic-onboard/inc/class-settings.php';

		$base    = wc_get_base_location();
		$default = '';

		if ( isset( $base['country'] ) && isset( $countries_states[ 'country:' . $base['country'] ] ) ) {
			$default = 'country:' . $base['country'];
		}

		if ( isset( $base['country'] ) && isset( $base['state'] ) && isset( $countries_states[ $base['country'] ] ) ) {
			$state = 'state:' . $base['country'] . ':' . $base['state'];
			if ( isset( $countries_states[ $base['country'] ]['values'][ $state ] ) ) {
				$default = $state;
			}
		}

		Orderable_Onboard_Settings::generate_select_field(
			array(
				'id'      => 'default_country',
				'title'   => __( 'Country / State', 'orderable' ),
				'desc'    => '',
				'choices' => $countries_states,
				'value'   => $default,
				'name'    => '',
				'class'   => '',
			)
		);

		return strip_tags( ob_get_clean(), '<option><optgroup>' );
	}
}
