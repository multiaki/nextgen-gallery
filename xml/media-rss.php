<?php
/**
* Media RSS presenting the pictures in counter chronological order.
* 
* @author Vincent Prat (http://www.vincentprat.info)
*
* @param mode The content we want to display (last_pictures|gallery|album). 
* 			  Defaults to last_pictures.
* 
* Parameters for mode = last_pictures
* 
* 	@param page The current picture ID (defaults to 0)
* 	@param show The number of pictures to include in one field (default 10) 
* 
* Parameters for mode = gallery
* 
* 	@param gid The gallery ID to show (defaults to first gallery)
* 	@param prev_next Whether to link to previous and next galleries (true|false).
* 					 Default to false.
* 
* Parameters for mode = album
* 
* 	@param aid The album ID to show
*/

// Load required files and set some useful variables
require_once(dirname(__FILE__) . "/../ngg-config.php");
require_once(dirname(__FILE__) . "/../lib/media-rss.php");

// Check we have the required GET parameters
$mode = $_GET["mode"];
if (!isset($mode) || $mode == '')
	$mode = last_pictures;

// Act according to the required mode
$rss = '';
if ($mode=='last_pictures') {
	
	// Get additional parameters
	$page = (int) $_GET["page"];	
	if (!isset($page) || $page == '') {
		$page = 0;
	}
	
	$show = (int) $_GET["show"];	
	if (!isset($show) || $show == '' || $show==0) {
		$show = 10;
	}
	
	$rss = nggMediaRss::get_last_pictures_mrss($page, $show);	
} else if ($mode=='gallery') {
		
	// Get all galleries
	$galleries = nggdb::find_all_galleries();
	
	if ( count($galleries) == 0 ) {
		header('content-type:text/plain;charset=utf-8');
		echo sprintf(__("No galleries have been yet created.","nggallery"), $gid);
		exit;
	}
	
	// Get additional parameters
	$gid = (int) $_GET["gid"];	
	
	if (!isset($gid) || $gid == '' || $gid == 0)
		$gid = $galleries[0]->gid;
	
	$prev_next = $_GET["prev_next"];
		
	if (!isset($prev_next) || $prev_next == '')
		$prev_next = false;
	else
		$prev_next = ($prev_next=='true' ? true : false);

	// Get the main gallery object
	$gallery = nggdb::find_gallery($gid);
		
	if (!isset($gallery) || $gallery==null) {
		header('content-type:text/plain;charset=utf-8');
		echo sprintf(__("The gallery ID=%s does not exist.","nggallery"), $gid);
		exit;
	}
	
	// Get previous and next galleries if required
	$gallery = $galleries[0];
	$prev_gallery = null;
	$next_gallery = null;

	for ($i=0; $i<count($galleries); $i++) {
		if ($gid==$galleries[$i]->gid) {
			$gallery = $galleries[$i];
			if ($prev_next) {			
				if ($i>0) {
					$prev_gallery = $galleries[$i-1];
				}
				if ($i<count($galleries)-1) {
					$next_gallery = $galleries[$i+1];
				}
			}
			break;
		}
	}
	
	$rss = nggMediaRss::get_gallery_mrss($gallery, $prev_gallery, $next_gallery);	
	
} else if ($mode=='album') {
	
	// Get additional parameters
	$aid = (int) $_GET["aid"];	
	if (!isset($aid) || $aid=='' || $aid==0) {
		header('content-type:text/plain;charset=utf-8');
		_e("No album ID has been provided as parameter","nggallery");
		exit;
	}
	
	// Get the album object
	$album = nggdb::find_album($aid);
	if (!isset($album) || $album==null ) {
		header('content-type:text/plain;charset=utf-8');
		echo sprintf(__("The album ID=%s does not exist.","nggallery"), $aid);
		exit;
	}
	
	$rss = nggMediaRss::get_album_mrss($album);	
} else {
	header('content-type:text/plain;charset=utf-8');
	echo sprintf(__("Invalid MediaRSS command (%s).","nggallery"), $mode);
	exit;
}


// Output header for media RSS
header("content-type:text/xml;charset=utf-8");
echo "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>\n";
echo $rss;
?>