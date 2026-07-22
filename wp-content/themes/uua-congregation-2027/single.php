<?php
/**
 * Single post template — modern full-width reading layout.
 *
 * @package uucg-modern
 */

get_header();
?>

	<div id="primary" class="content-area uucg-single-primary">
		<main id="main" class="main col-md-12 uucg-single-main" role="main">

			<?php
			while ( have_posts() ) :
				the_post();

				get_template_part( 'partials/content', 'single' );

				if ( is_singular( 'post' ) ) {
					get_template_part( 'partials/post', 'nav' );
				}

				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
			endwhile;
			?>

		</main>
	</div>

<?php
get_footer();
