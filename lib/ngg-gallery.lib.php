<?php
/**
* Class that represents a gallery
* 
* @author 		Vincent Prat
* @copyright 	Copyright 2008
* 
*/
class nggGallery {
	
	/** Database fields */
	var $gid = -1;
	var $name = '';
	var $path = '';
	var $title = '';
	var $galdesc = '';
	var $pageid = -1;
	var $previewpic = -1;
	var $author = '';
	
	/** Other fields */
	var $permalink = '';
		
	/**
	 * Constructor from database row
	 * 
	* @row The database row from which to initialise the object fields
	 */
	function nggGallery($row = null) {
		// Copy fields from database row
		//--
		if ($row!=null) {
			foreach ($row as $key => $value) {
				$this->$key = $value ;	
			}	
		}
		
		// Finish initialisation
		//--
	}
		
	/**
	 * Getter for images in the gallery
	 * 
	 * @return The images in the gallery (nggImage array)
	 */
	function get_images() {		
		return nggImageDAO::find_images_in_gallery($this);
	}
	
	/**
	 * Get the url where we can see the gallery
	 * TODO Get a permalink to a page presenting the gallery
	 */
	function get_permalink() {
		if ($this->permalink == '') {
			if ($this->pageid <= 0) {
				$this->permalink = get_option('siteurl');
			} else {
				$this->permalink = get_permalink($this->pageid);
			}
		}
		return $this->permalink;
	}
}

?>