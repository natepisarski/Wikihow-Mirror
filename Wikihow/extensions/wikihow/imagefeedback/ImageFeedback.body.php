<?php

/*
CREATE TABLE `image_feedback` (
  `ii_img_page_id` int(10) unsigned NOT NULL,
  `ii_wikiphoto_img` int(10) unsigned NOT NULL DEFAULT '0',
  `ii_page_id` int(10) unsigned NOT NULL,
  `ii_img_url` varchar(2048) NOT NULL,
  `ii_bad_votes` int(10) unsigned NOT NULL DEFAULT '0',
  `ii_bad_reasons` text NOT NULL,
  `ii_good_votes` int(10) unsigned NOT NULL DEFAULT '0',
  `ii_good_reasons` text NOT NULL,
  PRIMARY KEY (`ii_img_page_id`),
  UNIQUE KEY `ii_img_url` (`ii_img_url`(255)),
  KEY `ii_page_id` (`ii_page_id`),
  KEY `ii_votes` (`ii_bad_votes`),
  KEY `ii_good_votes` (`ii_good_votes`)
);
 */

/*
 *   Collects feedback on article images from users of wikihow
 */
class ImageFeedback extends UnlistedSpecialPage {
	const WIKIPHOTO_USER_NAME = 'wikiphoto';
	public static $allowAnonFeedback = null;

	public function __construct() {
		parent::__construct('ImageFeedback');
	}

	public function execute($par) {
		global $wgIsTitusServer, $wgIsDevServer, $wgIsToolsServer;

		$req = $this->getRequest();
		$user = $this->getUser();

		if ($req->wasPosted()) {
			$action = $req->getVal('a');
			if (in_array('staff', $user->getGroups()) && $action == 'reset_urls') {
				$this->resetUrls();
			} else {
				$this->handleImageFeedback();
			}
		} else {
			if (($wgIsTitusServer || $wgIsDevServer || $wgIsToolsServer) &&
				in_array( 'staff', $user->getGroups() )
			) {
				$this->showAdminForm();
			}
		}
	}

	private function showAdminForm() {
		EasyTemplate::set_path(__DIR__);
		$vars['ts'] = wfTimestampNow();
		$this->getOutput()->addHtml(EasyTemplate::html('imagefeedback_admin.tmpl.php'));
	}

	// The original function missed URLs from pages that had been deleted.
	// The function now checks if an article title has been deleted from the site.
	// If so, it searches the image_feedback table by URL rather than articleID.
	// The function also addresses the edge cases when an article ID or URL has changed.
	private function resetUrls() {
		$deletedNames = array();
		$urls = preg_split("@\n@", trim($this->getRequest()->getVal('if_urls')));
		$count = 0;
		$dbw = wfGetDB(DB_MASTER);

		foreach ($urls as $url) {
			if (!empty($url)) {
				$t = WikiPhoto::getArticleTitle($url);
				if ($t && $t->exists()) {
					$aids[] = $t->getArticleId();
					$aidsBackup[] = $dbw->addQuotes($t->getPrefixedURL());
					$count++;
				} elseif ($t && $t->isDeletedQuick()) { //if the page was deleted
					$deletedNames[] = $dbw->addQuotes($t->getPrefixedURL());
					$count++;
				} else {
					$invalid[] = $url;
				}
			}
		}

		$numUrls = sizeof($aids);
		$affectedRows = 0;
		if ($numUrls) {
			$dbw->delete('image_feedback',
				array("ii_img_page_id" => $aids),
				__METHOD__);
			//edge case where the article ID has changed since the image was stored - has happened before
			//handle by deleting by URL
			$affectedRows += $dbw->affectedRows();
			if ($affectedRows != $numUrls) {
				$deletedNames = array_merge($deletedNames, $aidsBackup);
			}
		}

		//delete the URLs from deleted images (articleID no longer exists)
		$numDeleted = sizeof($deletedNames);
		if ($numDeleted) {
			$deletedNames = "(" . implode(",", $deletedNames) . ")";
			$dbw->delete('image_feedback',
				array("ii_img_url IN $deletedNames"),
				__METHOD__);
			$affectedRows += $dbw->affectedRows();
		}

		if (sizeof($invalid)) {
			$invalid = "These input urls are never existed:<br><br>" . implode("<br>", $invalid);
		}
		$this->getOutput()->setArticleBodyOnly(true);
		$this->getOutput()->addHtml("$affectedRows reset.$invalid");
	}

	private function handleImageFeedback() {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$out->setArticleBodyOnly(true);
		$imgUrl = substr(trim($req->getVal('imgUrl')), 1);

		// Check if image is a wikiphoto image
		$title = Title::newFromUrl($imgUrl);
		if (!$title || !$title->exists()) {
			return;
		}

		$revision = Revision::newFromTitle($title);
		$uname = strtolower($revision->getUserText());
		$prefix = $req->getVal('voteType') == 'good' ? 'ii_good' : 'ii_bad';

		$dbw = wfGetDB(DB_MASTER);
		$isWikiPhotoImg = ($uname == self::WIKIPHOTO_USER_NAME) ? 1 : 0;
		$voteField = $prefix . '_votes';
		$reasonField = $prefix . '_reasons';
		// Remove / chars from reason since this will be our delimeter in the ii_reason field
		$reason = $req->getVal('reason');
		$reason = $user->getName() . ' says: ' . trim(str_replace('/', '', $reason));
		$reason = $dbw->strencode($reason);
		$row = [
			'ii_img_page_id' => $title->getArticleId(),
			'ii_wikiphoto_img' => $isWikiPhotoImg,
			'ii_page_id' => $req->getInt('aid'),
			'ii_img_url' => substr($title->getLocalUrl(), 1),
			$voteField => 1,
			$reasonField => $reason
		];
		$set = [
			"$voteField = $voteField + 1, $reasonField = CONCAT($reasonField, '/$reason')"

		];
		$dbw->upsert('image_feedback', $row, [], $set);
	}

	public static function getImageFeedbackLink() {
		if (self::isValidPage()) {
			$rptLink = "<a class='rpt_img' href='#'><span class='rpt_img_ico'></span>Helpful?</a>";
		} else {
			$rptLink = "";
		}
		return $rptLink;
	}

	public static function isValidPage() {
		$req = RequestContext::getMain()->getRequest();
		$title = RequestContext::getMain()->getTitle();
		$user = RequestContext::getMain()->getUser();

		if (is_null(self::$allowAnonFeedback)) {
			// Allow anon feedback on ~5% of articles
			self::$allowAnonFeedback = mt_rand(1, 100) <= 5;
		}

		$allowAnonFeedback = self::$allowAnonFeedback;

		$ctx = MobileContext::singleton();
		$isMobileMode = $ctx->shouldDisplayMobileView();

		return $user &&
			(!$user->isAnon() || $allowAnonFeedback) &&
			!$isMobileMode &&
			$title &&
			$title->exists() &&
			$title->inNamespace(NS_MAIN) &&
			$req &&
			$req->getVal('create-new-article') == '' &&
			!self::isMainPage();
	}

	public static function isMainPage() {
		$title = RequestContext::getMain()->getTitle();
		return $title
			&& $title->inNamespace(NS_MAIN)
			&& $title->getText() == wfMessage('mainpage')->text();
	}
}
