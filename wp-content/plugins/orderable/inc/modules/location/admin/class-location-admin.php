<?php
/**
 * Module: Location.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Addons module class.
 */
class Orderable_Location_Admin {
	/**
	 * Init.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'load_classes' ) );
		add_action( 'current_screen', array( __CLASS__, 'save_data' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_styles' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
		add_filter( 'orderable_onboard_settings_saved', array( __CLASS__, 'onboard_settings_saved' ) );

		add_filter( 'orderable_valid_admin_pages', array( __CLASS__, 'add_valid_admin_pages' ) );
	}

	/**
	 * Load classes.
	 *
	 * @return void
	 */
	public static function load_classes() {
		$meta_boxes = array(
			'location-store-address-meta-box'  => 'Orderable_Location_Store_Address_Meta_Box',
			'location-open-hours-meta-box'     => 'Orderable_Location_Open_Hours_Meta_Box',
			'location-store-services-meta-box' => 'Orderable_Location_Store_Services_Meta_Box',
			'location-order-options-meta-box'  => 'Orderable_Location_Order_Options_Meta_Box',
			'location-holidays-meta-box'       => 'Orderable_Location_Holidays_Meta_Box',
		);

		Orderable_Helpers::load_classes(
			$meta_boxes,
			'location/admin/meta-boxes',
			ORDERABLE_MODULES_PATH
		);
	}

