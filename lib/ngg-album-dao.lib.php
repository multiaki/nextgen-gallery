<?php
/**
* Data Access Object for the album object
* 
* @author 		Vincent Prat
* @copyright 	Copyright 2008
* 
*/
class nggAlbumDAO {
	
	/**
	 * Get all the albums
	 */
	function find_all_albums($order_by = 'id', $order_dir = 'ASC') {		
		global $wpdb;
		
		// Query database
		//--
		$rows = $wpdb->get_results("SELECT * FROM $wpdb->nggalbum ORDER BY $order_by $order_dir");
		
		// Build the object from the query result
		//--
		$albums = array(count($rows));
		$i = 0;		
		foreach ($rows as $row) {
			$albums[$i] = new nggAlbum($row);
			$i++;
		}
		
		return $albums;
	}
	
	/**
	 * Get an album given its ID
	 * 
	 * @id The album ID
	 * 
	 * @return A nggGallery object (null if not found)
	 */
	function find_album($id) {		
		global $wpdb;
		
		// Query database
		//--
		$row = $wpdb->get_row("SELECT * FROM $wpdb->nggalbum WHERE id = $id");
		
		// Build the object from the query result
		//--
		if ($row) {
			$album = new nggAlbum($row);	
			return $album;
		} else {
			return null;
		}
	}
	
	/**
	 * Delete an album
	 * 
	 * @id The album ID
	 */
	function delete_album($id) {		
		global $wpdb;		
		$wpdb->query("DELETE FROM $wpdb->nggalbum WHERE id = $id");
	}
}

?>