<?php

global $IP;
require_once("$IP/extensions/wikihow/TranslationLink.php");

class RevertTool extends UnlistedSpecialPage {
	public function __construct() {
	    parent::__construct('RevertTool');
	}

	private function revertList($list, $revertUser) {
		$results = array();
		$langCode = $this->getLanguage()->getCode();

		foreach ($list as $l) {
			if (preg_match('@^http://(.+)\.wikihow\.com/(.+)@',$l, $matches)) {

				if (!(($langCode=="en" && $matches[1] == "www") || $matches[1] == $langCode)) {
					$results[] = array('url' => $l, 'success' => false, 'msg' => "Invalid URL for language");
				} else {
					$link = $matches[2];
					$title = Title::newFromUrl($link);
					$article = new Article($title);
					if ($article && $article->exists()) {
						$ret = $article->commitRollback($revertUser, wfMessage("mass-revert-message"), TRUE,$resultDetails);
						if (empty($ret)) {
							$results[] = array('url'=> $l, 'success'=> true);
						} else {
							$results[] = array('url'=> $l, 'success'=> false, 'msg' => $ret[0][0]);
						}
					} else {
						$results[] = array('url' =>$l, 'success' => false, 'msg' => "Article not found");
					}
				}
			} else {
				$results[] = array('url' => $l, 'success' => false, 'msg' => "Bad URL");
			}
		}
		return($results);
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$langCode = $this->getLanguage()->getCode();

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}
		$userGroups = $user->getGroups();
		if ($user->getID() == 0 || !(in_array('sysop', $userGroups) || in_array('staff', $userGroups)) ) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		if (!$req->wasPosted()) {
			EasyTemplate::set_path( __DIR__ );
			$tmpl = EasyTemplate::html( "RevertTool.tmpl.php", array('msg' => wfMessage("mass-revert-message"), 'msgName' => "mass-revert-message"));
			$out->addHTML($tmpl);
			return true;
		} else {
			set_time_limit(0);
			$list = $req->getVal('page-list');
			$revertUser = $req->getVal('revert-user');
			$list = explode("\n",$list);
			$urls = array();
			if ($langCode != "en") {
				$pages = Misc::getPagesFromURLs($list);
				foreach ($pages as $page) {
					if ($page['lang'] == $langCode) {
						$urls[] = Misc::getLangBaseURL($page['lang']) . '/' . $page['page_title'];
					}
					else {
						$links = TranslationLink::getLinksTo($page['lang'], $page['page_id']);
						foreach ($links as $link) {
							if ($link->toLang == $langCode) {
								$urls[] = $link->toURL;
							}
						}
					}
				}
			}
			else {
				$urls = $list;
			}

			$results = $this->revertList($urls, $revertUser);
			$out->setArticleBodyOnly(true);
			$out->addHTML(json_encode($results));
			return true;
		}
	}
}
