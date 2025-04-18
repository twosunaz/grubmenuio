<?php
/**
 * Render Order Table block.
 *
 * @package orderable
 */

if ( ! class_exists( 'Orderable_Table_Ordering_Pro_Order' ) ) {
	return;
}

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

$table = $order->get_meta( Orderable_Table_Ordering_Pro_Order::$key_table );

if ( empty( $table ) ) {
	return;
}

$label = $attributes['label'] ?? __( 'Table #', 'orderable' );

?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php printf( '<span class="wp-block-orderable-receipt-layouts__label">%s</span>%s', esc_html( $label ), esc_html( $table ) ); ?>
</div>
