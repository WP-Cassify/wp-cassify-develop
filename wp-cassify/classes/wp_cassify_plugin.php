<?php
namespace wp_cassify;

class WP_Cassify_Plugin {
	
	public $wp_cassify_network_activated;
    
	public $wp_cassify_default_xpath_query_to_extact_cas_user;
	public $wp_cassify_default_xpath_query_to_extact_cas_attributes;
	public $wp_cassify_default_redirect_parameter_name;
	public $wp_cassify_default_service_ticket_parameter_name;	
	public $wp_cassify_default_service_service_parameter_name;
	public $wp_cassify_default_gateway_parameter_name;
	public $wp_cassify_default_bypass_parameter_name;
	public $wp_cassify_default_cachetimes_for_authrecheck;
	
	public $wp_cassify_default_wordpress_blog_http_port;
	public $wp_cassify_default_wordpress_blog_https_port;
	public $wp_cassify_default_ssl_check_certificate;

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
	
	public $wp_cassify_allowed_get_parameters;
	public $wp_cassify_error_messages;	
	public $wp_cassify_user_error_codes;
	
	private $wp_cassify_allow_rules = array();
	private $wp_cassify_deny_rules = array();
	
	private $wp_cassify_current_blog_id;
	
	private $wp_cassify_service_ticket_salt;

	private $wp_cassify_cache_times_for_auth_recheck;

	/**
	 * Constructor
	 */
	public function __construct() {
	}
	
	/**
	 * Initialize the plugin with parameters
	 * 
	 * @param 	string 	$wp_cassify_network_activated
	 * @param 	string 	$wp_cassify_default_xpath_query_to_extact_cas_user
	 * @param 	string 	$wp_cassify_default_xpath_query_to_extact_cas_attributes
	 * @param 	string 	$wp_cassify_default_redirect_parameter_name
	 * @param 	string 	$wp_cassify_default_service_ticket_parameter_name
	 * @param 	string 	$wp_cassify_default_service_service_parameter_name
	 * @param 	string 	$wp_cassify_default_gateway_parameter_name
	 * @param 	string 	$wp_cassify_default_bypass_parameter_name
	 * @param 	int 	$wp_cassify_default_cachetimes_for_authrecheck
	 * @param 	string 	$wp_cassify_default_wordpress_blog_http_port
	 * @param 	string 	$wp_cassify_default_wordpress_blog_https_port
	 * @param 	string 	$wp_cassify_default_ssl_check_certificate
	 * @param 	string 	$wp_cassify_default_login_servlet
	 * @param 	string 	$wp_cassify_default_logout_servlet
	 * @param 	string 	$wp_cassify_default_service_validate_servlet
	 * @param 	string 	$wp_cassify_default_allow_deny_order
	 * @param 	string 	$wp_cassify_match_first_level_parenthesis_group_pattern
	 * @param 	string 	$wp_cassify_match_second_level_parenthesis_group_pattern
	 * @param 	string 	$wp_cassify_match_cas_variable_pattern
	 * @param 	string 	$wp_cassify_allowed_operators
	 * @param 	string 	$wp_cassify_operator_prefix
	 * @param 	string 	$wp_cassify_allowed_parenthesis
	 * @param 	array	$wp_cassify_allowed_get_parameters
	 * @param 	array 	$wp_cassify_error_messages	
	 * @param 	array	$wp_cassify_user_error_codes
	 * @param	string	$wp_cassify_service_ticket_salt 
	 */ 
	public function init_parameters(
        $wp_cassify_network_activated,
		$wp_cassify_default_xpath_query_to_extact_cas_user,
		$wp_cassify_default_xpath_query_to_extact_cas_attributes,
		$wp_cassify_default_redirect_parameter_name,
		$wp_cassify_default_service_ticket_parameter_name,
		$wp_cassify_default_service_service_parameter_name,
		$wp_cassify_default_gateway_parameter_name,
		$wp_cassify_default_bypass_parameter_name,
		$wp_cassify_default_cachetimes_for_authrecheck,
		$wp_cassify_default_wordpress_blog_http_port,
		$wp_cassify_default_wordpress_blog_https_port,
		$wp_cassify_default_ssl_check_certificate,
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
		$wp_cassify_allowed_get_parameters,
		$wp_cassify_error_messages,
		$wp_cassify_user_error_codes,
		$wp_cassify_service_ticket_salt
	) {
		$this->wp_cassify_network_activated = $wp_cassify_network_activated;
		$this->wp_cassify_default_xpath_query_to_extact_cas_user = $wp_cassify_default_xpath_query_to_extact_cas_user;
		$this->wp_cassify_default_xpath_query_to_extact_cas_attributes = $wp_cassify_default_xpath_query_to_extact_cas_attributes;
		$this->wp_cassify_default_redirect_parameter_name = $wp_cassify_default_redirect_parameter_name;
		$this->wp_cassify_default_service_ticket_parameter_name = $wp_cassify_default_service_ticket_parameter_name;
		$this->wp_cassify_default_service_service_parameter_name = $wp_cassify_default_service_service_parameter_name;
		$this->wp_cassify_default_gateway_parameter_name = $wp_cassify_default_gateway_parameter_name;
		$this->wp_cassify_default_bypass_parameter_name = $wp_cassify_default_bypass_parameter_name;
		$this->wp_cassify_default_cachetimes_for_authrecheck = $wp_cassify_default_cachetimes_for_authrecheck;
		$this->wp_cassify_default_wordpress_blog_http_port = $wp_cassify_default_wordpress_blog_http_port;
		$this->wp_cassify_default_wordpress_blog_https_port = $wp_cassify_default_wordpress_blog_https_port;
		$this->wp_cassify_default_ssl_check_certificate = $wp_cassify_default_ssl_check_certificate;
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
		$this->wp_cassify_allowed_get_parameters = $wp_cassify_allowed_get_parameters;
		$this->wp_cassify_error_messages	= $wp_cassify_error_messages;
		$this->wp_cassify_user_error_codes = $wp_cassify_user_error_codes;
		$this->wp_cassify_service_ticket_salt = $wp_cassify_service_ticket_salt;
		
		// Check if CAS Authentication must be bypassed.
		if (! $this->wp_cassify_bypass() ) {
			
			// Add the filters
			add_filter( 'query_vars', array( $this , 'add_custom_query_var' ) );
			add_filter( 'login_url', array( $this, 'wp_cassify_clear_reauth' ) );
			add_filter( 'the_content', array( $this, 'wp_cassify_display_message' ) );			
			
			// Start PHP Session.
			add_action( 'wp_loaded', array( $this , 'wp_cassify_session_start' ), 1 ); 

            // Grab service ticket and authenticate user from cas
			add_action( 'wp_loaded', array( $this , 'wp_cassify_grab_service_ticket' ) , 2 );
			
			// Perform SLO (Single Log Out) (Not enabled by default)
            add_action( 'wp_loaded', array ( $this, 'wp_cassify_slo' ), 1 );
            
            // Perform gateway mode : detect if user was already cas authenticated via another app
            // to perform autologin. (Not enabled by default)
            add_action( 'template_redirect', array ( $this, 'wp_cassify_gateway_mode' ), 2 );
            
			// Check if user is loggued in, if not it redirect to CAS Server.
			add_action( 'wp_authenticate', array( $this , 'wp_cassify_redirect' ) , 1 ); 
			
			// Perform logout request
			add_action( 'wp_logout', array( $this , 'wp_cassify_logout' ) , 10 ); 
			
			// Send mails notifications if enabled.
			add_action( 'wp_cassify_send_notification', array( $this, 'wp_cassify_send_notification_message' ), 1, 1 );
		}
		
		// Get current blog id to store information session in $_SESSION['wp_cassify'][$blogid]
		$this->wp_cassify_current_blog_id = get_current_blog_id();
	}
	
