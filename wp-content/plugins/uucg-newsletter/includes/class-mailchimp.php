<?php
/**
 * Mailchimp API subscribe + AJAX handler.
 *
 * @package UUCG_Newsletter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Mailchimp integration.
 */
class UUCG_NL_Mailchimp {

	/**
	 * AJAX: subscribe email.
	 */
	public static function ajax_subscribe() {
		check_ajax_referer( 'uucg_nl_subscribe', 'nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( ! $email || ! is_email( $email ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please enter a valid email address.', 'uucg-newsletter' ),
				),
				400
			);
		}

		// Honeypot.
		if ( ! empty( $_POST['website'] ) ) {
			wp_send_json_success(
				array(
					'message' => UUCG_Newsletter::get_settings()['success_message'],
				)
			);
		}

		$result = self::subscribe( $email );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		$settings = UUCG_Newsletter::get_settings();
		wp_send_json_success(
			array(
				'message' => $settings['success_message'],
			)
		);
	}

	/**
	 * Subscribe an email via Mailchimp API 3.0.
	 *
	 * @param string $email Email.
	 * @return true|WP_Error
	 */
	public static function subscribe( $email ) {
		$api_key = UUCG_Newsletter::get_api_key();
		$list_id = UUCG_Newsletter::get_list_id();
		$settings = UUCG_Newsletter::get_settings();

		if ( ! $api_key || ! $list_id ) {
			return new WP_Error(
				'uucg_nl_config',
				__( 'Newsletter signup is not configured yet. Please add a Mailchimp API key and audience ID in Settings → Newsletter.', 'uucg-newsletter' )
			);
		}

		$dc = self::datacenter_from_key( $api_key );
		if ( ! $dc ) {
			return new WP_Error(
				'uucg_nl_key',
				__( 'Invalid Mailchimp API key format. It should end with a datacenter like -us12.', 'uucg-newsletter' )
			);
		}

		$subscriber_hash = md5( strtolower( $email ) );
		$url             = sprintf(
			'https://%s.api.mailchimp.com/3.0/lists/%s/members/%s',
			rawurlencode( $dc ),
			rawurlencode( $list_id ),
			$subscriber_hash
		);

		$status = ! empty( $settings['double_optin'] ) ? 'pending' : 'subscribed';

		$body = array(
			'email_address' => $email,
			'status_if_new' => $status,
			'status'        => $status,
		);

		$response = wp_remote_request(
			$url,
			array(
				'method'  => 'PUT',
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'apikey ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'uucg_nl_http',
				__( 'Could not reach Mailchimp. Please try again in a moment.', 'uucg-newsletter' )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Success range.
		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		// Already subscribed / member exists with different status.
		if ( isset( $data['title'] ) && false !== stripos( $data['title'], 'Member Exists' ) ) {
			return true;
		}
		if ( isset( $data['status'] ) && in_array( $data['status'], array( 'subscribed', 'pending' ), true ) ) {
			return true;
		}

		$detail = isset( $data['detail'] ) ? $data['detail'] : __( 'Signup failed. Please try again.', 'uucg-newsletter' );

		// Friendlier copy for common cases.
		if ( false !== stripos( $detail, 'looks fake' ) || false !== stripos( $detail, 'invalid' ) ) {
			$detail = __( 'That email doesn’t look valid. Please double-check and try again.', 'uucg-newsletter' );
		}

		return new WP_Error( 'uucg_nl_api', $detail );
	}

	/**
	 * Extract datacenter from API key (e.g. us12).
	 *
	 * @param string $api_key Key.
	 * @return string|null
	 */
	public static function datacenter_from_key( $api_key ) {
		if ( preg_match( '/-([a-z]{2}\d+)$/i', $api_key, $m ) ) {
			return strtolower( $m[1] );
		}
		return null;
	}
}
