<?php

global $IP;
require_once("$IP/extensions/wikihow/TranslationLink.php");
require_once("$IP/extensions/wikihow/alfredo/ImageTransfer.class.php");

class Alfredo extends UnlistedSpecialPage {
	private $langs;
	private $error;

	const ALFREDO_API_ENDPOINT = "https://alfredo.wikiknowhow.com/Special:Alfredo";

	// Used to store results of batch fetching pages from other sites
	private static $pageCache=array();

	public function __construct() {
		parent::__construct("Alfredo");
		$this->langs = Misc::getActiveLanguageNames();
	}

	/**
	 * Show the HTML template for adding images to URLs
	 */
	private function showTemplate() {
		global $wgOut, $wgActiveLangs;

		EasyTemplate::set_path( __DIR__ );
		$tmpl = EasyTemplate::html("Alfredo.tmpl.php", array('langs'=>$this->langs));

		$wgOut->addScript(HtmlSnips::makeUrlTag('/extensions/wikihow/common/download.jQuery.js'));
		$wgOut->addHTML($tmpl);
	}

	/**
	 * Get the protocol and host of API
	 * @param lang Language of the host
	 * @return Hash-array with protocol and host to connect to for API
	 */
	public static function getAPIInfo($lang) {
		global $wgServer;
		$useConstants = !preg_match("@(([^:]+):)?//([^.]{3}.+)@",$wgServer,$matches);
		if (!$useConstants) {
			$pos = strpos($wgServer, '//');
			$domain = $pos === false ? $wgServer : substr($wgServer, $pos + 2);
			$useConstants = in_array($domain, wfGetAllCanonicalDomains());
		}
		if ($useConstants) {
			if (!defined('WH_API_HOST') || !defined('WH_API_PROTOCOL')) {
				throw new Exception("WH_API_HOST and WH_API_PROTOCOL must be defined to fetch Alfredo pages from the command line or non-English site");
			}
			$host = WH_API_HOST;
			$protocol = WH_API_PROTOCOL;
		}
		else {
			$host = $matches[3];
			$protocol = $matches[2];
		}
		if ($lang != 'en') {
			$host = $lang . '.' . $host;
		}
		return(array('protocol'=>$protocol, 'host'=>$host));
	}

	/**
	 * Fetch multiple page ids and cache results
	 * @param lang Language code of site
	 * @param pageIds Array of page ids
	 * @return array of hash array with  steps, hash of page text, and image tag
	 */
	public static function batchFetchPages($lang, $pageIds) {
		$ai = self::getAPIInfo($lang);
		$url = self::ALFREDO_API_ENDPOINT;
		$headers = array();
		$headers[] = "Host: " . $ai['host'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array("api"=>1,"articleIds"=>implode(",",$pageIds), "auth"=>WH_DEV_ACCESS_AUTH));
		curl_setopt($ch, CURLOPT_USERPWD, WH_DEV_ACCESS_AUTH);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$text = curl_exec($ch);
		if (curl_errno($ch)) {
			die( "error from $url: " . curl_error($ch) );
		}
		curl_close($ch);

		$json = json_decode($text, true);
		if (!$json) {
			//die( "error from $url: empty response from local API" );
			//exit;
		}
		if ($json) {
			foreach ($json as $id=>$page) {
				self::$pageCache[$lang][$id] = $page;
			}
		}

		return($json);

	}

  /**
   * Get steps info about a page utilizing caching
	 * @param lang Language code of site
	 * @param pageId Id of the page
	 * @return array with 'steps',  hash of page text, and image tag
	 */
	public static function fetchPage($lang,$pageId) {
		if (isset(self::$pageCache[$lang][$pageId])) {
			return(self::$pageCache[$lang][$pageId]);
		}
		$ai = self::getAPIInfo($lang);
		$url = self::ALFREDO_API_ENDPOINT;
		$headers = array();
		$headers[] = "Host: " . $ai['host'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array("api"=>1,"articleIds"=>$pageId, "auth"=>WH_DEV_ACCESS_AUTH));
		curl_setopt($ch, CURLOPT_USERPWD, WH_DEV_ACCESS_AUTH);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$text = curl_exec($ch);
		curl_close($ch);

		$json = json_decode($text, true);
		self::$pageCache[$lang][$pageId] = $json[$pageId];

		return($json[$pageId]);
	}

