<?php

class PageHooks {

	// Allow varnish to purge un-urlencoded version of urls so that articles such
	// as Solve-a-Rubik's-Cube-(Easy-Move-Notation) can be requested without
	// passing through the varnish caches. We had a site stability issue for
	// logged in users on 5/19/2014 because Google featured the Rubik's cube on
	// their home page and a lot of people suddenly searched for it. All requests
	// were passed to our backend, which caused stability issues.
	public static function onTitleSquidURLsDecode($title, &$urls) {
		$reverse = array_flip($urls);
		foreach (array_keys($reverse) as $url) {
			$decoded = urldecode($url);
			if ( !isset($reverse[$decoded]) && strpos($decoded, ' ') === false ) {
				$reverse[$decoded] = true;
				$urls[] = $decoded;
			}
		}

		return true;
	}

	public static function onTitleSquidURLsPurge($title, &$urls) {
		global $wgContLang, $wgLanguageCode, $wgCanonicalServer;

		// We do not need to purge the history of a video page. Anons
		// probably don't care about video histories.
		if (!$title->inNamespace(NS_VIDEO)) {
			$historyUrl = $title->getInternalURL( 'action=history' );
			if ($wgLanguageCode == 'en') {
				$partialUrl = preg_replace("@^https?://[^/]+/@", "/", $historyUrl);
				$historyUrl = $wgCanonicalServer . $partialUrl;
			}
			$urls[] = $historyUrl;
		}

		// On the tools and data servers the Host is localhost. Since a lot of
		// maintenance scripts run off these servers, we want to purge the
		// canonical urls for the desktop and mobile sites instead.
		$mainUrl = $title->getInternalURL();
		$partialUrl = preg_replace("@^https?://[^/]+/@", "/", $mainUrl);
		$mainUrl = Misc::getLangBaseURL($wgLanguageCode, false) . $partialUrl;
		$mobileUrl = Misc::getLangBaseURL($wgLanguageCode, true) . $partialUrl;
		$urls[] = $mainUrl;
		$urls[] = $mobileUrl;

		// Purge only https urls now. - Reuben, March 2018
		foreach ($urls as &$url) {
			if (preg_match('@^http://@', $url)) {
				$url = preg_replace('@^http:@', 'https:', $url);
			}
		}

		// Make sure list of URLs is unique -- only purge URLs once.
		$urls = array_unique($urls);

		return true;
	}

	/**
	 * Purge the surrogate-key for an article when that article is purged through
	 * any normal purge.
	 *
	 * See: https://docs.fastly.com/guides/purging/getting-started-with-surrogate-keys
	 */
	public static function onPreCDNPurge($title, &$urls) {
		$id = $title->getArticleID();
		if ($id > 0) {
			$ctx = RequestContext::getMain();
			$langCode = $ctx->getLanguage()->getCode();
			$idResetTag = "id$langCode$id";

			// Create a job that clears a particular fastly surrogate-key via the api
			$params = ['action' => 'reset-tag', 'lang' => $langCode, 'tag' => $idResetTag];
			$job = new FastlyActionJob($title, $params);
			JobQueueGroup::singleton()->push($job);
		}

		return true;
	}

	// We simulate the useformat=mobile to display the mobile layout
	// when coming in on a mobile m. domain. We hook in as early as
	// possible because if MobileContext::shouldDisplayMobileView() is
	// called before this hook, its value is computed then cached
	// incorrectly.
	public static function onSetupAfterCacheSetMobile() {
		global $wgRequest, $wgNoMobileRedirectTest;

		// Uses raw headers rather than trying to instantiate a mobile
		// context object, which might not be possible
		if ( Misc::isMobileModeLite() && !$wgRequest->getVal('useformat')) {
			// Only define this param if doesn't already exist. Sometimes mobile pages have
			// useformat=desktop, in which case we redirect (see maybeRedirectIfUseformat())
			$wgRequest->setVal('useformat', 'mobile');
		}
		return true;
	}

	public static function onApiBeforeMain(&$main) {
		global $wgRequest;

		// Uses raw headers rather than trying to instantiate a mobile
		// context object, which might not be possible
		if ( Misc::isMobileModeLite() ) {
			$wgRequest->setVal('useformat', 'mobile');
		}

		// 2018-02-15 - Set the robots policy to noindex, follow for api.php requests as part of SEO optimizations
		header("X-Robots-Tag: noindex, nofollow", true);

		return true;
	}

