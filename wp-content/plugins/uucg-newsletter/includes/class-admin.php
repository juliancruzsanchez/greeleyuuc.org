<?php
/**
 * Admin settings for newsletter.
 *
 * @package UUCG_Newsletter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings → Newsletter.
 */
class UUCG_NL_Admin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Menu.
	 */
	public static function menu() {
		add_options_page(
			__( 'Newsletter', 'uucg-newsletter' ),
			__( 'Newsletter', 'uucg-newsletter' ),
			'manage_options',
			'uucg-newsletter',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Register setting.
	 */
	public static function register() {
		register_setting(
			'uucg_nl_group',
			UUCG_NL_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => UUCG_Newsletter::defaults(),
			)
		);
	}

	/**
	 * @param array $input Raw.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$d   = UUCG_Newsletter::defaults();
		$out = $d;
		if ( ! is_array( $input ) ) {
			return $out;
		}

		$out['api_key']         = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
		$out['list_id']         = isset( $input['list_id'] ) ? sanitize_text_field( $input['list_id'] ) : '';
		$out['double_optin']    = ! empty( $input['double_optin'] ) ? 1 : 0;
		$out['use_mc_plugin']   = ! empty( $input['use_mc_plugin'] ) ? 1 : 0;
		$out['title']           = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : $d['title'];
		$out['subtitle']        = isset( $input['subtitle'] ) ? sanitize_text_field( $input['subtitle'] ) : $d['subtitle'];
		$out['button_label']    = isset( $input['button_label'] ) ? sanitize_text_field( $input['button_label'] ) : $d['button_label'];
		$out['placeholder']     = isset( $input['placeholder'] ) ? sanitize_text_field( $input['placeholder'] ) : $d['placeholder'];
		$out['success_message'] = isset( $input['success_message'] ) ? sanitize_text_field( $input['success_message'] ) : $d['success_message'];

		return $out;
	}

	/**
	 * Settings UI.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s        = UUCG_Newsletter::get_settings();
		$resolved = array(
			'key'  => UUCG_Newsletter::get_api_key() ? '••••' . substr( UUCG_Newsletter::get_api_key(), -4 ) : '—',
			'list' => UUCG_Newsletter::get_list_id() ? UUCG_Newsletter::get_list_id() : '—',
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Newsletter Signup', 'uucg-newsletter' ); ?></h1>
			<p>
				<?php
				echo wp_kses(
					__( 'Place <code>[uucg_newsletter]</code> on any page. Styles: <code>style="card"</code> (default), <code>band</code>, <code>compact</code>, or <code>minimal</code>.', 'uucg-newsletter' ),
					array( 'code' => array() )
				);
				?>
			</p>

			<div class="notice notice-info inline" style="padding:12px 16px;margin:16px 0;">
				<p style="margin:0">
					<strong><?php esc_html_e( 'Resolved credentials', 'uucg-newsletter' ); ?>:</strong>
					<?php
					printf(
						/* translators: 1: masked key 2: list id */
						esc_html__( 'API key %1$s · Audience %2$s', 'uucg-newsletter' ),
						esc_html( $resolved['key'] ),
						esc_html( $resolved['list'] )
					);
					?>
				</p>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'uucg_nl_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Use official Mailchimp plugin keys', 'uucg-newsletter' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( UUCG_NL_OPTION ); ?>[use_mc_plugin]" value="1" <?php checked( ! empty( $s['use_mc_plugin'] ) ); ?> />
								<?php esc_html_e( 'If this plugin’s fields are empty, reuse Settings → Mailchimp (mc_api_key / mc_list_id).', 'uucg-newsletter' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="uucg_nl_api"><?php esc_html_e( 'Mailchimp API key', 'uucg-newsletter' ); ?></label></th>
						<td>
							<input type="text" class="large-text code" id="uucg_nl_api" name="<?php echo esc_attr( UUCG_NL_OPTION ); ?>[api_key]" value="<?php echo esc_attr( $s['api_key'] ); ?>" autocomplete="off" />
							<p class="description"><?php esc_html_e( 'Mailchimp → Account → Extras → API keys. Looks like abcd1234-us12.', 'uucg-newsletter' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="uucg_nl_list"><?php esc_html_e( 'Audience / List ID', 'uucg-newsletter' ); ?></label></th>
						<td>
							<input type="text" class="regular-text code" id="uucg_nl_list" name="<?php echo esc_attr( UUCG_NL_OPTION ); ?>[list_id]" value="<?php echo esc_attr( $s['list_id'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Audience → Settings → Audience name and defaults → Audience ID.', 'uucg-newsletter' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Double opt-in', 'uucg-newsletter' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( UUCG_NL_OPTION ); ?>[double_optin]" value="1" <?php checked( ! empty( $s['double_optin'] ) ); ?> />
								<?php esc_html_e( 'Require confirmation email (recommended).', 'uucg-newsletter' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="uucg_nl_title"><?php esc_html_e( 'Default title', 'uucg-newsletter' ); ?></label></th>
						<td><input type="text" class="regular-text" id="uucg_nl_title" name="<?php echo esc_attr( UUCG_NL_OPTION ); ?>[title]" value="<?php echo esc_attr( $s['title'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="uucg_nl_sub"><?php esc_html_e( 'Default subtitle', 'uucg-newsletter' ); ?></label></th>
						<td><input type="text" class="large-text" id="uucg_nl_sub" name="<?php echo esc_attr( UUCG_NL_OPTION ); ?>[subtitle]" value="<?php echo esc_attr( $s['subtitle'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="uucg_nl_btn"><?php esc_html_e( 'Button label', 'uucg-newsletter' ); ?></label></th>
						<td><input type="text" class="regular-text" id="uucg_nl_btn" name="<?php echo esc_attr( UUCG_NL_OPTION ); ?>[button_label]" value="<?php echo esc_attr( $s['button_label'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="uucg_nl_ph"><?php esc_html_e( 'Input placeholder', 'uucg-newsletter' ); ?></label></th>
						<td><input type="text" class="regular-text" id="uucg_nl_ph" name="<?php echo esc_attr( UUCG_NL_OPTION ); ?>[placeholder]" value="<?php echo esc_attr( $s['placeholder'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="uucg_nl_ok"><?php esc_html_e( 'Success message', 'uucg-newsletter' ); ?></label></th>
						<td><input type="text" class="large-text" id="uucg_nl_ok" name="<?php echo esc_attr( UUCG_NL_OPTION ); ?>[success_message]" value="<?php echo esc_attr( $s['success_message'] ); ?>" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Save newsletter settings', 'uucg-newsletter' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Shortcode examples', 'uucg-newsletter' ); ?></h2>
			<ul>
				<li><code>[uucg_newsletter]</code> — smaller cream card</li>
				<li><code>[uucg_newsletter style="full"]</code> — full width, same cream style</li>
				<li><code>[uucg_newsletter style="band"]</code> — smaller primary/maroon band</li>
				<li><code>[uucg_newsletter style="full-band"]</code> — full width primary/maroon band</li>
				<li><code>[uucg_newsletter style="compact" title="Join our list"]</code></li>
				<li><code>[uucg_newsletter style="minimal" show_title="0"]</code></li>
			</ul>
		</div>
		<?php
	}
}
