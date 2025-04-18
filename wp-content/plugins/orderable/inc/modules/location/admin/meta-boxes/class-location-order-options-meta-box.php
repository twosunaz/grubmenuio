<?php
/**
 * Module: Location.
 *
 * @since   1.18.0
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable_Multi_Location_Pro_Order_Options_Meta_Box class.
 */
class Orderable_Location_Order_Options_Meta_Box {
	/**
	 * Init.
	 */
	public static function run() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'orderable_location_get_save_data', array( __CLASS__, 'get_save_data' ) );
	}

	/**
	 * Get the meta box title.
	 *
	 * @return string
	 */
	public static function get_title() {
		return __( 'Order Options', 'orderable' );
	}

	/**
	 * Add the Meta Box.
	 *
	 * @return void
	 */
	public static function add() {
		add_meta_box(
			'orderable_location_order_options_meta_box',
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

		$asap                             = $location->get_asap_settings();
		$is_asap_delivery_date_enabled    = ! empty( $asap['date'] );
		$lead_time                        = $location->get_lead_time();
		$preorder_days                    = $location->get_preorder_days();
		$delivery_days_calculation_method = $location->get_delivery_calculation_method();

		$toggle_class = array(
			'orderable-toggle-field',
			'woocommerce-input-toggle',
		);

		$asap_delivery_date_toggle_class[] = $is_asap_delivery_date_enabled ? 'woocommerce-input-toggle--enabled' : 'woocommerce-input-toggle--disabled';
		$asap_delivery_date_toggle_class   = join( ' ', array_merge( $toggle_class, $asap_delivery_date_toggle_class ) );
		?>
		<div class="orderable-fields-row orderable-fields-row--meta">
			<div class="orderable-fields-row__body">
				<div class="orderable-fields-row__body-row">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'As Soon As Possible', 'Order Options', 'orderable-pro' ); ?></h3>
						<p>
							<?php echo esc_html__( 'Allow customers to request delivery "ASAP".', 'orderable-pro' ); ?>
						</p>
					</div>
					<div class="orderable-fields-row__body-row-right orderable-order-options__as-soon-as-possible">
						<div class="orderable-toggle-field-wrapper">
							<span
								class="<?php echo esc_attr( $asap_delivery_date_toggle_class ); ?>"
							>
								<?php echo esc_html( 'On delivery date' ); ?>
							</span>

							<input
								type="hidden"
								name="orderable_location_order_options_asap_delivery_date"
								class="orderable-toggle-field__input"
								value="<?php echo esc_attr( $is_asap_delivery_date_enabled ? 'yes' : 'no' ); ?>"
							/>

							<span class="orderable-toggle-field__label-wrapper">
								<span class="orderable-toggle-field__label">
									<?php echo esc_html__( 'On delivery date' ); ?>
								</span>
								<span class="orderable-toggle-field__label-help">
									<?php echo esc_html__( 'Allow "ASAP" as an option when choosing delivery date' ); ?>
								</span>
							</span>
						</div>

						<?php
						/**
						 * Output additional ASAP order options.
						 *
						 * @since 1.8.0
						 * @param Orderable_Location_Single $location Orderable_Location_Single instance.
						 */
						do_action( 'orderable_asap_order_options', $location );
						?>
					</div>
				</div>

				<div class="orderable-fields-row__body-row">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Lead Time', 'Order Options', 'orderable-pro' ); ?></h3>
						<p>
							<?php echo esc_html__( 'How long do you need to prepare the order? Leave blank or "0" for same day.', 'orderable-pro' ); ?>
						</p>
					</div>
					<div class="orderable-fields-row__body-row-right">
						<input
							type="number"
							name="orderable_location_lead_time"
							class="orderable-field"
							value="<?php echo esc_attr( $lead_time ); ?>"
							min="0"
							placeholder="0"
						/>

						<?php self::lead_time_period_field(); ?>
					</div>
				</div>

				<div class="orderable-fields-row__body-row">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Preorder Days', 'Order Options', 'orderable-pro' ); ?></h3>
						<p>
							<?php echo esc_html__( 'How many days do you want to offer delivery/pickup? Leave blank or "0" for same day. This setting should be at least the same as, or higher than, "Lead Time".', 'orderable-pro' ); ?>
						</p>
					</div>
					<div class="orderable-fields-row__body-row-right">
						<input
							type="number"
							name="orderable_location_preorder_days"
							class="orderable-field"
							value="<?php echo esc_attr( $preorder_days ); ?>"
							min="0"
							placeholder="0"
						/>
					</div>
				</div>

				<div class="orderable-fields-row__body-row">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Delivery Days Calculation Method', 'Order Options', 'orderable-pro' ); ?></h3>
						<p>
							<?php echo esc_html__( 'Calculate Lead time and Preorder Days based on all days of the week, open days, service days or weekdays.', 'orderable-pro' ); ?>
						</p>
						<br />
						<p>
							<a href="https://orderable.com/docs/how-to-set-your-order-lead-time/#change-how-lead-time-and-preorder-days-are-calculated" target="_blank">
								<?php echo esc_html( 'See documentation' ); ?>
							</a>
						</p>
					</div>
					<div class="orderable-fields-row__body-row-right">
						<select name="orderable_location_delivery_days_calculation_method">
							<option value="service" <?php selected( $delivery_days_calculation_method, 'service' ); ?>>
								<?php echo esc_html__( 'Service Days', 'orderable-pro' ); ?>
							</option>
							<option value="open" <?php selected( $delivery_days_calculation_method, 'open' ); ?>>
								<?php echo esc_html__( 'Open Days', 'orderable-pro' ); ?>
							</option>
							<option value="weekdays" <?php selected( $delivery_days_calculation_method, 'weekdays' ); ?>>
								<?php echo esc_html__( 'Weekdays Only', 'orderable-pro' ); ?>
							</option>
							<option value="all" <?php selected( $delivery_days_calculation_method, 'all' ); ?>>
								<?php echo esc_html__( 'All Days', 'orderable-pro' ); ?>
							</option>
						</select>
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
			'store_general_asap'               => array(),
			'store_general_lead_time'          => '',
			'store_general_preorder'           => '',
			'store_general_calculation_method' => 'service',
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

		$order_options_data = array(
			'store_general_asap'               => self::get_asap_data(),
			'store_general_lead_time'          => Orderable_Location_Admin::get_posted_value( 'orderable_location_lead_time' ),
			'store_general_preorder'           => Orderable_Location_Admin::get_posted_value( 'orderable_location_preorder_days' ),
			'store_general_calculation_method' => Orderable_Location_Admin::get_posted_value( 'orderable_location_delivery_days_calculation_method' ),
		);

		return array_merge( $data, $order_options_data );
	}

	/**
	 * Get location store ASAP setting sent via POST.
	 *
	 * @return array
	 */
	public static function get_asap_data() {
		$store_general_asap = array();

		if ( 'yes' === Orderable_Location_Admin::get_posted_value( 'orderable_location_order_options_asap_delivery_date' ) ) {
			$store_general_asap[] = 'day';
		}

		if ( 'yes' === Orderable_Location_Admin::get_posted_value( 'orderable_location_order_options_asap_delivery_time' ) ) {
			$store_general_asap[] = 'slot';
		}

		return $store_general_asap;
	}

	/**
	 * Output the Lead Time Period field.
	 *
	 * @return void
	 */
	protected static function lead_time_period_field() {
		$location         = new Orderable_Location_Single();
		$lead_time_period = $location->get_lead_time_period();

		$options = array(
			array(
				'value'    => 'minutes',
				'label'    => __( 'Minutes (Pro)', 'orderable' ),
				'disabled' => true,
			),
			array(
				'value'    => 'hours',
				'label'    => __( 'Hours (Pro)', 'orderable' ),
				'disabled' => true,
			),
			array(
				'value' => 'days',
				'label' => __( 'Days', 'orderable' ),
			),
		);

		/**
		 * Filter the options for the Lead Time Period select field.
		 *
		 * @since 1.18.0
		 * @hook orderable_location_lead_time_period_field_options
		 * @param  array $options The Lead Time Period field options.
		 * @return array New value
		 */
		$options = apply_filters( 'orderable_location_lead_time_period_field_options', $options );

		?>
		<select name="orderable_location_lead_time_period">
			<?php foreach ( $options as $option ) : ?>
				<option
					value="<?php echo esc_attr( $option['value'] ); ?>"
					<?php selected( $lead_time_period, $option['value'] ); ?>
					<?php disabled( ! empty( $option['disabled'] ) ); ?>
					title="<?php echo empty( $option['disabled'] ) ? '' : esc_attr__( 'Available in Pro', 'orderable' ); ?>"
				>
					<?php echo esc_html( $option['label'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}
}
