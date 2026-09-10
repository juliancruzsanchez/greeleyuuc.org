<?php
/**
 * Page template — child theme override.
 *
 * Replaces the parent's left + right sidebar layout with a single full-width
 * column. Page content lives in the modern `uucg-modern` design layer; the
 * heavy parent chrome (Bootstrap 7/2/3 split, widget sidebars) is removed.
 *
 * @package uucg-modern
 * @version 1.5.1
 */

get_header(); ?>

<div id="primary" class="content-area uucg-page">
	<main id="main" class="main" role="main">

		<?php
		while ( have_posts() ) :
			the_post();
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'uucg-page-article' ); ?>>
				<header class="uucg-page-header">
					<?php if ( ! is_front_page() ) : ?>
						<h1 class="uucg-page-title"><?php the_title(); ?></h1>
					<?php endif; ?>
				</header>
				<div class="uucg-page-content">
					<?php the_content(); ?>
				</div>
			</article>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>

	</main>
</div>

<?php
get_footer();
