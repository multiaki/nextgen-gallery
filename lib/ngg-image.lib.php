<?php

/**
* Image PHP class for the WordPress plugin NextGEN Gallery
* nggallery.lib.php
* 
* @author 		Alex Rabe 
* @copyright 	Copyright 2007-2008
* 
*/
class nggImage{
	
	/**** Public variables ****/	
	var $errmsg			=	"";			// Error message to display, if any
	var $error			=	FALSE; 		// Error state
	var $imagePath		=	"";			// URL Path to the image
	var $thumbPath		=	"";			// URL Path to the thumbnail
	var $absPath		=	"";			// Server Path to the image
	var $thumbPrefix	=	"";			// FolderPrefix to the thumbnail
	var $thumbFolder	=	"";			// Foldername to the thumbnail
	var $href			=	"";			// A href link code
	
	/**** Image Data ****/
	var $galleryid		=	0;			// Gallery ID
	var $imageID		=	0;			// Image ID	
	var $filename		=	"";			// Image filename
	var $description	=	"";			// Image description	
	var $alttext		=	"";			// Image alttext	
	var $exclude		=	"";			// Image exclude
	var $thumbcode		=	"";			// Image effect code

	/**** Gallery Data ****/
	var $name			=	"";			// Gallery name
	var $path			=	"";			// Gallery path	
	var $title			=	"";			// Gallery title
	var $pageid			=	0;			// Gallery page ID
	var $previewpic		=	0;			// Gallery preview pic				
	
	/**
	* Constructor
	*/
	function nggImage($imageID = '0') {		
		global $wpdb;
		
		//initialize variables
		$this->imageID = (int) $imageID;
		
		// get image values
		$imageData = $wpdb->get_row("SELECT * FROM $wpdb->nggpictures WHERE pid = '$this->imageID' ") or $this->error = true;
		if($this->error == false) {
			foreach ($imageData as $key => $value) {
				$this->$key = $value ;
			}
		}
		
		// get gallery values
		$galleryData = $wpdb->get_row("SELECT * FROM $wpdb->nggallery WHERE gid = '$this->galleryid' ") or $this->error = true;
		if($this->error == false) {
			foreach ($galleryData as $key => $value) {
				$this->$key = $value ;	
			}
		}
		
		if($this->error == false) {
			// set gallery url
			$this->get_thumbnail_folder($this->path, FALSE);
			$this->imagePath 	= get_option ('siteurl')."/".$this->path."/".$this->filename;
			$this->thumbPath 	= get_option ('siteurl')."/".$this->path.$this->thumbFolder.$this->thumbPrefix.$this->filename;
			$this->absPath 		= WINABSPATH.$this->path."/".$this->filename;
		}
	}
	
	/**********************************************************/
	function get_thumbnail_folder($gallerypath, $include_Abspath = TRUE) {
		//TODO:Double coded, see also class nggallery, fix it !
		if (!$include_Abspath) 
			$gallerypath = WINABSPATH.$gallerypath;
		
		if (!file_exists($gallerypath))
			return FALSE;
		
		if (is_dir($gallerypath."/thumbs")) {
			$this->thumbFolder 	= "/thumbs/";
			$this->thumbPrefix 	= "thumbs_";		
			return TRUE;
		}
		
		// old mygallery check
		if (is_dir($gallerypath."/tumbs")) {
			$this->thumbFolder	= "/tumbs/";
			$this->thumbPrefix 	= "tmb_";
			return TRUE;
		}
		
		if (is_admin()) {
			if (!is_dir($gallerypath."/thumbs")) {
				if ( !wp_mkdir_p($gallerypath."/thumbs") )
				return FALSE;
				$this->thumbFolder	= "/thumbs/";
				$this->thumbPrefix 	= "thumbs_";			
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
	* Get the thumbnail code (to add effects on thumbnail click)
	*
	* Applies the filter ''
	*/
	function get_thumbcode($galleryname = "") {
		// read the option setting
		$ngg_options = get_option('ngg_options');
		
		// get the effect code
		if ($ngg_options['thumbEffect'] != "none") {
			$this->thumbcode = stripslashes($ngg_options['thumbCode']);
		} else if ($ngg_options['thumbEffect'] == "highslide") {
			$this->thumbcode = str_replace("%GALLERY_NAME%", "'".$galleryname."'", $this->thumbcode);
		} else {
			$this->thumbcode = str_replace("%GALLERY_NAME%", $galleryname, $this->thumbcode);
		}
				
		return apply_filters('ngg_get_thumbcode', $this->thumbcode, $this);
	}
	
	function get_href_link() {
		// create the a href link from the picture
		$this->href  = "\n".'<a href="'.$this->imagePath.'" title="'.stripslashes($this->description).'" '.$this->get_thumbcode($this->name).'>'."\n\t";
		$this->href .= '<img alt="'.$this->alttext.'" src="'.$this->imagePath.'"/>'."\n".'</a>'."\n";

		return $this->href;
	}

	function get_href_thumb_link() {
		// create the a href link with the thumbanil
		$this->href  = "\n".'<a href="'.$this->imagePath.'" title="'.stripslashes($this->description).'" '.$this->get_thumbcode($this->name).'>'."\n\t";
		$this->href .= '<img alt="'.$this->alttext.'" src="'.$this->thumbPath.'"/>'."\n".'</a>'."\n";

		return $this->href;
	}
	
	function cached_singlepic_file($width = "", $height = "", $mode = "" ) {
		// This function creates a cache for all singlepics to reduce the CPU load
		$ngg_options = get_option('ngg_options');
		
		include_once(NGGALLERY_ABSPATH.'/lib/ngg-thumbnail.lib.php');
		
		// cache filename should be unique
		$cachename   	= $this->imageID. "_". $mode . "_". $width. "x". $height ."_". $this->filename;
		$cachefolder 	= WINABSPATH .$ngg_options['gallerypath'] . "cache/";
		$cached_url  	= get_option ('siteurl') ."/". $ngg_options['gallerypath'] . "cache/" . $cachename;
		$cached_file	= $cachefolder . $cachename;
		
		// check first for the file
		if ( file_exists($cached_file) ) {
			return $cached_url;
		}
		
		// create folder if needed
		if ( !file_exists($cachefolder) ) {
			if ( !wp_mkdir_p($cachefolder) ) {
				return false;
			}
		}
		
		$thumb = new ngg_Thumbnail($this->absPath, TRUE);
		// echo $thumb->errmsg;
		
		if (!$thumb->error) {	
			$thumb->resize($width , $height);
			
			if ($mode == 'watermark') {
				if ($ngg_options['wmType'] == 'image') {
					$thumb->watermarkImgPath = $ngg_options['wmPath'];
					$thumb->watermarkImage($ngg_options['wmPos'], $ngg_options['wmXpos'], $ngg_options['wmYpos']); 
				}
				if ($ngg_options['wmType'] == 'text') {
					$thumb->watermarkText = $ngg_options['wmText'];
					$thumb->watermarkCreateText($ngg_options['wmColor'], $ngg_options['wmFont'], $ngg_options['wmSize'], $ngg_options['wmOpaque']);
					$thumb->watermarkImage($ngg_options['wmPos'], $ngg_options['wmXpos'], $ngg_options['wmYpos']);  
				}
			}
			
			if ($mode == 'web20') {
				$thumb->createReflection(40,40,50,false,'#a4a4a4');
			}
			
			// save the new cache picture
			$thumb->save($cached_file,$ngg_options['imgQuality']);
		}
		$thumb->destruct();
		
		// check again for the file
		if (file_exists($cached_file)) {
			return $cached_url;
		}
		
		return false;
	}
}

?>