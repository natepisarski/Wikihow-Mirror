<?php

global $IP;
require_once("$IP/extensions/wikihow/TranslationLink.php");

class RevertTool extends UnlistedSpecialPage {
  function __construct() {
	    parent::__construct('RevertTool');
	}
	public function revertList($list, $revertUser) {
		global $wgLanguageCode;
		$results = array();

		foreach($list as $l) {
			if(preg_match('@^http://(.+)\.wikihow\.com/(.+)@',$l, $matches)) {

				if(!(($wgLanguageCode=="en" && $matches[1] == "www") || $matches[1] == $wgLanguageCode)) {
					$results[] = array('url' => $l, 'success' => false, 'msg' => "Invalid URL for language");
				}
				else {
					$link = $matches[2];
					$title = Title::newFromUrl($link);
					$article = new Article($title);
					if($article && $article->exists()) {
						$ret=$article->commitRollback($revertUser, wfMessage("mass-revert-message"), TRUE,$resultDetails);
						if(empty($ret)) {
							$results[] = array('url'=> $l, 'success'=> true);
						}
						else {
							$results[] = array('url'=> $l, 'success'=> false, 'msg' => $ret[0][0]);
						}
					}
					else {
						$results[] = array('url' =>$l, 'success' => false, 'msg' => "Article not found");
					}
				}
			}
			else {
				$results[] = array('url' => $l, 'success' => false, 'msg' => "Bad URL");
			}
		}
		return($results);
	}
	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang, $wgLanguageCode;

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}
		$userGroups = $wgUser->getGroups();
    if ($wgUser->getID() == 0 || !(in_array('sysop', $userGroups) || in_array('staff', $userGroups)) ) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
		  return;
		}
		if(!$wgRequest->wasPosted()) {
		  EasyTemplate::set_path( dirname(__FILE__) );
		  $tmpl = EasyTemplate::html( "RevertTool.tmpl.php", array('msg'=>wfMessage("mass-revert-message"), 'msgName' => "mass-revert-message"));
		  $wgOut->addHTML($tmpl);
			return(true);
		}
		else {
			set_time_limit(0);
			$list = $wgRequest->getVal('page-list');
			$revertUser = $wgRequest->getVal('revert-user');
			$list = explode("\n",$list);
			$urls = array();
			if($wgLanguageCode != "en") {
				$pages = Misc::getPagesFromURLs($list);
				foreach($pages as $page) {
					if($page['lang'] == $wgLanguageCode) {
						$urls[] = Misc::getLangBaseURL($page['lang']) . '/' . $page['page_title'];
					}
					else {
						$links = TranslationLink::getLinksTo($page['lang'], $page['page_id']);
						foreach($links as $link) {
							if($link->toLang == $wgLanguageCode) {
								$urls[] = $link->toURL;
							}
						}
					}
				}
			}
			else {
				$urls = $list;
			}

			$results=$this->revertList($urls, $revertUser);
			$wgOut->setArticleBodyOnly(true);
			$wgOut->addHTML(json_encode($results));
			return true;
		}
	}
}
