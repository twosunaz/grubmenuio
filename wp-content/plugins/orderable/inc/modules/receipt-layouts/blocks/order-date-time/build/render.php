<?php
/**
 * Render Order Date Time block.
 *
 * @package orderable
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

$order_date = $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

if ( empty( $order_date ) ) {
	return;
}

$label = $attributes['label'] ?? '';

?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php printf( '<span class="wp-block-orderable-receipt-layouts__label">%s</span> %s', esc_html( $label ), esc_html( $order_date ) ); ?>
</div>
