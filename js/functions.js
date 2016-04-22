jQuery( document ).ready(function() {

	// Some fields state initialization...
	jQuery( '#wp_cassify_fixed_datetime_limit' ).datepicker();
	jQuery( '#wp_cassify_fixed_datetime_limit' ).hide();
	
    jQuery( '#wp_cassify_custom_user_meta' ).hide();

	// for metaboxes
	jQuery(".if-js-closed").removeClass("if-js-closed").addClass("closed");
	postboxes.add_postbox_toggles( 'wp-cassify');

	// scroll to last submit button clicked.
	if( typeof wp_cassify_screen_data.scrollToId != 'undefined') {

		var wp_cassify_id_to_scroll = '';
		
		if ( wp_cassify_screen_data.scrollToId == 'wp_cassify_send_notification_test_message' ) {
			wp_cassify_id_to_scroll = '#wp_cassify_metabox_notifications_settings';
		}
		else {
			wp_cassify_id_to_scroll = '#' +  wp_cassify_screen_data.scrollToId.replace( 'save_options', 'metabox' );
		}

		jQuery( 'html, body' ).animate({
        		scrollTop: jQuery( wp_cassify_id_to_scroll ).offset().top
    		}, 
    		500
		);
	}
	
	// Disabled field if checkbox unchecked
    if( jQuery( '#wp_cassify_notifications_smtp_auth' ).is(':checked') ) {
    	jQuery( '#wp_cassify_notifications_encryption_type' ).prop( 'disabled', false );
    }
    else {
    	jQuery( '#wp_cassify_notifications_encryption_type' ).prop( 'disabled', 'disabled' );
    }	
});

/*
*	Authorizations rules functions
*/
jQuery( '#wp_cassify_add_rule' ).click( function ( evt ) {
	
	var wp_cassify_add_rule_option = jQuery( '#wp_cassify_autorization_rule' ).val();
	var wp_cassify_rule_type = jQuery( '#wp_cassify_rule_type' ).val();
	var wp_cassify_last_rule_index = jQuery( '#wp_cassify_autorization_rules option:last-child' ).val();
	
	jQuery( '#wp_cassify_autorization_rules' )
         .append(jQuery( '<option></option>' )
         .attr( 'value', wp_cassify_rule_type + '|' + wp_cassify_add_rule_option ) 
         .text( wp_cassify_rule_type + '|' + wp_cassify_add_rule_option )); 
    
    jQuery( '#wp_cassify_autorization_rule' ).val('');
       
	evt.preventDefault();
	return false;
});

jQuery( '#wp_cassify_remove_rule' ).click(function ( evt ) {
	
	jQuery('#wp_cassify_autorization_rules option:selected').remove();
	
	evt.preventDefault();	
	return false;
});

jQuery( '#wp_cassify_autorization_rules' ).dblclick( function ( evt ) {
	
	jQuery('#wp_cassify_autorization_rule' ).val( jQuery( '#wp_cassify_autorization_rules option:selected' ).val().split( '|' )[ 1 ] );
	jQuery('#wp_cassify_rule_type' ).val( jQuery( '#wp_cassify_autorization_rules option:selected' ).val().split( '|' )[ 0 ] );
	jQuery('#wp_cassify_autorization_rules option:selected' ).remove();
	
	evt.preventDefault();	
	return false;
});

/*
*	User roles rules functions
*/
jQuery( '#wp_cassify_add_user_role_rule' ).click( function ( evt ) {

	var wp_cassify_user_role = jQuery( '#wp_cassify_default_user_roles option:selected' ).val();
	var wp_cassify_user_role_rule = jQuery( '#wp_cassify_user_role_rule' ).val();
	
	jQuery( '#wp_cassify_user_role_rules' )
         .append(
         	jQuery( '<option></option>' )
         		.attr( 'value', wp_cassify_user_role + '|' + wp_cassify_user_role_rule ) 
         		.text( wp_cassify_user_role + '|' + wp_cassify_user_role_rule )
     		); 	

	evt.preventDefault();
	return false;
});

jQuery( '#wp_cassify_remove_user_role_rule' ).click( function ( evt ) {
	
	jQuery( '#wp_cassify_user_role_rules option:selected' ).remove();
	
	evt.preventDefault();	
	return false;
});

jQuery( '#wp_cassify_user_role_rules' ).dblclick( function ( evt ) {

	jQuery( '#wp_cassify_user_role_rule' ).val( jQuery( '#wp_cassify_user_role_rules option:selected' ).val().split( '|' )[ 1 ] );
	jQuery( '#wp_cassify_default_user_roles' ).val( jQuery( '#wp_cassify_user_role_rules option:selected' ).val().split( '|' )[ 0 ] );
	jQuery( '#wp_cassify_user_role_rules option:selected' ).remove();

	evt.preventDefault();	
	return false;
});

