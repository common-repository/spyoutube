<?php
if (!function_exists('add_action')){
	require_once(dirname(__FILE__).'/../../../wp-config.php');
}
if($_POST['ajaxaction'] == 'rating'){
	hdYouTube::Rate($_POST['rate'],$_POST['video']);
}