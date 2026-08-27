<?php

/**
 * Bulk conversion of the existing library: the admin JS fetches the queue,
 * then converts one attachment per request so progress is visible and a
 * slow shared host never times out. Regeneration writes the sizes in the
 * target format; the OLD intermediate files are deliberately kept on disk —
 * deleting them would 404 any old post content that hot-links a sized URL.
 * "Saved" therefore measures what visitors download, not disk usage.
 */

namespace IbracodesAvif;

use WP_Error;
use WP_REST_Request;

if (! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('avif-converter-by-ibracodes/v1', '/queue', [
        'methods' => 'GET',
        'permission_callback' => fn () => current_user_can('manage_options'),
        'callback' => __NAMESPACE__.'\\rest_queue',
    ]);

    register_rest_route('avif-converter-by-ibracodes/v1', '/convert', [
        'methods' => 'POST',
        'permission_callback' => fn () => current_user_can('manage_options'),
        'callback' => __NAMESPACE__.'\\rest_convert',
    ]);
});

/**
 * Attachments that still need conversion to the current target format.
 *
 * @return int[]
 */
function pending_ids(): array
{
    if (! target_mime()) {
        return [];
    }

    return array_map('intval', get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'post_mime_type' => SOURCE_MIMES,
        'posts_per_page' => -1,
        'fields' => 'ids',
        // one-off admin query behind a manage_options gate, not a front-end path
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        'meta_query' => [
            'relation' => 'OR',
            ['key' => MARKER, 'compare' => 'NOT EXISTS'],
            ['key' => MARKER, 'value' => target_mime(), 'compare' => '!='],
        ],
    ]));
}

function rest_queue()
{
    return ['ids' => pending_ids(), 'target' => target_mime()];
}

function rest_convert(WP_REST_Request $request)
{
    $id = (int) $request['id'];
    $file = $id ? get_attached_file($id) : false;

    if (! $file || ! file_exists($file)) {
        return new WP_Error('not_found', 'No such attachment file', ['status' => 404]);
    }

    if (! active()) {
        return new WP_Error('inactive', 'Conversion is disabled or unsupported', ['status' => 400]);
    }

    require_once ABSPATH.'wp-admin/includes/image.php';

    $before = generated_bytes($id);

    $metadata = wp_generate_attachment_metadata($id, $file);

    if (! $metadata) {
        return new WP_Error('failed', 'Metadata regeneration failed', ['status' => 500]);
    }

    wp_update_attachment_metadata($id, $metadata);

    $after = generated_bytes($id);
    $saved = max(0, $before - $after);

    $stats = (array) get_option(STATS, []);
    $stats['saved'] = (int) ($stats['saved'] ?? 0) + $saved;
    update_option(STATS, $stats, false);

    return ['id' => $id, 'before' => $before, 'after' => $after, 'saved' => $saved];
}

/**
 * Total bytes of the attachment's generated intermediate files — what a
 * page actually serves (the untouched original is not part of it).
 */
function generated_bytes(int $attachment_id): int
{
    $metadata = wp_get_attachment_metadata($attachment_id);
    $file = get_attached_file($attachment_id);

    if (! $metadata || ! $file) {
        return 0;
    }

    $dir = dirname($file);
    $total = 0;

    foreach (($metadata['sizes'] ?? []) as $size) {
        $path = $dir.'/'.$size['file'];

        if (is_file($path)) {
            $total += (int) filesize($path);
        }
    }

    return $total;
}
