<?php
/**
* imagemagick.inc.php
*
* @author 		Frederic De Ranter
* @copyright	Copyright 2008
* @version 		0.1 (PHP4)
* @based 		on thumbnail.inc.php by Ian Selby (gen-x-design.com)
* @since		NextGEN V1.0.0
*
*/
/**
* PHP class for dynamically resizing, cropping, and rotating images for thumbnail purposes and either displaying them on-the-fly or saving them.
* with ImageMagick
*/

class ngg_Thumbnail {
/**
* Error message to display, if any
* @var string
*/
var $errmsg;
/**
* Whether or not there is an error
* @var boolean
*/
var $error;
/**
* Format of the image file
* @var string
*/
var $format;
/**
* File name and path of the image file
* @var string
*/
var $fileName;
/**
* Image meta data if any is available (jpeg/tiff) via the exif library
* @var array
*/
var $imageMeta;
/**
* Current dimensions of working image
* @var array
*/
var $currentDimensions;
/**
* New dimensions of working image
* @var array
*/
var $newDimensions;
/**
* Image resource for newly manipulated image
* @var resource
* @access private
*/
var $newImage;
/**
* Image resource for image before previous manipulation
* @var resource
* @access private
*/
var $oldImage;
/**
* Image resource for image being currently manipulated
* @var resource
* @access private
*/
var $workingImage;
/**
* Percentage to resize image b
* @var int
* @access private
*/
var $percent;
/**
* Maximum width of image during resize
* @var int
* @access private
*/
var $maxWidth;
/**
* Maximum height of image during resize
* @var int
* @access private
*/
var $maxHeight;
/**
* Image for Watermark
* @var string
*/
var $watermarkImgPath;
/**
* Text for Watermark
* @var string
*/
var $watermarkText;
/**
* String to execute ImageMagick convert.
* @var string
*/
var $imageMagickExec;
/**
* String to execute ImageMagick composite.
* @var string
*/
var $imageMagickComp;


  /*
   * in: filename, error
   * out: nothing 
   * init of class: init of variables, detect needed memory (gd), image format (gd), detect image size (GetImageSize is general PHP, not GD), Image Meta?
   */

	function ngg_Thumbnail($fileName,$no_ErrorImage = false) {
		//make sure ImageMagick is installed
		exec("convert -version", $magickv);
      $helper = preg_match('/Version: ImageMagick ([0-9])/', $magickv[0], $magickversion);
      if (!$magickversion[0]>"5") {
      	echo 'You do not have ImageMagick installed.' . "\n";
      	exit;
      }

    	//initialize variables
      $this->errmsg               = '';
      $this->error                = false;
      $this->currentDimensions    = array();
      $this->newDimensions        = array();
      $this->fileName             = $fileName;
      $this->imageMeta			    = array();
      $this->percent              = 100;
      $this->maxWidth             = 0;
      $this->maxHeight            = 0;
      $this->watermarkImgPath		 = '';
      $this->watermarkText		    = '';
      $this->imageMagickExec		 = '';
      $this->imageMagickComp		 = '';
      
        //check to see if file exists
      if(!file_exists($this->fileName)) {
      	$this->errmsg = 'File not found';
         $this->error = true;
      }
      //check to see if file is readable
      elseif(!is_readable($this->fileName)) {
         $this->errmsg = 'File is not readable';
         $this->error = true;
      }

		if($this->error == false) { 
	    $size = GetImageSize($this->fileName);
      $this->currentDimensions = array('width'=>$size[0],'height'=>$size[1]);
	  }
    if($this->error == true) {
      //if(!$no_ErrorImage)
      	//$this->showErrorImage();
      return;
    }
	}

    /**
     * Must be called to free up allocated memory after all manipulations are done
     */

    function destruct() {
     //not needed
		return;
    }
    
    /**
     * Returns the current width of the image
     * @return int
     */
    function getCurrentWidth() {
        return $this->currentDimensions['width'];
    }

    /**
     * Returns the current height of the image
     * @return int
     */
    function getCurrentHeight() {
        return $this->currentDimensions['height'];
    }

    /**
     * Calculates new image width
     * @param int $width
     * @param int $height
     * @return array
     */
    function calcWidth($width,$height) {
        $newWp = (100 * $this->maxWidth) / $width;
        $newHeight = ($height * $newWp) / 100;
        return array('newWidth'=>intval($this->maxWidth),'newHeight'=>intval($newHeight));
    }

    /**
     * Calculates new image height
     * @param int $width
     * @param int $height
     * @return array
     */
    function calcHeight($width,$height) {
        $newHp = (100 * $this->maxHeight) / $height;
        $newWidth = ($width * $newHp) / 100;
        return array('newWidth'=>intval($newWidth),'newHeight'=>intval($this->maxHeight));
    }

