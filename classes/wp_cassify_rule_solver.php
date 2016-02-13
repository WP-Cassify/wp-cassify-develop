<?php

namespace wp_cassify;

class wp_cassify_rule_solver_item {
	
	public $parenthesis_group;
	public $left_operand;
	public $right_operand;
	public $operator;
	public $result = FALSE;
	public $error = FALSE;
	public $error_message = FALSE;
}

class wp_cassify_rule_solver {

	public $match_first_level_parenthesis_group_pattern;
	public $match_second_level_parenthesis_group_pattern;
	public $match_cas_variable_pattern;
	public $allowed_operators = array();
	public $operator_prefix;
	public $allowed_parenthesis = array();
	public $wp_cassify_initial_rule;	
	public $wp_cassify_rule_solver_item_array = array();
	public $error_messages = array();	
	public $cas_user_datas = array();
	
	/**
	 * Wrap operand with double quotes.
	 * @param string $operand
	 * @return $string
	 */ 
	private function wrap_operand_with_double_quotes( $operand ) {
	
		return '"' . $operand . '"';	
	}
	
	/**
	 * Strip double quotes from operand.
	 * @param string $operand
	 * @return $string
	 */ 
	private function strip_double_quotes_from_operand( $operand ) {
	
		return trim( $operand , '"' );	
	}	
	
	private function strip_parenthesis_from_rule() {
		
		$this->wp_cassify_initial_rule = str_replace( $this->allowed_parenthesis, 
			'', 
			$this->wp_cassify_initial_rule ); 
	}

	/**
	 * Match parenthesis group in security rule
	 * @param array &$wp_cassify_rule_solver_item_array
	 * @param string $wp_cassify_initial_rule
	 * @param string $match_parenthesis_group_pattern
	 */
	private function match_parenthesis_groups( 
		&$wp_cassify_rule_solver_item_array,
		$wp_cassify_initial_rule, 
		$match_parenthesis_group_pattern ) {

		$matched_parenthesis_groups = FALSE;
		$matches_parenthesis_groups = array();
		
		unset( $this->wp_cassify_rule_solver_item_array );
		$this->wp_cassify_rule_solver_item_array = array();

		preg_match_all( $match_parenthesis_group_pattern, $wp_cassify_initial_rule, $matches_parenthesis_groups );

		if ( ( is_array( $matches_parenthesis_groups[1] ) ) && ( count( $matches_parenthesis_groups[1] ) > 0 ) ) {
			foreach( $matches_parenthesis_groups[1] as $matches_parenthesis_group ) {
				$wp_cassify_rule_solver_item = new wp_cassify_rule_solver_item();
				$wp_cassify_rule_solver_item->parenthesis_group = $matches_parenthesis_group;
				
				array_push( $this->wp_cassify_rule_solver_item_array, $wp_cassify_rule_solver_item);		
			}
		}
	}
	
	/**
	 * Match variables from CAS Server (CAS Userid and attributes )
	 * @param string $wp_cassify_rule_operand
	 * @param string $match_cas_variable_pattern
	 */ 	
	private function match_cas_variable( &$wp_cassify_rule_operand, $match_cas_variable_pattern ) {

		$matches_cas_variable_groups = array();

		preg_match_all( $match_cas_variable_pattern, $wp_cassify_rule_operand, $matches_cas_variable_groups );

		if ( ( is_array( $matches_cas_variable_groups[1] ) ) && ( count( $matches_cas_variable_groups[1] ) == 1 ) ) {
			// Replace with real value if it's a CAS variable and not a constant.
			$wp_cassify_rule_operand = $this->cas_user_datas[ $matches_cas_variable_groups[1][0] ];
		}
	}	
	
	/**
	 * Determine the operator of a parenthesis group and isolate it.
	 * @param object $wp_cassify_rule_solver_item
	 * @param array $allowed_operators
	 * return bool $set_operator
	 */ 		
	private function set_operator( &$wp_cassify_rule_solver_item, $allowed_operators = array() ) {

		$set_operator = FALSE;

		if ( ( is_array( $allowed_operators ) ) && ( count( $allowed_operators ) > 0 ) ) {
			foreach( $allowed_operators as $allowed_operator ) {
				if ( strpos( $wp_cassify_rule_solver_item->parenthesis_group, $allowed_operator ) != false ) {
					$wp_cassify_rule_solver_item->operator = $allowed_operator;
					$set_operator = TRUE;
				}
			}
		}
		
		return $set_operator;
	}

