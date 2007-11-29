<?php

/**
 * Image METADATA PHP class for the WordPress plugin NextGEN Gallery
 * nggmeta.lib.php
 * 
 * @author 		Alex Rabe 
 * @copyright 	Copyright 2007
 * 
 */
	  
class nggMeta{

	/**** Image Data ****/
    var $imagePath		=	"";		// ABS Path to the image
	var $exif_data 		= 	false;	// EXIF data array
	var $iptc_data 		= 	false;	// IPTC data array
	var $xmp_data  		= 	false;	// XMP data array

  /**
   * nggMeta::nggMeta()
   *
   * @param mixed $image
   * @return
   */
   
 	function nggMeta($image) {
 		$this->imagePath = $image;
 		
 		if ( !file_exists( $this->imagePath ) )
			return false;

 		$size = getimagesize ( $this->imagePath, $metadata );
 		
		if ($size && is_array($metadata)) {

			// get exif - data
			if ( is_callable('exif_read_data'))
			$this->exif_data = @exif_read_data($this->imagePath, 0, true );
 			
 			// get the iptc data - should be in APP13
 			if ( is_callable('iptcparse'))
			$this->iptc_data = @iptcparse($metadata["APP13"]);

			// get the xmp data in a XML format
			if ( is_callable('xml_parser_create'))
			$this->xmp_data = $this->extract_XMP($this->imagePath);
			
			return true;
		}
 		
 		return false;
 	}
	
  /**
   * nggMeta::get_EXIF()
   * See also http://trac.wordpress.org/changeset/6313
   *
   * @return structured EXIF data
   */
	function get_EXIF() {
		
		if (!$this->exif_data)
			return false;
			
		$meta= array();
		
		// taken from WP core
		$exif = $this->exif_data['EXIF'];
		if (!empty($exif['FNumber']))
			$meta['aperture'] = round( $this->exif_frac2dec( $exif['FNumber'] ), 2 );
		if (!empty($exif['Model']))
			$meta['camera'] = trim( $exif['Model'] );
		if (!empty($exif['DateTimeDigitized']))
			$meta['created_timestamp'] = date_i18n(get_option('date_format').' '.get_option('time_format'), $this->exif_date2ts($exif['DateTimeDigitized']));
		if (!empty($exif['FocalLength']))
			$meta['focal_length'] = $this->exif_frac2dec( $exif['FocalLength'] );
		if (!empty($exif['ISOSpeedRatings']))
			$meta['iso'] = $exif['ISOSpeedRatings'];
		if (!empty($exif['ExposureTime']))
			$meta['shutter_speed'] = $this->exif_frac2dec( $exif['ExposureTime'] );

		// additional information
		$exif = $this->exif_data['IFD0'];
		if (!empty($exif['Model']))
			$meta['camera'] = $exif['Model'];
		if (!empty($exif['Make']))
			$meta['make'] = $exif['Make'];

		// this is done by Windows
		$exif = $this->exif_data['WINXP'];
		if (!empty($exif['Title']))
			$meta['title'] = $exif['Title'];
		if (!empty($exif['Author']))
			$meta['author'] = $exif['Author'];
		if (!empty($exif['Keywords']))
			$meta['tags'] = $exif['Keywords'];
		if (!empty($exif['Subject']))
			$meta['subject'] = $exif['Subject'];
			
		return $meta;
	
	}
	
	// convert a fraction string to a decimal
	function exif_frac2dec($str) {
		@list( $n, $d ) = explode( '/', $str );
		if ( !empty($d) )
			return $n / $d;
		return $str;
	}
	
	// convert the exif date format to a unix timestamp
	function exif_date2ts($str) {
		// seriously, who formats a date like 'YYYY:MM:DD hh:mm:ss'?
		@list( $date, $time ) = explode( ' ', trim($str) );
		@list( $y, $m, $d ) = explode( ':', $date );
	
		return strtotime( "{$y}-{$m}-{$d} {$time}" );
	}

