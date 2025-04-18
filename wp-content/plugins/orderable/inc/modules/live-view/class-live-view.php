<?php
/**
 * Module: Live View.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Live View module class.
 */
class Orderable_Live_View {
	/**
	 * Init.
	 */
	public static function run() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'remove_filters' ) );
		add_filter( 'heartbeat_received', array( __CLASS__, 'heartbeat_received' ), 10, 2 );
		add_action( 'admin_footer', array( __CLASS__, 'add_live_view_button' ), 100 );
		add_action( 'admin_footer', array( __CLASS__, 'embed_ding' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'output_filter_orders_nonce_field' ), 50 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'live_view_input' ), 60 );
		// HPOS Compatibility.
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( __CLASS__, 'output_filter_orders_nonce_field' ), 50 );
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( __CLASS__, 'live_view_input' ), 60 );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'init', array( __CLASS__, 'create_order_manager_role' ) );
		add_action( 'current_screen', array( __CLASS__, 'restrict_order_manager_role_access' ) );
	}

	/**
	 * Is order live view.
	 *
	 * @return bool
	 */
	public static function is_live_view() {
		return self::is_orders_page() && isset( $_GET['orderable_live_view'] );
	}

	/**
	 * Is order page.
	 *
	 * @return bool
	 */
	public static function is_orders_page() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( empty( $screen ) ) {
			return false;
		}

		// HPOS Compatibility.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return 'woocommerce_page_wc-orders' === $screen->id;
		}

		return 'edit' === $screen->base && 'shop_order' === $screen->post_type;
	}

	/**
	 * Enqueue Admin assets.
	 *
	 * @return void
	 */
	public static function admin_assets() {
		if ( ! self::is_live_view() ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// JS.
		wp_enqueue_script( 'heartbeat' );
		wp_enqueue_script( 'orderable-live-view-admin', ORDERABLE_URL . 'inc/modules/live-view/assets/admin/js/main' . $suffix . '.js', [ 'heartbeat', 'jquery' ], ORDERABLE_VERSION, true );

		$order_page_url = OrderUtil::custom_orders_table_usage_is_enabled() ? admin_url( 'admin.php?orderable_live_view&page=wc-orders' ) : admin_url( 'edit.php?orderable_live_view&post_type=shop_order' );

		$params = array(
			'last_order_id'     => self::get_last_order_id(),
			'filtered_service'  => Orderable_Services_Order::get_filtered_service(),
			'filtered_due_date' => Orderable_Timings_Order::get_filtered_due_date(),
			'orderby'           => empty( $_GET['orderby'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['orderby'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
			'url'               => $order_page_url,
		);

		wp_localize_script( 'orderable-live-view-admin', 'orderable_live_view_vars', $params );

		// CSS.
		wp_enqueue_style( 'orderable-live-view-admin', ORDERABLE_URL . 'inc/modules/live-view/assets/admin/css/live-view' . $suffix . '.css', [], ORDERABLE_VERSION );
	}

	/**
	 * Remove order list filters.
	 */
	public static function remove_filters() {
		if ( ! self::is_live_view() ) {
			return;
		}

		global $wc_list_table;

		remove_action( 'restrict_manage_posts', array( $wc_list_table, 'restrict_manage_posts' ) );
	}

	/**
	 * Add to heartbeat response.
	 *
	 * @param array $response
	 * @param array $data
	 *
	 * @return mixed
	 */
	public static function heartbeat_received( $response, $data ) {
		if ( ! isset( $data['orderable_heartbeat'] ) || 'orderable_live_view' !== $data['orderable_heartbeat'] ) {
			return $response;
		}

		$response['orderable'] = array(
			'last_order_id'    => self::get_last_order_id(),
			'filtered_service' => $data['orderable_filtered_service'],
			'due_date'         => $data['orderable_filtered_due_date'],
		);

		return $response;
	}

	/**
	 * Get last order ID.
	 *
	 * @return int
	 */
	public static function get_last_order_id() {
		add_filter(
			'woocommerce_order_data_store_cpt_get_orders_query',
			array( __CLASS__, 'add_location_id_in_order_meta_query' ),
			10,
			2
		);

		// phpcs:ignore WordPress.Security.NonceVerification
		$location_id = empty( $_POST['data']['orderable_location_id'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['data']['orderable_location_id'] ) );

		$orders = wc_get_orders(
			array(
				'limit'                 => 1,
				'orderable_location_id' => $location_id,
			)
		);

		remove_filter(
			'woocommerce_order_data_store_cpt_get_orders_query',
			array( __CLASS__, 'add_location_id_in_order_meta_query' )
		);

		if ( empty( $orders ) ) {
			return 0;
		}

		return $orders[0]->get_id();
	}

	/**
	 * Update the order meta query to filter by location ID.
	 *
	 * @param array $query      WP_Query args from WC_Order_Query.
	 * @param array $query_vars query vars from a WC_Order_Query.
	 * @return array
	 */
	public static function add_location_id_in_order_meta_query( $query, $query_vars ) {
		if ( empty( $query_vars['orderable_location_id'] ) ) {
			return $query;
		}

		$location_id = $query_vars['orderable_location_id'];

		$meta_query_args = array(
			'key' => '_orderable_location_id',
		);

		$meta_query_args['value'] = $location_id;

		$query['meta_query'][] = $meta_query_args;

		return $query;
	}

	/**
	 * Embed ding sound.
	 */
	public static function embed_ding() {
		if ( ! self::is_live_view() ) {
			return;
		}

		/**
		 * Allows to override the notification audio file.
		 */
		$audio_url = apply_filters( 'orderable_live_view_new_order_audio_file_url', esc_url( ORDERABLE_URL ) . 'inc/modules/live-view/assets/audio/ding.wav' );
		?>
		<audio id="orderable_ding" src="<?php echo esc_attr( $audio_url ); ?>" type="audio/wav"></audio>
		<?php
	}

	/**
	 * Add live_view button.
	 */
	public static function add_live_view_button() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen                    = get_current_screen();
		$shop_order_page_screen_id = OrderUtil::custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'edit-shop_order';

		if ( $shop_order_page_screen_id !== $screen->id ) {
			return;
		}

		$enable_button  = sprintf( '<a href="%s" class="page-title-action orderable-live-view-button orderable-live-view-button--enable">%s</a>', admin_url( 'edit.php?post_type=shop_order&orderable_live_view' ), __( 'Enable Live View', 'orderable' ) );
		$disable_button = '';
		$enable_audio   = '';

		if ( self::is_live_view() ) {
			$disable_button = sprintf( '<a href="%s" class="page-title-action orderable-live-view-button orderable-live-view-button--disable orderable-live-view-button--margin-left-6">%s</a>', admin_url( 'edit.php?post_type=shop_order' ), __( 'Exit Live View', 'orderable' ) );
			$enable_audio   = sprintf( '<button class="page-title-action orderable-live-view-button orderable-live-view-button--audio orderable-live-view-button--margin-left-6" data-orderable-alt-text="%1$s" data-orderable-mute-status="0">%2$s</a>', __( 'Unmute', 'orderable' ), __( 'Mute', 'orderable' ) );
		}
		?>
		<script>
			jQuery( document ).ready( function() {
				var $add_new_button = jQuery( '.page-title-action' );

				if ( $add_new_button.length <= 0 ) {
					return;
				}

				$add_new_button.after( '<?php echo $enable_audio; ?>' ).after( '<?php echo $disable_button; ?>' ).after( '<?php echo $enable_button; ?>' );
			} );
		</script>
		<?php
	}

	/**
	 * Add hidden input so filtering maintains live view.
	 */
	public static function live_view_input() {
		if ( ! self::is_live_view() ) {
			return;
		}
		?>
		<input type="hidden" name="orderable_live_view" value="1" />
		<?php
	}

	/**
	 * Add settings page.
	 */
	public static function add_settings_page() {
		add_submenu_page( 'orderable', __( 'Live Order View', 'orderable' ), __( 'Live Order View', 'orderable' ), 'manage_shop_order_terms', 'edit.php?post_type=shop_order&orderable_live_view', '', 1 );
	}

	/**
	 * Add new user role
	 */
	public static function create_order_manager_role() {
		// Capabilities for order manager role.
		$capabilities = array(
			'edit_posts'                   => true,
			'view_admin_dashboard'         => true,
			'read'                         => true,
			'edit_shop_order'              => true,
			'read_shop_order'              => true,
			'delete_shop_order'            => true,
			'edit_shop_orders'             => true,
			'edit_others_shop_orders'      => true,
			'publish_shop_orders'          => true,
			'read_private_shop_orders'     => true,
			'delete_shop_orders'           => true,
			'delete_private_shop_orders'   => true,
			'delete_published_shop_orders' => true,
			'delete_others_shop_orders'    => true,
			'edit_private_shop_orders'     => true,
			'edit_published_shop_orders'   => true,
			'manage_shop_order_terms'      => true,
			'edit_shop_order_terms'        => true,
			'delete_shop_order_terms'      => true,
			'assign_shop_order_terms'      => true,
		);

		$role         = get_role( 'order_manager' );
		$removed_role = false;

		// Ensure that we remove the legacy role that does not
		// include the new `edit_posts` capability.
		if ( $role && ! $role->has_cap( 'edit_posts' ) ) {
			remove_role( 'order_manager' );
			$removed_role = true;
		}

		if ( ! $role || $removed_role ) {
			add_role( __( 'order_manager', 'orderable' ), __( 'Order Manager', 'orderable' ), $capabilities );
		}
	}

	/**
	 * Restrict access to parts of the admin dashboard that
	 * the `edit_posts` capability makes available.
	 *
	 * @return void
	 */
	public static function restrict_order_manager_role_access() {
		global $pagenow, $current_screen;

		$userdata = get_userdata( get_current_user_id() );

		if ( ! $userdata || ! is_array( $userdata->roles ) || ! in_array( 'order_manager', $userdata->roles, true ) ) {
			return;
		}

		// Remove access to menu pages made visible by the `edit_posts` cap.
		remove_menu_page( 'edit.php' );
		remove_menu_page( 'edit-comments.php' );

		$order_page_url = OrderUtil::custom_orders_table_usage_is_enabled() ? admin_url( 'admin.php?page=wc-orders' ) : admin_url( 'edit.php?post_type=shop_order' );

		if (
			'edit-post' === $current_screen->id ||
			'edit-comments' === $current_screen->id
			) {
			wp_safe_redirect( $order_page_url );
			exit;
		}

		if ( OrderUtil::custom_orders_table_usage_is_enabled() && 'post-new.php' === $pagenow ) {
			wp_safe_redirect( $order_page_url );
			exit;
		}

		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() && 'post-new.php' === $pagenow && 'shop_order' !== $current_screen->id ) {
			wp_safe_redirect( $order_page_url );
			exit;
		}
	}

	/**
	 * Output nonce field with the action `orderable_filter_orders`.
	 *
	 * @return void
	 */
	public static function output_filter_orders_nonce_field() {
		wp_nonce_field( 'orderable_filter_orders', '_orderable_filter_orders_nonce' );
	}
}
