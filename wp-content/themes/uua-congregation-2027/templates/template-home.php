<?php
/**
 * Template Name: Home Page
 * Description: Modern home page with full-bleed chalice hero, services block, contact info, and newsletter signup. Use this for the static front page.
 *
 * @package uucg-modern
 * @version 1.5.1
 */

get_header();
?>

<main id="main" class="main uucg-home" role="main">

	<section class="uucg-home-hero" aria-label="<?php esc_attr_e( 'Welcome to UUCG', 'uucg-modern' ); ?>">
		<div class="uucg-home-hero__inner">
			<h1 class="uucg-home-hero__title"><?php esc_html_e( 'Welcome to the Unitarian Universalist Church of Greeley!', 'uucg-modern' ); ?></h1>
			<p class="uucg-home-hero__lede"><?php esc_html_e( 'A liberal progressive faith community', 'uucg-modern' ); ?><br><?php esc_html_e( 'in the heart of Greeley', 'uucg-modern' ); ?></p>

			<aside class="uucg-home-hero__invite" aria-label="<?php esc_attr_e( 'Invitation card', 'uucg-modern' ); ?>">
				<p class="uucg-home-hero__invite-line"><?php esc_html_e( 'As we work to make sense of this world in which we live…', 'uucg-modern' ); ?></p>
				<p class="uucg-home-hero__invite-line uucg-home-hero__invite-line--accent"><?php esc_html_e( 'Breathe.', 'uucg-modern' ); ?></p>
				<p class="uucg-home-hero__invite-line"><?php esc_html_e( 'Do not let go of faith and hope and love.', 'uucg-modern' ); ?></p>
				<p class="uucg-home-hero__invite-line uucg-home-hero__invite-line--cta"><?php esc_html_e( 'Looking for a community to help with this?', 'uucg-modern' ); ?><br><?php esc_html_e( 'Join us at 10 AM each Sunday.', 'uucg-modern' ); ?></p>
			</aside>
		</div>
	</section>

	<section class="uucg-home-services" aria-label="<?php esc_attr_e( 'Sunday service', 'uucg-modern' ); ?>">
		<div class="uucg-home-section">
			<h2 class="uucg-home-services__title"><?php esc_html_e( 'Services are held Sundays at 10 AM. We hope you will join us in our sanctuary!', 'uucg-modern' ); ?></h2>
			<p class="uucg-home-services__sub">
				<?php
				/* translators: 1: opening parentheses, 2: link, 3: closing parentheses */
				echo wp_kses(
					sprintf(
						__( '(For video recordings of older services %1$sclick here%2$s. We are not currently recording services each week. If you would like to help record and post services, please let us know.)', 'uucg-modern' ),
						'<a href="' . esc_url( home_url( '/video-recordings-of-recent-hybrid-services/' ) ) . '">',
						'</a>'
					),
					array( 'a' => array( 'href' => array() ) )
				);
				?>
			</p>
			<p class="uucg-home-services__tagline"><?php esc_html_e( 'We gather in love and fellowship to worship, foster spiritual growth, serve humanity, and to understand ourselves and our universe.', 'uucg-modern' ); ?></p>

			<div class="uucg-home-services__contact">
				<span class="uucg-home-services__pride" aria-hidden="true"></span>
				<p class="uucg-home-services__phone"><a href="tel:9703516751">970-351-6751</a> <span class="uucg-home-services__sep" aria-hidden="true">|</span> <a href="https://maps.google.com/?q=929+15th+Street,+Greeley,+CO+80631" target="_blank" rel="noopener noreferrer">929 15th Street, Greeley, CO 80631</a></p>
			</div>
		</div>
	</section>

	<section class="uucg-home-newsletter" aria-label="<?php esc_attr_e( 'Newsletter signup', 'uucg-modern' ); ?>">
		<div class="uucg-home-section">
			<h2 class="uucg-home-newsletter__title"><?php esc_html_e( 'Subscribe To Our Newsletter!', 'uucg-modern' ); ?></h2>
			<div class="uucg-home-newsletter__form">
				<?php echo do_shortcode( '[uucg_newsletter style="card" show_title="0"]' ); ?>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();
