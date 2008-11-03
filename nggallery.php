<?php
/*
Plugin Name: NextGEN Gallery
Plugin URI: http://alexrabe.boelinger.com/?page_id=80
Description: A NextGENeration Photo gallery for the WEB2.0(beta).
Author: NextGEN DEV-Team
Version: 1.0.0-beta1

Author URI: http://alexrabe.boelinger.com/

Copyright 2007-2008 by Alex Rabe & NextGEN DEV-Team

The NextGEN button is taken from the Fugue Icons of http://www.pinvoke.com/.

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
if (!class_exists('nggLoader')) {
class nggLoader {
	
	var $version     = '1.0.0.b1';
	var $dbversion   = '0.9.7';
	var $minium_WP   = '2.7-beta';
	var $minium_WPMU = '2.7';
	var $updateURL   = 'http://nextgen.boelinger.com/version.php';
	var $options     = '';
	var $manage_page;
	
	function nggLoader() {

		// Stop the plugin if we missed the requirements
		if ( ( !$this->required_version() ) && ( !$this->check_memory_limit() ) )
			return;
			
		// Get some constants first
		$this->define_constant();
		$this->define_tables();
		$this->register_taxonomy();
		$this->load_options();
		$this->load_dependencies();
				
		// Init options & tables during activation & deregister init option
		register_activation_hook( dirname(__FILE__) . '/nggallery.php', array(&$this, 'activate') );
		register_deactivation_hook( dirname(__FILE__) . '/nggallery.php', array(&$this, 'deactivate') );	
		
		if ( function_exists('register_uninstall_hook') )
			register_uninstall_hook( dirname(__FILE__) . '/nggallery.php', array(&$this, 'uninstall') );
		
		// Start this plugin once all other plugins are fully loaded
		add_action( 'plugins_loaded', array(&$this, 'start_plugin') );

	}
	
	function start_plugin() {

		global $nggRewrite;
				
		// Load the language file
		add_action('init', array(&$this, 'load_textdomain') );
		
		// Content Filters
		add_filter('ngg_gallery_name', 'sanitize_title');
		
		// Load the admin panel or the frontend functions
		if ( is_admin() ) {	
			
			// Pass the init check or show a message
			if (get_option( "ngg_init_check" ) != false )
				add_action( 'admin_notices', create_function('', 'echo \'<div id="message" class="error"><p><strong>' . get_option( "ngg_init_check" ) . '</strong></p></div>\';') );
				
		} else {			
			
			// Add MRSS to wp_head
			add_action('wp_head', array('nggMediaRss', 'add_mrss_alternate_link'));
			
			// If activated, add PicLens/Cooliris javascript to footer
			if ( $this->options['usePicLens'] )
				add_action('wp_head', array('nggMediaRss', 'add_piclens_javascript'));
			
			// Add rewrite rules
			$nggRewrite = new nggRewrite();
			
			// Why is this not core ?
			add_action('wp_head', 'wp_print_styles');
				
			// Add the script and style files
			add_action('wp_print_scripts', array(&$this, 'load_scripts') );
			add_action('wp_print_styles', array(&$this, 'load_styles') );

		}	
	}
	
	
	function required_version() {
		
		global $wp_version, $wpmu_version;
		
		// Check for WPMU installation
		if (!defined ('IS_WPMU'))
			define('IS_WPMU', version_compare($wpmu_version, $this->minium_WPMU, '>=') );
			
		// Check for WP version installation
		$wp_ok  =  version_compare($wp_version, $this->minium_WP, '>=');
		
		if ( ($wp_ok == FALSE) and (IS_WPMU != TRUE) ) {
			add_action(
				'admin_notices', 
				create_function(
					'', 
					'global $ngg; printf (\'<div id="message" class="error"><p><strong>\' . __(\'Sorry, NextGEN Gallery works only under WordPress %s or higher\', "nggallery" ) . \'</strong></p></div>\', $ngg->minium_WP );'
				)
			);
			return false;
		}
		
		return true;
		
	}
	
	function check_memory_limit() {
		
		$memory_limit = (int) substr( ini_get('memory_limit'), 0, -1);
		//This works only with enough memory, 8MB is silly, wordpress requires already 7.9999
		if ( ($memory_limit != 0) && ($memory_limit < 12 ) ) {
			add_action(
				'admin_notices', 
				create_function(
					'', 
					'echo \'<div id="message" class="error"><p><strong>' . __('Sorry, NextGEN Gallery works only with a Memory Limit of 16 MB higher',"nggallery") . '</strong></p></div>\';'
				)
			);
			return false;
		}
		
		return true;
		
	}
	
	function define_tables() {		
		global $wpdb;
		
		// add database pointer 
		$wpdb->nggpictures					= $wpdb->prefix . 'ngg_pictures';
		$wpdb->nggallery					= $wpdb->prefix . 'ngg_gallery';
		$wpdb->nggalbum						= $wpdb->prefix . 'ngg_album';
		
	}
	
	function register_taxonomy() {		

		// Register the NextGEN taxonomy	
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

	function define_constant() {
		
		//TODO:SHOULD BE REMOVED LATER
		define('NGGVERSION', $this->version);
		// Minimum required database version
		define('NGG_DBVERSION', $this->dbversion);
		define('NGGURL', $this->updateURL);

		// required for Windows & XAMPP
		define('WINABSPATH', str_replace("\\","/",ABSPATH) );
			
		// define URL
		define('NGGFOLDER', plugin_basename( dirname(__FILE__)) );
		
		define('NGGALLERY_ABSPATH', str_replace("\\","/", WP_CONTENT_DIR.'/plugins/'.plugin_basename( dirname(__FILE__)).'/') );
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
	
	function load_dependencies() {
	
		// Load global libraries
		require_once (dirname (__FILE__) . '/lib/core.php');
		require_once (dirname (__FILE__) . '/lib/ngg-db.php');
		require_once (dirname (__FILE__) . '/lib/image.php');
		require_once (dirname (__FILE__) . '/lib/meta.php');
		require_once (dirname (__FILE__) . '/lib/tags.php');
		require_once (dirname (__FILE__) . '/lib/media-rss.php');
		require_once (dirname (__FILE__) . '/widgets/widgets.php');
		include_once (dirname (__FILE__) . '/admin/tinymce/tinymce.php');
		
		if (DOING_AJAX)
			require_once (dirname (__FILE__) . '/admin/ajax.php');
					
		// Load backend libraries
		if ( is_admin() ) {	
			require_once (dirname (__FILE__) . '/admin/admin.php');
			require_once (dirname (__FILE__) . '/admin/media-upload.php');
			$this->nggAdminPanel = new nggAdminPanel();
			
		// Load frontend libraries							
		} else {
			require_once (dirname (__FILE__) . '/nggfunctions.php');
			require_once (dirname (__FILE__) . '/lib/shortcodes.php');
			require_once (dirname (__FILE__) . '/lib/rewrite.php');
		}			
	}
	
	function load_textdomain() {
		
		load_plugin_textdomain('nggallery', false, dirname( plugin_basename(__FILE__) ) . '/lang');

	}
	
	function load_scripts() {

		echo "<meta name='NextGEN' content='" . $this->version . "' />\n";
		
		//	activate Thickbox
		if ($this->options['thumbEffect'] == "thickbox") 
			wp_enqueue_script( 'thickbox' );

		// activate modified Shutter reloaded if not use the Shutter plugin
		if ( ($this->options['thumbEffect'] == "shutter") && !function_exists('srel_makeshutter') ) {
			wp_register_script('shutter', NGGALLERY_URLPATH .'shutter/shutter-reloaded.js', false ,'1.3.0');
			wp_localize_script('shutter', 'shutterSettings', array(
						'msgLoading' => __('L O A D I N G', 'nggallery'),
						'msgClose' => __('Click to Close', 'nggallery'),
						'imageCount' => '1'				
			) );
			wp_enqueue_script( 'shutter' );
	    }
		
		// required for the slideshow
		if ( $this->options['galShowSlide'] ) 
			wp_enqueue_script('swfobject', NGGALLERY_URLPATH .'admin/js/swfobject.js', FALSE, '2.1');

	}
	
	function load_styles() {
		
		// check first the theme folder for a nggallery.css
		if ( nggGallery::get_theme_css_file() )
			wp_enqueue_style('NextGEN', nggGallery::get_theme_css_file() , false, '1.0.0', 'screen'); 
		else if ($this->options['activateCSS'])
			wp_enqueue_style('NextGEN', NGGALLERY_URLPATH.'css/'.$this->options['CSSfile'], false, '1.0.0', 'screen'); 
		
		//	activate Thickbox
		if ($this->options['thumbEffect'] == "thickbox") 
			wp_enqueue_style( 'thickbox');

		// activate modified Shutter reloaded if not use the Shutter plugin
		if ( ($this->options['thumbEffect'] == "shutter") && !function_exists('srel_makeshutter') )
			wp_enqueue_style('shutter', NGGALLERY_URLPATH .'shutter/shutter-reloaded.css', false, '1.3.0', 'screen');
		
	}
	
	function load_options() {
		// Load the options
		$this->options = get_option('ngg_options');
	}
	
	function activate() {
		
		include_once (dirname (__FILE__) . '/admin/install.php');
		nggallery_install();
		// remove the update message
		delete_option( 'ngg_update_exists' );
		
	}
	
	function deactivate() {
		
		// remove & reset the init check option
		delete_option( 'ngg_init_check' );
		delete_option( 'ngg_update_exists' );
	}
	
	function uninstall() {
		
		include_once (dirname (__FILE__) . '/admin/install.php');
		nggallery_uninstall();
		// remove the update message
		delete_option( 'ngg_update_exists' );
		
	}
}
	// Let's start the holy plugin
	global $ngg;
	$ngg = new nggLoader();
}

?>