	/**
	 * Determine the two operands (left and right) of a parenthesis group and isolate it.
	 * @param object $wp_cassify_rule_solver_item
	 * return bool $set_operator
	 */
	private function set_operand( &$wp_cassify_rule_solver_item ) {

		$set_operand = FALSE;

		if (! empty( $wp_cassify_rule_solver_item->operator ) ) {
			$parenthesis_group_exploded = explode( $wp_cassify_rule_solver_item->operator, 
			$wp_cassify_rule_solver_item->parenthesis_group );
			
			if ( ( is_array( $parenthesis_group_exploded ) ) && ( count( $parenthesis_group_exploded ) == 2 ) ) {
				$wp_cassify_rule_solver_item->left_operand = trim( $parenthesis_group_exploded[0], ' ' );
				$wp_cassify_rule_solver_item->right_operand = trim( $parenthesis_group_exploded[1], ' ' );
				
				$set_operand = TRUE;
			}
		}	

		return $set_operand;
	}
	
	/**
	 * Solve equation in a parenthesis group
	 * @param string $wp_cassify_rule_solver_item 
	 */	
	public function solve_item( &$wp_cassify_rule_solver_item ) {
	
		switch( $wp_cassify_rule_solver_item->operator ) {
		
			case '-EQ' :
			
				if ( $this->wrap_operand_with_double_quotes( $wp_cassify_rule_solver_item->left_operand ) == $wp_cassify_rule_solver_item->right_operand ) {
					$wp_cassify_rule_solver_item->result = 'TRUE';	
				}
				else {
					$wp_cassify_rule_solver_item->result = 'FALSE';
				}
			
				break;
				
			case '-NEQ' :
			
				if ( $wp_cassify_rule_solver_item->left_operand != $wp_cassify_rule_solver_item->right_operand ) {
					$wp_cassify_rule_solver_item->result = 'TRUE';	
				}
				else {
					$wp_cassify_rule_solver_item->result = 'FALSE';
				}
			
				break;
					
			case '-CONTAINS' :
				
				if ( strpos( $wp_cassify_rule_solver_item->left_operand, $this->strip_double_quotes_from_operand( $wp_cassify_rule_solver_item->right_operand ) ) !== FALSE ) {
					$wp_cassify_rule_solver_item->result = 'TRUE';	
				}
				else {
					$wp_cassify_rule_solver_item->result = 'FALSE';
				}
			
				break;
				
			case '-STARTWITH' :
			
				if ( $this->startsWith( $wp_cassify_rule_solver_item->right_operand, $wp_cassify_rule_solver_item->left_operand ) ) {
					$wp_cassify_rule_solver_item->result = 'TRUE';	
				}
				else {
					$wp_cassify_rule_solver_item->result = 'FALSE';
				}
			
				break;
				
			case '-ENDWITH' :
			
				if ( $this->endsWith( $wp_cassify_rule_solver_item->left_operand, $wp_cassify_rule_solver_item->right_operand ) ) {
					$wp_cassify_rule_solver_item->result = 'TRUE';	
				}
				else {
					$wp_cassify_rule_solver_item->result = 'FALSE';
				}
			
				break;
				
			case '-AND' :
			
				if ( $wp_cassify_rule_solver_item->left_operand == $wp_cassify_rule_solver_item->right_operand ) {
					$wp_cassify_rule_solver_item->result = 'TRUE';	
				}
				else {
					$wp_cassify_rule_solver_item->result = 'FALSE';
				}
			
				break;
				
			case '-OR' :
			
				if ( ( $wp_cassify_rule_solver_item->left_operand == 'TRUE' ) || ( $wp_cassify_rule_solver_item->right_operand == 'TRUE' ) ) {
					$wp_cassify_rule_solver_item->result = 'TRUE';	
				}
				else {
					$wp_cassify_rule_solver_item->result = 'FALSE';
				}
			
				break;									
				
			default :
			
				$wp_cassify_rule_solver_item->error = 'TRUE';
				$wp_cassify_rule_solver_item->error_message = $this->error_messages[ 'solve_item_error' ];			
						
				break;
		}
	}
	
	/**
	 * Check if string starts with another string.
	 * @param string $haystack
	 * @param string $needle
	 */ 		
	private function startsWith( $haystack, $needle ) {
		
		return $needle === "" || strrpos( $haystack, $needle, -strlen( $haystack ) ) !== FALSE;
	}
	
