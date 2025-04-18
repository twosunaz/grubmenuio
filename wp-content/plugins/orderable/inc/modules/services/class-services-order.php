<?php
/**
 * Module: Services order.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Services order class.
 */
class Orderable_Services_Order {
	/**
	 * Init.
	 */
	public static function run() {
		add_action( 'restrict_manage_posts', array( __CLASS__, 'services_filter' ), 50 );
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( __CLASS__, 'services_filter' ), 50 );
		add_action( 'pre_get_posts', array( __CLASS__, 'update_query_to_filter_admin_orders' ), 100 );
		add_filter( 'woocommerce_shop_order_list_table_prepare_items_query_args', array( __CLASS__, 'update_query_args_to_filter_admin_orders' ), 100 );
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_admin_order_columns' ), 10 );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'add_admin_order_columns_content' ), 10, 2 );
		add_filter( 'orderable_get_order_date_time', array( __CLASS__, 'modify_order_date_time_labels' ), 10, 2 );
		add_action( 'woocommerce_before_order_object_save', array( __CLASS__, 'before_save_order' ), 10, 2 );

		// HPOS Compatibility.
		add_action( 'admin_init', array( __CLASS__, 'add_service_custom_column' ) );
	}

	/**
	 * Services filter.
	 */
	public static function services_filter() {
		$service = self::get_filtered_service();

		$options = [
			'All services',
			'delivery',
			'pickup',
		];

		/**
		 * Filter the services filter options.
		 *
		 * @since 1.14.0
		 * @hook orderable_services_filter_options
		 * @param  array  $options          The options.
		 * @param  string $filtered_service The filtered service.
		 * @return array New value
		 */
		$options = apply_filters( 'orderable_services_filter_options', $options, $service );

		if ( ! is_array( $options ) ) {
			$options = [];
		}

		?>
		<select name="orderable_service">
			<?php foreach ( $options as $option ) : ?>
				<option
					value="<?php echo 'All services' === $option ? '' : esc_attr( $option ); ?>"
					<?php selected( $service, $option ); ?>
				>
					<?php echo esc_html( Orderable_Services::get_service_label( $option ) ? Orderable_Services::get_service_label( $option ) : $option ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Get filtered service.
	 *
	 * @return mixed
	 */
	public static function get_filtered_service() {
		// phpcs:ignore WordPress.Security.NonceVerification
		$service = empty( $_GET['orderable_service'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['orderable_service'] ) );

		if ( ! $service ) {
			return '';
		}

		return $service;
	}

	/**
	 * Update the query object to filter orders.
	 *
	 * @param WP_Query $query The query to retrieve the orders.
	 */
	public static function update_query_to_filter_admin_orders( $query ) {
		if ( ! Orderable_Orders::is_orders_page() || 'shop_order' !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = (array) $query->get( 'meta_query' );
		$service    = self::get_filtered_service();

		if ( ! empty( $service ) ) {
			$meta_query[] = array(
				'key'   => '_orderable_service_type',
				'value' => $service,
			);
		}

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Update the query args to filter orders.
	 *
	 * @param array $query_args The query args used to retrieve the orders.
	 * @return array
	 */
	public static function update_query_args_to_filter_admin_orders( $query_args ) {
		if ( ! Orderable_Orders::is_orders_page() ) {
			return $query_args;
		}

		$meta_query = empty( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ? array() : $query_args['meta_query'];
		$service    = self::get_filtered_service();

		if ( empty( $service ) ) {
			return $query_args;
		}

		$meta_query[] = array(
			'key'   => '_orderable_service_type',
			'value' => $service,
		);

		$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery

		return $query_args;
	}

	/**
	 * Get service type for an order.
	 *
	 * @param WC_Order $order
	 *
	 * @return bool|string
	 */
	public static function get_service_type( $order ) {
		$shipping_methods = $order->get_shipping_methods();
		$type             = false;

		if ( empty( $shipping_methods ) ) {
			return apply_filters( 'orderable_get_service_type', $type, $order );
		}

		$shipping_method    = array_shift( $shipping_methods );
		$shipping_method_id = $shipping_method->get_method_id();

		$type = Orderable_Services::is_pickup_method( $shipping_method_id ) ? 'pickup' : 'delivery';

		return apply_filters( 'orderable_get_service_type', $type, $order );
	}

	/**
	 * Add columns to admin orders screen.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public static function add_admin_order_columns( $columns ) {
		$columns['orderable_service_type'] = __( 'Service', 'orderable' );

		return $columns;
	}

	/**
	 * Add columns content to admin orders screen.
	 *
	 * @param $column_name
	 * @param $post_id
	 */
	public static function add_admin_order_columns_content( $column_name, $post_id ) {
		if ( 'orderable_service_type' === $column_name ) {
			$order = wc_get_order( $post_id );
			$type  = self::get_service_type( $order );

			if ( empty( $type ) ) {
				echo '&mdash;';

				return;
			}

			$background = 'pickup' === $type ? '#C6C7E1' : '#c5e2df';
			$color      = 'pickup' === $type ? '#5457a0' : '#356964';

			$background = apply_filters( 'orderable_service_type_column_background_color', $background, $column_name, $post_id, $order, $type );
			$color      = apply_filters( 'orderable_service_type_column_text_color', $color, $column_name, $post_id, $order, $type );

			printf( '<mark class="order-status order-status--%s" style="background-color: %s; color: %s;"><span>%s</span></mark>', esc_attr( $type ), esc_attr( $background ), esc_attr( $color ), Orderable_Services::get_service_label( $type ) );
		}
	}

	/**
	 * Modify order date/time labels.
	 *
	 * @param array    $labels
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public static function modify_order_date_time_labels( $labels, $order ) {
		$type       = self::get_service_type( $order );
		$type_label = Orderable_Services::get_service_label( $type );

		/* translators: 1: service name; 2: date label. E.g.: "Pickup Date", "Delivery Date" */
		$labels['order_date']['label'] = sprintf( _x( '%1$s %2$s', 'Order date', 'orderable' ), $type_label, $labels['order_date']['label'] );
		/* translators: 1: service name; 2: time label. E.g.: "Pickup Time", "Delivery Time" */
		$labels['order_time']['label'] = sprintf( _x( '%1$s %2$s', 'Order time', 'orderable' ), $type_label, $labels['order_time']['label'] );

		return $labels;
	}

	/**
	 * Save order meta.
	 *
	 * @param WC_Abstract_Order $abstract_order
	 * @param WC_Data_Store     $data_store
	 *
	 * @return void
	 */
	public static function before_save_order( $abstract_order, $data_store ) {
		if ( empty( $abstract_order ) ) {
			return;
		}

		$type = self::get_service_type( $abstract_order );

		$abstract_order->update_meta_data( '_orderable_service_type', $type );
	}

	/**
	 * Add Service custom column.
	 *
	 * Compatible with HPOS.
	 *
	 * @return void
	 */
	public static function add_service_custom_column() {
		$shop_order_page_screen_id = wc_get_page_screen_id( 'shop-order' );

		add_filter( "manage_{$shop_order_page_screen_id}_columns", array( __CLASS__, 'add_admin_order_columns' ), 10 );
		add_action( "manage_{$shop_order_page_screen_id}_custom_column", array( __CLASS__, 'add_admin_order_columns_content' ), 10, 2 );
	}
}
