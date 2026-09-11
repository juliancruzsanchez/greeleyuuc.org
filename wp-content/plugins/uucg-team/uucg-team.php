<?php
/**
 * Plugin Name:       UUCG Team
 * Plugin URI:        https://greeleyuuc.org
 * Description:       Renders the minister and Board of Trustees on the About Us page. Single shortcode: [uucg_team]. Data is hard-coded so the plugin is portable across UUA congregations; replace the contents of UUCG_Team::team_data() to customize.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            UUCG
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       uucg-team
 *
 * @package UUCG_Team
 */

defined( 'ABSPATH' ) || exit;

define( 'UUCG_TEAM_VERSION', '1.0.0' );
define( 'UUCG_TEAM_FILE', __FILE__ );
define( 'UUCG_TEAM_PATH', plugin_dir_path( __FILE__ ) );
define( 'UUCG_TEAM_URL', plugin_dir_url( __FILE__ ) );

require_once UUCG_TEAM_PATH . 'includes/class-shortcode.php';

/**
 * Plugin bootstrap.
 */
final class UUCG_Team {

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
		UUCG_Team_Shortcode::init();
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'uucg-team', false, dirname( plugin_basename( UUCG_TEAM_FILE ) ) . '/languages' );
	}

	public function register_assets() {
		wp_register_style(
			'uucg-team-frontend',
			UUCG_TEAM_URL . 'assets/css/frontend.css',
			array(),
			UUCG_TEAM_VERSION
		);
	}

	/**
	 * Team roster.
	 *
	 * The minister gets a big card with photo and bio. Board members get
	 * a uniform grid card with monogram, name, role, and a mailto link.
	 *
	 * Replace the contents of this method (or hook the
	 * `uucg_team_data` filter) to swap in your own congregation.
	 *
	 * @return array{minister: array, board: array<int, array{name:string,role:string,email:string}>}
	 */
	public static function team_data() {
		return array(
			'minister' => array(
				'name'      => 'Rev. Dr. Matt Ricke',
				'pronouns'  => 'he/they',
				'title'     => 'Contract Minister',
				'email'     => 'minister@greeleyuuc.org',
				'photo'     => 'minister.png',
				'bio'       => array(
					'Rev. Dr. Matt Ricke (he/they) is honored to serve as contract minister of the Unitarian Universalist Church of Greeley. Previously serving as UUCG’s community minister, he joins the congregation in the shared work of tending a progressive spiritual community: gathering for worship, asking meaningful questions, caring for one another, discerning a faithful future, and helping translate our deepest values into action in the wider world.',
					'Matt’s journey to Unitarian Universalist ministry has been shaped by a lifelong fascination with the places where people meet life’s deepest questions: Who are we? What do we owe one another? How do we find meaning amid change, loss, conflict, wonder, and transformation? His spiritual life has been nourished by the spacious theology of Unitarian Universalism, earth-centered spirituality, progressive Christianity, humanist thought, and a deep trust that wisdom is often found at the thresholds of our lives.',
					'Before entering congregational ministry, Matt spent much of his professional life as an educator and university administrator. He also served as a religious educator, crisis chaplain, spiritual director, and death doula. Matt has completed theological training with Cherry Hill Seminary and Phillips Theological Seminary’s Center for Ministry and Lay Training, participates in the EcoPreacher Initiative (BTS Center, Creation Justice Ministries, and Lexington Theological Seminary), and serves as a course instructor at Iliff School of Theology’s Death Care Collective.',
					'When he is not working, studying, or preparing for worship, he enjoys spending time with his husband, Eddie, and beloved dogs (Pepper, Calypso, and Meeko), exploring the natural beauty of Colorado, making music, practicing yoga, reading, and following the winding paths of spiritual curiosity wherever they may lead.',
				),
			),
			'board' => array(
				array( 'name' => 'Lorraine Ramos',  'role' => 'President',          'email' => 'Lorrainer@greeleyuuc.org' ),
				array( 'name' => 'Kathy Vaughn',    'role' => 'President-Emeritus', 'email' => 'Kathy@greeleyuuc.org' ),
				array( 'name' => 'Cherie Thomason', 'role' => 'Vice President',     'email' => 'Cheriet@greeleyuuc.org' ),
				array( 'name' => 'Meg du Bray',     'role' => 'Secretary',          'email' => 'Megd@greeleyuuc.org' ),
				array( 'name' => 'Anne Kremer',     'role' => 'Treasurer',          'email' => 'Annek@greeleyuuc.org' ),
				array( 'name' => 'Quinn Hill',      'role' => 'Member-at-Large',    'email' => 'Quinnh@greeleyuuc.org' ),
				array( 'name' => 'Nancy McFarlin',  'role' => 'Member-at-Large',    'email' => 'Nancym@greeleyuuc.org' ),
				array( 'name' => 'JC Sanchez',      'role' => 'Member-at-Large',    'email' => 'jcsanchez@greeleyuuc.org' ),
			),
		);
	}

	/**
	 * Derive a monogram (initials) for a name.
	 *
	 * @param string $name Full name.
	 * @return string Up to 3 uppercase initials.
	 */
	public static function initials( $name ) {
		$parts = preg_split( '/\s+/u', trim( $name ) );
		$initials = '';
		$count    = 0;
		foreach ( $parts as $part ) {
			if ( $count >= 3 ) {
				break;
			}
			$first = mb_substr( $part, 0, 1 );
			if ( $first !== '' ) {
				$initials .= mb_strtoupper( $first );
				$count++;
			}
		}
		return $initials !== '' ? $initials : '·';
	}
}

function uucg_team() {
	return UUCG_Team::instance();
}

add_action( 'plugins_loaded', 'uucg_team' );

register_activation_hook(
	__FILE__,
	function () {
		// No options to seed.
	}
);
