<?php
/**
 * Embed markup builders for TikTok, Instagram, Facebook, and X.
 *
 * @package UUCG_In_The_Loop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build platform-specific embed HTML from URLs.
 */
class UUCG_ITL_Embeds {

	/**
	 * Username from a profile URL.
	 *
	 * @param string $url Profile URL.
	 * @return string
	 */
	public static function username_from_profile_url( $url ) {
		if ( preg_match( '~tiktok\.com/@([^/?#]+)~i', (string) $url, $m ) ) {
			return sanitize_text_field( $m[1] );
		}
		return '';
	}

	/**
	 * Bare numeric IDs from a hide list (one per line).
	 *
	 * @param string $raw Textarea.
	 * @return string[]
	 */
	public static function parse_hide_ids( $raw ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
		$ids   = array();
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || '#' === $line[0] ) {
				continue;
			}
			if ( preg_match( '/^\d{10,}$/', $line ) ) {
				$ids[] = $line;
			}
		}
		return $ids;
	}

	/**
	 * Parse a textarea of URLs into a clean list.
	 *
	 * @param string $raw One URL per line (comments after # ignored).
	 * @return string[]
	 */
	public static function parse_urls( $raw ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
		$urls  = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || '#' === $line[0] ) {
				continue;
			}
			// Allow "label | url" or plain url.
			if ( false !== strpos( $line, '|' ) ) {
				$parts = array_map( 'trim', explode( '|', $line, 2 ) );
				$line  = end( $parts );
			}
			$url = esc_url_raw( $line );
			if ( $url && filter_var( $url, FILTER_VALIDATE_URL ) ) {
				$urls[] = $url;
			}
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * Normalize TikTok video URL to a canonical form.
	 *
	 * @param string $url Raw URL.
	 * @return string|null Canonical video URL or null.
	 */
	public static function normalize_tiktok_url( $url ) {
		$url = trim( $url );

		// https://www.tiktok.com/@user/video/1234567890 (optional query string)
		if ( preg_match( '~tiktok\.com/@([^/?]+)/video/(\d+)~i', $url, $m ) ) {
			return 'https://www.tiktok.com/@' . $m[1] . '/video/' . $m[2];
		}

		// https://www.tiktok.com/video/123 (rare)
		if ( preg_match( '~tiktok\.com/video/(\d+)~i', $url, $m ) ) {
			return 'https://www.tiktok.com/video/' . $m[1];
		}

		// https://vm.tiktok.com/XXXX/ — keep as-is; no stable video id without resolve.
		if ( preg_match( '~https?://(vm|vt)\.tiktok\.com/[A-Za-z0-9]+~i', $url ) ) {
			return esc_url_raw( $url );
		}

		// https://www.tiktok.com/t/XXXX/
		if ( preg_match( '~tiktok\.com/t/[A-Za-z0-9]+~i', $url ) ) {
			return esc_url_raw( $url );
		}

		return null;
	}

	/**
	 * Extract numeric TikTok video ID from a URL.
	 *
	 * @param string $url URL.
	 * @return string|null
	 */
	public static function extract_tiktok_video_id( $url ) {
		if ( preg_match( '~/video/(\d+)~', $url, $m ) ) {
			return $m[1];
		}
		return null;
	}

	/**
	 * Normalize Facebook post/video/reel URL.
	 *
	 * @param string $url Raw URL.
	 * @return string|null
	 */
	public static function normalize_facebook_url( $url ) {
		$url = esc_url_raw( trim( $url ) );
		if ( ! $url ) {
			return null;
		}
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return null;
		}
		$host = strtolower( $host );
		if (
			false === strpos( $host, 'facebook.com' )
			&& false === strpos( $host, 'fb.watch' )
			&& false === strpos( $host, 'fb.com' )
		) {
			return null;
		}
		return $url;
	}

	/**
	 * Normalize X / Twitter status URL.
	 *
	 * @param string $url Raw URL.
	 * @return string|null
	 */
	public static function normalize_x_url( $url ) {
		$url = trim( $url );

		// twitter.com or x.com status.
		if ( preg_match( '#(?:twitter|x)\.com/([^/]+)/status/(\d+)#i', $url, $m ) ) {
			return 'https://twitter.com/' . $m[1] . '/status/' . $m[2];
		}

		return null;
	}

	/**
	 * Normalize Instagram post / reel URL.
	 *
	 * @param string $url Raw URL.
	 * @return string|null
	 */
	public static function normalize_instagram_url( $url ) {
		$url = trim( $url );

		// https://www.instagram.com/p/CODE/ or /reel/CODE/ or /tv/CODE/
		if ( preg_match( '#instagram\.com/(p|reel|reels|tv)/([A-Za-z0-9_-]+)#i', $url, $m ) ) {
			$type = strtolower( $m[1] );
			if ( 'reels' === $type ) {
				$type = 'reel';
			}
			return 'https://www.instagram.com/' . $type . '/' . $m[2] . '/';
		}

		return null;
	}

	/**
	 * TikTok video embed.
	 *
	 * Prefer the official player iframe with an explicit video ID and autoplay off.
	 * (Empty data-video-id made embed.js load embed/v2/ with no ID → “unavailable”.)
	 *
	 * @param string $url Video URL.
	 * @return string HTML.
	 */
	public static function tiktok_video( $url ) {
		$canonical = self::normalize_tiktok_url( $url );
		if ( ! $canonical ) {
			return '';
		}

		$cite     = esc_url( $canonical );
		$video_id = self::extract_tiktok_video_id( $canonical );

		// Best path: direct player iframe with video ID — autoplay disabled.
		if ( $video_id ) {
			// player/v1 supports autoplay=0; embed/v2 often starts muted autoplay on scroll.
			$embed_src = add_query_arg(
				array(
					'autoplay'           => '0',
					'loop'               => '0',
					'music_info'         => '1',
					'description'        => '1',
					'progress_bar'       => '1',
					'play_button'        => '1',
					'volume_control'     => '1',
					'fullscreen_button'  => '1',
					'timestamp'          => '0',
					'closed_caption'     => '0',
					'rel'                => '0',
				),
				'https://www.tiktok.com/player/v1/' . rawurlencode( $video_id )
			);

			return sprintf(
				'<div class="uucg-itl-card uucg-itl-card--tiktok" data-platform="tiktok" data-video-id="%1$s">
					<div class="uucg-itl-card__media uucg-itl-card__media--tiktok">
						<iframe
							class="uucg-itl-tiktok-iframe"
							src="%2$s"
							title="%3$s"
							loading="lazy"
							scrolling="no"
							allow="encrypted-media; fullscreen; picture-in-picture; accelerometer; clipboard-write; gyroscope"
							allowfullscreen
							referrerpolicy="strict-origin-when-cross-origin"
						></iframe>
						<p class="uucg-itl-tiktok-fallback">
							<a href="%4$s" target="_blank" rel="noopener noreferrer">%5$s</a>
						</p>
					</div>
				</div>',
				esc_attr( $video_id ),
				esc_url( $embed_src ),
				esc_attr__( 'TikTok video', 'uucg-in-the-loop' ),
				$cite,
				esc_html__( 'Open on TikTok', 'uucg-in-the-loop' )
			);
		}

		// Short links (vm.tiktok.com) — blockquote for embed.js to resolve.
		// Note: third-party short-link hydration may still autoplay; prefer full /video/ URLs.
		return sprintf(
			'<div class="uucg-itl-card uucg-itl-card--tiktok" data-platform="tiktok">
				<div class="uucg-itl-card__media uucg-itl-card__media--tiktok">
					<blockquote class="tiktok-embed" cite="%1$s" data-video-id="" data-autoplay="0" style="max-width:605px;min-width:325px;">
						<section>
							<a target="_blank" rel="noopener noreferrer" href="%1$s">%2$s</a>
						</section>
					</blockquote>
				</div>
			</div>',
			$cite,
			esc_html__( 'Watch on TikTok', 'uucg-in-the-loop' )
		);
	}

	/**
	 * Facebook post / video embed via official plugin.
	 *
	 * Video/reel URLs use fb-video with autoplay off; other posts use fb-post.
	 *
	 * @param string $url Post or video URL.
	 * @return string HTML.
	 */
	public static function facebook_post( $url ) {
		$canonical = self::normalize_facebook_url( $url );
		if ( ! $canonical ) {
			return '';
		}

		$href = esc_url( $canonical );

		// Dedicated video player so we can disable autoplay.
		if ( preg_match( '#/(videos?|reel|watch)/#i', $canonical ) || false !== strpos( $canonical, 'fb.watch' ) ) {
			return sprintf(
				'<div class="uucg-itl-card uucg-itl-card--facebook" data-platform="facebook">
					<div class="uucg-itl-card__media">
						<div class="fb-video"
							data-href="%1$s"
							data-width="auto"
							data-show-text="true"
							data-autoplay="false"
							data-allowfullscreen="true"></div>
					</div>
				</div>',
				$href
			);
		}

		return sprintf(
			'<div class="uucg-itl-card uucg-itl-card--facebook" data-platform="facebook">
				<div class="uucg-itl-card__media">
					<div class="fb-post" data-href="%1$s" data-width="auto" data-show-text="true"></div>
				</div>
			</div>',
			$href
		);
	}

	/**
	 * Facebook Page Plugin (timeline-style).
	 *
	 * @param string $page_url Facebook page URL.
	 * @param int    $height   Pixel height.
	 * @param string $tabs     Comma list: timeline, events, messages.
	 * @return string HTML.
	 */
	public static function facebook_page_plugin( $page_url, $height = 700, $tabs = 'timeline' ) {
		$page_url = esc_url( $page_url );
		if ( ! $page_url ) {
			return '';
		}

		$height = max( 300, absint( $height ) );
		$tabs   = sanitize_text_field( $tabs );

		return sprintf(
			'<div class="uucg-itl-card uucg-itl-card--facebook uucg-itl-card--page" data-platform="facebook">
				<div class="uucg-itl-card__media">
					<div class="fb-page"
						data-href="%1$s"
						data-tabs="%2$s"
						data-width="500"
						data-height="%3$d"
						data-small-header="false"
						data-adapt-container-width="true"
						data-hide-cover="false"
						data-show-facepile="true">
						<blockquote cite="%1$s" class="fb-xfbml-parse-ignore">
							<a href="%1$s">%4$s</a>
						</blockquote>
					</div>
				</div>
			</div>',
			$page_url,
			esc_attr( $tabs ),
			$height,
			esc_html__( 'View on Facebook', 'uucg-in-the-loop' )
		);
	}

	/**
	 * Instagram post / reel embed (official blockquote + embed.js).
	 *
	 * @param string $url Post or reel URL.
	 * @return string HTML.
	 */
	public static function instagram_post( $url ) {
		$canonical = self::normalize_instagram_url( $url );
		if ( ! $canonical ) {
			return '';
		}

		$href = esc_url( $canonical );

		return sprintf(
			'<div class="uucg-itl-card uucg-itl-card--instagram" data-platform="instagram">
				<div class="uucg-itl-card__media">
					<blockquote
						class="instagram-media"
						data-instgrm-permalink="%1$s"
						data-instgrm-version="14"
						style="background:#FFF;border:0;border-radius:3px;margin:1px;max-width:540px;min-width:280px;padding:0;width:99.375%%;">
						<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>
					</blockquote>
				</div>
			</div>',
			$href,
			esc_html__( 'View on Instagram', 'uucg-in-the-loop' )
		);
	}

	/**
	 * X / Twitter embedded tweet.
	 *
	 * @param string $url Status URL.
	 * @return string HTML.
	 */
	public static function x_tweet( $url ) {
		$canonical = self::normalize_x_url( $url );
		if ( ! $canonical ) {
			return '';
		}

		$href = esc_url( $canonical );

		return sprintf(
			'<div class="uucg-itl-card uucg-itl-card--x" data-platform="x">
				<div class="uucg-itl-card__media">
					<blockquote class="twitter-tweet" data-dnt="true" data-theme="light">
						<a href="%1$s">%2$s</a>
					</blockquote>
				</div>
			</div>',
			$href,
			esc_html__( 'View on X', 'uucg-in-the-loop' )
		);
	}

	/**
	 * X / Twitter profile timeline embed.
	 *
	 * @param string $username Without @.
	 * @param int    $height   Pixel height.
	 * @return string HTML.
	 */
	public static function x_timeline( $username, $height = 700 ) {
		$username = sanitize_user( ltrim( (string) $username, '@' ), true );
		if ( ! $username ) {
			return '';
		}

		$height = max( 300, absint( $height ) );
		$href   = 'https://twitter.com/' . rawurlencode( $username );

		return sprintf(
			'<div class="uucg-itl-card uucg-itl-card--x uucg-itl-card--timeline" data-platform="x">
				<div class="uucg-itl-card__media">
					<a class="twitter-timeline"
						data-height="%1$d"
						data-theme="light"
						data-chrome="nofooter noborders transparent"
						href="%2$s">
						%3$s
					</a>
				</div>
			</div>',
			$height,
			esc_url( $href ),
			/* translators: %s: X username */
			esc_html( sprintf( __( 'Posts by @%s', 'uucg-in-the-loop' ), $username ) )
		);
	}

	/**
	 * Whether a platform has any configured embeddable content.
	 *
	 * @param string $platform Platform key.
	 * @param array  $settings Plugin settings.
	 * @return bool
	 */
	public static function platform_has_content( $platform, $settings ) {
		return '' !== self::render_platform_feed( $platform, $settings );
	}

	/**
	 * Whether a platform is “linked” (profile and/or content configured).
	 * Used to hide unconfigured social tabs on the frontend.
	 *
	 * @param string $platform Platform key.
	 * @param array  $settings Plugin settings.
	 * @return bool
	 */
	public static function platform_is_linked( $platform, $settings ) {
		switch ( $platform ) {
			case 'tiktok':
				return ! empty( $settings['tiktok_urls'] )
					|| ! empty( $settings['tiktok_username'] )
					|| ! empty( $settings['tiktok_profile'] );

			case 'instagram':
				return ! empty( $settings['instagram_urls'] )
					|| ! empty( $settings['instagram_username'] )
					|| ! empty( $settings['instagram_profile'] );

			case 'facebook':
				return ! empty( $settings['facebook_page'] )
					|| ! empty( $settings['facebook_urls'] );

			case 'x':
				return ! empty( $settings['x_username'] )
					|| ! empty( $settings['x_urls'] );
		}

		return false;
	}

	/**
	 * Build a full tab grid of cards for a platform.
	 *
	 * @param string $platform tiktok|instagram|facebook|x.
	 * @param array  $settings Plugin settings.
	 * @return string HTML (empty if nothing to show).
	 */
	public static function render_platform_feed( $platform, $settings ) {
		$html = '';

		switch ( $platform ) {
			case 'tiktok':
				$urls = self::parse_urls( $settings['tiktok_urls'] ?? '' );
				foreach ( $urls as $url ) {
					$html .= self::tiktok_video( $url );
				}
				break;

			case 'instagram':
				$urls = self::parse_urls( $settings['instagram_urls'] ?? '' );
				foreach ( $urls as $url ) {
					$html .= self::instagram_post( $url );
				}
				break;

			case 'facebook':
				if ( ! empty( $settings['facebook_use_page_plugin'] ) && ! empty( $settings['facebook_page'] ) ) {
					$html .= self::facebook_page_plugin(
						$settings['facebook_page'],
						(int) ( $settings['timeline_height'] ?? 700 ),
						$settings['page_plugin_tabs'] ?? 'timeline'
					);
				}
				$urls = self::parse_urls( $settings['facebook_urls'] ?? '' );
				foreach ( $urls as $url ) {
					$html .= self::facebook_post( $url );
				}
				break;

			case 'x':
				if ( ! empty( $settings['x_use_timeline'] ) && ! empty( $settings['x_username'] ) ) {
					$html .= self::x_timeline(
						$settings['x_username'],
						(int) ( $settings['timeline_height'] ?? 700 )
					);
				}
				$urls = self::parse_urls( $settings['x_urls'] ?? '' );
				foreach ( $urls as $url ) {
					$html .= self::x_tweet( $url );
				}
				break;
		}

		return $html;
	}

	/**
	 * Profile / follow URL for a platform header CTA.
	 *
	 * @param string $platform Platform key.
	 * @param array  $settings Settings.
	 * @return array{url:string,label:string}|null
	 */
	public static function profile_link( $platform, $settings ) {
		switch ( $platform ) {
			case 'tiktok':
				if ( ! empty( $settings['tiktok_profile'] ) ) {
					return array(
						'url'   => esc_url( $settings['tiktok_profile'] ),
						'label' => __( 'Follow on TikTok', 'uucg-in-the-loop' ),
					);
				}
				if ( ! empty( $settings['tiktok_username'] ) ) {
					$user = ltrim( $settings['tiktok_username'], '@' );
					return array(
						'url'   => esc_url( 'https://www.tiktok.com/@' . rawurlencode( $user ) ),
						'label' => __( 'Follow on TikTok', 'uucg-in-the-loop' ),
					);
				}
				break;

			case 'instagram':
				if ( ! empty( $settings['instagram_profile'] ) ) {
					return array(
						'url'   => esc_url( $settings['instagram_profile'] ),
						'label' => __( 'Follow on Instagram', 'uucg-in-the-loop' ),
					);
				}
				if ( ! empty( $settings['instagram_username'] ) ) {
					$user = ltrim( $settings['instagram_username'], '@' );
					return array(
						'url'   => esc_url( 'https://www.instagram.com/' . rawurlencode( $user ) . '/' ),
						'label' => __( 'Follow on Instagram', 'uucg-in-the-loop' ),
					);
				}
				break;

			case 'facebook':
				if ( ! empty( $settings['facebook_page'] ) ) {
					return array(
						'url'   => esc_url( $settings['facebook_page'] ),
						'label' => __( 'Follow on Facebook', 'uucg-in-the-loop' ),
					);
				}
				break;

			case 'x':
				if ( ! empty( $settings['x_username'] ) ) {
					$user = ltrim( $settings['x_username'], '@' );
					return array(
						'url'   => esc_url( 'https://x.com/' . rawurlencode( $user ) ),
						'label' => __( 'Follow on X', 'uucg-in-the-loop' ),
					);
				}
				break;
		}

		return null;
	}
}
