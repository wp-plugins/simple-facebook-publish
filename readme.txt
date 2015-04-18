=== Simple Facebook Publish ===
Contributors: mktatwp
Tags: simple, easy, automated, facebook, post, publish
Requires at least: 3.0.1
Tested up to: 4.1.1
Stable tag: 0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This is a very simple plugin for publishing your wordpress posts to facebook.

== Description ==

This plugin is maybe the most simple implementation of a facebook publishing functionality out there. All it does is adding a checkbox to the edit screen of the post types you wish.
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

0.3

- added support for multiple post types
- fixed some bugs

0.2

- added language support
- Access token now gets generated automatically!

0.1

- quick and dirty version for own purpose

== Screenshots ==

1. post edit screen
2. settings page