	/**
	 * Allow custom get parameters in url
	 * @param 	array 	$vars An array of GET allowed parameters
	 * @return 	array	$vars An array of GET allowed parameters filled with extra parameters
	 */
	public function add_custom_query_var( $vars ){
	
		$vars[] = $this->wp_cassify_default_service_ticket_parameter_name;
		$vars[] = $this->wp_cassify_default_service_service_parameter_name;
		$vars[] = $this->wp_cassify_default_bypass_parameter_name;
		$vars[] = $this->wp_cassify_default_gateway_parameter_name;
	
		foreach ( $this->wp_cassify_allowed_get_parameters as $allowed_get_parameter ) {
			$vars[] = $allowed_get_parameter;
		}
		
		return $vars;
	}	
	
	/**
	 * Display information messages from plugin on front-ofice
	 * 
	 * @param 	string $content	Page content to replace by message to display
	 * @return	string $content	Page content to replace by message to display
	 */ 
	public function wp_cassify_display_message( $content ) {
		
		$wp_cassify_message_parameter = get_query_var( 'wp-cassify-message' );
		
		if (! empty( $wp_cassify_message_parameter ) ) {
			$content = '<h1>'. $this->wp_cassify_user_error_codes[ $wp_cassify_message_parameter ] . '</h1>';
		}
		
		return $content;
	}
	
	/**
	 * Clear reauth parameter from login url to login directly from CAS server.
	 * 
	 * @param	string	$login_url	The wp login url
	 * @return	string	$login_url	The wp login url without reauth parameter.
	 */ 
    public function wp_cassify_clear_reauth( $login_url ) {
        
        $login_url = remove_query_arg( 'reauth', $login_url );
        return $login_url;
    }	
	
	/**
	 * Start the php session inside the plugin because session is needed to store callback url.
	 */	 
	public function wp_cassify_session_start( $force = false ) {
	    
	    $wp_cassify_enable_gateway_mode = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_enable_gateway_mode' );
	    
	    if ( $force || isset( $_COOKIE[ session_name() ] ) || $wp_cassify_enable_gateway_mode ) {
		    if(!session_id() && !wp_doing_ajax() && !wp_doing_cron() && !is_admin() && !headers_sent() && php_sapi_name() !== 'cli' ) {
				session_start();
			}
		}
	}

	/**
	 * Replace current session id with service ticket UID.
	 * to handle SLO requests
	 * 
	 * @param	string	$service_ticket	ST provided by CAS Server in callback URL.
	 * @param	boolean	$restore		if true, attach current session values to new session id.
	 */
    private function wp_cassify_switch_session_id( $service_ticket, $restore = false ) {

        $this->wp_cassify_session_start();
 		// Backup current session vars
        $current_session = $_SESSION;
        
        // Extract service ticket unique ID. Service ticket is structured by default
        // like this : ST-index-XXXXXXX-host
        // For more information see this : 
        // https://apereo.github.io/cas/4.1.x/installation/Configuring-Ticketing-Components.html
        $service_ticket_uid = explode( '-', $service_ticket )[ 2 ];

		// Hash the ticket to ensure that the value meets the PHP 7.1 requirement
		$session_id = hash( 'sha256', $this->wp_cassify_service_ticket_salt . $service_ticket_uid );

		if ( session_id() !== "" ) {
		    session_unset();
		    session_destroy();
		}
		
        session_id( $session_id );
        session_start();
        
        // Restoring current session vars.
        if ( $restore ) {
        	$_SESSION = $current_session;
        }
    }	
	
	/**
	 * This function store CAS User authentication state into session.
	 * @param bool $is_authenticated	If true, CAS User authentication state is set to true. 
	 */
	public function wp_cassify_set_authenticated( $is_authenticated ) {
		
		$_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['user_auth']  = $is_authenticated;
	}
	
	/**
	 * This function informed if user is already authenticated by CAS.
	 */ 
	public function wp_cassify_is_authenticated() {
		
		$is_authenticated = false;

		if ( isset( $_SESSION['wp_cassify'] ) ) {
			if ( array_key_exists('user_auth', $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ] ) ) {
				if ( $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['user_auth'] == true ) {
					$is_authenticated = true;
				}
			}
		}
		
