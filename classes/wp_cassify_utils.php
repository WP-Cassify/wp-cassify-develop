<?php
namespace wp_cassify;

class WP_Cassify_Utils {

	/**
	 * Perform an SSL web request to retrieve xml response containing cas-user id and cas-user attributes.
	 * @param 	string $url							Http url targeted by webrequest.
	 * @param 	string $ssl_cipher					Cipher used to process webrequest on https.
	 * @param 	string $ssl_check_certificate		Disable ssl certificate check.
	 * @return 	string $response					HTTP response received from target.
	 */ 
	public static function wp_cassify_do_ssl_web_request( $url, $ssl_cipher, $ssl_check_certificate = 'disabled' ) {
		
		if (! function_exists ( 'curl_init' ) ) {
			die( 'Please install php cURL library !');
		}

		$curlopt_ssl_verify_peer = 0;
		
		if ( $ssl_check_certificate == 'enabled' ) {
			$curlopt_ssl_verify_peer = 1;
		}

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_URL, $url ) ;
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $curlopt_ssl_verify_peer );
		
		//curl_setopt( $ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13' );
		curl_setopt( $ch, CURLOPT_SSLVERSION, $ssl_cipher );

		if ( ( defined( 'WP_DEBUG' ) ) && ( WP_DEBUG == true ) ) {
			curl_setopt( $ch, CURLOPT_VERBOSE, true );
		}
		
		$response = curl_exec( $ch );

		if( curl_errno( $ch ) )	{		
			if ( ( defined( 'WP_DEBUG' ) ) && ( WP_DEBUG == true ) ) {
				$info = curl_getinfo( $ch );
				die( 'Curl error: ' . curl_error( $ch ) . print_r( $info, TRUE ) );		
			}
			else {
				die( 'Curl error: active WP_DEBUG in wp-config.php');
			}
		} 

		curl_close( $ch );

		return $response;
	}
	
	/**
	 * Return the current url with parameters.
	 * @param 	string $wp_cassify_default_wordpress_blog_http_port		Port use for http communications. 80 By default.
	 * @param 	string $wp_cassify_default_wordpress_blog_https_port	Port use for https communications. 443 By default.
	 * @return 	string $current_url										Return current http url with parameters 
	 */   
	public static function wp_cassify_get_current_url( $wp_cassify_default_wordpress_blog_http_port = 80, $wp_cassify_default_wordpress_blog_https_port = 443 ) {
		
		$current_url = ( @$_SERVER[ 'HTTPS' ] == 'on' ) ? 'https://' : 'http://';
		
		// If cassified application is hosted behind reverse proxy.
		if ( isset( $_SERVER[ 'HTTP_X_FORWARDED_HOST' ] ) ) {
			$current_url .= $_SERVER[ 'HTTP_X_FORWARDED_HOST' ];
		}
		else {
			$current_url .= $_SERVER[ 'SERVER_NAME' ];
		}
		
		if( ( $_SERVER[ 'SERVER_PORT' ] != $wp_cassify_default_wordpress_blog_http_port ) && 
			( $_SERVER[ 'SERVER_PORT' ] != $wp_cassify_default_wordpress_blog_https_port ) ) {
			$current_url .= ':' . $_SERVER[ 'SERVER_PORT' ];
		} 

		// Specific use case configuration for Wordpress hosted on nginx behind AWS loadbalancer.
		if ( 
			isset( $_SERVER[ 'HTTP_HOST' ] ) && 
			isset( $_SERVER[ 'HTTP_X_FORWARDED_PORT' ] ) && 
			isset( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ) ) {
			
			$current_url = ( @$_SERVER[ 'HTTPS' ] == 'on' ? 'https://' : 'http://' ) . $_SERVER[ 'HTTP_HOST' ];
				
			if( ( $_SERVER[ 'HTTP_X_FORWARDED_PORT' ] != $wp_cassify_default_wordpress_blog_http_port ) && 
				( $_SERVER[ 'HTTP_X_FORWARDED_PORT' ] != $wp_cassify_default_wordpress_blog_https_port ) ) {
				$current_url .= ':' . $_SERVER[ 'SERVER_PORT' ];
			} 	
			
			if ( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] == 'https' ) {
				$current_url = str_replace( "http:", "https:", $current_url );
			}
		}

		$current_url .= $_SERVER[ 'REQUEST_URI' ];
		
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
		
		parse_str( $query, $url_params );

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
		
		$query 		= rawurlencode( parse_url( $url , PHP_URL_QUERY ) );

		parse_str( $query, $url_params );

		$query 		= '?' . http_build_query( $url_params, PHP_QUERY_RFC3986 );

		$query_encoded_url = "$scheme$user$pass$host$port$path$query$fragment"; 

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

			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID );
			
			do_action( 'wp_login', $user->user_login );
		}
		else {
			die( 'User account does not exists in Wordpress database !');
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
			if ( (! empty( $cas_user_email_attribute_value ) ) && ( email_exists( $cas_user_email_attribute_value ) == false ) ) {
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
		else {
			die( 'Redirect URL is not valid !');
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
     * @param array 	$post_array						$_POST array passed by reference
     * @param string 	$field_name						Form field name.
     * @param bool 		$do_not_check_empty				Empty values are accepted.
     * @param bool 		$network_activated				TRUE if plugin is activated on network. false if not.
     */
    public static function wp_cassify_update_textfield( &$post_array, $field_name, $do_not_check_empty = false, $wp_cassify_network_activated ) {

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

        if ( (! empty( $post_array[ $field_name ] ) ) && ( $post_array[ $field_name ] == $checked_value_name ) ) {
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
	 * Function used to encrypt data
	 * @param 	string	$text				Text to encrypt
	 * @param	string	$salt				String to salt encrypted string
	 * @return 	string	$encrypted_string	Text encrypted
	 */ 
	public static function wp_cassify_simple_encrypt( $text, $salt = "wp_cassify" ) {
		
		if (! function_exists ( 'mcrypt_encrypt' ) ) {
			die( 'Please install php mcrypt library !');
		}		
		
		$encrypted_string = trim( 
			base64_encode( 
				mcrypt_encrypt( 
					MCRYPT_RIJNDAEL_256, 
					$salt, 
					$text, 
					MCRYPT_MODE_ECB, 
					mcrypt_create_iv( 
						mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB ), 
						MCRYPT_RAND 
					) 
				)
			)
		);
		
		return $encrypted_string;
	}

	/**
	 * Function used to decrypt data
	 * @param 	string	$text				Text to decrypt
	 * @param	string	$salt				String to salt encrypted string
	 * @return 	string	$decrypted_string	Text decrypted
	 */ 
	public static function wp_cassify_simple_decrypt( $text, $salt = "wp_cassify_12345" ) {
		
		if (! function_exists ( 'mcrypt_decrypt' ) ) {
			die( 'Please install php mcrypt library !');
		}
		
		$decrypted_string = mcrypt_decrypt(
			MCRYPT_RIJNDAEL_256, 
			$salt, 
			base64_decode( $text ), 
			MCRYPT_MODE_ECB, 
			mcrypt_create_iv( 
				mcrypt_get_iv_size( 
					MCRYPT_RIJNDAEL_256, 
					MCRYPT_MODE_ECB
				), 
				MCRYPT_RAND
			)
		);
		
		return $decrypted_string;
	}    

    /**
     * Export all plugins configuration options
	 * @param bool 		$wp_cassify_network_activated
	 * @return array	$wp_cassify_export_configuration_options
     */	
	function wp_cassify_export_configuration_options( $wp_cassify_network_activated ) {

		global $wpdb;
	
		$wp_cassify_export_configuration_options = array();
		$configuration_options = null;
	
	    if ( $wp_cassify_network_activated ) {
			$configuration_options = $wpdb->get_results( "SELECT `meta_key`, `meta_value` FROM {$wpdb->prefix}sitemeta WHERE `meta_key` LIKE 'wp_cassify%'" );
	    }
	    else {
			$configuration_options = $wpdb->get_results( "SELECT `option_name`, `option_value` FROM {$wpdb->prefix}options WHERE `option_name` LIKE 'wp_cassify%'" );
	    }
	    
	    foreach( $configuration_options as $configuration_option ) {
	    	$wp_cassify_export_configuration_options[ $configuration_option->option_name ] = $configuration_option->option_value;
	    }
		
		return $wp_cassify_export_configuration_options;
	}
	
    /**
     * Import all plugins configuration options
	 * @param array		$wp_cassify_import_configuration_options
	 * @param bool 		$wp_cassify_network_activated
     */	
	function wp_cassify_import_configuration_options( $wp_cassify_import_configuration_options = array(), $wp_cassify_network_activated ) {

		global $wpdb;
		$restore_option_sql_query = null;

	    foreach( $wp_cassify_import_configuration_options as $option_name => $option_value ) {

			if ( $option_name == 'wp_cassify_xml_response_value' ) {
				$option_value = htmlentities( $option_value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
				$option_value = htmlentities( $option_value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
			}

		    if ( $wp_cassify_network_activated ) {
				$restore_option_sql_query = "UPDATE {$wpdb->prefix}sitemeta " . 
					" SET `meta_value` = '" . $option_value . "' " .
					" WHERE `meta_key` = '" .	$option_name .  "' ";
		    }
		    else {
				$restore_option_sql_query = "UPDATE {$wpdb->prefix}options " . 
					" SET `option_value` = '" . $option_value . "' " .
					" WHERE `option_name` = '" .	$option_name .  "' ";
		    }
		    
		    $wpdb->query($restore_option_sql_query);
	    }
	}	
	
	/**
	 * Function used by plugin to send mail.
	 * @param 	string	$from			Sender email address
	 * @param 	string	$to				Recipient email address
	 * @param 	string	$subject		Subject message
	 * @param 	string	$body			Body message
	 * @param 	string	$smtp_host		Ip or fqdn of smtp host
	 * @param 	string	$smtp_port		Port used by smtp host
	 * @param 	string	$smtp_auth		Cipher used if authentication
	 * @param 	string	$smtp_password	Smtp password
	 * @return 	bool	$send_result	Return TRUE if mail is sended correctly. FAIL if not.
	 */ 
	public static function wp_cassify_sendmail( $from, $to, $subject, $body, $priority, $smtp_host, $smtp_port = 25, $smtp_auth = false, $smtp_encryption_type, $smtp_user, $smtp_password ) {
		
		// Create the Transport
		$transport = \Swift_SmtpTransport::newInstance( $smtp_host, $smtp_port )
			->setUsername( $smtp_user )
			->setPassword( $smtp_password );
			
		if ( $smtp_auth == TRUE ) {
			$transport->setEncryption( $smtp_encryption_type );
			// $transport->setStreamOptions( array( $smtp_encryption_type => array( 'allow_self_signed' => true, 'verify_peer' => false ) ) );
		}
		
		// Create the Mailer using your created Transport
		$mailer = \Swift_Mailer::newInstance( $transport );			

		// Create the message
		$message = \Swift_Message::newInstance()
			->setSubject( $subject )
			->setFrom( array( $from => $from ) )
			->setTo( array( $to ) )
			->setBody( $body )
			->setContentType( 'text/html; charset=UTF-8')
			->setReturnPath( $from )
			->setPriority( $priority );
			
		// Send the message
		if (! $mailer->send( $message, $failures ) ) {
			$send_result = $failures;
		}
		else {
			$send_result = TRUE;
		}
		
		return $send_result;
	}
}

?>
