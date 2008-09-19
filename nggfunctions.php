<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/**
 * nggShowSlideshow()
 * 
 * @param integer $galleryID
 * @param integer $irWidth
 * @param integer $irHeight
 * @return the content
 */
function nggShowSlideshow($galleryID, $irWidth, $irHeight) {
	
	require_once (dirname (__FILE__).'/lib/swfobject.php');
	
	global $wpdb;

	$ngg_options = nggGalleryPlugin::get_option('ngg_options');

	// remove media file from RSS feed
	if ( is_feed() ) {
		$out = '[' . $ngg_options['galTextSlide'] . ']'; 
		return $out;
	}

	if (empty($irWidth) ) $irWidth = (int) $ngg_options['irWidth'];
	if (empty($irHeight)) $irHeight = (int) $ngg_options['irHeight'];

	// init the flash output
	$swfobject = new swfobject( NGGALLERY_URLPATH.'imagerotator.swf', 'so' . $galleryID, $irWidth, $irHeight, '7.0.0', 'false');

	$swfobject->message = '<p>'. __('The <a href="http://www.macromedia.com/go/getflashplayer">Flash Player</a> and <a href="http://www.mozilla.com/firefox/">a browser with Javascript support</a> are needed..', 'nggallery').'</p>';
	$swfobject->add_params('wmode', 'opaque');
	$swfobject->add_params('allowfullscreen', 'true');

	// adding the flash parameter	
	$swfobject->add_flashvars( 'file', NGGALLERY_URLPATH.'xml/imagerotator.php?gid='.$galleryID );
	$swfobject->add_flashvars( 'shuffle', $ngg_options['irShuffle'], 'false', 'bool');
	$swfobject->add_flashvars( 'linkfromdisplay', $ngg_options['irLinkfromdisplay'], 'false', 'bool');
	$swfobject->add_flashvars( 'shownavigation', $ngg_options['irShownavigation'], 'false', 'bool');
	$swfobject->add_flashvars( 'showicons', $ngg_options['irShowicons'], 'true', 'bool');
	$swfobject->add_flashvars( 'kenburns', $ngg_options['irKenburns'], 'false', 'bool');
	$swfobject->add_flashvars( 'overstretch', $ngg_options['irOverstretch'], 'false', 'bool');
	$swfobject->add_flashvars( 'rotatetime', $ngg_options['irRotatetime'], 5, 'int');
	$swfobject->add_flashvars( 'transition', $ngg_options['irTransition'], 'random', 'string');
	$swfobject->add_flashvars( 'backcolor', $ngg_options['irBackcolor'], 'FFFFFF', 'string', '0x');
	$swfobject->add_flashvars( 'frontcolor', $ngg_options['irFrontcolor'], '000000', 'string', '0x');
	$swfobject->add_flashvars( 'lightcolor', $ngg_options['irLightcolor'], '000000', 'string', '0x');
	$swfobject->add_flashvars( 'screencolor', $ngg_options['irScreencolor'], '000000', 'string', '0x');
	if ($ngg_options['irWatermark'])
		$swfobject->add_flashvars( 'logo', $ngg_options['wmPath'], '', 'string'); 
	$swfobject->add_flashvars( 'audio', $ngg_options['irAudio'], '', 'string');
	$swfobject->add_flashvars( 'width', $irWidth, '260');
	$swfobject->add_flashvars( 'height', $irHeight, '320');	
	// create the output
	$out  = $swfobject->output();
	// add now the script code
    $out .= "\n".'<script type="text/javascript" defer="defer">';
	if ($ngg_options['irXHTMLvalid']) $out .= "\n".'<!--';
	if ($ngg_options['irXHTMLvalid']) $out .= "\n".'//<![CDATA[';
	$out .= $swfobject->javascript();
	if ($ngg_options['irXHTMLvalid']) $out .= "\n".'//]]>';
	if ($ngg_options['irXHTMLvalid']) $out .= "\n".'-->';
	$out .= "\n".'</script>';

	$out = apply_filters('ngg_show_slideshow_content', $out);
			
	return $out;	
}

