<?php
/**
 * Admin Notices.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin Notices class.
 */
class Orderable_Admin_Notices {
	/**
	 * Run.
	 */
	public static function run() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_notices', array( __CLASS__, 'display_notices' ) );
		add_action( 'admin_init', array( __CLASS__, 'dismiss' ) );
	}

	/**
	 * Dismiss notices.
	 */
	public static function dismiss() {
		// Permissions check.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$action = empty( $_GET['orderable_action'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['orderable_action'] ) );

		// Not our notices, bail.
		if ( 'dismiss_notice' !== $action ) {
			return;
		}

		// Get notice.
		$name = empty( $_GET['orderable_notice'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['orderable_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $name ) {
			return;
		}

		// Notice is dismissed.
		update_option( 'orderable_dismissed_notice_' . $name, 1 );
	}

	/**
	 * Check if notice is dismissed.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function is_dismissed( $name ) {
		return (bool) get_option( 'orderable_dismissed_notice_' . $name, false );
	}

	/**
	 * Display notices if they exist.
	 */
	public static function display_notices() {
		$notices = apply_filters( 'orderable_admin_notices', array() );

		if ( empty( $notices ) ) {
			return;
		}

		$default_options = array(
			'name'        => null,
			'title'       => '',
			'description' => '',
			'dismissable' => true,
		);

		foreach ( $notices as $notice ) {
			$notice = wp_parse_args( $notice, $default_options );

			if ( is_null( $notice['name'] ) || self::is_dismissed( $notice['name'] ) ) {
				continue;
			} ?>
			<div class="notice notice--orderable" style="border-left-color: #7031F5;">
				<p><strong><?php echo wp_kses_post( $notice['title'] ); ?></strong></p>
				<p><?php echo wp_kses_post( $notice['description'] ); ?></p>
				<?php if ( $notice['dismissable'] ) { ?>
					<p>
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'orderable_action' => 'dismiss_notice',
									'orderable_notice' => $notice['name'],
								)
							)
						);
						?>
									"><?php _e( 'Dismiss Notice', 'orderable' ); ?></a>
					</p>
				<?php } ?>
			</div>
			<?php
		}
	}
}
