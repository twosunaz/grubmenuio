<?php
/**
 * Integration with WooCommerce Points and Rewards plugin.
 *
 * @see https://woocommerce.com/products/woocommerce-points-and-rewards/
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable_Integration_WooCommerce_Points_And_Rewards integration class.
 */
class Orderable_Integration_WooCommerce_Points_And_Rewards {

	/**
	 * Init.
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! class_exists( 'WC_Points_Rewards' ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ) );
		add_action( 'orderable_side_menu_before_product_options_wrapper', array( __CLASS__, 'get_points_earned_when_purchasing_message' ) );
		add_action( 'woocommerce_widget_shopping_cart_total', array( __CLASS__, 'output_total_points_to_be_earned' ), 5 );
		add_action( 'orderable_before_product_actions', array( __CLASS__, 'output_product_points' ) );

		add_filter( 'woocommerce_available_variation', array( __CLASS__, 'add_points_earned_message_to_variation_data' ), 10, 3 );

	}

	/**
	 * Add the frontend assets
	 *
	 * @return void
	 */
	public static function frontend_assets() {
		if ( is_admin() ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'orderable-integrations-woocommerce-points-and-rewards', ORDERABLE_URL . 'inc/integrations/woocommerce-points-and-rewards/assets/frontend/css/integration-woocommerce-points-and-rewards' . $suffix . '.css', array(), ORDERABLE_VERSION );

		wp_enqueue_script( 'orderable-integrations-woocommerce-points-and-rewards', ORDERABLE_URL . 'inc/integrations/woocommerce-points-and-rewards/assets/frontend/js/main' . $suffix . '.js', array( 'jquery' ), ORDERABLE_VERSION, true );
	}

