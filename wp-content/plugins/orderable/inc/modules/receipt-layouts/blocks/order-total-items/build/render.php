<?php
/**
 * Render Order Total Ttems block.
 *
 * @package orderable
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

$label = $attributes['label'] ?? __( 'Total items: ', 'orderable' );

$items_count = $order->get_item_count();
?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php printf( '<span class="wp-block-orderable-receipt-layouts__label">%s</span>%d', esc_html( $label ), absint( $items_count ) ); ?>
</div>
