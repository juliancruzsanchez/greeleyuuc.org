<?php
/**
 * Social media links — only networks with a configured URL.
 *
 * Supports Facebook, X, Instagram, TikTok, YouTube, Pinterest.
 *
 * @package uucg-modern
 */

defined( 'ABSPATH' ) || exit;

$networks = array(
	'facebook'  => array(
		'url'   => get_theme_mod( 'uuatheme_facebook_link' ),
		'label' => __( 'Facebook', 'uuatheme' ),
		'icon'  => 'facebook',
	),
	'x'         => array(
		// Prefer dedicated X field; fall back to legacy Twitter field.
		'url'   => get_theme_mod( 'uuatheme_x_link' ) ?: get_theme_mod( 'uuatheme_twitter_link' ),
		'label' => __( 'X', 'uuatheme' ),
		'icon'  => 'x',
	),
	'instagram' => array(
		'url'   => get_theme_mod( 'uuatheme_instagram_link' ),
		'label' => __( 'Instagram', 'uuatheme' ),
		'icon'  => 'instagram',
	),
	'tiktok'    => array(
		'url'   => get_theme_mod( 'uuatheme_tiktok_link' ),
		'label' => __( 'TikTok', 'uuatheme' ),
		'icon'  => 'tiktok',
	),
	'youtube'   => array(
		'url'   => get_theme_mod( 'uuatheme_youtube_link' ),
		'label' => __( 'YouTube', 'uuatheme' ),
		'icon'  => 'youtube',
	),
	'pinterest' => array(
		'url'   => get_theme_mod( 'uuatheme_pinterest_link' ),
		'label' => __( 'Pinterest', 'uuatheme' ),
		'icon'  => 'pinterest',
	),
);

$has_any = false;
foreach ( $networks as $net ) {
	if ( ! empty( $net['url'] ) ) {
		$has_any = true;
		break;
	}
}

if ( ! $has_any ) {
	return;
}

echo '<div class="social-media-links">';

foreach ( $networks as $key => $net ) {
	$url = is_string( $net['url'] ) ? trim( $net['url'] ) : '';
	if ( '' === $url ) {
		continue;
	}

	printf(
		'<a href="%1$s" class="social-link social-link--%2$s" target="_blank" rel="noopener noreferrer">%3$s<span class="sr-only">%4$s</span></a> ',
		esc_url( $url ),
		esc_attr( $key ),
		uucg_social_icon_svg( $net['icon'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		esc_html( $net['label'] )
	);
}

echo '</div>';