	// We hook into the UnknownAction callback so that we set the
	// resulting page as 404 when the action is not found.
	// WARNING: If we ever start supporting other actions by using
	// this hook, we should just test that this hook does not set
	// the page as 404 for those new actions.
	public static function onUnknownAction($action, $page) {
		$page->getContext()->getOutput()->setStatusCode('404');
		return true;
	}

	/**
	 * A callback check if the request is behind fastly, and if so, look for
	 * the XFF header.
	 */
	public static function checkFastlyProxy($ip, &$trusted) {
		if (!$trusted) {
			$value = isset($_SERVER[WH_FASTLY_HEADER_NAME]) ? $_SERVER[WH_FASTLY_HEADER_NAME] : '';
			$trusted = $value == WH_FASTLY_HEADER_VALUE;
		}
		return true;
	}

	/**
	 * Mediawiki 1.21 seems to redirect pages differently from 1.12, so we recreate
	 * the 1.12 functionality from "redirect" articles that are present in the DB.
	 *    - Reuben, 12/23/2013
	 */
	public static function onInitializeArticleMaybeRedirect($title, $request, $ignoreRedirect, &$target, $article) {
		if ( !$ignoreRedirect && !$target && $article->isRedirect() ) {
			$target = $article->followRedirect();
			if ($target instanceof Title) {
				if ( Misc::isMobileMode() && GoogleAmp::hasAmpParam( $request ) ) {
					$target = GoogleAmp::getAmpUrl( $target);
				} else {
					$target = $target->getFullURL();
				}
			}
		}
		return true;
	}

	/**
	 * Redirect to the relevant domain if the useformat=[desktop|mobile] parameter exists
	 */
	public static function maybeRedirectIfUseformat(&$title, &$unused, &$output, &$user, $request, $mediaWiki) {
		if ($request->wasPosted() || !$request->getVal('useformat')) {
			return true;
		}

		$isMobile = Misc::isMobileModeLite();
		$useformat = $request->getVal('useformat');
		$lang = $output->getLanguage()->getCode();

		if ($isMobile && $useformat == 'desktop') {
			$baseUrl = Misc::getLangBaseURL($lang);
		} elseif (!$isMobile && $useformat == 'mobile') {
			$baseUrl = Misc::getLangBaseURL($lang, true);
		} else {
			$baseUrl = null;
		}

		if ($baseUrl) {
			$params = $request->getValues();
			unset($params['useformat'], $params['title']);
			$destUrl = wfAppendQuery($baseUrl . $title->getLocalURL(), $params);

			$orig = $request->getFullRequestURL();
			$message = "maybeRedirectIfUseformat: orig=$orig, dest=$destUrl";
			wfDebugLog('redirects', $message);

			$output->redirect($destUrl, 301);
		}

		return true;
	}

	/**
	 * Add a no-index header to Special:RecentChanges RSS feeds
	 */
	public static function noIndexRecentChangesRSS( &$title, &$article, &$output, &$user, $request, $mediaWiki ) {
		if (
			$title &&
			$title->inNamespace( NS_SPECIAL ) &&
			$title->getPrefixedURL() == SpecialPage::getTitleFor( 'Recentchanges' )->getPrefixedURL() &&
			( $request->getVal( 'feed' ) === 'rss' || $request->getVal( 'feed' ) === 'atom' )
		) {
			header( 'X-Robots-Tag: noindex', true );
		}

		return true;
	}

	/**
	 * BeforePageDisplay Hook handler to add surrogate key headers
	 * @param $out
	 * @param $skin
	 * @return bool
	 */
	public static function addVarnishHeaders($out, $skin) {
		$title = $out->getTitle();
		self::addSurrogateKeyHeaders($out, $title, $out->getRequest());

		return true;
	}

	/**
	 * Sets up article requests to have a Surrogate-Key response header, which
	 * is used to purge Fastly of all variants of an article.
	 *
	 * @param $out OutputPage
	 * @param $title Title
	 */
	public static function addSurrogateKeyHeaders($out, $title, $req) {
		if ($req && $title) {
			$layoutStr = Misc::isMobileMode() ? 'mb' : 'dt';
			$req->response()->header("X-Layout: $layoutStr");
			$out->addVaryHeader('X-Layout');

			$langCode = $out->getLanguage()->getCode();
			$id = $title->getArticleID();
			$rollingResetTensStr = ($id > 0 ? ' rollnn' . ($id % 10) : '');
			$rollingResetHundredsStr = ($id > 0 ? ' rollnnn' . ($id % 100) : '');
			$idResetStr = ($id > 0 ? " id$langCode$id" : '');
			$req->response()->header("Surrogate-Key: $layoutStr$rollingResetTensStr$rollingResetHundredsStr$idResetStr");
		}
	}



