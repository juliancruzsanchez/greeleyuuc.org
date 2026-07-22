<?php
/**
 * Newsletter shortcode renderer.
 *
 * @package UUCG_Newsletter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcodes: [uucg_newsletter] [newsletter_signup]
 */
class UUCG_NL_Shortcode {

	/**
	 * Register.
	 */
	public static function init() {
		add_shortcode( 'uucg_newsletter', array( __CLASS__, 'render' ) );
		add_shortcode( 'newsletter_signup', array( __CLASS__, 'render' ) );
	}

	/**
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$settings = UUCG_Newsletter::get_settings();

		$atts = shortcode_atts(
			array(
				'title'       => $settings['title'],
				'subtitle'    => $settings['subtitle'],
				'button'      => $settings['button_label'],
				'placeholder' => $settings['placeholder'],
				'style'       => 'card', // card | full | band | full-band | compact | minimal
				'show_title'  => '1',
			),
			$atts,
			'uucg_newsletter'
		);

		$style = sanitize_key( $atts['style'] );
		// Aliases.
		if ( 'wide' === $style ) {
			$style = 'full';
		}
		if ( 'fullband' === $style || 'band-full' === $style ) {
			$style = 'full-band';
		}
		if ( ! in_array( $style, array( 'card', 'full', 'band', 'full-band', 'compact', 'minimal' ), true ) ) {
			$style = 'card';
		}

		$uid = 'uucg-nl-' . wp_unique_id();

		wp_enqueue_style( 'uucg-nl-frontend' );
		wp_enqueue_script( 'uucg-nl-frontend' );
		wp_localize_script(
			'uucg-nl-frontend',
			'uucgNl',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'uucg_nl_subscribe' ),
				'i18n'    => array(
					'sending' => __( 'Signing you up…', 'uucg-newsletter' ),
					'error'   => __( 'Something went wrong. Please try again.', 'uucg-newsletter' ),
				),
			)
		);

		$show_title = ! ( '0' === (string) $atts['show_title'] || 'false' === strtolower( (string) $atts['show_title'] ) );

		ob_start();
		?>
		<section
			class="uucg-nl uucg-nl--<?php echo esc_attr( $style ); ?>"
			id="<?php echo esc_attr( $uid ); ?>"
			aria-label="<?php echo esc_attr( $atts['title'] ? $atts['title'] : __( 'Newsletter signup', 'uucg-newsletter' ) ); ?>"
		>
			<div class="uucg-nl__inner">
				<?php if ( $show_title && ( $atts['title'] || $atts['subtitle'] ) ) : ?>
					<header class="uucg-nl__header">
						<?php if ( $atts['title'] ) : ?>
							<h2 class="uucg-nl__title"><?php echo esc_html( $atts['title'] ); ?></h2>
						<?php endif; ?>
						<?php if ( $atts['subtitle'] ) : ?>
							<p class="uucg-nl__subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
						<?php endif; ?>
					</header>
				<?php endif; ?>

				<form class="uucg-nl__form" method="post" novalidate data-uucg-nl-form>
					<label class="uucg-nl__label screen-reader-text" for="<?php echo esc_attr( $uid ); ?>-email">
						<?php esc_html_e( 'Email address', 'uucg-newsletter' ); ?>
					</label>
					<div class="uucg-nl__row">
						<input
							type="email"
							class="uucg-nl__input"
							id="<?php echo esc_attr( $uid ); ?>-email"
							name="email"
							autocomplete="email"
							inputmode="email"
							required
							placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
						/>
						<button type="submit" class="uucg-nl__btn">
							<span class="uucg-nl__btn-label"><?php echo esc_html( $atts['button'] ); ?></span>
						</button>
					</div>
					<!-- Honeypot -->
					<div class="uucg-nl__hp" aria-hidden="true">
						<label for="<?php echo esc_attr( $uid ); ?>-website"><?php esc_html_e( 'Website', 'uucg-newsletter' ); ?></label>
						<input type="text" name="website" id="<?php echo esc_attr( $uid ); ?>-website" value="" tabindex="-1" autocomplete="off" />
					</div>
					<p class="uucg-nl__status" data-uucg-nl-status role="status" aria-live="polite" hidden></p>
					<p class="uucg-nl__fineprint">
						<?php esc_html_e( 'No spam — just meaningful updates. Unsubscribe anytime.', 'uucg-newsletter' ); ?>
					</p>
				</form>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}
