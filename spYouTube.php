<?php
/*
 Plugin Name: spYouTube
 Plugin URI: http://www.scriptpara.de/skripte/spyoutube/
 Description: Managing your YouTube videos in your wp-admin
 Author: Sebastian Klaus
 Version: 0.6
 Author URI: http://www.scriptpara.de
 */

@ini_set('max_execution_time', 0);
@ini_set('upload_max_filesize', '500M');
@ini_set('post_max_size', '500M');
@ini_set('memory_limit', '500M');

// init the plugin
add_action('init', 'spYouTubeInit');

$settings = spYouTubeGetSettings();

// init
function spYouTubeInit() {
	// incluce the main class hdYouTube
	require_once('hdYouTube.php');

	// load language
	load_plugin_textdomain('spYouTube','/wp-content/plugins/spyoutube/languages/');

	// Create a master category for newsletter and its sub-pages
	add_action('admin_menu', 'spYouTubeMenu');

	// Add entry to config file
	add_option('spYouTube','', 'spYouTube settings');

	add_shortcode('video', 'spYouTubeShowVideo');
	add_shortcode('allvideos', 'spYouTubeShowAllVideos');

	include(dirname(__FILE__).'/media-upload.php');
}

function spYouTubeShowVideo($atts){
	extract(shortcode_atts(array(
		'id' => 'no id',
		'width' => '425',
		'height' => '334',
		'allowFullScreen', 'true',
		'rating' => 'false'
	), $atts));
	$video = '<object width="'.$width.'" height="'.$height.'">
					<param name="movie" value="http://www.youtube.com/v/' . $id . '&hl=en&fs=1"></param>
					<param name="allowFullScreen" value="'.$allowFullScreen.'"></param>
					<param name="allowscriptaccess" value="always"></param>
					<embed src="http://www.youtube.com/v/' . $id . '&hl=en&fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="'.$width.'" height="'.$height.'"></embed>
				</object>';
	if($rating == 'true'){
		try{
			$videoObject = hdYouTube::GetSingleVideo($id);
			$rating = substr($videoObject->Rating['average'],0,1);
			$video .= '<link rel="stylesheet" type="text/css" href="/wp-content/plugins/spyoutube/css/jquery.rating.css" />';
			$video .= '<script type="text/javascript" src="/wp-content/plugins/spyoutube/js/jquery.js"></script>';
			$video .= '<script type="text/javascript" src="/wp-content/plugins/spyoutube/js/rating.js.php"></script>';
			$video .= '<script type="text/javascript" src="/wp-content/plugins/spyoutube/js/jquery.rating.js"></script>';
			$video .= '<br/>';
			for($x=1; $x<=5; $x++){
				$checked = ($rating == $x) ? 'checked="checked"' : '';
				$video .= '<input name="star-'.$id.'" type="radio" class="star" value="'.$id.'" '.$checked.'/>';
			}
			$video .= '<span id="ratingText-'.$id.'" style="margin-left:10px;"></span>';
		} catch (Exception $e) {
			$video .= '<br/>'.__('Rating could only be shown for your own videos', 'spYouTube');
		}
	}
	$video .= '<br/><br/>';
	return $video;
}

function spYouTubeShowAllVideos($atts){
	global $settings;
	extract(shortcode_atts(array(
		'template' => 'default',
		'style' => 'default'
	), $atts));

	$videos = hdYouTube::GetVideos($settings->spYouTubeUser);
	$file = dirname(__FILE__).'/templates/'.$template.'.php';
	$include = (file_exists($file)) ? $file : dirname(__FILE__).'/templates/default.php';
	require_once($include);
	return $videoTemplateResult;
}

// Function to deal with adding the newsletter menus
function spYouTubeMenu(){
	// Set admin as the only one who can use newsletter for security
	$allowed_group = 'manage_options';

	// Add the admin panel pages for newsletter. Use permissions pulled from above
	if (function_exists('add_menu_page')){
		add_menu_page(__('spYouTube', 'spYouTube'), __('spYouTube', 'spYouTube'), $allowed_group, 'spYouTube', 'spYouTube', 'images/media-button-video.gif');
	}
	if (function_exists('add_submenu_page')){
		add_submenu_page('spYouTube', __('Manage videos', 'spYouTube'), __('Manage videos', 'spYouTube'), $allowed_group, 'spYouTube', 'spYouTube');
		add_submenu_page('spYouTube', __('Upload video', 'spYouTube'), __('Upload video', 'spYouTube'), $allowed_group, 'spYouTubeUpload', 'spYouTubeUpload');
		add_submenu_page('spYouTube', __('Settings', 'spYouTube'), __('Settings', 'spYouTube'), $allowed_group, 'spYouTubeSettings', 'spYouTubeSettings');
	}
}

