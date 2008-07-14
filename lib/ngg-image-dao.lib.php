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
	 * Get all the images from a given gallery
	 * 
	 * @gid The gallery object
	 * 
	 * @return An array containing the nggImage objects representing the images in the gallery.
	 */
	function find_images_in_gallery($gallery, $order_by = 'sortorder', $order_dir = 'ASC') {
		global $wpdb;
		
		// Query database
		//--
		$rows = $wpdb->get_results("SELECT * FROM $wpdb->nggpictures WHERE galleryid = $gallery->gid ORDER BY $order_by $order_dir");
		
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
	
}

?>