<?php
/**
 * Plugin Name:       UUCG In the Loop
 * Plugin URI:        https://greeleyuuc.org
 * Description:       Modern tabbed social feed for TikTok, Instagram, Facebook, and X — embed videos and posts in a beautiful responsive layout for the In the Loop page.
 * Version:           1.3.3
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            UUCG
 * Author URI:        https://greeleyuuc.org
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       uucg-in-the-loop
 *
 * @package UUCG_In_The_Loop
 */

defined( 'ABSPATH' ) || exit;

define( 'UUCG_ITL_VERSION', '1.3.3' );
define( 'UUCG_ITL_FILE', __FILE__ );
define( 'UUCG_ITL_PATH', plugin_dir_path( __FILE__ ) );
define( 'UUCG_ITL_URL', plugin_dir_url( __FILE__ ) );
define( 'UUCG_ITL_OPTION', 'uucg_itl_settings' );

require_once UUCG_ITL_PATH . 'includes/class-embeds.php';
require_once UUCG_ITL_PATH . 'includes/class-shortcode.php';
require_once UUCG_ITL_PATH . 'includes/class-admin.php';

/**
 * Main plugin bootstrap.
 */
final class UUCG_In_The_Loop {

	/**
	 * Singleton instance.
	 *
	 * @var UUCG_In_The_Loop|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return UUCG_In_The_Loop
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		UUCG_ITL_Shortcode::init();
		if ( is_admin() ) {
			UUCG_ITL_Admin::init();
		}
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'uucg-in-the-loop', false, dirname( plugin_basename( UUCG_ITL_FILE ) ) . '/languages' );
	}

	/**
	 * Register frontend assets (enqueued only when shortcode renders).
	 */
	public function register_assets() {
		wp_register_style(
			'uucg-itl-frontend',
			UUCG_ITL_URL . 'assets/css/frontend.css',
			array(),
			UUCG_ITL_VERSION
		);

		wp_register_script(
			'uucg-itl-frontend',
			UUCG_ITL_URL . 'assets/js/frontend.js',
			array(),
			UUCG_ITL_VERSION,
			true
		);
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'title'            => __( 'In the Loop', 'uucg-in-the-loop' ),
			'subtitle'         => __( 'Stay connected with the latest from our community.', 'uucg-in-the-loop' ),
			'default_tab'      => 'tiktok',
			'columns'          => 3,
			'show_header'      => 1,
			'tiktok_username'  => '',
			'tiktok_profile'   => '',
			'tiktok_urls'      => '',
			'instagram_username' => '',
			'instagram_profile'  => '',
			'instagram_urls'     => '',
			'facebook_page'    => '',
			'facebook_urls'    => '',
			'facebook_use_page_plugin' => 0,
			'x_username'       => '',
			'x_urls'           => '',
			'x_use_timeline'   => 0,
			'timeline_height'  => 700,
			'page_plugin_tabs' => 'timeline',
		);
	}

	/**
	 * Get merged settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$stored = get_option( UUCG_ITL_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return wp_parse_args( $stored, self::defaults() );
	}
}

/**
 * Boot the plugin.
 */
function uucg_in_the_loop() {
	return UUCG_In_The_Loop::instance();
}

add_action( 'plugins_loaded', 'uucg_in_the_loop' );

/**
 * Activation: seed defaults if missing.
 */
register_activation_hook(
	__FILE__,
	function () {
		if ( false === get_option( UUCG_ITL_OPTION ) ) {
			add_option( UUCG_ITL_OPTION, UUCG_In_The_Loop::defaults() );
		}
	}
);
