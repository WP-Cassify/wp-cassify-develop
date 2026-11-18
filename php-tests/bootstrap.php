<?php
namespace wp_cassify {
    // WordPress doubles are namespaced so these tests need no WordPress installation.
    function get_option( $name ) { return $name === 'wp_cassify_service_url_validation_mode' ? 'off' : false; }
    function get_site_option( $name ) { return get_option( $name ); }
    function home_url() { return 'https://example.org'; }
    function admin_url() { return 'https://example.org/wp-admin/'; }
    function __( $message, $domain = '' ) { return $message; }
    function wp_die( $message, $title = '', $args = array() ) {
        throw new \wp_cassify\tests\Request_Error( $message, $args['response'] );
    }
}

namespace wp_cassify\tests {
    require_once __DIR__ . '/../wp-cassify/classes/wp_cassify_utils.php';
    require_once __DIR__ . '/../wp-cassify/classes/wp_cassify_plugin.php';
    require_once __DIR__ . '/../wp-cassify/classes/wp_cassify_rule_solver.php';

    class Request_Error extends \RuntimeException {}

    function expect_same( $expected, $actual ) {
        if ( $actual !== $expected ) {
            throw new \RuntimeException( 'Expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
        }
    }

    function invoke_private( $plugin, $name, $value ) {
        $method = new \ReflectionMethod( $plugin, $name );
        if ( PHP_VERSION_ID < 80100 ) { $method->setAccessible( true ); }
        return $method->invoke( $plugin, $value );
    }

    function create_plugin() {
        require __DIR__ . '/../wp-cassify/config.php';
        $plugin = new \wp_cassify\WP_Cassify_Plugin();
        foreach ( array(
            'wp_cassify_default_wordpress_blog_http_port',
            'wp_cassify_default_wordpress_blog_https_port',
            'wp_cassify_default_redirect_parameter_name',
            'wp_cassify_default_service_ticket_parameter_name',
            'wp_cassify_default_gateway_parameter_name',
            'wp_cassify_default_xpath_query_to_extact_cas_user',
            'wp_cassify_default_xpath_query_to_extact_cas_attributes',
        ) as $property ) {
            $plugin->$property = $$property;
        }
        return $plugin;
    }
}
