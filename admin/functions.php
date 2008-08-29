<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

class nggAdmin{

	// **************************************************************
	function create_gallery($gallerytitle, $defaultpath) {
		// create a new gallery & folder
		global $wpdb, $user_ID;
 
		// get the current user ID
		get_currentuserinfo();

		//cleanup pathname
		$galleryname = apply_filters('ngg_gallery_name', $gallerytitle);
		$nggpath = $defaultpath.$galleryname;
		$nggRoot = WINABSPATH.$defaultpath;
		$txt = "";
		
		// No gallery name ?
		if (empty($galleryname)) {	
			nggGalleryPlugin::show_error( __('No valid gallery name!', 'nggallery') );
			return false;
		}
		
		// check for main folder
		if ( !is_dir($nggRoot) ) {
			if ( !wp_mkdir_p($nggRoot) ) {
				$txt  = __('Directory', 'nggallery').' <strong>'.$defaultpath.'</strong> '.__('didn\'t exist. Please create first the main gallery folder ', 'nggallery').'!<br />';
				$txt .= __('Check this link, if you didn\'t know how to set the permission :', 'nggallery').' <a href="http://codex.wordpress.org/Changing_File_Permissions">http://codex.wordpress.org/Changing_File_Permissions</a> ';
				nggGalleryPlugin::show_error($txt);
				return false;
			}
		}

		// check for permission settings, Safe mode limitations are not taken into account. 
		if ( !is_writeable($nggRoot ) ) {
			$txt  = __('Directory', 'nggallery').' <strong>'.$defaultpath.'</strong> '.__('is not writeable !', 'nggallery').'<br />';
			$txt .= __('Check this link, if you didn\'t know how to set the permission :', 'nggallery').' <a href="http://codex.wordpress.org/Changing_File_Permissions">http://codex.wordpress.org/Changing_File_Permissions</a> ';
			nggGalleryPlugin::show_error($txt);
			return false;
		}
		
		// 1. Create new gallery folder
		if ( !is_dir(WINABSPATH.$nggpath) ) {
			if ( !wp_mkdir_p (WINABSPATH.$nggpath) ) 
				$txt  = __('Unable to create directory ', 'nggallery').$nggpath.'!<br />';
		}
		
		// 2. Check folder permission
		if ( !is_writeable(WINABSPATH.$nggpath ) )
			$txt .= __('Directory', 'nggallery').' <strong>'.$nggpath.'</strong> '.__('is not writeable !', 'nggallery').'<br />';

		// 3. Now create "thumbs" folder inside
		if ( !is_dir(WINABSPATH.$nggpath.'/thumbs') ) {				
			if ( !wp_mkdir_p ( WINABSPATH.$nggpath.'/thumbs') ) 
				$txt .= __('Unable to create directory ', 'nggallery').' <strong>'.$nggpath.'/thumbs !</strong>';
		}
		
		if (SAFE_MODE) {
			$help  = __('The server setting Safe-Mode is on !', 'nggallery');	
			$help .= '<br />'.__('If you have problems, please create directory', 'nggallery').' <strong>'.$nggpath.'</strong> ';	
			$help .= __('and the thumbnails directory', 'nggallery').' <strong>'.$nggpath.'/thumbs</strong> '.__('with permission 777 manually !', 'nggallery');
			nggGalleryPlugin::show_message($help);
		}
		
		// show a error message			
		if ( !empty($txt) ) {
			if (SAFE_MODE) {
			// for safe_mode , better delete folder, both folder must be created manually
				@rmdir(WINABSPATH.$nggpath.'/thumbs');
				@rmdir(WINABSPATH.$nggpath);
			}
			nggGalleryPlugin::show_error($txt);
			return false;
		}
		
		$result=$wpdb->get_var("SELECT name FROM $wpdb->nggallery WHERE name = '$galleryname' ");
		if ($result) {
			nggGalleryPlugin::show_error(__('Gallery', 'nggallery').' <strong>'.$galleryname.'</strong> '.__('already exists', 'nggallery'));
			return false;			
		} else { 
			$result = $wpdb->query("INSERT INTO $wpdb->nggallery (name, path, title, author) VALUES ('$galleryname', '$nggpath', '$gallerytitle' , '$user_ID') ");
			if ($result) {
				$message  = __('Gallery %1$s successfully created.<br/>You can show this gallery with the tag %2$s.<br/>','nggallery');
				$message  = sprintf($message, $galleryname, '[gallery=' . $wpdb->insert_id . ']');
				$message .= '<a href="' . get_option('siteurl') . '/wp-admin/admin.php?page=nggallery-manage-gallery&mode=edit&gid=' . $wpdb->insert_id . '" >';
				$message .= __('Edit gallery','nggallery');
				$message .= '</a>';
				
				nggGalleryPlugin::show_message($message); 
			}
			return true;
		} 
	}
	
