<?php
/*  
 * Copyright 2006 Vincent Prat  (email : vpratfr@yahoo.fr)
*/

// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

if (!function_exists('ngg_do_picture_shortcode')) {

/**
 * Function to show a single picture:
 *     [picture id="10" caption="none|alttext|desc" float="|left|right" width="" height="" mode="|watermark|web20" /]
 *
 * where
 *  - id is one picture id
 *  - caption is the text to put under the thumbnail
 *  - float is the CSS float property to apply to the thumbnail
 *  - width is width of the single picture you want to show (original width if this parameter is missing)
 *  - height is height of the single picture you want to show (original height if this parameter is missing)
 *  - mode is one of none, watermark or web20 (transformation applied to the picture)
 * 
 * If the tag contains some text, this will be inserted as an additional caption to the picture too. Example:
 * 		[picture id="10" caption="alttext"]This is an additional caption[/picture]
 * This tag will show a picture with under it two HTML span elements containing respectively the alttext of the picture 
 * and the additional caption specified in the tag.   
 */
function ngg_do_picture_shortcode($atts, $content=null) {
	global $nggRewrite;

	$out = '';

	// Extract attributes
	extract(shortcode_atts(array(
		'id' 		=> '',
		'caption' 	=> 'none',
		'float'		=> '',
		'width'		=> '',
		'height'	=> '',
		'mode'		=> ''
	), $atts));

	// Some error checks
	if ($id=='')
		return "<p style='color: red; border: 1px solid red;'>A picture ID must be supplied for the shortcode [picture]</p>";

	if ($caption != 'none' && $caption != 'alttext' && $caption != 'desc')
		$caption != 'none';

	if ($mode != '' && $mode != 'watermark' && $mode != 'web20')
		$mode == '';

	// get picture data
	$picture = nggImageDAO::find_image($id);

	// Check picture existance
	if ($picture==null) {
		$out .= '<div class="ngg-singlepic" style="color: red;">' 
			.  sprintf(__("[picture id='%s' /] &raquo; Image does not exist!", 'nggallery'), $id)  
			. '</div>';
		return $out;
	}

	// check for cached picture
	if (($ngg_options['imgCacheSinglePic']) && ($post->post_status == 'publish') && ($width!='') && ($height!=''))
		$cache_url = $picture->cached_singlepic_file($width, $height, $mode );

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

	// Start building output
	$out = '<div class="ngg-singlepic '. $float .'">';

	// add fullsize picture as link if original size was not requested
	$out .= '<a href="' . $picture->imageURL . '" title="' . stripslashes($picture->description) . '" ' . $picture->get_thumbcode("singlepic".$id) . ' >';

	if (!$cache_url)
		$out .= '<img src="'.NGGALLERY_URLPATH.'nggshow.php?pid='.$id.'&amp;width='.$width.'&amp;height='.$height.'&amp;mode='.$mode.'" alt="'.stripslashes($picture->alttext).'" title="'.stripslashes($picture->alttext).'" />';
	else
		$out .= '<img src="'.$cache_url.'" alt="'.stripslashes($picture->alttext).'" title="'.stripslashes($picture->alttext).'" />';

	$out .= '</a>';

	if ($caption == "alttext") {
		$out .= '<div>' . html_entity_decode(stripslashes($picture->alttext)) . '</div> ' . "\n";
	} else if ($caption == "desc") {
		$out .= '<div>' . html_entity_decode(stripslashes($picture->description)) . '</div> ' . "\n";
	} 
	
	if ($content!=null)
		$out .= '<div>' . $content . '</div>' . "\n";

	// Apply filter
	$out = apply_filters('ngg_show_singlepic_content', $out, $picture );
	
	// End of output generation
	$out .= '</div>';

	return $out;
}

}

?>