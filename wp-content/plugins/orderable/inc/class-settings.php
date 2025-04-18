<?php
/**
 * Settings methods.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings class.
 */
class Orderable_Settings {
	/**
	 * Settings framework instance.
	 *
	 * @var null|Orderable_Settings_Framework
	 */
	public static $settings_framework = null;

	/**
	 * Settings.
	 *
	 * @var array
	 */
	public static $settings = array();

	/**
	 * Option group.
	 *
	 * @var string
	 */
	protected static $option_group = 'orderable';

	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_opt_in' ) );
		add_action( 'init', array( __CLASS__, 'init_settings' ) );
		add_filter( 'plugin_action_links_' . ORDERABLE_BASENAME, array( __CLASS__, 'plugin_settings_link' ) );
		add_action( 'init', array( __CLASS__, 'init_onboarding' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ), 1 );
		add_filter( 'orderable_default_settings', array( __CLASS__, 'default_settings' ) );
		add_filter( 'wpsf_register_settings_orderable', array( __CLASS__, 'register_settings' ) );
		add_filter( 'wpsf_register_settings_orderable', array( __CLASS__, 'reorder_settings_tabs' ), 200 );
		add_filter( 'iconic_onboard_save_orderable_result', array( __CLASS__, 'save_onboard_settings' ), 10, 2 );
		add_filter( 'wpsf_title_orderable', array( __CLASS__, 'settings_logo' ) );
		add_action( 'wpsf_after_title_orderable', array( __CLASS__, 'settings_header' ) );

		// Add category fields.
		add_action( 'product_cat_add_form_fields', array( __CLASS__, 'add_category_fields' ), 20 );
		add_action( 'product_cat_edit_form_fields', array( __CLASS__, 'edit_category_fields' ), 20 );
		add_action( 'created_term', array( __CLASS__, 'save_category_fields' ), 10, 3 );
		add_action( 'edit_term', array( __CLASS__, 'save_category_fields' ), 10, 3 );

