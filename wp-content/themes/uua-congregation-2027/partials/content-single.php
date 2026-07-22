<?php
/**
 * Modern single post content.
 *
 * @package uucg-modern
 */

$is_post       = ( 'post' === get_post_type() );
$is_testimonial = is_singular( 'testimonial' );
$reading_mins  = function_exists( 'uucg_modern_reading_time' ) ? uucg_modern_reading_time() : 0;
$categories    = $is_post ? get_the_category() : array();
$tags          = $is_post ? get_the_tags() : false;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'uucg-article' ); ?>>

	<header class="uucg-article__header">
		<h1 class="uucg-article__title entry-title"><?php the_title(); ?></h1>

		<?php
		// Meaningful categories only (skip default "Uncategorized").
		$show_cats = array();
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			foreach ( $categories as $cat ) {
				if ( 'uncategorized' === $cat->slug ) {
					continue;
				}
				$show_cats[] = $cat;
			}
		}
		?>
		<?php if ( ! empty( $show_cats ) ) : ?>
			<div class="uucg-article__cats" aria-label="<?php esc_attr_e( 'Categories', 'uucg-modern' ); ?>">
				<?php foreach ( $show_cats as $cat ) : ?>
					<a class="uucg-article__cat" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
						<?php echo esc_html( $cat->name ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( has_excerpt() && $is_post ) : ?>
			<p class="uucg-article__deck"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>

		<div class="uucg-article__meta entrymeta">
			<?php if ( $is_post || ! $is_testimonial ) : ?>
				<div class="uucg-article__meta-primary">
					<?php
					$author_id   = (int) get_the_author_meta( 'ID' );
					$author_name = get_the_author();
					$author_url  = get_author_posts_url( $author_id );
					if ( $author_name && $is_post ) :
						?>
						<a class="uucg-article__author" href="<?php echo esc_url( $author_url ); ?>">
							<?php echo get_avatar( $author_id, 48, '', $author_name, array( 'class' => 'uucg-article__avatar' ) ); ?>
							<span class="uucg-article__author-name"><?php echo esc_html( $author_name ); ?></span>
						</a>
					<?php endif; ?>

					<span class="uucg-article__meta-sep" aria-hidden="true"></span>

					<time class="uucg-article__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
						<?php echo esc_html( get_the_date() ); ?>
					</time>

					<?php if ( $reading_mins > 0 && $is_post ) : ?>
						<span class="uucg-article__meta-sep" aria-hidden="true"></span>
						<span class="uucg-article__read">
							<?php
							printf(
								/* translators: %d: estimated minutes to read */
								esc_html( _n( '%d min read', '%d min read', $reading_mins, 'uucg-modern' ) ),
								(int) $reading_mins
							);
							?>
						</span>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<?php get_template_part( 'partials/entry-meta' ); ?>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( has_post_thumbnail() && ! $is_testimonial ) : ?>
		<figure class="uucg-article__hero">
			<?php
			the_post_thumbnail(
				'large',
				array(
					'class'   => 'uucg-article__hero-img',
					'loading' => 'eager',
				)
			);
			$caption = get_the_post_thumbnail_caption();
			if ( $caption ) :
				?>
				<figcaption class="uucg-article__hero-caption"><?php echo esc_html( $caption ); ?></figcaption>
			<?php endif; ?>
		</figure>
	<?php elseif ( has_post_thumbnail() && $is_testimonial ) : ?>
		<?php the_post_thumbnail( 'medium', array( 'class' => 'alignright round uucg-article__thumb' ) ); ?>
	<?php endif; ?>

	<div class="uucg-article__body entry-content">
		<?php the_content(); ?>
	</div>

	<?php
	wp_link_pages(
		array(
			'before' => '<nav class="uucg-article__pages page-nav"><span class="uucg-article__pages-label">' . esc_html__( 'Pages:', 'uucg-modern' ) . '</span>',
			'after'  => '</nav>',
		)
	);
	?>

	<?php if ( $tags && ! is_wp_error( $tags ) ) : ?>
		<footer class="uucg-article__footer">
			<span class="uucg-article__tags-label"><?php esc_html_e( 'Tagged', 'uucg-modern' ); ?></span>
			<div class="uucg-article__tags">
				<?php foreach ( $tags as $tag ) : ?>
					<a class="uucg-article__tag" href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>">
						<?php echo esc_html( $tag->name ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</footer>
	<?php endif; ?>

	<?php if ( $is_post && get_the_author_meta( 'description' ) ) : ?>
		<aside class="uucg-article__byline-card" aria-label="<?php esc_attr_e( 'About the author', 'uucg-modern' ); ?>">
			<?php echo get_avatar( get_the_author_meta( 'ID' ), 72, '', get_the_author(), array( 'class' => 'uucg-article__byline-avatar' ) ); ?>
			<div class="uucg-article__byline-text">
				<p class="uucg-article__byline-kicker"><?php esc_html_e( 'Written by', 'uucg-modern' ); ?></p>
				<p class="uucg-article__byline-name">
					<a href="<?php echo esc_url( get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ); ?>">
						<?php the_author(); ?>
					</a>
				</p>
				<p class="uucg-article__byline-bio"><?php the_author_meta( 'description' ); ?></p>
			</div>
		</aside>
	<?php endif; ?>

</article>
