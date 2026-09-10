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

## One-shot install

Run `install.sh` against any WordPress install (Local site, fresh WP, production host). It drops in the UUA parent theme, the UUA Services plugin, the UUCG child theme, and the three `uucg-*` plugins in one pass.

```bash
# From a fresh clone, against a Local by Flywheel site:
./install.sh ~/Local\ Sites/uucgsite/app/public

# Dry-run first to see what would happen:
./install.sh /var/www/html --dry-run

# Only refresh the parent theme and services plugin:
./install.sh ~/Sites/uucg --only theme,services

# Overwrite an existing install:
./install.sh /var/www/html --force
```

The script reads vendored zips from `vendor/uua/` and the UUCG custom code from `wp-content/`. Override either with `--vendor <dir>` or `--source <dir>`. It refuses to clobber an existing theme or plugin folder unless you pass `--force`.

## Local setup

1. Open the site in [Local](https://localwp.com/) (or any WordPress stack) and start it.
2. Run `./install.sh` (see above) to drop in the theme and plugins.
3. **Appearance → Themes** → activate **UUCG Warm Modern (2027)**.
4. **Plugins** → activate **UUA Services** and the three `uucg-*` plugins.
5. Configure each `uucg-*` plugin under **Settings** (or its admin page).

## Deploying to production

Copy (or rsync) only:

- `wp-content/themes/uua-congregation-2027/`
- `wp-content/plugins/uucg-in-the-loop/`
- `wp-content/plugins/uucg-worship-schedule/`
- `wp-content/plugins/uucg-newsletter/`

Then clear any page/CSS caches. Parent theme and WP core must already be present on the host.

## Agent / contributor notes

See [AGENTS.md](./AGENTS.md) for edit boundaries (what may be changed vs third-party code to leave alone).
