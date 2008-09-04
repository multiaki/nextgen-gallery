<?php  

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { 
	die('You are not allowed to call this page directly.'); 
}

function nggallery_admin_manage_gallery() {
	global $wpdb, $ngg;

	//TODO:GID & Mode should the hidden post variables

	// GET variables
	$act_gid = (int) $_GET['gid'];
	$act_pid = (int) $_GET['pid'];	
	$mode = trim(attribute_escape($_GET['mode']));

	//TODO: Reomove this vars
	$hideThumbs = ngg_hide_thumb();
	$showTags = ngg_show_tags();

	if ($mode == 'delete') {
	// Delete a gallery
	
		check_admin_referer('ngg_editgallery');
	
		// get the path to the gallery
		$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$act_gid' ");
		if ($gallerypath){
	
			// delete pictures
			//TODO:Remove also Tag reference
			$imagelist = $wpdb->get_col("SELECT filename FROM $wpdb->nggpictures WHERE galleryid = '$act_gid' ");
			if ($ngg->options['deleteImg']) {
				if (is_array($imagelist)) {
					foreach ($imagelist as $filename) {
						@unlink(WINABSPATH . $gallerypath . '/thumbs/thumbs_' . $filename);
						@unlink(WINABSPATH . $gallerypath .'/'. $filename);
					}
				}
				// delete folder
					@rmdir( WINABSPATH . $gallerypath . '/thumbs' );
					@rmdir( WINABSPATH . $gallerypath );
			}
		}

		$delete_pic = $wpdb->query("DELETE FROM $wpdb->nggpictures WHERE galleryid = $act_gid");
		$delete_galllery = $wpdb->query("DELETE FROM $wpdb->nggallery WHERE gid = $act_gid");
		
		if($delete_galllery)
			nggGalleryPlugin::show_message( __('Gallery','nggallery').' \''.$act_gid.'\' '.__('deleted successfully','nggallery'));
			
	 	$mode = 'main'; // show mainpage
	}

	if ($mode == 'delpic') {
	// Delete a picture
	//TODO:Remove also Tag reference
		check_admin_referer('ngg_delpicture');
		$filename = $wpdb->get_var("SELECT filename FROM $wpdb->nggpictures WHERE pid = '$act_pid' ");
		if ($filename) {
			$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$act_gid' ");
			if ($gallerypath){
				$thumb_folder = nggGalleryPlugin::get_thumbnail_folder($gallerypath, FALSE);
				if ($ngg->options['deleteImg']) {
					@unlink(WINABSPATH . $gallerypath. '/thumbs/thumbs_' .$filename);
					@unlink(WINABSPATH . $gallerypath . '/' . $filename);
				}
			}		
			$delete_pic = $wpdb->query("DELETE FROM $wpdb->nggpictures WHERE pid = $act_pid");
		}
		if($delete_pic)
			nggGalleryPlugin::show_message( __('Picture','nggallery').' \''.$act_pid.'\' '.__('deleted successfully','nggallery') );
			
	 	$mode = 'edit'; // show pictures

	}
	
	if (isset ($_POST['bulkaction']) && isset ($_POST['doaction']))  {
		// do bulk update
		
		check_admin_referer('ngg_updategallery');
		
		$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$act_gid' ");
		$imageslist = array();
		
		if ( is_array($_POST['doaction']) ) {
			foreach ( $_POST['doaction'] as $imageID ) {
				$imageslist[] = $wpdb->get_var("SELECT filename FROM $wpdb->nggpictures WHERE pid = '$imageID' ");
			}
		}
		
		switch ($_POST['bulkaction']) {
			case 0;
			// No action
				break;
			case 1:
			// Set watermark
				nggAdmin::do_ajax_operation( 'set_watermark' , $_POST['doaction'], __('Set watermark','nggallery') );
				break;
			case 2:
			// Create new thumbnails
				nggAdmin::do_ajax_operation( 'create_thumbnail' , $_POST['doaction'], __('Create new thumbnails','nggallery') );
				break;
			case 3:
			// Resample images
				nggAdmin::do_ajax_operation( 'resize_image' , $_POST['doaction'], __('Resize images','nggallery') );
				break;
			case 4:
			// Delete images
				if ( is_array($_POST['doaction']) ) {
				if ($gallerypath){
					$thumb_folder = nggGalleryPlugin::get_thumbnail_folder($gallerypath, FALSE);
					foreach ( $_POST['doaction'] as $imageID ) {
						$filename = $wpdb->get_var("SELECT filename FROM $wpdb->nggpictures WHERE pid = '$imageID' ");
						if ($ngg->options['deleteImg']) {
							@unlink(WINABSPATH.$gallerypath.'/'.$thumb_folder.'/'. "thumbs_" .$filename);
							@unlink(WINABSPATH.$gallerypath.'/'.$filename);	
						} 
						$delete_pic = $wpdb->query("DELETE FROM $wpdb->nggpictures WHERE pid = $imageID");
					}
				}		
				if($delete_pic)
					nggGalleryPlugin::show_message(__('Pictures deleted successfully ',"nggallery"));
				}
				break;
			case 8:
			// Import Metadata
				nggAdmin::import_MetaData($_POST['doaction']);
				nggGalleryPlugin::show_message(__('Import metadata finished',"nggallery"));
				break;
		}
	}
	
	// will be called after a ajax operation
	if (isset ($_POST['ajax_callback']))  {
			if ($_POST['ajax_callback'] == 1)
				nggGalleryPlugin::show_message(__('Operation successfull. Please clear your browser cache.',"nggallery"));
		$mode = 'edit';		
	}
	
	if (isset ($_POST['TB_bulkaction']) && isset ($_POST['TB_SelectGallery']))  {
		
		check_admin_referer('ngg_thickbox_form');
		
		$pic_ids  = explode(",", $_POST['TB_imagelist']);
		$dest_gid = (int) $_POST['dest_gid'];
		
		switch ($_POST['TB_bulkaction']) {
			case 9:
			// Copy images
				nggAdmin::copy_images( $pic_ids, $dest_gid, false );
				break;
			case 10:
			// Move images
				nggAdmin::copy_images( $pic_ids, $dest_gid, true );
				break;
		}
	}
	
	if (isset ($_POST['TB_bulkaction']) && isset ($_POST['TB_EditTags']))  {
		// do tags update

		check_admin_referer('ngg_thickbox_form');

		switch ($_POST['TB_bulkaction']) {
		}

		// get the images list		
		$pic_ids = explode(",", $_POST['TB_imagelist']);
		$taglist = explode(",", $_POST['taglist']);
		$taglist = array_map('trim', $taglist);
		
		foreach($pic_ids as $pic_id) {
			
			// which action should be performed ?
			switch ($_POST['TB_bulkaction']) {
				case 0;
				// No action
					break;
				case 7:
				// Overwrite tags
					wp_set_object_terms($pic_id, $taglist, 'ngg_tag');
					break;					
				case 5:
				// Add / append tags
					wp_set_object_terms($pic_id, $taglist, 'ngg_tag', TRUE);
					break;
				case 6:
				// Delete tags
					$oldtags = wp_get_object_terms($pic_id, 'ngg_tag', 'fields=names');
					// get the slugs, to vaoid  case sensitive problems
					$slugarray = array_map('sanitize_title', $taglist);
					$oldtags = array_map('sanitize_title', $oldtags);
					// compare them and return the diff
					$newtags = array_diff($oldtags, $slugarray);
					wp_set_object_terms($pic_id, $newtags, 'ngg_tag');
					break;
			}
		}

		nggGalleryPlugin::show_message(__('Tags changed',"nggallery"));
	}

	if (isset ($_POST['updatepictures']))  {
	// Update pictures	
	
		check_admin_referer('ngg_updategallery');
		
		$gallery_title   = attribute_escape($_POST['title']);
		$gallery_path    = attribute_escape($_POST['path']);
		$gallery_desc    = attribute_escape($_POST['gallerydesc']);
		$gallery_pageid  = (int) $_POST['pageid'];
		$gallery_preview = (int) $_POST['previewpic'];
		
		$wpdb->query("UPDATE $wpdb->nggallery SET title= '$gallery_title', path= '$gallery_path', galdesc = '$gallery_desc', pageid = '$gallery_pageid', previewpic = '$gallery_preview' WHERE gid = '$act_gid'");

		if (isset ($_POST['author']))  {		
			$gallery_author  = (int) $_POST['author'];
			$wpdb->query("UPDATE $wpdb->nggallery SET author = '$gallery_author' WHERE gid = '$act_gid'");
		}

		if ($showTags)
			ngg_update_tags(attribute_escape($_POST['tags']));			
		else 
			ngg_update_pictures(attribute_escape($_POST['description']), attribute_escape($_POST['alttext']), attribute_escape($_POST['exclude']), $act_gid );

		//hook for other plugin to update the fields
		do_action('ngg_update_gallery', $act_gid, $_POST);

		nggGalleryPlugin::show_message(__('Update successful',"nggallery"));
	}

	if (isset ($_POST['scanfolder']))  {
	// Rescan folder
		check_admin_referer('ngg_updategallery');
	
		$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$act_gid' ");
		nggAdmin::import_gallery($gallerypath);
	}

	if (isset ($_POST['addnewpage']))  {
	// Add a new page
	
		check_admin_referer('ngg_updategallery');
		
		$parent_id      = attribute_escape($_POST['parent_id']);
		$gallery_title  = attribute_escape($_POST['title']);
		$gallery_name   = $wpdb->get_var("SELECT name FROM $wpdb->nggallery WHERE gid = '$act_gid' ");
		
		// Create a WP page
		global $user_ID;

		$page['post_type']    = 'page';
		$page['post_content'] = '[gallery='.$act_gid.']';
		$page['post_parent']  = $parent_id;
		$page['post_author']  = $user_ID;
		$page['post_status']  = 'publish';
		$page['post_title']   = $gallery_title == '' ? $gallery_name : $gallery_title;

		$gallery_pageid = wp_insert_post ($page);
		if ($gallery_pageid != 0) {
			$result = $wpdb->query("UPDATE $wpdb->nggallery SET title= '$gallery_title', pageid = '$gallery_pageid' WHERE gid = '$act_gid'");
			nggGalleryPlugin::show_message( __('New gallery page ID','nggallery'). ' ' . $pageid . ' -> <strong>' . $gallery_title . '</strong> ' .__('created','nggallery') );
		}
	}
	
	if (isset ($_POST['backToGallery'])) {
		$mode = 'edit';
	}
	
	// show sort order
	if ( ($mode == 'sort') || isset ($_POST['sortGallery'])) {
		$mode = 'sort';
		include_once (dirname (__FILE__). '/sort.php');
		nggallery_sortorder($act_gid);
		return;
	}

	if (($mode == '') or ($mode == "main"))
		nggallery_manage_gallery_main();
	
	if ($mode == 'edit')
		nggallery_picturelist($hideThumbs,$showTags);
	
}//nggallery_admin_manage_gallery