		// Admin Pointers.
		add_action( 'orderable_admin_assets_enqueued', array( __CLASS__, 'admin_pointers' ) );
		add_filter( 'orderable_admin_script_deps', array( __CLASS__, 'admin_script_deps' ) );
	}

	/**
	 * Init settings.
	 */
	public static function init_settings() {
		require_once ORDERABLE_VENDOR_PATH . 'wp-settings-framework/wp-settings-framework.php';

		self::$settings_framework = new Orderable_Settings_Framework( null, self::$option_group );
		self::$settings           = self::$settings_framework->get_settings();
	}

	/**
	 * Add settings page link to plugin listing.
	 *
	 * @param array $links
	 *
	 * @return array
	 */




	public static function plugin_settings_link( $links = array() ) {
		$settings_url = admin_url( 'admin.php?page=orderable-settings' );
		$links[]      = sprintf( '<a href="%s">%s</a>', esc_url( $settings_url ), esc_html( __( 'Settings', 'orderable' ) ) );

		return $links;
	}

	/**
	 * Add settings page.
	 */
	public static function add_settings_page() {
		$icon = self::get_orderable_svg_icon();

		add_menu_page( 'Orderable', 'Orderable', 'manage_options', 'orderable', null, $icon, 54 );

		self::$settings_framework->add_settings_page(
			array(
				'parent_slug' => 'orderable',
				'page_title'  => __( 'Orderable Settings', 'orderable' ),
				'menu_title'  => __( 'Settings', 'orderable' ),
				'capability'  => 'manage_options',
			)
		);

		remove_submenu_page( 'orderable', 'orderable' );
	}

	/**
	 * Add default settings.
	 *
	 * @param array $default_settings
	 *
	 * @return array
	 */
	public static function default_settings( $default_settings = array() ) {
		$default_settings['style_style_color']         = '#000000';
		$default_settings['style_style_buttons']       = 'rounded';
		$default_settings['style_products_title_size'] = 20;
		$default_settings['style_products_price_size'] = 18;

		return $default_settings;
	}

	/**
	 * Register settings.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function register_settings( $settings = array() ) {
		$settings = array(
			'tabs'     => array(),
			'sections' => array(),
		);

		$settings['tabs'] = array(
			array(
				'id'       => 'dashboard',
				'title'    => __( 'Dashboard', 'orderable' ),
				'priority' => 0,
			),
			array(
				'id'       => 'style',
				'title'    => __( 'Style', 'orderable' ),
				'priority' => 100,
			),
		);

		$settings['sections']['dashboard_getting_started'] = array(
			'tab_id'              => 'dashboard',
			'section_id'          => 'getting_started',
			'section_title'       => __( 'Getting Started', 'orderable' ),
			'section_description' => sprintf( __( 'Below you will find a playlist of useful "getting started" videos for Orderable. If you need any help getting things set up, please <a href="%s" target="_blank">reach out to our support team</a>.', 'orderable' ), 'https://my.orderable.com/support/?utm_source=orderable&utm_medium=plugin&utm_campaign=getting-started' ),
			'section_order'       => 0,
			'fields'              => array(
				'playlist' => array(
					'id'     => 'playlist',
					'title'  => '',
					'type'   => 'custom',
					'output' => wp_oembed_get( 'https://youtube.com/playlist?list=PLUUdHDDAkhAV8-k86JRjB34Xtbp1y6kXh' ),
				),
			),
		);

		$settings['sections']['style'] = array(
			'tab_id'              => 'style',
			'section_id'          => 'style',
			'section_title'       => __( 'General', 'orderable' ),
			'section_description' => '',
			'section_order'       => 10,
			'fields'              => array(
				'color'   => array(
					'id'       => 'color',
					'title'    => __( 'Brand Color', 'orderable' ),
					'subtitle' => __( 'Select an accent color to be used for buttons and other elements.', 'orderable' ),
					'type'     => 'color',
					'default'  => self::get_setting_default( 'style_style_color' ),
				),
				'buttons' => array(
					'id'       => 'buttons',
					'title'    => __( 'Button Style', 'orderable' ),
					'subtitle' => __( 'Choose a button style which suits your theme.', 'orderable' ),
					'type'     => 'select',
					'default'  => self::get_setting_default( 'style_style_buttons' ),
					'choices'  => array(
						'rounded' => __( 'Rounded', 'orderable' ),
						'square'  => __( 'Square', 'orderable' ),
					),
				),
			),
		);

		$settings['sections']['products_style'] = array(
			'tab_id'              => 'style',
			'section_id'          => 'products',
			'section_title'       => __( 'Product Cards', 'orderable' ),
			'section_description' => '',
			'section_order'       => 15,
			'fields'              => array(
				'title_size' => array(
					'id'       => 'title_size',
					'title'    => __( 'Product Title Size (px)', 'orderable' ),
					'subtitle' => __( 'Set the product title font size in pixels.', 'orderable' ),
					'type'     => 'number',
					'default'  => self::get_setting_default( 'style_products_title_size' ),
				),
				'price_size' => array(
					'id'       => 'price_size',
					'title'    => __( 'Product Price Size (px)', 'orderable' ),
					'subtitle' => __( 'Set the product price font size in pixels.', 'orderable' ),
					'type'     => 'number',
					'default'  => self::get_setting_default( 'style_products_price_size' ),
				),
			),
		);
				// Add new tab for Time Slots
		$settings['tabs'][] = array(
			'id'       => 'time_slots',
			'title'    => __( 'Time Slots', 'orderable' ),
			'priority' => 200,
		);

		// Add a section under the Time Slots tab
		$settings['sections']['time_slots_section'] = array(
			'tab_id'        => 'time_slots',
			'section_id'    => 'time_slots_section',
			'section_title' => __( 'Available Time Slots', 'orderable' ),
			'section_order' => 10, // Fix the earlier warning too
			'fields'        => array(
				'custom_time_slots' => array(
					'id'       => 'custom_time_slots',
					'title'    => __( 'Time Slots', 'orderable' ),
					'subtitle' => __( 'Check all available time slots.', 'orderable' ),
					'type'     => 'checkboxes',
					'default'  => [],
					'choices'  => array(
						'00' => '12AM',  '01' => '1AM',   '02' => '2AM',   '03' => '3AM',   '04' => '4AM',
						'05' => '5AM',   '06' => '6AM',   '07' => '7AM',   '08' => '8AM',   '09' => '9AM',
						'10' => '10AM',  '11' => '11AM',  '12' => '12PM',  '13' => '1PM',   '14' => '2PM',
						'15' => '3PM',   '16' => '4PM',   '17' => '5PM',   '18' => '6PM',   '19' => '7PM',
						'20' => '8PM',   '21' => '9PM',   '22' => '10PM',  '23' => '11PM',
					),
				),
			),
		);
		return $settings;
	}

	/**
	 * Reorder settings tabs.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function reorder_settings_tabs( $settings = array() ) {
		usort(
			$settings['tabs'],
			function ( $a, $b ) {
				$a['priority'] = empty( $a['priority'] ) ? 0 : absint( $a['priority'] );
				$b['priority'] = empty( $b['priority'] ) ? 0 : absint( $b['priority'] );

				return $a['priority'] - $b['priority'];
			}
		);

		return $settings;
	}

	/**
	 * Get setting default.
	 *
	 * @param string $setting_name
	 *
	 * @return bool|mixed
	 */
	public static function get_setting_default( $setting_name = '' ) {
		if ( empty( $setting_name ) ) {
			return false;
		}

		$defaults = apply_filters( 'orderable_default_settings', array() );

		if ( ! isset( $defaults[ $setting_name ] ) ) {
			return false;
		}

		return $defaults[ $setting_name ];
	}

	/**
	 * Get setting.
	 *
	 * This method is accessible before the settings are assign to self::$settings.
	 *
	 * @param string $setting_name
	 *
	 * @return bool|mixed
	 */
	public static function get_setting( $setting_name = '' ) {
		if ( empty( $setting_name ) ) {
			return false;
		}

		/**
		 * Filter the orderable settings.
		 *
		 * @since 1.8.0
		 * @hook orderable_settings
		 * @param  false|array $settings     The orderable settings.
		 * @param  string      $setting_name The setting name to be retrieved.
		 * @return false|array New value
		 */
		$settings = apply_filters( 'orderable_settings', false, $setting_name );

		if ( isset( $settings[ $setting_name ] ) ) {
			return $settings[ $setting_name ];
		}

		$settings = get_option( self::$option_group . '_settings' );

		if ( isset( $settings[ $setting_name ] ) ) {
			return $settings[ $setting_name ];
		}

		return self::get_setting_default( $setting_name );
	}

	/**
	 * @param string $suffix
	 *
	 * @return bool
	 */
	public static function is_settings_page( $suffix = '' ) {
		if ( ! is_admin() ) {
			return false;
		}

		// Allow bypass from other modules.
		if ( apply_filters( 'orderable_is_settings_page', false ) ) {
			return true;
		}

		$valid_pages = apply_filters(
			'orderable_valid_admin_pages',
			array(
				self::$option_group . '-settings' . $suffix,
			)
		);
		$page        = empty( $_GET['page'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! in_array( $page, $valid_pages, true ) ) {
			return false;
		}

		return true;
	}

	


	/**
	 * Init onboarding.
	 */
	public static function init_onboarding() {
		if ( ! self::is_settings_page() || defined( 'WC_DOING_AJAX' ) ) {
			if ( empty( $_REQUEST['plugin_slug'] ) || 'orderable' !== $_REQUEST['plugin_slug'] ) {
				return;
			}
		}

		include_once ORDERABLE_INC_PATH . 'vendor/iconic-onboard/class-iconic-onboard.php';

		$slides = apply_filters(
			'orderable_onboarding_slides',
			array(
				'welcome'      => array(
					'header_image' => ORDERABLE_URL . '/assets/img/onboarding/orderable-onboarding-header.jpg',
					'title'        => 'Welcome',
					'description'  => 'Thank you for choosing Orderable to add local ordering to your website. This short setup wizard will guide you though configuring Orderable.',
					'button_text'  => 'Begin <span class="dashicons dashicons-arrow-right-alt2"></span>',
					'button_icon'  => '',
				),
				'requirements' => array(
					'title'       => 'Requirements',
					'description' => 'Orderable requires WooCommerce for the order checkout, management, and reporting. In this step we will install WooCommerce for you. This might take a couple of minutes.',
					'button_text' => 'Install &amp; Activate WooCommerce',
					'wait'        => 'install_plugin',
					'json_data'   => array(
						'wait_text'   => __( 'Installing...', 'orderable' ),
						'plugin_data' => array(
							'name'      => __( 'WooCommerce', 'orderable' ),
							'repo-slug' => 'woocommerce',
							'file'      => 'woocommerce.php',
						),
					),
				),
				'business'     => array(
					'title'       => 'Business Info',
					'description' => 'Orderable needs some basic business information that will be used when orders are placed.',
					'button_text' => 'Continue <span class="dashicons dashicons-arrow-right-alt2"></span>',
					'fields'      => array(
						'name'            => array(
							'id'      => 'business_name',
							'title'   => __( 'Business Name', 'orderable' ),
							'desc'    => '',
							'type'    => 'text',
							'default' => get_bloginfo( 'name' ),
						),
						'address'         => array(
							'id'    => 'business_address',
							'title' => __( 'Address line 1', 'orderable' ),
							'desc'  => '',
							'type'  => 'text',
						),
						'address_2'       => array(
							'id'    => 'business_address_2',
							'title' => __( 'Address line 2', 'orderable' ),
							'desc'  => '',
							'type'  => 'text',
						),
						'city'            => array(
							'id'    => 'business_city',
							'title' => __( 'City', 'orderable' ),
							'desc'  => '',
							'type'  => 'text',
						),
						'default_country' => array(
							'id'      => 'default_country',
							'title'   => __( 'Country / State', 'orderable' ),
							'desc'    => '',
							'type'    => 'select',
							'choices' => array(),
						),
						'postcode'        => array(
							'id'    => 'business_postcode',
							'title' => __( 'Postcode / ZIP', 'orderable' ),
							'desc'  => '',
							'type'  => 'text',
						),
						'email'           => array(
							'id'      => 'business_email',
							'title'   => __( 'Business Email', 'orderable' ),
							'desc'    => '',
							'type'    => 'text',
							'default' => get_option( 'admin_email' ),
						),
						array(
							'id'      => 'opt_in',
							'title'   => '',
							'desc'    => __( 'Please keep me up to date via email on new Orderable training and features', 'orderable' ),
							'type'    => 'checkbox',
							'default' => 1,
						),
					),
				),
				'location'     => array(
					'title'       => 'Location Info',
					'description' => 'Help us set up your ordering system. You can refine these details further after completing the onboarding process.',
					'button_text' => "Continue <span class='dashicons dashicons-arrow-right-alt2'></span>",
					'fields'      => array(
						array(
							'id'      => 'services',
							'title'   => __( 'Which services do you offer?', 'orderable' ),
							'desc'    => '',
							'type'    => 'checkboxes',
							'choices' => array(
								'flat_rate'    => __( 'Delivery', 'orderable' ),
								'local_pickup' => __( 'Pickup', 'orderable' ),
							),
						),
						array(
							'id'      => 'days',
							'title'   => __( 'Which days of the week are you open?', 'orderable' ),
							'desc'    => '',
							'type'    => 'checkboxes',
							'choices' => array(
								1 => __( 'Monday', 'orderable' ),
								2 => __( 'Tuesday', 'orderable' ),
								3 => __( 'Wednesday', 'orderable' ),
								4 => __( 'Thursday', 'orderable' ),
								5 => __( 'Friday', 'orderable' ),
								6 => __( 'Saturday', 'orderable' ),
								0 => __( 'Sunday', 'orderable' ),
							),
						),
						array(
							'id'      => 'open_hours',
							'title'   => __( 'What are your normal opening hours?', 'orderable' ),
							'desc'    => '',
							'type'    => 'custom',
							'default' => self::get_open_hours_fields(),
						),
					),
				),
				'done'         => array(
					'title'       => 'All Done',
					'description' => "Congratulations, You Did It! Orderable is ready to use on your website. You've successfully completed the setup process and all that is left for you to do is create/customize your products.",
					'button_text' => "Save and Finish <span class='dashicons dashicons-yes'></span>",
				),
			)
		);

		if ( function_exists( 'WC' ) ) {
			unset( $slides['requirements'] );

			$base             = wc_get_base_location();
			$countries_states = self::get_countries_states();
			$default          = '';

			if ( isset( $base['country'] ) && isset( $countries_states[ 'country:' . $base['country'] ] ) ) {
				$default = 'country:' . $base['country'];
			}

			if ( isset( $base['country'] ) && isset( $base['state'] ) && isset( $countries_states[ $base['country'] ] ) ) {
				$state = 'state:' . $base['country'] . ':' . $base['state'];
				if ( isset( $countries_states[ $base['country'] ]['values'][ $state ] ) ) {
					$default = $state;
				}
			}

			$slides['business']['fields']['default_country']['choices'] = $countries_states;
			$slides['business']['fields']['default_country']['default'] = $default;
			$slides['business']['fields']['address']['default']         = WC()->countries->get_base_address();
			$slides['business']['fields']['address_2']['default']       = WC()->countries->get_base_address_2();
			$slides['business']['fields']['city']['default']            = WC()->countries->get_base_city();
			$slides['business']['fields']['postcode']['default']        = WC()->countries->get_base_postcode();
		}

		Orderable_Onboard::run(
			array(
				'version'     => ORDERABLE_VERSION,
				'plugin_slug' => 'orderable',
				'plugin_url'  => ORDERABLE_URL,
				'plugin_path' => ORDERABLE_PATH,
				'slides'      => $slides,
			)
		);
	}

	/**
	 * Save the onboarding data in database.
	 *
	 * @param array $result Was the submission a success?
	 * @param array $fields Submitted fields.
	 *
	 * @return bool|array $result
	 */
	public static function save_onboard_settings( $result, $fields ) {
		if ( empty( $fields['orderable_settings'] ) ) {
			return false;
		}

		// Get existing/default settings.
		$settings = get_option( 'orderable_settings', array() );

		// Assign opening hours.
		$from = array(
			'hour'   => sanitize_text_field( $fields['orderable_settings']['iconic_onboard_open_hours']['from']['hour'] ),
			'minute' => sanitize_text_field( $fields['orderable_settings']['iconic_onboard_open_hours']['from']['minute'] ),
			'period' => sanitize_text_field( $fields['orderable_settings']['iconic_onboard_open_hours']['from']['period'] ),
		);
		$to   = array(
			'hour'   => sanitize_text_field( $fields['orderable_settings']['iconic_onboard_open_hours']['to']['hour'] ),
			'minute' => sanitize_text_field( $fields['orderable_settings']['iconic_onboard_open_hours']['to']['minute'] ),
			'period' => sanitize_text_field( $fields['orderable_settings']['iconic_onboard_open_hours']['to']['period'] ),
		);

		$settings['store_general_open_hours'] = array();

		$days = is_array( $fields['orderable_settings']['iconic_onboard_days'] ) ? $fields['orderable_settings']['iconic_onboard_days'] : array();

		for ( $day = 0; $day <= 6; $day ++ ) {
			$settings['store_general_open_hours'][ $day ] = array(
				'enabled'    => in_array( (string) $day, $days, true ),
				'from'       => $from,
				'to'         => $to,
				'max_orders' => '',
			);
		}

		// Save Business details.
		$name                   = isset( $fields['orderable_settings']['iconic_onboard_business_name'] ) ? wc_clean( wp_unslash( $fields['orderable_settings']['iconic_onboard_business_name'] ) ) : '';
		$address                = isset( $fields['orderable_settings']['iconic_onboard_business_address'] ) ? wc_clean( wp_unslash( $fields['orderable_settings']['iconic_onboard_business_address'] ) ) : '';
		$address_2              = isset( $fields['orderable_settings']['iconic_onboard_business_address_2'] ) ? wc_clean( wp_unslash( $fields['orderable_settings']['iconic_onboard_business_address_2'] ) ) : '';
		$city                   = isset( $fields['orderable_settings']['iconic_onboard_business_city'] ) ? wc_clean( wp_unslash( $fields['orderable_settings']['iconic_onboard_business_city'] ) ) : '';
		$country_state          = isset( $fields['orderable_settings']['iconic_onboard_default_country'] ) ? wc_clean( wp_unslash( $fields['orderable_settings']['iconic_onboard_default_country'] ) ) : '';
		$stripped_country_state = $country_state ? str_replace( array( 'country:', 'state:' ), '', $country_state ) : '';
		$stripped_country_state = explode( ':', $stripped_country_state )[0];
		$postcode               = isset( $fields['orderable_settings']['iconic_onboard_business_postcode'] ) ? wc_clean( wp_unslash( $fields['orderable_settings']['iconic_onboard_business_postcode'] ) ) : '';
		$email                  = isset( $fields['orderable_settings']['iconic_onboard_business_email'] ) ? wc_clean( wp_unslash( $fields['orderable_settings']['iconic_onboard_business_email'] ) ) : '';
		$opt_in                 = ! empty( $fields['orderable_settings']['iconic_onboard_opt_in'] );

		if ( ! empty( $name ) ) {
			update_option( 'blogname', $name );
		}

		update_option( 'woocommerce_store_address', $address );
		update_option( 'woocommerce_store_address_2', $address_2 );
		update_option( 'woocommerce_store_city', $city );
		update_option( 'woocommerce_default_country', $stripped_country_state );
		update_option( 'woocommerce_store_postcode', $postcode );
		update_option( 'admin_email', $email );

		// Process opt in
		if ( $opt_in ) {
			Orderable_Webhooks::subscribe( $email );
		}

		// Restrict delivery area to country/state.
		if ( ! empty( $stripped_country_state ) ) {
			update_option( 'woocommerce_allowed_countries', 'specific' );
			update_option( 'woocommerce_specific_allowed_countries', array( $stripped_country_state ) );
		}

		// Save store services.
		if ( ! empty( $fields['orderable_settings']['iconic_onboard_services'] ) ) {
			$settings['store_general_services'] = array();

			$existing_zone_id = null;
			$zone_name        = __( 'Local', 'orderable' );
			$zones            = WC_Shipping_Zones::get_zones();

			if ( ! empty( $zones ) ) {
				foreach ( $zones as $zone ) {
					if ( $zone_name !== $zone['zone_name'] ) {
						continue;
					}

					$existing_zone_id          = $zone['id'];
					$shipping_method_instances = ! empty( $zone['shipping_methods'] ) ? array_keys( $zone['shipping_methods'] ) : false;
					break;
				}
			}

			$zone = new WC_Shipping_Zone( $existing_zone_id );
			$zone->set_zone_name( $zone_name );

			if ( ! empty( $country_state ) ) {
				$zone->clear_locations();
				$location_parts = explode( ':', $country_state );

				switch ( $location_parts[0] ) {
					case 'state':
						$zone->add_location( $location_parts[1] . ':' . $location_parts[2], 'state' );
						break;
					case 'country':
						$zone->add_location( $location_parts[1], 'country' );
						break;
					case 'continent':
						$zone->add_location( $location_parts[1], 'continent' );
						break;
				}
			}

			if ( ! empty( $shipping_method_instances ) ) {
				foreach ( $shipping_method_instances as $shipping_method_instance_id ) {
					$zone->delete_shipping_method( $shipping_method_instance_id );
				}
			}

			$service_types = array(
				'flat_rate'    => 'delivery',
				'local_pickup' => 'pickup',
			);

			foreach ( $fields['orderable_settings']['iconic_onboard_services'] as $service ) {
				$service = wc_clean( $service );
				$zone->add_shipping_method( $service );

				$settings['store_general_services'][] = $service_types[ $service ];
			}

			$zone->save();
		}

		// Misc.
		$settings['store_general_service_hours_pickup_same'] = 1;

		// Save settings.
		update_option( 'orderable_settings', $settings );

		/**
		 * Action hook for when the onboard settings are saved.
		 *
		 * @param array $fields The settings.
		 */
		do_action( 'orderable_onboard_settings_saved', $fields );

		// Return success.
		return $result;
	}

	/**
	 * Get countries and states for select field.
	 *
	 * @return array
	 */
	public static function get_countries_states() {
		$countries_states = array();

		if ( ! function_exists( 'WC' ) ) {
			return $countries_states;
		}

		$countries = WC()->countries->get_countries();

		if ( empty( $countries ) ) {
			return $countries_states;
		}

		foreach ( $countries as $key => $value ) {
			$countries_states[ 'country:' . $key ] = $value;
			$states                                = WC()->countries->get_states( $key );

			if ( empty( $states ) ) {
				continue;
			}

			$countries_states[ $key ] = array(
				'group_label' => $value,
				'values'      => array(),
			);

			foreach ( $states as $state_key => $state_value ) {
				$state_key                                        = sprintf( 'state:%s:%s', $key, $state_key );
				$countries_states[ $key ]['values'][ $state_key ] = sprintf( '%s &mdash; %s', $value, $state_value );
			}
		}

		return $countries_states;
	}

	/**
	 * Get open hours fields.
	 *
	 * @return string
	 */
	public static function get_open_hours_fields() {
		ob_start();
		?>
		<div class="iconic-onboard-modal-setting__field-section">
			<div style="margin: 0 0 8px;"><?php _e( 'From:', 'orderable' ); ?></div>
			<?php
			echo Orderable_Timings_Settings::get_time_field(
				'orderable_settings[iconic_onboard_open_hours][from]',
				array(
					'hour'   => 9,
					'minute' => '00',
					'period' => 'AM',
				)
			);
			?>
		</div>
		<div class="iconic-onboard-modal-setting__field-section">
			<div style="margin: 0 0 8px;"><?php _e( 'To:', 'orderable' ); ?></div>
			<?php
			echo Orderable_Timings_Settings::get_time_field(
				'orderable_settings[iconic_onboard_open_hours][to]',
				array(
					'hour'   => 5,
					'minute' => '00',
					'period' => 'PM',
				)
			);
			?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Category fields.
	 */
	public static function add_category_fields() {
		?>
		<div class="form-field term-display-type-wrap">
			<label for="visibility"><?php esc_html_e( 'Visibility', 'orderable' ); ?></label>
			<select id="visibility" name="visibility" class="postform">
				<option value=""><?php esc_html_e( 'Default', 'orderable' ); ?></option>
				<option value="hidden"><?php esc_html_e( 'Hidden', 'orderable' ); ?></option>
			</select>
			<p><?php _e( 'Choose whether to hide (on the frontend) this category archive and all single product pages within this category. Hiding them is recommended if using this category in the Orderable menu shortcode.', 'orderable' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Edit category fields.
	 *
	 * @param mixed $term Term (category) being edited.
	 */
	public static function edit_category_fields( $term ) {
		$visibility = get_term_meta( $term->term_id, 'visibility', true );
		?>
		<tr class="form-field term-display-type-wrap">
			<th scope="row" valign="top">
				<label for="visibility"><?php esc_html_e( 'Visibility', 'orderable' ); ?></label>
			</th>
			<td>
				<select id="visibility" name="visibility" class="postform">
					<option value="" <?php selected( '', $visibility ); ?>><?php esc_html_e( 'Default', 'orderable' ); ?></option>
					<option value="hidden" <?php selected( 'hidden', $visibility ); ?>><?php esc_html_e( 'Hidden', 'orderable' ); ?></option>
				</select>
				<p><?php _e( 'Choose whether to hide (on the frontend) this category archive and all single product pages within this category. Hiding them is recommended if using this category in the Orderable menu shortcode.', 'orderable' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save category fields.
	 *
	 * @param mixed  $term_id  Term ID being saved.
	 * @param mixed  $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public static function save_category_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( 'product_cat' !== $taxonomy ) {
			return;
		}

		$visibility = isset( $_POST['visibility'] ) ? sanitize_text_field( wp_unslash( $_POST['visibility'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		update_term_meta( $term_id, 'visibility', $visibility );
	}

	/**
	 * Get hidden category IDs.
	 *
	 * @return array
	 */
	public static function get_hidden_categories() {
		global $wpdb;

		static $hidden_categories = null;

		if ( ! is_null( $hidden_categories ) ) {
			return apply_filters( 'orderable_hidden_categories', $hidden_categories );
		}

		$hidden_categories = array();

		$results = $wpdb->get_results(
			"SELECT DISTINCT tm.term_id
			FROM $wpdb->term_taxonomy AS tt
			INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id
			INNER JOIN $wpdb->termmeta AS tm ON t.term_id = tm.term_id
			WHERE tt.taxonomy = 'product_cat'
			AND tm.meta_key = 'visibility'
			AND tm.meta_value IN ( 'hidden' )
			
			UNION DISTINCT
			
			SELECT DISTINCT term_id 
			FROM $wpdb->term_taxonomy
			WHERE parent IN (
				SELECT DISTINCT tm.term_id
				FROM $wpdb->term_taxonomy AS tt
				INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id
				INNER JOIN $wpdb->termmeta AS tm ON t.term_id = tm.term_id
				WHERE tt.taxonomy = 'product_cat'
				AND tm.meta_key = 'visibility'
				AND tm.meta_value IN ( 'hidden' )
			)",
			ARRAY_A
		);

		if ( is_wp_error( $results ) || empty( $results ) ) {
			return apply_filters( 'orderable_hidden_categories', $hidden_categories );
		}

		$hidden_categories = array_map( 'absint', wp_list_pluck( $results, 'term_id' ) );

		return apply_filters( 'orderable_hidden_categories', $hidden_categories );
	}

	/**
	 * Add logo to settings page.
	 */
	public static function settings_logo( $title = '' ) {
		return sprintf( '<img src="%s" width="164" height="36" />', esc_url( ORDERABLE_ASSETS_URL . 'img/orderable-logo.svg' ) );
	}

	/**
	 * Help buttons and version number
	 */
	public static function settings_header() {
		?>
		<style>
			.wpsf-settings__header {
				justify-content: space-between;
			}

			.orderable-settings-header {
				display: flex;
				align-items: center;
			}

			.orderable-settings-header__links {
				margin: 0 10px 0 0;
				padding: 0;
				list-style: none none outside;
				height: 20px;
			}

			.orderable-settings-header__links li {
				margin: 0 10px 0 0;
				padding: 0;
				display: inline-block;
			}

			.orderable-settings-header__links a {
				text-decoration: none;
				color: #64707B !important;
				white-space: nowrap;
				display: flex;
				align-items: center;
			}

			.orderable-settings-header__links a:hover {
				color: #7031F5 !important;
			}

			.orderable-settings-header__links a svg {
				fill: #7031F5;
				margin-right: 4px;
			}

			@media screen and (max-width: 648px) {
				.orderable-settings-header__links {
					display: none;
				}
			}
		</style>
		<div class="orderable-settings-header">
			<ul class="orderable-settings-header__links">
				<li><a href="https://my.orderable.com/support/?utm_source=orderable&utm_medium=plugin&utm_campaign=settings-header" target="_blank">
						<svg fill="#000000" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20px" height="20px">
							<path d="M 12 2 C 6.4889971 2 2 6.4889971 2 12 C 2 17.511003 6.4889971 22 12 22 C 17.511003 22 22 17.511003 22 12 C 22 6.4889971 17.511003 2 12 2 z M 12 4 C 16.430123 4 20 7.5698774 20 12 C 20 16.430123 16.430123 20 12 20 C 7.5698774 20 4 16.430123 4 12 C 4 7.5698774 7.5698774 4 12 4 z M 12 6 C 9.79 6 8 7.79 8 10 L 10 10 C 10 8.9 10.9 8 12 8 C 13.1 8 14 8.9 14 10 C 14 12 11 12.367 11 15 L 13 15 C 13 13.349 16 12.5 16 10 C 16 7.79 14.21 6 12 6 z M 11 16 L 11 18 L 13 18 L 13 16 L 11 16 z" />
						</svg> <?php _e( 'Get Help', 'orderable' ); ?></a></li>
				<li><a href="https://my.orderable.com/roadmap/?utm_source=orderable&utm_medium=plugin&utm_campaign=settings-header" target="_blank">
						<svg fill="#000000" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20px" height="20px">
							<path d="M 11 0 L 11 3 L 13 3 L 13 0 L 11 0 z M 4.2226562 2.8085938 L 2.8085938 4.2226562 L 4.9296875 6.34375 L 6.34375 4.9296875 L 4.2226562 2.8085938 z M 19.777344 2.8085938 L 17.65625 4.9296875 L 19.070312 6.34375 L 21.191406 4.2226562 L 19.777344 2.8085938 z M 12 5 C 8.1456661 5 5 8.1456661 5 12 C 5 14.767788 6.6561188 17.102239 9 18.234375 L 9 21 C 9 22.093063 9.9069372 23 11 23 L 13 23 C 14.093063 23 15 22.093063 15 21 L 15 18.234375 C 17.343881 17.102239 19 14.767788 19 12 C 19 8.1456661 15.854334 5 12 5 z M 12 7 C 14.773666 7 17 9.2263339 17 12 C 17 14.184344 15.605334 16.022854 13.666016 16.708984 L 13 16.943359 L 13 21 L 11 21 L 11 16.943359 L 10.333984 16.708984 C 8.3946664 16.022854 7 14.184344 7 12 C 7 9.2263339 9.2263339 7 12 7 z M 0 11 L 0 13 L 3 13 L 3 11 L 0 11 z M 21 11 L 21 13 L 24 13 L 24 11 L 21 11 z M 4.9296875 17.65625 L 2.8085938 19.777344 L 4.2226562 21.191406 L 6.34375 19.070312 L 4.9296875 17.65625 z M 19.070312 17.65625 L 17.65625 19.070312 L 19.777344 21.191406 L 21.191406 19.777344 L 19.070312 17.65625 z" />
						</svg> <?php _e( 'Request Feature', 'orderable' ); ?></a></li>
				<li><a href="https://orderable.com/documentation/?utm_source=orderable&utm_medium=plugin&utm_campaign=settings-header" target="_blank">
						<svg fill="#000000" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20px" height="20px">
							<path d="M 6 2 C 4.895 2 4 2.895 4 4 L 4 20 C 4 21.105 4.895 22 6 22 L 18 22 C 19.105 22 20 21.105 20 20 L 20 4 C 20 2.895 19.105 2 18 2 L 14 2 L 8 2 L 6 2 z M 6 4 L 8 4 L 8 12 L 11 10.5 L 14 12 L 14 4 L 18 4 L 18 20 L 6 20 L 6 4 z M 10 4 L 12 4 L 12 8.7636719 L 11.894531 8.7109375 L 11 8.2636719 L 10.105469 8.7109375 L 10 8.7636719 L 10 4 z" />
						</svg> <?php _e( 'View Docs', 'orderable' ); ?></a></li>
			</ul>
			<?php printf( '<span class="orderable-settings-header__version" style="margin: 0 0 0 auto; background: #f4f5f6; display: inline-block; padding: 0 10px; border-radius: 13px; height: 26px; line-height: 26px; white-space: nowrap; box-sizing: border-box; color: #65707b;">v%s</span>', ORDERABLE_VERSION ); ?>
		</div>
		<?php
	}

	/**
	 * Add pointers.
	 */
	public static function admin_pointers() {
		$screen_id = self::screen_has_pointers();

		if ( ! $screen_id ) {
			return;
		}

		$pointers = array();

		if ( $screen_id === 'orderable_page_orderable-settings' ) {
			$pointers['orderable-store-settings'] = array(
				'target'  => '#toplevel_page_orderable a[href="admin.php?page=orderable-location"]',
				'next'    => 'orderable-layout-builder',
				'options' => array(
					'content'  => '<h3>' . esc_html__( 'Set Up Your Location', 'orderable' ) . '</h3>' .
								  '<p>' .
								  esc_html__( "Configure your location's opening hours, delivery/pickup schedule, and holidays.", 'orderable' ) .
								  ' <a href="https://orderable.com/getting-started?utm_source=orderable&utm_medium=plugin&utm_campaign=pointer" target="_blank">' . esc_html__( 'Learn more' ) . '</a>.' .
								  '</p>',
					'position' => array(
						'edge'  => 'left',
						'align' => 'left',
					),
				),
			);

			$pointers['orderable-layout-builder'] = array(
				'target'  => '#toplevel_page_orderable a[href="edit.php?post_type=orderable_layouts"]',
				'next'    => 'orderable-order-view',
				'options' => array(
					'content'  => '<h3>' . esc_html__( 'Product Layouts', 'orderable' ) . '</h3>' .
								  '<p>' .
								  esc_html__( 'Use the Layout Builder to create a product list based on category. Embed your layout using the shortcode or block.', 'orderable' ) .
								  ' <a href="https://orderable.com/layout-builder?utm_source=orderable&utm_medium=plugin&utm_campaign=pointer" target="_blank">' . esc_html__( 'Learn more' ) . '</a>.' .
								  '</p>',
					'position' => array(
						'edge'  => 'left',
						'align' => 'left',
					),
				),
			);

			$pointers['orderable-order-view'] = array(
				'target'  => '#toplevel_page_orderable a[href="edit.php?post_type=shop_order&orderable_live_view"]',
				'options' => array(
					'content'  => '<h3>' . esc_html__( 'Live Order View', 'orderable' ) . '</h3>' .
								  '<p>' .
								  esc_html__( 'Use the Live Order View to get notified and manage orders in real time.', 'orderable' ) .
								  ' <a href="https://orderable.com/process-orders?utm_source=orderable&utm_medium=plugin&utm_campaign=pointer" target="_blank">' . esc_html__( 'Learn more' ) . '</a>.' .
								  '</p>',
					'position' => array(
						'edge'  => 'left',
						'align' => 'left',
					),
				),
			);
		}

		$pointers = apply_filters( 'orderable_pointers', $pointers );

		$dismissed      = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$valid_pointers = array();

		if ( in_array( 'orderable-tour-dismissed', $dismissed ) ) {
			return;
		}

		// Check pointers and remove dismissed ones.
		foreach ( $pointers as $pointer_id => $pointer ) {
			// Sanity check
			if ( in_array( $pointer_id, $dismissed ) ) {
				continue;
			}

			// Add the pointer to $valid_pointers array
			$valid_pointers[ $pointer_id ] = $pointer;
		}

		if ( empty( $valid_pointers ) ) {
			return;
		}

		// Add pointers style to queue.
		wp_enqueue_style( 'wp-pointer' );

		// Add pointer options to script.
		wp_localize_script(
			'orderable',
			'orderable_pointers',
			array(
				'pointers' => $valid_pointers,
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'i18n'     => array(
					'close' => esc_html__( 'Close', 'orderable' ),
					'next'  => esc_html__( 'Next', 'orderable' ),
					'skip'  => esc_html__( 'Skip', 'orderable' ),
				),
			)
		);
	}

	/**
	 * Add script deps for admin scripts.
	 *
	 * @param array $deps
	 *
	 * @return array
	 */
	public static function admin_script_deps( $deps = array() ) {
		if ( ! self::screen_has_pointers() ) {
			return $deps;
		}

		$deps[] = 'wp-pointer';

		return $deps;
	}

	/**
	 * Does this screen have pointers?
	 *
	 * If screen has pointers, return the screen ID. Otherwise false.
	 *
	 * @return bool|string
	 */
	public static function screen_has_pointers() {
		$screen    = get_current_screen();
		$screen_id = $screen->id;

		$pointers = array( 'orderable_page_orderable-settings' );

		return in_array( $screen_id, $pointers, true ) ? $screen_id : false;
	}

	/**
	 * Maybe opt in based on previous setting.
	 */
	public static function maybe_opt_in() {
		$opt_in = get_option( 'orderable_opt_in' );

		// Opt in is only stored in the database if opting in failed.
		if ( ! $opt_in ) {
			return;
		}

		Orderable_Webhooks::subscribe();
	}

	/**
	 * Universal upgrade page content.
	 *
	 * @param string $feature Feature name.
	 */
	public static function upgrade_page_content( $feature = '' ) {
		$utm_campaign = str_replace( ' ', '-', strtolower( $feature ) );
		include ORDERABLE_TEMPLATES_PATH . 'admin/orderable-pro-page.php';
	}

	/**
	 * Get SVG icon of the Orderable logo.
	 *
	 * @return string
	 */
	public static function get_orderable_svg_icon() {
		return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQ0IiBoZWlnaHQ9IjE4MyIgdmlld0JveD0iMCAwIDI0NCAxODMiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+DQo8cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTE5MC40NzggMTY2LjdDMjI3LjM2MiAxNDIuNzEgMjUzLjI3MSA5MS44NDM4IDI0MC44NTYgNTYuMzA5NUMyMjguNDQyIDIwLjk1NTcgMTc3LjUyMyAwLjkzMzgzNSAxMjMuNzI3IDAuMDMxOTUxNEM3MC4xMDk3IC0wLjg2OTkzMyAxMy42MTQgMTcuMzQ4MSAyLjI3ODkxIDUxLjI1OUMtOS4yMzYxMyA4NS4xNjk4IDI0LjU4OTMgMTM0Ljk1NCA2NS40MzE3IDE2MS4yODlDMTA2LjA5NCAxODcuNjI0IDE1My43NzQgMTkwLjY5IDE5MC40NzggMTY2LjdaTTE4My4yMzQgNzguNzIwNkMxODMuMjM0IDgzLjkyNDEgMTc4LjEwOCA5MS42Mjg3IDE2Ny4zNDggOTEuNjI4N0MxNTYuNTg4IDkxLjYyODcgMTUxLjQ2MyA4My45MjQxIDE1MS40NjMgNzguNzIwNkMxNTEuNDYzIDczLjUxNzIgMTU2LjU4OCA2NS44MTI2IDE2Ny4zNDggNjUuODEyNkMxNzguMTA4IDY1LjgxMjYgMTgzLjIzNCA3My41MTcyIDE4My4yMzQgNzguNzIwNlpNMTY5LjExOCAxMjQuNjk3QzE5NS4zNDkgMTIzLjgyMiAyMTYuMzI4IDEwMy41NzIgMjE2LjMyOCA3OC43MjA2QzIxNi4zMjggNTMuMzEyNCAxOTQuMzk5IDMyLjcxNSAxNjcuMzQ4IDMyLjcxNUMxNjcuMjU3IDMyLjcxNSAxNjcuMTY3IDMyLjcxNTMgMTY3LjA3NiAzMi43MTU3SDg2LjczNUM4MS42NjMgMzIuNzE1NyA3Ny41NTEyIDM2LjgzNTIgNzcuNTUxMiA0MS45MTY4Qzc3LjU1MTIgNDYuOTk4NSA4MS42NjMgNTEuMTE4IDg2LjczNSA1MS4xMThIMTI4LjE2TDEyOC4xNDQgNTEuMTM3MUMxMzIuOTQyIDUxLjQ1MTEgMTM2LjczNiA1NS40NDE3IDEzNi43MzYgNjAuMzE4M0MxMzYuNzM2IDY1LjQgMTMyLjYxNiA2OS41MTk1IDEyNy41MzQgNjkuNTE5NUgxMTkuMzQ4TDExOS4zNDggNjkuNTIwMkg1My4wNjExQzQ3Ljk4OTEgNjkuNTIwMiA0My44NzczIDczLjYzOTcgNDMuODc3MyA3OC43MjEzQzQzLjg3NzMgODMuODAyOSA0Ny45ODkxIDg3LjkyMjQgNTMuMDYxMSA4Ny45MjI0SDEyNy42NTJDMTMyLjY4IDg3Ljk4NTQgMTM2LjczNiA5Mi4wODA0IDEzNi43MzYgOTcuMTIyOEMxMzYuNzM2IDEwMiAxMzIuOTQyIDEwNS45OSAxMjguMTQ0IDEwNi4zMDRMMTI4LjE2MSAxMDYuMzI1SDc0LjQ5QzY5LjQxNzkgMTA2LjMyNSA2NS4zMDYyIDExMC40NDQgNjUuMzA2MiAxMTUuNTI2QzY1LjMwNjIgMTIwLjYwNyA2OS40MTc5IDEyNC43MjcgNzQuNDkgMTI0LjcyN0gxNjguMzY5QzE2OC42MjEgMTI0LjcyNyAxNjguODcxIDEyNC43MTcgMTY5LjExOCAxMjQuNjk3Wk0xNTAuODY0IDEzNy41NDVDMTUyLjA2NSAxNDEuMTE4IDE0OS41NTggMTQ2LjIzMyAxNDUuOTg4IDE0OC42NDZDMTQyLjQzNSAxNTEuMDU4IDEzNy44MjEgMTUwLjc1IDEzMy44ODUgMTQ4LjEwMkMxMjkuOTMyIDE0NS40NTQgMTI2LjY1OSAxNDAuNDQ3IDEyNy43NzMgMTM3LjAzN0MxMjguODcgMTMzLjYyNyAxMzQuMzM4IDEzMS43OTUgMTM5LjUyNyAxMzEuODg2QzE0NC43MzQgMTMxLjk3NyAxNDkuNjYyIDEzMy45OSAxNTAuODY0IDEzNy41NDVaTTE2MS42NzYgMTM3LjU0NUMxNjAuNDc0IDE0MS4xMTggMTYyLjk4MiAxNDYuMjMzIDE2Ni41NTIgMTQ4LjY0NkMxNzAuMTA0IDE1MS4wNTggMTc0LjcxOSAxNTAuNzUgMTc4LjY1NCAxNDguMTAyQzE4Mi42MDcgMTQ1LjQ1NCAxODUuODgxIDE0MC40NDcgMTg0Ljc2NiAxMzcuMDM3QzE4My42NjkgMTMzLjYyNyAxNzguMjAyIDEzMS43OTUgMTczLjAxMiAxMzEuODg2QzE2Ny44MDYgMTMxLjk3NyAxNjIuODc3IDEzMy45OSAxNjEuNjc2IDEzNy41NDVaIiBmaWxsPSIjOUNBMUE4Ii8+DQo8L3N2Zz4=';
	}
}
