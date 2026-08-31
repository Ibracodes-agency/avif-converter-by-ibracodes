=== Ibracodes AVIF Converter ===
Contributors: ibracodes
Tags: avif, webp, image optimization, performance, compression
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.3.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Convert images to AVIF and update the image URLs already saved inside Elementor and other page builders, so old pages get smaller too.

== Description ==

Converting a media library to AVIF is the easy half of the job. The half that
usually gets skipped: **pages built with Elementor, Divi, Beaver Builder or any
other builder keep serving the old JPEGs afterwards.** Builders copy the image
URL as plain text into the page they save, so converting the library changes
nothing for them — those pages only pick up the new files when a human opens
and re-saves every one of them.

This plugin does both halves. It converts your images, and then it updates
those stored URLs across posts, pages and builder data, so an existing site
gets faster without anyone re-saving a single page. There is a preview mode
that reports exactly what would change before anything is written.

The conversion itself runs through WordPress's own image editor, so the
generated image sizes simply **are** AVIF. No HTML filtering on every request,
no `.htaccess` rules, no redirect layer: your theme, your builder and
WooCommerce keep working exactly as before and serve the smaller files.

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

* It never modifies your original uploads. WordPress keeps the file exactly as
  you uploaded it and it stays reachable through the standard original-image
  functions, so anything that cannot read AVIF — social network link previews,
  for example — still works.
* It never deletes your old JPEG or PNG files, so image links inside existing
  content can never break.
* It does not contact any external server, collect any data, or add anything to
  the front end of your site.

**Requirements**

Your server needs GD or Imagick with AVIF support to produce AVIF, or with WebP
support for the fallback. The settings screen shows which one is available and
what to ask your host for if neither is.

Plugin page: https://ibracodes.com/resources/%d7%aa%d7%95%d7%a1%d7%a3-avif-%d7%9c%d7%95%d7%95%d7%a8%d7%93%d7%a4%d7%a8%d7%a1/

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

Nothing is lost. WordPress keeps your uploaded file untouched on the server and
serves the converted versions to visitors, so you always have the pristine
source to go back to.

= Does it keep my alt text, captions and titles? =

Yes. Converting only rewrites image files; the alt text, title, caption and
description stored with each image are never touched, including during the bulk
conversion.

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

= 1.3.0 =
* Renamed to Ibracodes AVIF Converter.
* Translations now come from translate.wordpress.org instead of shipping with
  the plugin.
* Declared the full settings schema on registration.

= 1.2.2 =
* Passes the official Plugin Check with no errors: translator comments,
  prefixed filter name, documented admin query.
* Tested on WordPress 7.1, including alt text, captions and titles surviving
  conversion.

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

= 1.3.0 =
Renamed to Ibracodes AVIF Converter.

= 1.2.2 =
Plugin Check clean and verified on WordPress 7.1.

= 1.2.1 =
Fixes inverted settings switches on left-to-right admins.
