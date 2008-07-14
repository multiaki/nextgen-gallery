<?php
/*  Copyright 2006 Vincent Prat  (email : vpratfr@yahoo.fr)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//############################################################################
// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('You are not allowed to call this page directly.');
}
//############################################################################


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
	//--
	extract(shortcode_atts(array(
		'id' 		=> '',
		'caption' 	=> 'none',
		'float'		=> '',
		'width'		=> '',
		'height'	=> '',
		'mode'		=> ''
	), $atts));

	// Some error checks
	//--
	if ($id=='') {
		return "<p style='color: red; border: 1px solid red;'>A picture ID must be supplied for the shortcode [picture]</p>";
	}

	if ($caption!='none' && $caption!='alttext' && $caption!='desc') {
		return "<p style='color: red; border: 1px solid red;'>Invalid value for the caption parameter of the shortcode [picture]</p>";
	}

	if ($mode!='' && $mode!='watermark' && $mode!='web20') {
		return "<p style='color: red; border: 1px solid red;'>Invalid value for the mode parameter of the shortcode [picture]</p>";
	}

	// get picture data
	//--
	$picture = nggImageDAO::find_image($id);

	// check for cached picture
	//--
	if (($ngg_options['imgCacheSinglePic']) && ($post->post_status == 'publish') && ($width!='') && ($height!='')) {
		$cache_url = $picture->cached_singlepic_file($width, $height, $mode );
	} 

	// add float to img
	//--
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
	//--
	$out = '<div class="ngg-singlepic '. $float .'">';

	// add fullsize picture as link if original size was not requested
	//--
	$out .= '<a href="' . $picture->imagePath . '" title="' . stripslashes($picture->description)
			  . '" ' . $picture->get_thumbcode("singlepic".$id) . ' >';

	if (!$cache_url) {
		$out .= '<img src="'.NGGALLERY_URLPATH.'nggshow.php?pid='.$id.'&amp;width='.$width.'&amp;height='.$height.'&amp;mode='.$mode.'" alt="'.stripslashes($picture->alttext).'" title="'.stripslashes($picture->alttext).'" />';
	} else {
		$out .= '<img src="'.$cache_url.'" alt="'.stripslashes($picture->alttext).'" title="'.stripslashes($picture->alttext).'" />';
	}

	$out .= '</a>';

	if ($caption == "alttext") {
		$out .= '<div>' . html_entity_decode(stripslashes($picture->alttext)) . '</div> ' . "\n";
	} else if ($caption == "desc") {
		$out .= '<div>' . html_entity_decode(stripslashes($picture->description)) . '</div> ' . "\n";
	} 
	
	if ($content!=null) {
		$out .= '<div>' . $content . '</div>' . "\n";
	}

	// Apply filter
	//--
	$out = apply_filters('ngg_show_singlepic_content', $out, $picture );
	
	// End of output generation
	//--
	$out .= '</div>';

	return $out;
}

}

?>