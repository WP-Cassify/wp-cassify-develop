=== Plugin Name ===
Contributors: aaf017
Tags: Auth, authentication, CAS, central, centralized, integration, ldap, Cassify, phpCAS, server, service, system
Donate link: https://wpcassify.wordpress.com/
Requires at least: 4.4
Tested up to: 4.4.2
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The plugin is a JASIG CAS Client. It performs CAS authentication and autorization for Wordpress.

== Description ==

This JASIG CAS authentication plugin has no phpCas library dependency. This is not only an authentication plugin. 
You can build custom authorization rules according to cas user attributes populated. If user don't exist in Wordpress 
database, it can be created automatically. There are many features. You can customize everything : CAS servlets URL, 
XPath Query to parse cas server xml response, user attributes you want to populate.

= Website =

https://wpcassify.wordpress.com/

= Features included =

* Authorization rule editor
* Automatic user creation if not exist in Wordpress database
* You can customize CAS Ticket parsing thanks to XPath Query
* You can choose CAS User attributes you want to populate. Then you can access them via PHP Session.
* Manage URL White List to bypass CAS Authentication on certain pages
* Network activation allowed
* You can set logout URL. This perform a logout redirection avec CAS Logout.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/wp-cassify` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->WP Cassify screen to configure the plugin

== Screenshots ==

1. This is the basic options of the plugin.
2. This is the authorization rule editor.
3. You can edit the xpath query to parse cas ticket with custom structure. You can also define user attribute's you want to population into php session.

== Frequently Asked Questions ==

* How to perform CAS Authentication on a front Office page ?
Install WordPress Access Control Plugin. And mark page as "Only accessible by members". You can use anoter ACL plugin. If page require an authentication, CAS Authentication is fired.

* How to protect all website ?
Install WordPress Access Control Plugin. In Settings >> Members Only, Check "Make Blog Members Only" option.

* Another question ?
Contact me at aa_francois@yahoo.fr and i try answer to your question.

== Changelog ==

= 1.0 =
* First version.

= 1.2 =
* Multisite configuration possible if network activated.

== Upgrade Notice ==
