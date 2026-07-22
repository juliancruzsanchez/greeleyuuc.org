# UUCG WordPress — agent guide

Local WordPress site for the **Unitarian Universalist Church of Greeley** (greeleyuuc.org). Workspace root is the WordPress web root (`app/public` in Local by Flywheel).

## First rule: only edit custom code

This tree includes full WordPress core, third-party plugins, and stock themes. **Do not modify** core, vendor plugins, or parent theme files unless the user explicitly asks.

### Own / primary work surfaces

| Path | What it is |
|------|------------|
| `wp-content/themes/uua-congregation-2027/` | Active child theme — **UUCG Warm Modern (2027)** |
| `wp-content/plugins/uucg-in-the-loop/` | Custom plugin — social feed shortcode `[in_the_loop]` |
| `wp-content/plugins/uucg-worship-schedule/` | Custom plugin — schedule shortcode `[worship_schedule]` |

### Read-only unless asked

| Path | Notes |
|------|--------|
| `wp-admin/`, `wp-includes/` | WordPress core |
| `wp-content/themes/uua-congregation/` | Parent theme (UUA Congregation) — extend via child only |
| `wp-content/themes/twenty*` | Unused stock themes |
| `wp-content/plugins/{events-manager,jetpack,mailchimp,ml-slider,so-widgets-bundle,wordpress-seo,list-category-posts,uua-services}/` | Third-party |
| `wp-content/uploads/` | Media library binaries |
| `wp-config.php` | Local env secrets — never commit or rewrite casually |

## Architecture

- **Parent theme:** `uua-congregation` (Bootstrap 3 layout, widgets, Customizer colour schemes: default / `grey-red` / `aqua-green`).
- **Child theme:** `uua-congregation-2027` layers glass UI, tokens, and motion.
  - Design tokens + most UI: `assets/css/modern.css` (`:root` / `body.uucg-modern`).
  - Interactions: `assets/js/modern.js` (sticky header state, scroll reveals, soft card tilt, mobile drawer).
  - Body class `uucg-modern` always; `uucg-no-title-or-path` on **No Title or Path** page template.
- **No Title or Path** (`templates/template-no-title.php`): used for plugin-driven pages (In the Loop, Worship Schedule). Content is the page body — **not** a hoverable blog card. Do not re-apply `.hentry` lift styles there.
- Custom plugins ship their own `assets/css/frontend.css` and share theme CSS variables (`--uucg-*`). Roots (`.uucg-itl`, `.uucg-ws`) stay chrome-free; individual cards may still have light hover depth.

## Conventions

- PHP: WordPress coding style, `defined( 'ABSPATH' ) || exit;`, escape output, nonces on admin actions.
- CSS: prefer theme tokens (`--uucg-primary`, `--uucg-radius`, `--uucg-ease`, etc.) over hard-coded one-offs.
- Bump child theme `Version` in `style.css` when changing enqueued CSS/JS (cache bust via `uucg_modern_version()`).
- Respect `prefers-reduced-motion`.
- Prefer small, targeted diffs. Do not reformat unrelated third-party files.
- Site content/shortcodes live in the WP DB; code lives in the paths above.

## Common tasks

| Task | Where |
|------|--------|
| Visual polish / spacing / glass shell | `uua-congregation-2027/assets/css/modern.css` |
| Nav, drawer, scroll motion | `uua-congregation-2027/assets/js/modern.js` + related CSS |
| In the Loop UI / shortcode | `plugins/uucg-in-the-loop/` |
| Worship schedule UI / sheet fetch | `plugins/uucg-worship-schedule/` |
| Page template without title/breadcrumbs | `templates/template-no-title.php`, body class `uucg-no-title-or-path` |

## What not to do

- Do not “upgrade” parent theme markup by editing `uua-congregation` — override in the child.
- Do not treat full-page shortcode articles as blog cards (no whole-body `translateY` hover).
- Do not commit secrets from `wp-config.php` or Local credentials.
- Do not run destructive DB or filesystem operations without confirmation.

## Local context

- Dev URL is typically a Local site (e.g. `https://uucg.local`).
- Git in this workspace may track only a subset of files; prefer editing custom theme/plugin paths regardless.
