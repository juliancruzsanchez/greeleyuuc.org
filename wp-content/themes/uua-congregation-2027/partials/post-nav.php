<?php
/**
 * Previous / next post navigation for single posts.
 *
 * @package uucg-modern
 */

$prev = get_previous_post();
$next = get_next_post();

if ( ! $prev && ! $next ) {
	return;
}
?>

<nav class="uucg-post-nav" aria-label="<?php esc_attr_e( 'Post navigation', 'uucg-modern' ); ?>">
	<?php if ( $prev ) : ?>
		<a class="uucg-post-nav__card uucg-post-nav__card--prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
			<span class="uucg-post-nav__dir"><?php esc_html_e( 'Previous', 'uucg-modern' ); ?></span>
			<span class="uucg-post-nav__title"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
		</a>
	<?php else : ?>
		<span class="uucg-post-nav__card uucg-post-nav__card--empty" aria-hidden="true"></span>
	<?php endif; ?>

	<?php if ( $next ) : ?>
		<a class="uucg-post-nav__card uucg-post-nav__card--next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
			<span class="uucg-post-nav__dir"><?php esc_html_e( 'Next', 'uucg-modern' ); ?></span>
			<span class="uucg-post-nav__title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
		</a>
	<?php else : ?>
		<span class="uucg-post-nav__card uucg-post-nav__card--empty" aria-hidden="true"></span>
	<?php endif; ?>
</nav>