	// **************************************************************
	function import_gallery($galleryfolder) {
		// ** $galleryfolder contains relative path
		
		//TODO: Check permission of existing thumb folder & images
		
		global $wpdb, $user_ID;

		// get the current user ID
		get_currentuserinfo();
		
		$created_msg = "";
		
		// remove trailing slash at the end, if somebody use it
		if (substr($galleryfolder, -1) == '/') $galleryfolder = substr($galleryfolder, 0, -1);
		$gallerypath = WINABSPATH.$galleryfolder;
		
		if (!is_dir($gallerypath)) {
			nggGalleryPlugin::show_error(__('Directory', 'nggallery').' <strong>'.$gallerypath.'</strong> '.__('doesn&#96;t exist!', 'nggallery'));
			return ;
		}
		
		// read list of images
		$new_imageslist = nggAdmin::scandir($gallerypath);
		if (empty($new_imageslist)) {
			nggGalleryPlugin::show_message(__('Directory', 'nggallery').' <strong>'.$gallerypath.'</strong> '.__('contains no pictures', 'nggallery'));
			return;
		}
		// check & create thumbnail folder
		if ( !nggGalleryPlugin::get_thumbnail_folder($gallerypath) )
			return;
		
		// take folder name as gallery name		
		$galleryname = basename($galleryfolder);
		
		// check for existing galleryfolder
		$gallery_id = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE path = '$galleryfolder' ");

		if (!$gallery_id) {
			$result = $wpdb->query("INSERT INTO $wpdb->nggallery (name, path, title, author) VALUES ('$galleryname', '$galleryfolder', '$galleryname', '$user_ID') ");
			if (!$result) {
				nggGalleryPlugin::show_error(__('Database error. Could not add gallery!','nggallery'));
				return;
			}
			$created_msg =__('Gallery','nggallery').' <strong>'.$galleryname.'</strong> '.__('successfully created!','nggallery').'<br />';
			$gallery_id = $wpdb->insert_id;  // get index_id
		}
		
		// Look for existing image list
		$old_imageslist = $wpdb->get_col("SELECT filename FROM $wpdb->nggpictures WHERE galleryid = '$gallery_id' ");
		// if no images are there, create empty array
		if ($old_imageslist == NULL) $old_imageslist = array();
		// check difference
		$new_images = array_diff($new_imageslist, $old_imageslist);
		
		// add images to database		
		$image_ids = nggAdmin::add_Images($gallery_id, $new_images);
		
		// now create thumbnails
		nggAdmin::do_ajax_operation( 'create_thumbnail' , $image_ids, __('Create new thumbnails','nggallery') );
				
		nggGalleryPlugin::show_message($created_msg . count($image_ids) .__(' picture(s) successfully added','nggallery'));
		return;

	}
	// **************************************************************
	function scandir($dirname = ".") { 
		// thx to php.net :-)
		$ext = array("jpeg", "jpg", "png", "gif"); 
		$files = array(); 
		if($handle = opendir($dirname)) { 
		   while(false !== ($file = readdir($handle))) 
		       for($i=0;$i<sizeof($ext);$i++) 
		           if(stristr($file, ".".$ext[$i])) 
		               $files[] = utf8_encode($file); 
		   closedir($handle); 
		} 
		sort($files);
		return ($files); 
	} 
	
