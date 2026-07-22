<?php
/**
 * Admin settings for Worship Schedule.
 *
 * @package UUCG_Worship_Schedule
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings page.
 */
class UUCG_WS_Admin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_post_uucg_ws_refresh', array( __CLASS__, 'handle_refresh' ) );
	}

	public static function add_menu() {
		add_options_page(
			__( 'Worship Schedule', 'uucg-worship-schedule' ),
			__( 'Worship Schedule', 'uucg-worship-schedule' ),
			'manage_options',
			'uucg-worship-schedule',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * @param string $hook Hook.
	 */
	public static function enqueue( $hook ) {
		if ( 'settings_page_uucg-worship-schedule' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'uucg-ws-admin',
			UUCG_WS_URL . 'assets/css/admin.css',
			array(),
			UUCG_WS_VERSION
		);
	}

	public static function register_settings() {
		register_setting(
			'uucg_ws_group',
			UUCG_WS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => UUCG_Worship_Schedule::defaults(),
			)
		);
	}

	/**
	 * @param array $input Raw.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = UUCG_Worship_Schedule::defaults();
		$out      = $defaults;

		if ( ! is_array( $input ) ) {
			return $out;
		}

		// Allow pasting a full Google Sheets URL.
		if ( ! empty( $input['sheet_url'] ) ) {
			$parsed = self::parse_sheet_url( $input['sheet_url'] );
			if ( $parsed['id'] ) {
				$out['sheet_id'] = $parsed['id'];
			}
			if ( $parsed['gid'] ) {
				$out['sheet_gid'] = $parsed['gid'];
			}
		}

		if ( ! empty( $input['sheet_id'] ) ) {
			$out['sheet_id'] = sanitize_text_field( $input['sheet_id'] );
		}
		if ( isset( $input['sheet_gid'] ) && '' !== $input['sheet_gid'] ) {
			$out['sheet_gid'] = preg_replace( '/[^0-9]/', '', (string) $input['sheet_gid'] );
		}

		$out['cache_minutes']  = isset( $input['cache_minutes'] ) ? max( 5, absint( $input['cache_minutes'] ) ) : 60;
		$out['title']          = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : $defaults['title'];
		$out['subtitle']       = isset( $input['subtitle'] ) ? sanitize_text_field( $input['subtitle'] ) : $defaults['subtitle'];
		$out['show_header']    = ! empty( $input['show_header'] ) ? 1 : 0;
		$out['default_view']   = ( isset( $input['default_view'] ) && 'calendar' === $input['default_view'] ) ? 'calendar' : 'list';
		$out['show_past']      = ! empty( $input['show_past'] ) ? 1 : 0;
		$out['show_empty']     = ! empty( $input['show_empty'] ) ? 1 : 0;
		$out['show_associate'] = ! empty( $input['show_associate'] ) ? 1 : 0;
		$out['show_holidays']  = ! empty( $input['show_holidays'] ) ? 1 : 0;
		$out['show_summary']   = ! empty( $input['show_summary'] ) ? 1 : 0;
		$out['timezone']       = isset( $input['timezone'] ) ? sanitize_text_field( $input['timezone'] ) : $defaults['timezone'];

		// Bust cache when sheet target changes.
		$prev = UUCG_Worship_Schedule::get_settings();
		if ( $prev['sheet_id'] !== $out['sheet_id'] || $prev['sheet_gid'] !== $out['sheet_gid'] ) {
			delete_transient( UUCG_WS_CACHE_KEY );
		}

		return $out;
	}

	/**
	 * Extract spreadsheet ID + gid from a full URL.
	 *
	 * @param string $url URL.
	 * @return array{id:string,gid:string}
	 */
	public static function parse_sheet_url( $url ) {
		$id  = '';
		$gid = '';
		if ( preg_match( '#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $url, $m ) ) {
			$id = $m[1];
		}
		if ( preg_match( '#[?&#]gid=([0-9]+)#', $url, $m ) ) {
			$gid = $m[1];
		}
		return array(
			'id'  => $id,
			'gid' => $gid,
		);
	}

	/**
	 * Manual refresh handler.
	 */
	public static function handle_refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'uucg-worship-schedule' ) );
		}
		check_admin_referer( 'uucg_ws_refresh' );

		$result  = UUCG_WS_Fetcher::refresh_cache( true );
		$redirect = add_query_arg(
			array(
				'page'             => 'uucg-worship-schedule',
				'uucg_ws_refreshed'=> is_wp_error( $result ) ? '0' : '1',
			),
			admin_url( 'options-general.php' )
		);

		if ( is_wp_error( $result ) ) {
			set_transient(
				'uucg_ws_admin_notice',
				$result->get_error_message(),
				60
			);
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Render settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$s    = UUCG_Worship_Schedule::get_settings();
		$meta = get_option( UUCG_WS_CACHE_META, array() );
		$data = UUCG_WS_Fetcher::get_data();

		if ( isset( $_GET['uucg_ws_refreshed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$ok = '1' === $_GET['uucg_ws_refreshed']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $ok ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Schedule refreshed from Google Sheets.', 'uucg-worship-schedule' ) . '</p></div>';
			} else {
				$err = get_transient( 'uucg_ws_admin_notice' );
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $err ? $err : __( 'Refresh failed.', 'uucg-worship-schedule' ) ) . '</p></div>';
			}
		}
		?>
		<div class="wrap uucg-ws-admin">
			<h1><?php esc_html_e( 'Worship Schedule', 'uucg-worship-schedule' ); ?></h1>
			<p class="uucg-ws-admin__intro">
				<?php
				echo wp_kses(
					__( 'Pulls Sundays from your Google Sheet and displays topics, seasonal categories, and speakers. Place <code>[worship_schedule]</code> on any page.', 'uucg-worship-schedule' ),
					array( 'code' => array() )
				);
				?>
			</p>

			<div class="uucg-ws-admin__shortcode-box">
				<strong><?php esc_html_e( 'Shortcode', 'uucg-worship-schedule' ); ?>:</strong>
				<code>[worship_schedule]</code>
				<span class="description">
					<?php esc_html_e( 'Examples:', 'uucg-worship-schedule' ); ?>
					<code>[worship_schedule default_view="calendar"]</code>
					<code>[worship_schedule show_past="1" show_empty="0"]</code>
					<code>[worship_schedule quarter="autumn"]</code>
				</span>
			</div>

			<div class="uucg-ws-admin__status">
				<div>
					<strong><?php esc_html_e( 'Cached services', 'uucg-worship-schedule' ); ?>:</strong>
					<?php echo esc_html( (string) count( $data['services'] ) ); ?>
				</div>
				<div>
					<strong><?php esc_html_e( 'Year theme', 'uucg-worship-schedule' ); ?>:</strong>
					<?php echo esc_html( $data['year_theme'] ? $data['year_theme'] : '—' ); ?>
				</div>
				<div>
					<strong><?php esc_html_e( 'Last fetch', 'uucg-worship-schedule' ); ?>:</strong>
					<?php
					if ( ! empty( $meta['fetched_at'] ) ) {
						echo esc_html(
							date_i18n(
								get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
								(int) $meta['fetched_at'] + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS )
							)
						);
					} else {
						echo '—';
					}
					?>
				</div>
				<?php if ( ! empty( $meta['last_error'] ) ) : ?>
					<div class="uucg-ws-admin__error">
						<strong><?php esc_html_e( 'Last error', 'uucg-worship-schedule' ); ?>:</strong>
						<?php echo esc_html( $meta['last_error'] ); ?>
					</div>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="uucg-ws-admin__refresh-form">
					<input type="hidden" name="action" value="uucg_ws_refresh" />
					<?php wp_nonce_field( 'uucg_ws_refresh' ); ?>
					<?php submit_button( __( 'Refresh from Google Sheets now', 'uucg-worship-schedule' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'uucg_ws_group' ); ?>

				<div class="uucg-ws-admin__grid">
					<section class="uucg-ws-admin__card">
						<h2><?php esc_html_e( 'Google Sheet', 'uucg-worship-schedule' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="uucg_ws_sheet_url"><?php esc_html_e( 'Sheet URL', 'uucg-worship-schedule' ); ?></label></th>
								<td>
									<input
										type="url"
										class="large-text"
										id="uucg_ws_sheet_url"
										name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[sheet_url]"
										placeholder="https://docs.google.com/spreadsheets/d/…/edit?gid=…"
										value=""
									/>
									<p class="description">
										<?php esc_html_e( 'Paste the full share link to auto-fill ID and tab. Leave blank to keep the values below.', 'uucg-worship-schedule' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_ws_sheet_id"><?php esc_html_e( 'Spreadsheet ID', 'uucg-worship-schedule' ); ?></label></th>
								<td>
									<input
										type="text"
										class="large-text code"
										id="uucg_ws_sheet_id"
										name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[sheet_id]"
										value="<?php echo esc_attr( $s['sheet_id'] ); ?>"
									/>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_ws_sheet_gid"><?php esc_html_e( 'Sheet tab gid', 'uucg-worship-schedule' ); ?></label></th>
								<td>
									<input
										type="text"
										class="regular-text code"
										id="uucg_ws_sheet_gid"
										name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[sheet_gid]"
										value="<?php echo esc_attr( $s['sheet_gid'] ); ?>"
									/>
									<p class="description"><?php esc_html_e( 'The number after gid= in the sheet URL.', 'uucg-worship-schedule' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_ws_cache"><?php esc_html_e( 'Cache (minutes)', 'uucg-worship-schedule' ); ?></label></th>
								<td>
									<input
										type="number"
										min="5"
										max="1440"
										id="uucg_ws_cache"
										name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[cache_minutes]"
										value="<?php echo esc_attr( (string) $s['cache_minutes'] ); ?>"
										class="small-text"
									/>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_ws_tz"><?php esc_html_e( 'Timezone', 'uucg-worship-schedule' ); ?></label></th>
								<td>
									<input
										type="text"
										id="uucg_ws_tz"
										name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[timezone]"
										value="<?php echo esc_attr( $s['timezone'] ); ?>"
										class="regular-text"
									/>
									<p class="description"><?php esc_html_e( 'Used to decide which services are “past” (e.g. America/Denver).', 'uucg-worship-schedule' ); ?></p>
								</td>
							</tr>
						</table>
						<p class="description">
							<?php esc_html_e( 'The spreadsheet must be shared as “Anyone with the link can view” so WordPress can download the CSV.', 'uucg-worship-schedule' ); ?>
						</p>
					</section>

					<section class="uucg-ws-admin__card">
						<h2><?php esc_html_e( 'Display', 'uucg-worship-schedule' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="uucg_ws_title"><?php esc_html_e( 'Title', 'uucg-worship-schedule' ); ?></label></th>
								<td><input type="text" class="regular-text" id="uucg_ws_title" name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[title]" value="<?php echo esc_attr( $s['title'] ); ?>" /></td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_ws_subtitle"><?php esc_html_e( 'Subtitle', 'uucg-worship-schedule' ); ?></label></th>
								<td><input type="text" class="large-text" id="uucg_ws_subtitle" name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[subtitle]" value="<?php echo esc_attr( $s['subtitle'] ); ?>" /></td>
							</tr>
							<tr>
								<th scope="row"><label for="uucg_ws_default_view"><?php esc_html_e( 'Default view', 'uucg-worship-schedule' ); ?></label></th>
								<td>
									<select id="uucg_ws_default_view" name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[default_view]">
										<option value="list" <?php selected( $s['default_view'], 'list' ); ?>><?php esc_html_e( 'List', 'uucg-worship-schedule' ); ?></option>
										<option value="calendar" <?php selected( $s['default_view'], 'calendar' ); ?>><?php esc_html_e( 'Calendar', 'uucg-worship-schedule' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Options', 'uucg-worship-schedule' ); ?></th>
								<td>
									<label><input type="checkbox" name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[show_header]" value="1" <?php checked( ! empty( $s['show_header'] ) ); ?> /> <?php esc_html_e( 'Show header', 'uucg-worship-schedule' ); ?></label><br />
									<label><input type="checkbox" name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[show_past]" value="1" <?php checked( ! empty( $s['show_past'] ) ); ?> /> <?php esc_html_e( 'Include past services', 'uucg-worship-schedule' ); ?></label><br />
									<label><input type="checkbox" name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[show_empty]" value="1" <?php checked( ! empty( $s['show_empty'] ) ); ?> /> <?php esc_html_e( 'Include dates with no topic/speaker yet', 'uucg-worship-schedule' ); ?></label><br />
									<label><input type="checkbox" name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[show_associate]" value="1" <?php checked( ! empty( $s['show_associate'] ) ); ?> /> <?php esc_html_e( 'Show worship associate', 'uucg-worship-schedule' ); ?></label><br />
									<label><input type="checkbox" name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[show_holidays]" value="1" <?php checked( ! empty( $s['show_holidays'] ) ); ?> /> <?php esc_html_e( 'Show holidays / observances', 'uucg-worship-schedule' ); ?></label><br />
									<label><input type="checkbox" name="<?php echo esc_attr( UUCG_WS_OPTION ); ?>[show_summary]" value="1" <?php checked( ! empty( $s['show_summary'] ) ); ?> /> <?php esc_html_e( 'Show newsletter summary when present', 'uucg-worship-schedule' ); ?></label>
								</td>
							</tr>
						</table>
					</section>
				</div>

				<?php submit_button( __( 'Save settings', 'uucg-worship-schedule' ) ); ?>
			</form>

			<?php if ( ! empty( $data['services'] ) ) : ?>
				<details class="uucg-ws-admin__preview">
					<summary><?php esc_html_e( 'Preview first 8 services', 'uucg-worship-schedule' ); ?></summary>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'uucg-worship-schedule' ); ?></th>
								<th><?php esc_html_e( 'Season', 'uucg-worship-schedule' ); ?></th>
								<th><?php esc_html_e( 'Topic', 'uucg-worship-schedule' ); ?></th>
								<th><?php esc_html_e( 'Celebrant', 'uucg-worship-schedule' ); ?></th>
								<th><?php esc_html_e( 'Holidays', 'uucg-worship-schedule' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $data['services'], 0, 8 ) as $svc ) : ?>
								<tr>
									<td><?php echo esc_html( $svc['date_display'] ); ?></td>
									<td><?php echo esc_html( $svc['quarter_label'] ); ?></td>
									<td><?php echo esc_html( $svc['topic'] ? $svc['topic'] : '—' ); ?></td>
									<td><?php echo esc_html( $svc['celebrant'] ? $svc['celebrant'] : '—' ); ?></td>
									<td><?php echo esc_html( $svc['holidays'] ? $svc['holidays'] : '—' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</details>
			<?php endif; ?>

			<details class="uucg-ws-admin__help">
				<summary><?php esc_html_e( 'Expected spreadsheet columns', 'uucg-worship-schedule' ); ?></summary>
				<ol>
					<li><?php esc_html_e( 'Theme / seasonal quarter (e.g. Autumn Quarter: Composting…)', 'uucg-worship-schedule' ); ?></li>
					<li><?php esc_html_e( 'Date (MM/DD/YYYY)', 'uucg-worship-schedule' ); ?></li>
					<li><?php esc_html_e( 'Celebrant (speaker)', 'uucg-worship-schedule' ); ?></li>
					<li><?php esc_html_e( 'Worship Associate', 'uucg-worship-schedule' ); ?></li>
					<li><?php esc_html_e( 'Topic', 'uucg-worship-schedule' ); ?></li>
					<li><?php esc_html_e( 'Holidays', 'uucg-worship-schedule' ); ?></li>
					<li><?php esc_html_e( '…music, hymns, RE, summary (optional)', 'uucg-worship-schedule' ); ?></li>
				</ol>
			</details>
		</div>
		<?php
	}
}
