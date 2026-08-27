=== Ibracodes AVIF Converter ===
Contributors: ibracodes
Tags: avif, webp, image optimization, performance, compression
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.2.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Serve your images as AVIF. Converts new uploads, the existing library, and image URLs already saved in old pages.

== Description ==

Images are usually the heaviest thing a page downloads. AVIF typically cuts
that weight by half or more compared to JPEG, with no visible quality loss.

This plugin converts your images to AVIF using WordPress's own image editor,
so the generated image sizes simply **are** AVIF. There is no HTML rewriting,
no `.htaccess` rules and no redirect layer: your theme, your page builder and
WooCommerce keep working exactly as before and serve the smaller files
automatically.

**What it does**

* **New uploads** — every generated image size is written as AVIF.
* **WebP fallback** — on servers that cannot encode AVIF the plugin uses WebP
  instead, which is still a large saving over JPEG and PNG. The settings screen
  tells you exactly what your server supports.
* **The existing library** — one click converts everything already uploaded,
  one image at a time so it works on shared hosting, with a progress bar and a
  running total of the bandwidth saved.
* **Old pages** — page builders such as Elementor copy image URLs as text into
  their saved pages, so those pages keep serving JPEG even after conversion. A
  second tool updates those stored URLs to the AVIF versions, so existing pages
  benefit without being opened and re-saved.

**What it does not do**

* It never modifies your original uploads. The original file stays untouched
  and remains available for anything that cannot read AVIF, such as social
  network link previews.
* It never deletes your old JPEG or PNG files, so image links inside existing
  content can never break.
* It does not contact any external server, collect any data, or add anything to
  the front end of your site.

**Requirements**

Your server needs GD or Imagick with AVIF support to produce AVIF, or with WebP
support for the fallback. The settings screen shows which one is available and
what to ask your host for if neither is.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it through
   Plugins → Add New.
2. Activate the plugin.
3. Go to Settings → Ibracodes AVIF Converter and check the server capability
   card at the top.
4. Optional: press "Convert entire library" to convert images you uploaded
   before installing the plugin.
5. Optional: press "Update all content" afterwards so pages built with a page
   builder start using the converted images.

== Frequently Asked Questions ==

= Will my images look worse? =

At the default quality of 70 the difference is not visible in normal use, while
the files are typically less than half the size. Lower values compress harder;
below about 65, fine text inside screenshots starts to smear.

= What happens to my original images? =

Nothing. Only the resized versions that WordPress generates are converted. The
original upload is kept exactly as you uploaded it.

= My server does not support AVIF. Is the plugin useless? =

No. It automatically falls back to WebP, which almost every server supports and
which is still much smaller than JPEG or PNG. The settings screen states which
format is in use.

= Do old browsers still work? =

Every browser released since 2023 supports AVIF, and older ones support WebP.
Visitors on very old software may not see converted images, which is why the
plugin keeps the original files rather than deleting them.

= Why do my Elementor pages still show the old images? =

Page builders store the image URL as text inside the page, so the page keeps
requesting the old file. Run "Update all content" once after converting the
library and those stored URLs are updated to the converted images.

= Does the content update change anything else in my pages? =

No. It only changes the file extension of image URLs that point to your own
uploads folder, and only when the converted file actually exists. Everything
else in the content is left untouched. As with any bulk change, take a database
backup first.

= Does the plugin send any data anywhere? =

No. It makes no external requests and collects no information.

== Screenshots ==

1. The settings screen: server capability, conversion options and the bulk tools.
2. Converting the existing media library with live progress and bandwidth saved.
3. Updating image URLs stored inside existing pages and page-builder data.

== Changelog ==

= 1.2.1 =
* Directory assets and screenshots.
* Fixed the settings switches rendering inverted on left-to-right admins.
* The content updater now has its own wording and a preview that reports
  what would change without writing anything.

= 1.2.0 =
* Prepared for the WordPress plugin directory: renamed to Ibracodes AVIF
  Converter, longer unique prefixes, and standard WordPress data APIs.

= 1.1.0 =
* Added the content URL updater, so pages built with a page builder use the
  converted images without being re-saved.

= 1.0.0 =
* First release: AVIF conversion for new uploads, WebP fallback, server
  capability detection, quality setting and bulk conversion of the media
  library.

== Upgrade Notice ==

= 1.2.1 =
Fixes inverted settings switches on left-to-right admins.
