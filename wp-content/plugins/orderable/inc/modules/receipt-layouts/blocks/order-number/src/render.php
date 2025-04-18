<?php
/**
 * Render Order Number block.
 *
 * @package orderable
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

$label = $attributes['label'] ?? __( 'Order Number #', 'orderable' );
?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php printf( '<span class="wp-block-orderable-receipt-layouts__label">%s</span>%d', esc_html( $label ), absint( $order->get_id() ) ); ?>
</div>
