<?php
/**
 * Render Order Payment Method block.
 *
 * @package orderable
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

$label = $attributes['label'] ?? __( 'Payment method: ', 'orderable' );
?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php printf( '<span class="wp-block-orderable-receipt-layouts__label">%s</span>%s', esc_html( $label ), esc_html( $order->get_payment_method_title() ) ); ?>
</div>
