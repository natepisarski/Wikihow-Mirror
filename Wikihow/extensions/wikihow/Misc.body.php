<?php

//
// We don't really have a place to put random, small pieces of functionality.
// This class addresses that.
//
class Misc {

	public static $referencesCount = null;

	/*
	 * adminPostTalkMessage
	 * - returns true/false
	 *
	 * $to_user = User object of who is getting this talk message
	 * $from_user = User object of who is sending this talk message
	 * $comment = The text that is displayed in the talk page message
	 */
	public static function adminPostTalkMessage($to_user, $from_user, $comment) {
		global $wgLang;
		$existing_talk = '';

		//make sure we have everything we need...
		if (empty($to_user) || empty($from_user) || empty($comment)) return false;

		$formattedComment = TalkPageFormatter::createComment( $from_user, $comment );

		$talkPage = $to_user->getUserPage()->getTalkPage();

		if ($talkPage->getArticleId() > 0) {
			$r = Revision::newFromTitle($talkPage);
			$existing_talk = ContentHandler::getContentText( $r->getContent() ) . "\n\n";
		}
		$text = $existing_talk . $formattedComment ."\n\n";

		$flags = EDIT_FORCE_BOT | EDIT_SUPPRESS_RC;

		$wikiPage = WikiPage::factory($talkPage);
		$content = ContentHandler::makeContent($text, $talkPage);
		$result = $wikiPage->doEditContent($content, "", $flags);

		return $result->isOK();
	}

	public static function getDTDifferenceString($date, $isUnixTimestamp = false) {
		if (empty($date)) {
			return "No date provided";
		}

		if ($isUnixTimestamp) {
			$unix_date = $date;
		} else {
			$date = $date . " UTC";
			$unix_date = strtotime($date);
		}

		$now = time();
		$lengths = array("60","60","24","7","4.35","12","10");

		// check validity of date
		if (empty($unix_date)) {
			return "Bad date: $date";
		}

		// is it future date or past date
		if ($now > $unix_date) {
			$difference = $now - $unix_date;
			$tenseMsg = 'rcwidget_time_past_tense';
		} else {
			$difference = $unix_date - $now;
			$tenseMsg = 'rcwidget_time_future_tense';
		}

		for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
			$difference /= $lengths[$j];
		}
		$difference = round($difference);

		if ($difference != 1) {
			$periods = array(wfMessage("second-plural")->text(), wfMessage("minute-plural")->text(), wfMessage("hour-plural")->text(), wfMessage("day-plural")->text(),
						wfMessage("week-plural")->text(), wfMessage("month-plural")->text(), wfMessage("year-plural")->text(), wfMessage("decade-plural")->text());
		} else {
			$periods = array(wfMessage("second")->text(), wfMessage("minute")->text(), wfMessage("hour")->text(), wfMessage("day")->text(),
						wfMessage("week")->text(), wfMessage("month-singular")->text(), wfMessage("year-singular")->text(), wfMessage("decade")->text());
		}

