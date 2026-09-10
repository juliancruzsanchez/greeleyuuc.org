# UUCG site lineup plan

## Mirror

Live site (Weebly, `https://www.greeleyuuc.org`) scraped via `scripts/mirror_live.py` on 2026-09-10.

- 321 unique URLs discovered from `sitemap.xml`
- 321 HTML pages saved to `mirror/html/`
- 0 failures
- Top-level inventory: 23 root pages + 198 audio service recordings + 56 archived ministerial musings + 42 hybrid service video recordings + 1 Earth Day subpage + 1 Bonnie & Hollis subpage
- Manifests: `mirror/manifest.json` (full), `mirror/inventory.json` (grouped by section), `mirror/top_pages.json` (23 root pages)

Key finding: live site is still on Weebly (`x-host: ...weebly.net`). All `wp-content/` work in this repo is the new WordPress instance that will replace it.

## Plugin-driven pages

The three `uucg-*` plugins only provide shortcodes, so each one needs a WordPress page to host it. All three pages use the `templates/template-no-title.php` template (body class `uucg-no-title-or-path`), which suppresses the page-title heading and breadcrumbs so the plugin's own header renders cleanly.

| Page | Slug | Post ID | Template | Shortcode | Replaces (live Weebly page) |
|------|------|--------:|----------|-----------|------------------------------|
| In the Loop | `in-the-loop` | 6 | No Title or Path | `[in_the_loop]` | (new) |
| Worship Schedule | `worship-schedule` | 7 | No Title or Path | `[worship_schedule]` | (new) |
| Subscribe — UU Connections | `newsletter` | 8 | No Title or Path | `[uucg_newsletter style="card" show_title="1"]` | `subscribe-to-uu-connections-weekly-newsletter.html` |

## Migrated content pages

Body content was extracted with `scripts/extract_mirror.py` and imported with `scripts/import_pages.py`. Both use the Weebly HTML structure as-is and just strip scripts, social-share buttons, comments, and rewrite image / link / audio URLs to absolute.

| Page | Slug | Post ID |
|------|------|--------:|
| About Us | `about-us` | 9 |
| Additional Resources | `additional-resources` | 10 |
| Bonnie & Hollis' Greeley Trib Commentary | `bonnie-hollis-greeley-trib-commentary` | 11 |
| Calendar | `calendar` | 12 |
| Contact | `contact` | 13 |
| COVID 19 Response | `covid-19-response` | 14 |
| Education | `education` | 15 |
| Get Involved | `get-involved` | 16 |
| History | `history` | 17 |
| Membership | `membership` | 18 |
| Minister, Board, & Staff | `minister-board-staff` | 19 |
| Nature Mandalas & Painted Rocks | `nature-mandalas-painted-rocks` | 20 |
| Pledging | `pledging` | 21 |
| Social Justice | `social-justice` | 22 |
| Sunday Service | `sunday-service` | 23 |
| UUCG and the Arts | `uucg-and-the-arts` | 24 |

## Archive index pages not migrated

The 5 archive index pages are empty once the sub-archive pages are not migrated. They were intentionally skipped so the menu does not link to dead ends.

- `services-audio-only` (would list 198 sub-pages, none migrated)
- `video-recordings-of-recent-hybrid-services` (42 sub-pages, none)
- `archived-monthly-ministerial-musings-from-the-past` (56 sub-pages, none)
- `bonnie--hollis-commentary` (1 sub-page, none)
- `earth-day-fair-and-film` (1 sub-page, none)

The Weebly "Subscribe to UU Connections" form page is also skipped because the plugin-driven `/newsletter/` page replaces it.

## Menus

Built with `scripts/wire_menus.py` and assigned to the `uua-congregation` parent theme's nav locations.

**Primary** (assigned to `primary_navigation`):

- Home
- About
  - About Us
  - History
  - Minister, Board, & Staff
  - Contact
- Community
  - Get Involved
  - In the Loop
  - Bonnie & Hollis' Greeley Trib Commentary
- Worship
  - Sunday Service
  - Worship Schedule
  - Additional Resources
- News & Reminders
  - Calendar
  - Education
  - Subscribe
  - Pledging
  - COVID 19 Response
  - Social Justice
  - UUCG and the Arts
  - Membership
  - Nature Mandalas & Painted Rocks

**Footer** (assigned to `footer_navigation`): Contact, Sunday Service, Worship Schedule, Calendar, Subscribe.

## Skipped per user instruction

- Bulk migration of the 298 sub-archive pages (audio services, video recordings, ministerial musings, and the 2 single-sub-page archives). The extractor at `scripts/extract_mirror.py` still produces these in `mirror/extracted/` for future use; the importer skips them.

## What still needs human input

1. **Mailchimp API key + audience ID** in Settings → Newsletter.
2. **At least one social platform handle or URL** in Settings → In the Loop.
3. **Google Sheet URL** in Settings → Worship Schedule (defaults are seeded; confirm they point at the real UUCG sheet).
4. **Address / phone / email / hours** in the Customizer so the footer contact block renders.
5. **Static front page** choice (Customizer → Homepage settings) — currently the site shows the default WP behavior; the live Weebly site has a static landing page.
6. **Theme `[uucg_staff]` shortcode** if the user wants to swap the migrated `minister-board-staff` content for the modern theme's directory shortcode.
