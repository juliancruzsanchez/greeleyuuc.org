<?php
/**
 * Fetch & cache schedule data from Google Sheets.
 *
 * @package UUCG_Worship_Schedule
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remote fetch + transient cache.
 */
class UUCG_WS_Fetcher {

	/**
	 * Build public CSV export URL.
	 *
	 * @param string $sheet_id Spreadsheet ID.
	 * @param string $gid      Sheet tab gid.
	 * @return string
	 */
	public static function csv_url( $sheet_id, $gid ) {
		$sheet_id = sanitize_text_field( $sheet_id );
		$gid      = preg_replace( '/[^0-9]/', '', (string) $gid );
		return sprintf(
			'https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%s',
			rawurlencode( $sheet_id ),
			$gid
		);
	}

	/**
	 * Get services (from cache, refreshing if needed).
	 *
	 * @param bool $force Force network refresh.
	 * @return array{year_theme:string,services:array,meta:array}
	 */
	public static function get_data( $force = false ) {
		$settings = UUCG_Worship_Schedule::get_settings();
		$cached   = get_transient( UUCG_WS_CACHE_KEY );
		$meta     = get_option( UUCG_WS_CACHE_META, array() );

		if ( ! $force && is_array( $cached ) && isset( $cached['services'] ) ) {
			return array(
				'year_theme' => isset( $cached['year_theme'] ) ? $cached['year_theme'] : '',
				'services'   => $cached['services'],
				'meta'       => is_array( $meta ) ? $meta : array(),
			);
		}

		$result = self::refresh_cache( $force );
		if ( is_wp_error( $result ) ) {
			// Fall back to last good cache stored as option (survives transient expiry).
			$fallback = get_option( UUCG_WS_CACHE_KEY . '_backup', null );
			if ( is_array( $fallback ) && isset( $fallback['services'] ) ) {
				return array(
					'year_theme' => isset( $fallback['year_theme'] ) ? $fallback['year_theme'] : '',
					'services'   => $fallback['services'],
					'meta'       => array_merge(
						is_array( $meta ) ? $meta : array(),
						array(
							'error'       => $result->get_error_message(),
							'using_stale' => true,
						)
					),
				);
			}

			return array(
				'year_theme' => '',
				'services'   => array(),
				'meta'       => array(
					'error'  => $result->get_error_message(),
					'source' => 'error',
				),
			);
		}

		$meta = get_option( UUCG_WS_CACHE_META, array() );
		return array(
			'year_theme' => $result['year_theme'],
			'services'   => $result['services'],
			'meta'       => is_array( $meta ) ? $meta : array(),
		);
	}

	/**
	 * Fetch CSV, parse, and store cache.
	 *
	 * @param bool $force Unused flag for API symmetry.
	 * @return array|WP_Error
	 */
	public static function refresh_cache( $force = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		$settings = UUCG_Worship_Schedule::get_settings();
		$url      = self::csv_url( $settings['sheet_id'], $settings['sheet_gid'] );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 20,
				'redirection'=> 5,
				'headers'    => array(
					'Accept' => 'text/csv,text/plain,*/*',
				),
				'user-agent' => 'UUCG-Worship-Schedule/' . UUCG_WS_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			self::store_meta_error( $response->get_error_message(), $url );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 || '' === trim( (string) $body ) ) {
			// Try gviz endpoint as fallback (sometimes more reliable when export is gated).
			$gviz = sprintf(
				'https://docs.google.com/spreadsheets/d/%s/gviz/tq?tqx=out:csv&gid=%s',
				rawurlencode( $settings['sheet_id'] ),
				preg_replace( '/[^0-9]/', '', (string) $settings['sheet_gid'] )
			);
			$response = wp_remote_get(
				$gviz,
				array(
					'timeout'     => 20,
					'redirection' => 5,
					'user-agent'  => 'UUCG-Worship-Schedule/' . UUCG_WS_VERSION . '; ' . home_url( '/' ),
				)
			);
			if ( is_wp_error( $response ) ) {
				self::store_meta_error( $response->get_error_message(), $gviz );
				return $response;
			}
			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			$url  = $gviz;
		}

