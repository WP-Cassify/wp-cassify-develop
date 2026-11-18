<?php
namespace wp_cassify\tests;

$tests = array();
foreach ( array( '2027-12-31', '12/31/27', '12/31/2027', '31/12/27', '31/12/2027' ) as $date ) {
    $tests['parse ' . $date] = function () use ( $date ) {
        $result = invoke_private( create_plugin(), 'wp_cassify_parse_expiration_date', $date );
        expect_same( '2027-12-31 00:00:00', $result->format( 'Y-m-d H:i:s' ) );
    };
}
foreach ( array( '', 'not-a-date' ) as $date ) {
    $tests['reject ' . var_export( $date, true )] = function () use ( $date ) {
        expect_same( null, invoke_private( create_plugin(), 'wp_cassify_parse_expiration_date', $date ) );
    };
}
return $tests;
