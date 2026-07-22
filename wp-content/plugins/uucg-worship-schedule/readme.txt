=== UUCG Worship Schedule ===
Contributors: uucg
Tags: worship, schedule, calendar, google-sheets, church
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pulls the worship calendar from a Google Sheet and displays topics, seasonal categories, and speakers in list + calendar views.

== Description ==

**UUCG Worship Schedule** syncs a public Google Spreadsheet (CSV export) and renders:

* **List view** — date cards with topic, celebrant, worship associate, holidays, season chips
* **Calendar view** — month grid colored by liturgical season; tap a Sunday for details
* **Season filters** — Autumn / Winter / Spring / Summer
* **Celebrant filters** — filter the list and calendar by speaker (stacks with season)

Default sheet columns (UUCG 2026–27 layout):

Theme · Date · Celebrant · Worship Associate · Topic · Holidays · music · hymns · RE · summary

= Shortcode =

`[worship_schedule]`

Optional attributes: `default_view`, `show_past`, `show_empty`, `show_associate`, `show_holidays`, `show_summary`, `quarter`, `view`, `title`, `subtitle`, `show_header`.

== Installation ==

1. Activate the plugin.
2. Confirm the sheet is shared **Anyone with the link can view**.
3. Settings → Worship Schedule (pre-filled with the UUCG sheet).
4. Click **Refresh from Google Sheets now**.
5. Add `[worship_schedule]` to a page.

== Changelog ==

= 1.0.5 =
* Season and celebrant filters are compact dropdowns on one toolbar row.

= 1.0.4 =
* Filter services by celebrant (works with season filters in list and calendar views).

= 1.0.0 =
* Initial release.
