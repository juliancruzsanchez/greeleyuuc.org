<?php
/**
 * Modern staff / leadership directory.
 *
 * Shortcode: [uucg_staff]
 * Renders featured ministers + board/contributors grid with
 * photo or initials-based head placeholders.
 *
 * @package uucg-modern
 */

defined( 'ABSPATH' ) || exit;

/**
 * Staff people + sections. Filterable for easy updates.
 *
 * @return array
 */
function uucg_staff_directory_data() {
	$data = array(
		'intro'    => __( 'Meet the people who guide worship, care for our community, and keep UUCG running with heart.', 'uucg-modern' ),
		'sections' => array(
			array(
				'id'     => 'ministers',
				'title'  => __( 'Ministers', 'uucg-modern' ),
				'layout' => 'featured',
				'people' => array(
					array(
						'name'     => 'Matthew Villarreal',
						'role'     => 'Minister',
						'image_id' => 241,
						'bio'      => array(
							'I am eager to serve as the quarter-time minister at the Unitarian Universalist Church of Greeley, as helping others on their spiritual journeys and doing important work in the move towards justice have always been deep passions of mine.',
							'I am a person of deep curiosity and hope to learn from you even as you learn from me. I was born and raised here in Greeley and want to do what I can to make this community a positive presence.',
							'After moving away from conservative dogmatic religion, I have grown to love Unitarian Universalism with its embrace of all religious traditions and openness to deep questions and evolving understandings of ourselves and the universe.',
							'Some of my special interests and hobbies include physics, philosophy, religion, spirituality, psychology, running, lifting, and spending time with friends and animal companions.',
							'I have a Bachelor of Arts in Religious Studies with minors in Communications and Philosophy from Doane University in Crete, Nebraska. Additionally, I received a Master of Divinity degree at Eden Theological Seminary in St. Louis, Missouri. I have done a full year of internship at the Unitarian Universalist Churches of Birmingham and Tuscaloosa and hope to continue applying the skills I have gained through my education and internship experiences during my time here at UUCG. Peace and love be with you all!',
						),
					),
					array(
						'name'     => 'Matt Ricke',
						'role'     => 'Community Minister',
						'image_id' => 94,
						'bio'      => array(
							'The UUCG Board has discerned to ordain and recognize Matt Ricke as a community minister in affiliation with the UUCG. Matt’s specific skills and training lie in conflict resolution, chaplaincy, spiritual companionship, and social justice.',
							'Outside of his work with UUCG, Matt is the owner and sole proprietor of Owlsong, LLC, a spiritually-driven, social justice-focused consultancy offering workshop facilitation, mediation, conflict coaching, and spiritual direction and consulting services to members of the community, faith-based organizations, and nonprofits.',
							'As an affiliated community minister, Matt will provide these services for members of the UUCG in kind while also serving the world at large, both as an independent spiritual care provider and representative of the UUCG.',
						),
					),
				),
			),
			array(
				'id'     => 'board',
				'title'  => __( 'Board of Trustees & Contributors', 'uucg-modern' ),
				'layout' => 'grid',
				'people' => array(
					array(
						'name'     => 'Kathy Vaughn',
						'role'     => 'President',
						'image_id' => 0,
					),
					array(
						'name'     => 'Marlene Terrazas',
						'role'     => 'Treasurer',
						'image_id' => 0,
					),
					array(
						'name'     => 'Rosemary Edmiston',
						'role'     => 'Member at Large',
						'image_id' => 0,
					),
					array(
						'name'     => 'Cody Oshkide',
						'role'     => 'Member at Large',
						'image_id' => 0,
					),
					array(
						'name'     => 'Joe Joyner',
						'role'     => 'Member at Large · AV',
						'image_id' => 242,
					),
					array(
						'name'     => 'Quinn Hill',
						'role'     => 'Web + Marketing Specialist',
						'image_id' => 0,
					),
					array(
						'name'     => 'Greg Thomson',
						'role'     => 'A/V Technician',
						'image_id' => 0,
					),
					array(
						'name'     => 'J.C. Sanchez',
						'role'     => 'Marketing',
						'image_id' => 239,
					),
				),
			),
		),
		'covenant' => array(
			'label'  => __( 'Our work together', 'uucg-modern' ),
			'quote'  => __( 'Love is the spirit of this church, and service is its law; this is our great covenant: to dwell together in peace, to seek the truth in love, and to help one another.', 'uucg-modern' ),
			'cite'   => 'James Vila Blake, 1894',
		),
	);

	/**
	 * Filter staff directory data.
	 *
	 * @param array $data Directory structure.
	 */
	return apply_filters( 'uucg_staff_directory_data', $data );
}

/**
 * Initials from a display name (e.g. "J.C. Sanchez" → "JS").
 *
 * @param string $name Full name.
 * @return string
 */
