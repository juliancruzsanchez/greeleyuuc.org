<?php
/**
 * Template Name: Home Page
 * Description: Modern, low-chrome home page. Follows the UUA demo site's information hierarchy (hero, mission, news/events, connect) but with a flat, typographic treatment — no nested cards.
 *
 * @package uucg-modern
 * @version 1.6.0
 */

get_header();
?>

<main id="main" class="main uucg-home" role="main">

	<?php
	// Service time lives in the customizer. Default to "10:00 AM" if unset.
	$uucg_service_time = get_theme_mod( 'uucg_home_service_time', __( '10:00 AM', 'uucg-modern' ) );
	?>

	<!-- HERO ============================================================== -->
	<section class="uucg-home-hero" aria-label="<?php esc_attr_e( 'Welcome to UUCG', 'uucg-modern' ); ?>">
		<div class="uucg-home-hero__inner">
			<p class="uucg-home-hero__kicker"><?php esc_html_e( 'Sundays', 'uucg-modern' ); ?> · <strong><?php echo esc_html( $uucg_service_time ); ?></strong></p>
			<h1 class="uucg-home-hero__title"><?php esc_html_e( 'Welcome to the Unitarian Universalist Church of Greeley', 'uucg-modern' ); ?></h1>
			<p class="uucg-home-hero__lede"><?php esc_html_e( 'A liberal, progressive faith community in the heart of Greeley.', 'uucg-modern' ); ?></p>
		</div>
	</section>

	<!-- VISIT US =========================================================== -->
	<?php
	$uucg_address   = get_theme_mod( 'uucg_contact_address', '929 15th Street, Greeley, CO 80631' );
	$uucg_phone     = get_theme_mod( 'uucg_contact_phone', '970-351-6751' );
	$uucg_email     = get_theme_mod( 'uucg_contact_email', 'office_manager@greeleyuuc.org' );
	$uucg_hours     = get_theme_mod( 'uucg_contact_hours', '' );
	$uucg_map_url   = get_theme_mod( 'uucg_contact_map_url', 'https://maps.google.com/?q=929+15th+Street,+Greeley,+CO+80631' );
	$uucg_has_contact = ( $uucg_address || $uucg_phone || $uucg_email || $uucg_hours );
	?>

	<?php if ( $uucg_has_contact ) : ?>
	<section class="uucg-home-visit" aria-label="<?php esc_attr_e( 'Visit us', 'uucg-modern' ); ?>">
		<div class="uucg-home-visit__inner">
			<?php if ( $uucg_address ) : ?>
				<p class="uucg-home-visit__row">
					<span class="uucg-home-visit__label"><?php esc_html_e( 'Address', 'uucg-modern' ); ?></span>
					<a class="uucg-home-visit__value" href="<?php echo esc_url( $uucg_map_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo nl2br( esc_html( $uucg_address ) ); ?></a>
				</p>
			<?php endif; ?>
			<?php if ( $uucg_hours ) : ?>
				<p class="uucg-home-visit__row">
					<span class="uucg-home-visit__label"><?php esc_html_e( 'Hours', 'uucg-modern' ); ?></span>
					<span class="uucg-home-visit__value"><?php echo nl2br( esc_html( $uucg_hours ) ); ?></span>
				</p>
			<?php endif; ?>
			<?php if ( $uucg_phone ) : ?>
				<p class="uucg-home-visit__row">
					<span class="uucg-home-visit__label"><?php esc_html_e( 'Phone', 'uucg-modern' ); ?></span>
					<a class="uucg-home-visit__value" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $uucg_phone ) ); ?>"><?php echo esc_html( $uucg_phone ); ?></a>
				</p>
			<?php endif; ?>
			<?php if ( $uucg_email ) : ?>
				<p class="uucg-home-visit__row">
					<span class="uucg-home-visit__label"><?php esc_html_e( 'Email', 'uucg-modern' ); ?></span>
					<a class="uucg-home-visit__value" href="mailto:<?php echo esc_attr( antispambot( $uucg_email ) ); ?>"><?php echo esc_html( antispambot( $uucg_email ) ); ?></a>
				</p>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<!-- MISSION CARDS ====================================================== -->
	<section class="uucg-home-mission" aria-label="<?php esc_attr_e( 'Our mission', 'uucg-modern' ); ?>">
		<div class="uucg-home-mission__inner">
			<article class="uucg-home-mission__item">
				<p class="uucg-home-mission__num">01</p>
				<h2 class="uucg-home-mission__title"><?php esc_html_e( 'Who we are', 'uucg-modern' ); ?></h2>
				<p class="uucg-home-mission__body"><?php esc_html_e( 'We are brave, curious, compassionate thinkers and doers. Diverse in faith, ethnicity, history, and spirituality, we build a community that changes lives.', 'uucg-modern' ); ?></p>
				<a class="uucg-home-mission__link" href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>"><?php esc_html_e( 'About UUCG', 'uucg-modern' ); ?> <span aria-hidden="true">→</span></a>
			</article>
			<article class="uucg-home-mission__item">
				<p class="uucg-home-mission__num">02</p>
				<h2 class="uucg-home-mission__title"><?php esc_html_e( 'What we do', 'uucg-modern' ); ?></h2>
				<p class="uucg-home-mission__body"><?php esc_html_e( 'Standing for love, justice, and peace since 1880, we gather for worship, programs, events, and more in the Greeley community and beyond.', 'uucg-modern' ); ?></p>
				<a class="uucg-home-mission__link" href="<?php echo esc_url( home_url( '/social-justice/' ) ); ?>"><?php esc_html_e( 'Our social justice work', 'uucg-modern' ); ?> <span aria-hidden="true">→</span></a>
			</article>
			<article class="uucg-home-mission__item">
				<p class="uucg-home-mission__num">03</p>
				<h2 class="uucg-home-mission__title"><?php esc_html_e( 'Get involved', 'uucg-modern' ); ?></h2>
				<p class="uucg-home-mission__body"><?php esc_html_e( 'Bring your passion and desire to see change. Be prepared to exercise your mind and open your heart. Together we can do the most good.', 'uucg-modern' ); ?></p>
				<a class="uucg-home-mission__link" href="<?php echo esc_url( home_url( '/get-involved/' ) ); ?>"><?php esc_html_e( 'Ways to help', 'uucg-modern' ); ?> <span aria-hidden="true">→</span></a>
			</article>
		</div>
	</section>

	<!-- SCHEDULE + WORSHIP ================================================ -->
	<section class="uucg-home-schedule" aria-label="<?php esc_attr_e( 'Worship schedule', 'uucg-modern' ); ?>">
		<div class="uucg-home-schedule__inner">
			<div class="uucg-home-schedule__copy">
				<figure class="uucg-home-schedule__media">
					<img
						src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/sanctuary.jpg' ); ?>"
						alt="<?php esc_attr_e( 'UUCG sanctuary and grounds', 'uucg-modern' ); ?>"
						loading="lazy"
					/>
				</figure>
				<p class="uucg-home-schedule__kicker"><?php esc_html_e( 'Sundays at the church', 'uucg-modern' ); ?></p>
				<h2 class="uucg-home-schedule__title"><?php esc_html_e( 'We gather in love and fellowship.', 'uucg-modern' ); ?></h2>
				<p class="uucg-home-schedule__body"><?php esc_html_e( 'Worship, foster spiritual growth, serve humanity, and understand ourselves and our universe. All are welcome at the table.', 'uucg-modern' ); ?></p>

				<ul class="uucg-home-schedule__expect" aria-label="<?php esc_attr_e( 'What to expect', 'uucg-modern' ); ?>">
					<li>
						<span class="uucg-home-schedule__expect-num">01</span>
						<span class="uucg-home-schedule__expect-text"><strong><?php esc_html_e( 'Music and chalice lighting.', 'uucg-modern' ); ?></strong> <?php esc_html_e( 'A blend of hymns, choir, and instrumental pieces to open the hour.', 'uucg-modern' ); ?></span>
					</li>
					<li>
						<span class="uucg-home-schedule__expect-num">02</span>
						<span class="uucg-home-schedule__expect-text"><strong><?php esc_html_e( 'A story for the week.', 'uucg-modern' ); ?></strong> <?php esc_html_e( 'A reflection, reading, or sermon rooted in our values and the wider world.', 'uucg-modern' ); ?></span>
					</li>
					<li>
						<span class="uucg-home-schedule__expect-num">03</span>
						<span class="uucg-home-schedule__expect-text"><strong><?php esc_html_e( 'Time together.', 'uucg-modern' ); ?></strong> <?php esc_html_e( 'Coffee, conversation, and connection after the service.', 'uucg-modern' ); ?></span>
					</li>
				</ul>

				<a class="uucg-home-schedule__cta" href="<?php echo esc_url( home_url( '/sunday-service/' ) ); ?>">
					<?php esc_html_e( 'Plan your visit', 'uucg-modern' ); ?>
					<span aria-hidden="true">→</span>
				</a>
			</div>
			<div class="uucg-home-schedule__widget">
				<?php echo do_shortcode( '[worship_schedule view="list" show_header="0" default_view="list" show_past="0" show_empty="1"]' ); ?>
			</div>
		</div>
	</section>

	<!-- NEWS + QUOTE ====================================================== -->
	<section class="uucg-home-news" aria-label="<?php esc_attr_e( 'News and reflections', 'uucg-modern' ); ?>">
		<div class="uucg-home-news__inner">
			<div class="uucg-home-news__col">
				<h2 class="uucg-home-news__heading"><?php esc_html_e( 'Recent news', 'uucg-modern' ); ?></h2>
				<?php
				$uucg_recent = new WP_Query(
					array(
						'post_type'           => 'post',
						'posts_per_page'      => 5,
						'ignore_sticky_posts' => 1,
						'no_found_rows'       => true,
					)
				);
				if ( $uucg_recent->have_posts() ) :
					echo '<ul class="uucg-home-news__list">';
					while ( $uucg_recent->have_posts() ) :
						$uucg_recent->the_post();
						echo '<li><a href="' . esc_url( get_permalink() ) . '">';
						echo '<span class="uucg-home-news__title">' . esc_html( get_the_title() ) . '</span>';
						echo '<span class="uucg-home-news__date">' . esc_html( get_the_date() ) . '</span>';
						echo '</a></li>';
					endwhile;
					echo '</ul>';
					wp_reset_postdata();
				else :
					echo '<p class="uucg-home-news__empty">' . esc_html__( 'No posts yet — check back soon.', 'uucg-modern' ) . '</p>';
				endif;
				?>
			</div>
			<aside class="uucg-home-news__col uucg-home-news__col--quote">
				<blockquote class="uucg-home-quote">
					<p class="uucg-home-quote__text"><?php esc_html_e( 'We are part of a liberal faith tradition of seekers and skeptics alike who proclaim that love is the spirit of this church.', 'uucg-modern' ); ?></p>
					<footer class="uucg-home-quote__cite">— <?php esc_html_e( 'UUCG mission', 'uucg-modern' ); ?></footer>
				</blockquote>
			</aside>
		</div>
	</section>

	<!-- NEWSLETTER ========================================================== -->
	<section class="uucg-home-newsletter" aria-label="<?php esc_attr_e( 'Newsletter signup', 'uucg-modern' ); ?>">
		<div class="uucg-home-newsletter__inner">
			<h2 class="uucg-home-newsletter__title"><?php esc_html_e( 'Subscribe to our newsletter', 'uucg-modern' ); ?></h2>
			<p class="uucg-home-newsletter__lede"><?php esc_html_e( 'Reflections, events, and community news in your inbox each week.', 'uucg-modern' ); ?></p>
			<div class="uucg-home-newsletter__form">
				<?php echo do_shortcode( '[uucg_newsletter style="minimal" show_title="0"]' ); ?>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();