    /**
     * Calculates new image size based on percentage
     * @param int $width
     * @param int $height
     * @return array
     */
    function calcPercent($width,$height) {
        $newWidth = ($width * $this->percent) / 100;
        $newHeight = ($height * $this->percent) / 100;
        return array('newWidth'=>intval($newWidth),'newHeight'=>intval($newHeight));
    }

    /**
     * Calculates new image size based on width and height, while constraining to maxWidth and maxHeight
     * @param int $width
     * @param int $height
     */
    function calcImageSize($width,$height) {
        $newSize = array('newWidth'=>$width,'newHeight'=>$height);

        if($this->maxWidth > 0) {

            $newSize = $this->calcWidth($width,$height);

            if($this->maxHeight > 0 && $newSize['newHeight'] > $this->maxHeight) {
                $newSize = $this->calcHeight($newSize['newWidth'],$newSize['newHeight']);
            }

            //$this->newDimensions = $newSize;
        }

        if($this->maxHeight > 0) {
            $newSize = $this->calcHeight($width,$height);

            if($this->maxWidth > 0 && $newSize['newWidth'] > $this->maxWidth) {
                $newSize = $this->calcWidth($newSize['newWidth'],$newSize['newHeight']);
            }

            //$this->newDimensions = $newSize;
        }

        $this->newDimensions = $newSize;
    }

    /**
     * Calculates new image size based percentage
     * @param int $width
     * @param int $height
     */
    function calcImageSizePercent($width,$height) {
        if($this->percent > 0) {
            $this->newDimensions = $this->calcPercent($width,$height);
        }
    }




/* here start the rewritten functions for ImageMagick
*******************************************************************************
* the functions create a string to be executed when the save command is executed
* 'show' function: temp image that can be displayed?
* 
*******************************************************************************
*/



    /**
     * Resizes image to maxWidth x maxHeight
     *
     * @param int $maxWidth
     * @param int $maxHeight
     */
	  
	function resize($maxWidth = 0, $maxHeight = 0, $resampleMode = 3) {
		$this->maxWidth = $maxWidth;
    $this->maxHeight = $maxHeight;

    $this->calcImageSize($this->currentDimensions['width'],$this->currentDimensions['height']);

		//string to resize the picture to $this->newDimensions['newWidth'],$this->newDimensions['newHeight']
		//should result in: -thumbnail $this->newDimensions['newWidth']x$this->newDimensions['newHeight']
		$this->imageMagickExec .= " -resize ".$maxWidth."x".$maxHeight;
			
		// next calculations should be done with the 'new' dimensions
		$this->currentDimensions['width'] = $this->newDimensions['newWidth'];
		$this->currentDimensions['height'] = $this->newDimensions['newHeight'];
		
	}

   /**
	 * Crops the image from calculated center in a square of $cropSize pixels
	 *
	 * @param int $cropSize
	 */
	function cropFromCenter($cropSize, $resampleMode = 3) {
	   if($cropSize > $this->currentDimensions['width']) $cropSize = $this->currentDimensions['width'];
	   if($cropSize > $this->currentDimensions['height']) $cropSize = $this->currentDimensions['height'];

	   //$cropX = intval(($this->currentDimensions['width'] - $cropSize) / 2);
	   //$cropY = intval(($this->currentDimensions['height'] - $cropSize) / 2);

		//string to crop the picture to $cropSize,$cropSize (from center)
		//result: -gravity Center -crop $cropSizex$cropSize+0+0
		$this->imageMagickExec .= ' -gravity Center -crop ' . $cropSize . 'x' . $cropSize . '+0+0';
		
		// next calculations should be done with the 'new' dimensions
		$this->currentDimensions['width'] = $cropSize;
		$this->currentDimensions['height'] = $cropSize;		
	}

	/**
	 * Advanced cropping function that crops an image using $startX and $startY as the upper-left hand corner.
	 *
	 * @param int $startX
	 * @param int $startY
	 * @param int $width
	 * @param int $height
	 */
	function crop($startX,$startY,$width,$height) {
	    //make sure the cropped area is not greater than the size of the image
	   if($width > $this->currentDimensions['width']) $width = $this->currentDimensions['width'];
	   if($height > $this->currentDimensions['height']) $height = $this->currentDimensions['height'];
	    //make sure not starting outside the image
	   if(($startX + $width) > $this->currentDimensions['width']) $startX = ($this->currentDimensions['width'] - $width);
	   if(($startY + $height) > $this->currentDimensions['height']) $startY = ($this->currentDimensions['height'] - $height);
	   if($startX < 0) $startX = 0;
	   if($startY < 0) $startY = 0;

		//string to crop the picture to $width,$height (from $startX,$startY)
		//result: -crop $widthx$height+$startX+$startY
		$this->imageMagickExec .= ' -crop ' . $width . 'x' . $height . '+' . $startX .'+' . $startY;

		$this->currentDimensions['width'] = $width;
		$this->currentDimensions['height'] = $height;
	}

