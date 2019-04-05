<?php

class ProfileBox extends UnlistedSpecialPage {

	static $featuredArticles = array();

	public function __construct() {
		parent::__construct( 'ProfileBox' );
	}

	public static function getUserPageHeaderHTML() {
		$html = '';

		$req = RequestContext::getMain()->getRequest();
		$action = $req->getVal('action', 'view');

		$profileBoxName = wfMessage('profilebox-name')->text();
		if ($action == 'view') {
			$html = "
<div id='gatEditRemoveButtons'>
	<a href='/index.php?title=Special:ProfileBox' id='gatProfileEditButton'>" . wfMessage('edit')->plain() . "</a>
	| <a href='#' id='remove_user_page'>" . wfMessage('remove')->plain() . " $profileBoxName</a>
</div>";
		}
		return $html;
	}

	private function getPBTitle() {
		$user = $this->getUser();

		$name = "";

		$name .= wfMessage('profilebox-name')->text();
		$name .= " for ". $user->getName();
		$avatar = Avatar::getPicture($user->getName());

		if ($user->getID() > 0) {
			$userReg = $user->getRegistration();
			if ($userReg) {
				$ts = wfTimestamp(TS_UNIX, $userReg);
			} else {
				$ts = wfTimestamp(TS_UNIX, '20060725043938');
			}
			$pbDate = self::getMemberSince($ts);
		}
		$heading = $avatar . "<div id='avatarNameWrap'><h1 class=\"firstHeading\">" . $name . "</h1><div id='regdate'>" . wfMessage('pb-joinedwikihow', $pbDate)->text() . "</div></div><div style='clear: both;'> </div>";

		return $heading;
	}