function nggallery_manage_gallery_main() {
// *** show main gallery list

	global $wpdb;
	
	?>
	<div class="wrap">
		<h2><?php _e('Gallery Overview', 'nggallery') ?></h2>
		<br style="clear: both;"/>
		<table class="widefat">
			<thead>
			<tr>
				<th scope="col" ><?php _e('ID') ?></th>
				<th scope="col" ><?php _e('Title', 'nggallery') ?></th>
				<th scope="col" ><?php _e('Description', 'nggallery') ?></th>
				<th scope="col" ><?php _e('Author', 'nggallery') ?></th>
				<th scope="col" ><?php _e('Page ID', 'nggallery') ?></th>
				<th scope="col" ><?php _e('Quantity', 'nggallery') ?></th>
				<th scope="col" ><?php _e('Action'); ?></th>
			</tr>
			</thead>
			<tbody>
<?php			
$gallerylist = nggGalleryDAO::find_all_galleries('gid', 'asc');

if($gallerylist) {
	foreach($gallerylist as $gallery) {
		$class = ( $class == 'class="alternate"' ) ? '' : 'class="alternate"';
		$gid = $gallery->gid;
		$counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpictures WHERE galleryid = '$gid'");
		$author_user = get_userdata( (int) $gallery->author );
		?>
		<tr id="gallery-<?php echo $gid ?>" <?php echo $class; ?> >
			<th scope="row"><?php echo $gid; ?></th>
			<td>
				<?php if(nggAdmin::can_manage_this_gallery($gallery->author)) { ?>
					<a href="<?php echo wp_nonce_url("admin.php?page=nggallery-manage-gallery&amp;mode=edit&amp;gid=".$gid, 'ngg_editgallery')?>" class='edit' title="<?php _e('Edit') ?>" >
						<?php echo $gallery->title; ?>
					</a>
				<?php } else { ?>
					<?php echo $gallery->title; ?>
				<?php } ?>
			</td>
			<td><?php echo $gallery->galdesc; ?></td>
			<td><?php echo $author_user->display_name; ?></td>
			<td><?php echo $gallery->pageid; ?></td>
			<td><?php echo $counter; ?></td>
			<td>
				<?php if(nggAdmin::can_manage_this_gallery($gallery->author)) : ?>
					<a href="<?php echo wp_nonce_url("admin.php?page=nggallery-manage-gallery&amp;mode=delete&amp;gid=".$gid, 'ngg_editgallery')?>" class="delete" onclick="javascript:check=confirm( '<?php _e("Delete this gallery ?",'nggallery')?>');if(check==false) return false;"><?php _e('Delete') ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
} else {
	echo '<tr><td colspan="7" align="center"><strong>'.__('No entries found','nggallery').'</strong></td></tr>';
}
?>			
			</tbody>
		</table>
	</div>
<?php
} //nggallery_manage_gallery_main

function nggallery_picturelist($hideThumbs = false,$showTags = false) {
// *** show picture list
	global $wpdb, $user_ID, $ngg;
	
	// GET variables
	$act_gid = (int) $_GET['gid'];
	
	// get gallery values
	$act_gallery = nggGalleryDAO::find_gallery($act_gid);
	
	if ($act_gallery == null) {
		nggGalleryPlugin::show_error(__('Gallery not found.', 'nggallery'));
		return;
	}

	//TODO:Redundant, Redundant, Redundant... REWORK
	// set gallery url
	$act_gallery_url 	= get_option ('siteurl')."/".$act_gallery->path."/";
	$act_thumbnail_url 	= get_option ('siteurl')."/".$act_gallery->path.nggGalleryPlugin::get_thumbnail_folder($act_gallery->path, FALSE);
	$act_thumb_prefix   = "thumbs_" ;
	$act_thumb_abs_src	= WINABSPATH.$act_gallery->path.nggGalleryPlugin::get_thumbnail_folder($act_gallery->path, FALSE);
	$act_author_user    = get_userdata( (int) $act_gallery->author );
?>

<script type="text/javascript"> 
	function showDialog( windowId ) {
		var form = document.getElementById('updategallery');
		var elementlist = "";
		for (i = 0, n = form.elements.length; i < n; i++) {
			if(form.elements[i].type == "checkbox") {
				if(form.elements[i].name == "doaction[]")
					if(form.elements[i].checked == true)
						if (elementlist == "")
							elementlist = form.elements[i].value
						else
							elementlist += "," + form.elements[i].value ;
			}
		}
		jQuery("#" + windowId + "_bulkaction").val(jQuery("#bulkaction").val());
		jQuery("#" + windowId + "_imagelist").val(elementlist);
		// console.log (jQuery("#TB_imagelist").val());
		tb_show("", "#TB_inline?width=640&height=120&inlineId=" + windowId + "&modal=true", false);
	}
</script>
<script type="text/javascript">
<!--
function checkAll(form)
{
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox") {
			if(form.elements[i].name == "doaction[]") {
				if(form.elements[i].checked == true)
					form.elements[i].checked = false;
				else
					form.elements[i].checked = true;
			}
		}
	}
}

