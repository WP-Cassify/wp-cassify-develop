<?php
namespace wp_cassify;

class WP_Cassify_Utils {



	/**
	 * Perform an SSL web request to retrieve xml response containing 
	 * cas-user id and cas-user attributes.
	 * @param string $url
	 * @param string $ssl_cipher
	 * @param string $ssl_check_certificate
	 * @return string $response
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
	 * @param string $wp_cassify_default_wordpress_blog_http_port
	 * @param string $wp_cassify_default_wordpress_blog_https_port
	 * @return string $current_url
	 */   
	public static function wp_cassify_get_current_url( $wp_cassify_default_wordpress_blog_http_port, $wp_cassify_default_wordpress_blog_https_port ) {
		
		$current_url = ( @$_SERVER[ 'HTTPS' ] == 'on' ) ? 'https://' : 'http://';
		$current_url .= $_SERVER[ 'SERVER_NAME' ];
	 
		if( ( $_SERVER[ 'SERVER_PORT' ] != $wp_cassify_default_wordpress_blog_http_port ) && 
			( $_SERVER[ 'SERVER_PORT' ] != $wp_cassify_default_wordpress_blog_https_port ) ) {
			$current_url .= ':' . $_SERVER[ 'SERVER_PORT' ];
		} 
	 
		$current_url .= $_SERVER[ 'REQUEST_URI' ];
		
		return $current_url;
	}
	
	/**
	 * Return value of a parameter passed in url with get method.
	 * @param string $url
	 * @param string $get_parameter_name
	 * @return string $get_parameter_value
	 */ 
	public static function wp_cassify_extract_get_parameter( $url , $get_parameter_name ) {
		
		$get_parameter_value = NULL;
		
		$query = parse_url( $url , PHP_URL_QUERY );
		
		parse_str( $query, $url_params );

		if (! empty( $url_params[ $get_parameter_name ] ) ) {
			$get_parameter_value = $url_params[ $get_parameter_name ];
		}
		
		return $get_parameter_value;
	}
	
	/**
	 * Return the left part of an URI
	 * @param string $url
	 * @return string $left_part_uri
	 */ 
	public static function wp_cassify_get_host_uri( $url ) {
		
		$left_part_uri = NULL;
		
		$query = parse_url( $url );

		if ( $query != FALSE ) {
			$left_part_uri = $query[ 'scheme' ] . '://' . $query[ 'host' ];
			if (! empty( $query[ 'port' ] ) ) {
				$left_part_uri .= ':' . $query[ 'port' ]; 
			}	
		}
		
		return $left_part_uri;
	}	
	
	/**
	 *  Authenticate user into Wordpress
	 *  @param $user_id
	 */ 
	public static function wp_cassify_auth_user_wordpress( $cas_user_id ) {
		
		if ( username_exists( $cas_user_id ) ) {
			$user = get_userdatabylogin( $cas_user_id );

			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID );
			
			do_action( 'wp_login', $user_login );
		}
		else {
			die( 'User account does not exists in Wordpress database !');
		}
	}
	
	/**
	 * Create wordpress user account if not exist.
	 * @param string $cas_user_id
	 * @param string $cas_user_email_attribute_value
	 * @return object $wp_user_id
	 */ 
	public static function wp_cassify_create_wordpress_user( $cas_user_id, $cas_user_email_attribute_value ) {

		$user_email = NULL;
		$wp_user_id = 0;

		if (! username_exists( $cas_user_id ) ) {
			if ( (! empty( $cas_user_email_attribute_value ) ) && ( email_exists( $cas_user_email_attribute_value ) == FALSE ) ) {
				$user_email = $cas_user_email_attribute_value;
			}
			
			$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
			$wp_user_id = wp_create_user( $cas_user_id, $random_password, $user_email );
		}
		
		return $wp_user_id;
	}
	
	/**
	 * Set role to an existing Wordpress user
	 * @param string $wordpress_user_login
	 * @param string $role_key
	 * @return bool $wp_user_role_updated
	 */ 
	public static function wp_cassify_set_role_to_wordpress_user( $wordpress_user_login, $role_key ) {
		
		$wp_user_role_updated = FALSE;
		$wp_user = get_user_by( 'login', $wordpress_user_login );
		
		if ( $wp_user != FALSE ) {
			$wp_user->set_role( $role_key );
			$wp_user_role_updated = TRUE;
		}

		return $wp_user_role_updated;
	}
	
    /**
     * Return array with Wordpress user roles.
     * @return array $wordpress_roles;
     */
    public static function wp_cassify_get_wordpress_roles_names() {
        
		$wp_roles = new \WP_Roles();
		$wordpress_roles = $wp_roles->get_names();
		
		return $wordpress_roles;
    }
    	
	/**
	 * Process http redirection.
	 * @param string redirect_url
	 */ 
	public static function wp_cassify_redirect_url( $redirect_url ) {
		
		// Perform redirection only if url is valid.
		if ( filter_var( $redirect_url, FILTER_VALIDATE_URL ) ) {
			wp_redirect( $redirect_url ); 
			exit;
		}
		else {
			die( 'Redirect URL is not valid !');
		}	
	}
        
    /**
     * Get plugin option according to activation plugin level
     * @param bool $network_activated
     * @param string $option_name
     * @return $wp_cassify_plugin_option;
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
}

?>
