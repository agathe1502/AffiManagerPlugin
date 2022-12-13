<?php
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// Blocking direct access to plugin      -=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
defined('ABSPATH') or die('Are you crazy!');

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// Load plugin translations              -=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
add_action('plugins_loaded', 'am_translate_load_textdomain', 1);
if (!function_exists('am_translate_load_textdomain')) {
    function am_translate_load_textdomain()
    {
        $path = basename(dirname(__FILE__)) . '/languages/';
        load_plugin_textdomain(AM_ID_LANGUAGES, false, $path);
    }
}

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// Load plugin files                     -=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
if (!function_exists('is_plugin_active'))
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// Include Titan Framework               -=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
$titan_check_framework_install = 'titan-framework/titan-framework.php';
// --- Check if plugin titan framework is installed
if (is_plugin_active($titan_check_framework_install)) {
    require_once(WP_CONTENT_DIR . '/plugins/titan-framework/titan-framework-embedder.php');
} else {
    require_once(AM_PATH . 'lib/titan-framework/titan-framework-embedder.php');
}

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// Initialize plugin SQL Debug Mode      -=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
defined('_AM_DEBUG') or define('_AM_DEBUG', false);

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// Initialize plugin Files               -=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
$file = AM_PATH . "utils/functions.php";
if (file_exists($file)) require_once($file);

$amFiles = ['interface'];
foreach ($amFiles as $amFile) {
    $file = AM_PATH . 'am-' . $amFile . '.php';
    if (file_exists($file)) require_once($file);
}

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

if (!function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if (!function_exists('am_get_version')) {

    function am_get_version($am_infos = 'Version')
    {
        $plugin_data = get_plugin_data(AM_PATH . 'amPlugin.php');
        $plugin_version = $plugin_data["$am_infos"];

        return $plugin_version;
    }
}
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

// Create / Update table campaigns
require_once(AM_PATH . 'am-create-db.php');

// Replace content of post
if (!function_exists( 'str_get_html' ) ) {
    require_once(AM_PATH . 'lib/simple_html_dom.php');
}
