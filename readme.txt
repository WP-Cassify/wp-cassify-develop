=== Plugin Name ===
Contributors: aaf017
Tags: Auth, authentication, CAS, central, centralized, integration, ldap, Cassify, phpCAS, server, service, system, JASIG, JASIG CAS
Donate link: https://wpcassify.wordpress.com/donate/
Requires at least: 4.4
Tested up to: 4.4.2
Stable tag: 1.5.1
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

= Requirements =

* Require php5-curl package
* Require at least PHP version 5.3.10

= Features included =

* (New Feature !) Synchronize Wordpress User metas with CAS User attributes
* (New Feature !) Set conditionnal users roles
* Tested with CAS Server version 4.1.4
* Compatible with CAS Protocol version 2 and 3
* Authorization rule editor
* Automatic user creation if not exist in Wordpress database
* Compatible with Wordpress Access Control Plugin
* You can choose CAS User attributes you want to populate. Then you can access them via PHP Session
* Manage URL White List to bypass CAS Authentication on certain pages
* Network activation allowed
* You can set logout URL. This perform a logout redirection avec CAS Logout

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/wp-cassify` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->WP Cassify screen to configure the plugin

== Screenshots ==

1. This is the basic options of the plugin.
2. This is the authorization rule editor.
3. This is the user role rule editor. Set users roles according CAS User Attributes.

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

= 1.5 =
* User Role Rule Editor : set user wordpress role according to CAS Attributes values.

= 1.5.1 =
* Synchronize Wordpress User metas with CAS User attributes

== Upgrade Notice ==
