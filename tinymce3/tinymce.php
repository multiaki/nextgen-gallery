<?php

/**
 * @title TinyMCE V3 Button Integration (for Wp2.5)
 * @author Alex Rabe
 */

function ngg_addbuttons() {

	// Don't bother doing this stuff if the current user lacks permissions
	if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) return;
	
	// Check for NextGEN capability
	if ( !current_user_can('NextGEN Use TinyMCE') ) return;
	 
	// Add only in Rich Editor mode
	if ( get_user_option('rich_editing') == 'true') {
	 
	// add the button for wp21 in a new way
		add_filter("mce_external_plugins", "nextgen_button_plugin", 5);
		add_filter('mce_buttons', 'nextgen_button', 5);
	}
}

// used to insert button in wordpress 2.1x editor
function nextgen_button($buttons) {

	array_push($buttons, "separator", "NextGEN");

	return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function nextgen_button_plugin($plugin_array) {    

	$plugin_array = array( 'NextGEN' => NGGALLERY_URLPATH.'tinymce3/editor_plugin.js' );
	
	return $plugin_array;
}

// init process for button control
add_action('init', 'ngg_addbuttons');

?>