	/**
	 * Get product points.
	 *
	 * @param WC_Product $product The product.
	 * @return int
	 */
	public static function get_product_points( $product ) {
		if (
			! class_exists( 'WC_Points_Rewards' ) ||
			! class_exists( 'WC_Points_Rewards_Product' ) ||
			! class_exists( 'WC_Points_Rewards_Manager' )
		) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks
			return apply_filters( 'orderable_loyalty_rewards_product_points', 0, $product );
		}

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks
			return apply_filters( 'orderable_loyalty_rewards_product_points', 0, $product );
		}

		if ( $product->is_type( 'variable' ) ) {
			$wc_points_rewards_product = WC_Points_Rewards::instance()->get( 'product' );

			if ( empty( $wc_points_rewards_product ) ) {
				// phpcs:ignore WooCommerce.Commenting.CommentHooks
				return apply_filters( 'orderable_loyalty_rewards_product_points', 0, $product );
			}

			$points = $wc_points_rewards_product->get_highest_points_variation(
				$product->get_available_variations(),
				$product->get_id()
			);
		} else {
			$points = WC_Points_Rewards_Product::get_points_earned_for_product_purchase( $product );
			$points = WC_Points_Rewards_Manager::round_the_points( $points );
		}

		/**
		 * Filter the product points that can be earned when
		 * purchasing the product.
		 *
		 * @since 1.11.0
		 * @hook orderable_loyalty_rewards_product_points
		 * @param  int              $points  The product points.
		 * @param  WC_Product|mixed $product The product.
		 * @return int New value
		 */
		$points = apply_filters( 'orderable_loyalty_rewards_product_points', $points, $product );

		return absint( $points );
	}

	/**
	 * Replace the points in the message.
	 *
	 * @param int        $points  The product points.
	 * @param WC_Product $product The product.
	 * @return string
	 */
	protected static function replace_points_in_message( $points, $product ) {
		$default = '';

		if (
			! class_exists( 'WC_Points_Rewards' ) ||
			empty( $product ) ||
			! is_a( $product, 'WC_Product' )
		) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks
			return apply_filters( 'orderable_loyalty_rewards_product_message', $default, $points, $product );
		}

		$message = $product->is_type( 'variable' ) ? get_option( 'wc_points_rewards_variable_product_message' ) : get_option( 'wc_points_rewards_single_product_message' );

		if ( empty( $message ) || ! is_string( $message ) ) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks
			return apply_filters( 'orderable_loyalty_rewards_product_message', $default, $points, $product );
		}

		$message = str_replace( '{points}', number_format_i18n( $points ), $message );
		$message = str_replace( '{points_label}', WC_Points_Rewards::instance()->get_points_label( $points ), $message );

		/**
		 * Filter description.
		 *
		 * @since 1.11.0
		 * @hook orderable_loyalty_rewards_product_message
		 * @param  string           $message The product message.
		 * @param  int              $points  The product points.
		 * @param  WC_Product|mixed $product The product.
		 * @return string New value
		 */
		$message = apply_filters( 'orderable_loyalty_rewards_product_message', $message, $points, $product );

		return $message;
	}

	/**
	 * Get the points earned message when purchasing a product.
	 *
	 * @param WC_Product $product The product object.
	 * @return void
	 */
	public static function get_points_earned_when_purchasing_message( $product ) {
		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$points = self::get_product_points( $product );

		if ( empty( $points ) ) {
			return;
		}

		$message = self::replace_points_in_message( $points, $product );

		if ( empty( $message ) ) {
			return;
		}

		?>
		<div class="orderable-points-to-be-earned">
		<?php
			echo wp_kses_post( $message );
		?>
		</div>
		<?php
	}

	/**
	 * Output the total points to be earned based on
	 * the products added to the cart.
	 *
	 * @return void
	 */
	public static function output_total_points_to_be_earned() {
		$wc_points_rewards_cart_checkout = new WC_Points_Rewards_Cart_Checkout();

		$message = $wc_points_rewards_cart_checkout->generate_earn_points_message();

		if ( empty( $message ) ) {
			return;
		}

		?>
		<span class="orderable-total-points-to-be-earned">
			<?php echo wp_kses_post( $message ); ?>
		</span>
		<?php
	}

	/**
	 * Add points earned when purchasing message to the variation data.
	 *
	 * @param array                $data      The variation data.
	 * @param WC_Product_Variable  $product   The variable (parent) product.
	 * @param WC_Product_Variation $variation The variation product.
	 * @return array
	 */
	public static function add_points_earned_message_to_variation_data( $data, $product, $variation ) {
		$allowed_ajax_actions = array(
			'orderable_get_product_options',
			'orderable_get_cart_item_options',
		);

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_POST['action'] ) || ! in_array( $_POST['action'], $allowed_ajax_actions, true ) ) {
			return $data;
		}

		if ( ! class_exists( 'WC_Points_Rewards' ) ) {
			return $data;
		}

		$points = self::get_product_points( $variation );

		if ( empty( $points ) ) {
			return $data;
		}

		$message = get_option( 'wc_points_rewards_single_product_message' );
		$message = str_replace( '{points_label}', WC_Points_Rewards::instance()->get_points_label( $points ), $message );

		if ( empty( $message ) ) {
			return $data;
		}

		$data['points_earned']                         = $points;
		$data['points_earned_when_purchasing_message'] = $message;

		return $data;
	}

	/**
	 * Output the product points.
	 *
	 * @param WC_Product $product The product.
	 * @return void
	 */
	public static function output_product_points( $product ) {
		$ajax_actions_to_skip = array(
			'orderable_get_product_options',
			'orderable_get_cart_item_options',
		);

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_POST['action'] ) && in_array( $_POST['action'], $ajax_actions_to_skip, true ) ) {
			return;
		}

		if ( ! class_exists( 'WC_Points_Rewards' ) ) {
			return;
		}

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$points = self::get_product_points( $product );

		if ( empty( $points ) ) {
			return;
		}

		if ( $product->is_type( 'variable' ) ) {
			// translators: %1$d - max number of points earned when purchasing the product.
			$product_points_message = __( 'Points: up to %1$d', 'orderable' );
		} else {
			// translators: %1$d - number of points earned when purchasing the product.
			$product_points_message = __( 'Points: %1$d', 'orderable' );
		}

		/**
		 * Filter the product points message.
		 *
		 * @since 1.11.0
		 * @hook orderable_product_points_message
		 * @param  string     $product_points_message The product points message.
		 * @param  int        $points                 The product points.
		 * @param  WC_Product $product                The product.
		 * @return string New value
		 */
		$product_points_message = apply_filters( 'orderable_product_points_message', $product_points_message, $points, $product );

		?>
		<div class="orderable-product__points-earned">
			<?php echo esc_html( sprintf( $product_points_message, $points ) ); ?>
		</div>
		<?php
	}
}
