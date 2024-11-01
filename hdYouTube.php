<?php
/**
 * hdYouTube gives yout the ability to upload a video from your browser directly to YouTube.
 *
 * Copyright (C) [2008] [HDNET GmbH & CoKG]
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with this program; if not, see http://www.gnu.org/licenses/.
 *
 * @example Upload and get the YouTube video ID: $video_id = hdYouTube::UploadVideo($_FILES['videofile']['tmp_name'], $_FILES['videofile']['name'],$_FILES['videofile']['type'], $_POST['title'], $_POST['description'], $_POST['tags']);
 * @example Request video status: $video_status = hdYouTube::CheckVideoStatus($video_id);
 * @example Delete a video: hdYouTube::DeleteVideo($video_id);
 *
 * @version 1.5.2
 * @author Sebastian Klaus <sk@hdnet.de;sebastian-klaus@gmx.de>
 * @since 20.11.2008
 * @copyright HDNET GmbH & CoKG
 * @uses PHP5, Zend Framwork
 *
 * @uses Zend Framwork
 * @link http://framework.zend.com
 * Extract the package to /Zend/
 *
 * What you need:
 * YouTube account.
 * @link http://www.youtube.com
 *
 * Client ID and developer ID.
 * @link http://code.google.com/apis/youtube/dashboard/
 *
 * CHANGELOG:
 *
 * Version 1.5.2
 * - performance tuning
 *
 * Version 1.5.1
 * - comments for videos implemented
 *
 * Version 1.5
 * - added a duration calculator
 * - added function to display a videofeed, can be overwritten
 * - added a search function
 *
 * Version 1.4
 * - get single video by ID
 * - get thumbnails for videos
 *
 * Version 1.3
 * - adds YouTube categories
 *
 * Version 1.2
 * - adds a listing of uploaded videos
 *
 * Version 1.1
 * - replaced the function Init() with constructor
 * - Validating required fields
 * - Exception handling
 */

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) );

/**
 * Execution time set to unlimited
 *
 * max_input_time, post_max_size, upload_max_filesize, max_input_time could be changed in htaccess
 * php_value memory_limit 500M
 * php_value post_max_size 500M
 * php_value upload_max_filesize 500M
 * php_value max_input_time -1
 */

/**
 * Includes the Zend Framwork libraries
 */

require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_App_Util');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Http_Client');

/**
 * Required URLs
 * Don't change them
 */
define('AUTHENTICATION_URL', 'https://www.google.com/youtube/accounts/ClientLogin');
define('UPLOAD_URL', 'http://uploads.gdata.youtube.com/feeds/users/default/uploads');

class hdYouTube {
	protected $YouTube = null;
	protected $Title = '';
	protected $Description = '';
	protected $Tags = 'none';
	protected $Category = 'Comedy';
	protected $Private = false;
	protected $VideoId = '';
	protected $FileTmpName = '';
	protected $FileName = '';
	protected $FileType = '';
	protected $Username = '';

