(function($) {
nggAjax = {
		settings: {
			url: nggAjaxSetup.url, 
			type: "POST",
			action: nggAjaxSetup.action,
			operation : nggAjaxSetup.operation,
			nonce: nggAjaxSetup.nonce,
			ids: nggAjaxSetup.ids,
			timeout: 10000
		},
	
		run: function( index ) {
			var req = $.ajax({
				type: "POST",
			   	url: this.settings.url,
			   	data:"action=" + this.settings.action + "&operation=" + this.settings.operation + "&_wpnonce=" + this.settings.nonce + "&image=" + this.settings.ids[index],
			   	cache: false,
			   	timeout: 10000,
			   	success: function(msg){
			   		switch (msg) {
			   			case "-1":
					   		nggProgressBar.addNote( "You do not have the correct permission" );
						break;
			   			case "0":
					   		nggProgressBar.addNote( "Unexpected Error" );
						break;
			   			case "1":
					   		// show nothing, its better
						break;
						default:
							// Return the message
							nggProgressBar.addNote( "A failure occured", msg );
						break; 			   			
			   		}

			    },
			    error: function (msg) {
					nggProgressBar.addNote( "A failure occured", msg );
				},
				complete: function () {
					index++;
					nggProgressBar.increase( index );
					// parse the whole array
					if (index < nggAjax.settings.ids.length)
						nggAjax.run( index );
					else 
						nggProgressBar.finished();
				} 
			});
		},
	
		init: function() {
			percentPerStep = Math.round (100 / this.settings.ids.length ); 
			var index = 0;
			// start the ajax process
			this.run( index );			
		}
	}
}(jQuery));