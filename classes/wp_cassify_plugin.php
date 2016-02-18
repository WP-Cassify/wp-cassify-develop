<?php
namespace wp_cassify;

class WP_Cassify_Plugin {
	
	public $wp_cassify_network_activated;
    
	public $wp_cassify_default_xpath_query_to_extact_cas_user;
	public $wp_cassify_default_xpath_query_to_extact_cas_attributes;
	public $wp_cassify_default_redirect_parameter_name;
	public $wp_cassify_default_service_ticket_parameter_name;	
	public $wp_cassify_default_service_service_parameter_name;
	public $wp_cassify_default_bypass_parameter_name;
	
	public $wp_cassify_default_wordpress_blog_http_port;
	public $wp_cassify_default_wordpress_blog_https_port;	

	public $wp_cassify_default_login_servlet;
	public $wp_cassify_default_logout_servlet;
	public $wp_cassify_default_service_validate_servlet;
	
	public $wp_cassify_default_allow_deny_order;
	
	public $wp_cassify_match_first_level_parenthesis_group_pattern;
	public $wp_cassify_match_second_level_parenthesis_group_pattern;
	public $wp_cassify_match_cas_variable_pattern;
	public $wp_cassify_allowed_operators;
	public $wp_cassify_operator_prefix;
	public $wp_cassify_allowed_parenthesis;
	public $wp_cassify_error_messages;	
	
	private $wp_cassify_allow_rules = array();
	private $wp_cassify_deny_rules = array();

	/**
	 * Constructor
	 */
	public function __construct() {
	}
	
	public function init_parameters(
        $wp_cassify_network_activated,
		$wp_cassify_default_xpath_query_to_extact_cas_user,
		$wp_cassify_default_xpath_query_to_extact_cas_attributes,
		$wp_cassify_default_redirect_parameter_name,
		$wp_cassify_default_service_ticket_parameter_name,
		$wp_cassify_default_service_service_parameter_name,
		$wp_cassify_default_bypass_parameter_name,
		$wp_cassify_default_wordpress_blog_http_port,
		$wp_cassify_default_wordpress_blog_https_port,
		$wp_cassify_default_login_servlet,
		$wp_cassify_default_logout_servlet,
		$wp_cassify_default_service_validate_servlet,
		$wp_cassify_default_allow_deny_order,
		$wp_cassify_match_first_level_parenthesis_group_pattern,
		$wp_cassify_match_second_level_parenthesis_group_pattern,
		$wp_cassify_match_cas_variable_pattern,
		$wp_cassify_allowed_operators,
		$wp_cassify_operator_prefix,
		$wp_cassify_allowed_parenthesis,
		$wp_cassify_error_messages	
	) {
		$this->wp_cassify_network_activated = $wp_cassify_network_activated;
		$this->wp_cassify_default_xpath_query_to_extact_cas_user = $wp_cassify_default_xpath_query_to_extact_cas_user;
		$this->wp_cassify_default_xpath_query_to_extact_cas_attributes = $wp_cassify_default_xpath_query_to_extact_cas_attributes;
		$this->wp_cassify_default_redirect_parameter_name = $wp_cassify_default_redirect_parameter_name;
		$this->wp_cassify_default_service_ticket_parameter_name = $wp_cassify_default_service_ticket_parameter_name;
		$this->wp_cassify_default_service_service_parameter_name = $wp_cassify_default_service_service_parameter_name;	
		$this->wp_cassify_default_bypass_parameter_name = $wp_cassify_default_bypass_parameter_name;
		$this->wp_cassify_default_wordpress_blog_http_port = $wp_cassify_default_wordpress_blog_http_port;
		$this->wp_cassify_default_wordpress_blog_https_port = $wp_cassify_default_wordpress_blog_https_port;
		$this->wp_cassify_default_login_servlet = $wp_cassify_default_login_servlet;
		$this->wp_cassify_default_logout_servlet = $wp_cassify_default_logout_servlet;
		$this->wp_cassify_default_service_validate_servlet = $wp_cassify_default_service_validate_servlet;
		$this->wp_cassify_default_allow_deny_order = $wp_cassify_default_allow_deny_order;
		$this->wp_cassify_match_first_level_parenthesis_group_pattern = $wp_cassify_match_first_level_parenthesis_group_pattern;
		$this->wp_cassify_match_second_level_parenthesis_group_pattern = $wp_cassify_match_second_level_parenthesis_group_pattern;
		$this->wp_cassify_match_cas_variable_pattern = $wp_cassify_match_cas_variable_pattern;
		$this->wp_cassify_allowed_operators = $wp_cassify_allowed_operators;
		$this->wp_cassify_operator_prefix = $wp_cassify_operator_prefix;
		$this->wp_cassify_allowed_parenthesis = $wp_cassify_allowed_parenthesis;
		$this->wp_cassify_error_messages	= $wp_cassify_error_messages;
		
		// Check if CAS Authentication must be bypassed.
		if (! $this->wp_cassify_bypass() ) {
		
			// Add the filters
			add_filter( 'query_vars', array( $this , 'add_custom_query_var' ) );
			add_filter('login_url', array( $this, 'wp_cassify_clear_reauth') );
			
			// Add the actions
			add_action('init', array( $this , 'wp_cassify_session_start' ), 1); 
			add_action( 'init', array( $this , 'wp_cassify_grab_service_ticket' ) , 2); 
			add_action( 'wp_authenticate', array( $this , 'wp_cassify_redirect' ) , 1); 
			add_action( 'wp_logout', array( $this , 'wp_cassify_logout' ) , 10); 		
		}
	}
	
