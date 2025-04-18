<?php
/**
 * Render Order Totals block.
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

$keys_to_skip = [ 'shipping', 'payment_method' ];
foreach ( $order->get_order_item_totals() as $key => $total ) {
	if ( in_array( $key, $keys_to_skip, true ) ) {
		continue;
	}

	if ( str_contains( $key, 'orderable_' ) ) {
		continue;
	}

	$totals[ $key ] = $total; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
}
?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php foreach ( $totals ?? [] as $total ) : ?>
		<div class="wp-block-orderable-order-totals__item">
			<span class="wp-block-orderable-receipt-layouts__label">
				<?php echo esc_html( $total['label'] ); ?>
			</span>
			<?php echo wp_kses_post( $total['value'] ); ?>
		</div>
	<?php endforeach; ?>
</div>