/**
 * nggShowGallery()
 * 
 * @param int $galleryID
 * @return the content
 */
function nggShowGallery( $galleryID ) {
	
	global $wpdb, $nggRewrite;
	
	$ngg_options = nggGalleryPlugin::get_option('ngg_options');
	$galleryID = (int) $galleryID;

	// $_GET from wp_query
	$show    = get_query_var('show');
	$pid     = get_query_var('pid');
	$pageid  = get_query_var('pageid');
	
	// set $show if slideshow first
	if ( empty( $show ) AND ($ngg_options['galShowOrder'] == 'slide')) {
		if (is_home()) $pageid = get_the_ID();
		$show = 'slide';
	}

	// go on only on this page
	if ( !is_home() || $pageid == get_the_ID() ) { 
			
		// 1st look for ImageBrowser link
		if (!empty( $pid))  {
			$out = nggShowImageBrowser($galleryID);
			return $out;
		}
		
		// 2nd look for slideshow
		if ( $show == 'slide' ) {
			$args['show'] = "gallery";
			$out  = '<div class="ngg-galleryoverview">';
			$out .= '<div class="slideshowlink"><a class="slideshowlink" href="' . $nggRewrite->get_permalink($args) . '">'.$ngg_options['galTextGallery'].'</a></div>';
			$out .= nggShowSlideshow($galleryID,$ngg_options['irWidth'],$ngg_options['irHeight']);
			$out .= '</div>'."\n";
			$out .= '<div class="ngg-clear"></div>'."\n";
			return $out;
		}
	}
	
	//Set sort order value, if not used (upgrade issue)
	$ngg_options['galSort'] = ($ngg_options['galSort']) ? $ngg_options['galSort'] : "pid";
	$ngg_options['galSortDir'] = ($ngg_options['galSortDir'] == "DESC") ? "DESC" : "ASC";

	// get all picture with this galleryid
	$picturelist = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->nggallery AS t INNER JOIN $wpdb->nggpictures AS tt ON t.gid = tt.galleryid WHERE t.gid = '$galleryID' AND tt.exclude != 1 ORDER BY tt.$ngg_options[galSort] $ngg_options[galSortDir] ");
	if (is_array($picturelist)) { 
		$out = nggCreateGallery($picturelist,$galleryID);
	}
	
	$out = apply_filters('ngg_show_gallery_content', $out, intval($galleryID));
	return $out;
}

/**
 * nggCreateGallery()
 * 
 * @param mixed $picturelist
 * @param bool $galleryID
 * @return the content
 */
