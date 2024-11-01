<?php

function ngg_wp_upload_tabs ($tabs) {
	$newtab = array('spyoutube' => __('spYouTube','spYouTube'));
    return array_merge($tabs,$newtab);
}

add_filter('media_upload_tabs', 'ngg_wp_upload_tabs');

function media_upload_spyoutube() {
	return wp_iframe( 'media_upload_spyoutube_form', $errors );
}

add_action('media_upload_spyoutube', 'media_upload_spyoutube');

function media_upload_spyoutube_form($errors) {
	global $wpdb, $wp_query, $wp_locale, $type, $tab, $post_mime_types, $ngg;

	media_upload_header();

	$form_action_url = get_option('siteurl') . "/wp-admin/media-upload.php?type={$GLOBALS['type']}&tab=spyoutube";
	$settings = spYouTubeGetSettings();
	?>

	<form id="filter" action="" method="get">
	<input type="hidden" name="type" value="<?php echo attribute_escape( $GLOBALS['type'] ); ?>" />
	<input type="hidden" name="tab" value="<?php echo attribute_escape( $GLOBALS['tab'] ); ?>" />
	<input type="hidden" name="action" value="sp_search" />
	<input type="hidden" name="paged" value="1" />

	<div class="tablenav">

		<div class="alignleft actions">
			<?= __('Search YouTube', 'spYouTube'); ?>:
			<input type="text" class="regular-text" value="<?= $_GET['spYouTubeSearch'] ?>" id="spYouTubeSearch" name="spYouTubeSearch"/>
			<input type="submit" id="show-gallery" value="<?= attribute_escape( __('Search', 'spYouTube') ); ?>" class="button-secondary" />
			<?
			if($_GET['action'] == 'sp_search'){
				?>
				<a href="<?= $form_action_url; ?>"><?= __('Cancel search', 'spYouTube'); ?></a>
				<?
			}?>
		</div>
		<br style="clear:both;" />
	</div>
	</form>

	<script type="text/javascript" src="/wp-content/plugins/spYouTube/js/jquery.js"></script>
	<script type="text/javascript" src="/wp-content/plugins/spYouTube/js/scripts.js"></script>

	<?
	if($_GET['action'] == 'sp_search'){
		$allVideos = hdYouTubeSearch::GetItAll($_GET['spYouTubeSearch']);
		$total = count($allVideos);
		?>
		<form class="media-upload-form" id="library-form-<?=$entry->VideoID; ?>">
			<?= __('Results for', 'spYouTube'); ?> <strong>"<?= $_GET['spYouTubeSearch']; ?>"</strong>:<br/><br/>
			<?php
			$page =  ($_GET['paged'] == '') ? 1 : $_GET['paged'];
			$page_links = paginate_links( array(
				'base' => add_query_arg( 'paged', '%#%' ),
				'format' => '',
				'total' => ceil($total / 10),
				'current' => $page
			));

			if ( $page_links )
				echo "<div class='tablenav-pages'>$page_links</div><br/>";
			?>
			<div id="media-items">
				<?
				$index = $_GET['paged']*10;
				$videos = hdYouTubeSearch::GetIt($_GET['spYouTubeSearch'], $index);
				foreach ($videos as $entry) {
					$html = '[video id="'.$entry->VideoID.'"]';
					?>
					<div id="media-item-<?= $entry->VideoID; ?>" class="media-item preloaded">
						<img alt="" src="<?= $entry->Thumbnails[0]->Url; ?>" class="pinkynail toggle"/>
						<input type="hidden" name="videoid" value="<?= $entry->VideoID; ?>" />
						<a href="#" onclick="$('#hidden_<?= $entry->VideoID; ?>').toggle('slow');" class="toggle describe-toggle-on "><? _e('Details', 'spYouTube'); ?></a>
						<div class="filename new"><?= $entry->Title; ?> (<?= $entry->VideoID; ?>)</div>
						<div id="hidden_<?= $entry->VideoID; ?>" style="display:none;">
							<table>
								<tr>
									<td>
										<?= $entry->PlayerEmbed; ?>
									</td>
									<td valign="top">
										<? _e('Duration', 'spYouTube'); ?>: <?= $entry->DurationFormatted; ?><br/>
										<? _e('Rating', 'spYouTube'); ?>: <?= $entry->Rating['average']; ?><br/><br/>
										<a href="#" onclick="insertIntoPost('<?= stripslashes(htmlspecialchars($html)); ?>');"><? _e('Insert', 'spYouTube'); ?></a>
									</td>
								</tr>
							</table>
						</div>
					</div>
					<?
				}
				?>
			</div>
		</form>
	<? }else{ ?>
		<form class="media-upload-form" id="library-form-<?=$entry->VideoID; ?>">
			<div id="media-items">
				<?
				$videos = hdYouTube::GetVideos($settings->spYouTubeUser);
				foreach ($videos as $entry) {
					$html = '[video id="'.$entry->VideoID.'" rating="true"]';
					?>
					<div id="media-item-<?= $entry->VideoID; ?>" class="media-item preloaded">
						<img alt="" src="<?= $entry->Thumbnails[0]->Url; ?>" class="pinkynail toggle"/>
						<input type="hidden" name="videoid" value="<?= $entry->VideoID; ?>" />
						<a href="#" onclick="insertIntoPost('<?= stripslashes(htmlspecialchars($html)); ?>');" class="toggle describe-toggle-on "><? _e('Insert', 'spYouTube'); ?></a>
						<div class="filename new"><?= $entry->Title; ?> (<?= $entry->VideoID; ?>)</div>
					</div>
					<?
				}
				?>
			</div>
		</form>
		<?
	}
}