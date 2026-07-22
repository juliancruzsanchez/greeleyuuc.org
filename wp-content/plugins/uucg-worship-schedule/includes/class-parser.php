<?php
/**
 * Parse Google Sheet CSV rows into structured services.
 *
 * @package UUCG_Worship_Schedule
 */

defined( 'ABSPATH' ) || exit;

/**
 * Spreadsheet parser.
 */
class UUCG_WS_Parser {

	/**
	 * Known column header aliases → internal keys.
	 *
	 * @var array<string,string>
	 */
	private static $header_map = array(
		'theme'              => 'theme',
		'liturgical theme'   => 'theme',
		'quarter'            => 'theme',
		'category'           => 'theme',
		'date'               => 'date',
		'celebrant'          => 'celebrant',
		'speaker'            => 'celebrant',
		'preacher'           => 'celebrant',
		'worship associate'  => 'associate',
		'associate'          => 'associate',
		'topic'              => 'topic',
		'title'              => 'topic',
		'holidays'           => 'holidays',
		'holiday'            => 'holidays',
		'music'              => 'music',
		'hymn 1'             => 'hymn1',
		'hymn 2'             => 'hymn2',
		'hymn 3'             => 'hymn3',
		're'                 => 're',
		'title and summary of your service for newsletter/preservice slideshow' => 'summary',
		'summary'            => 'summary',
		'description'        => 'summary',
	);

	/**
	 * Parse raw CSV string.
	 *
	 * @param string $csv Raw CSV.
	 * @return array{year_theme:string,services:array<int,array>}
	 */
	public static function parse_csv( $csv ) {
		$rows = self::csv_to_rows( $csv );
		if ( empty( $rows ) ) {
			return array(
				'year_theme' => '',
				'services'   => array(),
			);
		}

		// First cell of header row often holds the year theme.
		$header_row   = $rows[0];
		$year_theme   = self::extract_year_theme( $header_row );
		$column_index = self::map_headers( $header_row );

		// Fallback positional map matching the known UUCG sheet layout.
		if ( empty( $column_index['date'] ) && empty( $column_index['topic'] ) ) {
			$column_index = array(
				'theme'     => 0,
				'date'      => 1,
				'celebrant' => 2,
				'associate' => 3,
				'topic'     => 4,
				'holidays'  => 5,
				'music'     => 7,
				'hymn1'     => 8,
				'hymn2'     => 9,
				'hymn3'     => 10,
				're'        => 11,
				'summary'   => 12,
			);
		}

		$services    = array();
		$last_theme  = '';
		$data_rows   = array_slice( $rows, 1 );

		foreach ( $data_rows as $row ) {
			if ( ! is_array( $row ) || self::row_is_empty( $row ) ) {
				continue;
			}

			$date_raw = self::cell( $row, $column_index, 'date' );
			$date_iso = self::normalize_date( $date_raw );
			if ( ! $date_iso ) {
				continue;
			}

			$theme = self::cell( $row, $column_index, 'theme' );
			// Ignore the year-theme header text if it leaked into a data cell.
			if ( $theme && $year_theme && strcasecmp( $theme, $year_theme ) === 0 ) {
				$theme = '';
			}
			if ( '' !== $theme ) {
				$last_theme = $theme;
			} else {
				$theme = $last_theme;
			}
			// Sundays before the first seasonal quarter row.
			if ( '' === $theme ) {
				$theme = __( 'Start of church year', 'uucg-worship-schedule' );
			}

			$topic      = self::cell( $row, $column_index, 'topic' );
			$celebrant  = self::clean_person( self::cell( $row, $column_index, 'celebrant' ) );
			$associate  = self::clean_person( self::cell( $row, $column_index, 'associate' ) );
			$holidays   = self::cell( $row, $column_index, 'holidays' );
			$summary    = self::cell( $row, $column_index, 'summary' );
			$music      = self::cell( $row, $column_index, 'music' );
			$re         = self::cell( $row, $column_index, 're' );

			$quarter_key = self::quarter_slug( $theme );

			$services[] = array(
				'date'         => $date_iso,
				'date_display' => self::format_display_date( $date_iso ),
				'date_short'   => self::format_short_date( $date_iso ),
				'weekday'      => self::format_weekday( $date_iso ),
				'theme'        => $theme,
				'quarter'      => $quarter_key,
				'quarter_label'=> self::quarter_label( $theme, $quarter_key ),
				'topic'        => $topic,
				'celebrant'    => $celebrant,
				'associate'    => $associate,
				'holidays'     => $holidays,
				'summary'      => $summary,
				'music'        => $music,
				're'           => $re,
				'hymns'        => array_filter(
					array(
						self::cell( $row, $column_index, 'hymn1' ),
						self::cell( $row, $column_index, 'hymn2' ),
						self::cell( $row, $column_index, 'hymn3' ),
					)
				),
				'is_tbd'       => self::is_tbd_speaker( $celebrant ),
				'has_content'  => ( '' !== $topic || '' !== $celebrant || '' !== $holidays || '' !== $summary ),
			);
		}

		// Sort chronologically.
		usort(
			$services,
			function ( $a, $b ) {
				return strcmp( $a['date'], $b['date'] );
			}
		);

		return array(
			'year_theme' => $year_theme,
			'services'   => $services,
		);
	}