function getNumChecked(form)
{
	var num = 0;
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox") {
			if(form.elements[i].name == "doaction[]")
				if(form.elements[i].checked == true)
					num++;
		}
	}
	return num;
}

// this function checl for a the number of seleted images, sumbmit false when no one selected
function checkSelected() {

	var numchecked = getNumChecked(document.getElementById('updategallery'));
	 
	if(numchecked < 1) { 
		alert('<?php echo js_escape(__("No images selected",'nggallery')); ?>');
		return false; 
	} 
	//TODO: For copy to and move to we need some better way around
	if (jQuery('#bulkaction').val() == 9) {
		showDialog('selectgallery');
		return false;
	}
	
	if (jQuery('#bulkaction').val() == 10) {
		showDialog('selectgallery');
		return false;
	}
		
	return confirm('<?php echo sprintf(js_escape(__("You are about to start the bulk edit for %s images \n \n 'Cancel' to stop, 'OK' to proceed.",'nggallery')), "' + numchecked + '") ; ?>');
}

jQuery(document).ready( function() {
	// close postboxes that should be closed
	jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
	// postboxes
	add_postbox_toggles('ngg-manage-gallery');
});

//-->
</script>

<div class="wrap">

<h2><?php _e('Gallery', 'nggallery') ?> : <?php echo $act_gallery->title; ?></h2>

