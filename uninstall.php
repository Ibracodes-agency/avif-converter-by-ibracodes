<?php

/**
 * Remove the plugin's stored settings and stats on uninstall. Converted
 * images are kept — they are the site's content.
 */
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('ibracodes_avif_settings');
delete_option('ibracodes_avif_stats');
