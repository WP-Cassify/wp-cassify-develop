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
		add_shortcode( 'wp_cassify_logout_with_redirect', array( $this , 'wp_cassify_logout_with_redirect' ) ); 
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
	 * Function wich create shortcode like this [wp_cassify_logout_with_redirect service_redirect_url='http://www.dev.lan/sandbox01/mypage/']
	 * to generate link like this https://cas.dev.lan/cas/logout?service=http://www.dev.lan/sandbox01/mypage/
	 * @param	array	$shortcode_attributes		Attributes of wordpress shortcode
	 * @return	string	$logout_with_redirect_link
	 */ 
	public function wp_cassify_logout_with_redirect( $shortcode_attributes ) {

		$logout_with_redirect_link = "";

		$attributes = shortcode_atts( 
			array(
				'service_redirect_url' => 'url of service callback url'
			), 
			$shortcode_attributes 
		);
		
		// $logout_with_redirect_link = $this->wp_cassify_base_url . 
		// 	$this->wp_cassify_default_logout_servlet . '?' .
		// 	$this->wp_cassify_default_service_service_parameter_name . '=' .
		// 	$attributes[ 'service_redirect_url' ];

		$logout_with_redirect_link = $this->wp_cassify_base_url . 
		 	$this->wp_cassify_default_logout_servlet . '?' .
		 	$this->wp_cassify_default_service_service_parameter_name . '=' .
		 	 wp_logout_url( $attributes[ 'service_redirect_url' ] );

		//$logout_with_redirect_link = wp_logout_url( $attributes[ 'service_redirect_url' ] );		
		
		return $logout_with_redirect_link;
	}	
}

?>
