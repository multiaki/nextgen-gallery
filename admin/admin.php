<?php
/**
 * nggAdminPanel - Admin Section for NextGEN Gallery
 * 
 * @package NextGEN Gallery
 * @author Alex Rabe
 * @copyright 2008
 * @since 1.0.0
 */
class nggAdminPanel{
	
	// constructor
	function nggAdminPanel() {

		// Add the admin menu
		add_action( 'admin_menu', array (&$this, 'add_menu') );
		
		// Add the script and style files
		add_action('admin_print_scripts', array(&$this, 'load_scripts') );
		add_action('admin_print_styles', array(&$this, 'load_styles') );
	
	}

	// integrate the menu	
	function add_menu()  {
		
		add_menu_page(__('Gallery', 'nggallery'), __('Gallery', 'nggallery'), 'NextGEN Gallery overview', NGGFOLDER, array (&$this, 'show_menu'));
	    add_submenu_page( NGGFOLDER , __('Overview', 'nggallery'), __('Overview', 'nggallery'), 'NextGEN Gallery overview', NGGFOLDER, array (&$this, 'show_menu'));
		add_submenu_page( NGGFOLDER , __('Add Gallery', 'nggallery'), __('Add Gallery', 'nggallery'), 'NextGEN Upload images', 'nggallery-add-gallery', array (&$this, 'show_menu'));
	    add_submenu_page( NGGFOLDER , __('Manage Gallery', 'nggallery'), __('Manage Gallery', 'nggallery'), 'NextGEN Manage gallery', 'nggallery-manage-gallery', array (&$this, 'show_menu'));
	    add_submenu_page( NGGFOLDER , __('Album', 'nggallery'), __('Album', 'nggallery'), 'NextGEN Edit album', 'nggallery-manage-album', array (&$this, 'show_menu'));
	    add_submenu_page( NGGFOLDER , __('Tags', 'nggallery'), __('Tags', 'nggallery'), 'NextGEN Manage tags', 'nggallery-tags', array (&$this, 'show_menu'));
	    add_submenu_page( NGGFOLDER , __('Options', 'nggallery'), __('Options', 'nggallery'), 'NextGEN Change options', 'nggallery-options', array (&$this, 'show_menu'));
	    if (wpmu_enable_function('wpmuStyle'))
			add_submenu_page( NGGFOLDER , __('Style', 'nggallery'), __('Style', 'nggallery'), 'NextGEN Change style', 'nggallery-style', array (&$this, 'show_menu'));
	    add_submenu_page( NGGFOLDER , __('Setup Gallery', 'nggallery'), __('Setup', 'nggallery'), 'activate_plugins', 'nggallery-setup', array (&$this, 'show_menu'));
	    if (wpmu_enable_function('wpmuRoles'))
			add_submenu_page( NGGFOLDER , __('Roles', 'nggallery'), __('Roles', 'nggallery'), 'activate_plugins', 'nggallery-roles', array (&$this, 'show_menu'));
	    add_submenu_page( NGGFOLDER , __('About this Gallery', 'nggallery'), __('About', 'nggallery'), 'NextGEN Gallery overview', 'nggallery-about', array (&$this, 'show_menu'));
		if (wpmu_site_admin())
			add_submenu_page( 'wpmu-admin.php' , __('NextGEN Gallery', 'nggallery'), __('NextGEN Gallery', 'nggallery'), 'activate_plugins', 'nggallery-wpmu', array (&$this, 'show_menu'));

	}

	// load the script for the defined page and load only this code	
	function show_menu() {
		
		global $ngg;

		// check for upgrade and show upgrade screen
		if( get_option( 'ngg_db_version' ) != NGG_DBVERSION ) {
			include_once (dirname (__FILE__). '/functions.php');
			include_once (dirname (__FILE__). '/upgrade.php');
			nggallery_upgrade_page();
			return;			
		}
		
  		switch ($_GET['page']){
			case "nggallery-add-gallery" :
				include_once (dirname (__FILE__). '/functions.php');	// admin functions
				include_once (dirname (__FILE__). '/addgallery.php');	// nggallery_admin_add_gallery
				nggallery_admin_add_gallery();
				break;
			case "nggallery-manage-gallery" :
				include_once (dirname (__FILE__). '/functions.php');	// admin functions
				include_once (dirname (__FILE__). '/manage.php');		// nggallery_admin_manage_gallery
				// Initate the Manage Gallery page
				$ngg->manage_page = new nggManageGallery ();
				// Render the output now, because you cannot access a object during the constructor is not finished
				$ngg->manage_page->controller();
				
				break;
			case "nggallery-manage-album" :
				include_once (dirname (__FILE__). '/album.php');		// nggallery_admin_manage_album
				nggallery_admin_manage_album();
				break;				
			case "nggallery-options" :
				include_once (dirname (__FILE__). '/settings.php');		// nggallery_admin_options
				nggallery_admin_options();
				break;
			case "nggallery-tags" :
				include_once (dirname (__FILE__). '/tags.php');			// nggallery_admin_tags
				break;
			case "nggallery-style" :
				include_once (dirname (__FILE__). '/style.php');		// nggallery_admin_style
				nggallery_admin_style();
				break;
			case "nggallery-setup" :
				include_once (dirname (__FILE__). '/setup.php');		// nggallery_admin_setup
				nggallery_admin_setup();
				break;
			case "nggallery-roles" :
				include_once (dirname (__FILE__). '/roles.php');		// nggallery_admin_roles
				nggallery_admin_roles();
				break;
			case "nggallery-import" :
				include_once (dirname (__FILE__). '/myimport.php');		// nggallery_admin_import
				nggallery_admin_import();
				break;
			case "nggallery-about" :
				include_once (dirname (__FILE__). '/about.php');		// nggallery_admin_about
				nggallery_admin_about();
				break;
			case "nggallery-wpmu" :
				include_once (dirname (__FILE__). '/style.php');		
				include_once (dirname (__FILE__). '/wpmu.php');			// nggallery_wpmu_admin
				nggallery_wpmu_setup();
				break;
			case "nggallery" :
			default :
				include_once (dirname (__FILE__). '/overview.php'); 	// nggallery_admin_overview
				nggallery_admin_overview();
				break;
		}
	}
	