function uucg_staff_initials( $name ) {
	$name = trim( wp_strip_all_tags( (string) $name ) );
	if ( '' === $name ) {
		return '?';
	}

	// Normalize dotted initials like "J.C." into tokens.
	$name  = preg_replace( '/\./', ' ', $name );
	$parts = preg_split( '/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY );
	if ( empty( $parts ) ) {
		return '?';
	}

	if ( count( $parts ) === 1 ) {
		return strtoupper( mb_substr( $parts[0], 0, 2 ) );
	}

	$first = mb_substr( $parts[0], 0, 1 );
	$last  = mb_substr( $parts[ count( $parts ) - 1 ], 0, 1 );
	return strtoupper( $first . $last );
}

/**
 * Stable 0–5 tone index from a name (for placeholder color variety).
 *
 * @param string $name Name.
 * @return int
 */
function uucg_staff_tone_index( $name ) {
	return abs( (int) crc32( strtolower( trim( $name ) ) ) ) % 6;
}

/**
 * Soft person-head silhouette (decorative, aria-hidden parent).
 *
 * @return string
 */
function uucg_staff_head_svg() {
	return '<svg class="uucg-staff-card__head-icon" viewBox="0 0 64 64" width="64" height="64" focusable="false" aria-hidden="true"><circle cx="32" cy="24" r="12" fill="currentColor"/><path d="M10 58c2.5-14 12-22 22-22s19.5 8 22 22" fill="currentColor"/></svg>';
}

/**
 * Photo or placeholder head for a person.
 *
 * @param array  $person Person data.
 * @param string $size   Attachment size.
 * @return string
 */
function uucg_staff_avatar_html( $person, $size = 'medium' ) {
	$name     = isset( $person['name'] ) ? $person['name'] : '';
	$image_id = isset( $person['image_id'] ) ? (int) $person['image_id'] : 0;
	$tone     = uucg_staff_tone_index( $name );

	if ( $image_id > 0 && wp_attachment_is_image( $image_id ) ) {
		$img = wp_get_attachment_image(
			$image_id,
			$size,
			false,
			array(
				'class'   => 'uucg-staff-card__photo',
				'alt'     => $name,
				'loading' => 'lazy',
			)
		);
		if ( $img ) {
			return '<div class="uucg-staff-card__avatar uucg-staff-card__avatar--photo">' . $img . '</div>';
		}
	}

	$initials = uucg_staff_initials( $name );

	return sprintf(
		'<div class="uucg-staff-card__avatar uucg-staff-card__avatar--placeholder tone-%d" role="img" aria-label="%s">%s<span class="uucg-staff-card__initials">%s</span></div>',
		$tone,
		/* translators: %s: person name */
		esc_attr( sprintf( __( 'Portrait placeholder for %s', 'uucg-modern' ), $name ) ),
		uucg_staff_head_svg(),
		esc_html( $initials )
	);
}

/**
 * Render one person card.
 *
 * @param array  $person Person data.
 * @param string $layout featured|grid.
 * @return string
 */
function uucg_staff_render_card( $person, $layout = 'grid' ) {
	$name = isset( $person['name'] ) ? $person['name'] : '';
	$role = isset( $person['role'] ) ? $person['role'] : '';
	$bio  = isset( $person['bio'] ) ? $person['bio'] : array();
	if ( is_string( $bio ) && $bio !== '' ) {
		$bio = array( $bio );
	}
	if ( ! is_array( $bio ) ) {
		$bio = array();
	}

	$size  = ( 'featured' === $layout ) ? 'medium_large' : 'medium';
	$class = 'uucg-staff-card';
	if ( 'featured' === $layout ) {
		$class .= ' uucg-staff-card--featured';
	}

	ob_start();
	?>
	<article class="<?php echo esc_attr( $class ); ?>">
		<?php echo uucg_staff_avatar_html( $person, $size ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="uucg-staff-card__body">
			<?php if ( $role ) : ?>
				<p class="uucg-staff-card__role"><?php echo esc_html( $role ); ?></p>
			<?php endif; ?>
			<h3 class="uucg-staff-card__name"><?php echo esc_html( $name ); ?></h3>
			<?php if ( ! empty( $bio ) ) : ?>
				<div class="uucg-staff-card__bio">
					<?php foreach ( $bio as $para ) : ?>
						<p><?php echo esc_html( $para ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/**
 * Shortcode: modern staff directory.
 *
 * @param array $atts Attributes (unused reserved).
 * @return string
 */
function uucg_staff_shortcode( $atts = array() ) {
	unset( $atts );
	$data = uucg_staff_directory_data();

	ob_start();
	?>
	<div class="uucg-staff" data-uucg-staff>
		<?php if ( ! empty( $data['intro'] ) ) : ?>
			<p class="uucg-staff__intro"><?php echo esc_html( $data['intro'] ); ?></p>
		<?php endif; ?>

		<?php foreach ( (array) $data['sections'] as $section ) : ?>
			<?php
			$layout = isset( $section['layout'] ) ? $section['layout'] : 'grid';
			$people = isset( $section['people'] ) ? (array) $section['people'] : array();
			if ( empty( $people ) ) {
				continue;
			}
			$section_id = isset( $section['id'] ) ? sanitize_html_class( $section['id'] ) : '';
			?>
			<section class="uucg-staff__section uucg-staff__section--<?php echo esc_attr( $layout ); ?>" <?php echo $section_id ? 'id="staff-' . esc_attr( $section_id ) . '"' : ''; ?>>
				<header class="uucg-staff__section-header">
					<h2 class="uucg-staff__section-title"><?php echo esc_html( $section['title'] ); ?></h2>
					<div class="uucg-staff__section-rule" aria-hidden="true"></div>
				</header>

				<div class="uucg-staff__<?php echo 'featured' === $layout ? 'featured' : 'grid'; ?>">
					<?php foreach ( $people as $person ) : ?>
						<?php echo uucg_staff_render_card( $person, $layout ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>

		<?php if ( ! empty( $data['covenant']['quote'] ) ) : ?>
			<figure class="uucg-staff__covenant">
				<?php if ( ! empty( $data['covenant']['label'] ) ) : ?>
					<p class="uucg-staff__covenant-label"><?php echo esc_html( $data['covenant']['label'] ); ?></p>
				<?php endif; ?>
				<blockquote class="uucg-staff__covenant-quote">
					<p><?php echo esc_html( $data['covenant']['quote'] ); ?></p>
				</blockquote>
				<?php if ( ! empty( $data['covenant']['cite'] ) ) : ?>
					<figcaption class="uucg-staff__covenant-cite">— <?php echo esc_html( $data['covenant']['cite'] ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'uucg_staff', 'uucg_staff_shortcode' );

/**
 * Modern override for parent [uua_staffer] shortcode (CPT-based).
 * Falls back to original markup shape with modern classes + placeholders.
 *
 * @param array $atts Shortcode atts.
 * @return string
 */
function uucg_staffer_shortcode_modern( $atts ) {
	$atts = shortcode_atts(
		array(
			'order'      => 'ASC',
			'orderby'    => 'menu_order title',
			'number'     => -1,
			'department' => '',
		),
		$atts,
		'uua_staffer'
	);

	$tax_query = null;
	if ( ! empty( $atts['department'] ) ) {
		$tax_query = array(
			array(
				'taxonomy' => 'department',
				'field'    => 'slug',
				'terms'    => $atts['department'],
			),
		);
	}

	$query = new WP_Query(
		array(
			'post_type'      => 'staff',
			'order'          => $atts['order'],
			'orderby'        => $atts['orderby'],
			'posts_per_page' => (int) $atts['number'],
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);

	if ( ! $query->have_posts() ) {
		return '';
	}

	$stafferoptions = get_option( 'staffer' );
	$estyle         = is_array( $stafferoptions ) && ! empty( $stafferoptions['estyle'] ) ? $stafferoptions['estyle'] : 'excerpt';

	ob_start();
	?>
	<div class="uucg-staff uucg-staff--cpt">
		<div class="uucg-staff__grid">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$pid  = get_the_ID();
				$role = get_post_meta( $pid, 'staffer_staff_title', true );
				$person = array(
					'name'     => get_the_title(),
					'role'     => $role,
					'image_id' => get_post_thumbnail_id( $pid ),
				);
				?>
				<article <?php post_class( 'uucg-staff-card' ); ?>>
					<a class="uucg-staff-card__link" href="<?php the_permalink(); ?>">
						<?php echo uucg_staff_avatar_html( $person, 'medium' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<div class="uucg-staff-card__body">
							<?php if ( $role ) : ?>
								<p class="uucg-staff-card__role"><?php echo esc_html( $role ); ?></p>
							<?php endif; ?>
							<h3 class="uucg-staff-card__name"><?php the_title(); ?></h3>
						</div>
					</a>
					<?php if ( 'none' !== $estyle ) : ?>
						<div class="uucg-staff-card__bio">
							<?php
							if ( 'full' === $estyle ) {
								the_content();
							} else {
								the_excerpt();
							}
							?>
						</div>
					<?php endif; ?>
				</article>
			<?php endwhile; ?>
		</div>
	</div>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}

/**
 * Replace parent staffer shortcode after theme setup.
 */
function uucg_staff_override_parent_shortcode() {
	remove_shortcode( 'uua_staffer' );
	add_shortcode( 'uua_staffer', 'uucg_staffer_shortcode_modern' );
}
add_action( 'after_setup_theme', 'uucg_staff_override_parent_shortcode', 20 );
