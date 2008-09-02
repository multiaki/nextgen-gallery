<?php
/**
* Class to produce Media RSS nodes
* 
* @author 		Vincent Prat
* @copyright 	Copyright 2008
*/
class nggMediaRss {
	
	/**
	 * Get a node corresponding to one gallery image
	 *
	 * @param $image The image object
	 */
	function get_image_mrss_node($image) {		
		$ngg_options = nggGalleryPlugin::get_option('ngg_options');
		
		$tags = $image->get_tags();
		$tag_names = '';
		foreach ($tags as $tag) {
			$tag_names .= ($tag_names=='' ? $tag->name : ', ' . $tag->name);
		}
		
		$title = html_entity_decode(stripslashes($image->alttext));
		$desc = html_entity_decode(stripslashes($image->description));
		
		$thumbwidth = $ngg_options['thumbwidth'];
		$thumbheight = ($ngg_options['thumbfix'] ? $ngg_options['thumbheight'] : $thumbwidth); 	
		
		$out  = "    <item>\n";
		$out .= "      <title><![CDATA[" . $title . "]]></title>\n";
		$out .= "      <description><![CDATA[" . $desc . "]]></description>\n";
		$out .= "      <link>" . $image->get_permalink() . "</link>\n";		
		$out .= "      <media:content url='" . $image->imageURL . "' medium='image' />\n";
		$out .= "      <media:title><![CDATA[" . $title . "]]></media:title>\n";
		$out .= "      <media:description><![CDATA[" . $desc . "]]></media:description>\n";
		$out .= "      <media:thumbnail url='" . $image->thumbURL . "' width='" . $thumbwidth . "' height='" . $thumbheight . "' />\n";
		$out .= "      <media:keywords><![CDATA[" . $tag_names . "]]></media:keywords>\n";
		$out .= "      <media:copyright><![CDATA[Copyright (c) " . get_option("blogname") . " (" . get_option("siteurl") . ")]]></media:copyright>\n";
		$out .= "    </item>\n";

		return $out;
	}	
	
	/**
	 * Function called by the wp_head action to output the RSS link for medias
	 */
	function add_mrss_alternate_link() {
		echo "<link id='MediaRSS' rel='alternate' type='application/rss+xml' title='' href='" . nggMediaRss::get_mrss_url() . "' />";		
	}
	
	/**
	 * Get the URL of a gallery media RSS
	 */
	function get_gallery_mrss_url($gid) {		
		return NGGALLERY_URLPATH . 'xml/gallery-media-rss.php?gid=' . $gid;
	}
	
	/**
	 * Get the URL of the general media RSS
	 */
	function get_mrss_url($gid='') {		
		if ($gid!='') {
			return NGGALLERY_URLPATH . 'xml/all-media-rss.php?gid=' . $gid;
		}
		return NGGALLERY_URLPATH . 'xml/all-media-rss.php';
	}
	
	/**
	 * Add the javascript required to enable PicLens/CoolIris support 
	 */
	function add_piclens_javascript() {
		echo "\n" . '<!-- NextGen Gallery CoolIris/PicLens support -->';
		echo "\n" . '<script type="text/javascript" src="http://lite.piclens.com/current/piclens_optimized.js"></script>';
		echo "\n" . '<!-- /NextGen Gallery CoolIris/PicLens support -->';
	}
}

?>