	private function displayForm() {
		$out = $this->getOutput();
		$user = $this->getUser();
		$langCode = $this->getLanguage()->getCode();

		$out->addHTML('<div class="section_text">');
		$out->addHTML($this->getPBTitle());
		$out->addHTML('</div>');

		$live = '';
		$occupation = '';
		$aboutme = '';
		if ($user->getOption('profilebox_display') == 1) {
			$t = Title::newFromText($user->getUserPage() . '/profilebox-live');
			if ($t->getArticleId() > 0) {
				$r = Revision::newFromTitle($t);
				$live = ContentHandler::getContentText( $r->getContent() );
			}
			$t = Title::newFromText($user->getUserPage() . '/profilebox-occupation');
			if ($t->getArticleId() > 0) {
				$r = Revision::newFromTitle($t);
				$occupation = ContentHandler::getContentText( $r->getContent() );
			}
			$t = Title::newFromText($user->getUserPage() . '/profilebox-aboutme');
			if ($t->getArticleId() > 0) {
				$r = Revision::newFromTitle($t);
				$aboutme = ContentHandler::getContentText( $r->getContent() );
				$aboutme = preg_replace('/\\\\r\\\\n/s',"\n",$aboutme);
				$aboutme = stripslashes($aboutme);
			}

			if ($user->getOption('profilebox_stats') == 1) { $checkStats = 'CHECKED'; }
			if ($user->getOption('profilebox_startedEdited') == 1) { $checkStartedEdited = 'CHECKED'; }
			if ($user->getOption('profilebox_questions_answered',1) == 1) { $checkQuestionsAnswered = 'CHECKED'; }
			if ($user->getOption('profilebox_favs') == 1) { $checkFavs = 'CHECKED'; }

			if ($t = Title::newFromID($user->getOption('profilebox_fav1'))) {
				if ($t->getArticleId() > 0) {
					$fav1 = $t->getText();
					$fav1id = $t->getArticleId();
				}
			}
			if ($t = Title::newFromID($user->getOption('profilebox_fav2'))) {
				if ($t->getArticleId() > 0) {
					$fav2 = $t->getText();
					$fav2id = $t->getArticleId();
				}
			}
			if ($t = Title::newFromID($user->getOption('profilebox_fav3'))) {
				if ($t->getArticleId() > 0) {
					$fav3 = $t->getText();
					$fav3id = $t->getArticleId();
				}
			}

		} else {
			$checkStats = 'CHECKED';
			$checkStartedEdited = 'CHECKED';
			$checkQuestionsAnswered = 'CHECKED';
			$checkFavs = 'CHECKED';
		}


		$out->addHTML("
<script language='javascript' src='" . wfGetPad('/extensions/wikihow/profilebox/profilebox.js?') . WH_SITEREV . "'></script>
<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/profilebox/profilebox.css?') . WH_SITEREV . "' type='text/css' />

<form method='post' name='profileBoxForm'>
<div class='section_text'><div class='altblock'></div>" .wfMessage('pb-demographic')->text() . "

<div class='pb_block'>" .wfMessage('pb-location')->text() . "<br />
<input class='input_med' type='text' name='live' value='".$live."' placeholder='" .wfMessage('pb-location-ph')->text() . "'>
</div>

<div class='pb_block'>" .
($langCode == "en" ? wfMessage('pb-website-entry')->text() : wfMessage('pb-website')->text()) . "<br />
<input class='input_med' type='text' name='occupation' value='".$occupation."' placeholder='" .wfMessage('pb-website-ph')->text() . "'>
</div>

<div class='pb_block'>" .
wfMessage('pb-aboutme')->text() . "<br />
<textarea class='textarea_med' name='aboutme' cols='55' rows='3' style='overflow:auto;' placeholder='".wfMessage('pb-aboutme-ph')->text()."' >".$aboutme."</textarea>
</div>

</div>
<br />
<div class='section_text'><div class='altblock'></div>" .
wfMessage('pb-displayinfo')->text() . "<br /><br />
<input type='checkbox' name='articleStats' id='articleStats' ".$checkStats."> <label for='articleStats'>".wfMessage('profilebox-checkbox-stats')->text()."</label><br /><br />
<input type='checkbox' name='articleStartedEdited' id='articleStartedEdited' ".$checkStartedEdited."> <label for='articleStartedEdited'>".wfMessage('pb-checkbox-articlesstartededited')->text()."</label><br /><br />
<input type='checkbox' name='questionsAnswered' id='questionsAnswered' ".$checkQuestionsAnswered."> <label for='questionsAnswered'>".wfMessage('pb-checkbox-questionsanswered')->text()."</label><br />
");

$out->addHTML("
<!-- <input type='checkbox' name='recentTalkpage'> Most recent talk page messages<br /> -->
</div>
<br />

<div class='profileboxform_btns'>
	<a href='/".$user->getUserPage()."' class='button secondary'>" . wfMessage('cancel')->text() . "</a>
	<input class='button primary' type='submit' id='gatProfileSaveButton' name='save' value='" . wfMessage('pb-save')->text() . "' />
</div>

</form>
");
	}

	public static function onInitProfileBox($user) {
		$user->setOption('profilebox_fav1', "");
		$user->setOption('profilebox_fav2', "");
		$user->setOption('profilebox_fav3', "");

		$user->setOption('profilebox_stats', 1);

		$user->setOption('profilebox_startedEdited', 1);
		$user->setOption('profilebox_questions_answered', 1);

		$user->setOption('profilebox_display', 1);

		$user->saveSettings();

		return true;
	}

	private function pbConfig() {
		$req = $this->getRequest();
		$user = $this->getUser();

		$dbr = wfGetDB(DB_REPLICA);
		$live = $dbr->strencode(strip_tags($req->getVal('live'), '<p><br><b><i>'));
		$occupation = $dbr->strencode(strip_tags($req->getVal('occupation'), '<p><br><b><i>'));
		$aboutme = $dbr->strencode(strip_tags($req->getVal('aboutme'), '<p><br><b><i>'));

		$t = Title::newFromText($user->getUserPage() . '/profilebox-live');
		$article = new Article($t);
		if ($t->getArticleId() > 0) {
			$article->updateArticle($live, 'profilebox-live-update', true, $watch);
		} elseif ($live) {
			$article->insertNewArticle($live, 'profilebox-live-update', true, $watch, false, false, true);
		}

		$t = Title::newFromText($user->getUserPage() . '/profilebox-occupation');
		$article = new Article($t);
		if ($t->getArticleId() > 0) {
			$article->updateArticle($occupation, 'profilebox-occupation-update', true, $watch);
		} elseif ($occupation) {
			$article->insertNewArticle($occupation, 'profilebox-occupation-update', true, $watch, false, false, true);
		}

		$t = Title::newFromText($user->getUserPage() . '/profilebox-aboutme');
		$article = new Article($t);
		if ($t->getArticleId() > 0) {
			$article->updateArticle($aboutme, 'profilebox-aboutme-update', true, $watch);
		} elseif ($aboutme) {
			$article->insertNewArticle($aboutme, 'profilebox-aboutme-update', true, $watch, false, false, true);
		}

		$userpageurl = $user->getUserPage() . '';
		$t = Title::newFromText( $userpageurl, NS_USER );
		$article = new Article($t);
		$userpage = " \n";
		if ($t->getArticleId() > 0) {
			/*
			$r = Revision::newFromTitle($t);
			$curtext .= ContentHandler::getContentText( $r->getContent() );

			if (!preg_match('/<!-- blank -->/',$curtext)) {
				$userpage .= $curtext;
				$article->updateArticle($userpage, 'profilebox-userpage-update', true, $watch);
			}
			*/
		} else {
			$article->insertNewArticle($userpage, 'profilebox-userpage-update', true, $watch, false, false, true);
		}

		$user->setOption('profilebox_fav1', $req->getVal('fav1'));
		$user->setOption('profilebox_fav2', $req->getVal('fav2'));
		$user->setOption('profilebox_fav3', $req->getVal('fav3'));

		if ($req->getVal('articleStats') == 'on') {
			$user->setOption('profilebox_stats', 1);
		} else {
			$user->setOption('profilebox_stats', 0);
		}

		if ($req->getVal('articleStartedEdited') == 'on') {
			$user->setOption('profilebox_startedEdited', 1);
		} else {
			$user->setOption('profilebox_startedEdited', 0);
		}

		if ($req->getVal('questionsAnswered') == 'on') {
			$user->setOption('profilebox_questions_answered', 1);
		} else {
			$user->setOption('profilebox_questions_answered', 0);
		}

/*
		if ( ($req->getVal('articleFavs') == 'on') &&
				($req->getVal('fav1') || $req->getVal('fav2') || $req->getVal('fav3')) )
		{
			$user->setOption('profilebox_favs', 1);
		} else {
			$user->setOption('profilebox_favs', 0);
		}
*/

		$user->setOption('profilebox_display', 1);

		$user->saveSettings();

	}

	 // Used in a maintenance script
	 // deleteBannedPages.php
	public static function removeUserData($user) {
		$removed = false;

		$t = Title::newFromText($user->getUserPage() . '/profilebox-live');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$txt = ContentHandler::getContentText( $r->getContent() );
			if ($txt != '') {
				$wikiPage = WikiPage::factory($t);
				$content = ContentHandler::makeContent('', $t);
				$wikiPage->doEditContent($content, 'profilebox-live-empty' );
				$removed = true;
			}
		}

		$t = Title::newFromText($user->getUserPage() . '/profilebox-occupation');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$txt = ContentHandler::getContentText( $r->getContent() );
			if ($txt != '') {
				$wikiPage = WikiPage::factory($t);
				$content = ContentHandler::makeContent('', $t);
				$wikiPage->doEditContent($content, 'profilebox-occupation-empty');
				$removed = true;
			}
		}

		$t = Title::newFromText($user->getUserPage() . '/profilebox-aboutme');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$txt = ContentHandler::getContentText( $r->getContent() );
			if ($txt != '') {
				$wikiPage = WikiPage::factory($t);
				$content = ContentHandler::makeContent('', $t);
				$wikiPage->doEditContent($content, 'profilebox-aboutme-empty');
				$removed = true;
			}
		}