	/**
	 * Add images to translation table for automatically adding to
	 * translated pages
	 */
	private function addImage($pageId, $toLang, $toAID) {
		global $wgUser;

		$it = new ImageTransfer();
		$it->fromLang = "en";
		$it->fromAID = $pageId;
		$it->toLang = $toLang;
		$it->toAID = $toAID;
		$it->creator = $wgUser->getName();
		$it->timeStarted = wfTimestampNow();

		$it->insert();
	}

	/**
	  * Add a bunch of URLs to the database for adding their images to various languages
		* @param urls New-line seperated list of URLs or Article ids from POST
		* @param langs Comma-seperated list of languages to add the images on
		*/
	private function addImages($urls, $langs) {
		$urls = preg_split("@[\r\n]+@",Misc::getUrlDecodedData($urls));
		$langs = preg_split("@,@",urldecode($langs));

		$realUrls = array();
		$langIds = array();
		foreach ($urls as $url) {
			if (is_numeric($url)) {
				$langIds[] = array("lang"=>"en","id"=>$url);
			}
			else {
				$realUrls[] = $url;
			}
		}
		$pagesA = Misc::getPagesFromURLs($realUrls, array('page_id', 'page_title'));
		$badURLs = array();
		$results = array();

		foreach ($realUrls as $url) {
			if (!isset($pagesA[$url])) {
				foreach ($langs as $lang) {
					ImageTransfer::addBadURL($url, $lang, "URL not found");
				}
				$results[] = array('fromURL' => $url, 'toURL' => '', 'status' => 'Bad URL');

			}
		}

		$pagesB = Misc::getPagesFromLangIds($langIds, array('page_id','page_title'));
		$pages = array_merge($pagesA, $pagesB);

		$fromIDs = array();
		$urlLookup = array();
		foreach ($pages as $page) {
			$fromIDs[] = $page['page_id'];
			$urlLookup[$page['page_id']] = Misc::getLangBaseURL('en') . '/' . $page['page_title'];
		}
		if (sizeof($fromIDs) == 0) {
			return(array());
		}
		// Fetch to cache the pages for later use in addImage
		$this->batchFetchPages("en", $fromIDs);

		$langTLs = array();
		foreach ($langs as $lang) {
			$langTLs[$lang] = TranslationLink::getLinks("en",$lang,array("tl_from_aid in (" . implode(',', $fromIDs) . ")"));
			$newFromIds = array_map( function($m) {
				return($m->fromAID);
			},$langTLs[$lang]);
			foreach ($fromIDs as $id) {
				//Add error links
				if (!in_array($id, $newFromIds)) {
					$this->addImage($id, $lang, 0);
					if (isset($urlLookup[$id])) {
						$results[] = array('fromURL' => $urlLookup[$id], 'toURL' => '', 'status' => ('Could not find any translation link to ' . $lang));
					}
				}
			}
			TranslationLink::batchPopulateURLs($langTLs[$lang]);

			// Cache the other language pages too in a batch
			$langIds = array();
			foreach ($langTLs[$lang] as $tl) {
					$langIds[] = $tls->toAID;
			}
			$this->batchFetchPages($lang, $langIds);
		}
		foreach ($langs as $lang) {
			foreach ($langTLs[$lang] as $tl) {
				if (!$this->addImage($tl->fromAID, $tl->toLang, $tl->toAID)) {
					$results[] = array('fromURL' => $tl->fromURL, 'toURL'=> $tl->toURL, 'status' => 'Queued');
				}
				else {
						$results[] = array('fromURL' => $tl->fromURL, 'toURL'=> $tl->toURL, 'status' => 'Failed to queue');
				}
			}
		}
		return($results);
	}
	/**
	 * Handle API calls to get the steps from an article
	 */
	private function doAPI() {
		global $wgRequest, $wgOut, $wgContLang;
		$articleIds = $wgRequest->getVal("articleIds");
		$articleIds = preg_split("@,@", $articleIds);

		$dbr = wfGetDB(DB_REPLICA);
		$wgOut->setArticleBodyOnly(true);

		$articles = array();
		foreach ($articleIds as $articleId) {
			if (is_numeric($articleId)) {
				$r = Revision::loadFromPageId($dbr, $articleId);
				if ($r) {
					$txt = ContentHandler::getContentText( $r->getContent() );
					$intro = Wikitext::getIntro($txt);
					$text = Wikitext::getStepsSection($txt, true);
					$lines = preg_split("@\n@",$text[0]);
					$text = "";

					// We remove extra lines technically in the 'steps' section, but which don't actually contain steps
					// Find the last line starting with a '#'
					$lastLine = 0;
					$n = 0;
					foreach ($lines as $line) {
						if ($line[0] == '#') {
							$lastLine = $n;
						}
						$n++;
					}

					// Truncate lines after the last line with a '#'
					$n = 0;
					foreach ($lines as $line) {
						if ($n > $lastLine) {
							break;
						}
						if ($n != 0) {
							$text .= "\n";
						}
						$text .= $line;
						$n++;
					}
					if (strlen($text) > 0) {
						$articles[$articleId] = array("steps" => $text,
																					"intro" => $intro,
																					"altImageTags" => array($wgContLang->getNSText(NS_IMAGE)));
					}
				}
			}
		}
		$wgOut->addHTML(json_encode($articles));
	}

