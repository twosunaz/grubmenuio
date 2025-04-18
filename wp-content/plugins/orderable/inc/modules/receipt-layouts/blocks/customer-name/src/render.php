<?php
/**
 * Render Customer name block.
 *
 * @package orderable
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

$name = $order->get_formatted_shipping_full_name();

if ( empty( $name ) ) {
	$name = $order->get_formatted_billing_full_name();
}

if ( empty( $name ) ) {
	return;
}

$label = $attributes['label'] ?? __( 'Customer name: ', 'orderable' );
?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php printf( '<span class="wp-block-orderable-receipt-layouts__label">%s</span>%s', esc_html( $label ), esc_html( $name ) ); ?>
</div>
