<?php
/**
 * Module: Tip Pro Checkout.
 *
 * @package Orderable/Classes
 */
defined( 'ABSPATH' ) || exit;

/**
 * Tip Pro Checkout class.
 */
class Orderable_Tip_Pro_Checkout {
	/**
	 * Init.
	 */
	public static function run() {
		add_action( 'woocommerce_review_order_after_cart_contents', array( __CLASS__, 'checkout_add_tip_options' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'checkout_apply_tip' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'handle_apply_tip_on_checkout_block' ) );
		add_action( 'woocommerce_cart_calculate_fees', [ __CLASS__, 'update_percentage_tip' ], 15 );
	}

	/**
	 * Add Tip Options in WooCommerce Checkout page.
	 *
	 * @return void
	 */
	public static function checkout_add_tip_options() {
		$enable_tip = Orderable_Settings::get_setting( 'tip_general_enable_tip' );

		if ( empty( $enable_tip ) ) {
			return;
		}

		$checkout_page_content = get_the_content( null, false, wc_get_page_id( 'checkout' ) );

		?>
		<tr class="orderable-tip-row">
			<td colspan="2">
				<?php
				/**
				 * If Orderable Tip was added using the shortcode [orderable_tip],
				 * we render only the fields that hold the tip data.
				 */
				if ( has_shortcode( $checkout_page_content, 'orderable_tip' ) ) {
					Orderable_Tip_Pro::tip_custom_form();
				} else {
					Orderable_Tip_Pro::add_tip_section();
				}
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Apply Tip amount with total.
	 *
	 * @return void
	 */
	public static function checkout_apply_tip() {
		if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
			return;
		}

		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );
		} else {
			$post_data = $_POST;
		}

		$tip_data = Orderable_Tip_Pro::get_session_tip_data();

		if ( isset( $post_data['tip_amount'] ) ) {
			$tip_data = array(
				'index'      => wc_clean( wp_unslash( $post_data['tip_index'] ) ),
				'amount'     => wc_clean( wp_unslash( $post_data['tip_amount'] ) ),
				'percentage' => wc_clean( wp_unslash( $post_data['tip_percentage'] ) ),
			);

			$tip_data['amount'] = $tip_data['amount'] < 0 ? 0 : $tip_data['amount'];

			Orderable_Tip_Pro::set_session_tip_data( $tip_data );
		}

		if ( isset( $tip_data['amount'] ) && $tip_data['amount'] > 0 ) {
			WC()->cart->add_fee( __( 'Tip', 'orderable-pro' ), $tip_data['amount'] );
		}
	}

	/**
	 * Handle apply tip on WooCommerce Checkout block.
	 *
	 * @return void
	 */
	public static function handle_apply_tip_on_checkout_block() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$tip_data = Orderable_Tip_Pro::get_session_tip_data();

		if ( empty( $tip_data ) ) {
			return;
		}

		WC()->cart->add_fee( __( 'Tip', 'orderable-pro' ), $tip_data['amount'] );
	}

	/**
	 * Re-calculate percentage tip.
	 *
	 * When the cart is updated, Orderable has to calculate
	 * the tip again if the tip is based on a percentage value.
	 *
	 * @param WC_Cart $cart The cart object.
	 * @return void
	 */
	public static function update_percentage_tip( WC_Cart $cart ) {
		if ( ! empty( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$tip_data = Orderable_Tip_Pro::get_session_tip_data();

		if ( empty( $tip_data['percentage'] ) || ! is_numeric( $tip_data['percentage'] ) ) {
			return;
		}

		$fees     = $cart->get_fees();
		$fee_name = __( 'Tip', 'orderable-pro' );

		foreach ( $fees as $key => $fee ) {
			if ( $fee_name !== $fee->name ) {
				continue;
			}

			unset( $fees[ $key ] );
		}

		WC()->cart->fees_api()->set_fees( $fees );

		$tip_data['amount'] = number_format( ( $cart->get_subtotal() * $tip_data['percentage'] ) / 100, 2, '.', '' );

		Orderable_Tip_Pro::set_session_tip_data( $tip_data );
		WC()->cart->add_fee( $fee_name, $tip_data['amount'] );
	}
}
