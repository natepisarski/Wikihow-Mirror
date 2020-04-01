<?php

if (!defined('MEDIAWIKI')) die();

class VideoEmbedHelperTool extends UnlistedSpecialPage {

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

		$citation = $this->formatCitation($citation);
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
		$wikiPage = WikiPage::factory($videoTitle);
		$content = ContentHandler::makeContent($videoWikiText, $videoTitle);
		$wikiPage->doEditContent($content, $editSummary);
		ImportVideo::markVideoAsPatrolled($wikiPage->getId());
		return true;
	}

	// Lovingly plagiarized from ImportVideo::updateMainArticle()
	private function updateMainArticle($title, $videoTitle, $videoCitation) {
		$r = Revision::newFromTitle($title);
		if (!$r) {
			return false;
		}
		$text = ContentHandler::getContentText( $r->getContent() );

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
		if (!$newtext) {
			$newtext = $newsection;
		}
		$watch = $this->getUser()->isWatched($title);
		$newtext = $this->addBulletCitation($newtext, $videoCitation);

		$editSummary = $this->msg('evht_addingvideo_summary');
		$a->updateArticle($newtext, $editSummary, false, $watch);

		return true;
	}

	private function youtubeIdFromUrl($url) {
		$pattern =
		'%^             # Match any youtube URL
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

	public function execute($par) {
		$userGroups = $user->getGroups();
		if ($user->isBlocked() || (!in_array('staff', $userGroups) && !in_array('staff_widget', $userGroups))) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$citation = $req->getVal('citation');
			$videoUrl = urldecode($req->getVal('videoUrl'));
			$videoId = $this->youtubeIdFromUrl($videoUrl);
			$target = urldecode($req->getVal('target'));
			$result = array('success' => false,
					'target' => $target,
					'videoId' => $videoId,
					'videoUrl' => $videoUrl);

			$target = $this->getArticleFromUrl($target);
			if (preg_match('/[0-9]{1,8}/', $target)) {
				$title = Title::newFromID((int)$target);
			} else {
				$title = Misc::getTitleFromText($target);
			}

			if ( !$title || !$title->exists() ) {
				$result['success'] = false;
				$result['articleUrl'] = $target;
				$result['error'] = "Article does not exist";
				$out->addHtml(json_encode($result));
				return;
			}

			$result['articleURL'] = $title->getLocalURL();

			$videoTitle = Title::makeTitle(NS_VIDEO, $title->getText());
			$videoSuccess = $this->updateVideoArticle($videoTitle, $videoId);
			$articleSuccess = $this->updateMainArticle($title, $videoTitle, $citation);

			$result['success'] = $articleSuccess && $videoSuccess;
			$out->addHtml(json_encode($result));
		} else {
			$out->setHTMLTitle('Video Embed Helper Tool - wikiHow');
			$out->addModules('ext.wikihow.VideoEmbedHelperTool');
			$out->addHTML($this->getTemplateHtml());
		}
	}

}
