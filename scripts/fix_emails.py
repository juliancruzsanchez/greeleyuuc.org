#!/usr/bin/env python3
"""
Decode Cloudflare email obfuscation placeholders in the extracted content and
replace them with proper mailto: links. Re-rewrites the parent <a> so the link
target is the actual email address.
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

EXTRACTED = Path("/Users/jc/Local Sites/greeleyuuc.org/mirror/extracted")
CFE_A_RE = re.compile(
    r'<a\b[^>]*class="__cf_email__"[^>]*data-cfemail="([a-f0-9]+)"[^>]*>.*?</a>',
    re.S | re.I,
)
# Some pages use a <span class="__cf_email__"> inside an <a> that points at
# /cdn-cgi/l/email-protection. We replace just the <span> in that case.
CFE_SPAN_RE = re.compile(
    r'<span\b[^>]*class="__cf_email__"[^>]*data-cfemail="([a-f0-9]+)"[^>]*>.*?</span>',
    re.S | re.I,
)


def decode_cfemail(hex_str: str) -> str:
    data = bytes.fromhex(hex_str)
    if not data:
        return ""
    key = data[0]
    return bytes(b ^ key for b in data[1:]).decode("utf-8", errors="replace")


def main() -> int:
    fixed = 0
    for path in EXTRACTED.rglob("*.html"):
        text = path.read_text(encoding="utf-8")
        new_text = text
        for m in list(CFE_A_RE.finditer(text)):
            email = decode_cfemail(m.group(1))
            if not email or "@" not in email:
                continue
            replacement = f'<a href="mailto:{email}">{email}</a>'
            new_text = new_text.replace(m.group(0), replacement, 1)
        for m in list(CFE_SPAN_RE.finditer(new_text)):
            email = decode_cfemail(m.group(1))
            if not email or "@" not in email:
                continue
            replacement = f'<a href="mailto:{email}">{email}</a>'
            new_text = new_text.replace(m.group(0), replacement, 1)
        if new_text != text:
            path.write_text(new_text, encoding="utf-8")
            fixed += 1
            print(f"  fixed: {path.relative_to(EXTRACTED)}", flush=True)
    print(f"[done] {fixed} files updated", flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
