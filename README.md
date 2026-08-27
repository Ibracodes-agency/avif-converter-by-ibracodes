# AVIF Converter by Ibracodes

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
activate, then check **Settings → AVIF Converter by Ibracodes**.

The canonical directory listing text lives in `readme.txt` (WordPress.org
format); this file is the GitHub-facing overview.

## Requirements

PHP with GD or Imagick able to encode AVIF (ideal) or WebP (fallback).
WordPress 6.5+.

License: GPL-2.0-or-later
