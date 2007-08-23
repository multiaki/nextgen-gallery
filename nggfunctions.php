<?php

function searchnggallerytags($content) {

	global $wpdb;
	$ngg_options = get_option('ngg_options');
	
	$search = "@\[singlepic=(\d+)(|,\d+|,)(|,\d+|,)(|,watermark|,web20|,)(|,right|,left|,)\]@i";
	
	if	(preg_match_all($search, $content, $matches)) {
		
		if (is_array($matches)) {
			foreach ($matches[1] as $key =>$v0) {
				// check for correct id
				$result = $wpdb->get_var("SELECT filename FROM $wpdb->nggpictures WHERE pid = '$v0' ");
				if($result){
					$search = $matches[0][$key];
					$replace= nggSinglePicture($v0,$matches[2][$key],$matches[3][$key],$matches[4][$key],$matches[5][$key]);
					$content= str_replace ($search, $replace, $content);
				}
			}	
		}
	}// end singelpic

	$search = "@(?:<p>)*\s*\[album\s*=\s*(\w+|^\+)(|,extend|,compact)\]\s*(?:</p>)*@i";
	
	if	(preg_match_all($search, $content, $matches)) {
		if (is_array($matches)) {
			foreach ($matches[1] as $key =>$v0) {
				// check for album id
				$albumID = $wpdb->get_var("SELECT id FROM $wpdb->nggalbum WHERE id = '$v0' ");
				if(!$albumID) $albumID = $wpdb->get_var("SELECT id FROM $wpdb->nggalbum WHERE name = '$v0' ");
				if($albumID) {
					$search = $matches[0][$key];
					$replace= nggShowAlbum($albumID,$matches[2][$key]);
					$content= str_replace ($search, $replace, $content);
				}
			}	
		}
	}// end album

	$search = "@(?:<p>)*\s*\[gallery\s*=\s*(\w+|^\+)\]\s*(?:</p>)*@i";
	
	if	(preg_match_all($search, $content, $matches)) {
		if (is_array($matches)) {
			foreach ($matches[1] as $key =>$v0) {
				// check for gallery id
				$galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE gid = '$v0' ");
				if(!$galleryID) $galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE name = '$v0' ");
				if($galleryID) {
					$search = $matches[0][$key];
					$replace= nggShowGallery($galleryID);
					$content= str_replace ($search, $replace, $content);
				}
			}	
		}
	}// end gallery

	$search = "@(?:<p>)*\s*\[imagebrowser\s*=\s*(\w+|^\+)\]\s*(?:</p>)*@i";
	
	if	(preg_match_all($search, $content, $matches)) {
		if (is_array($matches)) {
			foreach ($matches[1] as $key =>$v0) {
				// check for gallery id
				$galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE gid = '$v0' ");
				if(!$galleryID) $galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE name = '$v0' ");
				if($galleryID) {
					$search = $matches[0][$key];
					$replace= nggShowImageBrowser($galleryID);
					$content= str_replace ($search, $replace, $content);
				}
			}	
		}
	}// end gallery
	
	$search = "@(?:<p>)*\s*\[slideshow\s*=\s*(\w+|^\+)(|,(\d+)|,)(|,(\d+))\]\s*(?:</p>)*@i";

	if	(preg_match_all($search, $content, $matches)) {
		if (is_array($matches)) {
			foreach ($matches[1] as $key =>$v0) {
				// check for gallery id
				$galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE gid = '$v0' ");
				if(!$galleryID) $galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE name = '$v0' ");
				if($galleryID) {
					$search = $matches[0][$key];
					// get the size if they are set
			 		$irWidth  =  $matches[3][$key]; 
					$irHeight =  $matches[5][$key];
					$replace= nggShowSlideshow($galleryID,$irWidth,$irHeight);
					$content= str_replace ($search, $replace, $content);
				}
			}	
		}
	}// end slideshow
	
	$search = "@(?:<p>)*\s*\[tags\s*=\s*(.*?)\s*\]\s*(?:</p>)*@i";

	if	(preg_match_all($search, $content, $matches)) {
		if (is_array($matches)) {
			foreach ($matches[1] as $key =>$v0) {
				$search = $matches[0][$key];
				$replace= nggShowGalleryTags($v0);
				$content= str_replace ($search, $replace, $content);
			}	
		}
	}// end gallery tags 

	$search = "@(?:<p>)*\s*\[albumtags\s*=\s*(.*?)\s*\]\s*(?:</p>)*@i";

	if	(preg_match_all($search, $content, $matches)) {
		if (is_array($matches)) {
			foreach ($matches[1] as $key =>$v0) {
				$search = $matches[0][$key];
				$replace= nggShowAlbumTags($v0);
				$content= str_replace ($search, $replace, $content);
			}	
		}
	}// end album tags 
	
	// attach related images based on category or tags
	if ($ngg_options['activateTags']) 
		$content .= nggShowRelatedImages();
	
	return $content;
}// end search content

