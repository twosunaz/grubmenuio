<?php
/**
 * Plugin Name: Orderable - Local Ordering System
 * Author URI: https://orderable.com
 * Description: Take local online ordering to a whole new level with Orderable.
 * Version: 1.18.0
 * Author: Orderable
 * Text Domain: orderable
 * WC requires at least: 5.4.0
 * WC tested up to: 9.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orderable main class.
 */
class Orderable {
	/**
	 * @var string Plugin version.
	 */
	public static $version = '1.18.0';

	/**
	 * @var string Required pro version.
	 */
	public static $required_pro_version = '1.17.0';

	/**
	 * Construct the plugin.
	 */
	public function __construct() {
		$this->define_constants();

		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_action( 'plugins_loaded', array( $this, 'run' ), 20 );

		// Redirect to settings page on activate.
		add_action( 'activated_plugin', array( $this, 'activate' ), 20 );

		// Declare compatibility with High-Performance Order Storage (HPOS).
		add_action(
			'before_woocommerce_init',
			function() {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				}
			}
		);

		add_action( 'woocommerce_blocks_loaded', array( $this, 'load_woocommerce_blocks' ) );
	}

	/**
	 * Load Textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'orderable', false, ORDERABLE_LANGUAGES_PATH );
	}

	/**
	 * Run.
	 */
	public function run() {
		if ( ! self::is_woo_active() ) {
			add_action( 'admin_notices', array( __CLASS__, 'orderable_notice_woocommerce' ) );

			// If Woo is not active, don't run Orderable.
			return;
		}

		$this->load_classes();
		$this->update_check();
		Orderable_Database::run();

		if ( ! $this->validate_pro_version() ) {
			add_action( 'admin_notices', array( __CLASS__, 'orderable_notice_pro_version' ) );

			$this->define( 'ORDERABLE_PRO_INVALID', true );
		}

		do_action( 'orderable_init' );
	}

	/**
	 * Run when plugin activated.
	 *
	 * @param $plugin
	 *
	 * @return void
	 */
	public function activate( $plugin ) {
		if ( ! self::is_woo_active() ) {
			return;
		}

		$checked = isset( $_REQUEST['checked'] ) ? $_REQUEST['checked'] : array();

		// Ensure we are not doing a bulk activation.
		if ( is_array( $checked ) && count( $checked ) > 1 ) {
			return;
		}

		if ( ORDERABLE_BASENAME !== $plugin ) {
			return;
		}

		wp_redirect( admin_url( 'admin.php?page=orderable-settings' ) );
		exit;
	}

	/**
	 * Run script sif plugin version has changed.
	 */
	private function update_check() {
		if ( ORDERABLE_VERSION === get_site_option( 'orderable_version' ) ) {
			return;
		}

		Orderable_Helpers::delete_orderable_transients();
		update_option( 'orderable_version', ORDERABLE_VERSION );
	}

	/**
	 * Activate WooCommerce notice.
	 */
	public static function orderable_notice_woocommerce() {
		?>
		<div class="notice notice-error">
			<p>
				<?php _e( 'Orderable requires WooCommerce to be installed and activated.', 'orderable' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Get Orderable pro version notice.
	 */
	public static function orderable_notice_pro_version() {
		?>
		<div class="notice notice-error">
			<p>
				<?php printf( __( 'Orderable Pro needs to be at least v%s for compatibility. Please update the Orderable Pro plugin.', 'orderable' ), self::$required_pro_version ); ?>
				<a href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>"><?php _e( 'Update now', 'orderable' ); ?></a>.
			</p>
		</div>
		<?php
	}

	/**
	 * Define Constants.
	 */
	private function define_constants() {
		$this->define( 'ORDERABLE_PATH', plugin_dir_path( __FILE__ ) );
		$this->define( 'ORDERABLE_URL', plugin_dir_url( __FILE__ ) );
		$this->define( 'ORDERABLE_ASSETS_URL', ORDERABLE_URL . 'assets/' );
		$this->define( 'ORDERABLE_INC_PATH', ORDERABLE_PATH . 'inc/' );
		$this->define( 'ORDERABLE_MODULES_PATH', ORDERABLE_INC_PATH . 'modules/' );
		$this->define( 'ORDERABLE_VENDOR_PATH', ORDERABLE_INC_PATH . 'vendor/' );
		$this->define( 'ORDERABLE_TEMPLATES_PATH', ORDERABLE_PATH . 'templates/' );
		$this->define( 'ORDERABLE_TPL_PATH', ORDERABLE_PATH . 'templates/' );
		$this->define( 'ORDERABLE_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'ORDERABLE_LANGUAGES_PATH', dirname( ORDERABLE_BASENAME ) . '/languages/' );
		$this->define( 'ORDERABLE_VERSION', self::$version );
		$this->define( 'ORDERABLE_CACHE_EXPIRATION_TIME', 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name
	 * @param string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Load classes.
	 */
	private function load_classes() {
		require_once ORDERABLE_INC_PATH . 'database/class-database.php';
		require_once ORDERABLE_INC_PATH . 'class-helpers.php';
		require_once ORDERABLE_INC_PATH . 'class-settings.php';
		require_once ORDERABLE_INC_PATH . 'class-modules.php';
		require_once ORDERABLE_INC_PATH . 'class-assets.php';
		require_once ORDERABLE_INC_PATH . 'class-products.php';
		require_once ORDERABLE_INC_PATH . 'class-ajax.php';
		require_once ORDERABLE_INC_PATH . 'class-orders.php';
		require_once ORDERABLE_INC_PATH . 'class-admin-notices.php';
		require_once ORDERABLE_INC_PATH . 'class-webhooks.php';
		require_once ORDERABLE_INC_PATH . 'class-shortcodes.php';
		require_once ORDERABLE_INC_PATH . 'class-ask-review.php';
		require_once ORDERABLE_INC_PATH . 'class-integrations.php';
		require_once ORDERABLE_INC_PATH . 'class-compat-flux-checkout.php';

		Orderable_Settings::run();
		Orderable_Assets::run();
		Orderable_Modules::run();
		Orderable_Products::run();
		Orderable_Ajax::run();
		Orderable_Shortcodes::run();
		Orderable_Ask_Review::run();
		Orderable_Integrations::run();
		Orderable_Compat_Flux_Checkout::run();
	}

	/**
	 * Validate the Orderable core version number.
	 *
	 * @return bool
	 */
	private static function validate_pro_version() {
		if ( ! defined( 'ORDERABLE_PRO_VERSION' ) ) {
			// Pro version is valid because it doesn't exist.
			return true;
		}

		$pro_version = explode( '-', ORDERABLE_PRO_VERSION );

		return version_compare( $pro_version[0], self::$required_pro_version, '>=' );
	}

	/**
	 * Is Woo active.
	 *
	 * @return bool
	 */
	public static function is_woo_active() {
		return in_array( 'woocommerce/woocommerce.php', (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ||
			   in_array( 'woocommerce/woocommerce.php', array_keys( (array) get_site_option( 'active_sitewide_plugins' ), true ) );
	}

	/**
	 * Load WooCommerce blocks.
	 *
	 * @return void
	 */
	public function load_woocommerce_blocks() {
		require_once ORDERABLE_MODULES_PATH . 'checkout/blocks/order-date/class-checkout-order-date-blocks-integration.php';
		require_once ORDERABLE_MODULES_PATH . 'checkout/blocks/order-date/class-checkout-order-date-extend-store-api.php';

		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function( $integration_registry ) {
				$integration_registry->register( new Checkout_Order_Date_Blocks_Integration() );
			}
		);

		woocommerce_store_api_register_endpoint_data( Checkout_Order_Date_Extend_Store_API::extend_cart_schema() );
		woocommerce_store_api_register_endpoint_data( Checkout_Order_Date_Extend_Store_API::extend_checkout_schema() );

		add_action(
			'woocommerce_store_api_checkout_update_order_from_request',
			array( 'Checkout_Order_Date_Blocks_Integration', 'save_order_service_date_fields' ),
			10,
			2
		);
	}
}

$orderable = new Orderable();
