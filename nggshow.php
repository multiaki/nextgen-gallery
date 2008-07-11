<?php
/*  Copyright 2008 Alex Rabe

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

// Load wp-config
//--
$wpconfig = realpath("../../../wp-config.php");
if (!file_exists($wpconfig)) {
	die('wp-config is not there');
}
require_once($wpconfig);
global $wpdb;

// reference thumbnail class
//--
include_once('lib/ngg-thumbnail.lib.php');
include_once('lib/ngg-gallery.lib.php');

// get the plugin options
//--
$ngg_options = get_option('ngg_options');	

// Some parameters from the URL
//--
$pictureID = (int) $_GET['pid'];
$mode = attribute_escape($_GET['mode']);
$height = $_GET['height'];
$width = $_GET['width'];

// let's get the image data
//--
$picture  = new nggImage($pictureID);
$thumb = new ngg_Thumbnail($picture->absPath);

// Resize if necessary
//--
if (isset($height) && isset($width) && $width!='' && $height!='') {
	$thumb->resize($_GET['width'], $_GET['height']);
}

// Apply effects according to the mode parameter
//--
if ($mode == 'watermark') {
	if ($ngg_options['wmType'] == 'image') {
		$thumb->watermarkImgPath = $ngg_options['wmPath'];
		$thumb->watermarkImage($ngg_options['wmPos'], $ngg_options['wmXpos'], $ngg_options['wmYpos']); 
	} else if ($ngg_options['wmType'] == 'text') {
		$thumb->watermarkText = $ngg_options['wmText'];
		$thumb->watermarkCreateText($ngg_options['wmColor'], $ngg_options['wmFont'], $ngg_options['wmSize'], $ngg_options['wmOpaque']);
		$thumb->watermarkImage($ngg_options['wmPos'], $ngg_options['wmXpos'], $ngg_options['wmYpos']);  
	}
} else if ($mode == 'web20') {
	$thumb->createReflection(40,40,50,false,'#a4a4a4');
}

// Show thumbnail
//--
$thumb->show();
$thumb->destruct();

exit;
?>