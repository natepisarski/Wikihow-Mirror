<?php

class RCPatrol extends SpecialPage {

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'RCPatrol' );
		$wgHooks['OutputPageBeforeHTML'][] = array('RCPatrol::postParserCallback');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	// the way the difference engine works now, you need to pass the oldid in as false
	// to ensure that it will display new articles (and use a hook to preserve it to false).
	// if you pass it 0 for oldid, it
	// will compare the new id to the previous revision
	// this function will clean it up
	public static function cleanOldId($oldId) {
		if ($oldId === 0 || $oldId === '0') {
			$oldId = false;
		}
		return $oldId;
	}

	private static function setActiveWidget() {
		$standings = new RCPatrolStandingsIndividual();
		$standings->addStatsWidget();
		$standings = new QuickEditStandingsIndividual();
		$standings->addStatsWidget();
	}

	private static function setLeaderboard() {
		$standings = new QuickEditStandingsGroup();
		$standings->addStandingsWidget();
	}

	public function execute($par) {
		global $wgReadOnly;

		$this->setHeaders();

		if ( $this->getUser()->isBlocked() ) {
			throw new UserBlockedError( $this->getUser()->getBlock() );
		}

		if ( $wgReadOnly ) {
			$this->getOutput()->prepareErrorPage("RC Patrol Disabled Temporarily");
			$this->getOutput()->addHTML("Site is currently in read-only mode. RCPatrol is unavailable");
			return;
		}

		$userGroups = $this->getUser()->getGroups();
		if ( !$this->getUser()->isAllowed('patrol') ) {
			$this->getOutput()->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($this->getRequest()->getVal('a') == 'rollback') {
			self::doRollback();
			return;
		}

		// Lojjik Braughler
		// Checks if the user has a throttle in a place and if they reached their limit for the day
		// Requires PatrolThrottle extension
		if ( class_exists( 'PatrolUser' ) ) { // you can safely disable this extension by simply commenting it out of imports.php
			$patroller = PatrolUser::newFromUser( $this->getUser() );
			if ( !$patroller->canUseRCPatrol( false ) ) {
				$this->getOutput()->addHTML( PatrolUser::getThrottleMessageHTML() );
				return;
			}
		}
		// End Patrol Throttle feature

		self::setActiveWidget();
		// INTL: Leaderboard is across the user database so we'll just enable for English at the moment
		if ($this->getLanguage()->getCode() == 'en') {
			self::setLeaderboard();
		}

		$out = $this->getOutput();
		$out->addModules('common.mousetrap');
		$out->addModules('ext.wikihow.UsageLogs');
		$out->addModules('jquery.ui.dialog');
		$out->addModules('ext.wikihow.rcpatrol');
		$out->addModules('ext.wikihow.editor_script');

		$out->addHTML(QuickNoteEdit::displayQuickEdit() . QuickNoteEdit::displayQuickNote());
		$out->addHTML(self::getErrorBoxHtml());
		$result = self::getNextArticleToPatrol();
		if ($result) {
			$rcTest = null;
			$testHtml = "";
			if (class_exists('RCTest') && RCTest::isEnabled()) {
				$rcTest = new RCTest();
				$testHtml = $rcTest->getTestHtml();
			}
			$out->addHTML("<div id='rct_results'></div>");
			$out->addHTML("<div id='bodycontents2' class='tool sticky'>");
			$titleText = RCTestStub::getTitleText($result, $rcTest);
			$out->addHTML("<div id='articletitle' style='display:none;'>$titleText</div>");
			$out->addHTML("<div id='rc_header' class='tool_header'>");
			$out->addHtml('<p id="rc_helplink" class="tool_help"><a href="/Patrol-Recent-Changes-on-wikiHow" target="_blank">Learn how</a></p>');
			$out->addHTML('<a href="#" id="rcpatrol_keys">Get Shortcuts</a>');
			$out->addHTML(self::getListOfTemplatesHtml($result['title']));
			// if this was a redirect, the title may have changed so update our context
			$oldTitle = $this->getContext()->getTitle();
			$this->getContext()->setTitle($result['title']);
			$d = RCTestStub::getDifferenceEngine($this->getContext(), $result, $rcTest);
			$d->loadRevisionData();
			$this->getContext()->setTitle($oldTitle);
			$out->addHTML(RCPatrol::getButtons($result, $d->mNewRev, $rcTest));
			$out->addHTML('<div id="rcpatrol_info" style="display:none;">'. wfMessage('rcpatrol_keys')->text() . '</div>');
			$out->addHTML("</div>"); //end too_header
			$d->showDiffPage();
			$out->addHTML($testHtml);
			$out->addHTML("</div>");
		} else {
			$out->addWikiMsg( 'markedaspatrolledtext' );
		}
		$out->setPageTitle("RC Patrol");
	}

	private static function getErrorBoxHtml() {
		$html = <<<EOHTML
	<div style="display:none" class="rcp_err">
		<div style="display:none" class="rcp_err_dump">
		</div>
		<div class="rcp_err_msg">
			RC Patrol encountered an error. You can <a href="#" class="rcp_err_reload">reload RC Patrol</a> or <a href="#" class="rcp_err_show">show any available technical information</a>.
		</div>
	</div>
EOHTML;
		return $html;
	}

	public static function getNextArticleToPatrol($rcid = null) {
		$userName = RequestContext::getMain()->getUser()->getName();
		while ($result = RCPatrolData::getNextArticleToPatrolInner($rcid)) {
			if (!isset($result['title']) || !$result['title']) {
				if (isset($result['rc_cur_id'])) {
					self::skipArticle($result['rc_cur_id']);
				}
			} elseif (isset($result['users'][$userName])) {
				self::skipArticle($result['rc_cur_id']);
			} else {
				break;
			}
		}
		return $result;
	}

	public static function skipArticle($id) {
		global $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
		// skip the article for now
		$cookiename = $wgCookiePrefix . "Rcskip";
		$cookie = $id;
		if (isset($_COOKIE[$cookiename])) {
			$cookie .= "," . $_COOKIE[$cookiename];
		}
		$exp = time() + 2*60*60; // expire after 2 hours
		setcookie( $cookiename, $cookie, $exp, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
		$_COOKIE[$cookiename] = $cookie;
	}

	private static function getMarkAsPatrolledLink($title, $rcid, $hi, $low, $count, $setonload, $new, $old, $vandal) {
		$req = RequestContext::getMain()->getRequest();
		$sns 	= $req->getVal('show_namespace');
		$inv	= $req->getVal('invert');
		$fea	= $req->getVal('featured');
		$rev 	= $req->getVal('reverse');
		$token  = RequestContext::getMain()->getUser()->getEditToken($rcid);
		$articleId = $title->mArticleID;

		$url = "/Special:RCPatrolGuts?target=" . urlencode($title->getFullText())
			. "&action=markpatrolled&rcid={$rcid}"
			. "&invert=$inv&reverse=$rev&featured=$fea&show_namespace=$sns"
			. "&rchi={$hi}&rclow={$low}&new={$new}&old={$old}&vandal={$vandal}&token=" . urlencode($token);

		$class1 = "class='button primary' style='float: right;' ";
		$class2 = "class='button secondary' style='float: left;' ";
		$link =  " <input type='button' $class2 id='skippatrolurl' onclick=\"return WH.RCPatrol.skip();\" title='" . wfMessage('rcpatrol_skip_title') .
			"' value='" . wfMessage('rcpatrol_skip_button') . "' data-event_action='skip' data-article_id='$articleId' data-assoc_id='$rcid'/>";
		$link .=  "<input type='button' $class1 id='markpatrolurl' class='op-action' onclick=\"return WH.RCPatrol.markPatrolled();\" title='" . wfMessage('rcpatrol_patrolled_title') .
			"' value='" . wfMessage('rcpatrol_patrolled_button') . "' data-event_action='mark_patrolled' data-article_id='$articleId' data-assoc_id='$rcid'/>";
		if ($setonload) {
			$link .= "<script type='text/javascript'>marklink = '$url';
				skiplink = '$url&skip=1';
				$(document).ready(function() {
					WH.RCPatrol.setupTabs();
					WH.RCPatrol.preloadNext('$url&grabnext=true');
				});
				</script>";

		}
		# this is kind of dumb, but it works
		$link .= "<div id='newlinkpatrol' style='display:none;'>$url</div><div id='newlinkskip' style='display:none;'>$url&skip=1</div>"
			 . "<div id='skiptitle' style='display:none;'>" . urlencode($title->getDBKey()) . "</div>"
			 . "<input id='permalink' type='hidden' value='" . str_replace("&action=markpatrolled", "&action=permalink", $url)  . "'/>";
		return $link;
	}

	private static function doRollback() {
		global $wgContLang;

		$req = RequestContext::getMain()->getRequest();
		if ( RequestContext::getMain()->getUser()->isBlocked() ) {
			return false;
		}

		RequestContext::getMain()->getOutput()->setArticleBodyOnly(true);
		$response = "";

		$aid = $req->getInt('aid');
		$oldid = $req->getInt('old');
		$from = $req->getVal('from');
		$from = preg_replace( '/[_-]/', ' ', $from );

		$t = Title::newFromId($aid);
		if ($t && $t->exists()) {
			$r = Revision::newFromId($oldid);
			if ($r) {

				if ( $from == '' ) { // no public user name
					$summary = wfMessage( 'rcp-revertpage-nouser' );
				} else {
					$summary = wfMessage( 'rcp-revertpage' );
				}

				// Allow the custom summary to use the same args as the default message
				$args = array( $r->getUserText(), $from, $oldid);
				if ( $summary instanceof Message ) {
					$summary = $summary->params( $args )->inContentLanguage()->text();
				} else {
					$summary = wfMessage( $summary )->params( $args );
				}

				// Trim spaces on user supplied text
				$summary = trim( $summary );

				// Truncate for whole multibyte characters.
				$summary = $wgContLang->truncateForDatabase( $summary, 255 );

				$wikiPage = WikiPage::factory($t);
				$newRev = Revision::newFromTitle( $t );
				$old = Linker::revUserTools( Revision::newFromId( $oldid ) );
				$new = Linker::revUserTools( $newRev );
 				$revision = 'r' . htmlspecialchars($wgContLang->formatNum( $oldid, true ));
            	$revlink = Linker::link( $t, $revision, array(), array('oldid' => $oldid, 'diff' => 'prev') );
				$response = WfMessage( 'rcp-rollback-success' )->rawParams( $new, $old, $revlink );

				$aTitle = $wikiPage->getTitle()->getPrefixedText();
				$rTitle = $r->getTitle()->getPrefixedText();

				if ( $aTitle != $rTitle ) {
						wfDebugLog( 'rcpatrol', "Error: Article/revision mismatch - Article title: $aTitle (id $aid) | Revision title: $rTitle (r$oldid)\n"  );
						return;
				}

				$status = $wikiPage->doEditContent($r->getContent(), $summary);

				if ( !$status->isOK() ) {
					$response = $status->getErrorsArray();
				}

				// raise error, when the edit is an edit without a new version
				if ( empty( $status->value['revision'] ) ) {
					$resultDetails = array( 'current' => $current );
			 		$query = array( 'oldid' => $oldid, 'diff' => 'prev');

					$response = WfMessage( 'rcp-alreadyrolled')->params(array(
							htmlspecialchars( $t->getPrefixedText() ),
							htmlspecialchars( $from  ),
							htmlspecialchars( $newRev->getUserText() )
					))->inContentLanguage()->parse();
				}
			}
		}

		RequestContext::getMain()->getOutput()->addHtml($response);
	}


	private static function generateRollbackUrl($rev, $oldid = 0, &$rcTest) {
		global $wgServer;
		$t = $rev->getTitle();
		return  $wgServer . "/index.php?title=Special:RCPatrol&aid={$t->getArticleId()}&a=rollback&old={$oldid}&from=" . urlencode( $rev->getUserText() );
	}

	private static function generateRollback($rev, $oldid = 0, &$rcTest) {
		if (!$rev) return '';

		//first rev?
		if ($oldid == 0 || $oldid == '0' || !$oldid) {
			if ($rcTest && $rcTest->isTestTime()) {
				// wait, this is a test? nevermind then...
			}
			else {
				return '';
			}
		}

		$class = "class='button secondary' style='float: right;'";

		// Genrate an RC Patrol rollback url. Different than the normal mediawiki rollback
		// to handle multiple intermediate revisions (if they exist)
		$url =  self::generateRollbackUrl($rev, $oldid, $rcTest);

		$rcid = $rev->getId();
		$articleId = $rev->getParentId();

		$s = "
			<script type='text/javascript'>
				WH.RCPatrol.setRollbackURL(\"{$url}\");
				var msg_rollback_complete = \"" . htmlspecialchars(wfMessage('rollback_complete')) . "\";
				var msg_rollback_fail = \"" . htmlspecialchars(wfMessage('rollback_fail')) . "\";
				var msg_rollback_inprogress = \"" . htmlspecialchars(wfMessage('rollback_inprogress')) . "\";
				var msg_rollback_confirm= \"" . htmlspecialchars(wfMessage('rollback_confirm')) . "\";
			</script>
				<a id='rb_button' $class href='' onclick='return WH.RCPatrol.rollback();' title='" . wfMessage('rcpatrol_rollback_title') . "' data-event_action='rollback' data-article_id='$articleId' data-assoc_id='$rcid'>" . wfMessage('rcpatrol_rollback_button') . "</a>
			</span>";
		$s .= "<div id='newrollbackurl' style='display:none;'>{$url}</div>";
		return $s;

	}

	private static function getQuickEdit($title, $result) {
		// build the array of users for the quick note link sorted by
		// the # of bytes changed descending, i.e. more is better
		$users = array();
		$sorted = $result['users_len'];
		if (!$sorted)
			return;
		asort($sorted, SORT_NUMERIC);
		$sorted = array_reverse($sorted);
		foreach ($sorted as $s=>$len) {
			$u = User::newFromName($s);
			if (!$u) {
				// handle anons
				$u = new User();
				$u->setName($s);
			}
			$users[] = $u;
		}

		$rcid = $result['new'];
		$articleId = $result['title']->mArticleID;

		$editURL = Title::makeTitle(NS_SPECIAL, "QuickEdit")->getFullURL() . '?type=editform&target=' . urlencode($title->getFullText());
		$class = "class='button secondary' style='float: left;'";
		$link = "<script type='text/javascript'>var gQuickEditUrl = \"{$editURL}\";</script>";
		$link .=  "<a id='qe_button' title='" . wfMessage("rcpatrol_quick_edit_title") . "' href='' $class onclick=\"return initPopupEdit(gQuickEditUrl) ;\" data-event_action='quick_edit' data-article_id='$articleId' data-assoc_id='$rcid'>" .
			htmlspecialchars( wfMessage( 'rcpatrol_quick_edit_button' ) ) . "</a> ";

		$qn = str_replace("href", " title='" . wfMessage("rcpatrol_quick_note_title") . "' $class href", QuickNoteEdit::getQuickNoteLinkMultiple($title, $users));
		$link = $qn . $link;
		return $link;
	}

	static function getButtons($result, $rev, $rcTest = null) {
		$t = $result['title'];
		$s = "<table cellspacing='0' cellpadding='0' style='width:100%;'><tr><td style='vertical-align: middle; xborder: 1px solid #999;' class='rc_header'>";
		$u = new User();
		$u->setName($result['user']);
		$s .= "<a id='gb_button' href='' onclick='return WH.RCPatrol.goback();' title='" . wfMessage('rcpatrol_go_back_title') . "' class='button button_arrow secondary'></a>";
		$s .= self::getQuickEdit($t, $result);
		$s .= RCTestStub::getThumbsUpButton($result, $rcTest);
		$s .= self::getMarkAsPatrolledLink($result['title'], $result['rcid'], $result['rchi'], $result['rclo'], $result['count'], true, $result['new'], $result['old'], $result['vandal']);
		$s .= self::generateRollback($rev, $result['old'], $rcTest);
		$s .= "</td></tr></table>";
		$s .= "<div id='rc_subtabs'>
			<div id='rctab_advanced'>
				<a href='#'>" . wfMessage('rcpatrol_advanced_tab') . "</a>
			</div>
			<div id='rctab_ordering'>
				<a href='#'>" . wfMessage('rcpatrol_ordering_tab') . "</a>
			</div>
			<div id='rctab_user'>
				<a href='#'>" . wfMessage('rcpatrol_user_tab') . "</a>
			</div>
			<div id='rctab_help'>
				<a href='#'>" . wfMessage('rcpatrol_help_tab') . "</a>
			</div>
			<div style='float:none'></div>
		</div>";
		$s .= "<table style='clear:both;'>";
		$s .= self::getAdvancedTab($t, $result);
		$s .= self::getOrderingTab();
		$s .= self::getUserTab();
		$s .= "</table>";
		$s .= self::getHelpTab();
		$s .= "<div id='rollback-status' style='background-color: #FFFF00;'></div>";
		$s .= "<div id='thumbsup-status' style='background-color: #FFA;display:none;padding:2px;'></div>";
		$s .= "<div id='numrcusers' style='display:none;'>" . sizeof($result['users']) . "</div>";
		$s .= "<div id='numedits' style='display:none;'>". sizeof($result['count']) . "</div>";
		$s .= "<div id='quickedit_response_wrapper'></div>";
		return $s;
	}

	private static function getAdvancedTab($t, $result) {
		$tab = "<tr class='rc_submenu' id='rc_advanced'><td>";
		$tab .= "<a href='{$t->getFullURL()}?action=history' target='new'>" . wfMessage('rcpatrol_page_history') . "</a> -";
		if ($result['old'] > 0) {
			$tab .= " <a href='{$t->getFullURL()}?oldid={$result['old']}&diff={$result['new']}' target='new'>" . wfMessage('rcpatrol_view_diff') . "</a> -";
		}
		$tab .= " <a href='{$t->getTalkPage()->getFullURL()}' target='new'>" . wfMessage('rcpatrol_discuss') . "</a>";
		if ($t->userCan('move')) {
			$tab .= " - <a href='{$t->getFullURL()}?action=delete' target='new'>" . wfMessage('rcpatrol_delete') . "</a> -";
			$mp = SpecialPage::getTitleFor("Movepage", $t);
			$tab .= " <a href='{$mp->getFullURL()}' target='new'>" . wfMessage('rcpatrol_rename') . "</a> ";
		}

		$tab .= "</td></tr>";
		return $tab;
	}

	private static function getOrderingTab() {
		$reverse = RequestContext::getMain()->getRequest()->getVal('reverse', 0);
		$tab = "<tr class='rc_submenu' id='rc_ordering'><td>
			<div id='controls' style='text-align:center'>
			<input type='radio' id='reverse_newest' name='reverse' value='0' " . (!$reverse? "checked" : "") . " style='height: 10px;' onchange='WH.RCPatrol.changeReverse();'> <label for='reverse_newest'>" . wfMessage('rcpatrol_newest_oldest') . "</label>
			<input type='radio' id='reverse_oldest' name='reverse' value='1' id='reverse' " . ($reverse? "checked" : "") . " style='height: 10px; margin-left:10px;' onchange='WH.RCPatrol.changeReverse();'> <label for='reverse_oldest'>" .  wfMessage('rcpatrol_oldest_newest') . "</label>
			&nbsp; &nbsp; - &nbsp; &nbsp; " . wfMessage('rcpatrol_namespace') . ": " .  Html::namespaceselector(array($namespace)) . "
			</div></td></tr>";
		return $tab;
	}

	private static function getUserTab() {
		$tab = "<tr class='rc_submenu' id='rc_user'><td>
			<div id='controls' style='text-align:center'>
				" . wfMessage('rcpatrol_username') . ": <input type='text' name='rc_user_filter' id='rc_user_filter' size='30' onchange='WH.RCPatrol.changeUserFilter();'/> <script> $('#rc_user_filter').keypress(function(e) { if (e.which == 13) { $('#rc_user_filter_go').click(); return false; } }); </script>
				<input type='button' id='rc_user_filter_go' value='" . wfMessage('rcpatrol_go') . "' onclick='WH.RCPatrol.changeUser(true);'/>
				-
				<a href='#' onclick='WH.RCPatrol.changeUser(false);'>" . wfMessage('rcpatrol_off') . "</a>
			</div></td></tr>";
		return $tab;
	}

	private static function getHelpTab() {
		if (RequestContext::getMain()->getLanguage()->getCode() == 'en') {
			$helpTop = wfMessage('rcpatrolhelp_top');
		} else {
			$helpTop = wfMessage('rcpatrolhelp_top')->parseAsBlock();
		}

		$tab = "<div id='rc_help'>" . $helpTop . wfMessage('rcpatrolhelp_bottom') . "</div>";
		return $tab;
	}

	static function postParserCallback($outputPage, &$html) {
		//$html = WikihowArticleHTML::processArticleHTML($html, array('no-ads' => true));
		return true;
	}

	public static function getNextURLtoPatrol($rcid) {
		$req = RequestContext::getMain()->getRequest();
		$username = RequestContext::getMain()->getUser()->getName();
		$show_namespace = $req->getVal('show_namespace', null);
		if ($show_namespace === null) $show_namespace = $req->getVal('namespace', null);
		$invert = $req->getInt('invert');
		$reverse = $req->getInt('reverse');
		$featured = $req->getInt('featured');
		$associated = $req->getInt('associated');
		$fromrc = $req->getVal('fromrc') ? 'fromrc=1' : '';

		//TODO: shorten this to a selectRow call
		$dbw = wfGetDB( DB_MASTER );
		$sql = "SELECT rc_id, rc_cur_id, rc_new, rc_namespace, rc_title, rc_last_oldid, rc_this_oldid FROM recentchanges " .
			($featured ? " LEFT OUTER JOIN page on page_title = rc_title and page_namespace = rc_namespace " : "") .
			" WHERE rc_id " . ($reverse == 1 ? " > " : " < ")  . " $rcid and rc_patrolled = 0  " .
			($featured ? " AND page_is_featured = 1 " : "")
			. " AND rc_user_text != " . $dbw->addQuotes($username) . " ";

		if ($show_namespace != null && $show_namespace != '') {
			$sql .= " AND rc_namespace " . ($invert ? '!=' : '=') . (int) $show_namespace;
		} else  {
			// avoid the delete logs, etc
			$sql .= " AND rc_namespace NOT IN ( " . NS_VIDEO . ", " . NS_MEDIAWIKI . ") ";
		}
		$sql .= " ORDER by rc_id " . ($reverse == 1 ? " ASC " : " DESC ") . " LIMIT 1";
//error_log("$sql\n", 3, '/tmp/qs.txt');
		$res = $dbw->query($sql, __METHOD__);
		if ( $row = $dbw->fetchObject( $res ) ) {
			$xx = Title::makeTitle($row->rc_namespace, $row->rc_title);
			//we got one, right?
			if (!$xx || !RCPatrolData::userCanEdit($xx)) {
				return null;
			}

			$url = $xx->getFullURL() . "?rcid=" . $row->rc_id;
			if ($xx->isRedirect() || $row->rc_new == 1) {
				$url .= '&redirect=no';
			}
			if ($row->rc_new != 1) {
				$url .= "&curid=" . $row->rc_cur_id . "&diff="
					. $row->rc_this_oldid . "&oldid=" . $row->rc_last_oldid;
			}
			$url .= "&namespace=$show_namespace&invert=$invert&reverse=$reverse&associated=$associated&$fromrc";
		}
		return $url;
	}

	private static function skipPatrolled($article) {
		global $wgCookieExpiration, $wgCookiePath, $wgCookieDomain, $wgCookieSecure, $wgCookiePrefix;

		$req = RequestContext::getMain()->getRequest();
		$hi = $req->getInt( 'rchi', null );
		$lo = $req->getInt( 'rclow', null );
		$rcid = $req->getInt( 'rcid' );

		$dbr = wfGetDB(DB_REPLICA);
		$pageid = $dbr->selectField('recentchanges', 'rc_cur_id', array('rc_id=' . $rcid));
		if ($pageid && $pageid != '')
			$featured = $dbr->selectField('page', 'page_is_featured', array("page_id={$pageid}") );
		if ($featured) {
			// get all of the rcids to ignore
			$ids = array();
			if ($hi != null) {
				$res = $dbr->select('recentchanges', 'rc_id', array("rc_id>={$lo}", "rc_id<={$hi}", "rc_cur_id=$pageid"));
				foreach ($res as $row) {
					$ids[] = $row->rc_id;
				}
			} else {
				$ids[] = $rcid;
			}
			$cookiename = "WsSkip_" . wfTimestamp();
			$cookie = implode($ids, ",");
			$_SESSION[$cookiename] = $article->mToken;
			$exp = time() + 5*60*60;
			setcookie( $wgCookiePrefix.$cookiename, $cookie, $exp, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
		}
	}

	public static function getListOfTemplatesHtml(Title $title): string {
		$templates = TitleUtil::getTemplates($title);
		if (!$templates)
			return "<div class='rcp_template_list'>No templates found.</div>";

		$templates = array_map('htmlspecialchars', $templates);
		$templates = implode(', ', $templates);
		return "<div class='rcp_template_list'><b>Templates:</b> $templates</div>";
	}

}

class RCPatrolData {
	public static function getListofEditors($result) {
		$dbr = wfGetDB(DB_REPLICA);
		$users = array();
		$users_len = array();
		$res = $dbr->select('recentchanges',
			array('rc_user', 'rc_user_text', 'rc_new_len', 'rc_old_len'),
			array("rc_id <= " . $result['rchi'],
				"rc_id >= " . $result['rclo'],
				"rc_cur_id" => $result['rc_cur_id']));
		while ($row = $dbr->fetchObject($res)) {
			$u = array();
			if (isset($users[$row->rc_user_text])) {
				$u = $users[$row->rc_user_text];
				$u['edits']++;
				$u['len'] += $row->rc_new_len - $row->rc_old_len;
				$users[$row->rc_user_text] = $u;
				$users_len[$row->rc_user_text] = $u['len'];
				continue;
			}
			$u['id'] = $row->rc_user;
			$u['user_text'] = $row->rc_user_text;
			$u['edits']++;
			$u['len'] = $row->rc_new_len - $row->rc_old_len;
			$users_len[$row->rc_user_text] = $u['len'];
			$users[$row->rc_user_text] = $u;
		}
		$result['users'] = $users;
		$result['users_len'] = $users_len;
		return $result;
	}

	public static function getNextArticleToPatrolInner($rcid = null) {
		global $wgCookiePrefix;

		$req = RequestContext::getMain()->getRequest();
		$show_namespace		= $req->getVal('namespace');
		$invert				= $req->getVal('invert');
		$reverse			= $req->getVal('reverse');
		$featured			= $req->getVal('featured');
		$title				= $req->getVal('target');
		$skiptitle			= $req->getVal('skiptitle');
		$rc_user_filter		= trim(urldecode($req->getVal('rc_user_filter')));

		// assert that current user is not anon
		if (RequestContext::getMain()->getUser()->isAnon()) return null;

		// In English, when a user rolls back an edit, it gives the edit a comment
		// like: "Reverted edits by ...", so MediaWiki:rollback_comment_prefix
		// is set to "Reverted" in English wikiHow.
		$rollbackCommentPrefix = wfMessage('rollback_comment_prefix')->plain();

		if (empty($rollbackCommentPrefix) || strpos($rollbackCommentPrefix, '&') === 0) {
			die("Cannot use RCPatrol feature until MediaWiki:rollback_comment_prefix is set up properly");
		}

		$t = null;
		if ($title)
			$t = Title::newFromText($title);
		$skip = null;
		if ($skiptitle)
			$skip = Title::newFromText($skiptitle);

		$dbw = wfGetDB(DB_MASTER);
		/*	DEPRECATED rc_moved_to_ns & rc_moved_to_title columns
			$sql = "SELECT rc_id, rc_cur_id, rc_moved_to_ns, rc_moved_to_title, rc_new,
			  rc_namespace, rc_title, rc_last_oldid, rc_this_oldid
			FROM recentchanges
			LEFT OUTER JOIN page ON rc_cur_id = page_id AND rc_namespace = page_namespace
			WHERE ";*/
		$sql = "SELECT rc_id, rc_cur_id, rc_new, rc_namespace, rc_title, rc_last_oldid, rc_this_oldid
			FROM recentchanges
			LEFT OUTER JOIN page ON rc_cur_id = page_id AND rc_namespace = page_namespace
			WHERE ";

		if (!$req->getVal('ignore_rcid') && $rcid)
			$sql .= " rc_id " . ($reverse == 1 ? " > " : " < ")  . " $rcid and ";

		// if we filter by user we show both patrolled and non-patrolled edits
		if ($rc_user_filter) {
			$sql .= " rc_user_text = " . $dbw->addQuotes($rc_user_filter);
			if ($rcid)
				$sql .= " AND rc_id < " . $rcid;
		} else  {
			$sql .= " rc_patrolled = 0 ";
		}

		// can't patrol your own edits
		$sql .= " AND rc_user <> " . RequestContext::getMain()->getUser()->getID();

		// only featured?
		if ($featured)
			$sql .= " AND page_is_featured = 1 ";

		if ($show_namespace)  {
			$sql .= " AND rc_namespace " . ($invert ? '<>' : '=') . (int) $show_namespace;
		} else  {
			// always ignore video and Mediawiki
			$sql .= " AND rc_namespace <> " . NS_VIDEO;
			$sql .= " AND rc_namespace <> " . NS_MEDIAWIKI;
		}

		// log entries have namespace = -1, we don't want to show those, hide bots too
		$sql .= " AND rc_namespace >= 0 AND rc_bot = 0 ";

		//parse out Votebot (which isn't logging as a bot)
		$votebot = User::newFromName('Votebot');
		if ($votebot) {
			$sql .= " AND rc_user <> ".$votebot->getId()." ";
		}

		if ($t) {
			$sql .= " AND rc_title <> " . $dbw->addQuotes($t->getDBKey());
		}
		if ($skip) {
			$sql .= " AND rc_title <> " . $dbw->addQuotes($skip->getDBKey());
		}

		$sa = $req->getVal('sa');
		if ($sa) {
			$sa = Title::newFromText($sa);
			$sql .= " AND rc_title = " . $dbw->addQuotes($sa->getDBKey());
		}

		// has the user skipped any articles?
		$cookiename = $wgCookiePrefix."Rcskip";
		$skipids = "";
		if (isset($_COOKIE[$cookiename])) {
			$cookie_ids = array_unique(explode(",", $_COOKIE[$cookiename]));
			$ids = array(); //safety first
			foreach ($cookie_ids as $id) {
				$id = intval($id);
				if ($id > 0) $ids[] = $id;
			}
			if ($ids) {
				$skipids = " AND rc_cur_id NOT IN (" . implode(",", $ids) . ") ";
			}
		}
		$sql .= "$skipids ORDER BY rc_timestamp " . ($reverse == 1 ? "" : "DESC ") . "LIMIT 1";

		$res = $dbw->query($sql, __METHOD__);
		$row = $res->fetchObject();

		if ($row) {
			$result = array();
			$t = Title::makeTitle($row->rc_namespace, $row->rc_title);
			if ($t->isRedirect()) {
				$wp = new WikiPage($t);
				$t = $wp->getRedirectTarget();
			}

			// if title has been deleted set $t to null so we will skip it
			if (!$t->exists()) {
				MWDebug::log("$t does not exist");
				$t = null;
			}

			if (!self::userCanEdit($t)) {
				$t = null;
			}

			$result['rc_cur_id'] = $row->rc_cur_id;

			if ($rc_user_filter) {
				$result['rchi'] = $result['rclo'] = $row->rc_id;
				$result['new']		= $dbw->selectField('recentchanges', array('rc_this_oldid'), array('rc_id' => $row->rc_id), __METHOD__);
			} else {
				// always compare to current version
				$result['new']		= $dbw->selectField('revision', array('max(rev_id)'), array('rev_page' => $row->rc_cur_id), __METHOD__);
				$result['rchi']		= $dbw->selectField('recentchanges', array('rc_id'), array('rc_this_oldid' => $result['new']), __METHOD__);
				$result['rclo']		= $dbw->selectField('recentchanges', array('min(rc_id)'), array('rc_patrolled'=>0,"rc_cur_id"=>$row->rc_cur_id), __METHOD__);

				// do we have a reverted edit caught between these 2?
				// if so, only show the reversion, because otherwise you get the reversion trapped in the middle
				// and it shows a weird diff page.
				$hi = isset($result['rchi']) ? $result['rchi'] : $row->rc_id;

				if ($hi) {
					$reverted_id = $dbw->selectField('recentchanges',
						array('min(rc_id)'),
						array('rc_comment like ' . $dbw->addQuotes($rollbackCommentPrefix . '%'),
							"rc_id < $hi" ,
							"rc_id >= {$result['rclo']}",
							"rc_cur_id"=>$row->rc_cur_id),
						__METHOD__);
					if ($reverted_id) {
						$result['rchi'] = $reverted_id;
						$result['new'] = $dbw->selectField('recentchanges',
							array('rc_this_oldid'),
							array('rc_id' => $reverted_id),
							__METHOD__);
						$row->rc_id = $result['rchi'];
					}
				//} else {
				//	$email = new MailAddress("alerts@wikihow.com");
				//	$subject = "Could not find hi variable " . date("r");
				//	$body = print_r($_SERVER, true) . "\n\n" . $sql . "\n\n" . print_r($result, true) . "\n\n\$hi: " . $hi;
				//	UserMailer::send($email, $email, $subject, $body);
				}

				if (!$result['rclo']) $result['rclo'] = $row->rc_id;
				if (!$result['rchi']) $result['rchi'] = $row->rc_id;

				// is the last patrolled edit a rollback? if so, show the diff starting at that edit
				// makes it more clear when someone has reverted vandalism
				$result['vandal'] = 0;
				$comm = $dbw->selectField('recentchanges', array('rc_comment'), array('rc_id'=>$result['rclo']), __METHOD__);
				if (strpos($comm, $rollbackCommentPrefix) === 0) {
					$row2 = $dbw->selectRow('recentchanges', array('rc_id', 'rc_comment'),
						array("rc_id < {$result['rclo']}", 'rc_cur_id' => $row->rc_cur_id),
						__METHOD__,
						array("ORDER BY" => "rc_id desc", "LIMIT"=>1));
					if ($row2) {
						$result['rclo'] = $row2->rc_id;
					}
					$result['vandal'] = 1;
				}
			}
			$result['user']		= $dbw->selectField('recentchanges', array('rc_user_text'), array('rc_this_oldid' => $result['new']), __METHOD__);
			$result['old']      = $dbw->selectField('recentchanges', array('rc_last_oldid'), array('rc_id' => $result['rclo']), __METHOD__);
			$result['title']	= $t;
			$result['rcid']		= $row->rc_id;
			if ($result['rchi'] == $result['rclo']) {
				$conds = array('rc_id' => $result['rchi']);
			} else {
				$conds = array(
					'rc_id <= ' . $result['rchi'],
					'rc_id >= ' . $result['rclo']);
			}
			$result['count'] = $dbw->selectField('recentchanges',
				array('count(*)'),
				array("rc_id <= " . $result['rchi'],
					"rc_id >= " . $result['rclo'],
					"rc_patrolled" => 0,
					"rc_cur_id" => $row->rc_cur_id),
				__METHOD__);
			$result = self::getListofEditors($result);
			return $result;
		} else {
			return null;
		}
	}

	public static function userCanEdit($article) {
		if (!$article || !is_object($article)) {
			return false;
		}
		return !$article->isProtected()
			|| ($article->getRestrictions('edit')[0] != 'sysop')
			|| in_array('sysop', RequestContext::getMain()->getUser()->getGroups());
	}
}

class RCPatrolGuts extends UnlistedSpecialPage {
	function __construct() {
		global $wgHooks;
		parent::__construct('RCPatrolGuts');
		$wgHooks['OutputPageBeforeHTML'][] = array('RCPatrol::postParserCallback');

		// Reuben 1/26/2014: we were seeing JSON output broken in RCP by things
		// like trim() warnings, and this was easier to fix in short term. In long
		// term, RCP's javascript should deal with errors or connection problems
		// in the JSON responses!
		ini_set('display_errors', 0);
	}

	static function getUnpatrolledCount() {
		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField('recentchanges', array('count(*)'), array('rc_patrolled'=>0));
		$count = number_format($count, 0, ".", ",");
		$count .= wfMessage('rcpatrol_helplink')->text();
		return $count;
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$t = Title::newFromText($req->getVal('target'));

		$out->setArticleBodyOnly(true);
		if ($req->getVal('action') == 'permalink') {
			$result = array();
			$result['title'] = $t;
			$result['rchi'] = $req->getVal('rchi');
			$result['rclo'] = $req->getVal('rclow');
			$result['rcid'] = $req->getVal('rcid');
			$result['old'] = $req->getVal('old');
			$result['new'] = $req->getVal('new');
			$result['vandal'] = $req->getVal('vandal');
			$result['rc_cur_id'] = $t->getArticleID();
			$result = RCPatrolData::getListofEditors($result);
			$out->addHTML("<div id='articletitle' style='display:none;'><a href='{$t->getLocalURL()}'>{$t->getFullText()}</a></div>");
			$oldTitle = $this->getContext()->getTitle();
			$this->getContext()->setTitle($result['title']);
			$d = new DifferenceEngine($this->getContext(), RCPatrol::cleanOldId($req->getVal('old')), $req->getVal('new'), $req->getVal('rcid'));
			$d->loadRevisionData();
			$this->getContext()->setTitle($oldTitle);
			$out->addHTML("<div id='rc_header' class='tool_header'>");
			$out->addHTML('<a href="#" id="rcpatrol_keys">Get Shortcuts</a>');
			$out->addHTML(RCPatrol::getListOfTemplatesHtml($t));
			$out->addHTML(RCPatrol::getButtons($result, $d->mNewRev));
			$out->addHTML("</div>");
			$out->addHTML('<div id="rcpatrol_info" style="display:none;">'. wfMessage('rcpatrol_keys')->text() . '</div>');
			$d->showDiffPage();
			$out->disable();
			$response['html'] = $out->getHTML();
			print json_encode($response);
			return;
		}
		$a = new Article($t);
		if (!$req->getVal('grabnext')) {
			if (class_exists('RCTest') && RCTest::isEnabled() && $req->getVal('rctest')) {
				// Don't do anything if it's a test
			} elseif (!$req->getVal('skip') && $req->getVal('action') == 'markpatrolled') {
				$this->markRevisionsPatrolled($a);
			} elseif ($req->getVal('skip')) {
				// skip the article for now
				RCPatrol::skipArticle($t->getArticleID());
			}
		}

		// Reuben note: should we be clearing the existing html here, or is
		// there a better way?
		$out->clearHTML();
		$out->redirect('');
		$result = RCPatrol::getNextArticleToPatrol($req->getVal('rcid'));
		$response = array();
		if ($result) {
			$rcTest = null;
			$testHtml = "";
			if (class_exists('RCTest') && RCTest::isEnabled()) {
				$rcTest = new RCTest();
				$testHtml = $rcTest->getTestHtml();
				/* Uncomment to debug rctest
				$response['testtime'] = $rcTest->isTestTime() ? 1 : 0;
				$response['totpatrol'] = $rcTest->getTotalPatrols();
				$response['adjpatrol'] = $rcTest->getAdjustedPatrolCount();
				global $wgCookiePrefix;
				$response['testcookie'] = $_COOKIE[$wgCookiePrefix . '_rct_a'];
				*/
			}
			$t = $result['title'];
			$out->addHTML("<div id='bodycontents2'>");
			$titleText = RCTestStub::getTitleText($result, $rcTest);
			$out->addHTML("<div id='articletitle' style='display:none;'>$titleText</div>");

			// Initialize the RCTest object. This is use to inject
			// tests into the RC Patrol queue.

			$d = RCTestStub::getDifferenceEngine($this->getContext(), $result, $rcTest);
			$d->loadRevisionData();
			$out->addHTML("<div id='rc_header' class='tool_header'>");
			$out->addHTML('<a href="#" id="rcpatrol_keys">Get Shortcuts</a>');
			$out->addHTML(RCPatrol::getListOfTemplatesHtml($t));
			$out->addHTML(RCPatrol::getButtons($result, $d->mNewRev, $rcTest));
			$out->addHTML("</div>");
			$out->addHTML('<div id="rcpatrol_info" style="display:none;">'. wfMessage('rcpatrol_keys')->text() . '</div>');
			$d->showDiffPage();
			$out->addHtml($testHtml);

			$out->addHTML("</div>");
			$response['unpatrolled'] = self::getUnpatrolledCount();
		} else {
			$out->addWikiMsg( 'markedaspatrolledtext' );
			$response['unpatrolled'] = self::getUnpatrolledCount();
		}

		// Lojjik Braughler - Start of Patrol Throttle feature
		// If we're already in RC patrol, then we need to check if they hit their
		// limit before giving them a diff. Note: Pass true to canUseRCPatrol()
		// since we're already in RCP and it knows to subtract one.
		$patroller = null;
		if ( class_exists( 'PatrolUser' ) ) {
			$patroller = PatrolUser::newFromUser( $user );
		}

		if ( is_object( $patroller ) && !$patroller->canUseRCPatrol( true ) ) {
			$response['html'] = PatrolUser::getThrottleMessageHTML();
		} else {
			// Include next title for debugging
			$response['title'] = (string)$result['title'];
			$response['html'] = $out->getHTML();
			$response['rc_id'] = (integer)$result['new'];
			$response['article_id'] = $result['title']->mArticleID;
		}

		// Try to json_encode output and deal with any UTF8 encoding errors
		$jsonResponse = json_encode($response);
		if (json_last_error() == JSON_ERROR_UTF8) {
			$response['html'] = utf8_encode( $response['html'] );
			$jsonResponse = json_encode($response);
		}
		if (json_last_error() != JSON_ERROR_NONE) {
			$response['html'] = 'Could not encode article';
			$response['err'] = json_last_error_msg();
			$jsonResponse = json_encode($response);
		}

		$out->clearHTML();
		$out->addHTML( $jsonResponse );
	}

	private function markRevisionsPatrolled($article) {
		$user = $this->getUser();
		$request = $this->getRequest();

		// some sanity checks
		$rcid = $request->getInt( 'rcid' );
		$rc = RecentChange::newFromId( $rcid );
		if ( is_null( $rc ) ) {
			throw new ErrorPageError( 'markedaspatrollederror', 'markedaspatrollederrortext' );
		}

		if ( !$user->matchEditToken( $request->getVal( 'token' ), $rcid ) ) {
			throw new ErrorPageError( 'sessionfailure-title', 'sessionfailure' );
		}

		// check if skip has been passed to us
		if ($request->getInt('skip') != 1) {
			// find his and lows
			$rcids = array();
			$rcids[] = $rcid;
			if ($request->getVal('rchi', null) && $request->getVal('rclow', null)) {
				$hilos = wfGetRCPatrols($rcid, $request->getVal('rchi'), $request->getVal('rclow'), $article->mTitle->getArticleID());
				$rcids = array_merge($rcids, $hilos);
			}
			$rcids = array_unique($rcids);
			foreach ($rcids as $id) {
				RecentChange::markPatrolled( $id, false);
			}

			Hooks::run( 'MarkPatrolledBatchComplete', array(&$article, &$rcids, &$user));
		} else {
			RCPatrol::skipPatrolled($article);
		}
	}
}

class RCTestStub {
	// Inject the test diff if it's RCPatrol is supposed to show a test
	public static function getDifferenceEngine($context, $result, &$rcTest) {
		if (class_exists('RCTest') && RCTest::isEnabled()) {
			if ($rcTest && $rcTest->isTestTime()) {
				$result = $rcTest->getResultParams();

				// okay, so let's blow away this cookie so that if
				// the test fails to load (RC Patrol bug) the user
				// isn't cut off from another test
				$rcTest->setTestActive(false);
			}
		}

		return new DifferenceEngine($context, RCPatrol::cleanOldId($result['old']), $result['new']);
	}

	// Change the title to the test Title if RCPatrol is supposed to show a test
	public static function getTitleText($result, &$rcTest) {
		if (class_exists('RCTest') && RCTest::isEnabled()) {
			if ($rcTest && $rcTest->isTestTime()) {
				$result = $rcTest->getResultParams();
			}
		}
		$t = $result['title'];
		return "<a href='{$t->getLocalURL()}'>" . $t->getFullText() . "</a>";
	}

	public static function getThumbsUpButton($result, &$rcTest) {
		$button = "";
		if (class_exists('RCTest') && RCTest::isEnabled()) {
			if ($rcTest && $rcTest->isTestTime()) {
				$result = $rcTest->getResultParams();
			}
		}
		if (class_exists('ThumbsUp')) {
			//-1 is a secret code to our thumbs up function
			$result['old'] = ($result['old'] != 0) ? $result['old'] : -1;
			$button = ThumbsUp::getThumbsUpButton($result);
		}
		return $button;
	}
}
