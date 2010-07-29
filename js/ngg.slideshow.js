function nggStartSlideshow( obj, id, width, height, domain) { 
    
    var obj = '#' + obj;
    var stack = [];
    var url = domain + 'index.php?callback=json&api_key=true&format=json&method=gallery&id=' + id;

	jQuery.getJSON(url, function(r){
		if (r.stat == "ok"){
             
            for (img in r.images) {
				var photo = r.images[img];
                //populate images into an array
                stack.push( decodeURI( photo['imageURL'] ) );
            }
            
            // push the first three images out
            if (stack.length)
                jQuery( obj ).append( "<img style='display:none;' src='" + stack.shift() + "'/>"  );
            if (stack.length)
                jQuery( obj ).append( "<img style='display:none;' src='" + stack.shift() + "'/>"  );
            if (stack.length)
                jQuery( obj ).append( "<img style='display:none;' src='" + stack.shift() + "'/>"  );

            // Start the slideshow
            jQuery( obj + ' img' ).bind( 'load', function() {
                
                // hide the loader icon
            	jQuery( obj + '-loader' ).empty().remove();
                
                jQuery(obj + ' img:first').fadeIn(1000, function() {
               	    // Start the cycle plugin
                	jQuery( obj ).cycle( {
                		fx: 	'fade',
                        timeout: 10000,
                        next:   obj,
                        before: jCycle_onBefore
                	});
                });
                                
            });            
            
		}
	});

    // add images to slideshow step by step
    function jCycle_onBefore(curr, next, opts) {
        if (opts.addSlide)
            if (stack.length) {
                var img = new Image(); 
                img.src = stack.shift();
                jQuery( img ).css( 'display', 'none' ); 
                jQuery( img ).bind('load', function() { 
                    opts.addSlide(this); 
                });
            }
    }; 
}