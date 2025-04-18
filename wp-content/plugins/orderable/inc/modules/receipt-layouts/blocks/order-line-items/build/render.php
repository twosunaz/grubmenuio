<?php
/**
 * Render Order Line Items block.
 *
 * @package orderable
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

$show_meta_data = $attributes['showMetaData'] ?? false;

$order_items = $order->get_items();
?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php if ( $attributes['showLabel'] ) : ?>
		<div class="wp-block-orderable-order-line-item__label wp-block-orderable-receipt-layouts__label">
			<?php echo esc_html( $attributes['label'] ); ?>
		</div>
	<?php endif; ?>

	<?php foreach ( $order_items as $item_id => $item ) : ?>
		<div class="wp-block-orderable-order-line-item">
			<div class="wp-block-orderable-order-line-item__data">
				<div>
					<?php
						$qty          = $item->get_quantity();
						$refunded_qty = $order->get_qty_refunded_for_item( $item_id );

					if ( $refunded_qty ) {
						$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
					} else {
						$qty_display = esc_html( $qty );
					}
					?>

					<span class="wp-block-orderable-order-line-item__quantity">
						<?php printf( '%s&times;', esc_html( $qty_display ) ); ?>
					</span>

					<span class="wp-block-orderable-order-line-item__wrapper-name">
						<span class="wp-block-orderable-order-line-item__name">
							<?php echo wp_kses_post( $item->get_name() ); ?>
						</span>
						
						<span class="wp-block-orderable-order-line-item__metadata">
							<?php if ( $show_meta_data ) : ?>
								<?php wc_display_item_meta( $item ); ?>
							<?php endif; ?>
						</span>
					</span>

					<?php if ( $attributes['showPrices'] ) : ?>
						<span class="wp-block-orderable-order-line-item__subtotal">
							<?php echo $order->get_formatted_line_subtotal( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
					<?php endif; ?>
				</div>

				<?php if ( $attributes['showCheckboxes'] ) : ?>
					<div class="wp-block-orderable-order-line-item__checkbox"></div>
				<?php endif; ?>
			</div>

			
		</div>
	<?php endforeach; ?>
</div>
