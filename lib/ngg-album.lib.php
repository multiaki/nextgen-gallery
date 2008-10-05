<?php
/**
* Class that represents an album
* 
* @author 		Vincent Prat
* @copyright 	Copyright 2008
* 
*/
class nggAlbum {
	
	/** Database fields */
	var $id = -1;
	var $name = '';
	var $sortorder = '';
	
	/** Other fields */
	var $gallery_ids = array();
	var $galleries = null;
		
	/**
	 * Constructor from database row
	 * 
	* @row The database row from which to initialise the object fields
	 */
	function nggAlbum($row = null) {
		// Copy fields from database row
		//--
		if ($row!=null) {
			foreach ($row as $key => $value) {
				$this->$key = $value ;	
			}	
		}
		
		// Finish initialisation
		//--
		$this->gallery_ids = unserialize($this->sortorder);
	}
		
	/**
	 * Getter for images in the album
	 * 
	 * @return The images in the album (nggImage array)
	 */
	function get_images() {		
		return nggImageDAO::find_images_in_album($this);
	}
	
	/**
	 * Get the url where we can see the album
	 * TODO Get a permalink to a page presenting the album
	 */
	function get_permalink() {
		if ($this->permalink == '') {
			$this->permalink = get_option('siteurl');
		}
		return $this->permalink;
	}
	
	/**
	 * Get the galleries of the album (array indexed by gallery id)
	 */
	function get_galleries() {
		if ($this->galleries==null) {
			$this->galleries = array();
			foreach ($this->gallery_ids as $gid) {
				$this->galleries[$gid] = nggdb::find_gallery($gid); 
			}
		}
		return $this->galleries;
	}
}

?>