/**********************************************************/
function nggShowSlideshow($galleryID,$irWidth,$irHeight) {
	
	global $wpdb;
	$ngg_options = get_option('ngg_options');
	
	if (empty($irWidth) ) $irWidth = $ngg_options['irWidth'];
	if (empty($irHeight)) $irHeight = $ngg_options['irHeight'];

	$replace  = "\n".'<div class="slideshow" id="ngg_slideshow'.$galleryID.'">';
	$replace .= '<p>The <a href="http://www.macromedia.com/go/getflashplayer">Flash Player</a> and <a href="http://www.mozilla.com/firefox/">a browser with Javascript support</a> are needed..</p></div>';
    $replace .= "\n\t".'<script type="text/javascript">';
	if ($ngg_options['irXHTMLvalid']) $replace .= "\n\t".'<!--';
	if ($ngg_options['irXHTMLvalid']) $replace .= "\n\t".'//<![CDATA[';
	$replace .= "\n\t\t".'var so = new SWFObject("'.NGGALLERY_URLPATH.'imagerotator.swf", "ngg_slideshow'.$galleryID.'", "'.$irWidth.'", "'.$irHeight.'", "7", "#'.$ngg_options[irBackcolor].'");';
	$replace .= "\n\t\t".'so.addParam("wmode", "opaque");';
	$replace .= "\n\t\t".'so.addVariable("file", "'.NGGALLERY_URLPATH.'nggextractXML.php?gid='.$galleryID.'");';
	if (!$ngg_options['irShuffle']) $replace .= "\n\t\t".'so.addVariable("shuffle", "false");';
	if ($ngg_options['irLinkfromdisplay']) $replace .= "\n\t\t".'so.addVariable("linkfromdisplay", "false");';
	if ($ngg_options['irShownavigation']) $replace .= "\n\t\t".'so.addVariable("shownavigation", "true");';
	if ($ngg_options['irShowicons']) $replace .= "\n\t\t".'so.addVariable("showicons", "true");';
	if ($ngg_options['irKenburns']) $replace .= "\n\t\t".'so.addVariable("kenburns", "true");';
	if ($ngg_options['irWatermark']) $replace .= "\n\t\t".'so.addVariable("logo", "'.$ngg_options['wmPath'].'");';
	if (!empty($ngg_options['irAudio'])) $replace .= "\n\t\t".'so.addVariable("audio", "'.$ngg_options['irAudio'].'");';
	$replace .= "\n\t\t".'so.addVariable("overstretch", "'.$ngg_options['irOverstretch'].'");';
	$replace .= "\n\t\t".'so.addVariable("backcolor", "0x'.$ngg_options['irBackcolor'].'");';
	$replace .= "\n\t\t".'so.addVariable("frontcolor", "0x'.$ngg_options['irFrontcolor'].'");';
	$replace .= "\n\t\t".'so.addVariable("lightcolor", "0x'.$ngg_options['irLightcolor'].'");';
	$replace .= "\n\t\t".'so.addVariable("rotatetime", "'.$ngg_options['irRotatetime'].'");';
	$replace .= "\n\t\t".'so.addVariable("transition", "'.$ngg_options['irTransition'].'");';	
	$replace .= "\n\t\t".'so.addVariable("width", "'.$irWidth.'");';
	$replace .= "\n\t\t".'so.addVariable("height", "'.$irHeight.'");'; 
	$replace .= "\n\t\t".'so.write("ngg_slideshow'.$galleryID.'");';
	if ($ngg_options['irXHTMLvalid']) $replace .= "\n\t".'//]]>';
	if ($ngg_options['irXHTMLvalid']) $replace .= "\n\t".'-->';
	$replace .= "\n\t".'</script>';
		
	return $replace;
}

