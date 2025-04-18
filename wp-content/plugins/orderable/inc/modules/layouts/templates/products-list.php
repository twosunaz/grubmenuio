<?php
/**
 * Layout: Single product.
 *
 * This template can be overridden by copying it to yourtheme/orderable/layouts/products-list.php
 *
 * HOWEVER, on occasion Orderable will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Orderable/Templates
 *
 * @var array $products Array of products.
 * @var array $args     Array of shortcode args.
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="orderable-products-list orderable-products-list--<?php echo esc_attr( $args['layout'] ); ?>">
	<?php foreach ( $products as $product ) { ?>
		<div class="orderable-products-list__item">
			<?php
				/**
				 * Fires before product card.
				 *
				 * @since 1.7.0
				 * @hook orderable_before_product_card
				 * @param WC_Product $product The product.
				 * @param array      $args    Layout settings.
				 */
				do_action( 'orderable_before_product_card', $product, $args );
			?>

			<?php include Orderable_Helpers::get_template_path( 'product.php', 'layouts' ); ?>
		</div>
	<?php } ?>
</div>
