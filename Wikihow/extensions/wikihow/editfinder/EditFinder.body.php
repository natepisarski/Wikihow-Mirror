<?php

/*
CREATE TABLE `editfinder` (
  `ef_page` int(8) DEFAULT NULL,
  `ef_title` varchar(255) DEFAULT NULL,
  `ef_edittype` varchar(255) DEFAULT NULL,
  `ef_skip` mediumint(8) DEFAULT NULL,
  `ef_skip_ts` varchar(14) DEFAULT NULL,
  `ef_last_viewed` varchar(14) DEFAULT NULL,
  UNIQUE KEY `idx_ef` (`ef_page`,`ef_edittype`)
);

CREATE TABLE `editfinder_skip` (
  `efs_page` int(10) unsigned DEFAULT NULL,
  `efs_user` int(10) unsigned DEFAULT '0',
  `efs_visitor_id` varbinary(20) NOT NULL DEFAULT '',
  `efs_timestamp` varchar(14) DEFAULT NULL,
  KEY `efs_user` (`efs_user`)
);
*/

class EditFinder extends UnlistedSpecialPage {
	var $topicMode = false;

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'EditFinder');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	/**
	 * Set html template path for EditFinder actions
	 */
	public static function setTemplatePath() {
		EasyTemplate::set_path( __DIR__.'/' );
	}

	public static function getUnfinishedCount(&$dbr, $type) {
		switch ($type) {
		case 'Stub':
			$count = $dbr->selectField(
				array('page', 'templatelinks'),
				'count(*) as count',
				array('tl_title' => 'Stub', 'tl_from=page_id', 'page_namespace' => NS_MAIN),
				__METHOD__);
			return $count;

		case 'Format':
			$count = $dbr->selectField(
				array('page', 'templatelinks'),
				'count(*) as count',
				array('tl_title' => 'Format',
					'tl_from=page_id',
					'page_namespace' => '0'),
				__METHOD__);
			return $count;
		case 'Copyedit':
			$count = $dbr->selectField(
				array('page', 'templatelinks'),
				'count(*) as count',
				array('tl_title' => 'Copyedit',
					'tl_from=page_id',
					'page_namespace' => '0'),
				__METHOD__);
			return $count;
		case 'Cleanup':
			$count = $dbr->selectField(
				array('page', 'templatelinks'),
				'count(*) as count',
				array('tl_title' => 'Cleanup',
					'tl_from=page_id',
					'page_namespace' => '0'),
				__METHOD__);
			return $count;
		case 'Topic':
			// No real unfinished count for Greenhouse by Topic
			return 0;
		}

		return 0;
	}

	private function isTemplateTest($aid) {
		$pages = array( 'Test-Copyvio', 'Test-Copyviobot', 'Test-Cleanup', 'Test-Copyedit', 'Test-Stub' );
		$title = Title::newFromID($aid);

		return $title && in_array( $title->getDBkey(), $pages );
	}

	private function getNextArticle() {
		//skipping something?
		$skip_article = $this->getRequest()->getVal('skip');

		//flip through a few times in case we run into problem articles
		for ($i = 0; $i < 30; $i++) {
			$pageid = $this->getNext($skip_article);

			if (!$this->hasProblems($pageid) && !$this->isTemplateTest($pageid)) {
				return $this->returnNext($pageid);
			} else {
				// If there's a problem, come back to it later
				$skip_article = intVal($pageid);
			}
		}
		return $this->returnNext('');
	}

	private function getNextByInterest() {

		$dbw = wfGetDB(DB_MASTER);

		$aid = $this->getRequest()->getInt('id');

		if ($aid) {
			//get a specific article
			$sql = "SELECT page_id from page WHERE page_id = " . intval($aid) . " LIMIT 1";
		} else {
			$sql = "SELECT page_id from page p INNER JOIN categorylinks c ON c.cl_from = page_id WHERE page_namespace = 0 ";
			$sql .= $this->getSkippedArticles('page_id');
			$sql .= " AND ".$this->getUserInterests();

			$sql .= " ORDER BY p.page_random LIMIT 1;";
		}

		$res = $dbw->query($sql, __METHOD__);

		foreach ($res as $row) {
			$pageid = $row->page_id;
			$cat = $row->cl_to;
		}

		if ($pageid) {
			//not a specified an article, right?
			if (empty($aid)) {
				//is the article {{in use}}?
				if ($this->articleInUse($pageid)) {
					//mark it as viewed
					$pageid = '';
				}
			}
		}
		return $pageid;
	}

	private function getNext($skip_article) {
		$user = $this->getUser();
		$req = $this->getRequest();

		$dbw = wfGetDB(DB_MASTER);

		// mark skipped
		if (!empty($skip_article)) {
			$t = is_int($skip_article) ?
				Title::newFromID($skip_article) : Title::newFromText($skip_article);

			if (!$t || !$t->exists()) {
				$id = null;
			} else {
				$id = $t->getArticleID();
			}

			//mark the db for this user
			if (!empty($id)) {

				$cond = array(
					'efs_page'=>$id,
					'efs_timestamp'=>wfTimestampNow()
				);
				$cond['efs_user'] = $user->getId() ? $user->getId() : 0;
				if ($user->isAnon()) {
					$cond['efs_visitor_id'] = WikihowUser::getVisitorId();
				}
				$dbw->insert('editfinder_skip', $cond, __METHOD__);
			}
		}

		$aid = $req->getInt('id');

		if ($aid) {
			//get a specific article
			$sql = "SELECT ef_page from editfinder WHERE ef_page = " . intval($aid) . " LIMIT 1";
		} else {

			$timediff = date("YmdHis", strtotime("-1 hour"));
			$sql = "SELECT ef_page from editfinder ".
					" INNER JOIN page p ON page_id = ef_page ";

			if ($this->topicMode) {
				$sql .= "  INNER JOIN categorylinks c ON c.cl_from = page_id ".
						" WHERE ".$this->getUserInterests();
			} else {
				$edittype = ucfirst( strtolower( $req->getVal('edittype') ) );
				// Possible values, from DB, are: 'Format','Stub','Copyedit','Copyeditbot','Introduction','Cleanup','Clarity'
				$sql .= " WHERE ef_edittype = " . $dbw->addQuotes($edittype);
			}

			$sql .= " AND ef_last_viewed < ". $dbw->addQuotes($timediff)." ".
				"AND page_namespace = ".NS_MAIN." ".
				$this->getSkippedArticles()." ".
				$this->getUserCats();

			if ($this->topicMode) {
				$sql .= " ORDER BY ef_edittype DESC ";
			}

			$sql .= " LIMIT 1";
		}

		$res = $dbw->query($sql, __METHOD__);
		foreach ($res as $row) {
			$pageid = $row->ef_page;
		}

		if ($pageid) {
			//not a specified an article, right?
			if (empty($aid)) {
				//is the article {{in use}}?
				if ($this->articleInUse($pageid)) {
					//mark it as viewed
					$dbw->update(
						'editfinder',
						array('ef_last_viewed' => wfTimestampNow()),
						array('ef_page' => $pageid),
						__METHOD__);
					$pageid = '';
				}
			}
		} else {
			//no page id?
			if ($this->topicMode) {
				//Topic Greenhouse; grab from main page table
				$pageid = $this->getNextByInterest();
			}
		}

		return $pageid;
	}

	private function returnNext($pageid) {
		if (empty($pageid)) {
			//nothing? Ugh.
			$a['aid'] = '';
		} else {
			if (!$this->topicMode) {
				//touch db
				$dbw = wfGetDB(DB_MASTER);
				$dbw->update(
					'editfinder',
					array('ef_last_viewed' => wfTimestampNow()),
					array('ef_page' => $pageid),
					__METHOD__);
			}

			$a = array();

			$t = Title::newFromID($pageid);

			$a['aid'] = $pageid;
			$a['title'] = $t->getText();
			$a['url'] = $t->getLocalURL();
			$a['cat'] = CategoryInterests::getUsedCat($t);
		}

		//return array
		return $a;
	}

	private function confirmationModal($type, $id) {

		$t = Title::newFromID($id);
		$titletag = "[[".$t->getText()."|".wfMessage('howto', $t->getText())."]]";
		$content = 	"
			<div class='editfinder_modal'>
			<p>Thanks for your edits to <a href='".$t->getLocalURL()."'>".wfMessage('howto', $t->getText())."</a>.</p>
			<p>Would it be appropriate to remove the <span class='template_type'>".strtoupper($type)."</span> from this article?</p>
			<div style='clear:both'></div>
			<span style='float:right'>
			<input class='button secondary submit_button' id='ef_modal_no' type='button' value='".wfMessage('editfinder_confirmation_no')."' />
			<input class='button primary submit_button' id='ef_modal_yes' type='button' value='".wfMessage('editfinder_confirmation_yes')."' />
			</span>
			</div>";
		$this->getOutput()->addHTML($content);
	}

	private function cancelConfirmationModal($id) {

		$t = Title::newFromID($id);
		$titletag = "[[".$t->getText()."|".wfMessage('howto', $t->getText())."]]";
		$content = 	"
			<div class='editfinder_modal'>
			<p>Are you sure you want to stop editing <a href='".$t->getLocalURL()."'>".wfMessage('howto', $t->getText())."</a>?</p>
			<div style='clear:both'></div>
			<p id='efcc_choices'>
			<a href='#' id='efcc_yes'>".wfMessage('editfinder_cancel_yes')."</a>
			<input class='button blue_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' type='button' value='".wfMessage('editfinder_confirmation_no')."' id='efcc_no'>
			</p>
			</div>";
		$this->getOutput()->addHTML($content);
	}

	/**
	 * articleInUse
	 * check to see if {{inuse}} or {{in use}} is in the article
	 * returns boolean
	 **/
	private function articleInUse($aid) {
		$dbr = wfGetDB(DB_REPLICA);
		$r = Revision::loadFromPageId( $dbr, $aid );

		if (strpos(ContentHandler::getContentText( $r->getContent() ),'{{inuse') === false) {
			$result = false;
		} else {
			$result = true;
		}
		return $result;
	}

	private function getUserInterests() {
		$interests = CategoryInterests::getCategoryInterests();
		$interests = array_merge($interests, CategoryInterests::getSubCategoryInterests($interests));
		$interests = array_values(array_unique($interests));

		$dbr = wfGetDB(DB_REPLICA);

		$fn = function(&$value) {
			$value = str_replace(' ','-',$value);
		};
		array_walk($interests, $fn);
		$sql = empty($interests) ? "c.cl_to = ''" : " c.cl_to IN (" . $dbr->makeList($interests) . ") ";
		return $sql;
	}

	private function getUserInterestCount() {
		$interests = CategoryInterests::getCategoryInterests();
		return count($interests);
	}

	/**
	 * getUserCats
	 * grab categories specified by the user
	 * returns sql string
	 **/
	private function getUserCats() {
		global $wgCategoryNames;
		$user = $this->getUser();
		$cats = array();
		$catsql = '';
		$bitcat = 0;

		$dbr = wfGetDB(DB_REPLICA);

		$row = $dbr->selectRow(
			'suggest_cats',
			array('*'),
			array('sc_user' => $user->getID()),
			__METHOD__);

		if ($row) {
			$field = $row->sc_cats;
			$cats = preg_split("@,@", $field, 0, PREG_SPLIT_NO_EMPTY);
		}

		$topcats = array_flip($wgCategoryNames);

		foreach ($cats as $key => $cat) {
			foreach ($topcats as $keytop => $cattop) {
				$cat = str_replace('-',' ',$cat);
				if (strtolower($keytop) == $cat) {
					$bitcat |= $cattop;
					break;
				}
			}
		}
		if ($bitcat > 0) {
			$catsql = ' AND p.page_catinfo & '.$bitcat.' <> 0';
		}
		return $catsql;
	}

	/**
	 * getSkippedArticles
	 * grab articles that were already "skipped" by the user
	 * returns sql string
	 **/
	private function getSkippedArticles($column = 'ef_page') {
		$user = $this->getUser();
		$skipped = '';
		$dbw = wfGetDB(DB_MASTER);

		$cond = array();
		$cond['efs_user'] = $user->getId() ? $user->getId() : 0;
		if ($user->isAnon()) {
			$cond['efs_visitor_id'] = WikihowUser::getVisitorId();
		}

		$res = $dbw->select(
			'editfinder_skip',
			array('efs_page'),
			$cond,
			__METHOD__);

		foreach ($res as $row) {
			$skipped_ary[] = $row->efs_page;
		}
		if (count($skipped_ary) > 0) {
			$skipped = ' AND ' . $column . ' NOT IN ('. $dbw->makeList($skipped_ary) .') ';
		}

		return $skipped;
	}

	/**
	 * hasProblems
	 * (returns TRUE if there's a problem)
	 * - Makes sure last edit has been patrolled
	 **/
	private function hasProblems($pageid) {
		if (empty($pageid)) return true;

		$t = Title::newFromId($pageid);
		if (!$t) return true;

		//last edit patrolled?
		if (!GoodRevision::patrolledGood($t)) return true;

		//all clear?
		return false;
	}

	public function execute($par) {
		global $wgParser, $efType;


		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$target = isset($par) ? $par : $req->getVal( 'target' );

		self::setTemplatePath();

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$this->topicMode = strtolower($target) == 'topic' || strtolower($req->getVal('edittype')) == 'topic';

		if ($req->getVal( 'fetchArticle' )) {
			$out->setSquidMaxage(0);
			$out->setArticleBodyOnly(true);

			print json_encode($this->getNextArticle());
			return;

		} elseif ($req->getVal( 'show-article' )) {
			$out->setArticleBodyOnly(true);

			if ($req->getInt('aid') == '') {
				$catsJs = $this->topicMode ? "editFinder.getThoseInterests();" : "editFinder.getThoseCats();";
				$catsTxt = $this->topicMode ? "interests" : "categories";
				$out->addHTML('<br />');
				return;
			}

			$t = Title::newFromID($req->getInt('aid'));

			$articleTitleLink = $t->getLocalURL();
			$articleTitle = $t->getText();
			//$edittype = $a['edittype'];

			//get article
			$a = new Article($t);

			$r = Revision::newFromTitle($t);
			$popts = $out->parserOptions();
			$popts->setTidy(true);
			$popts->enableLimitReport();
			$parserOutput = $wgParser->parse( ContentHandler::getContentText( $r->getContent() ), $t, $popts, true, true, $a->getRevIdFetched() );
			$popts->setTidy(false);
			$popts->enableLimitReport( false );
			$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $r->getContent() ));
			$html = WikihowArticleHTML::processArticleHTML($parserOutput->getText(), array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));
			$out->addHTML($html);
			return;

		} elseif ($req->getVal( 'edit-article' )) {
			// SHOW THE EDIT FORM
			$out->setArticleBodyOnly(true);
			$t = Title::newFromID($req->getInt('aid'));
			$a = new Article($t);
			$editor = new EditPage( $a );
			$editor->edit();
			return;

		} elseif ($req->getVal( 'action' ) == 'submit') {
			$out->setArticleBodyOnly(true);

			$efType = strtolower($req->getVal('type'));

			$t = Title::newFromID($req->getInt('aid'));

			//log it
			$params = array($efType);
			$log = new LogPage( 'EF_'. substr($efType, 0, 7), false ); // false - dont show in recentchanges

			$log->addEntry('', $t, 'Repaired an article -- '.strtoupper($efType).'.', $params);

			$text = $req->getVal('wpTextbox1');
			$sum = $req->getVal('wpSummary');

			//save the edit
			$wikiPage = WikiPage::factory($t);
			$content = ContentHandler::makeContent($text, $t);
			$wikiPage->doEditContent($content, $sum, EDIT_UPDATE);
			Hooks::run("EditFinderArticleSaveComplete", array($wikiPage, $text, $sum, $user, $efType));
			return;

		} elseif ($req->getVal( 'confirmation' )) {
			$out->setArticleBodyOnly(true);
			print $this->confirmationModal($req->getVal('type'),$req->getInt('aid')) ;
			return;

		} elseif ($req->getVal( 'cancel-confirmation' )) {
			$out->setArticleBodyOnly(true);
			print $this->cancelConfirmationModal($req->getInt('aid')) ;
			return;

		} else { //default view (same as most of the views)
			$out->setArticleBodyOnly(false);

			//custom topic from querystring
			$qs_topic = $this->getRequest()->getVal('topic');
			if ($qs_topic != '') CategoryInterests::addCategoryInterest($qs_topic);

			$efType = strtolower($target);
			if (strpos($efType,'/') !== false) {
				$efType = substr($efType,0,strpos($efType,'/'));
			}
			if ($efType == '') {
				//no type specified?  send 'em to format...
				$out->redirect('/Special:EditFinder/Format');
			} elseif ($efType == 'stub') {
				//Stub is deprecated. send 'em to topic...
				$out->redirect('/Special:EditFinder/Topic');
			}

			$out->addModules('ext.wikihow.greenhouse');
			$out->addModuleStyles('ext.wikihow.greenhouse.styles');

			//add main article info
			$vars = array('pagetitle' => wfMessage('app-name').': '.wfMessage($efType),'question' => wfMessage('editfinder-question'),
				'yep' => wfMessage('editfinder_yes'),'nope' => wfMessage('editfinder_no'),'helparticle' => wfMessage('help_'.$efType));
			$vars['lc_categories'] = $this->topicMode ? 'interests' : 'categories';
			$vars['editfinder_edit_title'] = wfMessage('editfinder_edit_title');
			$vars['editfinder_skip_title'] = wfMessage('editfinder_skip_title');
			$vars['ef_num_cats'] = $this->topicMode ? $this->getUserInterestCount() : 0;
			$vars['edittype'] = strtolower($efType);

			$html = EasyTemplate::html('editfinder_main.tmpl.php',$vars);
			$out->addHTML($html);

			$out->setHTMLTitle(wfMessage('app-name').': '.wfMessage($efType).' - wikiHow');
			$out->setPageTitle(wfMessage('app-name').': '.wfMessage($efType));
		}

		$stats = new EditFinderStandingsIndividual($efType);
		$stats->addStatsWidget();
		$standings = new EditFinderStandingsGroup($efType);
		$standings->addStandingsWidget();
	}

}