	/**
	 * Runs on BeforePageDisplay hook, making creating a new internet.org
	 * cache vertical in front end caches (varnish).
	 *
	 * 1) Add the Vary header x-internetorg to every mobile domain response.
	 * 2) We add the x-internetorg: 1 HTTP response header to all
	 *    internet.org requests as well.
	 */
	public static function addInternetOrgVaryHeader($out, $skin) {
		if ( $out && Misc::isMobileMode() ) {
			$req = $out->getRequest();
			$out->addVaryHeader('x-internetorg');
			if ($req && WikihowMobileTools::isInternetOrgRequest() ) {
				$req->response()->header('x-internetorg: 1');
			}
		}
		return true;
	}

	// Check country ban
	public static function enforceCountryPageViewBan(&$outputPage, &$text) {
		$countryBanHeader = $outputPage->getRequest()->getHeader('x-country-ban');
		if ($countryBanHeader == 'YES'
			//|| $outputPage->getRequest()->getVal('test') == 'ban'
		) {
			$text = '
				<p><br/>
				Article cannot be viewed<br/>
				Please visit <a href="/Main-Page">wikiHow Main Page</a> instead.</p>';
		}
		return true;
	}

	/* data schema
	 *
	 CREATE TABLE redirect_page (
		rp_page_id int(8) unsigned NOT NULL,
		rp_folded varchar(255) NOT NULL,
		rp_redir varchar(255) NOT NULL,
		PRIMARY KEY(rp_page_id),
		INDEX(rp_folded)
	 );
	 */


	/**
	 * Callback to check for a case-folded redirect
	 */
	public static function check404Redirect($title) {
		$redirectTitle = Misc::getCaseRedirect( $title );

		if ( $redirectTitle ) {
			return $redirectTitle->getPartialURL();
		}
	}

	/**
	 * Callback to create, modify or delete a case-folded redirect
	 */
	public static function modify404Redirect($pageid, $newTitle) {
		static $dbw = null;
		if (!$dbw) $dbw = wfGetDB(DB_MASTER);
		$pageid = intval($pageid);

		if ($pageid <= 0) {
			return;
		} elseif (!$newTitle
				|| !$newTitle->exists()
				|| !$newTitle->inNamespace(NS_MAIN))
		{
			$dbw->delete('redirect_page', array('rp_page_id' => $pageid), __METHOD__);
		} else {
			// debug:
			//$field = $dbw->selectField('redirect_page', 'count(*)', array('rp_page_id'=>$pageid));
			//if ($field > 0) { print "$pageid $newTitle\n"; }
			$newrow = array(
				'rp_page_id' => intval($pageid),
				'rp_folded' => Misc::redirectGetFolded( $newTitle->getText() ),
				'rp_redir' => substr( $newTitle->getText(), 0, 255 ),
			);
			$dbw->replace('redirect_page', 'rp_page_id', $newrow, __METHOD__);
		}
	}

	public static function setPage404IfNotExists() {
		global $wgTitle, $wgOut, $wgLanguageCode, $wgUser;

		// Note: if namespace < 0, it's a virtual namespace like NS_SPECIAL
		// Check if image exists for foreign language images, because Title may not exist since image may only be on English
		if ($wgTitle
			&& $wgTitle->getNamespace() >= 0
			&& !$wgTitle->exists()
			&& ($wgLanguageCode == 'en'
				|| !$wgTitle->inNamespace(NS_IMAGE)
				|| !wfFindFile($wgTitle))
		) {
			$redirect = self::check404Redirect($wgTitle);
			if (!$redirect) {
				$wgOut->setStatusCode(404);
			} else {
				$wgOut->redirect('/' . $redirect, 301);
			}
		}
		if ($wgTitle && !$wgUser->isLoggedIn() && $wgTitle->inNamespace(NS_TALK)) {
			//want to 404 logged out discussion pages
			$wgOut->setStatusCode( 404 );
		}
		return true;
	}

	//
	// Hooks for managing 404 redirect system
	//
	public static function fix404AfterMove($oldTitle, $newTitle) {
		if ($oldTitle && $newTitle) {
			self::modify404Redirect($oldTitle->getArticleID(), null);
			self::modify404Redirect($newTitle->getArticleID(), $newTitle);
		}
		return true;
	}