	function load_scripts() {
		
		wp_register_script('ngg-ajax', NGGALLERY_URLPATH .'admin/js/ngg.ajax.js', array('jquery'), '1.0.0');
		wp_localize_script('ngg-ajax', 'nggAjaxSetup', array(
					'url' => admin_url('admin-ajax.php'),
					'action' => 'ngg_ajax_operation',
					'operation' => '',
					'nonce' => wp_create_nonce( 'ngg-ajax' ),
					'ids' => '',
					'permission' => __('You do not have the correct permission', 'nggallery'),
					'error' => __('Unexpected Error', 'nggallery'),
					'failure' => __('A failure occurred', 'nggallery')				
		) );
		wp_register_script('ngg-progressbar', NGGALLERY_URLPATH .'admin/js/ngg.progressbar.js', array('jquery'), '1.0.0');
		
		switch ($_GET['page']) {
			case "nggallery-manage-gallery" :
				wp_enqueue_script( 'postbox' );
				wp_enqueue_script( 'ngg-ajax' );
				wp_enqueue_script( 'ngg-progressbar' );
				add_thickbox();
			break;
			case "nggallery-manage-album" :
				wp_enqueue_script( 'jquery-ui-sortable' );
			break;
			case "nggallery-options" :
				wp_enqueue_script( 'jquery-ui-tabs' );
			break;		
			case "nggallery-add-gallery" :
				wp_enqueue_script( 'jquery-ui-tabs' );
				wp_enqueue_script( 'mutlifile', NGGALLERY_URLPATH .'admin/js/jquery.MultiFile.js', array('jquery'), '1.1.1' );
				wp_enqueue_script( 'ngg-swfupload-handler', NGGALLERY_URLPATH .'admin/js/swfupload.handler.js', array('swfupload'), '1.0.0' );
				wp_enqueue_script( 'ngg-ajax' );
				wp_enqueue_script( 'ngg-progressbar' );
			break;
		}
	}		
	
	function load_styles() {
		
		switch ($_GET['page']) {
			case NGGFOLDER :
				wp_enqueue_style( 'nggadmin', NGGALLERY_URLPATH .'admin/css/nggadmin.css', false, '2.5.0', 'screen' );
				wp_admin_css( 'css/dashboard' );
			break;
			case "nggallery-add-gallery" :
			case "nggallery-options" :
				wp_enqueue_style( 'nggtabs', NGGALLERY_URLPATH .'admin/css/jquery.ui.tabs.css', false, '2.5.0', 'screen' );
			case "nggallery-manage-gallery" :
			case "nggallery-roles" :
			case "nggallery-manage-album" :
				wp_enqueue_style( 'nggadmin', NGGALLERY_URLPATH .'admin/css/nggadmin.css', false, '2.5.0', 'screen' );
				wp_enqueue_style( 'thickbox');			
			break;
			case "nggallery-tags" :
				wp_enqueue_style( 'nggtags', NGGALLERY_URLPATH .'admin/css/tags-admin.css', false, '2.6.0', 'screen' );
				break;
			case "nggallery-style" :
				wp_admin_css( 'css/theme-editor' );
			break;
		}	
	}

}

function wpmu_site_admin() {
	// Check for site admin
	if (function_exists('is_site_admin'))
		if (is_site_admin())
			return true;
			
	return false;
}

function wpmu_enable_function($value) {
	if (IS_WPMU) {
		$ngg_options = get_site_option('ngg_options');
		return $ngg_options[$value];
	}
	// if this is not WPMU, enable it !
	return true;
}

?>