<?php
/**
 * Template part for page content without a title heading.
 *
 * @package uucg-modern
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'uucg-no-title-page' ); ?>>
	<?php the_content(); ?>

	<?php wp_link_pages( array( 'before' => '<nav class="pagination">', 'after' => '</nav>' ) ); ?>
</article>