/*
*	Attributes mapping functions
*/
jQuery( '#wp_cassify_add_user_attribute_mapping' ).click( function ( evt ) {

	var wp_cassify_user_meta = jQuery( '#wp_cassify_wordpress_user_meta_list option:selected' ).val();
	var wp_cassify_cas_user_attribute = jQuery( '#wp_cassify_cas_user_attribute' ).val();
	
	if ( jQuery( '#wp_cassify_wordpress_user_meta_list' ).val() == 'custom_user_meta' ) {
		wp_cassify_user_meta = jQuery( '#wp_cassify_custom_user_meta' ).val();
	}

	jQuery( '#wp_cassify_user_attributes_mapping_list' )
         .append(
         	jQuery( '<option></option>' )
         		.attr( 'value', wp_cassify_user_meta + '|' + wp_cassify_cas_user_attribute ) 
         		.text( wp_cassify_user_meta + '|' + wp_cassify_cas_user_attribute )
     		);
    
    jQuery( '#wp_cassify_cas_user_attribute' ).val( '' );
    jQuery( '#wp_cassify_custom_user_meta' ).val( '' );

	evt.preventDefault();
	return false;
});

jQuery( '#wp_cassify_remove_user_attribute_mapping' ).click( function ( evt ) {
	
	jQuery( '#wp_cassify_user_attributes_mapping_list option:selected' ).remove();
	
	evt.preventDefault();	
	return false;
});

jQuery( '#wp_cassify_user_attributes_mapping_list' ).dblclick( function ( evt ) {

	var wp_cassify_user_attributes_mapping = jQuery( '#wp_cassify_user_attributes_mapping_list option:selected' ).val().split( '|' )[ 0 ];
	
	if ( ( jQuery( '#wp_cassify_wordpress_user_meta_list option[value="' + wp_cassify_user_attributes_mapping  + '"]' ).length > 0 ) === false ) {
		jQuery( '#wp_cassify_wordpress_user_meta_list' ).val( 'custom_user_meta' );
		jQuery( '#wp_cassify_custom_user_meta' ).show();
		jQuery( '#wp_cassify_custom_user_meta' ).val( wp_cassify_user_attributes_mapping );
	}
	else {
		jQuery( '#wp_cassify_wordpress_user_meta_list' ).val( wp_cassify_user_attributes_mapping );
		jQuery( '#wp_cassify_custom_user_meta' ).hide();
	}

	jQuery( '#wp_cassify_cas_user_attribute' ).val( jQuery( '#wp_cassify_user_attributes_mapping_list option:selected' ).val().split( '|' )[ 1 ] );
	jQuery( '#wp_cassify_user_attributes_mapping_list option:selected' ).remove();

	evt.preventDefault();	
	return false;
});

jQuery( '#wp_cassify_wordpress_user_meta_list' ).change( function ( evt ) {
	
	if ( jQuery( '#wp_cassify_wordpress_user_meta_list' ).val() == 'custom_user_meta' ) {
		jQuery( '#wp_cassify_custom_user_meta' ).show();
	}
	else {
		jQuery( '#wp_cassify_custom_user_meta' ).hide();
	}
	
	evt.preventDefault();
	return false;
});

/*
*	Notifications parameters functions
*/
jQuery( '#wp_cassify_notifications_smtp_auth' ).change(function() {
    
    if( this.checked ) {
        jQuery( '#wp_cassify_notifications_encryption_type' ).prop('disabled', false);
    }
    else {
    	jQuery( '#wp_cassify_notifications_encryption_type' ).prop('disabled', 'disabled');
    }
    
	evt.preventDefault();
	return false;
});

/*
*	Notifications rules functions
*/
jQuery( '#wp_cassify_add_notification_rule' ).click( function ( evt ) {

	var wp_cassify_notification_action = jQuery( '#wp_cassify_notifications_actions option:selected' ).val();
	var wp_cassify_notification_rule = jQuery( '#wp_cassify_notification_rule' ).val();
	
	jQuery( '#wp_cassify_notification_rules' )
         .append(
         	jQuery( '<option></option>' )
         		.attr( 'value', wp_cassify_notification_action + '|' + wp_cassify_notification_rule ) 
         		.text( wp_cassify_notification_action + '|' + wp_cassify_notification_rule )
     		); 	

	evt.preventDefault();
	return false;
});

jQuery( '#wp_cassify_remove_notification_rule' ).click( function ( evt ) {
	
	jQuery( '#wp_cassify_notification_rules option:selected' ).remove();
	
	evt.preventDefault();	
	return false;
});

jQuery( '#wp_cassify_notification_rules' ).dblclick( function ( evt ) {

	jQuery( '#wp_cassify_notification_rule' ).val( jQuery( '#wp_cassify_notification_rules option:selected' ).val().split( '|' )[ 1 ] );
	jQuery( '#wp_cassify_notifications_actions' ).val( jQuery( '#wp_cassify_notification_rules option:selected' ).val().split( '|' )[ 0 ] );
	jQuery( '#wp_cassify_notification_rules option:selected' ).remove();

	evt.preventDefault();	
	return false;
});

