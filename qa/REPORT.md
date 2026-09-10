# UUCG content QA report

Generated 2026-09-10. Visual review of 20 local WordPress pages (3 plugin
pages + 16 migrated content pages + home) plus 6 live Weebly reference
pages, both captured with headless Chrome at 1280×1500.

Screenshots live in `qa/screenshots/` (local) and `qa/live-screenshots/`
(greeleyuuc.org Weebly).

## Issues found in the first screenshot pass

### Site-wide

1. **Site title showed `uucg_site`** instead of the church name. **Fixed** —
   `blogname` and `blogdescription` set via wp-cli.
2. **Default WP right sidebar (Search, Recent Posts, Recent Comments,
   Archives, Categories) showed on every content page.** **Fixed** —
   child theme `page.php` overrides the parent template; no
   `get_sidebar()` call, full-width main column.
3. **Weebly multicol HTML rendered as a constrained table** so the 2-column
   layouts on about-us, history, sunday-service, social-justice were
   either cramped or broken. **Fixed** — CSS rules in `modern.css`
   flatten `.wsite-multicol-table*` and re-flow as flex columns at
   720px+.
4. **Weebly section headings (`h2.wsite-content-title`) competed with the
   page title** because the parent theme's h2 is a serif display font at
   near-h1 size. **Fixed** — `.uucg-page-content h2` rules in `modern.css`
   demote them visually to 1.35rem, Fraunces 600.
5. **Cloudflare-obfuscated email links** (`[email protected]`) lost the
   real address when the live site was scraped. **Fixed** —
   `scripts/fix_emails.py` decodes the hex `data-cfemail` payload and
   rewrites the placeholder as a real `mailto:` link. 8 files updated.

### Per page

| Page | Issue | Status |
|------|-------|--------|
| about-us | [Video — see the original Weebly page for playback.] placeholder | Left as-is (Weebly video not migrated); the Weebly video wrapper is hidden via CSS. |
| contact | Email placeholders | Fixed; both now link to `office_manager@greeleyuuc.org`. |
| contact | Embedded map missing | Not migrated; would need the live site's static map asset. |
| history | Two-column table broken | Fixed via the multicol CSS; rendered as 2 columns at 720px+. |
| pledging | Embedded video (look-back) | Same video placeholder; left as-is. |
| pledging | Fair Share contribution table | Renders correctly with original colors. |
| minister-board-staff | Centered photo + bio | Acceptable; matches the original. |
| calendar | Empty page ("Calendar of Events" only) | Matches the live Weebly; future work = add a real calendar (events-manager plugin or shortcode). |

## Modern taste pass

- Page title rendered as a clean `<h1 class="uucg-page-title">` with
  Fraunces serif at 2.4rem max, sitting above a thin rule.
- Page content capped at 760px and centered on a 1280px viewport. Reads
  like a long-form article, not a cluttered CMS page.
- Default WP `.page-header` hidden so the title doesn't double-render.
- Weebly video wrappers hidden; the audio archive placeholder
  (`<p class="uucg-archive-audio">`) gets a soft surface treatment.
- Bullet/numbered lists inherit the modern theme's list styles.

## How to re-run the visual review

```bash
# Capture all 20 local pages
python3 scripts/screenshot.py

# Capture specific live Weebly pages for comparison
for slug in about-us contact history; do
  "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" \
    --headless --disable-gpu --no-sandbox --hide-scrollbars \
    --window-size=1280,1500 --screenshot=qa/live-screenshots/$slug.png \
    "https://www.greeleyuuc.org/$slug.html"
done
```

## Files touched

- `wp-content/themes/uua-congregation-2027/page.php` (new) — full-width
  page template replacing the parent sidebar layout.
- `wp-content/themes/uua-congregation-2027/assets/css/modern.css` — new
  block at the bottom: `.uucg-page` template styles, Weebly multicol
  flex, Weebly h2 demotion, video hide, archive audio treatment,
  default `.page-header` hide.
- `wp-content/themes/uua-congregation-2027/style.css` — version bump
  1.5.0 → 1.5.1 (cache bust for modern.css).
- `scripts/fix_emails.py` (new) — decodes Weebly Cloudflare email
  placeholders.
- `scripts/update_pages.py` (new) — updates existing pages with the
  freshly-fixed content (no duplicates).
- `scripts/screenshot.py` (new) — headless Chrome screenshot pass.
- `mirror/extracted/*.html` — 8 files updated to fix email placeholders.

## What still needs human input

- Static front page (Customizer → Homepage settings) — currently the
  site shows the default blog index. The live Weebly has a static
  landing page.
- In the Loop platform handles (TikTok / Instagram / Facebook / X).
- Mailchimp API key + audience ID.
- Customizer contact info (address / phone / email / hours) so the
  footer contact block renders.
- Either migrate the live Weebly `we_are_uucg_584.mp4` for about-us
  video, or drop the placeholder note.
- The Calendar page is empty by design (live Weebly is also empty);
  add a real events feed or use the `events-manager` plugin to drive it.
- Migrate the 298 sub-archive pages (audio / video / ministerial
  musings) — extractor at `scripts/extract_mirror.py` still produces
  these in `mirror/extracted/`, ready to be imported.
