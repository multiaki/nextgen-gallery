<?php

/**
* Tag PHP class for the WordPress plugin NextGEN Gallery
* nggallery.lib.php
* 
* @author 		Alex Rabe 
* @copyright 	Copyright 2007-2008
* 
*/
class nggTags {
	
	/**
	* Get images corresponding to a list of tags
	*/
	function get_images($taglist, $mode = "ASC") {
		// return the images based on the tag
		global $wpdb;
		
		// extract it into a array
		$taglist = explode(",", $taglist);		
		if (!is_array($taglist)) {
			$taglist = array($taglist);
		}
		
		$taglist = array_map('trim', $taglist);
		$new_slugarray = array_map('sanitize_title', $taglist);		
		$sluglist   = "'" . implode("', '", $new_slugarray) . "'";
		
		$picarray = array();
		$result = array();
		
		// first get all $trem_ids with this tag
		$term_ids = $wpdb->get_col( $wpdb->prepare("SELECT term_id FROM $wpdb->terms WHERE slug IN ($sluglist) ORDER BY term_id ASC "));
		$picids = get_objects_in_term($term_ids, 'ngg_tag');

		if (is_array($picids)){
			// now get all pictures
			$piclist = "'" . implode("', '", $picids) . "'";
			
			if ($mode == 'ASC') {
				$picarray = $wpdb->get_col("SELECT t.pid FROM $wpdb->nggpictures AS t WHERE t.pid IN ($piclist) ORDER BY t.pid ASC ");
			} else if ($mode == 'RAND') {
				$picarray = $wpdb->get_col("SELECT t.pid FROM $wpdb->nggpictures AS t WHERE t.pid IN ($piclist) ORDER BY rand() ");			
			} else {
				$picarray = $wpdb->get_col("SELECT t.pid FROM $wpdb->nggpictures AS t WHERE t.pid IN ($piclist) ");			
			}
			
			$i = 0;
			foreach ($picarray as $pid) {
				$result[$i] = new nggImage($pid);
				$i++;
			}
		}
		
		return $result;
	}
	
	/**
	* Return one image based on the tag. Required for a tag based album overview
	*/
	function get_album_images($taglist) {
		global $wpdb;
		
		$taxonomy = 'ngg_tag';

		// extract it into a array
		$taglist = explode(",", $taglist);
		
		if (!is_array($taglist)) {
			$taglist = array($taglist);
		}
		
		$taglist = array_map('trim', $taglist);
		$slugarray = array_map('sanitize_title', $taglist);
		$slugarray = array_unique($slugarray);

		$picarray = array();

		foreach($slugarray as $slug) {  
			// get random picture of tag 
			$tsql  = "SELECT p.*, g.*, t.*, tt.* FROM $wpdb->term_relationships AS tr";  
			$tsql .= " INNER JOIN $wpdb->nggpictures AS p ON (tr.object_id = p.pid)"; 
			$tsql .= " INNER JOIN $wpdb->nggallery AS g ON (g.gid = p.galleryid)"; 
			$tsql .= " INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)"; 
			$tsql .= " INNER JOIN $wpdb->terms AS t ON (tt.term_id = t.term_id)"; 
			$tsql .= " WHERE tt.taxonomy = '$taxonomy' AND t.slug = '$slug' ORDER BY rand() limit 1 "; 
			$pic_data = $wpdb->get_row($tsql, OBJECT);  
			
			if ($pic_data) $picarray[] = $pic_data;  
		} 
		
		return $picarray;
	}
}

?>