	/**
	 * Hook for purging title based watermark thumbnails when a page moves
	 */
	public static function onTitleMoveCompletePurgeThumbnails($oldTitle, $newTitle) {
		$newId = $newTitle->getArticleID();
		$dbw = wfGetDB( DB_MASTER );
		$table = "moved_title_images";
		$values = array( "mti_page_id" => $newId, 'mti_processed' => 0);
		$options = array( 'IGNORE' );

		$dbw->insert( $table, $values, __METHOD__, $options );

		return true;
	}

	public static function fix404AfterDelete($wikiPage) {
		if ($wikiPage) {
			$pageid = $wikiPage->getId();
			if ($pageid > 0) {
				self::modify404Redirect($pageid, null);
			}
		}
		return true;
	}

	public static function fix404AfterInsert($wikiPage) {
		if ($wikiPage) {
			$title = $wikiPage->getTitle();
			if ($title) {
				self::modify404Redirect($wikiPage->getID(), $title);
			}
		}
		return true;
	}

	public static function fix404AfterUndelete($title) {
		if ($title) {
			$pageid = $title->getArticleID();
			self::modify404Redirect($pageid, $title);
		}
		return true;
	}

	public static function onResourceLoaderStartupModuleQuery(&$query) {
		unset($query['version']);
		return true;
	}

	public static function onConfigStorageDbStoreConfig($key, $val) {
		if (class_exists('UserCompletedImages') && $key == UserCompletedImages::CONFIG_KEY) {
			$oldVal = ConfigStorage::dbGetConfig(UserCompletedImages::CONFIG_KEY);
			$oldVal = $oldVal ? explode("\n", $oldVal) : array();
			$val = $val ? explode("\n", $val) : array();
			UserCompletedImages::addToWhitelist(array_diff($val, $oldVal));
			UserCompletedImages::removeFromWhitelist(array_diff($oldVal, $val));
		}
		return true;
	}

	/**
	 * Decide whether on not to autopatrol an edit
	 */
	public static function onMaybeAutoPatrol($page, $user, &$patrolled) {
		global $wgLanguageCode, $wgRequest;

		// If this edit was already flagged autopatrol, only
		// keep this flag if the user has the autopatrol preference on
		if ( $patrolled && !$user->getOption('autopatrol') ) {
			$patrolled = false;
		}

		$userGroups = $user->getGroups();

		// All edits from users in the bot group are autopatrolled
		$noAutoPatrolBots = array('AnonLogBot');
		if ( in_array('bot', $userGroups)
			&& !in_array($user->getName(), $noAutoPatrolBots) )
		{
			$patrolled = true;
		}

		// Force auto-patrol for translators and international
		if ( $wgLanguageCode != "en" &&
			( in_array('sysop', $userGroups)
				|| in_array('staff', $userGroups)
				|| in_array('translator', $userGroups)
				|| in_array($user->getName(), array('AlfredoBot', 'InterwikiBot', wfMessage('translator_account')->plain()))
			))
		{
			$patrolled = true;
		}

		// All edits to User_kudos and User_kudos_talk namespace are autopatrolled
		if ( $page->mTitle->inNamespaces( NS_USER_KUDOS, NS_USER_KUDOS_TALK ) ) {
			$patrolled = true;
		}

		// All edits to User namespace (if editing their own page page) are autopatrolled
		if ( $page->mTitle->inNamespace(NS_USER) ) {
			$userName = $user->getName();
			$pageUser = $page->mTitle->getBaseText();
			if ($userName == $pageUser) {
				$patrolled = true;
			}
		}

		if ( $page->mTitle->inNamespace(NS_MAIN) ) {
			//all edits to overwritable articles are autopatrolled
			if ( $page->exists() && NewArticleBoost::isOverwriteAllowed($page->getTitle()) && $wgRequest->getVal("overwrite") == "yes" ) {
				$patrolled = true;
			}

			//all new articles no longer go into RCP
			$oldestRevision =  $page->getOldestRevision();
			$newestRevision = $page->getRevision();
			if ( $oldestRevision != null && $newestRevision != null && $oldestRevision->getId() == $newestRevision->getId() ) {
				$patrolled = true;
			}
		}

		//Summary: pages
		if ($page->mTitle->inNamespace(NS_SUMMARY)) {
			$patrolled = true;
		}

		//every page edit that adds a quick summary
		if ($page->mTitle->inNamespace(NS_MAIN) &&
			SummaryEditTool::authorizedUser($user) &&
			$page->getComment() == wfMessage('summary_add_log')->text())
		{
			$patrolled = true;
		}

		return true;
	}

