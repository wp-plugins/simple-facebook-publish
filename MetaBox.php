<?php

namespace SimpleFacebookPublish;

use Exception;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;

class MetaBox
{
    private $options;
    private $facebookSession;
    private $fbError;
    private $fbPostUrl;

    public function __construct($options, $facebookSession)
    {
        $this->options = $options;
        $this->facebookSession= $facebookSession;

        try {
            if ($this->facebookSession) {
                $this->facebookSession->validate();
            }
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
        if (is_array($this->options) && array_key_exists('post_types', $this->options) && in_array($post_type, $this->options['post_types'])) {
            add_meta_box(
                'simple_facebook_publish_meta_box',
                __('Publish on Facebook', 'simple-facebook-publish'),
                array($this, 'renderMetaBoxContent'),
                $post_type,
                'side'
            );
        } elseif ($post_type == 'post') {
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

            $data['picture'] = '';
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
            if (!array_key_exists('access_token', $this->options)) {
                throw new Exception('No Acces Token available. Go to the Settings page and authorize you facebook app generate one.');
            }
            $session = new FacebookSession($this->options['access_token']);
            if ($session) {
                $response = (new FacebookRequest(
                    $session, 'POST', '/me/feed', array(
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
            }
        } catch (Exception $e) {
            add_filter('redirect_post_location', array($this, 'adminNoAccessTokenQueryVar'), 99);
        }
    }

    public function adminPublishedNoticeQueryVar($location)
    {
        remove_filter('redirect_post_location', array($this, 'adminPublishedNoticeQueryVar'), 99);
        return add_query_arg(array('simple-facebook-publish-published-url' => $this->fbPostUrl), $location);
    }

    public function adminNoAccessTokenQueryVar($location)
    {
        remove_filter('redirect_post_location', array($this, 'adminNoAccessTokenQueryVar'), 99);
        return add_query_arg(array('simple-facebook-publish-no-access-token' => 1), $location);
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

        if (isset($_GET['simple-facebook-publish-no-access-token'])) {
            ?>
            <div class="error">
                <p>
                    <?php
                    echo '<b>' . __('Error: No Access Token!', 'simple-facebook-publish') . '</b>';
                    echo '<br>';
                    _e('There was no access token available to post to facebook. Go to the <a href="http://wordpress.local/wp-admin/options-general.php?page=simple-facebook-publish-settings-page">settings screen</a> and authorize your facebook apo to generate an access token.');
                    ?>
                </p>
            </div>
        <?php
        }
    }
}