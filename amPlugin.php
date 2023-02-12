<?php

/**
 * @author      affimanager.nilys.com
 * @copyright   2023 affimanager.nilys.com
 * @license     GPL-3.0+
 * Plugin Name: AffiManager
 * Description: Mise à jour des sites depuis la plateforme affimanager.nilys.com
 * Version:     0.1.7
 * Text Domain: AffiManager
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

defined( 'ABSPATH' ) or die( 'Are you crazyy!' );


class AmPlugin {

	public function __construct() {

		$this->init_constants();
		$this->run_update_checker();

		require_once( AM_PATH .  'includes.php' );

		register_activation_hook( __FILE__, 'am_install' );
		register_deactivation_hook( __FILE__, 'am_uninstall' );

        add_action( 'wp_enqueue_scripts', array( $this, 'am_load_scripts'));
        // Envoi le num de la nouvelle version
        add_action( 'upgrader_process_complete', array( $this, 'am_send_updated_version'), 10, 2);

    }

    function am_load_scripts(){
        wp_enqueue_script( 'am-scripts-js', plugin_dir_url( __FILE__ ) . 'js/am-scripts.js?v=' . am_get_version(), array('jquery'));
        wp_enqueue_style( 'am-styles-css', plugins_url( 'css/am-styles.css?v=' . am_get_version(), __FILE__ ) );
    }

	private function init_constants() {

		defined( 'AM_PATH' ) or define( 'AM_PATH', plugin_dir_path( __FILE__ ) );
		defined( 'AM_URL' ) or define( 'AM_URL', plugin_dir_url( __FILE__ ) );
		defined( 'AM_BASE' ) or define( 'AM_BASE', plugin_basename( __FILE__ ) );
		defined( 'AM_ID' ) or define( 'AM_ID', 'AffiManager' );
		defined( 'AM_ID_LANGUAGES' ) or define( 'AM_ID_LANGUAGES', 'am-translate' );
		defined( 'AM_VERSION' ) or define( 'AM_VERSION', '0.0.1' );
		defined( 'AM_NAME' ) or define( 'AM_NAME', 'AffiManager' );
	}

    function am_send_updated_version($upgrader_object, $options) {
        $our_plugin = plugin_basename( __FILE__ );
        // If an update has taken place and the updated type is plugins and the plugins element exists
        if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
            // Iterate through the plugins being updated and check if ours is there
            foreach( $options['plugins'] as $plugin ) {
                if( $plugin == $our_plugin ) {
                    // Your action if it is your plugin
                    $am_options = maybe_unserialize( get_option( 'am_options' ) );
                    $data = array(
                        'domain_url' => get_site_url(),
                        'wordpress_plugin_version' => am_get_version()
                    );
                    $data_json = json_encode( $data );

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://affimanager.nilys.com/api/wordpress-plugin/version',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 2,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $data_json,
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'Authorization: Bearer '. $am_options['am_api_key']
                        ),
                    ));

                    $response = curl_exec( $curl );
                    curl_close( $curl );
                }
            }
        }

    }

	private function run_update_checker() {

		require AM_PATH . '/lib/plugin-update-checker/plugin-update-checker.php';

        $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
			'https://github.com/agathe1502/AffiManagerPlugin',
			__FILE__
		);

        //Set the branch that contains the stable release.
        $myUpdateChecker->setBranch('master');

    }
}

new AmPlugin();
