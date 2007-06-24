<?php
function ngg_upload_tab_content() {
	// this is the content of the "Gallery" admin tab 
	global $ID, $wpdb , $style, $action ;
	$ngg_options = get_option('ngg_options');

	// select gallery id
	if (isset($_GET['select_gal'])){
		$galleryID = $_GET['select_gal'];
	} else {
		$galleryID = $_POST['from_gal'];
	}

 	// check for page navigation
	$page = (int) $_GET['paged'];
	$page = ($page == 0 ) ? 1 : $page;
 	$start = ( $page - 1 ) * 10;

	if (($action == "update") || ($action == "delete")) {
	
	// if update/delete pressed
		if ( isset($_POST['delete']) || ($action == "delete") ) {
			//TODO: Write a class function "Delete picture"
			$picture = $wpdb->get_row("SELECT * FROM $wpdb->nggpictures WHERE pid = '$ID' ");
			if ($picture) {
				if ($ngg_options[deleteImg]) {
					$gallerypath = $wpdb->get_var("SELECT path FROM $wpdb->nggallery WHERE gid = '$picture->galleryid' ");
					if ($gallerypath) {
						$thumb_folder = ngg_get_thumbnail_folder($gallerypath, FALSE);
						$thumb_prefix = ngg_get_thumbnail_prefix($gallerypath, FALSE);
						unlink(WINABSPATH.$gallerypath.'/'.$thumb_folder.'/'.$thumb_prefix.$picture->filename);
						unlink(WINABSPATH.$gallerypath.'/'.$picture->filename);	
					}
				} 
				$result = $wpdb->query("DELETE FROM $wpdb->nggpictures WHERE pid = $ID");
			}
		}
		
		if ( isset($_POST['save']) ) {
			$img_title   = attribute_escape($_POST[image_title]);
			$img_desc    = attribute_escape($_POST[image_desc]);
			$result = $wpdb->query("UPDATE $wpdb->nggpictures SET alttext= '$img_title', description = '$img_desc' WHERE pid = '$ID'");
		}
	}
	
	if (($action == "edit") || ($action == "view")) {
		( $style == 'inline' ) ? ngg_admintab_insert_pic() : ngg_image_edit();			
		return;
	}	

	?>
	<script type="text/javascript"> var tb_pathToImage = '<?php echo NGGALLERY_URLPATH ?>thickbox/loadingAnimationv3.gif';</script>
	<style type="text/css" media="all">@import "<?php echo NGGALLERY_URLPATH ?>thickbox/thickbox.css";</style>

	<form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="GET" id="ngg-gallery" style="padding-top:10px;">
	<input type="hidden" name="tab" value="<?php echo$_GET['tab']?>" />
	<input type="hidden" name="post_id" value="<?php echo$_GET['post_id']?>" />
	<input type="hidden" name="action" value="<?php echo$_GET['action']?>" />
	<input type="hidden" name="style" value="<?php echo$_GET['style']?>" />
	<input type="hidden" name="_wpnonce" value="<?php echo$_GET['_wpnonce']?>" />
	<input type="hidden" name="ID" value="<?php echo$_GET['ID']?>" />
	<div id="select-gallery"><?php _e('Select a gallery',"nggallery"); ?> :
	<select id="select_gal" name="select_gal" onchange="this.form.submit();">';
		<option value="0" ><?php _e('No gallery',"nggallery"); ?></option>
	<?php
	$gallerylist = $wpdb->get_results("SELECT * FROM $wpdb->nggallery ORDER BY gid ASC");
	if(is_array($gallerylist)) {
		foreach($gallerylist as $gallery) {
			echo '<option value="'.$gallery->gid.'" >'.$gallery->name.' | '.$gallery->title.'</option>'."\n";
		}
	}
	?>
		</select>
	</form>
	</div>
	<ul id="upload-files">
	<?php	  
	$picarray = $wpdb->get_col("SELECT pid FROM $wpdb->nggpictures WHERE galleryid = '$galleryID' AND exclude != 1 ORDER BY $ngg_options[galSort] $ngg_options[galSortDir] LIMIT $start, 10 ");	
	if($picarray) {
		foreach ($picarray as $picid) {
			
			$picture = $wpdb->get_row("SELECT * FROM $wpdb->nggpictures WHERE pid = '$picid'");
			$imagesrc = ngg_get_image_url($picid);
			$thumbsrc = ngg_get_thumbnail_url($picid);
			$href = add_query_arg( array('action' => $style == 'inline' ? 'view' : 'edit', 'ID' => $picid, 'select_gal' => $galleryID));
				
			echo '<li id="file-'.$picid.'" class="alignleft">';
			echo '<a class="file-link image" title="'.$picture->filename.'" href="'.$href.'" id="file-link-'.$picid.'">
				 <img alt="'.$picture->alttext.'" title="'.$picture->alttext.'" src="'.$thumbsrc.'"/></a>';
			echo '</li>';
			echo '
			<div class="upload-file-data">
			<p>
			<input id="nggimage-url-'.$picid.'" type="hidden" value="'.$imagesrc.'" name="nggimage-url-'.$picid.'"/>
			<input id="nggimage-thumb-url-'.$picid.'" type="hidden" value="'.$thumbsrc.'" name="nggimage-thumb-url-'.$picid.'"/>
			<input id="nggimage-width-'.$picid.'" type="hidden" value="170" name="nggimage-width-'.$picid.'"/>
			<input id="nggimage-height-'.$picid.'" type="hidden" value="128" name="nggimage-height-'.$picid.'"/>
			<input id="nggimage-title-'.$picid.'" type="hidden" value="'.$picture->filename.'" name="nggimage-title-'.$picid.'"/>
			<input id="nggimage-alttext-'.$picid.'" type="hidden" value="'.$picture->alttext.'" name="nggimage-alttext-'.$picid.'"/>
			<input id="nggimage-description-'.$picid.'" type="hidden" value="'.stripslashes($picture->description).'" name="nggimage-description-'.$picid.'"/>
			</p>
			</div>';
		}
	}	
	echo '</ul>';
	
}

