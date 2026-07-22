# UUCG Warm Modern (2027)

A child theme of the official **UUA Congregation** WordPress theme for the Unitarian Universalist Church of Greeley.

## What it does

Keeps all UUA theme structure, widgets, shortcodes, and Customizer options, and layers a warm, welcoming 2027 design on top:

- Soft ambient gradient background (no busy tile pattern)
- Frosted-glass masthead and content shell
- Sticky gradient navigation with glassy dropdowns
- Gentle card hover depth and optional 3D tilt
- Calm scroll-in reveals
- Warm typography (Nunito + Fraunces)
- Respects your UUA colour scheme (default / grey-red / aqua-green)
- Honors `prefers-reduced-motion`

## Activate

1. Appearance → Themes
2. Activate **UUCG Warm Modern (2027)**
3. Keep parent theme **UUA Congregation** installed

## Customize further

- **Customizer → Theme Colour Options** still works
- **Customizer → Additional CSS** still applies after this theme
- Edit `assets/css/modern.css` for design tokens (`:root` variables)

## Files

| Path | Role |
|------|------|
| `style.css` | Child theme header |
| `functions.php` | Enqueues parent + modern assets |
| `assets/css/modern.css` | Full visual redesign |
| `assets/js/modern.js` | Scroll state, reveals, soft tilt |
