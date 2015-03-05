<?php

require_once __DIR__ . '/facebook-php-sdk-v4-4.0-dev/autoload.php';

use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookSession;

class SimpleFacebookPublishMetaBox
{
    private $fbSession;
    private $fbError;
    private $fbPostUrl;

    public function __construct()
    {
        $options = get_option('simple-facebook-publish-option');

        try {
            FacebookSession::setDefaultApplication($options['app_id'], $options['app_secret']);
            $this->fbSession = new FacebookSession($options['access_token']);
            $this->fbSession->validate();
            add_action('add_meta_boxes', array($this, 'addMetaBox'));
            add_action('save_post', array($this, 'saveMetaBox'));
        } catch (Exception $e) {
            $this->fbError = $e;
            add_action('admin_notices', array($this, 'adminErrorNotice'));
        }

        add_action('admin_notices', array($this, 'adminPublishedNotice'));
    }

    /**
     * @param $post_type
     */
    public function addMetaBox($post_type)
    {
        if ($post_type == 'post') {
            add_meta_box(
                'simple_facebook_publish_meta_box',
                __('Publish on Facebook', 'simple-facebook-publish'),
                array($this, 'renderMetaBoxContent'),
                'post',
                'side'
            );
        }
    }

    /**
     * @param $post
     */
    public function renderMetaBoxContent($post)
    {
        // Add an nonce field so we can check for it later.
        wp_nonce_field('simple_facebook_publish_meta_box', 'simple_facebook_publish_meta_box_nonce');

        // Use get_post_meta to retrieve an existing value from the database.
        $value = get_post_meta($post->ID, '_my_meta_value_key', true);

        // Display the form, using the current value.
        echo '<input type="checkbox" class="checkbox" id="simple_facebook_publish_post" name="simple_facebook_publish_post" value="1" />';
        echo '<label for="simple_facebook_publish_post" style="vertical-align: baseline;">';
        _e('Publish this post on Facebook', 'simple-facebook-publish');
        echo '</label> ';
    }

    /**
     * @param $post_id
     * @return mixed
     */
    public function saveMetaBox($post_id)
    {
        // Check if our nonce is set.
        if (!isset($_POST['simple_facebook_publish_meta_box_nonce']))
            return $post_id;

        $nonce = $_POST['simple_facebook_publish_meta_box_nonce'];

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($nonce, 'simple_facebook_publish_meta_box'))
            return $post_id;

        // If this is an autosave, our form has not been submitted,
        //     so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;

        // Check the user's permissions.
        if (!current_user_can('edit_post', $post_id))
            return $post_id;

        /* OK, its safe for us to save the data now. */

        // Sanitize the user input.
        if (isset($_POST['simple_facebook_publish_post'])) {
            $data = [];
            $data['link'] = get_permalink($post_id);
            $data['message'] = strip_tags($_POST['content']);
            $data['name'] = get_the_title($post_id);
            $data['description'] = get_the_date('d.m.Y', $post_id);
            $data['caption'] = str_replace(array('http://', 'https://'), '', site_url());

            $thumbnail_id = get_post_thumbnail_id($post_id);
            if ($thumbnail_id) {
                $thumbnail = wp_get_attachment_image_src($thumbnail_id, 'large');
                $data['picture'] = $thumbnail[0];
            }

            $this->post($data);
        }
    }

    /**
     * @param $data
     * @throws \Facebook\FacebookRequestException
     */
    public function post($data)
    {
        try {
            $response = (new FacebookRequest(
                $this->fbSession, 'POST', '/me/feed', array(
                    'link' => $data['link'],
                    'message' => $data['message'],
                    'picture' => $data['picture'],
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'caption' => $data['caption']
                )
            ))->execute()->getGraphObject();

            $this->fbPostUrl = 'https://facebook.com/' . $response->getProperty('id');

            add_filter('redirect_post_location', array($this, 'adminPublishedNoticeQueryVar'), 99);

        } catch (FacebookRequestException $e) {
            echo "Exception occured, code: " . $e->getCode();
            echo " with message: " . $e->getMessage();
        }
    }

    public function adminPublishedNoticeQueryVar($location)
    {
        remove_filter('redirect_post_location', array($this, 'adminPublishedNoticeQueryVar'), 99);
        return add_query_arg(array('simple-facebook-publish-published-url' => $this->fbPostUrl), $location);
    }

    public function adminErrorNotice()
    {
        $currentScreen = get_current_screen();
        ?>
        <div class="error">
            <h3>Simple Facebook Publish</h3>

            <p>
                <b><?php _e('An error occured!', 'simple-facebook-publish'); ?></b>
                <?php
                if ($currentScreen->id != 'settings_page_simple-facebook-publish-settings-page') {
                    _e('Please validate your <a href="options-general.php?page=simple-facebook-publish-settings-page">settings</a>', 'simple-facebook-publish');
                }
                ?>
            </p>

            <p><i><?php echo $this->fbError->getCode() . ': ' . $this->fbError->getMessage(); ?></i></p>
        </div>
    <?php
    }

    public function adminPublishedNotice()
    {
        if (isset($_GET['simple-facebook-publish-published-url'])) {
            ?>
            <div class="updated">
                <p>
                    <?php
                    _e('Successfully posted on Facebook!', 'simple-facebook-publish');
                    echo ' <a href="' . $_GET['simple-facebook-publish-published-url'] . '" target="_blank">' . __('View post', 'simple-facebook-publish') . '</a>';
                    ?>
                </p>
            </div>
        <?php
        }
    }
}