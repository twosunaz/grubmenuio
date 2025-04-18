<?php
/**
 * Module: Receipt Layouts.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\ReceiptRendering\ReceiptRenderingEngine;
use Automattic\WooCommerce\Internal\TransientFiles\TransientFilesEngine;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Checkout module class.
 */
class Orderable_Receipt_Layouts {
	/**
	 * Post type key.
	 *
	 * @var string
	 */
	protected static $post_type_key = 'orderable_receipt';

	/**
	 * Init.
	 */
	public static function run() {
		if ( self::should_disable_module() ) {
			return;
		}

		add_action( 'init', [ __CLASS__, 'register_blocks' ] );
		add_action( 'init', [ __CLASS__, 'register_post_type' ] );
		add_action( 'admin_init', [ __CLASS__, 'should_create_default_receipt_layouts' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'dequeue_assets_from_block_editor' ], 45 );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'enqueue_block_editor_assets' ], 50 );
		add_action( 'current_screen', [ __CLASS__, 'register_block_patterns' ], 15 );
		add_action( 'admin_action_duplicate_' . self::$post_type_key, [ __CLASS__, 'handle_duplicate_action' ], 10 );
		add_action( 'current_screen', [ __CLASS__, 'update_theme_support' ] );
		add_action( 'admin_print_footer_scripts-woocommerce_page_wc-orders', [ __CLASS__, 'add_print_order_buttons_to_hpos_edit_order_page' ] );
		add_action( 'admin_print_footer_scripts-post.php', [ __CLASS__, 'add_print_order_buttons_to_edit_order_page' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets_to_orders_page' ] );

		add_filter( 'block_categories_all', [ __CLASS__, 'add_orderable_block_category' ], 10, 2 );
		add_filter( 'allowed_block_types_all', [ __CLASS__, 'get_allowed_blocks' ], 10, 2 );
		add_filter( 'rest_dispatch_request', [ __CLASS__, 'maybe_apply_receipt_layout' ], 10, 3 );
		add_filter( 'should_load_remote_block_patterns', [ __CLASS__, 'skip_remote_block_patterns' ], 15 );
		add_filter( 'post_row_actions', [ __CLASS__, 'add_duplicate_row_action_link' ], 10, 2 );
		add_filter( 'woocommerce_admin_order_preview_get_order_details', [ __CLASS__, 'add_print_order_button_to_preview_order_details' ] );
		add_filter( 'wpsf_register_settings_orderable', [ __CLASS__, 'add_settings' ] );
	}

	/**
	 * Whether the Receipt Layouts module should be disabled.
	 *
	 * @return boolean
	 */
	protected static function should_disable_module() {
		$disable = class_exists( 'Zprint\Setup' ) || Orderable_Helpers::woocommerce_version_compare( '8.7', '<' );
		/**
		 * Filter whether the Receipt Layouts module should be disabled.
		 *
		 * @since 1.18.0
		 * @hook orderable_disable_receipt_layouts_module
		 * @param  bool $disable The value to disable the Receipt Layouts module.
		 * @return bool
		 */
		return apply_filters( 'orderable_disable_receipt_layouts_module', $disable );
	}

	/**
	 * Get the layout ID defined in Printing settings.
	 *
	 * @return int|null
	 */
	protected static function get_layout_id() {
		$layout_id = absint( Orderable_Settings::get_setting( 'printing_printing_settings_default_printing_layout' ) );

		if ( self::layout_exists( $layout_id ) ) {
			return $layout_id;
		}

		$receipt_layout = self::get_last_receipt_layout();

		if ( ! $receipt_layout ) {
			return null;
		}

		return $receipt_layout->ID;

	}

	/**
	 * Check if layout exists
	 *
	 * @param int $id The layout ID.
	 * @return bool
	 */
	protected static function layout_exists( $id ) {
		if ( empty( $id ) ) {
			return false;
		}

		$receipt_layout = self::get_receipt_layout( $id );

		return ! empty( $receipt_layout );
	}

	/**
	 * Get the rendered receipt layout.
	 *
	 * @param int $layout_id The receipt layout ID.
	 * @return string|false
	 */
	protected static function get_layout( $layout_id ) {
		$orderable_layout = get_post( $layout_id );

		if ( empty( $orderable_layout ) ) {
			return false;
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks
		$rendered_content = apply_filters( 'the_content', $orderable_layout->post_content );

		if ( empty( $rendered_content ) || ! is_string( $rendered_content ) ) {
			return false;
		}

		return trim( $rendered_content );
	}

	/**
	 * Apply Receipt Layout
	 *
	 * @param WC_Order $order The order.
	 * @param string   $layout The layout content.      $layout_id The receipt layout ID.
	 * @return string
	 */
	protected static function apply_layout( $order, $layout ) {
		ob_start();
		$css = include __DIR__ . '/templates/order-receipt-css.php';
		$css = ob_get_contents();
		ob_end_clean();

		/**
		 * Filter the CSS to be used in the receipt layout template.
		 *
		 * @since 1.16.0
		 * @hook orderable_receipt_layouts_template_css
		 * @param  string                         $css   The CSS used in the receipt layout template.
		 * @param  WC_Order|WC_Order_Refund|false $order The order.
		 * @return string New value
		 */
		$data['css']     = apply_filters( 'orderable_receipt_layouts_template_css', $css, $order );
		$data['content'] = $layout;

		ob_start();
		?>
		<html>
			<head>
				<meta
					http-equiv="Content-Type"
					content="<?php bloginfo( 'html_type' ); ?>; charset=<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>"
				/>
				<style>
					<?php echo $data['css']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</style>

				<script>
					window.print();
				</script>
			</head>

			<body>
				<?php
					/**
					 * Fires before receipt layout content.
					 *
					 * @since 1.16.0
					 * @hook orderable_receipt_layouts_before_template_content
					 * @param  WC_Order|WC_Order_Refund|false $order The order.
					 */
					do_action( 'orderable_receipt_layouts_before_template_content', $order );

					echo $data['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

					/**
					 * Fires after receipt layout content.
					 *
					 * @since 1.16.0
					 * @hook orderable_receipt_layouts_before_template_content
					 * @param  WC_Order|WC_Order_Refund|false $order The order.
					 */
					do_action( 'orderable_receipt_layouts_after_template_content', $order );
				?>
			</body>
		</html>
		<?php

		return ob_get_clean();
	}

	/**
	 * Register blocks to be used in the Receipt Layouts custom post type.
	 *
	 * @return void
	 */
	public static function register_blocks() {
		$args = [
			'supports' => [
				'html'       => false,
				'align'      => false,
				'spacing'    => [
					'padding' => true,
					'margin'  => true,
				],
				'color'      => [
					'text' => true,
				],
				'typography' => [
					'textAlign' => true,
				],
			],
		];

		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/customer-billing-details/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/customer-name/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/customer-shipping-details/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/divider/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-date-time/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-line-items/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-location/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-meta-fields/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-notes/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-number/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-payment-method/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-service-date-time/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-service-type/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-table/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-total-items/build', $args );
		register_block_type( ORDERABLE_MODULES_PATH . 'receipt-layouts/blocks/order-totals/build', $args );
	}

	/**
	 * Get the order to be used in the receipt layout based on the
	 * `orderable_layout_id` parameter passed in the GET request.
	 *
	 * @return WC_Order|WC_Order_Refund|null
	 */
	public static function get_order() {
		switch ( true ) {
			case wp_is_rest_endpoint():
				$order_id = self::get_order_id_from_rest_endpoint();
				break;

			case wp_doing_ajax():
				$order_id = self::get_order_id_from_ajax();
				break;

			default:
				$order_id = self::get_order_id_from_edit_page();
				break;
		}

		if ( ! $order_id ) {
			return null;
		}

		return wc_get_order( $order_id );
	}

	/**
	 * Get the order ID from REST endpoint request.
	 *
	 * @return int|null
	 */
	protected static function get_order_id_from_rest_endpoint() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? false ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		parse_str( $_SERVER['QUERY_STRING'] ?? '', $request_query_string );

		if ( empty( $request_query_string['orderable_layout_id'] ) ) {
			return null;
		}

		/**
		 * Try to catch a pattern like `/wc/v3/orders/1005/receipt`
		 * to make sure we are intercepting the correct endpoint
		 */
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( 1 !== preg_match( '#.*/wc/v3/orders/(?P<id>[\d]+)/receipt\W.*#', $_SERVER['REQUEST_URI'] ?? '', $matches ) ) {
			return null;
		}

		$order_id = $matches['id'] ?? null;

		if ( empty( $order_id ) ) {
			return null;
		}

		return absint( $order_id );
	}

	/**
	 * Get the order ID from REST endpoint request.
	 *
	 * @return int|null
	 */
	protected static function get_order_id_from_ajax() {
		$allowed_actions = [
			'woocommerce_get_order_details',
		];

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_GET['action'] ) || empty( $_GET['order_id'] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

		if ( ! in_array( $action, $allowed_actions, true ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$order_id = sanitize_text_field( wp_unslash( $_GET['order_id'] ) );

		if ( empty( $order_id ) ) {
			return null;
		}

		return absint( $order_id );
	}

	/**
	 * Get the order ID from edit Order page.
	 *
	 * @return int|null
	 */
	protected static function get_order_id_from_edit_page() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( empty( $screen ) ) {
			return null;
		}

		if ( OrderUtil::custom_orders_table_usage_is_enabled() && 'woocommerce_page_wc-orders' !== $screen->id ) {
			return null;
		}

		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() && 'shop_order' !== $screen->post_type ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_GET['action'] ) || ( empty( $_GET['id'] ) && empty( $_GET['post'] ) ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

		if ( 'edit' !== $action ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$order_id = absint( wp_unslash( $_GET['id'] ?? $_GET['post'] ?? 0 ) );

		return $order_id;
	}

	/**
	 * Get the Receipt block wrapper attributes.
	 *
	 * By default, the class `'wp-block-orderable-receipt-layouts` is added
	 * to all Receipt blocks.
	 *
	 * @return string
	 */
	public static function get_receipt_block_wrapper_attributes() {
		return get_block_wrapper_attributes( [ 'class' => 'wp-block-orderable-receipt-layouts' ] );
	}

	/**
	 * Get allowed blocks for `orderable_receipt` post type.
	 *
	 * @param bool|string[]           $allowed_block_types  Array of block type slugs, or boolean to enable/disable all.
	 * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
	 * @return bool|string[]
	 */
	public static function get_allowed_blocks( $allowed_block_types, $block_editor_context ) {
		$receipt_layouts_blocks = [
			'orderable/customer-billing-details',
			'orderable/customer-name',
			'orderable/customer-shipping-details',
			'orderable/divider',
			'orderable/order-date-time',
			'orderable/order-line-items',
			'orderable/order-location',
			'orderable/order-meta-fields',
			'orderable/order-notes',
			'orderable/order-number',
			'orderable/order-payment-method',
			'orderable/order-service-date-time',
			'orderable/order-service-type',
			'orderable/order-table',
			'orderable/order-total-items',
			'orderable/order-totals',
		];

		if ( self::$post_type_key === ( $block_editor_context->post->post_type ?? false ) ) {
			$additional_blocks = [
				'core/paragraph',
				'core/spacer',
				'core/columns',
				'core/column',
				'core/heading',
				'core/table',
				'core/image',
			];

			return array_merge( $receipt_layouts_blocks, $additional_blocks );
		}

		if ( empty( $allowed_block_types ) ) {
			return $allowed_block_types;
		}

		if ( is_array( $allowed_block_types ) ) {
			// Remove the Receipt Layouts blocks
			return array_values(
				array_diff(
					$allowed_block_types,
					$receipt_layouts_blocks
				)
			);
		}

		$registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
		$all_block_types   = array_keys( $registered_blocks );

		// Remove the Receipt Layouts blocks
		return array_values(
			array_diff(
				$all_block_types,
				$receipt_layouts_blocks
			)
		);
	}

	/**
	 * Register Receipt Layouts post type.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		$labels = Orderable_Helpers::prepare_post_type_labels(
			array(
				'plural'   => __( 'Receipt Layouts', 'orderable' ),
				'singular' => __( 'Receipt Layout', 'orderable' ),
			)
		);

		$args = [
			'labels'              => $labels,
			'supports'            => [ 'title', 'editor' ],
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'orderable',
			'menu_position'       => 55,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_in_rest'        => true,
			'capability_type'     => 'post',
		];

		register_post_type( self::$post_type_key, $args );
	}

	/**
	 * Check whether the current page is the block editor
	 *
	 * @param WP_Screee|null $current_screen The current screen.
	 * @return boolean
	 */
	protected static function is_orderable_receipt_block_editor_page( $current_screen = null ) {
		$current_screen = is_a( $current_screen, 'WP_Screen' ) ? $current_screen : get_current_screen();

		if ( ! $current_screen ) {
			return false;
		}

		if ( ! $current_screen->is_block_editor() ) {
			return false;
		}

		if ( self::$post_type_key !== $current_screen->post_type ) {
			return false;
		}

		return true;
	}

	/**
	 * Dequeue assets from block editor.
	 *
	 * Since some 3rd-party assets can conflict
	 * with Orderable Layout styles, this function tries
	 * to remove them and keep only the core assets.
	 *
	 * @return void
	 */
	public static function dequeue_assets_from_block_editor() {
		if ( ! self::is_orderable_receipt_block_editor_page() ) {
			return;
		}

		global $wp_styles, $wp_scripts;

		remove_editor_styles();

		foreach ( $wp_styles->queue as $handle ) {
			$allowed_handles = [
				'admin-bar',
				'media-views',
				'imgareaselect',
				'buttons',
				'editor-buttons',
				'wp-edit-post',
				'wp-block-directory',
				'wp-format-library',
			];

			if ( in_array( $handle, $allowed_handles, true ) ) {
				continue;
			}

			wp_dequeue_style( $handle );
		}

		foreach ( $wp_scripts->queue as $handle ) {
			$allowed_handles = [
				'common',
				'admin-bar',
				'heartbeat',
				'wp-edit-post',
				'media-editor',
				'media-audiovideo',
				'mce-view',
				'image-edit',
				'editor',
				'quicktags',
				'wplink',
				'jquery-ui-autocomplete',
				'media-upload',
				'wp-block-styles',
				'wp-block-directory',
				'wp-format-library',
			];

			if ( in_array( $handle, $allowed_handles, true ) ) {
				continue;
			}

			wp_dequeue_script( $handle );
		}
	}

	/**
	 * Enqueue block editor assets for `orderable_receipt` post type.
	 *
	 * @return void
	 */
	public static function enqueue_block_editor_assets() {
		if ( ! self::is_orderable_receipt_block_editor_page() ) {
			return;
		}

		$suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$suffix_css = ( is_rtl() ? '-rtl' : '' ) . $suffix;

		wp_enqueue_style(
			'orderable-receipt-admin',
			ORDERABLE_URL . 'inc/modules/receipt-layouts/assets/admin/css/block-editor/receipt-layouts-block-editor' . $suffix_css . '.css',
			[],
			ORDERABLE_VERSION
		);

		wp_enqueue_script(
			'orderable-receipt-admin',
			ORDERABLE_URL . 'inc/modules/receipt-layouts/assets/admin/js/block-editor/main' . $suffix_css . '.js',
			[ 'wp-hooks' ],
			ORDERABLE_VERSION,
			true
		);
	}

	/**
	 * Intercept the receipt geneartion and maybe apply the Receipt layout.
	 *
	 * @param mixed           $dispatch_result Dispatch result, will be used if not empty.
	 * @param WP_REST_Request $request         Request used to generate the response.
	 * @param string          $route           Route matched for the request.
	 * @return mixed|array
	 */
	public static function maybe_apply_receipt_layout( $dispatch_result, WP_REST_Request $request, $route ) {
		if ( '/wc/v3/orders/(?P<id>[\d]+)/receipt' !== $route ) {
			return $dispatch_result;
		}

		$orderable_layout_id = $request->get_param( 'orderable_layout_id' );

		if ( ! $orderable_layout_id ) {
			return $dispatch_result;
		}

		$order_id = $request->get_param( 'id' );

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return $dispatch_result;
		}

		if ( ! $request->get_param( 'force_new' ) ) {
			$existing_receipt_filename = wc_get_container()->get( ReceiptRenderingEngine::class )->get_existing_receipt( $order );

			if ( ! is_null( $existing_receipt_filename ) ) {
				return $existing_receipt_filename;
			}
		}

		$expiration_date =
			$request->get_param( 'expiration_date' ) ??
			gmdate( 'Y-m-d', strtotime( "+{$request->get_param('expiration_days')} days" ) );

		$layout = self::get_layout( $orderable_layout_id );

		if ( ! $layout ) {
			return null;
		}

		$rendered_template = self::apply_layout( $order, $layout );

		if ( empty( $rendered_template ) ) {
			return $dispatch_result;
		}

		$file_name = wc_get_container()->get( TransientFilesEngine::class )->create_transient_file( $rendered_template, $expiration_date );

		$order->update_meta_data( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY, $file_name );
		$order->save_meta_data();

		if ( is_null( $file_name ) ) {
			return new WP_Error( 'woocommerce_rest_not_found', __( 'Order not found', 'woocommerce' ), [ 'status' => 404 ] );
		}

		$expiration_date = TransientFilesEngine::get_expiration_date( $file_name );
		$public_url      = wc_get_container()->get( TransientFilesEngine::class )->get_public_url( $file_name );

		return [
			'receipt_url'     => $public_url,
			'expiration_date' => $expiration_date,
		];
	}

	/**
	 * Get rendered receipt layout public URL
	 *
	 * @param int|WC_Order $order The order.
	 * @param int          $layout_id The layout ID.
	 * @return string|null
	 */
	public static function get_public_url( $order, $layout_id ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return null;
		}

		$layout = self::get_layout( $layout_id );

		if ( ! $layout ) {
			return null;
		}

		$rendered_layout = self::apply_layout( $order, $layout );

		if ( ! $rendered_layout ) {
			return null;
		}

		$expiration = new DateTime( 'now', wp_timezone() );
		$expiration->modify( '+3 hours' );

		$file_name = wc_get_container()->get( TransientFilesEngine::class )->create_transient_file( $rendered_layout, $expiration->getTimestamp() );

		$order->update_meta_data( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY, $file_name );
		$order->save_meta_data();

		if ( is_null( $file_name ) ) {
			return null;
		}

		return wc_get_container()->get( TransientFilesEngine::class )->get_public_url( $file_name );
	}

	/**
	 * Add `Orderable` block category
	 *
	 * @param array[]                 $block_categories     Array of categories for block types.
	 * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
	 * @return array[]
	 */
	public static function add_orderable_block_category( $block_categories, $block_editor_context ) {
		if ( self::$post_type_key !== ( $block_editor_context->post->post_type ?? false ) ) {
			return $block_categories;
		}

		if ( ! empty( $block_categories['orderable'] ) ) {
			return $block_categories;
		}

		$block_categories[] = [
			'slug'  => 'orderable',
			'title' => __( 'Orderable', 'orderable' ),
		];

		return $block_categories;
	}

	/**
	 * Skip remote block patterns for `orderable_receipt` custom post type.
	 *
	 * @param bool $should_load_remote Wehther should load remote block patterns.
	 * @return bool
	 */
	public static function skip_remote_block_patterns( $should_load_remote ) {
		$http_referer = wp_get_referer();

		if ( ! $http_referer ) {
			return $should_load_remote;
		}

		$query = wp_parse_url( $http_referer, PHP_URL_QUERY );

		if ( ! $query ) {
			return $should_load_remote;
		}

		wp_parse_str( $query, $result );

		$post_id   = absint( $result['post'] ?? 0 );
		$action    = $result['action'] ?? '';
		$post_type = $result['post_type'] ?? '';

		if ( ( empty( $post_id ) || 'edit' !== $action ) && ( self::$post_type_key !== $post_type ) ) {
			return $should_load_remote;
		}

		if ( ! empty( $post_id ) && self::$post_type_key !== get_post_type( $post_id ) ) {
			return $should_load_remote;
		}

		return false;
	}

	protected static function get_block_patterns() {
		$block_patterns = [];

		$pattern_files = glob( ORDERABLE_MODULES_PATH . '/receipt-layouts/patterns/*.php' );

		if ( ! $pattern_files ) {
			return $block_patterns;
		}

		$default_pattern_properties = [
			'postTypes'  => [ self::$post_type_key ],
			'categories' => [ 'orderable/receipt-layouts' ],
		];

		foreach ( $pattern_files as $pattern_file ) {
			$pattern_data = get_file_data(
				$pattern_file,
				[
					'title'       => 'Title',
					'description' => 'Description',
					'slug'        => 'Slug',
					'categories'  => 'Categories',
				]
			);

			if ( empty( $pattern_data['title'] ) || empty( $pattern_data['slug'] ) ) {
				continue;
			}

			$pattern_properties = wp_parse_args(
				array_filter( $pattern_data ),
				$default_pattern_properties
			);

			if ( ! file_exists( $pattern_file ) ) {
				continue;
			}

			ob_start();

			include $pattern_file;
			$pattern_properties['content'] = ob_get_clean();

			$block_patterns[] = $pattern_properties;
		}

		return $block_patterns;
	}

	/**
	 * Register `orderable_receipt` block patterns.
	 *
	 * @return void
	 */
	public static function register_block_patterns() {
		$screen = get_current_screen();

		if ( self::$post_type_key !== $screen->post_type || ! $screen->is_block_editor() ) {
			return;
		}

		$registered_patterns          = WP_Block_Patterns_Registry::get_instance()->get_all_registered() ?? [];
		$registered_category_patterns = WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered() ?? [];

		foreach ( $registered_patterns as $pattern_data ) {
			unregister_block_pattern( $pattern_data['name'] );
		}

		foreach ( $registered_category_patterns as $category_pattern_data ) {
			unregister_block_pattern_category( $category_pattern_data['name'] );
		}

		register_block_pattern_category(
			'orderable/receipt-layouts',
			[
				'label' => __( 'Orderable Receipt Layouts', 'orderable' ),
			]
		);

		foreach ( self::get_block_patterns() as $block_pattern ) {
			register_block_pattern( $block_pattern['slug'], $block_pattern );
		}
	}

	/**
	 * Add `Duplicate` action
	 *
	 * @param string[] $actions An array of row action links.
	 * @param WP_Post  $post    The post object
	 * @return string[]
	 */
	public static function add_duplicate_row_action_link( $actions, $post ) {
		if ( self::$post_type_key !== $post->post_type ) {
			return $actions;
		}

		$post_type_object = get_post_type_object( self::$post_type_key );

		if ( empty( $post_type_object ) ) {
			return $actions;
		}

		$url_to_duplicate = wp_nonce_url(
			admin_url(
				sprintf(
					'edit.php?post_type=%1$s&action=duplicate_%1$s&amp;post=%2$d',
					self::$post_type_key,
					$post->ID
				)
			),
			'orderable_duplicate_' . self::$post_type_key . '_' . $post->ID
		);

		$actions['duplicate'] = sprintf(
			'<a href="%s" aria-label="%s" rel="permalink">%s</a>',
			$url_to_duplicate,
			// translators: %s - singular name of the Receipt Layout type.
			sprintf( __( 'Make a duplicate from this %s' ), $post_type_object->labels->singular_name ),
			__( 'Duplicate', 'iconic-wsb' )
		);

		return $actions;
	}

	/**
	 * Handle the action to duplicate the receipt layout.
	 *
	 * @return void
	 */
	public static function handle_duplicate_action() {
		$post_type_object = get_post_type_object( self::$post_type_key );

		if ( empty( $post_type_object ) ) {
			wp_die( esc_html__( "It's not possible to duplicate this receipt layout", 'orderable' ) );
		}

		if ( empty( $_REQUEST['post'] ) ) {
			wp_die(
				sprintf(
					// translators: %s - Receipt Layout type.
					esc_html__( 'No %s to duplicate has been supplied!', 'orderable' ),
					esc_html( $post_type_object->labels->singular_name )
				)
			);
		}

		$receipt_layout_id = absint( $_REQUEST['post'] );

		check_admin_referer( 'orderable_duplicate_' . self::$post_type_key . '_' . $receipt_layout_id );

		$receipt_layout = self::get_receipt_layout( $receipt_layout_id, [ 'post_status' => 'any' ] );

		if ( ! $receipt_layout ) {
			wp_die(
				sprintf(
					/* translators: %1$s: Receipt Layout type; %2$d: Receipt Layout ID*/
					esc_html__( '%1$s creation failed, could not find original post: %2$d', 'orderable' ),
					esc_html( $post_type_object->labels->singular_name ),
					esc_html( $receipt_layout_id )
				)
			);
		}

		$duplicated_post_args = [
			/* translators: %s contains the name of the original post. */
			'post_title'   => sprintf( esc_html__( '%s (Copy)', 'orderable' ), get_the_title( $receipt_layout_id ) ),
			'post_content' => get_the_content( null, false, $receipt_layout_id ),
			'post_type'    => self::$post_type_key,
		];

		$duplicated_post_id = wp_insert_post( $duplicated_post_args );

		// it can hold WP_Error @phpstan-ignore function.impossibleType
		if ( empty( $duplicated_post_id ) || is_wp_error( $duplicated_post_id ) ) {
			wp_die(
				sprintf(
					// translators: %s - Receipt Layout type.
					esc_html__( '%s creation failed', 'orderable' ),
					esc_html( $post_type_object->labels->singular_name )
				)
			);
		}

		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $duplicated_post_id ) );
		exit;
	}

	/**
	 * Update the theme support.
	 *
	 * @param WP_Screen $current_screen Current WP_Screen object.
	 * @return void
	 */
	public static function update_theme_support( $current_screen ) {
		if ( ! self::is_orderable_receipt_block_editor_page( $current_screen ) ) {
			return;
		}

		add_theme_support( 'custom-spacing' );

		add_theme_support(
			'editor-color-palette',
			[
				[
					'name'  => __( 'Default', 'orderable' ),
					'slug'  => 'default',
					'color' => '#111111',
				],
			]
		);
	}

	/**
	 * Get the default `Print Order` label.
	 *
	 * @return string
	 */
	protected static function get_button_label() {
		return __( 'Print Order', 'orderable' );
	}

	/**
	 * Add Print Order button to preview order details.
	 *
	 * @param array $order_details The order details.
	 * @return array
	 */
	public static function add_print_order_button_to_preview_order_details( $order_details ) {
		if ( empty( $order_details['data']['id'] ) ) {
			return $order_details;
		}

		$layout_id = self::get_layout_id();

		if ( ! $layout_id ) {
			return $order_details;
		}

		$order_id = $order_details['data']['id'];

		$url = self::get_public_url( $order_id, $layout_id );

		ob_start();
		?>
		<div class="orderable-receipt-layouts__wrapper-print-order-button" style="float:left;margin-right: 15px">
			<?php self::output_print_order_button( $url, self::get_button_label(), $order_id ); ?>
		</div>
		<?php

		$print_order_button = ob_get_clean();

		$order_details['actions_html'] = $print_order_button . $order_details['actions_html'];
		return $order_details;

	}

	/**
	 * Output the Print Order button.
	 *
	 * @param string $url      The URL.
	 * @param string $label    The Label.
	 * @param int    $order_id The order ID.
	 * @return void
	 */
	protected static function output_print_order_button( $url, $label, $order_id ) {
		$classes = [ 'button-primary', 'orderable-receipt-layouts__print-order-button' ];
		$classes = join( ' ', $classes );

		$receipt_layouts = self::get_receipt_layouts( [ 'posts_per_page' => 20 ] );

		if ( ! $receipt_layouts ) {
			return;
		}
		?>
		<span style="position: relative;">
			<a
				href="<?php echo esc_url( $url ); ?>"
				target="_blank"
				rel="noopener noreferrer"
				role="button"
				class="<?php echo esc_attr( $classes ); ?>"
			>
				<?php echo esc_html( $label ); ?>
			</a>
			<?php self::output_list_receipt_layouts( $receipt_layouts, $order_id, $url ); ?>
		</span>
		<?php
	}

	/**
	 * Output the list of receipt layouts.
	 *
	 * @param array $receipt_layouts The receipt layouts.
	 * @param int   $order_id        The order ID.
	 * @return void
	 */
	protected static function output_list_receipt_layouts( $receipt_layouts, $order_id, $default_receipt_layout_url ) {
		if ( ! $receipt_layouts || count( $receipt_layouts ) < 2 ) {
			return;
		}

		$default_receipt_layout_id = self::get_layout_id();

		foreach ( $receipt_layouts as $key => $receipt_layout ) {
			if ( $default_receipt_layout_id !== $receipt_layout->ID ) {
				continue;
			}

			$default_receipt_layout = $receipt_layout;
			unset( $receipt_layouts[ $key ] );
			break;
		}

		array_unshift( $receipt_layouts, $default_receipt_layout );

		?>
		<button class="button-primary orderable-receipt-layouts__receipt-layout-options-button">
			<span class="dashicons dashicons-arrow-down orderable-receipt-layouts__receipt-layout-options-icon-button"></span>
		</button>
		<ul class="orderable-receipt-layouts__receipt-layout-options-list">
			<?php foreach ( $receipt_layouts as $receipt_layout ) : ?>
				<li class="orderable-receipt-layouts__receipt-layout-options-item-list">
					<?php if ( $receipt_layout->ID === $default_receipt_layout_id ) : ?>
						<a
							href="<?php echo esc_url( $default_receipt_layout_url ); ?>"
							target="_blank"
							rel="noopener noreferrer"
							role="button"
							class="orderable-receipt-layouts__receipt-layout-option orderable-receipt-layouts__receipt-layout-option-print-link"
						>
							<?php echo sprintf( '%s (default)', esc_html( $receipt_layout->post_title ) ); ?>
						</a>
					<?php endif; ?>

					<?php if ( $receipt_layout->ID !== $default_receipt_layout_id ) : ?>
						<button
							class="orderable-receipt-layouts__receipt-layout-option orderable-receipt-layouts__receipt-layout-option-print-button"
							data-order-id="<?php echo esc_attr( $order_id ); ?>"
							data-receipt-layout-id="<?php echo esc_attr( $receipt_layout->ID ); ?>"
						>
							<?php echo esc_html( $receipt_layout->post_title ); ?>
						</button>
						<span class="orderable-receipt-layouts__receipt-layout-option-loading spinner"></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Add Print Order buttons to the edit order page (HPOS)
	 */
	public static function add_print_order_buttons_to_hpos_edit_order_page() {
		if ( empty( $_GET['action'] ) || empty( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

		if ( 'edit' !== $action ) {
			return;
		}

		$order_id = absint( wp_unslash( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		self::output_print_order_buttons_to_edit_order_page( $order_id );
	}

	/**
	 * Output the script to render the print order butons to edit order page.
	 *
	 * @param int $order_id The order ID.
	 */
	protected static function output_print_order_buttons_to_edit_order_page( $order_id ) {
		$layout_id = self::get_layout_id();

		if ( ! $layout_id ) {
			return;
		}

		$url = self::get_public_url( $order_id, $layout_id );

		ob_start();

		self::output_print_order_button( $url, self::get_button_label(), $order_id );

		$print_order_button = trim( ob_get_clean() );

		?>
			<script>
				jQuery( document ).ready( function() {
					const $add_new_button = jQuery( '.page-title-action' ).first();

					if ( $add_new_button.length ) {
						$add_new_button.before( `<span style="position:relative; top: -3px"><?php echo $print_order_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>` );
					}

					const $add_items = jQuery('.add-items');

					if ($add_items.length) {
						$add_items.prepend(`<div style="float:left; margin-right:.25em"><?php echo $print_order_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>`);
					}

				} );
			</script>
		<?php
	}

	/**
	 * Add print order buttons to edit order page (classic).
	 */
	public static function add_print_order_buttons_to_edit_order_page() {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( empty( $screen ) ) {
			return;
		}

		if ( 'shop_order' !== $screen->id ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_GET['action'] ) || empty( $_GET['post'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

		if ( 'edit' !== $action ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$order_id = absint( wp_unslash( $_GET['post'] ) );
		self::output_print_order_buttons_to_edit_order_page( $order_id );
	}

	/**
	 * Add Printing settings.
	 *
	 * @param array $settings The Orderable settings.
	 * @return array
	 */
	public static function add_settings( $settings ) {
		$settings['tabs'][] = [
			'id'       => 'printing',
			'title'    => __( 'Printing', 'orderable' ),
			'priority' => 30,
		];

		$settings['sections'][] = [
			'tab_id'              => 'printing',
			'section_id'          => 'printing_settings',
			'section_title'       => __( 'Printing Settings', 'orderable' ),
			'section_description' => '',
			'section_order'       => 0,
			'fields'              => [
				[
					'id'       => 'default_printing_layout',
					'title'    => __( 'Default layout', 'orderable' ),
					'subtitle' => __( 'Select the default receipt layout when printing an order.', 'orderable' ),
					'type'     => 'select',
					'choices'  => self::get_receipt_layouts_options(),
				],
			],
		];

		return $settings;
	}

	/**
	 * Get receipt layout by ID.
	 *
	 * @param int   $id The receipt layout ID.
	 * @param array $query_args The WP_Query args.
	 * @return WP_Post|null
	 */
	protected static function get_receipt_layout( $id, $query_args = [] ) {
		$default_query_args = [
			'p'              => $id,
			'posts_per_page' => 1,
		];

		$query_args = wp_parse_args( $query_args, $default_query_args );

		$receipt_layout = self::get_receipt_layouts( $query_args );

		return $receipt_layout[0] ?? null;
	}

	/**
	 * Get last receipt layout created.
	 *
	 * @return WP_Post|int|null
	 */
	protected static function get_last_receipt_layout() {
		$receipt_layouts = self::get_receipt_layouts(
			[
				'posts_per_page' => 1,
				'order'          => 'DESC',
				'orderby'        => 'modified date',
			]
		);

		return $receipt_layouts[0] ?? null;
	}

	/**
	 * Get receipt layouts.
	 *
	 * @param array $query_args The WP_Query args.
	 * @return WP_Post[]|int[]|null
	 */
	protected static function get_receipt_layouts( $query_args = [] ) {
		$default_query_args = [
			'fields'                 => 'all',
			'post_type'              => self::$post_type_key,
			'posts_per_page'         => 200,
			'post_status'            => 'publish',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		$query_args = wp_parse_args( $query_args, $default_query_args );

		/**
		 * Filter the query args to retrieve the receipt layouts.
		 *
		 * @since 1.18.0
		 * @hook orderable_get_receipt_layouts_query_args
		 * @param  array $query_args The WP query args to retrieve the receipt layouts.
		 */
		$query_args = apply_filters( 'orderable_get_receipt_layouts_query_args', $query_args );

		$query = new WP_Query( $query_args );

		if ( empty( $query->posts ) ) {
			return null;
		}

		return $query->posts;
	}

	/**
	 * Get the receipt layout options.
	 *
	 * @return array
	 */
	protected static function get_receipt_layouts_options() {
		$options = [ __( 'No receipt layouts created', 'orderable' ) ];

		$receipt_layouts = self::get_receipt_layouts();

		if ( ! $receipt_layouts ) {
			return $options;
		}

		$options = [ __( 'Select...', 'orderable' ) ];

		foreach ( $receipt_layouts as $layout ) {
			$options[ $layout->ID ] = $layout->post_title;
		}

		return $options;
	}

	/**
	 * Enqueue assets to orders page.
	 *
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public static function enqueue_assets_to_orders_page( $hook_suffix ) {
		$current_screen = get_current_screen();

		if ( 'woocommerce_page_wc-orders' !== $hook_suffix && 'edit-shop_order' !== $current_screen->id && 'shop_order' !== $current_screen->id ) {
			return;
		}

		if ( ! self::get_layout_id() ) {
			return;
		}

		$asset_id = 'orderable-receipt-orders-page';

		$assets_path = ORDERABLE_URL . 'inc/modules/receipt-layouts/assets/admin/';
		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$suffix_css  = ( is_rtl() ? '-rtl' : '' ) . $suffix;

		wp_enqueue_style( $asset_id, $assets_path . 'css/orders-page/receipt-layouts-orders-page' . $suffix_css . '.css', [], ORDERABLE_VERSION );
		wp_enqueue_script( $asset_id, $assets_path . 'js/orders-page/main' . $suffix . '.js', [ 'jquery' ], ORDERABLE_VERSION, true );

		wp_localize_script(
			$asset_id,
			'orderableReceiptLayouts',
			[
				'receiptLayoutId' => self::get_layout_id(),
			]
		);
	}

	/**
	 * Get the option key.
	 *
	 * @return string
	 */
	protected static function get_has_created_default_receipt_layouts_key() {
		return '_orderable_has_created_default_receipt_layouts';
	}

	/**
	 * Check whether there is at least one receipt layout created.
	 *
	 * @return boolean
	 */
	protected static function has_receipt_layouts() {
		$receipt_layouts = self::get_receipt_layouts(
			[
				'post_per_page' => 1,
				'fields'        => 'ids',
				'post_status'   => 'any',
			]
		);

		return ! empty( $receipt_layouts );
	}

	/**
	 * Check if it's necessary to create the default receipt layouts.
	 *
	 * @return void
	 */
	public static function should_create_default_receipt_layouts() {
		$option_key = self::get_has_created_default_receipt_layouts_key();

		$has_created_default_receipt_layouts = get_option( $option_key, false );

		if ( ! empty( $has_created_default_receipt_layouts ) ) {
			return;
		}

		if ( self::has_receipt_layouts() ) {
			update_option( $option_key, true, false );
			return;
		}

		foreach ( self::get_block_patterns() as $block_pattern ) {
			wp_insert_post(
				[
					'post_title'   => esc_html( $block_pattern['title'] ),
					'post_content' => wp_kses_post( $block_pattern['content'] ),
					'post_type'    => self::$post_type_key,
					'post_status'  => 'publish',
				]
			);
		}

		if ( self::has_receipt_layouts() ) {
			update_option( $option_key, true, false );
			return;
		}
	}
}