	/**
	 * Login to YouTube,
	 * Creates YouTube object
	 *
	 * @return object
	 */
	public function __construct() {
		global $settings;
		try {
			// YouTube login
			$httpClient = Zend_Gdata_ClientLogin::getHttpClient($username = $settings->spYouTubeUser, $password = $settings->spYouTubePass, $service = 'youtube', $client = null, $source = 'My Test', $loginToken = null, $loginCaptcha = null, AUTHENTICATION_URL);
			$httpClient->setConfig(array(
				'timeout' => 9999));
			// Create YouTube object
			$this->YouTube = new Zend_Gdata_YouTube($httpClient, '', $settings->spYouTubeClientId, $settings->spYouTubeDeveloperId);
		} catch ( Exception $e ) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Sets the Filesource to the YouTube object
	 *
	 * @return object
	 */
	protected function GetFileSource() {
		// Add temporary file
		$filesource = $this->YouTube->newMediaFileSource($this->FileTmpName);
		$filesource->setContentType($this->FileType);
		$filesource->setSlug($this->FileName);
		return $filesource;
	}

	/**
	 * Creates the video entry on YouTube
	 *
	 * @return string
	 */
	protected function CreateEntry() {
		// Set video entry
		$myVideoEntry = new Zend_Gdata_YouTube_VideoEntry();
		$myVideoEntry->setMediaSource($this->GetFileSource());
		$myVideoEntry->setVideoTitle($this->Title);
		$myVideoEntry->setVideoDescription($this->Description);
		$myVideoEntry->setVideoCategory($this->Category);
		$myVideoEntry->SetVideoTags($this->Tags);

		if ($this->Private) {
			// Set video as private
			$myVideoEntry->setVideoPrivate();
		}

		try {
			$Result = $this->YouTube->insertEntry($myVideoEntry, UPLOAD_URL, 'Zend_Gdata_YouTube_VideoEntry');
		} catch ( Exception $e ) {
			throw new Exception($e->getMessage());
		}

		// Get Video-ID from YouTube
		return $Result->getVideoId();
	}

	/**
	 * Internal deleting function
	 *
	 * @return boolean
	 */
	protected function Delete() {
		// Get complete video object
		$VideoEntry = $this->YouTube->getFullVideoEntry($this->VideoId);
		// Delete video on YouTube
		return $this->YouTube->delete($VideoEntry);
	}

	/**
	 * Internal check if the video is fully processed
	 *
	 * @param string $AReturnString
	 * @return boolean/string
	 */
	protected function CheckIt($AReturnString) {
		// Checking video status
		$VideoEntry = $this->YouTube->getFullVideoEntry($this->VideoId);
		$Status = $VideoEntry->getVideoState();
		if ($Status != null) {
			if ($AReturnString) {
				return $Status->getName();
			}
			return false;
		}
		return true;
	}

	/**
	 * Validates the function parameters
	 *
	 * @param string $AType
	 * @return Exception
	 */
	protected function Validate($AType) {
		switch($AType){
			case 'create':
				$field_array = array(
						'Filename' => $this->FileTmpName,
						'Filename' => $this->FileName,
						'Filetype' => $this->FileType,
						'Title' => $this->Title,
						'Description' => $this->Description,
						'Tags' => $this->Tags);
				foreach($field_array as $key => $entry) {
					if (empty($entry)) {
						throw new Exception($key . ' is empty');
						return false;
					}
				}
				break;
			case 'edit':
				if (! isset($this->VideoId)) {
					throw new Exception(__('VideoID is empty'));
					return false;
				}
				$field_array = array(
						'Title' => $this->Title,
						'Category' => $this->Category,
						'Description' => $this->Description,
						'Tags' => $this->Tags);
				foreach($field_array as $key => $entry) {
					if (empty($entry)) {
						throw new Exception($key . ' is empty');
						return false;
					}
				}
				break;
			case 'check':
			case 'delete':
			case 'getsingle':
				if (! isset($this->VideoId)) {
					throw new Exception(__('VideoID is empty'));
					return false;
				}
				break;
			case 'get':
				if (! isset($this->Username)) {
					throw new Exception(__('Username is empty'));
					return false;
				}
				break;
		}
		return true;
	}

	/**
	 * Builds the embed code for the YouTube player
	 *
	 * @param object $AVideo
	 * @return string
	 */
	protected function GetPlayerEmbed($AVideo, $AWidth = 425, $AHeight = 344) {
		return '<object width="'.$AWidth.'" height="'.$AHeight.'">
					<param name="movie" value="http://www.youtube.com/v/' . $AVideo->getVideoId() . '&hl=en&fs=1"></param>
					<param name="allowFullScreen" value="true"></param>
					<param name="allowscriptaccess" value="always"></param>
					<embed src="http://www.youtube.com/v/' . $AVideo->getVideoId() . '&hl=en&fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="'.$AWidth.'" height="'.$AHeight.'"></embed>
				</object>';
	}

	/**
	 * Creates the video object to work with
	 *
	 * @param string $AVideo
	 * @return object
	 */
	protected function CreateVideoObject($AVideo) {
		$Videos = new stdClass();
		$Videos->VideoID = $AVideo->getVideoId();
		$Videos->Updated = $AVideo->getUpdated();
		$Videos->Title = $AVideo->getVideoTitle();
		$Videos->Description = $AVideo->getVideoDescription();
		$Videos->Category = $AVideo->getVideoCategory();
		$Videos->Url = $AVideo->getVideoWatchPageUrl();
		$Videos->PlayerEmbed = $this->GetPlayerEmbed($AVideo);
		$Videos->Duration = $AVideo->getVideoDuration();
		$Videos->DurationFormatted = $this->BuildSecondsFormatted($AVideo->getVideoDuration());
		$Videos->Views = $AVideo->getVideoViewCount();
		$Videos->Rating = $AVideo->getVideoRatingInfo();
		$Videos->GeoLocation = $AVideo->getVideoGeoLocation();

		$VideoTagCollection = $AVideo->getVideoTags();
		foreach($VideoTagCollection as $videoTags) {
			$Tags = new stdClass();
			$Tags->Title = $videoTags;
			$Videos->Tags[] = $Tags;
		}

		$trenner = '';
		$tags = '';
		foreach ($Videos->Tags as $tag) {
			$tags .= $trenner.$tag->Title;
			$trenner = ', ';
		}
		$Videos->CommaTags = $tags;

		$VideoThumbnailCollection = $AVideo->getVideoThumbnails();
		foreach($VideoThumbnailCollection as $videoThumbnail) {
			$Thumbs = new stdClass();
			$Thumbs->Url = $videoThumbnail['url'];
			$Thumbs->Time = $videoThumbnail['time'];
			$Thumbs->Width = $videoThumbnail['width'];
			$Thumbs->Height = $videoThumbnail['height'];
			$Videos->Thumbnails[] = $Thumbs;
		}

		$youtube = new Zend_Gdata_YouTube();
		$VideoCommentsCollection = $youtube->getVideoCommentFeed($Videos->VideoID);
		foreach($VideoCommentsCollection as $videoComment) {
			$Comments = new stdClass();
			$Comments->Author = $videoComment->author[0]->name->text;
			$Comments->Comment = $videoComment->title->text;
			$Comments->FullText = $videoComment->content->text;
			$Videos->Comments[] = $Comments;
		}
		return $Videos;
	}

	/**
	 * Puts all YouTube-Parameters from
	 * internal Zend functions into a
	 * hdYouTube object
	 *
	 * @return object
	 */
	protected function GetVideosByUsername() {
		$UploadListFeed = $this->YouTube->getUserUploads($this->Username);
		foreach($UploadListFeed as $UploadListEntry) {
			$ResultArray[] = $this->CreateVideoObject($UploadListEntry);
		}
		return $ResultArray;
	}

	/**
	 * Returns a video object
	 *
	 * @return object
	 */
	protected function GetVideoById() {
		$VideoEntry = $this->YouTube->getFullVideoEntry($this->VideoId);
		return $this->CreateVideoObject($VideoEntry);
	}

	/**
	 * Returns a video object
	 *
	 * @return object
	 */
	protected function Edit() {
		$VideoEntry = $this->YouTube->getFullVideoEntry($this->VideoId);
		$putUrl = $VideoEntry->getEditLink()->getHref();
		$VideoEntry->setVideoTitle($this->Title);
		$VideoEntry->setVideoDescription($this->Description);
		$VideoEntry->setVideoCategory($this->Category);
		$VideoEntry->SetVideoTags($this->Tags);
		return $this->YouTube->updateEntry($VideoEntry, $putUrl);
	}

	/**
	 * Returns a formatted time like 00:00:00
	 *
	 * @param int $ASeconds
	 * @return string
	 */
	protected function BuildSecondsFormatted($ASeconds) {
		return gmdate('G', $ASeconds) . ':' . gmdate('i', $ASeconds) . ':' . gmdate('s', $ASeconds);
	}

	/**
	 * Returns the rating message
	 *
	 * @param int $ARate
	 * @return string
	 */
	protected function SetVideoRating($ARate){
		$VideoEntry = $this->YouTube->getFullVideoEntry($this->VideoId);
		$VideoEntry->setVideoRating($ARate);
		$ratingUrl = $VideoEntry->getVideoRatingsLink()->getHref();

		try {
			$ratedVideoEntry = $this->YouTube->insertEntry( $VideoEntry, $ratingUrl, 'Zend_Gdata_YouTube_VideoEntry');
		} catch (Zend_Gdata_App_HttpException $httpException) {
			echo $this->GetVideoRatingMessage($httpException->getRawResponseBody());
		}
	}

	/**
	 * Returns the formatted rating message
	 *
	 * @param string $AMessage
	 * @return string
	 */
	protected function GetVideoRatingMessage($AMessage){
		if(strpos($AMessage, 'your own videos')){
			return __('Rating your own videos is not permitted.', 'spYouTube');
		}
	}

	/**
	 * Returns the uploaded videos from a username
	 *
	 * @param string $AUsername
	 * @return object
	 */
	public static function GetVideos($AUsername) {
		$GetVideos = new hdYouTube();
		$GetVideos->Username = $AUsername;
		$GetVideos->Validate('get');
		return $GetVideos->GetVideosByUsername();
	}

	/**
	 * Returns a video object
	 *
	 * @param string $AVideoId
	 * @return object
	 */
	public static function GetSingleVideo($AVideoId) {
		$GetVideo = new hdYouTube();
		$GetVideo->VideoId = $AVideoId;
		$GetVideo->Validate('getsingle');
		return $GetVideo->GetVideoById();
	}

	/**
	 * Check if video is prozessing...
	 *
	 * @param string $AVideoId
	 * @param boolean $AReturnString
	 * @return boolean
	 */
	public static function CheckVideoStatus($AVideoId) {
		$Check = new hdYouTube();
		$Check->VideoId = $AVideoId;
		$Check->Validate('check');
		return $Check->CheckIt($AReturnString);
	}

	/**
	 * Delete a video
	 *
	 * @param string $AVideoId
	 * @return boolean
	 */
	public static function DeleteVideo($AVideoId) {
		$Delete = new hdYouTube();
		$Delete->VideoId = $AVideoId;
		$Delete->Validate('delete');
		return $Delete->Delete();
	}

	/**
	 * Edit a video
	 *
	 * @param string $AVideoId
	 * @return boolean
	 */
	public static function EditVideo($AVideoId, $ATitle, $ADescription = '&nbsp;', $ATags, $ACategory) {
		$Edit = new hdYouTube();
		$Edit->VideoId = $AVideoId;
		$Edit->Title = htmlspecialchars($ATitle);
		if($ADescription == ''){
			$ADescription = ' ';
		}
		$Edit->Description = htmlspecialchars($ADescription);
		$Edit->Tags = htmlspecialchars($ATags);
		$Edit->Category = $ACategory;
		$Edit->Validate('edit');
		return $Edit->Edit();
	}

	/**
	 * Upload a video to YouTube
	 *
	 * @param string $AFileTmpName
	 * @param string $AFileName
	 * @param string $AFileType
	 * @param string $ATitle
	 * @param string $ADescription
	 * @param string $ATags
	 * @param string $ACategory
	 * @param boolean $APrivate
	 * @return string
	 */
	public static function AddVideo($AFileTmpName, $AFileName, $AFileType, $ATitle, $ADescription, $ATags, $ACategory = 'Comedy', $APrivate = false) {
		$UploadVideo = new hdYouTube();
		$UploadVideo->FileTmpName = $AFileTmpName;
		$UploadVideo->FileName = $AFileName;
		$UploadVideo->FileType = $AFileType;
		$UploadVideo->Title = htmlspecialchars($ATitle);
		if($ADescription == ''){
			$ADescription = $ATitle;
		}
		$UploadVideo->Description = htmlspecialchars($ADescription);
		$UploadVideo->Tags = htmlspecialchars($ATags);
		$UploadVideo->Category = $ACategory;
		$UploadVideo->Private = $APrivate;
		$UploadVideo->Validate('create');
		return $UploadVideo->CreateEntry();
	}

	/**
	 * Returns YouTube categories as object
	 * from class hdYouTubeCategories
	 *
	 * @return object
	 */
	public static function GetCategories() {
		return hdYouTubeCategories::GetCategories();
	}

	public static function Rate($ARate, $AVideoId){
		$RateVideo = new hdYouTube();
		$RateVideo->VideoId = $aVideoId;
		return $RateVideo->SetVideoRating($ARate);
	}
}

/**
 * YouTube search class
 *
 */
class hdYouTubeSearch extends hdYoutube {
	protected $SearchTerm = '';
	protected $Limit = 10;
	protected $Start = 0;

