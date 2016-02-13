
jQuery( document ).ready(function() {
    jQuery( '#wp_cassify_custom_user_meta' ).hide();
});

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

jQuery( '#wp_cassify_save_options' ).click( function ( evt ) {
	
	jQuery( '#wp_cassify_autorization_rules option' ).prop( 'selected', true );
	jQuery( '#wp_cassify_user_role_rules option' ).prop( 'selected', true );
	jQuery( '#wp_cassify_user_attributes_mapping_list option' ).prop( 'selected', true );
});