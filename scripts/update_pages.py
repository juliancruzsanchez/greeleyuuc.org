#!/usr/bin/env python3
"""
Update existing WordPress pages in the local uucgsite install with the
freshly-fixed extracted content. Looks each page up by slug, then sets
post_content from the matching extracted HTML file.

Avoids creating duplicates (which the original import script did).
"""

from __future__ import annotations

import json
import os
import subprocess
import sys
from pathlib import Path

WP_ROOT = Path("/Users/jc/Local Sites/uucgsite/app/public")
EXTRACTED = Path("/Users/jc/Local Sites/greeleyuuc.org/mirror/extracted")
ID_INDEX = EXTRACTED / "_ids.json"


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


def main() -> int:
    if not ID_INDEX.exists():
        print(f"!! {ID_INDEX} missing; run import_pages.py first", file=sys.stderr)
        return 1
    ids = json.loads(ID_INDEX.read_text())["ids"]

    updated = 0
    for slug, post_id in ids.items():
        body_path = EXTRACTED / f"{slug}.html"
        if not body_path.exists():
            # Some slugs include the .html extension; try the .html variant
            if not (EXTRACTED / f"{slug}.html").exists():
                print(f"  skip: no extracted file for {slug}", flush=True)
                continue
        body = body_path.read_text(encoding="utf-8")
        result = wp(
            "post", "update", str(post_id),
            f"--post_content={body}",
        )
        if "Success" in result or "Updated" in result:
            updated += 1
            print(f"  ok: {slug} (#{post_id})", flush=True)
        else:
            print(f"  !! {slug}: {result[:200]}", flush=True)

    print(f"[done] {updated} pages updated", flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