	/**
	 * nggAdmin::createThumbnail() - function to create or recreate a thumbnail
	 * 
	 * @param object | int $image contain all information about the image or the id
	 * @return string result code
	 * @since v1.0.0
	 */
	function create_thumbnail($image) {
		
		if(! class_exists('ngg_Thumbnail'))
			require_once( nggGalleryPlugin::graphic_library() );
		
		if ( is_numeric($image) )
			$image = nggImageDAO::find_image( $image );

		if ( !is_object($image) ) 
			return __('Object didn\'t contain correct data','nggallery');

		$ngg_options = get_option('ngg_options');
		
		// check for existing thumbnail
		if (file_exists($image->thumbPath))
			if (!is_writable($image->thumbPath))
				return $image->filename . __(' is not writeable ','nggallery');

		$thumb = new ngg_Thumbnail($image->imagePath, TRUE);

		// skip if file is not there
		if (!$thumb->error) {
			if ($ngg_options['thumbcrop']) {
				
				// THX to Kees de Bruin, better thumbnails if portrait format
				$width = $ngg_options['thumbwidth'];
				$height = $ngg_options['thumbheight'];
				$curwidth = $thumb->currentDimensions['width'];
				$curheight = $thumb->currentDimensions['height'];
				if ($curwidth > $curheight) {
					$aspect = (100 * $curwidth) / $curheight;
				} else {
					$aspect = (100 * $curheight) / $curwidth;
				}
				$width = intval(($width * $aspect) / 100);
				$height = intval(($height * $aspect) / 100);
				$thumb->resize($width,$height,$ngg_options['thumbResampleMode']);
				$thumb->cropFromCenter($width,$ngg_options['thumbResampleMode']);
			} 
			elseif ($ngg_options['thumbfix'])  {
				// check for portrait format
				if ($thumb->currentDimensions['height'] > $thumb->currentDimensions['width']) {
					$thumb->resize($ngg_options['thumbwidth'], 0,$ngg_options['thumbResampleMode']);
					// get optimal y startpos
					$ypos = ($thumb->currentDimensions['height'] - $ngg_options['thumbheight']) / 2;
					$thumb->crop(0, $ypos, $ngg_options['thumbwidth'],$ngg_options['thumbheight'],$ngg_options['thumbResampleMode']);	
				} else {
					$thumb->resize(0,$ngg_options['thumbheight'],$ngg_options['thumbResampleMode']);	
					// get optimal x startpos
					$xpos = ($thumb->currentDimensions['width'] - $ngg_options['thumbwidth']) / 2;
					$thumb->crop($xpos, 0, $ngg_options['thumbwidth'],$ngg_options['thumbheight'],$ngg_options['thumbResampleMode']);	
				}
			} else {
				$thumb->resize($ngg_options['thumbwidth'],$ngg_options['thumbheight'],$ngg_options['thumbResampleMode']);	
			}
			
			// save the new thumbnail
			$thumb->save($image->thumbPath, $ngg_options['thumbquality']);
			nggAdmin::chmod ($image->thumbPath); 
		} 
				
		$thumb->destruct();
		
		if ( !empty($thumb->errmsg) )
			return ' <strong>' . $image->filename . ' (Error : '.$thumb->errmsg .')</strong>';
		
		// success
		return '1'; 
	}
	
	/**
	 * nggAdmin::resize_image() - create a new image, based on the height /width
	 * 
	 * @param object | int $image contain all information about the image or the id
	 * @param integer $width optional 
	 * @param integer $height optional
	 * @return string result code
	 */
	function resize_image($image, $width = 0, $height = 0) {
		
		if(! class_exists('ngg_Thumbnail'))
			require_once( nggGalleryPlugin::graphic_library() );

		if ( is_numeric($image) )
			$image = nggImageDAO::find_image( $image );
		
		if ( !is_object($image) ) 
			return __('Object didn\'t contain correct data','nggallery');	
		
		$ngg_options = get_option('ngg_options');

		$width  = ($width  != 0) ? $ngg_options['imgWidth']  : $width;
		$height = ($height != 0) ? $ngg_options['imgHeight'] : $height;
		
		if (!is_writable($image->imagePath))
			return ' <strong>' . $image->filename . __(' is not writeable','nggallery') . '</strong>';
		
		$file = new ngg_Thumbnail($image->imagePath, TRUE);

		// skip if file is not there
		if (!$file->error) {
			$file->resize($width, $height, $ngg_options['imgResampleMode']);
			$file->save($image->imagePath, $ngg_options['imgQuality']);
		}
		
		$file->destruct();

		if ( !empty($file->errmsg) )
			return ' <strong>' . $image->filename . ' (Error : '.$file->errmsg .')</strong>';		

		return '1';
	}

