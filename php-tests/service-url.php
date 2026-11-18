<?php
namespace wp_cassify\tests;
use wp_cassify\WP_Cassify_Utils;

$tests = array(
    'encode URL without query' => function () {
        expect_same( 'https://example.org/?', rawurldecode( WP_Cassify_Utils::wp_cassify_encode_query_in_url( 'https://example.org/' ) ) );
    },
    'strip URL without query' => function () {
        expect_same( 'https://example.org/', WP_Cassify_Utils::wp_cassify_strip_get_parameter( 'https://example.org/', 'ticket' ) );
    },
    'preserve query parameters and fragment' => function () {
        expect_same( 'https://example.org/?x=hello#part', WP_Cassify_Utils::wp_cassify_strip_get_parameter( 'https://example.org/?ticket=ST-1&x=hello#part', 'ticket' ) );
        expect_same( 'https://example.org/?x=hello', rawurldecode( WP_Cassify_Utils::wp_cassify_encode_query_in_url( 'https://example.org/?x=hello' ) ) );
    },
    'bare login callback goes to admin' => function () {
        $GLOBALS['pagenow'] = 'wp-login.php';
        $_SERVER['REQUEST_URI'] = '/wp-login.php';
        expect_same( 'https://example.org/wp-admin/', create_plugin()->wp_cassify_get_service_callback_url() );
    },
);
foreach ( array( '/?redirect_to[]=foo', '/?redirect_to[x][y]=foo' ) as $uri ) {
    $tests['reject ' . $uri] = function () use ( $uri ) {
        $_SERVER['REQUEST_URI'] = $uri;
        try { create_plugin()->wp_cassify_get_service_callback_url(); }
        catch ( Request_Error $error ) { expect_same( 400, $error->getCode() ); return; }
        throw new \RuntimeException( 'Array redirect accepted' );
    };
}
foreach ( array(
    '/' => 'https://example.org/',
    '/?redirect_to=%2Fprivate' => 'https://example.org/private',
    '/?redirect_to=https%3A%2F%2Fexample.org%2Fprivate' => 'https://example.org/private',
    '/?ticket=ST-1&x=hello' => 'https://example.org/?x=hello',
) as $uri => $expected ) {
    $tests['callback ' . $uri] = function () use ( $uri, $expected ) {
        $_SERVER['REQUEST_URI'] = $uri;
        expect_same( $expected, create_plugin()->wp_cassify_get_service_callback_url() );
    };
}
return $tests;
