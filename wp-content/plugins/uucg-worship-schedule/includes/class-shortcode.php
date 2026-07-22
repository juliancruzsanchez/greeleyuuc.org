<?php
/**
 * Frontend shortcode: list + calendar views.
 *
 * @package UUCG_Worship_Schedule
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode renderer.
 */
class UUCG_WS_Shortcode {

	/**
	 * Register shortcodes.
	 */
	public static function init() {
		add_shortcode( 'worship_schedule', array( __CLASS__, 'render' ) );
		add_shortcode( 'uucg_worship_schedule', array( __CLASS__, 'render' ) );
	}

	/**
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$settings = UUCG_Worship_Schedule::get_settings();

		$atts = shortcode_atts(
			array(
				'title'          => $settings['title'],
				'subtitle'       => $settings['subtitle'],
				'show_header'    => $settings['show_header'],
				'default_view'   => $settings['default_view'],
				'show_past'      => $settings['show_past'],
				'show_empty'     => $settings['show_empty'],
				'show_associate' => $settings['show_associate'],
				'show_holidays'  => $settings['show_holidays'],
				'show_summary'   => $settings['show_summary'],
				'quarter'        => 'all',
				'view'           => '', // force single view: list|calendar|both
			),
			$atts,
			'worship_schedule'
		);

		$data     = UUCG_WS_Fetcher::get_data();
		$services = UUCG_WS_Fetcher::filter_services(
			$data['services'],
			array(
				'show_past'  => (bool) $atts['show_past'],
				'show_empty' => (bool) $atts['show_empty'],
				'quarter'    => $atts['quarter'],
			)
		);

		$default_view = sanitize_key( $atts['default_view'] );
		if ( ! in_array( $default_view, array( 'list', 'calendar' ), true ) ) {
			$default_view = 'list';
		}

		$force_view = sanitize_key( $atts['view'] );
		if ( 'list' === $force_view || 'calendar' === $force_view ) {
			$default_view = $force_view;
			$show_toggle  = false;
		} else {
			$show_toggle = true;
		}

		$uid = 'uucg-ws-' . wp_unique_id();

		// Calendar months covered by filtered services.
		$months = self::months_from_services( $services );
		if ( empty( $months ) ) {
			$today  = UUCG_WS_Fetcher::today_iso();
			$months = array( substr( $today, 0, 7 ) );
		}

		// Index services by date for calendar cells.
		$by_date = array();
		foreach ( $services as $svc ) {
			$by_date[ $svc['date'] ] = $svc;
		}

		// Distinct quarters / celebrants present (for filter chips).
		// Use the same service set shown on the page so options match visible rows.
		$quarters   = self::available_quarters( $services );
		$celebrants = self::available_celebrants( $services );

		wp_enqueue_style( 'uucg-ws-frontend' );
		wp_enqueue_script( 'uucg-ws-frontend' );

		wp_localize_script(
			'uucg-ws-frontend',
			'uucgWs',
			array(
				'uid'         => $uid,
				'defaultView' => $default_view,
				'months'      => $months,
				'i18n'        => array(
					'prev' => __( 'Previous month', 'uucg-worship-schedule' ),
					'next' => __( 'Next month', 'uucg-worship-schedule' ),
				),
			)
		);

		ob_start();
		?>
		<section
			class="uucg-ws"
			id="<?php echo esc_attr( $uid ); ?>"
			data-default-view="<?php echo esc_attr( $default_view ); ?>"
			data-show-associate="<?php echo ! empty( $atts['show_associate'] ) ? '1' : '0'; ?>"
			aria-label="<?php echo esc_attr( $atts['title'] ? $atts['title'] : __( 'Worship schedule', 'uucg-worship-schedule' ) ); ?>"
		>
			<?php if ( ! empty( $atts['show_header'] ) && ( $atts['title'] || $atts['subtitle'] || ! empty( $data['year_theme'] ) ) ) : ?>
				<header class="uucg-ws__header">
					<?php if ( ! empty( $data['year_theme'] ) ) : ?>
						<p class="uucg-ws__year-theme"><?php echo esc_html( $data['year_theme'] ); ?></p>
					<?php endif; ?>
					<?php if ( $atts['title'] ) : ?>
						<h2 class="uucg-ws__title"><?php echo esc_html( $atts['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( $atts['subtitle'] ) : ?>
						<p class="uucg-ws__subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
					<?php endif; ?>
					<div class="uucg-ws__rainbow" aria-hidden="true"></div>
				</header>
			<?php endif; ?>

			<?php if ( ! empty( $data['meta']['error'] ) && empty( $services ) ) : ?>
				<div class="uucg-ws__alert" role="alert">
					<?php echo esc_html( $data['meta']['error'] ); ?>
				</div>
			<?php elseif ( ! empty( $data['meta']['using_stale'] ) ) : ?>
				<div class="uucg-ws__alert uucg-ws__alert--soft" role="status">
					<?php esc_html_e( 'Showing the last successfully loaded schedule (live update unavailable).', 'uucg-worship-schedule' ); ?>
				</div>
			<?php endif; ?>

			<div class="uucg-ws__toolbar">
				<?php if ( $show_toggle ) : ?>
					<div class="uucg-ws__view-toggle" role="tablist" aria-label="<?php esc_attr_e( 'Schedule view', 'uucg-worship-schedule' ); ?>">
						<button
							type="button"
							class="uucg-ws__view-btn<?php echo 'list' === $default_view ? ' is-active' : ''; ?>"
							role="tab"
							data-view="list"
							aria-selected="<?php echo 'list' === $default_view ? 'true' : 'false'; ?>"
						>
							<span class="uucg-ws__view-icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/></svg>
							</span>
							<?php esc_html_e( 'List', 'uucg-worship-schedule' ); ?>
						</button>
						<button
							type="button"
							class="uucg-ws__view-btn<?php echo 'calendar' === $default_view ? ' is-active' : ''; ?>"
							role="tab"
							data-view="calendar"
							aria-selected="<?php echo 'calendar' === $default_view ? 'true' : 'false'; ?>"
						>
							<span class="uucg-ws__view-icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
							</span>
							<?php esc_html_e( 'Calendar', 'uucg-worship-schedule' ); ?>
						</button>
					</div>
				<?php endif; ?>

				<?php
				$show_season_filters    = count( $quarters ) > 1 && 'all' === $atts['quarter'];
				$show_celebrant_filters = count( $celebrants ) > 1;
				?>
				<?php if ( $show_season_filters || $show_celebrant_filters ) : ?>
					<div class="uucg-ws__filters">
						<?php if ( $show_season_filters ) : ?>
							<label class="uucg-ws__select-wrap">
								<span class="uucg-ws__filters-label"><?php esc_html_e( 'Season', 'uucg-worship-schedule' ); ?></span>
								<select class="uucg-ws__select" data-filter="quarter" aria-label="<?php esc_attr_e( 'Filter by season', 'uucg-worship-schedule' ); ?>">
									<option value="all" selected><?php esc_html_e( 'All seasons', 'uucg-worship-schedule' ); ?></option>
									<?php foreach ( $quarters as $q_key => $q_label ) : ?>
										<option value="<?php echo esc_attr( $q_key ); ?>"><?php echo esc_html( $q_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						<?php endif; ?>

						<?php if ( $show_celebrant_filters ) : ?>
							<label class="uucg-ws__select-wrap">
								<span class="uucg-ws__filters-label"><?php esc_html_e( 'Celebrant', 'uucg-worship-schedule' ); ?></span>
								<select class="uucg-ws__select" data-filter="celebrant" aria-label="<?php esc_attr_e( 'Filter by celebrant', 'uucg-worship-schedule' ); ?>">
									<option value="all" selected><?php esc_html_e( 'All celebrants', 'uucg-worship-schedule' ); ?></option>
									<?php foreach ( $celebrants as $c_key => $c_label ) : ?>
										<option value="<?php echo esc_attr( $c_key ); ?>"><?php echo esc_html( $c_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( empty( $services ) ) : ?>
				<div class="uucg-ws__empty">
					<p class="uucg-ws__empty-title"><?php esc_html_e( 'No services to show', 'uucg-worship-schedule' ); ?></p>
					<p class="uucg-ws__empty-text"><?php esc_html_e( 'Check the spreadsheet connection in Settings → Worship Schedule, or allow past/empty services.', 'uucg-worship-schedule' ); ?></p>
				</div>
			<?php else : ?>

				<!-- LIST VIEW -->
				<div
					class="uucg-ws__panel uucg-ws__panel--list<?php echo 'list' === $default_view ? ' is-active' : ''; ?>"
					data-panel="list"
					<?php echo 'list' === $default_view ? '' : 'hidden'; ?>
				>
					<div class="uucg-ws__list">
						<?php
						$current_quarter = null;
						foreach ( $services as $svc ) :
							$celebrant_slug = self::celebrant_slug( $svc );
							if ( $current_quarter !== $svc['quarter'] ) :
								$current_quarter = $svc['quarter'];
								?>
								<div class="uucg-ws__season-heading uucg-ws__season-heading--<?php echo esc_attr( $svc['quarter'] ); ?>" data-quarter="<?php echo esc_attr( $svc['quarter'] ); ?>">
									<span class="uucg-ws__season-dot" aria-hidden="true"></span>
									<span class="uucg-ws__season-label"><?php echo esc_html( $svc['quarter_label'] ); ?></span>
									<?php if ( ! empty( $svc['theme'] ) && $svc['theme'] !== $svc['quarter_label'] ) : ?>
										<span class="uucg-ws__season-theme"><?php echo esc_html( self::theme_question( $svc['theme'] ) ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<article
								class="uucg-ws-card uucg-ws-card--<?php echo esc_attr( $svc['quarter'] ); ?><?php echo empty( $svc['has_content'] ) ? ' is-sparse' : ''; ?>"
								data-quarter="<?php echo esc_attr( $svc['quarter'] ); ?>"
								data-celebrant="<?php echo esc_attr( $celebrant_slug ); ?>"
								data-date="<?php echo esc_attr( $svc['date'] ); ?>"
								id="<?php echo esc_attr( $uid . '-svc-' . $svc['date'] ); ?>"
							>
								<div class="uucg-ws-card__date">
									<span class="uucg-ws-card__weekday"><?php echo esc_html( $svc['weekday'] ); ?></span>
									<span class="uucg-ws-card__day"><?php echo esc_html( self::day_number( $svc['date'] ) ); ?></span>
									<span class="uucg-ws-card__month"><?php echo esc_html( self::month_short( $svc['date'] ) ); ?></span>
								</div>
								<div class="uucg-ws-card__body">
									<div class="uucg-ws-card__meta">
										<span class="uucg-ws-card__chip uucg-ws-card__chip--<?php echo esc_attr( $svc['quarter'] ); ?>">
											<?php echo esc_html( $svc['quarter_label'] ); ?>
										</span>
										<?php if ( ! empty( $atts['show_holidays'] ) && ! empty( $svc['holidays'] ) ) : ?>
											<span class="uucg-ws-card__chip uucg-ws-card__chip--holiday">
												<?php echo esc_html( $svc['holidays'] ); ?>
											</span>
										<?php endif; ?>
									</div>

									<h3 class="uucg-ws-card__topic">
										<?php
										if ( ! empty( $svc['topic'] ) ) {
											echo esc_html( $svc['topic'] );
										} else {
											echo esc_html__( 'Worship service', 'uucg-worship-schedule' );
										}
										?>
									</h3>

									<div class="uucg-ws-card__people">
										<div class="uucg-ws-card__person">
											<span class="uucg-ws-card__role"><?php esc_html_e( 'Celebrant', 'uucg-worship-schedule' ); ?></span>
											<span class="uucg-ws-card__name<?php echo ! empty( $svc['is_tbd'] ) ? ' is-tbd' : ''; ?>">
												<?php
												if ( ! empty( $svc['celebrant'] ) && empty( $svc['is_tbd'] ) ) {
													echo esc_html( $svc['celebrant'] );
												} elseif ( ! empty( $svc['celebrant'] ) ) {
													echo esc_html( $svc['celebrant'] );
												} else {
													echo esc_html__( 'To be announced', 'uucg-worship-schedule' );
												}
												?>
											</span>
										</div>
										<?php if ( ! empty( $atts['show_associate'] ) && ! empty( $svc['associate'] ) ) : ?>
											<div class="uucg-ws-card__person">
												<span class="uucg-ws-card__role"><?php esc_html_e( 'Worship Associate', 'uucg-worship-schedule' ); ?></span>
												<span class="uucg-ws-card__name"><?php echo esc_html( $svc['associate'] ); ?></span>
											</div>
										<?php endif; ?>
									</div>

									<?php if ( ! empty( $atts['show_summary'] ) && ! empty( $svc['summary'] ) ) : ?>
										<p class="uucg-ws-card__summary"><?php echo esc_html( $svc['summary'] ); ?></p>
									<?php endif; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
					<p class="uucg-ws__filter-empty" data-filter-empty hidden>
						<?php esc_html_e( 'No services match these filters.', 'uucg-worship-schedule' ); ?>
					</p>
				</div>

				<!-- CALENDAR VIEW -->
				<div
					class="uucg-ws__panel uucg-ws__panel--calendar<?php echo 'calendar' === $default_view ? ' is-active' : ''; ?>"
					data-panel="calendar"
					<?php echo 'calendar' === $default_view ? '' : 'hidden'; ?>
				>
					<div class="uucg-ws__cal-nav">
						<button type="button" class="uucg-ws__cal-btn" data-cal-nav="prev" aria-label="<?php esc_attr_e( 'Previous month', 'uucg-worship-schedule' ); ?>">
							<span aria-hidden="true">‹</span>
						</button>
						<h3 class="uucg-ws__cal-title" data-cal-title></h3>
						<button type="button" class="uucg-ws__cal-btn" data-cal-nav="next" aria-label="<?php esc_attr_e( 'Next month', 'uucg-worship-schedule' ); ?>">
							<span aria-hidden="true">›</span>
						</button>
					</div>

					<?php foreach ( $months as $month_key ) : ?>
						<?php
						list( $y, $m ) = array_map( 'intval', explode( '-', $month_key ) );
						$month_label   = date_i18n( 'F Y', mktime( 12, 0, 0, $m, 1, $y ) );
						$grid          = self::build_month_grid( $y, $m );
						?>
						<div
							class="uucg-ws__month"
							data-month="<?php echo esc_attr( $month_key ); ?>"
							data-month-label="<?php echo esc_attr( $month_label ); ?>"
							hidden
						>
							<div class="uucg-ws__dow" aria-hidden="true">
								<?php
								// Sunday-start week to match church calendar feel.
								$dows = array(
									__( 'Sun', 'uucg-worship-schedule' ),
									__( 'Mon', 'uucg-worship-schedule' ),
									__( 'Tue', 'uucg-worship-schedule' ),
									__( 'Wed', 'uucg-worship-schedule' ),
									__( 'Thu', 'uucg-worship-schedule' ),
									__( 'Fri', 'uucg-worship-schedule' ),
									__( 'Sat', 'uucg-worship-schedule' ),
								);
								foreach ( $dows as $dow ) {
									echo '<span class="uucg-ws__dow-cell">' . esc_html( $dow ) . '</span>';
								}
								?>
							</div>
							<div class="uucg-ws__cal-grid" role="grid" aria-label="<?php echo esc_attr( $month_label ); ?>">
								<?php foreach ( $grid as $cell ) : ?>
									<?php
									if ( empty( $cell['date'] ) ) {
										echo '<div class="uucg-ws__day uucg-ws__day--pad" aria-hidden="true"></div>';
										continue;
									}
									$iso     = $cell['date'];
									$svc     = isset( $by_date[ $iso ] ) ? $by_date[ $iso ] : null;
									$classes = array( 'uucg-ws__day' );
									if ( $svc ) {
										$classes[] = 'uucg-ws__day--service';
										$classes[] = 'uucg-ws__day--' . $svc['quarter'];
										if ( empty( $svc['has_content'] ) ) {
											$classes[] = 'is-sparse';
										}
									}
									if ( $iso === UUCG_WS_Fetcher::today_iso() ) {
										$classes[] = 'is-today';
									}
									?>
									<div
										class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
										role="gridcell"
										data-date="<?php echo esc_attr( $iso ); ?>"
										data-quarter="<?php echo $svc ? esc_attr( $svc['quarter'] ) : ''; ?>"
										data-celebrant="<?php echo $svc ? esc_attr( self::celebrant_slug( $svc ) ) : ''; ?>"
										<?php if ( $svc ) : ?>
											tabindex="0"
											aria-label="<?php echo esc_attr( self::day_aria_label( $svc ) ); ?>"
										<?php endif; ?>
									>
										<span class="uucg-ws__day-num"><?php echo esc_html( (string) $cell['day'] ); ?></span>
										<?php if ( $svc ) : ?>
											<div class="uucg-ws__day-body">
												<span class="uucg-ws__day-topic">
													<?php
													echo esc_html(
														$svc['topic']
															? $svc['topic']
															: __( 'Worship', 'uucg-worship-schedule' )
													);
													?>
												</span>
												<span class="uucg-ws__day-speaker">
													<?php
													if ( ! empty( $svc['celebrant'] ) && empty( $svc['is_tbd'] ) ) {
														echo esc_html( $svc['celebrant'] );
													} elseif ( ! empty( $svc['celebrant'] ) ) {
														echo esc_html( $svc['celebrant'] );
													} else {
														echo esc_html__( 'TBA', 'uucg-worship-schedule' );
													}
													?>
												</span>
												<?php if ( ! empty( $atts['show_holidays'] ) && ! empty( $svc['holidays'] ) ) : ?>
													<span class="uucg-ws__day-holiday"><?php echo esc_html( $svc['holidays'] ); ?></span>
												<?php endif; ?>
											</div>
											<button
												type="button"
												class="uucg-ws__day-open"
												data-open-date="<?php echo esc_attr( $iso ); ?>"
												aria-label="<?php echo esc_attr( sprintf( __( 'Details for %s', 'uucg-worship-schedule' ), $svc['date_display'] ) ); ?>"
											></button>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>

					<!-- Detail drawer for calendar taps -->
					<div class="uucg-ws__drawer" data-drawer hidden>
						<div class="uucg-ws__drawer-backdrop" data-drawer-close></div>
						<div class="uucg-ws__drawer-panel" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $uid ); ?>-drawer-title">
							<button type="button" class="uucg-ws__drawer-close" data-drawer-close aria-label="<?php esc_attr_e( 'Close', 'uucg-worship-schedule' ); ?>">×</button>
							<div class="uucg-ws__drawer-content" data-drawer-content></div>
						</div>
					</div>

					<script type="application/json" class="uucg-ws__data">
						<?php
						echo wp_json_encode(
							array(
								'services' => $by_date,
								'months'   => $months,
							)
						);
						?>
					</script>
				</div>

				<div class="uucg-ws__legend" aria-hidden="true">
					<span class="uucg-ws__legend-item uucg-ws__legend-item--autumn"><?php esc_html_e( 'Autumn', 'uucg-worship-schedule' ); ?></span>
					<span class="uucg-ws__legend-item uucg-ws__legend-item--winter"><?php esc_html_e( 'Winter', 'uucg-worship-schedule' ); ?></span>
					<span class="uucg-ws__legend-item uucg-ws__legend-item--spring"><?php esc_html_e( 'Spring', 'uucg-worship-schedule' ); ?></span>
					<span class="uucg-ws__legend-item uucg-ws__legend-item--summer"><?php esc_html_e( 'Summer', 'uucg-worship-schedule' ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $data['meta']['fetched_at'] ) ) : ?>
				<p class="uucg-ws__updated">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: local datetime */
							__( 'Schedule updated %s', 'uucg-worship-schedule' ),
							date_i18n(
								get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
								(int) $data['meta']['fetched_at'] + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS )
							)
						)
					);
					?>
				</p>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param array $services Services.
	 * @return string[] Y-m keys.
	 */
	private static function months_from_services( array $services ) {
		$months = array();
		foreach ( $services as $svc ) {
			if ( empty( $svc['date'] ) ) {
				continue;
			}
			$months[ substr( $svc['date'], 0, 7 ) ] = true;
		}
		$keys = array_keys( $months );
		sort( $keys );
		return $keys;
	}

	/**
	 * @param array $services All services.
	 * @return array<string,string>
	 */
	private static function available_quarters( array $services ) {
		$out = array();
		foreach ( $services as $svc ) {
			if ( empty( $svc['quarter'] ) || 'general' === $svc['quarter'] ) {
				continue;
			}
			$out[ $svc['quarter'] ] = $svc['quarter_label'];
		}
		// Stable seasonal order.
		$order = array( 'opening', 'autumn', 'winter', 'spring', 'summer' );
		$sorted = array();
		foreach ( $order as $key ) {
			if ( isset( $out[ $key ] ) ) {
				$sorted[ $key ] = $out[ $key ];
			}
		}
		return $sorted;
	}

	/**
	 * Unique celebrants for filter chips (slug => display label).
	 *
	 * @param array $services Services currently shown.
	 * @return array<string,string>
	 */
	private static function available_celebrants( array $services ) {
		$named = array();
		$has_tbd = false;

		foreach ( $services as $svc ) {
			$slug = self::celebrant_slug( $svc );
			if ( 'tbd' === $slug ) {
				$has_tbd = true;
				continue;
			}
			if ( ! isset( $named[ $slug ] ) && ! empty( $svc['celebrant'] ) ) {
				$named[ $slug ] = $svc['celebrant'];
			}
		}

		// Case-insensitive alpha by label.
		uasort(
			$named,
			function ( $a, $b ) {
				return strcasecmp( $a, $b );
			}
		);

		if ( $has_tbd ) {
			$named['tbd'] = __( 'To be announced', 'uucg-worship-schedule' );
		}

		return $named;
	}

	/**
	 * Stable slug for a service celebrant (for data attributes / filters).
	 *
	 * @param array $svc Service row.
	 * @return string
	 */
	private static function celebrant_slug( array $svc ) {
		if ( ! empty( $svc['is_tbd'] ) || empty( $svc['celebrant'] ) ) {
			return 'tbd';
		}
		$slug = sanitize_title( $svc['celebrant'] );
		return $slug ? $slug : 'tbd';
	}

	/**
	 * Build Sunday-start month grid.
	 *
	 * @param int $year  Year.
	 * @param int $month Month 1-12.
	 * @return array<int,array{day:?int,date:?string}>
	 */
	private static function build_month_grid( $year, $month ) {
		$first_wday = (int) date( 'w', mktime( 12, 0, 0, $month, 1, $year ) ); // 0=Sun.
		$days_in    = (int) date( 't', mktime( 12, 0, 0, $month, 1, $year ) );
		$cells      = array();

		for ( $i = 0; $i < $first_wday; $i++ ) {
			$cells[] = array( 'day' => null, 'date' => null );
		}
		for ( $d = 1; $d <= $days_in; $d++ ) {
			$cells[] = array(
				'day'  => $d,
				'date' => sprintf( '%04d-%02d-%02d', $year, $month, $d ),
			);
		}
		// Pad to full weeks.
		while ( count( $cells ) % 7 !== 0 ) {
			$cells[] = array( 'day' => null, 'date' => null );
		}
		return $cells;
	}

	/**
	 * @param string $iso Y-m-d.
	 * @return string
	 */
	private static function day_number( $iso ) {
		return ltrim( substr( $iso, 8, 2 ), '0' );
	}

	/**
	 * @param string $iso Y-m-d.
	 * @return string
	 */
	private static function month_short( $iso ) {
		$ts = strtotime( $iso . ' 12:00:00' );
		return $ts ? date_i18n( 'M', $ts ) : '';
	}

	/**
	 * Extract parenthetical question from theme string.
	 *
	 * @param string $theme Theme.
	 * @return string
	 */
	private static function theme_question( $theme ) {
		if ( preg_match( '/\(([^)]+)\)/', $theme, $m ) ) {
			return $m[1];
		}
		return '';
	}

	/**
	 * @param array $svc Service.
	 * @return string
	 */
	private static function day_aria_label( array $svc ) {
		$parts = array( $svc['date_display'] );
		if ( ! empty( $svc['topic'] ) ) {
			$parts[] = $svc['topic'];
		}
		if ( ! empty( $svc['celebrant'] ) ) {
			$parts[] = sprintf(
				/* translators: %s: speaker name */
				__( 'Celebrant: %s', 'uucg-worship-schedule' ),
				$svc['celebrant']
			);
		}
		return implode( '. ', $parts );
	}
}
