<?php
/**
 * Drawer: Floating Cart Button.
 *
 * This template can be overridden by copying it to yourtheme/orderable/drawer/floating-cart.php
 *
 * HOWEVER, on occasion Orderable will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Orderable/Templates
 */

defined( 'ABSPATH' ) || exit;

$position = Orderable_Settings::get_setting( 'style_cart_position' );

if ( 'none' === $position ) {
	return;
}

$cart_count = WC()->cart->get_cart_contents_count();
$style      = Orderable_Drawer_Settings::get_cart_icon_css();
?>

<div class="orderable-floating-cart orderable-floating-cart--<?php echo esc_attr( $position ); ?>" data-orderable-trigger="cart" style="<?php echo esc_attr( $style ); ?>">
	<button class="orderable-floating-cart__button"><?php echo file_get_contents( ORDERABLE_PATH . 'assets/icons/basket.svg' ); ?></button>
	<span class="orderable-floating-cart__count"><?php echo wp_kses_data( $cart_count ); ?></span>
</div>
