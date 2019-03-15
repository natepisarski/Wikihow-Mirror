<?php

if (!defined('MEDIAWIKI')) die();
class VideoEmbedHelperTool extends UnlistedSpecialPage {
	/*
	 * @var string main mustache template (sans extension).
	 */
	const TEMPLATE = 'SpecialVideoEmbedHelperTool';

	public function __construct() {
		parent::__construct('VideoEmbedHelperTool');
	}

	private function formatCitation($citation) {
		if ( $citation[0] == "*" ) {
			return $citation;
		} else {
			return "*" . $citation;
		}
	}

	private function addBulletCitation($wikiText, $citation) {
		$newWikiText = $wikiText;
		$limit = -1;
		$count = 0;

		$citation = self::formatCitation($citation);
		$scHeaderPattern = "/(==\s?Sources\sand\sCitations\s?==\n)(\\n?)/";
		$scHeader = "==Sources and Citations==";
		$newWikiText = preg_replace($scHeaderPattern, "$1$2$citation\n", $wikiText, $limit, $count);

		if ($count < 1) {
			$newWikiText = preg_replace('/(.+)(__[a-z]{4,10}__\s*^.)/', "$1\\n$scHeader\\n$citation\\n$2", $wikiText, $limit, $count);
			if ( $count < 1) {
				$newWikiText = "$wikiText\n$scHeader\n$citation";
			}
		}

		return $newWikiText;
	}


	// Lovingly plagiarized from ImportVideo::updateVideoArticle()
	private function updateVideoArticle($videoTitle, $videoId) {
		$importer = new ImportVideoYoutube("youtube");
		$videoWikiText = $importer->loadVideoText($videoId);
		if ($videoWikiText == null || empty($videoId)) {
			return false;
		}
		$editSummary = $this->msg('evht_addingvideo_summary');
		$a = new Article($videoTitle);
		$a->doEdit($videoWikiText, $editSummary);
		ImportVideo::markVideoAsPatrolled($a->getId());
		return true;
	}

