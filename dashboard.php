<?php
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// Blocking direct access to plugin      -=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
defined('ABSPATH') or die('Are you crazy!');

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// Create tab's dashboard                -=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// ----------------------------------------
$dashboardTab->createOption(array(
    'name' => __('API', AM_ID_LANGUAGES),
    'type' => 'heading',
));
// ----------------------------------------
$dashboardTab->createOption(array(
    'id' => 'am_api_key',
    'name' => __('API key', AM_ID_LANGUAGES),
    'type' => 'text',
    'desc' => __('Fill in your key (API)', AM_ID_LANGUAGES),
    'unit' => am_get_connection_status()
));
$dashboardTab->createOption(array(
    'id' => 'am_update_blocks_table',
    'type' => 'text',
    'hidden' => true
));
$dashboardTab->createOption(array(
    'id' => 'am_get_categories',
    'type' => 'text',
    'hidden' => true
));
// ----------------------------------------
if (!function_exists("am_admin_notice_error")) {
    function am_admin_notice_error()
    {
        $am_options = TitanFramework::getInstance('am');
        $am_class = 'notice notice-error';

        $menu_name = AM_NAME;
        $am_current_options = maybe_unserialize(get_option('am_options'));

        if (!empty($am_current_options['am_menu_name'])) {
            $menu_name = $am_current_options['am_menu_name'];
        }

        $am_message = strtoupper($menu_name) . ': ' . sprintf(__('Fill in all <a href="%s">dashboard options</a>', AM_ID_LANGUAGES), get_admin_url(get_current_blog_id(), 'admin.php?page=AffiManager&tab=dashboard'));

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($am_class), $am_message);
    }
}

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
//     Check if options are not empty    -=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
if (empty($am_options->getOption('am_api_key'))) {
    add_action('admin_notices', 'am_admin_notice_error');
}
?>
