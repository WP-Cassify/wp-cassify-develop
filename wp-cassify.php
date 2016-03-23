<?php

/**
 * Plugin Name: WP Cassify
 * Plugin URI: https://wpcassify.wordpress.com/
 * Description: CAS Authentication Client for Wordpress. Also, it handle custom authorizations rules from cas user attributes.
 * Version: 1.6.1
 * Author: Alain-Aymerick FRANCOIS
 * Author URI: https://wpcassify.wordpress.com/a-propos/
 * License: GPLv2
 */
 
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if (! function_exists( 'get_plugin_data' )) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
}

$wp_cassify_plugin_datas = get_plugin_data( __FILE__ );
$wp_cassify_plugin_directory = plugin_dir_url( __FILE__ ); 
$wp_cassify_network_activated = FALSE;

include( plugin_dir_path( __FILE__ ) . 'config.php');

include( plugin_dir_path( __FILE__ ) . 'classes/vendor/swiftmailer/swiftmailer/lib/swift_required.php');
include( plugin_dir_path( __FILE__ ) . 'classes/wp_cassify_utils.php');
include( plugin_dir_path( __FILE__ ) . 'classes/wp_cassify_rule_solver.php');
include( plugin_dir_path( __FILE__ ) . 'classes/wp_cassify_plugin.php');

include( plugin_dir_path( __FILE__ ) . 'admin/admin-menu.php');

/**
 * Uninstall script of the plugin 
 */ 
register_deactivation_hook( __FILE__, 'wp_cassify_deactivation' );

function wp_cassify_deactivation() {
	
	global $wp_cassify_plugin_options_list;
    global $wp_cassify_network_activated;
	
	foreach ( $wp_cassify_plugin_options_list as $option ) {
        if ( $wp_cassify_network_activated ) {
            delete_site_option( $option );
        }
        else {
            delete_option( $option );
        }
	}  
}

/**
 * Test if plugin is network activated.
 * 
 */
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( is_plugin_active_for_network( 'wp-cassify/wp-cassify.php' ) ) {
    $wp_cassify_network_activated = TRUE;
}

$wp_cassify_admin_page = new \wp_cassify\WP_Cassify_Admin_Page();
$wp_cassify_admin_page->init_parameters(
		$wp_cassify_plugin_datas,
		$wp_cassify_plugin_directory,
		$wp_cassify_network_activated,
		$wp_cassify_plugin_options_list,
		$wp_cassify_default_protocol_version_values,
		$wp_cassify_default_login_servlet,
		$wp_cassify_default_logout_servlet,
		$wp_cassify_default_service_validate_servlet,
		$wp_cassify_default_ssl_cipher_values,
		$wp_cassify_default_ssl_check_certificate,
		$wp_cassify_default_xpath_query_to_extact_cas_user,
		$wp_cassify_default_xpath_query_to_extact_cas_attributes,
		$wp_cassify_default_notifications_options,
		$wp_cassify_default_allow_deny_order,
		$wp_cassify_wordpress_user_meta_list
);

$GLOBALS['wp-cassify'] = new \wp_cassify\WP_Cassify_Plugin(); 
$GLOBALS['wp-cassify']->init_parameters(
        $wp_cassify_network_activated,
		$wp_cassify_default_xpath_query_to_extact_cas_user,
		$wp_cassify_default_xpath_query_to_extact_cas_attributes,
		$wp_cassify_default_redirect_parameter_name,
		$wp_cassify_default_service_ticket_parameter_name,
		$wp_cassify_default_service_service_parameter_name,
		$wp_cassify_default_bypass_parameter_name,
		$wp_cassify_default_wordpress_blog_http_port,
		$wp_cassify_default_wordpress_blog_https_port,
		$wp_cassify_default_ssl_check_certificate,
		$wp_cassify_default_login_servlet,
		$wp_cassify_default_logout_servlet,
		$wp_cassify_default_service_validate_servlet,
		$wp_cassify_default_allow_deny_order,
		$wp_cassify_match_first_level_parenthesis_group_pattern,
		$wp_cassify_match_second_level_parenthesis_group_pattern,
		$wp_cassify_match_cas_variable_pattern,
		$wp_cassify_allowed_operators,
		$wp_cassify_operator_prefix,
		$wp_cassify_allowed_parenthesis,
		$wp_cassify_error_messages
);
?>
