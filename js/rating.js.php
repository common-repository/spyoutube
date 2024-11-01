<?php
if (!function_exists('add_action')){
	require_once(dirname(__FILE__).'/../../../../wp-config.php');
}
?>
function submitRating(rate, videoId){
	var endRate = rate+1;
	$.post('/wp-content/plugins/spYouTube/ajax.php', { ajaxaction: 'rating', rate: endRate, video: videoId }, function(data){
		$('#ratingText-'+videoId).html(data);
	});
}