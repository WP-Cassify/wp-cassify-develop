<?php
namespace wp_cassify;

class WP_Cassify_Shortcodes {
	
	public $wp_cassify_network_activated;
	
	public $wp_cassify_base_url;
	public $wp_cassify_default_redirect_parameter_name;
	public $wp_cassify_default_service_service_parameter_name;
	public $wp_cassify_default_wordpress_blog_http_port;
	public $wp_cassify_default_wordpress_blog_https_port;
	public $wp_cassify_default_login_servlet;
	public $wp_cassify_default_logout_servlet;
	
	/**
	 * Constructor
	 */
	public function __construct() {
	}
	
	/**
	 * Initialize the plugin with parameters
	 * 
	 * param string $wp_cassify_network_activated
	 * param string $wp_cassify_default_redirect_parameter_name
	 * param string $wp_cassify_default_service_service_parameter_name
	 * param string $wp_cassify_default_wordpress_blog_http_port
	 * param string $wp_cassify_default_wordpress_blog_https_port
	 * param string $wp_cassify_default_login_servlet
	 * param string $wp_cassify_default_logout_servlet
	 */ 
	public function init_parameters(
		$wp_cassify_network_activated,
		$wp_cassify_default_redirect_parameter_name,
		$wp_cassify_default_service_service_parameter_name,
		$wp_cassify_default_wordpress_blog_http_port,
		$wp_cassify_default_wordpress_blog_https_port,
		$wp_cassify_default_login_servlet,
		$wp_cassify_default_logout_servlet
	) {
		
		$this->wp_cassify_network_activated = $wp_cassify_network_activated;
		$this->wp_cassify_default_redirect_parameter_name = $wp_cassify_default_redirect_parameter_name;
		$this->wp_cassify_default_service_service_parameter_name = $wp_cassify_default_service_service_parameter_name;	
		$this->wp_cassify_default_wordpress_blog_http_port = $wp_cassify_default_wordpress_blog_http_port;
		$this->wp_cassify_default_wordpress_blog_https_port = $wp_cassify_default_wordpress_blog_https_port;
		$this->wp_cassify_default_login_servlet = $wp_cassify_default_login_servlet;
		$this->wp_cassify_default_logout_servlet = $wp_cassify_default_logout_servlet;

		$this->wp_cassify_base_url = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_base_url' );
		
		// Add shortcodes
		add_shortcode( 'wp_cassify_login_with_redirect', array( $this , 'wp_cassify_login_with_redirect' ) ); 
		add_shortcode( 'wp_cassify_autologin_on_public_page', array( $this , 'wp_cassify_autologin_on_public_page' ) ); 
	}

	/**
	 * Function wich create shortcode like this [wp_cassify_login_with_redirect service_redirect_url='http://www.dev.lan/sandbox01/mypage/']
	 * to generate link like this https://cas.dev.lan/cas/login?service=http://www.dev.lan/sandbox01/mypage/
	 * @param	array	$shortcode_attributes		Attributes of wordpress shortcode
	 * @return	string	$login_with_redirect_link
	 */ 
	public function wp_cassify_login_with_redirect( $shortcode_attributes ) {

		$login_with_redirect_link = "";

		$attributes = shortcode_atts( 
			array(
				'service_redirect_url' => 'url of service callback url'
			), 
			$shortcode_attributes 
		);
		
		$login_with_redirect_link = $this->wp_cassify_base_url . 
			$this->wp_cassify_default_login_servlet . '?' .
			$this->wp_cassify_default_service_service_parameter_name . '=' .
			$attributes[ 'service_redirect_url' ];
		
		return $login_with_redirect_link;
	}	
	
	/**
	 * If you're already authenticated by CAS Server, it performs auto-login on Wordpress non-authenticated pages.
	 * [wp_cassify_autologin_on_public_page='http://www.dev.lan/sandbox01/mypage/']
	 * @param	array	$shortcode_attributes		Attributes of wordpress shortcode
	 */ 
	public function wp_cassify_autologin_on_public_page() {

		
		$current_url = ( @$_SERVER[ 'HTTPS' ] == 'on' ) ? 'https://' : 'http://';
		
		// If cassified application is hosted behind reverse proxy.
		if ( isset( $_SERVER[ 'HTTP_X_FORWARDED_HOST' ] ) ) {
			$current_url .= $_SERVER[ 'HTTP_X_FORWARDED_HOST' ];
		}
		else {
			$current_url .= $_SERVER[ 'SERVER_NAME' ];
		}

		$current_url .= $_SERVER[ 'REQUEST_URI' ];		
		
		error_log( '============================================' );
		error_log( 'REFERRER : ' . $_SERVER['HTTP_REFERER']  );
		error_log( '============================================' );
		
		error_log( '============================================' );
		error_log( 'CURRENT URL : ' . $current_url  );
		error_log( '============================================' );


		if ( isset( $GLOBALS['wp-cassify'] ) ) {
			if(! isset( $SESSION['wp_cassify_autologin'] ) ) {
				$SESSION['wp_cassify_autologin'] = true;
				$GLOBALS['wp-cassify']->wp_cassify_check_authentication();
				
				
				
				error_log( 'ok1');
			}
			else {
				error_log( 'ok2');	
			}
		}
		else {
			die( 'wp-cassify plugin not loaded !' );
		}

	}
}

?>