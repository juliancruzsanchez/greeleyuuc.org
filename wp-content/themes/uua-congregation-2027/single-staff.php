<?php
/**
 * Single staff member (CPT) — modern profile layout.
 *
 * @package uucg-modern
 */

get_header();
?>

	<div id="primary" class="content-area primary-content col-md-12">
		<main id="main" class="main" role="main">

		<?php
		while ( have_posts() ) :
			the_post();

			$role    = get_post_meta( get_the_ID(), 'staffer_staff_title', true );
			$email   = get_post_meta( get_the_ID(), 'staffer_staff_email', true );
			$phone   = get_post_meta( get_the_ID(), 'staffer_staff_phone', true );
			$website = get_post_meta( get_the_ID(), 'staffer_staff_website', true );
			$person  = array(
				'name'     => get_the_title(),
				'role'     => $role,
				'image_id' => get_post_thumbnail_id(),
			);
			?>

			<article <?php post_class( 'uucg-staff-profile' ); ?>>
				<header class="uucg-staff-profile__header">
					<?php
					if ( function_exists( 'uucg_staff_avatar_html' ) ) {
						echo uucg_staff_avatar_html( $person, 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} elseif ( has_post_thumbnail() ) {
						the_post_thumbnail( 'medium_large', array( 'class' => 'uucg-staff-card__photo' ) );
					}
					?>
					<div class="uucg-staff-profile__meta">
						<?php if ( $role ) : ?>
							<p class="uucg-staff-profile__role"><?php echo esc_html( $role ); ?></p>
						<?php endif; ?>
						<h1 class="uucg-staff-profile__title entry-title"><?php the_title(); ?></h1>
						<p class="uucg-staff-profile__contact">
							<?php if ( $phone ) : ?>
								<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
							<?php endif; ?>
							<?php if ( $phone && $email ) : ?>
								<span aria-hidden="true"> · </span>
							<?php endif; ?>
							<?php if ( $email ) : ?>
								<a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a>
							<?php endif; ?>
							<?php if ( $website ) : ?>
								<?php if ( $phone || $email ) : ?>
									<span aria-hidden="true"> · </span>
								<?php endif; ?>
								<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Website', 'uucg-modern' ); ?></a>
							<?php endif; ?>
						</p>
					</div>
				</header>

				<div class="uucg-staff-profile__content entry-content">
					<?php the_content(); ?>
				</div>
			</article>

		<?php endwhile; ?>

		</main>
	</div>

<?php
get_footer();
