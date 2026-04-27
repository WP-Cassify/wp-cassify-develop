<?php

/**
 * Plugin Name: WP Cassify
 * Plugin URI: https://wpcassify.wordpress.com/
 * Description: CAS Authentication Client for Wordpress. Also, it handle custom authorizations rules from cas user attributes.
 * Version: 2.4.5
 * Requires PHP: 7.0
 * Author: Alain-Aymerick FRANCOIS
 * Author URI: https://wpcassify.wordpress.com/about-me/
 * License: GPLv2
 */
 
defined( 'ABSPATH' ) || exit;

/**
 * Current plugin version. Used for option migration tracking.
 */
define( 'WP_CASSIFY_VERSION', '2.4.5' );

if (! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
}

$wp_cassify_plugin_datas = get_plugin_data( __FILE__, false, false);
$wp_cassify_plugin_directory = plugin_dir_url( __FILE__ ); 
$wp_cassify_network_activated = false;

include( plugin_dir_path( __FILE__ ) . 'config.php' );
include( plugin_dir_path( __FILE__ ) . 'classes/wp_cassify_utils.php' );
include( plugin_dir_path( __FILE__ ) . 'classes/wp_cassify_rule_solver.php' );
include( plugin_dir_path( __FILE__ ) . 'classes/wp_cassify_plugin.php' );
include( plugin_dir_path( __FILE__ ) . 'classes/wp_cassify_shortcodes.php' );

include( plugin_dir_path( __FILE__ ) . 'admin/admin-menu.php' );

global $wpdb;


/**
 * Uninstall script of the plugin 
 */ 
register_deactivation_hook( __FILE__, 'wp_cassify_deactivation' );
register_uninstall_hook( __FILE__, 'wp_cassify_uninstall' );

function wp_cassify_deactivation() {
	// Nothing here.
}

/**
 * Runs option migration tasks when the plugin version changes.
 *
 * Called on 'plugins_loaded' to handle silent upgrades (i.e. updates made
 * through the WordPress update mechanism without manual deactivation/reactivation).
 *
 * Migration added in 2.4.1:
 *   In 2.4.0, the default value of wp_cassify_enable_url_bypass was changed from
 *   'enabled' to 'disabled'. Users who had never explicitly saved this option relied
 *   on the old default being 'enabled'. If we detect a pre-2.4.1 upgrade on an
 *   already-configured site (i.e. wp_cassify_base_url is set), we write 'enabled'
 *   explicitly so their access is not silently broken.
 *   Fresh installs of 2.4.1 (wp_cassify_base_url is empty) are not affected and
 *   correctly start with the 'disabled' default.
 */
function wp_cassify_maybe_migrate_options() {
	global $wp_cassify_network_activated;

	$stored_version = $wp_cassify_network_activated
		? get_site_option( 'wp_cassify_plugin_version', '' )
		: get_option( 'wp_cassify_plugin_version', '' );

	// Nothing to do when already on the current version.
	if ( $stored_version === WP_CASSIFY_VERSION ) {
		return;
	}

	// --- Migration: any version prior to 2.4.1 ---
	// In 2.4.0, wp_cassify_enable_url_bypass was changed to default to 'disabled'.
	// Restore 'enabled' for existing sites that never explicitly configured this option.
	if ( version_compare( $stored_version, '2.4.1', '<' ) ) {
		$existing_bypass = $wp_cassify_network_activated
			? get_site_option( 'wp_cassify_enable_url_bypass', '' )
			: get_option( 'wp_cassify_enable_url_bypass', '' );

		$existing_base_url = $wp_cassify_network_activated
			? get_site_option( 'wp_cassify_base_url', '' )
			: get_option( 'wp_cassify_base_url', '' );

		// Only migrate when the option was never explicitly saved AND the plugin
		// was already configured (base URL set), meaning this is a real upgrade,
		// not a brand-new installation.
		if ( ( $existing_bypass === '' || $existing_bypass === false ) && ! empty( $existing_base_url ) ) {
			\wp_cassify\WP_Cassify_Utils::wp_cassify_update_option(
				$wp_cassify_network_activated,
				'wp_cassify_enable_url_bypass',
				'enabled'
			);
		}
	}

	// Persist the current plugin version so this migration only runs once.
	if ( $wp_cassify_network_activated ) {
		update_site_option( 'wp_cassify_plugin_version', WP_CASSIFY_VERSION );
	} else {
		update_option( 'wp_cassify_plugin_version', WP_CASSIFY_VERSION );
	}
}
add_action( 'plugins_loaded', 'wp_cassify_maybe_migrate_options' );

function wp_cassify_uninstall() {
	
	global $wpdb;
	$option_prefix_like = 'wp_cassify%';
    
    // Delete network activated options
	$network_meta_table = $wpdb->base_prefix . 'sitemeta';
	if ( preg_match( '/^[A-Za-z0-9_]+$/', $network_meta_table ) === 1 ) {
		$network_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $network_meta_table ) );

		if ( $network_table_exists === $network_meta_table ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$network_meta_table}` WHERE `meta_key` LIKE %s", $option_prefix_like ) );
		}
	}

	// Delete blog options
	$blogs = get_sites();

	for( $i = 0; $i <= count( $blogs ) - 1; $i++ ) {
		// 1 is network
		if ( $blogs[ $i ]->blog_id > 1 ) {
			$blog_id = absint( $blogs[ $i ]->blog_id );
			$_tbl_name = $wpdb->base_prefix . $blog_id . '_options';

			if ( preg_match( '/^[A-Za-z0-9_]+$/', $_tbl_name ) === 1 ) {
				$blog_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $_tbl_name ) );

				if ( $blog_table_exists === $_tbl_name ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM `{$_tbl_name}` WHERE `option_name` LIKE %s", $option_prefix_like ) );
				}
			}
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
    $wp_cassify_network_activated = true;
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
		$wp_cassify_default_expirations_options,
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
		$wp_cassify_default_gateway_parameter_name,
		$wp_cassify_default_bypass_parameter_name,
		$wp_cassify_default_cachetimes_for_authrecheck,
		$wp_cassify_default_wordpress_blog_http_port,
		$wp_cassify_default_wordpress_blog_https_port,
		$wp_cassify_default_ssl_check_certificate,
		$wp_cassify_default_login_servlet,
		$wp_cassify_default_logout_servlet,
		$wp_cassify_default_service_validate_servlet,
		$wp_cassify_default_notifications_options,
		$wp_cassify_default_allow_deny_order,
		$wp_cassify_match_first_level_parenthesis_group_pattern,
		$wp_cassify_match_second_level_parenthesis_group_pattern,
		$wp_cassify_match_cas_variable_pattern,
		$wp_cassify_allowed_operators,
		$wp_cassify_operator_prefix,
		$wp_cassify_allowed_parenthesis,
		$wp_cassify_allowed_get_parameters,
		$wp_cassify_error_messages,
		$wp_cassify_user_error_codes,
		$wp_cassify_service_ticket_salt,
		$wp_cassify_default_bypass_parameter_value,
		$wp_cassify_default_enable_url_bypass
);

$wp_cassify_shortcodes = new \wp_cassify\WP_Cassify_Shortcodes();
$wp_cassify_shortcodes->init_parameters(
	$wp_cassify_network_activated,
	$wp_cassify_default_redirect_parameter_name,
	$wp_cassify_default_service_service_parameter_name,
	$wp_cassify_default_wordpress_blog_http_port,
	$wp_cassify_default_wordpress_blog_https_port,
	$wp_cassify_default_login_servlet,
	$wp_cassify_default_logout_servlet
);

?>
