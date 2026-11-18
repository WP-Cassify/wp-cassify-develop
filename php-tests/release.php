<?php
namespace wp_cassify\tests;

return array(
    'release gates SVN credentials on PHP and browser suites and pins the deploy action' => function () {
        $release = file_get_contents( __DIR__ . '/../.github/workflows/release.yml' );
        $php_tests = file_get_contents( __DIR__ . '/../.github/workflows/php-tests.yml' );
        expect_same( 1, preg_match( '/^on: \[[^\n]*\bworkflow_call\b[^\n]*\]/m', $php_tests ) );
        expect_same( 1, preg_match( '#^  test:\n(?:    [^\n]+\n)*    uses: \./\.github/workflows/php-tests\.yml$#m', $release ) );
        expect_same( 1, preg_match( '/^  release:\n(?:    [^\n]+\n)*    needs: \[test, browser-php8, browser-php7, browser-multisite\]$/m', $release ) );
        foreach ( array( '', '-php7', '-multisite' ) as $variant ) {
            $browser_workflow = 'docker-playwright-tests' . $variant . '.yml';
            $browser = file_get_contents( __DIR__ . '/../.github/workflows/' . $browser_workflow );
            expect_same( true, strpos( $release, 'uses: ./.github/workflows/' . $browser_workflow ) !== false );
            expect_same( true, strpos( $browser, 'workflow_call:' ) !== false );
            expect_same( true, strpos( $browser, '--exit-code-from playwright-suite' ) !== false );
        }
        expect_same( 1, preg_match( '#uses: 10up/action-wordpress-plugin-deploy@[a-f0-9]{40}(?:\s|$)#', $release ) );
        expect_same( 1, preg_match( '#^          BUILD_DIR: wp-cassify/$#m', $release ) );
    },
);
