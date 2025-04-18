<?php
/**
 * Dokan Integration.
 *
 * @package Orderable/Classes
 */

/**
 * Dokan Integration.
 */
class Orderable_Pro_Integration_Dokan extends Orderable_Pro_Multi_Vendor {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	public $plugin_name = 'dokan';

	/**
	 * Instance.
	 *
	 * @var Orderable_Pro_Integration_Dokan
	 */
	public static $instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! function_exists( 'dokan' ) ) {
			return;
		}

		parent::__construct( $this->plugin_name );

		add_filter( 'dokan_get_dashboard_nav', array( __CLASS__, 'add_dashboard_menu' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_orderable_query_vars' ), 10, 1 );
		add_filter( 'dokan_get_template_part', array( __CLASS__, 'modify_dokan_template_part' ), 10, 3 );
		add_filter( 'orderable_location_get_selected_location_id', array( $this, 'modify_selected_location_on_vendor_dashboard' ), 10, 1 );
		add_filter( 'wpsf_register_settings_orderable', array( $this, 'add_dokan_settings' ), 20 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'init', array( __CLASS__, 'on_init' ) );
		add_action( 'dokan_load_custom_template', array( $this, 'load_custom_orderable_template' ) );
		add_action( 'orderable_location_object_init', array( $this, 'modify_location_data_for_vendor_dashboard' ) );
		add_filter( 'woocommerce_shipping_packages', array( $this, 'add_seller_id_to_packages' ), 5, 1 );
	}

	/**
	 * Get singleton instance.
	 *
	 * @return Orderable_Pro_Integration_Dokan
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * On init hook.
	 *
	 * @return void
	 */
	public static function on_init() {
		add_rewrite_endpoint( 'orderable', EP_ROOT | EP_PAGES );
	}

	/**
	 * Get vendor by product.
	 *
	 * @param array $product_id Product ID.
	 *
	 * @return int|bool
	 */
	public function get_vendor_by_product( $product_id ) {
		$vendor = dokan_get_vendor_by_product( $product_id );

		return empty( $vendor ) ? false : $vendor->id;
	}

	/**
	 * Get vendor user role.
	 *
	 * @return string
	 */
	public function get_vendor_user_role() {
		return 'seller';
	}

	/**
	 * Is the given user a vendor?
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	public function is_user_vendor( $user_id ) {
		return dokan_is_user_seller( $user_id );
	}

	/**
	 * Add Vendor Dashboard Menu item for Orderble.
	 *
	 * @param array $menu_items Menu items.
	 *
	 * @return array
	 */
	public static function add_dashboard_menu( $menu_items ) {
		$menu_items['orderable'] = array(
			'title' => __( 'Locations', 'orderable' ),
			'icon'  => sprintf( '<i class="orderable-icon"></i>' ),
			'url'   => dokan_get_navigation_url( 'orderable' ),
			'pos'   => 31,
		);

		return $menu_items;
	}

	/**
	 * Add orderable query vars.
	 *
	 * @param array $vars Query vars.
	 *
	 * @return array
	 */
	public static function add_orderable_query_vars( $vars ) {
		$vars[] = 'orderable';
		return $vars;
	}

	/**
	 * Load custom orderable template.
	 *
	 * @param array $query_vars Query vars.
	 *
	 * @return void
	 */
	public function load_custom_orderable_template( $query_vars ) {
		if ( ! isset( $query_vars['orderable'] ) ) {
			return;
		}

		$location_id = filter_input( INPUT_GET, 'orderable_mv_location_id' );

		if ( ! empty( $location_id ) ) {
			dokan_get_template_part( 'orderable-single-location', null, array( 'dokan_integration' => $this ) );
		} else {
			dokan_get_template_part( 'orderable-locations', null, array( 'dokan_integration' => $this ) );
		}
	}

	/**
	 * Load Orderable templates on the Vendor Dashboard.
	 *
	 * @param string $template Template.
	 * @param string $slug     Slug.
	 * @param string $name     Name.
	 *
	 * @return string
	 */
	public static function modify_dokan_template_part( $template, $slug, $name ) {
		if ( 'orderable-locations' === $slug ) {
			return ORDERABLE_PRO_PATH . 'templates/dokan/locations.php';
		} elseif ( 'orderable-single-location' === $slug ) {
			return ORDERABLE_PRO_PATH . 'templates/dokan/single-location.php';
		}

		return $template;
	}

	/**
	 * Enqueue assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! dokan_is_seller_dashboard() ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( 'orderable-dokan-integration-css', ORDERABLE_PRO_URL . 'inc/integrations/dokan/assets/frontend/css/dokan' . $suffix . '.css', array(), ORDERABLE_PRO_VERSION );
		wp_enqueue_script( 'orderable-dokan-integration-js', ORDERABLE_PRO_URL . 'inc/integrations/dokan/assets/frontend/js/main' . $suffix . '.js', array(), ORDERABLE_PRO_VERSION, true );

		$inline_style = '
		:root {
			--orderable-icon-url: url( "' . ORDERABLE_URL . '/assets/img/orderable-icon.svg" );
		}';

		wp_add_inline_style( 'orderable-dokan-integration-css', $inline_style );

		$location_id = filter_input( INPUT_GET, 'orderable_mv_location_id' );
		if ( empty( $location_id ) ) {
			return;
		}

		$this->enqueue_location_assets();
	}

	/**
	 * Change the location ID for the vendor location.
	 *
	 * @param int $location_id Location ID.
	 *
	 * @return int
	 */
	public function modify_selected_location_on_vendor_dashboard( $location_id ) {
		$location_id_get = filter_input( INPUT_GET, 'orderable_mv_location_id', FILTER_SANITIZE_SPECIAL_CHARS );

		if ( 'new' === $location_id_get ) {
			return null;
		}

		if ( ! empty( $location_id_get ) && is_numeric( $location_id_get ) ) {
			return intval( $location_id_get );
		}

		return $location_id;
	}

	/**
	 * Modify location data for the Vendor Dashboard.
	 *
	 * @param Orderable_Location_Single $location Location object.
	 *
	 * @return void
	 */
	public function modify_location_data_for_vendor_dashboard( $location ) {
		$location_id = filter_input( INPUT_GET, 'orderable_mv_location_id' );
		$vendor_id   = get_current_user_id();

		if ( empty( $location_id ) || ! dokan_is_user_seller( $vendor_id ) ) {
			return;
		}

		$data = $this->get_location_data_for_vendor( $location_id, $vendor_id );

		if ( empty( $data ) || empty( $data['data'] ) ) {
			return;
		}

		$this->modify_location_data( $location, $data );
	}

	/**
	 * Add Dokan settings to Orderable settings.
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	public static function add_dokan_settings( $settings ) {
		$settings['tabs'][] = array(
			'id'       => 'multi-vendor',
			'title'    => __( 'Multi-Vendor', 'orderable-pro' ),
			'priority' => 100,
		);

		$settings['sections'][] = array(
			'tab_id'              => 'integrations',
			'section_id'          => 'dokan',
			'section_title'       => __( 'Dokan', 'orderable' ),
			'section_description' => '',
			'section_order'       => 20,
			'fields'              => array(
				array(
					'id'       => 'allow_vendor_override_location',
					'title'    => __( 'Allow vendors to override Location settings', 'orderable-pro' ),
					'subtitle' => __( 'Allow vendors to override individual location settings like Service hours, Lead time, Holidays etc for products in their stores.', 'orderable-pro' ),
					'type'     => 'select',
					'choices'  => array(
						'assigned_only' => __( 'Assigned Locations only', 'orderable-pro' ),
						'all'           => __( 'All Locations', 'orderable-pro' ),
						'dont_allow'    => __( 'Don\'t Allow', 'orderable-pro' ),
					),
					'default'  => 'assigned_only',
				),
				array(
					'id'       => 'allow_vendor_create_location',
					'title'    => __( 'Allow vendors to create new Locations', 'orderable-pro' ),
					'subtitle' => __( 'You can exempt individual vendors from their users profile page (Users > edit user > Orderable).', 'orderable-pro' ),
					'type'     => 'select',
					'choices'  => array(
						'yes' => __( 'Yes', 'orderable-pro' ),
						'no'  => __( 'No', 'orderable-pro' ),
					),
					'default'  => 'assigned_only',
				),
			),
		);

		return $settings;
	}

	/**
	 * Is the given vendor owner of the location.
	 *
	 * @param int $vendor_id   Vendor ID.
	 * @param int $location_id Location ID.
	 *
	 * @return bool
	 */
	public function is_vendor_owner_of_location( $vendor_id, $location_id ) {
		if ( 'new' === $location_id ) {
			return true;
		}

		$location      = new Orderable_Location_Single_Pro( $location_id );
		$post_id       = $location->location_data['post_id'];
		$location_post = get_post( $post_id );

		if ( empty( $location_post ) ) {
			return false;
		}

		return (int) $location_post->post_author === (int) $vendor_id;
	}

	/**
	 * In some cases Dokan throws a PHP warning when calculating shipping.
	 * This filter is a workaround to fix that issue.
	 *
	 * @param array $packages Packages.
	 *
	 * @return array
	 */
	public static function add_seller_id_to_packages( $packages ) {
		foreach ( $packages as $i => &$package ) {
			if ( ! isset( $package['seller_id'] ) ) {
				$package['seller_id'] = 0;
			}
		}

		return $packages;
	}

}
