<?php
/**
 * Module: Location.
 *
 * @since   1.18.0
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable_Location_Store_Address_Meta_Box class.
 */
class Orderable_Location_Store_Address_Meta_Box {
	/**
	 * Init.
	 */
	public static function run() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'orderable_location_get_save_data', array( __CLASS__, 'get_save_data' ) );
	}

	/**
	 * Get the meta box title.
	 *
	 * @return string
	 */
	public static function get_title() {
		return __( 'Address', 'orderable' );
	}

	/**
	 * Add the Meta Box.
	 *
	 * @return void
	 */
	public static function add() {
		add_meta_box(
			'orderable_multi_location_store_address_meta_box',
			self::get_title(),
			array( __CLASS__, 'output' )
		);
	}

	/**
	 * Output the meta box.
	 *
	 * @return void
	 */
	public static function output() {
		$location = new Orderable_Location_Single();
		$data     = $location->get_address();

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$data = wp_parse_args(
			$data,
			array(
				'address_line_1' => '',
				'address_line_2' => '',
				'city'           => '',
				'country_state'  => '',
				'postcode_zip'   => '',
			)
		);

		if ( empty( $data['country_state'] ) ) {
			$data['country_state'] = get_option( 'woocommerce_default_country', '' );
		}

		if ( strstr( $data['country_state'], ':' ) ) {
			$country_state = explode( ':', $data['country_state'] );
			$country       = current( $country_state );
			$state         = end( $country_state );
		} else {
			$country = $data['country_state'];
			$state   = '*';
		}

		?>

		<div class="orderable-fields-row orderable-fields-row--meta">
			<div class="orderable-fields-row__body">
				<div class="orderable-fields-row__body-row">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Address Line 1', 'Location Address', 'orderable-pro' ); ?></h3>
					</div>
					<div class="orderable-fields-row__body-row-right orderable-fields-row__body-row-right--slug">
						<input
							type="text"
							name='orderable_address_line_1'
							class="orderable-field"
							value="<?php echo esc_attr( $data['address_line_1'] ); ?>"
						/>
						<p class='orderable-field-error-message'></p>
					</div>
				</div>

				<div class="orderable-fields-row__body-row">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Address Line 2', 'Location Address', 'orderable-pro' ); ?></h3>
					</div>
					<div class="orderable-fields-row__body-row-right orderable-fields-row__body-row-right--slug">
						<input
							type="text"
							name='orderable_address_line_2'
							class="orderable-field"
							value="<?php echo esc_attr( $data['address_line_2'] ); ?>"
						/>
						<p class='orderable-field-error-message'></p>
					</div>
				</div>

				<div class="orderable-fields-row__body-row">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'City', 'Location Address', 'orderable-pro' ); ?></h3>
					</div>
					<div class="orderable-fields-row__body-row-right orderable-fields-row__body-row-right--slug">
						<input
							type="text"
							name='orderable_city'
							class="orderable-field"
							value="<?php echo esc_attr( $data['city'] ); ?>"
						/>
						<p class='orderable-field-error-message'></p>
					</div>
				</div>

				<div class="orderable-fields-row__body-row">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Country / State', 'Location Address', 'orderable-pro' ); ?></h3>
					</div>
					<div class="orderable-fields-row__body-row-right orderable-fields-row__body-row-right--slug">
						<select
							name="orderable_country_state"
							class="wc-enhanced-select orderable-field"
							style="width: 426px;"
						>
							<?php WC()->countries->country_dropdown_options( $country, $state ); ?>
						</select>
						<p class='orderable-field-error-message'></p>
					</div>
				</div>

				<div class="orderable-fields-row__body-row">
					<div class="orderable-fields-row__body-row-left">
						<h3><?php echo esc_html_x( 'Postcode / ZIP', 'Location Address', 'orderable-pro' ); ?></h3>
					</div>
					<div class="orderable-fields-row__body-row-right orderable-fields-row__body-row-right--slug">
						<input
							type="text"
							name='orderable_post_code_zip'
							class="orderable-field"
							value="<?php echo esc_attr( $data['postcode_zip'] ); ?>"
						/>
						<p class='orderable-field-error-message'></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the default data.
	 *
	 * @param array $data The default data will be appended to $data.
	 * @return array
	 */
	public static function get_default_data( $data = array() ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$default_data = array(
			'orderable_address_line_1' => '',
			'orderable_address_line_2' => '',
			'orderable_city'           => '',
			'orderable_country_state'  => get_option( 'woocommerce_default_country', '' ),
			'orderable_post_code_zip'  => '',
		);

		return array_merge( $data, $default_data );
	}

	/**
	 * Return the data to be saved.
	 *
	 * @param array $data The data sent via POST will be appended to $data.
	 * @return array
	 */
	public static function get_save_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$store_address_data = array(
			'orderable_address_line_1' => Orderable_Location_Admin::get_posted_value( 'orderable_address_line_1' ),
			'orderable_address_line_2' => Orderable_Location_Admin::get_posted_value( 'orderable_address_line_2' ),
			'orderable_city'           => Orderable_Location_Admin::get_posted_value( 'orderable_city' ),
			'orderable_country_state'  => Orderable_Location_Admin::get_posted_value( 'orderable_country_state' ),
			'orderable_post_code_zip'  => Orderable_Location_Admin::get_posted_value( 'orderable_post_code_zip' ),
		);

		return array_merge( $data, $store_address_data );
	}
}
