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
            var i = 1;
            while (stack.length && i <= 3) {
                jQuery( obj ).append( "<img style='display:none;height:" + height + "px' src='" + stack.shift() + "'/>"  );
                i++;
            }

            // Start the slideshow
            jQuery( obj + ' img' ).bind( 'load', function() {
                
                // hide the loader icon
            	jQuery( obj + '-loader' ).empty().remove();
                
                jQuery(obj + ' img:first').fadeIn(1000, function() {
                    //Debug mode
                    //jQuery.fn.cycle.debug = true;
               	    // Start the cycle plugin
                	jQuery( obj ).cycle( {
                		fx: 	'fade',
                        containerResize: 1,
                        height: height,
                        fit: 1,
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