	// Temporary, for redirect debugging
//	public static function onBeforePageRedirect($out, $redirect) {
//		global $wgUser, $wgDebugRedirects;
//		if ($wgUser && in_array($wgUser->getName(), array('Reuben', 'Anna'))) {
//			$url = htmlspecialchars( $redirect );
//			print "<html>\n<head>\n<title>Redirect</title>\n</head>\n<body>\n";
//			print "<p>You are Anna or Reuben so you see this on a redirect\n";
//			print "<p>Location: <a href=\"$url\">$url</a></p>\n";
//			print "<pre>current backtrace:\n" . wfBacktrace() . "</pre>\n";
//			print "<pre>redirect point:\n" . $out->mRedirectSource . "</pre>\n";
//			print "</body>\n</html>\n";
//			exit;
//		}
//		return true;
//	}

	public static function addFirebug(OutputPage &$out, Skin &$skin) {
		if (@$_GET['firebug'] == true) {
			$out->addHeadItem('firebug', '<script src="//getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js"></script>');
		}
		return true;
	}

	public static function checkForDiscussionPage(&$out) {
		$title = $out->getTitle();

		//talk pages for anons redir to login
		if ($title && $title->isTalkPage() && !$title->inNamespace(NS_USER_TALK) && $out->getUser()->isAnon()) {
			$login = 'index.php?title='.SpecialPage::getTitleFor('Userlogin').'&type=signup&returnto='.urlencode($title->getPrefixedURL());
			$out->redirect($login);
		}
		return true;
	}

	// check for any query parameters that we do not allow in the url like a username or user
	public static function maybeRedirectRemoveInvalidQueryParams(&$title, &$unused, &$output, &$user, $request, $mediaWiki) {
		global $wgLanguageCode, $wgIsSecureSite, $wgIsStageDomain;

		// for now allow any query params in the non main namespace titles
		if ( !$title
			|| !$title->inNamespace(NS_MAIN)
			|| $request->wasPosted()
		) {
			return true;
		}

		$redirect = false;
		$query = $request->getValues();
		$regex = '/.@./';
		$badvalues = [];
		foreach ( $query as $key => $value ) {
			if ( $key == "title" ) {
				continue;
			}
			if ( is_string($value) && preg_match( $regex, $value ) ) {
				$redirect = true;
				array_push($badvalues, $value);
				unset( $query[$key] );
			}
		}

		if ( $redirect == true ) {
			unset( $query['title'] );
			$url = wfAppendQuery( $title->getFullURL(), $query );
			$debugtext = "maybeRedirectRemoveInvalidQueryParams: redirect == true; values = " . join(", ", $badvalues);
			wfDebugLog('redirects', $debugtext);
			$output->redirect( $url, 301 );
		}
		return true;
	}

	// Redirect anons to HTTPS if they come in on HTTP
	public static function maybeRedirectHTTPS(&$title, &$unused, &$output, &$user, $request, $mediaWiki) {
		global $wgIsSecureSite, $isOldDevServer;

		// HTTP -> HTTPS redirect
		// NOTE: don't redirect posted requests
		if ( !$wgIsSecureSite
			&& !$isOldDevServer
			&& $user && $user->isAnon()
			&& !$request->wasPosted()
		) {
			$redirUrl = wfExpandUrl( $request->getRequestURL(), PROTO_HTTPS );

			$debugtext = "maybeRedirectHTTPS HTTP -> HTTPS: wgIsSecureSite: $wgIsSecureSite, user.IsAnon: " . var_export($user->isAnon(), true) . " forceHTTPS: " . $request->getCookie('forceHTTPS', '') . " wasPosted: " . var_export($request->wasPosted(), true);
			wfDebugLog('redirects', $debugtext);
			$output->redirect( $redirUrl, 301 );
		} elseif ($wgIsSecureSite && $isOldDevServer) {
			$redirUrl = wfExpandUrl( $request->getRequestURL(), PROTO_HTTP );
			$output->redirect( $redirUrl, 301 );
		}

		return true;
	}

