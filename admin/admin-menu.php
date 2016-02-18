<?php

namespace wp_cassify;

class WP_Cassify_Admin_Page {

	public $wp_cassify_plugin_datas;
	public $wp_cassify_plugin_directory;
    public $wp_cassify_network_activated;
	public $wp_cassify_plugin_options_list;
	public $wp_cassify_default_protocol_version_values = array();
	public $wp_cassify_default_login_servlet;
	public $wp_cassify_default_logout_servlet;
	public $wp_cassify_default_service_validate_servlet;
	public $wp_cassify_default_ssl_cipher_values = array();
	public $wp_cassify_default_xpath_query_to_extact_cas_user;
	public $wp_cassify_default_xpath_query_to_extact_cas_attributes;
	public $wp_cassify_default_allow_deny_order;
	public $wp_cassify_wordpress_user_meta_list;
        
    private $wp_cassify_admin_page_slug;
    private $wp_cassify_multisite_admin_page_slug;
        
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
		$wp_cassify_default_xpath_query_to_extact_cas_user,
		$wp_cassify_default_xpath_query_to_extact_cas_attributes,
		$wp_cassify_default_allow_deny_order,
		$wp_cassify_wordpress_user_meta_list
	) {
		$this->wp_cassify_plugin_datas = $wp_cassify_plugin_datas;
		$this->wp_cassify_plugin_directory = $wp_cassify_plugin_directory;
      	$this->wp_cassify_network_activated = $wp_cassify_network_activated;
		$this->wp_cassify_plugin_options_list = $wp_cassify_plugin_options_list;
		$this->wp_cassify_default_protocol_version_values = $wp_cassify_default_protocol_version_values;
		$this->wp_cassify_default_login_servlet = $wp_cassify_default_login_servlet;
		$this->wp_cassify_default_logout_servlet = $wp_cassify_default_logout_servlet;
		$this->wp_cassify_default_service_validate_servlet = $wp_cassify_default_service_validate_servlet;
		$this->wp_cassify_default_ssl_cipher_values = $wp_cassify_default_ssl_cipher_values;
		$this->wp_cassify_default_xpath_query_to_extact_cas_user = $wp_cassify_default_xpath_query_to_extact_cas_user;
		$this->wp_cassify_default_xpath_query_to_extact_cas_attributes = $wp_cassify_default_xpath_query_to_extact_cas_attributes;
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
		
		add_action( 'admin_enqueue_scripts', array( $this , 'wp_cassify_add_custom_js' ) );
	}	
	
	/**
	 *  Add admin menu options
	 */ 
	public function wp_cassify_create_menu() {

		add_options_page( $this->wp_cassify_plugin_datas['Name'] . ' Options', 
			$this->wp_cassify_plugin_datas['Name'], 
			'manage_options', 
			'wp-cassify.php' , 
			array( $this, 'wp_cassify_options' )
		 );
			
		//call register settings function
		add_action( 'admin_init', array( $this , 'wp_cassify_register_plugin_settings' ) );
	}
        
	/**
	 *  Add admin menu options if plugin is activate over the network
	 */         
	public function wp_cassify_create_network_menu() {

		add_submenu_page( 
                        'settings.php',
                        $this->wp_cassify_plugin_datas['Name'] . ' Options', 
			$this->wp_cassify_plugin_datas['Name'], 
			'manage_options', 
			'wp-cassify.php' , 
			array( $this, 'wp_cassify_options' )
		 );
			
		//call register settings function
		add_action( 'admin_init', array( $this , 'wp_cassify_register_plugin_settings' ) );
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
	 * Add js functions to admin option page.
	 */ 
	public function wp_cassify_add_custom_js() {	
            
	   wp_enqueue_script(
			'wp_cassify_custom_js',	
			$this->wp_cassify_plugin_directory . 'js/functions.js',
			array( 'jquery' ),
			'1.0',
			TRUE
		);
	}
	
	/**
	 *  Display form to manage plugin options.
	 */ 
	public function wp_cassify_options() {

            if ( !current_user_can( 'manage_options' ) )  {
                    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
            }

            if ( ( isset( $_POST[ 'action' ] ) ) && ( $_POST[ 'action' ] == 'update' ) ) {    
            	
            		// Check security tocken
					if (! wp_verify_nonce ($_POST[ 'wp_cassify_admin_form' ], 'admin_form' ) ) {
						die( 'Security Check !' );
					}
            	
                    $this->wp_cassify_update_textfield( $_POST, 'wp_cassify_base_url' );                        
					$this->wp_cassify_update_textfield( $_POST, 'wp_cassify_protocol_version' ); 

                    $this->wp_cassify_update_checkbox( $_POST, 'wp_cassify_disable_authentication', 'disabled' );
                    $this->wp_cassify_update_checkbox( $_POST, 'wp_cassify_create_user_if_not_exist', 'create_user_if_not_exist' );		

                    $this->wp_cassify_update_textfield( $_POST, 'wp_cassify_ssl_cipher', TRUE ); 
                    $this->wp_cassify_update_textfield( $_POST, 'wp_cassify_redirect_url_after_logout' ); 
                    $this->wp_cassify_update_textfield( $_POST, 'wp_cassify_login_servlet' ); 
                    $this->wp_cassify_update_textfield( $_POST, 'wp_cassify_logout_servlet' );
                    
                    if ( $_POST[ 'wp_cassify_protocol_version' ] == '3' ) {
                    	$this->wp_cassify_update_textfield_manual( 
                    		'p3/' . $this->wp_cassify_default_service_validate_servlet, 
                    		'wp_cassify_service_validate_servlet' 
                		);
                    }
                    else {
						$this->wp_cassify_update_textfield_manual( 
                    		'', 
                    		'wp_cassify_service_validate_servlet' 
                		);
                    }
                    
                    $this->wp_cassify_update_textfield( $_POST, 'wp_cassify_xpath_query_to_extact_cas_user' ); 
                    $this->wp_cassify_update_textfield( $_POST, 'wp_cassify_xpath_query_to_extact_cas_attributes' ); 
                    $this->wp_cassify_update_textfield( $_POST, 'wp_cassify_attributes_list' ); 
                    $this->wp_cassify_update_textfield( $_POST, 'wp_cassify_allow_deny_order' ); 

                    $this->wp_cassify_update_multiple_select( $_POST, 'wp_cassify_autorization_rules' ); 
                    $this->wp_cassify_update_multiple_select( $_POST, 'wp_cassify_user_role_rules' ); 
                    $this->wp_cassify_update_textfield( $_POST, 'wp_cassify_redirect_url_if_not_allowed' ); 
                    $this->wp_cassify_update_textfield( $_POST, 'wp_cassify_redirect_url_white_list' ); 
                    
                    $this->wp_cassify_update_multiple_select( $_POST, 'wp_cassify_user_attributes_mapping_list' ); 

                    // Empty cache when options are updated.
                    if (function_exists('w3tc_pgcache_flush')) {
                            w3tc_pgcache_flush();

                            // Sleep during cache is cleaning.
                            sleep ( 2 );
                    } 
            }

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
                    $wp_cassify_ssl_cipher_selected = '1';
            }

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
            
            $wp_cassify_user_attributes_mapping_list = unserialize( 
                    WP_Cassify_Utils::wp_cassify_get_option( 
                            $this->wp_cassify_network_activated, 
                            'wp_cassify_user_attributes_mapping_list' 
                    ) 
            );

            if ( ( is_array( $wp_cassify_user_attributes_mapping_list ) ) && ( count( $wp_cassify_user_attributes_mapping_list ) > 0 ) ) {
                foreach ( $wp_cassify_user_attributes_mapping_list as $wp_cassify_user_attributes_mapping_key => $wp_cassify_user_attributes_mapping_value ) {
                    $wp_cassify_user_attributes_mapping_list_selected[ $wp_cassify_user_attributes_mapping_value ] = $wp_cassify_user_attributes_mapping_value;  
                }
            }
            else {
            	$wp_cassify_user_attributes_mapping_list_selected = array();
            }
			
            $post_action_page = NULL;
            
            if ( $this->wp_cassify_network_activated ) {
                $wp_cassify_post_action_url = $this->wp_cassify_multisite_admin_page_slug . '?page=wp-cassify.php';
            }
            else {
                $wp_cassify_post_action_url = $this->wp_cassify_admin_page_slug . '?page=wp-cassify.php';
            }
?>
		<div class="wrap">
		<h2><?php echo $this->wp_cassify_plugin_datas[ 'Name' ] ?></h2>

		<form method="post" action="<?php echo $wp_cassify_post_action_url; ?>">
			<?php wp_nonce_field( 'admin_form', 'wp_cassify_admin_form' ); // Set security token ?>
			<?php settings_fields( 'wp-cassify-settings-group' ); ?>
			<?php do_settings_sections( 'wp-cassify-settings-group' ); ?>
			<table class="optiontable form-table">
				<tr valign="top">
					<th scope="row"><label for="wp_cassify_base_url">CAS Server base url</label></th>
					<td>
						<input type="text" id="wp_cassify_base_url" name="wp_cassify_base_url" class="post_form" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_base_url' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">Example : https://you-cas-server/cas/ </span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">CAS Version protocol</th>
					<td>
						<select id="wp_cassify_protocol_version" name="wp_cassify_protocol_version" class="post_form">
							<?php foreach ( $this->wp_cassify_default_protocol_version_values as $wp_cassify_default_protocol_version_key => $wp_cassify_default_protocol_version_value ) { ?>
								<?php if ( $wp_cassify_default_protocol_version_value == $wp_cassify_default_protocol_version_selected ) { ?>
									<option value="<?php echo $wp_cassify_default_protocol_version_key; ?>" selected><?php echo $wp_cassify_default_protocol_version_value; ?></option>
								<?php } else { ?>
									<option value="<?php echo $wp_cassify_default_protocol_version_key; ?>"><?php echo $wp_cassify_default_protocol_version_value; ?></option>
								<?php } ?>						
							<?php } ?>
						</select>
						<br /><span class="description">Default value : <?php echo $this->wp_cassify_default_protocol_version_values[ '3' ]; ?></span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Disable CAS Authentication</th>
					<?php if ( $is_disabled ) { ?>
					<td><input type="checkbox" name="wp_cassify_disable_authentication" class="post_form" value="disabled" checked /></td>
					<?php } else { ?>
					<td><input type="checkbox" name="wp_cassify_disable_authentication" class="post_form" value="disabled" /></td>
					<?php }?>
				</tr>
				<tr valign="top">
					<th scope="row">Create user if not exist</th>
					<?php if ( $create_user_if_not_exist ) { ?>
					<td><input type="checkbox" name="wp_cassify_create_user_if_not_exist" class="post_form" value="create_user_if_not_exist" checked /><br /><span class="description">Create wordpress user account if not exist.</span></td>
					<?php } else { ?>
					<td><input type="checkbox" name="wp_cassify_create_user_if_not_exist" class="post_form" value="create_user_if_not_exist" /><br /><span class="description">Create wordpress user account if not exist.</span></td>
					<?php }?>
				</tr>											
				<tr valign="top">
					<th scope="row">SSL Cipher used for query CAS Server with HTTPS Webrequest to validate service ticket</th>
					<td>
						<select id="wp_cassify_ssl_cipher" name="wp_cassify_ssl_cipher" class="post_form">
							<?php foreach ( $this->wp_cassify_default_ssl_cipher_values as $cipher_id => $cipher_name ) { ?>
								<?php if ( $cipher_id == $wp_cassify_ssl_cipher_selected ) { ?>
									<option value="<?php echo $cipher_id; ?>" selected><?php echo $cipher_name; ?></option>
								<?php } else { ?>
									<option value="<?php echo $cipher_id; ?>"><?php echo $cipher_name; ?></option>
								<?php } ?>						
							<?php } ?>
						</select>
						<br /><span class="description">Default value : <?php echo $this->wp_cassify_default_ssl_cipher_values[ '3' ]; ?></span>
					</td>
				</tr>	
				<tr valign="top">
					<th scope="row"><label for="wp_cassify_redirect_url_after_logout">Service logout redirect url</label></th>
					<td>
						<input type="text" id="wp_cassify_redirect_url_after_logout" name="wp_cassify_redirect_url_after_logout" class="post_form" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_after_logout' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">The blog home url is used when this option is not set .Url where the CAS Server redirect after logout. CAS Server must be configured correctly (see : followServiceRedirects option in JASIG documentation) </span>
					</td>
				</tr>								
				<tr valign="top">
					<td colspan="2">Update servlets name only if you have customized your CAS Server.</td>
				</tr>
				<tr valign="top">
					<th scope="row">Name of the login servlet (Default : <?php echo $this->wp_cassify_default_login_servlet; ?>)</th>
					<td><input type="text" id="wp_cassify_login_servlet" name="wp_cassify_login_servlet" class="post_form" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_login_servlet' ) ); ?>" size="40" class="regular-text" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Name of the logout servlet (Default : <?php echo $this->wp_cassify_default_logout_servlet; ?>)</th>
					<td><input type="text" id="wp_cassify_logout_servlet" name="wp_cassify_logout_servlet" class="post_form" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_logout_servlet' ) ); ?>" size="40" class="regular-text" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Name of the service validate servlet (Default : <?php echo $this->wp_cassify_default_service_validate_servlet; ?>)</th>
					<td><input type="text" id="wp_cassify_service_validate_servlet" name="wp_cassify_service_validate_servlet" class="post_form" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_service_validate_servlet' ) ); ?>" size="40" class="regular-text" /></td>
				</tr>		
				<tr valign="top">
					<th scope="row">Xpath query used to extract cas user id during parsing</th>
					<td>
						<input type="text" id="wp_cassify_xpath_query_to_extact_cas_user" name="wp_cassify_xpath_query_to_extact_cas_user" class="post_form" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_xpath_query_to_extact_cas_user' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">(Default : <?php echo $this->wp_cassify_default_xpath_query_to_extact_cas_user; ?>)</span>
					</td>
				</tr>	
				<tr valign="top">
					<th scope="row">Xpath query used to extract cas user attributes during parsing</th>
					<td>
						<input type="text" id="wp_cassify_xpath_query_to_extact_cas_attributes" name="wp_cassify_xpath_query_to_extact_cas_attributes" class="post_form" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_xpath_query_to_extact_cas_attributes' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">(Default : <?php echo $this->wp_cassify_default_xpath_query_to_extact_cas_attributes; ?>)</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Cas user attributes you want to populate into session.</th>
					<td>
						<input type="text" id="wp_cassify_attributes_list" name="wp_cassify_attributes_list" class="post_form" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_attributes_list' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">You must write attribute name separated by comma like this : attribute_1,attribute_2,attribute_3</span>
					</td>
				</tr>			
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
							<?php foreach ( $this->wp_cassify_default_allow_deny_order as $allow_deny_order ) { ?>
								<?php if ( $allow_deny_order == $wp_cassify_allow_deny_order_selected ) { ?>
									<option value="<?php echo $allow_deny_order; ?>" selected><?php echo $allow_deny_order; ?></option>
								<?php } else { ?>
									<option value="<?php echo $allow_deny_order; ?>"><?php echo $allow_deny_order; ?></option>
								<?php } ?>						
							<?php } ?>
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
						<?php if ( ( is_array( $wp_cassify_autorization_rules_selected )  ) && ( count( $wp_cassify_autorization_rules_selected ) > 0 ) ) { ?>
						<?php 	foreach ( $wp_cassify_autorization_rules_selected as $wp_cassify_autorization_rules_selected_key => $wp_cassify_autorization_rules_selected_value ) { ?>
						<?php 		echo "<option value='$wp_cassify_autorization_rules_selected_value'>$wp_cassify_autorization_rules_selected_value</option>"; ?>
						<?php 	} ?>
						<?php } ?>
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
						<input type="text" id="wp_cassify_redirect_url_if_not_allowed" name="wp_cassify_redirect_url_if_not_allowed" class="post_form" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_if_not_allowed' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">Url where to redirect if user is not allowed to connect according to autorization rules below. If it's blog page, this page does not require authenticated access. The blog home url is used when this option is not set.</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="wp_cassify_redirect_url_white_list">White List URL(s)</label></th>
					<td>
						<input type="text" id="wp_cassify_redirect_url_white_list" name="wp_cassify_redirect_url_white_list" class="post_form" value="<?php echo esc_attr( WP_Cassify_Utils::wp_cassify_get_option( $this->wp_cassify_network_activated, 'wp_cassify_redirect_url_white_list' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">List of URL(s) that don't intercepted by CAS Authentication. Use ';' as URL separator.</span>
					</td>
				</tr>				
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
						<?php foreach ( $wp_cassify_wordpress_roles as $wp_cassify_wordpress_role_key => $wp_cassify_wordpress_role_value ) { ?>
						<?php 		echo "<option value='$wp_cassify_wordpress_role_key'>$wp_cassify_wordpress_role_value</option>"; ?>
						<?php } ?>
						</select>
						<input type="text" id="wp_cassify_user_role_rule" name="wp_cassify_user_role_rule" class="post_form" value="" size="60" class="regular-text" /><br />
						<br />
						<select id="wp_cassify_user_role_rules" name="wp_cassify_user_role_rules[]" class="post_form" multiple="multiple" style="height:100px;width:590px" size="10">
						<?php if ( ( is_array( $wp_cassify_user_role_rules_selected )  ) && ( count( $wp_cassify_user_role_rules_selected ) > 0 ) ) { ?>
						<?php 	foreach ( $wp_cassify_user_role_rules_selected as $wp_cassify_user_role_rules_selected_key => $wp_cassify_user_role_rules_selected_value ) { ?>
						<?php 		echo "<option value='$wp_cassify_user_role_rules_selected_value'>$wp_cassify_user_role_rules_selected_value</option>"; ?>
						<?php 	} ?>
						<?php } ?>
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
					<th scope="row" colspan="2">Synchronize Wordpress User metas with CAS User attributes </th>
				</tr>
				<tr valign="top">
					<th scope="row">Wordpress User Meta</th>
					<td>
						<select id="wp_cassify_wordpress_user_meta_list" name="wp_cassify_wordpress_user_meta_list" class="post_form"> ?>
						<?php foreach ( $this->wp_cassify_wordpress_user_meta_list as $wp_cassify_wordpress_user_meta ) { ?>
						<?php 		echo "<option value='$wp_cassify_wordpress_user_meta'>$wp_cassify_wordpress_user_meta</option>"; ?>
						<?php } ?>
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
						<?php if ( ( is_array( $wp_cassify_user_attributes_mapping_list_selected )  ) && ( count( $wp_cassify_user_attributes_mapping_list_selected ) > 0 ) ) { ?>
						<?php 	foreach ( $wp_cassify_user_attributes_mapping_list_selected as $wp_cassify_user_attributes_mapping_list_selected_key => $wp_cassify_user_attributes_mapping_list_selected_value ) { ?>
						<?php 		echo "<option value='$wp_cassify_user_attributes_mapping_list_selected_value'>$wp_cassify_user_attributes_mapping_list_selected_value</option>"; ?>
						<?php 	} ?>
						<?php } ?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<td></td>
					<td>
						<span class="description">Double click on rule in list to edit it.</span>
						<?php submit_button( 'Add User Attribute Mapping', 'secondary', 'wp_cassify_add_user_attribute_mapping', FALSE, array( 'id' => 'wp_cassify_add_user_attribute_mapping' ) ); ?>
						<?php submit_button( 'Remove User Attribute Mapping', 'secondary', 'wp_cassify_remove_user_attribute_mapping', FALSE, array( 'id' => 'wp_cassify_remove_user_attribute_mapping' ) ); ?>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options', FALSE, array( 'id' => 'wp_cassify_save_options' ) ); ?>			
		</form>
		</div>
		<?php
	}
        
    /**
     * Save plugin options stored in form textfield into database.
     * @param array $post_array
     * @param string $field_name
     * @param bool $do_not_check_empty
     */
    private function wp_cassify_update_textfield( &$post_array, $field_name, $do_not_check_empty = FALSE ) {

		$field_value = '';
		
		if (! $do_not_check_empty ) {
			if(! empty( $post_array[ $field_name ] ) ) {
	        	$field_value = $post_array[ $field_name ];
	        }
		}
		else {
			$field_value = $post_array[ $field_name ];
		}
		
        if ( $this->wp_cassify_network_activated ) {
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
     */
    private function wp_cassify_update_textfield_manual( $field_value, $field_name ) {

        if( isset( $field_value ) ) {
            if ( $this->wp_cassify_network_activated ) {
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
     */
    private function wp_cassify_update_checkbox( &$post_array, $field_name, $checked_value_name ) {

        if ( (! empty( $post_array[ $field_name ] ) ) && ( $post_array[ $field_name ] == $checked_value_name ) ) {
            if ( $this->wp_cassify_network_activated ) {
                update_site_option( $field_name , $post_array[ $field_name ] );
            }
            else {
                update_option( $field_name , $post_array[ $field_name ] );
            }      
        }
        else {
            if ( $this->wp_cassify_network_activated ) {
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
     */
    private function wp_cassify_update_multiple_select( &$post_array, $field_name ) {
        
		$field_value = '';

        if(! empty( $post_array[ $field_name ] ) ) {
        	$field_value = $post_array[ $field_name ];
        	
            if( !is_serialized( $field_value ) ) {
                $field_value = serialize( $field_value );
            }
        }
        
        update_option( $field_name , sanitize_text_field( $field_value ) );
    }     
}
?>
