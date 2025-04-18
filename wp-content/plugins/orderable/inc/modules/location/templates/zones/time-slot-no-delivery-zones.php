<?php
/**
 * Template: Time Slot No Delivery Zones.
 *
 * @since   1.18.0
 * @package Orderable
 */

$style_css = empty( ! $data['delivery_zones'] ) ? 'display: none;' : '';
?>

<div
class="orderable-table-delivery-zones-row__no-items" style="<?php echo esc_attr( $style_css ); ?>">

	<p class="orderable-table-delivery-zones-row__no-items-desc">
		<?php
		// @todo reword to prompt the user what to do and how "no zones" works.
		echo esc_html_e( 'No delivery zones have been added for these service hours. This slot will be available to all delivery zones that match the customer\'s address.', 'orderable' );
		?>
	</p>

</div>