	/**
	 * Gets the result from the api
	 *
	 * @return object
	 */
	protected function GetSearchResult() {
		$query = $this->YouTube->newVideoQuery();
		$query->setOrderBy('viewCount');
		$query->setMaxResults($this->Limit);
		$query->setVideoQuery($this->SearchTerm);
		$query->setStartIndex($this->Start);
		$result = $this->YouTube->getVideoFeed($query);
		$object = array();
		foreach($result as $entry) {
			$object[] = $this->CreateVideoObject($entry);
		}
		return $object;
	}

	/**
	 * Get all results from the api
	 *
	 * @return object
	 */
	protected function GetAllSearchResults() {
		$query = $this->YouTube->newVideoQuery();
		$query->setOrderBy('viewCount');
		$query->setVideoQuery($this->SearchTerm);
		$result = $this->YouTube->getVideoFeed($query);
		$object = array();
		foreach($result as $entry) {
			$object[] = $entry;
		}
		return $object;
	}

	/**
	 * Calls the GetSearchResult function
	 *
	 * @param string $ASearch
	 * @param int $ALimit
	 * @return object
	 */
	public static function GetIt($ASearch, $AStart = 0, $ALimit = 10) {
		$ShowIt = new hdYouTubeSearch();
		$ShowIt->SearchTerm = $ASearch;
		$ShowIt->Limit = $ALimit;
		$ShowIt->Start = $AStart;
		return $ShowIt->GetSearchResult();
	}

