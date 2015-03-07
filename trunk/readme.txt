=== Simple Facebook Publish ===
Contributors: mktatwp
Tags: simple, easy, automated, facebook, post, publish
Requires at least: 3.0.1
Tested up to: 4.1.1
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This is a very simple plugin for publishing your wordpress posts to facebook.

== Description ==

This plugin is maybe the most simple implementation of a facebook publishing functionality out there. All it does is adding a checkbox to the post edit screen.
Checking this checkbox will convert your wordpress post into a facebook post which is automatically published on saving/updating the post.

The plugin makes use of the latest facebook api version (PHP SDK 4.4.0).

== Installation ==

You need a facebook app with the following permissions, reviewed by facebook:

- manage_pages
- publish_actions

To create a facebook app go to: https://developers.facebook.com/apps

After installing the plugin go to "Settings" > "Simple Facebook Publish" and enter your app id and app secret and save these.
Now click on authorize app, accept all permissions it asks for and choose the page where your post will be published to.
Now on the post edit screen you will see an additional meta box with a checkbox. Check this checkbox before saving/updating a post to publish it on facebook.

**ATTENTION:** Your server needs to run php 5.4 or later!

== Frequently Asked Questions ==

No questions asked yet. Feel free... ;)

== Changelog ==

1.1

- Access token now gets generated automatically!

Initial commit. No changes yet.

== Screenshots ==

1. post edit screen
2. settings page