  /**
   * nggMeta::i8n_name()
   *
   * @param mixed $key
   * @return translated $key
   */
	function i8n_name($key) {
		
		$tagnames = array(
		'aperture' 			=> __('Aperture','nggallery'),
		'credit' 			=> __('Credit','nggallery'),
		'camera' 			=> __('Camera','nggallery'),
		'caption' 			=> __('Caption','nggallery'),
		'created_timestamp' => __('Date/Time','nggallery'),
		'copyright' 		=> __('Copyright','nggallery'),
		'focal_length' 		=> __('Focal length','nggallery'),
		'iso' 				=> __('ISO','nggallery'),
		'shutter_speed' 	=> __('Shutter speed','nggallery'),
		'title' 			=> __('Titel','nggallery'),
		'author' 			=> __('Author','nggallery'),
		'tags' 				=> __('Tags','nggallery'),
		'subject' 			=> __('Subject','nggallery'),
		'make' 				=> __('Make','nggallery')
		);
		
		if ($tagnames[$key]) $key = $tagnames[$key];
		
		return($key);

	}	
	
  /**
   * nggMeta::readIPTC() - IPTC Data Information for EXIF Display
   *
   * @param mixed $output_tag
   * @return
   */
	function get_IPTC() {
	
	// --------- Set up Array Functions --------- //
		$iptcTags = array (
			"2#005" => __('Title','nggallery'),
			"2#007" => __('Edit Status','nggallery'),
			"2#008" => __('Editorial Update','nggallery'),
			"2#010" => __('Urgency','nggallery'),
			"2#012" => __('Subject Reference','nggallery'),
			"2#015" => __('Category','nggallery'),
			"2#020" => __('Supplemental Category','nggallery'),
			"2#022" => __('Fixture Identifier','nggallery'),
			"2#025" => __('Keywords','nggallery'),
			"2#026" => __('Content Location Code','nggallery'),
			"2#027" => __('Content Location Name','nggallery'),
			"2#030" => __('Release Date','nggallery'),
			"2#035" => __('Release Time','nggallery'),
			"2#037" => __('Expiration Date','nggallery'),
			"2#035" => __('Expiration Time','nggallery'),
			"2#040" => __('Special Instructions','nggallery'),
			"2#042" => __('Action Advised','nggallery'),
			"2#045" => __('Reference Service','nggallery'),
			"2#047" => __('Reference Date','nggallery'),
			"2#050" => __('Reference Number','nggallery'),
			"2#055" => __('Date Created','nggallery'),
			"2#060" => __('Time Created','nggallery'),
			"2#062" => __('Digital Creation Date','nggallery'),
			"2#063" => __('Digital Creation Time','nggallery'),
			"2#065" => __('Originating Program','nggallery'),
			"2#070" => __('Program Version','nggallery'),
			"2#075" => __('Object Cycle','nggallery'),
			"2#080" => __('By-Line (Author)','nggallery'),
			"2#085" => __('By-Line Title (Author Position)','nggallery'),
			"2#090" => __('City','nggallery'),
			"2#092" => __('Sub-Location','nggallery'),
			"2#095" => __('Province/State','nggallery'),
			"2#100" => __('Country/Primary Location Code','nggallery'),
			"2#101" => __('Country/Primary Location Name','nggallery'),
			"2#103" => __('Original Transmission Reference','nggallery'),
			"2#105" => __('Headline','nggallery'),
			"2#110" => __('Credit','nggallery'),
			"2#115" => __('Source','nggallery'),
			"2#116" => __('Copyright Notice','nggallery'),
			"2#118" => __('Contact','nggallery'),
			"2#120" => __('Caption/Abstract','nggallery'),
			"2#122" => __('Caption Writer/Editor','nggallery')
		);
		
		// var_dump($this->iptc_data);
		if($this->iptc_data) {
			$IPTCarray = array();
			foreach ($iptcTags as $key => $value) {
				if ($this->iptc_data[$key])
					$IPTCarray[$value] = trim(utf8_encode(implode(", ", $this->iptc_data[$key])));

			}
			return $IPTCarray;
		}

		return false;
	}

