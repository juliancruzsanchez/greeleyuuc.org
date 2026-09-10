#!/usr/bin/env python3
"""
Mirror the live greeleyuuc.org (Weebly) site.

- Reads the sitemap.xml
- Downloads each URL into a local mirror directory, preserving the URL path
- Extracts page title, meta description, and a short text summary for each page
- Writes a JSON manifest of all pages and a top-level inventory
- Polite: serial fetches with a short delay, follows redirects, sets a UA
"""

from __future__ import annotations

import json
import re
import sys
import time
from html import unescape
from pathlib import Path
from urllib.parse import urljoin, urlparse

import requests
from bs4 import BeautifulSoup

SITEMAP = "https://greeleyuuc.org/sitemap.xml"
BASE = "https://www.greeleyuuc.org"
OUT = Path("/Users/jc/Local Sites/greeleyuuc.org/mirror")
HTML_DIR = OUT / "html"
ASSET_DIR = OUT / "assets"
UA = "UUCG-Mirror/1.0 (+local migration; contact: jc)"
TIMEOUT = 30
DELAY = 0.15
MAX_RETRIES = 3


def load_urls() -> list[str]:
    r = requests.get(SITEMAP, timeout=TIMEOUT, headers={"User-Agent": UA})
    r.raise_for_status()
    urls = re.findall(r"<loc>([^<]+)</loc>", r.text)
    seen: set[str] = set()
    out: list[str] = []
    for u in urls:
        if u not in seen:
            seen.add(u)
            out.append(u)
    return out


def url_to_local_path(url: str) -> Path:
    """Map a URL like https://www.greeleyuuc.org/foo/bar.html to HTML_DIR/foo/bar.html
    or https://www.greeleyuuc.org/foo/bar/ to HTML_DIR/foo/bar/index.html
    """
    p = urlparse(url)
    path = p.path.lstrip("/")
    if not path:
        path = "index.html"
    if path.endswith("/"):
        path = path + "index.html"
    if "." not in path.split("/")[-1]:
        path = path + ".html"
    return HTML_DIR / path


def fetch(url: str) -> str | None:
    last_err = None
    for attempt in range(MAX_RETRIES):
        try:
            r = requests.get(
                url,
                timeout=TIMEOUT,
                headers={"User-Agent": UA, "Accept": "text/html,*/*"},
                allow_redirects=True,
            )
            r.raise_for_status()
            ct = r.headers.get("content-type", "")
            if "text/html" not in ct and "xml" not in ct:
                # skip non-html (pdf, images in sitemap are unusual)
                return None
            return r.text
        except Exception as e:  # noqa: BLE001
            last_err = e
            time.sleep(0.5 * (attempt + 1))
    print(f"  !! failed after {MAX_RETRIES} tries: {url} :: {last_err}", file=sys.stderr)
    return None


def extract_meta(soup: BeautifulSoup, url: str) -> dict:
    title = ""
    if soup.title and soup.title.string:
        title = soup.title.string.strip()

    desc = ""
    md = soup.find("meta", attrs={"name": "description"})
    if md and md.get("content"):
        desc = md["content"].strip()

    canonical = ""
    cl = soup.find("link", rel="canonical")
    if cl and cl.get("href"):
        canonical = cl["href"].strip()

    h1 = ""
    h1el = soup.find("h1")
    if h1el:
        h1 = h1el.get_text(" ", strip=True)

    # collect text from the main body region (Weebly uses #wsite-content)
    main = soup.find(id="wsite-content") or soup.find("article") or soup.find("main") or soup.body
    text = ""
    if main:
        # strip scripts/styles
        for tag in main.find_all(["script", "style", "noscript"]):
            tag.decompose()
        text = re.sub(r"\s+", " ", main.get_text(" ", strip=True)).strip()
    text = unescape(text)
    snippet = text[:600]

    # nav/header detection so we can see what is in the site nav
    nav_links: list[dict[str, str]] = []
    nav = soup.find(id="navigation") or soup.find("nav") or soup.find(id="wsite-menu")
    if nav:
        for a in nav.find_all("a", href=True):
            label = a.get_text(" ", strip=True)
            if not label:
                continue
            nav_links.append({"label": label, "href": a["href"]})

    return {
        "url": url,
        "title": title,
        "description": desc,
        "canonical": canonical,
        "h1": h1,
        "nav": nav_links,
        "snippet": snippet,
        "word_count": len(text.split()) if text else 0,
    }


def main() -> int:
    HTML_DIR.mkdir(parents=True, exist_ok=True)
    ASSET_DIR.mkdir(parents=True, exist_ok=True)
    urls = load_urls()
    print(f"[sitemap] {len(urls)} unique URLs", flush=True)

    manifest: list[dict] = []
    failed: list[str] = []

    for i, url in enumerate(urls, 1):
        html = fetch(url)
        if html is None:
            failed.append(url)
            continue
        soup = BeautifulSoup(html, "html.parser")
        meta = extract_meta(soup, url)
        out_path = url_to_local_path(url)
        out_path.parent.mkdir(parents=True, exist_ok=True)
        out_path.write_text(html, encoding="utf-8")
        manifest.append({**meta, "local_path": str(out_path.relative_to(OUT))})
        if i % 25 == 0 or i == len(urls):
            print(f"[mirror] {i}/{len(urls)}  {url}", flush=True)
        time.sleep(DELAY)

    # write manifest
    (OUT / "manifest.json").write_text(
        json.dumps({"base": BASE, "count": len(manifest), "failed": failed, "pages": manifest}, indent=2),
        encoding="utf-8",
    )

    # build a top-level inventory grouped by first path segment
    inventory: dict[str, list[dict]] = {}
    for entry in manifest:
        seg = urlparse(entry["url"]).path.strip("/").split("/")[0] or "_root"
        # Weebly wraps .html pages without subdirs
        if seg.endswith(".html"):
            seg = "_root"
        if seg not in inventory:
            inventory[seg] = []
        inventory[seg].append(
            {
                "url": entry["url"],
                "title": entry["title"],
                "h1": entry["h1"],
                "word_count": entry["word_count"],
            }
        )
    (OUT / "inventory.json").write_text(json.dumps(inventory, indent=2), encoding="utf-8")

    # write a flat top-level pages list (deduped, by title)
    flat: list[dict] = []
    seen_titles: set[str] = set()
    for entry in manifest:
        # pick pages that look like real content pages (not archive items)
        path = urlparse(entry["url"]).path
        # skip archive monthly posts, keep the .html top-level pages
        if path.endswith(".html"):
            key = (entry["title"] or path).lower()
            if key in seen_titles:
                continue
            seen_titles.add(key)
            flat.append(
                {
                    "url": entry["url"],
                    "title": entry["title"],
                    "h1": entry["h1"],
                    "description": entry["description"],
                }
            )
    (OUT / "top_pages.json").write_text(json.dumps(flat, indent=2), encoding="utf-8")

    print(f"[done] mirrored={len(manifest)} failed={len(failed)}", flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
