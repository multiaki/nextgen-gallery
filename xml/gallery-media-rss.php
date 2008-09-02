<?php
/**
* Media RSS for a given gallery
* @author Vincent Prat (http://www.vincentprat.info)
*
* @param gid The gallery ID is passed as a GET parameter
*/

// Load required files and set some useful variables
//--
require_once(dirname(__FILE__) . "/../ngg-config.php");
require_once(dirname(__FILE__) . "/../lib/ngg-media-rss.lib.php");

$site_url = get_option ("siteurl");

// Check we have the required GET parameters
//--
$gid = (int) $_GET["gid"];

if (!isset($gid) || $gid=="" || $gid==0) {
	header('content-type:text/plain;charset=utf-8');
	_e("No gallery ID has been provided as parameter","nggallery");
	exit;
}

// Get the gallery object
//--
$gallery = nggGalleryDAO::find_gallery($gid);

if (!isset($gallery) || $gallery==null) {
	header('content-type:text/plain;charset=utf-8');
	echo sprintf(__("The gallery ID=%s does not exist.","nggallery"), $gid);
	exit;
}

// Get the images for current gallery
//--
$images = $gallery->get_images();

// Output header for media RSS
//--
header("content-type:text/xml;charset=utf-8");
echo "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>\n";
echo "<rss version='2.0' xmlns:media='http://search.yahoo.com/mrss' xmlns:atom='http://www.w3.org/2005/Atom'>\n";
echo "  <channel>\n";
echo "    <title><![CDATA[" . stripslashes($gallery->title) . "]]></title>\n";
echo "    <description><![CDATA[" . stripslashes($gallery->galdesc) . "]]></description>\n";
echo "    <link>" . get_option("siteurl") . "</link>\n";
echo "    <generator><![CDATA[NextGen Gallery [http://alexrabe.boelinger.com]]]></generator>\n";

foreach ($images as $image) {
	echo nggMediaRss::get_image_mrss_node($image);
}

echo "  </channel>\n";
echo "</rss>\n";
?>
