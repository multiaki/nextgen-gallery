<?php
/*
* Widgets registration
* 
* @author Vincent Prat
*/

require_once(dirname (__FILE__) . '/media-rss-widget.php');

$ngg_mrss_widget = new nggMediaRssWidget();
add_action('widgets_init', array(&$ngg_mrss_widget, 'register_widget'));


?>
