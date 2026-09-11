=== UUCG Fair Share Calculator ===
Contributors: UUCG
Tags: uucg, fair share, pledge, calculator, giving
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Interactive Fair Share contribution guide for the Unitarian Universalist Church of Greeley. Visitors enter their monthly income and see the suggested pledge at four generosity tiers (Supporter, Sustainer, Visionary, Transformer), all based on the UUA Fair Share Giving Guide.

== Shortcode ==

Use `[fair_share_calculator]` in any post, page, or widget.

Attributes:
  - `initial` (int)  — starting monthly income. Default 5000.
  - `source`  (str)  — optional caption above the calculator. Default: "Based on the UUA Fair Share Contribution Guide."

Example:
  [fair_share_calculator initial="6500"]

== Changelog ==

= 1.0.0 =
  Initial release.
