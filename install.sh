#!/usr/bin/env bash
#
# install.sh — install the UUCG WordPress stack into a target WordPress install.
#
# What it puts in place:
#   wp-content/themes/uua-congregation/        UUA parent theme (from vendor/uua/)
#   wp-content/themes/uua-congregation-2027/  UUCG Warm Modern (2027) child theme
#   wp-content/plugins/uua-services/          UUA Services plugin
#   wp-content/plugins/uucg-in-the-loop/      Social tabs shortcode
#   wp-content/plugins/uucg-worship-schedule/ Schedule shortcode
#   wp-content/plugins/uucg-newsletter/       Mailchimp signup shortcode
#
# Usage:
#   ./install.sh /path/to/wordpress
#   ./install.sh /path/to/wordpress --force
#   ./install.sh /path/to/wordpress --only theme,services
#   ./install.sh --help
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# --- Defaults ---------------------------------------------------------------

DEFAULT_VENDOR="${SCRIPT_DIR}/vendor/uua"
DEFAULT_SOURCE="${SCRIPT_DIR}/wp-content"
THEME_ZIP_NAME="uua-congregation.zip"
SERVICES_ZIP_NAME="uua-services.zip"

# --- CLI ---------------------------------------------------------------------

WP_PATH=""
VENDOR_DIR="$DEFAULT_VENDOR"
SOURCE_DIR="$DEFAULT_SOURCE"
FORCE=0
DRY_RUN=0
ASSUME_YES=0
ONLY=""

usage() {
  cat <<EOF
install.sh — install the UUCG WordPress stack

Usage:
  $(basename "$0") <wp-path> [options]

Arguments:
  <wp-path>             Path to a WordPress installation (folder containing
                        wp-config.php and wp-content/).

Options:
  --vendor <dir>        Directory with vendored zips. Default: ${DEFAULT_VENDOR}
  --source <dir>        Directory with UUCG custom content (themes/uua-congregation-2027
                        and plugins/uucg-*). Default: ${DEFAULT_SOURCE}
  --only <list>         Comma-separated subset to install: theme, services, custom.
                        Default: install all three.
  --force               Overwrite existing theme/plugin folders.
  --yes                 Assume "yes" to any confirmation prompt.
  --dry-run             Print what would happen, make no changes.
  -h, --help            Show this help.

Examples:
  $(basename "$0") /Users/you/Local\ Sites/uucgsite/app/public
  $(basename "$0") ~/Sites/uucg --only theme,services
  $(basename "$0") /var/www/html --force --dry-run
EOF
}

die()   { echo "Error: $*" >&2; exit 1; }
note()  { echo "  $*"; }
ok()    { echo "  ok $*"; }
warn()  { echo "  warn: $*"; }

require_arg() {
  local flag="$1" value="${2:-}"
  [ -n "$value" ] || die "missing value for $flag"
}

parse_args() {
  while [ $# -gt 0 ]; do
    case "$1" in
      -h|--help) usage; exit 0 ;;
      --vendor)  require_arg "$1" "${2:-}"; VENDOR_DIR="$2"; shift 2 ;;
      --source)  require_arg "$1" "${2:-}"; SOURCE_DIR="$2"; shift 2 ;;
      --only)    require_arg "$1" "${2:-}"; ONLY="$2"; shift 2 ;;
      --force)   FORCE=1; shift ;;
      --yes)     ASSUME_YES=1; shift ;;
      --dry-run) DRY_RUN=1; shift ;;
      --)        shift; break ;;
      -*)        die "unknown flag: $1 (try --help)" ;;
      *)
        if [ -z "$WP_PATH" ]; then
          WP_PATH="$1"; shift
        else
          die "unexpected positional argument: $1"
        fi
        ;;
    esac
  done

  [ -n "$WP_PATH" ] || { usage; die "missing <wp-path>"; }

  # Normalize paths.
  case "$WP_PATH" in
    ~*) WP_PATH="${WP_PATH/#\~/$HOME}" ;;
  esac
  WP_PATH="$(cd "$WP_PATH" 2>/dev/null && pwd || echo "$WP_PATH")"
  case "$VENDOR_DIR" in
    ~*) VENDOR_DIR="${VENDOR_DIR/#\~/$HOME}" ;;
  esac
  case "$SOURCE_DIR" in
    ~*) SOURCE_DIR="${SOURCE_DIR/#\~/$HOME}" ;;
  esac
}

# --- Validation -------------------------------------------------------------

check_wp_install() {
  [ -d "$WP_PATH" ]              || die "not a directory: $WP_PATH"
  [ -f "$WP_PATH/wp-config.php" ] || die "no wp-config.php at $WP_PATH (is this a WordPress install?)"
  [ -d "$WP_PATH/wp-content" ]    || die "no wp-content/ at $WP_PATH"
  [ -d "$WP_PATH/wp-content/themes" ]  || die "no wp-content/themes/ at $WP_PATH"
  [ -d "$WP_PATH/wp-content/plugins" ] || die "no wp-content/plugins/ at $WP_PATH"
}

check_vendor() {
  [ -d "$VENDOR_DIR" ] || die "vendor dir not found: $VENDOR_DIR"
  [ -f "$VENDOR_DIR/$THEME_ZIP_NAME" ]    || die "missing $THEME_ZIP_NAME in $VENDOR_DIR"
  [ -f "$VENDOR_DIR/$SERVICES_ZIP_NAME" ] || die "missing $SERVICES_ZIP_NAME in $VENDOR_DIR"
}

