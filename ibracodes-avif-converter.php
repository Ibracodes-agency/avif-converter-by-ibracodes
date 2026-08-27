<?php

/**
 * Plugin Name:       Ibracodes AVIF Converter
 * Plugin URI:        https://ibracodes.com/plugins/avif-converter/
 * Description:       Converts uploaded images to AVIF (WebP fallback), converts the existing library, and updates image URLs in old content.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ibracodes
 * Author URI:        https://ibracodes.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ibracodes-avif-converter
 * Domain Path:       /languages
 */

namespace IbracodesAvif;

if (! defined('ABSPATH')) {
    exit;
}

const VERSION = '1.2.0';
const OPTION = 'ibracodes_avif_settings';
const STATS = 'ibracodes_avif_stats';
const MARKER = '_ibracodes_avif_converted';

define(__NAMESPACE__.'\\DIR', plugin_dir_path(__FILE__));
define(__NAMESPACE__.'\\URL', plugin_dir_url(__FILE__));

require DIR.'includes/convert.php';
require DIR.'includes/bulk.php';
require DIR.'includes/rewrite.php';
require DIR.'includes/settings.php';
