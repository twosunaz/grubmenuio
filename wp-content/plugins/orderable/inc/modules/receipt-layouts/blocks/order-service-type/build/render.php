<?php
/**
 * Render Order Service Types block.
 *
 * @package orderable
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

$service_type = $order->get_meta( '_orderable_service_type' );

if ( empty( $service_type ) ) {
	return;
}

$service_type = Orderable_Services::get_service_label( $service_type );

$label = $attributes['label'] ?? __( 'Shipping:', 'orderable' );
?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php printf( '<span class="wp-block-orderable-receipt-layouts__label">%s</span>%s', esc_html( $label ), esc_html( $service_type ) ); ?>
</div>
