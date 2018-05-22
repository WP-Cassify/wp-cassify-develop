<?php

namespace wp_cassify;

class WP_Cassify_Admin_Page {

	public $wp_cassify_plugin_datas;
	public $wp_cassify_plugin_directory;
    public $wp_cassify_network_activated;
	public $wp_cassify_plugin_options_list;
	public $wp_cassify_default_login_servlet;
	public $wp_cassify_default_logout_servlet;
	public $wp_cassify_default_service_validate_servlet;
	public $wp_cassify_default_ssl_cipher_values = array();
	public $wp_cassify_default_ssl_check_certificate;
	public $wp_cassify_default_xpath_query_to_extact_cas_user;
	public $wp_cassify_default_xpath_query_to_extact_cas_attributes;
	public $wp_cassify_default_notifications_options;
	public $wp_cassify_default_expirations_options;
	public $wp_cassify_default_allow_deny_order;
	public $wp_cassify_wordpress_user_meta_list;
        
    private $wp_cassify_admin_page_slug;
    private $wp_cassify_multisite_admin_page_slug;
    
    private $wp_cassify_admin_page_hook;
        
	/**
	 * Constructor
	 */
	public function __construct() {
	}
	
	public function init_parameters( 
		$wp_cassify_plugin_datas,
		$wp_cassify_plugin_directory,
        $wp_cassify_network_activated,
		$wp_cassify_plugin_options_list,
		$wp_cassify_default_protocol_version_values,
		$wp_cassify_default_login_servlet,
		$wp_cassify_default_logout_servlet,
		$wp_cassify_default_service_validate_servlet,
		$wp_cassify_default_ssl_cipher_values,
		$wp_cassify_default_ssl_check_certificate,
		$wp_cassify_default_xpath_query_to_extact_cas_user,
		$wp_cassify_default_xpath_query_to_extact_cas_attributes,
		$wp_cassify_default_notifications_options,
		$wp_cassify_default_expirations_options,
		$wp_cassify_default_allow_deny_order,
		$wp_cassify_wordpress_user_meta_list
	) {
		$this->wp_cassify_plugin_datas = $wp_cassify_plugin_datas;
		$this->wp_cassify_plugin_directory = $wp_cassify_plugin_directory;
      	$this->wp_cassify_network_activated = $wp_cassify_network_activated;
		$this->wp_cassify_plugin_options_list = $wp_cassify_plugin_options_list;
		$this->wp_cassify_default_protocol_version_values = $wp_cassify_default_protocol_version_values;
		$this->wp_cassify_default_ssl_check_certificate = $wp_cassify_default_ssl_check_certificate;
		$this->wp_cassify_default_login_servlet = $wp_cassify_default_login_servlet;
		$this->wp_cassify_default_logout_servlet = $wp_cassify_default_logout_servlet;
		$this->wp_cassify_default_service_validate_servlet = $wp_cassify_default_service_validate_servlet;
		$this->wp_cassify_default_ssl_cipher_values = $wp_cassify_default_ssl_cipher_values;
		$this->wp_cassify_default_xpath_query_to_extact_cas_user = $wp_cassify_default_xpath_query_to_extact_cas_user;
		$this->wp_cassify_default_xpath_query_to_extact_cas_attributes = $wp_cassify_default_xpath_query_to_extact_cas_attributes;
		$this->wp_cassify_default_notifications_options = $wp_cassify_default_notifications_options;
		$this->wp_cassify_default_expirations_options = $wp_cassify_default_expirations_options;
		$this->wp_cassify_default_allow_deny_order = $wp_cassify_default_allow_deny_order;
		$this->wp_cassify_wordpress_user_meta_list = $wp_cassify_wordpress_user_meta_list;
		
		$this->wp_cassify_admin_page_slug = 'options-general.php';
    	$this->wp_cassify_multisite_admin_page_slug = 'settings.php';
		
        // Add the actions
        if ( $this->wp_cassify_network_activated ) {
            add_action( 'network_admin_menu', array( $this , 'wp_cassify_create_network_menu' ) );
        }
        else {
            add_action( 'admin_menu', array( $this , 'wp_cassify_create_menu' ) );
        }
	}	

	/**
	 *  Multisite and single-site add_action statements
	 */                 
	 public function wp_cassify_add_admin_actions() {
	 	
		// Add javascript needed by metaboxes
		add_action( 'admin_init', array( $this , 'wp_cassify_add_metaboxes_js' ) );			 
		 
		// Call export plugin configurations settings function
		add_action( 'load-'. $this->wp_cassify_admin_page_hook, array( $this , 'wp_cassify_export_configuration_options_settings' ) );
                
                // Call import plugin configurations settings function
		add_action( 'load-'. $this->wp_cassify_admin_page_hook, array( $this , 'wp_cassify_import_configuration_options_settings' ) );
		 
		// Register differents metaboxes on admin screen.
		add_action( 'load-'. $this->wp_cassify_admin_page_hook, array( $this, 'wp_cassify_register_metaboxes' ) );  
		
		// Add custom javascript functions specific to this plugin
		add_action( 'load-'. $this->wp_cassify_admin_page_hook, array( $this , 'wp_cassify_add_custom_js' ) );
	}
	
	/**
	 *  Add admin menu options
	 */ 
	public function wp_cassify_create_menu() {

		$this->wp_cassify_admin_page_hook = add_options_page( $this->wp_cassify_plugin_datas['Name'] . ' Options', 
			$this->wp_cassify_plugin_datas['Name'], 
			'manage_options', 
			'wp-cassify.php' , 
			array( $this, 'wp_cassify_options' )
		 );
		 
		// Load common actions to single and multi-sites configurations
		$this->wp_cassify_add_admin_actions();
	}
	
	/**
	 *  Add admin menu options if plugin is activate over the network
	 */         
	public function wp_cassify_create_network_menu() {

		$this->wp_cassify_admin_page_hook = add_submenu_page( 
            'settings.php',
            $this->wp_cassify_plugin_datas['Name'] . ' Options', 
			$this->wp_cassify_plugin_datas['Name'], 
			'manage_options', 
			'wp-cassify.php' , 
			array( $this, 'wp_cassify_options' )
		 );

		// Call register settings function
		add_action( 'admin_init', array( $this , 'wp_cassify_register_plugin_settings' ) );

		// Call register settings function
		add_action( 'admin_init', array( $this , 'wp_cassify_register_plugin_settings' ) );
		
		// Load common actions to single and multi-sites configurations
		$this->wp_cassify_add_admin_actions();
	}
	
	/**
	 * Add javascript needed by metaboxes
	 */ 
	 public function wp_cassify_add_metaboxes_js() {
	 	
		// Ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'wp-lists' );
		wp_enqueue_script( 'postbox' );	 	
	 }
	
