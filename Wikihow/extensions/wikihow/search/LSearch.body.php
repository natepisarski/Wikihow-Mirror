<?php

/**
 * Our Search special page. Uses Yahoo Boss and Elasticsearch to retrieve results.
 */
class LSearch extends SpecialPage {

	public function isMobileCapable() {
		return true;
	}

	const RESULTS_PER_PAGE = 30;
	const RESULTS_PER_PAGE_DESKTOP = 10;
	const RESULTS_PER_PAGE_MOBILE = 20;

	const SEARCH_OTHER = 0;
	const SEARCH_LOGGED_IN = 1;
	const SEARCH_MOBILE = 2;
	const SEARCH_APP = 3;
	const SEARCH_RSS = 4;
	const SEARCH_RAW = 5;
	const SEARCH_404 = 6;
	const SEARCH_CATSEARCH = 7;
	const SEARCH_LOGGED_OUT = 8;
	const SEARCH_INTERNAL = 9;

	const SEARCH_WEB = 10 ;

	const NO_IMG_BLUE = '/extensions/wikihow/search/no_img_blue.png';
	const NO_IMG_BLUE_MOBILE = '/extensions/wikihow/search/no_img_blue_mobile.png';
	const NO_IMG_GREEN = '/extensions/wikihow/search/no_img_green.png';
	const NO_IMG_GREEN_MOBILE = '/extensions/wikihow/search/no_img_green_mobile.png';

	const ONE_WEEK_IN_SECONDS = 604800;
	const ONE_DAY_IN_SECONDS = 86400;
	const FIVE_MINUTES_IN_SECONDS = 300;

	var $mResults = array();
	var $mSpelling = array();
	var $mLast = 0;
	var $mQ = '';
	var $mStart = 0;
	var $mLimit = 0;
	var $searchUrl = '/wikiHowTo';
	var $disableAds = false;
	var $showSuicideHotline = false;
	var $enableCdnCaching = true;
	var $mResultsSource = '';

	var $mEnableBeta = true;

	public function __construct() {
		global $wgHooks;
		parent::__construct('LSearch');

		$this->setListed(false);
		$this->mNoImgBlueMobile = wfGetPad(self::NO_IMG_BLUE_MOBILE);
		$this->mNoImgGreenMobile = wfGetPad(self::NO_IMG_GREEN_MOBILE);

		$wgHooks['ShowBreadCrumbs'][] = array($this, 'removeBreadCrumbsCallback');
		$wgHooks['AfterFinalPageOutput'][] = array($this, 'onAfterFinalPageOutput');
	}

	/**
	 * /wikiHowTo?... and /Special:LSearch page entry point
	 */
	public function execute($par) {
		// Added this hack to test whether we can stop some usertype:logged(in|out)
		// queries can be removed from index. Remove this code eventually, say 6 mos.
		// from now. Added by Reuben originally on July 30, 2012.
		$queryString = @$_SERVER['REQUEST_URI'];
		if (strpos($queryString, 'usertype') !== false) {
			header('HTTP/1.0 404 Not Found');
			print "Page not found";
			exit;
		}

		$req = $this->getRequest();

		$this->mStart = $req->getVal('start', 0);
		$this->mQ = self::getSearchQuery();
		$this->mLimit = $req->getVal('limit', 20);

		$this->getOutput()->setRobotPolicy( 'noindex,nofollow' );

		// Track requests in statds/grafana
		WikihowStatsd::increment('search.request');

		if ($req->getBool('internal')) {
			$this->regularSearch(true);
		} elseif ($req->getBool('rss')) {
			$this->rssSearch();
		} elseif ($req->getBool('raw')) {
			$this->rawSearch();
		} else {
			$this->regularSearch();
		}
	}

	/**
	 * A call used to parse titles from external search results
	 */
	public function externalSearchResultTitles($q, $first = 0, $limit = 30, $minrank = 0, $searchType = self::SEARCH_OTHER) {
		$this->externalSearchResults($q, $first, $limit, $searchType);
		$results = [];
		$searchResults = $this->mResults['results'];
		if (!is_array($searchResults)) return $results;
		foreach ($searchResults as $r) {
			if (!is_array($r)) {
				// This can be a string sometimes, as evidenced by this error in our
				// web logs:
				// NOTICE: PHP message: PHP Warning:  array_change_key_case() expects parameter 1 to be array, string given in /opt/wikihow/prod/extensions/wikihow/search/LSearch.body.php on line 89
				continue;
			}
			$r = array_change_key_case($r);
			$url = $this->localizeUrl($r['url']);
			$t = Title::newFromText(urldecode($url));
			if ($t && $t->exists()) $results[] = $t;
		}
		return $results;
	}

	/**
	 * This function is called by DupTitleChecker for deduping titles.
	 * It returns Bing results for $q
	 * Check with Jordan before using this function for any other purpose.
	 * This is a paid service.
	 */
	public function getBingSearchResults( $q, $first = 0, $limit = 50, $searchType = self::SEARCH_WEB ) {
		$this->externalSearchResultsBing( $q, $first, $limit, $searchType );
		return $this->mResults['results'] ;
	}

	public static function getSearchQuery(): string {
		$req = RequestContext::getMain()->getRequest();
		$q = trim($req->getVal('search', ''));
		return $q;
	}

	public static function formatSearchQuery( $q ): string {
		// special case search term filtering
		if (strtolower($q) == 'sat') { // searching for SAT, not sitting
			$q = "\"SAT\"";
		}

		// Prepend "how to" to the query on EN
		$prefixes = [
			'how to ',
			'howto ',
			'how ',
			'wikihow to ',
			'wikihowto ',
			'wikihow ',
			'to '
		];
		if ( $q && !Misc::isIntl() ) {
			$changed = false;
			foreach ( $prefixes as $prefix ) {
				if ( strtolower( substr( $q, 0, strlen( $prefix ) ) ) === $prefix ) {
					$q = 'how to ' . substr( $q, strlen( $prefix ) );
					$changed = true;
					break;
				}
			}
			if ( !$changed ) {
				$q = "how to " . $q;
			}
		}
		return $q;
	}