function nggCreateGallery($picturelist, $galleryID = false) {
    global $nggRewrite;
    
    $ngg_options = nggGalleryPlugin::get_option('ngg_options');
	$siteurl = get_option ('siteurl');
	    
    // $_GET from wp_query
	$nggpage  = get_query_var('nggpage');
	$pageid   = get_query_var('pageid');
    
    if (!is_array($picturelist)) {
		$picturelist = array($picturelist);
	}
	
	$gallery = new stdclass;
	$gallery->ID = (int) $galleryID;
	$gallery->show_slideshow = false;
	
	$maxElement  = $ngg_options['galImages'];
	$thumbwidth  = $ngg_options['thumbwidth'];
	$thumbheight = $ngg_options['thumbheight'];		
	
	// set thumb size 
	$thumbsize = "";
	if ($ngg_options['thumbfix'])  $thumbsize = 'width="'.$thumbwidth.'" height="'.$thumbheight.'"';
	if ($ngg_options['thumbcrop']) $thumbsize = 'width="'.$thumbwidth.'" height="'.$thumbwidth.'"';
	
	// show slideshow link
	if ($galleryID) {
		if (($ngg_options['galShowSlide']) AND (NGGALLERY_IREXIST)) {
			$gallery->show_slideshow = true;
			$gallery->slideshow_link = $nggRewrite->get_permalink(array ('show' => "slide"));
			$gallery->slideshow_link_text = $ngg_options['galTextSlide'];
		}
		
		if ($ngg_options['usePicLens']) {
			$gallery->show_piclens = true;
			$gallery->piclens_link = "javascript:PicLensLite.start({feedUrl:'" . nggMediaRss::get_gallery_mrss_url($gallery->ID) . "'});";
			$gallery->piclens_link_text = __('[View with PicLens]','nggallery');
		}
	}
	
 	// check for page navigation
 	if ($maxElement > 0) {
	 	if ( !is_home() || $pageid == get_the_ID() ) {
			if ( !empty( $nggpage ) )	
				$page = (int) $nggpage;
			else
				 $page = 1;
		}
		else $page = 1;
		 
	 	$start = $offset = ( $page - 1 ) * $maxElement;
	 	
	 	$total = count($picturelist);
	 	
		// remove the element if we didn't start at the beginning
		if ($start > 0 ) array_splice($picturelist, 0, $start);
		
		// return the list of images we need
		array_splice($picturelist, $maxElement);
	
		$navigation = nggGalleryPlugin::create_navigation($page, $total, $maxElement);
	} else {
		$navigation = '<div class="ngg-clear">&nbsp;</div>';
	}	

	foreach ($picturelist as $key => $picture) {
		// Get image
		$image = nggImageDAO::find_image($picture->pid);
	
		// set image url
		$folder_url		= $siteurl."/".$picture->path."/";
		
		// choose link between imagebrowser or effect
		$link = ($ngg_options['galImgBrowser']) ? $nggRewrite->get_permalink(array('pid'=>$picture->pid)) : $folder_url.$picture->filename;	
		
		// get the effect code
		if ($galleryID) {
			$thumbcode = ($ngg_options['galImgBrowser']) ? "" : $image->get_thumbcode($picturelist[0]->name);
		} else {
			$thumbcode = ($ngg_options['galImgBrowser']) ? "" : $image->get_thumbcode(get_the_title());
		}
		
		// add a filter for the link
		$picturelist[$key]->imageURL = apply_filters('ngg_create_gallery_link', $link, $picture);
		$picturelist[$key]->thumbnailURL = $folder_url . "thumbs/thumbs_" . $picture->filename;
		$picturelist[$key]->size = $thumbsize;
		$picturelist[$key]->thumbcode  = $thumbcode;
	}

	// create the output
	$out = nggGalleryPlugin::capture ('gallery', array ('gallery' => $gallery, 'images' => $picturelist, 'pagination' => $navigation) );

	return $out;
}

/**
 * nggShowAlbum()
 * 
 * @param int $albumID
 * @param string $mode
 * @param string $sortorder
 * @return the content
 */
function nggShowAlbum($albumID, $mode = "extend", $sortorder = "") {
	
	global $wpdb;
	
	// $_GET from wp_query
	$gallery  = get_query_var('gallery');
	$album    = get_query_var('album');

	// look for gallery variable 
	if (!empty( $gallery ))  {
		
		if ( $albumID != $album ) 
			return;

		$galleryID = (int) $gallery;
		$out = nggShowGallery($galleryID);
		return $out;
	} 

	$mode = ltrim($mode,',');
	$albumID = $wpdb->escape($albumID);

	if (!empty($sortorder)) {
		$gallery_array = unserialize($sortorder);
	} 
	
 	if ( is_array($gallery_array) )
 		$out .= nggCreateAlbum( $gallery_array, $mode, $albumID );
	
	$out = apply_filters('ngg_show_album_content', $out, intval($albumID));

	return $out;
}

