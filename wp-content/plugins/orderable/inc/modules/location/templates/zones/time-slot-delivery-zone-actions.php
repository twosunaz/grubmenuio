<?php
/**
 * Template: Time Slot Delivery Zone Actions.
 *
 * @since   1.18.0
 * @package Orderable
 */

?>

<div
class="orderable-table-delivery-zones-row__actions">

	<button type="button" class="orderable-table-delivery-zones-row__item-link js-add-existing-delivery-zone">
		<span class="dashicons dashicons-location"></span>
		<?php esc_html_e( 'Use Existing Zone(s)', 'orderable' ); ?>
	</button>

	<button type="button" class="orderable-table-delivery-zones-row__item-link js-open-add-delivery-zone-modal" data-action="add-new">
		<span class="dashicons dashicons-plus"></span>
		<?php esc_html_e( 'Add New Delivery Zone', 'orderable' ); ?>
	</button>

</div>
