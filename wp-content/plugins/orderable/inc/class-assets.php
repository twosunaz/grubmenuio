<?php
/**
 * Script and style assets.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Assets class.
 */
class Orderable_Assets {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * Frontend assets.
	 */
	public static function frontend_assets() {
		if ( is_admin() ) {
			return;
		}

		$suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$suffix_css = ( is_rtl() ? '-rtl' : '' ) . $suffix;

		wp_enqueue_style( 'orderable', ORDERABLE_ASSETS_URL . 'frontend/css/main' . $suffix_css . '.css', array(), ORDERABLE_VERSION );
		wp_enqueue_script( 'orderable', ORDERABLE_ASSETS_URL . 'frontend/js/main' . $suffix . '.js', array( 'jquery', 'wc-add-to-cart' ), ORDERABLE_VERSION, true );

		wp_add_inline_style( 'orderable', self::get_styles() );

		wp_localize_script(
			'orderable',
			'orderable_vars',
			array(
				'i18n'                                => array(
					'out_of_stock' => __( 'Sorry, that product is out of stock.', 'orderable' ),
					'unavailable'  => __( 'Sorry, that product is unavailable.', 'orderable' ),
					'no_exist'     => __( 'Sorry, that combination does not exist.', 'orderable' ),
				),
				'ajax_url'                            => WC()->ajax_url(),
				/**
				 * If the option "Enable AJAX add to cart buttons on archives" is not enabled,
				 * we need to turn off the click event for .add_to_cart_button elements on drawer.js
				 * to keep the AJAX behaviour only on Mini cart.
				 */
				'woocommerce_enable_ajax_add_to_cart' => 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' ),
			)
		);

		do_action( 'orderable_after_frontend_assets' );
	}

	/**
	 * Get styles from settings.
	 *
	 * @return string
	 */
	public static function get_styles() {
		$brand_color               = Orderable_Settings::get_setting( 'style_style_color' );
		$product_title_font_size   = Orderable_Settings::get_setting( 'style_products_title_size' );
		$product_title_line_height = $product_title_font_size * 1.2;
		$product_price_font_size   = Orderable_Settings::get_setting( 'style_products_price_size' );
		$product_price_line_height = $product_price_font_size * 1.2;

		$styles = array(
			'.orderable-button { color: %1$s; border-color: %1$s; }',
			'.orderable-button--hover, .orderable-button:hover, .orderable-button:active, .orderable-button--active, .orderable-button:focus { border-color: %1$s; background: %1$s !important; color: #fff; }',
			'.orderable-tabs__item--active a.orderable-tabs__link { background: %1$s !important; }',
			'.orderable-button--filled, .orderable-drawer__cart .orderable-mini-cart__buttons .button.checkout { border-color: %1$s; background: %1$s !important; color: #fff; }',
			'.orderable-button--filled.orderable-button--hover, .orderable-button--filled:hover, .orderable-button--filled:active, .orderable-button--filled:focus, .orderable-drawer__cart .orderable-mini-cart__buttons .button.checkout:hover, .orderable-drawer__cart .orderable-mini-cart__buttons .button.checkout:active, .orderable-drawer__cart .orderable-mini-cart__buttons .button.checkout:focus { border-color: %1$s; background: %1$s !important; }',
			'.orderable-button--loading:after { border-top-color: %1$s; border-left-color: %1$s; }',
			'.orderable-product-option--checked .orderable-product-option__label-state { border-color: %1$s !important; }',
			".orderable-products-list .orderable-product__title { font-size: {$product_title_font_size}px; line-height: {$product_title_line_height}px; }",
			".orderable-product__actions-price .amount { font-size: {$product_price_font_size}px; line-height: {$product_price_line_height}px; }",
		);

		return apply_filters( 'orderable_styles', sprintf( implode( '', $styles ), $brand_color ) );
	}

	/**
	 * Admin assets.
	 */
	public static function admin_assets() {
		if ( ! is_admin() || ! Orderable_Settings::is_settings_page() ) {
			return;
		}

		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$style_deps  = apply_filters( 'orderable_admin_style_deps', array( 'wc-admin-layout' ) );
		$script_deps = apply_filters( 'orderable_admin_script_deps', array( 'jquery' ) );

		wp_enqueue_style( 'orderable', ORDERABLE_ASSETS_URL . 'admin/css/main' . $suffix . '.css', $style_deps, ORDERABLE_VERSION );
		wp_enqueue_style( 'select2', ORDERABLE_ASSETS_URL . 'vendor/select2/select2' . $suffix . '.css', array(), ORDERABLE_VERSION );

		wp_enqueue_media();
		wp_enqueue_script( 'orderable-select2', ORDERABLE_ASSETS_URL . 'vendor/select2/select2' . $suffix . '.js', $script_deps, ORDERABLE_VERSION );
		wp_enqueue_script( 'orderable-jquery-multi-select', ORDERABLE_ASSETS_URL . 'vendor/jquery-multi-select/jquery.multi-select' . $suffix . '.js', array( 'jquery' ), ORDERABLE_VERSION );
		wp_enqueue_script( 'orderable', ORDERABLE_ASSETS_URL . 'admin/js/main' . $suffix . '.js', array( 'jquery' ), ORDERABLE_VERSION );

		wp_localize_script(
			'orderable',
			'orderable_vars',
			array(
				'i18n' => array(
					'confirm_remove_service_hours' => __( 'Are you sure you want to remove these service hours?', 'orderable' ),
				),
			)
		);

		do_action( 'orderable_admin_assets_enqueued' );
	}

	/**
	 * Add body classes.
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public static function body_class( $classes = array() ) {
		if ( $button_style = Orderable_Settings::get_setting( 'style_style_buttons' ) ) {
			$classes[] = sprintf( 'orderable--button-style-%s', $button_style );
		}

		return $classes;
	}

	/**
	 * Adjust brightness of hex.
	 *
	 * @param string $hex
	 * @param int    $steps
	 *
	 * @return string
	 */
	public static function adjust_hex( $hex, $steps ) {
		// Steps should be between -255 and 255. Negative = darker, positive = lighter
		$steps = max( - 255, min( 255, $steps ) );

		// Normalize into a six character long hex string
		$hex = str_replace( '#', '', $hex );
		if ( strlen( $hex ) == 3 ) {
			$hex = str_repeat( substr( $hex, 0, 1 ), 2 ) . str_repeat( substr( $hex, 1, 1 ), 2 ) . str_repeat( substr( $hex, 2, 1 ), 2 );
		}

		// Split into three parts: R, G and B
		$color_parts = str_split( $hex, 2 );
		$return      = '#';

		foreach ( $color_parts as $color ) {
			$color   = hexdec( $color ); // Convert to decimal
			$color   = max( 0, min( 255, $color + $steps ) ); // Adjust color
			$return .= str_pad( dechex( $color ), 2, '0', STR_PAD_LEFT ); // Make two char hex code
		}

		return $return;
	}
}