	/**
	 * Mediawiki 1.21 doesn't natively redirect immediately if your http Host header
	 * isn't the same as $wgServer. We rely on this functionality so that domain names
	 * like wiki.ehow.com redirect to www.wikihow.com.
	 */
	private static function maybeRedirectToCanonical($output, $request, $httpHost) {
		global $wgServer, $wgIsAppServer, $wgIsDevServer, $wgIsToolsServer, $wgIsTitusServer;

		if (($wgIsAppServer || $wgIsDevServer)
			&& $wgServer != 'https://' . $httpHost
			&& !preg_match("@[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+@", $httpHost) // check that Host is not an IP
			&& !$wgIsToolsServer
			&& !$wgIsTitusServer
		) {
			$debugtext = "maybeRedirectToCanonical: wgIsAppServer: $wgIsAppServer, wgIsDevServer: $wgIsDevServer, wgServer != https:// . httpHost: " . var_export(($wgServer != 'https://' . $httpHost), true). " , hostIsIp " . var_export(preg_match("@[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+@", $httpHost), true) . " , wgIsToolsServer: $wgIsToolsServer, wgIsTitusServer: $wgIsTitusServer";
			wfDebugLog('redirects', $debugtext);
			$output->redirect( $wgServer . $request->getRequestURL(), 301 );
		}

		return true;
	}

	/**
	 * Redirect or 404 domains such as wikihow.es, testers.wikihow.com, ...
	 * NOTE: We should redirect to the mobile domains when relevant. E.g.
	 *
		elseif (preg_match('@it\.m\.wikihow\.com$@', $httpHost)) {
			$wgServer = 'https://m.wikihow.it';
		}
	 */
	public static function maybeRedirectProductionDomain(&$title, &$unused, &$output, &$user, $request, $mediaWiki) {
		global $wgServer, $wgCommandLineMode;

		$httpHost = (string)$request->getHeader('HOST');
		if (!$wgCommandLineMode) {
			if (preg_match('@^(apache[0-9]+|html5|pad[0-9]*|testers)\.wikihow\.com$@', $httpHost)) {
				header('HTTP/1.0 404 Not Found');
				die("Domain deactivated");
			}

			$preRedir = $wgServer;

			# We want to redirect the CCTLD domain wikihow.es to es.wikihow.com for
			# all languages where we own the domain. See bug #997 for reference.

			// German/French/Dutch
			if (preg_match('@(^|\.)wikihow\.(de|fr|nl)$@', $httpHost, $m)) {
				$wgServer = 'https://' . $m[2] . '.wikihow.com';
			}

			// Spanish
			elseif (preg_match('@(^|\.)wikihow\.(es|com\.mx)$@', $httpHost)) {
				$wgServer = 'https://es.wikihow.com';
			}

			// Hindi
			elseif (preg_match('@(^|\.)wikihow\.in$@', $httpHost)) {
				$wgServer = 'https://hi.wikihow.com';
			}

			// Indonesian
			elseif (preg_match('@(^|\.)wikihow\.(id|co\.id)$@', $httpHost)) {
				$wgServer = 'https://id.wikihow.com';
			}

			// Portuguese
			elseif (preg_match('@(^|\.)wikihow\.(pt|com\.br)$@', $httpHost)) {
				$wgServer = 'https://pt.wikihow.com';
			}

			// Thai
			elseif (preg_match('@(^|\.)wikihow\.in\.th$@', $httpHost)) {
				$wgServer = 'https://th.wikihow.com';
			}

			// Chinese
			elseif (preg_match('@(^|\.)wikihow\.(cn|hk|tw)$@', $httpHost)) {
				$wgServer = 'https://zh.wikihow.com';
			}

			// Japanese (dedicated domain)
			elseif (preg_match('@ja\.(m\.)?wikihow\.com$@', $httpHost)) {
				$wgServer = 'https://www.wikihow.jp';
			}
			elseif (preg_match('@^wikihow\.jp$@', $httpHost)) {
				$wgServer = 'https://www.wikihow.jp';
			}

			// Vietnamese (dedicated domain)
			elseif (preg_match('@vi\.(m\.)?wikihow\.com$@', $httpHost)) {
				$wgServer = 'https://www.wikihow.vn';
			}
			elseif (preg_match('@^wikihow\.vn$@', $httpHost)) {
				$wgServer = 'https://www.wikihow.vn';
			}

			// Italian (dedicated domain)
			elseif (preg_match('@it\.(m\.)?wikihow\.com$@', $httpHost)) {
				$wgServer = 'https://www.wikihow.it';
			}
			elseif (preg_match('@^wikihow\.it$@', $httpHost)) {
				$wgServer = 'https://www.wikihow.it';
			}

			// Czech (dedicated domain)
			elseif (preg_match('@cs\.(m\.)?wikihow\.com$@', $httpHost)) {
				$wgServer = 'https://www.wikihow.cz';
			}
			elseif (preg_match('@^wikihow\.cz$@', $httpHost)) {
				$wgServer = 'https://www.wikihow.cz';
			}

			// Turkish (dedicated domain)
			elseif (preg_match('@tr\.(m\.)?wikihow\.com$@', $httpHost)) {
				$wgServer = 'https://www.wikihow.com.tr';
			}
			elseif (preg_match('@^wikihow\.com.tr$@', $httpHost)) {
				$wgServer = 'https://www.wikihow.com.tr';
			}

			if ($preRedir != $wgServer) {
				$debugtext = "maybeRedirectProductionDomain: preRedir: $preRedir, wgServer: $wgServer";
				wfDebugLog('redirects', $debugtext);
				$output->redirect( $wgServer . $request->getRequestURL(), 301 );
			}
		}

		self::maybeRedirectToCanonical($output, $request, $httpHost);

		return true;
	}