		$user->setOption('profilebox_stats', 0);
		$user->setOption('profilebox_startedEdited', 0);
		$user->setOption('profilebox_questions_answered', 0);
		$user->setOption('profilebox_favs', 0);

		$user->setOption('profilebox_fav1', 0);
		$user->setOption('profilebox_fav2', 0);
		$user->setOption('profilebox_fav3', 0);

		$user->setOption('profilebox_display', 0);
		$user->saveSettings();

		return($removed);
	}

	private function removeData() {
		self::removeUserData($this->getUser());
		return "SUCCESS";
	}

	public static function fetchStats($pagename) {
		global $wgReadOnly;

		$dbr = wfGetDB(DB_REPLICA);
		$t = Title::newFromText($pagename);
		$u = User::newFromName($t->getText());
		if (!$u || $u->getID() == 0) {
			$ret = wfMessage('profilebox_ajax_error')->text();
			return $ret;
		}

		$cachetime = 24 * 60 * 60; // 1 day
		if (RequestContext::getMain()->getUser()->getID() == $u->getID()) {
			$cachetime = 60;
		}

		$updateflag = 0;
		$response = array();
		$row = $dbr->selectRow('profilebox', '*', array('pb_user' => $u->getID()), __METHOD__);
		if ($row) {
			$now = time();
			$last = strtotime($row->pb_lastUpdated . " UTC");
			$diff = $now - $last;

			if (isset($row->pb_lastUpdated) && $diff <= $cachetime) {
				$response['created'] = number_format($row->pb_started, 0, "", ",");
				$response['edited'] = number_format($row->pb_edits, 0, "", ",");
				$response['patrolled'] = number_format($row->pb_patrolled, 0, "", ",");
				$response['viewership'] = number_format($row->pb_viewership, 0, "", ",");
				$response['uid'] = $u->getID();
				$response['contribpage'] = "/Special:Contributions/" . $u->getName();
				if (class_exists('ThumbsUp')) {
					$response['thumbs_given'] = number_format($row->pb_thumbs_given, 0, "", ",");
					$response['thumbs_received'] = number_format($row->pb_thumbs_received, 0, "", ",");
				}

				$updateflag = 0;
			} else {
				$updateflag = 1;
			}
		} else {
			$updateflag = 1;
		}

		if (!$wgReadOnly && $updateflag) {
			$conds = array(
				'fe_page=page_id',
				'fe_user' => $u->getID(),
				"page_title not like 'Youtube%'",
				'page_is_redirect' => 0,
				'page_namespace' => NS_MAIN
			);
			$created = $dbr->selectField(['firstedit','page'], 'count(*)', $conds, __METHOD__);

			$conds = array('log_user' => $u->getID(), 'log_type' => 'patrol');
			$patrolled = $dbr->selectField('logging', 'count(*)', $conds, __METHOD__);

			$edited = WikihowUser::getAuthorStats($u->getName());

			$viewership = 0;
			$vsql = "select sum(page_counter) as viewership from page,firstedit where page_namespace=0 and page_id=fe_page and fe_user=".$u->getID();
			//More accurate but will take longer
			//$vsql = "select sum(distinct(page_counter)) as viewership from page,revision where page_namespace=0 and page_id=rev_page and rev_user=".$u->getID()." GROUP BY rev_page;
			$vres = $dbr->query($vsql, __METHOD__);
			foreach ($vres as $row1) {
				$viewership += $row1->viewership;
			}

			$dbw = wfGetDB(DB_MASTER);

			$set = [
				'pb_started' => $created,
				'pb_edits' => $edited,
				'pb_patrolled' => $patrolled,
				'pb_viewership' => $viewership,
				'pb_lastUpdated' => wfTimestampNow()
			];
			$row = array_merge(['pb_user' => $u->getID()], $set);
			$dbw->upsert('profilebox', $row, [], $set, __METHOD__);

			$response['created'] = number_format($created, 0, "", ",");
			$response['edited'] = number_format($edited, 0, "", ",");
			$response['patrolled'] = number_format($patrolled, 0, "", ",");
			$response['viewership'] = number_format($viewership, 0, "", ",");
			$response['uid'] = $u->getID();
			$response['contribpage'] = "/Special:Contributions/" . $u->getName();
			if (class_exists('ThumbsUp')) {
				$response['thumbs_given'] = number_format($row->pb_thumbs_given, 0, "", ",");
				$response['thumbs_received'] = number_format($row->pb_thumbs_received, 0, "", ",");
			}

		}

		$answered_count = TopAnswerers::countLiveAnswersByUserId($u->getID());
		$response['qa_answered'] = number_format($answered_count, 0, "", ",");

		//check badges
		$badges = ProfileStats::genBadges($dbr, $u);
		$response = array_merge($response, $badges);

		return $response;
	}

	public static function getFeaturedArticles() {
		global $wgMemc;
		$cachekey = wfMemckey('pb-fa-list');
		$fas = $wgMemc->get($cachekey);
		if (is_array($fas)) {
			self::$featuredArticles = $fas;
		}

		if (!self::$featuredArticles) {
			// LIST ALL FEATURED ARTICLES
			$dbr = wfGetDB(DB_REPLICA);

			$res = $dbr->select(
				array('templatelinks', 'page'),
				array('page_title'),
				array('tl_from = page_id', 'tl_title' => 'Fa'),
				__METHOD__);

			foreach ($res as $row) {
				self::$featuredArticles[ $row->page_title ] = 1;
			}
			$wgMemc->set($cachekey, self::$featuredArticles);
		}
		return self::$featuredArticles;
	}

	// Used in WelcomeWagon and CategoryInterests
	public static function fetchEditedData($username, $limit){
		$dbr = wfGetDB(DB_REPLICA);

		$u = User::newFromName(stripslashes($username));

		$order = array();
		$order['ORDER BY'] = 'rev_timestamp DESC';
		$order['GROUP BY'] = 'page_title';
		if ($limit) {
			$order['LIMIT'] = $limit;
		}
		$res = $dbr->select(
			array('revision', 'page'),
			array('page_id', 'page_title', 'page_namespace', 'rev_timestamp', 'page_counter'),
			array('rev_page=page_id', 'rev_user' => $u->getID(), 'page_namespace' => NS_MAIN),
			__METHOD__,
			$order);

		return $res;
	}

	private function fetchFavs($pagename) {
		$t = Title::newFromText($pagename);
		$u = User::newFromName($t->getText());
		if (!$u || $u->getID() == 0) {
			$ret = wfMessage('profilebox_ajax_error')->text();
			return;
		}

		$display = "";

		for ($i = 1; $i <= 3; $i++) {
			$fav = 'profilebox_fav'.$i;
			$page_id = '';
			$page_id = $u->getOption($fav);

			if ($page_id) {
				$t = Title::newFromID($page_id);
				if ($t->getArticleID() > 0)  {
					$display .= "<a href='/".$t->getPartialURL()."'>" . $t->getFullText() . "</a><br />\n";
				}
			}
		}

		return $display;
	}

	private function favsTitleSelector() {
		$dbr = wfGetDB(DB_REPLICA);
		$name = preg_replace('/ /','-', strtoupper($this->getRequest()->getVal('pbTitle')));

		$order = array();
		$order['LIMIT'] = '6';

		$res = $dbr->select(
			array('page'),
			array('page_id','page_title'),
			array("UPPER(page_title) like '%".$name."%'", 'page_namespace' => NS_MAIN),
			__METHOD__,
			$order);
		$display = "<ul>\n";
		foreach ($res as $row) {
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if ($t->getArticleID() > 0)  {
				$display .= "  <li id=".$row->page_id.">" . $t->getFullText() . "</li>\n";
			}
		}
		$display .= "</ul>\n";
		$res->free();

		return $display;
	}

	public function execute($par) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getContext()->getUser();

		$type = $request->getVal('type');
		if (!$user || $user->getID() == 0 && $type != 'ajax') {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$out->setArticleBodyOnly(true);

		if ($type == 'favsselector') {
			$out->setArticleBodyOnly(true);
			print $this->favsTitleSelector();
			return;
		} elseif ($type == 'ajax') {
			$out->setArticleBodyOnly(true);
			$element = $request->getVal('element');
			$dbr = wfGetDB(DB_REPLICA);
			$pagename = $dbr->strencode($request->getVal('pagename'));
			if (($element != '') && ($pagename != '')) {

				$t = Title::newFromText($pagename);
				$pageuser = User::newFromName($t->getText());
				$stats = new ProfileStats($pageuser);

				switch ($element) {
					case 'thumbed_less':
						print json_encode($stats->fetchThumbsData(5));
						break;
					case 'thumbed_more':
						print json_encode($stats->fetchThumbsData(100));
						break;
					case 'stats':
						print self::fetchStats($pagename);
						break;
					case 'created_less':
						print json_encode($stats->fetchCreatedData(5));
						break;
					case 'created_more':
						print json_encode($stats->fetchCreatedData(100));
						break;
					case 'answered_less':
						print json_encode($stats->fetchAnsweredData(5));
						break;
					case 'answered_more':
						print json_encode($stats->fetchAnsweredData(100));
						break;
					case 'favs':
						print $this->fetchFavs($pagename);
						break;
					default:
						wfDebug("ProfileBox ajax requesting  unknown element: $element \n");
						break;
				}
			}
			return;
		}

		if ($request->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$this->pbConfig();

			$t = $user->getUserPage();
			$out->redirect($t->getFullURL());
		} elseif ($type == 'remove') {
			$out->setArticleBodyOnly(true);
			$this->removeData();
			$out->addHTML("SUCCESS");
		} else {
			$out->setArticleBodyOnly(false);
			$this->displayForm();
		}
	}

	// Used in WikihowUserPage
	public static function getPageTop($u, $isMobile = false){
		$realName = User::whoIsReal($u->getId());
		if ($u->getRegistration() != '') {
			$pb_regdate = self::getMemberSince(wfTimestamp(TS_UNIX,$u->getRegistration()));
		} else {
			$pb_regdate = self::getMemberSince(wfTimestamp(TS_UNIX,'20060725043938'));
		}

		$pb_showlive = false;
		$pb_live = '';
		$t = Title::newFromText($u->getUserPage() . '/profilebox-live');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			if ($r) $pb_live = ContentHandler::getContentText( $r->getContent() );
			if ($pb_live) $pb_showlive = true;
		}

		$pb_showwork = false;
		$pb_work = '';
		$t = Title::newFromText($u->getUserPage() . '/profilebox-occupation');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			if ($r) $pb_work = ContentHandler::getContentText( $r->getContent() );
			if ($pb_work) $pb_showwork = true;
		}

		$t = Title::newFromText($u->getUserPage() . '/profilebox-aboutme');
		$pb_aboutme = '';
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			if ($r) {
				$pb_aboutme = ContentHandler::getContentText( $r->getContent() );
				$pb_aboutme = strip_tags($pb_aboutme, '<p><br><b><i>');
				$pb_aboutme = preg_replace('/\\\\r\\\\n/s',"\n",$pb_aboutme);
				$pb_aboutme = stripslashes($pb_aboutme);
			}
		}

		$social = self::getSocialLinks();

		$vars = array(
			'pb_user_name' => $u->getName(),
			'pb_display_name' => htmlspecialchars($realName ? $realName : $u->getName()),
			'pb_display_show' => $u->getOption('profilebox_display'),
			'pb_regdate' => $pb_regdate,
			'pb_showlive' => $pb_showlive,
			'pb_live' => $pb_live,
			'pb_showwork' => $pb_showwork,
			'pb_work' => $pb_work,
			'pb_aboutme' => $pb_aboutme,
			'pb_social' => $social,
			'pb_email_url' => "/" . SpecialPage::getTitleFor('Emailuser')->getPrefixedText() ."?target=" . $u->getName(),
		);

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars($vars);

		$tmpl_file = ($isMobile) ? 'header_mobile.tmpl.php' : 'header.tmpl.php';

		RequestContext::getMain()->getOutput()->addModules('jquery.ui.dialog');

		return $tmpl->execute($tmpl_file);
	}

	private static function getMemberSince($joinDate) {
		if (!$joinDate) {
			return wfMessage('since-unknown')->text();
		}

		$now = time();
		$over = wfMessage('over','')->text();
		$periods = array(wfMessage("day-plural")->text(), wfMessage("week-plural")->text(), wfMessage("month-plural")->text(), wfMessage("year-plural")->text());
		$period = array(wfMessage("day")->text(), wfMessage("week")->text(), wfMessage("month-singular")->text(), wfMessage("year-singular")->text());

		$dt1 = new DateTime("@$joinDate");
		$dt2 = new DateTime("@$now");
		$interval = $dt1->diff($dt2);

		if ($interval->y > 0) {
			return $over . $interval->y .' '. ($interval->y==1?$period[3]:$periods[3]);
		} elseif ($interval->m > 0) {
			return $over . $interval->m .' '. ($interval->m==1?$period[2]:$periods[2]);
		} elseif ($interval->w > 0) {
			return $over . $interval->w .' '. ($interval->w==1?$period[1]:$periods[1]);
		} elseif ($interval->d > 0) {
			return $over . $interval->d .' '. ($interval->d==1?$period[0]:$periods[0]);
		}
		else {
			return wfMessage('sincetoday')->text();
		}
	}

	// Used in ArticleMetaInfo.class.php
	public static function getMetaDesc() {
		$urlUser = RequestContext::getMain()->getTitle()->getText();
		$stats = self::fetchStats('User:'.$urlUser);

		if ($stats && is_array($stats)) {
			$desc = wfMessage('user_meta_description_extended',
					$urlUser,
					$stats['created'],
					$stats['edited'],
					$stats['viewership'],
					$stats['patrolled'])->text();
		}

		return $desc;
	}

	// Used in WikihowUserPage
	public static function getDisplayBadge($data) {
		$isLoggedIn = !RequestContext::getMain()->getUser()->isAnon();
		$display = "";

		$side = 'right';
		// Arabic is right-to-left
		if ( RequestContext::getMain()->getLanguage()->isRTL() ) {
			$side = 'left';
		}
		$distance = 15;

		if ($data['welcome'] == 1) {
			$inner = "<div class='pb-welcome pb-badge' style='{$side}:{$distance}px'></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
			$distance += 75;
		}
		if ($data['nab'] == 1) {
			$inner = "<div class='pb-nab pb-badge' style='{$side}:{$distance}px'></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
			$distance += 75;
		}
		if ($data['admin'] == 1) {
			$inner = "<div class='pb-admin pb-badge' style='{$side}:{$distance}px'></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
			$distance += 75;
		}
		if ($data['fa'] == 1) {
			$inner = "<div class='pb-fa pb-badge' style='{$side}:{$distance}px'></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
			$distance += 75;
		}

		return $display;
	}

	// Used in WikihowUserPage
	public static function getDisplayBadgeMobile($data) {
		$display = '';
		$isLoggedIn = !RequestContext::getMain()->getUser()->isAnon();

		if ($data['nab'] == 1) {
			$inner = "<div class='pb-nab pb-badge'><div><p>".wfMessage('pb-badge-nab')->text()."</p></div></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
		}
		if ($data['admin'] == 1){
			$inner = "<div class='pb-admin pb-badge'><div><p>".wfMessage('pb-badge-admin')->text()."</p></div></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
		}
		if ($data['fa'] == 1){
			$inner = "<div class='pb-fa pb-badge'><div><p>".wfMessage('pb-badge-fa')->text()."</p></div></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
		}
		if ($data['welcome'] == 1) {
			$inner = "<div class='pb-welcome pb-badge'><div><p>".wfMessage('pb-badge-welcome')->text()."</p></div></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
		}

		if ($display) {
			$display = '<div class="section_text userpage_section pb_badge_section">'.
						$display.
						'<div class="clearall"></div></div>';
		}

		return $display;
	}

	// Used in WikihowUserPage
	public static function getFinalStats($contributions, $views) {
		$html = '<div class="minor_section" id="pb_finalstats">'.
				$contributions.$views.
				'</div>';
		return $html;
	}

	// Used in WikihowUserPage
	public static function getPageViews() {
		$a = new Article( RequestContext::getMain()->getTitle() );
		$count = $a ? (int)$a->getCount() : 0;
		$countFormatted = RequestContext::getMain()->getLanguage()->formatNum( $count );
		$s = wfMessage( 'viewcountuser', $countFormatted )->text();
		return $s;
	}

	private static function getSocialLinks() {
		$title = RequestContext::getMain()->getTitle();
		$user = RequestContext::getMain()->getUser();
		$socialLinked = "";
		if ($u = User::newFromName($title->getDBKey())) {
			if (UserPagePolicy::isGoodUserPage($title->getDBKey())) {
				if (class_exists('FBLogin')
					&& class_exists('FBLink')
					&& $u->getID() == $user->getId()
					&& !$user->isGPlusUser()
					&& !$user->isCivicUser()
				) {
					//show or offer FB link
					$socialLinked = !$user->isFacebookUser() ? FBLink::showCTAHtml() : FBLink::showCTAHtml('FBLink_linked');
				} elseif ($u->isGPlusUser()) {
					if ($u->getID() == $user->getId()) {
						// Show link to disconnect Google account
						$loginEnabledMsg = wfMessage('pb-google-login-enabled');
						$unlinkMsg = wfMessage('pb-unlink-google-account');
						$socialLinked = "<div id='gplus_disconnect'>$loginEnabledMsg (<a>$unlinkMsg</a>)</div><div class='clearall'></div>";
					}
				}
			}
		}
		return $socialLinked;
	}
}

