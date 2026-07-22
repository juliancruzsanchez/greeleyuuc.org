<?php
/**
 * Template Name: Modern Blog Feed
 * Description: Magazine-style blog listing (featured post + card grid). Use this if the page is not set as the Posts page under Settings → Reading.
 *
 * @package uucg-modern
 */

get_header();

$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$blog_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => (int) get_option( 'posts_per_page', 10 ),
		'paged'               => $paged,
		'ignore_sticky_posts' => false,
	)
);
?>

	<div id="primary" class="content-area uucg-blog-primary">
		<main id="main" class="main col-md-12 uucg-blog-main" role="main">

			<header class="uucg-blog-header">
				<p class="uucg-blog-header__kicker"><?php esc_html_e( 'From the congregation', 'uucg-modern' ); ?></p>
				<h1 class="uucg-blog-header__title"><?php echo esc_html( get_the_title() ? get_the_title() : __( 'Blog', 'uucg-modern' ) ); ?></h1>
				<p class="uucg-blog-header__subtitle">
					<?php esc_html_e( 'Sermons, reflections, and news from Unitarian Universalist Church of Greeley.', 'uucg-modern' ); ?>
				</p>
				<div class="uucg-blog-header__rule" aria-hidden="true"></div>
			</header>

			<?php if ( $blog_query->have_posts() ) : ?>
				<?php
				$uucg_i         = 0;
				$uucg_grid_open = false;
				?>
				<div class="uucg-blog-feed">
					<?php
					while ( $blog_query->have_posts() ) :
						$blog_query->the_post();
						$uucg_i++;

						if ( 1 === $uucg_i && $paged < 2 ) {
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
						echo '</div>';
					}
					?>
				</div>

				<?php if ( $blog_query->max_num_pages > 1 ) : ?>
					<nav class="uucg-blog-pager" aria-label="<?php esc_attr_e( 'Posts navigation', 'uucg-modern' ); ?>">
						<div class="uucg-blog-pager__prev">
							<?php
							echo wp_kses_post(
								get_next_posts_link( __( '← Older posts', 'uucg-modern' ), $blog_query->max_num_pages )
							);
							?>
						</div>
						<div class="uucg-blog-pager__next">
							<?php
							echo wp_kses_post(
								get_previous_posts_link( __( 'Newer posts →', 'uucg-modern' ) )
							);
							?>
						</div>
					</nav>
				<?php endif; ?>

				<?php wp_reset_postdata(); ?>

			<?php else : ?>
				<div class="uucg-blog-empty">
					<p><?php esc_html_e( 'Sorry, no posts were found.', 'uucg-modern' ); ?></p>
				</div>
			<?php endif; ?>

		</main>
	</div>

<?php
get_footer();