	/**
	 * nggAdmin::set_watermark() - set the watermarl for the image
	 * 
	 * @param object | int $image contain all information about the image or the id
	 * @return string result code
	 */
	function set_watermark($image) {

		if(! class_exists('ngg_Thumbnail'))
			require_once( nggGalleryPlugin::graphic_library() );
		
		if ( is_numeric($image) )
			$image = nggImageDAO::find_image( $image );
		
		if ( !is_object($image) ) 
			return __('Object didn\'t contain correct data','nggallery');		
		
		$ngg_options = get_option('ngg_options');
	
		if (!is_writable($image->imagePath))
			return ' <strong>' . $image->filename . __(' is not writeable','nggallery') . '</strong>';
		
		$file = new ngg_Thumbnail( $image->imagePath, TRUE );

		// skip if file is not there
		if (!$file->error) {
			if ($ngg_options['wmType'] == 'image') {
				$file->watermarkImgPath = $ngg_options['wmPath'];
				$file->watermarkImage($ngg_options['wmPos'], $ngg_options['wmXpos'], $ngg_options['wmYpos']); 
			}
			if ($ngg_options['wmType'] == 'text') {
				$file->watermarkText = $ngg_options['wmText'];
				$file->watermarkCreateText($ngg_options['wmColor'], $ngg_options['wmFont'], $ngg_options['wmSize'], $ngg_options['wmOpaque']);
				$file->watermarkImage($ngg_options['wmPos'], $ngg_options['wmXpos'], $ngg_options['wmYpos']);  
			}
			$file->save($image->imagePath, $ngg_options['imgQuality']);
		}
		
		$file->destruct();

		if ( !empty($file->errmsg) )
			return ' <strong>' . $image->filename . ' (Error : '.$file->errmsg .')</strong>';		

		return '1';
	}

	// **************************************************************
	function add_Images($galleryID, $imageslist) {
		// add images to database		
		global $wpdb;
		
		$image_ids = array();
		
		if ( is_array($imageslist) ) {
			foreach($imageslist as $picture) {
				$result = $wpdb->query("INSERT INTO $wpdb->nggpictures (galleryid, filename, alttext, exclude) VALUES ('$galleryID', '$picture', '$picture', 0) ");
				$pic_id = (int) $wpdb->insert_id;
				if ($result) 
					$image_ids[] = $pic_id;

				// add the metadata
				if ($_POST['addmetadata']) 
					nggAdmin::import_MetaData($pic_id);
					
			} 
		} // is_array
		
		return $image_ids;
		
	}

	// **************************************************************
	function import_MetaData($imagesIds) {
		// add images to database		
		global $wpdb;
		
		require_once(NGGALLERY_ABSPATH.'/lib/ngg-image.lib.php');
		
		if (!is_array($imagesIds))
			$imagesIds = array($imagesIds);
		
		foreach($imagesIds as $pic_id) {
			$picture = nggImageDAO::find_image($pic_id);
			if (!$picture->error) {

				$meta = nggAdmin::get_MetaData($picture->imagePath);
				
				// get the title
				if (!$alttext = $meta['title'])
					$alttext = $picture->alttext;
				// get the caption / description field
				if (!$description = $meta['caption'])
					$description = $picture->description;
				// update database
				$result=$wpdb->query( "UPDATE $wpdb->nggpictures SET alttext = '$alttext', description = '$description'  WHERE pid = $pic_id");
				// add the tags
				if ($meta['keywords']) {
					$taglist = explode(",", $meta['keywords']);
					wp_set_object_terms($pic_id, $taglist, 'ngg_tag');
				} // add tags
			}// error check
		} // foreach
		
		return true;
		
	}

