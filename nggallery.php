<?php
/*
Plugin Name: NextGEN Gallery
Plugin URI: http://alexrabe.boelinger.com/?page_id=80
Description: A NextGENeration Photo gallery for the WEB2.0(beta).
Author: NextGEN DEV-Team
Version: 1.0.0a

Author URI: http://alexrabe.boelinger.com/

Copyright 2007-2008 by Alex Rabe & NextGEN DEV-Team

The NextGEN button is taken from the Silk set of FamFamFam. See more at 
http://www.famfamfam.com/lab/icons/silk/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Please note  :

The JW Image Rotator (Slideshow) is not part of this license and is available
under a Creative Commons License, which allowing you to use, modify and redistribute 
them for noncommercial purposes. 

For commercial use please look at the Jeroen's homepage : http://www.jeroenwijering.com/ 

*/ 

// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

// ini_set('display_errors', '1');
// ini_set('error_reporting', E_ALL);

class nggLoader {
	
	var $version     = '1.0.0.a';
	var $dbversion   = '0.9.5';
	var $minium_WP   = '2.6';
	var $minium_WPMU = '2.6';
	var $updateURL   = 'http://nextgen.boelinger.com/version.php';
	var $options     = '';
	
	function nggLoader() {
		
		global $nggRewrite;
		
		// Stop the plugin if we missed the requirements
		if ( ( !$this->requiredVersion() ) && ( !$this->checkMemoryLimit() ) )
			return;

		// define some variables
		$this->defineConstant();
		$this->defineTables();
		$this->registerTaxonomy();

		// Load the options
		$this->options = get_option('ngg_options');

		// Pass the init check or show a message
		if (get_option( "ngg_init_check" ) != false )
			add_action( 'admin_notices', create_function('', 'echo \'<div id="message" class="error fade"><p><strong>' . get_option( "ngg_init_check" ) . '</strong></p></div>\';') );

		// Load tinymce button 
		include_once (dirname (__FILE__)."/tinymce3/tinymce.php");
			
		// Load gallery class
		require_once (dirname (__FILE__).'/lib/ngg-gallery-plugin.lib.php');
		require_once (dirname (__FILE__).'/lib/ngg-gallery.lib.php');
		require_once (dirname (__FILE__).'/lib/ngg-gallery-dao.lib.php');
		require_once (dirname (__FILE__).'/lib/ngg-image.lib.php');
		require_once (dirname (__FILE__).'/lib/ngg-image-dao.lib.php');
		require_once (dirname (__FILE__).'/lib/ngg-meta.lib.php');
		require_once (dirname (__FILE__).'/lib/ngg-tags.lib.php');
		require_once (dirname (__FILE__).'/admin/ajax.php');

		// Load the language file
		add_action('init', array(&$this, 'load_textdomain') );
		
		// Content Filters
		add_filter('ngg_gallery_name', 'sanitize_title');
		
		// Init options & tables during activation 
		register_activation_hook( NGGFOLDER.'/nggallery.php', array(&$this, 'actiavte') );
		register_deactivation_hook( NGGFOLDER.'/nggallery.php', array(&$this, 'deactivate') );
		
		// Load the admin panel or the frontend functions
		if (is_admin()) {			
			require_once (dirname (__FILE__)."/admin/admin.php");				
		} else {			
			// Load the gallery generator
			require_once (dirname (__FILE__)."/nggfunctions.php");
			require_once (dirname (__FILE__).'/lib/shortcodes.php');
			require_once (dirname (__FILE__).'/lib/rewrite.php');
			
			// Add rewrite rules
			$nggRewrite = new nggRewrite();
				
			// Add the script files
			add_action('wp_print_scripts', array(&$this, 'load_scripts') );
		}	

		// Init the gallery class
		$nggallery = new nggGalleryPlugin();
	
	}
	
	function requiredVersion() {
		
		global $wp_version, $wpmu_version;
		
		// Check for WPMU installation
		if (!defined ('IS_WPMU'))
			define('IS_WPMU', version_compare($wpmu_version, $this->minium_WPMU, '>=') );
			
		// Check for WP version installation
		$wp_ok  =  version_compare($wp_version, $this->minium_WP, '>=');		
		
		if ( ($wp_ok == FALSE) and (IS_WPMU != TRUE) ) {
//			add_action(
//				'admin_notices', 
//				create_function(
//					'', 
//					'printf (\'<div id="message" class="error fade"><p><strong>\' . __(\'Sorry, NextGEN Gallery works only under WordPress %s or higher\',"nggallery") . \'</strong></p></div>\', $this->minium_WP);'
//				));
			return false;
		}
		
		return true;
		
	}
	
