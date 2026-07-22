<?php
/**
 * Featured (hero) post on the first page of the blog.
 *
 * @package uucg-modern
 */

$reading = function_exists( 'uucg_modern_reading_time' ) ? uucg_modern_reading_time() : 0;
$cats    = get_the_category();
$cat     = null;
if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
	foreach ( $cats as $c ) {
		if ( 'uncategorized' !== $c->slug ) {
			$cat = $c;
			break;
		}
	}
}
?>

<article <?php post_class( 'uucg-featured' ); ?>>
	<a class="uucg-featured__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php
			the_post_thumbnail(
				'large',
				array(
					'class'   => 'uucg-featured__img',
					'loading' => 'eager',
					'alt'     => the_title_attribute( array( 'echo' => false ) ),
				)
			);
			?>
		<?php else : ?>
			<span class="uucg-featured__placeholder" aria-hidden="true"></span>
		<?php endif; ?>
	</a>

	<div class="uucg-featured__body">
		<p class="uucg-featured__kicker"><?php esc_html_e( 'Latest', 'uucg-modern' ); ?></p>

		<?php if ( $cat ) : ?>
			<a class="uucg-featured__cat" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
				<?php echo esc_html( $cat->name ); ?>
			</a>
		<?php endif; ?>

		<h2 class="uucg-featured__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<p class="uucg-featured__excerpt">
			<?php echo esc_html( wp_trim_words( get_the_excerpt(), 36, '…' ) ); ?>
		</p>

		<div class="uucg-featured__meta">
			<span class="uucg-featured__author"><?php the_author(); ?></span>
			<span class="uucg-card__dot" aria-hidden="true"></span>
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
			<?php if ( $reading > 0 ) : ?>
				<span class="uucg-card__dot" aria-hidden="true"></span>
				<span>
					<?php
					printf(
						esc_html( _n( '%d min read', '%d min read', $reading, 'uucg-modern' ) ),
						(int) $reading
					);
					?>
				</span>
			<?php endif; ?>
		</div>

		<a class="uucg-featured__cta" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'Read article', 'uucg-modern' ); ?>
			<span aria-hidden="true">→</span>
		</a>
	</div>
</article>