  /**
   * nggMeta::extract_XMP()
   * get XMP DATA  
   * code by Pekka Saarinen http://photography-on-the.net	
   *
   * @param mixed $filename
   * @return
   */
	function extract_XMP( $filename ) {

		//TODO:Require a lot of memory, could be better
		ob_start();
		@readfile($filename);
    	$source = ob_get_contents();
    	ob_end_clean();

		$start = strpos( $source, "<x:xmpmeta"   );
		$end   = strpos( $source, "</x:xmpmeta>" );
		if ((!$start === false) && (!$end === false)) {
			$lenght = $end - $start;
			$xmp_data = substr($source, $start, $lenght+12 );
			unset($source);
			return $xmp_data;
		} 
		
		unset($source);
		return false;
	}

	/**
	 * nggMeta::get_XMP()
	 *
	 * @package Taken from http://php.net/manual/en/function.xml-parse-into-struct.php
	 * @author Alf Marius Foss Olsen & Alex Rabe
	 * 
	 */
	function get_XMP() {
   
		if(!$this->xmp_data)
			return false; 
			
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); // Dont mess with my cAsE sEtTings
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); // Dont bother with empty info
		xml_parse_into_struct($parser, $this->xmp_data, $values);
		xml_parser_free($parser);
		  
		$xmlarray = array(); 		// The XML array
		$XMParray = array(); 		// The returned array
		$stack = array(); 			// tmp array used for stacking
	 	$list_element = false;		// rdf:li indicator
	 	$list_array   = array();	// tmp array for list elements
	 	  
		foreach($values as $val) {
			
		  	if($val['type'] == "open") {
		      	array_push($stack, $val['tag']);
		      	
		    } elseif($val['type'] == "close") {
		    	// reset the compared stack
		    	if ($list_element == false)
		      		array_pop($stack);
		      	// reset the rdf:li indicator & array
		      	$list_element = false;
		      	$list_array   = array();
		      	
		    } elseif($val['type'] == "complete") {
				if ($val['tag'] == "rdf:li") {
					// first go one element back
					if ($list_element == false)
						array_pop($stack);
					$list_element = true;
					// save it in our temp array
					$list_array[] = $val['value']; 
					// in the case it's a list element we seralize it
					$value = implode(",", $list_array);
					$this->setArrayValue($xmlarray, $stack, $value);
		      	} else {
		      		array_push($stack, $val['tag']);
		      		$this->setArrayValue($xmlarray, $stack, $val['value']);
		      		array_pop($stack);
		      	}
		    }
		    
		} // foreach
		
		// cut off the useless tags
		$xmlarray = $xmlarray['x:xmpmeta']['rdf:RDF']['rdf:Description'];
		  
		// --------- Some values from the XMP format--------- //
		$xmpTags = array (
			'xap:CreateDate' 			=> __('Date / Time','nggallery'),
			'xap:ModifyDate'  			=> __('Last modified','nggallery'),
			'xap:CreatorTool' 			=> __('Tool','nggallery'),
			'dc:format' 				=> __('Format','nggallery'),
			'dc:title'					=> __('Title','nggallery'),
			'dc:creator' 				=> __('Author','nggallery'),
			'dc:subject' 				=> __('Keywords','nggallery'),
			'photoshop:AuthorsPosition' => __('Position','nggallery'),
			'photoshop:City'			=> __('City','nggallery'),
			'photoshop:Country' 		=> __('Country','nggallery')
		);
		
		foreach ($xmpTags as $key => $value) {
			// the kex exist
			if ($xmlarray[$key]) {
				switch ($key) {
					case 'xap:CreateDate':
					case 'xap:ModifyDate':
						$XMParray[$value] = date_i18n(get_option('date_format').' '.get_option('time_format'), strtotime($xmlarray[$key]));
						break;				
					default :
						$XMParray[$value] = $xmlarray[$key];
				}
			}
		}
		  
		return $XMParray;
	}
	  
	function setArrayValue(&$array, $stack, $value) {
		if ($stack) {
			$key = array_shift($stack);
	    	$this->setArrayValue($array[$key], $stack, $value);
	    	return $array;
	  	} else {
	    	$array = $value;
	  	}
	}
	
}

?>