function spYouTube(){
	global $settings;

	try {
		$allClear = true;
		if($_GET['action'] == 'delete'){
			$deleted = hdYouTube::DeleteVideo($_GET['ident']);
			if($deleted->getMessage() == 'OK'){
				spYouTubeShowMessage(__('Video deleted', 'spYouTube'));
			}else{
				spYouTubeShowMessage(__('Video could not be deleted. Reason: ', 'spYouTube').$deleted->getMessage(), 'error');
			}
		}
		if($_POST['spYouTubeEdit'] == 1){
			$AVideoId = $_POST['spYouTubeID'];
			$ATitle = $_POST['spYouTubeUploadTitle_'.$AVideoId];
			$ADescription = $_POST['spYouTubeUploadDescription_'.$AVideoId];
			$ATags = $_POST['spYouTubeUploadTags_'.$AVideoId];
			$ACategory = $_POST['spYouTubeUploadCategory_'.$AVideoId];

			$field_array = array(
						'Title' => $ATitle,
						'Category' => $ACategory,
						'Description' => $ADescription,
						'Tags' => $ATags);
			foreach($field_array as $key => $entry) {
				if (empty($entry)) {
					spYouTubeShowMessage($key . ' is empty', 'error');
					$allClear = false;
				}
			}
			if($allClear == true){
				try {
					$editing = hdYouTube::EditVideo($AVideoId, $ATitle, $ADescription, $ATags, $ACategory);
					spYouTubeShowMessage(__('Video edited', 'spYouTube'));
				} catch ( Exception $e ) {
					spYouTubeShowMessage($e->getMessage(), 'error');
				}
			}
		}
		$videos = hdYouTube::GetVideos($settings->spYouTubeUser);
		?>
		<script type="text/javascript" src="/wp-content/plugins/spyoutube/js/jquery.js"></script>
		<script type="text/javascript" src="/wp-content/plugins/spyoutube/js/scripts.js"></script>
		<div class="wrap"><h2><? _e('Manage videos','spYouTube'); ?></h2></div>
		<a href="admin.php?page=spYouTubeUpload"><? _e('Upload new video'); ?></a>

		<table style="margin-top: 1em;width:99%;" class="widefat">
			<thead>
				<tr>
					<th class="manage-column" scope="col"><? _e('Screenshot', 'spYouTube'); ?></th>
					<th class="manage-column" scope="col"><? _e('Title', 'spYouTube'); ?></th>
					<th class="manage-column" scope="col"><? _e('Category', 'spYouTube'); ?></th>
					<th class="manage-column" scope="col"><? _e('Description', 'spYouTube'); ?></th>
					<th class="manage-column" scope="col"><? _e('Tags', 'spYouTube'); ?></th>
					<th class="manage-column" scope="col"><? _e('Duration', 'spYouTube'); ?></th>
					<th class="manage-column" scope="col"><? _e('Comments', 'spYouTube'); ?></th>
					<th class="manage-column" scope="col"><? _e('Rating', 'spYouTube'); ?></th>
					<th class="manage-column" scope="col"><? _e('Updated', 'spYouTube'); ?></th>
					<th class="manage-column" scope="col"></th>
				</tr>
			</thead>
			<tbody>
				<?
				$x = 1;
				foreach ($videos as $entry) {
					$class = '';
					if($x % 2 == 0){
						$class = 'alternate';
					}
					?>
					<tr class="<?= $class; ?> iedit" id="video_<?= $entry->VideoID ?>">
						<form action="<?= $_SERVER['REQUEST_URI']; ?>" method="post">
						<td style="width:70px;"><img src="<?= $entry->Thumbnails[0]->Url; ?>" alt="" width="60" /></td>
						<td>
							<span id="spYouTubeTitleText_<?= $entry->VideoID ?>">
								<?= $entry->Title; ?><br/>
								ID: <?= $entry->VideoID ?>
							</span>
							<span id="spYouTubeTitleInput_<?= $entry->VideoID ?>" style="display:none;"><input type="text" class="regular-text" value="<?= $entry->Title; ?>" id="spYouTubeUploadTitle_<?= $entry->VideoID ?>" name="spYouTubeUploadTitle_<?= $entry->VideoID ?>"/></span>
						</td>
						<td>
							<span id="spYouTubeCategoryText_<?= $entry->VideoID ?>"><?= $entry->Category; ?></span>
							<span id="spYouTubeCategoryInput_<?= $entry->VideoID ?>" style="display:none;">
								<select name="spYouTubeUploadCategory_<?= $entry->VideoID ?>" id="spYouTubeUploadCategory_<?= $entry->VideoID ?>">
									<?
									$cats = hdYouTube::GetCategories();
									foreach($cats as $cat) {
										$selected = ($cat->Description == $entry->Category) ? 'selected="selected"' : '';
										?>
										<option value="<?= $cat->Value; ?>" <?= $selected; ?>><?= $cat->Description; ?></option>
										<?
									}
									?>
								</select>
							</span>
						</td>
						<td>
							<span id="spYouTubeDescriptionText_<?= $entry->VideoID ?>"><?= $entry->Description; ?></span>
							<span id="spYouTubeDescriptionInput_<?= $entry->VideoID ?>" style="display:none;"><textarea name="spYouTubeUploadDescription_<?= $entry->VideoID ?>" id="spYouTubeUploadDescription_<?= $entry->VideoID ?>" rows="3" cols="25"><?= $entry->Description; ?></textarea></span>
						</td>
						<td>
							<span id="spYouTubeTagsText_<?= $entry->VideoID ?>"><?= $entry->CommaTags; ?></span>
							<span id="spYouTubeTagsInput_<?= $entry->VideoID ?>" style="display:none;"><input type="text" class="regular-text" value="<?= $tags; ?>" id="spYouTubeUploadTags_<?= $entry->VideoID ?>" name="spYouTubeUploadTags_<?= $entry->VideoID ?>"/></span>
						</td>
						<td><?= $entry->DurationFormatted; ?></td>
						<td><?= count($entry->Comments); ?></td>
						<td><?= $entry->Rating['average']; ?><?= (!empty($entry->Rating['numRaters'])) ? '<br/>'.$entry->Rating['numRaters'].' '.__('ratings', 'spYouTube') : '0 '.__('ratings', 'spYouTube'); ?></td>
						<td><?= date('Y-m-d H:i:s',strtotime($entry->Updated)); ?></td>
						<td>
							<span id="spYouTubeManageLinks_<?= $entry->VideoID ?>">
								<a href="http://www.youtube.com/watch?v=<?= $entry->VideoID ?>" target="_blank"><? _e('View', 'spYouTube'); ?></a> |
								<a href="#" onclick="changeForEdit('<?= $entry->VideoID ?>', false);"><? _e('Edit', 'spYouTube'); ?></a> |
								<a href="admin.php?page=spYouTube&amp;action=delete&amp;ident=<?= $entry->VideoID ?>" onclick="return confirm('moep?');"><? _e('Delete', 'spYouTube'); ?></a>
							</span>
							<span id="spYouTubeManageButtons_<?= $entry->VideoID ?>" style="display:none;">
								<input type="hidden" name="spYouTubeEdit" value="1" />
								<input type="hidden" name="spYouTubeID" value="<?= $entry->VideoID ?>" />
								<input class="button-primary" type="submit" value="<? _e('Edit video', 'spYouTube'); ?>" />
								<input class="button-primary" type="button" value="<? _e('Cancel editing', 'spYouTube'); ?>" onclick="changeForEdit('<?= $entry->VideoID ?>', true);" />
							</span>
						</td>
						</form>
					</tr>
					<?
					$x++;
				}
				?>
				<tr class="iedit">
					<td colspan="15">
						<strong><? _e('Embedding', 'spYouTube'); ?></strong><br/>
						<? _e('Embed video', 'spYouTube'); ?>: <strong>[video id="XXX"]</strong><br/>
						<? _e('Optional parameter', 'spYouTube'); ?>: <strong>[video id="XXX" width="XXX" height="XXX" allowFullScreen="true/false" rating="true"]</strong>
						<br/><br/>
						<? _e('Embed all videos', 'spYouTube'); ?>: <strong>[allvideos]</strong><br/>
						<? _e('Optional parameter', 'spYouTube'); ?>: <strong>[allvideos template="XXX"]</strong>
					</td>
				</tr>
			</tbody>
		</table>
		<?
		if($allClear == false){
			?>
			<script type="text/javascript">
				changeForEdit('<?= $AVideoId; ?>', false);
			</script>
			<?
		}
	} catch ( Exception $e ) {
		spYouTubeShowMessage($e->getMessage(), 'error');
	}
}

