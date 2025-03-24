<?php

require_once __DIR__ . '/classes/wp_cassify_rule_solver.php';

function run_tests() {

    require_once __DIR__ . '/config.php';
    
    $mock_cas_object = [
        'first_name' => 'Maria2',
        'email' => 'awoods1a@toplist.cz',
        'eduPersonAffiliation' => ['alumn', 'student', 'affiliate']
    ];

    $solver = new \wp_cassify\wp_cassify_rule_solver();

    $solver->match_first_level_parenthesis_group_pattern = $wp_cassify_match_first_level_parenthesis_group_pattern;
    $solver->match_second_level_parenthesis_group_pattern = $wp_cassify_match_second_level_parenthesis_group_pattern;
    $solver->match_cas_variable_pattern = $wp_cassify_match_cas_variable_pattern;
    $solver->allowed_operators = $wp_cassify_allowed_operators;
    $solver->operator_prefix = $wp_cassify_operator_prefix;
    $solver->allowed_parenthesis = $wp_cassify_allowed_parenthesis;
    $solver->error_messages = $wp_cassify_error_messages;
    $solver->cas_user_datas = $mock_cas_object;

    $tests = [
        '(CAS{first_name} -EQ "Maria") -AND (CAS{email} -CONTAINS "mhawkins0@mashable.com")' => false,
        '(CAS{first_name} -EQ "Maria2") -AND (CAS{email} -CONTAINS "awoods1a@toplist.cz")' => true,
        '(CAS{first_name} -NEQ "Maria2") -AND (CAS{email} -CONTAINS "awoods1a@toplist.cz")' => false,
        '(CAS{first_name} -NEQ "Maria2") -OR (CAS{email} -CONTAINS "awoods1a@toplist.cz")' => true,
        '[(CAS{eduPersonAffiliation} -CONTAINS "student") -AND (CAS{email} -ENDWITH "toplist.cz")]' => true,
        '(CAS{eduPersonAffiliation} -IN "alumn")' => true,
        '(CAS{eduPersonAffiliation} -IN "al")' => false,
        '(CAS{eduPersonAffiliation} -NOTIN "alumn")' => false,
        '(CAS{eduPersonAffiliation} -NOTIN "student")' => false,        
        '(CAS{eduPersonAffiliation} -NOTIN "al")' => true,
        '(CAS{eduPersonAffiliation} -CONTAINS "al")' => true,
        '(CAS{eduPersonAffiliation} -NCONTAINS "la")' => true,
        '(CAS{eduPersonAffiliation} -NCONTAINS "al")' => false,
        '(CAS{first_name} -STARTWITH "Mari"))' => true,
        '(CAS{first_name} -STARTWITH "ari"))' => false,
        '(CAS{first_name} -ENDWITH "2"))' => true,
        '(CAS{first_name} -ENDWITH "aria"))' => false,
    ];

    $all_tests_passed = true;
    foreach ($tests as $condition => $expected) {
        $result = $solver->solve($condition);
        assert($result === $expected, "Test failed: '$condition' expected " . var_export($expected, true) . " but got " . var_export($result, true));
        if($result === $expected) {
            echo "\tTest OK : '$condition' => " . var_export($expected, true) . "\n";
        } else {
            $all_tests_passed = false;
            echo "\tTest KO : '$condition' => " . var_export($result, true) . " but " . var_export($expected, true) . " was expected\n";
        }
    }

    if (!$all_tests_passed) {
        exit(1);
    }
}

run_tests();

echo "All tests executed.\n";
