=== Plugin Name ===

Contributors: aaf017
Tags: Auth, authentication, CAS, wpCAS, central, centralized, integration, ldap, Cassify, phpCAS, server, service, system, JASIG, JASIG CAS, CAS Authentication, central authentication service, access, authorization, education
Donate link: https://wpcassify.wordpress.com/donate/
Requires at least: 4.4
Tested up to: 4.7
Stable tag: 2.0.0
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

* (New feature !) : Backup / Restore plugin configuration options settings

* Tested with CAS Server version 4.1.4
* Compatible with CAS Protocol version 2 and 3
* Automatic user creation if not exist in Wordpress database.
* Synchronize Wordpress User metas with CAS User attributes.
* Add support for multivaluate cas user fields. Now multivaluate fields can be serialized to be stored in custom WP User meta.
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
* Define user account expiration rules bases on CAS User attributes.
* Network activation allowed
* You can set Service Logout URL (Needs to have CAS Server with followServiceRedirects option configured).
* Add support for web application hosted behind a reverse proxy. (Thanks to franck86)
* Add custom hooks : wp_cassify_after_cas_authentication, wp_cassify_before_auth_user_wordpress, wp_cassify_before_redirect, wp_cassify_after_redirect. (See online documentation)
* Custom filter to perform custom cas server response parsing. Hook name : wp_cassify_custom_parsing_cas_xml_response (See online documentation)
* Custom shortcode to generate CAS login link into your blog. (See online documentation)
* Debug settings, dump last xml cas server response.
* Detect if user has already authenticated by CAS from your public pages and perform auto-login. Include this in 
your index.php or in another template file inside your theme (It use CAS gateway mode) :
`
if (! isset( $_GET['wp_cassify_bypass'] ) ) {
    if (! is_user_logged_in() ) {
            if ( isset($GLOBALS['wp-cassify']) ) {
                    $GLOBALS['wp-cassify']->wp_cassify_check_authentication();
            }
    }
    else if (! is_user_member_of_blog() ) {
            if ( isset($GLOBALS['wp-cassify']) ) {
                    $GLOBALS['wp-cassify']->wp_cassify_check_authentication();
            }
    }
}
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

* Another question ?
Contact me at aa_francois@yahoo.fr and i try answer to your question.

== Changelog ==

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
