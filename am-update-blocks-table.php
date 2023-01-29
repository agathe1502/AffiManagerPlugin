<?php

if (ini_get('max_execution_time') < 300) {
    ini_set('max_execution_time', 300);
}

require_once dirname(__FILE__) . '/../../../wp-load.php';
require_once dirname(__FILE__) . '/lib/titan-framework/titan-framework-embedder.php';

class AmUpdateBlocksTable
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

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        global $wpdb;

        $blocksByIdFromAffiManager = [];

        // Check if the data is great
        foreach ($data as $block) {
            if (isset($block['id']) and
                isset($block['title']) and
                isset($block['slug']) and
                isset($block['content']) and
                isset($block['position']) and
                isset($block['enable']) and
                isset($block['status'])) {
                $blocksByIdFromAffiManager[$block['id']] = $block;
                $slug = $block['slug'];
                $title = $block['title'];
            } else {
                wp_send_json(array(
                    'status' => false,
                    'code' => 'incorrect_data',
                    'message' => __('Wrong data.', AM_ID_LANGUAGES),
                    'wordpress_plugin_version' => am_get_version(),
                ));
                return;
            }
        }

        // Get the blocks in the DB for this page
        $sql = "SELECT * FROM {$wpdb->prefix}am_blocks WHERE slug = '$slug'";
        $blocksFromDb = $wpdb->get_results($sql);

        $touteLesSql = [];
        foreach ($blocksFromDb as $blockFromDb) {
            $id = $blockFromDb->id;
            if (isset($blocksByIdFromAffiManager[$id])) {
                // Id is present in the list sended by Affimanager
                $blockFromAm = $blocksByIdFromAffiManager[$id];
                if ($blockFromAm["status"] == "WAITING_DEPLOYMENT") {
                    // Save all the data
                    $sql = "UPDATE {$wpdb->prefix}am_blocks SET position = %s, enable = %s, content = %s WHERE id = %s;";
                    $sql = $wpdb->prepare($sql, $blockFromAm["position"], $blockFromAm["enable"], $blockFromAm["content"], $id);
                } else {
                    // Save only position
                    $sql = "UPDATE {$wpdb->prefix}am_blocks SET position = %s WHERE id = %s;";
                    $sql = $wpdb->prepare($sql, $blockFromAm["position"], $id);
                }
                // Remove the id from the array
                unset($blocksByIdFromAffiManager[$id]);
            } else {
                // Block has been deleted => Delete it from the DB
                $sql = "DELETE FROM {$wpdb->prefix}am_blocks WHERE id = %s;";
                $sql = $wpdb->prepare($sql, $id);
            }
            $wpdb->query($sql);
            array_push($touteLesSql, $sql);
        }

        wp_send_json(array(
            'toutelesSql' => $touteLesSql
        ));

        return;
        // In the array, there are only ids to add
        foreach ($blocksByIdFromAffiManager as $id => $blockToAdd) {
            // Insert only if it need to be deployed
            if ($blockToAdd["status"] == "WAITING_DEPLOYMENT") {
                $sql = "INSERT INTO {$wpdb->prefix}am_blocks (id, position, slug, enable, content) VALUES (%s, %s, %s, %s, %s);";
                $sql = $wpdb->prepare($sql, $id, $blockToAdd["position"], $blockToAdd["slug"], $blockToAdd["enable"], $blockToAdd["content"]);
                $wpdb->query($sql);
            }
        }

        // Then, recreate the post content with the data saved in DB
        $args = array(
            'name'        => $slug,
            'post_type'   => 'post',
            'post_status' => 'publish',
            'numberposts' => 1
        );
        $post = get_posts($args)[0];

        $sql = "SELECT content FROM {$wpdb->prefix}am_blocks WHERE slug = '$slug' ORDER BY position";
        $contents = $wpdb->get_results($sql);

        $content = '';
        foreach ($contents as $post_content) {
            $content .= $post_content->content . '<br>';
        }

        if ($post == null) {
            $post = [
                'post_name' => $slug,
                'post_type'   => 'post',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_content' => $content
            ];
        }
        else {
            $post->post_content = $content;
            $post->post_title = $title;
        }

        wp_insert_post($post);

        wp_send_json(array(
            'status' => true,
            'code' => 'success',
            'message' => __('Success', AM_ID_LANGUAGES),
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

    public function is_yoast_active()
    {

        require_once(ABSPATH . 'wp-admin/includes/plugin.php');

        if (is_plugin_active('wordpress-seo/wp-seo.php')) {

            return true;
        }

        return false;
    }
}

$endpoint = new AmUpdateBlocksTable();
$endpoint->run();