function spYouTubeUpload(){
	global $settings;

	if($_POST['spYouTubeUpload'] == 1){
		$allClear = true;
		$ATitle = $_POST['spYouTubeUploadTitle'];
		$ADescription = $_POST['spYouTubeUploadDescription'];
		$ATags = $_POST['spYouTubeUploadTags'];
		$ACategory = $_POST['spYouTubeUploadCategory'];

		$field_array = array(
					'Title' => $ATitle,
					'Category' => $ACategory,
					'Description' => $ADescription,
					'Tags' => $ATags);
		foreach($field_array as $key => $entry) {
			if (empty($entry)) {
				spYouTubeShowMessage($key . ' is empty', 'error');
				$allClear = false;
			}
		}
		if($allClear == true){
			try {
				$video_id = hdYoutube::AddVideo($_FILES['spYouTubeUploadFile']['tmp_name'], $_FILES['spYouTubeUploadFile']['name'], $_FILES['spYouTubeUploadFile']['type'], $ATitle, $ADescription, $ATags, $ACategory);
			} catch ( Exception $e ) {
				spYouTubeShowMessage($e->getMessage(), 'error');
			}
			if ($video_id) {
				spYouTubeShowMessage(__('Video successfully committed'));
			}
		}
	}

	if(empty($settings->spYouTubeClientId) || empty($settings->spYouTubeDeveloperId)){
		spYouTubeShowMessage(__('ClientID or Developer Key empty. Please check your settings. Without these, you can not upload any video.'), 'error');
	}
	?>
	<script type="text/javascript" src="/wp-content/plugins/spyoutube/js/jquery.js"></script>
	<script type="text/javascript" src="/wp-content/plugins/spyoutube/js/scripts.js"></script>
	<div class="wrap"><h2><? _e('Upload video','spYouTube'); ?></h2></div>
	<form action="<?= $_SERVER['REQUEST_URI']; ?>" method="post" enctype="multipart/form-data">
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row"><label for="spYouTubeUploadCategory"><? _e('Category', 'spYouTube'); ?></label></th>
				<td>
					<select name="spYouTubeUploadCategory" id="spYouTubeUploadCategory">
						<?
						$cats = hdYouTube::GetCategories();
						foreach($cats as $entry) {
							$selected = ($_POST['spYouTubeUploadCategory'] == $entry->Value) ? 'selected="selected"' : '';
							?>
							<option value="<?= $entry->Value; ?>" <?= $selected; ?>><?= $entry->Description; ?></option>
							<?
						}
						?>
					</select>
					<span class="description"><? _e('Select your category', 'spYouTube'); ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="spYouTubeUploadTitle"><? _e('Title', 'spYouTube'); ?></label></th>
				<td>
					<input type="text" class="regular-text" value="<?= $_POST['spYouTubeUploadTitle']; ?>" id="spYouTubeUploadTitle" name="spYouTubeUploadTitle"/>
					<span class="description"><? _e('Title of the video', 'spYouTube'); ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="spYouTubeUploadDescription"><? _e('Description', 'spYouTube'); ?></label></th>
				<td>
					<textarea name="spYouTubeUploadDescription" id="spYouTubeUploadDescription" cols="37" rows="5"/><?= $_POST['spYouTubeUploadDescription']; ?></textarea>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="spYouTubeUploadTags"><? _e('Tags', 'spYouTube'); ?></label></th>
				<td>
					<input type="text" class="regular-text" value="<?= $_POST['spYouTubeUploadTags']; ?>" id="spYouTubeUploadTags" name="spYouTubeUploadTags"/>
					<span class="description"><? _e('Tags (comma sperated)', 'spYouTube'); ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="spYouTubeUploadFile"><? _e('Your video', 'spYouTube'); ?></label></th>
				<td>
					<input type="file" class="regular-text" value="" id="spYouTubeUploadFile" name="spYouTubeUploadFile"/>
					<span class="description"><? _e('Your video', 'spYouTube'); ?> (Max <?= ini_get('upload_max_filesize'); ?>)</span>
				</td>
			</tr>
		</tbody>
	</table><br/><br/>
	<input type="hidden" name="spYouTubeUpload" value="1" />
	<span id="spYouTubeUploadButton">
		<input class="button-primary" type="submit" value="<? _e('Upload video'); ?>" onclick="stopUploadButton();"/>
	</span>
	<span id="spYouTubeUploadText" style="display:none;">
		<img src="/wp-content/plugins/spyoutube/images/indicator.gif" alt="Indicator" title="Indicator"> <?= _e('Uploading... Please wait', 'spYouTube'); ?> <a href="admin.php?page=spYouTubeUpload"><? _e('Cancel', 'spYouTube'); ?></a>
	</span>
	</form>
	<?
}

