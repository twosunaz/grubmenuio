<?php
/**
 * Module: Location (Zones).
 *
 * @since   1.18.0
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable_Location_Zones_Admin class.
 */
class Orderable_Location_Zones_Admin {
	/**
	 * Init.
	 */
	public static function run() {
		add_action( 'orderable_before_time_slots_table_end', array( __CLASS__, 'output_delivery_zones_row' ), 10, 4 );
		add_action( 'admin_footer', array( __CLASS__, 'output_delivery_zones_js_templates' ) );
		add_action( 'admin_footer', array( __CLASS__, 'output_delivery_zones_modal_html' ) );
		add_action( 'orderable_before_save_service_hours', array( __CLASS__, 'delete_lookup_entry' ), 10, 2 );
		add_action( 'orderable_location_service_hour_inserted', array( __CLASS__, 'save_delivery_zone' ), 10, 3 );
		add_action( 'orderable_location_service_hour_updated', array( __CLASS__, 'save_delivery_zone' ), 10, 3 );
	}

	/**
	 * Output the delivery zones row in a given time slots table.
	 *
	 * @param string $type            Either `delivery` or `pickup`.
	 * @param int    $time_slot_id    Time slot ID.
	 * @param int    $time_slot_index Time slot index.
	 * @param array  $data            Location data.
	 * @return void
	 */
	public static function output_delivery_zones_row( $type, $time_slot_id, $time_slot_index, $data ) {
		if ( 'delivery' !== $type ) {
			return;
		}
		?>

		<tr class="orderable-table-delivery-zones-row">
			<th class="orderable-table__column orderable-table__column--medium">
				<?php esc_html_e( 'Delivery Zones', 'orderable' ); ?>
			</th>
			<td class="orderable-table-delivery-zones-row__list">
				<?php
				$slot_has_zones = false;

				if ( $data['delivery_zones'] ) {
					$zone_index = 1;
					foreach ( $data['delivery_zones'] as $zone ) {
						include Orderable_Helpers::get_template_path( 'zones/time-slot-delivery-zone.php', 'location' );
						$zone_index++;
					}
				}

				include Orderable_Helpers::get_template_path( 'zones/time-slot-no-delivery-zones.php', 'location' );
				include Orderable_Helpers::get_template_path( 'zones/time-slot-delivery-zone-actions.php', 'location' );
				?>
			</td>
		</tr>

		<?php
	}

	/**
	 * Output the JS template for delivery zone rows.
	 *
	 * @return void
	 */
	public static function output_delivery_zones_js_templates() {
		$tags = array(
			'a' => array(
				'href' => array(),
			),
		);

		$delivery_zones_page_url = sprintf(
			/* translators: opening anchor tag, closing anchor tag */
			__( 'All of the delivery zones that you have added to your store locations in Orderable are listed below. To manage them, click %1$shere%2$s.', 'orderable' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=orderable-delivery-zones' ) ) . '">',
			'</a>'
		);
		?>
		<!-- Delivery Zones for Locations UI -->
		<script type="text/html" id="tmpl-delivery-zones-row">
			<div
			class="orderable-table-delivery-zones-row__item"
			data-zone-count="{{{data.delivery_zone_id}}}"
			data-slot-id="{{{data.time_slot_id}}}"
			data-zone-id="{{{data.zone_id}}}"
			data-zone-name="{{{data.zone_name}}}"
			data-zone-postcodes="{{{data.zone_postcodes}}}"
			data-zone-fee="{{{data.zone_fee}}}"
			>

				<div class="orderable-table-delivery-zones-row__item-info">

					<h4 class="orderable-table-delivery-zones-row__item-title">
						{{{data.text_zone_title}}} {{{data.delivery_zone_id}}}
					</h4>

					<p class="orderable-table-delivery-zones-row__item-name">
						<span class="dashicons dashicons-location"></span>
						{{{data.zone_name}}}
					</p>

				</div>

				<div class="orderable-table-delivery-zones-row__item-links">

					<button type="button" class="orderable-table-delivery-zones-row__item-link js-open-add-delivery-zone-modal" data-action="edit">
						<span class="dashicons dashicons-edit"></span>
						{{{data.text_edit_zone}}}
					</button>

					<button type="button" class="orderable-table-delivery-zones-row__item-link js-remove-delivery-zone">
						<span class="dashicons dashicons-trash"></span>
						{{{data.text_remove_zone}}}
					</button>

				</div>

				<input type="hidden" name="service_hours[delivery][{{{data.time_slot_index}}}][zones][]" value="{{data.input_value}}"/>

			</div>
		</script>

		<!-- Existing Zones List Item -->
		<script type="text/html" id="tmpl-existing-zones-list-item">
			<li
			class="orderable-delivery-zones-modal__zones-list-item js-delivery-zones-list-item">
				<label for="zone_{{{data.zone_id}}}">
					<input
					autocomplete="off"
					id="zone_{{{data.zone_id}}}"
					class="orderable-delivery-zones-modal__field-checkbox"
					type="checkbox"
					name="zone_{{{data.zone_id}}}"
					value="{{{data.zone_id}}}"
					data-zone-postcodes="{{{data.zone_postcodes}}}"
					data-zone-name="{{{data.zone_name}}}"
					data-zone-fee="{{{data.zone_fee}}}"
					/>
					<span>{{{data.zone_name}}}</span>
				</label>
			</li>
		</script>

		<!-- Delivery Zones Table for WC Shipping Settings -->
		<script type="text/html" id="tmpl-delivery-zones-shipping-table">

			<div id="js-orderable-delivery-zones-table-container" class="orderable-delivery-zones-table-container">

				<h2 class="orderable-delivery-zones-heading">
					<span class="orderable-swoosh"></span>
					<?php echo esc_html__( 'Orderable Delivery Zones', 'orderable' ); ?>
				</h2>

				<p><?php echo wp_kses( $delivery_zones_page_url, $tags ); ?></p>

				<table class="orderable-delivery-zones widefat">
					<thead>
						<tr>
							<th class="orderable-delivery-zone-sort"></th>
							<th class="orderable-delivery-zone-name"><?php echo esc_html__( 'Zone name', 'orderable' ); ?></th>
							<th class="orderable-delivery-zone-region"><?php echo esc_html__( 'Regions(s)', 'orderable' ); ?></th>
							<th class="orderable-delivery-zone-locations"><?php echo esc_html__( 'Location(s)', 'orderable' ); ?></th>
							<th class="orderable-delivery-zone-methods"><?php echo esc_html__( 'Shipping method(s)', 'orderable' ); ?></th>
						</tr>
					</thead>
					<tbody id="js-orderable-delivery-zone-rows" class="orderable-delivery-zone-rows ui-sortable"></tbody>
				</table>

			</div>

		</script>

		<script type="text/html" id="tmpl-delivery-zones-list-table-row">

			<tr id="{{{data.zone_id}}}">
				<td class="zone_id column-zone_id hidden" data-colname="<?php echo esc_attr__( 'ID', 'orderable' ); ?>">{{{data.zone_id}}}</td>
				<td class="zone_name column-zone_name has-row-actions column-primary" data-colname="<?php echo esc_attr__( 'Area name', 'orderable' ); ?>">
					<span class="value">{{{data.zone_name}}}</span>
					<div class="row-actions">
						<span class="edit"><a role="button" href="#" data-zone-id="{{{data.zone_id}}}" data-zone-name="{{{data.zone_name}}}" data-zone-postcodes="{{{data.zone_postcodes}}}" data-zone-fee="{{{data.zone_fee}}}" data-action="edit"><?php esc_html_e( 'Edit', 'orderable' ); ?></a> | </span>
						<span class="delete"><a class="submitdelete" href="#" data-zone-id="{{{data.zone_id}}}"><?php esc_html_e( 'Delete', 'orderable' ); ?></a></span>
					</div>
					<button type="button" class="toggle-row">
						<span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'orderable' ); ?></span>
					</button>
				</td>
				<td class="zone_postcodes column-zone_postcodes" data-colname="<?php echo esc_attr__( 'Postcode(s) / Zip Code(s)', 'orderable' ); ?>">{{{data.zone_postcodes}}}</td>
				<td class="zone_locations column-zone_locations" data-colname="<?php echo esc_attr__( 'Location(s)', 'orderable' ); ?>"><?php esc_html_e( 'N/A', 'orderable' ); ?></td>
				<td class="zone_fee column-zone_fee" data-colname="<?php echo esc_attr__( 'Fee', 'orderable' ); ?>">
					<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">£</span>{{{data.zone_fee}}}</bdi></span>
				</td>
			</tr>

		</script>
		<?php
	}

