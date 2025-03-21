=== WP Cassify ===

Contributors: aaf017,vbonamy
Tags: Auth, authentication, CAS, wpCAS, central, centralized, integration, ldap, Cassify, phpCAS, server, service, system, JASIG, JASIG CAS, CAS Authentication, central authentication service, access, authorization, education
Donate link: https://wpcassify.wordpress.com/donate/
Requires at least: 4.4
Tested up to: 6.7
Requires PHP: 7.0
Stable tag: 2.3.7
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The plugin is an Apereo CAS Client. It performs CAS authentication and autorization for Wordpress.

== Description ==

If you're happy with this plugin :
As a reward for my efforts, I would like to receive T-shirts (or other goodies) as gifts from the universities or companies that use it. 
My size is L. Best regards.

This Apereo CAS authentication plugin has no phpCas library dependency. This is not only an authentication plugin. 
You can build custom authorization rules according to cas user attributes populated. If user don't exist in Wordpress 
database, it can be created automatically. There are many features. You can customize everything.

= Website =

https://wpcassify.wordpress.com/

= Development and release environment =

This plugin is now developed and tested from a github repository. You can find it here :
https://github.com/WP-Cassify/wp-cassify-develop

Don't hesitate to contribute to this project. You can fork it and make pull requests !

= Requirements =

* Require at least PHP version 7.0
* Require at least PHP CURL package

= Features included =

* SLO (Single Log Out) support (thanks to dedotombo and me)
* Adding NCONTAINS operator (thanks to blandman)
* Fix bug on Gateway mode (autologin) (thanks to dedotombo again). Now it's now necessary to hack theme files to fire it.
* Adding option logout on authentication failure to not disturb users
* Initialize PHP session at a later stage (on wp_loaded not on init)
* Adding some customs hooks and filters.

