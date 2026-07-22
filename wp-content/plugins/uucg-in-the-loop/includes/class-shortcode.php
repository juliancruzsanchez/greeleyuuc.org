<?php
/**
 * Frontend shortcode renderer.
 *
 * @package UUCG_In_The_Loop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders [in_the_loop] / [uucg_in_the_loop].
 */
class UUCG_ITL_Shortcode {

	/**
	 * Hook shortcodes.
	 */
	public static function init() {
		add_shortcode( 'in_the_loop', array( __CLASS__, 'render' ) );
		add_shortcode( 'uucg_in_the_loop', array( __CLASS__, 'render' ) );
	}

	/**
	 * Available tabs definition.
	 *
	 * @return array<string,array{label:string,icon:string}>
	 */
	public static function tabs() {
		return array(
			'tiktok'    => array(
				'label' => __( 'TikTok', 'uucg-in-the-loop' ),
				'icon'  => 'tiktok',
			),
			'instagram' => array(
				'label' => __( 'Instagram', 'uucg-in-the-loop' ),
				'icon'  => 'instagram',
			),
			'facebook'  => array(
				'label' => __( 'Facebook', 'uucg-in-the-loop' ),
				'icon'  => 'facebook',
			),
			'x'         => array(
				'label' => __( 'X', 'uucg-in-the-loop' ),
				'icon'  => 'x',
			),
		);
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$settings = UUCG_In_The_Loop::get_settings();

		$atts = shortcode_atts(
			array(
				'title'       => $settings['title'],
				'subtitle'    => $settings['subtitle'],
				'default_tab' => $settings['default_tab'],
				'columns'     => $settings['columns'],
				'show_header' => $settings['show_header'],
			),
			$atts,
			'in_the_loop'
		);

		$default_tab = sanitize_key( $atts['default_tab'] );
		$all_tabs    = self::tabs();
		$columns     = max( 1, min( 4, absint( $atts['columns'] ) ) );
		$uid         = 'uucg-itl-' . wp_unique_id();

		// Build panel content and keep only linked networks.
		$panels = array();
		$tabs   = array();
		foreach ( $all_tabs as $key => $tab ) {
			if ( ! UUCG_ITL_Embeds::platform_is_linked( $key, $settings ) ) {
				continue;
			}
			$tabs[ $key ]   = $tab;
			$panels[ $key ] = UUCG_ITL_Embeds::render_platform_feed( $key, $settings );
		}

		// Nothing configured yet.
		if ( empty( $tabs ) ) {
			return '<div class="uucg-itl uucg-itl--empty"><p class="uucg-itl__empty-text">' .
				esc_html__( 'Connect TikTok, Instagram, Facebook, or X in Settings → In the Loop to show feeds here.', 'uucg-in-the-loop' ) .
				'</p></div>';
		}

		if ( ! isset( $tabs[ $default_tab ] ) ) {
			$tab_keys    = array_keys( $tabs );
			$default_tab = $tab_keys[0];
		}

		// Prefer a tab that already has embed content when possible.
		if ( empty( $panels[ $default_tab ] ) ) {
			foreach ( $panels as $key => $content ) {
				if ( ! empty( $content ) ) {
					$default_tab = $key;
					break;
				}
			}
		}

		wp_enqueue_style( 'uucg-itl-frontend' );
		wp_enqueue_script( 'uucg-itl-frontend' );

		wp_localize_script(
			'uucg-itl-frontend',
			'uucgItl',
			array(
				'hasTiktok'    => ! empty( $panels['tiktok'] ),
				'hasInstagram' => ! empty( $panels['instagram'] ),
				'hasFacebook'  => ! empty( $panels['facebook'] ),
				'hasX'         => ! empty( $panels['x'] ),
			)
		);

		ob_start();
		?>
		<section
			class="uucg-itl"
			id="<?php echo esc_attr( $uid ); ?>"
			data-default-tab="<?php echo esc_attr( $default_tab ); ?>"
			data-columns="<?php echo esc_attr( (string) $columns ); ?>"
			aria-label="<?php echo esc_attr( $atts['title'] ? $atts['title'] : __( 'Social feed', 'uucg-in-the-loop' ) ); ?>"
		>
			<?php if ( ! empty( $atts['show_header'] ) && ( $atts['title'] || $atts['subtitle'] ) ) : ?>
				<header class="uucg-itl__header">
					<?php if ( $atts['title'] ) : ?>
						<h2 class="uucg-itl__title"><?php echo esc_html( $atts['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( $atts['subtitle'] ) : ?>
						<p class="uucg-itl__subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
					<?php endif; ?>
					<div class="uucg-itl__rainbow" aria-hidden="true"></div>
				</header>
			<?php endif; ?>

			<div class="uucg-itl__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Social platforms', 'uucg-in-the-loop' ); ?>">
				<?php foreach ( $tabs as $key => $tab ) : ?>
					<?php
					$is_active = ( $key === $default_tab );
					$tab_id    = $uid . '-tab-' . $key;
					$panel_id  = $uid . '-panel-' . $key;
					$is_empty  = empty( $panels[ $key ] );
					?>
					<button
						type="button"
						class="uucg-itl__tab uucg-itl__tab--<?php echo esc_attr( $key ); ?><?php echo $is_active ? ' is-active' : ''; ?><?php echo $is_empty ? ' is-empty' : ''; ?>"
						role="tab"
						id="<?php echo esc_attr( $tab_id ); ?>"
						aria-controls="<?php echo esc_attr( $panel_id ); ?>"
						aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
						data-tab="<?php echo esc_attr( $key ); ?>"
						tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
					>
						<span class="uucg-itl__tab-icon" aria-hidden="true">
							<?php echo self::icon_svg( $tab['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
						<span class="uucg-itl__tab-label"><?php echo esc_html( $tab['label'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="uucg-itl__panels">
				<?php foreach ( $tabs as $key => $tab ) : ?>
					<?php
					$is_active  = ( $key === $default_tab );
					$tab_id     = $uid . '-tab-' . $key;
					$panel_id   = $uid . '-panel-' . $key;
					$profile    = UUCG_ITL_Embeds::profile_link( $key, $settings );
					$feed_html  = $panels[ $key ];
					$grid_class = 'uucg-itl__grid uucg-itl__grid--cols-' . $columns;
					if ( 'facebook' === $key && ! empty( $settings['facebook_use_page_plugin'] ) && empty( self::has_extra_urls( $settings['facebook_urls'] ?? '' ) ) ) {
						$grid_class .= ' uucg-itl__grid--single';
					}
					if ( 'x' === $key && ! empty( $settings['x_use_timeline'] ) && empty( self::has_extra_urls( $settings['x_urls'] ?? '' ) ) ) {
						$grid_class .= ' uucg-itl__grid--single';
					}
					?>
					<div
						class="uucg-itl__panel uucg-itl__panel--<?php echo esc_attr( $key ); ?><?php echo $is_active ? ' is-active' : ''; ?>"
						role="tabpanel"
						id="<?php echo esc_attr( $panel_id ); ?>"
						aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
						data-platform="<?php echo esc_attr( $key ); ?>"
						<?php echo $is_active ? '' : 'hidden'; ?>
					>
						<?php if ( $profile ) : ?>
							<div class="uucg-itl__panel-toolbar">
								<a class="uucg-itl__follow" href="<?php echo esc_url( $profile['url'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( $profile['label'] ); ?>
									<span class="uucg-itl__follow-arrow" aria-hidden="true">→</span>
								</a>
							</div>
						<?php endif; ?>

						<?php if ( $feed_html ) : ?>
							<div class="<?php echo esc_attr( $grid_class ); ?>">
								<?php echo $feed_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped helpers. ?>
							</div>
						<?php else : ?>
							<div class="uucg-itl__empty">
								<div class="uucg-itl__empty-icon" aria-hidden="true">
									<?php echo self::icon_svg( $tab['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<p class="uucg-itl__empty-title">
									<?php
									/* translators: %s: platform name */
									echo esc_html( sprintf( __( 'No %s posts yet', 'uucg-in-the-loop' ), $tab['label'] ) );
									?>
								</p>
								<p class="uucg-itl__empty-text">
									<?php esc_html_e( 'Add post or video URLs in Settings → In the Loop.', 'uucg-in-the-loop' ); ?>
								</p>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Official embed SDKs loaded on demand by frontend.js -->
			<div class="uucg-itl__sdk-roots" aria-hidden="true">
				<div id="fb-root"></div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Whether a URL list has any entries.
	 *
	 * @param string $raw URL textarea.
	 * @return bool
	 */
	private static function has_extra_urls( $raw ) {
		return ! empty( UUCG_ITL_Embeds::parse_urls( $raw ) );
	}

	/**
	 * Inline SVG icons for tabs (no external icon font required).
	 *
	 * @param string $name Icon key.
	 * @return string
	 */
	public static function icon_svg( $name ) {
		$icons = array(
			'tiktok'    => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" focusable="false"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15.2a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.73a8.19 8.19 0 0 0 4.76 1.52V6.84a4.84 4.84 0 0 1-1-.15z"/></svg>',
			'instagram' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" focusable="false"><path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5M12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10m0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>',
			'facebook'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" focusable="false"><path d="M22 12.07C22 6.51 17.52 2 12 2S2 6.51 2 12.07c0 5.02 3.66 9.18 8.44 9.93v-7.03H7.9v-2.9h2.54V9.85c0-2.52 1.49-3.91 3.77-3.91 1.09 0 2.24.2 2.24.2v2.48h-1.26c-1.24 0-1.63.78-1.63 1.57v1.88h2.78l-.44 2.9h-2.34V22c4.78-.75 8.44-4.91 8.44-9.93z"/></svg>',
			'x'         => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" focusable="false"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>',
		);

		return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
	}
}
