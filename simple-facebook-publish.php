<?php
/*
Plugin Name: Simple Facebook Publish
Description: Most simple implementation of automated facebook posts.
Author: Markus Kottländer
Version: 0.1
Text Domain: simple-facebook-publish
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

require_once 'simpleFacebookPublishMetaBox.php';
require_once 'simpleFacebookPublishSettings.php';

$simpleFacebookPublishMetaBox = new SimpleFacebookPublishMetaBox();
if (is_admin()) {
    $simpleFacebookPublishSettings = new SimpleFacebookPublishSettings();
}