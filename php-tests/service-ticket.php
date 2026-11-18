<?php
namespace wp_cassify\tests;

$tests = array(
    'no ticket at a gateway callback' => function () {
        $_SERVER['REQUEST_URI'] = '/?gateway=true';
        expect_same( null, create_plugin()->wp_cassify_get_service_ticket() );
    },
    'preserve a custom ticket parameter and decode its value once' => function () {
        $plugin = create_plugin();
        $plugin->wp_cassify_default_service_ticket_parameter_name = 'cas_ticket';
        $_SERVER['REQUEST_URI'] = '/?cas_ticket=ST-demo%252B%2B';
        expect_same( 'ST-demo%2B+', $plugin->wp_cassify_get_service_ticket() );
    },
    'encoded query delimiters do not create a ticket parameter' => function () {
        $_SERVER['REQUEST_URI'] = '/?next=hello%26ticket%3DST-demo';
        expect_same( null, create_plugin()->wp_cassify_get_service_ticket() );
    },
    'preserve the WordPress query variable after unslashing' => function () {
        $GLOBALS['wp_cassify_test_query_vars']['ticket'] = addslashes( "ST-with'quote" );
        try {
            expect_same( "ST-with'quote", create_plugin()->wp_cassify_get_service_ticket() );
        } finally {
            unset( $GLOBALS['wp_cassify_test_query_vars'] );
        }
    },
    'reject an array from WordPress even when the URL has a ticket' => function () {
        $_SERVER['REQUEST_URI'] = '/?ticket=ST-valid';
        $GLOBALS['wp_cassify_test_query_vars']['ticket'] = array();
        try {
            expect_same( null, create_plugin()->wp_cassify_get_service_ticket() );
        } finally {
            unset( $GLOBALS['wp_cassify_test_query_vars'] );
        }
    },
    'encode opaque ticket and service values with RFC 3986' => function () {
        $plugin = create_plugin();
        $plugin->wp_cassify_default_service_ticket_parameter_name = 'cas_ticket';
        $plugin->wp_cassify_default_service_service_parameter_name = 'cas_service';
        $endpoint = 'https://cas.example.org/serviceValidate';
        $ticket = 'ST-opaque&+%';
        $_SERVER['REQUEST_URI'] = '/?cas_ticket=' . rawurlencode( $ticket );
        $service = 'https://example.org/?q=hello world&next=%2F';
        $url = invoke_private( $plugin, 'wp_cassify_build_service_validate_url',
            $endpoint, $plugin->wp_cassify_get_service_ticket(), $service
        );

        expect_same(
            $endpoint . '?cas_ticket=ST-opaque%26%2B%25&cas_service=https%3A%2F%2Fexample.org%2F%3Fq%3Dhello%20world%26next%3D%252F',
            $url
        );
    },
);

foreach ( array(
    'array' => 'ticket[]=ST-demo',
    'nested array' => 'ticket[x][y]=ST-demo',
    'empty value' => 'ticket=',
    'wrong prefix' => 'ticket=demo',
    'empty suffix' => 'ticket=ST-',
    'whitespace' => 'ticket=ST-demo%20',
    'control character' => 'ticket=ST-demo%0A',
    'more than 4096 bytes' => 'ticket=ST-' . str_repeat( 'x', 4094 ),
) as $name => $query ) {
    $tests['reject ' . $name] = function () use ( $query ) {
        $_SERVER['REQUEST_URI'] = '/?' . $query;
        expect_same( null, create_plugin()->wp_cassify_get_service_ticket() );
    };
}

return $tests;