	/**
	 * Output the markup for the modal window.
	 *
	 * @return void
	 */
	public static function output_delivery_zones_modal_html() {
		?>

		<div style="display: none;" id="orderable-delivery-zones-modal-wrapper" class="orderable-delivery-zones-modal-wrapper">
			<div class="orderable-delivery-zones-modal-background"></div>
			<!-- Add new -->
			<?php include Orderable_Helpers::get_template_path( 'zones/delivery-zones-modal-add-update.php', 'location' ); ?>

			<!-- Add from existing -->
			<?php include Orderable_Helpers::get_template_path( 'zones/delivery-zones-modal-add-existing.php', 'location' ); ?>
		</div>

		<?php
	}

	/**
	 * Delete lookup entry.
	 *
	 * @param int    $location_id  The location ID.
	 * @param string $service_type The service type. Either `delivery` or `pickup`.
	 * @return void
	 */
	public static function delete_lookup_entry( $location_id, $service_type ) {
		if ( empty( $location_id ) || 'delivery' !== $service_type ) {
			return;
		}

		Orderable_Location_Zones_CRUD_Handler::delete_lookup_entry( $location_id, false, false );
	}

	/**
	 * Save the delivery zone.
	 *
	 * @param array  $service_hour The service hour data.
	 * @param string $service_type The service type. Either `delivery` or `pickup`.
	 * @param int    $location_id  The location ID.
	 * @return void
	 */
	public static function save_delivery_zone( $service_hour, $service_type, $location_id ) {
		if ( empty( $service_hour['zones'] ) || 'delivery' !== $service_type || empty( $location_id ) ) {
			return;
		}

		$zones = $service_hour['zones'];

		foreach ( $zones as $zone ) {
			$zone_data = json_decode( $zone, true );

			if ( ! $zone_data ) {
				continue;
			}

			$time_slot_id = ( ! empty( $zone_data['time_slot_id'] ) ) ? absint( $zone_data['time_slot_id'] ) : false;

			if ( ! $time_slot_id ) {
				continue;
			}

			$zone_data['location_id'] = $location_id;

			if ( strlen( $zone_data['zone_id'] ) > 10 ) {
				// If the zone ID is a timestamp, we are creating a new zone.
				Orderable_Location_Zones_CRUD_Handler::add_new( $zone_data );
			} else {
				// Otherwise, we're updating and/or adding an existing zone.
				// @TODO - This could be optimised to determine if the zone
				// is already associated with the time slot before calling the
				// edit method. Not a huge priority right now.
				Orderable_Location_Zones_CRUD_Handler::edit( $zone_data );
				Orderable_Location_Zones_CRUD_Handler::create_lookup_entry( $zone_data['location_id'], $zone_data['zone_id'], $zone_data['time_slot_id'] );
			}
		}
	}
}
