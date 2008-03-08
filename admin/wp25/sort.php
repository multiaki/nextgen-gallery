<?php

/**
 * @author Alex Rabe
 * @copyright 2008
 */

function nggallery_sortorder($galleryID = 0){
	global $wpdb;
	
	if ($galleryID == 0) return;
	
	// get the options
	$ngg_options=get_option('ngg_options');	
	
	//TODO:A unique gallery call must provide me with this information, like $gallery  = new nggGallery($id);
	
	// get gallery values
	$act_gallery = $wpdb->get_row("SELECT * FROM $wpdb->nggallery WHERE gid = '$galleryID' ");

	// set gallery url
	$act_gallery_url 	= get_option ('siteurl')."/".$act_gallery->path."/";
	$act_thumbnail_url 	= get_option ('siteurl')."/".$act_gallery->path.nggallery::get_thumbnail_folder($act_gallery->path, FALSE);
	$act_thumb_prefix   = nggallery::get_thumbnail_prefix($act_gallery->path, FALSE);

	$picturelist = $wpdb->get_results("SELECT * FROM $wpdb->nggpictures WHERE galleryid = '$galleryID' ORDER BY $ngg_options[galSort] $ngg_options[galSortDir]");

?>
	<script type='text/javascript' src='<?php echo NGGALLERY_URLPATH ?>admin/js/sorter.js'></script>
	<div class="wrap">
		<div class="bordertitle">
			<h2 style="border: medium none ; padding-bottom: 0px;"><?php _e('Sort Gallery', 'nggallery') ?></h2>
			<form id="sortGallery" method="POST" onsubmit="saveImageOrder()" accept-charset="utf-8">
				<input name="sortorder" type="hidden" />
				<input class="button" type="submit" name="update" onclick="saveImageOrder()" value="<?php _e('Update Sort Order') ?> &raquo;" />
			</form>
		</div>
		<div id="debug" style="clear:both"></div>
		<?php 
		if($picturelist) {
			foreach($picturelist as $picture) {
				?>
				<div class="imageBox" id="image[<?php echo $picture->pid ?>]">
					<div class="imageBox_theImage" style="background-image:url('<?php echo $act_thumbnail_url.$act_thumb_prefix.$picture->filename ?>')"></div>	
					<div class="imageBox_label"><span><?php echo $picture->alttext ?></span></div>
				</div>
				<?php
			}
		}
		?>
		<div id="insertionMarker">
			<img src="<?php echo NGGALLERY_URLPATH ?>admin/images/marker_top.gif"/>
			<img src="<?php echo NGGALLERY_URLPATH ?>admin/images/marker_middle.gif" id="insertionMarkerLine"/>
			<img src="<?php echo NGGALLERY_URLPATH ?>admin/images/marker_bottom.gif"/>
		</div>
		<div id="dragDropContent"></div>
	</div>
	
<?php

}

?>