	/**
	 * Creates Apple-style reflection under image, optionally adding a border to main image
	 *
	 * @param int $percent
	 * @param int $reflection
	 * @param int $white
	 * @param bool $border
	 * @param string $borderColor
	 */
	function createReflection($percent,$reflection,$white,$border = true,$borderColor = '#a4a4a4') {
        $width = $this->currentDimensions['width'];
        $height = $this->currentDimensions['height'];

        $reflectionHeight = intval($height * ($reflection / 100));
        $newHeight = $height + $reflectionHeight;
        $reflectedPart = $height * ($percent / 100);
/*
Creates an Apple™-style reflection (it’s more of a web 2.0 thing now, I know…) 
from an image. This one’s a bit weird to explain, but here goes:

$percent - What percentage of the image to create the reflection from 
$reflection - What percentage of the image height should the reflection height be. 
i.e. If your image is 100 pixels high, and you set reflection to 40, the reflection would be 40 pixels high. 

$white - How transparent (using white as the background) the reflection should be, as a percent 
$border - Whether a border should be drawn around the original image (default is true) 
$borderColor - The hex value of the color you would like your border to be (default is #a4a4a4)*/

			$this->imageMagickExec .= '';
			
        /*$this->workingImage = ImageCreateTrueColor($width,$newHeight);

        ImageAlphaBlending($this->workingImage,true);

        $colorToPaint = ImageColorAllocateAlpha($this->workingImage,255,255,255,0);
        ImageFilledRectangle($this->workingImage,0,0,$width,$newHeight,$colorToPaint);

        imagecopyresampled(
                            $this->workingImage,
                            $this->newImage,
                            0,
                            0,
                            0,
                            $reflectedPart,
                            $width,
                            $reflectionHeight,
                            $width,
                            ($height - $reflectedPart));
        $this->imageFlipVertical();

        imagecopy($this->workingImage,$this->newImage,0,0,0,0,$width,$height);

        imagealphablending($this->workingImage,true);

        for($i=0;$i<$reflectionHeight;$i++) {
            $colorToPaint = imagecolorallocatealpha($this->workingImage,255,255,255,($i/$reflectionHeight*-1+1)*$white);
            imagefilledrectangle($this->workingImage,0,$height+$i,$width,$height+$i,$colorToPaint);
        }

        if($border == true) {
            $rgb = $this->hex2rgb($borderColor,false);
            $colorToPaint = imagecolorallocate($this->workingImage,$rgb[0],$rgb[1],$rgb[2]);
            imageline($this->workingImage,0,0,$width,0,$colorToPaint); //top line
            imageline($this->workingImage,0,$height,$width,$height,$colorToPaint); //bottom line
            imageline($this->workingImage,0,0,0,$height,$colorToPaint); //left line
            imageline($this->workingImage,$width-1,0,$width-1,$height,$colorToPaint); //right line
        }

      $this->oldImage = $this->workingImage;
		$this->newImage = $this->workingImage;*/
		$this->currentDimensions['width'] = $width;
		$this->currentDimensions['height'] = $newHeight;
	}
	
	/**
     * Based on the Watermark function by Marek Malcherek  
     * http://www.malcherek.de
     *
 	 * @param string $color
	 * @param string $wmFont
	 * @param int $wmSize
 	 * @param int $wmOpaque
     */
	function watermarkCreateText($color = '000000',$wmFont, $wmSize = 10, $wmOpaque = 90 ){
		//create a watermark.png image with the requested text.
		
		// set font path
		$wmFontPath = NGGALLERY_ABSPATH."fonts/".$wmFont;
		if ( !is_readable($wmFontPath))
			return;	
			
		/*
		$exec = "convert -size 800x500 xc:grey30 -font $wmFontPath -pointsize $wmSize -gravity center -draw \"fill '#$color$wmOpaque'  text 0,0  '$this->watermarkText'\" stamp_fgnd.png"; 
		$make_magick = system($exec);
		$exec = "convert -size 800x500 xc:black -font $wmFontPath -pointsize $wmSize -gravity center -draw \"fill white  text  1,1  '$this->watermarkText'  text  0,0  '$this->watermarkText' fill black  text -1,-1 '$this->watermarkText'\" +matte stamp_mask.png";
		$make_magick = system($exec);
		$exec = "composite -compose CopyOpacity  stamp_mask.png  stamp_fgnd.png  watermark.png";*/

		//convert the opacity between FF or 00; 100->0 and 0->FF (256)
		$opacity = dechex(round((100-$wmOpaque)*256/100));
		
		$exec = "convert -size 800x500 xc:none -font $wmFontPath -pointsize $wmSize -gravity center -fill '#$color$opacity' -annotate 0 '$this->watermarkText' watermark.png";
		$make_magick = system($exec);
		$exec = "mogrify -trim +repage watermark.png";		 
		$make_magick = system($exec);
	
		$this->watermarkImgPath = NGGALLERY_ABSPATH."watermark.png";

		return;		
	}
    
