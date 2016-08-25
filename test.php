<?php

// Test the plugin with php-cli : 
// - sudo chmod +x test.php
// - php ./test.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/wp_cassify_rule_solver.php';

$mock_cas_object = array(
	'first_name' => 'Maria2',
	'email' => 'awoods1a@toplist.cz'
);

// - One operator per parenthesis group like this : (...-AND...)
// - Two level parenthesis maximum are allowed. The first with square brackets and the sub-level with brackets like this : [(...-OR...) -AND (...-AND...)]
// - 
// $condition = '[(CAS{cas_user_id} -EQ "tferguson4") -AND (CAS{courriel} -CONTAINS "my-university.fr")] -OR (CAS{cas_user_id} -STARTWITH "test") -OR [(CAS{cas_user_id} -EQ "tferguson4") -OR (CAS{courriel} -STARTWITH "my-university.fr")]';
//$condition = '(CAS{first_name} -EQ "Maria") -AND (CAS{email} -CONTAINS "mhawkins0@mashable.com")';
// $condition = '(CAS{cas_user_id} -EQ "tferguson4") -AND (CAS{courriel} -CONTAINS "my-university.fr")';
// $condition = '(CAS{cas_user_id} -EQ "tferguson4") -AND (CAS{courriel} -CONTAINS "my-university.fr") -OR (CAS{cas_user_id} -STARTWITH "test")';
$condition = '(CAS{first_name} -EQ "Maria")';


$solver = new \wp_cassify\wp_cassify_rule_solver();

$solver->match_first_level_parenthesis_group_pattern = $wp_cassify_match_first_level_parenthesis_group_pattern;
$solver->match_second_level_parenthesis_group_pattern = $wp_cassify_match_second_level_parenthesis_group_pattern;
$solver->match_cas_variable_pattern = $wp_cassify_match_cas_variable_pattern;
$solver->allowed_operators = $wp_cassify_allowed_operators;
$solver->operator_prefix = $wp_cassify_operator_prefix;
$solver->allowed_parenthesis = $wp_cassify_allowed_parenthesis;
$solver->error_messages = $wp_cassify_error_messages;
$solver->cas_user_datas = $mock_cas_object;

var_dump( $solver->solve( $condition ) );

?>
