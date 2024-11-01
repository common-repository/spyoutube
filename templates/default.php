<?php
$videoTemplateResult = '<table>';
	foreach ($videos as $entry) {
		$videoTemplateResult .= '
		<tr>
			<td><a href="'.$entry->Url.'" target="blank"><img src="'.$entry->Thumbnails[0]->Url.'" alt="'.$entry->Title.'" /></a></td>
			<td valign="top">
				<a href="'.$entry->Url.'" target="blank"><strong>'.$entry->Title.'</strong></a><br/>
				'.__('Duration', 'spYouTube').': '.$entry->DurationFormatted.'<br/>
				'.__('Comments', 'spYouTube').': '.count($entry->Comments).'<br/>
				'.__('Tags', 'spYouTube').': '.$entry->CommaTags.'<br/>
				'.__('Updated', 'spYouTube').': '.date('Y-m-d H:i:s',strtotime($entry->Updated)).'
			</td>
		</tr>
		';
	}
$videoTemplateResult .= '</table>';
return $videoTemplateResult;