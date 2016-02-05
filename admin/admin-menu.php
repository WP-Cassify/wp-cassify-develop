<?php

namespace wp_cassify;

class WP_Cassify_Admin_Page {

	public $wp_cassify_plugin_datas;
	public $wp_cassify_plugin_directory;
	public $wp_cassify_plugin_options_list;
	public $wp_cassify_default_login_servlet;
	public $wp_cassify_default_logout_servlet;
	public $wp_cassify_default_service_validate_servlet;
	public $wp_cassify_default_ssl_cipher_values;
	public $wp_cassify_default_xpath_query_to_extact_cas_user;
	public $wp_cassify_default_xpath_query_to_extact_cas_attributes;
	public $wp_cassify_default_allow_deny_order;	

	/**
	 * Constructor
	 */
	public function __construct() {

		// Add the actions
		add_action( 'admin_menu', array( $this , 'wp_cassify_create_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this , 'wp_cassify_add_custom_js' ) );
	}
	
	public function init_parameters( 
		$wp_cassify_plugin_datas,
		$wp_cassify_plugin_directory,
		$wp_cassify_plugin_options_list,
		$wp_cassify_default_login_servlet,
		$wp_cassify_default_logout_servlet,
		$wp_cassify_default_service_validate_servlet,
		$wp_cassify_default_ssl_cipher_values,
		$wp_cassify_default_xpath_query_to_extact_cas_user,
		$wp_cassify_default_xpath_query_to_extact_cas_attributes,
		$wp_cassify_default_allow_deny_order
	) {
		$this->wp_cassify_plugin_datas = $wp_cassify_plugin_datas;
		$this->wp_cassify_plugin_directory = $wp_cassify_plugin_directory;
		$this->wp_cassify_plugin_options_list = $wp_cassify_plugin_options_list;
		$this->wp_cassify_default_login_servlet = $wp_cassify_default_login_servlet;
		$this->wp_cassify_default_logout_servlet = $wp_cassify_default_logout_servlet;
		$this->wp_cassify_default_service_validate_servlet = $wp_cassify_default_service_validate_servlet;
		$this->wp_cassify_default_ssl_cipher_values = $wp_cassify_default_ssl_cipher_values;
		$this->wp_cassify_default_xpath_query_to_extact_cas_user = $wp_cassify_default_xpath_query_to_extact_cas_user;
		$this->wp_cassify_default_xpath_query_to_extact_cas_attributes = $wp_cassify_default_xpath_query_to_extact_cas_attributes;
		$this->wp_cassify_default_allow_deny_order = $wp_cassify_default_allow_deny_order;	
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

		if ( ( isset($_POST[ 'action' ]) ) && ( $_POST[ 'action' ] == 'update' ) ) {
			if( isset($_POST[ 'wp_cassify_base_url' ]) ) {
				update_option( 'wp_cassify_base_url' , sanitize_text_field( $_POST[ 'wp_cassify_base_url' ] ) );
			}
			
			if ( ( isset( $_POST[ 'wp_cassify_disable_authentication' ] ) ) && ( $_POST[ 'wp_cassify_disable_authentication' ] == 'disabled' ) ) {
				update_option( 'wp_cassify_disable_authentication' , $_POST[ 'wp_cassify_disable_authentication' ] );
			}
			else {	
				update_option( 'wp_cassify_disable_authentication' , '' );
			}	
			
			if ( ( isset( $_POST[ 'wp_cassify_create_user_if_not_exist' ] ) ) && ( $_POST[ 'wp_cassify_create_user_if_not_exist' ] == 'create_user_if_not_exist' ) ) {
				update_option( 'wp_cassify_create_user_if_not_exist' , $_POST[ 'wp_cassify_create_user_if_not_exist' ] );
			}
			else {	
				update_option( 'wp_cassify_create_user_if_not_exist' , '' );
			}		
			
			if( isset($_POST[ 'wp_cassify_ssl_cipher' ]) ) {
				update_option( 'wp_cassify_ssl_cipher' , sanitize_text_field( $_POST[ 'wp_cassify_ssl_cipher' ] ) );
			}
			
			if( isset($_POST[ 'wp_cassify_redirect_url_after_logout' ]) ) {
				update_option( 'wp_cassify_redirect_url_after_logout' , sanitize_text_field( $_POST[ 'wp_cassify_redirect_url_after_logout' ] ) );
			}								
			
			if( isset($_POST[ 'wp_cassify_login_servlet' ]) ) {
				update_option( 'wp_cassify_login_servlet' , sanitize_text_field( $_POST[ 'wp_cassify_login_servlet' ] ) );
			}
			
			if( isset($_POST[ 'wp_cassify_logout_servlet' ]) ) {
				update_option( 'wp_cassify_logout_servlet' , sanitize_text_field( $_POST[ 'wp_cassify_logout_servlet' ] ) );
			}
			
			if( isset($_POST[ 'wp_cassify_service_validate_servlet' ]) ) {
				update_option( 'wp_cassify_service_validate_servlet' , sanitize_text_field( $_POST[ 'wp_cassify_service_validate_servlet' ] ) );
			}
			
			if( isset($_POST[ 'wp_cassify_xpath_query_to_extact_cas_user' ]) ) {
				update_option( 'wp_cassify_xpath_query_to_extact_cas_user' , sanitize_text_field( $_POST[ 'wp_cassify_xpath_query_to_extact_cas_user' ] ) );
			}	
			
			if( isset($_POST[ 'wp_cassify_xpath_query_to_extact_cas_attributes' ]) ) {
				update_option( 'wp_cassify_xpath_query_to_extact_cas_attributes' , sanitize_text_field( $_POST[ 'wp_cassify_xpath_query_to_extact_cas_attributes' ] ) );
			}
			
			if( isset($_POST[ 'wp_cassify_attributes_list' ]) ) {
				update_option( 'wp_cassify_attributes_list' , sanitize_text_field( $_POST[ 'wp_cassify_attributes_list' ] ) );
			}		

			if( isset($_POST[ 'wp_cassify_allow_deny_order' ]) ) {
				update_option( 'wp_cassify_allow_deny_order' , sanitize_text_field( $_POST[ 'wp_cassify_allow_deny_order' ] ) );
			}
			
			if( isset($_POST[ 'wp_cassify_autorization_rules' ]) ) {
				$wp_cassify_autorization_rules = $_POST[ 'wp_cassify_autorization_rules' ];
				
				if( !is_serialized( $wp_cassify_autorization_rules ) ) {
					$wp_cassify_autorization_rules = serialize( $wp_cassify_autorization_rules );
				}
				
				update_option( 'wp_cassify_autorization_rules' , sanitize_text_field( $wp_cassify_autorization_rules ) );
			}
			
			if( isset($_POST[ 'wp_cassify_redirect_url_if_not_allowed' ]) ) {
				update_option( 'wp_cassify_redirect_url_if_not_allowed' , sanitize_text_field( $_POST[ 'wp_cassify_redirect_url_if_not_allowed' ] ) );
			}	
			
			if( isset($_POST[ 'wp_cassify_redirect_url_white_list' ]) ) {
				update_option( 'wp_cassify_redirect_url_white_list' , sanitize_text_field( $_POST[ 'wp_cassify_redirect_url_white_list' ] ) );
			}
				
			// Empty cache when options are updated.
			if (function_exists('w3tc_pgcache_flush')) {
				w3tc_pgcache_flush();
				
				// Sleep during cache is cleaning.
				sleep ( 2 );
			} 
		}
		
		$is_disabled = FALSE;
		
		if ( get_option( 'wp_cassify_disable_authentication' ) == 'disabled' ) {
			$is_disabled = TRUE;
		}
		else {
			$is_disabled = FALSE;
		}
		
		$create_user_if_not_exist = FALSE;	
		
		if ( get_option( 'wp_cassify_create_user_if_not_exist' ) == 'create_user_if_not_exist' ) {
			$create_user_if_not_exist = TRUE;
		}
		else {
			$create_user_if_not_exist = FALSE;
		}	
		
		$wp_cassify_ssl_cipher =  get_option( 'wp_cassify_ssl_cipher' );
		
		if (! empty( $wp_cassify_ssl_cipher ) ) {
			$wp_cassify_ssl_cipher_selected = $wp_cassify_ssl_cipher;
		}
		else {
			$wp_cassify_ssl_cipher_selected = "1";
		}
		
		$wp_cassify_allow_deny_order = get_option( 'wp_cassify_allow_deny_order' );
		
		if (! empty( $wp_cassify_allow_deny_order ) ) {
			$wp_cassify_allow_deny_order_selected = $wp_cassify_allow_deny_order;
		}
		else {
			$wp_cassify_allow_deny_order_selected = $this->wp_cassify_default_allow_deny_order;
		}
		
		$wp_cassify_autorization_rules = unserialize( get_option( 'wp_cassify_autorization_rules' ) );
		
		if ( ( is_array( $wp_cassify_autorization_rules ) ) && ( count( $wp_cassify_autorization_rules ) > 0 ) ) {
			foreach ( $wp_cassify_autorization_rules as $rule_key => $rule_value ) {
				$wp_cassify_autorization_rules[ $rule_key ] = stripslashes( $rule_value );  
			}
		}
		else {
			$wp_cassify_autorization_rules = array();
		}		
?>
		<div class="wrap">
		<h2><?php echo $plugin_datas['Name'] ?></h2>

		<form method="post" action="options-general.php?page=wp-cassify.php">
			<?php settings_fields( 'wp-cassify-settings-group' ); ?>
			<?php do_settings_sections( 'wp-cassify-settings-group' ); ?>
			<table class="optiontable form-table">
				<tr valign="top">
					<th scope="row"><label for="wp_cassify_base_url">CAS Server base url</label></th>
					<td>
						<input type="text" id="wp_cassify_base_url" name="wp_cassify_base_url" class="post_form" value="<?php echo esc_attr( get_option( 'wp_cassify_base_url' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">Example : https://you-cas-server/cas/ </span>
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
					<th scope="row">SSL Cipher used for query CAS Server with HTTPS Webrequest</th>
					<td>
						<select name="wp_cassify_ssl_cipher" class="post_form">
							<?php foreach ( $this->wp_cassify_default_ssl_cipher_values as $cipher_id => $cipher_name ) { ?>
								<?php if ( $cipher_id == $wp_cassify_ssl_cipher_selected ) { ?>
									<option value="<?php echo $cipher_id; ?>" selected><?php echo $cipher_name; ?></option>
								<?php } else { ?>
									<option value="<?php echo $cipher_id; ?>"><?php echo $cipher_name; ?></option>
								<?php } ?>						
							<?php } ?>
						</select>
						<br /><span class="description">Default value : <?php echo $wp_cassify_default_ssl_cipher_values["1"]; ?></span>
					</td>
				</tr>	
				<tr valign="top">
					<th scope="row"><label for="wp_cassify_redirect_url_after_logout">Service logout redirect url</label></th>
					<td>
						<input type="text" id="wp_cassify_redirect_url_after_logout" name="wp_cassify_redirect_url_after_logout" class="post_form" value="<?php echo esc_attr( get_option( 'wp_cassify_redirect_url_after_logout' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">The blog home url is used when this option is not set .Url where the CAS Server redirect after logout. CAS Server must be configured correctly (see : followServiceRedirects option in JASIG documentation) </span>
					</td>
				</tr>								
				<tr valign="top">
					<td colspan="2">Update servlets name only if you have customized your CAS Server.</td>
				</tr>
				<tr valign="top">
					<th scope="row">Name of the login servlet (Default : <?php echo $this->wp_cassify_default_login_servlet; ?>)</th>
					<td><input type="text" id="wp_cassify_login_servlet" name="wp_cassify_login_servlet" class="post_form" value="<?php echo esc_attr( get_option( 'wp_cassify_login_servlet' ) ); ?>" size="40" class="regular-text" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Name of the logout servlet (Default : <?php echo $this->wp_cassify_default_logout_servlet; ?>)</th>
					<td><input type="text" id="wp_cassify_logout_servlet" name="wp_cassify_logout_servlet" class="post_form" value="<?php echo esc_attr( get_option( 'wp_cassify_logout_servlet' ) ); ?>" size="40" class="regular-text" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Name of the service validate servlet (Default : <?php echo $this->wp_cassify_default_service_validate_servlet; ?>)</th>
					<td><input type="text" id="wp_cassify_service_validate_servlet" name="wp_cassify_service_validate_servlet" class="post_form" value="<?php echo esc_attr( get_option( 'wp_cassify_service_validate_servlet' ) ); ?>" size="40" class="regular-text" /></td>
				</tr>		
				<tr valign="top">
					<th scope="row">Xpath query used to extract cas user id during parsing</th>
					<td>
						<input type="text" id="wp_cassify_xpath_query_to_extact_cas_user" name="wp_cassify_xpath_query_to_extact_cas_user" class="post_form" value="<?php echo esc_attr( get_option( 'wp_cassify_xpath_query_to_extact_cas_user' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">(Default : <?php echo $this->wp_cassify_default_xpath_query_to_extact_cas_user; ?>)</span>
					</td>
				</tr>	
				<tr valign="top">
					<th scope="row">Xpath query used to extract cas user attributes during parsing</th>
					<td>
						<input type="text" id="wp_cassify_xpath_query_to_extact_cas_attributes" name="wp_cassify_xpath_query_to_extact_cas_attributes" class="post_form" value="<?php echo esc_attr( get_option( 'wp_cassify_xpath_query_to_extact_cas_attributes' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">(Default : <?php echo $this->wp_cassify_default_xpath_query_to_extact_cas_attributes; ?>)</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Cas user attributes you want to populate into session.</th>
					<td>
						<input type="text" id="wp_cassify_attributes_list" name="wp_cassify_attributes_list" class="post_form" value="<?php echo esc_attr( get_option( 'wp_cassify_attributes_list' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">You must write attribute name separated by comma like this : attribute_1,attribute_2,attribute_3</span>
					</td>
				</tr>			
				<tr valign="top">
					<th scope="row">Build authorizaton rules</th>
					<td>
						<span class="description">Example rule syntax (Refer to plugin documentation) : (CAS{cas_user_id} -EQ "toto") -AND (CAS{courriel} -CONTAINS "crdp-aquitaine.fr")</span>
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
						<br /><span class="description">Default value : <?php echo $wp_cassify_default_allow_deny_order[0]; ?></span>
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
						<?php if ( ( is_array( $wp_cassify_autorization_rules )  ) && ( count( $wp_cassify_autorization_rules ) > 0 ) ) { ?>
						<?php 	foreach ( $wp_cassify_autorization_rules as $rule_key=>$rule_value ) { ?>
						<?php 		echo "<option value='$rule_value'>$rule_value</option>"; ?>
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
						<input type="text" id="wp_cassify_redirect_url_if_not_allowed" name="wp_cassify_redirect_url_if_not_allowed" class="post_form" value="<?php echo esc_attr( get_option( 'wp_cassify_redirect_url_if_not_allowed' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">Url where to redirect if user is not allowed to connect according to autorization rules below. If it's blog page, this page does not require authenticated access. The blog home url is used when this option is not set.</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="wp_cassify_redirect_url_white_list">White List URL(s)</label></th>
					<td>
						<input type="text" id="wp_cassify_redirect_url_white_list" name="wp_cassify_redirect_url_white_list" class="post_form" value="<?php echo esc_attr( get_option( 'wp_cassify_redirect_url_white_list' ) ); ?>" size="40" class="regular-text" />
						<br /><span class="description">List of URL(s) that don't intercepted by CAS Authentication. Use ';' as URL separator.</span>
					</td>
				</tr>																										
			</table>
			<?php submit_button( 'Save options', 'primary', 'wp_cassify_save_options', FALSE, array( 'id' => 'wp_cassify_save_options' ) ); ?>			
		</form>
		</div>
		<?php
	}
}
?>
