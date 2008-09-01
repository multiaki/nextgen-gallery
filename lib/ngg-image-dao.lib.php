<?php
/**
* Data Access Object for the image object
* 
* @author 		Vincent Prat
* @copyright 	Copyright 2008
* 
*/
class nggImageDAO {
	
	/**
	 * Insert an image in the database
	 * 
	 * @return the ID of the inserted image
	 */
	function insert_image($gid, $filename, $alttext, $desc, $exclude) {
		global $wpdb;
		
		$result = $wpdb->query(
			  "INSERT INTO $wpdb->nggpictures (galleryid, filename, description, alttext, exclude) VALUES "
			. "('$gid', '$filename', '$desc', '$alttext', '$exclude');");
		$pid = (int) $wpdb->insert_id;
		
		return $pid;
	}
	
	/**
	 * Get all the images from a given gallery
	 * 
	 * @gid The gallery object
	 * 
	 * @return An array containing the nggImage objects representing the images in the gallery.
	 */
	function find_images_in_gallery($gallery, $orderby = 'sortorder', $order = 'ASC', $use_exclude = false) {
		global $wpdb;
		
		// Query database
		//--
		if ($use_exclude) {
			$exclude_clause = ' AND exclude<>1 ';
		} else {
			$exclude_clause = '';
		}
		$rows = $wpdb->get_results("SELECT * FROM $wpdb->nggpictures WHERE galleryid=$gallery->gid $exclude_clause ORDER BY $orderby $order");
		
		// Build the object from the query result
		//--
		$images = array(count($rows));
		$i = 0;
		foreach ($rows as $row) {
			$images[$i] = new nggImage($gallery, $row);
			$i++;
		}
		
		return $images;
	}
	
	/**
	 * Get an image given its ID
	 * 
	 * @pid The image ID
	 * 
	 * @return A nggImage object representing the image (null if not found)
	 */
	function find_image($pid) {
		global $wpdb;
		
		// Query database
		//--
		$row = $wpdb->get_row("SELECT * FROM $wpdb->nggpictures WHERE pid = $pid");
		
		// Build the object from the query result
		//--
		if ($row) {
			$gallery = nggGalleryDAO::find_gallery($row->galleryid);	
			if ($gallery) {
				$image = new nggImage($gallery, $row);
				return $image;
			}
		} 
		
		return null;
	}
	
	/**
	 * Get images given a list of IDs 
	 * 
	 * @pids The image IDs
	 * 
	 * @return An array of nggImage objects representing the images
	 */
	function find_images_in_list($pids) {
		global $wpdb;
		
		$result = array();
		$id_list = implode(",", $pids);
		$gallery_cache = array();
		
		// Query database
		//--
		$rows = $wpdb->get_results("SELECT * FROM $wpdb->nggpictures WHERE pid in ($id_list)");
		
		// Build the object from the query result
		//--
		if ($rows) {	
			$i = 0;
			foreach ($rows as $row) {
				$gallery = $gallery_cache[$row->galleryid];
				if (!isset($gallery)) {
					$gallery = nggGalleryDAO::find_gallery($row->galleryid);
					if ($gallery) {
						$gallery_cache[$row->galleryid] = $gallery;
					}
				}
				
				if ($gallery) {
					$result[$i] = new nggImage($gallery, $row);
					$i++;
				}
			}
		} 
		
		return $result;
	}
}

?>