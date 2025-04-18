<?php
/**
 * Template: Delivery Zones Modal (Add Existing)
 *
 * @since   1.18.0
 * @package Orderable
 */

?>

<div
style="display: none;"
id="orderable-delivery-zones-modal-add-existing"
class="orderable-delivery-zones-modal orderable-delivery-zones-modal-add-existing">

	<div class="orderable-delivery-zones-modal__header">
		<h3 class="orderable-delivery-zones-modal__title">
			<?php esc_html_e( 'Select Delivery Zone(s)', 'orderable' ); ?>
		</h3>
	</div>

	<form novalidate class="orderable-delivery-zones-modal__form">

		<input autocomplete="off" class="js-delivery-zone-modal-time-slot" type="hidden" name="time-slot" value=""/>
		<input autocomplete="off" class="js-delivery-zone-modal-time-slot-index" type="hidden" name="time-slot-index" value=""/>

		<div class="orderable-delivery-zones-modal__search">

			<div class="orderable-delivery-zones-modal__search-container">
				<span class="dashicons dashicons-search"></span>
				<input
				id="js-delivery-zone-search"
				class="orderable-delivery-zones-modal__field orderable-delivery-zones-modal__field-search"
				autocomplete="off"
				type="text"
				name="zone_search"
				value=""
				placeholder="<?php esc_html_e( 'Search Zones...', 'orderable' ); ?>"/>
			</div>

		</div>

		<div class="orderable-delivery-zones-modal__body">
			<?php $zones = Orderable_Location_Zones::get_zones(); ?>

			<?php if ( ! empty( $zones ) ) { ?>
				<ul id="js-delivery-zone-modal-zones-list" class="orderable-delivery-zones-modal__zones-list">
					<?php foreach ( $zones as $zone ) { ?>
						<li
							class="orderable-delivery-zones-modal__zones-list-item js-delivery-zones-list-item">
							<label for="zone_<?php echo esc_attr( $zone['zone_id'] ); ?>">
								<input
									autocomplete="off"
									id="zone_<?php echo esc_attr( $zone['zone_id'] ); ?>"
									class="orderable-delivery-zones-modal__field-checkbox"
									type="checkbox"
									name="zone_<?php echo esc_attr( $zone['zone_id'] ); ?>"
									value="<?php echo esc_attr( $zone['zone_id'] ); ?>"
									data-zone-postcodes="<?php echo esc_attr( $zone['zone_postcodes'] ); ?>"
									data-zone-name="<?php echo esc_attr( $zone['zone_name'] ); ?>"
									data-zone-fee="<?php echo esc_attr( $zone['zone_fee'] ); ?>"
								/>
								<span><?php echo esc_html( $zone['zone_name'] ); ?></span>
							</label>
						</li>
					<?php } ?>
				</ul>
			<?php } ?>

			<p id="js-no-delivery-zones-msg" style="display: none" class="orderable-delivery-zones-modal__msg-no-zones">
				<?php esc_html_e( 'There are no existing delivery zones available.', 'orderable' ); ?>

				<button id="js-transition-existing-to-new-modal" type="button" class="orderable-delivery-zones-modal__button orderable-delivery-zones-modal__button--add-update">
					<?php echo esc_html__( 'Add New Delivery Zone', 'orderable' ); ?>
				</button>
			</p>
		</div>

	</form>

	<div class="orderable-delivery-zones-modal__footer">
		<button id="js-cancel-delivery-zone-modal" type="button" class="orderable-delivery-zones-modal__button orderable-delivery-zones-modal__button--cancel">
			<?php esc_html_e( 'Cancel', 'orderable' ); ?>
		</button>

		<button id="js-add-existing-delivery-zone" type="button" class="orderable-delivery-zones-modal__button orderable-delivery-zones-modal__button--add-existing" data-action="add-existing" disabled>
			<span>
				<svg class="icon" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid" viewBox="0 0 100 100">
				<circle cx="50" cy="50" r="35" fill="none" stroke="#fff" stroke-dasharray="164.93361431346415 56.97787143782138" stroke-width="10">
					<animateTransform attributeName="transform" dur="1s" keyTimes="0;1" repeatCount="indefinite" type="rotate" values="0 50 50;360 50 50"/>
				</circle>
				</svg>
				<span class="text"><?php esc_html_e( 'Add Existing Zone(s)', 'orderable' ); ?></span>
			</span>
		</button>
	</div>

</div>
