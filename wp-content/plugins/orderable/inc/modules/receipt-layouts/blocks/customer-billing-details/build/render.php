<?php
/**
 * Render Customer Billing Details block.
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
		<div class="wp-block-orderable-customer-billing-details__label wp-block-orderable-receipt-layouts__label">
			<?php echo esc_html( $attributes['label'] ); ?>
		</div>
	<?php endif; ?>

	<div class="wp-block-orderable-customer-billing-details__address">
		<?php echo wp_kses_post( $order->get_formatted_billing_address() ); ?>
	</div>

	<?php if ( $attributes['showPhone'] ) : ?>
		<div class="wp-block-orderable-customer-billing-details__phone">
			<?php echo esc_html( $order->get_billing_phone() ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $attributes['showEmail'] ) : ?>
		<div class="wp-block-orderable-customer-billing-details__email">
			<?php echo esc_html( $order->get_billing_email() ); ?>
		</div>
	<?php endif; ?>
</div>