/**********************************************************/
function nggShowGallery($galleryID) {
	
	global $wpdb;
	$ngg_options = get_option('ngg_options');

	// Get option
	$maxElement = $ngg_options['galImages'];
	$thumbwidth = $ngg_options['thumbwidth'];
	$thumbheight = $ngg_options['thumbheight'];
	
	// set thumb size 
	$thumbsize = "";
	if ($ngg_options['thumbfix'])  $thumbsize = 'style="width:'.$thumbwidth.'px; height:'.$thumbheight.'px;"';
	if ($ngg_options['thumbcrop']) $thumbsize = 'style="width:'.$thumbwidth.'px; height:'.$thumbwidth.'px;"';
	
	// get gallery values
	$act_gallery = $wpdb->get_row("SELECT * FROM $wpdb->nggallery WHERE gid = '$galleryID' ");

	// get the effect code
	$thumbcode = nggallery::get_thumbcode($act_gallery->name);

	// set gallery url
	$folder_url 	= get_option ('siteurl')."/".$act_gallery->path."/";
	$thumbnailURL 	= get_option ('siteurl')."/".$act_gallery->path.nggallery::get_thumbnail_folder($act_gallery->path, FALSE);
	$thumb_prefix   = nggallery::get_thumbnail_prefix($act_gallery->path, FALSE);

	// slideshow first
	if ( !isset( $_GET['show'] ) AND ($ngg_options['galShowOrder'] == 'slide')) $_GET['show'] = slide;
	// show a slide show
	if ( isset( $_GET['show'] ) AND ($_GET['show'] == slide) ) {
		$getvalue['show'] = "gallery";
		$gallerycontent  = '<div class="ngg-galleryoverview">';
		$gallerycontent .= '<a class="slideshowlink" href="' . add_query_arg($getvalue) . '">'.$ngg_options['galTextGallery'].'</a>';
		$gallerycontent .= nggShowSlideshow($galleryID,$ngg_options['irWidth'],$ngg_options['irHeight']);
		$gallerycontent .= '</div>'."\n";
		$gallerycontent .= '<div class="ngg-clear"></div>'."\n";
		return $gallerycontent;
	}

	// use the jQuery Plugin if activated
	if (($ngg_options['thumbEffect'] == "thickbox") && ($ngg_options['galUsejQuery'])) {
		$gallerycontent .= nggShowJSGallery($galleryID);
		return $gallerycontent;
	}
	
 	// check for page navigation
 	if ($maxElement > 0) {	
		if ( isset( $_GET['nggpage'] ) )	$page = (int) $_GET['nggpage'];
		else $page = 1; 
	 	$start = $offset = ( $page - 1 ) * $maxElement;
	 	
		//TODO: Check for proper values
	 	$ngg_options['galSort'] = ($ngg_options['galSort']) ? $ngg_options[galSort] : "pid";
		$ngg_options['galSortDir'] = ($ngg_options['galSortDir'] == "DESC") ? "DESC" : "ASC";
	
		$picturelist = $wpdb->get_results("SELECT * FROM $wpdb->nggpictures WHERE galleryid = '$galleryID' AND exclude != 1 ORDER BY $ngg_options[galSort] $ngg_options[galSortDir] LIMIT $start, $maxElement ");
		$total = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpictures WHERE galleryid = '$galleryID' AND exclude != 1 ");	
		
		$navigation = nggallery::create_navigation($page, $total, $maxElement);

	} else {
		$picturelist = $wpdb->get_results("SELECT * FROM $wpdb->nggpictures WHERE galleryid = '$galleryID' AND exclude != 1 ORDER BY $ngg_options[galSort] $ngg_options[galSortDir] ");	
	}
	
	if (is_array($picturelist)) {
		
	$gallerycontent  = '<div class="ngg-galleryoverview">';
	if (($ngg_options['galShowSlide']) AND (NGGALLERY_IREXIST)) {
		$getvalue['show'] = "slide";
		$gallerycontent .= '<a class="slideshowlink" href="' . add_query_arg($getvalue) . '">'.$ngg_options[galTextSlide].'</a>';
	}
	
	foreach ($picturelist as $picture) {
		$picturefile =  nggallery::remove_umlauts($picture->filename);
		$gallerycontent .= '<div class="ngg-gallery-thumbnail-box">'."\n\t";
		$gallerycontent .= '<div class="ngg-gallery-thumbnail">'."\n\t";
		$gallerycontent .= '<a href="'.$folder_url.$picturefile.'" title="'.stripslashes($picture->description).'" '.$thumbcode.' >';
		$gallerycontent .= '<img title="'.$picture->alttext.'" alt="'.$picture->alttext.'" src="'.$thumbnailURL.$thumb_prefix.$picture->filename.'" '.$thumbsize.' />';
		$gallerycontent .= '</a>'."\n".'</div>'."\n".'</div>'."\n";
		}
	$gallerycontent .= '</div>'."\n";
 	$gallerycontent .= ($maxElement > 0) ? $navigation : '<div class="ngg-clear"></div>'."\n";
	}
		
	return $gallerycontent;
}

