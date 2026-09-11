<?php
/**
 * Plugin Name:       UUCG Fair Share Calculator
 * Plugin URI:        https://greeleyuuc.org
 * Description:       Interactive "Fair Share" contribution guide for the Unitarian Universalist Church of Greeley. Renders a shortcode that takes a monthly or annual income and shows the suggested pledge at four generosity tiers (Supporter, Sustainer, Visionary, Transformer).
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            UUCG
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       uucg-fair-share
 *
 * @package UUCG_Fair_Share
 */

defined( 'ABSPATH' ) || exit;

define( 'UUCG_FS_VERSION', '1.0.0' );
define( 'UUCG_FS_FILE', __FILE__ );
define( 'UUCG_FS_PATH', plugin_dir_path( __FILE__ ) );
define( 'UUCG_FS_URL', plugin_dir_url( __FILE__ ) );

require_once UUCG_FS_PATH . 'includes/class-shortcode.php';
require_once UUCG_FS_PATH . 'includes/class-admin.php';

/**
 * Plugin bootstrap.
 */
final class UUCG_Fair_Share {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		UUCG_FS_Shortcode::init();
		if ( is_admin() ) {
			UUCG_FS_Admin::init();
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'uucg-fair-share', false, dirname( plugin_basename( UUCG_FS_FILE ) ) . '/languages' );
	}

	public function register_assets() {
		wp_register_style(
			'uucg-fs-frontend',
			UUCG_FS_URL . 'assets/css/frontend.css',
			array(),
			UUCG_FS_VERSION
		);
		wp_register_script(
			'uucg-fs-frontend',
			UUCG_FS_URL . 'assets/js/frontend.js',
			array(),
			UUCG_FS_VERSION,
			true
		);
	}

	/**
	 * The giving guide data, keyed by Adjusted Monthly Income.
	 *
	 * Each row contains:
	 *   - annual:  Approx. Adjusted Annual Income (display only)
	 *   - tiers:    { supporter, sustainer, visionary, transformer } as % of income
	 *
	 * Incomes below $1,000 fall back to the $1,000 row; incomes above
	 * $40,000 fall back to the $40,000 row. Incomes between rows are
	 * interpolated linearly.
	 *
	 * Source: UUA "Fair Share" Giving Guide
	 * (https://www.uua.org/leaderlab/outreach-marketing/website-help/wordpress-theme).
	 */
	public static function guide_data() {
		return array(
			array( 'monthly' => 1000,  'annual' => 12000,  'tiers' => array( 'supporter' => 2, 'sustainer' => 3, 'visionary' => 5,  'transformer' => 10 ) ),
			array( 'monthly' => 1500,  'annual' => 18000,  'tiers' => array( 'supporter' => 2, 'sustainer' => 3, 'visionary' => 5,  'transformer' => 10 ) ),
			array( 'monthly' => 2000,  'annual' => 25000,  'tiers' => array( 'supporter' => 2, 'sustainer' => 3, 'visionary' => 5,  'transformer' => 10 ) ),
			array( 'monthly' => 3000,  'annual' => 36000,  'tiers' => array( 'supporter' => 2, 'sustainer' => 3, 'visionary' => 5,  'transformer' => 10 ) ),
			array( 'monthly' => 4000,  'annual' => 50000,  'tiers' => array( 'supporter' => 3, 'sustainer' => 4, 'visionary' => 5,  'transformer' => 10 ) ),
			array( 'monthly' => 6500,  'annual' => 80000,  'tiers' => array( 'supporter' => 3, 'sustainer' => 4, 'visionary' => 6,  'transformer' => 10 ) ),
			array( 'monthly' => 8500,  'annual' => 100000, 'tiers' => array( 'supporter' => 3, 'sustainer' => 5, 'visionary' => 6,  'transformer' => 10 ) ),
			array( 'monthly' => 10000, 'annual' => 120000, 'tiers' => array( 'supporter' => 3, 'sustainer' => 5, 'visionary' => 6,  'transformer' => 10 ) ),
			array( 'monthly' => 12500, 'annual' => 150000, 'tiers' => array( 'supporter' => 4, 'sustainer' => 5, 'visionary' => 6,  'transformer' => 10 ) ),
			array( 'monthly' => 17000, 'annual' => 200000, 'tiers' => array( 'supporter' => 4, 'sustainer' => 6, 'visionary' => 7,  'transformer' => 10 ) ),
			array( 'monthly' => 25000, 'annual' => 300000, 'tiers' => array( 'supporter' => 5, 'sustainer' => 6, 'visionary' => 8,  'transformer' => 10 ) ),
			array( 'monthly' => 40000, 'annual' => 500000, 'tiers' => array( 'supporter' => 6, 'sustainer' => 7, 'visionary' => 9,  'transformer' => 10 ) ),
		);
	}

	/**
	 * Compute the suggestion for a given monthly income.
	 *
	 * Incomes that fall between two data rows are interpolated linearly so
	 * the % values feel continuous.
	 *
	 * @param float $monthly Monthly income.
	 * @return array{monthly: float, annual: float, tiers: array<string,float>}
	 */
	public static function suggestion( $monthly ) {
		$data = self::guide_data();

		// Clamp.
		if ( $monthly <= 0 ) {
			$monthly = $data[0]['monthly'];
		}
		$first = $data[0];
		$last  = end( $data );
		if ( $monthly < $first['monthly'] ) {
			return $first;
		}
		if ( $monthly >= $last['monthly'] ) {
			return $last;
		}

		// Find the two surrounding rows and interpolate the tier %s.
		for ( $i = 0; $i < count( $data ) - 1; $i++ ) {
			$lo = $data[ $i ];
			$hi = $data[ $i + 1 ];
			if ( $monthly >= $lo['monthly'] && $monthly < $hi['monthly'] ) {
				$span = $hi['monthly'] - $lo['monthly'];
				$t    = ( $monthly - $lo['monthly'] ) / $span;
				$tiers = array();
				foreach ( $lo['tiers'] as $key => $lo_pct ) {
					$hi_pct      = $hi['tiers'][ $key ];
					$tiers[ $key ] = round( $lo_pct + ( $hi_pct - $lo_pct ) * $t, 2 );
				}
				$annual = round( $lo['annual'] + ( $hi['annual'] - $lo['annual'] ) * $t );
				return array(
					'monthly' => $monthly,
					'annual'  => $annual,
					'tiers'   => $tiers,
				);
			}
		}
		return $last;
	}
}

function uucg_fair_share() {
	return UUCG_Fair_Share::instance();
}

add_action( 'plugins_loaded', 'uucg_fair_share' );

register_activation_hook(
	__FILE__,
	function () {
		// No options to seed. Activation is a no-op for now.
	}
);