	public static function maybeRedirectTitus(&$title, &$unused, &$output, &$user, $request, $mediaWiki) {
		global $wgIsSecureSite, $wgIsTitusServer;

		if ($wgIsTitusServer) {
			if (!$wgIsSecureSite) {
				// redirect to https url
				$redirUrl = wfExpandUrl( $request->getRequestURL(), PROTO_HTTPS );
				$output->redirect( $redirUrl );
			} else {
				$domainName = @$_SERVER['SERVER_NAME'];
				$isTitus = $domainName == 'titus.wikiknowhow.com';
				$isFlavius = $domainName == 'flavius.wikiknowhow.com';
				$isWVL = $domainName == 'wvl.wikiknowhow.com';

				if ($isTitus || $isFlavius || $isWVL) {
					$uri = @$_SERVER['REQUEST_URI'];
					if ($wgCommandLineMode
						|| strpos($uri, '/load.php') !== false
					) {
						# do something here? no.
					} else {
						$redirTitle = '';
						if ($isTitus) {
							$redirTitle = 'Special:TitusQueryTool';
						} elseif ($isFlavius) {
							$redirTitle = 'Special:FlaviusQueryTool';
						} elseif ($isWVL) {
							$redirTitle = 'Special:WikiVisualLibrary';
						}
						if ($redirTitle && (!$title || !$title->inNamespace(NS_SPECIAL))) {
							$isSameTitle = false;
							if ($title && $title->inNamespace(NS_SPECIAL) && $title->getPrefixedURL() == $redirTitle) {
								$isSameTitle = true;
							}
							// make sure there is no redirect loop
							if (!$isSameTitle) {
								$output->redirect( $wgServer . '/' . $redirTitle);
							}
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * This hook is executed to force special pages to redirect to production if
	 * the special page is not intended to be run on the titus host. If a special page
	 * is intended to be run on the titus host, implement this method in your special
	 * page class:
	 *
	 *   // method stops redirects when running on titus host
	 *   public function isSpecialPageAllowedOnTitus() {
	 *       return true;
	 *   }
	 */
	public static function onSpecialPageBeforeExecuteRedirectTitus($specialPage, $subPage) {
		$out = $specialPage->getOutput();
		$domainName = @$_SERVER['SERVER_NAME'];
		$isTitus = $domainName == 'titus.wikiknowhow.com';
		$isFlavius = $domainName == 'flavius.wikiknowhow.com';
		$isWVL = $domainName == 'wvl.wikiknowhow.com';
		if ($isTitus || $isFlavius || $isWVL) {
			$uri = @$_SERVER['REQUEST_URI'];
			$builtInAllowed =
				stripos($uri, 'special:userlog') !== false
				|| strpos($uri, 'Special:Captcha') !== false
				|| strpos($uri, 'Special:ChangePassword') !== false;
			if (!$builtInAllowed) {
				$className = get_class($specialPage);
				$reflector = new ReflectionClass($className);
				try {
					$method = $reflector->getMethod('isSpecialPageAllowedOnTitus');
					if ($method->isPublic() && $method->invoke($specialPage)) {
						$allowed = true;
					} else {
						$allowed = false;
					}
				} catch (ReflectionException $e) {
					$allowed = false;
				}
				if (!$allowed) {
					$url = $specialPage->getTitle()->getPrefixedURL();
					$out->redirect( 'https://www.wikihow.com/' . $url );
				}
			}
		}

		return true;
	}

	/**
	 * @param $title
	 * @param $unused
	 * @param $output
	 * @param $user
	 * @param $request
	 * @param $mediaWiki
	 * @return bool
	 *
	 * if the request has printable key set in the query param, unset it and the images param and
	 * redirect back to the regular page
	 */
	public static function redirectIfPrintableRequest(&$title, &$unused, &$output, &$user, $request, $mediaWiki) {
		global $wgServer;

		$redirect = false;
		$query = $request->getValues();
		foreach ( $query as $key => $value ) {
			if ( $key == "printable" ) {
				$redirect = true;
				break;
			}
		}
		if ( $redirect == true ) {
			unset( $query['printable'] );
			unset( $query['images'] );
			unset( $query['title'] );
			$url = wfAppendQuery( $title->getFullURL(), $query );
			$output->redirect( $url, 301 );
		}
	}
	/**
	 * @param $title
	 * @param $unused
	 * @param $output
	 * @param $user
	 * @param $request
	 * @param $mediaWiki
	 * @return bool
	 *
	 * Redirect to a specific special page for all request to prevent spiders from indexing the dev server
	 */
	public static function redirectIfNotBotRequest(&$title, &$unused, &$output, &$user, $request, $mediaWiki) {
		global $wgIsSecureSite, $isSecureDevServer, $wgCommandLineMode;

		if ($isSecureDevServer) {
			if (!$wgIsSecureSite) {
				// redirect to https url
				$redirUrl = wfExpandUrl( $request->getRequestURL(), PROTO_HTTPS );
				$output->redirect( $redirUrl );
			} else {
				$uri = @$_SERVER['REQUEST_URI'];
				if ($wgCommandLineMode
					|| strpos($uri, 'Special:MessengerSearchBot') !== false
					|| strpos($uri, 'Special:AlexaSkillReadArticleWebHook') !== false
					|| strpos($uri, 'Special:APIAIWikihowAgentWebHook') !== false
				) {
					# do something here? no.
				} else {
					$output->redirect( '/Special:AlexaSkillReadArticleWebHook');
				}
			}
		}

		return true;
	}

	public static function beforeArticlePurge( $wikiPage ) {
		if ( $wikiPage ) {
			RobotPolicy::clearArticleMemc( $wikiPage );
			RelatedWikihows::clearArticleMemc( $wikiPage );
		}
		return true;
	}

	// Turn on HTTPS for all logged in users on wikiHow
	public static function makeHTTPSforAllUsers($user, &$https) {
		global $wgIsDevServer, $wgServer;
		if (!$wgIsDevServer && $user && !$user->isAnon()) {
			// We haven't paid for the Fastly shared cert for domains like
			// es.m.wikihow.com, r.wikidogs.com, etc yet. So we exclude
			// these domains from HTTPS for now so that the user doesn't
			// see a terrible warning message.
			$count = substr_count($wgServer, '.');
			if ($count <= 2) {
				$https = true;
			} else {
				$https = false;
			}
		}
	}

	/**
	 * Hide certain head links for anons on noindex pages, to avoid leaking
	 * article info to Googlebot.
	 *
	 * @see OutputPage.php#getHeadLinksArray()
	 */
	public static function onOutputPageAfterGetHeadLinksArray(array &$links, OutputPage $out) {
		if (WikihowSkinHelper::shouldShowMetaInfo($out)) {
			return true;
		}

		foreach ($links as $key => $val) {
			$hide = ( $key === 'alternative-edit' )   // <link rel="edit" title="Edit" href="...
				|| ( $key === 'universal-edit-button' ) // <link rel="alternate" type="application/x-wiki" title="Edit" ...
				|| ( $key === 'meta-description' ) // <meta name="description" content="...
				|| preg_match('/rel=["\']canonical["\']/', $val);
			if ($hide) {
				unset($links[$key]);
			}
		}

		return true;
	}

	public static function onAfterDisplayNoArticleText( $article ) {
		$out = $article->getContext()->getOutput();
		if ( !GoogleAmp::isAmpMode( $out ) && $article->getTitle()->inNamespace(NS_MAIN) ) {
			$out->addHtml( SearchBox::render( $out ) );
		}
	}
}