/**********************************************************/
function nggShowJSGallery($galleryID) {
	// create a gallery with a jQuery plugin
	
	global $wpdb;
	$ngg_options = get_option('ngg_options');

	// Get option
	$maxElement = $ngg_options['galImages'];

	// get gallery values
	$act_gallery = $wpdb->get_row("SELECT * FROM $wpdb->nggallery WHERE gid = '$galleryID' ");

	// set gallery url
	$folder_url 	= get_option ('siteurl')."/".$act_gallery->path."/";
	$thumb_folder   = str_replace('/','',nggallery::get_thumbnail_folder($act_gallery->path, FALSE));

	$picturelist = $wpdb->get_results("SELECT * FROM $wpdb->nggpictures WHERE galleryid = '$galleryID' AND exclude != 1 ORDER BY $ngg_options[galSort] $ngg_options[galSortDir] ");	
	
	if (is_array($picturelist)) {
		
		// create array	
		$i = 0;
		
		$gallerycontent  = '<script type="text/javascript">'."\n";
		$gallerycontent .= 'var nggal'. $galleryID .'=new Array()'."\n";
		foreach ($picturelist as $picture) {
			$picturefile =  nggallery::remove_umlauts($picture->filename);
			$gallerycontent .= 'nggal'. $galleryID .'['.$i++.']=["'.$picture->filename.'", "'.$picture->alttext.'", "'.strip_tags(nggallery::ngg_nl2br($picture->description)).'"]'."\n";	
		}
		$gallerycontent .=	'jQuery(document).ready(function() {'."\n";
		$gallerycontent .=  '  jQuery("#nggal'. $galleryID .'").nggallery({'."\n";
		$gallerycontent .=	'		imgarray    : nggal'. $galleryID . ','."\n";
		$gallerycontent .=	'		name        : "'. $act_gallery->name . '",'."\n";
		$gallerycontent .=	'		galleryurl  : "'. $folder_url  . '",'."\n";
		$gallerycontent .=	'		thumbfolder : "'. $thumb_folder  . '",'."\n";
		if ($ngg_options['thumbEffect'] == "thickbox")
			$gallerycontent .=	'		thickbox    : true,'."\n";	
		$gallerycontent .=	'		maxelement  : '. $maxElement ."\n";
		$gallerycontent .=	'	});'."\n";
		$gallerycontent .=	'});'."\n";
		
		$gallerycontent .= '</script>'."\n";
		$gallerycontent .= '	<div id="nggal'. $galleryID .'">'."\n";
		$gallerycontent .= '	<!-- The content will be dynamically loaded in here -->'."\n";
		$gallerycontent .= '</div>'."\n";
		$gallerycontent .= '<div class="ngg-clear"></div>'."\n";
	}
		
	return $gallerycontent;	
}
/**********************************************************/
function nggShowAlbum($albumID,$mode = "extend") {
	
	global $wpdb;
	
	$albumcontent = "";

	// look for gallery variable 
	if (isset( $_GET['gallery']))  {
		
		if ($albumID != $_GET['album']) return $albumcontent;

		$galleryID = attribute_escape($_GET['gallery']);
		$albumcontent = nggShowGallery($galleryID);

	} else {

		$mode = ltrim($mode,',');
		$sortorder = $wpdb->get_var("SELECT sortorder FROM $wpdb->nggalbum WHERE id = '$albumID' ");
		if (!empty($sortorder)) {
			$gallery_array = unserialize($sortorder);
		} 
	
		$albumcontent = '<div class="ngg-albumoverview">';
		if (is_array($gallery_array)) {
		foreach ($gallery_array as $galleryID) {
			$albumcontent .= nggCreateAlbum($galleryID,$mode,$albumID);	
			}
		}
		$albumcontent .= '</div>'."\n";
		$albumcontent .= '<div class="ngg-clear"></div>'."\n";
	
	}
	
	return $albumcontent;
}

