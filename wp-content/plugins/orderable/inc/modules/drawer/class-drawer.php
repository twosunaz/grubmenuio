<?php
/**
 * Module: Drawer.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Drawer module class.
 */
class Orderable_Drawer {
	/**
	 * Init.
	 */
	public static function run() {
		self::load_classes();

		add_action( 'wp_footer', array( __CLASS__, 'add' ) );
		add_filter( 'woocommerce_add_to_cart_fragments', array( __CLASS__, 'cart_count_fragments' ), 10, 1 );
		add_filter( 'woocommerce_add_to_cart_fragments', array( __CLASS__, 'cart_content_fragments' ), 10, 1 );
		add_filter( 'woocommerce_add_to_cart_fragments', array( __CLASS__, 'quantity_roller_fragments_on_updating_product' ), 10, 1 );
		add_filter( 'woocommerce_cart_item_permalink', '__return_false' );
		add_filter( 'wc_get_template', array( __CLASS__, 'mini_cart_template' ), 100, 5 );
	}

	/**
	 * Load classes for this module.
	 */
	public static function load_classes() {
		$classes = array(
			'drawer-settings' => 'Orderable_Drawer_Settings',
			'drawer-ajax'     => 'Orderable_Drawer_Ajax',
		);

		foreach ( $classes as $file_name => $class_name ) {
			require_once ORDERABLE_MODULES_PATH . 'drawer/class-' . $file_name . '.php';

			$class_name::run();
		}
	}

	/**
	 * Add drawer and overlay.
	 */
	public static function add() {
		if ( is_admin() || is_cart() || is_checkout() ) {
			return;
		}

		include Orderable_Helpers::get_template_path( 'overlay.php', 'drawer' );
		include Orderable_Helpers::get_template_path( 'drawer.php', 'drawer' );
		include Orderable_Helpers::get_template_path( 'floating-cart.php', 'drawer' );
	}

	/**
	 * Update cart count after adding to cart.
	 *
	 * @param array $fragments Array of HTML fragments.
	 *
	 * @return mixed
	 */
	public static function cart_count_fragments( $fragments ) {
		ob_start();

		include Orderable_Helpers::get_template_path( 'floating-cart.php', 'drawer' );

		$fragments['.orderable-floating-cart'] = ob_get_clean();

		return $fragments;
	}

	/**
	 * Update cart content after adding to cart.
	 *
	 * @param array $fragments Array of HTML fragments.
	 *
	 * @return mixed
	 */
	public static function cart_content_fragments( $fragments ) {
		ob_start();

		self::mini_cart();

		$fragments['.orderable-mini-cart-wrapper'] = ob_get_clean();

		if ( Orderable_Helpers::has_notices() ) {
			ob_start();
			?>
			<div class="orderable-mini-cart__notices orderable-mini-cart__notices--border-top">
				<?php wc_print_notices(); ?>
			</div>
			<?php

			$fragments['.orderable-mini-cart__notices'] = ob_get_clean();
		}

		return $fragments;
	}

	/**
	 * Replace mini cart template.
	 *
	 * @param string $template
	 * @param string $template_name
	 * @param array  $args
	 * @param string $template_path
	 * @param string $default_path
	 *
	 * @return string
	 */
	public static function mini_cart_template( $template, $template_name, $args, $template_path, $default_path ) {
		if ( 'cart/mini-cart.php' !== $template_name ) {
			return $template;
		}

		if ( empty( $args['orderable'] ) ) {
			return $template;
		}

		// if file exists in theme, use that.
		$theme_file = get_stylesheet_directory() . '/orderable/cart/mini-cart.php';
		if ( file_exists( $theme_file ) ) {
			return $theme_file;
		}

		return ORDERABLE_PATH . 'woocommerce/cart/mini-cart.php';
	}

	/**
	 * Output mini cart with Orderable param set.
	 */
	public static function mini_cart() {
		?>
		<div class="orderable-mini-cart-wrapper">
			<?php woocommerce_mini_cart( array( 'orderable' => true ) ); ?>
		</div>
		<?php
	}

	/**
	 * Update the quantity roller outside the side drawer.
	 *
	 * @param array $fragments The WooCommerce fragments.
	 * @return array
	 */
	public static function quantity_roller_fragments_on_updating_product( $fragments ) {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_POST['action'] ) || empty( $_POST['product_id'] ) ) {
			return $fragments;
		}

		$action     = sanitize_text_field( wp_unslash( $_POST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$product_id = absint( sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( 'orderable_update_cart_item_options' !== $action || empty( $product_id ) ) {
			return $fragments;
		}

		$cart_item = Orderable_Helpers::is_product_in_the_cart( $product_id );

		if ( empty( $cart_item ) ) {
			return $fragments;
		}

		ob_start();
		Orderable_Products::get_quantity_roller( $cart_item );
		$fragments[ ".orderable-product[data-orderable-product-id='{$product_id}'] .orderable-product__actions-button .orderable-quantity-roller" ] = ob_get_clean();

		return $fragments;
	}
}