		if ( $code < 200 || $code >= 300 || '' === trim( (string) $body ) ) {
			$msg = sprintf(
				/* translators: %d: HTTP status */
				__( 'Could not download the spreadsheet (HTTP %d). Make sure the sheet is shared as “Anyone with the link can view”.', 'uucg-worship-schedule' ),
				(int) $code
			);
			self::store_meta_error( $msg, $url );
			return new WP_Error( 'uucg_ws_fetch', $msg );
		}

		// Detect HTML login / permission pages.
		if ( preg_match( '/^\s*</', $body ) && false !== stripos( $body, '<html' ) ) {
			$msg = __( 'Google returned a web page instead of CSV. Check that the spreadsheet is publicly viewable.', 'uucg-worship-schedule' );
			self::store_meta_error( $msg, $url );
			return new WP_Error( 'uucg_ws_html', $msg );
		}

		$parsed = UUCG_WS_Parser::parse_csv( $body );
		$payload = array(
			'year_theme' => $parsed['year_theme'],
			'services'   => $parsed['services'],
			'fetched_at' => time(),
		);

		$minutes = max( 5, absint( $settings['cache_minutes'] ) );
		set_transient( UUCG_WS_CACHE_KEY, $payload, $minutes * MINUTE_IN_SECONDS );
		update_option( UUCG_WS_CACHE_KEY . '_backup', $payload, false );

		$meta = array(
			'fetched_at'   => time(),
			'fetched_iso'  => gmdate( 'c' ),
			'source_url'   => $url,
			'count'        => count( $parsed['services'] ),
			'year_theme'   => $parsed['year_theme'],
			'error'        => '',
			'using_stale'  => false,
		);
		update_option( UUCG_WS_CACHE_META, $meta, false );

		return $payload;
	}

	/**
	 * @param string $message Error message.
	 * @param string $url     Attempted URL.
	 */
	private static function store_meta_error( $message, $url ) {
		$meta = get_option( UUCG_WS_CACHE_META, array() );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}
		$meta['last_error']   = $message;
		$meta['last_error_at']= time();
		$meta['source_url']   = $url;
		update_option( UUCG_WS_CACHE_META, $meta, false );
	}

	/**
	 * Filter services for display.
	 *
	 * @param array $services Services.
	 * @param array $args     Filters: show_past, show_empty, quarter, from, to.
	 * @return array
	 */
	public static function filter_services( array $services, array $args = array() ) {
		$settings  = UUCG_Worship_Schedule::get_settings();
		$show_past = array_key_exists( 'show_past', $args ) ? (bool) $args['show_past'] : ! empty( $settings['show_past'] );
		$show_empty= array_key_exists( 'show_empty', $args ) ? (bool) $args['show_empty'] : ! empty( $settings['show_empty'] );
		$quarter   = isset( $args['quarter'] ) ? sanitize_key( $args['quarter'] ) : '';
		$today     = self::today_iso();

		$out = array();
		foreach ( $services as $svc ) {
			if ( ! $show_past && isset( $svc['date'] ) && $svc['date'] < $today ) {
				continue;
			}
			if ( ! $show_empty && empty( $svc['has_content'] ) ) {
				continue;
			}
			if ( $quarter && $quarter !== 'all' && ( ! isset( $svc['quarter'] ) || $svc['quarter'] !== $quarter ) ) {
				continue;
			}
			$out[] = $svc;
		}
		return $out;
	}

	/**
	 * Today's date in site/settings timezone as Y-m-d.
	 *
	 * @return string
	 */
	public static function today_iso() {
		$settings = UUCG_Worship_Schedule::get_settings();
		$tz_name  = ! empty( $settings['timezone'] ) ? $settings['timezone'] : wp_timezone_string();
		try {
			$tz = new DateTimeZone( $tz_name );
		} catch ( Exception $e ) {
			$tz = wp_timezone();
		}
		$now = new DateTime( 'now', $tz );
		return $now->format( 'Y-m-d' );
	}
}
