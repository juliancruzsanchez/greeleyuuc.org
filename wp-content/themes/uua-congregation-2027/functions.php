<?php
/**
 * UUCG Warm Modern — child theme functions.
 *
 * @package uucg-modern
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Child theme version for cache busting.
 */
function uucg_modern_version() {
	$theme = wp_get_theme();
	return $theme->get( 'Version' ) ? $theme->get( 'Version' ) : '1.0.0';
}

/**
 * Enqueue parent + modern assets.
 */
function uucg_modern_enqueue_assets() {
	$parent = wp_get_theme( 'uua-congregation' );
	$parent_ver = $parent->exists() ? $parent->get( 'Version' ) : '1.4.0';

	// Parent theme stylesheet (parent uses get_stylesheet_uri, so child must load it).
	wp_enqueue_style(
		'uuatheme-parent-style',
		get_template_directory_uri() . '/style.css',
		array( 'font-awesome' ),
		$parent_ver
	);

	// Warm modern typefaces.
	wp_enqueue_style(
		'uucg-modern-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Nunito:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap',
		array(),
		null
	);

	// Child base (theme header only).
	wp_enqueue_style(
		'uucg-modern-style',
		get_stylesheet_uri(),
		array( 'uuatheme-parent-style', 'uucg-modern-fonts' ),
		uucg_modern_version()
	);

	// Full 2027 design layer.
	wp_enqueue_style(
		'uucg-modern-css',
		get_stylesheet_directory_uri() . '/assets/css/modern.css',
		array( 'uucg-modern-style' ),
		uucg_modern_version()
	);

	// Subtle motion / glass interactions.
	wp_enqueue_script(
		'uucg-modern-js',
		get_stylesheet_directory_uri() . '/assets/js/modern.js',
		array(),
		uucg_modern_version(),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'uucg_modern_enqueue_assets', 20 );

/**
 * Add body class for modern theme hooks.
 *
 * @param array $classes Body classes.
 * @return array
 */
function uucg_modern_body_class( $classes ) {
	$classes[] = 'uucg-modern';

	if ( is_page_template( 'templates/template-no-title.php' ) ) {
		$classes[] = 'uucg-no-title-or-path';
	}

	if ( is_singular( 'post' ) ) {
		$classes[] = 'uucg-single-post';
	}

	if (
		is_home()
		|| is_category()
		|| is_tag()
		|| is_author()
		|| is_date()
		|| ( is_archive() && ! is_post_type_archive() )
		|| is_page_template( 'templates/template-blog.php' )
	) {
		$classes[] = 'uucg-blog-feed';
	}

	if ( is_page( 'staff' ) || is_page_template( 'templates/template-staff.php' ) ) {
		$classes[] = 'uucg-staff-page';
	}

	if ( is_singular( 'staff' ) ) {
		$classes[] = 'uucg-staff-single';
	}

	return $classes;
}
add_filter( 'body_class', 'uucg_modern_body_class' );

/**
 * Staff directory shortcode + modern [uua_staffer] override.
 */
require_once get_stylesheet_directory() . '/includes/staff-directory.php';

/**
 * SEO + AI discoverability (schema, llms.txt, robots, REST org endpoint).
 */
require_once get_stylesheet_directory() . '/includes/seo.php';

/**
 * Estimate reading time in minutes from post content.
 *
 * @param int|null $post_id Post ID.
 * @return int Minutes (minimum 1 when content exists).
 */
function uucg_modern_reading_time( $post_id = null ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return 0;
	}

	$text  = wp_strip_all_tags( $post->post_content );
	$words = str_word_count( $text );
	if ( $words < 1 ) {
		return 0;
	}

	// Average adult reading pace.
	$mins = (int) ceil( $words / 220 );
	return max( 1, $mins );
}

/**
 * Preconnect to Google Fonts for faster paint.
 *
 * @param array  $urls          URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed for.
 * @return array
 */
