<?php

// options:

// script to check uncategorized images to see if they are copyrighted
// the images in question will have a template {{No License}} on the images page
require_once( __DIR__ . '/../Maintenance.php' );

class RemoveUnlicensedImages extends Maintenance {
	static $imageLinksToRemove = array();
	static $testing = false;

	public function __construct() {
		parent::__construct();

		$this->addOption( 'limit', 'max number to process', false, true, 'l');
		$this->addOption( 'limitUCI', 'max UCI images to process', false, true, 'u');
		$this->addOption( 'testing', 'use sandbox values for tineye api search', false, false);
	}

	public function execute() {
		echo "will delete unlicensed images with likely copyright violations\n";

		// this is the user that will be in the log for delete/revision actions
		$scriptUser = self::getScriptUser();
		$limit = null;
		if ($this->hasOption('limit')) {
			$limit = $this->getOption('limit');
		}
		if($this->hasOption('limitUCI')) {
			$limitUCI = $this->getOption('limitUCI');
		}
		if ($this->hasOption('testing')) {
			self::$testing = true;
		}
		// get the list of images to act on
		$imageIds = self::getUnlicensedImages($limit);

		decho("will check ".count($imageIds)." images", false, false);

		$usersToNotify = array();

		// check the copyright status of each one
		foreach($imageIds as $pageId) {
			$title = Title::newFromID($pageId);
			$file = wfLocalFile($title);

			// get the url of the image file so we can pass it on to the copyright check api call
			$url = "http://www.wikihow.com".$file->getUrl();

			// check if copyrighted
			$result = self::getCopyrightMatches($url);
			if ($result['error']) {
				decho('error', $result['message'], false);
				continue;
			}

			$matches = $result['matches'];
			if ($matches > 0) {
				decho("violation - $matches matches online for image with url: ", $url, false);

				// ok so delete the image and links to it in any articles
				self::deleteImageAndLinks($title, $file, $scriptUser);
				$usersToNotify[$file->getUser('id')] = true;
			} else {
				decho("no violation for image with url: ", $url, false);
				// now edit the image page so it has a new template
				self::markCheckedImage($title, $scriptUser);
			}
		}

		foreach($usersToNotify as $userId => $val) {
			$u = User::newFromId($userId);
			if (!$u->isAnon()) {
				self::sendRemovalMessage($u);
			}
		}

		self::removeImageLinks(self::$imageLinksToRemove);

		//now do the same for UCI images
		$imageIds = UCIPatrol::getImagesToBeCopyrightChecked($limitUCI);

		decho("will check ".count($imageIds)." picture patrol images", false, false);

		// check the copyright status of each one
		foreach($imageIds as $pageId) {
			$title = Title::newFromID($pageId);
			$file = wfLocalFile($title);

			if(empty($file)) { //don't want any exceptions thrown in this case
				continue;
			}

			// get the url of the image file so we can pass it on to the copyright check api call
			$url = "http://www.wikihow.com".$file->getUrl();

			// check if copyrighted
			$result = self::getCopyrightMatches($url);
			if ($result['error']) {
				decho('error', $result['message'], false);
				UCIPatrol::markCopyright($pageId, 1, 0, 1);
				continue;
			}

			$matches = $result['matches'];
			if ($matches > 0) {
				decho("violation - $matches matches online for image with url: ", $url, false);

				UCIPatrol::markCopyright($pageId, 1, $matches);
			} else {
				decho("no violation for image with url: ", $url, false);

				UCIPatrol::markCopyright($pageId, 0);
			}
		}

		echo "finished\n";
	}

	public static function getScriptUser() {
		$user = User::newFromName("Image Licensing Bot");
		if ( $user && !$user->isLoggedIn() ) {
			$user->addToDatabase();
			$user->addGroup( 'bot' );
		}
		return $user;
	}

