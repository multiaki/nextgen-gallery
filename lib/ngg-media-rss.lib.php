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
		
		$out  = '<item>';
		$out .= '<title><![CDATA[' . $title . ']]></title>';
		$out .= '<description><![CDATA[' . $desc . ']]></description>';
		$out .= '<link>' . $image->get_permalink() . '</link>';		
		$out .= '<media:content url="' . $image->imageURL . '" medium="image" />';
		$out .= '<media:title><![CDATA[' . $title . ']]></media:title>';
		$out .= '<media:description><![CDATA[' . $desc . ']]></media:description>';
		$out .= '<media:thumbnail url="' . $image->thumbURL . '" width="' . $thumbwidth . '" height="' . $thumbheight . '" />';
		$out .= '<media:keywords>' . $tag_names . '</media:keywords>';
		$out .= '</item>';

		return $out;
	}	
	
	/**
	 * Function called by the wp_head action to output the RSS link for medias
	 */
	function add_mrss_alternate_links() {
		
		echo '<link id="Gallery" rel="alternate" type="application/rss+xml" title="" href="' . nggMediaRss::get_gallery_mrss_url(1) . '" />';
		
	}
	
	/**
	 * Get the URL of a gallery media RSS
	 */
	function get_gallery_mrss_url($gid) {
		
		return NGGALLERY_URLPATH . 'xml/media-rss.php?gid=' . $gid;
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