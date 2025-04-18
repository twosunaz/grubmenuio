<?php
/**
 * Render Divider block.
 *
 * @package orderable
 */

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$order = Orderable_Receipt_Layouts::get_order();

if ( ! $order ) {
	return;
}

$line_style = $attributes['lineStyle'] ?? 'dashed';
$height     = $attributes['height'] ?? 2;

$style = "border:none; border-top-style:{$line_style}; border-top-width:{$height}px";

?>

<div <?php echo wp_kses_data( Orderable_Receipt_Layouts::get_receipt_block_wrapper_attributes() ); ?>>
	<hr
		style="<?php echo esc_attr( $style ); ?>"
	/>
</div>
