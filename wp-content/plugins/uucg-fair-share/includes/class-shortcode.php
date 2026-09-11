<?php
/**
 * Shortcode handler for the UUCG Fair Share Calculator.
 *
 * @package UUCG_Fair_Share
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the [fair_share_calculator] shortcode.
 */
class UUCG_FS_Shortcode {

	public static function init() {
		add_shortcode( 'fair_share_calculator', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render the calculator.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'initial' => '5000', // default starting monthly income
				'source'  => '',      // optional caption (e.g. "Based on UUA Fair Share Giving Guide")
			),
			$atts,
			'fair_share_calculator'
		);

		wp_enqueue_style( 'uucg-fs-frontend' );
		wp_enqueue_script( 'uucg-fs-frontend' );

		$tiers = array(
			array(
				'key'         => 'supporter',
				'label'       => __( 'Supporter', 'uucg-fair-share' ),
				'range'       => '2–6%',
				'description' => __( 'The congregation is an important part of my spiritual life. My fair share starts at the lower end of the range and rises with my capacity.', 'uucg-fair-share' ),
				'color'       => '#f6c343', // warm gold
			),
			array(
				'key'         => 'sustainer',
				'label'       => __( 'Sustainer', 'uucg-fair-share' ),
				'range'       => '3–7%',
				'description' => __( 'The congregation is my central community. I give at a sustaining level so we can plan ahead.', 'uucg-fair-share' ),
				'color'       => '#4f86c6', // sky
			),
			array(
				'key'         => 'visionary',
				'label'       => __( 'Visionary', 'uucg-fair-share' ),
				'range'       => '5–9%',
				'description' => __( 'I am committed to growth and change. My gift funds the work that makes UUCG a leader in our community.', 'uucg-fair-share' ),
				'color'       => '#5fbf6f', // green
			),
			array(
				'key'         => 'transformer',
				'label'       => __( 'Transformer', 'uucg-fair-share' ),
				'range'       => '10%',
				'description' => __( 'I want to make a transformational investment in the future of liberal religion. My gift sets the bar for what is possible.', 'uucg-fair-share' ),
				'color'       => '#e07a5f', // terracotta
			),
		);

		$guide = UUCG_Fair_Share::guide_data();
		$min   = (int) $guide[0]['monthly'];
		$max   = (int) end( $guide )['monthly'];
		$initial = max( $min, min( $max, (int) $atts['initial'] ) );

		ob_start();
		?>
		<section class="uucg-fs" data-min="<?php echo esc_attr( $min ); ?>" data-max="<?php echo esc_attr( $max ); ?>" aria-label="<?php esc_attr_e( 'Fair Share contribution guide', 'uucg-fair-share' ); ?>">
			<header class="uucg-fs__header">
				<p class="uucg-fs__kicker"><?php esc_html_e( 'How much should I pledge?', 'uucg-fair-share' ); ?></p>
				<h2 class="uucg-fs__title"><?php esc_html_e( 'Find your fair share.', 'uucg-fair-share' ); ?></h2>
				<?php if ( ! empty( $atts['source'] ) ) : ?>
					<p class="uucg-fs__source"><?php echo esc_html( $atts['source'] ); ?></p>
				<?php else : ?>
					<p class="uucg-fs__source"><?php esc_html_e( 'Based on the UUA Fair Share Contribution Guide.', 'uucg-fair-share' ); ?></p>
				<?php endif; ?>
			</header>

			<div class="uucg-fs__input" role="group" aria-label="<?php esc_attr_e( 'Income input', 'uucg-fair-share' ); ?>">
				<div class="uucg-fs__field">
					<label for="uucg-fs-income"><?php esc_html_e( 'Adjusted monthly income', 'uucg-fair-share' ); ?></label>
					<div class="uucg-fs__field-input">
						<span class="uucg-fs__currency">$</span>
						<input
							id="uucg-fs-income"
							type="number"
							inputmode="numeric"
							min="<?php echo esc_attr( $min ); ?>"
							max="<?php echo esc_attr( $max * 2 ); ?>"
							step="100"
							value="<?php echo esc_attr( $initial ); ?>"
							aria-describedby="uucg-fs-annual"
						/>
						<span class="uucg-fs__period"><?php esc_html_e( '/ month', 'uucg-fair-share' ); ?></span>
					</div>
				</div>
				<div class="uucg-fs__field">
					<span class="uucg-fs__label"><?php esc_html_e( 'Approx. adjusted annual income', 'uucg-fair-share' ); ?></span>
					<span class="uucg-fs__annual" id="uucg-fs-annual" data-prefix="$">—</span>
				</div>
				<label class="uucg-fs__slider">
					<span class="uucg-fs__slider-bounds">
						<span data-bound="<?php echo esc_attr( $min ); ?>">$<?php echo esc_html( number_format( $min ) ); ?></span>
						<span data-bound="<?php echo esc_attr( $max ); ?>">$<?php echo esc_html( number_format( $max ) ); ?></span>
					</span>
					<input
						type="range"
						class="uucg-fs__range"
						min="<?php echo esc_attr( $min ); ?>"
						max="<?php echo esc_attr( $max ); ?>"
						step="100"
						value="<?php echo esc_attr( $initial ); ?>"
						aria-label="<?php esc_attr_e( 'Income slider', 'uucg-fair-share' ); ?>"
					/>
				</label>
			</div>

			<div class="uucg-fs__tiers" role="list">
				<?php foreach ( $tiers as $tier ) : ?>
					<article class="uucg-fs__tier" data-tier="<?php echo esc_attr( $tier['key'] ); ?>" role="listitem" style="--tier-accent: <?php echo esc_attr( $tier['color'] ); ?>;">
						<header class="uucg-fs__tier-head">
							<span class="uucg-fs__tier-dot" aria-hidden="true"></span>
							<h3 class="uucg-fs__tier-name"><?php echo esc_html( $tier['label'] ); ?></h3>
							<span class="uucg-fs__tier-range"><?php echo esc_html( $tier['range'] ); ?> <span class="uucg-fs__tier-range-of"><?php esc_html_e( 'of income', 'uucg-fair-share' ); ?></span></span>
						</header>
						<p class="uucg-fs__tier-pct"><span class="uucg-fs__tier-pct-value" data-field="pct">—</span>%</p>
						<p class="uucg-fs__tier-pledge">$<span class="uucg-fs__tier-pledge-value" data-field="pledge">—</span><span class="uucg-fs__tier-pledge-period"><?php esc_html_e( '/ month', 'uucg-fair-share' ); ?></span></p>
						<p class="uucg-fs__tier-annual">$<span class="uucg-fs__tier-annual-value" data-field="annual">—</span><span class="uucg-fs__tier-annual-period"><?php esc_html_e( '/ year', 'uucg-fair-share' ); ?></span></p>
						<p class="uucg-fs__tier-desc"><?php echo esc_html( $tier['description'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>

			<footer class="uucg-fs__footer">
				<p class="uucg-fs__note"><?php esc_html_e( 'Whatever you are able to give, every pledge counts. Thank you for supporting UUCG.', 'uucg-fair-share' ); ?></p>
				<a class="uucg-fs__cta" href="<?php echo esc_url( home_url( '/pledging/' ) ); ?>#pledge"><?php esc_html_e( 'Ready to pledge', 'uucg-fair-share' ); ?> <span aria-hidden="true">→</span></a>
			</footer>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