	/**
	 * @param string $csv CSV text.
	 * @return array<int,array<int,string>>
	 */
	private static function csv_to_rows( $csv ) {
		$csv = (string) $csv;
		// Strip BOM.
		$csv = preg_replace( '/^\xEF\xBB\xBF/', '', $csv );
		if ( '' === trim( $csv ) ) {
			return array();
		}

		$stream = fopen( 'php://temp', 'r+' );
		if ( ! $stream ) {
			return array();
		}
		fwrite( $stream, $csv );
		rewind( $stream );

		$rows = array();
		while ( ( $data = fgetcsv( $stream ) ) !== false ) {
			$rows[] = array_map(
				function ( $cell ) {
					return is_string( $cell ) ? trim( $cell ) : '';
				},
				$data
			);
		}
		fclose( $stream );

		return $rows;
	}

	/**
	 * @param array $header_row First CSV row.
	 * @return string
	 */
	private static function extract_year_theme( $header_row ) {
		$first = isset( $header_row[0] ) ? trim( $header_row[0] ) : '';
		// Strip trailing "Theme" label noise if duplicated awkwardly.
		if ( $first && false === stripos( $first, 'date' ) ) {
			return $first;
		}
		return '';
	}

	/**
	 * Map header labels to column indexes.
	 *
	 * @param array $header_row Header cells.
	 * @return array<string,int>
	 */
	private static function map_headers( $header_row ) {
		$map = array();
		foreach ( $header_row as $i => $label ) {
			$key = strtolower( trim( (string) $label ) );
			$key = preg_replace( '/\s+/', ' ', $key );
			// Year theme cell is not a real column header.
			if ( 0 === $i && false === strpos( $key, 'date' ) && ! isset( self::$header_map[ $key ] ) ) {
				// Still allow theme column via positional fallback later.
				if ( false !== strpos( $key, 'theme' ) || false !== strpos( $key, 'liturgical' ) ) {
					$map['theme'] = $i;
				}
				continue;
			}
			if ( isset( self::$header_map[ $key ] ) ) {
				$map[ self::$header_map[ $key ] ] = $i;
			}
		}
		return $map;
	}

	/**
	 * @param array  $row Row cells.
	 * @param array  $index Column map.
	 * @param string $key  Field key.
	 * @return string
	 */
	private static function cell( $row, $index, $key ) {
		if ( ! isset( $index[ $key ] ) ) {
			return '';
		}
		$i = $index[ $key ];
		return isset( $row[ $i ] ) ? trim( (string) $row[ $i ] ) : '';
	}