	// **************************************************************
	function get_MetaData($picPath) {
		// must be Gallery absPath + filename
		
		require_once(NGGALLERY_ABSPATH.'/lib/ngg-meta.lib.php');
		
		$meta = array();

		$pdata = new nggMeta($picPath);
		$meta['title'] = $pdata->get_META('title');		
		$meta['caption'] = $pdata->get_META('caption');	
		$meta['keywords'] = $pdata->get_META('keywords');	
		
		return $meta;
		
	}

	// **************************************************************
	function unzip($dir, $file) {
		
		if(! class_exists('PclZip'))
			require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
				
		$archive = new PclZip($file);

		// extract all files in one folder
		if ($archive->extract(PCLZIP_OPT_PATH, $dir, PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_CB_PRE_EXTRACT, 'ngg_getOnlyImages') == 0) {
			nggGalleryPlugin::show_error("Error : ".$archive->errorInfo(true));
			return false;
		}

		return true;
	}
 
	// **************************************************************
	function getOnlyImages($p_event, $p_header)	{
		$info = pathinfo($p_header['filename']);
		// check for extension
		$ext = array("jpeg", "jpg", "png", "gif"); 
		if (in_array( strtolower($info['extension']), $ext)) {
			// For MAC skip the ".image" files
			if ($info['basename']{0} ==  "." ) 
				return 0;
			else 
				return 1;
		}
		// ----- all other files are skipped
		else {
		  return 0;
		}
	}

	// **************************************************************
	function import_zipfile($defaultpath) {
		
		if (nggAdmin::check_quota())
			return;
		
		$temp_zipfile = $_FILES['zipfile']['tmp_name'];
		$filename = $_FILES['zipfile']['name']; 
					
		// check if file is a zip file
		if (!eregi('zip|download|octet-stream', $_FILES['zipfile']['type'])) {
			@unlink($temp_zipfile); // del temp file
			nggGalleryPlugin::show_error(__('Uploaded file was no or a faulty zip file ! The server recognize : ','nggallery').$_FILES['zipfile']['type']);
			return; 
		}
		
		// get foldername if selected
		$foldername = $_POST['zipgalselect'];
		if ($foldername == "0") {	
			//cleanup and take the zipfile name as folder name
			$foldername = sanitize_title(strtok ($filename,'.'));
			//$foldername = preg_replace ("/(\s+)/", '-', strtolower(strtok ($filename,'.')));					
		}

		//TODO:FORM must get the path from the tables not from defaultpath !!!
		// set complete folder path		
		$newfolder = WINABSPATH.$defaultpath.$foldername;

		if (!is_dir($newfolder)) {
			// create new directories
			if (!wp_mkdir_p ($newfolder)) {
				$message = sprintf(__('Unable to create directory %s. Is its parent directory writable by the server?', 'nggallery'), $newfolder);
				nggGalleryPlugin::show_error($message);
				return false;
			}
			if (!wp_mkdir_p ($newfolder.'/thumbs')) {
				nggGalleryPlugin::show_error(__('Unable to create directory ', 'nggallery').$newfolder.'/thumbs !');
				return false;
			}
		} 
		
		// unzip and del temp file		
		$result = nggAdmin::unzip($newfolder, $temp_zipfile);
		@unlink($temp_zipfile);		

		if ($result) {
			$message = __('Zip-File successfully unpacked','nggallery').'<br />';		

			// parse now the folder and add to database
			$message .= nggAdmin::import_gallery($defaultpath.$foldername);
	
			nggGalleryPlugin::show_message($message);
		}
		
		return;
	}

