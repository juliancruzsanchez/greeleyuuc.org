# UUCG site lineup plan

## Mirror

Live site (Weebly, `https://www.greeleyuuc.org`) scraped via `mirror/scripts/mirror.py` on 2026-09-10.

- 321 unique URLs discovered from `sitemap.xml`
- 321 HTML pages saved to `mirror/html/`
- 0 failures
- Top-level inventory: 23 root pages + 198 audio service recordings + 56 archived ministerial musings + 42 hybrid service video recordings + 1 Earth Day subpage + 1 Bonnie & Hollis subpage
- Manifests: `mirror/manifest.json` (full), `mirror/inventory.json` (grouped by section), `mirror/top_pages.json` (23 top pages only)

Key finding: live site is still on Weebly (`x-host: ...weebly.net`). All `wp-content/` work in this repo is the new WordPress instance that will replace it.

## Plugin-driven pages created

The three `uucg-*` plugins only provide shortcodes, so each one needs a WordPress page to host it. All three pages use the `templates/template-no-title.php` template (body class `uucg-no-title-or-path`), which suppresses the page-title heading and breadcrumbs so the plugin's own header renders cleanly.

| Page | Slug | Post ID | Template | Shortcode | Replaces (live Weebly page) |
|------|------|--------:|----------|-----------|------------------------------|
| In the Loop | `in-the-loop` | 6 | No Title or Path | `[in_the_loop]` | (new — no live equivalent) |
| Worship Schedule | `worship-schedule` | 7 | No Title or Path | `[worship_schedule]` | (new — live `sunday-service.html` is descriptive, not a calendar) |
| Subscribe — UU Connections | `newsletter` | 8 | No Title or Path | `[uucg_newsletter style="card" show_title="1"]` | `subscribe-to-uu-connections-weekly-newsletter.html` |

## How each plugin renders

### `uucg-in-the-loop` → `in-the-loop`
- Tabs: TikTok, Instagram, Facebook, X
- Configure platform profiles / URLs in **Settings → In the Loop**
- Renders nothing until at least one platform is linked; shows an admin-facing empty state otherwise
- Assets: `wp-content/plugins/uucg-in-the-loop/assets/css/frontend.css`, `assets/js/frontend.js`
- Loads Facebook/X SDKs on demand via `frontend.js`

### `uucg-worship-schedule` → `worship-schedule`
- Pulls from a Google Sheet (default ID + GID are seeded; replace with the UUCG sheet in **Settings → Worship Schedule**)
- Cron: `uucg_ws_refresh_schedule` runs hourly; cache key `uucg_ws_services_cache`
- Default view: `list` (toggle to `calendar`)
- Force a single view with shortcode attr: `[worship_schedule view="list"]` or `[worship_schedule view="calendar"]`
- Quarter filter: `[worship_schedule quarter="spring"]` (autumn / winter / spring / summer / general)
- Empty until the sheet URL is set; shows a soft alert while using the stale cache

### `uucg-newsletter` → `newsletter`
- Mailchimp one-field signup. **Settings → Newsletter**: API key + audience ID (or fall back to the official Mailchimp plugin's `mc_api_key` / `mc_list_id` if `use_mc_plugin` is on, which is the default)
- Styles: `card` (default, this page), `full`, `band`, `full-band`, `compact`, `minimal`
- Use `style="band"` or `style="full-band"` for footer placement, `style="compact"` for sidebar
- `show_title="0"` to hide the inner header (use page title instead)

## Theme shortcode pages still needed

These are not plugin pages but use shortcodes that the modern theme adds, so they need a host page to be discoverable.

| Page | Shortcode | Source | Replaces (live) |
|------|-----------|--------|------------------|
| Staff & Leadership | `[uucg_staff]` | `themes/uua-congregation-2027/includes/staff-directory.php` | `minister-board--staff.html` |

## Content-only pages still to migrate (no plugin)

These are 1:1 content moves from the Weebly site. None use plugin shortcodes.

| WP page | Source (live) | Notes |
|---------|---------------|-------|
| About Us | `about-us.html` | "Our Faith" intro, worship time, inclusivity language |
| History | `history.html` | UUCG / Greeley UU history |
| Contact | `contact.html` | Address, phone, email, building rental notes |
| Get Involved | `get-involved.html` | Volunteer roles + flow |
| Membership | `membership.html` | Visit / Participate / Belong / Join |
| Sunday Service | `sunday-service.html` | Descriptive page about what Sunday service is (not the schedule) |
| Pledging | `pledging.html` | |
| Social Justice | `social-justice.html` | |
| COVID-19 Response | `covid-19-response.html` | |
| Education | `education.html` | |
| UUCG and the Arts | `uucg-and-the-arts.html` | |
| Earth Day Fair and Film | `earth-day-fair-and-film.html` | |
| Bonnie & Hollis' Greeley Trib Commentary | `bonnie--hollis-greeley-trib-commentary.html` | |
| Bonnie & Hollis Commentary | `bonnie--hollis-commentary.html` | |
| Services (Audio Only) | `services-audio-only.html` | Index page for the 198 archived audio services |
| Video Recordings of Recent Hybrid Services | `video-recordings-of-recent-hybrid-services.html` | Index page for the 42 video recordings |
| ARCHIVED: Monthly Ministerial Musings | `archived-monthly-ministerial-musings-from-the-past.html` | Index page for 56 reflections |
| Additional Resources | `additional-resources.html` | |
| Calendar | `calendar.html` | Likely `events-manager`-driven; needs a plugin choice or a custom Events page |

## Sub-archive migration (bulk)

Three section indexes are linked to a large number of sub-pages (198 / 56 / 42). Each sub-page is a standalone article in the live site. WordPress can host these as a custom post type (or as Posts under a category) so the index pages list them via a shortcode or `WP_Query`. The `events-manager` plugin, if kept, can take the Calendar page; the audio / video / musings archives need a decision (custom post type vs. Posts category vs. flat Pages).

## Menu + nav setup (not done yet)

Local site has no menus assigned. The 3 created pages + the `About / Community / Worship / News & Reminders / Nature Mandalas` structure from the live nav is the natural starting point. Run:

```bash
wp menu create "Primary"
# then add items via wp menu item add-post ...
```

## What was done in this pass

- 3 plugin pages created: `in-the-loop`, `worship-schedule`, `newsletter`
- All 3 use `templates/template-no-title.php`
- All 3 return HTTP 200 on the local URL
- Mirror captured (321 pages, no failures)
- Plan documented in this file

## What is NOT in this pass

- Content migration of the 19 non-plugin pages above
- Bulk migration of audio/video/musings sub-archives (298 pages)
- Menu wiring
- Mailchimp API key + audience ID (still in WP defaults — fill in **Settings → Newsletter**)
- In the Loop platform handles (TikTok / Instagram / Facebook / X) — fill in **Settings → In the Loop**
- Worship Schedule sheet URL — defaults are seeded but check **Settings → Worship Schedule** points at the real UUCG sheet
- Customizer contact info (address / phone / email / hours) — `theme_mod`s are empty so the footer contact block renders nothing