	function checkMemoryLimit() {
		
		$memory_limit = (int) substr( ini_get('memory_limit'), 0, -1);
		//This works only with enough memory, 8MB is silly, wordpress requires already 7.9999
		if ( ($memory_limit != 0) && ($memory_limit < 12 ) ) {
			add_action('admin_notices', create_function('', 'echo \'<div id="message" class="error fade"><p><strong>' . __('Sorry, NextGEN Gallery works only with a Memory Limit of 16 MB higher',"nggallery") . '</strong></p></div>\';'));
			return false;
		}
		
		return true;
		
	}
	
	function defineTables() {		
		global $wpdb, $wp_taxonomies;
		
		// add database pointer 
		$wpdb->nggpictures					= $wpdb->prefix . 'ngg_pictures';
		$wpdb->nggallery					= $wpdb->prefix . 'ngg_gallery';
		$wpdb->nggalbum						= $wpdb->prefix . 'ngg_album';
		
	}
	
	function registerTaxonomy() {		
		// Register the NextGEN taxonomy	
		//--
		register_taxonomy( 
			'ngg_tag', 
			'nggallery',
			array(
 	            'label' => __('Picture tag', 'nggallery'),
 	            'template' => __('Picture tag: %2$l.', 'nggallery'),
	            'helps' => __('Separate picture tags with commas.', 'nggallery'),
	            'sort' => true,
 	            'args' => array('orderby' => 'term_order'),
	            'rewrite' => array('slug' => 'picture-tag'),
 	            'query_var' => 'picture-tag'
			)
		);
		
	}

	function defineConstant() {
		
		//TODO:SHOULD BE REMOVED LATER
		define('NGGVERSION', $this->version);
		// Minimum required database version
		define('NGG_DBVERSION', $this->dbversion);
		define('NGGURL', $this->updateURL);

		// required for Windows & XAMPP
		$myabspath = str_replace("\\","/",ABSPATH);  
		define('WINABSPATH', $myabspath);
			
		// define URL
		define('NGGFOLDER', plugin_basename( dirname(__FILE__)) );
		define('NGGALLERY_ABSPATH', WP_CONTENT_DIR.'/plugins/'.plugin_basename( dirname(__FILE__)).'/' );
		define('NGGALLERY_URLPATH', WP_CONTENT_URL.'/plugins/'.plugin_basename( dirname(__FILE__)).'/' );
		
		// look for imagerotator
		define('NGGALLERY_IREXIST', file_exists( NGGALLERY_ABSPATH.'imagerotator.swf' ));

		// get value for safe mode
		if ( (gettype( ini_get('safe_mode') ) == 'string') ) {
			// if sever did in in a other way
			if ( ini_get('safe_mode') == 'off' ) define('SAFE_MODE', FALSE);
			else define( 'SAFE_MODE', ini_get('safe_mode') );
		} else
		define( 'SAFE_MODE', ini_get('safe_mode') );
		
	}
	
	function load_textdomain() {
		
		load_plugin_textdomain('nggallery', false, dirname( plugin_basename(__FILE__) ) . '/lang');

	}
	
	function load_scripts() {

		echo "<meta name='NextGEN' content='" . $this->version . "' />\n";
		if ($this->options['activateCSS']) 
			echo "\n".'<style type="text/css" media="screen">@import "'.NGGALLERY_URLPATH.'css/'.$this->options['CSSfile'].'";</style>';
		if ($this->options['thumbEffect'] == "thickbox") {
			echo "\n".'<script type="text/javascript"> var tb_pathToImage = "'.NGGALLERY_URLPATH.'thickbox/'.$this->options['thickboxImage'].'";</script>';
			echo "\n".'<style type="text/css" media="screen">@import "'.NGGALLERY_URLPATH.'thickbox/thickbox.css";</style>'."\n";
	   		wp_enqueue_script('ngg-thickbox', NGGALLERY_URLPATH .'thickbox/thickbox-pack.js', array('jquery'), '3.1.1');
	    }
		    
		// test for wordTube function
		if (!function_exists('integrate_swfobject')) {
			wp_enqueue_script('swfobject', NGGALLERY_URLPATH .'admin/js/swfobject.js', FALSE, '1.5');
		}

	}
	
	function activate() {
		
		include_once (dirname (__FILE__)."/admin/install.php");
		nggallery_install();
		
	}
	
	function deactivate() {
		
		// remove & reset the init check option
		delete_option( "ngg_init_check" );
		
	}
}

// Start this plugin once all other plugins are fully loaded
add_action( 'plugins_loaded', create_function( '', 'global $ngg; $ngg = new nggLoader();' ) );

?>