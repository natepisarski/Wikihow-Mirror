<?php

require_once(__DIR__ . '/../TranslationLink.php');

class TranslationLinkOverride extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('TranslationLinkOverride');
	}

	private static function httpDownloadHeaders() {
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="translation_link_results.txt"');
	}

	private static function decodeURL($url) {
		$url = urldecode($url);
		$url = preg_replace('@^https:@', 'http:', $url);
		return $url;
	}

	/**
	 * Parse a list of URLs from a tab-delimited file
	 */
	private static function parseURLlist($pageList) {
		$pageList = preg_split("@[\r\n]+@", $pageList);
		$urls = array();
		foreach ($pageList as $line) {
			$line = trim($line);
			$both = preg_split("@\t@", $line);
			if (count($both) == 2) {
				$urls[] = array(
					'url1' => self::decodeURL($both[0]), 'url1_raw' => $both[0],
					'url2' => self::decodeURL($both[1]), 'url2_raw' => $both[1]
				);
			} else {
				// maybe log error here?
			}
		}
		return $urls;
	}

	/**
	 * Gets a list of links that connect to a given URL, and return with Ajax
	 */
	private function fetchLinks($lang, $id) {
		$links = TranslationLink::getLinksTo($lang, $id);
		$json = array();
		foreach ($links as $link) {
			$json[] = array(
				'fromLang' => $link->fromLang,
				'fromID' => $link->fromAID,
				'fromURL' => $link->fromURL,
				'toLang' => $link->toLang,
				'toID' => $link->toAID,
				'toURL' => $link->toURL
			);
		}
		$this->getOutput()->setArticleBodyOnly(true);
		$this->getOutput()->addHTML(json_encode($json));
	}

	/**
	 * Delete a translation link
	 */
	private function doDelete($fromLang, $fromId, $toLang, $toId) {
		TranslationLink::writeLog(TranslationLink::ACTION_DELETE, $fromLang, NULL,
			$fromId, NULL, $toLang, "unknown", $toId, "TranslationLinkOverride");
		$link = new TranslationLink();
		$link->fromAID = $fromId;
		$link->fromLang = $fromLang;
		$link->toAID = $toId;
		$link->toLang = $toLang;

		$this->getOutput()->setArticleBodyOnly(true);
		if ($link->delete()) {
			$this->getOutput()->addHTML(json_encode(array('success'=>true)));
		} else {
			$this->getOutput()->addHTML(json_encode(array('success'=>false)));
		}
	}

	public function execute($par) {
		global $wgActiveLanguages, $wgIsToolsServer, $wgIsDevServer;

		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		if ($user->isBlocked()) {
			$out->blockedPage();
			return;
		}
		$userGroups = $user->getGroups();

		if ($user->getID() == 0
			|| !(in_array('sysop', $userGroups) || in_array('staff', $userGroups))
			|| !($wgIsToolsServer || $wgIsDevServer)
		) {
			$out->setRobotpolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$action = $req->getVal('action', NULL);

		if ($req->wasPosted() && $_FILES['upload']) {
			ini_set('memory_limit','2048M');
			$fileName = $_FILES['upload']['tmp_name'];
			$list = file_get_contents($fileName);
			set_time_limit(0);
			$lines = self::parseURLlist($list);
			self::httpDownloadHeaders();
			$urls = array();
			foreach ($lines as $line) {
				$urls[] = $line['url1'];
				$urls[] = $line['url2'];
			}
			$pageInfoTmp = Misc::getPagesFromURLs($urls,array("page_title","page_id"));
			$pageInfo = [];
			foreach ($pageInfoTmp as $url => $info) {
				$url = str_replace('https', 'http', $url);
				$pageInfo[$url] = $info;
			}
			$links = array();

			foreach ($lines as $line) {
				$link = new TranslationLink();
				$link->fromURL = $line['url1'];
				$link->toURL = $line['url2'];
				if (isset($pageInfo[$line['url1'] ]) && isset($pageInfo[$line['url2'] ])) {
					$p1=$pageInfo[$line['url1'] ];
					$p2=$pageInfo[$line['url2'] ];
					$link->fromLang = $p1['lang'];
					$link->fromAID = $p1['page_id'];
					$link->toLang = $p2['lang'];
					$link->toAID = $p2['page_id'];
				}
				$links[] = $link;
			}
			TranslationLink::batchUpdateTLStatus($links);
			TranslationLink::batchAddTranslationLinks($links);
			foreach ($links as $link) {
				if ($link->fromLang != NULL && $link->fromAID != NULL && $link->toLang != NULL && $link->toAID != NULL) {
					TranslationLink::writeLog(TranslationLink::ACTION_SAVE,$link->fromLang,NULL,$link->fromAID, $link->getFromPage(),$link->toLang, $link->getToPage(),$link->toAID,"TRANSLATION_OVERRIDE");
				}
				if ($link->tlStatus == TranslationLink::TL_STATUS_SAVED) {
					if ($link->oldTlStatus == TranslationLink::TL_STATUS_UPDATEABLE) {
						$message = "Updated existing link";
					} elseif ($link->oldTlStatus == TranslationLink::TL_STATUS_SAVED) {
						$message = "Already saved";
					} elseif ($link->oldTlStatus == TranslationLink::TL_STATUS_NEW) {
						$message = "Saved Link";
					} else {
						$message = "Already saved";
					}
				} elseif ($link->tlStatus == TranslationLink::TL_STATUS_NON_UPDATEABLE) {
					$message = "Conflicting existings links, unable to save";
				} else {
					$message = "Unable to save, URLs invalid or not found";
				}
				print $link->fromURL . "\t" . $link->toURL . "\t" . $message . "\n";
			}
			// Hack to override MediaWiki functionality
			exit;
			return true;
		} elseif ($action == null) {
			EasyTemplate::set_path( dirname(__FILE__) );
			$tmpl = EasyTemplate::html( "TranslationLinkOverride.tmpl.php");
			$out->addHTML($tmpl);
			return true;
		} elseif ($action == "fetchlinks") {
			$id = $req->getVal('id',null);
			$lang = $req->getVal('lang',null);
			$this->fetchLinks($lang, $id);
			return true;
		} elseif ($action == "search") {
			EasyTemplate::set_path(dirname(__FILE__));
			$langs = $wgActiveLanguages;
			$langs[] = 'en';
			$tmpl = EasyTemplate::html("TranslationLinkOverride.delete.tmpl.php",array('langs'=>$langs ));
			$out->addHTML($tmpl);
			return true;
		} elseif ($action == "dodelete") {
			$fromId = $req->getVal('fromId',null);
			$fromLang = $req->getVal('fromLang',null);
			$toId = $req->getVal('toId',null);
			$toLang = $req->getVal('toLang',null);
			$this->doDelete($fromLang,$fromId,$toLang,$toId);
		}
		return false;
	}
}