    /**
     * Modfied Watermark function by Steve Peart 
     * http://parasitehosting.com/
     *
 	 * @param string $relPOS
	 * @param int $xPOS
 	 * @param int $yPOS
     */
    function watermarkImage( $relPOS = 'botRight', $xPOS = 0, $yPOS = 0) {

		// if it's not a valid file die...
		if ( !is_readable($this->watermarkImgPath))
			return;	

		$size = GetImageSize($this->watermarkImgPath);
    $watermarkDimensions = array('width'=>$size[0],'height'=>$size[1]);
		
		$sourcefile_width=$this->currentDimensions['width'];
		$sourcefile_height=$this->currentDimensions['height'];
		
		$watermarkfile_width=$watermarkDimensions['width'];
		$watermarkfile_height=$watermarkDimensions['height'];

		switch(substr($relPOS, 0, 3)){
			case 'top': $dest_y = 0 + $yPOS; break;
			case 'mid': $dest_y = ($sourcefile_height / 2) - ($watermarkfile_height / 2); break;
			case 'bot': $dest_y = $sourcefile_height - $watermarkfile_height - $yPOS; break;
			default   : $dest_y = 0; break;
		}
		switch(substr($relPOS, 3)){
			case 'Left'	:	$dest_x = 0 + $xPOS; break;
			case 'Center':	$dest_x = ($sourcefile_width / 2) - ($watermarkfile_width / 2); break;
			case 'Right':	$dest_x = $sourcefile_width - $watermarkfile_width - $xPOS; break;
			default : 		$dest_x = 0; break;
		}
		if ($dest_y<0) {
			$dest_y = $dest_y; 
		} else { 
			$dest_y = "+".$dest_y;
		}
		if ($dest_x<0) {
			$dest_x = $dest_x; 
		} else { 
			$dest_x = "+".$dest_x;
		}
		
		$this->imageMagickComp .=  " -geometry $dest_x$dest_y '$this->watermarkImgPath' -composite";
		//" -dissolve 80% -geometry +$dest_x+$dest_y $this->watermarkImgPath";
	}
	
			
    
	/**
	 * Saves image as $name (can include file path), with quality of # percent if file is a jpeg
	 *
	 * @param string $name
	 * @param int $quality
	 * @return bool errorstate
	 */
	function save($name,$quality=85) {
	    $this->show($quality,$name);
	    if ($this->error == true) {
	    	$this->errmsg = 'Create Image failed. Check safe mode settings';
	    	return false;
	    }
	    return true;
	}
	    
	/**
	 * Outputs the image to the screen, or saves to $name if supplied.  Quality of JPEG images can be controlled with the $quality variable
	 *
	 * @param int $quality
	 * @param string $name
	 */
	function show($quality=85,$name = '') {
		//execute the ImageMagick command and save it to a temp file when name is empty.	
	   //important: resizing and crop to center : http://www.imagemagick.org/Usage/resize/#space_fill
	   
	   /*resulting string should be like:
	    * convert filename 
	    * 		 - (watermark)	    
	    * 		 -size (what size of the original file should be read) optional
	    * 		 -resize (-thumbnail)    
	    * 		 -gravity center	    
	    * 		 -crop  
	    * 		 +repage	    
	    */
		if($name != '') {
				$execString = "convert '$this->fileName' $this->imageMagickExec $this->imageMagickComp -quality $quality '$name'";
				//print $execString;
				$make_magick = system($execString);
	  } else {
	  	//return a raw image stream
				$execString = "convert '$this->fileName' $this->imageMagickExec $this->imageMagickComp -quality $quality JPG:-"; 
				//print $execString;
				header('Content-type: image/jpeg');
				passthru($execString);
		}
		//print $execString;		
	}
}
?>