<?php
/**
 * Plugin Name:       UUCG Worship Schedule
 * Plugin URI:        https://greeleyuuc.org
 * Description:       Pulls the worship calendar from a Google Sheet and displays topics, categories, and speakers in list + calendar views.
 * Version:           1.0.5
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            UUCG
 * Author URI:        https://greeleyuuc.org
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       uucg-worship-schedule
 *
 * @package UUCG_Worship_Schedule
 */

defined( 'ABSPATH' ) || exit;

define( 'UUCG_WS_VERSION', '1.0.5' );
define( 'UUCG_WS_FILE', __FILE__ );
define( 'UUCG_WS_PATH', plugin_dir_path( __FILE__ ) );
define( 'UUCG_WS_URL', plugin_dir_url( __FILE__ ) );
define( 'UUCG_WS_OPTION', 'uucg_ws_settings' );
define( 'UUCG_WS_CACHE_KEY', 'uucg_ws_services_cache' );
define( 'UUCG_WS_CACHE_META', 'uucg_ws_services_meta' );
define( 'UUCG_WS_CRON_HOOK', 'uucg_ws_refresh_schedule' );

require_once UUCG_WS_PATH . 'includes/class-parser.php';
require_once UUCG_WS_PATH . 'includes/class-fetcher.php';
require_once UUCG_WS_PATH . 'includes/class-shortcode.php';
require_once UUCG_WS_PATH . 'includes/class-admin.php';

/**
 * Main plugin class.
 */
final class UUCG_Worship_Schedule {

	/**
	 * @var UUCG_Worship_Schedule|null
	 */
	private static $instance = null;

	/**
	 * @return UUCG_Worship_Schedule
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( UUCG_WS_CRON_HOOK, array( 'UUCG_WS_Fetcher', 'refresh_cache' ) );

		UUCG_WS_Shortcode::init();
		if ( is_admin() ) {
			UUCG_WS_Admin::init();
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'uucg-worship-schedule', false, dirname( plugin_basename( UUCG_WS_FILE ) ) . '/languages' );
	}

	public function register_assets() {
		wp_register_style(
			'uucg-ws-frontend',
			UUCG_WS_URL . 'assets/css/frontend.css',
			array(),
			UUCG_WS_VERSION
		);
		wp_register_script(
			'uucg-ws-frontend',
			UUCG_WS_URL . 'assets/js/frontend.js',
			array(),
			UUCG_WS_VERSION,
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
			'sheet_id'       => '16rMsZR37Poh2chtbFKCq76DXl_rBo0qDvGuSFK1zgxM',
			'sheet_gid'      => '2129728388',
			'cache_minutes'  => 60,
			'title'          => __( 'Worship Schedule', 'uucg-worship-schedule' ),
			'subtitle'       => __( 'Topics, speakers, and seasonal themes for the church year.', 'uucg-worship-schedule' ),
			'show_header'    => 1,
			'default_view'   => 'list', // list | calendar
			'show_past'      => 0,
			'show_empty'     => 1,
			'show_associate' => 1,
			'show_holidays'  => 1,
			'show_summary'   => 1,
			'timezone'       => 'America/Denver',
		);
	}

	/**
	 * @return array
	 */
	public static function get_settings() {
		$stored = get_option( UUCG_WS_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return wp_parse_args( $stored, self::defaults() );
	}
}

/**
 * @return UUCG_Worship_Schedule
 */
function uucg_worship_schedule() {
	return UUCG_Worship_Schedule::instance();
}

add_action( 'plugins_loaded', 'uucg_worship_schedule' );

register_activation_hook(
	__FILE__,
	function () {
		if ( false === get_option( UUCG_WS_OPTION ) ) {
			add_option( UUCG_WS_OPTION, UUCG_Worship_Schedule::defaults() );
		}
		if ( ! wp_next_scheduled( UUCG_WS_CRON_HOOK ) ) {
			wp_schedule_event( time() + 300, 'hourly', UUCG_WS_CRON_HOOK );
		}
		// Prime cache on activation if possible.
		UUCG_WS_Fetcher::refresh_cache( true );
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		$timestamp = wp_next_scheduled( UUCG_WS_CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, UUCG_WS_CRON_HOOK );
		}
	}
);
