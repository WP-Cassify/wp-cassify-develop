<?php
namespace wp_cassify\tests;

$cases = array(
    'exact path' => array( true, '/public/', '/public/' ),
    'optional trailing slash' => array( true, '/public/', '/public' ),
    'subpath with a query' => array( true, '/public/', '/public/news?lang=fr' ),
    'multiple trimmed entries' => array( true, ' /news/ ; ; public/ ', '/public/news' ),
    'ignore query' => array( false, '/public/', '/private/?section=/public/' ),
    'ignore the CAS return target' => array( false, '/public/', '/private/?redirect_to=%2Fpublic%2F' ),
    'ignore fragment' => array( false, '/public/', '/private/#/public/' ),
    'require a complete segment' => array( false, '/public', '/publicity/' ),
    'require a path prefix' => array( false, '/public/', '/other/public/' ),
    'reject dot segments' => array( false, '/public/', '/public/../private/' ),
    'reject encoded dot segments' => array( false, '/public/', '/public/%2e%2e/private/' ),
    'reject encoded separators' => array( false, '/public/', '/public/%2Fnews/' ),
    'ignore a full URL entry' => array( false, 'https://example.org/public/', '/public/' ),
    'ignore an entry with a query' => array( false, '/public/?lang=fr', '/public/?lang=fr' ),
    'ignore empty entries' => array( false, ' ; ; ', '/' ),
    'unset option' => array( false, false, '/public/' ),
    'root entry matches the root' => array( true, '/', '/' ),
    'root entry excludes subpaths' => array( false, '/', '/private/' ),
    'relative to the WordPress Address' => array( true, '/public/', '/wp/public/', '/wp/' ),
    'outside the WordPress Address' => array( false, '/public/', '/public/', '/wp' ),
    'require a complete WordPress Address segment' => array( false, '/public/', '/wp-other/public/', '/wp' ),
);

$tests = array();
foreach ( $cases as $name => $case ) {
    $tests[$name] = function () use ( $case ) {
        list( $expected, $white_list, $path ) = $case;
        $GLOBALS['wp_cassify_test_options'] = array( 'wp_cassify_redirect_url_white_list' => $white_list );
        $GLOBALS['wp_cassify_test_site_url'] = 'https://example.org' . ( $case[3] ?? '' );
        try {
            expect_same( $expected, invoke_private( create_plugin(), 'wp_cassify_is_in_white_list', $path ) );
        } finally {
            unset( $GLOBALS['wp_cassify_test_options'], $GLOBALS['wp_cassify_test_site_url'] );
        }
    };
}
return $tests;