<br style="clear: both;"/>

<form id="updategallery" class="nggform" method="POST" action="<?php echo 'admin.php?page=nggallery-manage-gallery&amp;mode=edit&amp;gid='.$act_gid ?>" accept-charset="utf-8">
<?php wp_nonce_field('ngg_updategallery') ?>

<?php if ($showTags) { ?><input type="hidden" name="showTags" value="true" /><?php } ?>
<?php if ($hideThumbs) { ?><input type="hidden" name="hideThumbs" value="true" /><?php } ?>
<div id="poststuff">
	<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
	<div id="gallerydiv" class="postbox <?php echo postbox_classes('gallerydiv', 'ngg-manage-gallery'); ?>" >
		<h3><?php _e('Gallery settings', 'nggallery') ?></h3>
		<div class="inside">
			<table class="form-table" >
				<tr>
					<th align="left"><?php _e('Title') ?>:</th>
					<th align="left"><input type="text" size="50" name="title" value="<?php echo $act_gallery->title; ?>"  /></th>
					<th align="right"><?php _e('Page Link to', 'nggallery') ?>:</th>
					<th align="left">
					<select name="pageid" style="width:95%">
						<option value="0" ><?php _e('Not linked', 'nggallery') ?></option>
					<?php
						$pageids = get_all_page_ids();
						foreach($pageids as $pageid) {
							$post= get_post($pageid); 				
							if ($pageid == $act_gallery->pageid) $selected = 'selected="selected" ';
							else $selected = '';
							echo '<option value="'.$pageid.'" '.$selected.'>'.$post->post_title.'</option>'."\n";
						}
					?>
					</select>
					</th>
				</tr>
				<tr>
					<th align="left"><?php _e('Description') ?>:</th> 
					<th align="left"><textarea name="gallerydesc" cols="30" rows="3" style="width: 95%"  ><?php echo $act_gallery->galdesc; ?></textarea></th>
					<th align="right"><?php _e('Preview image', 'nggallery') ?>:</th>
					<th align="left">
						<select name="previewpic" >
							<option value="0" ><?php _e('No Picture', 'nggallery') ?></option>
							<?php
								$picturelist = nggImageDAO::find_images_in_gallery($act_gallery, $ngg->options['galSort'], $ngg->options['galSortDir']);
								if(is_array($picturelist)) {
									foreach($picturelist as $picture) {
										if ($picture->pid == $act_gallery->previewpic) $selected = 'selected="selected" ';
										else $selected = '';
										echo '<option value="'.$picture->pid.'" '.$selected.'>'.$picture->pid.' - '.$picture->filename.'</option>'."\n";
									}
								}
							?>
						</select>
					</th>
				</tr>
				<tr>
					<th align="left"><?php _e('Path', 'nggallery') ?>:</th> 
					<th align="left"><input <?php if (IS_WPMU) echo 'readonly = "readonly"'; ?> type="text" size="50" name="path" value="<?php echo $act_gallery->path; ?>"  /></th>
					<th align="right"><?php _e('Author', 'nggallery'); ?>:</th>
					<th align="left"> 
					<?php
						$editable_ids = ngg_get_editable_user_ids( $user_ID );
						if ( $editable_ids && count( $editable_ids ) > 1 )
							wp_dropdown_users( array('include' => $editable_ids, 'name' => 'author', 'selected' => empty( $act_gallery->author ) ? 0 : $act_gallery->author ) ); 
						else
							echo $act_author_user->display_name;
					?>
					</th>
				</tr>
				<tr>
					<th align="left">&nbsp;</th>
					<th align="left">&nbsp;</th>				
					<th align="right"><?php _e('Create new page', 'nggallery') ?>:</th>
					<th align="left"> 
					<select name="parent_id" style="width:95%">
						<option value="0"><?php _e ('Main page (No parent)', 'nggallery'); ?></option>
						<?php parent_dropdown ($group->page_id); ?>
					</select>
					<input type="submit" name="addnewpage" value="<?php _e ('Add page', 'nggallery'); ?>" id="group"/>
					</th>
				</tr>
			</table>
			
			<div class="submit">
				<input type="submit" name="scanfolder" value="<?php _e("Scan Folder for new images",'nggallery')?> " />
				<input type="submit" name="updatepictures" value="<?php _e("Save Changes",'nggallery')?> &raquo;" />
			</div>

		</div>
	</div>
