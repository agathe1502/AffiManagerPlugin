<?php
global $am_plugin_version;
$am_plugin_version = '1.0';


function get_sql_query()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'am_blocks';

    $charset_collate = $wpdb->get_charset_collate();

    return "CREATE TABLE $table_name (
            `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
            `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
            `position` int(11) NOT NULL,
            `enable` tinyint(1) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
}

function am_create_db_for_pages()
{
    global $am_plugin_version;
    $sql = get_sql_query();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('am_plugin_version', $am_plugin_version);
}


function am_update_db_plugin()
{
    global $am_plugin_version;
    if (get_site_option('am_plugin_version') != $am_plugin_version) {
        am_update_db_for_pages();
    }
}

function am_update_db_for_pages()
{
    global $am_plugin_version;

    $installed_ver = get_option("am_plugin_version");

    if ($installed_ver != $am_plugin_version) {

        $sql = get_sql_query();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);

        update_option('am_plugin_version', $am_plugin_version);
    }
}

add_action( 'plugins_loaded', 'am_create_db_for_pages' );
add_action( 'plugins_loaded', 'am_update_db_plugin' );