check_source() {
  # Source is optional. Warn if the custom content is missing — but only if the
  # user is actually requesting the custom step.
  if [ -d "$SOURCE_DIR/themes/uua-congregation-2027" ]; then
    HAS_CHILD_THEME=1
  else
    HAS_CHILD_THEME=0
  fi
  local p
  for p in uucg-in-the-loop uucg-worship-schedule uucg-newsletter; do
    if [ -d "$SOURCE_DIR/plugins/$p" ]; then
      HAS_CUSTOM_PLUGINS=1
      return
    fi
  done
  HAS_CUSTOM_PLUGINS=0
}

want_step() {
  local step="$1"
  if [ -z "$ONLY" ]; then return 0; fi
  case ",$ONLY," in *",$step,"*) return 0 ;; *) return 1 ;; esac
}

# --- Install helpers --------------------------------------------------------

ensure_target_slot() {
  # $1 = full path to where the theme/plugin will land
  local target="$1" label="$2"
  if [ -d "$target" ]; then
    if [ "$FORCE" -eq 1 ]; then
      warn "$label already present at $target (--force will overwrite)"
    else
      warn "$label already present at $target (skipped; pass --force to overwrite)"
      return 1
    fi
  fi
  return 0
}

run_extract() {
  # $1 = zip path, $2 = destination dir, $3 = label
  local zip="$1" dest="$2" label="$3"
  if [ "$DRY_RUN" -eq 1 ]; then
    note "[dry-run] would unzip $zip into $dest"
    return 0
  fi
  # Strip the top-level directory inside the zip so the contents land directly
  # in $dest, matching the conventional themes/<slug>/ layout.
  local tmp
  tmp="$(mktemp -d)"
  trap 'rm -rf "$tmp"' EXIT
  unzip -q "$zip" -d "$tmp"
  local top
  top="$(find "$tmp" -mindepth 1 -maxdepth 1 -type d | head -n 1)"
  [ -n "$top" ] || die "$label: zip has no top-level directory"
  mkdir -p "$dest"
  # rsync-style copy that overwrites in place; on macOS use cp -R.
  /usr/bin/rsync -a --delete "$top/" "$dest/" >/dev/null
  rm -rf "$tmp"
  trap - EXIT
  ok "installed $label at $dest"
}

run_copy() {
  # $1 = source dir, $2 = dest dir, $3 = label
  local src="$1" dest="$2" label="$3"
  if [ "$DRY_RUN" -eq 1 ]; then
    note "[dry-run] would copy $src to $dest"
    return 0
  fi
  mkdir -p "$(dirname "$dest")"
  /usr/bin/rsync -a --delete "$src/" "$dest/" >/dev/null
  ok "installed $label at $dest"
}

# --- Steps ------------------------------------------------------------------

install_parent_theme() {
  local dest="$WP_PATH/wp-content/themes/uua-congregation"
  ensure_target_slot "$dest" "UUA parent theme" || return 0
  run_extract "$VENDOR_DIR/$THEME_ZIP_NAME" "$dest" "UUA parent theme (uua-congregation)"
}

install_services_plugin() {
  local dest="$WP_PATH/wp-content/plugins/uua-services"
  ensure_target_slot "$dest" "UUA Services plugin" || return 0
  run_extract "$VENDOR_DIR/$SERVICES_ZIP_NAME" "$dest" "UUA Services plugin"
}

install_custom() {
  if [ "$HAS_CHILD_THEME" -eq 1 ]; then
    local dest="$WP_PATH/wp-content/themes/uua-congregation-2027"
    ensure_target_slot "$dest" "UUCG child theme" || true
    if [ ! -d "$dest" ] || [ "$FORCE" -eq 1 ]; then
      run_copy "$SOURCE_DIR/themes/uua-congregation-2027" "$dest" "UUCG child theme (uua-congregation-2027)"
    fi
  else
    warn "no UUCG child theme at $SOURCE_DIR/themes/uua-congregation-2027 (skipped)"
  fi

  if [ "$HAS_CUSTOM_PLUGINS" -eq 1 ]; then
    local p
    for p in uucg-in-the-loop uucg-worship-schedule uucg-newsletter; do
      if [ -d "$SOURCE_DIR/plugins/$p" ]; then
        local dest="$WP_PATH/wp-content/plugins/$p"
        ensure_target_slot "$dest" "UUCG plugin ($p)" || continue
        run_copy "$SOURCE_DIR/plugins/$p" "$dest" "UUCG plugin ($p)"
      fi
    done
  else
    warn "no UUCG custom plugins under $SOURCE_DIR/plugins/uucg-* (skipped)"
  fi
}

# --- Main -------------------------------------------------------------------

main() {
  parse_args "$@"

  echo "UUCG WordPress installer"
  echo "  target: $WP_PATH"
  echo "  vendor: $VENDOR_DIR"
  echo "  source: $SOURCE_DIR"
  [ -n "$ONLY" ] && echo "  only:   $ONLY"
  [ "$DRY_RUN" -eq 1 ] && echo "  mode:   dry-run (no changes)"
  [ "$FORCE"  -eq 1 ] && echo "  force:  on (will overwrite existing folders)"
  echo

  check_wp_install
  check_vendor
  check_source

  if want_step theme; then
    install_parent_theme
  else
    note "skipping parent theme (--only)"
  fi

  if want_step services; then
    install_services_plugin
  else
    note "skipping services plugin (--only)"
  fi

  if want_step custom; then
    install_custom
  else
    note "skipping UUCG custom content (--only)"
  fi

  echo
  echo "Done."
  echo
  echo "Next steps in wp-admin:"
  echo "  1. Appearance -> Themes: activate 'UUCG Warm Modern (2027)'."
  echo "  2. Plugins: activate 'UUA Services' and the three uucg-* plugins."
  echo "  3. Settings page for each uucg-* plugin (Mailchimp key, Google Sheet URL, etc.)."
}

main "$@"