</div> <!-- poststuff -->

<div class="tablenav ngg-tablenav">
	<div style="float: left;">
	<select id="bulkaction" name="bulkaction">
		<option value="0" ><?php _e("No action",'nggallery')?></option>
	<?php if (!$showTags) { ?>
		<option value="1" ><?php _e("Set watermark",'nggallery')?></option>
		<option value="2" ><?php _e("Create new thumbnails",'nggallery')?></option>
		<option value="3" ><?php _e("Resize images",'nggallery')?></option>
		<option value="4" ><?php _e("Delete images",'nggallery')?></option>
		<option value="8" ><?php _e("Import metadata",'nggallery')?></option>
		<option value="9" ><?php _e("Copy to...",'nggallery')?></option>
		<option value="10"><?php _e("Move to...",'nggallery')?></option>
	<?php } else { ?>	
		<option value="5" ><?php _e("Add tags",'nggallery')?></option>
		<option value="6" ><?php _e("Delete tags",'nggallery')?></option>
		<option value="7" ><?php _e("Overwrite tags",'nggallery')?></option>
	<?php } ?>	
	</select>
	
	<?php if (!$showTags) { ?> 
		<input class="button-secondary" type="submit" name="doaction" value="<?php _e("OK",'nggallery')?>" onclick="if ( !checkSelected() ) return false;" />
	<?php } else {?>
		<input class="button-secondary" type="submit" name="showThickbox" value="<?php _e("OK",'nggallery')?>" onclick="showDialog('tags'); return false;" />
	<?php } ?>
	
	<?php if (!$hideThumbs) { ?> 
		<input class="button-secondary" type="submit" name="togglethumbs" value="<?php _e("Hide thumbnails ",'nggallery')?>" /> 
	<?php } else {?>
		<input class="button-secondary" type="submit" name="togglethumbs" value="<?php _e("Show thumbnails ",'nggallery')?>" />
	<?php } ?>
	
	<?php if (!$showTags) { ?>
		<input class="button-secondary" type="submit" name="toggletags" value="<?php _e("Show tags",'nggallery')?>" /> 
	<?php } else {?>
		<input class="button-secondary" type="submit" name="toggletags" value="<?php _e("Hide tags",'nggallery')?>" />
	<?php } ?>
	
	<?php if ($ngg->options['galSort'] == "sortorder") { ?>
		<input class="button-secondary" type="submit" name="sortGallery" value="<?php _e("Sort gallery",'nggallery')?>" />
	<?php } ?>

	</div>
	<span style="float:right;"><input type="submit" name="updatepictures" class="button-secondary"  value="<?php _e("Save Changes",'nggallery')?> &raquo;" /></span>
