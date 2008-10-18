<?php
/*  
 * Copyright 2008 Vincent Prat  (email : vpratfr@yahoo.fr)
*/

// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { 	die('You are not allowed to call this page directly.'); }

if (!function_exists('ngg_do_thumb_shortcode')) {

/**
* Function to show a thumbnail or a set of thumbnails with shortcode of type:
*     [thumb id="1,2,4,5,..." caption="none|alttext|desc" float="|left|right" /]
* where 
*  - id is one or more picture ids
*  - caption is the text to put under the thumbnail
*  - float is the CSS float property to apply to the thumbnail
*/
function ngg_do_thumb_shortcode( $atts, $content=null ) {	
	global $nggRewrite;

	// Extract attributes
	extract(shortcode_atts(array(
		'id' 		=> '',
		'caption' 	=> 'none',
		'float'		=> ''
	), $atts));
	
	// make an array out of the ids
	$pids = explode( ',', $id );
	
	// Some error checks
	if ( count($pids) == 0 )
		return __('[Pictures not found]','nggallery');
	
	if ( $caption != 'none' && $caption != 'alttext' && $caption != 'desc')
		$caption = 'none';		
	
	// Get ngg options
	$ngg_options = nggGallery::get_option('ngg_options');
	
	// set thumb size 
 	$thumbwidth  = $ngg_options['thumbwidth'];
	$thumbheight = $ngg_options['thumbheight'];
		
	$thumbsize = '';
	if ($ngg_options['thumbfix'])  $thumbsize = 'style="width:'.$thumbwidth.'px; height:'.$thumbheight.'px;"';
	if ($ngg_options['thumbcrop']) $thumbsize = 'style="width:'.$thumbwidth.'px; height:'.$thumbwidth.'px;"';
	
	// a description below the picture, require fixed width
	$setwidth   = ( $caption != 'none' ) ? 'style="width:' . $thumbwidth . 'px;"' : '';
	$class_desc = ( $caption != 'none' ) ? 'desc' : '';

	// add float to img
	switch ($float) {
		case 'left': 
			$float=' ngg-left';
			break;

		case 'right': 
			$float=' ngg-right';
			break;

		case 'center': 
			$float=' ngg-center';
			break;

		default: 
			$float='';
			break;
	}
		
	// Start building the output HTML
	$out = '<div class="ngg-galleryoverview">';
	
	$pictures = nggdb::find_images_in_list($pids);
	
	// For each picture ID
	foreach ($pictures as $picture) {
	
		// choose link between imagebrowser or effect
		$link = ($ngg_options['galImgBrowser']) ? $nggRewrite->get_permalink(array('pid'=>$picture->pid)) : $picture->filename;
		$link = apply_filters('ngg_create_gallery_link', $link, $picture);
		
		// get the effect code
		$thumbcode = $picture->get_thumbcode('ngg');
		
		// create output
		$out .= '<div class="ngg-gallery-thumbnail-box ' . $class_desc . ' ' . $float . '">' . "\n\t";
		$out .= '<div class="ngg-gallery-thumbnail" ' . $setwidth . ' >' . "\n\t";
		$out .= '<a href="' . $link . '" title="' . stripslashes($picture->description) . '" ' . $thumbcode . ' >';
		$out .= '<img title="' . stripslashes($picture->alttext) . '" alt="' . stripslashes($picture->alttext) . '" ';
		$out .= 'src="' . $picture->thumbURL . '" ' . $thumbsize . ' />';
		$out .= '</a>' . "\n";
		
		if ($caption == "alttext")
			$out .= '<div>' . html_entity_decode(stripslashes($picture->alttext)) . '</div>' . "\n";
		else if ($caption == "desc")
			$out .= '<div>' . html_entity_decode(stripslashes($picture->description)) . '</div>' . "\n";

		// add filter for the output
		$out  = apply_filters('ngg_inner_gallery_thumbnail', $out, $picture);		
		$out .= '</div>'. "\n" .'</div>'."\n";
		$out  = apply_filters('ngg_after_gallery_thumbnail', $out, $picture);
	}
	
	$out .= '</div>';
	
	return $out;
}

}

?>