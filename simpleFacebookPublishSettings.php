<?php

class simpleFacebookPublishSettings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
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
        $this->options = get_option('simple-facebook-publish-option');
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
            'simple_facebook_publish', // ID
            __('Facebook App', 'simple-facebook-publish'), // Title
            array($this, 'printSectionInfo'), // Callback
            'simple-facebook-publish-settings-page' // Page
        );

        add_settings_field(
            'app_id', // ID
            'App ID', // Title
            array($this, 'appIdCallback'), // Callback
            'simple-facebook-publish-settings-page', // Page
            'simple_facebook_publish' // Section
        );

        add_settings_field(
            'app_secret',
            'App Secret',
            array($this, 'appSecretCallback'),
            'simple-facebook-publish-settings-page',
            'simple_facebook_publish'
        );

        add_settings_field(
            'access_token',
            'Permanent Page Access Token',
            array($this, 'accessTokenCallback'),
            'simple-facebook-publish-settings-page',
            'simple_facebook_publish'
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

        if (isset($input['access_token']))
            $new_input['access_token'] = sanitize_text_field($input['access_token']);

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function printSectionInfo()
    {
        print __('Enter your app id and secret and permanent page access token below:', 'simple-facebook-publish');
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

    /**
     * Get the settings option array and print one of its values
     */
    public function accessTokenCallback()
    {
        printf(
            '<input type="text" id="access_token" name="simple-facebook-publish-option[access_token]" value="%s" />',
            isset($this->options['access_token']) ? esc_attr($this->options['access_token']) : ''
        );
    }
}