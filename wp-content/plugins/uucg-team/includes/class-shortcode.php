<?php
/**
 * Shortcode handler for the UUCG Team plugin.
 *
 * @package UUCG_Team
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the [uucg_team] shortcode: minister card + board grid.
 */
class UUCG_Team_Shortcode {

	public static function init() {
		add_shortcode( 'uucg_team', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render the team section.
	 *
	 * @param array|string $atts Shortcode attributes (unused for now).
	 * @return string
	 */
	public static function render( $atts = array() ) {
		wp_enqueue_style( 'uucg-team-frontend' );

		$data = UUCG_Team::team_data();
		$minister = $data['minister'];
		$board    = $data['board'];

		$photo_url = UUCG_TEAM_URL . 'assets/images/' . $minister['photo'];

		ob_start();
		?>
		<section class="uucg-team" aria-label="<?php esc_attr_e( 'Our minister and board of trustees', 'uucg-team' ); ?>">

			<article class="uucg-team__minister" aria-labelledby="uucg-team-minister-name">
				<div class="uucg-team__minister-photo">
					<img
						src="<?php echo esc_url( $photo_url ); ?>"
						alt="<?php echo esc_attr( $minister['name'] ); ?>"
						loading="lazy"
						decoding="async"
					/>
				</div>
				<div class="uucg-team__minister-body">
					<p class="uucg-team__kicker"><?php esc_html_e( 'Our minister', 'uucg-team' ); ?></p>
					<h2 id="uucg-team-minister-name" class="uucg-team__minister-name">
						<?php echo esc_html( $minister['name'] ); ?>
						<?php if ( ! empty( $minister['pronouns'] ) ) : ?>
							<span class="uucg-team__minister-pronouns">(<?php echo esc_html( $minister['pronouns'] ); ?>)</span>
						<?php endif; ?>
					</h2>
					<?php if ( ! empty( $minister['title'] ) ) : ?>
						<p class="uucg-team__minister-title"><?php echo esc_html( $minister['title'] ); ?></p>
					<?php endif; ?>

					<div class="uucg-team__minister-bio">
						<?php foreach ( $minister['bio'] as $paragraph ) : ?>
							<p><?php echo esc_html( $paragraph ); ?></p>
						<?php endforeach; ?>
					</div>

					<?php if ( ! empty( $minister['email'] ) ) : ?>
						<p class="uucg-team__minister-contact">
							<a class="uucg-team__email" href="mailto:<?php echo esc_attr( $minister['email'] ); ?>">
								<?php echo esc_html( $minister['email'] ); ?>
							</a>
						</p>
					<?php endif; ?>
				</div>
			</article>

			<div class="uucg-team__board">
				<header class="uucg-team__board-header">
					<p class="uucg-team__kicker"><?php esc_html_e( 'Board of trustees', 'uucg-team' ); ?></p>
					<h3 class="uucg-team__board-title"><?php esc_html_e( 'Our 2026-2027 board', 'uucg-team' ); ?></h3>
				</header>

				<div class="uucg-team__board-grid" role="list">
					<?php foreach ( $board as $member ) : ?>
						<article class="uucg-team__board-card" role="listitem">
							<div class="uucg-team__board-monogram" aria-hidden="true">
								<?php echo esc_html( UUCG_Team::initials( $member['name'] ) ); ?>
							</div>
							<h4 class="uucg-team__board-name"><?php echo esc_html( $member['name'] ); ?></h4>
							<p class="uucg-team__board-role"><?php echo esc_html( $member['role'] ); ?></p>
							<?php if ( ! empty( $member['email'] ) ) : ?>
								<a class="uucg-team__board-email" href="mailto:<?php echo esc_attr( $member['email'] ); ?>">
									<?php echo esc_html( $member['email'] ); ?>
								</a>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</div>

		</section>
		<?php
		return (string) ob_get_clean();
	}
}
