<?php
/**
 * Template: Pro modal.
 *
 * This template can be overridden by copying it to yourtheme/orderable/accordion.php
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
 * @var array               $args    Array of args.
 */

defined( 'ABSPATH' ) || exit;

$accordion_data = Orderable_Products::get_accordion_data( $product );

if ( empty( $accordion_data ) ) {
	return;
}

$focus = ! empty( $args['focus'] ) ? $args['focus'] : false; ?>

<div class="orderable-accordion">
	<?php foreach ( $accordion_data as $product_tab_id => $product_tab ) { ?>
		<?php
		$is_focus = $focus === $product_tab['id'];
		$item_id  = sprintf( 'tab-%s-%d', $product_tab_id, $product->get_id() );
		?>
		<div id="<?php echo esc_attr( $product_tab['id'] ); ?>" class="orderable-accordion__item">
			<a href="#<?php echo esc_attr( $item_id ); ?>" class="orderable-accordion__item-link <?php echo esc_attr( $is_focus ? 'orderable-accordion__item-link--active' : '' ); ?>"><?php echo wp_kses_post( $product_tab['title'] ); ?></a>

			<div id="<?php echo esc_attr( $item_id ); ?>" class="orderable-accordion__item-content <?php echo esc_attr( $is_focus ? 'orderable-accordion__item-content--active' : '' ); ?>">
				<?php echo wp_kses_post( $product_tab['content'] ); ?>
			</div>
		</div>
	<?php } ?>
</div>
