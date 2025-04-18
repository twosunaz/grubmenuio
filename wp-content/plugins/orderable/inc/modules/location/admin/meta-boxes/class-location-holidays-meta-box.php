<?php
/**
 * Module: Location.
 *
 * @since   1.18.0
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable_Location_Holidays_Meta_Box class.
 */
class Orderable_Location_Holidays_Meta_Box {
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
		return __( 'Holidays', 'orderable' );
	}

	/**
	 * Add the Meta Box.
	 *
	 * @return void
	 */
	public static function add() {
		add_meta_box(
			'orderable_multi_location_holidays_meta_box',
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

		$should_show_enable_default_holidays_field = ! empty( $post ) && 'orderable_locations' === $post->post_type || ! is_admin();
		$enable_default_holidays                   = $location->get_enable_default_holidays();
		$class_toggle_field_value                  = $enable_default_holidays ? 'enabled' : 'disabled';

		?>
		<div class="orderable-fields-row orderable-fields-row--meta">
			<div class="orderable-fields-row__body">
				<?php if ( $should_show_enable_default_holidays_field ) : ?>
					<div class="orderable-fields-row__body-row">
						<div class="orderable-fields-row__body-row-left">
							<h3><?php echo esc_html__( 'Enable default holidays', 'orderable-pro' ); ?></h3>
							<p>
								<?php
									echo wp_kses_post(
										/**
										 * Admin: Filter the message for 'Enable default holidays' setting.
										 *
										 * @since 1.15.0
										 */
										apply_filters(
											'orderable_location_holiday_setting_description',
											sprintf(
												// translators: %s - Orderable settings URL.
												__( 'You can change the default holidays on the <a href="%s" target="_blank">settings page</a>.', 'orderable-pro' ),
												esc_url( admin_url( 'admin.php?page=orderable-settings' ) )
											)
										),
									);
								?>
							</p>
						</div>
						<div class="orderable-fields-row__body-row-right">
							<div class="orderable-store-open-hours__enable-default_holidays">
								<span
									class="orderable-toggle-field orderable-enable-default_holidays-toggle-field woocommerce-input-toggle woocommerce-input-toggle--<?php echo esc_attr( $class_toggle_field_value ); ?>"
								>
									<?php echo esc_html( 'Yes' ); ?>
								</span>

								<input
									type="hidden"
									name="orderable_location_enable_default_holidays"
									value="<?php echo esc_attr( $enable_default_holidays ? 'yes' : 'no' ); ?>"
									class="orderable-toggle-field__input"
								/>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<div class="orderable-fields-row__body-row">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Holidays', 'Order Options', 'orderable-pro' ); ?></h3>
						<p>
							<?php echo esc_html__( 'Days when your location is closed.', 'orderable-pro' ); ?>
						</p>
					</div>
					<div class="orderable-fields-row__body-row-right orderable-holidays__holidays">
						<?php self::holiday_fields(); ?>
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

		$default_data = array(
			'holidays' => array(
				array(
					'holiday_id' => '',
					'from'       => '',
					'to'         => '',
					'services'   => array(),
					'repeat'     => '',
				),
			),
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

		$holidays_data = array(
			'holidays' => self::get_location_holidays(),
		);

		return array_merge( $data, $holidays_data );
	}

	/**
	 * Save the location holidays.
	 *
	 * @return void
	 */
	public static function save_data() {
		global $post, $wpdb;

		if ( empty( $wpdb->orderable_location_holidays ) ) {
			return;
		}

		$post_id     = ! empty( $post ) ? $post->ID : null;
		$location_id = Orderable_Location::get_location_id( $post_id );

		if ( empty( $location_id ) ) {
			return;
		}

		$holidays         = self::get_location_holidays();
		$updated_holidays = array();

		// Remove all holidays.
		if ( empty( $holidays ) ) {
			$wpdb->delete(
				$wpdb->orderable_location_holidays,
				array(
					'location_id' => $location_id,
				),
				array(
					'%d',
				)
			);

			return;
		}

		// try to update the holidays that have an ID.
		foreach ( $holidays as $key => $holiday ) {
			if ( empty( $holiday['holiday_id'] ) ) {
				continue;
			}

			$result = $wpdb->update(
				$wpdb->orderable_location_holidays,
				array(
					'date_from'     => $holiday['from'] ? $holiday['from'] : null,
					'date_to'       => $holiday['to'] ? $holiday['to'] : null,
					'services'      => maybe_serialize( $holiday['services'] ),
					'repeat_yearly' => $holiday['repeat'],
				),
				array(
					'holiday_id'  => $holiday['holiday_id'],
					'location_id' => $location_id,
				)
			);

			if ( false !== $result ) {
				unset( $holidays[ $key ] );
				$updated_holidays[] = $holiday['holiday_id'];
			}
		}

		$conditions     = array(
			'location_id' => 'location_id = %d',
		);
		$prepare_values = array( $location_id );

		foreach ( $updated_holidays as $updated_holiday ) {
			$updated_holidays_placeholders[] = '%d';
			$prepare_values[]                = $updated_holiday;
		}

		if ( ! empty( $updated_holidays_placeholders ) ) {
			$conditions['holiday_id'] = 'holiday_id NOT IN ( ' . join( ', ', $updated_holidays_placeholders ) . ' )';
		}

		$conditions = array_filter( $conditions );

		if ( empty( $conditions ) ) {
			return;
		}

		$conditions = implode( ' AND ', $conditions );

		$delete_sql = "
			DELETE
				FROM
					`$wpdb->orderable_location_holidays`
				WHERE
					$conditions
		";

		/**
		 * Delete remaining holidays.
		 *
		 * After updating the holidays, we delete remaining holidays.
		 * That is, holidays that couldn't be updated (don't have an ID or
		 * were not found). That way, we will not leave holidays that were
		 * not sent in the request.
		 */
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$delete_sql,
				$prepare_values
			)
		);

		// there is no new holidays.
		if ( empty( $holidays ) ) {
			return;
		}

		foreach ( $holidays as $holiday ) {
			$wpdb->insert(
				$wpdb->orderable_location_holidays,
				array(
					'location_id'   => $location_id,
					'date_from'     => $holiday['from'] ? $holiday['from'] : null,
					'date_to'       => $holiday['to'] ? $holiday['to'] : null,
					'services'      => maybe_serialize( $holiday['services'] ),
					'repeat_yearly' => $holiday['repeat'],
				)
			);
		}
	}

	/**
	 * Output holiday fields.
	 *
	 * @param array $holidays The location holiday settings .
	 * @return void
	 */
	public static function holiday_fields( $holidays = array(), $field_name_prefix = 'orderable_location_holidays' ) {
		$location = new Orderable_Location_Single();

		$holidays = 'orderable_settings[holidays]' === $field_name_prefix ? $holidays : $location->get_holidays( null, false );

		if ( empty( $holidays ) ) {
			$holidays = self::get_default_data()['holidays'];
		}

		?>
		<div class="orderable-toolbar">
			<div class="orderable-toolbar__actions">
				<button class="orderable-admin-button orderable-admin-button--primary" data-orderable-trigger="new-row" data-orderable-target=".orderable-table--holidays">
					<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add holiday', 'orderable' ); ?></button>
			</div>
		</div>

		<table class="orderable-table orderable-table--holidays" cellpadding="0" cellspacing="0">
			<tbody class="orderable-table__body">
			<?php foreach ( $holidays as $index => $holiday ) : ?>
				<tr class="orderable-table__row orderable-table__row--repeatable" data-orderable-index="<?php echo esc_attr( $index ); ?>">
					<td class="orderable-table__cell orderable-table__cell--no-padding">
						<input
							type="hidden"
							name="<?php echo esc_attr( $field_name_prefix ); ?>[<?php echo esc_attr( $index ); ?>][holiday_id]"
							value="<?php echo empty( $holiday['holiday_id'] ) ? '' : esc_attr( $holiday['holiday_id'] ); ?>" 
						/>
						<table class="orderable-table orderable-table--child orderable-table--compact" cellpadding="0" cellspacing="0">
							<tbody>
							<tr>
								<th class="orderable-table__column orderable-table__column--medium">
									<?php esc_html_e( 'From', 'orderable' ); ?>
								</th>
								<td>
									<input
										type="text"
										class="datepicker"
										name="<?php echo esc_attr( $field_name_prefix ); ?>[<?php echo esc_attr( $index ); ?>][from]"
										value="<?php echo esc_attr( $holiday['from'] ); ?>"
										data-datepicker="{&quot;dateFormat&quot;:&quot;yy-mm-dd&quot;}"
										readonly="readonly"
									>
								</td>
							</tr>
							<tr>
								<th class="orderable-table__column orderable-table__column--medium">
									<?php esc_html_e( 'To', 'orderable' ); ?>
								</th>
								<td>
									<input
										type="text"
										class="datepicker"
										name="<?php echo esc_attr( $field_name_prefix ); ?>[<?php echo esc_attr( $index ); ?>][to]"
										value="<?php echo esc_attr( $holiday['to'] ); ?>"
										data-datepicker="{&quot;dateFormat&quot;:&quot;yy-mm-dd&quot;}"
										readonly="readonly"
									>
								</td>
							</tr>
							<tr>
								<th class="orderable-table__column orderable-table__column--medium">
									<?php esc_html_e( 'Services', 'orderable' ); ?>
								</th>
								<td>
									<ul class="wpsf-list wpsf-list--checkboxes">
										<li>
											<label>
												<input
													type="checkbox"
													name="<?php echo esc_attr( $field_name_prefix ); ?>[<?php echo esc_attr( $index ); ?>][services][]"
													value="delivery"
													<?php checked( ! empty( $holiday['services'] ) && in_array( 'delivery', $holiday['services'], true ) ); ?>
												>
												<?php esc_html_e( 'Delivery', 'orderable' ); ?>
											</label>
										</li>
										<li>
											<label>
												<input
													type="checkbox"
													name="<?php echo esc_attr( $field_name_prefix ); ?>[<?php echo esc_attr( $index ); ?>][services][]"
													value="pickup"
													<?php checked( ! empty( $holiday['services'] ) && in_array( 'pickup', $holiday['services'], true ) ); ?>
												>
												<?php esc_html_e( 'Pickup', 'orderable' ); ?>
											</label>
										</li>
									</ul>
								</td>
							</tr>
							<tr>
								<th class="orderable-table__column orderable-table__column--medium">
									<?php esc_html_e( 'Repeat Yearly?', 'orderable' ); ?>
								</th>
								<td>
									<input
										type="checkbox"
										name="<?php echo esc_attr( $field_name_prefix ); ?>[<?php echo esc_attr( $index ); ?>][repeat]"
										value="1"
										<?php checked( ! empty( $holiday['repeat'] ) ); ?>
									>
								</td>
							</tr>
							</tbody>
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
	 * Get location holiday settings sent via POST.
	 *
	 * @return array
	 */
	protected static function get_location_holidays() {
		$holidays = array();

		$nonce = empty( $_POST['_wpnonce_orderable_location'] ) ? false : sanitize_text_field( wp_unslash( $_POST['_wpnonce_orderable_location'] ) );

		if ( ! wp_verify_nonce( $nonce, 'orderable_location_save' ) ) {
			return $holidays;
		}

		if ( empty( $_POST['orderable_location_holidays'] ) ) {
			return $holidays;
		}

		$default_settings = array(
			'holiday_id' => '',
			'from'       => '',
			'to'         => '',
			'services'   => array(),
			'repeat'     => '',
		);

		$data = map_deep(
			wp_unslash( $_POST['orderable_location_holidays'] ),
			'sanitize_text_field'
		);

		$holidays = array_map(
			function( $item ) use ( $default_settings ) {
				$item = array_filter( $item );

				return empty( $item ) ? false : wp_parse_args( $item, $default_settings );
			},
			$data
		);

		$holidays = array_filter( $holidays );

		return $holidays;
	}
}
