/*
 * Implementation of jQuery UI Autocomplete
 * see http://jqueryui.com/demos/autocomplete/
 * Version:  1.0.0
 * Author : Alex Rabe
 */ 
jQuery.fn.nggAutocomplete = function ( args ) { 
    
    var defaults = { type: 'image',
                     domain: '',
                     limit: 50 };
    
    var s = jQuery.extend( {}, defaults, args);
    
    var settings = { method: 'autocomplete',
                    type: s.type,
                    format: 'json',
                    callback: 'json',
                    limit: s.limit };
                     
    var obj = this.selector;
    var id  = jQuery(this).attr('id');
    var cache = {}, lastXhr;
    
    //hide first the drop down field
    jQuery(obj).hide();
    jQuery(obj).after('<input name="' + id + '_ac" type="text" id="' + id + '_ac"/>');
    jQuery(obj + "_ac").autocomplete({
		source: function( request, response ) {
			var term = request.term;
			if ( term in cache ) {
				response( cache[ term ] );
				return;
			}
            // adding more $_GET parameter
            request = jQuery.extend( {}, settings, request);
			lastXhr = jQuery.getJSON( s.domain, request, function( data, status, xhr ) {
				// add term to cache
                cache[ term ] = data;
				if ( xhr === lastXhr )
					response( data );
			});
        },
        minLength: 0,
        select: function( event, ui ) {
            // adding this to the dropdown list
            jQuery(obj).append( new Option(ui.item.label, ui.item.id) );
            // now select it
            jQuery(obj).val(ui.item.id)
	   }
	});

   	jQuery(obj + "_ac").click(function() {
        // pass empty string as value to search for, displaying all results
        jQuery(obj + "_ac").autocomplete('search', jQuery(obj + "_ac").val() );
	});
}