/**
 * nggCreateAlbum()
 * 
 * @param int $galleriesID
 * @param string $mode
 * @param integer $albumID
 * @return the content
 */
function nggCreateAlbum( $galleriesID, $mode = "extend", $albumID = 0) {
	// create a gallery overview div
	
	global $wpdb, $nggRewrite;
	
	$ngg_options = nggGalleryPlugin::get_option('ngg_options');
	$sortorder = $galleriesID;
	$galleries = array();
	// if sombod didn't enter any mode , take the extend version
	$mode = ( empty($mode) ) ? "extend" : $mode ;
	
	// get the galleries information 	
 	foreach ($galleriesID as $i => $value)
   		$galleriesID[$i] = addslashes($value);
 	$unsort_galleries = $wpdb->get_results('SELECT * FROM '.$wpdb->nggallery.' WHERE gid IN (\''.implode('\',\'', $galleriesID).'\')', OBJECT_K);
	//TODO: Check this, problem exist when previewpic = 0 
	//$galleries = $wpdb->get_results('SELECT t.*, tt.* FROM '.$wpdb->nggallery.' AS t INNER JOIN '.$wpdb->nggpictures.' AS tt ON t.previewpic = tt.pid WHERE t.gid IN (\''.implode('\',\'', $galleriesID).'\')', OBJECT_K);

	// get the counter values 	
	$picturesCounter = $wpdb->get_results('SELECT galleryid, COUNT(*) as counter FROM '.$wpdb->nggpictures.' WHERE galleryid IN (\''.implode('\',\'', $galleriesID).'\') AND exclude != 1 GROUP BY galleryid', OBJECT_K);
	foreach ($picturesCounter as $key => $value)
		$unsort_galleries[$key]->counter = $value->counter;
	
	// get the id's of the preview images
 	$imagesID = array();
 	foreach ($unsort_galleries as $gallery_row)
 		$imagesID[] = $gallery_row->previewpic;
 	$albumPreview = $wpdb->get_results('SELECT pid, filename FROM '.$wpdb->nggpictures.' WHERE pid IN (\''.implode('\',\'', $imagesID).'\')', OBJECT_K);

	// re-order them and populate some 
 	foreach ($sortorder as $key) {
 		$galleries[$key] = $unsort_galleries[$key];
		
		// add the file name and the link 
		if ($galleries[$key]->previewpic  != 0) {
			$galleries[$key]->previewname = $albumPreview[$galleries[$key]->previewpic]->filename;
			$galleries[$key]->previewurl  = get_option ('siteurl')."/".$galleries[$key]->path."/thumbs/thumbs_".$albumPreview[$galleries[$key]->previewpic]->filename;
		} else {
			$first_image = $wpdb->get_row('SELECT * FROM '.$wpdb->nggpictures.' WHERE exclude != 1 AND galleryid = '. $key .' ORDER by pid DESC limit 0,1');
			$galleries[$key]->previewpic  = $first_image->pid;
			$galleries[$key]->previewname = $first_image->filename;
			$galleries[$key]->previewurl  = get_option ('siteurl')."/".$galleries[$key]->path."/thumbs/thumbs_".$first_image->filename;
		}

		// choose between variable and page link
		if ($ngg_options['galNoPages']) {
			$args['album'] = $albumID; 
			$args['gallery'] = $key;
			$galleries[$key]->pagelink = $nggRewrite->get_permalink($args);
		} else {
			$galleries[$key]->pagelink = get_permalink($galleries[$key]->pageid);
		}
		
		// description can contain HTML tags
		$galleries[$key]->galdesc = html_entity_decode ( stripslashes($galleries[$key]->galdesc) ) ;
	}
	
 	$out = '';
 	
	// create the output
	$out = nggGalleryPlugin::capture ('album-'.$mode, array ('albumID' => $albumID, 'galleries' => $galleries, 'mode' => $mode) );

	return $out;
 	
}