	# Hook callbacks

	/**
	 * A Mediawiki callback set in contructor of this class to stop the display
	 * of breadcrumbs at the top of the page.
	 */
	public function removeBreadCrumbsCallback(&$showBreadCrumb) {
		$showBreadCrumb = false;
		return true;
	}

	/*
	* Set cache-control headers right before page diplay
	*/
	public function onAfterFinalPageOutput($out) {
		$user = $out->getUser();
		if ( $user && $user->isAnon() && $out->getTitle() && $this->enableCdnCaching ) {
			$out = $this->getOutput();
			$req = $this->getRequest();
			$out->setSquidMaxage( self::ONE_DAY_IN_SECONDS );
			$req->response()->header( 'Cache-Control: s-maxage=' . self::ONE_DAY_IN_SECONDS . ', must-revalidate, max-age=' . self::ONE_DAY_IN_SECONDS );
			$future = time() + self::ONE_DAY_IN_SECONDS;
			$req->response()->header( 'Expires: ' . gmdate('D, d M Y H:i:s T', $future) );
			$out->enableClientCache(true);
			$out->sendCacheControl();
		}
		return true;
	}

	/*
	 * This hook removes the canonical url if it's Special:LSearch.  As per SEO discussions
	 * between Jordan and Reuben, a canonical link doesn't make sense for this particular page
	 */
	public static function onOutputPageAfterGetHeadLinksArray( &$headLinks, $out ) {
		$t = SpecialPage::getTitleFor('LSearch');
		$canonicalLink = Html::element( 'link', array(
			'rel' => 'canonical',
			'href' => wfExpandUrl($t->getLocalURL(), PROTO_CANONICAL)
		) );

		foreach($headLinks as $key => $val) {
			if ($val === $canonicalLink) {
				unset($headLinks[$key]);
			}
		}
		return true;
	}

	# Internals

	private function regularSearch($internal = false) {
		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
			$resultsPerPage = self::RESULTS_PER_PAGE;
		} elseif (Misc::isMobileMode()) {
			$resultsPerPage = self::RESULTS_PER_PAGE_MOBILE;
		} else {
			$resultsPerPage = self::RESULTS_PER_PAGE_DESKTOP;
		}

		if ($internal) {
			$this->mNoImgBlueMobile = '';
			$this->mNoImgGreenMobile = '';
			$resultCount = $this->internalSearchResults($this->mQ, $this->mStart, $resultsPerPage);
		} else {
			$resultCount = $this->externalSearchResults($this->mQ, $this->mStart, $resultsPerPage, self::SEARCH_LOGGED_IN);
		}

		if ($resultCount < 0) {
			$reqUrl = $this->getRequest()->getRequestURL();
			$out = $this->getOutput();
			$out->addHTML(wfMessage('lsearch_query_error', $reqUrl)->plain());
			$out->setStatusCode( 404 );
			return;
		}

		$this->getOutput()->setHTMLTitle(htmlspecialchars(
			wfMessage('lsearch_title_q', self::formatSearchQuery($this->mQ))->text()
		));

		$suggestionLink = $this->getSpellingSuggestion($this->searchUrl);
		$results = $this->mResults['results'] ? $this->mResults['results'] : [];

		$results = $this->makeTitlesUniform($results);
		$results = $this->supplementResults($results);
		$results = $this->removeDeIndexedResults($results);

		Hooks::run( 'LSearchRegularSearch', array( &$results ) );

