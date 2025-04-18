<?php
/**
 * Module: Timings checkout.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Timings checkout class.
 */
class Orderable_Timings_Checkout {
	/**
	 * Init.
	 */
	public static function run() {
		add_action( 'woocommerce_review_order_after_shipping', array( __CLASS__, 'output_timing_fields' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_checkout' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'process_checkout' ) );
		add_filter( 'woocommerce_order_button_html', array( __CLASS__, 'place_order_button' ) );
	}

	/**
	 * Output timing fields at checkout.
	 *
	 * @throws Exception
	 */
	public static function output_timing_fields() {
		if ( ! self::has_available_methods() ) {
			return;
		}

		$location      = Orderable_Location::get_selected_location();
		$service       = Orderable_Services::get_selected_service();
		$service_dates = $location->get_service_dates();
		$asap          = $location->get_asap_settings();

		// No dates required.
		if ( true === $service_dates ) {
			return;
		} ?>

		<?php if ( false === $service_dates ) { ?>
			<tr class="orderable-order-timings orderable-order-timings--no-dates">
				<th>
					<?php
					// Translators: %s Service type.
					printf( esc_html__( '%s Date', 'orderable' ), esc_html( $service ) );
					?>
				</th>
				<td>
				<?php
				// Translators: %s Service type.
				printf( esc_html__( 'Sorry, there are currently no slots available for %s.', 'orderable' ), esc_html( strtolower( $service ) ) );
				?>
				</td>
			</tr>
		<?php } else { ?>
			<tr class="orderable-order-timings orderable-order-timings--date">
				<th>
					<label for="orderable-date">
						<strong>
							<?php
							// Translators: %s Service type.
							printf( esc_html__( '%s Date', 'orderable' ), esc_html( $service ) );
							?>
						</strong>
					</label>
				</th>
				<td>
					<select name="orderable_order_date" id="orderable-date" class="orderable-order-timings__date">
						<option value=""><?php esc_html_e( 'Select a date...', 'orderable' ); ?></option>
						<?php if ( ! empty( $asap['date'] ) ) { ?>
							<option value="asap"><?php esc_html_e( 'As soon as possible', 'orderable' ); ?></option>
						<?php } ?>
						<?php foreach ( $service_dates as $service_date_data ) { ?>
							<option value="<?php echo esc_attr( $service_date_data['timestamp'] ); ?>" data-orderable-slots="<?php echo wc_esc_json( json_encode( array_values( $service_date_data['slots'] ) ) ); ?>"><?php echo $service_date_data['formatted']; ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr class="orderable-order-timings orderable-order-timings--time" style="display: none;">
				<th>
					<label for="orderable-time">
						<strong>
							<?php
							// Translators: %s Service type.
							printf( __( '%s Time', 'orderable' ), $service );
							?>
						</strong>
					</label>
				</th>
				<td>
					<input type="hidden" value="" name="orderable_order_time_slot_id" />
					<select name="orderable_order_time" id="orderable-time" class="orderable-order-timings__time" disabled="disabled">
						<option value=""><?php _e( 'Select a time...', 'orderable' ); ?></option>
						<?php if ( ! empty( $asap['time'] ) ) { ?>
							<option value="asap"><?php esc_html_e( 'As soon as possible', 'orderable' ); ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
		<?php } ?>
		<?php
	}

	/**
	 * Validate checkout fields.
	 */
	public static function validate_checkout() {
		$order_date = empty( $_POST['orderable_order_date'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['orderable_order_date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( isset( $_POST['orderable_order_date'] ) && empty( $order_date ) ) {
			wc_add_notice( __( 'Please select an order date.', 'orderable' ), 'error' );
		}
	}

	/**
	 * Process checkout fields.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @throws Exception
	 */
	public static function process_checkout( $order_id ) {
		$checkout_data = self::get_checkout_data();

		if ( empty( $checkout_data ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( empty( $order ) ) {
			return;
		}

		foreach ( $checkout_data as $key => $data ) {
			if ( ! $data['save'] ) {
				continue;
			}

			$order->update_meta_data( $key, $data['value'] );
		}

		$order->save();
	}

	/**
	 * Get posted checkout data.
	 *
	 * @return mixed|void
	 * @throws Exception
	 */
	public static function get_checkout_data() {
		$data = array();

		$location   = Orderable_Location::get_selected_location();
		$order_date = empty( $_POST['orderable_order_date'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['orderable_order_date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( $order_date && 'asap' !== $order_date ) {
			$date_format          = get_option( 'date_format' );
			$timestamp_adjusted   = Orderable_Timings::get_timestamp_adjusted( $order_date );
			$order_date_formatted = date_i18n( $date_format, $timestamp_adjusted );

			$data['_orderable_order_timestamp'] = array(
				'save'  => true,
				'value' => absint( $order_date ),
			);

			$data['orderable_order_date'] = array(
				'save'  => true,
				'value' => sanitize_text_field( $order_date_formatted ),
			);
		}

		if ( $order_date && 'asap' === $order_date ) {
			$dates = $location->get_service_dates();

			if ( ! $dates ) {
				return;
			}

			$earliest_order_date  = $dates[0]['timestamp'];
			$date_format          = get_option( 'date_format' );
			$timestamp_adjusted   = Orderable_Timings::get_timestamp_adjusted( $earliest_order_date );
			$order_date_formatted = date_i18n( $date_format, $timestamp_adjusted );

			$data['_orderable_order_timestamp'] = array(
				'save'  => true,
				'value' => absint( $earliest_order_date ),
			);

			$data['orderable_order_date'] = array(
				'save'  => true,
				'value' => sanitize_text_field( $order_date_formatted ) . esc_html__( ' (As soon as possible)', 'orderable' ),
			);
		}

		return apply_filters( 'orderable_checkout_data', $data );
	}

	/**
	 * Has available shipping methods.
	 *
	 * @return bool
	 */
	public static function has_available_methods() {
		$has_available_methods = false;
		$packages              = WC()->shipping()->get_packages();

		if ( ! empty( $packages ) ) {
			foreach ( $packages as $i => $package ) {
				if ( empty( $package['rates'] ) ) {
					continue;
				}

				$has_available_methods = true;
				break;
			}
		}

		return apply_filters( 'orderable_has_available_methods', $has_available_methods );
	}

	/**
	 * Modify place order button.
	 *
	 * @param string $button_html Button HTML.
	 * @return string
	 */
	public static function place_order_button( $button_html ) {
		$location      = Orderable_Location::get_selected_location();
		$service_dates = $location->get_service_dates();

		if ( $service_dates ) {
			return $button_html;
		}

		if ( strpos( $button_html, 'disabled' ) !== false ) {
			return $button_html;
		}

		$button_html = str_replace( '<button', '<button disabled', $button_html );

		return $button_html;
	}
}
