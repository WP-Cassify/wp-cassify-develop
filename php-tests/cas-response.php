<?php
namespace wp_cassify\tests;

$tests = array();
foreach ( array( '', '   ', '<broken>' ) as $xml ) {
    $tests['reject ' . var_export( $xml, true )] = function () use ( $xml ) {
        expect_same( array(), invoke_private( create_plugin(), 'wp_cassify_parse_xml_response', $xml ) );
    };
}
foreach ( array( false, true ) as $mode ) {
    $tests['parse CAS response with libxml internal errors ' . ( $mode ? 'on' : 'off' )] = function () use ( $mode ) {
        $xml = '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"><cas:authenticationSuccess><cas:user>alice</cas:user><cas:attributes><cas:email>alice@example.org</cas:email></cas:attributes></cas:authenticationSuccess></cas:serviceResponse>';
        libxml_use_internal_errors( $mode );
        $plugin = create_plugin();
        expect_same( array( 'cas_user_id' => 'alice', 'email' => 'alice@example.org' ), invoke_private( $plugin, 'wp_cassify_parse_xml_response', $xml ) );
        expect_same( $mode, libxml_use_internal_errors() );
        expect_same( array(), invoke_private( $plugin, 'wp_cassify_parse_xml_response', '<broken>' ) );
        expect_same( $mode, libxml_use_internal_errors() );
    };
}
return $tests;