		$enc_q = htmlspecialchars($this->mQ);
		$searchId = $this->sherlockSearch();	// initialize/check Sherlock cookie
		$this->displaySearchResults( $results, $resultsPerPage, $enc_q, $suggestionLink, $searchId, $this->mResultsSource );
	}

	/**
	 * @return int  Amount of results
	 */
	private function externalSearchResults($q, $start, $limit = 30, $searchType = self::SEARCH_OTHER): int {
		// Internal search is used for requests coming from services like FB messenger bot or Alexa.
		// These services often are intermittently blocked by yahoo search (which is free through our DDC contract).
		// Instead we send them to Bing, which we have to pay per query.
		// Trevor, 9/4/18 - Fallback to Special:Search if we have 0 results from an external provider
		if ( !$this->getRequest()->getBool( 'internal' ) ) {
			if ( $searchType == self::SEARCH_INTERNAL ) {
				$count = $this->externalSearchResultsBing( $q, $start, $limit, $searchType );
			} else {
				if ( $this->mEnableBeta ) {
					$count = $this->externalSearchResultsSolr( $q, $start, $limit, $searchType );
				} else {
					$count = $this->externalSearchResultsYahoo( $q, $start, $limit, $searchType );
				}
			}

			if ( $count > 0 ) {
				return $count;
			}
		}

		// Fallback to internal search results (extracts results from MediaWiki Special:Search) for
		// English only
		if ( $this->getLanguage()->getCode() == 'en' ) {
			return $this->internalSearchResults( $q, $start, $limit );
		}
		return 0;
	}

	/**
	 * Solr internal search API
	 *
	 * @return int  Amount of results
	 */
	private function externalSearchResultsSolr($q, $start, $limit = 30, $gm_type = self::SEARCH_OTHER): int {
		global $wgMemc, $wgSearchServerBase;

		$q = trim($q);

		if ($this->isBadQuery($q)) {
			return -1;
		}
		$q = self::formatSearchQuery($q);
		if ( substr( $q, 0, 7 ) === 'how to ' ) {
			// Use the normalization but not the "how to " since Solr does that on its own
			$q = substr( $q, 7 );
		} 

		$domain = Misc::getCanonicalDomain();
		$key = wfMemcKey('SolrSearchResultsV1', str_replace(' ', '-', $q), $start, $limit, $domain);
		$data = $wgMemc->get($key);

		if ( !is_array( $data ) ) {
			// Query Solr
			$params = [
				'count' => $limit,
				'start' => $start,
				'q' => $q,
				'domain' => preg_replace( '/^(www\.|m\.)/', '', $domain ),
			];

			$langCode = $this->getLanguage()->getCode();

			// Look for language specific search server, fallback on intl
			if ( array_key_exists( $langCode, $wgSearchServerBase ) ) {
				$server = $wgSearchServerBase[$langCode];
			} else {
				$server = $wgSearchServerBase['intl'];
			}
			$url = $server . '/search/' . $langCode . '?' . http_build_query( $params );

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: search.wikihow']);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

			// Parse response contents or return on failure

			$respBody = curl_exec($ch);
			$respCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($respCode != 200 || curl_errno($ch)) {
				WikihowStatsd::increment('search.error');
				curl_close($ch);
				return -1;
			}

			curl_close($ch);

			try {
				$response = json_decode( $respBody );
			} catch (Exception $e) {
				return -1;
			}

			// Collect data

			$data = [];

			if ( $response->suicide_hotline ) {
				$data['suicide'] = true;
			}

			if ( $response->spellcheck && $response->spellcheck->suggestions ) {
				// Replace each misspelling with the first suggested correction
				function interpolate( $query, $suggestions, $format = false ) {
					$suggestedQuery = $query;
					$offset = 0;
					for ( $i = 0; $i < count( $suggestions ); $i += 2 ) {
						$misspelling = $suggestions[$i];
						$suggestion = $suggestions[$i + 1];
						$start = $suggestion->startOffset + $offset;
						$length = $suggestion->endOffset + $offset - $start;
						$replacement = $suggestion->suggestion[0];
						if ( $format ) {
							$replacement = "<b><i>{$replacement}</i></b>";
						}
						$suggestedQuery = substr_replace( $suggestedQuery, $replacement, $start, $length );
						$offset += strlen( $replacement ) - strlen( $misspelling );
					}
					return $suggestedQuery;
				}
				$value = interpolate( $q, $response->spellcheck->suggestions );
				$label = interpolate( $q, $response->spellcheck->suggestions, true );

				$data['spelling'] = [ [ 'Value' => $value, 'Label' => $label ] ];
			}

			$data['results'] = [];
			if ( $response->results ) {
				foreach ( $response->results as $result ) {
					$formattedTitle = $response->highlighting->{$result->id}->formatted_title ?
						$response->highlighting->{$result->id}->formatted_title[0] : $result->formatted_title[0];
					if ( $result->category === 1 ) {
						$url = 'Category:' . str_replace( ' ', '-', $result->formatted_title[0] );
					} else {
						$title = Title::newFromID( $result->pageid );
						if ( !$title ) {
							continue;
						}
						$url = $title->getDBKey();
					}
					$data['results'][] = [
						'title' => $formattedTitle,
						'description' => '...',
						'url' => 'https://' . $domain . '/' . urlencode( $url )
					];
				}
			}

			$data['totalresults'] = (int) $response->numfound;

			// Update cache. If no results, cache only for 5 minutes to handle hiccups in search service
			$count = count( $data['results'] );
			$expiry = ( $count > 0 ) ? self::ONE_WEEK_IN_SECONDS : self::FIVE_MINUTES_IN_SECONDS;
			$wgMemc->set( $key, $data, $expiry );
		}

		// Use data

		if (
			($gm_type == self::SEARCH_LOGGED_IN || $gm_type == self::SEARCH_LOGGED_OUT ) &&
			// Suppress suggestions unless there are 0 results
			$count === 0
		) {
			$this->mSpelling = $data['spelling'] ?? [];
		}
		$this->mResults['results'] = $data['results'];
		$this->mLast = $this->mStart + count( $data['results'] );
		$this->mResults['totalresults'] = $data['totalresults'];
		$this->mResultsSource = 'whsolr';

		if ( $data['suicide'] ) {

			// Show suicide hotline info to visitors from US only
			if( $this->getRequest()->getHeader('x-cc') == 'US' ){
                        	$this->showSuicideHotline = true;
			}
			$this->disableAds = true;

			// Disable fastly cache if the search term is related to suicide/ self-harm
			$this->enableCdnCaching = false;
		}

		return count( $data['results'] );
	}

	/**
	 * Google WebSearch XML protocol
	 *
	 * Server IPs need to be manually authorized.
	 *
	 * Example request:
	 *  GET http://www.google.com/search
	 *  ?client=wikihow-search
	 *  &output=xml_no_dtd
	 *  &num=10
	 *  &start=0
	 *  &ie=utf8
	 *  &hl=en
	 *  &q=dance site:www.wikihow.com
	 *  &ip=123.45.6.789
	 *  &useragent=Mozilla/5.0 ...
	 *
	 * @link https://developers.google.com/custom-search/docs/xml_results
	 *
	 * @return int  Amount of results
	 */
	private function externalSearchResultsGoogle($q, $start, $limit = 30, $gm_type = self::SEARCH_OTHER): int {
		global $wgMemc;

		$q = trim($q);
		if ($this->isBadQuery($q)) {
			return -1;
		}
		$q = self::formatSearchQuery($q);

		$key = wfMemcKey('GoogleXMLAPIResultsV2', str_replace(' ', '-', $q), $start, $limit);
		$data = $wgMemc->get($key);

		if (!is_array($data)) {

			// Query Google

			$params = [
				'client' => 'wikihow-search',
				'output' => 'xml_no_dtd',
				'num' => $limit,
				'start' => $start,
				'ie' => 'utf8',
				'hl' => $this->getLanguage()->getCode(),
				'q' => "$q site:" . Misc::getCanonicalDomain(),
				'ip' => $_SERVER['SERVER_ADDR'],
				'useragent' => $_SERVER['HTTP_USER_AGENT'],
			];
			$url = 'http://www.google.com/search?' . http_build_query($params);

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

			// Parse response contents or return on failure

			$respBody = curl_exec($ch);
			$respCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($respCode != 200 || curl_errno($ch)) {
				curl_close($ch);
				return -1;
			}

			curl_close($ch);

			try {
				$xmlResp = @ new SimpleXMLElement($respBody);
			} catch (Exception $e) {
				return -1;
			}

			// Collect data

			$data = [];

			if ($xmlResp->Spelling->CORRECTED_QUERY->Q instanceof SimpleXMLElement) {
				$chunks = explode(' site:', $xmlResp->Spelling->CORRECTED_QUERY->Q);
				$data['spelling'] = [ [ 'Value' => $chunks[0], 'Label' => "<b><i>{$chunks[0]}</i></b>" ] ];
			}

			$data['results'] = [];
			if ($xmlResp->RES->R) foreach ($xmlResp->RES->R as $result) {
				$data['results'][] = [
					'title' => (string) $result->T,
					'description' => (string) $result->S,
					'url' => (string) $result->U,
				];
			}

			$data['totalresults'] = (int) $xmlResp->RES->M;

			// Update cache. If no results, cache only for 5 minutes to handle hiccups in search service
			$count = count($data['results']);
			$expiry = ($count > 0) ? self::ONE_WEEK_IN_SECONDS : self::FIVE_MINUTES_IN_SECONDS;
			$wgMemc->set($key, $data, $expiry);
		}

		// Use data

		if ($gm_type == self::SEARCH_LOGGED_IN || $gm_type == self::SEARCH_LOGGED_OUT) {
			$this->mSpelling = $data['spelling'] ?? [];
		}

		$num_results = count($data['results']);
		$this->mResults['results'] = $data['results'];
		$this->mLast = $this->mStart + $num_results;
		$this->mResults['totalresults'] = $data['totalresults'];

		return $num_results;

	}

	/**
	 * Query Yahoo proxy search. This is a search proxy to the search service formerly known as Yahoo BOSS provided
	 * by our search ad provider DDC (http://ddc.com). We get this service for free in exchange for hosting ads
	 * on our search results
	 *
	 * @return int  Amount of results
	 */
	private function externalSearchResultsYahoo($q, $start, $limit = 30, $gm_type = self::SEARCH_OTHER): int {
		global $wgMemc, $IP;

		$key = wfMemcKey("YPAResults4", str_replace(" ", "-", $q), $start, $limit);

		Hooks::run( 'LSearchYahooAfterGetCacheKey', array( &$key ) );

		$q = trim($q);
		if ($this->isBadQuery($q)) {
			return -1;
		}
		$q = self::formatSearchQuery($q);

		$set_cache = false;
		$contents = $wgMemc->get($key);
		if (!is_array($contents)) {
			// Reference url for building
			// http://yssads.ddc.com/x1.php?ua=Mozilla/5.0%20(Windows%20NT%206.1;%20WOW64)%20AppleWebKit/537.36%20(KHTML,%20like%20Gecko)%20Chrome/35.0.1916.153%20Safari/537.36&ip=69.231.120.208&surl=http%3A%2F%2Fddctestalgo.com&kw=change%20a%20tire&c=16588&n=5&algo=10&format=json
			$url = "http://yssads.ddc.com/x1.php";
			$siteKeyword = wfCanonicalDomain();
			$surl = $this->getSurl();

			Hooks::run( 'LSearchBeforeYahooSearch', array( &$siteKeyword, &$surl ) );

			$args = [
				'ua' => $_SERVER['HTTP_USER_AGENT'],
				'ip' => $this->getRequest()->getIP(),
				'surl' => $surl,
				'c' => '22937',
				'kw' => "$q site:$siteKeyword",
				'format' => 'json',
				'sponstart' => $limit,
				'algostart' => $start,
				'algo' => $limit
			];

			// Yahoo boss required OAuth 1.0 authentication
			require_once("$IP/extensions/wikihow/common/oauth/OAuth.php");

			$url = sprintf("%s?%s", $url, OAuthUtil::build_http_query($args));

			//echo($url);exit;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$rsp = curl_exec($ch);

			$contents = null;
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($http_code != 200 || curl_errno($ch)) {
//				echo $rsp;exit;
				curl_close($ch);
				return -1;
			} else {
				//echo $rsp;exit;
				$contents = json_decode($rsp, true);
				curl_close($ch);
			}

			$set_cache = true;
		}

		if ($gm_type == self::SEARCH_LOGGED_IN || $gm_type == self::SEARCH_LOGGED_OUT) {
			$suggestion = $contents['bossresponse']['spelling'][0]['Value'];
			if ( !empty( $suggestion ) ) {
				$this->mSpelling = [ [ 'Value' => $suggestion, 'Label' => "<b><i>{$suggestion}</i></b>" ] ];
			}
		}

		$this->mResults['results'] = $contents['web']['web'];
		$num_results = !empty($this->mResults['results']) ?
			count($this->mResults['results']) : 0;

		if ($set_cache) {
			// Set earlier cache expiration for empty results to handle hiccups in search service better
			$expiry = $num_results > 0 ? self::ONE_WEEK_IN_SECONDS : self::FIVE_MINUTES_IN_SECONDS;
			$wgMemc->set($key, $contents, $expiry);
		}

		$this->mLast = $this->mStart + $num_results;
		// The DDC web search proxy doesn't have a 'total results' argument so we simulate it by checking to see
		// if there is a nextargs value. A nextargs values signifies an additional page of results exist.  If nextargs
		// does exist make the total results one more than the last result count to ensure proper pagination
		$this->mResults['totalresults'] = empty($contents['nextargs']) ? $num_results : $this->mLast + 1;

		$this->mResultsSource = 'yahoo';

		return $num_results;

	}

	/**
	 * Query the Bing Search API, which is a (paid-for) API.  Use sparingly and check in with PMs before making
	 * a bunch of calls for any new feature work
	 *
	 * @return int  Amount of results
	 */
	private function externalSearchResultsBing($q, $start, $limit = 30, $searchType = self::SEARCH_OTHER): int {
		global $wgMemc, $wgLanguageCode;

		$key = wfMemcKey("BingSearchAPI-V7", str_replace(" ", "-", $q), $start, $limit);

		$q = trim($q);
		if ($this->isBadQuery($q)) {
			return -1;
		}
		$q = self::formatSearchQuery($q);

		$set_cache = false;
		$contents = $wgMemc->get($key);
		$siteKeyword = wfCanonicalDomain();

		if (!is_array($contents)) {
			// Request spelling results for logged in search
			if ($searchType == self::SEARCH_LOGGED_IN || $searchType == self::SEARCH_LOGGED_OUT) {
				$responseFilter = "Webpages,SpellSuggestions";
			} else {
				$responseFilter = "Webpages";
			}

			if ($searchType == self::SEARCH_WEB ) {
				$queryUrl =  "https://api.cognitive.microsoft.com/bing/v7.0/search?responseFilter=$responseFilter"
				. '&count=' . $limit . '&offset=' . $start . '&q=' . urlencode( $q ) . "&setLang=" . $wgLanguageCode;
			} else {
				$queryUrl =  "https://api.cognitive.microsoft.com/bing/v7.0/search?responseFilter=$responseFilter"
					. '&count=' . $limit . '&offset=' . $start . '&q=' . urlencode( "$q site:$siteKeyword" )
					. "&setLang=" . $wgLanguageCode;
			}

			// Enable text decoration if a search originates from a wikihow.com page
			if ($searchType == self::SEARCH_LOGGED_IN || $searchType == self::SEARCH_LOGGED_OUT) {
				$queryUrl .= "&textDecorations=true";
			}

			$ch = curl_init($queryUrl);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Ocp-Apim-Subscription-Key: "
				. WH_AZURE_COGNITIVE_SERVICES_BING_API_V7_SUBSCRIPTION_KEY]);

			$rsp = curl_exec($ch);
			//echo $rsp;exit;

			$contents = null;
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($http_code != 200 || curl_errno($ch)) {
				curl_close($ch);
				return -1;
			} else {
				$contents = json_decode($rsp, true);
				$contents = empty($contents['webPages']['value']) ? [] : $contents['webPages'];
				curl_close($ch);
			}

			$set_cache = true;
		}

		if ($searchType == self::SEARCH_LOGGED_IN || $searchType == self::SEARCH_LOGGED_OUT) {
			$suggestion = $contents['SpellingSuggestions'];
			if ( !empty( $suggestion ) ) {
				$this->mSpelling = [ [ 'Value' => $suggestion, 'Label' => "<b><i>{$suggestion}</i></b>" ] ];
			}
		}

		$this->mResults['results'] = isset($contents['value']) ?
			$this->normalizeBingResults($contents['value']) : null;

		$this->mResults['totalresults'] = isset($contents['totalEstimatedMatches']) ?
			$contents['totalEstimatedMatches'] : 0;

		$num_results = !empty($contents['value']) ? count($contents['value']) : 0;

		if ($set_cache) {
			// Set earlier cache expiration for empty results to handle hiccups in search service better
			$expiry = $num_results > 0 ? self::ONE_WEEK_IN_SECONDS : self::FIVE_MINUTES_IN_SECONDS;
			$wgMemc->set($key, $contents, $expiry);
		}

		$this->mLast = $this->mStart + $num_results;
		$this->mResultsSource = 'bing';

		return $num_results;
	}

	/**
	 * Add fields expected by LSearch for displaying output
	 *
	 * @param $results
	 * @return mixed
	 */
	private function normalizeBingResults($results) {
		foreach ($results as $i => $r) {
			// Bing puts title text results in the name field.  Add a title key in the results to normalize with
			// DDC/Yahoo.
			$r['title'] = $r['name'];
			$results[$i] = $r;
		}

		return $results;
	}

	private function internalSearchResults($q, $start, $limit = 30): int {
		global $IP, $wgMemc;

		$key = wfMemcKey('InternalSearchResults', str_replace(' ', '-', $q), $start, $limit);
		$mResults = $wgMemc->get($key);

		if (!is_array($mResults))
		{
			require_once("$IP/includes/specials/SpecialSearch.php");

			// Exclude de-indexed articles
			$this->getRequest()->setVal( 'ffrin', 0 );
			$this->getRequest()->setVal( 'ffriy', 1 );

			$specialSearch = new Finner($this->getRequest(), $this->getUser());
			$specialSearch->load();

			$engine = $specialSearch->getSearchEngine();
			$engine->setLimitOffset($limit, $start);
			$engine->setNamespaces($specialSearch->getNamespaces());
			$engine->showRedirects = false;
			$engine->setFeatureData('list-redirects', false);

			$term = str_replace("\n", ' ', $q);
			$term = $engine->transformSearchTerm($term);

			Hooks::run('SpecialSearchSetupEngine', [$specialSearch, 'default', $engine]);

			$rewritten = $engine->replacePrefixes($term);
			$titleMatches = $engine->searchTitle($rewritten);
			if (!($titleMatches instanceof SearchResultTooMany)) {
				$textMatches = $engine->searchText($rewritten);
			}

			$anyTextMatches = $textMatches && $textMatches instanceof CirrusSearch\Search\ResultSet;

			$totalResults = 0;
			if ($titleMatches)   { $totalResults += $titleMatches->getTotalHits(); }
			if ($anyTextMatches) { $totalResults += $textMatches->getTotalHits();  }

			$mResults = [ 'results' => [], 'totalresults' => $totalResults ];

			$matches = [];
			if ($titleMatches)   { while ($m = $titleMatches->next()) { $matches[] = $m; } }
			if ($anyTextMatches) { while ($m = $textMatches->next())  { $matches[] = $m; } }

			foreach ($matches as $match) {
				$title = $match->getTitle();
				if ($title) {
					$mResults['results'][] = [
						'title' => wfMessage('howto', $title->getText())->text(),
						'url' => $title->getDBKey(),
					];
				}
			}

			$expiry = $mResults['results'] ? self::ONE_DAY_IN_SECONDS : self::FIVE_MINUTES_IN_SECONDS;
			$wgMemc->set($key, $mResults, $expiry);
		}

		$count = count($mResults['results']);
		$this->mLast = $this->mStart + $count;
		$this->mResults = $mResults;
		$this->mResultsSource = 'elastic';

		return $count;
	}

	private function rssSearch() {
		$results = $this->externalSearchResultTitles($this->mQ, $this->mStart, self::RESULTS_PER_PAGE, 0, self::SEARCH_RSS);
		$this->getOutput()->setArticleBodyOnly(true);
		$pad = "           ";
		header("Content-type: text/xml;");
		print '<GSP VER="3.2">
<TM>0.083190</TM>
<Q>' . htmlspecialchars($this->mQ) . '</Q>
<PARAM name="filter" value="0" original_value="0"/>
<PARAM name="num" value="16" original_value="30"/>
<PARAM name="access" value="p" original_value="p"/>
<PARAM name="entqr" value="0" original_value="0"/>
<PARAM name="start" value="0" original_value="0"/>
<PARAM name="output" value="xml" original_value="xml"/>
<PARAM name="sort" value="date:D:L:d1" original_value="date%3AD%3AL%3Ad1"/>
<PARAM name="site" value="main_en" original_value="main_en"/>
<PARAM name="ie" value="UTF-8" original_value="UTF-8"/>
<PARAM name="client" value="internal_frontend" original_value="internal_frontend"/>
<PARAM name="q" value="' . htmlspecialchars($this->mQ) . '" original_value="' . htmlspecialchars($this->mQ) . '"/>
<PARAM name="ip" value="192.168.100.100" original_value="192.168.100.100"/>
<RES SN="1" EN="' . sizeof($results) . '">
<M>' . sizeof($results) . '</M>
<XT/>';
		$count = 1;
		foreach ($results as $r) {
			print "<R N=\"{$count}\">
<U>{$r->getFullURL()}</U>
<UE>{$r->getFullURL()}</UE>
<T>How to " . htmlspecialchars($r->getFullText()) . "{$pad}</T>
<RK>10</RK>
<HAS></HAS>
<LANG>en</LANG>
</R>";
			$count++;
		}
		print "</RES>
</GSP>";
	}

	private function rawSearch() {
		$contents = $this->externalSearchResultTitles($this->mQ, $this->mStart, self::RESULTS_PER_PAGE, 0, self::SEARCH_RAW);
		header("Content-type: text/plain");
		$this->getOutput()->setArticleBodyOnly(true);
		foreach ($contents as $t) {
			print "{$t->getCanonicalURL()}\n";
		}
	}

	// Executes the logic for managing the Sherlock Cookie & loggin search to DB
	private function sherlockSearch() {
		if (class_exists("Sherlock")) {
			$context = $this->getContext();

			// check if the user is logged in
			$user = $context->getUser();
			if ($user->isAnon()) {
				$logged = false;
			} else {
				$logged = true;
			}

			// Check if their using the mobile site
			if (Misc::isMobileMode()) {
				$platform = "mobile";
			} else {
				$platform = "desktop";
			}

			// Get visitor ID
			$vId = WikihowUser::getVisitorId();

			// Check if there's already a search id cookie
			$request = $context->getRequest();
			$searchId = $request->getCookie("sherlock_id");

			// Determine whether or not this is a new search
			if ($request->getCookie("sherlock_q") != $this->mQ) {
				$searchId = Sherlock::logSherlockSearch($this->mQ, $vId, $this->mResults['totalresults'], $logged, $platform);

				// Then make a new cookie
				$response = $request->response();
				$response->setcookie("sherlock_id", $searchId);
				$response->setcookie("sherlock_q", $this->mQ);
			} else {
				// It's the same query, so we're saying it's not a new search & they must have just gone "back".
				// Don't make a new search entry.
			}

			return $searchId;
		}
	}

	private function isBadQuery($q): bool {
		global $wgBogusQueries, $wgCensoredWords;

		if (empty($q)) {
			return true;
		}

		if (in_array(strtolower($q), $wgBogusQueries) ) {
			return true;
		}

		foreach ($wgCensoredWords as $censoredWord) {
			if (stripos($q, $censoredWord) !== false) {
				return true;
			}
		}

		return false;
	}

	private function cleanTitle(&$t) {
		// remove detailed title from search results

		$domain = wfCanonicalDomain();
		$tld = array_pop(explode('.', $domain)); // 'com', 'es', etc

		$t = str_replace("- <b>wikihow</b>.<b>$tld</b>", '', $t);
		$t = str_replace("- <b>$domain</b>", '', $t);

		$t = str_replace('<b>wikiHow</b>', "wikiHow", $t);
		$t = str_replace("–", '-', $t);
		$t = str_replace(" - wikiHow", "", $t);
		$t = preg_replace("@ \(with[^\.]+[\.]*@", "", $t);
		$t = preg_replace("/\:(.*?)steps$/i", "", $t);
		$t = str_replace(' - how to articles from wikiHow', '', $t);
		//$t = str_replace(' - How to do anything', '', $t);

		// If Bing highlighting enabled, switch out highlight characters for html bolding
		// See https://onedrive.live.com/view.aspx?resid=9C9479871FBFA822!112&app=Word&authkey=!ANNnJQREB0kDC04
		// for more info under the EnableHighlighting Option
		$t = preg_replace(["@\x{E000}@u","@\x{E001}@u"], ["<b>","</b>"], $t);
	}

	private function localizeUrl(&$url) {
		$domain = str_replace('.', '\.', wfCanonicalDomain());
		$localizedUrl = preg_replace("@^https?://$domain/@", '', $url);
		if ($localizedUrl == $url) {
			$domain = str_replace('.', '\.', wfCanonicalDomain('', true));
			$localizedUrl = preg_replace("@^https?://$domain/@", '', $url);
		}

		// a chance for a hook (like alternate domains) to localize the url
		// specific to their domain
		Hooks::run( 'LSearchAfterLocalizeUrl', array( &$localizedUrl, $url ) );

		return $localizedUrl;
	}

	/**
	 * Remove any results that are currently de-indexed. This might happend occasionally if
	 * the search index is out of date.
	 */
	private function removeDeIndexedResults( $inResults ) {
		$results = [];
		foreach ( $inResults as $result ) {
			$title = Title::newFromId( $result['id'] );
			if ( RobotPolicy::isTitleIndexable( $title ) ) {
				$results[] = $result;
			}
		}
		return $results;
	}

	/**
	 * Trim all the "- wikiHow" etc off the back of the titles from external
	 * engine. Make sure the titles can be turned into a MediaWiki Title object.
	 */
	private function makeTitlesUniform($inResults) {
		$results = array();

		// if the $inResults is not an array of results but just one result, wrap it in an array
		if ( array_key_exists( 'title', $inResults ) ) {
			$inResults = array( $inResults );
		}

		foreach ($inResults as $r) {
			$r = array_change_key_case($r);
			$t = htmlspecialchars_decode($r['title']);
			$this->cleanTitle($t);

			$url = $this->localizeUrl($r['url']);
			$tobj = Title::newFromText(urldecode($url), NS_MAIN);
			if (!$tobj || !$tobj->exists()) continue;
			$key = $tobj->getDBkey();

			$results[] = array(
				'title_match' => $t,
				'url' => $url,
				'key' => $key,
				'id' => $tobj->getArticleId(),
				'namespace' => $tobj->getNamespace(),
			);
		}
		return $results;
	}

	/**
	 * Add our own meta data to the search results to make them more
	 * interesting and informative to look at.
	 */
	private function supplementResults($titles) {
		global $wgMemc;

		if (count($titles) == 0) {
			return [];
		}

		$allArticleIds = array_reduce($titles, function($carry, $item) {
			return $carry . $item['id'];
		});

		$enc_q = urlencode($this->mQ);
		$cachekey = wfMemcKey('search_suppl', md5($allArticleIds));
		$rows = $wgMemc->get($cachekey);

		if (!is_array($rows)) {
			$ids = [];
			foreach ($titles as $title) {
				$ids[] = $title['id'];
			}

			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->select('search_results', '*', array('sr_id' => $ids), __METHOD__);
			$rows = [];
			foreach ($res as $row) {
				$rows[ $row->sr_title ] = (array)$row;
			}

			$wgMemc->set($cachekey, $rows);
		}

		foreach ($titles as $title) {
			$key = $title['key'];
			$hasSupplement = isset($rows[$key]);
			if ($hasSupplement) {
				foreach ($rows[$key] as $k => &$v) {
					if (preg_match('@^sr_@', $k)) {
						$k = preg_replace('@^sr_@', '', $k);
						if ($v && preg_match('@^img_thumb@', $k)) {
							$v = wfGetPad($v);
						}
						$title[$k] = $v;
					}
				}
			}
			$title['has_supplement'] = intval($hasSupplement);
			$isCategory = $title['namespace'] == NS_CATEGORY;
			$title['is_category'] = intval($isCategory);
			$results[] = $title;
		}

		return $results;
	}

	// will display the search results that have been formatted by supplementResults
	private function displaySearchResults( $results, $resultsPerPage, $enc_q, $suggestionLink, $searchId, $resultsSource ) {
		global $wgServer;

		$out = $this->getOutput();
		$sk = $this->getSkin();

		$mw = Title::makeTitle(NS_SPECIAL, "Search");
		$specialPageURL = $mw->getFullURL();

		$total = $this->mResults['totalresults'];
		$start = $this->mStart;
		$last = $this->mLast;

		$q = $this->mQ;

		$me = Title::makeTitle(NS_MAIN, 'wikiHowTo');

		// Google was complaining about "soft 404s" in GWMT, so I'm making this a hard 404 instead.
		// -Reuben, June 14, 2016
		if (!$results) {
			$out->setStatusCode(404);
			// count 'no results' in statsd too, so we can see ops-related spikes in grafana
			WikihowStatsd::increment('search.noresults');
		}

		$androidParam = class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest() ?
			"&" . AndroidHelper::QUERY_STRING_PARAM . "=1" : "";
		//buttons
		// - next
		$disabled = !($total > $start + $resultsPerPage && $last == $start + $resultsPerPage);
		// equivalent to: $disabled = $total <= $start + $resultsPerPage || $last != $start + $resultsPerPage;
		$next_url = '/' . $me . '?search=' . urlencode($q) . '&start=' . ($start + $resultsPerPage) . $androidParam;
		if ( $this->mEnableBeta ) {
			$next_url .= '&beta=true';
		}

		$next_label = wfMessage( "lsearch_next" )->text();
		if ( $disabled ) {
			$next_button = Html::rawElement(
				'span',
				[ 'class' => 'button buttonright primary disabled' ],
				$next_label
			);
		} else {
			$next_button = Html::rawElement(
				'a',
				[ 'href' => $next_url, 'class' => 'button buttonright primary' ],
				$next_label
			);
		}

		// - previous
		$disabled = !($start - $resultsPerPage >= 0);
		// equivalent to: $disabled = $start < $resultsPerPage;

		$prev_url = '/' . $me . '?search=' . urlencode($q) . ($start - $resultsPerPage !== 0 ? '&start=' . ($start - $resultsPerPage) : '') . $androidParam;
		if ( $this->mEnableBeta ) {
			$prev_url .= '&beta=true';
		}


		$prev_label = wfMessage( "lsearch_previous" )->text();
		if ( $disabled ) {
			$prev_button = Html::rawElement(
				'span',
				[ 'class' => 'button buttonleft primary disabled' ],
				$prev_label
			);
		} else {
			$prev_button = Html::rawElement(
				'a',
				[ 'href' => $prev_url, 'class' => 'button buttonleft primary' ],
				$prev_label
			);
		}

		$page = (int) ($start / $resultsPerPage) + 1;

		$adProvider = $this->mEnableBeta ? 'google' : 'yahoo';

		if ( $this->disableAds ) {
			wikihowAds::exclude();
		}

		if (!$resultsSource) {
			$resultsSource = '(unknown)';
		}

		$vars = array(
			'q' => $q,
			'enc_q' => $enc_q,
			'ads' => $this->disableAds ? '' :
				wikihowAds::getSearchAds($adProvider, $q, $page, count($results)),
			'sk' => $sk,
			'me' => $me,
			'max_results' => $resultsPerPage,
			'start' => $start,
			'first' => $start + 1,
			'last' => $last,
			'suggestionLink' => $suggestionLink,
			'results' => $results,
			'specialPageURL' => $specialPageURL,
			'total' => $total,
			'BASE_URL' => $wgServer,
			'next_button' => $next_button,
			'prev_button' => $prev_button,
			'results_source' => $resultsSource,
		);

		if (Misc::isMobileMode()) {
			$tmpl = 'search-results-mobile.tmpl.php';
			$out->addModuleStyles('ext.wikihow.lsearch.mobile.styles');
			$vars['no_img_blue'] = $this->mNoImgBlueMobile;
			$vars['no_img_green'] = $this->mNoImgGreenMobile;
		} else {
			$tmpl = 'search-results-desktop.tmpl.php';
			$out->addModuleStyles('ext.wikihow.lsearch.desktop.styles');
			$vars['no_img_blue'] = wfGetPad(self::NO_IMG_BLUE);
			$vars['no_img_green'] = wfGetPad(self::NO_IMG_GREEN);
		}

		// Use templates to generate the HTML for the search results & the Sherlock script
		EasyTemplate::set_path(__DIR__ . '/');
		$html = '';
		if ( $this->showSuicideHotline ) {
			$html .= EasyTemplate::html( 'suicide-hotline.tmpl.php' );
			$html .= EasyTemplate::html( 'crisis-text-line.tmpl.php' );
		}
		$html .= EasyTemplate::html($tmpl, $vars);
		// Check that the Sherlock class is loaded (IE: Not on international)
		if (class_exists('Sherlock')) {
			$html .= EasyTemplate::html('sherlock-script.tmpl.php', ['shs_key' => $searchId]);
		}

		$out->addHTML($html);
	}

	private function getSpellingSuggestion($url) {
		$spellingResults = $this->mSpelling;
		$suggestionLink = null;
		if (sizeof($spellingResults) > 0) {
			$suggestionValue = $spellingResults[0]['Value'];
			$suggestionLabel = $spellingResults[0]['Label'];
			// Lighthouse #1527 - We don't want spelling corrections for wikihow
			if (stripos($suggestion, "wiki how") === false) {
				$suggestionUrl = "$url?search=" . urlencode($suggestionValue);
				$suggestionLink = "<a href='$suggestionUrl'>$suggestionLabel</a>";
			}

		}
		return $suggestionLink;
	}

	private function getSurl(): string {
		$isM = Misc::isMobileMode();
		$lang = $this->getLanguage()->getCode();

		if ($lang == 'en')     $domain = $isM ? 'mobile.wikihow.com' : 'wikihow.com';
		elseif ($lang == 'ar') $domain = $isM ? 'arm.wikihow.com'    : 'ar.wikihow.com';
		elseif ($lang == 'cs') $domain = $isM ? 'mobile.wikihow.cz'  : 'wikihow.cz';
		elseif ($lang == 'de') $domain = $isM ? 'dem.wikihow.com'    : 'de.wikihow.com';
		elseif ($lang == 'es') $domain = $isM ? 'esm.wikihow.com'    : 'es.wikihow.com';
		elseif ($lang == 'fr') $domain = $isM ? 'frm.wikihow.com'    : 'fr.wikihow.com';
		elseif ($lang == 'hi') $domain = $isM ? 'him.wikihow.com'    : 'hi.wikihow.com';
		elseif ($lang == 'id') $domain = $isM ? 'idm.wikihow.com'    : 'id.wikihow.com';
		elseif ($lang == 'it') $domain = $isM ? 'mobile.wikihow.it'  : 'wikihow.it';
		elseif ($lang == 'ja') $domain = $isM ? 'mobile.wikihow.jp'  : 'wikihow.jp';
		elseif ($lang == 'ko') $domain = $isM ? 'kom.wikihow.com'    : 'ko.wikihow.com';
		elseif ($lang == 'nl') $domain = $isM ? 'nlm.wikihow.com'    : 'nl.wikihow.com';
		elseif ($lang == 'pt') $domain = $isM ? 'ptm.wikihow.com'    : 'pt.wikihow.com';
		elseif ($lang == 'ru') $domain = $isM ? 'rum.wikihow.com'    : 'ru.wikihow.com';
		elseif ($lang == 'th') $domain = $isM ? 'thm.wikihow.com'    : 'th.wikihow.com';
		elseif ($lang == 'vi') $domain = $isM ? 'mobile.wikihow.vn'  : 'wikihow.vn';
		elseif ($lang == 'zh') $domain = $isM ? 'zhm.wikihow.com'    : 'zh.wikihow.com';
		else                   $domain = $isM ? 'mobile.wikihow.com' : 'wikihow.com';

		return "http://$domain";
	}

	# Unused

	/**
	 * Used to log the search in the site_search_log table, to store this data for
	 * later analysis.
	 */
	/*
	private function logSearch($q, $host_id, $cache, $error, $curl_err, $gm_tm_count, $gm_ts_count, $username, $userid, $rank, $num_results, $gm_type) {
		$dbw = wfGetDB(DB_MASTER);
		$q = $dbw->strencode($q);
		$username = $dbw->strencode($username);
		$vals = array(
				'ssl_query' 		=> strtolower($q),
				'ssl_host_id' 		=> $host_id,
				'ssl_cache' 		=> $cache,
				'ssl_error' 		=> $error,
				'ssl_curl_error'	=> $curl_err,
				'ssl_ts_count' 		=> $gm_ts_count,
				'ssl_tm_count' 		=> $gm_tm_count,
				'ssl_user'			=> $userid,
				'ssl_user_text' 	=> $username,
				'ssl_num_results'	=> $num_results,
				'ssl_rank'			=> $rank,
				'ssl_type'			=> $gm_type
			);
		// FYI: this table has moved to whdata
		$res = $dbw->insert('site_search_log', $vals, __METHOD__);
	}
	*/
}
