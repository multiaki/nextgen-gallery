jQuery.fn.nggSlideshow = function ( args ) { 
    
    var defaults = { id:    1,
                     width: 320,
                     height: 240,
                     fx: 	'fade',
                     domain: '',
                     timeout: 5000, };
                     
    var s = jQuery.extend( {}, defaults, args);
    
    var obj = this.selector;
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
                jQuery( obj ).append( ImageResize(stack.shift(), s.width, s.height) );
                i++;
            }
            
            // hide the loader icon
        	jQuery( obj + '-loader' ).empty().remove();
            
            // Start the slideshow
            jQuery(obj + ' img:first').fadeIn(1000, function() {
           	    // Start the cycle plugin
            	jQuery( obj ).cycle( {
            		fx: 	s.fx,
                    containerResize: 1,
                    fit: 1,
                    timeout: s.timeout,
                    next:   obj,
                    before: jCycle_onBefore
            	});
            });
            
		}
	});

    //Resize Image and keep ratio on client side, better move to server side later
    function ImageResize(src, maxWidth , maxHeight) {
        
        var img = new Image();
            img.src = src;
             
        var height = maxHeight,
        	width = maxWidth;
        if (img.height >= img.width)
        	width = Math.floor( Math.ceil(img.width / img.height * maxHeight) );
        else
        	height = Math.floor( Math.ceil(img.height / img.width * maxWidth) );
        
        jQuery( img ).css({
          'display': 'none',
          'height': height,
          'width': width
        });
        
        return img;
	};

    // add images to slideshow step by step
    function jCycle_onBefore(curr, next, opts) {
        if (opts.addSlide)
            if (stack.length) {
                var next_img = ImageResize(stack.shift(), s.width, s.height)
                jQuery( next_img ).bind('load', function() { 
                    opts.addSlide(this);                     
                });
            }
    }; 
}