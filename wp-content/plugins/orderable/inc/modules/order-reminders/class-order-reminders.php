<?php
/**
 * Order reminders class.
 *
 * @package Orderable/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Orderable_Order_Reminders class
 */
class Orderable_Order_Reminders {
	/**
	 * Single instance of the Orderable_Order_Reminders object.
	 *
	 * @var Orderable_Order_Reminders
	 */
	public static $single_instance = null;

	/**
	 * Meta where we keep the number of sent reminder mail count.
	 *
	 * @var int
	 */
	public static $meta_key_sent_reminder_count;

	/**
	 * Cron action key.
	 *
	 * @var string
	 */
	public static $cron_action;

	/**
	 * Class args.
	 *
	 * @var array
	 */
	public static $args = [];

	/**
	 * Creates/returns the single instance Orderable_Order_Reminders object.
	 *
	 * @param array $args Arguments. Required data is given below.
	 * - enabled:           Boolean.
	 * - reminder_duration: array( 'number' => 1, 'unit' => minutes|hours|days )
	 * - email_body:        Email body.
	 * - max_reminder:      Maximum number of reminders.
	 * - plugin_slug:       Plugin slug.
	 * - order_meta:        This class will check for the presence of this metadata in the order,
	 *                      will send reminders only if this meta is not present.
	 *
	 * @return Orderable_Order_Reminders
	 */
	public static function run( $args = [] ) {
		if ( null === self::$single_instance ) {
			$reminder_duration = wp_parse_args(
				Orderable_Settings::get_setting( 'order_reminders_date_time_email_reminders_duration' ),
				[
					'number' => 1,
					'unit'   => 'hours',
				]
			);

			$default_args = [
				'enabled'           => Orderable_Settings::get_setting( 'order_reminders_date_time_enable_reminder' ),
				'reminder_duration' => $reminder_duration,
				'email_body'        => Orderable_Settings::get_setting( 'order_reminders_date_time_email_text' ),
				'max_reminder'      => Orderable_Settings::get_setting( 'order_reminders_date_time_max_emails' ),
				'plugin_slug'       => 'orderable',
				'order_meta'        => '_orderable_order_timestamp',
			];

			self::$args = wp_parse_args( $args, $default_args );

			self::$single_instance = new self();
			return self::$single_instance;
		}

		return self::$single_instance;
	}

	/**
	 * Construct.
	 */
	private function __construct() {
		self::$meta_key_sent_reminder_count = sprintf( '%s_reminder_count', self::$args['plugin_slug'] );
		self::$cron_action                  = sprintf( '%s_send_reminders', self::$args['plugin_slug'] );

		add_action( 'wp_loaded', [ $this, 'init' ] );

		add_filter( 'wpsf_register_settings_orderable', [ $this, 'add_settings' ] );
	}

	/**
	 * Init.
	 */
	public function init() {
		if ( ! self::$args['enabled'] ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_assets' ] );
		add_action( 'wp', [ $this, 'update_order_date_time' ] );
		add_action( 'woocommerce_order_details_after_order_table', [ $this, 'maybe_show_timing_fields' ] );

		add_action( 'woocommerce_payment_complete', [ $this, 'after_order_created' ], 100 );
		add_action( 'woocommerce_thankyou', [ $this, 'after_order_created' ], 100 );
		add_action( self::$cron_action, [ $this, 'send_reminders' ] );

		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ $this, 'add_meta_args_to_wc_query' ], 10, 2 );
		add_filter( 'woocommerce_get_order_item_totals', [ $this, 'maybe_show_select_order_date_button' ], 10, 2 );

