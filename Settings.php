<?php

namespace SimpleFacebookPublish;

use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;

class Settings
{
    private $options;
    private $redirectLoginHelper;
    /**
     * @var FacebookSession
     */
    private $facebookSession;

    public function __construct($options, $facebookSession, $facebookRedirectLoginHelper)
    {
        $this->options = $options;
        $this->facebookSession = $facebookSession;
        $this->redirectLoginHelper = $facebookRedirectLoginHelper;

        add_action('admin_menu', array($this, 'addSettingsPage'));
        add_action('admin_init', array($this, 'pageInit'));
    }

    /**
     * Add options page
     */
    public function addSettingsPage()
    {
        // This page will be under "Settings"
        add_options_page(
            __('Simple Facebook Publish Settings', 'simple-facebook-publish'),
            __('Simple Facebook Publish', 'simple-facebook-publish'),
            'manage_options',
            'simple-facebook-publish-settings-page',
            array($this, 'createSettingsPage')
        );
    }

    /**
     * Options page callback
     */
    public function createSettingsPage()
    {
        ?>
        <div class="wrap">
            <h2><?php echo $GLOBALS['title']; ?></h2>

            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields('simple-facebook-publish-options');
                do_settings_sections('simple-facebook-publish-settings-page');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Register and add settings
     */
    public function pageInit()
    {
        register_setting(
            'simple-facebook-publish-options', // Option group
            'simple-facebook-publish-option', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'simple_facebook_publish_app', // ID
            __('Facebook App', 'simple-facebook-publish'), // Title
            array($this, 'printAppSectionInfo'), // Callback
            'simple-facebook-publish-settings-page' // Page
        );

        add_settings_field(
            'app_id', // ID
            'App ID', // Title
            array($this, 'appIdCallback'), // Callback
            'simple-facebook-publish-settings-page', // Page
            'simple_facebook_publish_app' // Section
        );

        add_settings_field(
            'app_secret',
            'App Secret',
            array($this, 'appSecretCallback'),
            'simple-facebook-publish-settings-page',
            'simple_facebook_publish_app'
        );

        add_settings_field(
            'access_token',
            'Page',
            array($this, 'accessTokenCallback'),
            'simple-facebook-publish-settings-page',
            'simple_facebook_publish_app'
        );

        add_settings_section(
            'simple_facebook_publish_post_types', // ID
            __('Post Types', 'simple-facebook-publish'), // Title
            array($this, 'printPostTypesSectionInfo'), // Callback
            'simple-facebook-publish-settings-page' // Page
        );

        add_settings_field(
            'post_types',
            'Available post types',
            array($this, 'postTypesCallback'),
            'simple-facebook-publish-settings-page',
            'simple_facebook_publish_post_types'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return array $new_input
     */
    public function sanitize($input)
    {
        $new_input = array();
        if (isset($input['app_id']))
            $new_input['app_id'] = sanitize_text_field($input['app_id']);

        if (isset($input['app_secret']))
            $new_input['app_secret'] = sanitize_text_field($input['app_secret']);

        if (isset($input['access_token'])) {
            $accessToken = sanitize_text_field($input['access_token']);

            $session = new FacebookSession($accessToken);

            $permanentAccessTokenRequest = (new FacebookRequest(
                $session, 'GET', '/oauth/access_token', array(
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => $this->options['app_id'],
                    'client_secret' => $this->options['app_secret'],
                    'fb_exchange_token' => $accessToken
                )
            ))->execute()->getGraphObject()->asArray();
            $new_input['access_token'] = $permanentAccessTokenRequest['access_token'];
        }

        if (isset($input['post_types'])) {
            $new_input['post_types'] = $input['post_types'];
        }

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function printAppSectionInfo()
    {
        echo __('Enter your app details below, click on Authorize this App and choose a page to publish your posts on. Save your changes and you are ready to publish.', 'simple-facebook-publish') . '<br><br>';
        echo __('To create a facebook app go to: <a href="https://developers.facebook.com/apps" target="_blank">https://developers.facebook.com/apps</a>', 'simple-facebook-publish') . '<br>';
        echo __('After creating the app go to its settings page, enter your website domain in the App Domains field and click on Add Plattform > Website to enter your domain again as Site URL and Mobile Site URL. Now your app is ready to be used with this plugin.', 'simple-facebook-publish');
        echo '<br>';
    }

    /**
     * Print the Section text
     */
    public function printPostTypesSectionInfo()
    {
        echo __('Choose the post types you want to publish on facebook.', 'simple-facebook-publish');
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function appIdCallback()
    {
        printf(
            '<input type="text" id="app_id" name="simple-facebook-publish-option[app_id]" value="%s" />',
            isset($this->options['app_id']) ? esc_attr($this->options['app_id']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function appSecretCallback()
    {
        printf(
            '<input type="text" id="app_secret" name="simple-facebook-publish-option[app_secret]" value="%s" />',
            isset($this->options['app_secret']) ? esc_attr($this->options['app_secret']) : ''
        );
    }

    public function accessTokenCallback()
    {
        if ($this->facebookSession) {
            $accounts = (new FacebookRequest(
                $this->facebookSession, 'GET', '/me/accounts'
            ))->execute()->getGraphObject()->asArray();

            echo '<select name="simple-facebook-publish-option[access_token]">';
            echo '<option value="' . $this->facebookSession->getAccessToken() . '">' . __('Profile', 'simple-facebook-publish') . '</option>';
            foreach ($accounts['data'] as $account) {
                echo '<option value="' . $account->access_token . '" />', $account->name . '</option>';
            }
            echo '</select>';
        } else {
            if ($this->redirectLoginHelper) {
                $loginUrl = $this->redirectLoginHelper->getLoginUrl(array(
                    'publish_actions',
                    'manage_pages'
                ));

                if (isset($this->options['access_token'])) {
                    $session = new FacebookSession($this->options['access_token']);
                    $page = (new FacebookRequest(
                        $session, 'GET', '/me'
                    ))->execute()->getGraphObject()->asArray();

                    echo $page['name'] . '<br>';
                    echo '<a href="' . $loginUrl . '" class="button button-default">' . __('change', 'simple-facebook-publish') . '</a>';
                    echo '<input type="hidden" name="simple-facebook-publish-option[access_token]" value="' . $this->options['access_token'] . '" />';
                } else {
                    echo '<a href="' . $loginUrl . '" class="button button-default">' . __('authorize app', 'simple-facebook-publish') . '</a>';
                }

            } else {
                echo __('First save your app id an secret.', 'simple-facebook-publish');
            }
        }
    }

    public function postTypesCallback()
    {
        $postTypes = $post_types = get_post_types( '', 'names' );

        foreach ($postTypes as $postType) {
            if (in_array($postType, array('revision', 'nav_menu_item', 'attachment'))) continue;
            $postType = get_post_type_object($postType);
            // type "post" checked by default when no options were set before
            $checked = (
                (
                    (!is_array($this->options) || !array_key_exists('post_types', $this->options))
                    && $postType->name == 'post'
                )
                ||
                (is_array($this->options) && in_array($postType->name, $this->options['post_types']))
            ) ? 'checked="checked"' : '';
            echo '<label>';
            echo '<input type="checkbox" value="' . $postType->name . '" name="simple-facebook-publish-option[post_types][]" ' . $checked . '/>', $postType->labels->name;
            echo '</label><br>';
        }
    }
}