		return wfMessage($tenseMsg, $difference, $periods[$j])->text();
	}

	// Format a binary number
	/*public static function formatBinaryNum($n) {
		return sprintf('%032b', $n);
	}*/

	// Check if an $ip address (string) is within an IP network
	// and netmask, defined in $range (string).
	//
	// Note: $ip and $range need to be perfectly formatted!
	/*public static function isIpInRange($ip, $range) {
		list($range, $maskbits) = explode('/', $range);
		list($i1, $i2, $i3, $i4) = explode('.', $ip);
		list($r1, $r2, $r3, $r4) = explode('.', $range);
		$numi = ($i1 << 24) | ($i2 << 16) | ($i3 << 8) | $i4;
		$numr = ($r1 << 24) | ($r2 << 16) | ($r3 << 8) | $r4;
		$mask = 0;
		for ($i = 1; $i <= $maskbits; $i++) {
			$mask |= 1 << (32 - $i);
		}
		$masked = $numi & $mask;
		//print self::formatBinaryNum($masked) . ' ' .
		//	self::formatBinaryNum($numr) . ' ' .
		//	self::formatBinaryNum($numi) . "\n";
		return $masked === $numr;
	}*/

	/**
	 * Send a file to the user that forces them to download it.
	 */
	public static function outputFile($filename, &$output, $mimeType  = 'text/tsv') {
		$req = RequestContext::getMain()->getRequest();
		$out = RequestContext::getMain()->getOutput();
		$out->disable();
		$req->response()->header('Content-Type: ' . $mimeType);
		$req->response()->header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
		print $output;
	}

	// Makes a url given a dbkey or page title string
	public static function makeUrl($pageTitle, $domain = 'www.wikihow.com') {
		$pageTitle = str_replace(' ', '-', $pageTitle);
		return "https://$domain/" . urlencode($pageTitle);
	}

	// Url decode string data.  Decode it twice in the case where the user
	// inputted string may include urls already encode
	public static function getUrlDecodedData($data, $decodePlusSign = true) {
		// Keep the plusses around
		$decoded = $data;
		if ($decodePlusSign) {
			$decoded = preg_replace("@\+@", "%2B", $decoded);
		}
		$decoded = urldecode($decoded);
		if ($decodePlusSign) {
			$decoded = preg_replace("@\+@", "%2B", $decoded);
		}
		$decoded = urldecode($decoded);

		return $decoded;
	}

	/**
	  * Get database for a given language code
	  */
	public static function getLangDB($lang) {
		global $wgWikiHowLanguages;
		if ($lang == "en") {
			return WH_DATABASE_NAME_EN;
		} elseif (in_array($lang, $wgWikiHowLanguages)) {
			return 'wikidb_' . $lang;
		} else {
			#throw new Exception("$lang is not a WikiHow language in getLangDB");
			return '';
		}
	}

	/**
	 * Get a base URL for a given language code
	 */
	public static function getLangBaseURL($lang = '', $mobile = false) {
		return 'https://' . wfCanonicalDomain($lang, $mobile);
	}

	/**
	 * Get a base URL for a given language code
	 * Deprecated: use wfCanonicalDomain() function instead
	 */
	public static function getLangDomain($lang, $mobile = false) {
		wfDeprecated( __METHOD__, '1.23' );
		return wfCanonicalDomain($lang, $mobile);
	}

	/**
	 * Get the canonical domain, including alts
	 */
	public static function getCanonicalDomain($lang = '', $mobile = false): string {
		global $domainName;

		$altDomains = AlternateDomain::getAlternateDomains();
		foreach ($altDomains as $domain) {
			if (strstr($domainName, $domain)) {
				return ($mobile ? 'm.' : 'www.') . $domain;
			}
		}
		return wfCanonicalDomain($lang, $mobile);
	}

	/**
	 * Get a language code and partial URL from a full URL
	 */
	public static function getLangFromFullURL($url, $mobile = false) {
		$domainRegex = wfGetDomainRegex(
			$mobile,
			true, // includeEn?
			true // capture?
		);
		if (preg_match('@^(https?:)?//' . $domainRegex . '/([^?]+)@', $url, $matches)) {
			$domain = $matches[2];
			$langCode = wfGetLangCodeFromDomain($domain);
			$path = $matches[3];
			return array($langCode, $path);
		}
		return array('', '');
	}

	/**
	 * Infer the language code from a full URL. Works for mobile and desktop.
	 */
	public static function getLangFromURL(string $url): string {
		$url = trim($url);
		$isMobile = preg_match('@^https?://([a-zA-Z]{2}\.)?m\.@', $url) === 1;
		list($lang, $partial) = self::getLangFromFullURL($url, $isMobile);
		return $lang;
	}

	/**
	 * Get pages from language ids
	 * @param langIds List of language ids as an array-hash array('lang'=> ,'id'=>)
	 *
	 */
	public static function getPagesFromLangIds($langIds, $cols=array()) {
		$ll = array();
		foreach($langIds as $li) {
			$ll[$li['lang']][] = $li['id'];
		}
		$dbr = wfGetDB(DB_REPLICA);

		$pages = array();
		foreach ($ll as $l => $ids) {
			$tables = self::getLangDB($l) . '.page';
			$fields = $cols ? $cols : '*';
			$where = [ 'page_id' => $ids ];
			$res = $dbr->select($tables, $fields, $where);
			foreach ($res as $row) {
				$row = get_object_vars($row);
				$pages[$l][$row['page_id'] ] = array_merge($row, array('lang'=>$l));
			}
		}
		$rows = array();
		foreach ($langIds as $li) {
			if (isset($pages[$li['lang']][$li['id']])) {
				$rows[] = $pages[$li['lang']][$li['id']];
			} else {
				$rows[] = array('page_id'=>$li['id'],'lang'=>$li['lang']);
			}
		}

		return $rows;
	}

	/**
	 * Fetch pages for desktop urls in multiple languages
	 * @param $urls array of urls. These URLs should be decoded before being passed to function
	 * @return Hash-map of URL to pages
	 */
	public static function getPagesFromURLs($urls, $cols=array(), $includeRedirects=false) {
		global $wgActiveLanguages;
		$urlsByLang = array();
		$dbr = wfGetDB(DB_REPLICA);

		foreach ($urls as $url) {
			list($lang, $partial) = self::getLangFromFullURL($url);
			if ($lang && $partial) {
				$urlsByLang[$lang][] = $partial;
			}
		}
		if (!empty($cols) && !in_array('page_title', $cols)) {
			// page_title is required
			$cols[] = 'page_title';
		}
		$results = array();
		foreach ($urlsByLang as $lang => $titles) {
			$tables = self::getLangDB($lang) . '.page';
			$fields = $cols ? $cols : '*';
			$where = [
				'page_title' => $titles,
				'page_namespace' => NS_MAIN
			];
			if (!$includeRedirects) {
				$where['page_is_redirect'] = 0;
			}
			$baseURL = self::getLangBaseURL($lang);
			$res = $dbr->select($tables, $fields, $where);
			foreach ($res as $row) {
				$row = get_object_vars($row);
				$row['lang'] = $lang;
				$results[$baseURL . '/' . $row['page_title']] = $row;
			}
		}
		return $results;
	}

	/**
	 * Get just the page-name part of the url for any page on wikiHow.
	 */
	public static function fullUrlToPartial($url) {
		$domainRegex = wfGetDomainRegex(
			false, // mobile?
			true // includeEn?
		);
		if (preg_match('@https?://' . $domainRegex . '/(.+)@', $url, $matches)) {
			return $matches[1];
		} else {
			return "";
		}
	}

	/**
	 * Rollout of a feature based on $pageId of article
	 * @param int $startTime time in seconds since Jan 1, 1970 (unix time)
	 * @param int $duration time in seconds that the rollout period should last
	 * @param int $pageId the page id
	 * @return int true if and only if article should be rolled out
	 *
	 * Example:
	 *  $startTime = strtotime('March 20, 2013');
	 *  $twoWeeks = 2 * 7 * 24 * 60 * 60;
	 *  $pageId = 2053;
	 *  $rolloutArticle = Misc::percentileRolloutByPageId($startTime, $twoWeeks, $pageId);
	 */
	public static function percentileRolloutByPageId( $startTime, $duration, $pageId, $currentTime = 0 ) {
		$time = $currentTime ?: time();
		if ( !$pageId ) {
			global $wgTitle;
			if ( !$wgTitle ) {
				return true;
			} else {
				return self::percentileRollout($startTime, $duration, $wgTitle);
			}
		}

		if ( $time < $startTime ) {
			return false;
		}

		if ( $time > $startTime + $duration ) {
			return true;
		}

		// we use a prime so that the numbers are more evenly distributed with
		// modulo operator
		$modulus = 100003;
		$timeRank = ( $time - $startTime ) / $duration;
		$articleRank = ($pageId % $modulus) / $modulus;
		return $timeRank > $articleRank;
	}

	/**
	 * Rollout of a feature based on $wgTitle.
	 * @param int $startTime time in seconds since Jan 1, 1970 (unix time)
	 * @param int $duration time in seconds that the rollout period should last
	 * @return int true if and only if article should be rolled out
	 *
	 * Example:
	 *  $startTime = strtotime('March 20, 2013');
	 *  $twoWeeks = 2 * 7 * 24 * 60 * 60;
	 *  $rolloutArticle = Misc::percentileRollout($startTime, $twoWeeks);
	 */
	public static function percentileRollout($startTime, $duration, $titleObj = null, $currentTime = 0) {
		if (!$titleObj) {
			global $wgTitle;
			$title = $wgTitle ? $wgTitle->getText() : '';
		} elseif (is_string($titleObj)) {
			$title = $titleObj;
		} else {
			$title = $titleObj->getText();
		}
		$crc = crc32($title);
		$time = $currentTime ?: time();
		if ($time < $startTime) return false;
		if ($time > $startTime + $duration) return true;
		$timeRank = ($time - $startTime) / $duration;
		// we use a prime so that the numbers are more evenly distributed with
		// modulo operator
		$modulus = 100003;
		$articleRank = ($crc % $modulus) / $modulus;
		return $timeRank > $articleRank;
	}

	/**
	 * Generate a string of random characters
	 */
	public static function genRandomString($chars = 20) {
		$str = '';
		$set = array(
			'0','1','2','3','4','5','6','7','8','9',
			'a','b','c','d','e','f','g','h','i','j','k','l','m',
			'n','o','p','q','r','s','t','u','v','w','x','y','z',
			'A','B','C','D','E','F','G','H','I','J','K','L','M',
			'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
		);
		for ($i = 0; $i < $chars; $i++) {
			$r = mt_rand(0, count($set) - 1);
			$str .= $set[$r];
		}
		return $str;
	}

	/**
	 * Get list of active languages with their names
	 */
	public static function getActiveLanguageNames() {
		global $wgActiveLanguages, $wgLanguageNames;

		$languageInfo[] = array('languageCode' => 'en', 'languageName' => 'English');
		foreach ($wgActiveLanguages as $lang) {
			$languageInfo[] = array('languageCode' => $lang, 'languageName' => Language::fetchLanguageName($lang));
		}

		return $languageInfo;
	}

	/**
	 * Login to MediaWiki as a specific user while running script
	 * @param $user The username of the user to login as
	 * @param $forceBot if true, we will ensure we are in the bot group
	 */
	public static function loginAsUser($user, $forceBot=true) {
		global $wgUser;
		// next 2 lines taken from maintenance/deleteDefaultMessages.php
		$wgUser = User::newFromName($user);
		if ($forceBot && !in_array('bot',$wgUser->getGroups())) {
			$wgUser->addGroup('bot');
		}
	}

	// Used in WikihowSkin
	public static function capitalize($str) {
		if (mb_strlen($str) == 0) {
			return $str;
		}

		$fc = mb_substr($str,0,1);
		$fc = mb_strtoupper($fc);
		if (mb_strlen($str) > 1) {
			$ros = mb_substr($str,1);
			return $fc . $ros;
		} else {
			return $fc;
		}
	}

	//quick and dirty function to change numbers to words
	//e.g. 5 returns five
	//- only works 0-40
	//- fallback is it returns what you put in
	//- $upbound for upper end boundary (in case we only want smaller numbers translated)
	public static function numToWord($num,$upbound = 0) {
		global $wgLanguageCode;

		$num = (int)$num;
		if (!is_int($num)) return $num; //really a number, right?
		if ($wgLanguageCode != 'en') return $num; //English only...for now
		if ($upbound > 0 && $num > $upbound) return $num; //boundary check

		switch ($num) {
			case 0: return 'zero';
			case 1: return 'one';
			case 2: return 'two';
			case 3: return 'three';
			case 4: return 'four';
			case 5: return 'five';
			case 6: return 'six';
			case 7: return 'seven';
			case 8: return 'eight';
			case 9: return 'nine';
			case 10: return 'ten';
			case 11: return 'eleven';
			case 12: return 'twelve';
			case 13: return 'thirteen';
			case 14: return 'fourteen';
			case 15: return 'fifteen';
			case 16: return 'sixteen';
			case 17: return 'seventeen';
			case 18: return 'eighteen';
			case 19: return 'nineteen';
			case 20: return 'twenty';
			case 21: return 'twenty-one';
			case 22: return 'twenty-two';
			case 23: return 'twenty-three';
			case 24: return 'twenty-four';
			case 25: return 'twenty-five';
			case 26: return 'twenty-six';
			case 27: return 'twenty-seven';
			case 28: return 'twenty-eight';
			case 29: return 'twenty-nine';
			case 30: return 'thirty';
			case 31: return 'thirty-one';
			case 32: return 'thirty-two';
			case 33: return 'thirty-three';
			case 34: return 'thirty-four';
			case 35: return 'thirty-five';
			case 36: return 'thirty-six';
			case 37: return 'thirty-seven';
			case 38: return 'thirty-eight';
			case 39: return 'thirty-nine';
			case 40: return 'forty';
		}
		return $num;
	}

	/**
	 *
	 *	 d888b  d88888b d888888b d88888b .88b  d88. d8888b. d88888b d8888b. d88888b d888888b db      d88888b
	 *	88' Y8b 88'     `~~88~~' 88'     88'YbdP`88 88  `8D 88'     88  `8D 88'       `88'   88      88'
	 *	88      88ooooo    88    88ooooo 88  88  88 88oooY' 88ooooo 88   88 88ooo      88    88      88ooooo s
	 *	88  ooo 88~~~~~    88    88~~~~~ 88  88  88 88~~~b. 88~~~~~ 88   88 88~~~      88    88      88~~~~~
	 *	88. ~8~ 88.        88    88.     88  88  88 88   8D 88.     88  .8D 88        .88.   88booo. 88.
	 *	 Y888P  Y88888P    YP    Y88888P YP  YP  YP Y8888P' Y88888P Y8888D' YP      Y888888P Y88888P Y88888P
	 *
	 * Use this to get a compressed string of css or javascript from multiple
	 * files on the local file system. The files will be concatenated into a
	 * single string.
	 *
	 * @param string $scriptType 'css' or 'js'
	 * @param array $filenames filenames of local files. Note: for security, never
	 *   generate this variable from user input! Always pass in static strings.
	 * @param mixed some sort of cache invalidator. Uses WH_SITEREV in production
	 *   by default, and WH_SITEREV . max(filemtime($filenames)) in developement.
	 * @param boolean $flipCss Transform the CSS for right-to-left languages
	 *
	 * @example <code>
	 * $embedStr = Misc::getEmbedFiles('css', [__DIR__ . "/foo.css", __DIR__ . "/bar.css"]);
	 * $outputPage->addHTML('<style>' . $embedStr . '</style>');
	 * </code>
	 */
	public static function getEmbedFiles($scriptType, $filenames, $cacheInvalidator = null, $flipCss = false) {
		global $wgIsDevServer;
		if ($scriptType == 'css') {
			$filter = 'minify-css';
		} elseif ($scriptType == 'js') {
			$filter = 'minify-js';
		} else {
			throw new Exception("Unrecognized scriptType '$scriptType' in " . __METHOD__);
		}

		if (!$filenames || !is_array($filenames)) {
			throw new Exception("No array of filenames provided in " . __METHOD__);
		}

		if ($cacheInvalidator === null) {
			if ($wgIsDevServer) {
				// Attach the latest modified file timestamp to the invalidator
				$cacheInvalidator = WH_SITEREV
					. array_reduce(
						$filenames,
						function ($max, $filename) {
							return max($max, filemtime($filename));
						},
						-PHP_INT_MAX
					);

				// Check if any JS files ending in ".compiled.js" need to be
				// compiled still, and throw an error on dev. If you see this
				// error, you should make sure that the file you've edited
				// has been compiled to its final form. If you haven't done
				// this step, your changes won't be reflected in testing or
				// after being pushed to production.
				if ($scriptType == 'js') {
					foreach ($filenames as $filename) {
						$origFilename = preg_replace('@\.compiled\.js$@', '.js', $filename, -1, $count);
						if ($count > 0
							&& file_exists($origFilename)
							&& filemtime($origFilename) > filemtime($filename)
						) {
							// Original has been modified more recently than compiled
							// so we raise an exception.
							throw new MWException("ERROR: it appears that the file '$origFilename' needs to be " .
								"compiled into '$filename'. This error occurs on dev only to help ensure JS " .
								"files that should be compiled by hand are compiled before reaching production.");
						}
					}
				}
			} else {
				$cacheInvalidator = WH_SITEREV;
			}
		}

		// NOTE: make sure $filename is passed in a static string. Could cause
		// big security issues if it's derived from user input.
		$cache = null;
		if (function_exists('apc_fetch')) {
			$cache = wfGetCache(CACHE_ACCEL);
		} else {
			$cache = wfGetCache(CACHE_MEMCACHED);
		}

		$filesKey = md5(implode(':', $filenames));
		$cachekey = wfMemcKey('embedf', $filesKey, $cacheInvalidator);
		$res = $cache->get($cachekey);
		if (!is_string($res)) {
			$res = '';
			foreach ($filenames as $filename) {
				$contents = file_get_contents($filename);
				if ($contents !== false) {
					$res .= $contents;
				} elseif ($wgIsDevServer) {
					// Your friendly neighbourhood missing file detection service
					throw new MWException("ERROR: embed JS file '$filename' could not be loaded. " .
						"We show this error on dev only so that you can correct the problem before " .
						"the problem gets to production.");
				}
			}

			// set cache regardless of whether file was found, but give it
			// a lower expiry if it wasn't found or is empty
			$expiry = 86400; // 24 hours
			if (!$res) {
				$res = '';
				$expiry = 300; // 5 minutes
			}

			if ($flipCss && $scriptType == 'css') {
				$res = CSSJanus::transform($res, true, false);
			}

			$res = ResourceLoader::filter($filter, $res);
			$cache->set($cachekey, $res, $expiry);
		}
		return $res;
	}

	/*
	 * Use this to get a compressed string of css or javascript from a file
	 * on the local file system.
	 *
	 * @param string $scriptType 'css' or 'js'
	 * @param string $filename filename of local file. Note: for security, never
	 *   generate this variable from user input! Always pass in a static string.
	 * @param mixed some sort of cache invalidator. Uses WH_SITEREV in production
	 *   by default, and WH_SITEREV . filemtime($filename) in developement.
	 * @param boolean $flipCss Transform the CSS for right-to-left languages
	 *
	 * @example <code>
	 * $embedStr = Misc::getEmbedFile('css', __DIR__ . "/startingcss.css");
	 * $outputPage->addHTML('<style>' . $embedStr . '</style>');
	 * </code>
	 */
	public static function getEmbedFile($scriptType, $filename, $cacheInvalidator = null, $flipCss = false) {
		return self::getEmbedFiles($scriptType, [$filename], $cacheInvalidator, $flipCss);
	}

	/**
	 * Detect if we are displaying mobile skin or desktop.
	 * @return bool true if mobile skin, false if desktop
	 */
	public static function isMobileMode() {
		$ctx = MobileContext::singleton();
		$isMobileMode = $ctx->shouldDisplayMobileView();
		return $isMobileMode;
	}

	// Uses raw headers rather than trying to instantiate a mobile
	// context object, which might not be possible. Use this
	// version of the function only when it's not possible to use
	// the normal isMobileMode() method above.
	public static function isMobileModeLite() {
		global $wgServer, $wgNoMobileRedirectTest, $wgIsAnswersDomain, $wgIsDevServer;
		if ( $wgServer && preg_match('@\bm\.@', $wgServer) > 0 ) {
			return true;
		}
		if ( $wgIsDevServer && $wgServer && preg_match('@\bm\b@', $wgServer) > 0 ) {
			return true;
		}
		if ( $wgIsAnswersDomain ) {
			return true;
		}
		if ( $wgNoMobileRedirectTest && @$_SERVER['HTTP_X_BROWSER'] == 'mb' ) {
			$cookieName = MobileContext::USEFORMAT_COOKIE_NAME;
			if ( @$_COOKIE[$cookieName] != 'dt' && @$_COOKIE[$cookieName . 'tmp'] != 'dt' ) {
				return true;
			}
		}
		return false;
	}

	// This header is used by our Fastly VCL to allow mobile redirection
	// when the stars align (ie, they have a mobile User-Agent, they don't
	// have special cookies, and they are on a desktop domain).
	public static function setHeaderMobileFriendly() {
		if ( !self::isMobileMode() ) {
			$ctx = RequestContext::getMain();
			$ctx->getRequest()->response()->header('x-mobile-friendly: 1');
		}
	}

	public static function isUserInGroups($user, $groups) {
		foreach ($groups as $group) {
			if (in_array($group, $user->getGroups())) {
				return true;
			}
		}
		return false;
	}

	public static function reportTimeMS() {
		global $wgRequestTime;
		$elapsed = microtime( true ) - $wgRequestTime;
		return sprintf('%d', $elapsed * 1000);
	}

	/**
	 * Add a Google Analytics event to a cookie, which will be processed and cleared via
	 * JavaScript on the next page load.
	 *
	 * @param String  $category   The name you supply for the group of objects you want to track.
	 * @param String  $action     Commonly used to define the type of user interaction for the web object.
	 * @param String  $label      An optional string to provide additional dimensions to the event data.
	 * @param String  $value      An integer that you can use to provide numerical data about the user event.
	 * @param boolean $nonInter   When true, the event hit will not be used in bounce-rate calculation.
	 */
	public static function addAnalyticsEventToCookie($category, $action, $label = null,
													 $value = null, $nonInter = true) {
		global $wgCookieDomain, $wgCookiePath, $wgCookiePrefix, $wgCookieSecure;
		$events = json_decode($_COOKIE['wiki_ga_pending']);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$events = array();
		}
		$events[] = compact('category', 'action', 'label', 'value', 'nonInter');
		$name = $wgCookiePrefix . 'GAPendingEvents';
		$value = json_encode($events);
		setcookie($name, $value, time() + 3600, $wgCookiePath, '.' . $wgCookieDomain, $wgCookieSecure);

	}

	public static function getGoogleAnalyticsConfig(): array {
		global $wgLanguageCode;

		// Map extra GA trackers to tracker names (does not include the main tracker)

		$codes = [];

		if ($wgLanguageCode == 'es') {
			$codes['UA-2375655-10'] = 'es';
		} elseif ($wgLanguageCode == 'ja') {
			$codes['UA-2375655-11'] = 'ja';
		} elseif ($wgLanguageCode == 'vi') {
			$codes['UA-2375655-14'] = 'vi';
		} elseif ($wgLanguageCode == 'it') {
			$codes['UA-2375655-18'] = 'it';
		} elseif ($wgLanguageCode == 'cs') {
			$codes['UA-2375655-19'] = 'cs';
		} elseif ($wgLanguageCode == 'tr') {
			$codes['UA-2375655-29'] = 'tr';
		} elseif (class_exists('QADomain') && QADomain::isQADomain()) {
			$codes[QADomain::getGACode()] = 'qa';
		}

		Hooks::run('MiscGetExtraGoogleAnalyticsCodes', array(&$codes));

		// Adjusted bounce rate (https://moz.com/blog/adjusted-bounce-rate)

		$adjustedBounce = null;

		if (self::isAdjustedBounceRateEnabled()) {
			$adjustedBounce = [
				'accounts' => [ 'UA-2375655-20' ],
				'eventCategory' => '10_seconds',
				'eventAction' => 'read',
				'timeout' => 10
			];
		}

		return [
			'extraPropertyIds' => $codes,
			'adjustedBounceRate' => $adjustedBounce
		];
	}

	/**
	 * @param array   $data      To be serialized and rendered in the response body
	 * @param int     $code      HTTP status code
	 * @param string  $callback  An optional function name for JSONP
	 *
	 * NOTE: This method does not observe caching parameters set in OutputPage. If
	 *       you need these responses to be cached, consider using OutputPage directly.
	 */
	public static function jsonResponse($data, int $code=200, string $callback='') {
		$contentType = empty($callback) ? 'application/json' : 'application/javascript';

		$req = RequestContext::getMain()->getRequest();
		$req->response()->header("Content-Type: $contentType");

		$out = RequestContext::getMain()->getOutput();
		// NOTE: cannot use setArticleBodyOnly(true) here because it sets content-type
		// to be text/html every time.
		$out->disable();

		if ($code != 200) {
			$message = HttpStatus::getMessage($code);
			if ($message) {
				$req->response()->header("HTTP/1.1 $code $message");
			}
		}

		if (empty($callback)) {
			print json_encode($data);
		} else {
			print htmlspecialchars($callback) . '(' . json_encode($data) . ')';
		}

	}

	// try a few different ways to get a title from a string of text
	// which may be a page id or may be a url on our main or dev site
	// or may just be the name of the title
	// this is useful for admin pages where the input is a bunch of
	// title strings which often are urls but sometimes are not
	public static function getTitleFromText( $text ) {
		$title = null;
		$partial = '';

		// try to strip out our main or dev domain name
		if ( !$title || !$title->exists() ) {
			$domainRegex = wfGetDomainRegex(
				false, // mobile?
				true // includeEn?
			);
			if (preg_match('@' . $domainRegex . '/(.+)@', $text, $matches)) {
				$partial = $matches[1];
			} elseif (preg_match("/([a-zA-Z]+)\.(wikiknowhow|wikidiy|wikidogs)\.com\/(.+)/", $text, $matches)) {
				$partial = $matches[3];
			} else {
				$domainRegex = wfGetDomainRegex(
					true, // mobile?
					true // includeEn?
				);
				if (preg_match('@' . $domainRegex . '/(.+)@', $text, $matches)) {
					$partial = $matches[1];
				}
			}
			$title = Title::newFromText( $partial );
		}
		// try url decoding the partial/text
		if ( !$title || !$title->exists() && $partial ) {
			$title = Title::newFromText( urldecode($partial) );
		}
		// try page id
		if ( !$title || !$title->exists() && is_numeric( $text ) ) {
			$title = Title::newFromID( $text );
		}
		// try to just get title from the text
		if ( !$title || !$title->exists() ) {
			$title = Title::newFromText( $text );
		}
		if ( !$title || !$title->exists() ) {
			return null;
		}

		return $title;
	}

	// Remove all non-alphanumeric characters
	public static function getSectionName($name) {
		$pattern = RequestContext::getMain()->getLanguage()->getCode() == 'en'
			? "/[^A-Za-z0-9]/u"
			: "/[^\p{L}\p{N}\p{M}]/u";
		return preg_replace($pattern, '', mb_strtolower($name));
	}

	// Returns all combinations of booleans in an array of given length
	public static function getBoolCombinations($length) {
		$combos = pow(2, $length);
		$seq = array();
		for ($x = 0; $x < $combos; $x++) {
			$seq[$x] = array_map('boolval', str_split(str_pad(decbin($x), $length, 0, STR_PAD_LEFT)));
		}
		return $seq;
	}

	public static function onUnitTestsList(&$files) {
		$files = array_merge($files, glob(__DIR__ . '/tests/*Test.php'));
		return true;
	}

	public static function escapeJQuerySelector($selector) {
		return preg_replace('/(:|\.|\[|\]|,)/', '\\\\${1}', $selector);
	}

	// http://stackoverflow.com/questions/945724/cosine-similarity-vs-hamming-distance/1290286#1290286
	public static function cosineSimilarity($sentenceA, $sentenceB) {
		$tokensA = explode(' ',$sentenceA);
		$tokensB = explode(' ',$sentenceB);

		if (empty($tokensA) || empty($tokensB)) return 0;

		$a = $b = $c = 0;
		$uniqueTokensA = $uniqueTokensB = array();

		$uniqueMergedTokens = array_unique(array_merge($tokensA, $tokensB));

		foreach ($tokensA as $token) $uniqueTokensA[$token] = 0;
		foreach ($tokensB as $token) $uniqueTokensB[$token] = 0;

		foreach ($uniqueMergedTokens as $token) {
			$x = isset($uniqueTokensA[$token]) ? 1 : 0;
			$y = isset($uniqueTokensB[$token]) ? 1 : 0;
			$a += $x * $y;
			$b += $x;
			$c += $y;
		}
		return $b * $c != 0 ? $a / sqrt($b * $c) : 0;
	}

	/**
	 * Make a lower case, punctuation-free form of the article title
	 */
	public static function redirectGetFolded($text) {
		$text = mb_strtolower($text);
		$patterns = array('@[[:punct:]]@', '@\s+@');
		$replace  = array('', ' ');
		$text = preg_replace($patterns, $replace, $text);
		return substr( $text, 0, 255 );
	}

	/**
	 * check for a redirect based on alternate case of title
	 * returns null if it is not a case redirect
	 * returns the redirect target title otherwise
	 */
	public static function getCaseRedirect( $title ) {
		$req = RequestContext::getMain()->getRequest();
		$redir = null;
		if ($title && $title->inNamespace(NS_MAIN)
			&& $req && $req->getVal('redirect') !== 'no'
		) {
			$dbr = wfGetDB(DB_REPLICA);
			$text = Misc::redirectGetFolded( $title->getText() );
			$redirPageID = $dbr->selectField('redirect_page', 'rp_page_id', array('rp_folded' => $text), __METHOD__);
			$redirTitle = Title::newFromID($redirPageID);
			if ($redirTitle && $redirTitle->exists()) {
				$partial = $redirTitle->getPartialURL();
				if ($partial != $title->getPartialURL()) {
					$redir = $redirTitle;
				}
			}
		}
		return $redir;
	}

	/*
	 * get a scroll defer html element and the script tag to enable it
	 *
	 * @param string $element either img or video
	 * @param array $attributes the attributes to create a video or img element
	 * @return Title: The corresponding Title
	 */
	public static function getMediaScrollLoadHtml( $element, $attributes ) {
		$noScriptElement = Html::rawElement( $element, $attributes );

		if ( $element == 'video' ) {
			$attributes['playsinline'] = '';
			$attributes['webkit-playsinline'] = '';
			$attributes['muted'] = '';
			$attributes['loop'] = '';
			$poster = $attributes['poster'];
			if ( $poster ) {
				$attributes['data-poster'] = $poster;
			}
			unset( $attributes['poster'] );
			// fall back to img if no js available
			$noScriptElement = Html::rawElement( 'img', ['src'=>$attributes['data-poster']] );
		}

		$noScriptElement = '<noscript>'.$noScriptElement.'</noscript>';

		$src = $attributes['src'];
		$attributes['data-src'] = $src;
		unset( $attributes['src'] );

		// get the id
		if ( !isset( $attributes['id'] ) ) {
			$id = uniqid();
			$attributes['id'] = $id;
		}

		$element = Html::rawElement( $element, $attributes );

		$script = Html::inlineScript( "WH.shared.addScrollLoadItem('$id')" );
		return $element . $script . $noScriptElement;
	}

	public static function isIntl(): bool {
		global $wgLanguageCode;
		return $wgLanguageCode != 'en';
	}

	/**
	 * Send a 404 response and exit()
	 */
	public static function exitWith404($msg = "Page not found") {
		RequestContext::getMain()->getRequest()->response()->header("HTTP/1.1 404 Not Found");
		print $msg;
		exit;
	}

	public static function isAltDomain(): bool {
		return class_exists('AlternateDomain') && AlternateDomain::onAlternateDomain();
	}

	public static function isAdjustedBounceRateEnabled(): bool {
		global $domainName;
		return strpos($domainName, 'wikihow.pet') !== false;
	}

	public static function getReferencesCount(): int {
		if (!is_null(self::$referencesCount)) return self::$referencesCount;

		$context = RequestContext::getMain();
		$lang_code = $context->getLanguage()->getCode();

		$title = $context->getTitle();
		if (!$title) return 0;

		$page_id = $title->getArticleId();
		if (!$page_id) return 0;

		$count = wfGetDB(DB_REPLICA)->selectField(
			WH_DATABASE_NAME_EN . '.titus_copy',
			'ti_num_sources_cites',
			[
				'ti_language_code' => $lang_code,
				'ti_page_id' => $page_id
			],
			__METHOD__
		);

		return $count;
	}

	public static function getReferencesID(): string {
		$id = '#' . self::getSectionName( wfMessage('sources')->text() );
		if ( phpQuery::$defaultDocumentID ) {
			$references = '#' . self::getSectionName( wfMessage('references')->text() );
			if ( pq( $references )->length > 0 ) {
				$id = $references;
			}
		}
		return $id;
	}

}