	/**
	 * Check if we should load the assets.
	 *
	 * @param string $asset_type The type of asset:`styles` or `scripts`.
	 * @return boolean
	 */
	protected static function should_load_assets( $asset_type ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$current_screen = get_current_screen();

		if ( empty( $current_screen->id ) ) {
			return false;
		}

		/**
		 * Filter the pages allowed to load the location assets.
		 *
		 * @since 1.18.0
		 * @hook orderable_location_allowed_pages_to_load_assets
		 * @param  array  $allowed_pages     The allowed pages.
		 * @param  string $asset_type        The type of asset:`styles` or `scripts`.
		 * @param  string $current_screen_id The ID of screen.
		 * @return array New value
		 */
		$allowed_pages = apply_filters(
			'orderable_location_allowed_pages_to_load_assets',
			array(
				'orderable_page_orderable-location',
				'orderable_page_orderable-delivery-zones',
				'edit-orderable_locations',
				'woocommerce_page_wc-settings',
			),
			$asset_type,
			$current_screen->id
		);

		return is_array( $allowed_pages ) && in_array( $current_screen->id, $allowed_pages, true );
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @return void
	 */
	public static function load_styles() {
		if ( ! self::should_load_assets( 'styles' ) ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_style(
			'jquery-ui-css',
			ORDERABLE_URL . 'inc/vendor/wp-settings-framework/assets/vendor/jquery-ui/jquery-ui.css',
			array(),
			ORDERABLE_VERSION
		);

		wp_enqueue_style(
			'orderable-location-css',
			ORDERABLE_URL . 'inc/modules/location/assets/admin/css/location' . $suffix . '.css',
			array( 'woocommerce_admin_styles', 'jquery-ui-css' ),
			ORDERABLE_VERSION
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @return void
	 */
	public static function load_scripts() {
		if ( ! self::should_load_assets( 'scripts' ) ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'orderable-location-js',
			ORDERABLE_URL . 'inc/modules/location/assets/admin/js/main' . $suffix . '.js',
			array( 'wc-enhanced-select', 'jquery-ui-datepicker' ),
			ORDERABLE_VERSION,
			true
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
	 * Add valid admin page.
	 *
	 * @param array $pages The admin pages slug.
	 */
	public static function add_valid_admin_pages( $pages = array() ) {
		$pages[] = 'orderable-location';

		return $pages;
	}

	/**
	 * Add settings page.
	 */
	public static function add_settings_page() {
		add_submenu_page(
			'orderable',
			__( 'Location', 'orderable' ),
			__( 'Location', 'orderable' ),
			'manage_options',
			'orderable-location',
			array( __CLASS__, 'page_content' ),
			3
		);
	}

	/**
	 * Page content.
	 */
	public static function page_content() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'orderable' ) );
		}
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php echo esc_html__( 'Location', 'orderable' ); ?>
			</h1>

			<form method="post">
				<?php self::save_location_nonce_field(); ?>
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<?php self::metabox_ui_wrapper( Orderable_Location_Store_Address_Meta_Box::class ); ?>
							<?php self::metabox_ui_wrapper( Orderable_Location_Open_Hours_Meta_Box::class ); ?>
							<?php self::metabox_ui_wrapper( Orderable_Location_Store_Services_Meta_Box::class ); ?>
							<?php self::metabox_ui_wrapper( Orderable_Location_Order_Options_Meta_Box::class ); ?>
							<?php self::metabox_ui_wrapper( Orderable_Location_Holidays_Meta_Box::class ); ?>
						</div>
						<div id="postbox-container-1" class="postbox-container">
							<div id="major-publishing-actions" style="overflow: hidden; margin-bottom: 20px; border: 1px solid #DCDCDE;">
								<button class="button button-primary button-large" type="submit">
									<?php echo esc_html__( 'Save Changes', 'orderable' ); ?>
								</button>
							</div>

							<div class="orderable-cta">
								<h3 class="orderable-cta__title"><?php _e( 'Unlock Multi-Location Power with Orderable Pro', 'orderable' ); ?></h3>
								<p class="orderable-cta__description"><?php _e( 'Manage multiple locations effortlessly with Orderable Pro, perfect for businesses with more than one location. Take control of your delivery and pickup services like never before.', 'orderable' ); ?></p>
								<p><a href="<?php echo esc_url( Orderable_Helpers::get_pro_url( 'multi-location', 'pricing' ) ); ?>" class="orderable-admin-button orderable-admin-button--pro" target="_blank"><span class="dashicons dashicons-star-filled"></span> <?php _e( 'Upgrade Now', 'orderable' ); ?></a></p>
								<p><?php _e( 'or', 'orderable' ); ?> <a href="<?php echo esc_url( Orderable_Helpers::get_pro_url( 'multi-location' ) ); ?>" target="_blank"><?php _e( 'learn more', 'orderable' ); ?></a></p>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Add meta box HTML elements wrapper.
	 *
	 * @param string $meta_box_class The meta box class.
	 * @return void
	 */
	public static function metabox_ui_wrapper( $meta_box_class ) {
		if ( ! class_exists( $meta_box_class ) ) {
			return;
		}

		if ( ! is_callable( $meta_box_class, 'output' ) ) {
			return;
		}

		$title = is_callable( $meta_box_class, 'get_title' ) ? call_user_func( array( $meta_box_class, 'get_title' ) ) : '';
		?>
			<div id="<?php echo esc_attr( strtolower( $meta_box_class ) ); ?>" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php echo esc_html( $title ); ?>
					</h2>
				</div>
				<div class="inside">
					<?php call_user_func( array( $meta_box_class, 'output' ) ); ?>
				</div>
			</div>
		<?php
	}

	/**
	 * Save the orderable location data.
	 *
	 * @return void
	 */
	public static function save_data() {
		global $post;

		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$current_screen = get_current_screen();

		if ( empty( $current_screen->id ) ) {
			return;
		}

		if (
			'orderable_page_orderable-location' !== $current_screen->id &&
			( empty( $post ) || 'orderable_locations' !== $post->post_type )
		) {
			return;
		}

		if ( empty( $_POST ) || ! check_admin_referer( 'orderable_location_save', '_wpnonce_orderable_location' ) ) {
			return;
		}

		/**
		 * Filter the location data to be saved.
		 *
		 * @since 1.8.0
		 * @hook orderable_location_get_save_data
		 * @param  array $data    The data to be saved.
		 * @return array New value
		 */
		$data = apply_filters( 'orderable_location_get_save_data', get_option( 'orderable_settings', array() ) );

		if ( empty( $data ) ) {
			return;
		}

		/**
		 * Fires when location data is saved.
		 *
		 * @since 1.8.0
		 * @hook orderable_location_save_data
		 * @param  array $data The location data.
		 */
		do_action( 'orderable_location_save_data', $data );

		self::clear_location_data_cache( $data );
	}

	/**
	 * Clear location cache when updated.
	 *
	 * @param array $data The data saved.
	 * @return void
	 */
	protected static function clear_location_data_cache( $data ) {
		global $post;

		$post_id     = ! empty( $post ) ? $post->ID : null;
		$location_id = Orderable_Location::get_location_id( $post_id );

		if ( empty( $location_id ) ) {
			return;
		}

		$location_data_cache_key = 'orderable_get_location_data_' . $location_id;
		$time_slots_cache_key    = 'orderable_time_slots_' . $location_id;

		wp_cache_delete( $location_data_cache_key );
		wp_cache_delete( $time_slots_cache_key . '_delivery' );
		wp_cache_delete( $time_slots_cache_key . '_pickup' );
		wp_cache_delete( 'orderable_zones' );

		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			wp_cache_delete( 'orderable_time_slots_for_zone_' . $zone['zone_id'] );
			wp_cache_delete( "has_zone_{$location_id}_{$zone['zone_id']}_true" );
			wp_cache_delete( "has_zone_{$location_id}_{$zone['zone_id']}_false" );
			wp_cache_delete( "{$time_slots_cache_key}_delivery_{$zone['zone_id']}" );
			wp_cache_delete( "{$time_slots_cache_key}_pickup_{$zone['zone_id']}" );
		}

		if ( empty( $data['store_general_service_hours_delivery'] ) || ! is_array( $data['store_general_service_hours_delivery'] ) ) {
			return;
		}

		foreach ( $data['store_general_service_hours_delivery'] as $time_slot ) {
			if ( empty( $time_slot['time_slot_id'] ) ) {
				continue;
			}

			wp_cache_delete( 'orderable_zones_time_slot_' . $time_slot['time_slot_id'] );
		}
	}

	/**
	 * Output a nonce field to save the location data.
	 *
	 * @return void
	 */
	public static function save_location_nonce_field() {
		wp_nonce_field( 'orderable_location_save', '_wpnonce_orderable_location' );
	}

	/**
	 * Get the field value sent via POST.
	 *
	 * @param string $field_name The field name to retrieve.
	 * @return string
	 */
	public static function get_posted_value( $field_name ) {
		$nonce = empty( $_POST['_wpnonce_orderable_location'] ) ? false : sanitize_text_field( wp_unslash( $_POST['_wpnonce_orderable_location'] ) );

		if ( ! wp_verify_nonce( $nonce, 'orderable_location_save' ) ) {
			return '';
		}

		if ( ! is_string( $field_name ) || empty( $_POST[ $field_name ] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );
	}

	/**
	 * Add location data when onboard settings are saved.
	 *
	 * @param array $fields Field data.
	 */
	public static function onboard_settings_saved( $fields = array() ) {
		$orderable_fields = isset( $fields['orderable_settings'] ) ? $fields['orderable_settings'] : array();

		if ( empty( $orderable_fields ) ) {
			return;
		}

		global $wpdb;

		// Format open days.
		$open_days = ! empty( $orderable_fields['iconic_onboard_days'] ) ? array_map( 'absint', $orderable_fields['iconic_onboard_days'] ) : array();

		// Create an array with 7 items, each containing $orderable_fields['iconic_onboard_open_hours'].
		$posted_open_hours = ! empty( $orderable_fields['iconic_onboard_open_hours'] ) ? $orderable_fields['iconic_onboard_open_hours'] : array();

		if ( ! empty( $posted_open_hours ) ) {
			$posted_open_hours['enabled']    = false;
			$posted_open_hours['max_orders'] = '';

			$open_hours = array_fill( 0, 7, $posted_open_hours );

			// Update the 'enabled' key for each day based on the existence of the day key in $open_days
			foreach ( $open_hours as $day_key => $day_data ) {
				$open_hours[ $day_key ]['enabled'] = in_array( $day_key, $open_days, true );
			}
		}

		$data = array(
			'override_default_open_hours' => 1,
			'open_hours'                  => maybe_serialize( $open_hours ),
			'delivery'                    => ! empty( $orderable_fields['iconic_onboard_services'] ) && in_array( 'flat_rate', $orderable_fields['iconic_onboard_services'], true ),
			'pickup'                      => ! empty( $orderable_fields['iconic_onboard_services'] ) && in_array( 'local_pickup', $orderable_fields['iconic_onboard_services'], true ),
			'address_line_1'              => isset( $orderable_fields['iconic_onboard_business_address'] ) ? $orderable_fields['iconic_onboard_business_address'] : '',
			'address_line_2'              => isset( $orderable_fields['iconic_onboard_business_address_2'] ) ? $orderable_fields['iconic_onboard_business_address_2'] : '',
			'city'                        => isset( $orderable_fields['iconic_onboard_business_city'] ) ? $orderable_fields['iconic_onboard_business_city'] : '',
			'country_state'               => isset( $orderable_fields['iconic_onboard_default_country'] ) ? $orderable_fields['iconic_onboard_default_country'] : '',
			'postcode_zip'                => isset( $orderable_fields['iconic_onboard_business_postcode'] ) ? $orderable_fields['iconic_onboard_business_postcode'] : '',
			'is_main_location'            => 1,
		);

		// Get main location ID.
		$main_location_id = Orderable_Location::get_main_location_id();

		if ( empty( $main_location_id ) ) {
			$update = $wpdb->insert(
				$wpdb->orderable_locations,
				$data
			);
		} else {
			$update = $wpdb->update(
				$wpdb->orderable_locations,
				$data,
				array(
					'location_id' => $main_location_id,
				),
				null,
				array(
					'location_id' => '%d',
				)
			);
		}

		if ( ! $update || is_wp_error( $update ) ) {
			return;
		}

		if ( ! empty( $open_hours ) && ! empty( $open_days ) ) {
			$main_location_id = $main_location_id ? $main_location_id : Orderable_Location::get_main_location_id();

			// Check if any time slots exists for this location.
			$existing_time_slots = intval(
				$wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->orderable_location_time_slots} WHERE location_id = %d",
						$main_location_id
					)
				)
			);

			// If not, let's add one.
			if ( $existing_time_slots <= 0 ) {
				$wpdb->insert(
					$wpdb->orderable_location_time_slots,
					array(
						'location_id'  => $main_location_id,
						'service_type' => 'delivery',
						'days'         => maybe_serialize( array_map( 'strval', $open_days ) ),
						'period'       => 'all-day',
						'time_from'    => '',
						'time_to'      => '',
						'frequency'    => '',
						'cutoff'       => '',
						'max_orders'   => '',
						'has_zones'    => 0,
					)
				);
			}
		}
	}
}
