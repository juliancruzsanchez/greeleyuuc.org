=== UUCG In the Loop ===
Contributors: uucg
Tags: social, tiktok, facebook, twitter, x, feed, embed, shortcode
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern tabbed social feed for TikTok, Facebook, and X — beautiful responsive embeds for your In the Loop page.

== Description ==

**UUCG In the Loop** adds a polished, responsive social media section with three tabs:

* **TikTok** — curated video embeds
* **Facebook** — live Page plugin and/or individual post embeds
* **X** — live profile timeline and/or individual post embeds

All tabs share the same warm modern card design (aligned with the UUCG 2027 theme tokens).

= Usage =

1. Activate the plugin.
2. Go to **Settings → In the Loop**.
3. Add your account usernames/URLs and paste post or video URLs (one per line).
4. Place the shortcode on any page:

`[in_the_loop]`

Optional attributes:

`[in_the_loop default_tab="facebook" columns="2" show_header="0"]`

= Shortcode aliases =

* `[in_the_loop]`
* `[uucg_in_the_loop]`

== Installation ==

1. Upload the `uucg-in-the-loop` folder to `/wp-content/plugins/`.
2. Activate **UUCG In the Loop** through the Plugins screen.
3. Configure under **Settings → In the Loop**.
4. Add `[in_the_loop]` to your page.

== Frequently Asked Questions ==

= Why do I paste TikTok URLs instead of a username feed? =

TikTok does not provide a free public “profile timeline” embed the way X and Facebook do. Curating video URLs produces a reliable, beautiful grid of official embeds.

= Can Facebook and X load live feeds? =

Yes. Enable **Page plugin** (Facebook) or **Timeline widget** (X) in settings. You can also mix those with specific post URLs.

= Do I need API keys? =

No. The plugin uses official embed widgets (TikTok embed.js, Facebook SDK, X widgets.js).

== Changelog ==

= 1.0.0 =
* Initial release: tabbed TikTok / Facebook / X layout, admin settings, shortcode.
