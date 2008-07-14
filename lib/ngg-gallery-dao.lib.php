<?php
/**
* Data Access Object for the gallery object
* 
* @author 		Vincent Prat
* @copyright 	Copyright 2008
* 
*/
class nggGalleryDAO {
	
	/**
	 * Get all the galleries
	 */
	function find_all_galleries($order_by = 'gid', $order_dir = 'ASC') {		
		global $wpdb;
		
		// Query database
		//--
		$rows = $wpdb->get_results("SELECT * FROM $wpdb->nggallery ORDER BY $order_by $order_dir");
		
		// Build the object from the query result
		//--
		$galleries = array(count($rows));
		$i = 0;		
		foreach ($rows as $row) {
			$galleries[$i] = new nggGallery($row);
			$i++;
		}
		
		return $galleries;
	}
	
	/**
	 * Get a gallery given its ID
	 * 
	 * @gid The gallery ID
	 * 
	 * @return A nggGallery object (null if not found)
	 */
	function find_gallery($gid) {		
		global $wpdb;
		
		// Query database
		//--
		$row = $wpdb->get_row("SELECT * FROM $wpdb->nggallery WHERE gid = $gid");
		
		// Build the object from the query result
		//--
		if ($row) {
			$gallery = new nggGallery($row);	
			return $gallery;
		} else {
			return null;
		}
	}
	
	/**
	 * Delete a gallery AND all the pictures associated to this gallery!
	 * 
	 * @gid The gallery ID
	 */
	function delete_gallery($gid) {		
		global $wpdb;		
		$wpdb->query("DELETE FROM $wpdb->nggpictures WHERE galleryid = $gid");
		$wpdb->query("DELETE FROM $wpdb->nggallery WHERE gid = $gid");
	}
}

?>