</div>
<br style="clear: both;"/>
<table id="ngg-listimages" class="widefat" >
	<thead>
	<tr>
		<?php $gallery_columns = ngg_manage_gallery_columns(); ?>
		<?php foreach($gallery_columns as $gallery_column_key => $column_display_name) {
			switch ($gallery_column_key) {
				case 'cb' :
					$class = ' class="check-column"';
				break;
				case 'tags' :
					$class = ' style="width:70%"';
				break;
				case 'action' :
					$class = ' colspan="3" style="text-align: center"';
				break;
				default : 
					$class = ' style="text-align: center"';
			}
		?>
			<th scope="col"<?php echo $class; ?>><?php echo $column_display_name; ?></th>
		<?php } ?>
	</tr>
	</thead>
	<tbody>
<?php
if($picturelist) {
	
	$thumbsize = "";
	if ($ngg->options['thumbfix']) {
		$thumbsize = 'width="'.$ngg->options['thumbwidth'].'" height="'.$ngg->options['thumbheight'].'"';
	}
	
	if ($ngg->options['thumbcrop']) {
		$thumbsize = 'width="'.$ngg->options['thumbwidth'].'" height="'.$ngg->options['thumbwidth'].'"';
	}
		
	foreach($picturelist as $picture) {

		$pid     = $picture->pid;
		$class   = ( $class == 'class="alternate"' ) ? '' : 'class="alternate"';	
		$exclude = ( $picture->exclude ) ? 'checked="checked"' : '';
		
		?>
		<tr id="picture-<?php echo $pid ?>" <?php echo $class ?> style="text-align:center">
			<?php foreach($gallery_columns as $gallery_column_key => $column_display_name) {
				switch ($gallery_column_key) {
					case 'cb' :
						?> 
						<td class="check-column" scope="row"><input name="doaction[]" type="checkbox" value="<?php echo $pid ?>" /></td>
						<?php
					break;
					case 'id' :
						?>
						<td scope="row" style="text-align: center"><?php echo $pid ?></td>
						<?php
					break;
					case 'filename' :
						?>
						<td class="media-icon" style="text-align: left;">
							<a href="<?php echo $picture->imageURL; ?>" class="thickbox" title="<?php echo $picture->filename ?>">
								<?php echo $picture->filename ?>
							</a>
						</td>
						<?php						
					break;
					case 'thumbnail' :
						?>
						<td><a href="<?php echo $picture->imageURL; ?>" class="thickbox" title="<?php echo $picture->filename ?>">
								<img class="thumb" src="<?php echo $picture->thumbURL; ?>" <?php echo $thumbsize ?> />
							</a>
						</td>
						<?php						
					break;
					case 'desc_alt_title' :
						?>
						<td style="width:500px">
							<input name="alttext[<?php echo $pid ?>]" type="text" style="width:95%; margin-bottom: 2px;" value="<?php echo stripslashes($picture->alttext) ?>" /><br/>
							<textarea name="description[<?php echo $pid ?>]" style="width:95%; margin-top: 2px;" rows="2" ><?php echo stripslashes($picture->description) ?></textarea>
						</td>
						<?php						
					break;
					case 'description' :
						?>
						<td><textarea name="description[<?php echo $pid ?>]" class="textarea1" cols="42" rows="2" ><?php echo stripslashes($picture->description) ?></textarea></td>
						<?php						
					break;
					case 'alt_title_text' :
						?>
						<td><input name="alttext[<?php echo $pid ?>]" type="text" size="30" value="<?php echo stripslashes($picture->alttext) ?>" /></td>
						<?php						
					break;
					case 'exclude' :
						?>
						<td><input name="exclude[<?php echo $pid ?>]" type="checkbox" value="1" <?php echo $exclude ?> /></td>
						<?php						
					break;
					case 'tags' :
						$picture->tags = wp_get_object_terms($pid, 'ngg_tag', 'fields=names');
						if (is_array ($picture->tags) ) $picture->tags = implode(', ', $picture->tags); 
						?>
						<td style="width:500px"><textarea name="tags[<?php echo $pid ?>]" style="width:95%;" rows="2"><?php echo $picture->tags ?></textarea></td>
						<?php						
					break;
					case 'action' :
						?>
						<td><a href="<?php echo NGGALLERY_URLPATH."admin/showmeta.php?id=".$pid ?>" class="thickbox" title="<?php _e("Show Meta data",'nggallery')?>" ><?php _e('Meta') ?></a></td>
						<td><a href="<?php echo wp_nonce_url("admin.php?page=nggallery-manage-gallery&amp;mode=delpic&amp;gid=".$act_gid."&amp;pid=".$pid, 'ngg_delpicture')?>" class="delete" onclick="javascript:check=confirm( '<?php _e("Delete this file ?",'nggallery')?>');if(check==false) return false;" ><?php _e('Delete') ?></a></td>
						<?php
					break;					
					default : 
						?>
						<td><?php do_action('ngg_manage_gallery_custom_column', $gallery_column_key, $pid); ?></td>
						<?php
					break;
				}
			?>
			<?php } ?>
		</tr>
		<?php
	}
} else {
	echo '<tr><td colspan="8" align="center"><strong>'.__('No entries found','nggallery').'</strong></td></tr>';
}
?>
	
		</tbody>
	</table>
	<p class="submit"><input type="submit" name="updatepictures" value="<?php _e("Save Changes",'nggallery')?> &raquo;" /></p>
	</form>	
	<br class="clear"/>
	</div><!-- /#wrap -->

	<!-- #entertags -->
	<div id="tags" style="display: none;" >
		<form id="form-tags" method="POST" accept-charset="utf-8">
		<?php wp_nonce_field('ngg_thickbox_form') ?>
		<?php if ($showTags) { ?><input type="hidden" name="showTags" value="true" /><?php } ?>
		<?php if ($hideThumbs) { ?><input type="hidden" name="hideThumbs" value="true" /><?php } ?>
		<input type="hidden" id="tags_imagelist" name="TB_imagelist" value="" />
		<input type="hidden" id="tags_bulkaction" name="TB_bulkaction" value="" />
		<table width="100%" border="0" cellspacing="3" cellpadding="3" >
		  	<tr>
		    	<th><?php _e("Enter the tags",'nggallery')?> : <input name="taglist" type="text" style="width:99%" value="" /></th>
		  	</tr>
		  	<tr align="right">
		    	<td class="submit">
		    		<input type="submit" name="TB_EditTags" value="<?php _e("OK",'nggallery')?>" onclick="var numchecked = getNumChecked(document.getElementById('updategallery')); if(numchecked < 1) { alert('<?php echo js_escape(__("No images selected",'nggallery')); ?>'); tb_remove(); return false } return confirm('<?php echo sprintf(js_escape(__("You are about to start the bulk edit for %s images \n \n 'Cancel' to stop, 'OK' to proceed.",'nggallery')), "' + numchecked + '") ; ?>')" />
		    		&nbsp;
		    		<input type="reset" value="&nbsp;<?php _e("Cancel",'nggallery')?>&nbsp;" onclick="tb_remove()"/>
		    	</td>
			</tr>
		</table>
		</form>
	</div>
	<!-- /#entertags -->

	<!-- #selectgallery -->
	<div id="selectgallery" style="display: none;" >
		<form id="form-select-gallery" method="POST" accept-charset="utf-8">
		<?php wp_nonce_field('ngg_thickbox_form') ?>
		<?php if ($showTags) { ?><input type="hidden" name="showTags" value="true" /><?php } ?>
		<?php if ($hideThumbs) { ?><input type="hidden" name="hideThumbs" value="true" /><?php } ?>
		<input type="hidden" id="selectgallery_imagelist" name="TB_imagelist" value="" />
		<input type="hidden" id="selectgallery_bulkaction" name="TB_bulkaction" value="" />
		<table width="100%" border="0" cellspacing="3" cellpadding="3" >
		  	<tr>
		    	<th>
		    		<?php 
		    			_e("Select the destination gallery:", 'nggallery');
		    			$gallerylist = nggGalleryDAO::find_all_galleries();
		    		?>&nbsp;
		    		<select name="dest_gid" style="width:95%" >
		    			<?php 
		    				foreach ($gallerylist as $gallery) { 
		    					if ($gallery->gid != $act_gid) { 
		    			?>
						<option value="<?php echo $gallery->gid; ?>" ><?php echo $gallery->gid; ?> - <?php echo stripslashes($gallery->name); ?></option>
						<?php 
		    					} 
		    				}
		    			?>
		    		</select>
		    	</th>
		  	</tr>
		  	<tr align="right">
		    	<td class="submit">
		    		<input type="submit" name="TB_SelectGallery" value="<?php _e("OK",'nggallery')?>" onclick="var numchecked = getNumChecked(document.getElementById('updategallery')); if(numchecked < 1) { alert('<?php echo js_escape(__("No images selected",'nggallery')); ?>'); tb_remove(); return false } return confirm('<?php echo sprintf(js_escape(__("You are about to copy or move %s images \n \n 'Cancel' to stop, 'OK' to proceed.",'nggallery')), "' + numchecked + '") ; ?>')" />
		    		&nbsp;
		    		<input type="reset" value="<?php _e("Cancel",'nggallery')?>" onclick="tb_remove()"/>
		    	</td>
			</tr>
		</table>
		</form>
	</div>
	<!-- /#selectgallery -->

	<?php
			
} //nggallery_pciturelist

