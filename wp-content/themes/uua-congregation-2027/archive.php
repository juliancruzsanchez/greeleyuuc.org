<?php
/**
 * Archive pages — category, tag, date, author.
 *
 * @package uucg-modern
 */

get_header();
?>

	<div id="primary" class="content-area uucg-blog-primary">
		<main id="main" class="main col-md-12 uucg-blog-main" role="main">

			<header class="uucg-blog-header">
				<p class="uucg-blog-header__kicker"><?php esc_html_e( 'Archive', 'uucg-modern' ); ?></p>
				<?php
				the_archive_title( '<h1 class="uucg-blog-header__title">', '</h1>' );
				the_archive_description( '<div class="uucg-blog-header__subtitle taxonomy-description">', '</div>' );
				?>
				<div class="uucg-blog-header__rule" aria-hidden="true"></div>
			</header>

			<?php if ( have_posts() ) : ?>
				<div class="uucg-blog-feed">
					<div class="uucg-blog-grid">
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'partials/content', 'card' );
						endwhile;
						?>
					</div>
				</div>

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
