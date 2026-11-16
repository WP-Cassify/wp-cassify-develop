<?php
// Test-only MU plugin: emulate split home/site URLs without moving core files.
$token_file = __DIR__ . '/cassify-gateway.token';
if ( ! is_readable( $token_file ) || ! hash_equals( file_get_contents( $token_file ), $_SERVER['HTTP_X_CASSIFY_GATEWAY_TOKEN'] ?? '' ) ) {
    return;
}
add_filter( 'site_url', function ( $url, $path ) {
    // Leave core asset and admin URLs usable; only change the whitelist offset.
    return $path === '' ? home_url( '/wp' ) : $url;
}, 10, 2 );
$options = array(
    'wp_cassify_enable_gateway_mode' => 'enabled',
    'wp_cassify_disable_authentication' => '',
    'wp_cassify_enable_url_bypass' => '',
    'wp_cassify_override_service_url' => '',
    'wp_cassify_redirect_url_white_list' => $_SERVER['HTTP_X_CASSIFY_WHITE_LIST'] ?? '/public/',
);
foreach ( $options as $name => $value ) {
    add_filter( 'pre_option_' . $name, function () use ( $value ) { return $value; } );
}
