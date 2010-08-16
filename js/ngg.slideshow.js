function nggStartSlideshow( args ) { 
    
    var defaults = { obj: 'ngg-slideshow',
                     id: 1, 
                     width: 320,
                     height: 240,
                     domain: '',
                     timeout: 5000, };
                     
    var s = jQuery.extend( {}, defaults, args);
    
    var obj = '#' + s.obj;
    var stack = [];
    var url = s.domain + 'index.php?callback=json&api_key=true&format=json&method=gallery&id=' + s.id;

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
                jQuery( obj ).append( "<img style='display:none;height:" + s.height + "px' src='" + stack.shift() + "'/>"  );
                i++;
            }

            // hide the loader icon
        	jQuery( obj + '-loader' ).empty().remove();
            
            // Start the slideshow
            jQuery(obj + ' img:first').fadeIn(1000, function() {
           	    // Start the cycle plugin
            	jQuery( obj ).cycle( {
            		fx: 	'fade',
                    containerResize: 1,
                    height: s.height,
                    fit: 1,
                    timeout: s.timeout,
                    next:   obj,
                    before: jCycle_onBefore
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