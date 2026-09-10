#!/usr/bin/env python3
"""
Build the primary and footer navigation menus for the local UUCG WordPress
install, matching the structure of the live greeleyuuc.org site.

Top-level "About / Community / Worship / News & Reminders" items are custom
links (URL "#") used as dropdown toggle labels. Sub-items use the page IDs.
"""

from __future__ import annotations

import os
import subprocess
import sys
from pathlib import Path

WP_ROOT = Path("/Users/jc/Local Sites/uucgsite/app/public")


def wp(*args: str) -> str:
    cmd = ["wp", *args, "--path=" + str(WP_ROOT)]
    env = os.environ.copy()
    envrc = WP_ROOT.parent / ".envrc"
    if envrc.exists():
        for line in envrc.read_text().splitlines():
            line = line.strip()
            if line.startswith("export "):
                kv = line[7:].split("=", 1)
                if len(kv) == 2:
                    k = kv[0].strip()
                    v = kv[1].strip().strip('"').strip("'")
                    if k == "PATH":
                        env["PATH"] = v + ":" + env.get("PATH", "")
                    else:
                        env[k] = v
    r = subprocess.run(cmd, env=env, capture_output=True, text=True)
    if r.returncode != 0:
        print(f"  !! wp {args[:3] if args else '?'} failed: {r.stderr.strip()[:200]}", file=sys.stderr)
        return ""
    return r.stdout.strip()


def page_id(slug: str) -> int | None:
    out = wp("post", "list", "--post_type=page", f"--name={slug}", "--field=ID", "--format=csv")
    out = out.strip().splitlines()
    if not out or not out[0].isdigit():
        return None
    return int(out[0])


def add_custom(menu_slug: str, title: str, url: str, parent_id: str = "") -> str:
    args = ["menu", "item", "add-custom", menu_slug, title, url, "--porcelain"]
    if parent_id:
        args += ["--parent-id=" + parent_id]
    r = wp(*args)
    return r.strip()


def add_page(menu_slug: str, page_id_val: int, parent_id: str = "") -> str:
    args = ["menu", "item", "add-post", menu_slug, str(page_id_val), "--porcelain"]
    if parent_id:
        args += ["--parent-id=" + parent_id]
    r = wp(*args)
    return r.strip()


def main() -> int:
    home_url = wp("option", "get", "home")

    # 1) Create menus (delete any existing by the same name first)
    for old in wp("menu", "list", "--fields=term_id", "--format=csv").splitlines():
        if old.strip().isdigit():
            wp("menu", "delete", old.strip())

    primary = wp("menu", "create", "Primary", "--porcelain").strip()
    footer = wp("menu", "create", "Footer", "--porcelain").strip()
    print(f"[menu] primary term_id={primary}  footer term_id={footer}", flush=True)

    # Slug map for menu item shell commands
    primary_slug = "primary"
    footer_slug = "footer"

    def add_group(label: str, items: list[tuple[str, str]]) -> None:
        # The top-level group label is a custom link (used as a dropdown trigger)
        gid = add_custom(primary_slug, label, home_url + "#" + label.lower().replace(" ", "-").replace("&", "and"))
        print(f"  {label} -> item {gid}", flush=True)
        for sub_label, slug in items:
            pid = page_id(slug)
            if pid is None:
                print(f"    !! missing page slug: {slug}", file=sys.stderr)
                continue
            iid = add_page(primary_slug, pid, parent_id=gid)
            print(f"    {sub_label} -> item {iid}", flush=True)

    # 2) Home
    home_id = add_custom(primary_slug, "Home", home_url)
    print(f"  Home -> item {home_id}", flush=True)

    add_group("About", [
        ("About Us", "about-us"),
        ("History", "history"),
        ("Minister, Board, & Staff", "minister-board--staff"),
        ("Contact", "contact"),
    ])
    add_group("Community", [
        ("Get Involved", "get-involved"),
        ("In the Loop", "in-the-loop"),
        ("Bonnie & Hollis' Greeley Trib Commentary", "bonnie--hollis-greeley-trib-commentary"),
    ])
    add_group("Worship", [
        ("Sunday Service", "sunday-service"),
        ("Worship Schedule", "worship-schedule"),
        ("Additional Resources", "additional-resources"),
    ])
    add_group("News & Reminders", [
        ("Calendar", "calendar"),
        ("Education", "education"),
        ("Subscribe", "newsletter"),
        ("Pledging", "pledging"),
        ("COVID 19 Response", "covid-19-response"),
        ("Social Justice", "social-justice"),
        ("UUCG and the Arts", "uucg-and-the-arts"),
        ("Membership", "membership"),
        ("Nature Mandalas & Painted Rocks", "nature-mandalas--painted-rocks"),
    ])

    # 3) Footer: a short useful set
    contact_id = page_id("contact")
    if contact_id:
        add_page(footer_slug, contact_id)
    for label, slug in [
        ("Sunday Service", "sunday-service"),
        ("Worship Schedule", "worship-schedule"),
        ("Calendar", "calendar"),
        ("Subscribe", "newsletter"),
    ]:
        pid = page_id(slug)
        if pid is None:
            print(f"  !! missing page slug: {slug}", file=sys.stderr)
            continue
        add_page(footer_slug, pid)

    # 4) Assign menus to locations (use the menu slug, not the term id)
    # Note: arg order is <menu> <location>, not <location> <menu>.
    wp("menu", "location", "assign", primary_slug, "primary_navigation")
    wp("menu", "location", "assign", footer_slug, "footer_navigation")
    print(f"[menu] primary_navigation -> {primary_slug}", flush=True)
    print(f"[menu] footer_navigation -> {footer_slug}", flush=True)
    print("[done] menu wiring complete", flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
