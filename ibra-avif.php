<?php

/**
 * Plugin Name: Ibracodes AVIF Converter
 * Plugin URI: https://github.com/Ibracodes-agency/ibra-avif
 * Description: Serves your images as AVIF automatically — new uploads convert on the fly, the existing library converts in one click, and servers without AVIF support fall back to WebP.
 * Version: 1.1.0
 * Author: ibracodes
 * Author URI: https://ibracodes.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ibra-avif
 * Domain Path: /languages
 */

namespace IbraAvif;

if (! defined('ABSPATH')) {
    exit;
}

const VERSION = '1.1.0';
const OPTION = 'iaf_settings';
const STATS = 'iaf_stats';
const MARKER = '_iaf_converted';

define(__NAMESPACE__.'\\DIR', plugin_dir_path(__FILE__));
define(__NAMESPACE__.'\\URL', plugin_dir_url(__FILE__));

require DIR.'includes/convert.php';
require DIR.'includes/bulk.php';
require DIR.'includes/rewrite.php';
require DIR.'includes/settings.php';

add_action('init', function () {
    load_plugin_textdomain('ibra-avif', false, dirname(plugin_basename(__FILE__)).'/languages');
});
