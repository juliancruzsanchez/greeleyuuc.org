<?php
/**
 * Admin settings screen.
 *
 * @package UUCG_In_The_Loop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings → In the Loop admin page.
 */
class UUCG_ITL_Admin {

	/**
	 * Hook admin menus and assets.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Settings page under Settings.
	 */
	public static function add_menu() {
		add_options_page(
			__( 'In the Loop', 'uucg-in-the-loop' ),
			__( 'In the Loop', 'uucg-in-the-loop' ),
			'manage_options',
			'uucg-in-the-loop',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue admin CSS on our page only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue( $hook ) {
		if ( 'settings_page_uucg-in-the-loop' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'uucg-itl-admin',
			UUCG_ITL_URL . 'assets/css/admin.css',
			array(),
			UUCG_ITL_VERSION
		);
	}

	/**
	 * Register option + sections.
	 */
	public static function register_settings() {
		register_setting(
			'uucg_itl_group',
			UUCG_ITL_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => UUCG_In_The_Loop::defaults(),
			)
		);
	}

	/**
	 * Sanitize all settings fields.
	 *
	 * @param array $input Raw POST.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = UUCG_In_The_Loop::defaults();
		$out      = $defaults;

		if ( ! is_array( $input ) ) {
			return $out;
		}

		$out['title']       = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : $defaults['title'];
		$out['subtitle']    = isset( $input['subtitle'] ) ? sanitize_text_field( $input['subtitle'] ) : $defaults['subtitle'];
		$out['default_tab'] = isset( $input['default_tab'] ) ? sanitize_key( $input['default_tab'] ) : 'tiktok';
		if ( ! in_array( $out['default_tab'], array( 'tiktok', 'instagram', 'facebook', 'x' ), true ) ) {
			$out['default_tab'] = 'tiktok';
		}

		$out['columns']     = isset( $input['columns'] ) ? max( 1, min( 4, absint( $input['columns'] ) ) ) : 3;
		$out['show_header'] = ! empty( $input['show_header'] ) ? 1 : 0;

		$out['tiktok_username'] = isset( $input['tiktok_username'] ) ? sanitize_text_field( ltrim( $input['tiktok_username'], '@' ) ) : '';
		$out['tiktok_profile']  = isset( $input['tiktok_profile'] ) ? esc_url_raw( $input['tiktok_profile'] ) : '';
		$out['tiktok_urls']     = isset( $input['tiktok_urls'] ) ? self::sanitize_url_list( $input['tiktok_urls'] ) : '';

		$out['instagram_username'] = isset( $input['instagram_username'] ) ? sanitize_text_field( ltrim( $input['instagram_username'], '@' ) ) : '';
		$out['instagram_profile']  = isset( $input['instagram_profile'] ) ? esc_url_raw( $input['instagram_profile'] ) : '';
		$out['instagram_urls']     = isset( $input['instagram_urls'] ) ? self::sanitize_url_list( $input['instagram_urls'] ) : '';

		$out['facebook_page']            = isset( $input['facebook_page'] ) ? esc_url_raw( $input['facebook_page'] ) : '';
		$out['facebook_urls']            = isset( $input['facebook_urls'] ) ? self::sanitize_url_list( $input['facebook_urls'] ) : '';
		$out['facebook_use_page_plugin'] = ! empty( $input['facebook_use_page_plugin'] ) ? 1 : 0;
		$out['page_plugin_tabs']         = isset( $input['page_plugin_tabs'] ) ? sanitize_text_field( $input['page_plugin_tabs'] ) : 'timeline';

		$out['x_username']      = isset( $input['x_username'] ) ? sanitize_text_field( ltrim( $input['x_username'], '@' ) ) : '';
		$out['x_urls']          = isset( $input['x_urls'] ) ? self::sanitize_url_list( $input['x_urls'] ) : '';
		$out['x_use_timeline']  = ! empty( $input['x_use_timeline'] ) ? 1 : 0;
		$out['timeline_height'] = isset( $input['timeline_height'] ) ? max( 300, absint( $input['timeline_height'] ) ) : 700;

		return $out;
	}

	/**
	 * Keep URL list readable; strip dangerous content.
	 *
	 * @param string $raw Textarea.
	 * @return string
	 */
	private static function sanitize_url_list( $raw ) {
		$lines  = preg_split( '/\r\n|\r|\n/', (string) $raw );
		$clean  = array();
		foreach ( $lines as $line ) {
			$line = trim( wp_strip_all_tags( $line ) );
			if ( '' === $line ) {
				continue;
			}
			$clean[] = $line;
		}
		return implode( "\n", $clean );
	}

	/**
	 * Render settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$s = UUCG_In_The_Loop::get_settings();
		?>
		<div class="wrap uucg-itl-admin">
			<h1><?php esc_html_e( 'In the Loop — Social Feed', 'uucg-in-the-loop' ); ?></h1>
			<p class="uucg-itl-admin__intro">
				<?php
				echo wp_kses(
					__( 'Paste video and post URLs below, then place the shortcode <code>[in_the_loop]</code> on your In the Loop page. Tabs share the same modern card layout.', 'uucg-in-the-loop' ),
					array( 'code' => array() )
				);
				?>
			</p>

			<div class="uucg-itl-admin__shortcode-box">
				<strong><?php esc_html_e( 'Shortcode', 'uucg-in-the-loop' ); ?>:</strong>
				<code>[in_the_loop]</code>
				<span class="description">
					<?php esc_html_e( 'Optional attrs:', 'uucg-in-the-loop' ); ?>
					<code>[in_the_loop default_tab="facebook" columns="2" show_header="0"]</code>
				</span>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'uucg_itl_group' ); ?>

				<div class="uucg-itl-admin__grid">

					<!-- General -->
					<section class="uucg-itl-admin__card">
						<h2><?php esc_html_e( 'General', 'uucg-in-the-loop' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="uucg_itl_title"><?php esc_html_e( 'Title', 'uucg-in-the-loop' ); ?></label></th>
								<td><input name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[title]" type="text" id="uucg_itl_title" value="<?php echo esc_attr( $s['title'] ); ?>" class="regular-text" /></td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_itl_subtitle"><?php esc_html_e( 'Subtitle', 'uucg-in-the-loop' ); ?></label></th>
								<td><input name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[subtitle]" type="text" id="uucg_itl_subtitle" value="<?php echo esc_attr( $s['subtitle'] ); ?>" class="large-text" /></td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_itl_default_tab"><?php esc_html_e( 'Default tab', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<select name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[default_tab]" id="uucg_itl_default_tab">
										<option value="tiktok" <?php selected( $s['default_tab'], 'tiktok' ); ?>><?php esc_html_e( 'TikTok', 'uucg-in-the-loop' ); ?></option>
										<option value="instagram" <?php selected( $s['default_tab'], 'instagram' ); ?>><?php esc_html_e( 'Instagram', 'uucg-in-the-loop' ); ?></option>
										<option value="facebook" <?php selected( $s['default_tab'], 'facebook' ); ?>><?php esc_html_e( 'Facebook', 'uucg-in-the-loop' ); ?></option>
										<option value="x" <?php selected( $s['default_tab'], 'x' ); ?>><?php esc_html_e( 'X', 'uucg-in-the-loop' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_itl_columns"><?php esc_html_e( 'Columns (desktop)', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<select name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[columns]" id="uucg_itl_columns">
										<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
											<option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( (int) $s['columns'], $i ); ?>><?php echo esc_html( (string) $i ); ?></option>
										<?php endfor; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Show header', 'uucg-in-the-loop' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[show_header]" value="1" <?php checked( ! empty( $s['show_header'] ) ); ?> />
										<?php esc_html_e( 'Display title, subtitle, and rainbow accent', 'uucg-in-the-loop' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_itl_timeline_height"><?php esc_html_e( 'Timeline height (px)', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<input name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[timeline_height]" type="number" min="300" max="2000" step="10" id="uucg_itl_timeline_height" value="<?php echo esc_attr( (string) $s['timeline_height'] ); ?>" class="small-text" />
									<p class="description"><?php esc_html_e( 'Used for Facebook Page Plugin and X timeline widgets.', 'uucg-in-the-loop' ); ?></p>
								</td>
							</tr>
						</table>
					</section>

					<!-- TikTok -->
					<section class="uucg-itl-admin__card uucg-itl-admin__card--tiktok">
						<h2>
							<span class="uucg-itl-admin__badge">TikTok</span>
							<?php esc_html_e( 'Videos', 'uucg-in-the-loop' ); ?>
						</h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="uucg_itl_tiktok_username"><?php esc_html_e( 'Username', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<input name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[tiktok_username]" type="text" id="uucg_itl_tiktok_username" value="<?php echo esc_attr( $s['tiktok_username'] ); ?>" class="regular-text" placeholder="greeleyuuc" />
									<p class="description"><?php esc_html_e( 'Without @. Used for the “Follow on TikTok” button.', 'uucg-in-the-loop' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_itl_tiktok_profile"><?php esc_html_e( 'Profile URL (optional)', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<input name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[tiktok_profile]" type="url" id="uucg_itl_tiktok_profile" value="<?php echo esc_attr( $s['tiktok_profile'] ); ?>" class="large-text" placeholder="https://www.tiktok.com/@greeleyuuc" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_itl_tiktok_urls"><?php esc_html_e( 'Video URLs', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<textarea name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[tiktok_urls]" id="uucg_itl_tiktok_urls" rows="10" class="large-text code"><?php echo esc_textarea( $s['tiktok_urls'] ?? '' ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'One video URL per line. Example:', 'uucg-in-the-loop' ); ?>
										<code>https://www.tiktok.com/@greeleyuuc/video/7636822054037196045</code>
									</p>
								</td>
							</tr>
						</table>
					</section>

					<!-- Instagram -->
					<section class="uucg-itl-admin__card uucg-itl-admin__card--instagram">
						<h2>
							<span class="uucg-itl-admin__badge">Instagram</span>
							<?php esc_html_e( 'Posts & reels', 'uucg-in-the-loop' ); ?>
						</h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="uucg_itl_instagram_username"><?php esc_html_e( 'Username', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<input name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[instagram_username]" type="text" id="uucg_itl_instagram_username" value="<?php echo esc_attr( $s['instagram_username'] ?? '' ); ?>" class="regular-text" placeholder="yourchurch" />
									<p class="description"><?php esc_html_e( 'Without @. Used for the “Follow on Instagram” button.', 'uucg-in-the-loop' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_itl_instagram_profile"><?php esc_html_e( 'Profile URL (optional)', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<input name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[instagram_profile]" type="url" id="uucg_itl_instagram_profile" value="<?php echo esc_attr( $s['instagram_profile'] ?? '' ); ?>" class="large-text" placeholder="https://www.instagram.com/yourchurch/" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_itl_instagram_urls"><?php esc_html_e( 'Post / reel URLs', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<textarea name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[instagram_urls]" id="uucg_itl_instagram_urls" rows="10" class="large-text code"><?php echo esc_textarea( $s['instagram_urls'] ?? '' ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'One post or reel URL per line. Example:', 'uucg-in-the-loop' ); ?>
										<code>https://www.instagram.com/p/AbCdEfGhIjK/</code>
										or
										<code>https://www.instagram.com/reel/AbCdEfGhIjK/</code>
									</p>
								</td>
							</tr>
						</table>
					</section>

					<!-- Facebook -->
					<section class="uucg-itl-admin__card uucg-itl-admin__card--facebook">
						<h2>
							<span class="uucg-itl-admin__badge">Facebook</span>
							<?php esc_html_e( 'Posts & page', 'uucg-in-the-loop' ); ?>
						</h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="uucg_itl_facebook_page"><?php esc_html_e( 'Page URL', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<input name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[facebook_page]" type="url" id="uucg_itl_facebook_page" value="<?php echo esc_attr( $s['facebook_page'] ); ?>" class="large-text" placeholder="https://www.facebook.com/YourPage" />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Page plugin', 'uucg-in-the-loop' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[facebook_use_page_plugin]" value="1" <?php checked( ! empty( $s['facebook_use_page_plugin'] ) ); ?> />
										<?php esc_html_e( 'Embed the live Facebook Page timeline (requires a public Page URL)', 'uucg-in-the-loop' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_itl_page_plugin_tabs"><?php esc_html_e( 'Page tabs', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<input name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[page_plugin_tabs]" type="text" id="uucg_itl_page_plugin_tabs" value="<?php echo esc_attr( $s['page_plugin_tabs'] ); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Comma-separated: timeline, events, messages', 'uucg-in-the-loop' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_itl_facebook_urls"><?php esc_html_e( 'Post / video URLs', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<textarea name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[facebook_urls]" id="uucg_itl_facebook_urls" rows="10" class="large-text code"><?php echo esc_textarea( $s['facebook_urls'] ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'One post or video URL per line (optional if using the page plugin).', 'uucg-in-the-loop' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</section>

					<!-- X -->
					<section class="uucg-itl-admin__card uucg-itl-admin__card--x">
						<h2>
							<span class="uucg-itl-admin__badge">X</span>
							<?php esc_html_e( 'Posts & timeline', 'uucg-in-the-loop' ); ?>
						</h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="uucg_itl_x_username"><?php esc_html_e( 'Username', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<input name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[x_username]" type="text" id="uucg_itl_x_username" value="<?php echo esc_attr( $s['x_username'] ); ?>" class="regular-text" placeholder="yourchurch" />
									<p class="description"><?php esc_html_e( 'Without @.', 'uucg-in-the-loop' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Timeline widget', 'uucg-in-the-loop' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[x_use_timeline]" value="1" <?php checked( ! empty( $s['x_use_timeline'] ) ); ?> />
										<?php esc_html_e( 'Embed the live X / Twitter profile timeline', 'uucg-in-the-loop' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_itl_x_urls"><?php esc_html_e( 'Post URLs', 'uucg-in-the-loop' ); ?></label></th>
								<td>
									<textarea name="<?php echo esc_attr( UUCG_ITL_OPTION ); ?>[x_urls]" id="uucg_itl_x_urls" rows="10" class="large-text code"><?php echo esc_textarea( $s['x_urls'] ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'One post URL per line. Example:', 'uucg-in-the-loop' ); ?>
										<code>https://x.com/user/status/1234567890</code>
									</p>
								</td>
							</tr>
						</table>
					</section>

				</div>

				<?php submit_button( __( 'Save In the Loop settings', 'uucg-in-the-loop' ) ); ?>
			</form>

			<details class="uucg-itl-admin__help">
				<summary><?php esc_html_e( 'How to get post URLs', 'uucg-in-the-loop' ); ?></summary>
				<ul>
					<li><strong>TikTok:</strong> <?php esc_html_e( 'Open a video → Share → Copy link. Paste the full URL (one per line).', 'uucg-in-the-loop' ); ?></li>
					<li><strong>Facebook:</strong> <?php esc_html_e( 'Open a post → menu (⋯) → Copy link. Or enable the Page plugin for a live feed.', 'uucg-in-the-loop' ); ?></li>
					<li><strong>X:</strong> <?php esc_html_e( 'Open a post → Share → Copy link. Or enable the timeline widget for a live feed.', 'uucg-in-the-loop' ); ?></li>
				</ul>
				<p>
					<?php esc_html_e( 'Note: TikTok does not offer a free public “profile feed” embed the way X and Facebook do. Curate your best videos by URL for a polished grid. Facebook Page Plugin and X Timeline pull live content automatically when enabled.', 'uucg-in-the-loop' ); ?>
				</p>
			</details>
		</div>
		<?php
	}
}
