<?php
// ------------------------------------------------
// if uninstall.php is not called by WordPress, die
// ------------------------------------------------
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

function send_unauthentication( $api_key, $data ) {

    $data_json = json_encode( $data );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://affimanager.nilys.com/api/wordpress-plugin/unauthenticate',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_TIMEOUT => 0,
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

$am_options = maybe_unserialize( get_option( 'am_options' ) );
$data = array(
    'domain_url' => get_site_url(),
);
send_unauthentication( $am_options['am_api_key'], $data );


global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}am_blocks" );
delete_option("am_plugin_version");
delete_option("am_options");



?>