class ProfileStats {

	const CACHE_PREFIX = "pb";
	const MIN_RECORDS = 1;
	const MAX_RECORDS = 200;

	var $user;
	var $isOwnPage;

	public function __construct($user) {
		$this->user = $user;
		$this->isOwnPage = RequestContext::getMain()->getUser()->getID() == $this->user->getID();
	}

	public function fetchCreatedData($limit) {
		global $wgMemc;

		$cacheKey = wfMemcKey(ProfileStats::CACHE_PREFIX, 'created', $this->user->getID(), $limit);
		$result = $wgMemc->get($cacheKey);
		if (!$this->isOwnPage && $result) return $result;

		if (empty($limit)) $limit = self::MIN_RECORDS;
		if ($limit > self::MAX_RECORDS) $limit = self::MAX_RECORDS;

		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			['firstedit','page'],
			['page_id', 'page_title', 'page_namespace', 'fe_timestamp', 'page_counter'],
			[
				'fe_page = page_id',
				'fe_user' => $this->user->getID(),
				"page_title not like 'Youtube%'",
				'page_is_redirect' => 0,
				'page_namespace' => NS_MAIN
			],
			__METHOD__,
			[
				'ORDER BY' => 'fe_timestamp DESC',
				'LIMIT' => $limit + 1
			]
		);

