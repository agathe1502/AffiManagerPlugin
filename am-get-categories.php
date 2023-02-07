<?php

if (ini_get('max_execution_time') < 300) {
    ini_set('max_execution_time', 300);
}

require_once dirname(__FILE__) . '/../../../wp-load.php';
require_once dirname(__FILE__) . '/lib/titan-framework/titan-framework-embedder.php';

class AmGetCategories
{

    private $am_options;

    public function __construct()
    {
        $this->am_options = TitanFramework::getInstance('am');
    }

    public function run()
    {

        if (!$this->is_bearer_token_valid()) {

            wp_send_json(array(
                'status' => false,
                'code' => 'incorrect_api_key',
                'message' => __('Incorrect API key.', AM_ID_LANGUAGES),
                'wordpress_plugin_version' => am_get_version(),
            ));

            return;
        }

        $categories = get_categories();

        wp_send_json(array(
            'status' => true,
            'code' => 'success',
            'categories' => $categories,
            'wordpress_plugin_version' => am_get_version(),
        ));
    }

    private function is_bearer_token_valid()
    {

        $bearer_token = $this->get_bearer_token();

        $apy_key = $this->am_options->getOption('am_api_key');

        return (!empty($bearer_token) && !empty($apy_key) && $bearer_token === $apy_key);
    }

    /**
     * Get access token from header
     * */
    private function get_bearer_token()
    {

        $headers = $this->get_authorization_header();

        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {

                return $matches[1];
            }
        }


        return null;
    }

    /**
     * Get header Authorization
     * */
    private function get_authorization_header()
    {

        $headers = null;

        if (isset($_SERVER['Authorization'])) {

            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI

            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {

            $requestHeaders = apache_request_headers();

            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));

            if (isset($requestHeaders['Authorization'])) {

                $headers = trim($requestHeaders['Authorization']);
            }
        }

        return $headers;
    }
}

$endpoint = new AmGetCategories();
$endpoint->run();
