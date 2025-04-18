<?php
/**
 * Drawer settings.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Drawer settings class.
 */
class Orderable_Drawer_Settings {
	/**
	 * @var string Fine tune settings key.
	 */
	public static $fine_tune_cart_key = 'style_cart_fine_tune';

	/**
	 * Init.
	 */
	public static function run() {
		add_filter( 'orderable_default_settings', array( __CLASS__, 'default_settings' ) );
		add_filter( 'wpsf_register_settings_orderable', array( __CLASS__, 'register_settings' ), 20 );
		add_filter( 'orderable_settings_validate', array( __CLASS__, 'validate_settings' ), 10 );
		add_action( 'init', array( __CLASS__, 'position_accordion' ) );
	}

	/**
	 * Add default settings.
	 *
	 * @param array $default_settings
	 *
	 * @return array
	 */
	public static function default_settings( $default_settings = array() ) {
		$default_settings['style_cart_position']  = 'br';
		$default_settings['style_cart_fine_tune'] = array(
			'top'    => 0,
			'right'  => 0,
			'bottom' => 0,
			'left'   => 0,
		);

		$default_settings['drawer_quickview_description']        = 'none';
		$default_settings['drawer_quickview_accordion_position'] = 'orderable_side_menu_before_product_options';

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
		$settings['tabs']['drawer'] = array(
			'id'       => 'drawer',
			'title'    => __( 'Side Drawer', 'orderable' ),
			'priority' => 30,
		);

		$settings['sections']['cart'] = array(
			'tab_id'              => 'style',
			'section_id'          => 'cart',
			'section_title'       => __( 'Mini Cart Settings', 'orderable' ),
			'section_description' => '',
			'section_order'       => 20,
			'fields'              => array(
				'position'  => array(
					'id'       => 'position',
					'title'    => __( 'Cart Icon Position', 'orderable' ),
					'subtitle' => __( 'Set the position of the mini cart icon when the cart is not empty. Choose "None" to disable it.', 'orderable' ),
					'type'     => 'select',
					'choices'  => array(
						'none' => __( 'None', 'orderable' ),
						'br'   => __( 'Bottom Right', 'orderable' ),
						'bl'   => __( 'Bottom Left', 'orderable' ),
						'tl'   => __( 'Top Left', 'orderable' ),
						'tr'   => __( 'Top Right', 'orderable' ),
					),
					'default'  => Orderable_Settings::get_setting_default( 'style_cart_position' ),
				),
				'fine_tune' => array(
					'id'       => 'fine_tune',
					'title'    => __( 'Fine-Tune Position', 'orderable' ),
					'subtitle' => __( 'Fine-tune the position of the mini cart icon in pixels. These settings will push the cart icon away from the respective side.', 'orderable' ),
					'type'     => 'custom',
					'default'  => Orderable_Settings::get_setting_default( self::$fine_tune_cart_key ),
					'output'   => self::get_fine_tune_cart_fields(),
				),
			),
		);

		$settings['sections']['quickview'] = array(
			'tab_id'              => 'drawer',
			'section_id'          => 'quickview',
			'section_title'       => __( 'Quickview Settings', 'orderable' ),
			'section_description' => __( 'Settings for when products are displayed in the Orderable drawer.', 'orderable' ),
			'section_order'       => 10,
			'fields'              => array(
				'accordion_position' => array(
					'id'       => 'accordion_position',
					'title'    => __( 'Accordion Position', 'orderable' ),
					'subtitle' => __( 'Where should the product information accordion be displayed in the side drawer.', 'orderable' ),
					'type'     => 'select',
					'choices'  => array(
						'orderable_side_menu_before_product_options' => __( 'Before Product Options', 'orderable' ),
						'orderable_side_menu_after_product_options'  => __( 'After Product Options', 'orderable' ),
					),
					'default'  => Orderable_Settings::get_setting_default( 'drawer_quickview_accordion_position' ),
				),
				'description'        => array(
					'id'       => 'description',
					'title'    => __( 'Product Description', 'orderable' ),
					'subtitle' => __( 'Choose which product description to display in the side drawer.', 'orderable' ),
					'type'     => 'select',
					'choices'  => array(
						'none'  => __( 'None', 'orderable' ),
						'short' => __( 'Short Description', 'orderable' ),
						'full'  => __( 'Full Description', 'orderable' ),
					),
					'default'  => Orderable_Settings::get_setting_default( 'drawer_quickview_description' ),
				),
			),
		);

		return $settings;
	}

