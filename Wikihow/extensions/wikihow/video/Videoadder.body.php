<?php

class VideoAdder extends SpecialPage {

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'VideoAdder' );
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	// returns the HTML for the category drop down
	private function getCategoryDropDown() {
		$req = $this->getRequest();
		$cats = CategoryHelper::getTopLevelCategoriesForDropDown();
		$selected = $req->getVal('cat');
		$html = '<select id="va_category" onchange="WH.VideoAdder.chooseCat();"><option value="">All</option>';
		foreach ($cats as $c) {
			$c = trim($c);
			if ($c == "" || $c == "WikiHow" || $c == "Other")
				continue;
			if ($c == $selected)
				$html .= '<option value="' . $c . '" selected>' . $c . '</option>';
			else
				$html .= '<option value="' . $c . '">' . $c . '</option>';
		}
		$html .= '</select>';
		return $html;
	}

	// handles the coookie settings for skipping a video
	private static function skipArticle($id) {
		global $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
		// skip the article for now
		$cookiename = "VAskip";
		$cookie = $id;
		if (isset($_COOKIE[$wgCookiePrefix.$cookiename]))
			$cookie .= "," . $_COOKIE[$wgCookiePrefix.$cookiename];
		$exp = time() + 86400; // expire after 1 week
		setcookie( $wgCookiePrefix.$cookiename, $cookie, $exp, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
		$_COOKIE[$wgCookiePrefix.$cookiename] = $cookie;
	}

	/**
	 * Returns the total number of articles waiting to
	 * have images added to the Intro
	 * NOTE: Used by CommunityDashboard widget
	 */
	public static function getArticleCount(&$dbr) {
		$ts = wfTimestamp(TS_MW, time() - 10 * 60);

		$res = $dbr->select('videoadder', array('count(*) as C'), array ("va_inuse IS NULL or va_inuse < '{$ts}'", "va_skipped_accepted IS NULL", "va_page NOT In (5, 5791)", "va_template_ns IS NULL"), __METHOD__);
		$row = $dbr->fetchObject($res);

		return $row->C;
	}

	/**
	 * Returns the id/date of the last VAdder.
	 * NOTE: Used by CommunityDashboard widget
	 */
	public static function getLastVA(&$dbr) {
		$sql = "";
		$bots = WikihowUser::getBotIDs();

		if (sizeof($bots) > 0) {
			$sql = "va_user NOT IN (" . $dbr->makeList($bots) . ")";
		}

		if ($sql != "")
			$res = $dbr->select('videoadder', array('va_user', 'va_timestamp'), array('va_skipped_accepted' => 0, $sql), __METHOD__, array("ORDER BY"=>"va_timestamp DESC", "LIMIT"=>1));
		else
			$res = $dbr->select('videoadder', array('va_user', 'va_timestamp'), array('va_skipped_accepted' => 0), __METHOD__, array("ORDER BY"=>"va_timestamp DESC", "LIMIT"=>1));

		$row = $dbr->fetchObject($res);
		$vauser = array();
		$vauser['id'] = $row->va_user;
		$vauser['date'] = wfTimeAgo($row->va_timestamp);

		return $vauser;
	}

	/**
	 *
	 * Returns the id/date of the highest VAdder
	 * NOTE: Used by CommunityDashboard widget
	 */
	public static function getHighestVA(&$dbr, $period='7 days ago') {
		$startdate = strtotime($period);
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$sql = "";
		$bots = WikihowUser::getBotIDs();

		if (sizeof($bots) > 0) {
			$sql = " AND va_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$sql = "SELECT va_user, count(va_user) as va_count, MAX(va_timestamp) as va_recent FROM `videoadder` WHERE va_timestamp >= '" . $starttimestamp . "'" . $sql . " AND va_skipped_accepted IN ('0','1') GROUP BY va_user ORDER BY va_count DESC";
		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);

		if (!empty($row)) {
			$user = $row->va_user;
			$date = wfTimeAgo($row->va_recent);
		}
		else {
			$user = '';
			$date = '';
		}

		return [
			'id' => $user,
			'date' => $date
		];
	}

	// performs all of the logic of getting the next video, returns an array
	// array ( title object of the article to work on, video array the video returned from the api )
	private function getNext() {
		global $wgCookiePrefix, $wgCategoryNames;
		$req = $this->getRequest();
		$iv = new ImportVideoYoutube();
		$dbw = wfGetDB(DB_MASTER);
		$dbr = wfGetDB(DB_REPLICA);
		$cat = $req->getVal('va_cat') ? Title::makeTitleSafe(NS_CATEGORY, $req->getVal('va_cat')) : null;

		// get a list
		$cookiename = $wgCookiePrefix."VAskip";
		$skipids = [];
		if (isset($_COOKIE[$cookiename])) {
			$ids = array_unique(explode(",", $_COOKIE[$cookiename]));
			foreach ($ids as $id) {
				if (preg_match("@[^0-9]@", $id))
					continue;
				$skipids[] = $id;
			}
		}

		for ($i = 0; $i < 30; $i++) {
			if (rand(0, 2) < 2)
				$order_by = 'va_page_counter DESC'; //most popular page that has no video
			else
				$order_by = 'va_page_touched DESC'; //most recently edited page that has no video

			// if it's been in use for more than x minutes, forget 'em
			$ts = wfTimestamp(TS_MW, time() - 10 * 60);

			$from = ['videoadder'];
			$values = ['va_page', 'va_id'];
			$where = [
				'va_template_ns' => NULL,
				'va_skipped_accepted' => NULL,
				"(va_inuse is NULL or va_inuse < '{$ts}')"
			];
			$options = [
				'ORDER BY' => $order_by,
				'LIMIT' => 1
			];
			$joins = [];

			if (!empty($skipids)) $where[] = 'va_page NOT IN ('.$dbr->makeList($skipids).')';

			if (!empty($cat)) {
				$from[] = 'page';
				$cats = array_flip($wgCategoryNames);
				$mask = $cats[$cat->getText()];
				$where[] = "page_catinfo & {$mask} = {$mask}";
				$joins = ['page' => ['LEFT JOIN', 'va_page = page_id']];
			}

			$res = $dbr->select($from, $values, $where, __METHOD__, $options, $joins);

			if ($row = $dbr->fetchObject($res)) {
				$title = Title::newFromID($row->va_page);
				if ($title && !self::hasProblems( $title, $dbr )) {
					$iv->getTopResults($title, 1, wfMessage("howto", $title->getText())->text());
				}
			}
			// get the next title to deal with
			if (sizeof($iv->mResults) > 0) {
				// mark it as in use, so we don't get multiple people processing the same page
				$dbw->update("videoadder", array("va_inuse"=>wfTimestampNow()), array("va_page"=>$row->va_page));
				return array($title, $iv->mResults[0]);
			}
			// set va_skipped_accepted to 2 because we have no results, so we skip it again
			$dbw->update("videoadder", array("va_skipped_accepted"=>2), array("va_page"=>$row->va_page));
		}
		return null;
	}

	// widget settings for getting the weekly rankings
	// caches the rankings for 1 hour, no sense in thrashing the DB for 1 value here
	private function getWeekRankings() {
		global $wgMemc;
		$req = $this->getRequest();
		$rankings = null;
		$key = wfMemcKey("videoadder_rankings");
		if ($wgMemc->get($key) && !$req->getVal('flushrankings')) {
			$rankings = $wgMemc->get($key);
		}
		if (!$rankings) {
			$dbr = wfGetDB(DB_REPLICA);
			$ts 	= substr(wfTimestamp(TS_MW, time() - 7 * 24 * 3600), 0, 8) . "000000";
			$res = $dbr->query("SELECT va_user, count(*) as C from videoadder where va_timestamp >= '{$ts}' AND (va_skipped_accepted = '0' OR va_skipped_accepted = '1') group by va_user ORDER BY C desc;");
			foreach ($res as $row) {
				$rankings[$row->va_user] = $row->C;
			}
			$wgMemc->set($key, $rankings, 3600);
		}
		return $rankings;
	}

	// sets all of the side widgets for the page
	private static function setSideWidgets() {
		$indi = new VideoStandingsIndividual();
		$indi->addStatsWidget();

		$standings = new VideoStandingsGroup();
		$standings->addStandingsWidget();

	}

	// gets the top 5 reviewers for the side widget
	private function getReviewersTable() {
		$rankings = $this->getWeekRankings();
		$table = "<table>";
		$index = 0;
		if (isset($rankings) && is_array($rankings)) {
			foreach ($rankings as $u=>$c) {
				$u = User::newFromID($u);
				$u->load();
				$img = Avatar::getPicture($u->getName(), true);
				if ($img == '') {
					$img = Avatar::getDefaultPicture();
				}
				$table .= "<tr><td class='va_image'>{$img}</td><td class='va_reviewer'><a href='{$u->getUserPage()->getFullURL()}' target='new'>{$u->getName()}</a></td><td class='va_stat'>{$c}</td></tr>";
				$index++;
				if ($index == 5) break;
			}
		}
		$table .= "</table>";
		return $table;
	}


	/**
	 * hasProblems
	 * (returns TRUE if there's a problem)
	 * - Checks to see if there's an {{nfd}} template
	 * - Makes sure an article has been NABbed
	 * - Makes sure last edit has been patrolled
	 **/
	private static function hasProblems($t, $dbr) {
		global $wgParser;
		$r = Revision::newFromTitle($t);
		if ($r) {
			$intro = $wgParser->getSection(ContentHandler::getContentText( $r->getContent() ), 0);

			//check for {{nfd}} template
			if (preg_match('/{{nfd/', $intro)) return true;

			//is it NABbed?
			$is_nabbed = NewArticleBoost::isNABbed($dbr,$t->getArticleId());
			if (!$is_nabbed) return true;

			//last edit patrolled?
			if (!GoodRevision::patrolledGood($t)) return true;
		}

		//all clear?
		return false;
	}

	public function execute($par) {
		$out = $this->getOutput();
		$out->setRobotPolicy( 'noindex,nofollow' );
		$this->showPage($par);
	}

	private function showPage($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ($user->getID() == 0) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($req->getVal( 'fetchReviewersTable' )) {
			$out->setArticleBodyOnly(true);
			print $this->getReviewersTable();
			return;
		}

		$target = isset( $par ) ? $par : $req->getVal( 'target' );
		$cat = htmlspecialchars($req->getVal( 'cat' ));

		// get just the HTML for the Ajax call for the next video
		// used even on the initial page load
		if ($target == 'getnext') {
			$out->setArticleBodyOnly(true);

			// process any skipped videos
			if ($req->getVal('va_page_id') && !preg_match("@[^0-9]@",$req->getVal('va_page_id'))) {
				$dbw = wfGetDB(DB_MASTER);

				$vals = [
					'va_vid_id'			=> $req->getVal('va_vid_id'),
					'va_user'				=> $user->getID(),
					'va_user_text'	=> $user->getName(),
					'va_timestamp'	=> wfTimestampNow(),
					'va_inuse'			=> NULL,
					'va_src'				=> 'youtube'
				];

				$va_skip = $req->getVal('va_skip');
				if ($va_skip < 2) $vals['va_skipped_accepted'] = $va_skip;

				$dbw->update('videoadder', $vals, ['va_page' => $req->getVal('va_page_id')], __METHOD__);

				if ($req->getVal('va_skip') == 0 ) {
					// import the video
					$tx = Title::newFromID($req->getVal('va_page_id'));
					$ipv = new ImportVideoYoutube();

					if ( !empty( $req->getVal('va_vid_id') ) ) {
						$text = $ipv->loadVideoText($req->getVal('va_vid_id'));
						$vid = Title::makeTitle(NS_VIDEO, $tx->getText());
						ImportVideo::updateVideoArticle($vid, $text, wfMessage('va_addingvideo')->text());
						ImportVideo::updateMainArticle($tx, wfMessage('va_addingvideo')->text());
					}
					Hooks::run("VAdone", array());
					$out->redirect('');
				} elseif ($req->getVal('va_skip') == 2) {
					// the user has skipped it and not rejected this one, don't show it to them again
					self::skipArticle($req->getVal('va_page_id'));
					Hooks::run("VAskipped", array());
				}
			}
			$results = $this->getNext();

			if (empty($results)) {
				$out->addHTML("Something went wrong. Refresh.");
				return;
			}

			$title 	= $results[0];
			$vid	= $results[1];
			$id 	= $results[1]->id;

			if (!$title) {
				$out->addHTML("Something went wrong. Refresh.");
				return;
			}

			$revision = Revision::newFromTitle($title);
			$popts = $out->parserOptions();
			$popts->setTidy(true);
			$parserOutput = $out->parse(ContentHandler::getContentText( $revision->getContent() ), $title, $popts);
			$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $revision->getContent() ));
			$html = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));


			$result['guts'] = ("<div id='va_buttons'><a href='#' onclick='WH.VideoAdder.skip(); return false;' class='button secondary ' style='float:left;'>Skip</a>
<a id='va_yes' href='#' class='button primary disabled buttonright op-action'>Yes, it does</a>
<a id='va_no' href='#' class='button secondary buttonright op-action'>No, it doesn't</a><div class='clearall'></div></div>
<div id='va_title'><a href='{$title->getFullURL()}' target='new'>" .  wfMessage("howto", $title->getText()) . "</a></div>
					<div id='va_video' class='section_text'>
	<p id='va_notice'>" . wfMessage('va_notice')->text() . "</p>
					<iframe src='https://www.youtube.com/embed/{$id}' width='480' height='385'></iframe>
					<input type='hidden' id='va_page_id' value='{$title->getArticleID()}'/>
					<input type='hidden' id='va_page_title' value='" . htmlspecialchars($title->getText()) . "'/>
					<input type='hidden' id='va_page_url' value='" . htmlspecialchars($title->getFullURL()) . "'/>
					<input type='hidden' id='va_skip' value='0'/>
					<input type='hidden' id='va_vid_id' value='{$id}'/>
					<input type='hidden' id='va_src' value='youtube'/>
					</div>

			</div>
			");
			$result['article'] = $html;

			$dropdown = wfMessage('va_browsemsg')->text() . " " . $this->getCategoryDropDown();
			$result['options'] = "<div id='va_browsecat'>" . $dropdown . "</div>";
			$result['cat'] = $cat;
			print json_encode($result);
			return;
		}


		// add the layer of the page
		$this->setHeaders();
		self::setSideWidgets();
		$out->addModules('jquery.ui.dialog');
		$out->addModules('ext.wikihow.videoadder');
		$out->addModules('ext.wikihow.videoadder_styles');
		$out->addHTML("<div class='tool_header tool'><h1>" . wfMessage('va_question') . "<span id='va_instructions'>" . wfMessage('va_instructions') . "</span></h1>");
		$out->addHTML("<div id='va_guts'>
					<center><img src='/extensions/wikihow/rotate.gif'/></center>
				</div>");

		$out->addHTML("</div>");
		$out->addHTML("<input type='hidden' id='va_cat' value='{$cat}' />");
		$out->addHTML("<div id='va_article'></div>");

		//$langKeys = array('va_congrats', 'va_check');
		//$js = Wikihow_i18n::genJSMsgs($langKeys);
		$out->addHTML($js);
	}
}
