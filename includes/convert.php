<?php

/**
 * Core conversion. WordPress generates every intermediate size through its
 * image editor — the image_editor_output_format filter re-targets those to
 * AVIF (or WebP where the server can't encode AVIF). The ORIGINAL upload is
 * never touched: it stays available for anything that can't render modern
 * formats (social-preview scrapers most of all).
 */

namespace IbraAvif;

if (! defined('ABSPATH')) {
    exit;
}

const SOURCE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

/**
 * Stored settings over defaults.
 */
function settings(): array
{
    return array_replace([
        'enabled' => 1,
        // below ~65 fine text in UI screenshots turns to mush
        'quality' => 70,
    ], (array) get_option(OPTION, []));
}

/**
 * The best format this server can actually encode: AVIF, WebP as the
 * fallback, or null when neither is available (plugin stays inert).
 */
function target_mime(): ?string
{
    static $target = false;

    if ($target === false) {
        $target = null;

        foreach (['image/avif', 'image/webp'] as $mime) {
            if (wp_image_editor_supports(['mime_type' => $mime])) {
                $target = $mime;
                break;
            }
        }
    }

    return $target;
}

/**
 * Whether conversion is currently active (enabled + server capable).
 */
function active(): bool
{
    return settings()['enabled'] && target_mime() !== null;
}

add_filter('image_editor_output_format', function ($formats) {
    if (! active() || apply_filters('ibra_avif_skip', false)) {
        return $formats;
    }

    $target = target_mime();

    $formats['image/jpeg'] = $target;
    $formats['image/png'] = $target;

    if ($target === 'image/avif') {
        $formats['image/webp'] = $target;
    }

    return $formats;
});

add_filter('wp_editor_set_quality', function ($quality, $mime) {
    if (in_array($mime, ['image/avif', 'image/webp'], true)) {
        return (int) settings()['quality'];
    }

    return $quality;
}, 10, 2);

/**
 * Mark converted attachments (new uploads and bulk regenerations both end
 * up here) and count them once.
 */
add_filter('wp_generate_attachment_metadata', function ($metadata, $attachment_id) {
    if (! active() || ! in_array(get_post_mime_type($attachment_id), SOURCE_MIMES, true)) {
        return $metadata;
    }

    if (get_post_meta($attachment_id, MARKER, true) !== target_mime()) {
        update_post_meta($attachment_id, MARKER, target_mime());

        $stats = (array) get_option(STATS, []);
        $stats['converted'] = (int) ($stats['converted'] ?? 0) + 1;
        update_option(STATS, $stats, false);
    }

    return $metadata;
}, 20, 2);