		return $is_authenticated;
	}
	
	/**
	 * This function return cas user attributes populated into php session by plugin.
	 * @return array	$cas_user_datas Associative array containing user attributes and id. Use print_r to expect variable.
	 */ 
	public function wp_cassify_get_cas_user_datas() {
		
		$cas_user_datas = false;
		
		if ( isset( $_SESSION['wp_cassify'] ) ) {
			$cas_user_datas = $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['wp_cassify_cas_user_datas'];
		}
		
		return $cas_user_datas;
	}
	
	/**
	 * Get the times authentication will be cached before really accessing 
	 * the CAS server in gateway mode.
	 * 
	 * @return int $cache_times_for_auth_recheck
	 */ 
	public function wp_cassify_get_cache_times_for_auth_recheck() {
		
		$cachetimes_for_authrecheck = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_cachetimes_for_authrecheck' );
		
		if ( empty( $cachetimes_for_authrecheck ) ) {
			$cachetimes_for_authrecheck = $this->wp_cassify_default_cachetimes_for_authrecheck;
		}
		
		return $cachetimes_for_authrecheck;
	}
	
	/**
	 * Perform a redirection to cas server to obtain service ticket.
	 */ 
	public function wp_cassify_grab_service_ticket() {

		$service_url = null;	
		$service_ticket = null;
		$wordpress_user_account_created = false;
		$current_blog_id = get_current_blog_id();
		
		$wp_cassify_default_service_ticket_parameter_name = $this->wp_cassify_default_service_ticket_parameter_name;
		$wp_cassify_default_service_service_parameter_name = $this->wp_cassify_default_service_service_parameter_name;
		
		$service_url = $this->wp_cassify_get_service_callback_url();
		$service_ticket = $this->wp_cassify_get_service_ticket();	

		$gateway_mode = $this->wp_cassify_is_gateway_request( null );

		if ( (! is_user_logged_in() ) || (! is_user_member_of_blog() ) ) {		
			if (! empty( $service_ticket ) ) {
				// Ensure session is started
				$this->wp_cassify_session_start( true );

				// Retrieve configuration options from database
				$wp_cassify_base_url = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_base_url' );
				$wp_cassify_create_user_if_not_exist = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_create_user_if_not_exist' );
				$wp_cassify_ssl_cipher =  WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_ssl_cipher' );
				$wp_cassify_ssl_check_certificate =  WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_ssl_check_certificate' );
				$wp_cassify_attributes_list = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_attributes_list' );	
				$wp_cassify_login_servlet = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_login_servlet' );
				$wp_cassify_logout_servlet = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_logout_servlet' );
				$wp_cassify_service_validate_servlet =  WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_service_validate_servlet' );
				$wp_cassify_allow_deny_order = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_allow_deny_order' );
				$wp_cassify_autorization_rules = unserialize( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_autorization_rules' ) );		
		        $wp_cassify_user_role_rules = unserialize( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_user_role_rules' ) );
		        $wp_cassify_user_purge_user_roles_before_applying_rules = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_user_purge_user_roles_before_applying_rules' );
		        $wp_cassify_user_attributes_mapping_list = unserialize( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_user_attributes_mapping_list' ) );
		 		$wp_cassify_notification_rules = unserialize( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notification_rules' ) );
		 		$wp_cassify_expiration_rules = unserialize( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_expiration_rules' ) );		
		 		$wp_cassify_log_out_on_errors = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_log_out_on_errors' );
		 		$wp_cassify_enable_slo = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_enable_slo' );

		 				
				// Set defaults values if options are not set.
				$wp_cassify_login_servlet = ( empty( $wp_cassify_login_servlet ) ) ? $this->wp_cassify_default_login_servlet : $wp_cassify_login_servlet;
				$wp_cassify_logout_servlet = ( empty( $wp_cassify_logout_servlet ) ) ? $this->wp_cassify_default_logout_servlet : $wp_cassify_logout_servlet;
				$wp_cassify_service_validate_servlet = ( empty( $wp_cassify_service_validate_servlet ) ) ? $this->wp_cassify_default_service_validate_servlet : $wp_cassify_service_validate_servlet;
				$wp_cassify_ssl_cipher_selected = (! empty( $wp_cassify_ssl_cipher ) ) ? $wp_cassify_ssl_cipher : '0';
				$wp_cassify_ssl_check_certificate = ( empty( $wp_cassify_ssl_check_certificate ) ) ? $this->wp_cassify_default_ssl_check_certificate : $wp_cassify_ssl_check_certificate;
				$wp_cassify_allow_deny_order = ( empty( $wp_cassify_allow_deny_order ) ) ? $this->wp_cassify_default_allow_deny_order : $wp_cassify_allow_deny_order;
		
				if ( ( is_array( $wp_cassify_autorization_rules ) ) && ( count( $wp_cassify_autorization_rules ) > 0 ) ) {
					foreach ( $wp_cassify_autorization_rules as $rule_key => $rule_value ) {
						$wp_cassify_autorization_rules[ $rule_key ] = stripslashes( $rule_value );  
					}
				}
				else {
					$wp_cassify_autorization_rules = array();
				}						
				
				// If SLO enabled, replace session_id with service ticket to handle logout requests.
				if ( $wp_cassify_enable_slo == 'enable_slo') {	
					$this->wp_cassify_switch_session_id( $service_ticket, true );
				}
				
				$service_validate_url = $wp_cassify_base_url .
                    $wp_cassify_service_validate_servlet . '?' .
                    $wp_cassify_default_service_ticket_parameter_name . '=' . $service_ticket . '&' .
                    $wp_cassify_default_service_service_parameter_name .'=' . urlencode( $service_url );					

				// Define custom plugin filter to override service validate url
				if( has_filter( 'wp_cassify_override_service_validate_url' ) ) {
					$service_validate_url = apply_filters( 
						'wp_cassify_override_service_validate_url',
						$service_validate_url,
						$service_ticket,
						$service_url,						
						$wp_cassify_base_url, 
						$wp_cassify_service_validate_servlet,
						$wp_cassify_default_service_ticket_parameter_name,
						$wp_cassify_default_service_service_parameter_name
					);
				}

				$cas_server_xml_response = WP_Cassify_Utils::wp_cassify_do_ssl_web_request( 
					$service_validate_url, 
					$wp_cassify_ssl_cipher_selected,
					$wp_cassify_ssl_check_certificate,
					esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_curlopt_cainfo' ) ),
					esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_curlopt_capath' ) )
				);
				
				// Dump xml cas server response if debug option is enabled.	
				if ( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_xml_response_dump' ) == 'enabled' ) {
			        if ( $this->wp_cassify_network_activated ) {
			            update_site_option( 'wp_cassify_xml_response_value' , $cas_server_xml_response );
			        }
			        else {
			            update_option( 'wp_cassify_xml_response_value' , $cas_server_xml_response );
			        }
				}

				// Parse CAS Server response and store into associative array.
				$cas_user_datas = $this->wp_cassify_parse_xml_response( $cas_server_xml_response );

				if ( empty( $cas_user_datas['cas_user_id'] ) ) {
					if ( $wp_cassify_log_out_on_errors == 'log_out_on_errors' )
						$this->wp_cassify_logout();
						
					die( 'CAS Authentication failed 2 ! ' . $cas_server_xml_response );
				}
				else {
					$this->wp_cassify_set_authenticated( true );
				}
				
				// Define custom plugin filter to build your custom parsing function
				if( has_filter( 'wp_cassify_custom_parsing_cas_xml_response' ) ) {
					$cas_user_datas = apply_filters( 'wp_cassify_custom_parsing_cas_xml_response', $cas_server_xml_response, $cas_user_datas );
				}

				// Evaluate authorization rules
				if ( ( is_array( $wp_cassify_autorization_rules ) ) &&  ( count( $wp_cassify_autorization_rules ) > 0 ) ) {
					$this->wp_cassify_separate_rules( $wp_cassify_autorization_rules );
					
					// Force logout if user is not allowed.
					if (! $this->wp_cassify_is_user_allowed( $wp_cassify_allow_deny_order, $cas_user_datas ) ) {
						$this->wp_cassify_logout_if_not_allowed( 'user_is_not_allowed' );
					}	
				}
				
				// Evaluate expiration rules
				if ( ( is_array( $wp_cassify_expiration_rules ) ) &&  ( count( $wp_cassify_expiration_rules ) > 0 ) ) {					
					if ( $this->wp_cassify_is_user_account_expired( $cas_user_datas, $wp_cassify_expiration_rules ) ) {
						
						$notification_rule_matched = $this->wp_cassify_notification_rule_matched( 
						    'when_user_account_expire',
							$cas_user_datas, 
							$wp_cassify_notification_rules
						);
						
						if ( $notification_rule_matched ) {
							// Define custom plugin hook to send notification after user account has expired.
							do_action( 'wp_cassify_send_notification', 'User ' . $cas_user_datas[ 'cas_user_id' ] . ' : user account has expired.' );								
						}						
						
						// Force logout if user account has expired.
						$this->wp_cassify_logout_if_not_allowed( 'user_account_expired' );
					}
				}				
				
				// Define custom plugin hook after cas authentication. 
				// For example, for two factor authentication, you can plug another authentication plugin to fired custom action here.			
				do_action( 'wp_cassify_after_cas_authentication', $cas_user_datas );

				// Populate selected attributes into session
				$this->wp_cassify_populate_attributes_into_session( $cas_user_datas, $wp_cassify_attributes_list );

				// Create wordpress user account if not exist
				if ( $wp_cassify_create_user_if_not_exist == 'create_user_if_not_exist' ) {
					if ( WP_Cassify_Utils::wp_cassify_is_wordpress_user_exist( $cas_user_datas[ 'cas_user_id' ] ) == false ) {
						error_log('WP CASSIFY CREATE USER....');
						
						$wordpress_user_id = WP_Cassify_Utils::wp_cassify_create_wordpress_user( $cas_user_datas[ 'cas_user_id' ], null );

						if ( $wordpress_user_id > 0 ) {
							$wordpress_user_account_created = true;
							
							$notification_rule_matched = $this->wp_cassify_notification_rule_matched(
                                'after_user_account_created',
								$cas_user_datas, 
								$wp_cassify_notification_rules
							);
							
							if ( $notification_rule_matched ) {
								// Define custom plugin hook to send notification after user account has been created.
								do_action( 'wp_cassify_send_notification', 'User account has been created :' . $cas_user_datas[ 'cas_user_id' ] );							
							}
						}
					}
				}
				
				// Purge wordpress user roles before applying user role rules
				if ( $wp_cassify_user_purge_user_roles_before_applying_rules ) {
					WP_Cassify_Utils::wp_cassify_clear_roles_to_wordpress_user( $cas_user_datas[ 'cas_user_id' ] );
				}
				
				// Set wordpress user roles if defined in plugin admin settings
				$roles_to_push = $this->wp_cassify_get_roles_to_push( $current_blog_id, $cas_user_datas, $wp_cassify_user_role_rules, $this->wp_cassify_network_activated );
                
				// Define custom plugin filter to override list roles to push.
				if( has_filter( 'wp_cassify_grab_service_ticket_roles_to_push' ) ) {
					$roles_to_push = apply_filters( 'wp_cassify_grab_service_ticket_roles_to_push', $roles_to_push, $cas_user_datas );
				}                
                
				foreach ( $roles_to_push as $role ) {
					WP_Cassify_Utils::wp_cassify_add_role_to_wordpress_user( $cas_user_datas[ 'cas_user_id' ], $role );		
				}

				// Sync CAS User attributes with Wordpress User meta
				$this->wp_cassify_sync_user_metadata( 
					$cas_user_datas[ 'cas_user_id' ], 
					$cas_user_datas, 
					$wp_cassify_user_attributes_mapping_list
				);
				
				// Custom hook to perform action before wordpress authentication.
				do_action( 'wp_cassify_before_auth_user_wordpress', $cas_user_datas );
				
				// Auth user into wordpress
				WP_Cassify_Utils::wp_cassify_auth_user_wordpress( $cas_user_datas[ 'cas_user_id' ] );

				// Custom hook to perform action after wordpress authentication.
				do_action( 'wp_cassify_after_auth_user_wordpress', $cas_user_datas );

				$notification_rule_matched = $this->wp_cassify_notification_rule_matched(
					'after_user_login',                
					$cas_user_datas, 
					$wp_cassify_notification_rules
				);
				
				if ( $notification_rule_matched ) {
					// Define custom plugin hook to send notification after user has been logged in
					do_action( 'wp_cassify_send_notification', 'User ' . $cas_user_datas[ 'cas_user_id' ] . ' is logged in' );							
				}

				// Redirect to the service url.
				WP_Cassify_Utils::wp_cassify_redirect_url( $service_url );
			}
		}
	}		
	
	/**
	 * Perform a redirection to cas server to obtain service ticket.
	 * @param	bool	$gateway_mode	Make gateway request. See CAS documentation here : https://wiki.jasig.org/display/CAS/gateway.
	 */ 
	public function wp_cassify_redirect( $gateway_mode = false ) {
		
		do_action( 'wp_cassify_before_redirect' );

		$service_url = null;	
		$service_ticket = null;
			
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
		
		$service_url = $this->wp_cassify_get_service_callback_url();
		$service_ticket = $this->wp_cassify_get_service_ticket();
		
		$current_user = wp_get_current_user();
		
		if ( ( (! is_user_logged_in() ) && (! empty( $wp_cassify_base_url ) ) ) || ( $gateway_mode == TRUE ) )  {	
			if (! $this->wp_cassify_is_in_while_list( $service_url ) ) {	
				if ( empty( $service_url ) ) {
					if ( $wp_cassify_log_out_on_errors == 'log_out_on_errors' )
						$this->wp_cassify_logout();
					die( 'CAS Service URL not set !' );
				}	
				elseif ( empty( $service_ticket ) ) {	

					if ( parse_url( $service_url, PHP_URL_QUERY) ) {
						$service_url = WP_Cassify_Utils::wp_cassify_encode_query_in_url( $service_url );
					}					
					
					// Test if service url must be overriden by plugin options.
					$wp_cassify_override_service_url = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_override_service_url' );
					
					if (! empty( $wp_cassify_override_service_url ) ) {
						if ( strpos( $wp_cassify_override_service_url, "{WP_CASSIFY_CURRENT_SERVICE_URL}" ) !== FALSE ) {
							$wp_cassify_override_service_url = str_replace( "{WP_CASSIFY_CURRENT_SERVICE_URL}" , $service_url, $wp_cassify_override_service_url );
						}

						$service_url = $wp_cassify_override_service_url;
					}
					
					// Define custom plugin filter to build service url with your own value.
					if( has_filter( 'wp_cassify_redirect_service_url_filter' ) ) {
						$service_url = apply_filters( 'wp_cassify_redirect_service_url_filter', $service_url );
					}
					
					$redirect_url = $wp_cassify_base_url .
						$wp_cassify_login_servlet . '?' .
						$this->wp_cassify_default_service_service_parameter_name . '=' . $service_url;
						
					if ( $gateway_mode ) {
						$redirect_url .= '&gateway=true';
					}

					WP_Cassify_Utils::wp_cassify_redirect_url( $redirect_url );	
				}
			}
		}

		do_action( 'wp_cassify_after_redirect', $current_user->user_login );
	}	
	
	/**
	 * Logout from CAS and Wordpress
	 */ 
	function wp_cassify_logout() {
		
		$wp_cassify_logout_servlet = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_logout_servlet' );
		$wp_cassify_base_url = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_base_url' );
		$wp_cassify_redirect_url_after_logout = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_after_logout' );

		$current_url = WP_Cassify_Utils::wp_cassify_get_current_url( $this->wp_cassify_default_wordpress_blog_http_port, $this->wp_cassify_default_wordpress_blog_https_port );
		$redirect_to = WP_Cassify_Utils::wp_cassify_extract_get_parameter( $current_url , $this->wp_cassify_default_redirect_parameter_name );
		
		// Detect if user has been authenticated using CAS.
		$authenticated_by_cas = $this->wp_cassify_is_authenticated();
		
		do_action( 'wp_cassify_logout_before', $current_url, $redirect_to, $authenticated_by_cas );
		
		// Define default values if url parameters or options values empty.
		if ( empty( $wp_cassify_logout_servlet ) ) {
			$wp_cassify_logout_servlet = $this->wp_cassify_default_logout_servlet;
		}

		if (! empty( $redirect_to ) ) {
			$wp_cassify_redirect_url_after_logout = $redirect_to;
		}	
		elseif ( empty ( $wp_cassify_redirect_url_after_logout ) ) {
			$wp_cassify_redirect_url_after_logout = get_home_url();
		}

		// Send logout notification if rule is matched.	
		if ( isset(	$_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['wp_cassify_cas_user_datas'] ) ) {

			$cas_user_datas = $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['wp_cassify_cas_user_datas'];
			$wp_cassify_notification_rules = unserialize( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notification_rules' ) );
 		
			$notification_rule_matched = $this->wp_cassify_notification_rule_matched( 
			    'after_user_logout',
				$cas_user_datas, 
				$wp_cassify_notification_rules, 
			);
		
			if ( $notification_rule_matched ) {
				do_action( 'wp_cassify_send_notification', 'User account has been logged out :' . $cas_user_datas[ 'cas_user_id' ] );							
			}			
		}

		// Destroy wordpress session;
		session_destroy();

		if ( $authenticated_by_cas ) {
			// Redirect to the logout CAS end point.
			$redirect_url = $wp_cassify_base_url .
				$wp_cassify_logout_servlet . '?' .
				$this->wp_cassify_default_service_service_parameter_name . '=' . urlencode( $wp_cassify_redirect_url_after_logout );
		}
		else {
			// If user not authenticated by CAS redirect to home_url.
			$redirect_url = home_url();
		}
		
		WP_Cassify_Utils::wp_cassify_redirect_url( $redirect_url );
	}

    /**
     * Enable support for central logout (Single Sign Out).
     */
    public function wp_cassify_slo() {

        $wp_cassify_enable_slo = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_enable_slo' );
        $wp_cassify_base_url = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_base_url' );
        
        if ( $wp_cassify_enable_slo == 'enable_slo' ) {
            if ( !empty( $_POST['logoutRequest'] ) ) {
            	
                $decoded_logout_rq = urldecode( $_POST['logoutRequest'] );

                if ( true ) {
                    preg_match(
                    	"|<samlp:SessionIndex>(.*)</samlp:SessionIndex>|",
                    	$decoded_logout_rq, $tick, PREG_OFFSET_CAPTURE, 3
                    );
                    
                    $wrappedSamlSessionIndex = preg_replace( '|<samlp:SessionIndex>|', '', $tick[0][0] );
                    
                    $ticket2logout = preg_replace( '|</samlp:SessionIndex>|', '', $wrappedSamlSessionIndex );
                    $ticket2logout = preg_replace('/[^a-zA-Z0-9\-]/', '', $ticket2logout);
                    
	                // Switch session ID with Service Ticket ID
	                $this->wp_cassify_switch_session_id( $ticket2logout );

	                $cas_user_datas = $this->wp_cassify_get_cas_user_datas();

	                // Kill WP user session
	                if ( $cas_user_datas !== false ) {
	                	$wp_current_user = get_user_by( 'login', $cas_user_datas[ 'cas_user_id' ] );
	                	if ( $wp_current_user ) {
							wp_set_current_user ( $wp_current_user->ID );
							wp_destroy_all_sessions();	
	                	}
	                }
	                
	                // Overwrite current session
	                session_unset();
	                session_destroy();
	                
	                do_action( 'wp_cassify_slo_after' );
	                
                    exit();
                }
            }
        }
    }

	/**
	 *  Get the service ticket from cas server request.
	 */ 
	public function wp_cassify_get_service_ticket() {
				
		$wp_cassify_service_ticket = get_query_var( $this->wp_cassify_default_service_ticket_parameter_name );
		$current_url = WP_Cassify_Utils::wp_cassify_get_current_url(
			$this->wp_cassify_default_wordpress_blog_http_port,
			$this->wp_cassify_default_wordpress_blog_https_port	
		);
		
		if ( empty( $wp_cassify_service_ticket ) ) {
			$wp_cassify_service_ticket = WP_Cassify_Utils::wp_cassify_extract_get_parameter( 
				rawurldecode( $current_url ), 
				$this->wp_cassify_default_service_ticket_parameter_name );
		}

		return $wp_cassify_service_ticket;
	}


	/**
	 * Enable support for auto-login (Gateway Mode).
	 */
	public function wp_cassify_gateway_mode() {
		
		$wp_cassify_enable_gateway_mode = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_enable_gateway_mode' );
		
		if ( $wp_cassify_enable_gateway_mode ) {
		    if ( ( (! is_user_logged_in() ) || (! is_user_member_of_blog() ) ) && ( $_SESSION['wp_cassify'] ?? null && $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['user_auth'] ) && (! get_query_var( 'wp_cassify_bypass' ) ) ) {
		        if ( isset($GLOBALS['wp-cassify']) ) {
		            $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['user_auth'] = false;
		            $GLOBALS['wp-cassify']->wp_cassify_check_authentication();
		        }
		    }
		    else if ( (! is_user_logged_in() ) && (! get_query_var( 'wp_cassify_bypass' ) ) ){  
		        if ( isset($GLOBALS['wp-cassify']) ) {
		            $GLOBALS['wp-cassify']->wp_cassify_check_authentication();
		        }
		    }
		    else if ( ! is_user_member_of_blog() ) {
		        if ( isset($GLOBALS['wp-cassify']) ) {
		            $GLOBALS['wp-cassify']->wp_cassify_check_authentication();
		        }   
		    }
		}
	}
	
	/**
	 * Function used to detect if user has previously been authenticated by CAS.
	 * Performs redirection to cas server programmactically.
	 * This function is used throught hook wp_cassify_check_authentication.
	 * 
	 * @result	bool	$auth			Return true if user has already authenticated by CAS Server.
	 */ 
	public function wp_cassify_check_authentication() {

		$auth = false;
		
		if ( $this->wp_cassify_is_authenticated() ) {	
			$auth = true;
		}
		else if ( isset( $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['auth_checked'] ) ) {
        	// the previous request has redirected the client to the CAS server with gateway=true				
			unset( $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['auth_checked'] );
			
			$auth = false;
		}
		else {
			// avoid a check against CAS on every request
            if ( !isset( $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['unauth_count'] ) ) {
                $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['unauth_count'] = -2;
            }				
			
            if ( ( ( $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['unauth_count'] != -2 ) && ( $this->wp_cassify_get_cache_times_for_auth_recheck() == -1 ) ) || 
            	 ( $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['unauth_count'] >= 0  && $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['unauth_count'] < $this->wp_cassify_cache_times_for_auth_recheck )
            ) {
				$auth = false;

                if ( $this->wp_cassify_cache_times_for_auth_recheck != -1 ) {
                    $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['unauth_count']++;;
                }
            }
			else {
                $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['unauth_count'] = 0;
                $_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['auth_checked'] = true;
                
				$this->wp_cassify_redirect( true );	                
                
                // never reached
                $auth = false;
            }	    
		}

		return $auth;
	}

	/**
	 * Populate cas user_id and selected attributes from CAS into session.
	 * @param array $cas_user_datas
	 * @param array $wp_cassify_attributes_list
	 */ 
	public function wp_cassify_populate_attributes_into_session( $cas_user_datas, $wp_cassify_attributes_list ) {

		$cas_user_datas_filtered = array();
		
		// cas_user_id is populated by default.
		$cas_user_datas_filtered[ 'cas_user_id' ] = $cas_user_datas[ 'cas_user_id' ];

		if (! empty( $wp_cassify_attributes_list ) ) {
			$cas_user_attributes_names = explode( ',', $wp_cassify_attributes_list );

			if ( is_array( $cas_user_attributes_names ) ) {
				foreach( $cas_user_attributes_names as $cas_user_attributes_name ) {
					$cas_user_datas_filtered[ $cas_user_attributes_name ] = $cas_user_datas[ $cas_user_attributes_name ];
				}
			}
		}
		
		$_SESSION['wp_cassify'][ $this->wp_cassify_current_blog_id ]['wp_cassify_cas_user_datas'] = $cas_user_datas_filtered;
	}
	
	/**
	 *  Get the service callback url from php session.
	 *  @return string
	 */ 
	public function wp_cassify_get_service_callback_url() {
		
		$wp_cassify_callback_service_url = WP_Cassify_Utils::wp_cassify_get_current_url(
				$this->wp_cassify_default_wordpress_blog_http_port,
				$this->wp_cassify_default_wordpress_blog_https_port	);

		// Check if request use gateway mode.
		$gateway_mode = false;
		
		if ( $this->wp_cassify_is_gateway_request( $wp_cassify_callback_service_url ) ) {
			$gateway_mode = true;
		}
		
		$wp_cassify_redirect_parameter = WP_Cassify_Utils::wp_cassify_extract_get_parameter( $wp_cassify_callback_service_url , $this->wp_cassify_default_redirect_parameter_name );
		$wp_cassify_service_ticket_parameter = WP_Cassify_Utils::wp_cassify_extract_get_parameter( $wp_cassify_callback_service_url , $this->wp_cassify_default_service_ticket_parameter_name );
		
		if ( !empty( $wp_cassify_redirect_parameter ) ) {
			$wp_cassify_callback_service_url = WP_Cassify_Utils::wp_cassify_extract_get_parameter( 
				$wp_cassify_callback_service_url, 
				$this->wp_cassify_default_redirect_parameter_name 
			);

			// Append home_url if url contains only /my-slug-page/. callback service url must be fully qualified.
			if ( strrpos( $wp_cassify_callback_service_url, '/', -strlen( $wp_cassify_callback_service_url ) ) !== false ) {
				$wp_cassify_callback_service_url = WP_Cassify_Utils::wp_cassify_get_host_uri( home_url() ) . 
					$wp_cassify_callback_service_url;
			}
		}
		else if ( !empty( $wp_cassify_service_ticket_parameter ) ) {
			$wp_cassify_callback_service_url = WP_Cassify_Utils::wp_cassify_strip_get_parameter( $wp_cassify_callback_service_url , $this->wp_cassify_default_service_ticket_parameter_name );
			
			// Append home_url if url contains only /my-slug-page/. callback service url must be fully qualified.
			if ( strrpos( $wp_cassify_callback_service_url, '/', -strlen( $wp_cassify_callback_service_url ) ) !== false ) {
				$wp_cassify_callback_service_url = WP_Cassify_Utils::wp_cassify_get_host_uri( home_url() ) .
					$wp_cassify_callback_service_url;
			}
		}
		else {
			// $wp_cassify_callback_service_url = home_url() . '/';
			// Do nothing
		}
		
		if ( $gateway_mode ) {
			$wp_cassify_callback_service_url .= '%3Fgateway=true';
		}
		
		// Fix bug : slug is duplicate in return url
		// if( is_multisite() ) {
		// 	$blog_details = get_blog_details();
		// 	$blog_path = ltrim( $blog_details->path , '/');
		// 	$duplicate_path_to_remove = rtrim( $blog_path . $blog_path, '/' );
		// 	$duplicate_path_to_replace = rtrim( $blog_path, '/' );		
			
		// 	if ( ! empty( $duplicate_path_to_remove ) && strpos( $wp_cassify_callback_service_url, $duplicate_path_to_remove ) !== false ) {
		// 		$wp_cassify_callback_service_url = str_replace( $duplicate_path_to_remove, $duplicate_path_to_replace , $wp_cassify_callback_service_url );
		// 	}
		// }
		// End Fix bug

		return $wp_cassify_callback_service_url;
	}
	
	/**
	 * Logout from CAS and Wordpress
	 * @param string $wp_cassify_error_code	Error code passing by GET to display custom error messages after logout.	
	 */ 
	private function wp_cassify_logout_if_not_allowed( $wp_cassify_error_code = '' ) {

		$wp_cassify_logout_servlet = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_logout_servlet' );
		$wp_cassify_base_url = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_base_url' );
		$wp_cassify_redirect_url_if_not_allowed = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_if_not_allowed' );
		
		// Define default values if options values empty.
		if ( empty( $wp_cassify_logout_servlet ) ) {
			$wp_cassify_logout_servlet = $this->wp_cassify_default_logout_servlet;
		}
		
		if ( empty ( $wp_cassify_redirect_url_if_not_allowed ) ) {
			$wp_cassify_redirect_url_if_not_allowed = get_home_url();
			
			if (! empty( $wp_cassify_error_code ) )	{
				$wp_cassify_redirect_url_if_not_allowed .= '%3F' . 'wp-cassify-message=' . $wp_cassify_error_code;
			}			
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
	 * @param string $cas_server_xml_response	Xml response stream sent by CAS Server.
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
				
				// Process multivaluate fields. Test if array_key already exist.
				if ( array_key_exists( $cas_attribute_name, $cas_user_datas ) ) {
					if ( isset( $cas_user_datas[ $cas_attribute_name ] ) ) {
						if ( is_array( $cas_user_datas[ $cas_attribute_name ] ) ) {
							array_push( $cas_user_datas[ $cas_attribute_name ], $cas_user_attributes_item->nodeValue );
						}
						else {
							$cas_attribute_value = $cas_user_datas[ $cas_attribute_name ];
							$cas_user_datas[ $cas_attribute_name ] = array();
							
							array_push( $cas_user_datas[ $cas_attribute_name ], $cas_attribute_value );
							array_push( $cas_user_datas[ $cas_attribute_name ], $cas_user_attributes_item->nodeValue );
						}
					}
				}
				else {
					// Process single valuate field.
					$cas_user_datas[ $cas_attribute_name ] = $cas_user_attributes_item->nodeValue;
				}
			}
		}

		return $cas_user_datas;
	}

	/**
	 * Check if request use gateway mode.
	 * s
	 * @param 	string	$callback_service_url	Url used by CAS server to return to service.
	 * @return 	bool	$is_gateway_request		Return true if request to CAS Server is made in gateway mode (eg : ?gateway=true)	
	 */ 
	private function wp_cassify_is_gateway_request( $callback_service_url ) {
		
		$is_gateway_request = false;
		 
		// Test current url if callback_service_url is not set.
		if ( empty( $callback_service_url ) ) {
			$callback_service_url = WP_Cassify_Utils::wp_cassify_get_current_url(
				$this->wp_cassify_default_wordpress_blog_http_port,
				$this->wp_cassify_default_wordpress_blog_https_port	
			);			
		}
		
		$gateway = WP_Cassify_Utils::wp_cassify_extract_get_parameter( 
				rawurldecode( $callback_service_url ), 
				$this->wp_cassify_default_gateway_parameter_name );
		
		if (! empty( $gateway ) ) {
			$is_gateway_request = true;
		}
		
		return $is_gateway_request;
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
	 	
	 	$wp_cassify_bypass = false;
	 	
	 	$wp_cassify_bypass_by_referrer = '';
	 	$wp_cassify_bypass_by_post = '';
	 	$wp_cassify_bypass_by_get = '';
	 	
	 	$wp_cassify_disable_authentication = '';
	 	
	 	// 1- Check if bypass GET URL parameter is set from the Referrer.
 		if (! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$wp_cassify_bypass_by_referrer = WP_Cassify_Utils::wp_cassify_extract_get_parameter( $_SERVER['HTTP_REFERER'], $this->wp_cassify_default_bypass_parameter_name );
 		}
		
		// 2- Check if bypass parameter is send by POST
		if (! empty( $_POST['redirect_to'] ) ) {
			$wp_cassify_bypass_by_post = WP_Cassify_Utils::wp_cassify_extract_get_parameter( $_POST['redirect_to'], $this->wp_cassify_default_bypass_parameter_name );
		}

		// 3- Or check if bypass has been defined in admin panel.
		$wp_cassify_disable_authentication = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_disable_authentication' );
		
		// 4- Check $_GET['wp_cassify_bypass'] value
		if ( isset( $_GET[ $this->wp_cassify_default_bypass_parameter_name ] ) ) {
			// Can't use get_query_var function because 'query_vars' filter has not yet fired. I use $_GET instead. 
			$wp_cassify_bypass_by_get = $_GET[ $this->wp_cassify_default_bypass_parameter_name ];
		}

		if ( ( $wp_cassify_bypass_by_referrer == 'bypass' ) || ( $wp_cassify_bypass_by_post == 'bypass' ) || ( $wp_cassify_bypass_by_get == 'bypass' ) || ( $wp_cassify_disable_authentication == 'disabled' ) ) {
			$wp_cassify_bypass = true;
		}

		return $wp_cassify_bypass;
	 }
	
	/**
	 * Check if url is in white list and don't be authenticated by CAS.
	 * @param 	string 	$url				Url of to test		
	 * @return 	bool 	$is_in_while_list	Return true if user can acess this url without to be authenticated by CAS.
	 */
	 private function wp_cassify_is_in_while_list( $url ) {
		 
		 $is_in_while_list = false;
		 
		 $wp_cassify_redirect_url_white_list = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_white_list' );
		 $white_list_urls = explode( ';', $wp_cassify_redirect_url_white_list );
		 
		 if ( ( is_array( $white_list_urls ) ) && ( count( $white_list_urls ) > 0 ) ){
			foreach( $white_list_urls as $white_url ) {
				if ( strlen($white_url)>0 && strrpos( $url, $white_url, strlen( site_url() ) ) !== false ) {
					$is_in_while_list = true;
				}
			} 
		 }
		 
		 return $is_in_while_list;
	 } 

	/**
	 * Check if user is allow to connect according to autorization rules.
	 * 
	 * @param string 	$wp_cassify_allow_deny_order	Order to process authorization rules.
	 * @param array 	$cas_user_datas					Associative array containing CAS userID and attributes
	 * 
	 * @return bool 	$is_user_allowed				Return true if user is allowed to connect. Return false on the other hand.
	 */ 
	private function wp_cassify_is_user_allowed( $wp_cassify_allow_deny_order, $cas_user_datas = array() ) {

		$is_user_allowed = false;
		$rule_check = false;
		
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
							$is_user_allowed = true;
						}
					}
				}
			}
			
			if ( ( is_array( $this->wp_cassify_deny_rules ) ) && ( count( $this->wp_cassify_deny_rules ) > 0 ) ) {
				foreach ( $this->wp_cassify_deny_rules as $rule ) {
					if (! $rule_check ) {
						$rule_check = $solver->solve( $rule );
						
						if ( $rule_check ) {
							$is_user_allowed = false;
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
							$is_user_allowed = false;
						}						
					}
				}
			}
			
			if ( ( is_array( $this->wp_cassify_allow_rules ) ) && ( count( $this->wp_cassify_allow_rules ) > 0 ) ) {
				foreach ( $this->wp_cassify_allow_rules as $rule ) {
					if (! $rule_check ) {
						$rule_check = $solver->solve( $rule );
						
						if ( $rule_check ) {
							$is_user_allowed = true;
						}							
					}
				}
			}	
		}

		return $is_user_allowed;
	}
	
	/**
	 * Check if user is matched by Conditionnal Rule
	 * 
	 * @param 	string 	$wp_cassify_rule	WP Cassify rule
	 * @param 	array 	$cas_user_datas		Associative array containing CAS userID and attributes
	 * 
	 * @return 	bool	$rule_matched		Return true if WP Cassify rule assertion is verified. Return false on the other hand.
	 */ 
	private function wp_cassify_rule_matched( $wp_cassify_rule, $cas_user_datas = array() ) {

		$rule_matched = false;

		$solver = new \wp_cassify\wp_cassify_rule_solver();

		$solver->match_first_level_parenthesis_group_pattern = $this->wp_cassify_match_first_level_parenthesis_group_pattern;
		$solver->match_second_level_parenthesis_group_pattern = $this->wp_cassify_match_second_level_parenthesis_group_pattern;
		$solver->match_cas_variable_pattern = $this->wp_cassify_match_cas_variable_pattern;
		$solver->allowed_operators = $this->wp_cassify_allowed_operators;
		$solver->operator_prefix = $this->wp_cassify_operator_prefix;
		$solver->allowed_parenthesis = $this->wp_cassify_allowed_parenthesis;
		$solver->error_messages = $this->wp_cassify_error_messages;
		$solver->cas_user_datas = $cas_user_datas;

		$rule_matched = $solver->solve( $wp_cassify_rule );

		return $rule_matched;
	}

	/**
	 * Test if user account has expired.
	 * @param 	array 	$cas_user_datas							Associative array containing CAS userID and attributes
	 * @param	array	$expiration_rules			Array of WP Cassify user account expiration rules
	 * @result	bool	$is_user_account_expired		Return true if user account has expired. Return false on the other hand.	
	 */ 
	private function wp_cassify_is_user_account_expired( $cas_user_datas, $expiration_rules = array() ) {
		
		$is_user_account_expired = false;
		
		foreach ( $expiration_rules as $expiration_rule ) {
		
			$expiration_rule_parts = explode( '|', $expiration_rule );
			
			if ( ( is_array( $expiration_rule_parts ) ) && ( count( $expiration_rule_parts ) == 3 ) ) {
				$expiration_rule_type = $expiration_rule_parts[0];
				$expiration_rule_type_value = $expiration_rule_parts[1];
				$expiration_rule_value = stripslashes( $expiration_rule_parts[2] );
				
				if ( $this->wp_cassify_rule_matched( $expiration_rule_value, $cas_user_datas ) ) {
					
					switch( $expiration_rule_type )	{
						case 'after_user_account_created_time_limit' :
							$current_user = get_user_by( 'login', $cas_user_datas[ 'cas_user_id' ] );
							$expiration_date = new \DateTime( $current_user->user_registered );

							// Add expiration delay (in days) from user account registered date
							$expiration_date->add( new \DateInterval( 'P'. $expiration_rule_type_value . 'D' ) );
							break;
							
						case 'fixed_datetime_limit' :
							$expiration_date = new \DateTime( $expiration_rule_type_value );
							break;
					}
					
					$now = new \DateTime( 'now' );
					
					if ( $expiration_date < $now ) {
						$is_user_account_expired = true;	
					}
				}
			}
		}
		
		return $is_user_account_expired;		
	}

	/**
	 * Check if user is matched by Notification Rule
	 * 
	 * @param int		$current_blog_id	The id of the current blog
	 * @param array 	$cas_user_datas		Associative array containing CAS userID and attributes
	 * @param array 	$role_rules			Array containing all role rules
	 * @param bool		$network_activated	True if plugin is activated over the network
	 * 
	 * @return array 	$roles_to_push		Array containing roles to push to user
	 */ 	
	private function wp_cassify_get_roles_to_push( $current_blog_id, $cas_user_datas = array(), $role_rules = array(), $network_activated = false ) {
		
		$roles_to_push = array();

		if ( ( is_array( $role_rules ) ) && ( count( $role_rules ) > 0 ) ) {
			foreach ( $role_rules as $role_rule ) {
				$role_rule_parts = explode( '|', $role_rule );
				
				if ( $network_activated ) {
					if ( ( is_array( $role_rule_parts ) ) && ( count( $role_rule_parts ) == 3 ) ) {
						$role_rule_key = $role_rule_parts[0];
						// Determine scope of the rule if network activated
						$role_rule_blog_id = $role_rule_parts[1]; 
						$role_rule_expression = stripslashes( $role_rule_parts[2] );
						
						// role_rule_blog_id == 0 match "ALL BLOGS"
						if ( ( $role_rule_blog_id == $current_blog_id ) || ( $role_rule_blog_id == 0 ) ) {
							if ( $this->wp_cassify_rule_matched( $role_rule_expression, $cas_user_datas ) ) {
								array_push( $roles_to_push, $role_rule_key );
							}
						}
					}						
				}
				else {
					if ( ( is_array( $role_rule_parts ) ) && ( count( $role_rule_parts ) == 2 ) ) {
						$role_rule_key = $role_rule_parts[0];
						$role_rule_expression = stripslashes( $role_rule_parts[1] );
		
						if ( $this->wp_cassify_rule_matched( $role_rule_expression, $cas_user_datas ) ) {
							array_push( $roles_to_push, $role_rule_key );
						}
					}					
				}
			}
		}

		return $roles_to_push;
	}

	/**
	 * Check if user is matched by Notification Rule
	 * @param	array 	$cas_user_datas				Associative array containing CAS userID and attributes
	 * @param 	array 	$notification_rules			Array containing all notification rules
	 * @param 	array 	$trigger_name				The name of the action wich fire the notification
	 * @return 	boolean	$notification_rule_matched	Return true if notification rule assertion is verified. Return false on the other hand.
	 */ 	
	private function wp_cassify_notification_rule_matched( $trigger_name, $cas_user_datas = array(), $notification_rules = array()) {
		
		$notification_rule_matched = false;

		if ( ( is_array( $notification_rules ) ) && ( count( $notification_rules ) > 0 ) ) {
			foreach ( $notification_rules as $notification_rule ) {
				$notification_rule_parts = explode( '|', $notification_rule );
				
				if ( ( is_array( $notification_rule_parts ) ) && ( count( $notification_rule_parts ) == 2 ) ) {
					$notification_rule_key = $notification_rule_parts[0];
					$notification_rule_expression = stripslashes( $notification_rule_parts[1] );
					
					if ( $notification_rule_key == $trigger_name ) {
						if ( $this->wp_cassify_rule_matched( $notification_rule_expression, $cas_user_datas ) ) {
							$notification_rule_matched = true;
						}						
					}
				}
			}
		}		
		
		return $notification_rule_matched;
	}

	/**
	 * Synchronize CAS User attributes values with Wordpress User metadatas. Create custom user_meta if not exist.
	 * @param 	string 	$cas_user_id								CAS userID
	 * @param	array 	$cas_user_datas								Associative array containing CAS userID and attributes
	 * @param 	array	$wp_cassify_user_attributes_mapping_list	Array containing mapping between CAS user attributes and Wordpress user attributes
	 */ 
	private function wp_cassify_sync_user_metadata( $cas_user_id, $cas_user_datas = array(), $wp_cassify_user_attributes_mapping_list = array() ) {
		
		if ( ( is_array( $wp_cassify_user_attributes_mapping_list ) ) && ( count( $wp_cassify_user_attributes_mapping_list ) > 0 ) ) {
            $wp_user = get_user_by( 'login', $cas_user_id );
			
			if ( $wp_user != false  ) {
	            foreach( $wp_cassify_user_attributes_mapping_list as $wp_cassify_user_attributes_mapping ) {
	            	$wp_cassify_user_attributes_mapping_parts = explode( '|', $wp_cassify_user_attributes_mapping );
	            	
	            	$wp_cassify_wordpress_user_meta = $wp_cassify_user_attributes_mapping_parts[ '0' ];
	            	$wp_cassify_cas_user_attribute = $wp_cassify_user_attributes_mapping_parts[ '1' ];
	            	
			        $cas_user_data_formatted = null;
            	
					if (! array_key_exists( $wp_cassify_cas_user_attribute, $cas_user_datas ) )
						continue;

            		if ( is_array( $cas_user_datas[ $wp_cassify_cas_user_attribute ] ) ) {
            			$cas_user_data_formatted = maybe_serialize( $cas_user_datas[ $wp_cassify_cas_user_attribute ] );
            		}
            		else {
            			$cas_user_data_formatted = $cas_user_datas[ $wp_cassify_cas_user_attribute ];
            		}
	            	
	            	$mapping_set = false;
	            	
	            	if ( property_exists( $wp_user->data, $wp_cassify_wordpress_user_meta ) ) {
	            	
	            		$user_id = wp_update_user( 
	            			array( 
	            				'ID' => $wp_user->data->ID, 
	            				$wp_cassify_wordpress_user_meta => $cas_user_data_formatted
            				) 
        				);

	            		$mapping_set = true;
	            	}
	            	
	            	if (! $mapping_set ) {
	            		
	            		$wp_cassify_wordpress_user_meta_value = get_user_meta( $wp_user->ID, $wp_cassify_wordpress_user_meta );
	            		
	            		if ( empty( $wp_cassify_wordpress_user_meta_value ) ) {
            				add_user_meta( 
            					$wp_user->ID, 
            					$wp_cassify_wordpress_user_meta, 
            					$cas_user_data_formatted, 
            					true 
    						);
	            		}
	            		else {
	            			update_user_meta( 
	            				$wp_user->ID, 
	            				$wp_cassify_wordpress_user_meta, 
	            				$cas_user_data_formatted
            				);
	            		}
	            	}
	            }
			}
		}
	}
	
	/**
	 * Send email notification
	 * @param string $message		Body of email notification message.
	 * @result boole $send_result	Return true if message is sent successfully. Return false on the other hand.
	 */ 
	public function wp_cassify_send_notification_message( $message ) {

		$wp_cassify_notifications_smtp_to = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_to' ) );
		$wp_cassify_notifications_subject_prefix = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_subject_prefix' ) );

		if ( empty( $message ) ) {
			$wp_cassify_send_notification_subject = $this->wp_cassify_default_notifications_options[ 'wp_cassify_default_notifications_subject' ];
			$wp_cassify_send_notification_message = $this->wp_cassify_default_notifications_options[ 'wp_cassify_default_notifications_message' ];
		}
		else {
			$wp_cassify_send_notification_subject = $message;
			$wp_cassify_send_notification_message = $message;
		}
	
		if ( empty( $wp_cassify_notifications_subject_prefix ) ) {
			$wp_cassify_send_notification_subject = $this->wp_cassify_default_notifications_options[ 'wp_cassify_default_notifications_subject_prefix' ] . $wp_cassify_send_notification_subject;
		}
		else {
			$wp_cassify_send_notification_subject = $wp_cassify_notifications_subject_prefix  . $wp_cassify_send_notification_subject;			
		}

		$wp_cassify_notifications_smtp_auth = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_auth' ) );
		
		$wp_cassify_notifications_smtp_auth_enabled = false;
		$wp_cassify_notifications_encryption_type = null;
		
		if ( $wp_cassify_notifications_smtp_auth == 'enabled' ) {
			$wp_cassify_notifications_smtp_auth_enabled = true;
			$wp_cassify_notifications_encryption_type = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_encryption_type' ) );
		}
		
		$wp_cassify_notifications_priority = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_priority' ) );

		$wp_cassify_notifications_smtp_user = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_user' ) );
		$wp_cassify_notifications_smtp_password = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_password' ) );
		
		$wp_cassify_notifications_salt = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_salt' ) );
		
		if (! empty( $wp_cassify_notifications_salt ) ) {
			$wp_cassify_notifications_smtp_password = WP_Cassify_Utils::wp_cassify_simple_decrypt( 
				$wp_cassify_notifications_smtp_password,
				$wp_cassify_notifications_salt
			);
		}
		else {
			$wp_cassify_notifications_smtp_password = WP_Cassify_Utils::wp_cassify_simple_decrypt( 
				$wp_cassify_notifications_smtp_password
			);						
		}

		$send_result = WP_Cassify_Utils::wp_cassify_sendmail( 
			esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_from' ) ), 
			$wp_cassify_notifications_smtp_to, 
			$wp_cassify_send_notification_subject, 
			$wp_cassify_send_notification_message,
			$wp_cassify_notifications_priority,
			esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_host' ) ), 
			$wp_cassify_notifications_encryption_type,
			$wp_cassify_notifications_smtp_user, 
			$wp_cassify_notifications_smtp_password,
			esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_port' ) ), 
			$wp_cassify_notifications_smtp_auth_enabled
		);
		
		return $send_result;
	}
} 
?>
