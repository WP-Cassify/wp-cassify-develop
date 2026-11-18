<?php
namespace wp_cassify {
    // WordPress doubles are namespaced so these tests need no WordPress installation.
    function get_option( $name ) {
        return $GLOBALS['wp_cassify_test_options'][$name] ?? ( $name === 'wp_cassify_service_url_validation_mode' ? 'off' : false );
    }
    function get_site_option( $name ) { return get_option( $name ); }
    function get_query_var( $name, $default = '' ) { return $GLOBALS['wp_cassify_test_query_vars'][$name] ?? $default; }
    function wp_unslash( $value ) { return stripslashes( $value ); }
    function home_url() { return 'https://example.org'; }
    function site_url() { return $GLOBALS['wp_cassify_test_site_url'] ?? home_url(); }
    function admin_url() { return 'https://example.org/wp-admin/'; }
    function do_action( $name, ...$args ) {}
    function apply_filters( $name, $value ) { return $GLOBALS['wp_cassify_test_filters'][$name] ?? $value; }
    function shortcode_atts( $defaults, $attributes ) { return array_merge( $defaults, $attributes ); }
    function esc_url_raw( $url ) { return $url; }
    function wp_safe_redirect( $url ) { throw new \wp_cassify\tests\Request_Redirect( $url ); }
    function __( $message, $domain = '' ) { return $message; }
    function wp_die( $message, $title = '', $args = array() ) {
        throw new \wp_cassify\tests\Request_Error( $message, $args['response'] );
    }
}

namespace wp_cassify\tests {
    require_once __DIR__ . '/../wp-cassify/classes/wp_cassify_utils.php';
    require_once __DIR__ . '/../wp-cassify/classes/wp_cassify_plugin.php';
    require_once __DIR__ . '/../wp-cassify/classes/wp_cassify_rule_solver.php';
    require_once __DIR__ . '/../wp-cassify/classes/wp_cassify_shortcodes.php';

    class Request_Error extends \RuntimeException {}
    class Request_Redirect extends \RuntimeException {}

    function expect_same( $expected, $actual ) {
        if ( $actual !== $expected ) {
            throw new \RuntimeException( 'Expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
        }
    }

    function invoke_private( $plugin, $name, ...$args ) {
        $method = new \ReflectionMethod( $plugin, $name );
        if ( PHP_VERSION_ID < 80100 ) { $method->setAccessible( true ); }
        return $method->invoke( $plugin, ...$args );
    }

    function create_plugin() {
        require __DIR__ . '/../wp-cassify/config.php';
        $plugin = new \wp_cassify\WP_Cassify_Plugin();
        foreach ( array(
            'wp_cassify_default_allow_deny_order',
            'wp_cassify_match_first_level_parenthesis_group_pattern',
            'wp_cassify_match_second_level_parenthesis_group_pattern',
            'wp_cassify_match_cas_variable_pattern',
            'wp_cassify_allowed_operators',
            'wp_cassify_operator_prefix',
            'wp_cassify_allowed_parenthesis',
            'wp_cassify_error_messages',
            'wp_cassify_default_wordpress_blog_http_port',
            'wp_cassify_default_wordpress_blog_https_port',
            'wp_cassify_default_redirect_parameter_name',
            'wp_cassify_default_service_ticket_parameter_name',
            'wp_cassify_default_service_service_parameter_name',
            'wp_cassify_default_gateway_parameter_name',
            'wp_cassify_default_xpath_query_to_extact_cas_user',
            'wp_cassify_default_xpath_query_to_extact_cas_attributes',
        ) as $property ) {
            $plugin->$property = $$property;
        }
        return $plugin;
    }
}
