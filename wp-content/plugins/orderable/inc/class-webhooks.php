<?php
/**
 * Webhooks.
 *
 * @package Orderable/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings class.
 */
class Orderable_Webhooks {
	/**
	 * Webhook server URL.
	 *
	 * @var string
	 */
	public static $server_url = 'https://my.orderable.com/';

	/**
	 * Subscribe to email list.
	 *
	 * @param string $email
	 */
	public static function subscribe( $email = null ) {
		$email = ! $email ? get_option( 'admin_email' ) : $email;

		if ( empty( $email ) ) {
			return;
		}

		$user_id = get_current_user_id();

		$webook_url = self::$server_url . '?orderable-webhook=subscribe';

		$url_data = array(
			'email'      => $email,
			'first_name' => get_user_meta( $user_id, 'first_name', true ),
			'last_name'  => get_user_meta( $user_id, 'last_name', true ),
			'full_name'  => get_user_meta( $user_id, 'display_name', true ),
			'website'    => get_site_url(),
		);

		foreach ( $url_data as $url_data_key => $url_data_item ) {
			$webook_url .= sprintf( '&%s=%s', urlencode( $url_data_key ), urlencode( $url_data_item ) );
		}

		$opt_in_response      = wp_remote_post( $webook_url );
		$opt_in_response_body = ! is_wp_error( $opt_in_response ) ? json_decode( wp_remote_retrieve_body( $opt_in_response ), true ) : array();

		if ( ! empty( $opt_in_response_body['success'] ) ) {
			// Log opt in status for processing later.
			update_option( 'orderable_opt_in', 1 );
		}

		// Opt in successful so delete the flag.
		delete_option( 'orderable_opt_in' );
	}
}