	/**
	 * Allow custom get parameters in url
	 * @param array $vars
	 * @return string
	 */
	public function add_custom_query_var( $vars ){
	  
	  $vars[] = $this->wp_cassify_default_service_ticket_parameter_name;
	  $vars[] = $this->wp_cassify_default_service_service_parameter_name;
	  $vars[] = $this->wp_cassify_default_bypass_parameter_name;
	  
	  return $vars;
	}	
	
	/**
	 * Clear reauth parameter from login url to login directly from CAS server.
	 */ 
    public function wp_cassify_clear_reauth( $login_url ) {
        
        $login_url = remove_query_arg('reauth', $login_url);
        return $login_url;
    }	
	
	/**
	 * Start the php session inside the plugin because session is needed to store callback url.
	 */	 
	public function wp_cassify_session_start() {
		
		if(! session_id() ) {
			session_start();
		}
	}
	
	/**
	 * Perform a redirection to cas server to obtain service ticket.
	 */ 
	public function wp_cassify_grab_service_ticket() {
		
		$service_url = NULL;	
		$service_ticket = NULL;
			
		$wp_cassify_base_url = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_base_url' );
		$wp_cassify_create_user_if_not_exist = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_create_user_if_not_exist' );
		$wp_cassify_ssl_cipher =  WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_ssl_cipher' );
		$wp_cassify_attributes_list = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_attributes_list' );	
		$wp_cassify_login_servlet = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_login_servlet' );
		$wp_cassify_logout_servlet = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_logout_servlet' );
		$wp_cassify_service_validate_servlet =  WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_service_validate_servlet' );
		$wp_cassify_allow_deny_order = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_allow_deny_order' );
		$wp_cassify_autorization_rules = unserialize( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_autorization_rules' ) );		
        $wp_cassify_user_role_rules = unserialize( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_user_role_rules' ) );
        $wp_cassify_user_attributes_mapping_list = unserialize( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_user_attributes_mapping_list' ) );

		// Define default values if options values empty.
		if ( empty( $wp_cassify_login_servlet ) ) {
			$wp_cassify_login_servlet = $this->wp_cassify_default_login_servlet;
		}

		if ( empty( $wp_cassify_logout_servlet ) ) {
			$wp_cassify_logout_servlet = $this->wp_cassify_default_logout_servlet;
		}
		
		if ( empty( $wp_cassify_service_validate_servlet ) ) {
			$wp_cassify_service_validate_servlet = $this->wp_cassify_default_service_validate_servlet;
		}	
		
		if (! empty( $wp_cassify_ssl_cipher ) ) {
			$wp_cassify_ssl_cipher_selected = $wp_cassify_ssl_cipher;
		}
		else {
			$wp_cassify_ssl_cipher_selected = '0';
		}
		
		if ( empty( $wp_cassify_allow_deny_order ) ) {
			$wp_cassify_allow_deny_order = $this->wp_cassify_default_allow_deny_order;
		}		
		
		if ( ( is_array( $wp_cassify_autorization_rules ) ) && ( count( $wp_cassify_autorization_rules ) > 0 ) ) {
			foreach ( $wp_cassify_autorization_rules as $rule_key => $rule_value ) {
				$wp_cassify_autorization_rules[ $rule_key ] = stripslashes( $rule_value );  
			}
		}
		else {
			$wp_cassify_autorization_rules = array();
		}		
		
		$service_url = $this->wp_cassify_get_service_callback_url();
		$service_ticket = $this->wp_cassify_get_service_ticket();	
		
		if (! is_user_logged_in() ) {	
			if (! empty( $service_ticket ) ) {
				$service_validate_url = $wp_cassify_base_url .
					$wp_cassify_service_validate_servlet . '?' .
					$this->wp_cassify_default_service_ticket_parameter_name . '=' . $service_ticket . '&' .
					$this->wp_cassify_default_service_service_parameter_name .'=' . $service_url;				
					
				$cas_server_xml_response = WP_Cassify_Utils::wp_cassify_do_ssl_web_request( 
					$service_validate_url, 
					$wp_cassify_ssl_cipher_selected 
				);
				
				// Parse CAS Server response and store into associative array.
				$cas_user_datas = $this->wp_cassify_parse_xml_response( $cas_server_xml_response );
				
				// Evaluate authorization rules
				if ( count( $wp_cassify_autorization_rules ) > 0 ) {
					$this->wp_cassify_separate_rules( $wp_cassify_autorization_rules );
					
					// Force logout if user is not allowed.
					if (! $this->wp_cassify_is_user_allowed( $cas_user_datas, $wp_cassify_allow_deny_order ) ) {
						$this->wp_cassify_logout_if_not_allowed();
					}	
				}

				// Populate selected attributes into session
				$this->wp_cassify_populate_attributes_into_session( $cas_user_datas, $wp_cassify_attributes_list );

				// Create wordpress user account if not exist
				if ( $wp_cassify_create_user_if_not_exist == 'create_user_if_not_exist' ) {
					WP_Cassify_Utils::wp_cassify_create_wordpress_user( $cas_user_datas[ 'cas_user_id' ], NULL );
				}
				
				// Set wordpress user roles if defined in plugin admin settings
				if ( ( is_array( $wp_cassify_user_role_rules ) ) && ( count( $wp_cassify_user_role_rules ) > 0 ) ) {
					foreach ( $wp_cassify_user_role_rules as $wp_cassify_user_role_rule ) {
						$wp_cassify_user_role_rule_parts = explode( '|', $wp_cassify_user_role_rule );
						
						if ( ( is_array( $wp_cassify_user_role_rule_parts ) ) && ( count( $wp_cassify_user_role_rule_parts ) == 2 ) ) {
							$wp_cassify_user_role_key = $wp_cassify_user_role_rule_parts[0];
							$wp_cassify_user_role_rule_expression = stripslashes( $wp_cassify_user_role_rule_parts[1] );

							if ( $this->wp_cassify_is_user_role_allowed( $cas_user_datas, $wp_cassify_user_role_rule_expression ) ) {
								WP_Cassify_Utils::wp_cassify_set_role_to_wordpress_user( $cas_user_datas[ 'cas_user_id' ], $wp_cassify_user_role_key );		
							}
						}
					}
				}
				
				// Sync CAS User attributes with Wordpress User meta
				$this->wp_cassify_sync_user_metadata( 
					$cas_user_datas[ 'cas_user_id' ], 
					$cas_user_datas, 
					$wp_cassify_user_attributes_mapping_list
				);
				
				// Auth user into wordpress
				WP_Cassify_Utils::wp_cassify_auth_user_wordpress( $cas_user_datas[ 'cas_user_id' ] );

				// Redirect to the service url.
				WP_Cassify_Utils::wp_cassify_redirect_url( $service_url );
			}
			else {
				die( 'Service Ticket not set !');
			}
		}
	}		
	
	/**
	 * Perform a redirection to cas server to obtain service ticket.
	 */ 
	public function wp_cassify_redirect() {

		$service_url = NULL;	
		$service_ticket = NULL;
			
		$wp_cassify_base_url = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_base_url' );
		$wp_cassify_login_servlet = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_login_servlet' );
		$wp_cassify_logout_servlet = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_logout_servlet' );
		
		// Define default values if options values empty.
		if ( empty( $wp_cassify_login_servlet ) ) {
			$wp_cassify_login_servlet = $this->wp_cassify_default_login_servlet;
		}

		if ( empty( $wp_cassify_logout_servlet ) ) {
			$wp_cassify_logout_servlet = $this->wp_cassify_default_logout_servlet;
		}
		
		if ( empty( $wp_cassify_service_validate_servlet ) ) {
			$wp_cassify_service_validate_servlet = $this->wp_cassify_default_service_validate_servlet;
		}	
		
		if (! empty( $wp_cassify_ssl_cipher ) ) {
			$wp_cassify_ssl_cipher_selected = $wp_cassify_ssl_cipher;
		}
		else {
			$wp_cassify_ssl_cipher_selected = '1';
		}	
		
		$service_url = $this->wp_cassify_get_service_callback_url();
		$service_ticket = $this->wp_cassify_get_service_ticket();
		
		if ( (! is_user_logged_in() ) && (! empty( $wp_cassify_base_url ) ) ) {	
			if (! $this->wp_cassify_is_in_while_list( $service_url ) ) {	
				if ( empty( $service_url ) ) {
					die( 'CAS Service URL not set !');
				}	
				elseif ( empty( $service_ticket ) ) {	
					$redirect_url = $wp_cassify_base_url .
						$wp_cassify_login_servlet . '?' .
						$this->wp_cassify_default_service_service_parameter_name . '=' . $service_url;
						
					WP_Cassify_Utils::wp_cassify_redirect_url( $redirect_url );	
				}
			}
		}
	}	
	
	/**
	 * Logout from CAS and Wordpress
	 */ 
	function wp_cassify_logout() {

		$wp_cassify_logout_servlet = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_logout_servlet' );
		$wp_cassify_base_url = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_base_url' );
		$wp_cassify_redirect_url_after_logout = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_after_logout' );
		
		// Define default values if options values empty.
		if ( empty( $wp_cassify_logout_servlet ) ) {
			$wp_cassify_logout_servlet = $this->wp_cassify_default_logout_servlet;
		}
		
		if ( empty ( $wp_cassify_redirect_url_after_logout ) ) {
			$wp_cassify_redirect_url_after_logout = get_home_url();
		}

		// Destroy wordpress session;
		session_destroy();
		
		$redirect_url = $wp_cassify_base_url .
			$wp_cassify_logout_servlet . '?' .
			$this->wp_cassify_default_service_service_parameter_name . '=' . $wp_cassify_redirect_url_after_logout;
		
		// Redirect to the logout CAS end point.
		WP_Cassify_Utils::wp_cassify_redirect_url( $redirect_url );
	}

	/**
	 *  Get the service ticket from cas server request.
	 */ 
	public function wp_cassify_get_service_ticket() {
				
		$wp_cassify_service_ticket = get_query_var( $this->wp_cassify_default_service_ticket_parameter_name );
		
		if ( empty( $wp_cassify_service_ticket ) ) {

			$current_url = WP_Cassify_Utils::wp_cassify_get_current_url(
					$this->wp_cassify_default_wordpress_blog_http_port,
					$this->wp_cassify_default_wordpress_blog_https_port	
				);			
			$wp_cassify_service_ticket = WP_Cassify_Utils::wp_cassify_extract_get_parameter( 
				rawurldecode( $current_url ), 
				$this->wp_cassify_default_service_ticket_parameter_name );
		}

		return $wp_cassify_service_ticket;
	}

	/**
	 * Populate cas user_id and selected attributes from CAS into session.
	 * @param array $cas_user_datas
	 * @param array $wp_cassify_attributes_list
	 */ 
	public function wp_cassify_populate_attributes_into_session( $cas_user_datas, $wp_cassify_attributes_list ) {

		if (! empty( $wp_cassify_attributes_list ) ) {
			$cas_user_attributes_names = explode( ',', $wp_cassify_attributes_list );

			if ( is_array( $cas_user_attributes_names ) ) {
				
				$cas_user_datas_filtered = array();
				
				// cas_user_id is populated by default.
				$cas_user_datas_filtered[ 'cas_user_id' ] = $cas_user_datas[ 'cas_user_id' ];
				
				foreach( $cas_user_attributes_names as $cas_user_attributes_name ) {
					$cas_user_datas_filtered[ $cas_user_attributes_name ] = $cas_user_datas[ $cas_user_attributes_name ];
				}
				
				if( session_id() == '' ) {
    				session_start();
				}
				
				$_SESSION['wp_cassify_cas_user_datas'] = $cas_user_datas_filtered;
			}
		}
	}
	
	/**
	 *  Get the service callback url from php session.
	 *  @return string
	 */ 
	public function wp_cassify_get_service_callback_url() {
		
		$wp_cassify_callback_service_url = WP_Cassify_Utils::wp_cassify_get_current_url(
				$this->wp_cassify_default_wordpress_blog_http_port,
				$this->wp_cassify_default_wordpress_blog_https_port	
			);

		if ( strpos( $wp_cassify_callback_service_url, $this->wp_cassify_default_redirect_parameter_name . '=' ) !== FALSE ) {

			$wp_cassify_callback_service_url = WP_Cassify_Utils::wp_cassify_extract_get_parameter( 
				rawurldecode( $wp_cassify_callback_service_url ), 
				$this->wp_cassify_default_redirect_parameter_name );

			// Append home_url if url contains only /my-slug-page/. callback service url must be fully qualified.
			if ( strrpos( $wp_cassify_callback_service_url, '/', -strlen( $wp_cassify_callback_service_url ) ) !== FALSE ) {
				$wp_cassify_callback_service_url = WP_Cassify_Utils::wp_cassify_get_host_uri( home_url() ) .
					$wp_cassify_callback_service_url;
			}
		}
		else if ( strpos( $wp_cassify_callback_service_url, '?' . $this->wp_cassify_default_service_ticket_parameter_name . '=' ) !== FALSE ) {

			$wp_cassify_callback_service_url = explode( '?' . $this->wp_cassify_default_service_ticket_parameter_name . '=' , $wp_cassify_callback_service_url );
			$wp_cassify_callback_service_url = $wp_cassify_callback_service_url[0];
			
			// Append home_url if url contains only /my-slug-page/. callback service url must be fully qualified.
			if ( strrpos( $wp_cassify_callback_service_url, '/', -strlen( $wp_cassify_callback_service_url ) ) !== FALSE ) {
				$wp_cassify_callback_service_url = WP_Cassify_Utils::wp_cassify_get_host_uri( home_url() ) .
					$wp_cassify_callback_service_url;
			}
		}
		else {
			$wp_cassify_callback_service_url = home_url() . '/';
		}

		return $wp_cassify_callback_service_url;
	}
	
	/**
	 * Logout from CAS and Wordpress
	 */ 
	private function wp_cassify_logout_if_not_allowed() {

		$wp_cassify_logout_servlet = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_logout_servlet' );
		$wp_cassify_base_url = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_base_url' );
		$wp_cassify_redirect_url_if_not_allowed = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_if_not_allowed' );
		
		// Define default values if options values empty.
		if ( empty( $wp_cassify_logout_servlet ) ) {
			$wp_cassify_logout_servlet = $this->wp_cassify_default_logout_servlet;
		}
		
		if ( empty ( $wp_cassify_redirect_url_if_not_allowed ) ) {
			$wp_cassify_redirect_url_if_not_allowed = get_home_url();
		}

		// Destroy wordpress session;
		session_destroy();
		
		$redirect_url = $wp_cassify_base_url .
			$wp_cassify_logout_servlet . '?' .
			$this->wp_cassify_default_service_service_parameter_name . '=' . $wp_cassify_redirect_url_if_not_allowed;
		
		// Redirect to the logout CAS end point.
		WP_Cassify_Utils::wp_cassify_redirect_url( $redirect_url );
	}			

	/**
	 * Parse the CAS Server response
	 * @param string $cas_server_xml_response
	 * @return array $cas_user_datas
	 */
	 private function wp_cassify_parse_xml_response( $cas_server_xml_response ) {

		$wp_cassify_xpath_query_to_extact_cas_user = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_xpath_query_to_extact_cas_user' );
		$wp_cassify_xpath_query_to_extact_cas_attributes = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_xpath_query_to_extact_cas_attributes' );

		// Define default values if options values empty.
		if ( empty( $wp_cassify_xpath_query_to_extact_cas_user ) ) {
			$wp_cassify_xpath_query_to_extact_cas_user = $this->wp_cassify_default_xpath_query_to_extact_cas_user;
		}

		if ( empty( $wp_cassify_xpath_query_to_extact_cas_attributes ) ) {
			$wp_cassify_xpath_query_to_extact_cas_attributes = $this->wp_cassify_default_xpath_query_to_extact_cas_attributes;
		}

		$cas_user_datas = array();

		$cas_user_datas_xml = new \DomDocument(); 
		$cas_user_datas_xml->loadXML( $cas_server_xml_response );

		$xpath = new \DOMXPath( $cas_user_datas_xml );

		// Extract cas user_id
		$query_cas_user = $wp_cassify_xpath_query_to_extact_cas_user;
		$cas_user_entries = $xpath->query( $query_cas_user );

		foreach ( $cas_user_entries as $cas_user_entry ) {
			$cas_user_datas[ 'cas_user_id' ] = $cas_user_entry->nodeValue;
		}

		// Extract attributes
		$query_cas_attributes = $wp_cassify_xpath_query_to_extact_cas_attributes;
		$cas_user_attributes = $xpath->query( $query_cas_attributes );
		
		if ( $cas_user_attributes->length > 0 ) {
			$cas_user_attributes_items = $cas_user_attributes->item( 0 );
			foreach ( $cas_user_attributes_items->childNodes as $cas_user_attributes_item ) {
				$cas_attribute_name = preg_replace( '#^cas:#', '', $cas_user_attributes_item->nodeName );
				$cas_user_datas[ $cas_attribute_name ] = $cas_user_attributes_item->nodeValue;
			}
		}

		return $cas_user_datas;
	}

	/**
	 * Store rules in two array according to her type (ALLOW or DENY).
	 * @param array $wp_cassify_autorization_rules
	 */ 
	private function wp_cassify_separate_rules( $wp_cassify_autorization_rules ) {
		
		foreach ( $wp_cassify_autorization_rules as $wp_cassify_autorization_rule ) {
			$wp_cassify_rule_parts = explode( '|', $wp_cassify_autorization_rule );
			$wp_cassify_rule_type = $wp_cassify_rule_parts[ 0 ]; 
			$wp_cassify_rule_expression = $wp_cassify_rule_parts[ 1 ];
			
			if ( $wp_cassify_rule_type == 'ALLOW' ) {
				array_push( $this->wp_cassify_allow_rules, $wp_cassify_rule_expression );
			}
			
			if ( $wp_cassify_rule_type == 'DENY' ) {
				array_push( $this->wp_cassify_deny_rules, $wp_cassify_rule_expression );
			}
		}
	}
	
	/**
	 * Test if this URL must be bypassed by CAS Authentication
	 * @return bool $wp_cassify_bypass
	 */
	 private function wp_cassify_bypass() {
	 	
	 	$wp_cassify_bypass = FALSE;
	 	
	 	// 1- Check if byposss GET URL parameter is set from the Referrer.
		$current_url = WP_Cassify_Utils::wp_cassify_get_current_url(
			$this->wp_cassify_default_wordpress_blog_http_port,
			$this->wp_cassify_default_wordpress_blog_https_port
		);
		
		$wp_cassify_bypass = WP_Cassify_Utils::wp_cassify_extract_get_parameter( $_SERVER['HTTP_REFERER'], $this->wp_cassify_default_bypass_parameter_name );
		
		// 2- Or check if bypass has been defined in admin panel.
		$wp_cassify_disable_authentication = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_disable_authentication' );
		
		if ( ( $wp_cassify_bypass == 'bypass' ) || ( $wp_cassify_disable_authentication == 'disabled' ) ) {
			$wp_cassify_bypass = TRUE;
		}
		
		return $wp_cassify_bypass;
	 }
	
	/**
	 * Check if url is in white list and don't be authenticated by CAS.
	 * @param string $url
	 * @return bool $is_in_while_list
	 */
	 private function wp_cassify_is_in_while_list( $url ) {
		 
		 $is_in_while_list = FALSE;
		 
		 $wp_cassify_redirect_url_white_list = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_white_list' );
		 $white_list_urls = explode( ';', $wp_cassify_redirect_url_white_list );
		 
		 if ( ( is_array( $white_list_urls ) ) && ( count( $white_list_urls ) > 0 ) ){
			foreach( $white_list_urls as $white_url ) {
				if ( strrpos( $url, $white_url, -strlen( $url ) ) !== FALSE ) {
					$is_in_while_list = TRUE;
				}
			} 
		 }
		 
		 return $is_in_while_list;
	 } 

	/**
	 * Check if user is allow to connect according to autorization rules.
	 * @param array $cas_user_datas
	 * @param string $wp_cassify_allow_deny_order
	 * @return bool $is_user_allowed
	 */ 
	private function wp_cassify_is_user_allowed( $cas_user_datas = array(), $wp_cassify_allow_deny_order ) {

		$is_user_allowed = FALSE;
		$rule_check = FALSE;
		
		$solver = new \wp_cassify\wp_cassify_rule_solver();

		$solver->match_first_level_parenthesis_group_pattern = $this->wp_cassify_match_first_level_parenthesis_group_pattern;
		$solver->match_second_level_parenthesis_group_pattern = $this->wp_cassify_match_second_level_parenthesis_group_pattern;
		$solver->match_cas_variable_pattern = $this->wp_cassify_match_cas_variable_pattern;
		$solver->allowed_operators = $this->wp_cassify_allowed_operators;
		$solver->operator_prefix = $this->wp_cassify_operator_prefix;
		$solver->allowed_parenthesis = $this->wp_cassify_allowed_parenthesis;
		$solver->error_messages = $this->wp_cassify_error_messages;
		$solver->cas_user_datas = $cas_user_datas;

		// Check Allow rules first
		if ( $wp_cassify_allow_deny_order == 'allow, deny' ) {
			if ( ( is_array( $this->wp_cassify_allow_rules ) ) && ( count( $this->wp_cassify_allow_rules ) > 0 ) ) {
				foreach ( $this->wp_cassify_allow_rules as $rule ) {
					if (! $rule_check ) {
						$rule_check = $solver->solve( $rule );
						
						if ( $rule_check ) {
							$is_user_allowed = TRUE;
						}
					}
				}
			}
			
			if ( ( is_array( $this->wp_cassify_deny_rules ) ) && ( count( $this->wp_cassify_deny_rules ) > 0 ) ) {
				foreach ( $this->wp_cassify_deny_rules as $rule ) {
					if (! $rule_check ) {
						$rule_check = $solver->solve( $rule );
						
						if ( $rule_check ) {
							$is_user_allowed = FALSE;
						}							
					}
				}
			}				
		}
		else { // Check Deny Rules first
			if ( ( is_array( $this->wp_cassify_deny_rules ) ) && ( count( $this->wp_cassify_deny_rules ) > 0 ) ) {
				foreach ( $this->wp_cassify_deny_rules as $rule ) {
					if (! $rule_check ) {
						$rule_check = $solver->solve( $rule );
						
						if ( $rule_check ) {
							$is_user_allowed = FALSE;
						}						
					}
				}
			}
			
			if ( ( is_array( $this->wp_cassify_allow_rules ) ) && ( count( $this->wp_cassify_allow_rules ) > 0 ) ) {
				foreach ( $this->wp_cassify_allow_rules as $rule ) {
					if (! $rule_check ) {
						$rule_check = $solver->solve( $rule );
						
						if ( $rule_check ) {
							$is_user_allowed = TRUE;
						}							
					}
				}
			}	
		}

		return $is_user_allowed;
	}
	
	/**
	 * Check if user is matched by Conditionnal User Role Rule
	 * @param array $cas_user_datas
	 * @param string $wp_cassify_user_role_rule
	 * @return $is_user_role_allowed
	 */ 
	private function wp_cassify_is_user_role_allowed( $cas_user_datas = array(), $wp_cassify_user_role_rule ) {

		$is_user_role_allowed = FALSE;

		$solver = new \wp_cassify\wp_cassify_rule_solver();

		$solver->match_first_level_parenthesis_group_pattern = $this->wp_cassify_match_first_level_parenthesis_group_pattern;
		$solver->match_second_level_parenthesis_group_pattern = $this->wp_cassify_match_second_level_parenthesis_group_pattern;
		$solver->match_cas_variable_pattern = $this->wp_cassify_match_cas_variable_pattern;
		$solver->allowed_operators = $this->wp_cassify_allowed_operators;
		$solver->operator_prefix = $this->wp_cassify_operator_prefix;
		$solver->allowed_parenthesis = $this->wp_cassify_allowed_parenthesis;
		$solver->error_messages = $this->wp_cassify_error_messages;
		$solver->cas_user_datas = $cas_user_datas;

		$is_user_role_allowed = $solver->solve( $wp_cassify_user_role_rule );

		return $is_user_role_allowed;
	}

	/**
	 * Synchronize CAS User attributes values with Wordpress User metadatas. Create custom user_meta if not exist.
	 * @param string $cas_user_id
	 * @param array $cas_user_datas
	 * @param array	$wp_cassify_user_attributes_mapping_list
	 */ 
	private function wp_cassify_sync_user_metadata( $cas_user_id, $cas_user_datas = array(), $wp_cassify_user_attributes_mapping_list = array() ) {
		
		if ( ( is_array( $wp_cassify_user_attributes_mapping_list ) ) && ( count( $wp_cassify_user_attributes_mapping_list ) > 0 ) ) {
            $wp_user = get_user_by( 'login', $cas_user_id );
			
			if ( $wp_user != FALSE  ) {
	            foreach( $wp_cassify_user_attributes_mapping_list as $wp_cassify_user_attributes_mapping ) {
	            	$wp_cassify_user_attributes_mapping_parts = explode( '|', $wp_cassify_user_attributes_mapping );
	            	
	            	$wp_cassify_wordpress_user_meta = $wp_cassify_user_attributes_mapping_parts[ '0' ];
	            	$wp_cassify_cas_user_attribute = $wp_cassify_user_attributes_mapping_parts[ '1' ];
	            	
	            	$mapping_set = FALSE;
	            	
	            	if ( property_exists( $wp_user->data, $wp_cassify_wordpress_user_meta ) ) {
	            		$wp_user->data->$wp_cassify_wordpress_user_meta = $cas_user_datas[ $wp_cassify_cas_user_attribute ];
	            		$mapping_set = TRUE;
	            	}
	            	
	            	if (! $mapping_set ) {
	            		
	            		$wp_cassify_wordpress_user_meta_value = get_user_meta( $wp_user->ID, $wp_cassify_wordpress_user_meta );
	            		
	            		if ( empty( $wp_cassify_wordpress_user_meta_value ) ) {
            				add_user_meta( 
            					$wp_user->ID, 
            					$wp_cassify_wordpress_user_meta, 
            					$cas_user_datas[ $wp_cassify_cas_user_attribute ], 
            					TRUE 
    						);
	            		}
	            		else {
	            			update_user_meta( 
	            				$wp_user->ID, 
	            				$wp_cassify_wordpress_user_meta, 
	            				$cas_user_datas[ $wp_cassify_cas_user_attribute ]
            				);
	            		}
	            	}
	            }
			}
		}
	}
}
?>
