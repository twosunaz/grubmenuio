<?php
/**
 * Layout: Single product.
 *
 * This template can be overridden by copying it to yourtheme/orderable/layouts/product.php
 *
 * HOWEVER, on occasion Orderable will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Orderable/Templates
 *
 * @var array      $category Category array.
 * @var WC_Product $product  Product object.
 * @var array      $args     Array of args for the shortcode.
 */

defined( 'ABSPATH' ) || exit;

$class = Orderable_Layouts::get_product_card_classes( $args ); ?>

<div
	class="<?php echo esc_attr( implode( ' ', $class ) ); ?>"
	data-orderable-product-id="<?php echo esc_attr( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ); ?>"
	data-orderable-product-type="<?php echo esc_attr( $product->get_type() ); ?>"
	<?php if ( ! empty( $args['card_click'] ) ) { ?>
		data-orderable-trigger="<?php echo esc_attr( $args['card_click'] ); ?>"
		data-orderable-variation-id="<?php echo esc_attr( $product->is_type( 'variation' ) ? $product->get_id() : 0 ); ?>"
		data-orderable-variation-attributes=""
	<?php } ?>
>
	<?php require Orderable_Helpers::get_template_path( 'templates/product/hero.php' ); ?>
	<div class="orderable-product__content-wrap">
		<?php require Orderable_Helpers::get_template_path( 'templates/product/card-content.php' ); ?>
		<?php require Orderable_Helpers::get_template_path( 'templates/product/actions.php' ); ?>
	</div>
</div>