	// **************************************************************
	function upload_images() {
	// upload of pictures
		
		global $wpdb;
		
		// WPMU action
		if (nggAdmin::check_quota())
			return;

		// Images must be an array
		$imageslist = array();

		// get selected gallery
		$galleryID = (int) $_POST['galleryselect'];

		if ($galleryID == 0) {
			nggGalleryPlugin::show_error(__('No gallery selected !','nggallery'));
			return;	
		}

		// get the path to the gallery	
		$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$galleryID' ");

		if (!$gallerypath){
			nggGalleryPlugin::show_error(__('Failure in database, no gallery path set !','nggallery'));
			return;
		} 
				
		// read list of images
		$dirlist = nggAdmin::scandir(WINABSPATH.$gallerypath);
		
		foreach ($_FILES as $key => $value) {
			
			// look only for uploded files
			if ($_FILES[$key]['error'] == 0) {
				$temp_file = $_FILES[$key]['tmp_name'];
				$filepart = pathinfo ( strtolower($_FILES[$key]['name']) );
				// required until PHP 5.2.0
				$filepart['filename'] = substr($filepart["basename"],0 ,strlen($filepart["basename"]) - (strlen($filepart["extension"]) + 1) );
				
				$filename = sanitize_title($filepart['filename']) . "." . $filepart['extension'];

				// check for allowed extension
				$ext = array("jpeg", "jpg", "png", "gif"); 
				if (!in_array($filepart['extension'],$ext)){ 
					nggGalleryPlugin::show_error('<strong>'.$_FILES[$key]['name'].' </strong>'.__('is no valid image file!','nggallery'));
					continue;
				}

				// check if this filename already exist in the folder
				$i = 0;
				while (in_array($filename,$dirlist)) {
					$filename = sanitize_title($filepart['filename']) . "_" . $i++ . "." .$filepart['extension'];
				}
				
				$dest_file = WINABSPATH.$gallerypath."/".$filename;
				
				//check for folder permission
				if (!is_writeable(WINABSPATH.$gallerypath)) {
					$message = sprintf(__('Unable to write to directory %s. Is this directory writable by the server?', 'nggallery'), WINABSPATH.$gallerypath);
					nggGalleryPlugin::show_error($message);
					return;				
				}
				
				// save temp file to gallery
				if (!@move_uploaded_file($_FILES[$key]['tmp_name'], $dest_file)){
					nggGalleryPlugin::show_error(__('Error, the file could not moved to : ','nggallery').$dest_file);
					nggAdmin::check_safemode(WINABSPATH.$gallerypath);		
					continue;
				} 
				if (!nggAdmin::chmod ($dest_file)) {
					nggGalleryPlugin::show_error(__('Error, the file permissions could not set','nggallery'));
					continue;
				}
				
				// add to imagelist & dirlist
				$imageslist[] = $filename;
				$dirlist[] = $filename;

			}
		}
		
		if (count($imageslist) > 0) {
			
			// add images to database		
			$image_ids = nggAdmin::add_Images($galleryID, $imageslist);

			//create thumbnails
			nggAdmin::do_ajax_operation( 'create_thumbnail' , $image_ids, __('Create new thumbnails','nggallery') );
			
			nggGalleryPlugin::show_message( count($image_ids) . __(' Image(s) successfully added','nggallery'));
		}
		
		return;

	} // end function
	
	// **************************************************************
	function swfupload_image($galleryID = 0) {
		// This function is called by the swfupload
		global $wpdb;
		
		$ngg_options = get_option('ngg_options');
		
		if ($galleryID == 0) {
			@unlink($temp_file);		
			return __('No gallery selected !','nggallery');;
		}

		// WPMU action
		if (nggAdmin::check_quota())
			return;

		// Check the upload
		if (!isset($_FILES["Filedata"]) || !is_uploaded_file($_FILES["Filedata"]["tmp_name"]) || $_FILES["Filedata"]["error"] != 0) 
			return __('Invalid upload. Error Code : ','nggallery').$_FILES["Filedata"]["error"];

		// get the filename and extension
		$temp_file = $_FILES["Filedata"]['tmp_name'];
		$filepart = pathinfo ( strtolower($_FILES["Filedata"]['name']) );
		// required until PHP 5.2.0
		$filepart['filename'] = substr($filepart["basename"],0 ,strlen($filepart["basename"]) - (strlen($filepart["extension"]) + 1) );
		$filename = sanitize_title($filepart['filename']).".".$filepart['extension'];

		// check for allowed extension
		$ext = array("jpeg", "jpg", "png", "gif"); 
		if (!in_array($filepart['extension'],$ext))
			return $_FILES[$key]['name'].__('is no valid image file!','nggallery');

		// get the path to the gallery	
		$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$galleryID' ");
		if (!$gallerypath){
			@unlink($temp_file);		
			return __('Failure in database, no gallery path set !','nggallery');
		} 

		// read list of images
		$imageslist = nggAdmin::scandir( WINABSPATH.$gallerypath );

		// check if this filename already exist
		$i = 0;
		while (in_array($filename,$imageslist)) {
			$filename = sanitize_title($filepart['filename']) . "_" . $i++ . "." .$filepart['extension'];
		}
		
		$dest_file = WINABSPATH.$gallerypath."/".$filename;
				
		// save temp file to gallery
		if ( !@move_uploaded_file($_FILES["Filedata"]['tmp_name'], $dest_file) ){
			nggAdmin::check_safemode(WINABSPATH.$gallerypath);	
			return __('Error, the file could not moved to : ','nggallery').$dest_file;
		} 
		
		if ( !nggAdmin::chmod($dest_file) )
			return __('Error, the file permissions could not set','nggallery');
		
		return "0";
	}	
	
