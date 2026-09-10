#!/usr/bin/env python3
"""
Bulk-create WordPress pages in the local uucgsite install from the extracted
mirror files.

- Reads /Users/jc/Local Sites/greeleyuuc.org/mirror/extracted/_index.json
- Creates one wp post per entry via `wp post create --porcelain`
- For sub pages, sets post_parent to the section index page (looked up by slug)
- Writes /Users/jc/Local Sites/greeleyuuc.org/mirror/extracted/_ids.json
  with the resulting slug -> ID map (and section -> ID for the index pages)
"""

from __future__ import annotations

import json
import os
import subprocess
import sys
from pathlib import Path

EXTRACTED = Path("/Users/jc/Local Sites/greeleyuuc.org/mirror/extracted")
WP_ROOT = Path("/Users/jc/Local Sites/uucgsite/app/public")

# Slugs we want to skip (already have plugin pages, or are archive indexes with no sub-pages)
SKIP_SLUGS = {
    # plugin pages already exist
    "in-the-loop", "worship-schedule", "newsletter",
    # archive index pages that have no real content (the sub-pages aren't being migrated)
    "archived-monthly-ministerial-musings-from-the-past",
    "bonnie--hollis-commentary",
    "earth-day-fair-and-film",
    "services-audio-only",
    "video-recordings-of-recent-hybrid-services",
    # the Weebly subscribe page is replaced by the plugin /newsletter/ page
    "subscribe-to-uu-connections-weekly-newsletter",
}


def wp(*args: str) -> str:
    """Run a wp-cli command in the local WordPress install and return stdout."""
    cmd = ["wp", *args, "--path=" + str(WP_ROOT)]
    # Include the env from the Local by Flywheel .envrc so wp-cli can find mysql/php
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
        print(f"  !! wp {args[0] if args else '?'} failed: {r.stderr.strip()[:200]}", file=sys.stderr)
        return ""
    return r.stdout.strip()


def main() -> int:
    index = json.loads((EXTRACTED / "_index.json").read_text())
    # Filter to what we want to import
    work = []
    for e in index:
        if e["type"] == "sub":
            # Skip sub-archives per user instruction
            continue
        if e["slug"] in SKIP_SLUGS:
            continue
        if not e["body_path"]:
            continue
        work.append(e)

    print(f"[import] {len(work)} root pages to import", flush=True)

    ids: dict[str, int] = {}
    failed: list[str] = []
    for i, e in enumerate(work, 1):
        body_file = EXTRACTED / e["body_path"]
        # wp post create reads post_content from --post_content-file
        post_id = wp(
            "post", "create",
            f"--post_type=page",
            "--post_status=publish",
            f"--post_title={e['title']}",
            f"--post_name={e['slug']}",
            f"--post_content={body_file.read_text(encoding='utf-8')}",
            "--porcelain",
        )
        if not post_id or not post_id.isdigit():
            failed.append(e["slug"])
            print(f"  !! failed: {e['slug']}", flush=True)
            continue
        ids[e["slug"]] = int(post_id)
        # menu_order
        wp("post", "meta", "update", post_id, "_wp_page_template", "default")
        if i % 10 == 0 or i == len(work):
            print(f"[import] {i}/{len(work)}  {e['slug']} -> {post_id}", flush=True)

    (EXTRACTED / "_ids.json").write_text(json.dumps({"ids": ids, "failed": failed}, indent=2), encoding="utf-8")
    print(f"[done] imported={len(ids)} failed={len(failed)}", flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
