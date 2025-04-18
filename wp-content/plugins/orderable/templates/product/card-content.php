<?php
/**
 * Template: Product Card Content.
 *
 * This template can be overridden by copying it to yourtheme/orderable/card-content.php
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

/**
 * Filter the product short description to show in the card.
 *
 * @since 1.1.0
 * @hook orderable_short_description
 * @param  string     $short_description The product short description.
 * @param  WC_Product $product           The product.
 * @return string New value
 */
$short_description = apply_filters( 'orderable_short_description', $product->get_short_description(), $product );
?>

<div class="orderable-product__content">

	<?php
		/**
		 * Fires before product title in the product card.
		 *
		 * @since 1.7.0
		 * @hook orderable_before_product_title
		 * @param WC_Product $product The product.
		 * @param array      $args    Layout settings.
		 */
		do_action( 'orderable_before_product_title', $product, $args );
	?>

	<h2 class="orderable-product__title"><?php echo esc_html( $product->get_name() ); ?></h2>

	<?php
		/**
		 * Fires before product description in the product card.
		 *
		 * @since 1.7.0
		 * @hook orderable_before_product_description
		 * @param WC_Product $product The product.
		 * @param array      $args    Layout settings.
		 */
		do_action( 'orderable_before_product_description', $product, $args );
	?>

	<?php if ( ! empty( $short_description ) && '&nbsp;' !== $short_description ) { ?>
		<p class="orderable-product__description"><?php echo wp_kses_post( $short_description ); ?></p>
	<?php } ?>
</div>
