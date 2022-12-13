<?php
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// Blocking direct access to plugin      -=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
defined('ABSPATH') or die('Are you crazy!');


add_action( 'tf_create_options', 'am_create_options' );
function am_create_options() {

    remove_filter( 'admin_footer_text', 'addTitanCreditText' );

    /***************************************************************
     * Launch options framework instance
     ***************************************************************/
    $am_options = TitanFramework::getInstance( 'am' );
    /***************************************************************
     * Create option menu item
     ***************************************************************/
    $am_panel = $am_options->createAdminPanel( array(
        'menu_title' => "AffiManager",
        'name' => '<a></a>',
        'icon'       => 'dashicons-external',
        'id'         => AM_ID,
        'capability' => 'manage_options',
        'desc'       => '',
    ) );

    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
    // Create settings panel tabs              -=
    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
    $dashboardTab = $am_panel->createTab( array(
        'name' => __( 'Settings', AM_ID_LANGUAGES ),
        'id'   => 'dashboard',
    ) );

    $amOptionFile = AM_PATH .'dashboard.php';
    if (file_exists($amOptionFile))
        require_once($amOptionFile);

    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
    // Launch options framework instance     -=
    // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
    $dashboardTab->createOption( array(
        'type'      => 'save',
        'save'      => __( 'Save', AM_ID_LANGUAGES ),
        'use_reset' => false,
    ) );

    create_missing_files_after_update();

} // END am_create_options

function create_missing_files_after_update() {
    $am_options = TitanFramework::getInstance( 'am' );

    // Création des fichiers s'ils existent (supprimés lors d'une mise à jour du plugin)
    $previous_file_update_db = AM_PATH . $am_options->getOption( 'am_update_blocks_table' );
    if ( !file_exists( $previous_file_update_db ) && $previous_file_update_db != AM_PATH) {
        $content = "<?php require_once '" . AM_PATH . "am-update-blocks-table.php';";
        file_put_contents( $previous_file_update_db, $content );
    }
}

function am_save_options($container, $activeTab, $options ) {

    if ( empty( $activeTab ) ) {

        return;
    }

    $am_options = maybe_unserialize( get_option( 'am_options' ) );

    if ( empty( $am_options['am_api_key'] ) ||
        empty( $am_options['am_update_blocks_table'] ) ) {
        return;
    }

    $data = array(
        'domain_url' => get_site_url(),
        'wordpress_plugin_url'  => AM_URL . $am_options['am_update_blocks_table'],
        'wordpress_plugin_version' => am_get_version(),
    );

    $response = am_curl( $am_options['am_api_key'], $data );

    $parsed_response = json_decode($response, true);

    var_dump($parsed_response);
    am_set_connection_status( $parsed_response );
}

add_action( 'tf_save_admin_am', 'am_save_options', 10, 3 );

function am_curl($api_key, $data ) {

    $data_json = json_encode( $data );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://affimanager.nilys.com/api/wordpress-plugin/check-key',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_TIMEOUT => x0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data_json,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $api_key
        ),
    ));

    $response = curl_exec( $curl );
    curl_close( $curl );

    return $response;
}

function am_get_connection_status() {

    var_dump('get statuses');

    $connection_statuses = array(
        'fail'                => __( 'Unsuccessful connection!', AM_ID_LANGUAGES ),
        'ok'                  => __( 'Login successful!', AM_ID_LANGUAGES ),
        'blocked_by_firewall' => __( 'A firewall seems to block the connection of the plugin !', AM_ID_LANGUAGES ),
        'wrong_token'         => __( 'The connection failed. Check that you have added your site to AffiManager.', AM_ID_LANGUAGES ),
    );

    $am_options = maybe_unserialize( get_option( 'am_options' ) );

    var_dump($am_options['am_connection_status']);
    if ( ! empty( $am_options['am_connection_status'] ) && array_key_exists( $am_options['am_connection_status'], $connection_statuses ) ) {

        if ( 'ok' === $am_options['am_connection_status'] ) {

            return am_get_styled_status( $connection_statuses[ $am_options['am_connection_status'] ] );
        } else {

            return am_get_styled_status( $connection_statuses[ $am_options['am_connection_status'] ], false );
        }
    }

    return am_get_styled_status( $connection_statuses['fail'], false );
}

function am_get_styled_status($message, $is_successful = true ) {

    if ( $is_successful ) {

        return '<span style="color: #00FF00;"><span style="font-size: 25px; vertical-align: middle;">&#10003;</span>' . $message . '</span>';
    }

    return '<span style="color: #FF0000;"><span style="font-size: 25px; vertical-align: middle;">&#10005;</span>' . $message . '</span>';
}

function am_pre_save_admin($container, $activeTab, $options ) {

    $am_options = TitanFramework::getInstance( 'am' );

    $api_key = $am_options->getOption( 'am_api_key' );

    if ( empty( $api_key )) {

        am_redirect_to_form();
        exit();
    }


    $random_file_update_db = am_random3() . '.php';
    $previous_file_update_db = AM_PATH . $am_options->getOption( 'am_update_blocks_table' );
    $container->owner->setOption( 'am_update_blocks_table', $random_file_update_db );

    $new_file_update_db = AM_PATH . $random_file_update_db;

    if ( !file_exists( $previous_file_update_db ) || $previous_file_update_db == AM_PATH) {
        $content = "<?php require_once '" . AM_PATH . "am-update-blocks-table.php';";

        file_put_contents( $new_file_update_db, $content );
    } else {
        rename( $previous_file_update_db, $new_file_update_db );
    }
}

add_action( 'tf_pre_save_admin_am', 'am_pre_save_admin', 10, 3 );

function am_redirect_to_form() {

    $url = wp_get_referer();
    $url = add_query_arg( 'page', urlencode( AM_ID ), $url );
    $url = add_query_arg( 'tab', urlencode( 'dashboard' ), $url );

    wp_redirect( esc_url_raw( $url ) );
}


function am_set_connection_status($parsed_response ) {

    if ( ! empty( $parsed_response['status'] ) ) {

        if ( $parsed_response['status'] === "ok" ) {

            $connection_status = 'ok';
        } else if ( ! empty( $parsed_response['code'] ) ) {

            $connection_status = $parsed_response['code'];
        } else {

            $connection_status = 'fail';
        }

    } else {

        $connection_status = 'fail';
    }

    $am_options = maybe_unserialize( get_option( 'am_options' ) );
    $am_options['am_connection_status'] = $connection_status;
    update_option( 'am_options', maybe_serialize( $am_options ) );
}