	/**
	 * Check if string ends with another string.
	 * @param string $haystack
	 * @param string $needle
	 */ 	
	private function endsWith( $haystack, $needle ) {
		
		return $needle === "" || ( ( $temp = strlen( $haystack ) - strlen( $needle ) ) >= 0 && strpos( $haystack, $needle, $temp ) !== FALSE);
	}
	
	private function check_if_no_error() {
	
		$no_error = TRUE;
	
		if ( ( is_array( $this->wp_cassify_rule_solver_item_array ) ) && ( count( $this->wp_cassify_rule_solver_item_array > 0 ) ) ) {
			foreach ($this->wp_cassify_rule_solver_item_array as $wp_cassify_rule_solver_item) {
				if ( $wp_cassify_rule_solver_item->error == 'TRUE' ) {
					$no_error = FALSE;
				}
			}
		}
		
		return $no_error;
	}
	
	/**
	 * Replace parenthesis groups with expression result in rule.
	 * @return string
	 */ 
	private function replace_groups_with_results() {
	
		$wp_cassify_rule = $this->wp_cassify_initial_rule;

		if ( ( is_array( $this->wp_cassify_rule_solver_item_array ) ) && ( count( $this->wp_cassify_rule_solver_item_array ) > 0 ) ) {				
			foreach ($this->wp_cassify_rule_solver_item_array as $wp_cassify_rule_solver_item) {
				$wp_cassify_rule = str_replace( $wp_cassify_rule_solver_item->parenthesis_group, 
					$wp_cassify_rule_solver_item->result,
					$wp_cassify_rule
				);	
			}
		}	
		
		return $wp_cassify_rule;
	}
	
	/**
	 * Simplify the final expression. This function is recursive.
	 */ 
	private function reduce_expression() {
		
		$operators_count = substr_count( $this->wp_cassify_initial_rule, $this->operator_prefix );
	
		if ( $operators_count > 1 ) {
			$first_operator_position = strpos( $this->wp_cassify_initial_rule, $this->operator_prefix);
			
			$search_second_operator_in_right_part = substr( $this->wp_cassify_initial_rule, 
				( $first_operator_position + 1 ), 
				strlen( $this->wp_cassify_initial_rule ) -  $first_operator_position
			);
			$second_operator_position = $first_operator_position + strpos( $search_second_operator_in_right_part, $this->operator_prefix ) + 1;
			
			$left_part = substr( $this->wp_cassify_initial_rule, 0, $second_operator_position );
			$right_part = substr( $this->wp_cassify_initial_rule, 
				$second_operator_position,  
				strlen( $this->wp_cassify_initial_rule ) -  $second_operator_position
			);
			
			$wp_cassify_rule_solver_item = new wp_cassify_rule_solver_item();
			$wp_cassify_rule_solver_item->parenthesis_group = $left_part;
			
			if (! $this->set_operator( $wp_cassify_rule_solver_item, $this->allowed_operators ) ) {
				$wp_cassify_rule_solver_item->error = TRUE;
				$wp_cassify_rule_solver_item->error_message = $this->error_messages[ 'operator_not_found' ];
			}
			
			if (! $this->set_operand( $wp_cassify_rule_solver_item ) ) {
				$wp_cassify_rule_solver_item->error = TRUE;
				$wp_cassify_rule_solver_item->error_message = $this->error_messages[ 'operand_not_found' ];
			}

			$this->solve_item( $wp_cassify_rule_solver_item );	
			$this->wp_cassify_initial_rule = $wp_cassify_rule_solver_item->result . ' ' . $right_part;
		}
		elseif ( $operators_count == 1 ) {
			$wp_cassify_rule_solver_item = new wp_cassify_rule_solver_item();
			$wp_cassify_rule_solver_item->parenthesis_group = $this->wp_cassify_initial_rule;
			
			if (! $this->set_operator( $wp_cassify_rule_solver_item, $this->allowed_operators ) ) {
				$wp_cassify_rule_solver_item->error = TRUE;
				$wp_cassify_rule_solver_item->error_message = $this->error_messages[ 'operator_not_found' ];
			}
			
			if (! $this->set_operand( $wp_cassify_rule_solver_item ) ) {
				$wp_cassify_rule_solver_item->error = TRUE;
				$wp_cassify_rule_solver_item->error_message = $this->error_messages[ 'operand_not_found' ];
			}

			$this->solve_item( $wp_cassify_rule_solver_item );
			$this->wp_cassify_initial_rule = $wp_cassify_rule_solver_item->result;
			
			$operators_count = 0;
		}	
		
		// Recursive call until the expression is reduced
		if ( $operators_count > 0 ) {
			$this->reduce_expression();
		} 
	}
	
