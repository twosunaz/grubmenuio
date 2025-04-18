<?php
/**
 * Template: Product Options.
 *
 * This template can be overridden by copying it to yourtheme/orderable/options.php
 *
 * HOWEVER, on occasion Orderable will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Orderable/Templates
 *
 * @var WC_Product $product                  Product.
 * @var bool       $orderable_single_product Determines whether it's possible to add to cart (i.e. a single product view).
 * @var array      $args                     Array of args.
 */

defined( 'ABSPATH' ) || exit;

global $orderable_single_product;

$orderable_single_product = true; ?>

<div class="orderable-product orderable-product--options orderable-product--image-cropped">
	<?php require Orderable_Helpers::get_template_path( 'templates/product/hero.php' ); ?>

	<div class="orderable-sb-container" data-orderable-scroll-id="product">
		<?php do_action( 'orderable_side_menu_before_product_title', $product, $args ); ?>

		<h2 class="orderable-product__title"><?php echo wp_kses_post( $product->get_name() ); ?></h2>

		<?php do_action( 'orderable_side_menu_before_product_options_wrapper', $product, $args ); ?>

		<div class="orderable-product__options-wrap">
			<?php do_action( 'orderable_side_menu_before_product_options', $product, $args ); ?>

			<?php if ( ! empty( $attributes ) ) { ?>
				<table class="orderable-product__options" cellspacing="0" cellpadding="0">
					<?php foreach ( $attributes as $attribute_name => $options ) : ?>
						<tr class="orderable-product__option">
							<th class="orderable-product__option-label">
								<label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo wc_attribute_label( $attribute_name ); // WPCS: XSS ok. ?></label>
							</th>
							<td class="orderable-product__option-select">
								<?php
									wc_dropdown_variation_attribute_options(
										array(
											'options'   => $options,
											'attribute' => $attribute_name,
											'selected'  => empty( $selected[ wc_variation_attribute_name( $attribute_name ) ] ) ? false : $selected[ wc_variation_attribute_name( $attribute_name ) ],
											'product'   => $product,
											'class'     => 'orderable-input orderable-input--select orderable-input--validate',
										)
									);
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php } ?>

			<?php do_action( 'orderable_side_menu_after_product_options', $product, $args ); ?>

			<div class="orderable-product__messages"></div>

			<?php if ( ! empty( $variations_json ) ) : ?>
				<script class="orderable-product__variations" type="application/json"><?php echo $variations_json; ?></script>
			<?php endif; ?>
		</div>

	</div>

	<?php do_action( 'orderable_side_menu_after_product_options_wrapper', $product, $args ); ?>

	<?php require Orderable_Helpers::get_template_path( 'templates/product/actions.php' ); ?>
</div>
