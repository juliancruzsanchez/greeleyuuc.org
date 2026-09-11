/*!
 * UUCG Fair Share Calculator — frontend live calculator.
 *
 * Reads the UUA Fair Share Giving Guide data embedded in the page (via
 * a global from PHP) and updates the 4 tier cards as the user types or
 * drags the income slider. The annual value and per-tier % / monthly /
 * annual pledge update in real time.
 */
( function () {
	'use strict';

	// Tier meta (label, range, color) is rendered by the PHP shortcode.
	// The monthly→% mapping is computed from a small lookup table that
	// matches the UUA Fair Share Giving Guide exactly.
	var FAIR_SHARE = [
		{ monthly: 1000,  tiers: { supporter: 2, sustainer: 3, visionary: 5,  transformer: 10 } },
		{ monthly: 1500,  tiers: { supporter: 2, sustainer: 3, visionary: 5,  transformer: 10 } },
		{ monthly: 2000,  tiers: { supporter: 2, sustainer: 3, visionary: 5,  transformer: 10 } },
		{ monthly: 3000,  tiers: { supporter: 2, sustainer: 3, visionary: 5,  transformer: 10 } },
		{ monthly: 4000,  tiers: { supporter: 3, sustainer: 4, visionary: 5,  transformer: 10 } },
		{ monthly: 6500,  tiers: { supporter: 3, sustainer: 4, visionary: 6,  transformer: 10 } },
		{ monthly: 8500,  tiers: { supporter: 3, sustainer: 5, visionary: 6,  transformer: 10 } },
		{ monthly: 10000, tiers: { supporter: 3, sustainer: 5, visionary: 6,  transformer: 10 } },
		{ monthly: 12500, tiers: { supporter: 4, sustainer: 5, visionary: 6,  transformer: 10 } },
		{ monthly: 17000, tiers: { supporter: 4, sustainer: 6, visionary: 7,  transformer: 10 } },
		{ monthly: 25000, tiers: { supporter: 5, sustainer: 6, visionary: 8,  transformer: 10 } },
		{ monthly: 40000, tiers: { supporter: 6, sustainer: 7, visionary: 9,  transformer: 10 } }
	];

	function suggestion( monthly ) {
		monthly = Math.max( 0, Number( monthly ) || 0 );
		if ( monthly <= 0 ) {
			monthly = FAIR_SHARE[ 0 ].monthly;
		}
		var first = FAIR_SHARE[ 0 ];
		var last  = FAIR_SHARE[ FAIR_SHARE.length - 1 ];
		if ( monthly < first.monthly ) {
			return {
				monthly: first.monthly,
				annual: first.monthly * 12,
				tiers: first.tiers
			};
		}
		if ( monthly >= last.monthly ) {
			return {
				monthly: monthly,
				annual: monthly * 12,
				tiers: last.tiers
			};
		}
		for ( var i = 0; i < FAIR_SHARE.length - 1; i++ ) {
			var lo = FAIR_SHARE[ i ];
			var hi = FAIR_SHARE[ i + 1 ];
			if ( monthly >= lo.monthly && monthly < hi.monthly ) {
				var span = hi.monthly - lo.monthly;
				var t    = ( monthly - lo.monthly ) / span;
				var tiers = {};
				Object.keys( lo.tiers ).forEach( function ( key ) {
					tiers[ key ] = +( lo.tiers[ key ] + ( hi.tiers[ key ] - lo.tiers[ key ] ) * t ).toFixed( 1 );
				} );
				var annual = Math.round( lo.monthly * 12 + ( hi.monthly * 12 - lo.monthly * 12 ) * t );
				return { monthly: monthly, annual: annual, tiers: tiers };
			}
		}
		return { monthly: monthly, annual: monthly * 12, tiers: last.tiers };
	}

	function fmtMoney( n ) {
		n = Math.round( n );
		if ( n >= 1000 ) {
			return n.toLocaleString( 'en-US' );
		}
		return String( n );
	}

	function update( root ) {
		var input = root.querySelector( '#uucg-fs-income' );
		if ( ! input ) {
			return;
		}
		var monthly = Number( input.value ) || 0;
		var s = suggestion( monthly );

		// Annual income.
		var annualEl = root.querySelector( '#uucg-fs-annual' );
		if ( annualEl ) {
			annualEl.textContent = '$' + fmtMoney( s.annual );
		}

		// Per-tier cards.
		var tiers = root.querySelectorAll( '.uucg-fs__tier' );
		tiers.forEach( function ( card ) {
			var key = card.getAttribute( 'data-tier' );
			var pct = s.tiers[ key ];
			if ( pct === undefined ) {
				return;
			}
			var monthlyPledge = s.monthly * ( pct / 100 );
			var annualPledge  = monthlyPledge * 12;

			var pctEl = card.querySelector( '[data-field="pct"]' );
			var plgEl = card.querySelector( '[data-field="pledge"]' );
			var annEl = card.querySelector( '[data-field="annual"]' );
			if ( pctEl ) {
				pctEl.textContent = Number.isInteger( pct ) ? pct : pct.toFixed( 1 );
			}
			if ( plgEl ) {
				plgEl.textContent = fmtMoney( monthlyPledge );
			}
			if ( annEl ) {
				annEl.textContent = fmtMoney( annualPledge );
			}
		} );
	}

	function init( root ) {
		var input = root.querySelector( '#uucg-fs-income' );
		var range = root.querySelector( '.uucg-fs__range' );
		if ( ! input ) {
			return;
		}

		function recompute() {
			update( root );
		}

		// Number input drives everything.
		input.addEventListener( 'input', function () {
			if ( range ) {
				range.value = input.value;
			}
			recompute();
		} );

		// Range syncs to the number input.
		if ( range ) {
			range.addEventListener( 'input', function () {
				input.value = range.value;
				recompute();
			} );
		}

		recompute();
	}

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	ready( function () {
		var roots = document.querySelectorAll( '.uucg-fs' );
		roots.forEach( init );
	} );
} )();
