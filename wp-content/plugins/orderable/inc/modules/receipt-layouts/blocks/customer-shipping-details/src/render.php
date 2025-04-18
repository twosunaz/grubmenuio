<?php
/**
 * Render Customer Shipping Details block.
 *
 * @package orderable
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php if ( $attributes['showLabel'] ) : ?>
		<div class="wp-block-orderable-customer-shipping-details__label wp-block-orderable-receipt-layouts__label">
			<?php echo esc_html( $attributes['label'] ); ?>
		</div>
	<?php endif; ?>

	<div class="wp-block-orderable-customer-shipping-details__address">
		<?php echo wp_kses_post( $order->get_formatted_shipping_address() ); ?>
	</div>
</div>
