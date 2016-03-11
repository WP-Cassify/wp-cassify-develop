=== Plugin Name ===

Contributors: aaf017
Tags: Auth, authentication, CAS, central, centralized, integration, ldap, Cassify, phpCAS, server, service, system, JASIG, JASIG CAS, CAS Authentication
Donate link: https://wpcassify.wordpress.com/donate/
Requires at least: 4.4
Tested up to: 4.4.2
Stable tag: 1.5.8
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

* Require at least PHP version 5.3.10
* Require at least PHP CURL package (for example, 'apt-get install php5-curl' if you're using php5 on debian systems).

= Features included =

* (New Feature !) Custom hook to perform actions just after cas authentication. Hook name : wp_cassify_after_cas_authentication. ( Screencast available )
* (New Feature !) Custom hook to perform custom cas server response parsing. Hook name : wp_cassify_custom_parsing_cas_xml_response
* Tested with CAS Server version 4.1.4
* Compatible with CAS Protocol version 2 and 3
* Synchronize Wordpress User metas with CAS User attributes
* Set up Wordpress Roles to User according to CAS User attributes
* Authorization rule editor
* Automatic user creation if not exist in Wordpress database
* Compatible with Wordpress Access Control Plugin
* You can choose CAS User attributes you want to populate. Then you can access them via PHP Session
* Manage URL White List to bypass CAS Authentication on certain pages
* Network activation allowed
* You can set Service Logout URL (Needs to have CAS Server with followServiceRedirects option configured).

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/wp-cassify` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->WP Cassify screen to configure the plugin

== Screenshots ==

1. This is the basic options of the plugin.
2. This is the authorization rule editor.
3. Set up WordPress Roles to User according to CAS User attributes.

== Frequently Asked Questions ==

* Where can i find plugin documentation ?
You can find documentation here (See Plugin options and Screencasts) : https://wpcassify.wordpress.com/

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

= 1.5.2 =
* Some bug fixes.

= 1.5.3 =
* Some bug fixes. Security fixes.

= 1.5.4 =
* Add custom GET parameter (?wp_cassify_bypass=bypass) to bypass CAS Authentication on certain urls. See online documentation for more infos. Bug fixes on Authorization rule editor.

= 1.5.5 =
* Some bug fixes.

= 1.5.6 =
* (New Feature !) Custom hook to perform actions just after cas authentication. Hook name : wp_cassify_after_cas_authentication
* (New Feature !) Custom hook to perform custom cas server response parsing. Hook name : wp_cassify_custom_parsing_cas_xml_response

= 1.5.7 =
* Replace cURL function with wp_remote_get function. Now plugin does not require php5-curl.
* Add notice message on admin screen.

= 1.5.8 =
* Fix SSL Certificate probleme. Reintroduce cURL function instead of wp_remote_get function.
* Add SSL Configuration option : SSL Check Certificate wich turn on/off CURLOPT_SSL_VERIFYPEER option.


== Upgrade Notice ==
