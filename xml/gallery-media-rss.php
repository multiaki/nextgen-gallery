<?php
/**
* Media RSS for a given gallery
* @author Vincent Prat (http://www.vincentprat.info)
*
* @param gid The gallery ID is passed as a GET parameter
*/

// Load required files and set some useful variables
//--
require_once(dirname(__FILE__) . '/../ngg-config.php');
require_once(dirname(__FILE__) . '/../lib/ngg-media-rss.lib.php');

$site_url = get_option ('siteurl');

// Check we have the required GET parameters
//--
$gid = (int) $_GET['gid'];

if (!isset($gid) || $gid=='' || $gid==0) {
	header("content-type:text/plain;charset=utf-8");
	_e('No gallery ID has been provided as parameter','nggallery');
	exit;
}

// Get the gallery object
//--
$gallery = nggGalleryDAO::find_gallery($gid);

if (!isset($gallery) || $gallery==null) {
	header("content-type:text/plain;charset=utf-8");
	echo sprintf(__('The gallery ID=%s does not exist.','nggallery'), $gid);
	exit;
}

// Get the images for current gallery
//--
$images = nggImageDAO::find_images_in_gallery($gallery);

// Output header for media RSS
//--
?><?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss">
<channel>
	<title><?php echo $gallery->title; ?></title>
	<link><?php echo $gallery->get_permalink(); ?></link>
	<description><?php echo $gallery->galdesc; ?></description>
	<generator>NextGen Gallery [http://alexrabe.boelinger.com]</generator>
<?php
	foreach ($images as $image) {
		echo nggMediaRss::get_image_mrss_node($image);
	}
?>		
</channel>
</rss>
