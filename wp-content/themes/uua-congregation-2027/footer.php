<?php
/**
 * Footer — contact block, widgets, affiliation logos, social.
 *
 * @package uucg-modern
 */

$address = get_theme_mod( 'uuatheme_congregation_address', '' );
$phone   = get_theme_mod( 'uucg_contact_phone', '' );
$email   = get_theme_mod( 'uucg_contact_email', '' );
$hours   = get_theme_mod( 'uucg_contact_hours', '' );
$map_url = get_theme_mod( 'uucg_contact_map_url', '' );

// Build a sensible Google Maps link if no custom URL.
if ( ! $map_url && $address ) {
	$map_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address );
}

$has_contact = ( $address || $phone || $email || $hours );
?>

			</main><!-- #main -->
		</div><!-- .content -->
	</div><!-- .container -->
</div><!-- .wrap -->

<footer class="content-info" role="contentinfo">
	<div class="spacer container">&nbsp;</div>

	<div class="container">

		<?php if ( $has_contact ) : ?>
			<section class="uucg-footer-contact" aria-label="<?php esc_attr_e( 'Contact information', 'uucg-modern' ); ?>">
				<div class="uucg-footer-contact__grid">
					<div class="uucg-footer-contact__intro">
						<p class="uucg-footer-contact__kicker"><?php esc_html_e( 'Visit & connect', 'uucg-modern' ); ?></p>
						<h2 class="uucg-footer-contact__heading"><?php esc_html_e( 'We’d love to welcome you', 'uucg-modern' ); ?></h2>
						<p class="uucg-footer-contact__blurb">
							<?php esc_html_e( 'Unitarian Universalist Church of Greeley — a liberal progressive faith community.', 'uucg-modern' ); ?>
						</p>
					</div>

					<ul class="uucg-footer-contact__list">
						<?php if ( $address ) : ?>
							<li class="uucg-footer-contact__item">
								<span class="uucg-footer-contact__icon" aria-hidden="true">
									<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
								</span>
								<div>
									<span class="uucg-footer-contact__label"><?php esc_html_e( 'Address', 'uucg-modern' ); ?></span>
									<?php if ( $map_url ) : ?>
										<a class="uucg-footer-contact__value" href="<?php echo esc_url( $map_url ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo nl2br( esc_html( $address ) ); ?>
										</a>
									<?php else : ?>
										<span class="uucg-footer-contact__value"><?php echo nl2br( esc_html( $address ) ); ?></span>
									<?php endif; ?>
								</div>
							</li>
						<?php endif; ?>

						<?php if ( $phone ) : ?>
							<li class="uucg-footer-contact__item">
								<span class="uucg-footer-contact__icon" aria-hidden="true">
									<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M6.62 10.79a15.15 15.15 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24c1.12.37 2.33.57 3.57.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.4 21 3 13.6 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1.02l-2.2 2.2z"/></svg>
								</span>
								<div>
									<span class="uucg-footer-contact__label"><?php esc_html_e( 'Phone', 'uucg-modern' ); ?></span>
									<a class="uucg-footer-contact__value" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
										<?php echo esc_html( $phone ); ?>
									</a>
								</div>
							</li>
						<?php endif; ?>

						<?php if ( $email ) : ?>
							<li class="uucg-footer-contact__item">
								<span class="uucg-footer-contact__icon" aria-hidden="true">
									<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5L4 8V6l8 5 8-5v2z"/></svg>
								</span>
								<div>
									<span class="uucg-footer-contact__label"><?php esc_html_e( 'Email', 'uucg-modern' ); ?></span>
									<a class="uucg-footer-contact__value" href="<?php echo esc_url( 'mailto:' . $email ); ?>">
										<?php echo esc_html( $email ); ?>
									</a>
								</div>
							</li>
						<?php endif; ?>

						<?php if ( $hours ) : ?>
							<li class="uucg-footer-contact__item">
								<span class="uucg-footer-contact__icon" aria-hidden="true">
									<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
								</span>
								<div>
									<span class="uucg-footer-contact__label"><?php esc_html_e( 'Hours', 'uucg-modern' ); ?></span>
									<span class="uucg-footer-contact__value"><?php echo nl2br( esc_html( $hours ) ); ?></span>
								</div>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<div class="row footer-widgets">
			<?php
			dynamic_sidebar( 'sidebar-footer-one' );
			dynamic_sidebar( 'sidebar-footer-two' );
			dynamic_sidebar( 'sidebar-footer-three' );
			?>

			<section class="col-md-3 affiliation-logos widget text-3 widget_text">
				<a href="https://uua.org/" title="Unitarian Universalist Association">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/uua_logo.png' ); ?>" alt="Unitarian Universalist Association Logo" class="img-responsive uua-flag">
				</a>
				<?php if ( $uuatheme_welcoming_congregation_link = get_theme_mod( 'uuatheme_welcoming_congregation_link' ) ) : ?>
					<a href="<?php echo esc_url( $uuatheme_welcoming_congregation_link ); ?>" title="Welcoming Congregation">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo-welcoming-congregation.png' ); ?>" alt="Welcoming Congregation Logo" class="welcoming-congregation-logo">
					</a>
				<?php endif; ?>
				<?php if ( $uuatheme_green_sanctuary_link = get_theme_mod( 'uuatheme_green_sanctuary_link' ) ) : ?>
					<a href="<?php echo esc_url( $uuatheme_green_sanctuary_link ); ?>" title="Green Sanctuary">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo-green-sanctuary.png' ); ?>" alt="Green Sanctuary Logo" class="green-sanctuary-logo">
					</a>
				<?php endif; ?>
			</section>
		</div>

		<div class="footer-details">
			<div class="col-md-4">
				<div class="copyright">
					<?php
					if ( $uuatheme_copyright_text = get_theme_mod( 'uuatheme_copyright_text' ) ) {
						echo wp_kses_post( $uuatheme_copyright_text );
					} else {
						printf( '&copy; %d %s', (int) gmdate( 'Y' ), esc_html( get_bloginfo( 'name' ) ) );
					}
					?>
				</div>
			</div>
			<div class="col-md-8 footer-navigation">
				<?php get_template_part( 'partials/social-media-icons' ); ?>

				<?php
				if ( has_nav_menu( 'footer_navigation' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'footer_navigation',
							'menu_class'     => 'nav nav-pills',
						)
					);
				}
				?>
			</div>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>

</body>
</html>
