<?php
/**
 * Render Order Service Date Time block.
 *
 * @package orderable
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

$service_type = $order->get_meta( '_orderable_service_type' );
$service_type = Orderable_Services::get_service_label( $service_type );

$order_service_date = $order->get_meta( 'orderable_order_date' );

if ( empty( $order_service_date ) ) {
	return;
}

$order_service_time = false;
if ( class_exists( 'Orderable_Timings_Pro_Checkout' ) ) {
	$order_service_time = $order->get_meta( 'orderable_order_time' );
}

?>

<?php if ( $attributes['showDate'] ?? true ) : ?>
	<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
		<?php
			// translators: %1$s - date.
			printf( __( '<span class="wp-block-orderable-receipt-layouts__label">%1$s Date:</span> %2$s', 'orderable' ), esc_html( $service_type ), esc_html( $order_service_date ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
<?php endif; ?>

<?php if ( $order_service_time && ( $attributes['showTime'] ?? true ) ) : ?>
	<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
		<?php
			// translators: %1$s - time
			printf( __( '<span class="wp-block-orderable-receipt-layouts__label">%1$s Time:</span> %2$s', 'orderable' ), esc_html( $service_type ), esc_html( $order_service_time ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
<?php endif; ?>
