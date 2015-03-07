<?php

namespace SimpleFacebookPublish;

use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookSession;

class SimpleFacebookPublish
{
    private $options;
    private $redirectLoginHelper;
    private $facebookSession;
    private $metaBox;
    private $settings;

    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'loadTextdomain'));
        $this->options = get_option('simple-facebook-publish-option');
        $this->authorize();

        $this->metaBox = new MetaBox($this->options, $this->facebookSession);
        $this->settings = new Settings($this->options, $this->facebookSession, $this->redirectLoginHelper);
    }

    public function loadTextdomain()
    {
        load_plugin_textdomain('simple-facebook-publish', false, plugin_basename(dirname(__FILE__)) . '/languages');
    }

    public function authorize()
    {
        if ($this->options['app_id'] && $this->options['app_secret']) {
            FacebookSession::setDefaultApplication($this->options['app_id'], $this->options['app_secret']);

            if (!$this->facebookSession) {
                $this->redirectLoginHelper = new FacebookRedirectLoginHelper(site_url() . '/wp-admin/options-general.php?page=simple-facebook-publish-settings-page');

                // Use the login url on a link or button to redirect to Facebook for authentication
                try {
                    $this->facebookSession = $this->redirectLoginHelper->getSessionFromRedirect();
                } catch (FacebookRequestException $e) {
                    // When Facebook returns an error
                } catch (\Exception $e) {
                    // When validation fails or other local issues
                }
            }
        }
    }
}