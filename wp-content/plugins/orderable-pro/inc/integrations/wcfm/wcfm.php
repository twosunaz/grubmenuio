<?php
/**
 * WCFM Integration.
 *
 * @package Orderable/Classes
 */

/**
 * WCFM Integration.
 */
class Orderable_Pro_Integration_Wcfm extends Orderable_Pro_Multi_Vendor {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	public $plugin_name = 'wcfm';

	/**
	 * Instance.
	 *
	 * @var Orderable_Pro_Integration_Wcfm
	 */
	public static $instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'WCFM' ) ) {
			return;
		}

		parent::__construct( $this->plugin_name );

		add_filter( 'wcfm_menus', array( $this, 'add_dashboard_menu' ), 30, 1 );

		add_filter( 'wcfm_endpoint_orderable_title', array( $this, 'update_title' ) );
		add_filter( 'wcfm_endpoints_slug', array( $this, 'endpoint_slug' ) );
		add_filter( 'wcfm_query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'before_wcfm_load_views', array( $this, 'load_view' ), 10 );

		add_filter( 'orderable_location_get_selected_location_id', array( $this, 'modify_selected_location_on_vendor_dashboard' ), 10, 1 );
		add_filter( 'wpsf_register_settings_orderable', array( $this, 'add_settings' ), 20 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'init', array( $this, 'on_init' ) );
		add_action( 'orderable_location_object_init', array( $this, 'modify_location_data_for_vendor_dashboard' ) );
	}

	/**
	 * Get singleton instance.
	 *
	 * @return Orderable_Pro_Integration_Wcfm
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
	public function on_init() {
		global $WCFM_Query;

		// Intialize WCFM End points.
		$WCFM_Query->init_query_vars();
		$WCFM_Query->add_endpoints();

		if ( ! get_option( 'wcfm_updated_end_point_orderable' ) ) {
			flush_rewrite_rules();
			update_option( 'wcfm_updated_end_point_orderable', 1 );
		}
	}

	/**
	 * Update page title for Orderable.
	 *
	 * @param string $title Title.
	 *
	 * @return string
	 */
	public function update_title( $title ) {
		$location_id = filter_input( INPUT_GET, 'orderable_mv_location_id', FILTER_SANITIZE_SPECIAL_CHARS );

		if ( empty( $location_id ) || 'new' === $location_id ) {
			return __( 'Orderable', 'orderable-pro' );
		}

		$location = new Orderable_Location_Single( $location_id );

		return $location->location_data['title'];
	}

	/**
	 * Get vendor by product.
	 *
	 * @param array $product_id Product ID.
	 *
	 * @return int|bool
	 */
	public function get_vendor_by_product( $product_id ) {
		return wcfm_get_vendor_id_by_post( $product_id );
	}


	/**
	 * Add query vars.
	 *
	 * @param array $fields Fields.
	 *
	 * @return array
	 */
	public function add_query_vars( $fields ) {
		$wcfm_modified_endpoints = (array) get_option( 'wcfm_endpoints' );
		$fields['orderable']     = ! empty( $wcfm_modified_endpoints['orderable'] ) ? $wcfm_modified_endpoints['orderable'] : 'orderable';

		return $fields;
	}

	/**
	 * Load view.
	 *
	 * @param string $end_point End point.
	 *
	 * @return void
	 */
	public function load_view( $end_point ) {
		$wcfm_integration = $this;
		$location_id      = filter_input( INPUT_GET, 'orderable_mv_location_id', FILTER_SANITIZE_SPECIAL_CHARS );

		switch ( $end_point ) {
			case 'orderable':
				if ( $location_id ) {
					require_once ORDERABLE_PRO_PATH . 'templates/wcfm/single-location.php';
				} else {
					require_once ORDERABLE_PRO_PATH . 'templates/wcfm/all-locations.php';
				}
				break;
		}
	}

	/**
	 * Add dashboard menu to WCFM dashboard.
	 *
	 * @param array $wcfm_menus Menu Items.
	 *
	 * @return array
	 */
	public function add_dashboard_menu( $wcfm_menus ) {
		$wcfm_menus['orderable'] = array(
			'label' => __( 'Locations', 'orderable' ),
			'url'   => $this->get_wcfm_orderable_url(),
			'icon'  => 'orderable',
		);

		return $wcfm_menus;
	}

	/**
	 * Get vendor user role.
	 *
	 * @return string
	 */
	public function get_vendor_user_role() {
		return 'wcfm_vendor';
	}

	/**
	 * Is the given user a vendor?
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	public function is_user_vendor( $user_id ) {
		return wcfm_is_vendor( $user_id );
	}

	/**
	 * Add endpoint slug 'orderable'.
	 *
	 * @param array $fields Fields.
	 *
	 * @return array
	 */
	public function endpoint_slug( $fields ) {
		$fields['orderable'] = 'orderable';
		return $fields;
	}

	/**
	 * Enqueue assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! is_wcfm_page() ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( 'orderable-wcfm-integration-css', ORDERABLE_PRO_URL . 'inc/integrations/wcfm/assets/frontend/css/wcfm' . $suffix . '.css', array(), ORDERABLE_PRO_VERSION );
		wp_enqueue_script( 'orderable-wcfm-integration-js', ORDERABLE_PRO_URL . 'inc/integrations/wcfm/assets/frontend/js/main' . $suffix . '.js', array(), ORDERABLE_PRO_VERSION, true );

		$inline_style = '
		:root {
			--orderable-icon-url: url( "' . ORDERABLE_URL . '/assets/img/orderable-icon.svg" );
		}';

		wp_add_inline_style( 'orderable-wcfm-integration-css', $inline_style );

		$location_id = filter_input( INPUT_GET, 'orderable_mv_location_id' );
		if ( empty( $location_id ) ) {
			return;
		}

		wp_localize_script(
			'orderable-wcfm-integration-js',
			'wcfm_orderable_var',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);

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

		if ( empty( $location_id ) || ! wcfm_is_vendor( $vendor_id ) ) {
			return;
		}

		$data = $this->get_location_data_for_vendor( $location_id, $vendor_id );

		if ( empty( $data ) || empty( $data['data'] ) ) {
			return;
		}

		$this->modify_location_data( $location, $data );
	}

	/**
	 * Add WCFM settings to Orderable settings.
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	public static function add_settings( $settings ) {
		$settings['tabs'][] = array(
			'id'       => 'multi-vendor',
			'title'    => __( 'Multi-Vendor', 'orderable-pro' ),
			'priority' => 100,
		);

		$settings['sections'][] = array(
			'tab_id'              => 'integrations',
			'section_id'          => 'wcfm',
			'section_title'       => __( 'WCFM Marketplace', 'orderable' ),
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
	 * Get orderable URL within WCFM dashboard.
	 *
	 * @return string
	 */
	public function get_wcfm_orderable_url() {
		$wcfm_page = get_wcfm_page();

		if ( empty( $wcfm_page ) ) {
			return '';
		}

		$get_wcfm_settings_url = wcfm_get_endpoint_url( 'orderable', '', $wcfm_page );
		return $get_wcfm_settings_url;
	}

}
