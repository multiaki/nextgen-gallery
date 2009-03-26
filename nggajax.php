<?php
require_once('ngg-config.php');

if ((!isset($_GET['galleryid']) || !is_numeric($_GET['galleryid'])) || (!isset($_GET['id']) || !is_numeric($_GET['id'])) || !isset($_GET['type'])) {
	die('Insufficient parameters.');
}

switch ($_GET['type']) {
	case 'gallery':
		set_query_var('pageid', intval($_GET['id']));
		// TODO: in what situation is nggpage different from pageid?
		set_query_var('nggpage', intval($_GET['id']));
		
		echo nggShowGallery( intval($_GET['galleryid']) );
		
		break;
	case 'browser':
		set_query_var('pid', intval($_GET['id']));
		global $id;
			
		echo nggShowImageBrowser( intval($_GET['galleryid']) );
		
		break;
	default:
		echo 'Wrong request type specified.';
}