function spYouTubeSettings(){
	global $settings;

	if($_POST['spYouTubeSettings'] == 1){
		spYouTubeSaveSettings();
		spYouTubeShowMessage(__('Settings saved'));
	}
	?>
	<div class="wrap"><h2><? _e('Settings','spYouTube'); ?></h2></div>
	<form action="<?= $_SERVER['REQUEST_URI']; ?>" method="post">
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row"><label for="spYouTubeUser"><? _e('YouTube username', 'spYouTube'); ?></label></th>
				<td>
					<input type="text" class="regular-text" value="<?= $settings->spYouTubeUser; ?>" id="spYouTubeUser" name="spYouTubeUser"/>
					<span class="description"><? _e('Your username for YouTube', 'spYouTube'); ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="spYouTubePass"><? _e('YouTube password', 'spYouTube'); ?></label></th>
				<td>
					<input type="password" class="regular-text" value="<?= $settings->spYouTubePass; ?>" id="spYouTubePass" name="spYouTubePass"/>
					<span class="description"><? _e('Your password for YouTube', 'spYouTube'); ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="spYouTubeClientId"><? _e('Google Client ID', 'spYouTube'); ?></label></th>
				<td>
					<input type="text" class="regular-text" value="<?= $settings->spYouTubeClientId; ?>" id="spYouTubeClientId" name="spYouTubeClientId"/>
					<span class="description"><? _e('No Client ID yet? Get it here:', 'spYouTube'); ?> <a href="http://code.google.com/apis/youtube/dashboard/" target="_blank">Google</a></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="spYouTubeDeveloperId"><? _e('Google developer ID', 'spYouTube'); ?></label></th>
				<td>
					<input type="text" class="regular-text" value="<?= $settings->spYouTubeDeveloperId; ?>" id="spYouTubeDeveloperId" name="spYouTubeDeveloperId"/>
					<span class="description"><? _e('No developer ID yet? Get it here:', 'spYouTube'); ?> <a href="http://code.google.com/apis/youtube/dashboard/" target="_blank">Google</a></span>
				</td>
			</tr>
		</tbody>
	</table><br/><br/>
	<input type="hidden" name="spYouTubeSettings" value="1" />
	<input class="button-primary" type="submit" value="<? _e('Save Changes', 'spYouTube'); ?>" />
	</form>
	<?
}

// show message after submit
function spYouTubeShowMessage($aMessage, $aClass = 'updated'){
	$result = '<div class="'.$aClass.' fade"><p>'.$aMessage.'</p></div>';
	echo $result;
}

// save the settings
function spYouTubeSaveSettings(){
	$class = new stdClass();
	foreach ($_POST as $key => $entry) {
		$class->$key = $entry;
	}
	update_option('spYouTube', serialize($class));
}

// get the saved settings from database
function spYouTubeGetSettings(){
	return unserialize(get_option('spYouTube'));
}