		if ( false === as_next_scheduled_action( self::$cron_action ) ) {
			$duration = $this->get_duration();
			if ( ! empty( $duration ) ) {
				as_schedule_single_action( $duration, self::$cron_action, [] );
			}
		}
	}

	/**
	 * Check if data/time data exists.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function after_order_created( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $this->is_order_date_pending( $order ) ) {
			return;
		}

		$order->update_status( 'wc-on-hold' );
		$order->update_meta_data( self::$meta_key_sent_reminder_count, 0 );

		$order->save();
	}

	/**
	 * Is delivery date pending.
	 *
	 * @param WC_Order|int $order WC_Order|Order ID.
	 *
	 * @return bool
	 */
	public function is_order_date_pending( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return false;
		}

		return empty( $order->get_meta( self::$args['order_meta'] ) );
	}

	/**
	 * Send reminders.
	 */
	public function send_reminders() {
		if ( ! self::$args['enabled'] ) {
			return;
		}

		$duration = $this->get_duration( true );
		if ( false === $duration ) {
			$duration = 0;
		}

		$max_reminder = is_numeric( self::$args['max_reminder'] ) ? absint( self::$args['max_reminder'] ) : 3;

		$args = [
			'return'                                => 'ids',
			'limit'                                 => 200, // upper limit to prevent crashing the site.
			'status'                                => 'on-hold',
			'date_created'                          => '<' . ( time() - $duration ),
			self::$args['plugin_slug'] . '_wcquery' => true,
			'meta_query'                            => [ // phpcs:ignore WordPress.DB.SlowDBQuery
				[
					'key'     => self::$args['order_meta'],
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => self::$meta_key_sent_reminder_count,
					'value'   => $max_reminder,
					'compare' => '<',
					'type'    => 'numeric',
				],
			],
		];

		/**
		 * Filter the query args to retrieve the orders to send the reminder.
		 *
		 * @since 1.14.0
		 * @hook orderable_order_reminders_get_orders_query_args
		 * @param  array $query_args           The query args to retrieve the orders to send the reminder.
		 * @param  array $order_reminders_args The Order Reminders args.
		 * @return array New value
		 */
		$args = apply_filters( 'orderable_order_reminders_get_orders_query_args', $args, self::$args );

		$orders = wc_get_orders( $args );

		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			/**
			 * Filter whether order reminder emails should be skipped. Default: false.
			 *
			 * @since 1.14.0
			 * @hook orderable_order_reminders_skip_sending_email
			 * @param  bool     $value The value to skip or not. Default: false.
			 * @param  WC_Order $order The order object.
			 * @return bool New value
			 */
			$skip_sending_order_reminders_email = apply_filters( 'orderable_order_reminders_skip_sending_email', false, $order );

			if ( $skip_sending_order_reminders_email ) {
				continue;
			}

			$to = $order->get_billing_email();
			// Translators: Order number.
			$subject = sprintf( esc_html__( 'Order Date Reminder (Order #%d)', 'orderable' ), $order->get_order_number() );
			$body    = self::$args['email_body'];

			$title = sprintf( 'Reminder to enter the order date and time for your order #%d', $order->get_id() );

			$title = $this->replace_placeholders( $title, $order );
			$body  = $this->replace_placeholders( $body, $order );
			$this->send_email( $to, $subject, $title, $body );

			// Increment the reminder count.
			$reminder_count = absint( $order->get_meta( self::$meta_key_sent_reminder_count ) );
			$order->update_meta_data( self::$meta_key_sent_reminder_count, ++$reminder_count );

			$order->save();
		}
	}

	/**
	 * Get duration.
	 *
	 * @param bool $return_diff Return difference.
	 *
	 * @return float
	 */
	public function get_duration( $return_diff = false ) {
		if (
			empty( self::$args['reminder_duration']['number'] ) ||
			empty( self::$args['reminder_duration']['unit'] )
			) {
			return false;
		}

		$duration = floatval( self::$args['reminder_duration']['number'] );
		$unit     = self::$args['reminder_duration']['unit'];

		// unit is minutes, hours, days.
		if ( 'minutes' === $unit ) {
			$duration = $duration * 60;
		} elseif ( 'hours' === $unit ) {
			$duration = $duration * 60 * 60;
		} elseif ( 'days' === $unit ) {
			$duration = $duration * 60 * 60 * 24;
		}

		if ( $return_diff ) {
			return $duration;
		}

		return time() + $duration;
	}

	/**
	 * Add meta_query argument to WC_Query.
	 * WC_Query doesn't directly accepts 'meta_query' argument.
	 *
	 * @param Object $query      Query.
	 * @param array  $query_vars Array.
	 *
	 * @return Object.
	 */
	public function add_meta_args_to_wc_query( $query, $query_vars ) {
		if ( empty( $query_vars[ self::$args['plugin_slug'] . '_wcquery' ] ) ) {
			return $query;
		}

		$query['meta_query'] = $query_vars['meta_query']; // phpcs:ignore WordPress.DB.SlowDBQuery

		return $query;
	}

	/**
	 * Send email using the WooCommerce template.
	 *
	 * @param string $to            Receiver's email.
	 * @param string $email_subject Email subject.
	 * @param string $body_heading  Title which appears at the top in large fonts.
	 * @param string $body_message  Email text.
	 *
	 * @return bool
	 */
	public function send_email( $to, $email_subject, $body_heading, $body_message ) {
		$mailer  = WC()->mailer();
		$message = $mailer->wrap_message( $body_heading, $body_message );
		return $mailer->send( $to, wp_strip_all_tags( $email_subject ), $message );
	}

	/**
	 * Replace placeholders.
	 *
	 * @param string   $string  The subject in which we want to replace placeholders.
	 * @param WC_Order $order   Order.
	 *
	 * @return string
	 */
	public function replace_placeholders( $string, $order ) {
		$string = str_replace( '{ORDER_ID}', $order->get_id(), $string );
		$string = str_replace( '{SITE_NAME}', get_bloginfo( 'name' ), $string );
		$string = str_replace( '{ORDER_NUMBER}', $order->get_order_number(), $string );
		$string = str_replace( '{ORDER_DATE_TIME}', $order->get_date_created()->format( 'Y-m-d H:i:s' ), $string );
		$string = str_replace( '{CUSTOMER_NAME}', $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), $string );
		$string = str_replace( '{CUSTOMER_EMAIL}', $order->get_billing_email(), $string );
		$string = str_replace( '{CUSTOMER_ADDRESS}', wp_strip_all_tags( str_replace( '<br/>', "\n", $order->get_formatted_billing_address() ) ), $string );
		$string = str_replace( '{CUSTOMER_PHONE}', $order->get_billing_phone(), $string );
		$string = str_replace( '{NOTE}', $order->get_customer_note(), $string );
		$string = str_replace( '{THANKYOU_URL}', $order->get_checkout_order_received_url(), $string );

		if ( false !== strpos( $string, '{CART_ITEMS}' ) ) {
			$cart_items = '';

			foreach ( $order->get_items() as $item ) {
				$cart_items .= $item['name'] . ' x ' . $item['qty'] . ', ';
			}

			$cart_items = trim( $cart_items, ', ' );

			$string = str_replace( '{CART_ITEMS}', $cart_items, $string );
		}

		return $string;
	}

	/**
	 * Add the frontend assets
	 *
	 * @return void
	 */
	public function frontend_assets() {
		if ( ! self::is_thankyou_page() && ! self::is_my_account_order_page() ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'orderable-modules-order-reminders',
			ORDERABLE_URL . 'inc/modules/order-reminders/assets/frontend/css/order-reminders' . $suffix . '.css',
			[],
			ORDERABLE_VERSION
		);

		wp_enqueue_script(
			'orderable-modules-order-reminders',
			ORDERABLE_URL . 'inc/modules/order-reminders/assets/frontend/js/main' . $suffix . '.js',
			[ 'jquery' ],
			ORDERABLE_VERSION,
			true
		);
	}

	/**
	 * Returns false of the ID of the order if it is the Thank you page.
	 *
	 * @return bool|int False or Order ID.
	 */
	protected static function is_thankyou_page() {
		if ( is_checkout() && is_wc_endpoint_url( 'order-received' ) ) {
			global $wp;
			if ( isset( $wp->query_vars['order-received'] ) ) {
				$order_id = absint( $wp->query_vars['order-received'] );
				return $order_id;
			}

			return false;
		}

		return false;
	}

	/**
	 * Is View order page.
	 *
	 * @return int Order ID.
	 */
	protected static function is_my_account_order_page() {
		global $wp;

		return ( is_view_order_page() && isset( $wp->query_vars['view-order'] ) ) ? $wp->query_vars['view-order'] : false;
	}

	/**
	 * Update Order date and time.
	 *
	 * @return void
	 */
	public function update_order_date_time() {
		if ( ! class_exists( 'Orderable_Timings_Checkout' ) ) {
			return;
		}

		if ( ! self::is_thankyou_page() && ! self::is_my_account_order_page() ) {
			return;
		}

		if ( empty( $_POST['_orderable_update_order_date_time'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_orderable_update_order_date_time'] ) ), 'order_reminders' ) ) {
			return;
		}

		$order_id = sanitize_text_field( wp_unslash( $_POST['orderable_order_id'] ?? '' ) );

		if ( empty( $order_id ) ) {
			return;
		}

		$ignore_needs_shipping = function() {
			return true;
		};

		add_filter( 'orderable_location_service_dates_ignore_needs_shipping', $ignore_needs_shipping, 50 );
		Orderable_Timings_Checkout::process_checkout( $order_id );
		remove_filter( 'orderable_location_service_dates_ignore_needs_shipping', $ignore_needs_shipping, 50 );
	}

	/**
	 * Maybe show the fields to change the order date and time.
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public function maybe_show_timing_fields( WC_Order $order ) {
		if ( ! Orderable_Settings::get_setting( 'order_reminders_date_time_enable_reminder' ) ) {
			return;
		}

		if ( empty( $order ) ) {
			return;
		}

		if ( ! empty( $order->get_meta( '_orderable_order_timestamp' ) ) ) {
			return;
		}

		$service_type = $order->get_meta( '_orderable_service_type' );

		if ( ! in_array( $service_type, [ 'pickup', 'delivery' ], true ) ) {
			return;
		}

		$location_id    = absint( $order->get_meta( '_orderable_location_id' ) );
		$location_class = class_exists( 'Orderable_Location_Single_Pro' ) ? 'Orderable_Location_Single_Pro' : 'Orderable_Location_Single';
		$location       = new $location_class( $location_id );

		$service_type_label = Orderable_Services::get_service_label( $service_type );

		$shipping_methods = $order->get_shipping_methods();

		foreach ( $shipping_methods as $shipping_method ) {
			if ( ! is_a( $shipping_method, 'WC_Order_Item_Shipping' ) ) {
				continue;
			}

			$zone = WC_Shipping_Zones::get_zone_by( 'instance_id', $shipping_method->get_instance_id() );

			if ( empty( $zone ) ) {
				continue;
			}

			$zone_id = $zone->get_id();

			break;
		}

		if ( ! empty( $zone_id ) ) {
			WC()->session->set( 'orderable_chosen_zone_id', $zone_id );
		}

		$service_dates = $location->get_service_dates( $service_type, true );
		$asap          = $location->get_asap_settings();

		// No dates required.
		if ( true === $service_dates ) {
			return;
		}

		$this->output_modal( $order, $service_type_label, $service_dates, $asap );
	}

	/**
	 * Output the Order Reminders modal.
	 *
	 * @param WC_Order $order              The order.
	 * @param string   $service_type_label The service type (`delivery` or `pickup`).
	 * @param array    $service_dates      The service dates available.
	 * @param array    $asap               The ASAP settings.
	 * @return void
	 */
	protected function output_modal( $order, $service_type_label, $service_dates, $asap ) {
		?>
		<div class="orderable-order-date-time-reminders-modal">
			<div class="orderable-order-date-time-reminders-modal__inner">
				<?php
				/**
				 * Fires before Order Reminders modal header.
				 *
				 * @since 1.14.0
				 * @hook orderable_order_reminders_before_modal_header
				 * @param  WC_Order $order The order object.
				 */
					do_action( 'orderable_order_reminders_before_modal_header', $order );
				?>
				<div class="orderable-order-date-time-reminders-modal__header">
					<?php esc_html_e( 'Choose your order date', 'orderable' ); ?>
					<?php
					/**
					 * Fires within Order Reminders modal header.
					 *
					 * @since 1.14.0
					 * @hook orderable_order_reminders_modal_header
					 * @param  WC_Order $order The order object.
					 */
						do_action( 'orderable_order_reminders_modal_header', $order );
					?>
				</div>
				<?php
				/**
				 * Fires before Order Reminders modal fields.
				 *
				 * @since 1.14.0
				 * @hook orderable_order_reminders_before_modal_fields
				 * @param  WC_Order $order The order object.
				 */
					do_action( 'orderable_order_reminders_before_modal_fields', $order );
				?>
				<div class="orderable-order-date-time-reminders-modal__fields">
					<form method="post" class="orderable-order-date-time-reminders-modal__form">
						<label
							for="orderable-date"
							class="orderable-order-date-time-reminders-modal__label"
						>
							<?php
							// Translators: %s Service type.
							printf( esc_html__( '%s Date', 'orderable' ), esc_html( $service_type_label ) );
							?>
						</label>

						<select
							name="orderable_order_date"
							id="orderable-date"
							class="orderable-order-timings__date orderable-order-date-time-reminders-modal__date-field orderable-order-date-time-reminders-modal__select"
						>
							<option value=""><?php esc_html_e( 'Select a date...', 'orderable' ); ?></option>
							<?php if ( ! empty( $asap['date'] ) ) { ?>
								<option value="asap"><?php esc_html_e( 'As soon as possible', 'orderable' ); ?></option>
							<?php } ?>
							<?php foreach ( $service_dates as $service_date_data ) { ?>
								<option
									value="<?php echo esc_attr( $service_date_data['timestamp'] ); ?>"
									data-orderable-slots="<?php echo wc_esc_json( wp_json_encode( array_values( $service_date_data['slots'] ) ) ); ?>"
								>
									<?php echo esc_html( $service_date_data['formatted'] ); ?>
								</option>
							<?php } ?>
						</select>

						<div class="orderable-order-date-time-reminders-modal__time" style="display: none;">
							<label
								for="orderable-time"
								class="orderable-order-date-time-reminders-modal__label"
							>
								<?php
								// Translators: %s Service type.
								printf( esc_html__( '%s Time', 'orderable' ), esc_html( $service_type_label ) );
								?>
							</label>
						
							<select
								name="orderable_order_time"
								id="orderable-time"
								class="orderable-order-timings__time orderable-order-date-time-reminders-modal__time-field orderable-order-date-time-reminders-modal__select"
								disabled="disabled"
							>
								<option value=""><?php esc_html_e( 'Select a time...', 'orderable' ); ?></option>
								<?php if ( ! empty( $asap['time'] ) ) { ?>
									<option value="asap"><?php esc_html_e( 'As soon as possible', 'orderable' ); ?></option>
								<?php } ?>
							</select>
						</div>

						<?php wp_nonce_field( 'order_reminders', '_orderable_update_order_date_time' ); ?>
						<input
							type="hidden"
							name="orderable_order_id"
							value="<?php echo esc_attr( $order->get_id() ); ?>"
						/>
						<br />
						<button
							type="submit"
							class="orderable-order-date-time-reminders-modal__button orderable-order-date-time-reminders-modal__save orderable-button orderable-button--filled orderable-button--full"
							disabled
						>
							<?php esc_html_e( 'Update', 'orderable' ); ?>
						</button>
					</form>
				</div>
				<?php
				/**
				 * Fires before Order Reminders modal footer.
				 *
				 * @since 1.14.0
				 * @hook orderable_order_reminders_before_modal_footer
				 * @param  WC_Order $order The order object.
				 */
					do_action( 'orderable_order_reminders_before_modal_footer', $order );
				?>
				<div class="orderable-order-date-time-reminders-modal__footer">
					<button
						class="orderable-order-date-time-reminders-modal__button orderable-order-date-time-reminders-modal__cancel"
						type="button"
					>
						<?php esc_html_e( 'Cancel', 'orderable' ); ?>
					</button>

					<?php
					/**
					 * Fires within Order Reminders modal footer.
					 *
					 * @since 1.14.0
					 * @hook orderable_order_reminders_modal_footer
					 * @param  WC_Order $order The order object.
					 */
						do_action( 'orderable_order_reminders_modal_footer', $order );
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add Order Reminders settings.
	 *
	 * @param array $settings The Orderable settings.
	 * @return array
	 */
	public function add_settings( $settings ) {
		$settings['tabs'][] = [
			'id'       => 'order_reminders',
			'title'    => __( 'Order Reminders', 'orderable' ),
			'priority' => 30,
		];

		$settings['sections'][] = [
			'tab_id'              => 'order_reminders',
			'section_id'          => 'date_time',
			'section_title'       => __( 'Order Date Time Reminder Settings', 'orderable' ),
			'section_description' => '',
			'section_order'       => 0,
			'fields'              => [
				[
					'id'       => 'enable_reminder',
					'title'    => __( 'Enable Reminders', 'orderable' ),
					'subtitle' => __( 'Turn on email reminders for customers who have not selected the order date/time during checkout.<br><br>Especially helpful for orders placed with express checkout payment methods like Google Pay, Apple Pay, and PayPal Express checkout.', 'orderable' ),
					'type'     => 'checkbox',
				],
				[
					'id'       => 'email_reminders_duration',
					'title'    => __( 'Reminder Frequency Duration', 'orderable' ),
					'subtitle' => __( 'Set the frequency for sending automated email reminders to customers.', 'orderable' ),
					'type'     => 'custom',
					'output'   => $this->get_reminder_duration_fields(),
					'default'  => 12,
				],
				[
					'id'       => 'max_emails',
					'title'    => __( 'Maximum Number of Reminders', 'orderable' ),
					'subtitle' => __( 'Set the maximum number of email reminders sent to each customer.', 'orderable' ),
					'type'     => 'number',
					'default'  => 3,
				],
				[
					'id'       => 'email_text',
					'title'    => __( 'Email Text', 'orderable' ),
					'subtitle' => __( 'Customize the content of the email reminder.<br><br>Available placeholders: {SITE_NAME}, {ORDER_ID}, {ORDER_NUMBER}, {ORDER_DATE_TIME}, {CUSTOMER_NAME}, {CUSTOMER_EMAIL}, {CUSTOMER_ADDRESS}, {CUSTOMER_PHONE}, {NOTE}, {CART_ITEMS}, {THANKYOU_URL} ', 'orderable' ),
					'type'     => 'textarea',
					'default'  => "Hello {CUSTOMER_NAME}, thank you for your order.\n\nPlease select the timeslot for your order #{ORDER_ID} by clicking on the URL below.\n\n{THANKYOU_URL}\n\nThanks,\n\n{SITE_NAME}",
				],
			],
		];

		return $settings;
	}

	/**
	 * Get reminder duration field.
	 */
	protected function get_reminder_duration_fields() {
		$reminder_duration = Orderable_Settings::get_setting( 'order_reminders_date_time_email_reminders_duration' );
		$number            = $reminder_duration['number'] ?? '1';
		$unit              = $reminder_duration['unit'] ?? 'hours';

		ob_start();
		?>
		<div class="wds-reminder-duration">
			<input 
				type="number" 
				name='orderable_settings[order_reminders_date_time_email_reminders_duration][number]' 
				value="<?php echo esc_attr( $number ); ?>"
				/>
			<select name='orderable_settings[order_reminders_date_time_email_reminders_duration][unit]'>
				<option <?php selected( $unit, 'minutes' ); ?> value="minutes"><?php esc_html_e( 'Minute(s)', 'orderable' ); ?></option>
				<option <?php selected( $unit, 'hours' ); ?> value="hours"><?php esc_html_e( 'Hour(s)', 'orderable' ); ?></option>
				<option <?php selected( $unit, 'days' ); ?> value="days"><?php esc_html_e( 'Day(s)', 'orderable' ); ?></option>
			</select>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Maybe show the `Select a date` button to select the order date.
	 *
	 * @param array    $total_rows  Total rows.
	 * @param WC_Order $order The order object.
	 * @return array
	 */
	public function maybe_show_select_order_date_button( $total_rows, WC_Order $order ) {
		if ( ! Orderable_Settings::get_setting( 'order_reminders_date_time_enable_reminder' ) ) {
			return $total_rows;
		}

		if ( empty( $order ) ) {
			return $total_rows;
		}

		if ( ! empty( $order->get_meta( '_orderable_order_timestamp' ) ) ) {
			return $total_rows;
		}

		$service_type = $order->get_meta( '_orderable_service_type' );

		if ( ! in_array( $service_type, [ 'pickup', 'delivery' ], true ) ) {
			return $total_rows;
		}

		$order_total = $total_rows['order_total'];

		unset( $total_rows['order_total'] );

		$total_rows['orderable_order_reminders'] = array(
			'label' => __( 'Choose your order date', 'orderable' ),
			'value' => sprintf(
				'<a href="%1$s" role="button">%2$s</a>',
				is_order_received_page() ? $order->get_checkout_order_received_url() : $order->get_view_order_url(),
				__( 'Select a date', 'orderable' )
			),
		);

		$total_rows['order_total'] = $order_total;

		return $total_rows;
	}
}