/**
 * nggShowImageBrowser()
 * 
 * @param int $galleryID
 * @return the content
 */
function nggShowImageBrowser($galleryID) {
	
	global $wpdb;
	
	$ngg_options = nggGalleryPlugin::get_option('ngg_options');
	
	// get the pictures
	$galleryID = $wpdb->escape($galleryID);
	$picturelist = $wpdb->get_col("SELECT pid FROM $wpdb->nggpictures WHERE galleryid = '$galleryID' AND exclude != 1 ORDER BY $ngg_options[galSort] $ngg_options[galSortDir]");	
	if (is_array($picturelist)) { 
		$out = nggCreateImageBrowser($picturelist);
	}
	
	$out = apply_filters('ngg_show_imagebrowser_content', $out, intval($galleryID));
	return $out;
	
}

/**
 * nggCreateImageBrowser()
 * 
 * @param array $picarray with pid
 * @return the content
 */
function nggCreateImageBrowser($picarray) {

	global $nggRewrite;
	
	require_once(dirname (__FILE__).'/lib/ngg-meta.lib.php');
	
	// $_GET from wp_query
	$pid  = get_query_var('pid');

    if (!is_array($picarray))
		$picarray = array($picarray);

	$total = count($picarray);

	// look for gallery variable 
	if ( !empty( $pid )) {
		$act_pid = (int) $pid;
	} else {
		reset($picarray);
		$act_pid = current($picarray);
	}
	
	// get ids for back/next
	$key = array_search($act_pid,$picarray);
	if (!$key) {
		$act_pid = reset($picarray);
		$key = key($picarray);
	}
	$back_pid = ( $key >= 1 ) ? $picarray[$key-1] : end($picarray) ;
	$next_pid = ( $key < ($total-1) ) ? $picarray[$key+1] : reset($picarray) ;
	
	// get the picture data
	$picture = nggImageDAO::find_image($act_pid);
	// if we didn't get some data, exit now
	if ($picture == null)
		return;
		
	// add more variables for render output
	$picture->href_link = $picture->get_href_link();
	$picture->previous_image_link = $nggRewrite->get_permalink(array ('pid' => $back_pid));
	$picture->next_image_link  = $nggRewrite->get_permalink(array ('pid' => $next_pid));
	$picture->number = $key + 1;
	$picture->total = $total;
	$picture->alttext = html_entity_decode(stripslashes($picture->alttext));
	$picture->description = html_entity_decode(stripslashes($picture->description));
	
	// let's get the meta data
	$meta = new nggMeta($picture->imagePath);
	$exif = $meta->get_EXIF();
	$iptc = $meta->get_IPTC();
	$xmp  = $meta->get_XMP();
		
	// create the output
	$out = nggGalleryPlugin::capture ('imagebrowser', array ('image' => $picture , 'meta' => $meta, 'exif' => $exif, 'iptc' => $iptc, 'xmp' => $xmp) );
	
	return $out;
	
}

/**
 * nggSinglePicture() - create a gallery based on the tags
 * 
 * @param int $imageID, db-ID of the image
 * @param int $width, width of the image
 * @param int $height, height of the image
 * @param string $mode could be none, watermark, web20
 * @param string $float could be none, left, right
 * @return the content
 */
