<?php
/**
 * Template: Product Actions.
 *
 * This template can be overridden by copying it to yourtheme/orderable/actions.php
 *
 * HOWEVER, on occasion Orderable will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Orderable/Templates
 *
 * @var WC_Product_Variable $product Product.
 */

defined( 'ABSPATH' ) || exit;
?>

<?php
/**
 * Fires before product actions in the product card.
 *
 * @since 1.7.0
 * @hook orderable_before_product_actions
 * @param WC_Product $product The product.
 * @param array      $args    Layout settings.
 */
do_action( 'orderable_before_product_actions', $product, $args );
?>

<div class="orderable-product__actions">
	<div class="orderable-product__actions-price">
		<?php echo $product->get_price_html(); ?>
	</div>
	<div class="orderable-product__actions-button">
		<?php
		if (
			! empty( $args['quantity_roller'] ) &&
			! $product->is_type( 'variable' ) &&
			( ! class_exists( 'Orderable_Pro_Conditions_Matcher' ) || empty( Orderable_Pro_Conditions_Matcher::get_applicable_cpt( 'orderable_addons', $product ) ) )
		) {
			$cart_item = wp_doing_ajax() ? array() : Orderable_Helpers::is_product_in_the_cart( $product->get_id() );

			Orderable_Products::get_quantity_roller( $cart_item );
		}

		if ( empty( $cart_item_key ) ) {
			echo wp_kses_post( Orderable_Products::get_add_to_cart_button( $product, 'orderable-product__add-to-order', $args ) );
		} else {
			echo wp_kses_post( Orderable_Products::get_update_cart_item_button( $cart_item_key, $product, 'orderable-product__update-cart-item orderable-button--filled' ) );
		}
		?>
	</div>
</div>