/**********************************************************/
function nggCreateAlbum($galleryID,$mode = "extend",$albumID = 0) {
	// create a gallery overview div
	
	global $wpdb;
	$ngg_options = get_option('ngg_options');
	
	$gallerycontent = $wpdb->get_row("SELECT * FROM $wpdb->nggallery WHERE gid = '$galleryID' ");


	
	if ($gallerycontent) {
 		if ($mode == "compact") {
			if ($gallerycontent->previewpic != 0)
				$insertpic = '<img class="Thumb" width="91" height="68" alt="'.$gallerycontent->title.'" src="'.nggallery::get_thumbnail_url($gallerycontent->previewpic).'"/>';
			else 
				$insertpic = __('Watch gallery', 'nggallery');
 			$counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpictures WHERE galleryid = '$galleryID'");
 			$galleryoutput = '	
				<div class="ngg-album-compact">
					<div class="ngg-album-compactbox">
						<div class="ngg-album-link">
							<a class="Link" href="'.$link.'">'.$insertpic.'</a>
						</div>
					</div>
					<h4><a class="ngg-album-desc" title="'.$gallerycontent->title.'" href="'.$link.'">'.$gallerycontent->title.'</a></h4>
					<p><b>'.$counter.'</b> '.__('Photos', 'nggallery').'</p></div>';
		} else {
			// mode extend
			if ($gallerycontent->previewpic != 0)
				$insertpic = '<img src="'.nggallery::get_thumbnail_url($gallerycontent->previewpic).'" alt="'.$gallerycontent->title.'" title="'.$gallerycontent->title.'"/>';
			else 
				$insertpic = __('Watch gallery', 'nggallery');
			$galleryoutput = '
			<div class="ngg-album">
				<div class="ngg-albumtitle"><a href="'.$link.'">'.$gallerycontent->title.'</a></div>
				<div class="ngg-albumcontent">
					<div class="ngg-thumbnail"><a href="'.$link.'">'.$insertpic.'</a></div>
					<div class="ngg-description"><p>'.html_entity_decode($gallerycontent->description).'</p></div>'."\n".'</div>'."\n".'</div>';

		}
	}
	
	return $galleryoutput;
}

