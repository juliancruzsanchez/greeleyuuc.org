<?php
/**
 * Plugin Name:       UUCG Newsletter Signup
 * Plugin URI:        https://greeleyuuc.org
 * Description:       Beautiful one-field Mailchimp signup. Shortcode: [uucg_newsletter]. Works with your Mailchimp API key + audience ID.
 * Version:           1.1.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            UUCG
 * License:           GPL-2.0-or-later
 * Text Domain:       uucg-newsletter
 *
 * @package UUCG_Newsletter
 */

defined( 'ABSPATH' ) || exit;

define( 'UUCG_NL_VERSION', '1.1.1' );
define( 'UUCG_NL_FILE', __FILE__ );
define( 'UUCG_NL_PATH', plugin_dir_path( __FILE__ ) );
define( 'UUCG_NL_URL', plugin_dir_url( __FILE__ ) );
define( 'UUCG_NL_OPTION', 'uucg_nl_settings' );

require_once UUCG_NL_PATH . 'includes/class-mailchimp.php';
require_once UUCG_NL_PATH . 'includes/class-shortcode.php';
require_once UUCG_NL_PATH . 'includes/class-admin.php';

/**
 * Bootstrap.
 */
final class UUCG_Newsletter {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_ajax_uucg_nl_subscribe', array( 'UUCG_NL_Mailchimp', 'ajax_subscribe' ) );
		add_action( 'wp_ajax_nopriv_uucg_nl_subscribe', array( 'UUCG_NL_Mailchimp', 'ajax_subscribe' ) );

		UUCG_NL_Shortcode::init();
		if ( is_admin() ) {
			UUCG_NL_Admin::init();
		}
	}

	/**
	 * Register assets (enqueued when shortcode renders).
	 */
	public function register_assets() {
		wp_register_style(
			'uucg-nl-frontend',
			UUCG_NL_URL . 'assets/css/frontend.css',
			array(),
			UUCG_NL_VERSION
		);
		wp_register_script(
			'uucg-nl-frontend',
			UUCG_NL_URL . 'assets/js/frontend.js',
			array(),
			UUCG_NL_VERSION,
			true
		);
	}

	/**
	 * @return array
	 */
	public static function defaults() {
		return array(
			'api_key'          => '',
			'list_id'          => '',
			'double_optin'     => 1,
			'title'            => __( 'Stay in the loop', 'uucg-newsletter' ),
			'subtitle'         => __( 'Get reflections, events, and community news in your inbox.', 'uucg-newsletter' ),
			'button_label'     => __( 'Sign up', 'uucg-newsletter' ),
			'placeholder'      => __( 'Your email address', 'uucg-newsletter' ),
			'success_message'  => __( 'You’re almost in! Check your email to confirm.', 'uucg-newsletter' ),
			'use_mc_plugin'    => 1,
		);
	}

	/**
	 * @return array
	 */
	public static function get_settings() {
		$stored = get_option( UUCG_NL_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return wp_parse_args( $stored, self::defaults() );
	}

	/**
	 * Resolve API key: plugin setting, then official Mailchimp plugin option.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		$s = self::get_settings();
		if ( ! empty( $s['api_key'] ) ) {
			return trim( $s['api_key'] );
		}
		if ( ! empty( $s['use_mc_plugin'] ) ) {
			$key = get_option( 'mc_api_key', '' );
			if ( $key ) {
				return trim( (string) $key );
			}
		}
		return '';
	}

	/**
	 * Resolve list/audience ID.
	 *
	 * @return string
	 */
	public static function get_list_id() {
		$s = self::get_settings();
		if ( ! empty( $s['list_id'] ) ) {
			return trim( $s['list_id'] );
		}
		if ( ! empty( $s['use_mc_plugin'] ) ) {
			$id = get_option( 'mc_list_id', '' );
			if ( $id ) {
				return trim( (string) $id );
			}
		}
		return '';
	}
}

/**
 * @return UUCG_Newsletter
 */
function uucg_newsletter() {
	return UUCG_Newsletter::instance();
}

add_action( 'plugins_loaded', 'uucg_newsletter' );

register_activation_hook(
	__FILE__,
	function () {
		if ( false === get_option( UUCG_NL_OPTION ) ) {
			add_option( UUCG_NL_OPTION, UUCG_Newsletter::defaults() );
		}
	}
);