	/**
	 * Solve the authentication rule. Check the rule assertion.
	 * @param string $wp_cassify_rule
	 * @return bool
	 */ 
	public function solve( $wp_cassify_rule ) {
		
		$result = FALSE;
		
		$this->wp_cassify_initial_rule = $wp_cassify_rule;
		
		// Reset precedents rule items.
		$this->wp_cassify_rule_solver_item_array = array();
	
		// Step 1 : match second level parenthesis groups
		$this->match_parenthesis_groups($this->wp_cassify_rule_solver_item_array,
			$this->wp_cassify_initial_rule, 
			$this->match_second_level_parenthesis_group_pattern );
			
		// Step 2 : resolve second level parenthesis.
		if ( ( is_array( $this->wp_cassify_rule_solver_item_array ) ) && ( count( $this->wp_cassify_rule_solver_item_array ) > 0 ) ) {
			foreach ($this->wp_cassify_rule_solver_item_array as $wp_cassify_rule_solver_item) {
				if (! $this->set_operator( $wp_cassify_rule_solver_item, $this->allowed_operators ) ) {
					$wp_cassify_rule_solver_item->error = TRUE;
					$wp_cassify_rule_solver_item->error_message = $this->error_messages[ 'operator_not_found' ];
				}
				
				if (! $this->set_operand( $wp_cassify_rule_solver_item ) ) {
					$wp_cassify_rule_solver_item->error = TRUE;
					$wp_cassify_rule_solver_item->error_message = $this->error_messages[ 'operand_not_found' ];
				}

				$this->match_cas_variable( $wp_cassify_rule_solver_item->left_operand, $this->match_cas_variable_pattern);
				$this->match_cas_variable( $wp_cassify_rule_solver_item->right_operand, $this->match_cas_variable_pattern);
				
				$this->solve_item( $wp_cassify_rule_solver_item );		
			}
		}
		
		// Step 3 : replace second level parenthesis groups with result
		if ( $this->check_if_no_error() ) {
			$this->wp_cassify_initial_rule = $this->replace_groups_with_results();
		}

		// Step 4 : match first level parenthesis groups
		$this->match_parenthesis_groups($this->wp_cassify_rule_solver_item_array,
			$this->wp_cassify_initial_rule, 
			$this->match_first_level_parenthesis_group_pattern );	

		// Step 5 : resolve first level parenthesis.
		if ( ( is_array( $this->wp_cassify_rule_solver_item_array ) ) && ( count( $this->wp_cassify_rule_solver_item_array ) > 0 ) ) {
			foreach ($this->wp_cassify_rule_solver_item_array as $wp_cassify_rule_solver_item) {	
				if (! $this->set_operator( $wp_cassify_rule_solver_item, $this->allowed_operators ) ) {
					$wp_cassify_rule_solver_item->error = TRUE;
					$wp_cassify_rule_solver_item->error_message = $this->error_messages[ 'operator_not_found' ];
				}
				
				if (! $this->set_operand( $wp_cassify_rule_solver_item ) ) {
					$wp_cassify_rule_solver_item->error = TRUE;
					$wp_cassify_rule_solver_item->error_message = $this->error_messages[ 'operand_not_found' ];
				}

				$this->match_cas_variable( $wp_cassify_rule_solver_item->left_operand, $this->match_cas_variable_pattern);
				$this->match_cas_variable( $wp_cassify_rule_solver_item->right_operand, $this->match_cas_variable_pattern);
				
				$this->solve_item( $wp_cassify_rule_solver_item );		
			}
		}

		// Step 6 : match first level parenthesis groups
		if ( $this->check_if_no_error() ) {
			$this->wp_cassify_initial_rule = $this->replace_groups_with_results();

			// Step 7 : strip parenthesis from rule
			$this->strip_parenthesis_from_rule(); 

			// Step 8 : reduce the expression
			$this->reduce_expression();
		}
		
		if ( trim( $this->wp_cassify_initial_rule ) == 'TRUE' ) {
			$result = TRUE;
		}

		return $result;		
	}
}

?>