/*
*	Expirations rules functions
*/
jQuery( '#wp_cassify_default_expirations_types' ).change( function ( evt ) {
	
	if ( jQuery( '#wp_cassify_default_expirations_types' ).val() == 'after_user_account_created_time_limit' ) {
		jQuery( '#wp_cassify_fixed_datetime_limit' ).hide();
		jQuery( '#wp_cassify_after_user_account_created_time_limit' ).show();
	}
	else {
		jQuery( '#wp_cassify_after_user_account_created_time_limit' ).hide();
		jQuery( '#wp_cassify_fixed_datetime_limit' ).show();
	}
	
	evt.preventDefault();
	return false;
});

jQuery( '#wp_cassify_add_expiration_rule' ).click( function ( evt ) {

	var wp_cassify_default_expirations_type = jQuery( '#wp_cassify_default_expirations_types option:selected' ).val();
	var wp_cassify_expiration_rule = jQuery( '#wp_cassify_expiration_rule' ).val();
	var wp_cassify_default_expirations_type_value = '';
	
	if ( jQuery( '#wp_cassify_default_expirations_types' ).val() == 'after_user_account_created_time_limit' ) {
		wp_cassify_default_expirations_type_value = jQuery( '#wp_cassify_after_user_account_created_time_limit' ).val();
	}
	else {
		jQuery( '#wp_cassify_after_user_account_created_time_limit' ).hide();
		wp_cassify_default_expirations_type_value = jQuery( '#wp_cassify_fixed_datetime_limit' ).val();
	}
	
	jQuery( '#wp_cassify_expiration_rules' )
         .append(
         	jQuery( '<option></option>' )
         		.attr( 'value', wp_cassify_default_expirations_type + '|' + wp_cassify_default_expirations_type_value + '|' + wp_cassify_expiration_rule ) 
         		.text( wp_cassify_default_expirations_type + '|' + wp_cassify_default_expirations_type_value + '|' + wp_cassify_expiration_rule )
     		); 	

	evt.preventDefault();
	return false;
});

jQuery( '#wp_cassify_remove_expiration_rule' ).click( function ( evt ) {
	
	jQuery( '#wp_cassify_expiration_rules option:selected' ).remove();
	
	evt.preventDefault();	
	return false;
});

jQuery( '#wp_cassify_expiration_rules' ).dblclick( function ( evt ) {

	var wp_cassify_default_expirations_type = jQuery( '#wp_cassify_expiration_rules option:selected' ).val().split( '|' )[ 0 ];
	var wp_cassify_default_expirations_type_value = jQuery( '#wp_cassify_expiration_rules option:selected' ).val().split( '|' )[ 1 ];

	jQuery( '#wp_cassify_expiration_rule' ).val( jQuery( '#wp_cassify_expiration_rules option:selected' ).val().split( '|' )[ 2 ] );
	jQuery( '#wp_cassify_default_expirations_types' ).val( wp_cassify_default_expirations_type );
	
	if ( wp_cassify_default_expirations_type == 'after_user_account_created_time_limit' ) {
		jQuery( '#wp_cassify_fixed_datetime_limit' ).hide();
		jQuery( '#wp_cassify_after_user_account_created_time_limit' ).show();
		jQuery( '#wp_cassify_after_user_account_created_time_limit' ).val( wp_cassify_default_expirations_type_value );
	}
	else {
		jQuery( '#wp_cassify_after_user_account_created_time_limit' ).hide();
		jQuery( '#wp_cassify_fixed_datetime_limit' ).show();
		jQuery( '#wp_cassify_fixed_datetime_limit' ).val( wp_cassify_default_expirations_type_value );
	}	

	jQuery( '#wp_cassify_expiration_rules option:selected' ).remove();

	evt.preventDefault();	
	return false;
});

/*
*	PLugin options saving
*/
jQuery( '[data-style="wp_cassify_save_options"]' ).click( function ( evt ) {
	
	jQuery( '#wp_cassify_autorization_rules option' ).prop( 'selected', true );
	jQuery( '#wp_cassify_user_role_rules option' ).prop( 'selected', true );
	jQuery( '#wp_cassify_user_attributes_mapping_list option' ).prop( 'selected', true );
	jQuery( '#wp_cassify_notification_rules option' ).prop( 'selected', true );
	jQuery( '#wp_cassify_expiration_rules option' ).prop( 'selected', true );

	if ( evt.target.id == 'wp_cassify_save_options_notifications_settings' ) {
		if ( jQuery.inArray( jQuery( '#wp_cassify_notifications_salt' ).val().length, [ 16, 24, 32 ] ) ) {
			alert( 'Salt error : only keys of sizes 16, 24 or 32 supported');
			
			evt.preventDefault();
			return false;
		}
		
		if ( jQuery( '#wp_cassify_notifications_smtp_password' ).val() != jQuery( '#wp_cassify_notifications_smtp_confirm_password' ).val() ) {
			alert( 'SMTP Password does not macth confirmation !');
			
			evt.preventDefault();
			return false;
		}		
	}
});