	// **************************************************************
	function check_quota() {
		// Only for WPMU
			if ( (IS_WPMU) && wpmu_enable_function('wpmuQuotaCheck'))
				if( $error = upload_is_user_over_quota( false ) ) {
					nggGalleryPlugin::show_error( __( 'Sorry, you have used your space allocation. Please delete some files to upload more files.','nggallery' ) );
					return true;
				}
			return false;
	}
	
	// **************************************************************
	function chmod($filename = "") {
		// Set correct file permissions (taken from wp core)
		$stat = @ stat(dirname($filename));
		$perms = $stat['mode'] & 0007777;
		$perms = $perms & 0000666;
		if ( @chmod($filename, $perms) )
			return true;
			
		return false;
	}
	
	function check_safemode($foldername) {
		// Check UID in folder and Script
		// Read http://www.php.net/manual/en/features.safe-mode.php to understand safe_mode
		if ( SAFE_MODE ) {
			
			$script_uid = ( ini_get('safe_mode_gid') ) ? getmygid() : getmyuid();
			$folder_uid = fileowner($foldername);

			if ($script_uid != $folder_uid) {
				$message  = sprintf(__('SAFE MODE Restriction in effect! You need to create the folder <strong>%s</strong> manually','nggallery'), $foldername);
				$message .= '<br />' . sprintf(__('When safe_mode is on, PHP checks to see if the owner (%s) of the current script matches the owner (%s) of the file to be operated on by a file function or its directory','nggallery'), $script_uid, $folder_uid );
				nggGalleryPlugin::show_error($message);
				return false;
			}
		}
		
		return true;
	}
	
	function can_manage_this_gallery($check_ID) {
		// check is the ID fit's to the user_ID'
		global $user_ID, $wp_roles;
		
		// get the current user ID
		get_currentuserinfo();
		
		if ( !current_user_can('NextGEN Manage others gallery') )
			if ( $user_ID != $check_ID)
				return false;
		
		return true;
	
	}
	
	function do_ajax_operation( $operation, $image_array, $title = '' ) {
		
		if ( !is_array($image_array) || empty($image_array) )
			return;

		$js_array  = implode('","', $image_array);

		?>
		<script type="text/javascript">

			Images = new Array("<?php echo $js_array; ?>");

			nggAjaxOptions = {
				operation: "<?php echo $operation; ?>",
				ids: Images,		
			  	header: "<?php echo $title; ?>",
			  	maxStep: Images.length
			};
			
			jQuery(document).ready( function(){ 
				nggProgressBar.init( nggAjaxOptions );
				nggAjax.init( nggAjaxOptions );
			} );
		</script>
		
		<div id="progressbar_container" class="wrap"></div>
		
		<?php	
	}

} // END class nggAdmin

// **************************************************************
//TODO: Cannot be member of a class ? Check PCLZIP later...
function ngg_getOnlyImages($p_event, $p_header)	{
	
	return nggAdmin::getOnlyImages($p_event, $p_header);
	
}

?>