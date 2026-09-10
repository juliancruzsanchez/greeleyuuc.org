#!/usr/bin/env python3
"""
Headless Chrome screenshot pass for the local UUCG WordPress install.

Captures one full-page PNG per page into screenshots/<slug>.png.
"""

from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

BASE = "http://uucgsite.local"
OUT = Path("/Users/jc/Local Sites/greeleyuuc.org/qa/screenshots")
OUT.mkdir(parents=True, exist_ok=True)
CHROME = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"

# All slugs we want to capture
PAGES = [
    ("home", "/"),
    ("about-us", "/about-us/"),
    ("additional-resources", "/additional-resources/"),
    ("bonnie-hollis-greeley-trib-commentary", "/bonnie-hollis-greeley-trib-commentary/"),
    ("calendar", "/calendar/"),
    ("contact", "/contact/"),
    ("covid-19-response", "/covid-19-response/"),
    ("education", "/education/"),
    ("get-involved", "/get-involved/"),
    ("history", "/history/"),
    ("in-the-loop", "/in-the-loop/"),
    ("membership", "/membership/"),
    ("minister-board-staff", "/minister-board-staff/"),
    ("nature-mandalas-painted-rocks", "/nature-mandalas-painted-rocks/"),
    ("newsletter", "/newsletter/"),
    ("pledging", "/pledging/"),
    ("social-justice", "/social-justice/"),
    ("sunday-service", "/sunday-service/"),
    ("uucg-and-the-arts", "/uucg-and-the-arts/"),
    ("worship-schedule", "/worship-schedule/"),
]


def main() -> int:
    # First, fetch the viewport-fitted PNG of each page
    summary: list[dict] = []
    for slug, path in PAGES:
        url = BASE + path
        out = OUT / f"{slug}.png"
        # Use --hide-scrollbars and a generous viewport for the desktop look.
        # Full-page screenshot via --screenshot uses the visible viewport only;
        # we then concatenate by re-running with --window-size tall enough to
        # capture the page (1500px handles the typical content length).
        result = subprocess.run(
            [
                CHROME,
                "--headless",
                "--disable-gpu",
                "--no-sandbox",
                "--hide-scrollbars",
                "--virtual-time-budget=3000",
                "--window-size=1280,1500",
                f"--screenshot={out}",
                url,
            ],
            capture_output=True,
            text=True,
            timeout=20,
        )
        if not out.exists() or out.stat().st_size < 1000:
            print(f"  !! failed: {slug} ({url})", flush=True)
            summary.append({"slug": slug, "url": url, "ok": False})
            continue
        size = out.stat().st_size
        print(f"  ok: {slug} -> {out.name} ({size} bytes)", flush=True)
        summary.append({"slug": slug, "url": url, "ok": True, "path": str(out), "size": size})

    (OUT / "_summary.json").write_text(json.dumps(summary, indent=2), encoding="utf-8")
    ok = sum(1 for s in summary if s["ok"])
    print(f"[done] {ok}/{len(summary)} ok", flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
