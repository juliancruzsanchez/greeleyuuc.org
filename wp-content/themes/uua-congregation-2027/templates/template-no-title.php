<?php
/**
 * Template Name: No Title or Path
 * Description: Full-width page with no page title heading and no breadcrumb path. Ideal for In the Loop, worship schedule, and other shortcode-driven pages that supply their own heading.
 *
 * @package uucg-modern
 * @version 1.3.4
 */

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="main col-md-12" role="main">

			<?php
			while ( have_posts() ) :
				the_post();
				?>

				<article id="post-<?php the_ID(); ?>" <?php post_class( 'uucg-no-title-page' ); ?>>
					<?php
					// No page-header / the_title() — content supplies its own heading.
					// Breadcrumbs are suppressed in header.php for this template.
					the_content();

					wp_link_pages(
						array(
							'before' => '<nav class="pagination">',
							'after'  => '</nav>',
						)
					);
					?>
				</article>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
			endwhile;
			?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