		if ($res) {
			$fas = ProfileBox::getFeaturedArticles();
			foreach ($res as $row) {
				$created = get_object_vars($row);
				$created['views'] = number_format($row->page_counter, 0, '',',');
				$created['fa'] = isset($fas[ $row->page_title ]) ? (bool)$fas[ $row->page_title ] : false;

				$title = Title::makeTitle($row->page_namespace, $row->page_title);
				$created['title'] = $title->getText();

				$rs = RisingStar::isRisingStar($title->getArticleID(), $dbr);
				$created['rs'] = (bool)$rs;

				$results[] = $created;
			}
		}

		$wgMemc->set($cacheKey, $results, 60*10);

		return $results;
	}

	public function fetchThumbsData($limit) {
		global $wgMemc;

		$cacheKey = wfMemcKey(ProfileStats::CACHE_PREFIX, 'thumbs', $this->user->getID(), $limit);
		$result = $wgMemc->get($cacheKey);
		if (!$this->isOwnPage && $result) return $result;

		if (empty($limit)) $limit = self::MIN_RECORDS;
		if ($limit > self::MAX_RECORDS) $limit = self::MAX_RECORDS;

		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			['thumbs','page', 'revision'],
			['page_namespace', 'page_id', 'page_title', 'count(thumb_rev_id) as cnt', 'thumb_rev_id', 'rev_timestamp'],
			[
				'thumb_recipient_id' => $this->user->getID(),
				'thumb_exclude' => 0,
				'thumb_page_id=page_id',
				'thumb_rev_id=rev_id'
			],
			__METHOD__,
			[
				'GROUP BY' => 'thumb_rev_id',
				'ORDER BY' => 'rev_id DESC',
				'LIMIT' => $limit + 1
			]
		);

		$user = RequestContext::getMain()->getUser();
		$isLoggedIn = $user && !$user->isAnon();

		$results = [];
		if ($res) {
			foreach ($res as $row) {
				$t = Title::newFromID($row->page_id);
				$page = get_object_vars($row);
				$page['title'] = $t->getText();

				if ($isLoggedIn) {
					$page['ago'] = Linker::linkKnown($t, wfTimeAgo($row->rev_timestamp), [], ['diff' => $row->thumb_rev_id, 'oldid' => 'PREV']);
				}
				else {
					$page['ago'] = wfTimeAgo($row->rev_timestamp);
				}

				$results[] = $page;
			}
			$res->free();
		}

		$wgMemc->set($cacheKey, $results, 60*10);

		return $results;
	}

	public function fetchAnsweredData($limit) {
		global $wgMemc;

		$cacheKey = wfMemcKey(ProfileStats::CACHE_PREFIX, 'answered', $this->user->getID(), $limit);
		$result = $wgMemc->get($cacheKey);
		if (!$this->isOwnPage && $result) return $result;

		if (empty($limit)) $limit = self::MIN_RECORDS;
		if ($limit > self::MAX_RECORDS) $limit = self::MAX_RECORDS;

		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			TopAnswerers::TABLE_ANSWERER_CATEGORIES,
			[
				'qac_category',
				'qac_count'
			],
			['qac_user_id' => $this->user->getID()],
			__METHOD__,
			[
				'ORDER BY' => 'qac_count DESC',
				'LIMIT' => $limit + 1
			]
		);

		$templates = TopAnswerers::badTemplates(true);

		$results = [];
		if ($res) {
			foreach ($res as $row) {
				if (in_array($row->qac_category,$templates)) continue;

				$cat = get_object_vars($row);
				$cat['url'] = '/Category:'.str_replace(' ','-',$row->qac_category);
				$results[] = $cat;
			}
		}

		$wgMemc->set($cacheKey, $results, 60*10);

		return $results;
	}

	// Used in WikihowUserPage
	public function getBadges() {
		//no badges for anons
		if ($this->user->isAnon()) return;

		$dbr = wfGetDB(DB_REPLICA);
		return self::genBadges($dbr, $this->user);
	}

	public static function genBadges($dbr, $user) {
		static $badgeCache = array();

		if (!$user) return array();
		$userID = $user->getID();

		if ( isset($badgeCache[$userID]) ) {
			return $badgeCache[$userID];
		}

		$response = array();

		$groups = $user->getGroups();
		if ( in_array( 'sysop', $groups ) )
			$response['admin'] = 1;
		else
			$response['admin'] = 0;

		$rights = $user->getRights();
		if ( in_array('newarticlepatrol', $rights ) )
			$response['nab'] = 1;
		else
			$response['nab'] = 0;

		$resFA = $dbr->select(
			array('firstedit', 'templatelinks'),
			'*',
			array('fe_page=tl_from',
				'fe_user' => $user->getID(),
				"(tl_title = 'Fa' OR tl_title = 'FA')" ),
			__METHOD__,
			array('GROUP BY' => 'fe_page') );
		$countFA = $resFA->numRows();

		$resRS = $dbr->select(
			array('firstedit', 'pagelist'),
			'*',
			array('fe_page=pl_page',
				'fe_user' => $user->getID() ),
			__METHOD__,
			array('GROUP BY' => 'fe_page') );
		$countRS = $resRS->numRows();

		if ($countFA + $countRS >= 5)
			$response['fa'] = 1;
		else
			$response['fa'] = 0;

		if ( in_array( 'welcome_wagon', $groups ) )
			$response['welcome'] = 1;
		else
			$response['welcome'] = 0;

		$badgeCache[$userID] = $response;
		return $response;
	}

	// Used in WikihowUserPage
	public function getContributions() {
		$user = $this->user;
		$username = $user->getName();
		$real_name = User::whoIsReal($user->getId());
		$real_name = $real_name ? $real_name : $username;
		$real_name = htmlspecialchars($real_name);
		$contribsPage = SpecialPage::getTitleFor( 'Contributions', $username );

		// check if viewing user is logged in
		$isLoggedIn = !RequestContext::getMain()->getUser()->isAnon();

		$userstats = "<div id='userstats'>";
		if ($user && $user->getID() > 0) {
			$editsMade = number_format(WikihowUser::getAuthorStats($username), 0, "", ",");
			if ($isLoggedIn) {
				$userstats .= wfMessage('contributions-made', $real_name, $editsMade, $contribsPage->getFullURL())->text();
			} else {
				$userstats .= wfMessage('contributions-made-anon', $real_name, $editsMade)->text();
			}
		} else { // showing an anon user page
			if ($isLoggedIn) {
				$link = '<a href="' . $contribsPage->getFullURL() . '">' .
					RequestContext::getMain()->getTitle()->getText() . '</a>';
				$userstats .= wfMessage('contributions-link', $link)->text();
			}
		}
		$userstats .= "</div>";

		return $userstats;
	}

}
