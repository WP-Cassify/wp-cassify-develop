<?php
namespace wp_cassify\tests;

$rules = array(
    'ALLOW|(CAS{group} -EQ "admin")',
    'ALLOW|(CAS{group} -EQ "staff")',
    'DENY|(CAS{blocked} -EQ "suspended")',
    'DENY|(CAS{blocked} -EQ "yes")',
);

$cases = array(
    'both groups match' => array( $rules, 'staff', 'yes', false, true ),
    'only ALLOW matches' => array( $rules, 'staff', 'no', true, true ),
    'only DENY matches' => array( $rules, 'guest', 'yes', false, false ),
    'neither group matches' => array( $rules, 'guest', 'no', false, false ),
    'no ALLOW rules' => array( array_slice( $rules, 2 ), 'staff', 'yes', false, false ),
    'no DENY rules' => array( array_slice( $rules, 0, 2 ), 'staff', 'yes', true, true ),
);

$tests = array();
foreach ( $cases as $name => $case ) {
    list( $case_rules, $group, $blocked, $allow_deny, $deny_allow ) = $case;
    foreach ( array( 'allow, deny' => $allow_deny, 'deny, allow' => $deny_allow ) as $order => $expected ) {
        $tests[$name . ' with ' . $order] = function () use ( $case_rules, $group, $blocked, $order, $expected ) {
            $plugin = create_plugin();
            invoke_private( $plugin, 'wp_cassify_separate_rules', $case_rules );
            $attributes = array( 'group' => $group, 'blocked' => $blocked );
            expect_same( $expected, invoke_private( $plugin, 'wp_cassify_is_user_allowed', $order, $attributes ) );
        };
    }
}

$tests['DENY takes precedence by default'] = function () use ( $rules ) {
    $plugin = create_plugin();
    invoke_private( $plugin, 'wp_cassify_separate_rules', $rules );
    $attributes = array( 'group' => 'staff', 'blocked' => 'yes' );
    expect_same( false, invoke_private( $plugin, 'wp_cassify_is_user_allowed', false, $attributes ) );
};

return $tests;
