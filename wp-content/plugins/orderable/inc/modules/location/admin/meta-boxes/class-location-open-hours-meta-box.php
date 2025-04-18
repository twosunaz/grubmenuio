<?php
/**
 * Module: Location.
 *
 * @since   1.18.0
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable_Location_Open_Hours_Meta_Box class.
 */
class Orderable_Location_Open_Hours_Meta_Box {
	/**
	 * Init.
	 */
	public static function run() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'orderable_location_save_data', array( __CLASS__, 'save_data' ) );

		add_filter( 'orderable_location_get_save_data', array( __CLASS__, 'get_save_data' ) );
	}

	/**
	 * Get the meta box title.
	 *
	 * @return string
	 */
	public static function get_title() {
		return __( 'Open Hours', 'orderable' );
	}

	/**
	 * Add the Meta Box.
	 *
	 * @return void
	 */
	public static function add() {
		add_meta_box(
			'orderable_multi_location_store_open_hours_meta_box',
			self::get_title(),
			array( __CLASS__, 'output' )
		);
	}

	/**
	 * Output the meta box.
	 *
	 * @return void
	 */
	public static function output() {
		global $post;

		$location = new Orderable_Location_Single();

		$should_show_override_open_hours_field = ! is_admin() || ( ! empty( $post ) && 'orderable_locations' === $post->post_type );

		$data = array(
			'orderable_override_open_hours' => $location->get_override_default_open_hours(),
			'store_general_open_hours'      => $location->get_open_hours(),
		);

		$override_open_hours = (bool) $data['orderable_override_open_hours'] || ( is_admin() && ! $should_show_override_open_hours_field );

		$class_toggle_field_value = $override_open_hours ? 'enabled' : 'disabled';

		$orderable_open_hours_settings_class = array(
			'orderable-open-hours-settings',
		);

		$override_open_hours_row_class = array(
			'orderable-fields-row__body-row',
			'orderable-store-open-hours__override-open-hours',
		);

		$open_hours_row_class = array(
			'orderable-fields-row__body-row',
			'orderable-store-open-hours__open-hours',
		);

		if ( $override_open_hours ) {
			$orderable_open_hours_settings_class[] = 'orderable-store-open-hours--hide';
		} else {
			$open_hours_row_class[] = 'orderable-store-open-hours--hide';
		}

		if ( ! $should_show_override_open_hours_field ) {
			$override_open_hours_row_class[] = 'orderable-store-open-hours--hide';
		}

		$override_open_hours_row_class       = join( ' ', $override_open_hours_row_class );
		$open_hours_row_class                = join( ' ', $open_hours_row_class );
		$orderable_open_hours_settings_class = join( ' ', $orderable_open_hours_settings_class );

		?>
		<div class="orderable-fields-row orderable-fields-row--meta">
			<div class="orderable-fields-row__body">
				<div class="<?php echo esc_attr( $override_open_hours_row_class ); ?>">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Override Default Open Hours', 'Open Hours', 'orderable-pro' ); ?></h3>
						<p>
							<?php
								echo wp_kses_post(
									/**
									 * Filter the override open hours description.
									 *
									 * @since 1.18.0
									 */
									apply_filters(
										'orderable_location_open_hours_override_description',
										sprintf(
											// translators: %s - Orderable settings URL.
											__( 'Override the default open hours. You can change the default open hours on the <a href="%s" target="_blank">settings page</a>.', 'orderable-pro' ),
											esc_url( admin_url( 'admin.php?page=orderable-settings' ) )
										)
									)
								);
							?>
						</p>
					</div>
					<div class="orderable-fields-row__body-row-right">
						<div class="orderable-store-open-hours__override-open-hours">
							<span
								class="orderable-toggle-field orderable-override-open-hours-toggle-field woocommerce-input-toggle woocommerce-input-toggle--<?php echo esc_attr( $class_toggle_field_value ); ?>"
							>
								<?php echo esc_html( 'Yes' ); ?>
							</span>

							<input
								type="hidden"
								name="orderable_override_open_hours"
								value="<?php echo esc_attr( $override_open_hours ? 'yes' : 'no' ); ?>"
								class="orderable-toggle-field__input"
							/>

							<div class="<?php echo esc_attr( $orderable_open_hours_settings_class ); ?>">
								<?php self::show_open_hours(); ?>
							</div>
						</div>
					</div>
				</div>

				<div class="<?php echo esc_attr( $open_hours_row_class ); ?>">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Open Hours', 'Open Hours', 'orderable-pro' ); ?></h3>
						<p>
							<?php
								echo esc_html( __( 'The days and hours your location is open. Leave "Max Orders" empty for no limit', 'orderable-pro' ) );
							?>
						</p>
					</div>
					<div class="orderable-fields-row__body-row-right orderable-store-open-hours__open-hour-fields">
						<?php echo ( self::get_open_hours_fields( $data['store_general_open_hours'] ) ); ?>
					</div>
				</div>

				<div class="orderable-fields-row__body-row orderable-store-open-hours__timezone">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Timezone', 'Open Hours', 'orderable-pro' ); ?></h3>
					</div>
					<div class="orderable-fields-row__body-row-right">
						<p>
							<?php
								echo wp_kses_post( self::get_timezone_description() );
							?>
						</p>
						<p class='orderable-field-error-message'></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the timezone description.
	 *
	 * @return string
	 */
	public static function get_timezone_description() {
		$timezone_string = get_option( 'timezone_string', '' );

		if ( empty( $timezone_string ) ) {
			$offset          = get_option( 'gmt_offset', 0 );
			$timezone_string = 'UTC' . ( $offset < 0 ? '-' : '+' ) . abs( $offset );
		}

		$timezone_description = sprintf(
			// translators: %1$s - site timezone; %2$s - WordPress settings page URL.
			__( 'Your site\'s current timezone is <code>%1$s</code>. You can edit the timezone on the <a href="%2$s" target="_blank">settings page</a>.', 'orderable-pro' ),
			esc_html( $timezone_string ),
			esc_url( admin_url( 'options-general.php#timezone_string' ) )
		);

		return $timezone_description;
	}

	/**
	 * Get the default data.
	 *
	 * @param array $data The default data will be appended to $data.
	 * @return array
	 */
	public static function get_default_data( $data = array() ) {
		global $post;

		if ( ! is_array( $data ) || ( ! empty( $post ) && 'auto-draft' === $post->post_status ) ) {
			return $data;
		}

		$default_data = array(
			'orderable_override_open_hours' => 'no',
			'store_general_open_hours'      => array(),
		);

		return array_merge( $data, $default_data );
	}

	/**
	 * Return the data to be saved.
	 *
	 * @param array $data The data sent via POST will be appended to $data.
	 * @return array
	 */
	public static function get_save_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$store_open_hours_data = array(
			'orderable_override_open_hours' => Orderable_Location_Admin::get_posted_value( 'orderable_override_open_hours' ),
			'store_general_open_hours'      => self::get_location_open_hours(),
		);

		return array_merge( $data, $store_open_hours_data );
	}

	/**
	 * Get location open hours settings sent via POST.
	 *
	 * @return array
	 */
	protected static function get_location_open_hours() {
		$open_hours = array();

		$nonce = empty( $_POST['_wpnonce_orderable_location'] ) ? false : sanitize_text_field( wp_unslash( $_POST['_wpnonce_orderable_location'] ) );

		if ( ! wp_verify_nonce( $nonce, 'orderable_location_save' ) ) {
			return $open_hours;
		}

		if ( empty( $_POST['orderable_settings']['store_general_open_hours'] ) ) {
			return $open_hours;
		}

		$data = map_deep(
			wp_unslash( $_POST['orderable_settings']['store_general_open_hours'] ),
			'sanitize_text_field'
		);

		foreach ( array_keys( Orderable_Timings::get_days_of_the_week() ) as $day_number ) {
			$day_fields = $data[ $day_number ];

			if ( empty( $day_fields ) ) {
				continue;
			}

			$open_hours[ $day_number ] = array(
				'enabled'    => ! empty( $day_fields['enabled'] ),
				'from'       => array(
					'hour'   => $day_fields['from']['hour'],
					'minute' => $day_fields['from']['minute'],
					'period' => $day_fields['from']['period'],
				),
				'to'         => array(
					'hour'   => $day_fields['to']['hour'],
					'minute' => $day_fields['to']['minute'],
					'period' => $day_fields['to']['period'],
				),
				'max_orders' => isset( $day_fields['max_orders'] ) ? $day_fields['max_orders'] : '',
			);
		}

		return $open_hours;
	}

	/**
	 * Show the main location open hours.
	 *
	 * @return void
	 */
	protected static function show_open_hours() {
		$open_hours_settings = Orderable_Location::get_default_open_hours();
		?>

		<?php foreach ( Orderable_Timings::get_days_of_the_week() as $day_number => $day_name ) : ?>
			<div class="orderable-open-hours-settings__day">
				<span class="orderable-open-hours-settings__day-name">
					<?php echo esc_html( $day_name ); ?>
				</span>

				<span class="orderable-open-hours-settings__hours">
					<?php echo esc_html( self::get_day_open_hours( $open_hours_settings[ $day_number ] ) ); ?>
				</span>
			</div>
		<?php endforeach; ?>

		<?php
	}

	/**
	 * Get the open hours range. E.g.: 5:00PM - 10:00PM.
	 *
	 * @param array $day_setting The day settings.
	 * @return string
	 */
	protected static function get_day_open_hours( $day_setting ) {
		if ( empty( $day_setting['enabled'] ) ) {
			return __( 'Closed', 'orderable-pro' );
		}

		$from = $day_setting['from']['hour'] . ':' . $day_setting['from']['minute'] . $day_setting['from']['period'];
		$to   = $day_setting['to']['hour'] . ':' . $day_setting['to']['minute'] . $day_setting['to']['period'];

		return $from . ' - ' . $to;
	}

	/**
	 * Get open hours fields.
	 *
	 * @param array $open_hours_settings The location open hours settings .
	 * @return void|string
	 */
	public static function get_open_hours_fields( $open_hours_settings = array() ) {

		$days                = Orderable_Timings::get_days_of_the_week();
		$open_hours_settings = empty( $open_hours_settings ) ? Orderable_Location::get_default_open_hours() : $open_hours_settings;

		ob_start();
		?>
		<table class="orderable-table orderable-table--open-hours" cellpadding="0" cellspacing="0">
			<thead>
			<tr>
				<th class="orderable-table__column orderable-table__column--checkbox">&nbsp;</th>
				<th class="orderable-table__column orderable-table__column--label">&nbsp;</th>
				<th class="orderable-table__column orderable-table__column--time"><?php esc_html_e( 'Open Hours (From)', 'orderable' ); ?></th>
				<th class="orderable-table__column orderable-table__column--time"><?php esc_html_e( 'Open Hours (To)', 'orderable' ); ?></th>
				<th class="orderable-table__column orderable-table__column--last"><?php esc_html_e( 'Max Orders (Day)', 'orderable' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $days as $day_number => $day_name ) {
				$day_settings = isset( $open_hours_settings[ $day_number ] ) ? $open_hours_settings[ $day_number ] : array();
				$from         = ! empty( $day_settings['from'] ) ? $day_settings['from'] : array();
				$to           = ! empty( $day_settings['to'] ) ? $day_settings['to'] : array();
				$enabled      = ! empty( $day_settings['enabled'] );
				?>
				<tr class="orderable-table__row <?php echo $enabled ? '' : 'orderable-table__row--hidden'; ?>">
					<td class="orderable-table__column orderable-table__column--checkbox orderable-table__column--always-visible">
						<input class="orderable-enable-day" type="checkbox" name="orderable_settings[<?php echo esc_attr( Orderable_Timings_Settings::$open_hours_key ); ?>][<?php echo esc_attr( $day_number ); ?>][enabled]" id="store_general_open_hours_<?php echo esc_attr( $day_number ); ?>_enabled" value="1" <?php checked( $enabled ); ?> data-orderable-day="<?php echo esc_attr( $day_number ); ?>">
					</td>
					<td class="orderable-table__column orderable-table__column--label orderable-table__column--always-visible">
						<label for="<?php echo esc_attr( Orderable_Timings_Settings::$open_hours_key ); ?>_<?php echo esc_attr( $day_number ); ?>_enabled"><?php echo esc_html( $day_name ); ?></label>
					</td>
					<td class="orderable-table__column orderable-table__column--time">
						<strong class="orderable-table__rwd-labels"><?php esc_html_e( 'Open Hours (From)', 'orderable' ); ?></strong>
						<?php echo Orderable_Helpers::kses( Orderable_Timings_Settings::get_time_field( 'orderable_settings[' . Orderable_Timings_Settings::$open_hours_key . '][' . $day_number . '][from]', $from ), 'form' ); ?>
					</td>
					<td class="orderable-table__column orderable-table__column--time">
						<strong class="orderable-table__rwd-labels"><?php esc_html_e( 'Open Hours (To)', 'orderable' ); ?></strong>
						<?php echo Orderable_Helpers::kses( Orderable_Timings_Settings::get_time_field( 'orderable_settings[' . Orderable_Timings_Settings::$open_hours_key . '][' . $day_number . '][to]', $to ), 'form' ); ?>
					</td>
					<td class="orderable-table__column orderable-table__column--last">
						<?php echo Orderable_Helpers::kses( Orderable_Timings_Settings::get_max_orders_field( 'orderable_settings[' . Orderable_Timings_Settings::$open_hours_key . '][' . $day_number . '][max_orders]', $day_settings ), 'form' ); ?>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php

		return ob_get_clean();
	}

	/**
	 * Save store settings.
	 *
	 * @return void
	 */
	public static function save_data() {
		global $post, $wpdb;

		if ( empty( $wpdb->orderable_locations ) ) {
			return;
		}

		$post_id     = ! empty( $post ) ? $post->ID : null;
		$location_id = Orderable_Location::get_location_id( $post_id );

		$override_open_hours = 'yes' === Orderable_Location_Admin::get_posted_value( 'orderable_override_open_hours' );
		$open_hours          = maybe_serialize( self::get_location_open_hours() );

		$store_services = Orderable_Location_Store_Services_Meta_Box::get_store_services();

		$pickup_hours_same_as_delivery    = Orderable_Location_Admin::get_posted_value( 'orderable_location_service_hours_pickup_same_as_delivery' );
		$asap                             = Orderable_Location_Order_Options_Meta_Box::get_asap_data();
		$lead_time                        = Orderable_Location_Admin::get_posted_value( 'orderable_location_lead_time' );
		$lead_time_period                 = defined( 'ORDERABLE_PRO_VERSION' ) ? Orderable_Location_Admin::get_posted_value( 'orderable_location_lead_time_period' ) : 'days';
		$preorder                         = Orderable_Location_Admin::get_posted_value( 'orderable_location_preorder_days' );
		$delivery_days_calculation_method = Orderable_Location_Admin::get_posted_value( 'orderable_location_delivery_days_calculation_method' );

		$delivery = is_array( $store_services ) && in_array( 'delivery', $store_services, true );
		$pickup   = is_array( $store_services ) && in_array( 'pickup', $store_services, true );

		$asap_date = is_array( $asap ) && in_array( 'day', $asap, true );
		$asap_time = is_array( $asap ) && in_array( 'slot', $asap, true );

		$enable_default_holidays = 'yes' === Orderable_Location_Admin::get_posted_value( 'orderable_location_enable_default_holidays' );

		$data = array(
			'post_id'                          => $post_id,
			'override_default_open_hours'      => (int) $override_open_hours,
			'open_hours'                       => $open_hours,
			'delivery'                         => (int) $delivery,
			'pickup'                           => (int) $pickup,
			'pickup_hours_same_as_delivery'    => (int) $pickup_hours_same_as_delivery,
			'asap_date'                        => (int) $asap_date,
			'asap_time'                        => (int) $asap_time,
			'lead_time'                        => $lead_time,
			'lead_time_period'                 => empty( $lead_time_period ) ? 'days' : $lead_time_period,
			'preorder'                         => $preorder,
			'delivery_days_calculation_method' => $delivery_days_calculation_method,
			'enable_default_holidays'          => $enable_default_holidays,
		);

		$store_address = array(
			'address_line_1' => Orderable_Location_Admin::get_posted_value( 'orderable_address_line_1' ),
			'address_line_2' => Orderable_Location_Admin::get_posted_value( 'orderable_address_line_2' ),
			'city'           => Orderable_Location_Admin::get_posted_value( 'orderable_city' ),
			'country_state'  => Orderable_Location_Admin::get_posted_value( 'orderable_country_state' ),
			'postcode_zip'   => Orderable_Location_Admin::get_posted_value( 'orderable_post_code_zip' ),
		);

		$data = array_merge( $data, $store_address );

		if ( empty( $location_id ) ) {
			// If this is the location settings page, then it's the main location.
			if ( isset( $_GET['page'] ) && 'orderable-location' === $_GET['page'] ) {
				$data['is_main_location'] = true;
			}

			$wpdb->insert(
				$wpdb->orderable_locations,
				$data
			);
		} elseif ( is_numeric( $location_id ) ) {
			$wpdb->update(
				$wpdb->orderable_locations,
				$data,
				array(
					'location_id' => $location_id,
				),
				null,
				array(
					'location_id' => '%d',
				)
			);
		}
	}
}
