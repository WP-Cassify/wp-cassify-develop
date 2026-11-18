<?php
namespace wp_cassify\tests;

use wp_cassify\WP_Cassify_Utils;

return array(
    'return normally when debug logging is disabled' => function () {
        expect_same( null, WP_Cassify_Utils::wp_cassify_log( 'Test message' ) );
    },
);
