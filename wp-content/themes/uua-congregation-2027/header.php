<?php
/**
 * Header — masthead hamburger + right slide-out drawer (mobile).
 *
 * @package uucg-modern
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div id="skip"><a href="#content"><?php _e( 'Skip to content', 'uuatheme' ); ?></a></div>

<?php get_template_part( 'partials/notice' ); ?>

<?php get_template_part( 'partials/slide', 'search' ); ?>

<?php get_template_part( 'partials/slide', 'location' ); ?>

<div class="row masthead-header">
	<div class="container">
		<div class="col-md-7 logo-area">
			<a class="navbar-brand" rel="home" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php
					$logo = get_theme_mod( 'uuatheme_logo_upload' );
					if ( $logo ) {
						if ( is_ssl() ) {
							$logo = str_replace( 'http://', 'https://', $logo );
						}
						echo '<img src="' . esc_url( $logo ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '">';
					} else {
						echo '<img src="' . esc_url( get_template_directory_uri() . '/assets/images/uuachalice_gradient.png' ) . '" alt="UUA Logo" class="default-logo">';
					}
				?>

				<div class="site-title" <?php echo ( $logo ) ? 'style="text-indent:-9999px"' : ''; ?>>
					<h1><?php bloginfo( 'name' ); ?></h1>
					<?php
						$description = get_bloginfo( 'description' );
						if ( ! empty( $description ) ) {
							echo '<span class="site-description">' . esc_html( $description ) . '</span>';
						}
					?>
				</div>
			</a>
		</div>
		<div class="col-md-5 header-right">

			<?php
			if ( has_nav_menu( 'utility_navigation' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'utility_navigation',
					'menu_class'     => 'nav nav-pills',
				) );
			}
			?>

			<div class="uucg-header-cta">
				<div class="header-text-field"><?php echo uuatheme_get_header_text(); ?></div>
				<div class="uucg-header-social uucg-social-desktop">
					<?php get_template_part( 'partials/social-media-icons' ); ?>
				</div>
			</div>

			<button
				type="button"
				class="uucg-drawer-toggle"
				aria-expanded="false"
				aria-controls="uucg-drawer"
				data-uucg-drawer-open
			>
				<span class="sr-only"><?php _e( 'Open menu', 'uuatheme' ); ?></span>
				<span class="uucg-hamburger" aria-hidden="true">
					<span class="uucg-hamburger-line"></span>
					<span class="uucg-hamburger-line"></span>
					<span class="uucg-hamburger-line"></span>
				</span>
			</button>
		</div>
	</div>
</div>

<?php
/**
 * Desktop horizontal nav (hidden on small screens).
 */
?>
<header class="banner navbar navbar-default navbar-static-top uucg-desktop-nav" role="banner">
	<div class="container">
		<nav class="navbar-collapse collapse in" role="navigation">
			<span class="sr-only"><?php _e( 'Main Navigation', 'uuatheme' ); ?></span>
			<?php
			if ( has_nav_menu( 'primary_navigation' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary_navigation',
					'menu_class'     => 'nav navbar-nav',
					'walker'         => new wp_bootstrap_navwalker(),
				) );
			}
			?>
		</nav>
	</div>
</header>

<?php
/**
 * Mobile drawer — sits on <body>, not inside a zero-height header.
 */
?>
<div class="uucg-drawer-root" id="uucg-drawer-root">
	<div class="uucg-drawer-backdrop" data-uucg-drawer-close tabindex="-1" aria-hidden="true"></div>

	<aside
		id="uucg-drawer"
		class="uucg-drawer-panel"
		role="dialog"
		aria-modal="true"
		aria-label="<?php esc_attr_e( 'Site menu', 'uuatheme' ); ?>"
		aria-hidden="true"
	>
		<div class="uucg-drawer-head">
			<span class="uucg-drawer-title"><?php _e( 'Menu', 'uuatheme' ); ?></span>
			<button
				type="button"
				class="uucg-drawer-close"
				data-uucg-drawer-close
				aria-label="<?php esc_attr_e( 'Close menu', 'uuatheme' ); ?>"
			>
				<span aria-hidden="true">&times;</span>
			</button>
		</div>

		<nav class="uucg-drawer-nav" role="navigation">
			<span class="sr-only"><?php _e( 'Main Navigation', 'uuatheme' ); ?></span>
			<?php
			if ( has_nav_menu( 'primary_navigation' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary_navigation',
					'menu_class'     => 'nav navbar-nav uucg-drawer-menu',
					'container'      => false,
					'walker'         => new wp_bootstrap_navwalker(),
				) );
			}
			?>
		</nav>

		<div class="uucg-drawer-social">
			<p class="uucg-drawer-social-label"><?php _e( 'Connect with us', 'uuatheme' ); ?></p>
			<?php get_template_part( 'partials/social-media-icons' ); ?>
		</div>
	</aside>
</div>

<div id="content" class="wrap" tabindex="0" role="document">
	<div class="container">
		<div class="content row">

		<?php
		// Hide path/breadcrumbs on the "No Title or Path" page template.
		$uucg_hide_breadcrumbs = is_page_template( 'templates/template-no-title.php' );

		if ( ! is_front_page() && ! $uucg_hide_breadcrumbs && function_exists( 'yoast_breadcrumb' ) ) {
			yoast_breadcrumb( '<div class="col-md-12"><p id="breadcrumbs">', '</p></div>' );
		}
		?>
