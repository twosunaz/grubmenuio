<?php
/**
 * Methods related to Ask for review notice.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ask for review only after a specific number of orders have been received on the store.
 */
class Orderable_Ask_Review {
	/**
	 * Order count required to display the notice.
	 *
	 * @var int
	 */
	private static $order_count_required = 20;

	/**
	 * Run.
	 *
	 * @return void
	 */
	public static function run() {
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'register_order' ), 10, 1 );
		add_action( 'admin_notices', array( __CLASS__, 'show_notice' ) );
		add_action( 'admin_init', array( __CLASS__, 'dismiss_notice' ) );
	}

	/**
	 * Dismiss notice.
	 *
	 * @return void
	 */
	public static function dismiss_notice() {
		$orderable_dismiss_review_notice = filter_input( INPUT_GET, 'orderable_dismiss_review_notice' );

		if ( ! $orderable_dismiss_review_notice ) {
			return;
		}

		check_admin_referer( 'orderable', '_nonce' );

		$data            = self::get_review_data();
		$data['dismiss'] = true;

		update_option( 'orderable_ask_review', $data );

		$url = remove_query_arg( array( 'orderable_dismiss_review_notice', '_nonce' ) );

		wp_safe_redirect( $url );
	}

	/**
	 * Get review data.
	 *
	 * @return array.
	 */
	public static function get_review_data() {
		$review_data = get_option( 'orderable_ask_review' );

		if ( empty( $review_data ) ) {
			$review_data = array(
				'dismiss'          => false,
				'orders_processes' => 0,
			);
		}

		return $review_data;
	}

	/**
	 * Register order.
	 *
	 * @return void
	 */
	public static function register_order() {
		$review_data = self::get_review_data();

		if ( ! is_array( $review_data ) || ! is_numeric( $review_data['orders_processes'] ) ) {
			return;
		}

		$review_data['orders_processes'] = absint( $review_data['orders_processes'] ) + 1;

		update_option( 'orderable_ask_review', $review_data, 1 );
	}

	/**
	 * Should display notice?
	 *
	 * @return bool
	 */
	public static function should_display_notice() {
		$review_data = self::get_review_data();

		if ( ! $review_data['dismiss'] && $review_data['orders_processes'] >= self::$order_count_required ) {
			return true;
		}

		return false;
	}

	/**
	 * Show notice.
	 *
	 * @return void
	 */
	public static function show_notice() {
		$dismiss_url = admin_url();
		$review_url  = 'https://wordpress.org/support/plugin/orderable/reviews/#new-post';

		if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
			// phpcs:ignore
			$current_url  = wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] );
			$current_url .= ( strpos( $current_url, '?' ) ? '&' : '?' ) . http_build_query(
				array(
					'orderable_dismiss_review_notice' => '1',
					'_nonce'                          => wp_create_nonce( 'orderable' ),
				)
			);
			$dismiss_url  = $current_url;
		}

		if ( self::should_display_notice() ) {
			?>
			<div class="notice notice-warning is-dismissible notice-orderable-ask-review" style="border-left-color: #4233B6;">
				<h4 style='margin-bottom: 10px;'>
					<?php
					/* translators: %1$s - number of orders. */
						echo esc_html( sprintf( __( 'You have processed %1$s+ orders with Orderable 🥳', 'orderable' ), self::$order_count_required ) );
					?>
				</h4>
				<p>
					<?php esc_html_e( 'You have been using Orderable for a while. If you are enjoying Orderable, please help us by leaving a review on WordPress.org', 'orderable' ); ?>
				</p>
				<p>
					<a href='<?php echo esc_url( $review_url ); ?>' target='_blank' class="button button-primary"><?php esc_html_e( 'Rate Now', 'orderable' ); ?></a>
					<a href='<?php echo esc_url( $dismiss_url ); ?>' class="button button-default"><?php esc_html_e( 'Dismiss Forever', 'orderable' ); ?></a>
				</p>
			</div>
			<?php
		}
	}
}
