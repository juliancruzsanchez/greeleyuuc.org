=== UUCG Team ===
Contributors: UUCG
Tags: uucg, team, staff, board, minister
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Renders the minister and Board of Trustees on the About Us page.

== Shortcode ==

Use `[uucg_team]` in any post, page, or widget.

No attributes.

== Customizing the roster ==

The team data (minister + board members) is hard-coded in `UUCG_Team::team_data()` inside `uucg-team.php`. Edit that method to swap in your own congregation. Each board entry is a `[name, role, email]` triple.

The minister's portrait is `assets/images/minister.png`. Replace the file with your own (keep the filename, or update the `photo` key in `team_data()`).

== Changelog ==

= 1.0.0 =
  Initial release.
