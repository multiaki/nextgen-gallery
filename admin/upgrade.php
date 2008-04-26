<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

// update routine for older version
function ngg_upgrade() {
	
	global $wpdb, $user_ID;
	
	$nggpictures					= $wpdb->prefix . 'ngg_pictures';
	$nggallery						= $wpdb->prefix . 'ngg_gallery';

	// get the current user ID
	get_currentuserinfo();

	// Be sure that the tables exist
	if($wpdb->get_var("show tables like '$nggpictures'") == $nggpictures) {

		$installed_ver = get_option( "ngg_db_version" );

		// v0.33 -> v.071
		if (version_compare($installed_ver, '0.71', '<')) {
			$wpdb->query("ALTER TABLE ".$nggpictures." CHANGE pid pid BIGINT(20) NOT NULL AUTO_INCREMENT ");
			$wpdb->query("ALTER TABLE ".$nggpictures." CHANGE galleryid galleryid BIGINT(20) NOT NULL ");
			$wpdb->query("ALTER TABLE ".$nggallery." CHANGE gid gid BIGINT(20) NOT NULL AUTO_INCREMENT ");
			$wpdb->query("ALTER TABLE ".$nggallery." CHANGE pageid pageid BIGINT(20) NULL DEFAULT '0'");
			$wpdb->query("ALTER TABLE ".$nggallery." CHANGE previewpic previewpic BIGINT(20) NULL DEFAULT '0'");
			$wpdb->query("ALTER TABLE ".$nggallery." CHANGE gid gid BIGINT(20) NOT NULL AUTO_INCREMENT ");
			$wpdb->query("ALTER TABLE ".$nggallery." CHANGE description galdesc MEDIUMTEXT NULL");
		}
		// v0.71 -> v0.84
		if (version_compare($installed_ver, '0.84', '<')) {
			$wpdb->query("ALTER TABLE ".$nggpictures." ADD sortorder BIGINT(20) DEFAULT '0' NOT NULL AFTER exclude");
		}

		// v0.84 -> v0.95
		if (version_compare($installed_ver, '0.95', '<')) {
			var_dump($user_ID);
			// first add the author field and set it to the current administrator
			$wpdb->query("ALTER TABLE ".$nggallery." ADD author BIGINT(20) NOT NULL DEFAULT '$user_ID' AFTER previewpic");
			// switch back to zero
			$wpdb->query("ALTER TABLE ".$nggallery." CHANGE author author BIGINT(20) NOT NULL DEFAULT '0'");
		}

		update_option( "ngg_db_version", NGG_DBVERSION );
	}
}

// Import the tags into the wp tables
function ngg_convert_tags() {
	global $wpdb, $wp_taxonomies;
	
	// get the obsolete tables
	$wpdb->nggtags						= $wpdb->prefix . 'ngg_tags';
	$wpdb->nggpic2tags					= $wpdb->prefix . 'ngg_pic2tags';
	
	$picturelist = $wpdb->get_col("SELECT pid FROM $wpdb->nggpictures");
	if ( is_array($picturelist) ) {
		foreach($picturelist as $id) {
			$tags = array();
			$tagarray = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->nggpic2tags AS t INNER JOIN $wpdb->nggtags AS tt ON t.tagid = tt.id WHERE t.picid = '$id' ORDER BY tt.slug ASC ");
			if (!empty($tagarray)){
				foreach($tagarray as $element) {
					$tags[$element->id] = $element->name;
				}
				wp_set_object_terms($id, $tags, 'ngg_tag');
			}
		}
	}
	
	// Update tags
	// $act_tags 	= addslashes(trim($_POST['act_tags']));
	// $tags = explode(',',$act_tags);
	// wp_set_object_terms($act_vid, $tags, WORDTUBE_TAXONOMY);
	
	// Retrieve tags to display
	// $act_tags = implode(',',wp_get_object_terms($act_vid, WORDTUBE_TAXONOMY, 'fields=names'));
}

function nggallery_upgrade_page()  {	
	global $wpdb;
	
?>

<div class="wrap">
	<h2><?php _e('Upgrade NextGEN Gallery', 'nggallery') ;?></h2>
	<?php ngg_convert_tags(); ?>
</div>
	
<?php
}

?>