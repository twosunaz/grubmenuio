<?php
/**
 * Template: Delivery Zones Modal (Add/Update)
 *
 * @since   1.18.0
 * @package Orderable
 */

?>

<div
style="display: none;"
id="orderable-delivery-zones-modal-add-update"
class="orderable-delivery-zones-modal orderable-delivery-zones-modal-add-update">

	<div class="orderable-delivery-zones-modal__header">

		<!-- Dynamic -->
		<h3 class="orderable-delivery-zones-modal__title"></h3>

	</div>

	<div class="orderable-delivery-zones-modal__tabs-nav">

		<button type="button" class="orderable-delivery-zones-modal__tabs-nav-link js-delivery-zones-tab-nav-link active">
			<?php esc_html_e( 'Use Postcode / Zip Code', 'orderable' ); ?>
		</button>

		<!-- Hidden as draw feature not yet implemented -->
		<button style="display: none;" type="button" class="orderable-delivery-zones-modal__tabs-nav-link js-delivery-zones-tab-nav-link">
			<?php esc_html_e( 'Draw Zone', 'orderable' ); ?>
		</button>

	</div>

	<div class="orderable-delivery-zones-modal__body">

		<div id="js-delivery-zone-modal-tab-postcode" class="orderable-delivery-zones-modal__tab orderable-delivery-zones-modal__tab-postcode active">

			<form novalidate class="orderable-delivery-zones-modal__form">

				<input autocomplete="off" class="js-delivery-zone-modal-time-slot" type="hidden" name="time-slot" value=""/>
				<input autocomplete="off" class="js-delivery-zone-modal-time-slot-index" type="hidden" name="time-slot-index" value=""/>
				<input autocomplete="off" id="js-delivery-zone-modal-zone-id" type="hidden" name="zone-id" value=""/>

				<!-- Postcodes / Zip Codes -->
				<label for="js-delivery-zone-modal-postcodes" class="orderable-delivery-zones-modal__label">
					<?php esc_html_e( 'Postcodes / Zip Codes*', 'orderable' ); ?>
				</label>
				<textarea
				id="js-delivery-zone-modal-postcodes"
				name="postcodes"
				class="orderable-delivery-zones-modal__field orderable-delivery-zones-modal__field-postcodes"
				autocomplete="off"
				placeholder="<?php esc_html_e( 'e.g. SW1A 2AA, SW1A*, 90210, 90210...99000', 'orderable' ); ?>"></textarea>

				<p id="js-delivery-zone-modal-valid-postcodes" style="display: none" class="orderable-delivery-zones-modal__msg orderable-delivery-zones-modal__msg-valid-postcodes">
					<?php esc_html_e( 'Please enter at least one valid postcode / zip code.', 'orderable' ); ?>
				</p>

				<!-- Area Name -->
				<label for="js-delivery-zone-modal-area-name" class="orderable-delivery-zones-modal__label">
					<?php esc_html_e( 'Area Name*', 'orderable' ); ?>
				</label>
				<input
				id="js-delivery-zone-modal-area-name"
				type="text"
				name="area_name"
				class="orderable-delivery-zones-modal__field orderable-delivery-zones-modal__field-area-name"
				autocomplete="off"
				placeholder="<?php esc_html_e( 'e.g. City Centre', 'orderable' ); ?>"/>

				<p id="js-delivery-zone-modal-valid-name" style="display: none" class="orderable-delivery-zones-modal__msg orderable-delivery-zones-modal__msg-valid-name">
					<?php esc_html_e( 'Please enter a valid zone name.', 'orderable' ); ?>
				</p>

				<!-- Delivery Fee -->
				<label for="js-delivery-zone-modal-fee" class="orderable-delivery-zones-modal__label">
					<?php esc_html_e( 'Delivery Fee', 'orderable' ); ?>
				</label>
				<input
				id="js-delivery-zone-modal-fee"
				type="text"
				inputmode="decimal"
				placeholder='e.g. 4.99'
				name="fee"
				value=""
				class="orderable-delivery-zones-modal__field orderable-delivery-zones-modal__field-fee"
				autocomplete="off"/>
			</form> 

		</div>

		<!-- Not yet implemented -->
		<div id="js-delivery-zone-modal-tab-draw" class="orderable-delivery-zones-modal__tab orderable-delivery-zones-modal__tab-draw"></div>

	</div>

	<div class="orderable-delivery-zones-modal__footer">

		<button id="js-cancel-delivery-zone-modal" type="button" class="orderable-delivery-zones-modal__button orderable-delivery-zones-modal__button--cancel">
			<?php esc_html_e( 'Cancel', 'orderable' ); ?>
		</button>

		<!-- Dynamic -->
		<button id="js-add-new-delivery-zone" type="button" class="orderable-delivery-zones-modal__button orderable-delivery-zones-modal__button--add-update" disabled>
			<span>
				<svg class="icon" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid" viewBox="0 0 100 100">
				<circle cx="50" cy="50" r="35" fill="none" stroke="#fff" stroke-dasharray="164.93361431346415 56.97787143782138" stroke-width="10">
					<animateTransform attributeName="transform" dur="1s" keyTimes="0;1" repeatCount="indefinite" type="rotate" values="0 50 50;360 50 50"/>
				</circle>
				</svg>
				<span class="text"></span>
			</span>
		</button>

	</div>

</div>
