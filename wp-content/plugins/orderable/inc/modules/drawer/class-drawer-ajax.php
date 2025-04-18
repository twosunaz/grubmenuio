<?php
/**
 * Module: Drawer Ajax.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Drawer module class.
 */
class Orderable_Drawer_Ajax {
	/**
	 * Init.
	 */
	public static function run() {
		Orderable_Ajax::add_ajax_methods(
			array(
				'cart_quantity'            => true,
				'update_cart_item_options' => true,
			),
			__CLASS__
		);
	}

	/**
	 * Modify cart item qty.
	 */
	public static function cart_quantity() {
		$product_id    = empty( $_POST['product_id'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['product_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$cart_item_key = empty( $_POST['cart_item_key'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$quantity      = filter_input( INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT );

		if ( empty( $product_id ) || empty( $cart_item_key ) || ! is_numeric( $quantity ) ) {
			wp_send_json_error();
		}

		$_product = wc_get_product( $product_id );

		if ( empty( $_product ) ) {
			wp_send_json_error();
		}

		$passed_validation        = true;
		$cart_updated             = false;
		$current_session_order_id = isset( WC()->session->order_awaiting_payment ) ? absint( WC()->session->order_awaiting_payment ) : 0;
		$product_qty_in_cart      = WC()->cart->get_cart_item_quantities();

		// is_sold_individually.
		if ( $_product->is_sold_individually() && $quantity > 1 ) {
			/* Translators: %s Product title. */
			wc_add_notice( sprintf( __( 'You can only have 1 %s in your cart.', 'orderable' ), $_product->get_name() ), 'error' );
			$passed_validation = false;
		}

		// We only need to check products managing stock, with a limited stock qty.
		if ( $_product->managing_stock() || $_product->backorders_allowed() ) {
			// Check stock based on all items in the cart and consider any held stock within pending orders.
			$held_stock = wc_get_held_stock_quantity( $_product, $current_session_order_id );

			if ( $_product->get_stock_quantity() < ( $held_stock + $quantity ) ) {
				/* translators: 1: product name 2: quantity in stock */
				wc_add_notice( sprintf( __( 'Sorry, we do not have enough "%1$s" in stock to fulfill your order (%2$s available). We apologize for any inconvenience caused.', 'woocommerce' ), $_product->get_name(), wc_format_stock_quantity_for_display( $_product->get_stock_quantity() - $held_stock, $_product ) ), 'error' );

				$passed_validation = false;
			}
		}

		if ( $passed_validation ) {
			WC()->cart->set_quantity( $cart_item_key, intval( $quantity ), false );
			$cart_updated = true;
		}

		if ( $cart_updated ) {
			WC()->cart->calculate_totals();
		}

		WC_AJAX::get_refreshed_fragments();
	}

	/**
	 * Update cart item attributes and addons.
	 */
	public static function update_cart_item_options() {
		if ( empty( $_POST['cart_item_key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error( null, 400 );
		}

		$cart_item_key    = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product_id       = absint( filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT ) );
		$attributes       = self::get_attributes();
		$variation_id     = empty( $attributes ) ? 0 : absint( filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_NUMBER_INT ) );
		$orderable_fields = self::get_orderable_fields();

		if (
			empty( $cart_item_key ) ||
			( empty( $attributes ) && empty( $orderable_fields ) )
		) {
			wp_send_json_error( null, 400 );
		}

		$cart_item = WC()->cart->get_cart_item( $cart_item_key );

		if ( empty( $cart_item ) ) {
			wp_send_json_error();
		}

		$product_data = empty( $variation_id ) ? wc_get_product( $cart_item['product_id'] ) : wc_get_product( $variation_id );

		if ( ! $product_data ) {
			wp_send_json_error();
		}

		// If the same data is sent (i.e. there is nothing to udpate), return early.
		if (
			WC()->cart->generate_cart_id(
				$product_id,
				$variation_id,
				empty( $attributes ) ? array( false ) : $attributes,
				empty( $orderable_fields ) ? array() : array( 'orderable_fields' => $orderable_fields )
			)
			===
			$cart_item_key
		) {
			wp_send_json_success();
		}

		self::update_cart_item_attributes( $cart_item, $variation_id, $attributes, $orderable_fields );

		WC()->cart->calculate_totals();

		WC_AJAX::get_refreshed_fragments();
	}

	/**
	 * Get attributes values to update.
	 *
	 * @return array.
	 */
	protected static function get_attributes() {
		if ( empty( $_POST['attributes'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return array();
		}

		$attributes = array_filter(
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			(array) json_decode( sanitize_text_field( wp_unslash( $_POST['attributes'] ) ) ),
			function( $attribute_value, $attribute_name ) {

				return ! empty( $attribute_value ) && false !== strpos( $attribute_name, 'attribute_' );
			},
			ARRAY_FILTER_USE_BOTH
		);

		return $attributes;
	}

	/**
	 * Get Orderable Product Addons fields.
	 *
	 * @return array
	 */
	protected static function get_orderable_fields() {

		if (
			! class_exists( 'Orderable_Addons_Pro_Field_Groups' ) ||
			! class_exists( 'Orderable_Addons_Pro_Fees' )
		) {
			return array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$orderable_fields = empty( $_POST['orderable_fields'] ) ? array() : map_deep( wp_unslash( $_POST['orderable_fields'] ), 'sanitize_text_field' );

		if ( empty( $orderable_fields ) || ! is_array( $orderable_fields ) ) {
			return array();
		}

		$orderable_item_data = array();

		foreach ( $orderable_fields as $group_id => $fields ) {
			foreach ( $fields as $field_id => $field ) {
				$field_setting = Orderable_Addons_Pro_Field_Groups::get_field_data( $field_id, $group_id );
				$fees          = Orderable_Addons_Pro_Fees::calculate_fees( $field, $field_id, $group_id );

				$orderable_item_data[ $field_id ] = array(
					'id'       => $field_id,
					'value'    => $field,
					'label'    => $field_setting['title'],
					'group_id' => $group_id,
					'fees'     => $fees,
				);
			}
		}

		return $orderable_item_data;
	}

	/**
	 * Update cart item attributes.
	 *
	 * @param array $original_cart_item The cart item to be updated.
	 * @param int   $variation_id       The new variation ID.
	 * @param array $attributes         The new attributes.
	 * @return void
	 */
	protected static function update_cart_item_attributes( $original_cart_item, $variation_id, $attributes, $cart_item_data ) {
		if ( empty( $cart_item_data ) && ! empty( $original_cart_item['orderable_fields'] ) ) {
			$cart_item_data = $original_cart_item['orderable_fields'];
		}

		if ( ! empty( $cart_item_data ) ) {
			$cart_item_data = array( 'orderable_fields' => $cart_item_data );
		}

		/**
		 * WooCommerce stores empty attributes ('variation' on the cart item)
		 * as `array( false )`.
		 */
		if ( empty( $attributes ) ) {
			$attributes = array( false );
		}

		/**
		 * We update the cart item by adding the updated item to the cart.
		 * This allows 3rd plugins/themes to integrate with the default
		 * WooCommerce hooks.
		 */
		$updated_cart_item_key = WC()->cart->add_to_cart( $original_cart_item['product_id'], $original_cart_item['quantity'], $variation_id, $attributes, $cart_item_data );

		$cart_content = WC()->cart->get_cart();

		/**
		 * We remove the updated item from the cart to re-insert
		 * at the same position of the original item.
		 */
		unset( $cart_content[ $updated_cart_item_key ] );

		$cart_content_updated = array();

		foreach ( $cart_content as $key => $item ) {
			// We re-insert the updated item at the same position of the original item.
			if ( $key === $original_cart_item['key'] ) {
				$updated_cart_item = WC()->cart->get_cart_item( $updated_cart_item_key );

				$cart_content_updated[ $updated_cart_item['key'] ] = $updated_cart_item;
			} else {
				$cart_content_updated[ $key ] = $item;
			}
		}

		WC()->cart->set_cart_contents( $cart_content_updated );
	}
}