/**************************************************************************/
function ngg_update_pictures( $nggdescription, $nggalttext, $nggexclude, $nggalleryid ) {
// update all pictures
	
	global $wpdb;
	
	if (is_array($nggdescription)) {
		foreach($nggdescription as $key=>$value) {
			$desc = $wpdb->escape($value);
			$result=$wpdb->query( "UPDATE $wpdb->nggpictures SET description = '$desc' WHERE pid = $key");
			if($result) $update_ok = $result;
		}
	}
	if (is_array($nggalttext)){
		foreach($nggalttext as $key=>$value) {
			$alttext = $wpdb->escape($value);
			$result=$wpdb->query( "UPDATE $wpdb->nggpictures SET alttext = '$alttext' WHERE pid = $key");
			if($result) $update_ok = $result;
		}
	}
	
	$nggpictures = $wpdb->get_results("SELECT pid FROM $wpdb->nggpictures WHERE galleryid = '$nggalleryid'");

	if (is_array($nggpictures)){
		foreach($nggpictures as $picture){
			if (is_array($nggexclude)){
				if (array_key_exists($picture->pid, $nggexclude)) {
					$result=$wpdb->query("UPDATE $wpdb->nggpictures SET exclude = 1 WHERE pid = '$picture->pid'");
					if($result) $update_ok = $result;
				} 
				else {
					$result=$wpdb->query("UPDATE $wpdb->nggpictures SET exclude = 0 WHERE pid = '$picture->pid'");
					if($result) $update_ok = $result;
				}
			} else {
				$result=$wpdb->query("UPDATE $wpdb->nggpictures SET exclude = 0 WHERE pid = '$picture->pid'");
				if($result) $update_ok = $result;
			}   
		}
	}
	
	return $update_ok;
}