	/**
	 * Validate settings.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function validate_settings( $settings = array() ) {
		return $settings;
	}

	/**
	 * Fine tune cart icon fields.
	 *
	 * @return string
	 */
	public static function get_fine_tune_cart_fields() {
		if ( ! is_admin() ) {
			return;
		}

		$settings = Orderable_Settings::get_setting( self::$fine_tune_cart_key );

		ob_start();
		?>
		<table class="orderable-table orderable-table--compact" cellpadding="0" cellspacing="0">
			<tbody>
			<tr>
				<th class="orderable-table__column orderable-table__column--medium"><?php esc_html_e( 'Top (px)', 'orderable' ); ?></th>
				<td>
					<input type="number" id="orderable-fine-tune-cart-top" value="<?php echo esc_attr( $settings['top'] ); ?>" name="orderable_settings[<?php echo esc_attr( self::$fine_tune_cart_key ); ?>][top]">
				</td>
			</tr>
			<tr>
				<th class="orderable-table__column orderable-table__column--medium"><?php esc_html_e( 'Bottom (px)', 'orderable' ); ?></th>
				<td>
					<input type="number" id="orderable-fine-tune-cart-bottom" value="<?php echo esc_attr( $settings['bottom'] ); ?>" name="orderable_settings[<?php echo esc_attr( self::$fine_tune_cart_key ); ?>][bottom]">
				</td>
			</tr>
			<tr>
				<th class="orderable-table__column orderable-table__column--medium"><?php esc_html_e( 'Right (px)', 'orderable' ); ?></th>
				<td>
					<input type="number" id="orderable-fine-tune-cart-right" value="<?php echo esc_attr( $settings['right'] ); ?>" name="orderable_settings[<?php echo esc_attr( self::$fine_tune_cart_key ); ?>][right]">
				</td>
			</tr>
			<tr>
				<th class="orderable-table__column orderable-table__column--medium"><?php esc_html_e( 'Left (px)', 'orderable' ); ?></th>
				<td>
					<input type="number" id="orderable-fine-tune-cart-left" value="<?php echo esc_attr( $settings['left'] ); ?>" name="orderable_settings[<?php echo esc_attr( self::$fine_tune_cart_key ); ?>][left]">
				</td>
			</tr>
			</tbody>
		</table>

		<script>
			jQuery( document ).ready( function( $ ) {
				var $fields = {
					top: $( '#orderable-fine-tune-cart-top' ),
					right: $( '#orderable-fine-tune-cart-right' ),
					bottom: $( '#orderable-fine-tune-cart-bottom' ),
					left: $( '#orderable-fine-tune-cart-left' )
				};

				/**
				 * Hide fields.
				 */
				function hide_fields() {
					$fields.top.closest( 'tr' ).hide();
					$fields.right.closest( 'tr' ).hide();
					$fields.bottom.closest( 'tr' ).hide();
					$fields.left.closest( 'tr' ).hide();
				}

				/**
				 * Show fields.
				 *
				 * @param position
				 */
				function show_fields( position ) {
					hide_fields();

					var $table = $fields.top.closest( 'table' );

					$table.closest( 'tr' ).show();

					if ( 'tr' === position ) {
						$fields.top.closest( 'tr' ).show();
						$fields.right.closest( 'tr' ).show();
					} else if ( 'br' === position ) {
						$fields.bottom.closest( 'tr' ).show();
						$fields.right.closest( 'tr' ).show();
					} else if ( 'bl' === position ) {
						$fields.bottom.closest( 'tr' ).show();
						$fields.left.closest( 'tr' ).show();
					} else if ( 'tl' === position ) {
						$fields.top.closest( 'tr' ).show();
						$fields.left.closest( 'tr' ).show();
					} else {
						$fields.top.closest( 'table' ).closest( 'tr' ).hide();
					}

					$table.find( 'tr' ).removeClass( 'orderable-table__row--last' );
					$table.find( 'tr' ).not( '[style*="display: none"]' ).last().addClass( 'orderable-table__row--last' );
				}

				var $position_field = $( '#style_cart_position' );

				show_fields( $position_field.val() );

				$position_field.on( 'change', function() {
					show_fields( $( this ).val() );
				} );
			} );
		</script>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get fine tune cart CSS.
	 *
	 * @return string
	 */
	public static function get_fine_tune_cart_settings_css() {
		$settings = Orderable_Settings::get_setting( self::$fine_tune_cart_key );
		$settings = array_filter( $settings );
		$css      = '';

		if ( ! empty( $settings ) ) {
			foreach ( $settings as $side => $value ) {
				$css .= sprintf( 'margin-%s:%spx;', $side, $value );
			}
		}

		return apply_filters( 'orderable_get_fine_tune_cart_css', $css, $settings );
	}

	/**
	 * Get cart icon CSS.
	 *
	 * @return mixed|void
	 */
	public static function get_cart_icon_css() {
		$cart_count = WC()->cart->get_cart_contents_count();

		$style  = '';
		$style .= self::get_fine_tune_cart_settings_css();
		$style .= $cart_count <= 0 ? 'display:none;' : '';

		return apply_filters( 'orderable_get_cart_icon_css', $style, $cart_count );
	}

	/**
	 * Position product accordion in drawer.
	 */
	public static function position_accordion() {
		$position = Orderable_Settings::get_setting( 'drawer_quickview_accordion_position' );

		add_filter( $position, array( __CLASS__, 'display_product_accordion' ), 10, 2 );
	}

	/**
	 * Display product accordion.
	 *
	 * @param WC_Product $product Product.
	 * @param array      $args    Args.
	 */
	public static function display_product_accordion( $product, $args = array() ) {
		include Orderable_Helpers::get_template_path( 'templates/product/accordion.php' );
	}
}
