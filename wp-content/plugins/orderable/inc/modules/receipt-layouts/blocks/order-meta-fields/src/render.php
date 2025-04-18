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

$fields_to_include = $attributes['fieldsToInclude'] ?? [];
$fields_to_exclude = $attributes['fieldsToExclude'] ?? [];

$metadata = [];

foreach ( $order->get_meta_data() as $metadata_item ) {
	$data = $metadata_item->get_data();

	if ( empty( $data['key'] ) ) {
		continue;
	}

	if ( in_array( $data['key'], $fields_to_include, true ) ) {
		$metadata[] = $data;

		continue;
	}

	if ( ! empty( $fields_to_include ) ) {
		continue;
	}

	if ( 0 === strpos( $data['key'], '_' ) ) {
		continue;
	}

	if ( in_array( $data['key'], $fields_to_exclude, true ) ) {
		continue;
	}

	$metadata[] = $data;
}

/**
 * Filter metadata to be used in the Order Meta Fields block.
 *
 * @since 1.8.0
 * @hook orderable_order_meta_fields_block_metadata
 * @param  array $metadata The order metadata.
 * @param  WC_Order $order The order object.
 * @param  array $fields_to_include Fields to include.
 * @param  array $fields_to_exclude Fields to exclude.
 */
$metadata = apply_filters( 'orderable_order_meta_fields_block_metadata', $metadata, $order, $fields_to_include, $fields_to_exclude );

?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<?php if ( $attributes['showLabel'] ) : ?>
		<div class="wp-block-orderable-order-meta__label wp-block-orderable-receipt-layouts__label">
			<?php echo esc_html( $attributes['label'] ); ?>
		</div>
	<?php endif; ?>

	<div class="wp-block-orderable-order-meta__metadata">
		<?php foreach ( $metadata as $metadata_item ) : ?>
			<div class="wp-block-orderable-order-meta__metadata-item">
				<span class="wp-block-orderable-order-meta__metadata-item-key wp-block-orderable-receipt-layouts__label">
					<?php printf( '%s:', esc_html( $metadata_item['key'] ) ); ?>
				</span>
				<span class="wp-block-orderable-order-meta__metadata-item-value">
					<?php echo esc_html( $metadata_item['value'] ); ?>
				</span>
			</div>
		<?php endforeach; ?>
	</div>
</div>
