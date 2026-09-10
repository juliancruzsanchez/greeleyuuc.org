#!/usr/bin/env python3
"""
Extract page content from the Weebly mirror into per-page HTML files.

Minimal cleaning: keep the Weebly structure (the modern theme's CSS handles it),
strip obvious noise (scripts, styles, social-share buttons, comments), and
rewrite relative URLs to absolute so assets keep loading from the Weebly CDN.
"""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path
from urllib.parse import urlparse

from bs4 import BeautifulSoup

MIRROR = Path("/Users/jc/Local Sites/greeleyuuc.org/mirror/html")
OUT = Path("/Users/jc/Local Sites/greeleyuuc.org/mirror/extracted")
OUT.mkdir(parents=True, exist_ok=True)


def absolutize(u: str) -> str:
    if not u:
        return u
    if u.startswith("//"):
        return "https:" + u
    if u.startswith("/"):
        return "https://www.greeleyuuc.org" + u
    return u


def clean(soup: BeautifulSoup) -> None:
    # Remove obviously dead nodes
    for tag in soup.find_all(["script", "style", "noscript", "iframe"]):
        # Keep YouTube / Vimeo iframes
        src = tag.get("src", "")
        if "youtube" in src or "vimeo" in src:
            continue
        tag.decompose()

    for sel in [
        ".blog-social", ".blog-comments", ".blog-comments-bottom",
        ".blog-separator",
        ".wsite-header", ".wsite-footer",
    ]:
        for tag in soup.select(sel):
            tag.decompose()

    # Convert Weebly video divs into a link to the original asset
    for vd in soup.find_all("div", class_="wsite-video"):
        # The video URL is hidden in a JSON-ish blob in the iframe; we cannot
        # recover it reliably. Drop the wrapper and leave a note.
        note = soup.new_tag("p")
        note.string = "[Video — see the original Weebly page for playback.]"
        vd.replace_with(note)

    # Weebly's `wsite-html5audio` carries the sermon MP3 — keep the audio element
    for audio in soup.find_all("audio"):
        src = audio.get("src", "")
        if src and not src.startswith(("http", "//")):
            audio["src"] = absolutize(src)
        audio["preload"] = "none"
        audio["controls"] = ""

    # Weebly images
    for img in soup.find_all("img"):
        src = img.get("src", "")
        if src and not src.startswith(("http", "data:", "//")):
            img["src"] = absolutize(src)
        elif src.startswith("//"):
            img["src"] = "https:" + src
        img["loading"] = "lazy"

    # Weebly links
    for a in soup.find_all("a"):
        href = a.get("href", "")
        if href and not href.startswith(("http", "mailto:", "#", "tel:")):
            a["href"] = absolutize(href)


def extract_title(soup: BeautifulSoup, fallback: str) -> str:
    t = soup.find("title")
    if t and t.string:
        s = t.string.strip()
        if s and s.lower() not in {"404 - page not found", "page not found"}:
            return s
    h1 = soup.find("h1")
    if h1:
        s = h1.get_text(" ", strip=True)
        if s:
            return s
    h2 = soup.find("h2", class_="blog-title")
    if h2:
        a = h2.find("a")
        if a:
            return a.get_text(" ", strip=True)
        return h2.get_text(" ", strip=True)
    h2 = soup.find("h2", class_="wsite-content-title")
    if h2:
        return h2.get_text(" ", strip=True)
    return fallback


def page_slug_from_path(rel: str) -> str:
    rel = rel[:-5] if rel.endswith(".html") else rel
    rel = rel[:-6] if rel.endswith("/index") else rel
    rel = rel.strip("/")
    return rel or "home"


def main() -> int:
    files = sorted(MIRROR.rglob("*.html"))
    print(f"[extract] {len(files)} html files", flush=True)

    index: list[dict] = []
    for i, fpath in enumerate(files, 1):
        rel = str(fpath.relative_to(MIRROR))
        if rel == "index.html":
            continue
        try:
            with fpath.open(encoding="utf-8", errors="replace") as f:
                raw = f.read()
        except Exception as e:  # noqa: BLE001
            print(f"  !! read error: {rel} :: {e}", file=sys.stderr)
            continue

        soup = BeautifulSoup(raw, "html.parser")
        title = extract_title(soup, fallback=rel)
        is_sub = "/" in rel
        blog_post = soup.find(class_="blog-post")

        if is_sub and blog_post:
            t_el = blog_post.find("h2", class_="blog-title")
            if t_el:
                a = t_el.find("a")
                if a:
                    title = a.get_text(" ", strip=True)
                else:
                    title = t_el.get_text(" ", strip=True)
            blog_content = blog_post.find(class_="blog-content")
            body_node = blog_content if blog_content else blog_post
        else:
            # Pick the wsite-section-elements div with the most content.
            secs = soup.find_all(class_="wsite-section-elements")
            if secs:
                body_node = max(secs, key=lambda s: len(s.get_text(" ", strip=True)))
            else:
                body_node = soup.find(id="wsite-content")

        if body_node is None:
            body_html = ""
        else:
            # Clone so cleaning doesn't touch the source
            body_html = str(body_node)
            sub = BeautifulSoup(body_html, "html.parser")
            clean(sub)
            body_html = str(sub)

        out_path = OUT / rel
        out_path.parent.mkdir(parents=True, exist_ok=True)
        if body_html:
            out_path.write_text(body_html, encoding="utf-8")

        page_type = "sub" if (is_sub and blog_post) else "root"
        if page_type == "sub":
            slug = Path(rel).stem
            section = page_slug_from_path(rel.split("/")[0] + ".html")
            date_el = soup.find(class_="date-text") if blog_post else None
            date = date_el.get_text(" ", strip=True) if date_el else ""
        else:
            slug = page_slug_from_path(rel)
            section = ""
            date = ""

        index.append({
            "rel": rel,
            "slug": slug,
            "title": title,
            "type": page_type,
            "section": section,
            "date": date,
            "body_path": rel if body_html else "",
            "body_len": len(body_html),
        })
        if i % 50 == 0:
            print(f"[extract] {i}/{len(files)}  {rel}", flush=True)

    (OUT / "_index.json").write_text(json.dumps(index, indent=2), encoding="utf-8")
    counts = {"root": 0, "sub": 0}
    for e in index:
        counts[e["type"]] = counts.get(e["type"], 0) + 1
    print(f"[done] {len(index)} pages extracted. types={counts}", flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
