<?php
/**
 * Module: Location.
 *
 * @since   1.18.0
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable_Location_Store_Services_Meta_Box class.
 */
class Orderable_Location_Store_Services_Meta_Box {
	/**
	 * Init.
	 */
	public static function run() {
		if ( ! is_admin() ) {
			return;
		}

		// Runs after Orderable_Location_Open_Hours_Meta_Box::save_store_settings(), otherwise no location ID exists.
		// This means service hours are saved when creating a new location.
		add_action( 'orderable_location_save_data', array( __CLASS__, 'save_data' ), 20 );

		add_filter( 'orderable_location_get_save_data', array( __CLASS__, 'get_save_data' ) );
	}

	/**
	 * Get the meta box title.
	 *
	 * @return string
	 */
	public static function get_title() {
		return __( 'Services', 'orderable' );
	}

	/**
	 * Add the Meta Box.
	 *
	 * @return void
	 */
	public static function add() {
		add_meta_box(
			'orderable_multi_location_store_services_meta_box',
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
		$location = new Orderable_Location_Single();

		$is_delivery_enabled = $location->is_service_enabled( 'delivery' );
		$is_pickup_enabled   = $location->is_service_enabled( 'pickup' );

		$store_services_toggle_class = array(
			'orderable-toggle-field',
			'woocommerce-input-toggle',
		);

		$store_services_delivery_toggle_class = array_merge(
			$store_services_toggle_class,
			array( 'orderable-delivery-toggle-field' )
		);

		$store_services_pickup_toggle_class = array_merge(
			$store_services_toggle_class,
			array( 'orderable-pickup-toggle-field' )
		);

		if ( $is_delivery_enabled ) {
			$store_services_delivery_toggle_class[] = 'woocommerce-input-toggle--enabled';
		} else {
			$store_services_delivery_toggle_class[] = 'woocommerce-input-toggle--disabled';
		}

		if ( $is_pickup_enabled ) {
			$store_services_pickup_toggle_class[] = 'woocommerce-input-toggle--enabled';
		} else {
			$store_services_pickup_toggle_class[] = 'woocommerce-input-toggle--disabled';
		}

		$store_services_delivery_toggle_class = join( ' ', $store_services_delivery_toggle_class );
		$store_services_pickup_toggle_class   = join( ' ', $store_services_pickup_toggle_class );

		?>
		<div class="orderable-fields-row orderable-fields-row--meta">
			<div class="orderable-fields-row__body">
				<div class="orderable-fields-row__body-row orderable-store-services">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Enable Services', 'Location Services', 'orderable-pro' ); ?></h3>
						<p>
							<?php
								echo esc_html__( 'Which services does this location offer?', 'orderable-pro' );
							?>
						</p>
					</div>
					<div class="orderable-fields-row__body-row-right orderable-store-services__enable-services">
						<div class="orderable-store-services__enable-service orderable-store-services__enable-service-delivery">
							<span
								class="<?php echo esc_attr( $store_services_delivery_toggle_class ); ?>"
							>
								<?php echo esc_html( 'Delivery' ); ?>
							</span>
							<span class="orderable-store-services__enable-service-label">
								<?php echo esc_html( 'Delivery' ); ?>
							</span>

							<input
								type="hidden"
								name="orderable_location_store_services_delivery"
								value="<?php echo esc_attr( $is_delivery_enabled ? 'yes' : 'no' ); ?>"
								class="orderable-toggle-field__input"
							/>
						</div>
						<div class="orderable-store-services__enable-service orderable-store-services__enable-service-pickup">
							<span
								class="<?php echo esc_attr( $store_services_pickup_toggle_class ); ?>"
							>
								<?php echo esc_html__( 'Pickup', 'orderable-pro' ); ?>
							</span>
							<span class="orderable-store-services__enable-service-label">
								<?php echo esc_html__( 'Pickup', 'orderable-pro' ); ?>
							</span>

							<input
								type="hidden"
								name="orderable_location_store_services_pickup"
								value="<?php echo esc_attr( $is_pickup_enabled ? 'yes' : 'no' ); ?>"
								class="orderable-toggle-field__input"
							/>
						</div>
					</div>
				</div>

				<div class="orderable-fields-row__body-row orderable-store-services__service-hours">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Service Hours', 'Location Services', 'orderable-pro' ); ?></h3>
						<p>
							<?php echo esc_html( __( 'Set Service Hours and Delivery Zones for your Delivery service.', 'orderable-pro' ) ); ?>
						</p>
						<br />
						<p>
							<?php
								echo esc_html(
									__(
										'The days and hours where you offer delivery/pickup services.',
										'orderable-pro'
									)
								);
							?>
						</p>
					</div>
					<div class="orderable-fields-row__body-row-right orderable-fields-row__body-row-right--slug orderable-store-services__service-hours-fields">
						<?php self::service_hours_fields(); ?>
					</div>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Get the default data.
	 *
	 * @param array $data The default data will be appended to $data.
	 * @return array
	 */
	public static function get_default_data( $data = array() ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$default_service_hours = array(
			array(
				'time_slot_id' => '',
				'days'         => array(),
				'period'       => 'all-day',
				'frequency'    => '',
				'cutoff'       => '',
				'max_orders'   => '',
				'time_from'    => '',
				'time_to'      => '',
				'from'         => array(
					'hour'   => '9',
					'minute' => '00',
					'period' => 'AM',
				),
				'to'           => array(
					'hour'   => '5',
					'minute' => '00',
					'period' => 'PM',
				),
			),
		);

		$default_data = array(
			'store_general_services'                  => array(),
			'store_general_service_hours_delivery'    => $default_service_hours,
			'store_general_service_hours_pickup_same' => '1',
			'store_general_service_hours_pickup'      => $default_service_hours,
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

		$store_services_data = array(
			'store_general_services'                  => self::get_store_services(),
			'store_general_service_hours_delivery'    => self::get_location_service_hours( 'delivery' ),
			'store_general_service_hours_pickup_same' => Orderable_Location_Admin::get_posted_value( 'orderable_location_service_hours_pickup_same_as_delivery' ),
			'store_general_service_hours_pickup'      => self::get_location_service_hours( 'pickup' ),
		);

		return array_merge( $data, $store_services_data );
	}

	/**
	 * Output service hours fields.
	 *
	 * @return void
	 */
	protected static function service_hours_fields() {
		$location = new Orderable_Location_Single();

		$services                = $location->get_services();
		$pickup_same_as_delivery = $location->get_pickup_hours_same_as_delivery();

		$is_delivery_enabled = $location->is_service_enabled( 'delivery' );
		$is_pickup_enabled   = $location->is_service_enabled( 'pickup' );
		$active_service      = false;

		if ( $is_delivery_enabled ) {
			$active_service = 'delivery';
		} elseif ( $is_pickup_enabled ) {
			$active_service = 'pickup';
		}

		$button_delivery_class = join(
			' ',
			array_keys(
				array_filter(
					array(
						'orderable-trigger-element--active' => 'delivery' === $active_service,
						'orderable-ui-hide' => ! $is_delivery_enabled,
					)
				)
			)
		);

		$button_pickup_class = join(
			' ',
			array_keys(
				array_filter(
					array(
						'orderable-trigger-element--active' => 'pickup' === $active_service,
						'orderable-ui-hide' => ! $is_pickup_enabled,
					)
				)
			)
		);

		$wrapper_pickup_class = join(
			' ',
			array_keys(
				array_filter(
					array(
						'orderable-element--disabled'      => (bool) $pickup_same_as_delivery,
						'orderable-toggle-wrapper--active' => 'pickup' === $active_service,
					)
				)
			)
		);

		?>
		<p
			class="orderable-notice orderable-notice--select-service <?php echo empty( $services ) ? '' : 'orderable-ui-hide'; ?>"
		>
			<?php esc_html_e( 'Please select services available for this location.', 'orderable' ); ?>
		</p>

		<div class="orderable-toolbar">
			<button
				class="orderable-admin-button orderable-admin-button--delivery <?php echo esc_attr( $button_delivery_class ); ?>"
				data-orderable-trigger="toggle-wrapper"
				data-orderable-wrapper="delivery"
				data-orderable-wrapper-group="service"
			>
				<?php esc_html_e( 'Delivery', 'orderable' ); ?>
			</button>

			<button
				class="orderable-admin-button orderable-admin-button--pickup <?php echo esc_attr( $button_pickup_class ); ?>"
				data-orderable-trigger="toggle-wrapper"
				data-orderable-wrapper="pickup"
				data-orderable-wrapper-group="service"
			>
				<?php esc_html_e( 'Pickup', 'orderable' ); ?>
			</button>

			<div class="orderable-toolbar__actions">
				<span
					class="orderable-toggle-wrapper orderable-toggle-wrapper--delivery <?php echo 'delivery' === $active_service ? 'orderable-toggle-wrapper--active' : ''; ?>"
					data-orderable-wrapper-group="service"
				>
					<button
						class="orderable-admin-button orderable-admin-button--primary"
						data-orderable-trigger="new-row"
						data-orderable-target=".orderable-table--service-hours-delivery"
					>
						<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add service hours', 'orderable' ); ?>
					</button>
				</span>

				<span
					class="orderable-element--pickup orderable-toggle-wrapper orderable-toggle-wrapper--pickup <?php echo esc_attr( $wrapper_pickup_class ); ?>"
					data-orderable-wrapper-group="service"
				>
					<button
						class="orderable-admin-button orderable-admin-button--primary"
						data-orderable-trigger="new-row"
						data-orderable-target=".orderable-table--service-hours-pickup"
					>
						<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add service hours', 'orderable' ); ?>
					</button>
				</span>
			</div>
		</div>

		<div
			class="orderable-toggle-wrapper orderable-toggle-wrapper--delivery <?php echo ( 'delivery' === $active_service ) ? 'orderable-toggle-wrapper--active' : ''; ?>"
			data-orderable-wrapper-group="service"
		>
			<?php
			self::service_hour_table( 'delivery' );
			/**
			 * Render content inside the delivery wrapper.
			 *
			 * @since 1.8.0
			 */
			do_action( 'orderable_store_services_delivery_wrapper' );
			?>
		</div>

		<div
			class="orderable-toggle-wrapper orderable-toggle-wrapper--pickup <?php echo ( 'pickup' === $active_service ) ? 'orderable-toggle-wrapper--active' : ''; ?>"
			data-orderable-wrapper-group="service"
		>
			<label
			for="orderable_location_service_hours_pickup_same_as_delivery"
			id="orderable_location_service_hours_pickup_same_as_delivery_label"
			class="orderable-store-services__pickup-same-as-delivery <?php echo esc_attr( ( ! $is_delivery_enabled ) ? 'orderable-ui-hide' : '' ); ?>">
			<input
				type="checkbox"
				id="orderable_location_service_hours_pickup_same_as_delivery"
				name="orderable_location_service_hours_pickup_same_as_delivery"
				value="1"
				<?php checked( $pickup_same_as_delivery ); ?>
				data-orderable-trigger="toggle-element"
				data-orderable-target=".orderable-element--pickup"
				data-orderable-toggle-class="orderable-element--disabled"
			>
			<?php esc_html_e( 'Same as delivery service hours', 'orderable' ); ?>
			</label>

			<?php
			self::service_hour_table( 'pickup', (bool) $pickup_same_as_delivery );
			/**
			 * Render content inside the pickup wrapper.
			 *
			 * @since 1.8.0
			 */
			do_action( 'orderable_store_services_pickup_wrapper' );
			?>
		</div>
		<?php
	}

	/**
	 * Output service hour table.
	 *
	 * @param string  $type     The type of service: `delivery` or `pickup`.
	 * @param boolean $disabled If table sould be disabled. Default: false.
	 * @return void
	 */
	protected static function service_hour_table( $type, $disabled = false ) {
		$location         = new Orderable_Location_Single();
		$days_of_the_week = Orderable_Timings::get_days_of_the_week( 'short' );

		$target_data = wp_json_encode(
			array(
				'all-day'    => array(
					'hide' => '[data-orderable-period="time-slots"]',
				),
				'time-slots' => array(
					'show' => '[data-orderable-period="time-slots"]',
				),
			)
		);

		$open_hours                  = $location->get_open_hours();
		$service_hours_type_settings = $location->get_service_hours( $type, true );

		if ( empty( $service_hours_type_settings ) ) {
			$service_hours_type_settings = self::get_default_data()[ 'store_general_service_hours_' . $type ];
		}

		?>
		<table
			class="orderable-table orderable-table--service-hours-<?php echo esc_attr( $type ); ?> orderable-element--<?php echo esc_attr( $type ); ?> <?php echo $disabled ? 'orderable-element--disabled' : ''; ?>"
			cellpadding="0"
			cellspacing="0"
		>
			<tbody class="orderable-table__body">
			<?php
			foreach ( $service_hours_type_settings as $index => $settings ) :
				$time_slot_id   = ! empty( $settings['time_slot_id'] ) ? absint( $settings['time_slot_id'] ) : '';
				$delivery_zones = $time_slot_id ? Orderable_Location_Zones::get_zones( $time_slot_id ) : array();
				?>
				<tr class="orderable-table__row orderable-table__row--repeatable" data-orderable-index="<?php echo esc_attr( $index ); ?>" data-orderable-time-slot="<?php echo esc_attr( $time_slot_id ); ?>">
					<td class="orderable-table__cell orderable-table__cell--no-padding">
						<input
							type="hidden"
							name="service_hours[<?php echo esc_attr( $type ); ?>][<?php echo esc_attr( $index ); ?>][time_slot_id]"
							value="<?php echo esc_attr( $time_slot_id ); ?>" 
						/>
						<table class="orderable-table orderable-table--child orderable-table--compact" cellpadding="0" cellspacing="0">
							<tbody>
							<tr>
								<th class="orderable-table__column orderable-table__column--medium"><?php esc_html_e( 'Days', 'orderable' ); ?></th>
								<td>
									<select
										class="orderable-select orderable-select--multi-select orderable-select--days"
										name="service_hours[<?php echo esc_attr( $type ); ?>][<?php echo esc_attr( $index ); ?>][days][]"
										multiple
										data-orderable-select-none-option="<?php esc_attr_e( 'Select "Open Hours" first', 'orderable' ); ?>"
									>
									<?php
									foreach ( $days_of_the_week as $day_number => $day_label ) :
										?>
										<?php
											$is_day_enabled = ! empty( $open_hours[ $day_number ]['enabled'] );
										?>
											<option
												value="<?php echo esc_attr( $day_number ); ?>"
												<?php selected( ! $disabled && in_array( (string) $day_number, $settings['days'], true ) && $is_day_enabled ); ?>
												<?php disabled( ! $is_day_enabled ); ?>
											>
												<?php echo esc_attr( $day_label ); ?>
											</option>
									<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<tr
								class="<?php echo 'all-day' === $settings['period'] ? 'orderable-table__row--last' : ''; ?>"
							>
								<th class="orderable-table__column orderable-table__column--medium">
									<?php esc_html_e( 'Period', 'orderable' ); ?>
								</th>
								<td>
									<select
										class="orderable-select"
										name="service_hours[<?php echo esc_attr( $type ); ?>][<?php echo esc_attr( $index ); ?>][period]"
										data-orderable-trigger="toggle-element-select"
										data-orderable-parent=".orderable-table__row"
										data-orderable-target="<?php echo esc_attr( $target_data ); ?>"
									>
										<option
											value="all-day"
											<?php selected( $settings['period'], 'all-day' ); ?>
										>
											<?php esc_html_e( 'All Day', 'orderable' ); ?>
										</option>
										<option
											value="time-slots"
											<?php selected( $settings['period'], 'time-slots' ); ?>
										>
											<?php esc_html_e( 'Time Slots', 'orderable' ); ?>
										</option>
									</select>
								</td>
							</tr>
							<?php
								echo Orderable_Helpers::kses( Orderable_Timings_Settings::get_time_slot_fields( 'service_hours[' . $type . '][' . $index . ']', $settings ), 'form' );
								/**
								 * Render content at the end of the time slots table.
								 *
								 * @since 1.8.0
								 */
								do_action(
									'orderable_before_time_slots_table_end',
									$type,
									absint( $time_slot_id ),
									absint( $index ),
									array(
										'delivery_zones' => $delivery_zones,
									)
								);
							?>
						</table>
					</td>
					<td class="orderable-table__column orderable-table__column--remove">
						<a href="javascript: void(0);" class="orderable-table__remove-row" data-orderable-trigger="remove-row"><span class="dashicons dashicons-trash"></span></a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get location open hours settings sent via POST.
	 *
	 * @param string $service The service name. E.g.: delivery or pickup.
	 * @return array
	 */
	protected static function get_location_service_hours( $service ) {
		$service_hours = array();

		$nonce = empty( $_POST['_wpnonce_orderable_location'] ) ? false : sanitize_text_field( wp_unslash( $_POST['_wpnonce_orderable_location'] ) );

		if ( ! wp_verify_nonce( $nonce, 'orderable_location_save' ) ) {
			return $service_hours;
		}

		if ( empty( $_POST['service_hours'][ $service ] ) ) {
			return $service_hours;
		}

		$default_settings = array(
			'time_slot_id' => '',
			'days'         => array(),
			'period'       => 'all-day',
			'from'         => '',
			'to'           => '',
			'frequency'    => '',
			'cutoff'       => '',
			'max_orders'   => '',
			'has_zones'    => 0,
		);

		$data = map_deep(
			wp_unslash( $_POST['service_hours'][ $service ] ),
			'sanitize_text_field'
		);

		if ( ! empty( $data ) && is_array( $data ) ) {
			$service_hours = array_map(
				function( $item ) use ( $default_settings ) {
					return wp_parse_args( $item, $default_settings );
				},
				$data
			);
		}

		return $service_hours;
	}

	/**
	 * Get location services settings sent via POST.
	 *
	 * @return array
	 */
	public static function get_store_services() {
		$store_general_services = array();

		if ( 'yes' === Orderable_Location_Admin::get_posted_value( 'orderable_location_store_services_delivery' ) ) {
			$store_general_services[] = 'delivery';
		}

		if ( 'yes' === Orderable_Location_Admin::get_posted_value( 'orderable_location_store_services_pickup' ) ) {
			$store_general_services[] = 'pickup';
		}

		return $store_general_services;
	}

	/**
	 * Save service hours.
	 *
	 * @return void
	 */
	public static function save_data() {
		global $post, $wpdb;

		if ( empty( $wpdb->orderable_location_time_slots ) ) {
			return;
		}

		$post_id = ! empty( $post ) ? $post->ID : false;

		self::save_service_hour( 'delivery', $post_id );
		self::save_service_hour( 'pickup', $post_id );
	}

	/**
	 * Save service hours settings in the Orderable custom table.
	 *
	 * @param string   $service_type The service type: `delivery` or `pickup`.
	 * @param int|null $post_id      The location ID. If null, the data is saved for the main store.
	 * @return void
	 */
	protected static function save_service_hour( $service_type, $post_id ) {
		global $wpdb;

		if ( empty( $service_type ) ) {
			return;
		}

		$location = new Orderable_Location_Single();

		/**
		 * Fires before saving service hours.
		 *
		 * @since 1.8.0
		 * @hook orderable_before_save_service_hours
		 * @param int      $location_id  The location ID.
		 * @param string   $service_type The service type: `delivery` or `pickup`.
		 */
		do_action( 'orderable_before_save_service_hours', $location->get_location_id(), $service_type );

		$service_hours         = self::get_location_service_hours( $service_type );
		$updated_service_hours = array();

		// Remove all service hours.
		if ( empty( $service_hours ) ) {
			$where_format = array(
				'service_type' => '%s',
			);

			if ( ! empty( $post ) ) {
				$where_format[] = '%d';
			}

			$wpdb->delete(
				$wpdb->orderable_location_time_slots,
				array(
					'service_type' => $service_type,
					'location_id'  => $location->get_location_id(),
				),
				$where_format
			);

			return;
		}

		// try to update the service_hours that have an ID.
		foreach ( $service_hours as $key => $service_hour ) {
			if ( empty( $service_hour['time_slot_id'] ) ) {
				continue;
			}

			$updated = $wpdb->update(
				$wpdb->orderable_location_time_slots,
				array(
					'days'       => maybe_serialize( $service_hour['days'] ),
					'period'     => $service_hour['period'],
					'time_from'  => maybe_serialize( $service_hour['from'] ),
					'time_to'    => maybe_serialize( $service_hour['to'] ),
					'frequency'  => $service_hour['frequency'],
					'cutoff'     => $service_hour['cutoff'],
					'max_orders' => $service_hour['max_orders'],
					'has_zones'  => ! empty( $service_hour['zones'] ),
				),
				array(
					'service_type' => $service_type,
					'time_slot_id' => $service_hour['time_slot_id'],
					'location_id'  => $location->get_location_id(),
				)
			);

			/**
			 * Fires after updating a service hour.
			 *
			 * @since 1.8.0
			 * @hook orderable_location_service_hour_updated
			 * @param array    $service_hour The service hour data.
			 * @param string   $service_type The service type: `delivery` or `pickup`.
			 * @param int      $location_id  The location ID.
			 */
			do_action( 'orderable_location_service_hour_updated', $service_hour, $service_type, $location->get_location_id() );

			// If successful (i.e. not false. 0 === nothing updated), remove the service hour from the array.
			// The remaining service hours will be inserted.
			if ( false !== $updated ) {
				unset( $service_hours[ $key ] );
				$updated_service_hours[] = $service_hour['time_slot_id'];
			}
		}

		$conditions = array(
			'service_type' => 'service_type = %s',
			'location_id'  => 'location_id = %d',
		);

		$prepare_values = array(
			$service_type,
			$location->get_location_id(),
		);

		foreach ( $updated_service_hours as $updated_service_hour ) {
			$updated_service_hours_placeholders[] = '%d';
			$prepare_values[]                     = $updated_service_hour;
		}

		if ( ! empty( $updated_service_hours_placeholders ) ) {
			$conditions['time_slot_id'] = 'time_slot_id NOT IN ( ' . join( ', ', $updated_service_hours_placeholders ) . ' )';
		}

		$conditions = array_filter( $conditions );

		if ( empty( $conditions ) ) {
			return;
		}

		$conditions = implode( ' AND ', $conditions );

		$delete_sql = "
			DELETE
				FROM
					`$wpdb->orderable_location_time_slots`
				WHERE
					$conditions
		";

		/**
		 * Delete remaining service hours.
		 *
		 * After updating the service hours, we delete remaining service hours.
		 * That is, services hours that couldn't be updated (don't have an ID or
		 * were not found). That way, we will not leave service hours that were
		 * not sent in the request.
		 */
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$delete_sql,
				$prepare_values
			)
		);

		// there is no new service hours.
		if ( empty( $service_hours ) ) {
			return;
		}

		$default_settings = array(
			'days'       => array(),
			'period'     => 'all-day',
			'from'       => '',
			'to'         => '',
			'frequency'  => '',
			'cutoff'     => '',
			'max_orders' => '',
			'has_zones'  => 0,
		);

		$service_hours = array_map(
			function( $item ) use ( $default_settings ) {
				return wp_parse_args( $item, $default_settings );
			},
			$service_hours
		);

		foreach ( $service_hours as $service_hour ) {
			// If all of these are empty, then let's nopt save the service hour.
			if ( empty( $service_hour['days'] ) && empty( $service_hour['time_slot_id'] ) && empty( $service_hour['zones'] ) ) {
				continue;
			}

			$wpdb->insert(
				$wpdb->orderable_location_time_slots,
				array(
					'location_id'  => $location->get_location_id(),
					'service_type' => $service_type,
					'days'         => maybe_serialize( $service_hour['days'] ),
					'period'       => $service_hour['period'],
					'time_from'    => maybe_serialize( $service_hour['from'] ),
					'time_to'      => maybe_serialize( $service_hour['to'] ),
					'frequency'    => $service_hour['frequency'],
					'cutoff'       => $service_hour['cutoff'],
					'max_orders'   => $service_hour['max_orders'],
					'has_zones'    => ! empty( $service_hour['zones'] ),
				)
			);

			$time_slot_id = $wpdb->insert_id;

			if ( ! empty( $service_hour['zones'] ) ) {
				$service_hour['zones'] = array_map(
					function ( $zone ) use ( $time_slot_id ) {
						$zone = json_decode( $zone, true );

						if ( ! $zone ) {
							return $zone;
						}

						$zone['time_slot_id'] = $time_slot_id;

						return wp_json_encode( $zone );
					},
					$service_hour['zones']
				);
			}

			/**
			 * Fires after inserting a service hour.
			 *
			 * @since 1.8.0
			 * @hook orderable_location_service_hour_inserted
			 * @param array    $service_hour The service hour data.
			 * @param string   $service_type The service type: `delivery` or `pickup`.
			 * @param int      $location_id  The location ID.
			 */
			do_action( 'orderable_location_service_hour_inserted', $service_hour, $service_type, $location->get_location_id() );
		}
	}
}