function uucg_modern_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href' => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
		$urls[] = 'https://fonts.googleapis.com';
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'uucg_modern_resource_hints', 10, 2 );

/**
 * Inline brand icons for social links (shared; safe to call many times).
 *
 * @param string $name Icon key.
 * @return string
 */
function uucg_social_icon_svg( $name ) {
	$icons = array(
		'facebook'  => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M22 12.07C22 6.51 17.52 2 12 2S2 6.51 2 12.07c0 5.02 3.66 9.18 8.44 9.93v-7.03H7.9v-2.9h2.54V9.85c0-2.52 1.49-3.91 3.77-3.91 1.09 0 2.24.2 2.24.2v2.48h-1.26c-1.24 0-1.63.78-1.63 1.57v1.88h2.78l-.44 2.9h-2.34V22c4.78-.75 8.44-4.91 8.44-9.93z"/></svg>',
		'x'         => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>',
		'instagram' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5M12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10m0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>',
		'tiktok'    => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15.2a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.73a8.19 8.19 0 0 0 4.76 1.52V6.84a4.84 4.84 0 0 1-1-.15z"/></svg>',
		'youtube'   => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2 31.5 31.5 0 0 0 0 12a31.5 31.5 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1A31.5 31.5 0 0 0 24 12a31.5 31.5 0 0 0-.5-5.8zM9.75 15.5v-7l6.5 3.5-6.5 3.5z"/></svg>',
		'pinterest' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12.02 2C6.63 2 3 5.83 3 10.6c0 3.04 1.7 4.8 2.74 4.8.43 0 .67-.1.77-.5.07-.26.3-1.2.4-1.56.1-.35.06-.47-.23-.78-.62-.74-1.02-1.7-1.02-3.06 0-3.12 2.33-5.91 6.07-5.91 3.31 0 5.14 2.02 5.14 4.72 0 3.55-1.57 6.55-3.9 6.55-1.29 0-2.25-1.06-1.94-2.37.37-1.56 1.08-3.24 1.08-4.37 0-1.01-.54-1.85-1.66-1.85-1.32 0-2.38 1.36-2.38 3.19 0 1.16.39 1.95.39 1.95l-1.58 6.69c-.47 1.99-.07 4.43-.04 4.67.02.14.2.18.28.07.12-.16 1.62-2 2.13-3.85.14-.53.82-3.2.82-3.2.4.77 1.58 1.45 2.83 1.45 3.72 0 6.25-3.39 6.25-7.93C20.04 5.5 16.62 2 12.02 2z"/></svg>',
	);

	return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

/**
 * Extra social Customizer fields (X + TikTok). Instagram already exists in parent.
 */
function uucg_modern_social_customizer_fields() {
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	Kirki::add_field( 'uuatheme_kirki_config', array(
		'type'        => 'text',
		'settings'    => 'uuatheme_x_link',
		'label'       => __( 'X (Twitter) Link', 'uuatheme' ),
		'description' => __( 'Full profile URL, e.g. https://x.com/yourchurch', 'uuatheme' ),
		'section'     => 'uuatheme_social_networks',
		'default'     => '',
		'priority'    => 16,
	) );

	Kirki::add_field( 'uuatheme_kirki_config', array(
		'type'        => 'text',
		'settings'    => 'uuatheme_tiktok_link',
		'label'       => __( 'TikTok Link', 'uuatheme' ),
		'description' => __( 'Full profile URL, e.g. https://www.tiktok.com/@yourchurch', 'uuatheme' ),
		'section'     => 'uuatheme_social_networks',
		'default'     => '',
		'priority'    => 32,
	) );
}
add_action( 'init', 'uucg_modern_social_customizer_fields', 20 );

/**
 * Enqueue pledge calculator assets on the pledging page (and when shortcode is used).
 */
function uucg_pledge_calculator_enqueue() {
	if ( ! is_singular() ) {
		return;
	}

	$post = get_post();
	if ( ! $post ) {
		return;
	}

	$needs = is_page( 'pledging' ) || has_shortcode( $post->post_content, 'uucg_pledge_calculator' );
	if ( ! $needs ) {
		return;
	}

	wp_enqueue_style(
		'uucg-pledge-calculator',
		get_stylesheet_directory_uri() . '/assets/css/pledge-calculator.css',
		array( 'uucg-modern-css' ),
		uucg_modern_version()
	);
	wp_enqueue_script(
		'uucg-pledge-calculator',
		get_stylesheet_directory_uri() . '/assets/js/pledge-calculator.js',
		array(),
		uucg_modern_version(),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'uucg_pledge_calculator_enqueue', 30 );

/**
 * Pledge calculator shortcode: [uucg_pledge_calculator]
 */
function uucg_pledge_calculator_shortcode() {
	// Ensure assets load even if page slug differs but shortcode is present.
	wp_enqueue_style( 'uucg-pledge-calculator' );
	wp_enqueue_script( 'uucg-pledge-calculator' );

	ob_start();
	?>
	<div class="uucg-pledge" data-uucg-pledge-calculator>
		<div class="uucg-pledge__intro">
			<h2>Pledge Calculator</h2>
			<p>
				Enter your adjusted household income to see suggested monthly pledges
				across our giving levels. Values follow our progressive giving guide.
			</p>
		</div>

		<div class="uucg-pledge__panel">
			<div class="uucg-pledge__controls">
				<div class="uucg-pledge__field">
					<label for="uucg-pledge-income">Your adjusted income</label>
					<div class="uucg-pledge__input-wrap">
						<span class="uucg-pledge__currency" aria-hidden="true">$</span>
						<input
							id="uucg-pledge-income"
							class="uucg-pledge__input"
							type="text"
							inputmode="decimal"
							autocomplete="off"
							placeholder="4,000"
							data-pledge-income
							value="4000"
						>
					</div>
				</div>

				<div class="uucg-pledge__field">
					<label>Income period</label>
					<div class="uucg-pledge__modes" role="radiogroup" aria-label="Income period">
						<label class="uucg-pledge__mode">
							<input type="radio" name="uucg-pledge-mode" value="monthly" data-pledge-mode checked>
							<span>Monthly</span>
						</label>
						<label class="uucg-pledge__mode">
							<input type="radio" name="uucg-pledge-mode" value="annual" data-pledge-mode>
							<span>Annual</span>
						</label>
					</div>
				</div>

				<div class="uucg-pledge__slider-row">
					<label for="uucg-pledge-slider">
						<span data-pledge-slider-label>Quick adjust (monthly)</span>
						<span data-pledge-slider-range>$1,000 – $12,000</span>
					</label>
					<input
						id="uucg-pledge-slider"
						class="uucg-pledge__slider"
						type="range"
						min="1000"
						max="12000"
						step="100"
						value="4000"
						data-pledge-slider
						style="--pc-slider-pct: 27.27%"
					>
				</div>
			</div>

			<div class="uucg-pledge__summary">
				<span>Adjusted monthly: <strong data-pledge-monthly-out>—</strong></span>
				<span>Approx. annual: <strong data-pledge-annual-out>—</strong></span>
			</div>
		</div>

		<p class="uucg-pledge__empty" data-pledge-empty hidden>
			Enter an income amount to see suggested pledges.
		</p>

		<div class="uucg-pledge__results" data-pledge-results>
			<article class="uucg-pledge__card uucg-pledge__card--gold" data-tier="s">
				<h3 class="uucg-pledge__card-name">Supporter</h3>
				<p class="uucg-pledge__card-range">2–6% of income</p>
				<p class="uucg-pledge__card-blurb">A meaningful starting point that sustains core ministry.</p>
				<div class="uucg-pledge__card-pct">Suggested <span data-tier-pct>—</span></div>
				<p class="uucg-pledge__card-amount">
					<span>Monthly pledge</span>
					<span data-tier-monthly>—</span>
				</p>
				<p class="uucg-pledge__card-annual" data-tier-annual>—</p>
			</article>

			<article class="uucg-pledge__card uucg-pledge__card--blue" data-tier="u">
				<h3 class="uucg-pledge__card-name">Sustainer</h3>
				<p class="uucg-pledge__card-range">3–7% of income</p>
				<p class="uucg-pledge__card-blurb">Helps the congregation thrive year-round.</p>
				<div class="uucg-pledge__card-pct">Suggested <span data-tier-pct>—</span></div>
				<p class="uucg-pledge__card-amount">
					<span>Monthly pledge</span>
					<span data-tier-monthly>—</span>
				</p>
				<p class="uucg-pledge__card-annual" data-tier-annual>—</p>
			</article>

			<article class="uucg-pledge__card uucg-pledge__card--green" data-tier="v">
				<h3 class="uucg-pledge__card-name">Visionary</h3>
				<p class="uucg-pledge__card-range">5–9% of income</p>
				<p class="uucg-pledge__card-blurb">Fuels growth, justice work, and new possibilities.</p>
				<div class="uucg-pledge__card-pct">Suggested <span data-tier-pct>—</span></div>
				<p class="uucg-pledge__card-amount">
					<span>Monthly pledge</span>
					<span data-tier-monthly>—</span>
				</p>
				<p class="uucg-pledge__card-annual" data-tier-annual>—</p>
			</article>

			<article class="uucg-pledge__card uucg-pledge__card--rose" data-tier="t">
				<h3 class="uucg-pledge__card-name">Transformer</h3>
				<p class="uucg-pledge__card-range">10% of income</p>
				<p class="uucg-pledge__card-blurb">A bold, transformative commitment to our shared mission.</p>
				<div class="uucg-pledge__card-pct">Suggested <span data-tier-pct>—</span></div>
				<p class="uucg-pledge__card-amount">
					<span>Monthly pledge</span>
					<span data-tier-monthly>—</span>
				</p>
				<p class="uucg-pledge__card-annual" data-tier-annual>—</p>
			</article>
		</div>

		<div class="uucg-pledge__table-wrap">
			<div class="uucg-pledge__table-caption">Giving guide reference</div>
			<p class="uucg-pledge__table-note">
				Suggested percentages rise with capacity — the same progressive path used by the calculator.
			</p>
			<table class="uucg-pledge__table">
				<thead>
					<tr>
						<th scope="col">Adjusted monthly income</th>
						<th scope="col">Approx. adjusted annual income</th>
						<th scope="col" class="tier-supporter" colspan="2">Supporter<br><small>2–6% of income</small></th>
						<th scope="col" class="tier-sustainer" colspan="2">Sustainer<br><small>3–7% of income</small></th>
						<th scope="col" class="tier-visionary" colspan="2">Visionary<br><small>5–9% of income</small></th>
						<th scope="col" class="tier-transformer" colspan="2">Transformer<br><small>10% of income</small></th>
					</tr>
					<tr>
						<th></th>
						<th></th>
						<th class="tier-supporter">Suggested %</th>
						<th class="tier-supporter">Monthly pledge</th>
						<th class="tier-sustainer">Suggested %</th>
						<th class="tier-sustainer">Monthly pledge</th>
						<th class="tier-visionary">Suggested %</th>
						<th class="tier-visionary">Monthly pledge</th>
						<th class="tier-transformer">Suggested %</th>
						<th class="tier-transformer">Monthly pledge</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$rows = array(
						array( 1000, 12000, 2, 20, 3, 30, 5, 50, 10, 100 ),
						array( 1500, 18000, 2, 30, 3, 45, 5, 75, 10, 150 ),
						array( 2000, 25000, 2, 40, 3, 60, 5, 100, 10, 200 ),
						array( 3000, 36000, 2, 60, 3, 90, 5, 150, 10, 300 ),
						array( 4000, 50000, 3, 120, 4, 160, 5, 200, 10, 400 ),
						array( 6500, 80000, 3, 195, 4, 260, 6, 390, 10, 650 ),
						array( 8500, 100000, 3, 255, 5, 425, 6, 510, 10, 850 ),
						array( 10000, 120000, 3, 300, 5, 500, 6, 600, 10, 1000 ),
						array( 12500, 150000, 4, 500, 5, 625, 6, 750, 10, 1250 ),
						array( 17000, 200000, 4, 680, 6, 1020, 7, 1190, 10, 1700 ),
						array( 25000, 300000, 5, 1250, 6, 1500, 8, 2000, 10, 2500 ),
						array( 40000, 500000, 6, 2400, 7, 2800, 9, 3600, 10, 4000 ),
					);
					foreach ( $rows as $r ) :
						?>
						<tr>
							<th scope="row">$<?php echo esc_html( number_format( $r[0] ) ); ?></th>
							<td>$<?php echo esc_html( number_format( $r[1] ) ); ?></td>
							<td class="tier-supporter"><?php echo (int) $r[2]; ?>%</td>
							<td class="tier-supporter">$<?php echo esc_html( number_format( $r[3] ) ); ?></td>
							<td class="tier-sustainer"><?php echo (int) $r[4]; ?>%</td>
							<td class="tier-sustainer">$<?php echo esc_html( number_format( $r[5] ) ); ?></td>
							<td class="tier-visionary"><?php echo (int) $r[6]; ?>%</td>
							<td class="tier-visionary">$<?php echo esc_html( number_format( $r[7] ) ); ?></td>
							<td class="tier-transformer"><?php echo (int) $r[8]; ?>%</td>
							<td class="tier-transformer">$<?php echo esc_html( number_format( $r[9] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<p class="uucg-pledge__footnote">
			These figures are guides, not requirements. Give what is joyful and sustainable for your household.
			“Adjusted income” usually means after taxes and essential needs — use what feels honest for your situation.
		</p>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'uucg_pledge_calculator', 'uucg_pledge_calculator_shortcode' );

/**
 * Allow the pledge calculator shortcode inside block content / classic content.
 */
add_filter( 'widget_text', 'do_shortcode' );

/**
 * Contact details in the Customizer (footer + site-wide).
 */
function uucg_modern_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'uucg_contact',
		array(
			'title'       => __( 'Contact Information', 'uucg-modern' ),
			'description' => __( 'Shown in the site footer. Address can also use the parent “Congregation Address for Maps” setting.', 'uucg-modern' ),
			'priority'    => 35,
		)
	);

	$wp_customize->add_setting(
		'uucg_contact_phone',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'uucg_contact_phone',
		array(
			'label'   => __( 'Phone', 'uucg-modern' ),
			'section' => 'uucg_contact',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'uucg_contact_email',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_email',
		)
	);
	$wp_customize->add_control(
		'uucg_contact_email',
		array(
			'label'   => __( 'Email', 'uucg-modern' ),
			'section' => 'uucg_contact',
			'type'    => 'email',
		)
	);

	$wp_customize->add_setting(
		'uucg_contact_hours',
		array(
			'default'           => "Sunday services 10:00 AM\nOffice hours by appointment",
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control(
		'uucg_contact_hours',
		array(
			'label'       => __( 'Hours / when to visit', 'uucg-modern' ),
			'section'     => 'uucg_contact',
			'type'        => 'textarea',
			'description' => __( 'One line per item is fine.', 'uucg-modern' ),
		)
	);

	$wp_customize->add_setting(
		'uucg_contact_map_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'uucg_contact_map_url',
		array(
			'label'       => __( 'Map link (optional)', 'uucg-modern' ),
			'section'     => 'uucg_contact',
			'type'        => 'url',
			'description' => __( 'Leave blank to auto-link the address in Google Maps.', 'uucg-modern' ),
		)
	);
}
add_action( 'customize_register', 'uucg_modern_customize_register' );