/**************************************************************************/
function ngg_update_tags( $taglist ) {
// update all tags

	if (is_array($taglist)){
		foreach($taglist as $key=>$value) {
			$tags = explode(",", $value);
			wp_set_object_terms($key, $tags, 'ngg_tag');
		}
	}

	return;

}

// Check if user can select a author
function ngg_get_editable_user_ids( $user_id, $exclude_zeros = true ) {
	global $wpdb;

	$user = new WP_User( $user_id );

	if ( ! $user->has_cap('NextGEN Manage others gallery') ) {
		if ( $user->has_cap('NextGEN Manage gallery') || $exclude_zeros == false )
			return array($user->id);
		else
			return false;
	}

	$level_key = $wpdb->prefix . 'user_level';
	$query = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '$level_key'";
	if ( $exclude_zeros )
		$query .= " AND meta_value != '0'";

	return $wpdb->get_col( $query );
}

function ngg_hide_thumb() {
	if (isset ($_POST['togglethumbs']))  {
		check_admin_referer('ngg_updategallery');
	// Toggle thumnails, forgive me if it's to complicated
		$hideThumbs = (isset ($_POST['hideThumbs'])) ?  false : true ;
	} else {
		$hideThumbs = (isset ($_POST['hideThumbs'])) ?  true : false ;
	}
	return $hideThumbs;	
}

function ngg_show_tags() {
	if (isset ($_POST['toggletags']))  {
		check_admin_referer('ngg_updategallery');
	// Toggle tag view
		$showTags = (isset ($_POST['showTags'])) ?  false : true ;
	} else {
		$showTags = (isset ($_POST['showTags'])) ?  true : false ;
	}
	return $showTags;	
}

// define the columns to display, the syntax is 'internal name' => 'display name'
function ngg_manage_gallery_columns() {
	$gallery_columns = array();
	
	$gallery_columns['cb'] = '<input name="checkall" type="checkbox" onclick="checkAll(document.getElementById(\'updategallery\'));" />';
	$gallery_columns['id'] = __('ID');
	
	if ( !ngg_hide_thumb() ) {
		$gallery_columns['thumbnail'] = __('Thumbnail', 'nggallery');
	} else {
		$gallery_columns['filename'] = __('File name', 'nggallery');
	}
	
	if ( !ngg_show_tags() )	{
		$gallery_columns['desc_alt_title'] = __('Description', 'nggallery') . '/' . __('Alt &amp; Title Text', 'nggallery');
		// $gallery_columns['description'] = __('Description', 'nggallery');
		// $gallery_columns['alt_title_text'] = __('Alt &amp; Title Text', 'nggallery');
		$gallery_columns['exclude'] = __('exclude', 'nggallery');
	} else {
		$gallery_columns['tags'] = __('Tags (comma separated list)', 'nggallery');
	}
	$gallery_columns['action'] 	= __('Action', 'nggallery');
	
	$gallery_columns = apply_filters('ngg_manage_gallery_columns', $gallery_columns);

	return $gallery_columns;
}
?>
