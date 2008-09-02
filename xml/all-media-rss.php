<?php
/**
 * Media RSS for all the pictures registered in NGG. It lists the pictures 
 * of one gallery and puts the other galleries as previous/next links.
 *
 * @author Vincent Prat (http://www.vincentprat.info)
 *
 * @param gid The gallery ID currently parsed is passed as a GET parameter (optional)
 */

// Load required files and set some useful variables
//--
require_once(dirname(__FILE__) . "/../ngg-config.php");
require_once(dirname(__FILE__) . "/../lib/ngg-media-rss.lib.php");

$site_url = get_option ("siteurl");

// Get all galleries
//--
$galleries = nggGalleryDAO::find_all_galleries();

if (count($galleries)==0) {
	header('content-type:text/plain;charset=utf-8');
	echo sprintf(__("No galleries have been yet created.","nggallery"), $gid);
	exit;
}

// Check if we have a gid GET parameters
//--
$current_gid = (int) $_GET["gid"];
if (!isset($current_gid) || $current_gid=="" || $current_gid==0) {
	// Get the first gallery we find
	//--
	$current_gid = $galleries[0]->gid;
}

// Find the previous and next galleries
//--
$current_gallery = $galleries[0];
$previous_gallery = null;
$next_gallery = null;

for ($i=0; $i<count($galleries); $i++) {
	if ($current_gid==$galleries[$i]->gid) {
		$current_gallery = $galleries[$i];
		if ($i>0) {
			$prev_gallery = $galleries[$i-1];
		}
		if ($i<count($galleries)-1) {
			$next_gallery = $galleries[$i+1];
		}
		break;
	}
}

// Get the images for current gallery
//--
$images = $current_gallery->get_images();

// Output header for media RSS
//--
header("content-type:text/xml;charset=utf-8");
echo "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>\n";
echo "<rss version='2.0' xmlns:media='http://search.yahoo.com/mrss' xmlns:atom='http://www.w3.org/2005/Atom'>\n";
echo "  <channel>\n";
echo "    <title><![CDATA[" . stripslashes($current_gallery->title) . "]]></title>\n";
echo "    <description><![CDATA[" . stripslashes($current_gallery->galdesc) . "]]></description>\n";
echo "    <link>" . get_option("siteurl") . "</link>\n";
echo "    <generator><![CDATA[NextGen Gallery [http://alexrabe.boelinger.com]]]></generator>\n";

if ($prev_gallery!=null) : 
	echo "    <atom:link rel='previous' href='" . nggMediaRss::get_gallery_mrss_url($prev_gallery->gid) . "' />\n";
endif;
if ($next_gallery!=null) : 
	echo "    <atom:link rel='next' href='" . nggMediaRss::get_gallery_mrss_url($next_gallery->gid) . "' />\n";
endif; 

foreach ($images as $image) {
	echo nggMediaRss::get_image_mrss_node($image);
}
echo "  </channel>\n";
echo "</rss>\n";
?>
