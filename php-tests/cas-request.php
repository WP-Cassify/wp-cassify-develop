<?php
namespace wp_cassify {
    function get_current_blog_id() { return 1; }
    function is_user_logged_in() { return $GLOBALS['wp_cassify_test_logged_in'] ?? false; }
    function is_multisite() { return false; }
    function wp_doing_ajax() { return false; }
    function wp_doing_cron() { return false; }
    function has_filter( $name ) { return isset( $GLOBALS['wp_cassify_test_filters'][$name] ); }
    function esc_attr( $value ) { return (string) $value; }

    function curl_init() { return new \stdClass(); }
    function curl_setopt( $handle, $option, $value ) {
        $handle->options[$option] = $value;
        return true;
    }
    function curl_exec( $handle ) {
        \wp_cassify\tests\expect_same( $GLOBALS['wp_cassify_test_timeout'] ?? 10, $handle->options[CURLOPT_CONNECTTIMEOUT] ?? false );
        \wp_cassify\tests\expect_same( $GLOBALS['wp_cassify_test_timeout'] ?? 10, $handle->options[CURLOPT_TIMEOUT] ?? false );
        \wp_cassify\tests\expect_same( PHP_SESSION_NONE, session_status() );
        $GLOBALS['wp_cassify_test_validation_url'] = $handle->options[CURLOPT_URL];
        return curl_errno( $handle ) ? false : '<broken>';
    }
    function curl_errno( $handle ) { return $GLOBALS['wp_cassify_test_curl_errno']; }
    function curl_close( $handle ) {}
}

namespace wp_cassify\tests {
    use wp_cassify\WP_Cassify_Utils;

    return array(
        'release the session during validation and resume it after the response' => function () {
            $_SERVER['REQUEST_URI'] = '/?ticket=ST-demo';
            $plugin = create_plugin();
            $save_path = session_save_path();
            $session = $_SESSION ?? null;
            $get = $_GET;
            $_GET['wp_cassify_membership_retry'] = '1';
            session_save_path( sys_get_temp_dir() );
            session_start();
            $session_id = session_id();
            $_SESSION['cas_request_test'] = 'preserved';
            $_SESSION['wp_cassify'][1]['membership_attempt_user'] = 123;
            $_SESSION['wp_cassify'][1]['membership_retry_at'] = time() + 30;

            try {
                foreach ( array( CURLE_OPERATION_TIMEDOUT => 504, 0 => 403 ) as $curl_errno => $status ) {
                    $GLOBALS['wp_cassify_test_curl_errno'] = $curl_errno;
                    try {
                        $plugin->wp_cassify_grab_service_ticket();
                        throw new \RuntimeException( 'Expected validation to fail' );
                    } catch ( Request_Error $error ) {
                        expect_same( $status, $error->getCode() );
                        expect_same(
                            $curl_errno ? 'The CAS server did not respond in time. Please try signing in again.' : 'CAS Authentication failed.',
                            $error->getMessage()
                        );
                    }

                    expect_same( $curl_errno ? PHP_SESSION_NONE : PHP_SESSION_ACTIVE, session_status() );
                    if ( $curl_errno ) {
                        session_start();
                        expect_same( false, isset( $_SESSION['wp_cassify'][1]['membership_attempt_user'] ) );
                        expect_same( false, isset( $_SESSION['wp_cassify'][1]['membership_retry_at'] ) );
                    }
                    expect_same( $session_id, session_id() );
                    expect_same( 'preserved', $_SESSION['cas_request_test'] );
                    expect_same( false, $plugin->wp_cassify_is_authenticated() );
                }
            } finally {
                if ( session_status() !== PHP_SESSION_ACTIVE ) {
                    session_start();
                }
                session_destroy();
                session_id( '' );
                session_save_path( $save_path );
                $_GET = $get;
                if ( $session === null ) {
                    unset( $_SESSION );
                } else {
                    $_SESSION = $session;
                }
                unset( $GLOBALS['wp_cassify_test_curl_errno'], $GLOBALS['wp_cassify_test_validation_url'] );
            }
        },
        'preserve the original cURL budget when an upgrade has a zero timeout' => function () {
            $GLOBALS['wp_cassify_test_curl_errno'] = 0;
            $GLOBALS['wp_cassify_test_timeout'] = false;
            try {
                expect_same( '<broken>', WP_Cassify_Utils::wp_cassify_do_ssl_web_request( 'https://cas.example.org/serviceValidate', 0, 'disabled', '', '', 0 ) );
            } finally {
                unset( $GLOBALS['wp_cassify_test_curl_errno'], $GLOBALS['wp_cassify_test_timeout'], $GLOBALS['wp_cassify_test_validation_url'] );
            }
        },
        'keep HTTP 502 for other cURL errors' => function () {
            $GLOBALS['wp_cassify_test_curl_errno'] = CURLE_COULDNT_CONNECT;
            try {
                WP_Cassify_Utils::wp_cassify_do_ssl_web_request( 'https://cas.example.org/serviceValidate', 0 );
            } catch ( Request_Error $error ) {
                expect_same( 502, $error->getCode() );
                return;
            } finally {
                unset( $GLOBALS['wp_cassify_test_curl_errno'], $GLOBALS['wp_cassify_test_validation_url'] );
            }
            throw new \RuntimeException( 'Expected the connection error to stop validation' );
        },
    );
}