	/**
	 * @param array $row Row.
	 * @return bool
	 */
	private static function row_is_empty( $row ) {
		foreach ( $row as $cell ) {
			if ( '' !== trim( (string) $cell ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Normalize many date formats to Y-m-d.
	 *
	 * @param string $raw Raw date.
	 * @return string|null
	 */
	public static function normalize_date( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return null;
		}

		// MM/DD/YYYY or M/D/YYYY.
		if ( preg_match( '#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $raw, $m ) ) {
			return sprintf( '%04d-%02d-%02d', (int) $m[3], (int) $m[1], (int) $m[2] );
		}

		// YYYY-MM-DD.
		if ( preg_match( '#^(\d{4})-(\d{2})-(\d{2})$#', $raw, $m ) ) {
			return sprintf( '%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3] );
		}

		// Excel serial? unlikely in CSV export.
		$ts = strtotime( $raw );
		if ( $ts ) {
			return gmdate( 'Y-m-d', $ts );
		}

		return null;
	}

	/**
	 * @param string $iso Y-m-d.
	 * @return string
	 */
	public static function format_display_date( $iso ) {
		$ts = strtotime( $iso . ' 12:00:00' );
		return $ts ? date_i18n( 'l, F j, Y', $ts ) : $iso;
	}

	/**
	 * @param string $iso Y-m-d.
	 * @return string
	 */
	public static function format_short_date( $iso ) {
		$ts = strtotime( $iso . ' 12:00:00' );
		return $ts ? date_i18n( 'M j', $ts ) : $iso;
	}

	/**
	 * @param string $iso Y-m-d.
	 * @return string
	 */
	public static function format_weekday( $iso ) {
		$ts = strtotime( $iso . ' 12:00:00' );
		return $ts ? date_i18n( 'D', $ts ) : '';
	}

	/**
	 * @param string $name Person name.
	 * @return string
	 */
	private static function clean_person( $name ) {
		$name = trim( $name );
		// Collapse internal whitespace.
		$name = preg_replace( '/\s+/', ' ', $name );
		return $name;
	}

	/**
	 * @param string $name Celebrant value.
	 * @return bool
	 */
	private static function is_tbd_speaker( $name ) {
		$n = strtolower( trim( $name ) );
		return in_array( $n, array( '', '??', '?', 'tbd', 'tba', 'open', 'n/a', 'na' ), true );
	}

	/**
	 * Map theme string to quarter slug for filtering/colors.
	 *
	 * @param string $theme Theme cell.
	 * @return string autumn|winter|spring|summer|general
	 */
	public static function quarter_slug( $theme ) {
		$t = strtolower( (string) $theme );
		if ( false !== strpos( $t, 'autumn' ) || false !== strpos( $t, 'fall' ) || false !== strpos( $t, 'compost' ) ) {
			return 'autumn';
		}
		if ( false !== strpos( $t, 'winter' ) || false !== strpos( $t, 'rooting' ) ) {
			return 'winter';
		}
		if ( false !== strpos( $t, 'spring' ) || false !== strpos( $t, 'germinat' ) ) {
			return 'spring';
		}
		if ( false !== strpos( $t, 'summer' ) || false !== strpos( $t, 'flourish' ) ) {
			return 'summer';
		}
		if ( false !== strpos( $t, 'start of church' ) || false !== strpos( $t, 'ingathering' ) ) {
			return 'opening';
		}
		return 'general';
	}

	/**
	 * Short quarter chip label.
	 *
	 * @param string $theme Full theme.
	 * @param string $slug  Quarter slug.
	 * @return string
	 */
	public static function quarter_label( $theme, $slug ) {
		$labels = array(
			'autumn'  => __( 'Autumn · Composting', 'uucg-worship-schedule' ),
			'winter'  => __( 'Winter · Rooting', 'uucg-worship-schedule' ),
			'spring'  => __( 'Spring · Germinating', 'uucg-worship-schedule' ),
			'summer'  => __( 'Summer · Flourishing', 'uucg-worship-schedule' ),
			'opening' => __( 'Start of church year', 'uucg-worship-schedule' ),
			'general' => __( 'Worship', 'uucg-worship-schedule' ),
		);

		// Prefer richer labels extracted from theme when present.
		if ( $theme && preg_match( '/^(Autumn|Winter|Spring|Summer)\s+Quarter:\s*([^(]+)/i', $theme, $m ) ) {
			return trim( $m[1] . ' · ' . trim( $m[2] ) );
		}

		return isset( $labels[ $slug ] ) ? $labels[ $slug ] : $labels['general'];
	}
}
