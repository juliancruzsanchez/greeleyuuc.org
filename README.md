# greeleyuuc.org — UUCG custom WordPress code

Custom **plugins** and **child theme** for the [Unitarian Universalist Church of Greeley](https://greeleyuuc.org) site.

This repository tracks only application source we maintain. WordPress core, the UUA Congregation parent theme, third-party plugins, uploads, and `wp-config.php` stay out of git (see `.gitignore`).

## What’s in here

### Theme

| Path | Description |
|------|-------------|
| `wp-content/themes/uua-congregation-2027/` | **UUCG Warm Modern (2027)** — child of UUA Congregation. Design tokens, blog, staff directory, footer contact/newsletter, etc. |

### Plugins

| Path | Shortcode | Description |
|------|-----------|-------------|
| `wp-content/plugins/uucg-in-the-loop/` | `[in_the_loop]` | Social tabs (TikTok, Facebook, X, Instagram) |
| `wp-content/plugins/uucg-worship-schedule/` | `[worship_schedule]` | Worship schedule from Google Sheet (list + calendar) |
| `wp-content/plugins/uucg-newsletter/` | `[uucg_newsletter]` | Mailchimp one-field signup |

Secrets (Mailchimp API key, sheet URLs, etc.) are stored in **WordPress options / Settings**, not in this repo.

## Local setup

1. Open the site in [Local](https://localwp.com/) (or any WordPress stack) and start it.
2. Keep parent theme **UUA Congregation** installed.
3. Activate **UUCG Warm Modern (2027)**.
4. Activate the three `uucg-*` plugins as needed.
5. Configure each plugin under **Settings** (or the plugin’s admin page).

## Deploying to production

Copy (or rsync) only:

- `wp-content/themes/uua-congregation-2027/`
- `wp-content/plugins/uucg-in-the-loop/`
- `wp-content/plugins/uucg-worship-schedule/`
- `wp-content/plugins/uucg-newsletter/`

Then clear any page/CSS caches. Parent theme and WP core must already be present on the host.

## Agent / contributor notes

See [AGENTS.md](./AGENTS.md) for edit boundaries (what may be changed vs third-party code to leave alone).
