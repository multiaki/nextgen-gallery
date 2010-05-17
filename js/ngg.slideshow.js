function nggStartSlideshow( obj, id, width, height) { 
    
    var obj = '#' + obj;

	var url = 'http://localhost/wpdev/index.php?callback=json&format=json&method=gallery&id=' + id;

	jQuery.getJSON(url, function(r){
		if (r.stat == "ok"){
		  
            for ( img in r.images) {
				var photo = r.images[img];
				var alt   = photo['alttext'].replace(/<("[^"]*"|'[^']*'|[^'">])*>/gi,"");
				var title = photo['description'].replace(/<("[^"]*"|'[^']*'|[^'">])*>/gi,"");
				var src   = decodeURI( photo['imageURL'] );
                //populate images
			    jQuery( obj ).append( "<div class='ngg-slide'><img src='" + src + "' width='" + width + "' alt='" + alt + "'/></div>" );                
            }
            
            // Setup the max-height
            jQuery( obj + ' .ngg-slideshow div img' ).each( function () {
            	jQuery( this ).css( 'max-height', height );
            });
            
            // Start the slideshow
            jQuery( obj + ' img' ).bind( 'load', function() {
                
                // hide the loader icon
            	jQuery( obj + '-loader' ).remove();
                
            	// Start the cycle plugin
            	jQuery( obj ).cycle( {
            		fx: 	'fade',
            		height: height
            	});
            });            
            
		}
	});
}