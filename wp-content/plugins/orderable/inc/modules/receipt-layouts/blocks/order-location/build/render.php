<?php
/**
 * Render Order Location block.
 *
 * @package orderable
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

$location_name = $order->get_meta( '_orderable_location_name' );
$location_name = empty( $location_name ) ? __( 'Main Location', 'orderable' ) : $location_name;

$label = $attributes['label'] ?? __( 'Location:', 'orderable' );
?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php printf( '<span class="wp-block-orderable-receipt-layouts__label">%s</span>%s', esc_html( $label ), esc_html( $location_name ) ); ?>
</div>
