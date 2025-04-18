<?php
/**
 * Abstract class for Multi Vendor integrations.
 *
 * To create a new Multi Vendor integration, extend this class and implement the abstract methods.
 * Then create a new instance of the class in the inc/class-integrations.php file.
 *
 * @package Orderable/Classes
 */

/**
 * Common functions related to Multi Vendor plugins support.
 */
abstract class Orderable_Pro_Multi_Vendor {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	public $plugin_name;

	/**
	 * Init.
	 *
	 * @param string $plugin Name of the multi-vendor plugin example: dokan, wcfm etc. No spaces allowd.
	 *
	 * @throws Exception If plugin name is incorrect or has spaces.
	 */
	public function __construct( $plugin ) {
		// Check if plugin name has spaces.
		if ( str_contains( $plugin, ' ' ) ) {
			throw new Exception( 'Plugin name should not have spaces.' );
		}

		$this->plugin_name = $plugin;

		// Filters.
		add_filter( 'orderable_get_service_hours', array( $this, 'modify_service_hours_for_vendor' ), 10, 5 );
		add_filter( 'orderable_location_holidays_query_result', array( $this, 'modify_holidays_query_result' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prevent_mixed_vendor_cart' ), 10, 3 );
		add_filter( 'orderable_pro_get_locations_for_shipping_zones', array( $this, 'get_locations_for_shipping_zones' ), 10, 3 );
		add_filter( 'orderable_location_holiday_setting_description', array( $this, 'modify_holidays_setting_description' ) );
		add_filter( 'orderable_location_open_hours_override_description', array( $this, 'modify_open_hours_setting_description' ) );

		// Actions.
		add_action( 'orderable_location_object_init', array( $this, 'modify_location_data_for_vendor' ) );
		add_filter( 'orderable_get_time_slots_for_zone', array( $this, 'modify_timeslot_for_zone' ), 10, 2 );

		// Ajax.
		add_action( "wp_ajax_orderable_{$this->plugin_name}_save_location_data", array( $this, 'ajax_save_location_data' ) );
		add_action( "wp_ajax_orderable_{$this->plugin_name}_override_location", array( $this, 'ajax_update_override_toggle_status' ) );

		// User profile settings.
		add_action( 'show_user_profile', array( $this, 'add_location_setting_to_vendor_profile' ), 10, 1 );
		add_action( 'edit_user_profile', array( $this, 'add_location_setting_to_vendor_profile' ), 10, 1 );
		add_action( 'personal_options_update', array( $this, 'save_location_setting_from_vendor_profile' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_location_setting_from_vendor_profile' ) );

		add_filter( 'register_post_type_args', array( $this, 'add_author_support_for_location_post_type' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart', array( $this, 'deselect_location_if_product_doesnt_belong_to_vendor' ), 10, 1 );
	}

	/**
	 * Get vendor ID for a product.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return int Vendor ID.
	 */
	abstract public function get_vendor_by_product( $product_id );

	/**
	 * Get user role for vendors. Example 'seller' for Dokan.
	 *
	 * @return string
	 */
	abstract public function get_vendor_user_role();

	/**
	 * Check if current user is a vendor.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	abstract public function is_user_vendor( $user_id );

	/**
	 * Show Edit single location form.
	 *
	 * @param int $location_id Location ID to edit. Note: its not the location post ID.
	 *
	 * @return void
	 */
	public function show_edit_location_screen( $location_id ) {
		Orderable_Location_Admin::metabox_ui_wrapper( Orderable_Location_Store_Address_Meta_Box::class );
		Orderable_Location_Admin::metabox_ui_wrapper( Orderable_Location_Open_Hours_Meta_Box::class );
		Orderable_Location_Admin::metabox_ui_wrapper( Orderable_Location_Store_Services_Meta_Box::class );
		Orderable_Location_Admin::metabox_ui_wrapper( Orderable_Location_Order_Options_Meta_Box::class );
		Orderable_Location_Admin::metabox_ui_wrapper( Orderable_Location_Holidays_Meta_Box::class );

		Orderable_Location_Zones_Admin::output_delivery_zones_modal_html();
		Orderable_Location_Zones_Admin::output_delivery_zones_js_templates();
		?>

		<input type="hidden" id="orderable-mv-plugin-id" value="<?php echo esc_attr( $this->plugin_name ); ?>">
		<input type="hidden" id="orderable-mv-location-id" value="<?php echo esc_attr( $location_id ); ?>">
		<?php wp_nonce_field( 'orderable_save_location_data', 'orderable_location_nonce' ); ?>
		<input type="button" value='<?php echo esc_attr__( 'Save', 'orderable-pro' ); ?>' class="orderable-mv-save-location-btn" id="<?php echo esc_attr( sprintf( 'orderable-mv-save-location-%s', $this->plugin_name ) ); ?>">
		<?php
	}

	/**
	 * Update override status for a location.
	 */
	public function ajax_update_override_toggle_status() {
		check_admin_referer( 'orderable_save_location_data' );

		$location_id = isset( $_POST['location_id'] ) ? sanitize_text_field( wp_unslash( $_POST['location_id'] ) ) : '';
		$vendor_id   = get_current_user_id();
		$override    = isset( $_POST['override'] ) ? filter_input( INPUT_POST, 'override', FILTER_VALIDATE_BOOLEAN ) : false;
		$plugin      = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';
		$override    = $override ? 'yes' : 'no';

		if ( empty( $location_id ) || empty( $vendor_id ) || empty( $plugin ) ) {
			wp_send_json_error();
		}

		$prefix = $this->get_meta_prefix( $location_id );

		update_user_meta( $vendor_id, $prefix . 'override', $override );
		wp_send_json_success();
	}

	/**
	 * Get location data for a vendor.
	 *
	 * @param int  $location_id Location ID.
	 * @param bool $vendor_id   Vendor ID.
	 *
	 * @return array
	 */
	public function get_location_data_for_vendor( $location_id, $vendor_id = false ) {
		if ( 'new' === $location_id ) {
			return array(
				'override' => 'yes',
				'data'     => array(),
			);
		}

		if ( ! $vendor_id ) {
			$vendor_id = get_current_user_id();
		}

		$prefix = $this->get_meta_prefix( $location_id );

		return array(
			'override' => get_user_meta( $vendor_id, $prefix . 'override', true ),
			'data'     => get_user_meta( $vendor_id, $prefix . 'data', true ),
		);
	}

	/**
	 * Check if location is overriden by vendor.
	 *
	 * @param int $location_id Location ID.
	 * @param int $vendor_id   Vendor ID.
	 *
	 * @return bool
	 */
	public function is_location_overriden( $location_id, $vendor_id = false ) {
		if ( ! $vendor_id ) {
			$vendor_id = get_current_user_id();
		}

		$data = $this->get_location_data_for_vendor( $location_id, $vendor_id );

		if ( empty( $data ) || empty( $data['override'] ) || 'yes' !== $data['override'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Save data for a single location.
	 *
	 * @return void
	 */
	public function ajax_save_location_data() {
		check_admin_referer( 'orderable_save_location_data' );

		$location_id     = isset( $_POST['location_id'] ) ? sanitize_text_field( wp_unslash( $_POST['location_id'] ) ) : '';
		$vendor_id       = get_current_user_id();
		$plugin          = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';
		$data            = isset( $_POST['data'] ) ? filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ) : array();
		$new_location_id = false;

		if ( empty( $location_id ) || empty( $vendor_id ) || empty( $plugin ) || empty( $data ) ) {
			wp_send_json_error();
		}

		if ( 'new' === $location_id ) {
			if ( ! $this->is_vendor_allowed_to_create_locations( $vendor_id ) ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to create new locations.', 'orderable-pro' ) ) );
			}

			$new_location_id = $this->create_location( $data );

			if ( empty( $new_location_id ) ) {
				wp_send_json_error();
			}

			$location_id = $new_location_id;
		} else {
			if ( ! empty( $data['orderable_location_name'] ) ) {
				$location = new Orderable_Location_Single_Pro( $location_id );
				$location->update_title( $data['orderable_location_name'] );
			}
		}

		$prefix = $this->get_meta_prefix( $location_id );

		$data = $this->add_timeslot_id( $data );
		update_user_meta( $vendor_id, $prefix . 'data', $data );

		wp_send_json_success(
			array(
				'message'         => __( 'Location data saved successfully.', 'orderable-pro' ),
				'new_location_id' => $new_location_id,
			)
		);
	}

	/**
	 * Since vendor timeslots are not stored in the database table, we need to generate a random ID for them.
	 * Loop through all the service hours add a random ID to timeslots if they don't have one.
	 *
	 * @param array $data Vendor Location Data.
	 *
	 * @return array
	 */
	public function add_timeslot_id( $data ) {
		if ( empty( $data['service_hours']['delivery'] ) ) {
			return $data;
		}

		foreach ( $data['service_hours']['delivery'] as &$service_hour ) {
			$timeslot_id                  = $this->generate_random_id();
			$service_hour['time_slot_id'] = empty( $service_hour['time_slot_id'] ) ? $timeslot_id : $service_hour['time_slot_id'];

			if ( empty( $service_hour['zones'] ) ) {
				continue;
			}

			foreach ( $service_hour['zones'] ?? array() as $zone_key => $zone_json ) {
				$zone = json_decode( $zone_json, true );
				if ( empty( $zone['time_slot_id'] ) || 'NaN' === $zone['time_slot_id'] ) {
					$zone['time_slot_id'] = $timeslot_id;

					$zone                               = wp_json_encode( $zone );
					$service_hour['zones'][ $zone_key ] = $zone;
				}
			}
		}

		return $data;
	}

	/**
	 * Get meta prefix for a location.
	 *
	 * @param int $location_id Location ID.
	 *
	 * @return string
	 */
	public function get_meta_prefix( $location_id ) {
		return sprintf( 'orderable_location_%s_%s_', $this->plugin_name, $location_id );
	}

	/**
	 * Modify location data if cart contains products for vendor.
	 *
	 * @param Orderable_Location_Single $location Location object.
	 *
	 * @return void
	 */
	public function modify_location_data_for_vendor( $location ) {
		// Modify location object within on all other pages.
		$vendor = $this->get_vendors_from_cart();

		if ( empty( $vendor ) ) {
			return;
		}

		$vendor_id   = $vendor[0];
		$location_id = $location->location_data['location_id'];

		$data = $this->get_location_data_for_vendor( $location_id, $vendor_id );

		if ( empty( $data ) ) {
			return;
		}

		if ( empty( $data['override'] ) || 'yes' !== $data['override'] || empty( $data['data'] ) ) {
			return;
		}

		$this->modify_location_data( $location, $data );
	}

	/**
	 * Get vendors from cart.
	 *
	 * @param bool $use_cache Use cache.
	 *
	 * @return array
	 */
	public function get_vendors_from_cart( $use_cache = true ) {
		static $cache = array();

		if ( empty( WC()->cart ) || ! did_action( 'wp_loaded' ) ) {
			return array();
		}

		$cart_hash = WC()->cart->get_cart_hash();

		// If the cart hash is already in the cache, return the cached result.
		if ( $use_cache && isset( $cache[ $cart_hash ] ) ) {
			return $cache[ $cart_hash ];
		}

		$cart_vendors = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$vendor_user_id = $this->get_vendor_by_product( $cart_item['product_id'] );

			if ( ! $vendor_user_id ) {
				continue;
			}

			$cart_vendors[] = $vendor_user_id;
		}

		// Store the result in the cache.
		$cache[ $cart_hash ] = $cart_vendors;

		return $cart_vendors;
	}

	/**
	 * Modify service hours for a vendor.
	 *
	 * @param array                     $hours        Service hours.
	 * @param Orderable_Location_Single $location     Location object.
	 * @param string                    $service_type Service type.
	 * @param bool                      $is_admin     Is admin.
	 * @param bool                      $skip_zone    Skip zone.
	 *
	 * @return array
	 */
	public function modify_service_hours_for_vendor( $hours, $location, $service_type, $is_admin, $skip_zone ) {
		$location_id_get = isset( $_GET['orderable_mv_location_id'] ) ? sanitize_text_field( wp_unslash( $_GET['orderable_mv_location_id'] ) ) : '';

		if ( $is_admin && empty( $location_id_get ) ) {
			return $hours;
		}

		$location_id = false;
		$vendor_id   = false;

		// Edit location page.
		if ( ! empty( $location_id_get ) ) {
			$location_id = $location_id_get;
			$vendor_id   = get_current_user_id();
		} else {
			// Cart and checkout page.
			$vendor = $this->get_vendors_from_cart();

			if ( empty( $vendor ) ) {
				return $hours;
			}

			$vendor_id   = $vendor[0];
			$location_id = $location->location_data['location_id'];
		}

		if ( empty( $location_id ) || empty( $vendor_id ) ) {
			return $hours;
		}

		$data = $this->get_location_data_for_vendor( $location_id, $vendor_id );

		if ( empty( $data ) || empty( $data['override'] ) || 'yes' !== $data['override'] || empty( $data['data'] ) ) {
			return $hours;
		}

		if ( 'pickup' === $service_type && ! empty( $data['data']['orderable_location_service_hours_pickup_same_as_delivery'] ) ) {
			$service_type = 'delivery';
		}

		if ( empty( $data['data']['service_hours'][ $service_type ] ) ) {
			return $hours;
		}

		$updated_hours = $data['data']['service_hours'][ $service_type ];

		// Add location ID and service type to the service hours.
		foreach ( $updated_hours as &$hour ) {
			$hour['location_id']  = $location_id;
			$hour['service_type'] = $service_type;
			$hour['days']         = empty( $hour['days'] ) ? array() : $hour['days'];

			if ( empty( $hour['time_slot_id'] ) ) {
				$hour['time_slot_id'] = uniqid();
			}
		}

		return $updated_hours;
	}

	/**
	 * Modify location data for a vendor.
	 *
	 * @param Orderable_Location $location    Location object.
	 * @param array              $vendor_data Vendor data.
	 *
	 * @return void
	 */
	public function modify_location_data( $location, $vendor_data ) {
		$location->location_data['address_line_1']                   = $vendor_data['data']['orderable_address_line_1'];
		$location->location_data['address_line_2']                   = $vendor_data['data']['orderable_address_line_2'];
		$location->location_data['city']                             = $vendor_data['data']['orderable_city'];
		$location->location_data['country_state']                    = $vendor_data['data']['orderable_country_state'];
		$location->location_data['postcode_zip']                     = $vendor_data['data']['orderable_post_code_zip'];
		$location->location_data['override_default_open_hours']      = $this->convert_bool( $vendor_data, 'orderable_override_open_hours' );
		$location->location_data['asap_date']                        = $this->convert_bool( $vendor_data, 'orderable_location_order_options_asap_delivery_date' );
		$location->location_data['asap_time']                        = $this->convert_bool( $vendor_data, 'orderable_location_order_options_asap_delivery_time' );
		$location->location_data['lead_time']                        = $vendor_data['data']['orderable_location_lead_time'];
		$location->location_data['lead_time_period']                 = $vendor_data['data']['orderable_location_lead_time_period'];
		$location->location_data['preorder']                         = $vendor_data['data']['orderable_location_preorder_days'];
		$location->location_data['delivery_days_calculation_method'] = $vendor_data['data']['orderable_location_delivery_days_calculation_method'];
		$location->location_data['open_hours']                       = ! empty( $vendor_data['data']['orderable_settings'] ) ? serialize( $vendor_data['data']['orderable_settings']['store_general_open_hours'] ) : '';
		$location->location_data['delivery']                         = $this->convert_bool( $vendor_data, 'orderable_location_store_services_delivery' );
		$location->location_data['pickup']                           = $this->convert_bool( $vendor_data, 'orderable_location_store_services_pickup' );
		$location->location_data['pickup_hours_same_as_delivery']    = $this->convert_bool( $vendor_data, 'orderable_location_service_hours_pickup_same_as_delivery' );
		$location->location_data['enable_default_holidays']          = $this->convert_bool( $vendor_data, 'orderable_location_enable_default_holidays' );
	}

	/**
	 * Convert bool value.
	 *
	 * @param array  $data Data.
	 * @param string $key  Key.
	 *
	 * @return string
	 */
	private function convert_bool( $data, $key ) {
		if ( empty( $data['data'][ $key ] ) ) {
			return false;
		}

		return ( 'yes' === $data['data'][ $key ] || '1' === $data['data'][ $key ] ) ? '1' : '0';
	}

	/**
	 * Modify holidays.
	 *
	 * @param array $holidays Holidays.
	 * @param array $location Location.
	 *
	 * @return array
	 */
	public function modify_holidays_query_result( $holidays, $location ) {
		$location_id = isset( $_GET['orderable_mv_location_id'] ) ? absint( wp_unslash( $_GET['orderable_mv_location_id'] ) ) : 0;

		$data = false;
		if ( ! empty( $location_id ) ) {
			// Vendor dashboard.
			$data = $this->get_location_data_for_vendor( $location_id );
		} else {
			// Cart and checkout page.
			$location_id = $location->location_data['location_id'];
			$vendors     = $this->get_vendors_from_cart();

			if ( empty( $vendors ) ) {
				return $holidays;
			}

			$data = $this->get_location_data_for_vendor( $location_id, $vendors[0] );
		}

		if ( empty( $data ) || 'yes' !== $data['override'] || empty( $data['data'] ) ) {
			return $holidays;
		}

		$formatted_holidays = array();

		foreach ( $data['data']['orderable_location_holidays'] as $holiday ) {

			$formatted_holidays[] = array(
				'holiday_id' => '',
				'from'       => $holiday['from'],
				'to'         => $holiday['to'],
				'services'   => ! empty( $holiday['services'] ) ? serialize( $holiday['services'] ) : '',
				'repeat'     => $holiday['repeat'] ?? '',
			);
		}

		return $formatted_holidays;
	}

	/**
	 * Prevent mixed vendor cart.
	 *
	 * @param bool $passed     Passed.
	 * @param int  $product_id Product ID.
	 * @param int  $quantity   Quantity.
	 *
	 * @return bool
	 */
	public function prevent_mixed_vendor_cart( $passed, $product_id, $quantity ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$product_id       = isset( $_POST['product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) : '';
		$add_to_cart      = isset( $_GET['add-to-cart'] ) ? sanitize_text_field( wp_unslash( $_GET['add-to-cart'] ) ) : '';
		$add_to_cart_post = isset( $_POST['add-to-cart'] ) ? sanitize_text_field( wp_unslash( $_POST['add-to-cart'] ) ) : '';
		// phpcs:enable

		$product_id = ! empty( $product_id ) ? $product_id : ( ! empty( $add_to_cart ) ? $add_to_cart : $add_to_cart_post );

		if ( empty( $product_id ) ) {
			return $passed;
		}

		$vendor_id    = $this->get_vendor_by_product( $product_id );
		$cart_vendors = $this->get_vendors_from_cart();

		if ( empty( $cart_vendors ) || empty( $vendor_id ) ) {
			return $passed;
		}

		$cart_vendors = array_unique( $cart_vendors );

		if ( count( $cart_vendors ) > 1 || $cart_vendors[0] !== $vendor_id ) {
			wc_add_notice( 'You can only purchase from one vendor at a time.', 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Add Assigned location setting to vendor profile.
	 *
	 * @param WP_User $user User object.
	 *
	 * @return void
	 */
	public function add_location_setting_to_vendor_profile( $user ) {
		if ( ! $this->is_user_vendor( $user->ID ) ) {
			return;
		}

		$locations = get_posts(
			array(
				'post_type'   => 'orderable_locations',
				'numberposts' => 100,
			)
		);

		$selected_locations = (array) get_user_meta( $user->ID, 'orderable_mv_assigned_locations', true );
		$allow_new_location = get_user_meta( $user->ID, 'orderable_mv_allow_new_location', true );

		?>
			<h2><?php esc_html_e( 'Orderable', 'orderable-pro' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Assign Locations', 'orderable-pro' ); ?></th>
					<td>
						<select multiple="multiple" id="orderable-mv-vendor-assigned-location" name="orderable_mv_assigned_locations[]" class="select2" style="width: 400px">
							<?php
							foreach ( $locations as $location ) {
								$location_obj = new Orderable_Location_Single_Pro( $location->ID );
								$location_id  = $location_obj->location_data['location_id'];
								echo '<option value="' . esc_attr( $location_id ) . '"' . ( in_array( (string) $location_id, $selected_locations, true ) ? ' selected="selected"' : '' ) . '>' . esc_attr( $location->post_title ) . '</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e( 'Allow this Vendor to create new Locations', 'orderable-pro' ); ?>
					</th>
					<td>
						<select name="orderable_mv_allow_new_location" id="">
							<option <?php selected( $allow_new_location, 'as_per_global' ); ?> value="as_per_global">As per the global settings</option>
							<option <?php selected( $allow_new_location, 'yes' ); ?> value="yes">Yes</option>
							<option <?php selected( $allow_new_location, 'no' ); ?> value="no">No</option>
						</select>
					</td>
				</tr>
			</table>
			<script>
				jQuery("#orderable-mv-vendor-assigned-location").select2();
			</script>
		<?php
		// Add this to enqueue select2 script and style.
		wp_enqueue_script( 'select2' );
		wp_enqueue_style( 'select2' );
	}

	/**
	 * Save location setting from vendor profile.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function save_location_setting_from_vendor_profile( $user_id ) {
		if ( ! $this->is_user_vendor( $user_id ) ) {
			return;
		}

		$assigned_locations  = filter_input( INPUT_POST, 'orderable_mv_assigned_locations', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$allow_new_locations = filter_input( INPUT_POST, 'orderable_mv_allow_new_location', FILTER_SANITIZE_SPECIAL_CHARS );

		update_user_meta( $user_id, 'orderable_mv_assigned_locations', $assigned_locations );
		update_user_meta( $user_id, 'orderable_mv_allow_new_location', $allow_new_locations );
	}

	/**
	 * Check if vendor is allowed to edit location.
	 *
	 * @param int $vendor_id   Vendor ID.
	 * @param int $location_id Location ID.
	 *
	 * @return bool
	 */
	public function is_vendor_allowed_to_override_location( $vendor_id, $location_id ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$global_allowed = Orderable_Settings::get_setting( "integrations_{$this->plugin_name}_allow_vendor_override_location" );

		if ( 'new' === $location_id ) {
			return $this->is_vendor_allowed_to_create_locations( $vendor_id );
		}

		if ( 'dont_allow' === $global_allowed ) {
			return false;
		}

		if ( 'all' === $global_allowed ) {
			return true;
		}

		$assigned_locations = get_user_meta( $vendor_id, 'orderable_mv_assigned_locations', true );

		if ( empty( $assigned_locations ) ) {
			return false;
		}

		return in_array( (string) $location_id, $assigned_locations, true );
	}

	/**
	 * Check if vendor is allowed to create location.
	 *
	 * @param int $vendor_id Vendor ID.
	 *
	 * @return bool
	 */
	public function is_vendor_allowed_to_create_locations( $vendor_id ) {
		$global_allowed = Orderable_Settings::get_setting( "integrations_{$this->plugin_name}_allow_vendor_create_location" );
		$user_meta      = get_user_meta( $vendor_id, 'orderable_mv_allow_new_location', true );

		if ( 'as_per_global' === $user_meta || empty( $user_meta ) ) {
			return 'yes' === $global_allowed;
		}

		if ( 'no' === $user_meta ) {
			return false;
		}

		return true;
	}

	/**
	 * Insert location post type and table.
	 *
	 * @param array $data Posted Data.
	 *
	 * @return bool|int
	 */
	public function create_location( $data ) {
		$vendor_id = get_current_user_id();
		$new_post  = wp_insert_post(
			array(
				'post_title'  => $data['orderable_location_name'],
				'post_type'   => Orderable_Multi_Location_Pro::$post_type_key,
				'post_status' => 'publish',
				'post_author' => $vendor_id,
			)
		);

		if ( empty( $new_post ) ) {
			return false;
		}

		global $wpdb;

		$insert = array(
			'post_id'                          => $new_post,
			'title'                            => $data['orderable_location_name'],
			'override_default_open_hours'      => (int) $data['orderable_override_open_hours'],
			'open_hours'                       => ! empty( $data['orderable_settings'] ) ? serialize( $data['orderable_settings']['store_general_open_hours'] ) : '',
			'delivery'                         => (int) 'yes' === $data['orderable_location_store_services_delivery'],
			'pickup'                           => (int) 'yes' === $data['orderable_location_store_services_pickup'],
			'pickup_hours_same_as_delivery'    => (int) $data['orderable_location_service_hours_pickup_same_as_delivery'],
			'asap_date'                        => (int) 'yes' === $data['orderable_location_order_options_asap_delivery_date'],
			'asap_time'                        => (int) 'yes' === $data['orderable_location_order_options_asap_delivery_time'],
			'lead_time'                        => $data['orderable_location_lead_time'],
			'lead_time_period'                 => empty( $data['orderable_location_lead_time_period'] ) ? 'days' : $data['orderable_location_lead_time_period'],
			'preorder'                         => $data['orderable_location_preorder_days'],
			'delivery_days_calculation_method' => $data['orderable_location_delivery_days_calculation_method'],
			'enable_default_holidays'          => $data['orderable_location_enable_default_holidays'],
		);

		$store_address = array(
			'address_line_1' => $data['orderable_address_line_1'],
			'address_line_2' => $data['orderable_address_line_2'],
			'city'           => $data['orderable_city'],
			'country_state'  => $data['orderable_country_state'],
			'postcode_zip'   => $data['orderable_post_code_zip'],
		);

		$insert = array_merge( $insert, $store_address );

		$wpdb->insert(
			$wpdb->orderable_locations,
			$insert
		);

		$new_location_id = $wpdb->insert_id;

		// Assign location to vendor.
		$assigned_locations   = (array) get_user_meta( $vendor_id, 'orderable_mv_assigned_locations', true );
		$assigned_locations[] = (string) $new_location_id;
		$prefix               = $this->get_meta_prefix( $new_location_id );

		update_user_meta( $vendor_id, 'orderable_mv_assigned_locations', $assigned_locations );
		update_user_meta( $vendor_id, $prefix . 'override', 'yes' );

		update_post_meta( $new_post, 'orderable_mv_vendor_id', $vendor_id );

		return $new_location_id;
	}

	/**
	 * Enqueue location assets.
	 */
	public function enqueue_location_assets() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_style(
			'jquery-ui-css',
			ORDERABLE_URL . 'inc/vendor/wp-settings-framework/assets/vendor/jquery-ui/jquery-ui.css',
			array(),
			ORDERABLE_VERSION
		);

		wp_enqueue_style(
			'orderable-mv-location-css',
			ORDERABLE_URL . 'inc/modules/location/assets/admin/css/location' . $suffix . '.css',
			array( 'jquery-ui-css' ),
			ORDERABLE_VERSION
		);

		wp_enqueue_style( 'multi-location-pro-css', ORDERABLE_PRO_URL . 'inc/modules/multi-location-pro/assets/frontend/css/multi-locations.css', array(), ORDERABLE_PRO_VERSION );
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_style( 'orderable-css', ORDERABLE_ASSETS_URL . 'admin/css/main' . $suffix . '.css', array(), ORDERABLE_VERSION );
		wp_enqueue_style( 'select2', ORDERABLE_ASSETS_URL . 'vendor/select2/select2' . $suffix . '.css', array(), ORDERABLE_VERSION );

		wp_enqueue_media();
		wp_enqueue_script( 'orderable-admin', ORDERABLE_ASSETS_URL . 'admin/js/main' . $suffix . '.js', array( 'jquery' ), ORDERABLE_VERSION, true );
		wp_enqueue_script( 'orderable-layouts', ORDERABLE_URL . 'inc/modules/layouts/assets/admin/js/main' . $suffix . '.js', array( 'jquery', 'thickbox' ), ORDERABLE_VERSION, true );

		wp_enqueue_script( 'orderable-select2', ORDERABLE_ASSETS_URL . 'vendor/select2/select2' . $suffix . '.js', array( 'jquery' ), ORDERABLE_VERSION, true );
		wp_enqueue_script( 'orderable-jquery-multi-select', ORDERABLE_ASSETS_URL . 'vendor/jquery-multi-select/jquery.multi-select' . $suffix . '.js', array( 'jquery' ), ORDERABLE_VERSION, true );

		wp_enqueue_script( 'orderable-location-js', ORDERABLE_URL . 'inc/modules/location/assets/admin/js/main' . $suffix . '.js', array(), ORDERABLE_VERSION, true );

		wp_enqueue_script( 'serialize-json', ORDERABLE_PRO_ASSETS_URL . 'vendor/jquery.serializejson.min.js', array(), ORDERABLE_VERSION, true );

		wp_localize_script(
			'orderable',
			'orderable_vars',
			array(
				'i18n' => array(
					'confirm_remove_service_hours' => __( 'Are you sure you want to remove these service hours?', 'orderable' ),
				),
			)
		);

		wp_localize_script(
			'orderable-location-js',
			'orderable_dz_js_vars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'orderable-dz-nonce' ),
				'action'   => 'orderable_dz_crud',
				'text'     => array(
					'modal_add'            => esc_html__( 'Add Delivery Zone', 'orderable-pro' ),
					'modal_update'         => esc_html__( 'Update Delivery Zone', 'orderable-pro' ),
					'zone_title'           => esc_html__( 'Delivery Zone', 'orderable-pro' ),
					'zone_edit'            => esc_html__( 'Edit', 'orderable-pro' ),
					'zone_remove'          => esc_html__( 'Remove', 'orderable-pro' ),
					'zone_confirm_remove'  => esc_html__( 'Are you sure you want to remove this zone?', 'orderable-pro' ),
					'zone_confirm_delete'  => esc_html__( 'Are you sure you want to delete this zone?', 'orderable-pro' ),
					'successfully_added'   => esc_html__( 'successfully added!', 'orderable-pro' ),
					'successfully_updated' => esc_html__( 'successfully updated!', 'orderable-pro' ),
					'successfully_removed' => esc_html__( 'successfully removed!', 'orderable-pro' ),
				),
			)
		);
	}

	/**
	 * Ensure that the shipping zone is available for the vendor's timeslots.
	 *
	 * Use filter `orderable_get_time_slots_for_zone` to modify the timeslot for the given zone.
	 * Since the vendor timeslots are stored in the vendor's user meta and not in the `wp_orderable_location_delivery_zones_lookup`
	 * table, we need to use our own logic to filter the timeslots.
	 *
	 * This code runs on Edit location page.
	 *
	 * @param array $time_slots Array of timeslots IDs.
	 * @param array $zone_id    Zone ID.
	 *
	 * @return array
	 */
	public function modify_timeslot_for_zone( $time_slots, $zone_id ) {
		$location_id_get = isset( $_GET['orderable_mv_location_id'] ) ? sanitize_text_field( wp_unslash( $_GET['orderable_mv_location_id'] ) ) : '';

		if ( is_admin() || empty( $location_id_get ) ) {
			return $time_slots;
		}

		$location_data = $this->get_location_data_for_vendor( $location_id_get );

		if ( empty( $location_data['data']['service_hours'] ) || ! is_array( $location_data['data']['service_hours'] ) ) {
			return $time_slots;
		}

		$updated_timeslots = array();
		foreach ( $location_data['data']['service_hours']['delivery'] as $hour ) {
			if ( empty( $hour['zones'] ) ) {
				continue;
			}

			foreach ( $hour['zones'] as $zone_encoded ) {
				$zone = json_decode( $zone_encoded, true );
				if ( intval( $zone_id ) === intval( $zone['zone_id'] ) ) {
					$updated_timeslots[] = intval( $hour['time_slot_id'] );
				}
			}
		}

		return $updated_timeslots;
	}

	/**
	 * Add or Remove locations based on vendor's overriden settings.
	 *
	 * @param array $locations         Array of location IDs.
	 * @param array $shipping_zone_ids Array of shipping zone IDs.
	 * @param bool  $return_ids        Return IDs.
	 *
	 * @return locations
	 */
	public function get_locations_for_shipping_zones( $locations, $shipping_zone_ids, $return_ids ) {
		$vendors = $this->get_vendors_from_cart();

		if ( empty( $vendors ) ) {
			return $locations;
		}

		$vendor_id = $vendors[0];

		$all_locations     = Orderable_Multi_Location_Pro_Helper::get_all_locations();
		$location_ids      = array();
		$shipping_zone_ids = array_map( 'intval', $shipping_zone_ids );

		foreach ( $all_locations as $location ) {
			$location_id = $location->location_data['location_id'];

			$data = $this->get_location_data_for_vendor( $location_id, $vendor_id );

			if ( empty( $data ) || 'yes' !== $data['override'] || empty( $data['data'] ) ) {
				$location_ids[] = $location_id;
				continue;
			}

			foreach ( $data['data']['service_hours']['delivery'] as $hour ) {
				// If no zone selected then the location is available for all zones.
				if ( empty( $hour['zones'] ) ) {
					$location_ids[] = $location_id;
					continue;
				}

				foreach ( $hour['zones'] as $zone_encoded ) {
					$zone = json_decode( $zone_encoded, true );

					if ( in_array( (int) $zone['zone_id'], $shipping_zone_ids, true ) ) {
						$location_ids[] = $location_id;
					}
				}
			}
		}

		if ( $return_ids ) {
			return array_map( 'intval', $location_ids );
		}

		$location_obj = array();
		foreach ( $location_ids as $location_id ) {
			$location_obj[] = new Orderable_Location_Single_Pro( $location_id );
		}

		return $location_obj;
	}

	/**
	 * Modify the description for the enable holidays setting.
	 *
	 * @param string $description Description.
	 *
	 * @return string
	 */
	public function modify_holidays_setting_description( $description ) {
		$location_id = isset( $_GET['orderable_mv_location_id'] ) ? sanitize_text_field( wp_unslash( $_GET['orderable_mv_location_id'] ) ) : '';

		if ( empty( $location_id ) ) {
			return $description;
		}

		return __( 'Enable the site\'s global holidays for this location.', 'orderable-pro' );
	}

	/**
	 * Modify the description for the open hours setting.
	 *
	 * @param string $description Description.
	 *
	 * @return string
	 */
	public function modify_open_hours_setting_description( $description ) {
		$location_id = isset( $_GET['orderable_mv_location_id'] ) ? sanitize_text_field( wp_unslash( $_GET['orderable_mv_location_id'] ) ) : '';

		if ( empty( $location_id ) ) {
			return $description;
		}

		return __( 'Override the site\'s default open hours set by the site admin.', 'orderable-pro' );
	}

	/**
	 * Get array of all location posts for the current vendor.
	 *
	 * @return array
	 */
	public function get_vendor_locations() {
		return get_posts(
			array(
				'post_type'      => 'orderable_locations',
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => 'orderable_mv_vendor_id',
						'value'   => get_current_user_id(),
						'compare' => '=',
					),
					array(
						'key'     => 'orderable_mv_vendor_id',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
	}

	/**
	 * Add support for 'author' in location post type.
	 *
	 * @param array  $args      Post type args.
	 * @param string $post_type Post type key.
	 *
	 * @return array
	 */
	public function add_author_support_for_location_post_type( $args, $post_type ) {
		if ( Orderable_Multi_Location_Pro::$post_type_key !== $post_type ) {
			return $args;
		}

		$args['supports'][] = 'author';

		return $args;
	}

	/**
	 * When customer has a selected location which is exclusive to a vendor A,
	 * and the customers removes the product from vendor A and adds product
	 * from vendor B then the location from vendor A should be deselected.
	 */
	public function deselect_location_if_product_doesnt_belong_to_vendor() {
		$location_data = Orderable_Multi_Location_Pro::get_selected_location_data_from_session();

		if ( empty( $location_data['id'] ) ) {
			return;
		}

		$location         = new Orderable_Location_Single_Pro( $location_data['id'] );
		$location_post_id = $location->location_data['post_id'];
		$vendor_id        = get_post_meta( $location_post_id, 'orderable_mv_vendor_id', true );

		// The currently selected location is not a vendor created location/not exclusive.
		if ( empty( $vendor_id ) ) {
			return;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$cart_vendor_id = $this->get_vendor_by_product( $cart_item['product_id'] );

			if ( intval( $cart_vendor_id ) !== intval( $vendor_id ) ) {
				// Unselect the location.
				WC()->session->set( 'orderable_multi_location_id', 0 );
			}
		}
	}

	/**
	 * Get formatted services for the location.
	 *
	 * @param object $location Location object.
	 *
	 * @return string
	 */
	public function get_formatted_services( $location ) {
		$location_data = $location->location_data;
		$vendor_data   = $this->get_location_data_for_vendor( $location_data['location_id'], get_current_user_id() );

		if ( empty( $vendor_data['override'] ) || 'yes' !== $vendor_data['override'] || empty( $vendor_data['data'] ) ) {
			return $location->get_formatted_services();
		}

		$services = array();

		if ( $this->convert_bool( $vendor_data, 'orderable_location_store_services_pickup' ) ) {
			$services[] = 'Pickup';
		}

		if ( $this->convert_bool( $vendor_data, 'orderable_location_store_services_delivery' ) ) {
			$services[] = 'Delivery';
		}

		return implode( ', ', $services );
	}

	/**
	 * Get formatted address for the location.
	 *
	 * @param object $location Location object.
	 *
	 * @return string
	 */
	public function get_formatted_address( $location ) {
		$location_data = $location->location_data;
		$vendor_data   = $this->get_location_data_for_vendor( $location_data['location_id'], get_current_user_id() );

		if ( empty( $vendor_data['override'] ) || 'yes' !== $vendor_data['override'] || empty( $vendor_data['data'] ) ) {
			return $location->get_formatted_address();
		}

		$country_state = $vendor_data['data']['orderable_country_state'];

		if ( str_contains( $country_state, ':' ) ) {
			$country_state = explode( ':', $country_state );
			$country       = current( $country_state );
			$state         = end( $country_state );
		} else {
			$country = $country_state;
			$state   = '';
		}

		$data = array(
			'address_1' => $vendor_data['data']['orderable_address_line_1'],
			'address_2' => $vendor_data['data']['orderable_address_line_2'],
			'city'      => $vendor_data['data']['orderable_city'],
			'state'     => $state,
			'country'   => $country,
			'postcode'  => $vendor_data['data']['orderable_post_code_zip'],
		);

		return WC()->countries->get_formatted_address( $data, ', ' );
	}

	/**
	 * Generate random ID.
	 */
	public function generate_random_id() {
		return wp_rand( 10000, 999999999 );
	}
}
