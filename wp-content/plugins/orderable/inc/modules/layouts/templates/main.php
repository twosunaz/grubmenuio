<?php
/**
 * Layout: Main layout.
 *
 * This template can be overridden by copying it to yourtheme/orderable/layouts/main.php
 *
 * HOWEVER, on occasion Orderable will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Orderable/Templates
 *
 * @var array $products Array of products, sorted by category.
 * @var array $args     Array of shortcode args.
 */

defined( 'ABSPATH' ) || exit;

$original_products = $products;
?>

<div class="orderable-main <?php echo esc_attr( apply_filters( 'orderable_main_class', '', $args ) ); ?>">
	<?php do_action( 'orderable_main_before_sections', $args, $original_products ); ?>

	<div class="orderable-main__sections">
		<?php foreach ( $products as $product_group ) { ?>
			<?php
			$category             = $product_group['category'];
			$products             = $product_group['products'];
			$has_child_categories = ! empty( $category['children'] );
			?>

			<?php
			if ( empty( $product_group['products'] ) && ! $has_child_categories ) {
				continue;
			}
			?>

			<div id="category-<?php echo esc_attr( ! empty( $category['slug'] ) ? urldecode( $category['slug'] ) : 'uncategorized' ); ?>" class="orderable-main__group">
				<?php do_action( 'orderable_main_before_products', $args, $product_group['category'], $product_group['products'] ); ?>

				<?php
				if ( $has_child_categories ) {
					foreach ( $product_group['category']['children'] as $child_category_group ) {
						$category = $child_category_group['category'];
						$products = $child_category_group['products'];

						if ( empty( $products ) ) {
							continue;
						}

						do_action( 'orderable_main_before_products_category_children', $args, $category, $products );

						include Orderable_Helpers::get_template_path( 'products-list.php', 'layouts' );

						do_action( 'orderable_main_after_products_category_children', $args, $category, $products );
					}
				} else {
					include Orderable_Helpers::get_template_path( 'products-list.php', 'layouts' );
				}
				?>

				<?php do_action( 'orderable_main_after_products', $args, $product_group['category'], $product_group['products'] ); ?>
			</div>
		<?php } ?>
	</div>

	<?php do_action( 'orderable_main_after_sections', $args, $original_products ); ?>
</div>
