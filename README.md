# Ibracodes AVIF Converter

Serves your WordPress images as **AVIF** automatically. Built by
[ibracodes](https://ibracodes.com).

- **New uploads** — every generated image size is written as AVIF via
  WordPress's own image editor (`image_editor_output_format`). No `<picture>`
  rewriting, no .htaccess tricks: the sizes simply *are* AVIF.
- **WebP fallback** — servers that can't encode AVIF (no GD/Imagick AVIF
  support) automatically fall back to WebP. The settings page shows exactly
  what your server supports.
- **Existing library** — one click converts everything already uploaded, in
  batches with a progress bar and a "visitors download X less" counter.
- **Existing content** — a second queue rewrites stored image URLs to their
  AVIF twins across post content and page-builder data (Elementor's
  JSON-escaped URLs included), so old pages serve AVIF without being
  re-saved. Serialized meta is round-tripped safely, object meta is never
  touched, a URL is only swapped when its AVIF file exists, and Elementor's
  CSS cache is invalidated for rewritten pages. Take a DB backup first, as
  with any bulk content change.
- **Originals are never touched** — the pristine upload remains for
  social-preview scrapers (which don't render AVIF) and as a lossless source.
- **Old size files are kept** so image URLs inside existing posts never break.
- Quality setting (default 70 — below ~65 fine text in screenshots smears).
- Skip conversion programmatically with the `ibracodes_avif_skip` filter.

## Install

Copy this folder to `wp-content/plugins/ibra-avif` (or upload a zip),
activate, then check **Settings → Ibracodes AVIF Converter**.

The canonical directory listing text lives in `readme.txt` (WordPress.org
format); this file is the GitHub-facing overview.

## Requirements

PHP with GD or Imagick able to encode AVIF (ideal) or WebP (fallback).
WordPress 6.5+.

License: GPL-2.0-or-later

## Releasing to WordPress.org

Publishing is automated — a tag is the release:

```bash
# 1. bump the version in three places (all must agree)
#    - ibracodes-avif-converter.php  → Version: and const VERSION
#    - readme.txt                       → Stable tag: and a changelog entry
# 2. verify locally
bin/check-version.sh
# 3. release
git tag 1.2.3 && git push origin main --tags
```

The `deploy` workflow rejects the release if those versions disagree, then
builds the plugin (honouring `.distignore`), commits it to SVN trunk, creates
the matching SVN tag, and uploads the banners, icons and screenshots from
`.wordpress-org/`.

Banner, icon, screenshot or `readme.txt` changes alone do not need a release:
pushing them to `main` runs the `assets` workflow, which updates the listing
in place.

**One-time setup** (after the plugin is approved and SVN credentials arrive):
add two repository secrets under Settings → Secrets and variables → Actions —
`SVN_USERNAME` and `SVN_PASSWORD`, your WordPress.org login. Nothing is
published until those exist.
