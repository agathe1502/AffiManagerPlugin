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
                isset($block['categories']) and
                isset($block['featured_image_url']) and
                isset($block['status'])) {
                $blocksByIdFromAffiManager[$block['id']] = $block;
                $slug = $block['slug'];
                $title = $block['title'];
                $post_categories = $block['categories'];
                $featured_image_url = $block['featured_image_url'];
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
        }

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
            'post_status' => ['draft', 'published'],
            'numberposts' => 1
        );
        $post = get_posts($args)[0];

        $sql = "SELECT content FROM {$wpdb->prefix}am_blocks WHERE slug = '$slug' ORDER BY position";
        $contents = $wpdb->get_results($sql);


        // Create post without content
        if ($post == null) {
            $post = [
                'post_name' => $slug,
                'post_type'   => 'post',
                'post_status' => 'draft',
                'post_title' => $title,
                'post_category' => $post_categories
            ];
        }
        $post_id = wp_insert_post($post);

        $content = '';
        foreach ($contents as $post_content) {
            $content .= $post_content->content . '<br>';
        }

        $html = str_get_html($content);
        foreach($html->find('span[class=ob]') as $span) {
            $prop = 'data-ob';
            $href = $span->$prop;
            $span->$prop = base64_encode($href);
        }
        $existing_media = get_attached_media('image', $post_id);
        foreach($html->find('img') as $span) {
            $prop = 'src';
            $src = $span->$prop;
            $alt = 'alt';
            $filename = $this->get_image_slug($span->$alt) . '.' . pathinfo($src)['extension'] ?? basename($src);
            $class = 'class';
            $span->$class = "am-img";
            $new_image_url = $this->save_media($src, $filename, $post_id, $existing_media);
            $span->$prop = $new_image_url;
        }

        $content = (string)$html;
        // Update post content, title and categories
        $post->post_content = $content;
        $post->post_title = $title;
        $post->post_category = $post_categories;

        $post_id = wp_insert_post($post);

        // Create post feature image
        $featured_image_url_filename = $slug . '.' . pathinfo($featured_image_url)['extension'];
        $this->save_media($featured_image_url, $featured_image_url_filename, $post_id, $existing_media);

        wp_send_json(array(
            'status' => true,
            'code' => 'success',
            'message' => __('Success', AM_ID_LANGUAGES),
            'wordpress_plugin_version' => am_get_version(),
        ));
    }

    private function get_image_slug($image_alt) {
        $slug = str_replace(' ', '-', $image_alt);
        $slug = str_replace('\'', '-', $slug);
        $slug = str_replace('?', '', $slug);
        $slug = str_replace('!', '', $slug);
        $unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
        $slug = strtr($slug, $unwanted_array );
        return $slug;
    }

    private function save_media($image_url, $filename, $post_id, $existing_media) {

        foreach($existing_media as $media){
            $oldMediaId= $media->ID;
            $fileMedia = get_attached_file($oldMediaId, true);
            $oldMediaFilename = pathinfo($fileMedia,PATHINFO_BASENAME);

            if ($filename == $oldMediaFilename) {
                wp_delete_attachment($oldMediaId, true);
            }

        }

        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents( $image_url );

        if ( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        }
        else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        file_put_contents( $file, $image_data );
        $wp_filetype = wp_check_filetype( $filename, null );

        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name( $filename ),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $file, $post_id);
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        // Set thumbnail / Featured image
        if (isset($post_id)) {
            set_post_thumbnail( $post_id, $attach_id );
        }

        return wp_get_attachment_url($attach_id);
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