	/**
	 * Format URL for link
	 */
	static private function partialURLEncode($url) {
		$domainRegex = wfGetDomainRegex(
			false, // mobile?
			true, // includeEn?
			true // capture?
		);
		if (preg_match('@http://' . $domainRegex . '/(.+)@', $url, $matches)) {
			return 'http://' . $matches[1] . '/' . urlencode(urldecode($matches[2]));
		}
		else {
			return($url);
		}
	}

	/**
	 * Process a post request to add user URLs to Alfredo
	 */
	private function doAddURLs() {
		global $wgRequest, $wgOut;

		$urls = $wgRequest->getVal("urls");
		$langs = $wgRequest->getVal("langs");
		$results = $this->addImages($urls, $langs);

		//Give a TSV output on what TLs we have for doing the transfer
		$wgOut->setArticleBodyOnly(true);
		header('Content-Type: text/tsv');
		header('Content-Disposition: attachment; filename="output.xls"');

		$output = "From URL\tTo URL\n";
		foreach ($results as $result) {
			$output .= self::partialURLEncode($result['fromURL']) . "\t" . self::partialURLEncode($result['toURL']) . "\t" . $result['status'] . "\n";
		}
		$wgOut->addHTML($output);

	}

	/**
	 * Execute function
	 */
	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLanguageCode;
		global $wgIsToolsServer, $wgIsDevServer;

		if (!$wgIsToolsServer && !$wgIsDevServer) {
			$wgOut->setRobotPolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($wgRequest->wasPosted()) {
			$api = $wgRequest->getVal("api");

			if ($api == 1) {
				$auth = $wgRequest->getVal("auth");
				if ($auth != WH_DEV_ACCESS_AUTH) {
					$wgOut->setRobotPolicy('noindex,nofollow');
					$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
					return;
				}
				$this->doAPI();
			}
			else {
				if (!in_array('staff',$wgUser->getGroups())) {
			      $wgOut->setRobotPolicy('noindex,nofollow');
				    $wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
						return;
				}
				if ($wgLanguageCode != "en") {
			    $wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
				}
				$this->doAddURLs();
			}
		}
		else {
			if (!in_array('staff',$wgUser->getGroups())) {
				$wgOut->setRobotPolicy('noindex,nofollow');
				$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
				return;
			}
			if ($wgLanguageCode != "en") {
			    $wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			}

			$this->showTemplate();
		}
	}
}

