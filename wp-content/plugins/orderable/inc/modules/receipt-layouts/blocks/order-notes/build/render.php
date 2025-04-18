<?php
/**
 * Render Order Notes block.
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
		<div class="wp-block-orderable-order-notes__label wp-block-orderable-receipt-layouts__label">
			<?php echo esc_html( $attributes['label'] ); ?>
		</div>
	<?php endif; ?>

	<?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?>
</div>