/**********************************************************/
function nggShowImageBrowser($galleryID) {
	
	global $wpdb;
	$ngg_options = get_option('ngg_options');
	
	// get the pictures
	$picarray = $wpdb->get_col("SELECT pid FROM $wpdb->nggpictures WHERE galleryid = '$galleryID' AND exclude != 1 ORDER BY $ngg_options[galSort] $ngg_options[galSortDir]");	
	$total = count($picarray);

	// look for gallery variable 
	if ( isset( $_GET['pid'] )) {
		$act_pid = attribute_escape($_GET['pid']);
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
	$picture = new nggImage($act_pid);
	
	if ($picture) {
		$galleryoutput = '
		<div class="ngg-imagebrowser" >
			<h3>'.$picture->alttext.'</h3>
			<div class="pic">'.$picture->get_href_link().'</div>
			<div class="ngg-imagebrowser-nav">';
		if 	($back_pid) {
			$backlink['pid'] = $back_pid;
			$galleryoutput .='<div class="back"><a href="'.add_query_arg($backlink).'">'.'&#9668; '.__('Back', 'nggallery').'</a></div>';
		}
		if 	($next_pid) {
			$nextlink['pid'] = $next_pid;
			$galleryoutput .='<div class="next"><a href="'.add_query_arg($nextlink).'">'.__('Next', 'nggallery').' &#9658;'.'</a></div>';
		}
		$galleryoutput .='
				<div class="counter">'.__('Picture', 'nggallery').' '.($key+1).' '.__('from', 'nggallery').' '.$total.'</div>
				<div class="ngg-imagebrowser-desc"><p>'.html_entity_decode($picture->description).'</p></div>
			</div>	
		</div>';
	}
	return $galleryoutput;
	
}

/**********************************************************/
function nggSinglePicture($imageID,$width=250,$height=250,$mode="",$float="") {
	/** 
	* create a gallery based on the tags
	* @imageID		db-ID of the image
	* @width 		width of the image
	* @height 		height of the image
	* @mode 		none, watermark, web20
	* @float 		none, left, right
	*/
	global $wpdb;
	
	// remove the comma
	$float = ltrim($float,',');
	$mode = ltrim($mode,',');
	$width = ltrim($width,',');
	$height = ltrim($height,',');

	// get picturedata
	$picture = new nggImage($imageID);
	
	// add float to img
	if (!empty($float)) {
		switch ($float) {
		
		case 'left': $float=' style="float:left;" ';
		break;
		
		case 'right': $float=' style="float:right;" ';
		break;
		
		default: $float='';
		break;
		}
	}

	// add fullsize picture as link
	$content  = '<a href="'.$picture->imagePath.'" title="'.stripslashes($picture->description).'" '.$picture->get_thumbcode("singlepic".$imageID).' >';
	$content .= '<img class="ngg-singlepic" src="'.NGGALLERY_URLPATH.'nggshow.php?pid='.$imageID.'&amp;width='.$width.'&amp;height='.$height.'&amp;mode='.$mode.'" alt="'.$picture->alttext.'" title="'.$picture->alttext.'"'.$float.' />';
	$content .= '</a>';
	
	return $content;
}

/**********************************************************/
function nggShowGalleryTags($taglist) {
	/** 
	* create a gallery based on the tags
	* @taglist		list of tags as csv
	*/
	
	global $wpdb;
	
	// get now the related images
	$picturelist = ngg_Tags::get_images($taglist);

	// go on if not empty
	if (empty($picturelist))
		return;
	
	// get the options
	$ngg_options = get_option('ngg_options');

	// Get maxElements
	$maxElement = $ngg_options['galImages'];

	// Get width / height	
	$thumbwidth  = $ngg_options['thumbwidth'];
	$thumbheight = $ngg_options['thumbheight'];

	// set thumb size 
	$thumbsize = "";
	if ($ngg_options['thumbfix'])  $thumbsize = 'style="width:'.$thumbwidth.'px; height:'.$thumbheight.'px;"';
	if ($ngg_options['thumbcrop']) $thumbsize = 'style="width:'.$thumbwidth.'px; height:'.$thumbwidth.'px;"';
	
	// get the effect code
	$thumbcode = nggallery::get_thumbcode(get_the_title());
	
 	// check for page navigation
 	//TODO:nur ein gallerie sollte blättern
 	if ($maxElement > 0) {	
		if ( isset( $_GET['nggpage'] ) )	$page = (int) $_GET['nggpage'];
		else $page = 1; 
	 	$start = $offset = ( $page - 1 ) * $maxElement;
	 	
	 	$total = count($picturelist);
	 	
		// remove the element if we didn't start at the beginning
		if ($start > 0 ) array_splice($picturelist, 0, $start);
		// return the list of images we need
		array_splice($picturelist, $maxElement);
	
		$navigation = nggallery::create_navigation($page, $total, $maxElement);

	} 	
	
	// *** build the gallery output
	// Could be in a seperate function to avoid double work

	$content  = '<div class="ngg-galleryoverview ngg-tags">';
	
	foreach ($picturelist as $picture) {
		// set gallery url
		$folder_url 	= get_option ('siteurl')."/".$picture->path."/";
		$thumbnailURL 	= get_option ('siteurl')."/".$picture->path.nggallery::get_thumbnail_folder($picture->path, FALSE);
		$thumb_prefix   = nggallery::get_thumbnail_prefix($picture->path, FALSE);

		$picturefile =  nggallery::remove_umlauts($picture->filename);
		$content .= '<div class="ngg-gallery-thumbnail-box ngg-tags">'."\n\t";
		$content .= '<div class="ngg-gallery-thumbnail ngg-tags">'."\n\t";
		$content .= '<a href="'.$folder_url.$picturefile.'" title="'.stripslashes($picture->description).'" '.$thumbcode.' >';
		$content .= '<img title="'.$picture->alttext.'" alt="'.$picture->alttext.'" src="'.$thumbnailURL.$thumb_prefix.$picture->filename.'" '.$thumbsize.' />';
		$content .= '</a>'."\n".'</div>'."\n".'</div>'."\n";
	}

	$content .= '</div>'."\n";
	$content .= ($maxElement > 0) ? $navigation : '<div class="ngg-clear"></div>'."\n";

	return $content;
}

/**********************************************************/
function nggShowRelatedGallery($taglist, $maxImages = 0) {
	/** 
	* create a gallery based on the tags
	* @taglist		list of tags as csv
	* @maxImages	limit the number of images to show
	*/
	
	global $wpdb;
	
	// get now the related images
	$picturelist = ngg_Tags::get_images($taglist);
	
	// go on if not empty
	if (empty($picturelist))
		return;
		
	// get the options
	$ngg_options = get_option('ngg_options');

	// get the effect code
	$thumbcode = nggallery::get_thumbcode("Related images for ".get_the_title());

	// cut the list to maxImages
	if ($maxImages > 0 ) array_splice($picturelist, $maxImages);
	
 	// *** build the gallery output
	$content   = '<div class="ngg-related-gallery">';
	
	foreach ($picturelist as $picture) {
		// set gallery url
		$folder_url 	= get_option ('siteurl')."/".$picture->path."/";
		$thumbnailURL 	= get_option ('siteurl')."/".$picture->path.nggallery::get_thumbnail_folder($picture->path, FALSE);
		$thumb_prefix   = nggallery::get_thumbnail_prefix($picture->path, FALSE);

		$picturefile =  nggallery::remove_umlauts($picture->filename);
		$content .= '<a href="'.$folder_url.$picturefile.'" title="'.stripslashes($picture->description).'" '.$thumbcode.' >';
		$content .= '<img title="'.$picture->alttext.'" alt="'.$picture->alttext.'" src="'.$thumbnailURL.$thumb_prefix.$picture->filename.'" '.$thumbsize.' />';
		$content .= '</a>'."\n";
	}

	$content .= '</div>'."\n";

	return $content;
}

/**********************************************************/
function nggShowAlbumTags($taglist) {
	/** 
	* create a gallery based on the tags
	* @taglist		list of tags as csv
	*/

	//TODO: Albumtags could not used for post two time
	//TODO: Show Tag as post title ?
	
	global $wpdb;
	
	// look for gallerytag variable 
	if (isset( $_GET['gallerytag']))  {

		$galleryTag = attribute_escape($_GET['gallerytag']);
		$tagname  = $wpdb->get_var("SELECT name FROM $wpdb->nggtags WHERE slug = '$galleryTag' ");		
		$content  = '<div id="albumnav"><span><a href="'.get_permalink().'" title="'.__('Overview', 'nggallery').'">'.__('Overview', 'nggallery').'</a> | '.$tagname.'</span></div>';
		$content .=  nggShowGalleryTags($galleryTag);

	} else {
	
		// get now the related images
		$picturelist = ngg_Tags::get_album_images($taglist);
	
		// go on if not empty
		if (empty($picturelist))
			return;
	
		$content = '<div class="ngg-albumoverview">';
		foreach ($picturelist as $picture) {
			$gallerytag['gallerytag'] = $picture["slug"];
			$link = add_query_arg($gallerytag);
			
			$insertpic = '<img class="Thumb" width="91" height="68" alt="'.$picture["name"].'" src="'.nggallery::get_thumbnail_url($picture["pid"]).'"/>';
			$tagid = $picture['tagid'];
			$counter  = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpic2tags WHERE tagid = '$tagid' ");
			$content .= '	
				<div class="ngg-album-compact">
					<div class="ngg-album-compactbox">
						<div class="ngg-album-link">
							<a class="Link" href="'.$link.'">'.$insertpic.'</a>
						</div>
					</div>
					<h4><a class="ngg-album-desc" title="'.$picture["name"].'" href="'.$link.'">'.$picture["name"].'</a></h4>
					<p><b>'.$counter.'</b> '.__('Photos', 'nggallery').'</p></div>';
		}
		$content .= '</div>'."\n";
		$content .= '<div class="ngg-clear"></div>'."\n";
		
	}
	return $content;
}

/**********************************************************/
function nggShowRelatedImages($type = '', $maxImages = 0) {
	// return related images based on category or tags
		
		if ($type == '') {
			$ngg_options = get_option('ngg_options');
			$type = $ngg_options['appendType'];
			$maxImages = $ngg_options['maxImages'];
		}
	
		$sluglist = array();
		switch ($type) {
			
		case "tags":
			if (function_exists('get_the_tags')) { 
				$taglist = get_the_tags();
				
				if (is_array($taglist)) 
				foreach ($taglist as $tag)
					$sluglist[] = $tag->slug;
			}
			break;
		case "category":
			$catlist = get_the_category();
			
			if (is_array($catlist)) 
			foreach ($catlist as $cat)
				$sluglist[] = $cat->category_nicename;
		}
		
		$sluglist = implode(",", $sluglist);
		$content = nggShowRelatedGallery($sluglist, $maxImages);
		
		return $content;
}

/**********************************************************/
function the_related_images($type = 'tags', $maxNumbers = 7) {
	echo nggShowRelatedImages($type, $maxNumbers);
}

?>
