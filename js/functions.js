jQuery.ready(function() {
	
	function bracketsAreBalanced(s, bracketOpenType, bracketClosingType) {
		
	  // bracketOpenType => '[' or '('
	  // bracketClosingType => ']' or ')'  
	  var open = ( arguments.length > 1 ) ? arguments[1] : bracketOpenType;
	  var close = ( arguments.length > 2 ) ? arguments[2] : bracketClosingType;  
	  var c = 0;
	  
	  for(var i = 0; i < s.length; i++) {
		var ch = s.charAt(i);
		if ( ch == open ) {
		  c++;
		}
		else if ( ch == close ) {
		  c--;
		  if ( c < 0 ) return false;
		}
	  }
	  
	  return c == 0;
	}
});

jQuery( '#wp_cassify_add_rule' ).click(function ( evt ) {
	
	var wp_cassify_add_rule_option = jQuery( '#wp_cassify_autorization_rule' ).val();
	var wp_cassify_rule_type = jQuery( '#wp_cassify_rule_type' ).val();
	var wp_cassify_last_rule_index = jQuery('#wp_cassify_autorization_rules option:last-child').val();
	
	jQuery( '#wp_cassify_autorization_rules' )
         .append(jQuery( "<option></option>" )
         .attr( "value", wp_cassify_rule_type + '|' + wp_cassify_add_rule_option ) 
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

jQuery( '#wp_cassify_save_options' ).click(function ( evt ) {
	
	jQuery('#wp_cassify_autorization_rules option').prop('selected', true);
});

jQuery( '#wp_cassify_autorization_rules' ).dblclick(function ( evt ) {
	
	jQuery('#wp_cassify_autorization_rule').val( jQuery('#wp_cassify_autorization_rules option:selected').val().split( '|' )[1] );
	jQuery('#wp_cassify_autorization_rules option:selected').remove();
	
	evt.preventDefault();	
	return false;
});