	/**
	 * Add js functions to admin option page.
	 */ 
	public function wp_cassify_add_custom_js() {	

	   	wp_register_script(
			'wp_cassify_custom_js',	
			$this->wp_cassify_plugin_directory . 'js/functions.js',
			array( 'jquery' ),
			'1.0',
			TRUE
		);
        
        wp_localize_script( 'wp_cassify_custom_js', 'wp_cassify_screen_data', $this->wp_cassify_get_screen_data() );
        wp_enqueue_script( 'wp_cassify_custom_js' );
        
        // Add jQuery Datepicker control
        wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ) );
		wp_enqueue_style( 'jquery-ui-style', $this->wp_cassify_plugin_directory . 'css/jquery-ui.min.css' );
	}	
	
	/**
	 * Get the last form submit button id clicked by user.
	 * @return $screen_data
	 */ 
	public function wp_cassify_get_screen_data() {
		
		$screen_data = array();
		
		if ( ( isset( $_POST[ 'action' ] ) ) && ( $_POST[ 'action' ] == 'update' ) ) {    

			// Check security tocken
			if (! wp_verify_nonce( $_POST[ 'wp_cassify_admin_form' ], 'admin_form' ) ) {
				die( 'Security Check !' );
			}
			
			$posted_values = array_values( $_POST );

			if ( in_array( 'Save options', $posted_values ) ) {
				$screen_data[ 'scrollToId' ] = sanitize_text_field( array_search( 'Save options',  $_POST ) );
			}
			
			if ( in_array( 'Send test message', $posted_values ) ) {	
				$screen_data[ 'scrollToId' ] = sanitize_text_field( array_search( 'Send test message',  $_POST ) );
			}			
		}
		
		return $screen_data;
	}	
	
	/**
	 * Register the differents metaboxes availables on admin screen.
	 */ 
	public function wp_cassify_register_metaboxes() {

		add_meta_box( 
			'wp_cassify_metabox_general_settings', 
			'General Settings', 
			array( $this, 'wp_cassify_add_metabox_general_settings' ), 
			$this->wp_cassify_admin_page_hook, 
			'normal', 
			'high'
		);
		
		add_meta_box( 
			'wp_cassify_metabox_urls_settings', 
			'Urls Settings', 
			array( $this, 'wp_cassify_add_metabox_urls_settings' ), 
			$this->wp_cassify_admin_page_hook, 
			'normal', 
			'high'
		);	

		add_meta_box( 
			'wp_cassify_metabox_attributes_extraction_settings', 
			'Attributes Extraction Settings', 
			array( $this, 'wp_cassify_add_metabox_attributes_extraction_settings' ), 
			$this->wp_cassify_admin_page_hook, 
			'normal', 
			'high'
		);	

		add_meta_box( 
			'wp_cassify_metabox_authorization_rules_settings', 
			'Authorization Rules Settings', 
			array( $this, 'wp_cassify_add_metabox_authorization_rules_settings' ), 
			$this->wp_cassify_admin_page_hook, 
			'normal', 
			'high'
		);

		add_meta_box( 
			'wp_cassify_metabox_users_roles_settings', 
			'Users Roles Settings', 
			array( $this, 'wp_cassify_add_metabox_users_roles_settings' ), 
			$this->wp_cassify_admin_page_hook, 
			'normal', 
			'high'
		);

		add_meta_box( 
			'wp_cassify_metabox_users_attributes_synchronization_settings', 
			'Users Attributes Synchronization Settings', 
			array( $this, 'wp_cassify_add_metabox_users_attributes_synchronization_settings' ), 
			$this->wp_cassify_admin_page_hook, 
			'normal', 
			'high'
		);
		
		add_meta_box( 
			'wp_cassify_metabox_notifications_settings', 
			'Notifications Settings', 
			array( $this, 'wp_cassify_add_metabox_notifications_settings' ), 
			$this->wp_cassify_admin_page_hook, 
			'normal', 
			'high'
		);	
		
		add_meta_box( 
			'wp_cassify_metabox_notifications_rules_settings', 
			'Notifications Rules Settings', 
			array( $this, 'wp_cassify_add_metabox_notifications_rules_settings' ), 
			$this->wp_cassify_admin_page_hook, 
			'normal', 
			'high'
		);	
		
		add_meta_box( 
			'wp_cassify_metabox_expirations_rules_settings', 
			'Expirations Rules Settings', 
			array( $this, 'wp_cassify_add_metabox_expirations_rules_settings' ), 
			$this->wp_cassify_admin_page_hook, 
			'normal', 
			'high'
		);	
		
		add_meta_box( 
			'wp_cassify_metabox_debug_settings', 
			'Debug Settings', 
			array( $this, 'wp_cassify_add_metabox_debug_settings' ), 
			$this->wp_cassify_admin_page_hook, 
			'normal', 
			'high'
		);			
		
		add_meta_box( 
			'wp_cassify_metabox_backup_restore_configuration_settings', 
			'Backup / Restore configuration settings', 
			array( $this, 'wp_cassify_add_metabox_backup_restore_configuration_settings' ), 
			$this->wp_cassify_admin_page_hook, 
			'normal', 
			'high'
		);		
		
		// Side boxes
		add_meta_box( 
			'wp_cassify_metabox_plugin_brand', 
			'WP Cassify', 
			array( $this, 'wp_cassify_add_metabox_plugin_brand' ), 
			$this->wp_cassify_admin_page_hook, 
			'side', 
			'high'
		);	
		
		add_meta_box( 
			'wp_cassify_metabox_online_documentation', 
			'Online Documentation', 
			array( $this, 'wp_cassify_add_metabox_online_documentation' ), 
			$this->wp_cassify_admin_page_hook, 
			'side', 
			'high'
		);		
	}
	
	/**
	 * Display html output for metabox Plugin Brand.
	 */ 
	public function wp_cassify_add_metabox_plugin_brand() {
?>
	<div class="wp_cassify_brand"><img src="<?php echo $this->wp_cassify_plugin_directory . 'images/wp-cassify-logo.png'; ?>" /></div>
<?php					
	}	
	
	/**
	 * Display html output for metabox Online documentation.
	 */ 
	public function wp_cassify_add_metabox_online_documentation() {
?>
		<ul>
			<li><a href="https://wpcassify.wordpress.com/wp-cassify-plugin-documentation/" target="_blank">Full options documentation</a></li>
			<li><a href="https://wpcassify.wordpress.com/getting-started/" target="_blank">Screencasts</a></li>
			<li><a href="https://wordpress.org/plugins/wp-cassify/faq/">FAQ</a></li>
			<li><a href="https://wordpress.org/support/plugin/wp-cassify">Support</a></li>
		</ul>
<?php					
	}
	
	/**
	 * Display html output for metabox General Settings.
	 */ 
	public function wp_cassify_add_metabox_general_settings() {
		
        $wp_cassify_protocol_version = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_protocol_version' );

        if (! empty( $wp_cassify_protocol_version ) ) {
            $wp_cassify_default_protocol_version_selected = $wp_cassify_protocol_version;
        }
        else {
            $wp_cassify_default_protocol_version_selected = $this->wp_cassify_default_protocol_version_values[ '3' ]; // Default version 3.
        }

        $is_disabled = FALSE;

        if ( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_disable_authentication' ) == 'disabled' ) {
            $is_disabled = TRUE;
        }
        else {
            $is_disabled = FALSE;
        }

        $create_user_if_not_exist = FALSE;	

        if ( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_create_user_if_not_exist' ) == 'create_user_if_not_exist' ) {
            $create_user_if_not_exist = TRUE;
        }
        else {
            $create_user_if_not_exist = FALSE;
        }
        
        $wp_cassify_ssl_cipher = WP_Cassify_Utils::wp_cassify_get_option( 
            $this->wp_cassify_network_activated, 
            'wp_cassify_ssl_cipher' 
        );

        if ( isset( $wp_cassify_ssl_cipher ) ) {
            $wp_cassify_ssl_cipher_selected = $wp_cassify_ssl_cipher;
        }
        else {
            $wp_cassify_ssl_cipher_selected = '0';
        }
        
        $is_ssl_check_certificate_enabled = FALSE;

        if ( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_ssl_check_certificate' ) == 'enabled' ) {
            $is_ssl_check_certificate_enabled = TRUE;
        }
        else {
            $is_ssl_check_certificate_enabled = FALSE;
        }		
?>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row"><label for="wp_cassify_base_url">CAS Server base url</label></th>
				<td>
					<input type="text" id="wp_cassify_base_url" name="wp_cassify_base_url" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_base_url' ) ); ?>" size="40" class="regular-text post_form" />
					<br /><span class="description">Example : https://you-cas-server/cas/ </span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">CAS Version protocol</th>
				<td>
					<select id="wp_cassify_protocol_version" name="wp_cassify_protocol_version" class="post_form">
						<?php foreach ( $this->wp_cassify_default_protocol_version_values as $wp_cassify_default_protocol_version_key => $wp_cassify_default_protocol_version_value ) : ?>
							<?php if ( $wp_cassify_default_protocol_version_value == $wp_cassify_default_protocol_version_selected ) : ?>
								<option value="<?php echo $wp_cassify_default_protocol_version_key; ?>" selected><?php echo $wp_cassify_default_protocol_version_value; ?></option>
							<?php else : ?>
								<option value="<?php echo $wp_cassify_default_protocol_version_key; ?>"><?php echo $wp_cassify_default_protocol_version_value; ?></option>
							<?php endif; ?>						
						<?php endforeach; ?>
					</select>
					<br /><span class="description">Default value : <?php echo $this->wp_cassify_default_protocol_version_values[ '3' ]; ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Disable CAS Authentication</th>
				<td>
					<?php if ( $is_disabled ) : ?>
						<input type="checkbox" id="wp_cassify_disable_authentication" name="wp_cassify_disable_authentication" class="post_form" value="disabled" checked />
					<?php else : ?>
						<input type="checkbox" id="wp_cassify_disable_authentication" name="wp_cassify_disable_authentication" class="post_form" value="disabled" />
					<?php endif; ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Create user if not exist</th>
				<?php if ( $create_user_if_not_exist ) : ?>
				<td><input type="checkbox" id="wp_cassify_create_user_if_not_exist" name="wp_cassify_create_user_if_not_exist" class="post_form" value="create_user_if_not_exist" checked /><br /><span class="description">Create wordpress user account if not exist.</span></td>
				<?php else : ?>
				<td><input type="checkbox" id="wp_cassify_create_user_if_not_exist" name="wp_cassify_create_user_if_not_exist" class="post_form" value="create_user_if_not_exist" /><br /><span class="description">Create wordpress user account if not exist.</span></td>
				<?php endif; ?>
			</tr>
			<tr valign="top">
				<th scope="row">SSL Cipher used for query CAS Server with HTTPS Webrequest to validate service ticket</th>
				<td>
					<select id="wp_cassify_ssl_cipher" name="wp_cassify_ssl_cipher" class="post_form">
						<?php foreach ( $this->wp_cassify_default_ssl_cipher_values as $cipher_id => $cipher_name ) : ?>
							<?php if ( $cipher_id == $wp_cassify_ssl_cipher_selected ) : ?>
								<option value="<?php echo $cipher_id; ?>" selected><?php echo $cipher_name; ?></option>
							<?php else : ?>
								<option value="<?php echo $cipher_id; ?>"><?php echo $cipher_name; ?></option>
							<?php endif; ?>						
						<?php endforeach; ?>
					</select>
					<br /><span class="description">Default value : <?php echo $this->wp_cassify_default_ssl_cipher_values[ '0' ]; ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Enable SSL Certificate Check</th>
				<td>
					<?php if ( $is_ssl_check_certificate_enabled ) : ?>
						<input type="checkbox" name="wp_cassify_ssl_check_certificate" class="post_form" value="enabled" checked />
					<?php else : ?>
						<input type="checkbox" name="wp_cassify_ssl_check_certificate" class="post_form" value="enabled" />
					<?php endif; ?>
				</td>
			</tr>		
		</table>
		<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options_general_settings', FALSE, array( 'id' => 'wp_cassify_save_options_general_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?>	
<?php		
	}
	
	/**
	 * Display html output for metabox Url Settings.
	 */ 	
	public function wp_cassify_add_metabox_urls_settings() {
?>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row"><label for="wp_cassify_redirect_url_after_logout">Service logout redirect url</label></th>
				<td>
					<input type="text" id="wp_cassify_redirect_url_after_logout" name="wp_cassify_redirect_url_after_logout" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_after_logout' ) ); ?>" size="40" class="regular-text post_form" />
					<br /><span class="description">The blog home url is used when this option is not set .Url where the CAS Server redirect after logout. CAS Server must be configured correctly (see : followServiceRedirects option in JASIG documentation) </span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="wp_cassify_override_service_url">Override service callback url</label></th>
				<td>
					<input type="text" id="wp_cassify_override_service_url" name="wp_cassify_override_service_url" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_override_service_url' ) ); ?>" size="40" class="regular-text post_form" />
					<br /><span class="description">In multisite context, use this option if you want override service url parameter before beeing redirected to CAS. For example : https://my-current-domain.org?site={WP_CASSIFY_CURRENT_SERVICE_URL}. 
						Then when you try to access to https://blog1.my-current-domain.org/wp-admin/, you will be redirected to https://my-cas-server.org/cas/login?service=https://my-current-domain.org?site=https://blog1.my-current-domain.org/wp-admin/
					</span>
				</td>
			</tr>			
			<tr valign="top">
				<td colspan="2">Update servlets name only if you have customized your CAS Server.</td>
			</tr>
			<tr valign="top">
				<th scope="row">Name of the login servlet (Default : <?php echo $this->wp_cassify_default_login_servlet; ?>)</th>
				<td><input type="text" id="wp_cassify_login_servlet" name="wp_cassify_login_servlet" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_login_servlet' ) ); ?>" size="40" class="regular-text post_form" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Name of the logout servlet (Default : <?php echo $this->wp_cassify_default_logout_servlet; ?>)</th>
				<td><input type="text" id="wp_cassify_logout_servlet" name="wp_cassify_logout_servlet" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_logout_servlet' ) ); ?>" size="40" class="regular-text post_form" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Name of the service validate servlet (Default : <?php echo $this->wp_cassify_default_service_validate_servlet; ?>)</th>
				<td><input type="text" id="wp_cassify_service_validate_servlet" name="wp_cassify_service_validate_servlet" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_service_validate_servlet' ) ); ?>" size="40" class="regular-text post_form" /></td>
			</tr>
		</table>
		<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options_urls_settings', FALSE, array( 'id' => 'wp_cassify_save_options_urls_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?>	
<?php
	}
	
	/**
	 * Display html output for metabox Attribute extraction Settings.
	 */ 	
	public function wp_cassify_add_metabox_attributes_extraction_settings() {
?>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row">Xpath query used to extract cas user id during parsing</th>
				<td>
					<input type="text" id="wp_cassify_xpath_query_to_extact_cas_user" name="wp_cassify_xpath_query_to_extact_cas_user" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_xpath_query_to_extact_cas_user' ) ); ?>" size="40" class="regular-text post_form" />
					<br /><span class="description">(Default : <?php echo $this->wp_cassify_default_xpath_query_to_extact_cas_user; ?>)</span>
				</td>
			</tr>	
			<tr valign="top">
				<th scope="row">Xpath query used to extract cas user attributes during parsing</th>
				<td>
					<input type="text" id="wp_cassify_xpath_query_to_extact_cas_attributes" name="wp_cassify_xpath_query_to_extact_cas_attributes" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_xpath_query_to_extact_cas_attributes' ) ); ?>" size="40" class="regular-text post_form" />
					<br /><span class="description">(Default : <?php echo $this->wp_cassify_default_xpath_query_to_extact_cas_attributes; ?>)</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Cas user attributes you want to populate into session.</th>
				<td>
					<input type="text" id="wp_cassify_attributes_list" name="wp_cassify_attributes_list" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_attributes_list' ) ); ?>" size="40" class="regular-text post_form" />
					<br /><span class="description">You must write attribute name separated by comma like this : attribute_1,attribute_2,attribute_3</span>
				</td>
			</tr>
		</table>
		<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options_attributes_extraction_settings', FALSE, array( 'id' => 'wp_cassify_save_options_extraction_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?>
<?php
	}
	
	/**
	 * Display html output for metabox Authorization rules Settings.
	 */ 	
	public function wp_cassify_add_metabox_authorization_rules_settings() {
		
 		$wp_cassify_allow_deny_order = WP_Cassify_Utils::wp_cassify_get_option( 
            $this->wp_cassify_network_activated, 
            'wp_cassify_allow_deny_order' 
        );

        if (! empty( $wp_cassify_allow_deny_order ) ) {
            $wp_cassify_allow_deny_order_selected = $wp_cassify_allow_deny_order;
        }
        else {
            $wp_cassify_allow_deny_order_selected = $this->wp_cassify_default_allow_deny_order;
        }

        $wp_cassify_autorization_rules = unserialize( 
            WP_Cassify_Utils::wp_cassify_get_option( 
                    $this->wp_cassify_network_activated, 
                    'wp_cassify_autorization_rules' 
            ) 
        );

        if ( ( is_array( $wp_cassify_autorization_rules ) ) && ( count( $wp_cassify_autorization_rules ) > 0 ) ) {
            foreach ( $wp_cassify_autorization_rules as $rule_key => $rule_value ) {
                $wp_cassify_autorization_rules_selected[ $rule_key ] = stripslashes( $rule_value );  
            }
        }
        else {
        	$wp_cassify_autorization_rules_selected = array();
        }		
?>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row">Build authorizaton rules</th>
				<td>
					<span class="description">Example rule syntax (Refer to plugin documentation) : (CAS{cas_user_id} -EQ "m.brown") -AND (CAS{email} -CONTAINS "my-university.fr")</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Order Allow/Deny</th>
				<td>
					<select name="wp_cassify_allow_deny_order" class="post_form">
						<?php foreach ( $this->wp_cassify_default_allow_deny_order as $allow_deny_order ) : ?>
							<?php if ( $allow_deny_order == $wp_cassify_allow_deny_order_selected ) : ?>
								<option value="<?php echo $allow_deny_order; ?>" selected><?php echo $allow_deny_order; ?></option>
							<?php else : ?>
								<option value="<?php echo $allow_deny_order; ?>"><?php echo $allow_deny_order; ?></option>
							<?php endif; ?>						
						<?php endforeach; ?>
					</select>
					<br /><span class="description">Default value : <?php echo $this->wp_cassify_default_allow_deny_order[0]; ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Autorization rules</th>
				<td>
					<select id="wp_cassify_rule_type" name="wp_cassify_rule_type">
						<option value="ALLOW">Allow</option>
						<option value="DENY">Deny</option>
					</select>					
					<input type="text" id="wp_cassify_autorization_rule" name="wp_cassify_autorization_rules" class="post_form" value="" size="68" class="regular-text" /><br />
					<select id="wp_cassify_autorization_rules" name="wp_cassify_autorization_rules[]" class="post_form" multiple="multiple" style="height:100px;width:590px" size="10">
					<?php if ( ( is_array( $wp_cassify_autorization_rules_selected )  ) && ( count( $wp_cassify_autorization_rules_selected ) > 0 ) ) : ?>
						<?php foreach ( $wp_cassify_autorization_rules_selected as $wp_cassify_autorization_rules_selected_key => $wp_cassify_autorization_rules_selected_value ) : ?>
							<?php echo "<option value='$wp_cassify_autorization_rules_selected_value'>$wp_cassify_autorization_rules_selected_value</option>"; ?>
						<?php endforeach; ?>
					<?php endif; ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<td></td>
				<td>
					<span class="description">Double click on rule in list to edit it.</span>
					<?php submit_button( 'Add Rule', 'secondary', 'wp_cassify_add_rule', FALSE, array( 'id' => 'wp_cassify_add_rule' ) ); ?>
					<?php submit_button( 'Remove Rule', 'secondary', 'wp_cassify_remove_rule', FALSE, array( 'id' => 'wp_cassify_remove_rule' ) ); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="wp_cassify_redirect_url_if_not_allowed">User not allowed redirect url</label></th>
				<td>
					<input type="text" id="wp_cassify_redirect_url_if_not_allowed" name="wp_cassify_redirect_url_if_not_allowed" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_if_not_allowed' ) ); ?>" size="40" class="regular-text post_form" />
					<br /><span class="description">Url where to redirect if user is not allowed to connect according to autorization rules below. If it's blog page, this page does not require authenticated access. The blog home url is used when this option is not set.</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="wp_cassify_redirect_url_white_list">White List URL(s)</label></th>
				<td>
					<input type="text" id="wp_cassify_redirect_url_white_list" name="wp_cassify_redirect_url_white_list" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_white_list' ) ); ?>" size="40" class="regular-text post_form" />
					<br /><span class="description">List of URL(s) that don't intercepted by CAS Authentication. Use ';' as URL separator.</span>
				</td>
			</tr>
		</table>
		<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options_authorization_rules_settings', FALSE, array( 'id' => 'wp_cassify_save_options_authorization_rules_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?>
<?php
	}
	
	/**
	 * Display html output for metabox Users roles Settings.
	 */ 	
	public function wp_cassify_add_metabox_users_roles_settings() {
		
        $wp_cassify_user_role_rules = unserialize( 
            WP_Cassify_Utils::wp_cassify_get_option( 
                    $this->wp_cassify_network_activated, 
                    'wp_cassify_user_role_rules' 
            ) 
        );

        if ( ( is_array( $wp_cassify_user_role_rules ) ) && ( count( $wp_cassify_user_role_rules ) > 0 ) ) {
            foreach ( $wp_cassify_user_role_rules as $rule_key => $rule_value ) {
                $wp_cassify_user_role_rules_selected[ $rule_key ] = stripslashes( $rule_value );  
            }
        }
        else {
        	$wp_cassify_user_role_rules_selected = array();
        }

        $wp_cassify_user_purge_user_roles_before_applying_rules = FALSE;

        if ( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_user_purge_user_roles_before_applying_rules' ) == 'disabled' ) {
            $wp_cassify_user_purge_user_roles_before_applying_rules = TRUE;
        }
        else {
            $wp_cassify_user_purge_user_roles_before_applying_rules = FALSE;
        }        
?>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row">Set Conditionnal Users Roles</th>
				<td>
					<span class="description">Example rule syntax (Refer to plugin documentation) : (CAS{cas_user_id} -EQ "m.brown") -AND (CAS{email} -CONTAINS "my-university.fr")</span>
				</td>
			</tr>				
			<tr valign="top">
				<th scope="row">Push defaults roles to connected user </th>
				<td>
					<select id="wp_cassify_default_user_roles" name="wp_cassify_default_user_roles" class="post_form">
					<?php $wp_cassify_wordpress_roles = WP_Cassify_Utils::wp_cassify_get_wordpress_roles_names(); ?>
					<?php foreach ( $wp_cassify_wordpress_roles as $wp_cassify_wordpress_role_key => $wp_cassify_wordpress_role_value ) : ?>
						<?php echo "<option value='$wp_cassify_wordpress_role_key'>$wp_cassify_wordpress_role_value</option>"; ?>
					<?php endforeach; ?>
					</select>
					<?php if ( $this->wp_cassify_network_activated ) : ?>
					<?php $blogs = wp_get_sites();?>	
					<select id="wp_cassify_user_role_blog_id" name="wp_cassify_user_role_blog_id" class="post_form">
						<?php echo '<option value="0">(0)&nbsp;ALL BLOGS</option>';	?>		
						<?php for( $i = 0; $i <= count( $blogs ) - 1; $i++ ) : ?>
							<?php echo '<option value="' . $blogs[ $i ][ 'blog_id' ] . '">(' . $blogs[ $i ][ 'blog_id' ] . ')&nbsp;' . $blogs[ $i ][ 'domain' ] . $blogs[ $i ][ 'path' ] . '</option>';	?>	
						<?php endfor; ?>							
					</select>
					<?php endif; ?>
					<input type="text" id="wp_cassify_user_role_rule" name="wp_cassify_user_role_rule" class="post_form" value="" size="60" class="regular-text" /><br />
					<br />
					<select id="wp_cassify_user_role_rules" name="wp_cassify_user_role_rules[]" class="post_form" multiple="multiple" style="height:100px;width:590px" size="10">
					<?php if ( ( is_array( $wp_cassify_user_role_rules_selected )  ) && ( count( $wp_cassify_user_role_rules_selected ) > 0 ) ) : ?>
						<?php foreach ( $wp_cassify_user_role_rules_selected as $wp_cassify_user_role_rules_selected_key => $wp_cassify_user_role_rules_selected_value ) : ?>
							<?php echo "<option value='$wp_cassify_user_role_rules_selected_value'>$wp_cassify_user_role_rules_selected_value</option>"; ?>
						<?php endforeach; ?>
					<?php endif; ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<td></td>
				<td>
					<span class="description">Double click on rule in list to edit it.</span>
					<?php submit_button( 'Add Conditional Rule Role', 'secondary', 'wp_cassify_add_user_role_rule', FALSE, array( 'id' => 'wp_cassify_add_user_role_rule' ) ); ?>
					<?php submit_button( 'Remove Conditional Rule Role', 'secondary', 'wp_cassify_remove_user_role_rule', FALSE, array( 'id' => 'wp_cassify_remove_user_role_rule' ) ); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Purge user roles before applying rules (syncing wordpress local roles with CAS user attributes).</th>
				<td>
				<?php if ( $wp_cassify_user_purge_user_roles_before_applying_rules ) : ?>
					<input type="checkbox" id="wp_cassify_user_purge_user_roles_before_applying_rules" name="wp_cassify_user_purge_user_roles_before_applying_rules" class="post_form" value="disabled" checked />
				<?php else : ?>
					<input type="checkbox" id="wp_cassify_user_purge_user_roles_before_applying_rules" name="wp_cassify_user_purge_user_roles_before_applying_rules" class="post_form" value="disabled" />
				<?php endif; ?>
				</td>
			</tr>			
		</table>
		<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options_users_roles_settings', FALSE, array( 'id' => 'wp_cassify_save_options_users_roles_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?>
<?php
	}	
	
	/**
	 * Display html output for metabox Users attributes synchronization Settings.
	 */ 	
	public function wp_cassify_add_metabox_users_attributes_synchronization_settings() {

        $wp_cassify_user_attributes_mapping_list = unserialize( 
            WP_Cassify_Utils::wp_cassify_get_option( 
                    $this->wp_cassify_network_activated, 
                    'wp_cassify_user_attributes_mapping_list' 
            ) 
        );
		
		$wp_cassify_user_attributes_mapping_list_selected = array();
        if ( ( is_array( $wp_cassify_user_attributes_mapping_list ) ) && ( count( $wp_cassify_user_attributes_mapping_list ) > 0 ) ) {
            foreach ( $wp_cassify_user_attributes_mapping_list as $wp_cassify_user_attributes_mapping_key => $wp_cassify_user_attributes_mapping_value ) {
                $wp_cassify_user_attributes_mapping_list_selected[ $wp_cassify_user_attributes_mapping_value ] = $wp_cassify_user_attributes_mapping_value;  
            }
        }
        else {
        	$wp_cassify_user_attributes_mapping_list_selected = array();
        }				
?>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row" colspan="2">Synchronize Wordpress User metas with CAS User attributes </th>
			</tr>
			<tr valign="top">
				<th scope="row">Wordpress User Meta</th>
				<td>
					<select id="wp_cassify_wordpress_user_meta_list" name="wp_cassify_wordpress_user_meta_list" class="post_form"> ?>
					<?php foreach ( $this->wp_cassify_wordpress_user_meta_list as $wp_cassify_wordpress_user_meta ) : ?>
						<?php echo "<option value='$wp_cassify_wordpress_user_meta'>$wp_cassify_wordpress_user_meta</option>"; ?>
					<?php endforeach; ?>
					</select>
					<input type="text" id="wp_cassify_custom_user_meta" name="wp_cassify_custom_user_meta" class="post_form" value="" size="40" class="regular-text" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">CAS User Attribute</th>
				<td>
					<input type="text" id="wp_cassify_cas_user_attribute" name="wp_cassify_cas_user_attribute" class="post_form" value="" size="40" class="regular-text" />
				</td>
			</tr>
			<tr valign="top">
				<td>
				</td>
				<td>
					<select id="wp_cassify_user_attributes_mapping_list" name="wp_cassify_user_attributes_mapping_list[]" class="post_form" multiple="multiple" style="height:100px;width:590px" size="10">
						<?php if ( ( is_array( $wp_cassify_user_attributes_mapping_list_selected )  ) && ( count( $wp_cassify_user_attributes_mapping_list_selected ) > 0 ) ) : ?>
							<?php foreach ( $wp_cassify_user_attributes_mapping_list_selected as $wp_cassify_user_attributes_mapping_list_selected_key => $wp_cassify_user_attributes_mapping_list_selected_value ) : ?>
								<?php echo "<option value='$wp_cassify_user_attributes_mapping_list_selected_value'>$wp_cassify_user_attributes_mapping_list_selected_value</option>"; ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<td></td>
				<td>
					<span class="description">Double click on rule in list to edit it.</span>
					<?php submit_button( 'Add Attribute Mapping', 'secondary', 'wp_cassify_add_user_attribute_mapping', FALSE, array( 'id' => 'wp_cassify_add_user_attribute_mapping' ) ); ?>
					<?php submit_button( 'Remove Attribute Mapping', 'secondary', 'wp_cassify_remove_user_attribute_mapping', FALSE, array( 'id' => 'wp_cassify_remove_user_attribute_mapping' ) ); ?>
				</td>
			</tr>
		</table>
		<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options_users_attributes_synchronization_settings', FALSE, array( 'id' => 'wp_cassify_save_options_users_attributes_synchronization_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?>
<?php
	}
	
	/**
	 * Display html output for metabox Notifications Settings.
	 */ 	
	public function wp_cassify_add_metabox_notifications_settings() {

        $wp_cassify_notifications_smtp_auth_enabled = FALSE;

        if ( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_auth' ) == 'enabled' ) {
            $wp_cassify_notifications_smtp_auth_enabled = TRUE;
        }
        else {
            $wp_cassify_notifications_smtp_auth_enabled = FALSE;
        }

        $wp_cassify_notifications_smtp_port_selected = '';
        $wp_cassify_notifications_smtp_port = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_port' );

        if (! empty( $wp_cassify_notifications_smtp_port ) ) {
            $wp_cassify_notifications_smtp_port_selected = $wp_cassify_notifications_smtp_port;
        }
        
        $wp_cassify_notifications_priority_selected = '3';
        $wp_cassify_notifications_priority = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_priority' );

        if (! empty( $wp_cassify_notifications_priority ) ) {
            $wp_cassify_notifications_priority_selected = $wp_cassify_notifications_priority;
        }
        
        $wp_cassify_notifications_encryption_type_selected = $this->wp_cassify_default_notifications_options[ 'wp_cassify_default_notifications_encryption_type' ][ 'tls' ];
        $wp_cassify_notifications_encryption_type = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_encryption_type' );
        
        if (! empty( $wp_cassify_notifications_encryption_type ) ) {
            $wp_cassify_notifications_encryption_type_selected = $wp_cassify_notifications_encryption_type;
        }    
?>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row">SMTP Host</th>
				<td>
					<input type="text" id="wp_cassify_notifications_smtp_host" name="wp_cassify_notifications_smtp_host" class="post_form regular-text" size="40" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_host' ) ); ?>" />
					<br /><span class="description">IP or FQDN</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">SMTP port</th>
				<td>
					<select id="wp_cassify_notifications_smtp_port" name="wp_cassify_notifications_smtp_port" class="post_form">
						<?php foreach ( $this->wp_cassify_default_notifications_options[ 'wp_cassify_default_notifications_smtp_port' ] as $wp_cassify_default_notifications_smtp_port_key => $wp_cassify_default_notifications_smtp_port_value ) : ?>
							<?php if ( $wp_cassify_default_notifications_smtp_port_value == $wp_cassify_notifications_smtp_port_selected ) : ?>
								<option value="<?php echo $wp_cassify_default_notifications_smtp_port_key; ?>" selected><?php echo $wp_cassify_default_notifications_smtp_port_value; ?></option>
							<?php else : ?>
								<option value="<?php echo $wp_cassify_default_notifications_smtp_port_key; ?>"><?php echo $wp_cassify_default_notifications_smtp_port_value; ?></option>
							<?php endif; ?>						
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">SMTP Authentication</th>
				<td>
					<?php if ( $wp_cassify_notifications_smtp_auth_enabled ) : ?>
						<input type="checkbox" id="wp_cassify_notifications_smtp_auth" name="wp_cassify_notifications_smtp_auth" class="post_form" value="enabled" checked />
					<?php else : ?>
						<input type="checkbox" id="wp_cassify_notifications_smtp_auth" name="wp_cassify_notifications_smtp_auth" class="post_form" value="enabled" />
					<?php endif; ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">SMTP Authentication type</th>
				<td>
					<select id="wp_cassify_notifications_encryption_type" name="wp_cassify_notifications_encryption_type" class="post_form">
						<?php foreach ( $this->wp_cassify_default_notifications_options[ 'wp_cassify_default_notifications_encryption_type' ] as $wp_cassify_default_notifications_encryption_type_key => $wp_cassify_default_notifications_encryption_type_value ) : ?>
							<?php if ( $wp_cassify_default_notifications_encryption_type_value == $wp_cassify_notifications_encryption_type_selected ) : ?>
								<option value="<?php echo $wp_cassify_default_notifications_encryption_type_key; ?>" selected><?php echo $wp_cassify_default_notifications_encryption_type_value; ?></option>
							<?php else : ?>
								<option value="<?php echo $wp_cassify_default_notifications_encryption_type_key; ?>"><?php echo $wp_cassify_default_notifications_encryption_type_value; ?></option>
							<?php endif; ?>						
						<?php endforeach; ?>
					</select>
				</td>
			</tr>			
			<tr valign="top">
				<th scope="row">SMTP User</th>
				<td>
					<input type="text" id="wp_cassify_notifications_smtp_user" name="wp_cassify_notifications_smtp_user" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_user' ) ); ?>" class="post_form regular-text" size="40" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">SMTP Password</th>
				<td>
					<input type="password" id="wp_cassify_notifications_smtp_password" name="wp_cassify_notifications_smtp_password" class="post_form regular-text" value="" size="40" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">SMTP Password confirmation</th>
				<td>
					<input type="password" id="wp_cassify_notifications_smtp_confirm_password" name="wp_cassify_notifications_smtp_confirm_password" class="post_form regular-text" value="" size="40"  />
				</td>
			</tr>	
			<tr valign="top">
				<th scope="row">Salt</th>
				<td>
					<input type="text" id="wp_cassify_notifications_salt" name="wp_cassify_notifications_salt" class="post_form regular-text" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_salt' ) ); ?>" size="40" />
					<br /><span class="description">Salt is used to store user smtp password as encrypted value</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Priority</th>
				<td>
					<select id="wp_cassify_notifications_priority" name="wp_cassify_notifications_priority" class="post_form">
						<?php foreach ( $this->wp_cassify_default_notifications_options[ 'wp_cassify_default_notifications_priority' ] as $wp_cassify_default_notifications_priority_key => $wp_cassify_default_notifications_priority_value ) : ?>
							<?php if ( $wp_cassify_default_notifications_priority_key == $wp_cassify_notifications_priority_selected ) : ?>
								<option value="<?php echo $wp_cassify_default_notifications_priority_key; ?>" selected><?php echo $wp_cassify_default_notifications_priority_value; ?></option>
							<?php else : ?>
								<option value="<?php echo $wp_cassify_default_notifications_priority_key; ?>"><?php echo $wp_cassify_default_notifications_priority_value; ?></option>
							<?php endif; ?>						
						<?php endforeach; ?>
					</select>
				</td>
			</tr>			
			<tr valign="top">
				<th scope="row">SMTP From</th>
				<td>
					<input type="text" id="wp_cassify_notifications_smtp_from" name="wp_cassify_notifications_smtp_from" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_from' ) ); ?>" class="post_form regular-text" size="40" />
				</td>
			</tr>
				<tr valign="top">
				<th scope="row">SMTP To</th>
				<td>
					<input type="text" id="wp_cassify_notifications_smtp_to" name="wp_cassify_notifications_smtp_to" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_to' ) ); ?>" class="post_form regular-text" size="40" />
				</td>
			</tr>		
			<tr valign="top">
				<th scope="row">Subject prefix</th>
				<td>
					<input type="text" id="wp_cassify_notifications_subject_prefix" name="wp_cassify_notifications_subject_prefix" class="post_form regular-text" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_subject_prefix' ) ); ?>" size="40" />
					<br /><span class="description">Prefix of mail notification messages. Default value : <?php echo $this->wp_cassify_default_notifications_options[ 'wp_cassify_default_notifications_subject_prefix' ]; ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Send test message</th>
				<td>
					To : <input type="text" id="wp_cassify_notifications_send_to_test" name="wp_cassify_notifications_send_to_test" class="post_form regular-text" value="" size="40" />
					<?php submit_button( 'Send test message', 'secondary', 'wp_cassify_send_notification_test_message', FALSE, array( 'id' => 'wp_cassify_send_notification_test_message', 'data-style' => 'wp_cassify_save_options' ) ); ?>
				</td>
			</tr>
		</table>
		<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options_notifications_settings', FALSE, array( 'id' => 'wp_cassify_save_options_notifications_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?>
<?php
	}
	
	/**
	 * Display html output for metabox Notifications rules Settings.
	 */ 	
	public function wp_cassify_add_metabox_notifications_rules_settings() {
        
        $wp_cassify_notification_rules = unserialize( 
            WP_Cassify_Utils::wp_cassify_get_option( 
                    $this->wp_cassify_network_activated, 
                    'wp_cassify_notification_rules' 
            ) 
        );

        if ( ( is_array( $wp_cassify_notification_rules ) ) && ( count( $wp_cassify_notification_rules ) > 0 ) ) {
            foreach ( $wp_cassify_notification_rules as $rule_key => $rule_value ) {
                $wp_cassify_notification_rules_selected[ $rule_key ] = stripslashes( $rule_value );  
            }
        }
        else {
        	$wp_cassify_notification_rules_selected = array();
        }
?>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row">Set Conditionnal Notifications Rules</th>
				<td>
					<span class="description">Example rule syntax (Refer to plugin documentation) : (CAS{cas_user_id} -EQ "m.brown") -AND (CAS{email} -CONTAINS "my-university.fr")</span>
				</td>
			</tr>				
			<tr valign="top">
				<th scope="row">Send mail notification if user match criteria</th>
				<td>
					<select id="wp_cassify_notifications_actions" name="wp_cassify_notifications_actions" class="post_form">
						<?php foreach ( $this->wp_cassify_default_notifications_options[ 'wp_cassify_default_notifications_actions' ] as $wp_cassify_default_notifications_actions_key => $wp_cassify_default_notifications_actions_value ) : ?>
							<option value="<?php echo $wp_cassify_default_notifications_actions_key; ?>"><?php echo $wp_cassify_default_notifications_actions_value; ?></option>
						<?php endforeach; ?>
					</select>
					<input type="text" id="wp_cassify_notification_rule" name="wp_cassify_notification_rule" class="post_form" value="" size="60" class="regular-text" /><br />
					<br />
					<select id="wp_cassify_notification_rules" name="wp_cassify_notification_rules[]" class="post_form" multiple="multiple" style="height:100px;width:590px" size="10">
						<?php if ( ( is_array( $wp_cassify_notification_rules_selected )  ) && ( count( $wp_cassify_notification_rules_selected ) > 0 ) ) : ?>
							<?php foreach ( $wp_cassify_notification_rules_selected as $wp_cassify_notification_rules_selected_key => $wp_cassify_notification_rules_selected_value ) : ?>
								<?php echo "<option value='$wp_cassify_notification_rules_selected_value'>$wp_cassify_notification_rules_selected_value</option>"; ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
					<br />
					<span class="description">(*) Theses triggers needs that users attributes presents in notification rule are populated into session to be fired. See "Attributes Extraction Settings" to populate attributes into session.</span>
				</td>
			</tr>
			<tr valign="top">
				<td></td>
				<td>
					<span class="description">Double click on rule in list to edit it.</span>
					<?php submit_button( 'Add Notification Rule', 'secondary', 'wp_cassify_add_notification_rule', FALSE, array( 'id' => 'wp_cassify_add_notification_rule' ) ); ?>
					<?php submit_button( 'Remove Notification Rule', 'secondary', 'wp_cassify_remove_notification_rule', FALSE, array( 'id' => 'wp_cassify_remove_notification_rule' ) ); ?>
				</td>
			</tr>	
		</table>
		<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options_notifications_rules_settings', FALSE, array( 'id' => 'wp_cassify_save_options_notifications_rules_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?>
<?php
	}	
	
	/**
	 * Display html output for metabox Expirations rules Settings.
	 */ 	
	public function wp_cassify_add_metabox_expirations_rules_settings() {
		
        $wp_cassify_expiration_rules = unserialize( 
            WP_Cassify_Utils::wp_cassify_get_option( 
                    $this->wp_cassify_network_activated, 
                    'wp_cassify_expiration_rules' 
            ) 
        );

        if ( ( is_array( $wp_cassify_expiration_rules ) ) && ( count( $wp_cassify_expiration_rules ) > 0 ) ) {
            foreach ( $wp_cassify_expiration_rules as $rule_key => $rule_value ) {
                $wp_cassify_expiration_rules_selected[ $rule_key ] = stripslashes( $rule_value );  
            }
        }
        else {
        	$wp_cassify_expiration_rules_selected = array();
        }
		
?>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row">Set Conditionnal Expirations Rules</th>
				<td>
					<span class="description">Example rule syntax (Refer to plugin documentation) : (CAS{cas_user_id} -EQ "m.brown") -AND (CAS{email} -CONTAINS "my-university.fr")</span>
					<input type="text" id="wp_cassify_expiration_rule" name="wp_cassify_expiration_rule" class="post_form" value="" size="60" class="regular-text" />
				</td>
			</tr>				
			<tr valign="top">
				<th scope="row">Expire user account if user match criteria</th>
				<td>
					<select id="wp_cassify_default_expirations_types" name="wp_cassify_default_expirations_types" class="post_form">
						<?php foreach ( $this->wp_cassify_default_expirations_options[ 'wp_cassify_default_expirations_types' ] as $wp_cassify_default_expirations_types_key => $wp_cassify_default_expirations_types_value ) { ?>
								<option value="<?php echo $wp_cassify_default_expirations_types_key; ?>"><?php echo $wp_cassify_default_expirations_types_value; ?></option>
						<?php } ?>
					</select>
					<input type="text" id="wp_cassify_after_user_account_created_time_limit" name="wp_cassify_after_user_account_created_time_limit" class="post_form" value="" size="60" class="regular-text" />
					<input type="text" id="wp_cassify_fixed_datetime_limit" name="wp_cassify_fixed_datetime_limit" class="post_form" value="" size="60" class="regular-text" /><br />
					
					<br />
					<select id="wp_cassify_expiration_rules" name="wp_cassify_expiration_rules[]" class="post_form" multiple="multiple" style="height:100px;width:590px" size="10">
						<?php if ( ( is_array( $wp_cassify_expiration_rules_selected )  ) && ( count( $wp_cassify_expiration_rules_selected ) > 0 ) ) : ?>
							<?php foreach ( $wp_cassify_expiration_rules_selected as $wp_cassify_expiration_rules_selected_key => $wp_cassify_expiration_rules_selected_value ) : ?>
								<?php echo "<option value='$wp_cassify_expiration_rules_selected_value'>$wp_cassify_expiration_rules_selected_value</option>"; ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
					<br />
				</td>
			</tr>
			<tr valign="top">
				<td></td>
				<td>
					<span class="description">Double click on rule in list to edit it.</span>
					<?php submit_button( 'Add Expiration Rule', 'secondary', 'wp_cassify_add_expiration_rule', FALSE, array( 'id' => 'wp_cassify_add_expiration_rule' ) ); ?>
					<?php submit_button( 'Remove Expiration Rule', 'secondary', 'wp_cassify_remove_expiration_rule', FALSE, array( 'id' => 'wp_cassify_remove_expiration_rule' ) ); ?>
				</td>
			</tr>	
		</table>
		<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options_expirations_rules_settings', FALSE, array( 'id' => 'wp_cassify_save_options_expirations_rules_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?>
<?php
	}
	
	/**
	 * Display html output for metabox Debug Settings.
	 */ 	
	public function wp_cassify_add_metabox_debug_settings() {
		
        $is_enabled = FALSE;

        if ( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_xml_response_dump' ) == 'enabled' ) {
            $is_enabled = TRUE;
        }
        else {
            $is_enabled = FALSE;
        }
?>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row">Dump xml CAS server response</th>
				<td>
					<?php if ( $is_enabled ) : ?>
						<input type="checkbox" id="wp_cassify_xml_response_dump" name="wp_cassify_xml_response_dump" class="post_form" value="enabled" checked />
					<?php else :?>
						<input type="checkbox" id="wp_cassify_xml_response_dump" name="wp_cassify_xml_response_dump" class="post_form" value="enabled" />
					<?php endif; ?>
				</td>
			</tr>	
			<tr valign="top">
				<th scope="row">Last XML CAS server response</th>
				<td>
					<textarea rows="4" cols="50" id="wp_cassify_xml_response_value" name="wp_cassify_xml_response_value" class="post_form" value="" class="regular-text">
						<?php echo ltrim( esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_xml_response_value' ) ) ); ?>
					</textarea>
				</td>
			</tr>			
		</table>
		<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options_debug_settings', FALSE, array( 'id' => 'wp_cassify_save_options_debug_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?>
<?php
	}
	
	/**
	 * Display html output for metabox Backup / Restore configuration settings.
	 */ 	
	public function wp_cassify_add_metabox_backup_restore_configuration_settings() {
		
        $is_enabled = FALSE;

        if ( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_xml_response_dump' ) == 'enabled' ) {
            $is_enabled = TRUE;
        }
        else {
            $is_enabled = FALSE;
        }
?>
		<table class="optiontable form-table">
			<tr valign="top">
				<td><?php submit_button( 'Backup plugin configuration', 'secondary', 'wp_cassify_backup_plugin_options_settings', FALSE, array( 'id' => 'wp_cassify_backup_plugin_options_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?></td>
				<td><?php submit_button( 'Restore plugin configuration', 'secondary', 'wp_cassify_restore_plugin_options_settings', FALSE, array( 'id' => 'wp_cassify_restore_plugin_options_settings', 'data-style' => 'wp_cassify_save_options' ) ); ?></td>
			</tr>
			<tr valign="top">
				<th scope="row">Import configuration file</th>
				<td>
					<input type="file" id="wp_cassify_restore_plugin_options_configuration_settings_file" name="wp_cassify_restore_plugin_options_configuration_settings_file" class="post_form" value="" size="60" /><br />
				</td>
			</tr>			
		</table>
<?php
	}	
	
	/**
	 *  Register option into database.
	 */ 
	public function wp_cassify_register_plugin_settings() {

		foreach ( $this->wp_cassify_plugin_options_list as $option ) {
			register_setting( 'wp-cassify-settings-group', $option );
		}
	}
	
	/**
	 *  Display form to manage plugin options.
	 */ 
	public function wp_cassify_options() {

            if ( !current_user_can( 'manage_options' ) )  {
                wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
            }

            if ( $this->wp_cassify_is_options_updated() ) {    
        	
        		// Check security tocken
				if (! wp_verify_nonce ($_POST[ 'wp_cassify_admin_form' ], 'admin_form' ) ) {
					die( 'Security Check !' );
				}
				
        		// General settings
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_base_url', FALSE, $this->wp_cassify_network_activated );                        
				WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_protocol_version', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_checkbox( $_POST, 'wp_cassify_disable_authentication', 'disabled', $this->wp_cassify_network_activated );
                WP_Cassify_Utils::wp_cassify_update_checkbox( $_POST, 'wp_cassify_create_user_if_not_exist', 'create_user_if_not_exist', $this->wp_cassify_network_activated );	
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_ssl_cipher', TRUE, $this->wp_cassify_network_activated );
                WP_Cassify_Utils::wp_cassify_update_checkbox( $_POST, 'wp_cassify_ssl_check_certificate', 'enabled', $this->wp_cassify_network_activated );

				// Url settings
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_redirect_url_after_logout', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_override_service_url', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_login_servlet', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_logout_servlet', FALSE, $this->wp_cassify_network_activated );
                
                if ( $_POST[ 'wp_cassify_protocol_version' ] == '3' ) {
                	WP_Cassify_Utils::wp_cassify_update_textfield_manual( 
                		'p3/' . $this->wp_cassify_default_service_validate_servlet, 
                		'wp_cassify_service_validate_servlet',
                		$this->wp_cassify_network_activated
            		);
                }
                else {
					WP_Cassify_Utils::wp_cassify_update_textfield_manual( 
                		'', 
                		'wp_cassify_service_validate_servlet',
                		$this->wp_cassify_network_activated
            		);
                }
				
				// Attributes extraction settings
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_xpath_query_to_extact_cas_user', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_xpath_query_to_extact_cas_attributes', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_attributes_list', FALSE, $this->wp_cassify_network_activated ); 
                
                // Authorization rules settings
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_allow_deny_order', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_multiple_select( $_POST, 'wp_cassify_autorization_rules', $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_redirect_url_if_not_allowed', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_redirect_url_white_list', FALSE, $this->wp_cassify_network_activated ); 
                
                // User roles rules settings
                WP_Cassify_Utils::wp_cassify_update_multiple_select( $_POST, 'wp_cassify_user_role_rules', $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_checkbox( $_POST, 'wp_cassify_user_purge_user_roles_before_applying_rules', 'disabled', $this->wp_cassify_network_activated );
                
                // User attributes mapping settings
                WP_Cassify_Utils::wp_cassify_update_multiple_select( $_POST, 'wp_cassify_user_attributes_mapping_list', $this->wp_cassify_network_activated ); 
                
                // Notification settings
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_notifications_smtp_host', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_notifications_smtp_port', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_notifications_encryption_type', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_checkbox( $_POST, 'wp_cassify_notifications_smtp_auth', 'enabled', $this->wp_cassify_network_activated );
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_notifications_salt', FALSE, $this->wp_cassify_network_activated );
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_notifications_priority', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_notifications_smtp_user', FALSE, $this->wp_cassify_network_activated );

				// Store smtp password as encrypted string
				$wp_cassify_notifications_salt = WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_salt' );

				if (! empty( $_POST[ 'wp_cassify_notifications_smtp_password' ] ) ) {
					
					$wp_cassify_notifications_smtp_password = '';
					
					if (! empty( $wp_cassify_notifications_salt ) ) {
						$wp_cassify_notifications_smtp_password = WP_Cassify_Utils::wp_cassify_simple_encrypt( 
							$_POST[ 'wp_cassify_notifications_smtp_password' ],
							$wp_cassify_notifications_salt
						);
					}
					else {
						$wp_cassify_notifications_smtp_password = WP_Cassify_Utils::wp_cassify_simple_encrypt( 
							$_POST[ 'wp_cassify_notifications_smtp_password' ]
						);						
					}

					if (! empty( $wp_cassify_notifications_smtp_password ) ) {
						WP_Cassify_Utils::wp_cassify_update_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_password', $wp_cassify_notifications_smtp_password );
					}
				}

				WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_notifications_smtp_from', FALSE, $this->wp_cassify_network_activated ); 
				WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_notifications_smtp_to', FALSE, $this->wp_cassify_network_activated ); 
                WP_Cassify_Utils::wp_cassify_update_textfield( $_POST, 'wp_cassify_notifications_subject_prefix', FALSE, $this->wp_cassify_network_activated ); 

				// Notifications rules settings
				WP_Cassify_Utils::wp_cassify_update_multiple_select( $_POST, 'wp_cassify_notification_rules', $this->wp_cassify_network_activated ); 
				
				// Expirations rules settings
				WP_Cassify_Utils::wp_cassify_update_multiple_select( $_POST, 'wp_cassify_expiration_rules', $this->wp_cassify_network_activated ); 				

				// Debug settings
				WP_Cassify_Utils::wp_cassify_update_checkbox( $_POST, 'wp_cassify_xml_response_dump', 'enabled', $this->wp_cassify_network_activated );	
                
				// Send test notification message.
				if (! empty( $_POST['wp_cassify_send_notification_test_message'] ) ){
					
					$wp_cassify_send_notification_to = sanitize_text_field( $_POST[ 'wp_cassify_notifications_send_to_test' ] );
					$wp_cassify_notifications_subject_prefix = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_subject_prefix' ) );
					$wp_cassify_send_notification_subject =  $this->wp_cassify_default_notifications_options[ 'wp_cassify_send_notification_default_subject' ];
					$wp_cassify_send_notification_message = $this->wp_cassify_default_notifications_options[ 'wp_cassify_send_notification_default_message' ];
				
					if (! empty( $wp_cassify_notifications_subject_prefix ) ) {
						$wp_cassify_send_notification_subject = $wp_cassify_notifications_subject_prefix . $wp_cassify_send_notification_subject;
					}

					$wp_cassify_notifications_smtp_auth = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_auth' ) );
					
					$wp_cassify_notifications_smtp_auth_enabled = FALSE;
					$wp_cassify_notifications_encryption_type = NULL;
					
					if ( $wp_cassify_notifications_smtp_auth == 'enabled' ) {
						$wp_cassify_notifications_smtp_auth_enabled = TRUE;
						$wp_cassify_notifications_encryption_type = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_encryption_type' ) );
					}
					
					$wp_cassify_notifications_priority = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_priority' ) );

					$wp_cassify_notifications_smtp_user = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_user' ) );
					$wp_cassify_notifications_smtp_password = esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_password' ) );
					
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
						$wp_cassify_send_notification_to, 
						$wp_cassify_send_notification_subject, 
						$wp_cassify_send_notification_message,
						$wp_cassify_notifications_priority,
						esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_host' ) ), 
						esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_notifications_smtp_port' ) ), 
						$wp_cassify_notifications_smtp_auth_enabled,
						$wp_cassify_notifications_encryption_type,
						$wp_cassify_notifications_smtp_user, 
						$wp_cassify_notifications_smtp_password
					);
					
					if ( $send_result == TRUE ) {
?>
					<div id="message_test_notification" class="updated" >Mail sent successfully.</div>
<?php
					}
					else {
?>
					<div id="message_test_notification" class="error" ><?php echo esc_attr( print_r( $send_result ) ); ?></div>
<?php	
					}
				}
				
                // Empty cache when options are updated.
                if (function_exists('w3tc_pgcache_flush')) {
                    w3tc_pgcache_flush();

                    // Sleep during cache is cleaning.
                    sleep ( 2 );
                } 
            }
			
            $post_action_page = NULL;
            
            if ( $this->wp_cassify_network_activated ) {
                $wp_cassify_post_action_url = $this->wp_cassify_multisite_admin_page_slug . '?page=wp-cassify.php';
            }
            else {
                $wp_cassify_post_action_url = $this->wp_cassify_admin_page_slug . '?page=wp-cassify.php';
            }
?>
		<div class="wrap" id="wp-cassify">
		<h2><?php screen_icon('options-general'); ?><?php echo $this->wp_cassify_plugin_datas[ 'Name' ] ?></h2>
		
		<?php if ( $this->wp_cassify_is_options_updated() ) { ?>
				<div id="message" class="updated" >Settings saved successfully</div>
		<?php } ?>

		<form method="post" action="<?php echo $wp_cassify_post_action_url; ?>" enctype="multipart/form-data">
			<?php wp_nonce_field( 'admin_form', 'wp_cassify_admin_form' ); // Set security token ?>
			<?php settings_fields( 'wp-cassify-settings-group' ); ?>
			<?php do_settings_sections( 'wp-cassify-settings-group' ); ?>
			
			<?php if ( $this->wp_cassify_network_activated ) : ?>
				<input type="hidden" id="wp_cassify_network_activated" name="wp_cassify_network_activated" value="enabled" />
			<?php else : ?>
				<input type="hidden" id="wp_cassify_network_activated" name="wp_cassify_network_activated" value="disabled" />
			<?php endif; ?>

			
			<div id="poststuff" class="metabox-holder columns-2">
				<div id="side-info-column" class="inner-sidebar">
					<?php do_meta_boxes( $this->wp_cassify_admin_page_hook, 'side', array() ); ?>
				</div>
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_meta_boxes( $this->wp_cassify_admin_page_hook, 'normal', array() ); ?>
					</div>
				</div>
			</div>
		</form>
		</div>
		<?php
	}
	
	/**
	 * Export plugin configuration options settings
	 */ 
	public function wp_cassify_export_configuration_options_settings() {


        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( $this->wp_cassify_is_options_updated() ) {    
    	
    		// Check security tocken
			if (! wp_verify_nonce ($_POST[ 'wp_cassify_admin_form' ], 'admin_form' ) ) {
				die( 'Security Check 1 !' );
			}
			
			if (! empty( $_POST['wp_cassify_backup_plugin_options_settings'] ) ){
        	
	        	$wp_cassify_backup_plugin_options_settings = WP_Cassify_Utils::wp_cassify_export_configuration_options( false );
	
				nocache_headers();
				header( 'Content-Type: application/json; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename=wpcassify-settings-export-' . date( 'm-d-Y' ) . '.json' );
				header( "Expires: 0" );
				
				echo json_encode( $wp_cassify_backup_plugin_options_settings );
				exit;         	
			}
    	}
	}	

	/**
	 * Import plugin configuration options settings
	 */ 
	public function wp_cassify_import_configuration_options_settings() {


        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( $this->wp_cassify_is_options_updated() ) {    
    	
    		// Check security tocken
			if (! wp_verify_nonce ($_POST[ 'wp_cassify_admin_form' ], 'admin_form' ) ) {
				die( 'Security Check 2 !' );
			}

			if (! empty( $_FILES['wp_cassify_restore_plugin_options_configuration_settings_file']['name'] ) ) {

				//$extension = end( explode( '.', $_FILES['wp_cassify_restore_plugin_options_configuration_settings_file']['name'] ) );
				$extension = explode( '.', $_FILES['wp_cassify_restore_plugin_options_configuration_settings_file']['name'] );
				$extension = end( $extension );

				if( $extension != 'json' ) {
					wp_die( __( 'Please upload a valid .json file' ) );
				}
				
				$import_file = $_FILES['wp_cassify_restore_plugin_options_configuration_settings_file']['tmp_name'];
				
				if( empty( $import_file ) ) {
					wp_die( __( 'Please upload a file to import' ) );
				}
				
				$wp_cassify_import_configuration_options = (array) json_decode( file_get_contents( $import_file ) );
				
				WP_Cassify_Utils::wp_cassify_import_configuration_options(
					$wp_cassify_import_configuration_options, 
					$this->wp_cassify_network_activated
				);
							
				if ( $this->wp_cassify_network_activated ) {
					wp_safe_redirect( admin_url( $this->wp_cassify_multisite_admin_page_slug . '?page=wp-cassify.php' ) ); exit;
				}
				else {
					wp_safe_redirect( admin_url( $this->wp_cassify_admin_page_slug . '?page=wp-cassify.php' ) ); exit;
				}
			}
    	}
	}

	
	/**
	 * Detect if form has been submitted.
	 * @return is_updated
	 */ 
	private function wp_cassify_is_options_updated() {
		
		$is_updated = FALSE;
        if ( ( isset( $_POST[ 'action' ] ) ) && ( $_POST[ 'action' ] == 'update' ) ) {    
        	
    		$is_updated = TRUE;
        }
		
		return $is_updated;
	}
}
?>
