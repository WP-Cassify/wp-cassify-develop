<?php
// Run all PHP suites from the repository root: php php-tests/run.php
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );
require __DIR__ . '/bootstrap.php';

$total = 0;
$failures = 0;
foreach ( array( 'rule-solver', 'authorization', 'expiration', 'service-url', 'white-list', 'cas-response' ) as $suite ) {
    $tests = require __DIR__ . '/' . $suite . '.php';
    foreach ( $tests as $name => $test ) {
        ++$total;
        // Each case gets a clean request; restore shared state even on failure.
        $server = $_SERVER;
        $request = $_REQUEST;
        $had_pagenow = array_key_exists( 'pagenow', $GLOBALS );
        $pagenow = $had_pagenow ? $GLOBALS['pagenow'] : null;
        $libxml_mode = libxml_use_internal_errors();
        try {
            $_SERVER = array( 'SERVER_NAME' => 'example.org', 'SERVER_PORT' => 443, 'HTTPS' => 'on' );
            $_REQUEST = array();
            unset( $GLOBALS['pagenow'] );
            $test();
            echo "PASS: $suite / $name\n";
        } catch ( Throwable $error ) {
            ++$failures;
            echo "FAIL: $suite / $name: " . $error->getMessage() . "\n";
        } finally {
            $_SERVER = $server;
            $_REQUEST = $request;
            if ( $had_pagenow ) { $GLOBALS['pagenow'] = $pagenow; }
            else { unset( $GLOBALS['pagenow'] ); }
            libxml_clear_errors();
            libxml_use_internal_errors( $libxml_mode );
        }
    }
}
echo 'PHP ' . PHP_VERSION . ": $total tests, $failures failures\n";
exit( $failures ? 1 : 0 );