	// updates the template on an image that was checked and not a copyright violation
	public function markCheckedImage($fromTitle, $scriptUser) {
		$revision = Revision::newFromTitle($fromTitle);
		$text = ContentHandler::getContentText( $revision->getContent() );

		$text = preg_replace(
				'@\{\{No License\}\}@im',
				'{{No License Checked}}',
				$text);

		$content = ContentHandler::makeContent($text, $fromTitle);
		$summary = "marking unlicensed image as checked";
		$page = WikiPage::factory( $fromTitle );
		$result = $page->doEditContent( $content, $summary,EDIT_SUPPRESS_RC | EDIT_FORCE_BOT, false, $scriptUser);
		if ($result->value['revision'] !== null) {
			decho("changed template from {{No License}} to {{No License Checked}} on: ", $fromTitle->getText(), false);
		} else {
			decho("warning: no {{No License}} template to remove from: ", $fromTitle->getText(), false);
		}

	}
	public function deleteImageAndLinks($imageTitle, $file, $scriptUser) {
		// we will call function from ImagePage.php (which is protected) so do php work to
		// allow us to call it anyways
		$queryImageLinks = new ReflectionMethod('ImagePage', 'queryImageLinks');
		$queryImageLinks->setAccessible(true);

		// calling with limit of 100 should be more than enough since this is a relatively new image
		// cant image it being more than 1 to be honest since it's a new image and prob only on one article
		$linksFrom = array();
		$limit = 100;
		$res = $queryImageLinks->invoke(new ImagePage($imageTitle), $imageTitle->getDBkey(), $limit);

		// now remove wikitext references to this image
		foreach ( $res as $row ) {
			decho("will enqueue", $imageTitle, false);
			self::enqueueImageLinkForRemoval($row->page_title, $imageTitle);
		}

		// now delete the image file itself
		$oldimage = null;
		$reason = "copyright violation";
		$suppress = false;

		// got this code from ApiDelete.php
		$status = FileDeleteForm::doDelete($imageTitle, $file, $oldimage, $reason, $suppress, $scriptUser);

		if ( is_array( $status ) ) {
			decho("error deleting image file", $imageTitle->getFullText(), false);
			echo( $status[0] );
		} else {
			decho("deleted image file", $imageTitle->getFullText(), false);
		}
	}

	private function enqueueImageLinkForRemoval($fromTitle, $imageTitleText) {
		if (!self::$imageLinksToRemove[$fromTitle]) {
			self::$imageLinksToRemove[$fromTitle] = array();
		}
		self::$imageLinksToRemove[$fromTitle][] = $imageTitleText;
	}

	private function removeImageLinks($toRemove) {
		$scriptUser = self::getScriptUser();
		foreach($toRemove as $fromTitleText => $imageTitles) {
			self::removeImageLink($fromTitleText, $imageTitles, $scriptUser);
		}
	}

	public function removeImageLink($fromTitleText, $imageTitles, $asUser) {
		$result = Wikitext::removeImageLinksFromTitle($fromTitleText, $imageTitles, $asUser);

		if ($result->value['revision'] !== null) {
			decho("removed image links from", $fromTitleText, false);
		} else {
			decho("warning: nothing to remove from", $fromTitleText, false);
		}
	}

	// check for images that are in the unlicensed image category
	public function getUnlicensedImages($limit = null) {
		$dbr = wfGetDB( DB_REPLICA );
		$table = array('page', 'categorylinks');
		$vars = array('page_id');
		$conds = array('page_id=cl_from', 'page_namespace' => 6, 'cl_to' => "Unlicensed-Images");
		$options = array();

		if ($limit) {
			$options['LIMIT'] = $limit;
		}

		$res = $dbr->select($table, $vars, $conds, __METHOD__, $options);

		$images = array();

		foreach ( $res as $row ) {
			$images[] = $row->page_id;
		}
		$last = $dbr->lastQuery();

		return $images;
	}

	public function getCopyrightMatches($url) {
		$response = self::tineyeSearch($url);

		$result = array();
		if ($response['code'] != 200) {
			$result['error'] = true;
			$result['message'] = implode(' ', $response['messages']);
		}
		$result['matches'] = (int)@$response['results']['total_results'];

		return $result;
	}

	function tineyeSearch($image_url) {
		$api_url = "http://api.tineye.com/rest/search/";
		$key = WH_TINEYE_PRIVATE_KEY;
		$pubkey = WH_TINEYE_PUBLIC_KEY;

		if (self::$testing == true) {
			// if testing is true, then we can
			// use these -public- api values for testing
			$api_url = "http://api.tineye.com/sandbox/search/";
			$key = "6mm60lsCNIB,FwOWjJqA80QZHh9BMwc-ber4u=t^";
			$pubkey = "LCkn,2K7osVwkX95K4Oy";
		}

		$p = array(
				"offset" => "0",
				"limit" => "1",
				"image_url" => $image_url
				);

		$sorted_p = ksort($p);
		$query_p = http_build_query($p);
		$signature_p = strtolower($query_p);
		$action = "GET";
		$date = time();
		$nonce = uniqid();

		$string_to_sign = $key . $action . $date . $nonce . $api_url . $signature_p;

		$signature = hash_hmac("sha256", $string_to_sign, $key);

		$url = $api_url . "?api_key=" . $pubkey . "&";
		$url .= $query_p . "&date=" . $date . "&nonce=" . $nonce . "&api_sig=" . $signature;

		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($data, true);
		return $data;
	}

	function sendRemovalMessage($toUser) {
		global $wgUser;
		$tempUser = $wgUser;
		$fromUser = self::getScriptUser();
		$wgUser = $fromUser;
		$comment =  wfMessage('image-license-remove-message');
		Misc::adminPostTalkMessage($toUser, $fromUser, $comment);
		decho("sent removal user talk message to", $user, false);
		$wgUser = $tempUser;
	}
}

$maintClass = "RemoveUnlicensedImages";
require_once( RUN_MAINTENANCE_IF_MAIN );
