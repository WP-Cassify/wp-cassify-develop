<?php
namespace wp_cassify\tests;

use wp_cassify\WP_Cassify_Shortcodes;
use wp_cassify\WP_Cassify_Utils;

return array(
    'ignore non-CAS and malformed tickets for visitors and authenticated users' => function () {
        $plugin = create_plugin();
        try {
            foreach ( array( false, true ) as $logged_in ) {
                $GLOBALS['wp_cassify_test_logged_in'] = $logged_in;
                foreach ( array( 'ticket=order-123', 'ticket[]=order-123', 'ticket[]=ST-demo', 'ticket=ST-' ) as $query ) {
                    $_SERVER['REQUEST_URI'] = '/orders?' . $query;
                    expect_same( null, $plugin->wp_cassify_grab_service_ticket() );
                }
            }
        } finally {
            unset( $GLOBALS['wp_cassify_test_logged_in'] );
        }
    },
    'allow a gateway callback without a ticket' => function () {
        $_SERVER['REQUEST_URI'] = '/?q=books';
        expect_same( null, create_plugin()->wp_cassify_grab_service_ticket() );
    },
    'restore a business query on an anonymous gateway return' => function () {
        $destination = 'https://example.org/orders/?ticket=order-123&view=full';
        $service = WP_Cassify_Utils::wp_cassify_prepare_cas_service( $destination, 'ticket' );
        $_SERVER['REQUEST_URI'] = substr( $service, strlen( 'https://example.org' ) );

        expect_same( null, create_plugin()->wp_cassify_grab_service_ticket() );
        $previous_session = $_SESSION ?? null;
        $_SESSION['wp_cassify'][1]['auth_checked'] = true;
        try {
            create_plugin()->wp_cassify_grab_service_ticket();
        } catch ( Request_Redirect $redirect ) {
            expect_same( $destination, $redirect->getMessage() );
            return;
        } finally {
            $_SESSION = $previous_session;
        }
        throw new \RuntimeException( 'Gateway did not restore the destination query' );
    },
    'use the same enforce-mode service for login and validation' => function () {
        $plugin = create_plugin();
        $settings = $GLOBALS['wp_cassify_test_options'] ?? array();
        $GLOBALS['wp_cassify_test_options'] = array(
            'wp_cassify_service_url_validation_mode' => 'enforce',
            'wp_cassify_service_url_allowed_hosts' => 'example.org',
            'wp_cassify_base_url' => 'https://cas.example.org/',
            'wp_cassify_service_validate_servlet' => 'serviceValidate',
        );
        $GLOBALS['wp_cassify_test_curl_errno'] = 0;
        try {
            foreach ( array( false, true ) as $gateway ) {
                $_SERVER['REQUEST_URI'] = '/search/?q=hello%20world&part=one&part=two&next=%2F';
                $login = invoke_private( $plugin, 'wp_cassify_build_login_url', 'https://cas.example.org/login', $gateway, ! $gateway );
                parse_str( parse_url( $login, PHP_URL_QUERY ), $login_parameters );
                $service = 'https://example.org' . $_SERVER['REQUEST_URI'];
                if ( ! $gateway ) {
                    $service .= '&wp_cassify_membership_retry=1';
                }
                expect_same( $service, $login_parameters['service'] );
                expect_same( $gateway ? 'true' : null, $login_parameters['gateway'] ?? null );

                $_SERVER['REQUEST_URI'] = substr( $service, strlen( 'https://example.org' ) ) . '&ticket=ST-demo';
                try {
                    $plugin->wp_cassify_grab_service_ticket();
                    throw new \RuntimeException( 'The stub CAS response should fail after validation' );
                } catch ( Request_Error $error ) {
                    expect_same( 403, $error->getCode() );
                }
                parse_str( parse_url( $GLOBALS['wp_cassify_test_validation_url'], PHP_URL_QUERY ), $validation_parameters );
                expect_same( $service, $validation_parameters['service'] );
                expect_same( 'ST-demo', $validation_parameters['ticket'] );
            }
        } finally {
            $GLOBALS['wp_cassify_test_options'] = $settings;
            unset( $GLOBALS['wp_cassify_test_curl_errno'], $GLOBALS['wp_cassify_test_validation_url'] );
        }
    },
    'append the membership retry parameter to a service without a query' => function () {
        $_SERVER['REQUEST_URI'] = '/wp-admin/';
        $login = invoke_private( create_plugin(), 'wp_cassify_build_login_url', 'https://cas.example.org/login', false, true );
        parse_str( parse_url( $login, PHP_URL_QUERY ), $parameters );
        expect_same( 'https://example.org/wp-admin/?wp_cassify_membership_retry=1', $parameters['service'] );
    },
    'preserve the query when an unlisted service falls back to the canonical host' => function () {
        $settings = $GLOBALS['wp_cassify_test_options'] ?? array();
        $GLOBALS['wp_cassify_test_options'] = array(
            'wp_cassify_service_url_validation_mode' => 'enforce',
            'wp_cassify_service_url_allowed_hosts' => 'example.org',
        );
        $_SERVER['SERVER_NAME'] = 'external.example.net';
        $_SERVER['REQUEST_URI'] = '/search/?q=hello%20world';
        try {
            $login = invoke_private( create_plugin(), 'wp_cassify_build_login_url', 'https://cas.example.org/login', false, false );
            parse_str( parse_url( $login, PHP_URL_QUERY ), $parameters );
            expect_same( 'https://example.org/search/?q=hello%20world', $parameters['service'] );
        } finally {
            $GLOBALS['wp_cassify_test_options'] = $settings;
        }
    },
    'an explicitly empty query survives a CAS round trip' => function () {
        $destination = 'https://example.org/orders/?';
        $service = WP_Cassify_Utils::wp_cassify_prepare_cas_service( $destination, 'ticket' );
        expect_same( $destination, WP_Cassify_Utils::wp_cassify_get_cas_return_url( $service . '&ticket=ST-demo', 'ticket' ) );
    },
    'restore the original custom ticket and fragment' => function () {
        $shortcode = new WP_Cassify_Shortcodes();
        $shortcode->wp_cassify_base_url = 'https://cas.example.org/';
        $shortcode->wp_cassify_default_login_servlet = 'login';
        $shortcode->wp_cassify_default_service_service_parameter_name = 'service';
        $shortcode->wp_cassify_default_service_ticket_parameter_name = 'cas_ticket';
        $destination = 'https://example.org/orders?cas_ticket=order-123&view=full#details';
        $login = $shortcode->wp_cassify_login_with_redirect( array( 'service_redirect_url' => $destination ) );
        parse_str( parse_url( $login, PHP_URL_QUERY ), $query );
        $service = $query['service'];
        expect_same( null, WP_Cassify_Utils::wp_cassify_extract_get_parameter( $service, 'cas_ticket' ) );
        expect_same( $destination, WP_Cassify_Utils::wp_cassify_get_cas_return_url( $service . '&cas_ticket=ST-demo', 'cas_ticket' ) );
    },
);
