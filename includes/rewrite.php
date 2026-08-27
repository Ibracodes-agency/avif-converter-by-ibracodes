<?php

/**
 * Content URL rewriting: page builders (Elementor most of all) copy image
 * URLs as text into post content and meta, so converted images keep being
 * served as JPEG until every page is re-saved. This queue rewrites those
 * stored URLs to their AVIF twins — one post per request, and a URL is only
 * touched when the twin file actually exists on disk.
 *
 * Only the EXTENSION of a matched size URL changes, so JSON-escaped URLs
 * (Elementor stores https:\/\/…) survive untouched around the swap.
 * Serialized meta is round-tripped through (un)serialization — never
 * string-replaced raw — and meta containing objects is skipped entirely.
 */

namespace IbracodesAvif;

use WP_Error;
use WP_REST_Request;

if (! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('avif-converter-by-ibracodes/v1', '/rewrite-queue', [
        'methods' => 'GET',
        'permission_callback' => fn () => current_user_can('manage_options'),
        'callback' => __NAMESPACE__.'\\rest_rewrite_queue',
    ]);

    register_rest_route('avif-converter-by-ibracodes/v1', '/rewrite', [
        'methods' => 'POST',
        'permission_callback' => fn () => current_user_can('manage_options'),
        'callback' => __NAMESPACE__.'\\rest_rewrite',
        'args' => [
            'id' => [
                'required' => true,
                'validate_callback' => fn ($value) => is_numeric($value) && (int) $value > 0,
                'sanitize_callback' => 'absint',
            ],
            'preview' => [
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ],
        ],
    ]);
});

function rest_rewrite_queue()
{
    $types = array_diff(get_post_types(['show_ui' => true]), ['attachment']);

    return ['ids' => array_map('intval', get_posts([
        'post_type' => array_values($types),
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]))];
}

function rest_rewrite(WP_REST_Request $request)
{
    $id = (int) $request['id'];
    $preview = (bool) $request['preview'];

    if (! get_post($id)) {
        return new WP_Error('not_found', __('No such post', 'avif-converter-by-ibracodes'), ['status' => 404]);
    }

    $replaced = rewrite_post($id, $preview);

    if ($replaced && ! $preview) {
        $stats = (array) get_option(STATS, []);
        $stats['rewritten'] = (int) ($stats['rewritten'] ?? 0) + $replaced;
        update_option(STATS, $stats, false);
    }

    return ['id' => $id, 'replaced' => $replaced, 'preview' => $preview];
}

/**
 * Swap size-image URLs (…-300x200.jpg/png/webp) under the uploads dir for
 * their .avif twin — only when that twin exists. Handles plain text,
 * srcset lists and JSON-escaped URLs alike, because only the extension of
 * the matched string is replaced.
 */
function rewrite_urls(string $text, int &$count): string
{
    static $pattern = null;
    $uploads = wp_get_upload_dir();

    if ($pattern === null) {
        $bases = preg_quote($uploads['baseurl'], '#').'|'.preg_quote(str_replace('/', '\/', $uploads['baseurl']), '#');
        $pattern = '#(?:'.$bases.')[^"\'\s<>()]+?-\d+x\d+\.(?:jpe?g|png|webp)#i';
    }

    return (string) preg_replace_callback($pattern, function ($match) use ($uploads, &$count) {
        $url = str_replace('\/', '/', $match[0]);
        $twin = preg_replace('#\.(?:jpe?g|png|webp)$#i', '.avif', $uploads['basedir'].substr($url, strlen($uploads['baseurl'])));

        if (! is_file($twin)) {
            return $match[0];
        }

        $count++;

        return preg_replace('#\.(?:jpe?g|png|webp)$#i', '.avif', $match[0]);
    }, $text);
}

/**
 * Recursively rewrite string values; objects are left strictly alone
 * (corrupting an object graph is worse than a stale URL).
 */
function rewrite_value(mixed $value, int &$count): mixed
{
    if (is_string($value)) {
        return rewrite_urls($value, $count);
    }

    if (is_array($value)) {
        foreach ($value as $key => $item) {
            $value[$key] = rewrite_value($item, $count);
        }
    }

    return $value;
}

function contains_object(mixed $value): bool
{
    if (is_object($value)) {
        return true;
    }

    if (is_array($value)) {
        foreach ($value as $item) {
            if (contains_object($item)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Rewrite one post: content, excerpt and every meta row. Returns how many
 * URLs were swapped. Elementor's CSS cache is invalidated when its data
 * changed, so regenerated stylesheets pick the new URLs up too.
 *
 * In preview mode nothing is written — the same code path just reports how
 * many URLs a real run would change.
 */
function rewrite_post(int $post_id, bool $preview = false): int
{
    $count = 0;
    $post = get_post($post_id);

    $content = rewrite_urls($post->post_content, $count);
    $excerpt = rewrite_urls($post->post_excerpt, $count);

    if (! $preview && ($content !== $post->post_content || $excerpt !== $post->post_excerpt)) {
        wp_update_post([
            'ID' => $post_id,
            'post_content' => wp_slash($content),
            'post_excerpt' => wp_slash($excerpt),
        ]);
    }

    $elementorChanged = false;

    // get_post_meta() with no key returns every row already unserialized and
    // cached — no direct database query needed.
    foreach (get_post_meta($post_id) as $key => $raw) {
        foreach ($raw as $index => $stored) {
            $value = maybe_unserialize($stored);

            if (contains_object($value)) {
                continue;
            }

            $rowCount = 0;
            $rewritten = rewrite_value($value, $rowCount);

            if ($rowCount === 0) {
                continue;
            }

            if (! $preview) {
                // update_post_meta() unslashes what it stores, which would
                // strip the \/ escaping out of builder JSON — pre-slash the
                // new value. $value stays raw: prev_value matches the row.
                update_post_meta($post_id, $key, wp_slash($rewritten), $value);
            }

            $count += $rowCount;

            if (str_starts_with($key, '_elementor')) {
                $elementorChanged = true;
            }
        }
    }

    if ($elementorChanged && ! $preview) {
        delete_post_meta($post_id, '_elementor_css');
        delete_post_meta($post_id, '_elementor_element_cache');
    }

    return $count;
}