* Tested with CAS Server version 4.1.4
* Compatible with CAS Protocol version 2 and 3
* Automatic user creation if not exist in Wordpress database.
* Synchronize Wordpress User metas with CAS User attributes.
* Add support for multivaluate cas user fields. Now multivaluate fields can be serialized to be stored in custom WP User meta.
* Backup / Restore plugin configuration options settings
* You can choose CAS User attributes you want to populate. Then you can access them via PHP Session.
* Be careful, to access to CAS User Attributes from your theme file (from 1.8.4), use code below :
`
	<?php
		if ( isset($GLOBALS['wp-cassify']) ) {
			print_r( $GLOBALS['wp-cassify']->wp_cassify_get_cas_user_datas() );
		}
	?>
`
* Set up Wordpress Roles to User according to CAS User attributes.
* If plugin is network activated, you can define User Role Rule scope by blog id.
* Authorization rule editor.
* Compatible with Wordpress Access Control Plugin.
* Manage URL White List to bypass CAS Authentication on certain pages.
* Much simpler bypass authentication with post method provided by Susan Boland (See online documentation). Create wordpress authentication form with redirect attribute like this :
`
    <?php
         
        $args = array(
            'echo'           => true,
            'remember'       => true,
            'redirect' => site_url( '/?wp_cassify_bypass=bypass' ),
            'form_id'        => 'loginform',
            'id_username'    => 'user_login',
            'id_password'    => 'user_pass',
            'id_remember'    => 'rememberme',
            'id_submit'      => 'wp-submit',
            'label_username' => __( 'Username' ),
            'label_password' => __( 'Password' ),
            'label_remember' => __( 'Remember Me' ),
            'label_log_in'   => __( 'Log In' ),
            'value_username' => '',
            'value_remember' => false
        );
         
        wp_login_form( $args ); 
    ?>
`
* Receive email notifications when trigger is fired (after user account creation, after user login/logout).
* Define notifications rules based on user attributes values.
* Purge user roles before applying user role rules.
* Define user account expiration rules bases on CAS User attributes.
* Network activation allowed
* You can set Service Logout URL (Needs to have CAS Server with followServiceRedirects option configured).
* Add support for web application hosted behind a reverse proxy. (Thanks to franck86)
* Add custom hooks : wp_cassify_after_cas_authentication, wp_cassify_before_auth_user_wordpress, wp_cassify_before_redirect, wp_cassify_after_redirect. (See online documentation)
* Custom filter to perform custom cas server response parsing. Hook name : wp_cassify_custom_parsing_cas_xml_response (See online documentation)
* Custom shortcode to generate CAS login/logout link into your blog. (See online documentation)
* Debug settings, dump last xml cas server response.
* Detect if user has already authenticated by CAS from your public pages and perform auto-login with gateway mode
* Add '-IN' and '-NOTIN' operators to process array attributes values returned from CAS.
When you have :
`
	$cas_user_datas['title'] = array( 'Student', 'Professor' );
`
Then you can use :
`
	(CAS{title} -IN "professor")
`

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/wp-cassify` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->WP Cassify screen to configure the plugin

== Screenshots ==

1. This is the basic options of the plugin.
2. This is the URL settings.
3. This is the attributes extraction settings.
4. This is the authorization rule editor.
5. You can set wordpress role to user according to CAS attributes values.
6. Synchronize Wordpress User metas with CAS User attributes.
7. This is the email notifications settings.
8. Define notifications rules based on events and CAS User attributes
9. Define user account expiration rules bases on CAS User attributes (after x days from user creation date).
10. Define user account expiration rules bases on CAS User attributes (with fixed expiration date).

== Frequently Asked Questions ==

* Where can i find plugin documentation ?
You can find documentation here (See Plugin options and Screencasts) : https://wpcassify.wordpress.com/

* How to perform CAS Authentication on a front Office page ?
Install WordPress Access Control Plugin. And mark page as "Only accessible by members". You can use anoter ACL plugin. If page require an authentication, CAS Authentication is fired.

* How to protect all website ?
Install WordPress Access Control Plugin. In Settings >> Members Only, Check "Make Blog Members Only" option.

== Changelog ==

= 2.3.7 =
* Fix -NCONTAINS and -NOTIN rule evaluations.

= 2.3.6 =
* Fix and simplify wp_cassify_logout_with_redirect shortcode
* Sanitize service_redirect_url in wp_cassify_login_with_redirect to prevent potential XSS (reported by Muhammad Yudha via Patchstack).
* Fix 'Notice: Function _load_textdomain_just_in_time was called incorrectly.'

= 2.3.5 =
* Fix PHP start session only when needed (fix) - thanks to @kkatpcc
* Fix grammar in die message of wp_cassify_auth_user_wordpress - thanks to Randy Hammond

= 2.3.4 = 
* Fix wp_cassify_notification_rule_matched

= 2.3.3 = 
* Fix PHP start session only when needed - thanks to @partyka
* Fix PHP warning parse_str - thanks to @erseco
* Fix PHP warnings
* Ensure to run jquery when DOM is ready
* Fix CAS Single Log Out feature

= 2.3.2 = 
* Fix PHP8 support

= 2.3.1 = 
* New custom hook wp_cassify_after_auth_user_wordpress

= 2.3.0 = 
* Some bugfix

= 2.2.9 =
* Add PHP8 support (experimental). Reordering optionnal parameters to delete PHP8 deprecated warnings. Replace deprecated method wp_get_sites() by get_sites().

= 2.2.8 =
* Add new parameter to custom filter wp_cassify_grab_service_ticket_roles_to_push. Now you can use $cas_user_datas inside filter.

= 2.2.7 =
* Bug fix on multivalued fields with IN operator

= 2.2.6 =
* Bug fix on handle multivalued fields

= 2.2.5 =
* Handle multivalued fields (thanks to jusabatier)

= 2.2.2 =
* SLO (Single Log Out) support (thanks to dedotombo and me)
* Adding NCONTAINS operator (thanks to blandman)
* Fix bug on Gateway mode (autologin) (thanks to dedotombo again). Now it's now necessary to hack theme files to fire it.
* Adding option logout on authentication failure to not disturb users
* Initialize PHP session at a later stage (on wp_loaded not on init)

= 2.2.1 =
* Fix incorrect PHP version requirement, thanks to olhovsky.
* Fix PHP7 warnings, thanks to vbonamy.
* Fix serviceUrl with parameters, thanks to franck86
* Add new parameter settings for cURL : CA_PATH, CA_INFO, thanks to Basile

= 2.2 = 
* Replace SwiftMailer by native wordpress mailing library PHPMailer. Replace usage of deprecated MCrypt library by openssl. Somes bug fixes.

= 2.1.9 = 
* Fix error with recent version of JetPack.

= 2.1.8 = 
* Add support for Pantheon environments. Thanks to Jesse Loesberg.
* Fix error on uninstall if table does not exists

= 2.1.7 = 
* Adding meta require PHP version. Fixing error : fix restore configuration filein multi-site configuration. (Thanks to buddywhatshisname)

= 2.1.6 = 
* Fixing error : fix backup file download in multi-site configuration. (Thanks to buddywhatshisname)

= 2.1.5 = 
* Fixing error : removing extra slash from logo url

= 2.1.1 = 
* Fix missing file autoload_static.php

= 2.1.0 = 
* Fix SVN error

= 2.0.9 = 
* Upgrade SwiftMailer library to 6.0.2.

= 2.0.8 = 
* Add '-IN' and '-NOTIN' operators to process array attributes values.

= 2.0.7 = 
* Add new filter wp_cassify_override_service_validate_url. Build you own service validate url. Very useful when you're behind loadbalancer.

= 2.0.6 = 
* Add new feature : Purge user roles before applying rules (syncing wordpress local roles with CAS user attributes)
* Add new shortcode : wp_cassify_logout_with_redirect
* Some bug fixes

= 2.0.4 = 
* Fix bug on PHP Strict standards.

= 2.0.2 = 
* Fix bugs on backup/import configuration settings

= 1.9.9 = 
* Backup / Restore plugin configuration options settings

= 1.9.7 = 
* Add support for multivaluated field in wp_cassify_parse_xml_response and in wp_cassify_sync_user_metadata.
* Bug fix on duplicate path with php7. Thanks to Richard Tape.

= 1.9.6 = 
* Some bug fixes.

= 1.9.4 = 
* Debug settings, dump last xml cas server response.

= 1.9.3 = 
* Bug fix on rule solver (on NEQ operator)

= 1.9.2 = 
* Bug fix on rule solver

= 1.9.1 = 
* If plugin is network activated, you can define User Role Rule scope by blog id.

= 1.9.0 =
* Security fix.

= 1.8.9 =
* Bug fix on local logout.

= 1.8.8 =
* In multisite support in subdomain configuration, add an option to override service url.
* Add filter wp_cassify_redirect_service_url_filter

= 1.8.6 =
* Test multisite support in subdomain configuration.
* Fix bug on gateway mode.
* Remove function force_user_member_of_blog.

= 1.8.5 =
* Add function force_user_member_of_blog.

= 1.8.3 =
* Bug fix on user automatic creation.

= 1.8.2 =
* Add youtube video in plugin description.

= 1.8.1 =
* Bug fix on logout from local wordpress auth.

= 1.8.0 =
* Bug fix on service url with querystring parameter.

= 1.7.9 =
* Detect if user has already authenticated by CAS from your public pages and perform auto-login. Include this in 
your index.php or in another template file inside your theme (It use CAS gateway mode) :
`
if ( isset($GLOBALS['wp-cassify']) ) {
	$GLOBALS['wp-cassify']->wp_cassify_check_authentication();
} 
`
= 1.7.4 = 
* Bug fixes on notifications configuration settings.
* Bug fixes on notification message sending.

= 1.7.2 = 
* Define user account expiration rules bases on CAS User attributes !

= 1.7.0 =
* Bug fix : Suscriber role overwrite all other roles. Suscriber role is pushed only if user account is recently created.

= 1.6.9 =
* Warning bugs fixes.

= 1.6.8 =
* Bug fix
* Add custom hooks : wp_cassify_before_auth_user_wordpress, wp_cassify_before_redirect, wp_cassify_after_redirect. (See online documentation)

= 1.6.7 =
* Suscriber role is pushed by default when user is successfully authenticated by CAS.

= 1.6.6 =
* Bug fix
* Add custom hook : wp_cassify_before_auth_user_wordpress

= 1.6.5 =
* Add support for web application hosted behind a reverse proxy. (Thanks to franck86).

= 1.6.4 =
* Add shortcode to generate login CAS link with redirect.

= 1.6.3 =
* Fix bug on javascript control UI.

= 1.6.2 =
* Send email notifications when trigger is fired (after user account creation, after user login/logout).

= 1.6.1 =
* Email notifications when user account is created.

= 1.6.0 =
* New admin interface with metaboxes.
* Fix Bug multi-select fields if network activated.

= 1.5.9 =
* Fix Bug on synchronization between CAS User attributes and Wordpress User metas

= 1.5.8 =
* Fix SSL Certificate probleme. Reintroduce cURL function instead of wp_remote_get function.
* Add SSL Configuration option : SSL Check Certificate wich turn on/off CURLOPT_SSL_VERIFYPEER option.

= 1.5.7 =
* Replace cURL function with wp_remote_get function. Now plugin does not require php5-curl.
* Add notice message on admin screen.

= 1.5.6 =
* Custom hook to perform actions just after cas authentication. Hook name : wp_cassify_after_cas_authentication
* Custom hook to perform custom cas server response parsing. Hook name : wp_cassify_custom_parsing_cas_xml_response

= 1.5.5 =
* Some bug fixes.

= 1.5.4 =
* Add custom GET parameter (?wp_cassify_bypass=bypass) to bypass CAS Authentication on certain urls. See online documentation for more infos. Bug fixes on Authorization rule editor.

= 1.5.3 =
* Some bug fixes. Security fixes.

= 1.5.2 =
* Some bug fixes.

= 1.5.1 =
* Synchronize Wordpress User metas with CAS User attributes

= 1.5 =
* User Role Rule Editor : set user wordpress role according to CAS Attributes values.

= 1.2 =
* Multisite configuration possible if network activated.

= 1.0 =
* First version.

== Upgrade Notice ==