function nggSinglePicture($imageID, $width=250, $height=250, $mode='', $float='') {
	global $wpdb, $post;
	
	$ngg_options = nggGalleryPlugin::get_option('ngg_options');
	
	// remove the comma
	$float  = ltrim( $float, ',' );
	$mode   = ltrim( $mode, ',' );
	$width  = ltrim( $width, ',' );
	$height = ltrim( $height, ',' );

	// get picturedata
	$picture = nggImageDAO::find_image($imageID);
	// if we didn't get some data, exit now
	if ($picture == null)
		return;
			
	// add float to img
	if (!empty($float)) {
		switch ($float) {
		
		case 'left': $float=' ngg-left';
		break;
		
		case 'right': $float=' ngg-right';
		break;

		case 'center': $float=' ngg-center';
		break;
		
		default: $float='';
		break;
		}
	}
	
	// check fo cached picture
	if ( ($ngg_options['imgCacheSinglePic']) && ($post->post_status == 'publish') )
		$cache_url = $picture->cached_singlepic_file($width, $height, $mode );

	// add fullsize picture as link
	$out  = '<a href="'.$picture->imageURL.'" title="'.stripslashes($picture->description).'" '.$picture->get_thumbcode("singlepic".$imageID).' >';
	if (!$cache_url)
		$out .= '<img class="ngg-singlepic'. $float .'" src="'.NGGALLERY_URLPATH.'nggshow.php?pid='.$imageID.'&amp;width='.$width.'&amp;height='.$height.'&amp;mode='.$mode.'" alt="'.stripslashes($picture->alttext).'" title="'.stripslashes($picture->alttext).'" width="'. $width .'" height="'. $height.'" />';
	else
		$out .= '<img class="ngg-singlepic'. $float .'" src="'.$cache_url.'" alt="'.stripslashes($picture->alttext).'" title="'.stripslashes($picture->alttext).'" width="'. $width .'" height="'. $height.'" />';
	$out .= '</a>';

	$out = apply_filters('ngg_show_singlepic_content', $out, $picture );
	
	return $out;
}

/**
 * nggShowGalleryTags() - create a gallery based on the tags
 * 
 * @param mixed $taglist list of tags as csv
 * @return the content
 */
function nggShowGalleryTags($taglist) {	
	global $wpdb;
	
	// $_GET from wp_query
	$pid  	= get_query_var('pid');
	$pageid = get_query_var('pageid');
	
	// get now the related images
	$picturelist = nggTags::find_images_for_tags($taglist , 'ASC');
	
	// look for ImageBrowser 
	if ( $pageid == get_the_ID() || !is_home() )  
		if (!empty( $pid ))  {
			foreach ($picturelist as $picture) {
				$picarray[] = $picture->pid;
			}
			$out = nggCreateImageBrowser($picarray);
			return $out;
		}

	// go on if not empty
	if (empty($picturelist))
		return;
	
	// show gallery
	if (is_array($picturelist)) { 
		$out = nggCreateGallery($picturelist,false);
	}
	
	$out = apply_filters('ngg_show_gallery_tags_content', $out, $taglist);
	return $out;
}

/**
 * nggShowRelatedGallery() - create a gallery based on the tags
 * 
 * @param string $taglist list of tags as csv
 * @param integer $maxImages limit the number of images to show
 * @return the content
 */ 
function nggShowRelatedGallery($taglist, $maxImages = 0) {
	global $wpdb;
	
	$ngg_options = nggGalleryPlugin::get_option('ngg_options');
	
	// get now the related images
	$picturelist = nggTags::find_images_for_tags($taglist, 'RAND');
	
	// go on if not empty
	if (empty($picturelist)) {
		return;
	}
	
	// cut the list to maxImages
	if ($maxImages > 0 ) {
		array_splice($picturelist, $maxImages);
	}
	
 	// *** build the gallery output
	$out   = '<div class="ngg-related-gallery">';
	foreach ($picturelist as $picture) {
		// set gallery url
		$folder_url 	= get_option ('siteurl')."/".$picture->path."/";
		$thumbnailURL 	= get_option ('siteurl')."/".$picture->path.nggGalleryPlugin::get_thumbnail_folder($picture->path, FALSE);

		// get the effect code
		$thumbcode = $picture->get_thumbcode("Related images for " . get_the_title());
	
		$out .= '<a href="'.$folder_url.$picture->filename.'" title="'.stripslashes($picture->description).'" '.$thumbcode.' >';
		$out .= '<img title="'.stripslashes($picture->alttext).'" alt="'.stripslashes($picture->alttext).'" src="'.$thumbnailURL. "thumbs_" .$picture->filename.'" '.$thumbsize.' />';
		$out .= '</a>'."\n";
	}
	$out .= '</div>'."\n";
	
	$out = apply_filters('ngg_show_related_gallery_content', $out, $taglist);
	return $out;
}