	/**
	 * Calls the GetAllSearchResults function
	 *
	 * @param string $ASearch
	 * @param int $ALimit
	 * @return object
	 */
	public static function GetItAll($ASearch) {
		$ShowIt = new hdYouTubeSearch();
		$ShowIt->SearchTerm = $ASearch;
		return $ShowIt->GetAllSearchResults();
	}
}

/**
 * Returns the categories for the upload form
 *
 */
class hdYouTubeCategories {
	protected $Categories = null;

	/**
	 * Gets the schema file
	 *
	 */
	protected function GetSchema() {
		$this->Categories = file('http://gdata.youtube.com/schemas/2007/categories.cat');
	}

	/**
	 * Builds the return object
	 *
	 * @return object
	 */
	protected function GetMainCategories() {
		$this->GetSchema();
		$start = explode('<atom:category term=\'', $this->Categories[0]);
		$CategoriesArray = array();
		$count = 0;
		foreach($start as $entry) {
			if ($count != 0) {
				list($term,$description) = explode('\' label=\'', $entry);
				$description = explode('\' xml:lang=', $description);
				$CatObject = new stdClass();
				$CatObject->Value = $term;
				$CatObject->Description = $description[0];
				$CategoriesArray[] = $CatObject;
			}
			$count ++;
		}
		return $CategoriesArray;
	}

	/**
	 * Returns YouTube categories as object
	 *
	 * @return object
	 */
	public static function GetCategories() {
		$Categories = new hdYouTubeCategories();
		return $Categories->GetMainCategories();
	}
}