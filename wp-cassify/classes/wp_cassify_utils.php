<?php
namespace wp_cassify;

class WP_Cassify_Utils {

	/**
	 * Perform an SSL web request to retrieve xml response containing cas-user id and cas-user attributes.
	 * @param 	string	$url						Http url targeted by webrequest.
	 * @param 	string	$ssl_cipher					Cipher used to process webrequest on https.
	 * @param 	string 	$ssl_check_certificate		Disable ssl certificate check.
	 * @param	string	$ca_cainfo					Path to Certificate Authority (CA) bundle
	 * @param	string	$ca_capath					Specify directory holding CA certificates
	 * 
	 * @return 	string $response					HTTP response received from target.
	 */ 
	public static function wp_cassify_do_ssl_web_request( 
		$url, 
		$ssl_cipher, 
		$ssl_check_certificate = 'disabled', 
		$curlopt_cainfo = '',
		$curlopt_capath = '' ) {
		
		if (! function_exists ( 'curl_init' ) ) {
			wp_die(
				__( 'Please install PHP cURL library.', 'wp-cassify' ),
				__( 'Server Configuration Error', 'wp-cassify' ),
				array( 'response' => 500 )
			);
		}

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_URL, $url ) ;
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, ( ( $ssl_check_certificate === 'enabled' ) ? 1 : 0 ) );

		if ( $ssl_check_certificate === 'enabled' ) {
			if (! empty( $curlopt_cainfo ) ) {
				curl_setopt( $ch, CURLOPT_CAINFO, $curlopt_cainfo );
			}
			if (! empty( $curlopt_capath ) ) {
				curl_setopt( $ch, CURLOPT_CAPATH, $curlopt_capath );
			}
		}
		
		//curl_setopt( $ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13' );
		curl_setopt( $ch, CURLOPT_SSLVERSION, $ssl_cipher );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			curl_setopt( $ch, CURLOPT_VERBOSE, true );
		}
		
		$response = curl_exec( $ch );

		if( curl_errno( $ch ) )	{		
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$info = curl_getinfo( $ch );
				wp_die(
					'cURL error: ' . esc_html( curl_error( $ch ) ) . '<br /><pre>' . esc_html( print_r( $info, true ) ) . '</pre>',
					__( 'CAS Request Error', 'wp-cassify' ),
					array( 'response' => 502 )
				);
			}
			else {
				wp_die(
					__( 'cURL error: set WP_DEBUG to true in wp-config.php for detailed error information.', 'wp-cassify' ),
					__( 'CAS Request Error', 'wp-cassify' ),
					array( 'response' => 502 )
				);
			}
		} 

		curl_close( $ch );

		return $response;
	}

	/**
	 * Log a message to the PHP error log.
	 *
	 * - INFO and DEBUG levels are logged only when either:
	 *     (a) the plugin option 'wp_cassify_debug_log' is set to 'enabled', OR
	 *     (b) both WP_DEBUG and WP_DEBUG_LOG constants are true (dev environment).
	 * - WARN and ERROR levels are always logged regardless of settings.
	 *
	 * When WP_DEBUG_LOG is enabled, WordPress redirects error_log() output to
	 * wp-content/debug.log instead of the server error log.
	 *
	 * @param string $message  The message to log.
	 * @param string $level    Log level : DEBUG | INFO | WARN | ERROR. Default: 'INFO'.
	 */
	public static function wp_cassify_log( string $message, string $level = 'INFO' ): void {
		$always_log = in_array( $level, [ 'WARN', 'ERROR' ], true );

		$plugin_debug_enabled = get_option( 'wp_cassify_debug_log' ) === 'enabled'
			|| get_site_option( 'wp_cassify_debug_log' ) === 'enabled';

		$wp_debug_log_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG
			&& defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;

		if ( $always_log || $plugin_debug_enabled || $wp_debug_log_enabled ) {
			error_log( '[WP Cassify][' . $level . '] ' . $message );
		}
	}

	/**
	 * Return the current url with parameters.
	 * @param 	string $wp_cassify_default_wordpress_blog_http_port		Port use for http communications. 80 By default.
	 * @param 	string $wp_cassify_default_wordpress_blog_https_port	Port use for https communications. 443 By default.
	 * @return 	string $current_url										Return current http url with parameters 
	 */   
	public static function wp_cassify_get_current_url( $wp_cassify_default_wordpress_blog_http_port = 80, $wp_cassify_default_wordpress_blog_https_port = 443 ) {
		
		$current_url = ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] === 'on' ) ? 'https://' : 'http://';

		// // If cassified application is hosted behind reverse proxy.
		// if ( isset( $_SERVER[ 'HTTP_X_FORWARDED_HOST' ] ) ) {
		// 	$current_url .= $_SERVER[ 'HTTP_X_FORWARDED_HOST' ];
		// }
		// else {
		// 	$current_url .= $_SERVER[ 'SERVER_NAME' ];
		// }
		
		// if( ( $_SERVER[ 'SERVER_PORT' ] != $wp_cassify_default_wordpress_blog_http_port ) && 
		// 	( $_SERVER[ 'SERVER_PORT' ] != $wp_cassify_default_wordpress_blog_https_port ) ) {
		// 	$current_url .= ':' . $_SERVER[ 'SERVER_PORT' ];
		// } 

		// If cassified application is hosted behind reverse proxy.
		if ( isset( $_SERVER[ 'HTTP_X_FORWARDED_HOST' ] ) ) {
			$current_url .= $_SERVER[ 'HTTP_X_FORWARDED_HOST' ];
		} elseif (isset( $_ENV['PANTHEON_SITE'] )) { // Are we on Pantheon? Use HTTP_HOST
			$current_url .= $_SERVER['HTTP_HOST'];
		}
		else {
			$current_url .= $_SERVER[ 'SERVER_NAME' ];
		}

		if( isset( $_SERVER[ 'SERVER_PORT' ] ) &&
			( $_SERVER[ 'SERVER_PORT' ] != $wp_cassify_default_wordpress_blog_http_port ) &&
			( $_SERVER[ 'SERVER_PORT' ] != $wp_cassify_default_wordpress_blog_https_port ) &&
			!isset( $_ENV['PANTHEON_SITE'] ) ) // Don't use the port if we're on Pantheon
		  {
			$current_url .= ':' . $_SERVER[ 'SERVER_PORT' ];
		}

		// Specific use case configuration for Wordpress hosted on nginx behind AWS loadbalancer.
		if ( 
			isset( $_SERVER[ 'HTTP_HOST' ] ) && 
			isset( $_SERVER[ 'HTTP_X_FORWARDED_PORT' ] ) && 
			isset( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ) ) {
			
			$current_url = ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] === 'on' ? 'https://' : 'http://' ) . $_SERVER[ 'HTTP_HOST' ];
				
			if( ( $_SERVER[ 'HTTP_X_FORWARDED_PORT' ] != $wp_cassify_default_wordpress_blog_http_port ) && 
				( $_SERVER[ 'HTTP_X_FORWARDED_PORT' ] != $wp_cassify_default_wordpress_blog_https_port ) &&
				isset( $_SERVER[ 'SERVER_PORT' ] ) ) {
				$current_url .= ':' . $_SERVER[ 'SERVER_PORT' ];
			} 	
			
			if ( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] === 'https' ) {
				$current_url = str_replace( "http:", "https:", $current_url );
			}
		}

		$current_url .= isset( $_SERVER[ 'REQUEST_URI' ] ) ? $_SERVER[ 'REQUEST_URI' ] : '/';
		
		return $current_url;
	}
	
	/**
	 * Return value of a parameter passed in url with get method.
	 * @param 	string $url						Http url from wich you extract GET parameters
	 * @param 	string $get_parameter_name		GET parameter name
	 * @return 	string $get_parameter_value		GET parameter value
	 */ 
	public static function wp_cassify_extract_get_parameter( $url , $get_parameter_name ) {
		
		$get_parameter_value = null;
		
		$query = parse_url( $url , PHP_URL_QUERY );
		
		if (! empty( $query ) ) {
		  parse_str( $query, $url_params );
		}

		if (! empty( $url_params[ $get_parameter_name ] ) ) {
			$get_parameter_value = $url_params[ $get_parameter_name ];
		}
		
		return $get_parameter_value;
	}
	
	/**
	 * Return url without get parameter
	 * @param 	string $url						Http url from wich you want to strip GET parameter
	 * @param 	string $get_parameter_name		GET parameter name
	 * @return 	string $stripped_url			Url without stripped value
	 */ 
	public static function wp_cassify_strip_get_parameter( $url , $get_parameter_name ) {
		
		$stripped_url = null;
		
		$parsed_url = parse_url( $url );
		
		$scheme   	= isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : ''; 
		$host     	= isset( $parsed_url['host'] ) ? $parsed_url['host'] : ''; 
		$port     	= isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : ''; 
		$user     	= isset( $parsed_url['user'] ) ? $parsed_url['user'] : ''; 
		$pass     	= isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass']  : ''; 
		$pass     	= ( $user || $pass ) ? "$pass@" : ''; 
		$path     	= isset( $parsed_url['path'] ) ? $parsed_url['path'] : ''; 
		$fragment	= isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : ''; 		
		
		$query 		= parse_url( $url , PHP_URL_QUERY );

		parse_str( $query, $url_params );
		unset( $url_params[ $get_parameter_name ] );

		$query 		= http_build_query( $url_params );

		if (! empty( $query ) ) {
			$query = '?' . $query;
		}

		$stripped_url = "$scheme$user$pass$host$port$path$query$fragment"; 

		return $stripped_url;
	}	
	
	/**
	 * Return url with query parameters encoded
	 * @param 	string $url						Http url from wich you want to strip GET parameter
	 * @return 	string $query_encoded_url		Url with query parameters encoded
	 */ 
	public static function wp_cassify_encode_query_in_url( $url ) {
		
		$query_encoded_url = null;
		
		$parsed_url = parse_url( $url );
		
		$scheme   	= isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : ''; 
		$host     	= isset( $parsed_url['host'] ) ? $parsed_url['host'] : ''; 
		$port     	= isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : ''; 
		$user     	= isset( $parsed_url['user'] ) ? $parsed_url['user'] : ''; 
		$pass     	= isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass']  : ''; 
		$pass     	= ( $user || $pass ) ? "$pass@" : ''; 
		$path     	= isset( $parsed_url['path'] ) ? $parsed_url['path'] : ''; 
		$fragment	= isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : ''; 		
		
		// $query 		= rawurlencode( parse_url( $url , PHP_URL_QUERY ) );
		$query 		= parse_url( $url , PHP_URL_QUERY );
		
		parse_str( $query, $url_params );

		$query 		= '?' . http_build_query( $url_params, PHP_QUERY_RFC3986 );

		// $query_encoded_url = "$scheme$user$pass$host$port$path$query$fragment"; 
		$query_encoded_url = rawurlencode( "$scheme$user$pass$host$port$path$query$fragment" ); 

		return $query_encoded_url;
	}	
	
	/**
	 * Return the left part of an URI
	 * @param 	string $url				HTTP url from wich you want to extract left part.
	 * @return 	string $left_part_uri	Left part of http url with fqdn.
	 */ 
	public static function wp_cassify_get_host_uri( $url ) {
		
		$left_part_uri = null;
		
		$query = parse_url( $url );

		if ( $query != false ) {
			$left_part_uri = $query[ 'scheme' ] . '://' . $query[ 'host' ];
			if (! empty( $query[ 'port' ] ) ) {
				$left_part_uri .= ':' . $query[ 'port' ]; 
			}	
		}
		
		return $left_part_uri;
	}	
	
	/**
	 *  Authenticate user into Wordpress
	 *  @param $cas_user_id			Id of user provided by CAS Server response.
	 */ 
	public static function wp_cassify_auth_user_wordpress( $cas_user_id ) {
		
		if ( username_exists( $cas_user_id ) ) {
			$user = get_user_by( 'login', $cas_user_id );

			// In multisite, a user can exist at network level (wp_users) but not
			// be a member of the current sub-site. Without blog membership,
			// is_user_member_of_blog() stays false after authentication, which
			// causes wp_cassify_grab_service_ticket() to re-enter the auth flow
			// on every page load, creating an infinite redirect loop.
			// Role rules (wp_cassify_get_roles_to_push) already run before this
			// function and may have added the user via add_role(). We only fall
			// back to add_user_to_blog() when the user is still not a member
			// (no role rules matched, or the plugin is not network-activated).
			if ( is_multisite() && ! is_user_member_of_blog( $user->ID, get_current_blog_id() ) ) {
				add_user_to_blog( get_current_blog_id(), $user->ID, get_option( 'default_role', 'subscriber' ) );
				wp_cassify\WP_Cassify_Utils::wp_cassify_log(
					'Multisite: user ' . $cas_user_id . ' added to blog ' . get_current_blog_id() . ' with default role.',
					'INFO'
				);
			}

			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID );
			
			do_action( 'wp_login', $user->user_login, $user );
		}
		else {
			wp_die(
				__( 'User account does not exist in WordPress database.', 'wp-cassify' ),
				__( 'Access Denied', 'wp-cassify' ),
				array( 'response' => 403 )
			);
		}
	}	
	
	/**
	 * Create wordpress user account if not exist.
	 * @param 	string $cas_user_id							Id of user provided by CAS Server response.
	 * @param 	string $cas_user_email_attribute_value		CAS User email attribute provided by CAS Server response.
	 * @return 	object $wp_user_id							Return Wordpress User ID or null if user account has not been created.
	 */ 
	public static function wp_cassify_create_wordpress_user( $cas_user_id, $cas_user_email_attribute_value ) {

		$user_email = null;
		$wp_user_id = 0;

		if (! username_exists( $cas_user_id ) ) {
			if ( (! empty( $cas_user_email_attribute_value ) ) && ( email_exists( $cas_user_email_attribute_value ) === false ) ) {
				$user_email = $cas_user_email_attribute_value;
			}
			
			$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
			$wp_user_id = wp_create_user( $cas_user_id, $random_password, $user_email );
		}
		
		return $wp_user_id;
	}
	
	/**
	 * Check if wordpress user account already exist
	 * @param 	string	$cas_user_id				Id of user provided by CAS Server response.
	 * @return 	bool 	$is_wordpress_user_exist	Return TRUE if wordpress user account exist.
	 */ 
	public static function wp_cassify_is_wordpress_user_exist( $cas_user_id ) {

		$is_wordpress_user_exist = TRUE;

		if (! username_exists( $cas_user_id ) ) {
			$is_wordpress_user_exist = false;
		}
		
		return $is_wordpress_user_exist;
	}	
	
	/**
	 * Set role to an existing Wordpress user and remove all other roles
	 * @param	string 	$wordpress_user_login			Wordpress user login
	 * @param 	string 	$role_key						Role key used in Wordpress. For example, "author" for "Author".
	 * @return 	bool 	$wp_user_role_updated			Return TRUE if wordpress user account has been updated. false if not.
	 */ 
	public static function wp_cassify_set_role_to_wordpress_user( $wordpress_user_login, $role_key ) {
		
		$wp_user_role_updated = false;
		$wp_user = get_user_by( 'login', $wordpress_user_login );
		
		if ( $wp_user != false ) {
			$wp_user->set_role( $role_key );
			$wp_user_role_updated = TRUE;
		}

		return $wp_user_role_updated;
	}
	
	/**
	 * Add role to an existing Wordpress user
	 * @param	string 	$wordpress_user_login	Wordpress user login
	 * @param 	string 	$role_key				Role key used in Wordpress. For example, "author" for "Author".
	 * @return 	bool 	$wp_user_role_updated	Return TRUE if wordpress user account has been updated. false if not.
	 */ 
	public static function wp_cassify_add_role_to_wordpress_user( $wordpress_user_login, $role_key ) {
		
		$wp_user_role_updated = false;
		$wp_user = get_user_by( 'login', $wordpress_user_login );
		
		if ( $wp_user != false ) {
			$wp_user->add_role( $role_key );
			$wp_user_role_updated = TRUE;
		}

		return $wp_user_role_updated;
	}
	
	/**
	 * Clear all roles to an existing Wordpress user
	 * @param	string 	$wordpress_user_login	Wordpress user login
	 */ 
	public static function wp_cassify_clear_roles_to_wordpress_user( $wordpress_user_login ) {
		
		$wp_user = get_user_by( 'login', $wordpress_user_login );
		
		if ( $wp_user != false ) {
			foreach( $wp_user->roles as $role ) {
				$wp_user->remove_role( $role );
			}				
		}
	}	
	
    /**
     * Return array with Wordpress user roles.
     * @return array $wordpress_roles	Array of availables wordpress roles.
     */
    public static function wp_cassify_get_wordpress_roles_names() {
        
		$wp_roles = new \WP_Roles();
		$wordpress_roles = $wp_roles->get_names();
		
		return $wordpress_roles;
    }
    	
	/**
	 * Process http redirection.
	 * @param string redirect_url	Http url targeted by redirection.
	 */ 
	public static function wp_cassify_redirect_url( $redirect_url ) {
		
		// Perform redirection only if url is valid.
		if ( filter_var( $redirect_url, FILTER_VALIDATE_URL ) ) {
			wp_redirect( $redirect_url ); 
			exit();
		}
		else if ( filter_var( urldecode( $redirect_url ), FILTER_VALIDATE_URL ) ) {
			wp_redirect( $redirect_url ); 
			exit();			
		}
		else {
			wp_die(
				__( 'Redirect URL is not valid.', 'wp-cassify' ),
				__( 'Invalid Request', 'wp-cassify' ),
				array( 'response' => 400 )
			);
		}	
	}
        
    /**
     * Get plugin option according to activation plugin level
     * @param 	bool 	$network_activated			TRUE if plugin is activated on network. false if not.
     * @param 	string 	$option_name				Name of blog option or site option if network activated.
     * @return 	string 	$wp_cassify_plugin_option	Return the option value.
     */
    public static function wp_cassify_get_option( $network_activated, $option_name ) {
        
        $wp_cassify_plugin_option = '';
        
        if ( $network_activated ) {
            $wp_cassify_plugin_option = get_site_option( $option_name );
        }
        else {
            $wp_cassify_plugin_option = get_option( $option_name );
        }
        
        return $wp_cassify_plugin_option;
    }
    
    /**
     * Set plugin option according to activation plugin level
     * @param 	bool 	$network_activated			TRUE if plugin is activated on network. false if not.
     * @param 	string 	$option_name				Name of blog option or site option if network activated.
     * @param 	string 	$option_value				The new option value.
     */
    public static function wp_cassify_update_option( $network_activated, $option_name, $option_value ) {
        
        $wp_cassify_plugin_option = '';
        
        if ( $network_activated ) {
            update_site_option( $option_name , sanitize_text_field( $option_value ) );
        }
        else {
            update_option( $option_name , sanitize_text_field( $option_value ) );
        }
    }    

    /**
     * Save plugin options stored in form textfield into database.
	 * 
     * @param array 	$post_array						$_POST array passed by reference
     * @param string 	$field_name						Form field name.
     * @param bool 		$network_activated				TRUE if plugin is activated on network. false if not.
     * @param bool 		$do_not_check_empty				Empty values are accepted.
     */
    public static function wp_cassify_update_textfield( &$post_array, $field_name, $wp_cassify_network_activated, $do_not_check_empty = false ) {

		$field_value = '';
		
		if (! $do_not_check_empty ) {
			if(! empty( $post_array[ $field_name ] ) ) {
	        	$field_value = $post_array[ $field_name ];
	        }
		}
		else {
			$field_value = $post_array[ $field_name ];
		}
		
        if ( $wp_cassify_network_activated ) {
            update_site_option( $field_name , sanitize_text_field( $field_value ) );
        }
        else {
            update_option( $field_name , sanitize_text_field( $field_value ) );
        }                
    }
    
    /**
     * Save plugin options stored in form textfield into database.
     * @param string $field_value
     * @param string $field_name
     * @param bool $wp_cassify_network_activated
     */
    public static function wp_cassify_update_textfield_manual( $field_value, $field_name, $wp_cassify_network_activated ) {

        if( isset( $field_value ) ) {
            if ( $wp_cassify_network_activated ) {
                update_site_option( $field_name , sanitize_text_field( $field_value ) );
            }
            else {
                update_option( $field_name , sanitize_text_field( $field_value ) );
            }                
        }
    }    
        
    /**
     * Save plugin options stored in form checkbox into database.
     * @param array $post_array
     * @param string $field_name
     * @param bool $wp_cassify_network_activated
     */
	public static function wp_cassify_update_checkbox( &$post_array, $field_name, $checked_value_name, $wp_cassify_network_activated ) {

		if ( (! empty( $post_array[ $field_name ] ) ) && ( $post_array[ $field_name ] === $checked_value_name ) ) {
            if ( $wp_cassify_network_activated ) {
                update_site_option( $field_name , $post_array[ $field_name ] );
            }
            else {
                update_option( $field_name , $post_array[ $field_name ] );
            }      
        }
        else {
            if ( $wp_cassify_network_activated ) {
                update_site_option( $field_name , '' );
            }
            else {
                update_option( $field_name , '' );
            }
        }	            
    } 
    
    /**
     * Save plugin options stored in form multiple select into database.
     * @param array $post_array
     * @param string $field_name
	 * @param bool $wp_cassify_network_activated
     */
    public static function wp_cassify_update_multiple_select( &$post_array, $field_name, $wp_cassify_network_activated  ) {
        
		$field_value = '';

        if(! empty( $post_array[ $field_name ] ) ) {
        	$field_value = $post_array[ $field_name ];
        	
            if( !is_serialized( $field_value ) ) {
                $field_value = serialize( $field_value );
            }
        }
        
        if ( $wp_cassify_network_activated ) {
            update_site_option( $field_name , sanitize_text_field( $field_value ) );
        }
        else {
            update_option( $field_name , sanitize_text_field( $field_value ) );
        }        
    }      

	/**
	 * Safely unserialize a plugin option expected to contain an array.
	 * Handles legacy double-serialized values while disallowing classes.
	 *
	 * @param mixed $serialized_value
	 * @return array
	 */
	public static function wp_cassify_safe_unserialize_array( $serialized_value ) {

		if ( is_array( $serialized_value ) ) {
			return $serialized_value;
		}

		if ( (! is_string( $serialized_value ) ) || $serialized_value === '' ) {
			return array();
		}

		$current_value = $serialized_value;

		for ( $i = 0; $i < 2; $i++ ) {
			$unserialized_value = @unserialize( $current_value, array( 'allowed_classes' => false ) );

			if ( is_array( $unserialized_value ) ) {
				return $unserialized_value;
			}

			if ( is_string( $unserialized_value ) && is_serialized( $unserialized_value ) ) {
				$current_value = $unserialized_value;
				continue;
			}

			break;
		}

		return array();
	}
    
	/**
	 * Function used to encrypt data
	 * @param 	string	$text				Text to encrypt
	 * @param	string	$salt				String to salt encrypted string
	 * @return 	string	$encrypted_string	Text encrypted
	 */ 
	public static function wp_cassify_simple_encrypt( $text, $salt = "wp_cassify" ) {

		$cipher				= "AES-128-CBC"; 
		$key				= substr( hash( 'sha256', (string) $salt, true ), 0, 16 );
		$ivlen				= openssl_cipher_iv_length( $cipher );
		$iv					= openssl_random_pseudo_bytes( $ivlen );
		$ciphertext_raw 	= openssl_encrypt( $text, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv );
		$hmac 				= hash_hmac( 'sha256', $ciphertext_raw, $key, $as_binary=true );
		
		$encrypted_string 	= base64_encode( $iv.$hmac.$ciphertext_raw );
		
		return $encrypted_string;
	}

	/**
	 * Function used to decrypt data
	 * @param 	string	$text				Text to decrypt
	 * @param	string	$salt				String to salt encrypted string
	 * @return 	string	$decrypted_string	Text decrypted
	 */ 
	public static function wp_cassify_simple_decrypt( $text, $salt = "wp_cassify_12345" ) {

		$cipher				= "AES-128-CBC"; 
		$key				= substr( hash( 'sha256', (string) $salt, true ), 0, 16 );
		$c 					= base64_decode( $text );
		$ivlen 				= openssl_cipher_iv_length( $cipher );
		$iv 				= substr( $c, 0, $ivlen );
		$hmac 				= substr( $c, $ivlen, $sha2len=32 );
		$ciphertext_raw 	= substr( $c, $ivlen+$sha2len );
		$decrypted_string 	= openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv );
		$calcmac 			= hash_hmac( 'sha256', $ciphertext_raw, $key, $as_binary=true );

		// Backward compatibility with legacy values encrypted with an empty key.
		if ( $decrypted_string === false || ! hash_equals( $hmac, $calcmac ) ) {
			$legacy_key = '';
			$legacy_decrypted_string = openssl_decrypt($ciphertext_raw, $cipher, $legacy_key, $options=OPENSSL_RAW_DATA, $iv );
			$legacy_calcmac = hash_hmac( 'sha256', $ciphertext_raw, $legacy_key, $as_binary=true );
			if ( $legacy_decrypted_string !== false && hash_equals( $hmac, $legacy_calcmac ) ) {
				return $legacy_decrypted_string;
			}
		}
		
		if ( hash_equals( $hmac, $calcmac ) ) {//PHP 5.6+ timing attack safe comparison
		    return $decrypted_string;
		}
		else {
			return "";
		}
	}    

    /**
     * Export all plugins configuration options
	 * @param bool 		$wp_cassify_network_activated
	 * @return array	$wp_cassify_export_configuration_options
     */	
	public static function wp_cassify_export_configuration_options( $wp_cassify_network_activated ) {

		global $wpdb;
	
		$wp_cassify_export_configuration_options = array();
		$configuration_options = null;

		if ( $wp_cassify_network_activated ) {
			$configuration_options = $wpdb->get_results( "SELECT `meta_key`, `meta_value` FROM {$wpdb->prefix}sitemeta WHERE `meta_key` LIKE 'wp_cassify%' AND `meta_key` != 'wp_cassify_xml_response_value'" );
			
			foreach ( $configuration_options as $configuration_option ) {
				$wp_cassify_export_configuration_options[$configuration_option->meta_key] = $configuration_option->meta_value;
			}
		} else {
			$configuration_options = $wpdb->get_results( "SELECT `option_name`, `option_value` FROM {$wpdb->prefix}options WHERE `option_name` LIKE 'wp_cassify%' AND `option_name` != 'wp_cassify_xml_response_value'" );
			
			foreach ( $configuration_options as $configuration_option ) {
				$wp_cassify_export_configuration_options[$configuration_option->option_name] = $configuration_option->option_value;
			}
		}	    
		
		return $wp_cassify_export_configuration_options;
	}
	
    /**
     * Import all plugins configuration options
	 * 
	 * @param bool 		$wp_cassify_network_activated
	 * @param array		$wp_cassify_import_configuration_options
     */	
	public static function wp_cassify_import_configuration_options( $wp_cassify_network_activated, $wp_cassify_import_configuration_options = array() ) {

		global $wpdb;
		$restore_option_sql_query = null;

	    foreach( $wp_cassify_import_configuration_options as $option_name => $option_value ) {

			if ( $option_name === 'wp_cassify_xml_response_value' ) {
				$option_value = htmlentities( $option_value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
				$option_value = htmlentities( $option_value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
			}

		    if ( $wp_cassify_network_activated ) {
				$restore_option_sql_query = $wpdb->prepare(
					"UPDATE {$wpdb->prefix}sitemeta SET `meta_value` = %s WHERE `meta_key` = %s",
					$option_value,
					$option_name
				);
		    }
		    else {
				$restore_option_sql_query = $wpdb->prepare(
					"UPDATE {$wpdb->prefix}options SET `option_value` = %s WHERE `option_name` = %s",
					$option_value,
					$option_name
				);
		    }
		    
		    $wpdb->query( $restore_option_sql_query );
	    }
	}	
	
	/**
	 * Function used by plugin to send mail.
	 * 
	 * @param 	string	$from			Sender email address
	 * @param 	string	$to				Recipient email address
	 * @param 	string	$subject		Subject message
	 * @param 	string	$body			Body message
	 * @param 	string	$smtp_host		Ip or fqdn of smtp host
	 * @param 	string	$smtp_password	Smtp password
	 * @return 	bool	$send_result	Return TRUE if mail is sended correctly. FAIL if not.
	 * @param 	string	$smtp_port		Port used by smtp host
	 * @param 	string	$smtp_auth		Cipher used if authentication
	 */ 
	public static function wp_cassify_sendmail( $from, $to, $subject, $body, $priority, $smtp_host, $smtp_encryption_type, $smtp_user, $smtp_password, $smtp_port = 25, $smtp_auth = false ) {
		
		// Initialize phpmailer class
		global $phpmailer;
		
		// (Re)create it, if it's gone missing
		if ( ! ( $phpmailer instanceof \PHPMailer ) ) {
		    require_once ABSPATH . WPINC . '/class-phpmailer.php';
		    require_once ABSPATH . WPINC . '/class-smtp.php';
		}
		
		$phpmailer = new \PHPMailer;
		
		// SMTP configuration
		$phpmailer->isSMTP();                    
		$phpmailer->Host 		= $smtp_host;
		$phpmailer->SMTPAuth 	= true;
		$phpmailer->Username 	= $smtp_user;
		$phpmailer->Password 	= $smtp_password;
		$phpmailer->SMTPSecure 	= $smtp_encryption_type;
		$phpmailer->Port 		= $smtp_port;		
		$phpmailer->CharSet 	= "UTF-8";		
		$phpmailer->Subject 	= $subject;
		$phpmailer->Body    	= $body;

		$phpmailer->setFrom( $from );
		$phpmailer->addAddress( $to );		

		if( ! $phpmailer->send() ) {
		    $send_result =  $phpmailer->ErrorInfo;
		}
		else {
		    $send_result =  true;
		}

		return $send_result;
	}
}

?>