/**
 * nggShowAlbumTags() - create a gallery based on the tags
 * 
 * @param string $taglist list of tags as csv
 * @return the content
 */
function nggShowAlbumTags($taglist) {
	
	global $wpdb, $nggRewrite;

	// $_GET from wp_query
	$tag  			= get_query_var('gallerytag');
	$pageid 		= get_query_var('pageid');
	
	// look for gallerytag variable 
	if ( $pageid == get_the_ID() || !is_home() )  {
		if (!empty( $tag ))  {
	
			// avoid this evil code $sql = 'SELECT name FROM wp_ngg_tags WHERE slug = \'slug\' union select concat(0x7c,user_login,0x7c,user_pass,0x7c) from wp_users WHERE 1 = 1';
			$slug = attribute_escape( $tag );
			$tagname = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $wpdb->terms WHERE slug = %s", $slug ) );
			$out  = '<div id="albumnav"><span><a href="'.get_permalink().'" title="'.__('Overview', 'nggallery').'">'.__('Overview', 'nggallery').'</a> | '.$tagname.'</span></div>';
			$out .=  nggShowGalleryTags($slug);
			return $out;
	
		} 
	}
	
	// get now the related images
	$picturelist = nggTags::get_album_images($taglist);

	// go on if not empty
	if ( empty($picturelist) )
		return;
	
	// re-structure the object that we can use the standard template	
	foreach ($picturelist as $key => $picture) {
		$picturelist[$key]->previewpic  = $picture->pid;
		$picturelist[$key]->previewname = $picture->filename;
		$picturelist[$key]->previewurl  = get_option ('siteurl')."/".$picture->path."/thumbs/thumbs_".$picture->filename;
		$picturelist[$key]->counter     = $picture->count;
		$picturelist[$key]->title     	= $picture->name;
		$picturelist[$key]->pagelink    = $nggRewrite->get_permalink(array('gallerytag'=>$picture->slug));
	}	

	// create the output
	$out = nggGalleryPlugin::capture ('album-compact', array ('albumID' => '0', 'galleries' => $picturelist, 'mode' => 'compact') );
	
	$out = apply_filters('ngg_show_album_tags_content', $out, $taglist);
	
	return $out;
}

/**
 * nggShowRelatedImages() - return related images based on category or tags
 * 
 * @param string $type
 * @param integer $maxImages
 * @return the content
 */
function nggShowRelatedImages($type = '', $maxImages = 0) {
	$ngg_options = nggGalleryPlugin::get_option('ngg_options');

	if ($type == '') {
		$type = $ngg_options['appendType'];
		$maxImages = $ngg_options['maxImages'];
	}

	$sluglist = array();
	switch ($type) {
		
	case "tags":
		if (function_exists('get_the_tags')) { 
			$taglist = get_the_tags();
			
			if (is_array($taglist)) {
				foreach ($taglist as $tag) {
					$sluglist[] = $tag->slug;
				}
			}
		}
		break;
		
	case "category":
		$catlist = get_the_category();
		
		if (is_array($catlist)) {
			foreach ($catlist as $cat) {
				$sluglist[] = $cat->category_nicename;
			}
		}
	}
	
	$sluglist = implode(",", $sluglist);
	$out = nggShowRelatedGallery($sluglist, $maxImages);
	
	return $out;
}

/**
 * the_related_images()
 * function for theme authors
 * 
 * @return void
 */
function the_related_images($type = 'tags', $maxNumbers = 7) {
	echo nggShowRelatedImages($type, $maxNumbers);
}

?>