	// Lovingly plagiarized from ImportVideo::updateMainArticle()
	private function updateMainArticle($title, $videoTitle, $videoCitation) {
		$r = Revision::newFromTitle($title);
		if (!$r) {
			return false;
		}
		$text = $r->getText();

		$tag = "{{" . $videoTitle->getFullText() . "|}}";
		$newsection .= "\n\n== " . wfMessage('video') . " ==\n{$tag}\n\n";
		$a = new Article($title);

		$newtext = "";

		// Check for existing video section in the target article
		preg_match("/^==[ ]*" . wfMessage('video') . "/im", $text, $matches, PREG_OFFSET_CAPTURE);
		if (sizeof($matches) > 0 ) {
			// There is an existing video section, replace it
			$i = $matches[0][1];
			preg_match("/^==/im", $text, $matches, PREG_OFFSET_CAPTURE, $i+1);
			if (sizeof($matches) > 0) {
				$j = $matches[0][1];
				// == Video == was not the last section
				$newtext = trim(substr($text, 0, $i)) . $newsection . substr($text, $j, strlen($text));
			} else {
				// == Video == was the last section append it
				$newtext = trim($text) . $newsection;
			}
			// existing section, change it.
		} else {
			// There is not an existing video section, insert it after steps
			// This section could be cleaned up to handle it if there was an existing video section too I guess
			$arr = preg_split('/(^==[^=]*?==\s*?$)/m', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
			$found = false;
			for ($i =0 ; $i < sizeof($arr); $i++) {
				if (preg_match("/^==[ ]*" . wfMessage('steps') . "/", $arr[$i])) {
					$newtext .= $arr[$i];
					$i++;
					if ($i < sizeof($arr))
						$newtext .= $arr[$i];
					$newtext = trim($newtext) . $newsection;
					$found = true;
				} else {
					$newtext .= $arr[$i];
				}
			}
			if (!$found) {
				$arr = preg_split('/(^==[^=]*?==\s*?$)/m', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
				$newtext = "";
				$newtext = trim($arr[0]) . $newsection;
				for ($i =1 ; $i < sizeof($arr); $i++) {
					$newtext .= $arr[$i];
				}
			}
		}
		if ($newtext == "")
			$newtext = $newsection;
		$watch = $title->userIsWatching();
		$newtext = self::addBulletCitation($newtext,$videoCitation);

		$editSummary = $this->msg('evht_addingvideo_summary');
		$a->updateArticle($newtext, $editSummary, false, $watch);

		return true;
	}

	private function youtubeIdFromUrl($url) {
	    $pattern =
		'%^		# Match any youtube URL
		(?:https?://)?  # Optional scheme. Either http or https
		(?:www\.)?      # Optional www subdomain
		(?:             # Group host alternatives
		  youtu\.be/    # Either youtu.be,
		| youtube\.com  # or youtube.com
		  (?:           # Group path alternatives
		    /embed/     # Either /embed/
		  | /v/         # or /v/
		  | /watch\?v=  # or /watch\?v=
		  )             # End path alternatives.
		)               # End host alternatives.
		([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
		$%x'
		;
	    $result = preg_match($pattern, $url, $matches);
	    if ($result) {
		return $matches[1];
	    }
	    return false;
	}

	/*
	 * Return article name from an article
	 * URL, by returning the substring after the
	 * final forward-slash, or if no forward-
	 * slash exists, returning the entire string.
	 *
	 */
	private function getArticleFromUrl($url) {
		$pattern = '/^(?:.*\/)?(.*)$/';

		$url = trim($url);
		$result = preg_match($pattern, $url, $matches);
		if ($result) {
		    return $matches[1];
		}
		else {
		    return false;
		}
	}

	private function getTemplateHtml() {
		$options = [
			'loader' => new Mustache_Loader_FilesystemLoader(
				__DIR__ . '/'
			)
		];
		$m = new Mustache_Engine($options);
		return $m->render(self::TEMPLATE, []);
	}


	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		$user = $wgUser->getName();
		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() || (!in_array('staff', $userGroups) && !in_array('staff_widget', $userGroups))) {
			$wgOut->setRobotPolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($wgRequest->wasPosted()) {
			$wgOut->setArticleBodyOnly(true);
			$citation = $wgRequest->getVal('citation');
			$videoUrl = urldecode($wgRequest->getVal('videoUrl'));
			$videoId = self::youtubeIdFromUrl($videoUrl);
			$target = urldecode($wgRequest->getVal('target'));
			$result = array('success' => false,
					'target' => $target,
					'videoId' => $videoId,
					'videoUrl' => $videoUrl);

			$target = self::getArticleFromUrl($target);
			if (preg_match('/[0-9]{1,8}/', $target)) {
				$title = Title::newFromID((int)$target);
			} else {
				$title = Misc::getTitleFromText($target);
			}

			if ( !$title || !$title->exists() ) {
				$result['success'] = false;
				$result['articleUrl'] = $target;
				$result['error'] = "Article does not exist";
				$wgOut->addHtml(json_encode($result));
				return;
			}

			$result['articleURL'] = $title->getLocalURL();

			$videoTitle = Title::makeTitle(NS_VIDEO, $title->getText());
			$videoSuccess = self::updateVideoArticle($videoTitle, $videoId);
			$articleSuccess = self::updateMainArticle($title, $videoTitle, $citation);

			$result['success'] = $articleSuccess && $videoSuccess;
			$wgOut->addHtml(json_encode($result));
			return;
		} else {
			$wgOut->setHTMLTitle('Video Embed Helper Tool - wikiHow');
			$wgOut->addModules('ext.wikihow.VideoEmbedHelperTool');
			$wgOut->addHTML(self::getTemplateHtml());
		}
	}

}