function ngg_image_edit() {
	global $ID, $wpdb, $post_id, $tab, $style;
	
	$picture = $wpdb->get_row("SELECT * FROM $wpdb->nggpictures WHERE pid = '$ID'");
	$image_src = ngg_get_image_url($ID);
	$thumb_src = ngg_get_thumbnail_url($ID);

	?>
	<script type="text/javascript"> var tb_pathToImage = '<?php echo NGGALLERY_URLPATH ?>thickbox/loadingAnimationv3.gif';</script>
	<style type="text/css" media="all">@import "<?php echo NGGALLERY_URLPATH ?>thickbox/thickbox.css";</style>
	<form id="upload-file" method="post" action="<?php echo get_option('siteurl') . "/wp-admin/upload.php?style=$style&amp;tab=$tab&amp;post_id=$post_id"; ?>">
		<div id="file-title" style="padding-top:10px;">
			<h2><?php echo $picture->filename; ?></h2>
		</div>
		<div id="upload-file-view" class="alignleft">
		<?php echo '<a title="'.$picture->alttext.'" href="'.$image_src.'" class="thickbox"><img alt="'.$picture->alttext.'" title="'.$picture->alttext.'" src="'.$thumb_src.'"/></a>'; ?>
		</div>
		<table><col /><col class="widefat" />
			<tr>
				<th scope="row"><label for="url"><?php _e('URL',"nggallery"); ?></label></th>
				<td><input type="text" id="url" class="readonly" value="<?php echo $image_src ?>" readonly="readonly" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="image_title"><?php _e('Alt &amp; Title Text',"nggallery"); ?></label></th>
				<td><input type="text" id="image_title" name="image_title" value="<?php echo $picture->alttext; ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="image_desc"><?php _e('Description',"nggallery"); ?></label></th>
				<td><textarea name="image_desc" id="image_desc"><?php echo stripslashes($picture->description); ?></textarea></td>
			</tr>
			<tr id="buttons" class="submit">
				<td colspan='2'>
					<input type="submit" name="delete" id="delete" class="delete alignleft" value="<?php _e('Delete File'); ?>" onclick="javascript:check=confirm('<?php _e('Delete image ?','nggallery'); ?>');if(check==false) return false;" />
					<input type="hidden" name="from_tab" value="<?php echo $tab; ?>" />
					<input type="hidden" name="action" value="update" />
					<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
					<input type="hidden" name="from_gal" value="<?php echo $_GET['select_gal'] ?>" />
					<input type="hidden" name="ID" value="<?php echo $ID; ?>" />
					<?php wp_nonce_field( 'inlineuploading' ); ?>
					<div class="submit">
						<input type="submit" name="save" id="save" value="<?php _e('Save') ?> &raquo;" />
					</div>
				</td>
			</tr>
		</table>
	</form>	
<?php	
}

function ngg_admintab_insert_pic() {
	//TODO: define tb_pathToImage
	?>
	<script type="text/javascript"> var tb_pathToImage = '<?php echo NGGALLERY_URLPATH ?>thickbox/loadingAnimationv3.gif';</script>
	<style type="text/css" media="all">@import "<?php echo NGGALLERY_URLPATH ?>thickbox/thickbox.css";</style>
	<?php
}

?>
