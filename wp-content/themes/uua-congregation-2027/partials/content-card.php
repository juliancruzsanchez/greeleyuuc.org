<?php
/**
 * Blog grid card for post listings.
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

<article <?php post_class( 'uucg-card' ); ?>>
	<a class="uucg-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php
			the_post_thumbnail(
				'medium_large',
				array(
					'class'   => 'uucg-card__img',
					'loading' => 'lazy',
					'alt'     => the_title_attribute( array( 'echo' => false ) ),
				)
			);
			?>
		<?php else : ?>
			<span class="uucg-card__placeholder" aria-hidden="true">
				<span class="uucg-card__placeholder-mark">UU</span>
			</span>
		<?php endif; ?>
	</a>

	<div class="uucg-card__body">
		<?php if ( $cat ) : ?>
			<a class="uucg-card__cat" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
				<?php echo esc_html( $cat->name ); ?>
			</a>
		<?php endif; ?>

		<h2 class="uucg-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<p class="uucg-card__excerpt">
			<?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?>
		</p>

		<div class="uucg-card__meta">
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
			<?php if ( $reading > 0 ) : ?>
				<span class="uucg-card__dot" aria-hidden="true"></span>
				<span>
					<?php
					printf(
						/* translators: %d: minutes */
						esc_html( _n( '%d min', '%d min', $reading, 'uucg-modern' ) ),
						(int) $reading
					);
					?>
				</span>
			<?php endif; ?>
		</div>
	</div>
</article>
