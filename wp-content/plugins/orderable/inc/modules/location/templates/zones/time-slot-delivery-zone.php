<?php
/**
 * Template: Time Slot Delivery Zone.
 *
 * `$zone` contains `zone_id` and `zone_name`.
 *
 * `$index` contains the presentational index of the zone e.g. Delivery Zone {$index}
 *
 * `$time_slot_id` contains the index of the time slot.
 *
 * @since   1.18.0
 * @package Orderable
 */

$input_data = array(
	'time_slot_id'   => $time_slot_id,
	'zone_id'        => $zone['zone_id'],
	'zone_name'      => $zone['zone_name'],
	'zone_postcodes' => $zone['zone_postcodes'],
	'zone_fee'       => $zone['zone_fee'],
);

$encoded_input_data = wp_json_encode( $input_data );
$encoded_input_data = ( $encoded_input_data ) ? $encoded_input_data : '';
?>

<div
class="orderable-table-delivery-zones-row__item"
data-slot-id="<?php echo esc_attr( $time_slot_id ); ?>"
data-zone-id="<?php echo esc_attr( $zone['zone_id'] ); ?>"
data-zone-name="<?php echo esc_attr( $zone['zone_name'] ); ?>"
data-zone-postcodes="<?php echo esc_attr( $zone['zone_postcodes'] ); ?>"
data-zone-fee="<?php echo esc_attr( $zone['zone_fee'] ); ?>"
data-zone-count="<?php echo esc_attr( $zone_index ); ?>"
>

	<div class="orderable-table-delivery-zones-row__item-info">

		<h4 class="orderable-table-delivery-zones-row__item-title">
			<?php
			printf(
				/* Translators: delivery zone row index */
				esc_html__( 'Delivery Zone %d', 'orderable' ),
				esc_attr( $zone_index )
			);
			?>
		</h4>

		<p class="orderable-table-delivery-zones-row__item-name">
			<span class="dashicons dashicons-location"></span>
			<?php echo esc_html( $zone['zone_name'] ); ?>
		</p>

	</div>

	<div class="orderable-table-delivery-zones-row__item-links">

		<button type="button" class="orderable-table-delivery-zones-row__item-link js-open-add-delivery-zone-modal" data-action="edit">
			<span class="dashicons dashicons-edit"></span>
			<?php esc_html_e( 'Edit', 'orderable' ); ?>
		</button>

		<button type="button" class="orderable-table-delivery-zones-row__item-link js-remove-delivery-zone">
			<span class="dashicons dashicons-trash"></span>
			<?php esc_html_e( 'Remove', 'orderable' ); ?>
		</button>

	</div>

	<input
	type="hidden"
	name="service_hours[delivery][<?php echo esc_attr( $time_slot_index ); ?>][zones][]"
	value="<?php echo esc_attr( $encoded_input_data ); ?>"
	/>

</div>
