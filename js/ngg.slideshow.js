function nggStartSlideshow( obj, id, width, height) { 
    
    var obj = '#' + obj;
    var stack = [];
	var url = 'http://localhost/wpdev/index.php?callback=json&api_key=true&format=json&method=gallery&id=' + id;

	jQuery.getJSON(url, function(r){
		if (r.stat == "ok"){
		  
            var i = 0; 
                            
            for (img in r.images) {
                i++;
				var photo = r.images[img];
				var alt   = photo['alttext'].replace(/<("[^"]*"|'[^']*'|[^'">])*>/gi,"");
				var title = photo['description'].replace(/<("[^"]*"|'[^']*'|[^'">])*>/gi,"");
				var src   = decodeURI( photo['imageURL'] );
                //populate images
			    jQuery( obj ).append( "<img src='" + src + "' width='" + width + "' alt='" + alt + "'/>" );
                if (i == 2)
                    break;
          
            }
            
            // preload images into an array; we will preload id 3 and higher
            for ( img in r.images ) { 
                var new_img = new Image( );
                var photo = r.images[img];
                
                new_img.src = decodeURI( photo['imageURL'] );
                new_img.width = width;
                new_img.height = height;
                new_img.alt = photo['alttext'].replace(/<("[^"]*"|'[^']*'|[^'">])*>/gi,"");
                new_img.title = photo['description'].replace(/<("[^"]*"|'[^']*'|[^'">])*>/gi,"");
                
                jQuery( new_img ).bind('load', function() {
                    stack.push(this); 
                }); 
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
            		height: height,
                    before: jCycle_onBefore 
            	});
            });            
            
		}
	});

    // add images to slideshow 
    function jCycle_onBefore(curr, next, opts) { 
        if (opts.addSlide) // <-- important! 
            while(stack.length) {
                opts.addSlide(stack.pop());
            }  
    }; 

}