<?php
/**
 * Module: Timings order.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Timings order class.
 */
class Orderable_Timings_Order {
	/**
	 * Init.
	 */
	public static function run() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_footer', array( __CLASS__, 'enqueue_daterange_script' ) );
		add_filter( 'woocommerce_get_order_item_totals', array( __CLASS__, 'add_to_order_details' ), 10, 3 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'add_to_admin_order' ), 10, 1 );
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_admin_order_columns' ), 20 );
		add_filter( 'manage_edit-shop_order_sortable_columns', array( __CLASS__, 'add_admin_order_sortable_columns' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'add_admin_order_columns_content' ), 10, 2 );
		add_action( 'pre_get_posts', array( __CLASS__, 'update_query_to_filter_admin_orders' ), 100 );
		add_filter( 'woocommerce_shop_order_list_table_prepare_items_query_args', array( __CLASS__, 'update_query_args_to_filter_admin_orders' ), 100 );
		add_filter( 'woocommerce_orders_table_query_clauses', array( __CLASS__, 'modify_query_clauses_for_due_date_sorting' ), 10, 3 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'due_date_filter' ), 60 );
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( __CLASS__, 'due_date_filter' ), 60 );
		add_action( 'woocommerce_admin_order_preview_start', array( __CLASS__, 'modify_order_preview_template' ) );
		add_filter( 'woocommerce_admin_order_preview_get_order_details', array( __CLASS__, 'modify_order_preview_get_order_details' ), 10, 2 );

		// HPOS Compatibility.
		add_action( 'admin_init', array( __CLASS__, 'add_due_date_time_custom_column' ) );
	}

	/**
	 * Enqueue assets.
	 */
	public static function enqueue_assets() {
		if ( ! Orderable_Orders::is_orders_page() ) {
			return;
		}

		wp_enqueue_script( 'momentjs', ORDERABLE_ASSETS_URL . 'vendor/moment/moment.min.js', array( 'jquery' ), ORDERABLE_VERSION, true );
		wp_enqueue_script( 'daterangepicker', ORDERABLE_ASSETS_URL . 'vendor/daterangepicker/daterangepicker.js', array( 'jquery', 'momentjs' ), ORDERABLE_VERSION, true );
		wp_enqueue_style( 'daterangepicker', ORDERABLE_ASSETS_URL . 'vendor/daterangepicker/daterangepicker.css', array(), ORDERABLE_VERSION );

		wp_localize_script(
			'daterangepicker',
			'orderable_timings_daterangepicker',
			array(
				'today'        => esc_html__( 'Today', 'orderable' ),
				'tomorrow'     => esc_html__( 'Tomorrow', 'orderable' ),
				'next_7_days'  => esc_html__( 'Next 7 Days', 'orderable' ),
				'next_30_days' => esc_html__( 'Next 30 Days', 'orderable' ),
				'custom_range' => esc_html__( 'Custom Range', 'orderable' ),
				'clear'        => esc_html__( 'Clear', 'orderable' ),
				'apply'        => esc_html__( 'Apply', 'orderable' ),
			)
		);
	}

	/**
	 * Add date range script.
	 */
	public static function enqueue_daterange_script() {
		if ( ! Orderable_Orders::is_orders_page() ) {
			return;
		}
		?>
		<script>
			var orderable_date_picker = {
				/**
				 * On ready.
				 */
				ready: function() {
					orderable_date_picker.init();
					orderable_date_picker.watch();
				},

				/**
				 * Init datepicker.
				 */
				init: function() {
					var ranges = {};
					ranges[orderable_timings_daterangepicker.today] = [ moment(), moment() ];
					ranges[orderable_timings_daterangepicker.tomorrow] = [ moment().add( 1, 'days' ), moment().add( 1, 'days' ) ];
					ranges[orderable_timings_daterangepicker.next_7_days] = [ moment(), moment().add( 6, 'days' ) ];
					ranges[orderable_timings_daterangepicker.next_30_days] = [ moment(), moment().add( 29, 'days' ) ];

					jQuery( 'input[name="orderable_due_date"]' ).daterangepicker( {
						opens: 'left',
						autoUpdateInput: false,
						alwaysShowCalendars: true,
						locale: {
							format: 'YYYY/MM/DD',
							cancelLabel: orderable_timings_daterangepicker.clear,
							applyLabel: orderable_timings_daterangepicker.apply,
							customRangeLabel: orderable_timings_daterangepicker.custom_range,
							daysOfWeek: [
								'<?php esc_html_e( 'Su', 'orderable' ); ?>',
								'<?php esc_html_e( 'Mo', 'orderable' ); ?>',
								'<?php esc_html_e( 'Tu', 'orderable' ); ?>',
								'<?php esc_html_e( 'We', 'orderable' ); ?>',
								'<?php esc_html_e( 'Th', 'orderable' ); ?>',
								'<?php esc_html_e( 'Fr', 'orderable' ); ?>',
								'<?php esc_html_e( 'Sa', 'orderable' ); ?>',
							],
							monthNames: [
								'<?php esc_html_e( 'January', 'orderable' ); ?>',
								'<?php esc_html_e( 'February', 'orderable' ); ?>',
								'<?php esc_html_e( 'March', 'orderable' ); ?>',
								'<?php esc_html_e( 'April', 'orderable' ); ?>',
								'<?php esc_html_e( 'May', 'orderable' ); ?>',
								'<?php esc_html_e( 'June', 'orderable' ); ?>',
								'<?php esc_html_e( 'July', 'orderable' ); ?>',
								'<?php esc_html_e( 'August', 'orderable' ); ?>',
								'<?php esc_html_e( 'September', 'orderable' ); ?>',
								'<?php esc_html_e( 'October', 'orderable' ); ?>',
								'<?php esc_html_e( 'November', 'orderable' ); ?>',
								'<?php esc_html_e( 'December', 'orderable' ); ?>'
							],
						},
						ranges: ranges,
					} );
				},

				/**
				 * Watch datepicker events.
				 */
				watch: function() {
					jQuery( document.body ).on( 'apply.daterangepicker', 'input[name="orderable_due_date"]', function( ev, picker ) {
						jQuery( this ).val( picker.startDate.format( 'YYYY/MM/DD' ) + ' - ' + picker.endDate.format( 'YYYY/MM/DD' ) );
					} );

					jQuery( document.body ).on( 'cancel.daterangepicker', 'input[name="orderable_due_date"]', function( ev, picker ) {
						jQuery( this ).val( '' );
					} );
				}
			};

			jQuery( document ).ready( orderable_date_picker.ready );
			jQuery( document ).on( 'orderable-live-view-updated', orderable_date_picker.init );
		</script>
		<?php
	}

	/**
	 * Add date and time to order details.
	 *
	 * @param array    $total_rows
	 * @param WC_Order $order
	 * @param bool     $tax_display
	 *
	 * @return array
	 */
	public static function add_to_order_details( $total_rows, $order, $tax_display ) {
		$data        = self::get_order_date_time( $order );
		$order_total = $total_rows['order_total'];

		unset( $total_rows['order_total'] );

		foreach ( $data as $key => $value ) {
			if ( 'order_timestamp' === $key || empty( $value['value'] ) ) {
				continue;
			}

			$total_rows[ 'orderable_' . $key ] = array(
				'label' => wptexturize( $value['label'] . ':' ),
				'value' => wptexturize( $value['value'] ),
			);
		}

		$total_rows['order_total'] = $order_total;

		return $total_rows;
	}

	/**
	 * @param WC_Order $order
	 */
	public static function add_to_admin_order( $order ) {
		$data = self::get_order_date_time( $order );

		foreach ( $data as $key => $value ) {
			if ( 'order_timestamp' === $key || empty( $value['value'] ) ) {
				continue;
			}

			echo '<p><strong>' . $value['label'] . ':</strong> <br>' . $value['value'] . '</p>';
		}
	}

	/**
	 * Get order date/time.
	 *
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public static function get_order_date_time( $order ) {
		return apply_filters(
			'orderable_get_order_date_time',
			array(
				'order_date'      => array(
					'value' => $order->get_meta( 'orderable_order_date' ),
					'label' => __( 'Date', 'orderable' ),
				),
				'order_time'      => array(
					'value' => $order->get_meta( 'orderable_order_time' ),
					'label' => __( 'Time', 'orderable' ),
				),
				'order_timestamp' => array(
					'value' => $order->get_meta( '_orderable_order_timestamp' ),
					'label' => __( 'Timestamp', 'orderable' ),
				),
			),
			$order
		);
	}

	/**
	 * Add columns to admin orders screen.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public static function add_admin_order_columns( $columns ) {
		$columns['orderable_order_data'] = __( 'Due Date/Time', 'orderable' );

		return $columns;
	}

	/**
	 * Add columns content to admin orders screen.
	 *
	 * @param $column_name
	 * @param $post_id
	 */
	public static function add_admin_order_columns_content( $column_name, $post_id ) {
		if ( 'orderable_order_data' === $column_name ) {
			$order = wc_get_order( $post_id );
			$data  = self::get_order_date_time( $order );

			if ( ! empty( $data['order_date']['value'] ) ) {
				printf( '<p><strong>%s</strong>: %s</p>', __( 'Date', 'orderable' ), $data['order_date']['value'] );
			}

			if ( ! empty( $data['order_time']['value'] ) ) {
				printf( '<p><strong>%s</strong>: %s</p>', __( 'Time', 'orderable' ), $data['order_time']['value'] );
			}
		}
	}

	/**
	 * Register columns as sortable.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public static function add_admin_order_sortable_columns( $columns ) {
		$columns['orderable_order_data'] = '_orderable_order_timestamp';

		return $columns;
	}

	/**
	 * Update query object to filter orders by due date.
	 *
	 * @param WP_Query $query The query to retrieve the orders.
	 */
	public static function update_query_to_filter_admin_orders( $query ) {
		if ( ! Orderable_Orders::is_orders_page() || 'shop_order' !== $query->get( 'post_type' ) ) {
			return;
		}

		$args = array(
			'orderby'  => empty( $_GET['orderby'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['orderby'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
			'due_date' => array_filter( self::get_filtered_due_date() ),
		);

		self::update_query( $query, $args );
	}

	/**
	 * Update query args to filter orders by due date.
	 *
	 * @param array $query_args The query args used to retrieve the orders.
	 * @return array
	 */
	public static function update_query_args_to_filter_admin_orders( $query_args ) {
		if ( ! Orderable_Orders::is_orders_page() ) {
			return $query_args;
		}

		if ( empty( $_GET['_orderable_filter_orders_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_orderable_filter_orders_nonce'] ) ), 'orderable_filter_orders' ) ) {
			return $query_args;
		}

		$meta_query = empty( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ? array() : $query_args['meta_query'];

		$order_by = empty( $_GET['orderby'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
		$due_date = array_filter( self::get_filtered_due_date() );

		if ( ! empty( $order_by ) ) {
			$meta_query[] = array(
				'key'     => $query_args['orderby'],
				'compare' => 'EXISTS',
			);
		}

		if ( ! empty( $due_date['start_timestamp'] ) && ! empty( $due_date['end_timestamp'] ) ) {
			$meta_query[] = array(
				'key'     => '_orderable_order_timestamp',
				'value'   => array( $due_date['start_timestamp'], $due_date['end_timestamp'] ),
				'type'    => 'numeric',
				'compare' => 'BETWEEN',
			);
		}

		$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery

		return $query_args;
	}

	/**
	 * Modify query clauses for delivery date sorting.
	 *
	 * @param array  $pieces Pieces.
	 * @param string $query Query.
	 * @param array  $args  Args.
	 */
	public static function modify_query_clauses_for_due_date_sorting( $pieces, $query, $args ) {
		// phpcs:ignore WordPress.Security.NonceVerification
		$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ?? false ) );

		if ( '_orderable_order_timestamp' !== $orderby ) {
			return $pieces;
		}

		global $wpdb;

		// phpcs:ignore WordPress.Security.NonceVerification
		$order             = sanitize_text_field( wp_unslash( $_GET['order'] ?? 'asc' ) );
		$order             = 'asc' === $order ? 'ASC' : 'DESC';
		$pieces['join']   .= " LEFT JOIN (
			select * from {$wpdb->prefix}wc_orders_meta where meta_key = '_orderable_order_timestamp'
			) meta ON {$wpdb->prefix}wc_orders.id = meta.order_id ";
		$pieces['orderby'] = "meta.meta_value {$order}";

		return $pieces;
	}

	/**
	 * Update orders query.
	 *
	 * @param WP_Query $query
	 * @param array    $args
	 */
	public static function update_query( $query, $args = array() ) {
		$defaults = array(
			'orderby'  => '',
			'due_date' => '',
		);

		$args       = wp_parse_args( $args, $defaults );
		$meta_query = (array) $query->get( 'meta_query' );

		if ( ! empty( $args['orderby'] ) && '_orderable_order_timestamp' === $args['orderby'] ) {
			$meta_query[] = array(
				'key'     => $args['orderby'],
				'compare' => 'EXISTS',
			);
		}

		if ( ! empty( $args['due_date'] ) && ! empty( $args['due_date']['start_timestamp'] ) && ! empty( $args['due_date']['end_timestamp'] ) ) {
			$meta_query[] = array(
				'key'     => '_orderable_order_timestamp',
				'value'   => array( $args['due_date']['start_timestamp'], $args['due_date']['end_timestamp'] ),
				'type'    => 'numeric',
				'compare' => 'BETWEEN',
			);
		}

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Due date filter.
	 */
	public static function due_date_filter() {
		if ( ! Orderable_Orders::is_orders_page() ) {
			return;
		}

		$due_date = self::get_filtered_due_date();
		?>
		<input type="text" name="orderable_due_date" value="<?php echo esc_attr( $due_date['formatted'] ); ?>" placeholder="<?php esc_attr_e( 'Filter by due date', 'orderable' ); ?>" style="min-width: 195px;" />
		<?php
	}

	/**
	 * Get filtered due date.
	 *
	 * @return array
	 */
	public static function get_filtered_due_date() {
		$return = array(
			'start'           => '',
			'start_timestamp' => '',
			'end'             => '',
			'end_timestamp'   => '',
			'formatted'       => '',
		);

		$due_date = empty( $_GET['orderable_due_date'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['orderable_due_date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $due_date ) {
			return $return;
		}

		$due_date_exploded = explode( ' - ', $due_date );

		$return['start']     = isset( $due_date_exploded[0] ) ? $due_date_exploded[0] : false;
		$return['end']       = isset( $due_date_exploded[1] ) ? $due_date_exploded[1] : false;
		$return['formatted'] = $due_date;

		if ( $return['start'] ) {
			$start = DateTime::createFromFormat( 'Y/m/d H:i:s', $return['start'] . ' 00:00:00', wp_timezone() );

			$return['start_timestamp'] = $start->getTimestamp();
		}

		if ( $return['end'] ) {
			$end = DateTime::createFromFormat( 'Y/m/d H:i:s', $return['end'] . ' 00:00:00', wp_timezone() );

			$return['end_timestamp'] = $end->getTimestamp();
		}

		if ( isset( $start ) && ( ! $return['end'] || $return['end'] === $return['start'] ) ) {
			$start->add( new DateInterval( 'P1D' ) );

			$return['end_timestamp'] = $start->getTimestamp();
		}

		return $return;
	}

	/**
	 * Modify preview order template.
	 */
	public static function modify_order_preview_template() {
		?>
		<# if ( data.orderable.order_date.value ) { #>
		<div class="wc-order-preview-addresses" style="padding: 0;">
			<div class="wc-order-preview-address">
				<strong>{{ data.orderable.order_date.label }}</strong>
				{{ data.orderable.order_date.value }}
			</div>
			<# if ( data.orderable.order_time.value ) { #>
			<div class="wc-order-preview-address">
				<strong>{{ data.orderable.order_time.label }}</strong>
				{{ data.orderable.order_time.value }}
			</div>
			<# } #>
		</div>
		<# } #>
		<?php
	}

	/**
	 * Modify preview order details.
	 *
	 * @param array    $data
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public static function modify_order_preview_get_order_details( $data, $order ) {
		$data['orderable'] = self::get_order_date_time( $order );

		return $data;
	}

	/**
	 * Add Due Date/Time custom column.
	 *
	 * Compatible with HPOS.
	 *
	 * @return void
	 */
	public static function add_due_date_time_custom_column() {
		$shop_order_page_screen_id = wc_get_page_screen_id( 'shop-order' );

		add_filter( "manage_{$shop_order_page_screen_id}_columns", array( __CLASS__, 'add_admin_order_columns' ), 10 );
		add_filter( 'woocommerce_shop_order_list_table_sortable_columns', array( __CLASS__, 'add_admin_order_sortable_columns' ) );

		add_action( "manage_{$shop_order_page_screen_id}_custom_column", array( __CLASS__, 'add_admin_order_columns_content' ), 10, 2 );
	}
}
