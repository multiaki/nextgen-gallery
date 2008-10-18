<?php
/**
 * @author Alex Rabe, Vincent Prat 
 * @copyright 2008
 * @description Use WordPress Shortcode API for more features
 * @Docs http://codex.wordpress.org/Shortcode_API
 */

class NextGEN_shortcodes {
	
	// register the new shortcodes
	function NextGEN_shortcodes() {
	
		// convert the old shortcode
		add_filter('the_content', array(&$this, 'convert_shortcode'));
		
		add_shortcode( 'singlepic', array(&$this, 'show_singlepic' ) );
		add_shortcode( 'album', array(&$this, 'show_album' ) );
		add_shortcode( 'nggallery', array(&$this, 'show_gallery') );
		add_shortcode( 'imagebrowser', array(&$this, 'show_imagebrowser' ) );
		add_shortcode( 'slideshow', array(&$this, 'show_slideshow' ) );
		add_shortcode( 'nggtags', array(&$this, 'show_tags' ) );
		
		// Add shortcodes for thumbnail and images
		require_once(dirname (__FILE__) . '/ngg-shortcode-thumb.php');
		require_once(dirname (__FILE__) . '/ngg-shortcode-picture.php');
		add_shortcode( 'thumb', 'ngg_do_thumb_shortcode');
		add_shortcode( 'picture', 'ngg_do_picture_shortcode');
				
	}

	 /**
	   * NextGEN_shortcodes::convert_shortcode()
	   * convert old shortcodes to the new WordPress core style
	   * [gallery=1]  ->> [nggallery id=1]
	   * 
	   * @param string $content Content to search for shortcodes
	   * @return string Content with new shortcodes.
	   */
	function convert_shortcode($content) {
		
		$ngg_options = nggGallery::get_option('ngg_options');
	
		if ( stristr( $content, '[singlepic' )) {
			$search = "@\[singlepic=(\d+)(|,\d+|,)(|,\d+|,)(|,watermark|,web20|,)(|,right|,center|,left|,)\]@i";
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {

				foreach ($matches as $match) {
					// remove the comma
					$match[2] = ltrim($match[2],',');
					$match[3] = ltrim($match[3],',');	
					$match[4] = ltrim($match[4],',');	
					$match[5] = ltrim($match[5],',');						
					$replace = "[singlepic id=\"{$match[1]}\" w=\"{$match[2]}\" h=\"{$match[3]}\" mode=\"{$match[4]}\" float=\"{$match[5]}\" ]";
					$content = str_replace ($match[0], $replace, $content);
				}
			}
		}

		if ( stristr( $content, '[album' )) {
			$search = "@(?:<p>)*\s*\[album\s*=\s*(\w+|^\+)(|,extend|,compact)\]\s*(?:</p>)*@i"; 
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {

				foreach ($matches as $match) {
					// remove the comma
					$match[2] = ltrim($match[2],',');
					$replace = "[album id=\"{$match[1]}\" mode=\"{$match[2]}\"]";
					$content = str_replace ($match[0], $replace, $content);
				}
			}
		}		

		if ( stristr( $content, '[gallery' )) {
			$search = "@(?:<p>)*\s*\[gallery\s*=\s*(\w+|^\+)\]\s*(?:</p>)*@i";
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {

				foreach ($matches as $match) {
					$replace = "[nggallery id=\"{$match[1]}\"]";
					$content = str_replace ($match[0], $replace, $content);
				}
			}
		}	

		if ( stristr( $content, '[imagebrowser' )) {
			$search = "@(?:<p>)*\s*\[imagebrowser\s*=\s*(\w+|^\+)\]\s*(?:</p>)*@i";
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {

				foreach ($matches as $match) {
					$replace = "[imagebrowser id=\"{$match[1]}\"]";
					$content = str_replace ($match[0], $replace, $content);
				}
			}
		}

		if ( stristr( $content, '[slideshow' )) {
			$search = "@(?:<p>)*\s*\[slideshow\s*=\s*(\w+|^\+)(|,(\d+)|,)(|,(\d+))\]\s*(?:</p>)*@i";
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {

				foreach ($matches as $match) {
					// remove the comma
					$match[2] = ltrim($match[2],',');
					$match[3] = ltrim($match[3],',');	
					$replace = "[slideshow id=\"{$match[1]}\" w=\"{$match[2]}\" h=\"{$match[3]}\"]";
					$content = str_replace ($match[0], $replace, $content);
				}
			}
		}

		if ( stristr( $content, '[tags' )) {
			$search = "@(?:<p>)*\s*\[tags\s*=\s*(.*?)\s*\]\s*(?:</p>)*@i";
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {

				foreach ($matches as $match) {
					$replace = "[nggtags gallery=\"{$match[1]}\"]";
					$content = str_replace ($match[0], $replace, $content);
				}
			}
		}
		
		if ( stristr( $content, '[albumtags' )) {
			$search = "@(?:<p>)*\s*\[albumtags\s*=\s*(.*?)\s*\]\s*(?:</p>)*@i";
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {

				foreach ($matches as $match) {
					$replace = "[nggtags album=\"{$match[1]}\"]";
					$content = str_replace ($match[0], $replace, $content);
				}
			}
		}

		// attach related images based on category or tags
		if ($ngg_options['activateTags']) 
			$content .= nggShowRelatedImages();
	
		return $content;
	}
	
	function show_singlepic( $atts ) {
	
		extract(shortcode_atts(array(
			'id' 		=> 0,
			'w'		 	=> '',
			'h'		 	=> '',
			'mode'	 	=> '',
			'float'	 	=> ''
		), $atts ));
		
		$out = nggSinglePicture($id, $w, $h, $mode, $float);
			
		return $out;
	}

	function show_album( $atts ) {
	
		extract(shortcode_atts(array(
			'id' 		=> 0,
			'mode'		=> 'extend'	
		), $atts ));
		
		$out = nggShowAlbum($id, $mode, $albumSortOrder);
			
		return $out;
	}

	function show_gallery( $atts ) {
	
		extract(shortcode_atts(array(
			'id' 		=> 0,
			'mode'		=> ''	
		), $atts ));
		
		$out = nggShowGallery( $id, $mode);
			
		return $out;
	}

	function show_imagebrowser( $atts ) {
		
		global $wpdb;
	
		extract(shortcode_atts(array(
			'id' 		=> 0,
			'mode'		=> ''	
		), $atts ));

		$out = nggShowImageBrowser($id, $mode);
			
		return $out;
	}
	
	function show_slideshow( $atts ) {
		
		global $wpdb;
	
		extract(shortcode_atts(array(
			'id' 		=> 0,
			'w'		 	=> '',
			'h'		 	=> ''
		), $atts ));
		
		$galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE gid = '$id' ");
		if(!$galleryID) $galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE name = '$id' ");

		if( $galleryID )
			$out = nggShowSlideshow($galleryID, $w, $h);
		else 
			$out = __('[Gallery not found]','nggallery');
			
		return $out;
	}
	
	function show_tags( $atts ) {
	
		extract(shortcode_atts(array(
			'gallery' 		=> '',
			'album' 		=> ''
		), $atts ));
		
		if ( !empty($album) )
			$out = nggShowAlbumTags($album);
		else
			$out = nggShowGalleryTags($gallery);
		
		return $out;
	}

}

// let's use it
$nggShortcodes = new NextGEN_Shortcodes;	

?>