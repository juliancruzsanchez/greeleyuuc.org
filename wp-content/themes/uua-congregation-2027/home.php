<?php
/**
 * Blog posts index (Posts page).
 * Modern magazine-style listing: featured hero + card grid.
 *
 * @package uucg-modern
 */

get_header();
?>

	<div id="primary" class="content-area uucg-blog-primary">
		<main id="main" class="main col-md-12 uucg-blog-main" role="main">

			<header class="uucg-blog-header">
				<?php if ( is_home() && ! is_front_page() ) : ?>
					<p class="uucg-blog-header__kicker"><?php esc_html_e( 'From the congregation', 'uucg-modern' ); ?></p>
					<h1 class="uucg-blog-header__title"><?php single_post_title(); ?></h1>
				<?php else : ?>
					<p class="uucg-blog-header__kicker"><?php esc_html_e( 'Stories & updates', 'uucg-modern' ); ?></p>
					<h1 class="uucg-blog-header__title"><?php esc_html_e( 'Blog', 'uucg-modern' ); ?></h1>
				<?php endif; ?>
				<p class="uucg-blog-header__subtitle">
					<?php esc_html_e( 'Sermons, reflections, and news from Unitarian Universalist Church of Greeley.', 'uucg-modern' ); ?>
				</p>
				<div class="uucg-blog-header__rule" aria-hidden="true"></div>
			</header>

			<?php if ( have_posts() ) : ?>
				<?php
				$uucg_i        = 0;
				$uucg_grid_open = false;
				?>
				<div class="uucg-blog-feed">
					<?php
					while ( have_posts() ) :
						the_post();
						$uucg_i++;

						// First post on page 1 = featured hero.
						if ( 1 === $uucg_i && ! is_paged() ) {
							get_template_part( 'partials/content', 'featured' );
							continue;
						}

						if ( ! $uucg_grid_open ) {
							echo '<div class="uucg-blog-grid">';
							$uucg_grid_open = true;
						}

						get_template_part( 'partials/content', 'card' );
					endwhile;

					if ( $uucg_grid_open ) {
						echo '</div><!-- .uucg-blog-grid -->';
					}
					?>
				</div><!-- .uucg-blog-feed -->

				<?php
				global $wp_query;
				if ( $wp_query->max_num_pages > 1 ) :
					?>
					<nav class="uucg-blog-pager" aria-label="<?php esc_attr_e( 'Posts navigation', 'uucg-modern' ); ?>">
						<div class="uucg-blog-pager__prev">
							<?php next_posts_link( __( '← Older posts', 'uucg-modern' ) ); ?>
						</div>
						<div class="uucg-blog-pager__next">
							<?php previous_posts_link( __( 'Newer posts →', 'uucg-modern' ) ); ?>
						</div>
					</nav>
				<?php endif; ?>

			<?php else : ?>
				<div class="uucg-blog-empty">
					<p><?php esc_html_e( 'Sorry, no posts were found.', 'uucg-modern' ); ?></p>
					<?php get_search_form(); ?>
				</div>
			<?php endif; ?>

		</main>
	</div>

<?php
get_footer();
