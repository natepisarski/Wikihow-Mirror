<?php

/*
 * schema:
CREATE TABLE `category_article_votes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cat_slug` varchar(255) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `last_touched` varchar(14) DEFAULT NULL,
  `resolved` tinyint(1) DEFAULT '0',
  `votes_up` decimal(10,1) NOT NULL DEFAULT '0.0',
  `votes_down` decimal(10,1) NOT NULL DEFAULT '0.0',
  PRIMARY KEY (`id`),
  KEY `cat_slug` (`cat_slug`),
  KEY `resolved` (`resolved`)
);
*/

class CategoryGuardian extends UnlistedSpecialPage {

	const MAINTENANCE_MODE = false;
	const LOG_TYPE = 'category_guardian';
	public $out;
	public $user;
	public $sql;

	function __construct() {
		global $wgHooks, $wgDebugToolbar;
		parent::__construct('CategoryGuardian');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');

		$this->user = $this->getUser();
		$this->out = $this->getContext()->getOutput();
	}

	function execute($par) {
		$request = $this->getRequest();

		$this->out->setRobotPolicy('noindex,nofollow');
		$this->out->setHTMLTitle(wfMessage('category-guardian')->text());
		$this->out->setPageTitle(wfMessage('category-guardian')->text());

		# Check blocks
		$user = $this->getUser();
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		} elseif (self::MAINTENANCE_MODE) {
			$this->out->addWikiText(wfMessage('catch-disabled')->text());
			return;
		}

		if ($request->getVal('nextBatch')) {
			$this->out->setArticleBodyOnly(true);
			print_r($this->getJSON());
			return;

		} elseif ($request->wasPosted()) {
		  if (!XSSFilter::isValidRequest()) {
				$this->out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
				return;
			}
			$post = new PostVote();
			$post->saveVotes();
			$this->out->setArticleBodyOnly(true);

			print_r($this->getJSON());
			return;
		}

		// only render the template if not a JSON request, or a POST
		$this->addJSAndCSS();
		$this->addTemplateHtml();
		$this->addStandingGroups();
		$this->addUsageStats();
	}

	public function isMobileCapable() {
		return true;
	}

	protected function getJSON() {
		$result = null;
		if (class_exists('Plants') && Plants::usesPlants('CategoryGuardian') ) {
			$plants = new CategoryPlants();
			$result = $plants->getNextPlant();
		}

		if ($result == null) {
			$get = new GetArticles();
			$result = $get->getCategoryWithArticles();
		}

		$slugs = array();

		foreach ($result['articles'] as $row) {
			$article = (array) $row;
			$sum = new Summary($row->page_id);

			if ($sum->page) {
				$page_title = mb_convert_encoding($sum->getTitleText(), 'UTF-8', 'UTF-8');
				$blurb = mb_convert_encoding($sum->getSummary(), 'UTF-8', 'UTF-8');

				$item = array(
					'page_title' => $page_title,
					'blurb' => $blurb
				);

				array_push($slugs, array_merge($article, $item));
			}
		}

		$payload = array(
			'category'=> array(
				'mTextform' => $result['cat']->getText(),
				'mDbkeyform' => $result['cat']->getDBkey(),
			),
			'articles'=> $slugs
		);

		if ( !Misc::isMobileMode() ) {
			$payload['category']['link'] = $result['cat']->getFullURL();
		}

		if ($this->getRequest()->getVal('debug') and in_array('staff', $this->user->getGroups())) {
			global $sqlQueries;
			$payload['queries'] = $sqlQueries;
		}

		return json_encode($payload);
	}

	protected function addTemplateHtml() {
		$tpl = new EasyTemplate(__DIR__);
		$html = $tpl->execute('CategoryGuardian.tmpl.php');
		$this->out->addHTML($html);
	}

	protected function addJSAndCSS() {
		global $wgDebugToolbar;

		WikihowSkinHelper::maybeAddDebugToolbar($this->out);

		$this->out->addModules(
			array('ext.wikihow.UsageLogs', 'ext.wikihow.CategoryGuardian')
		);
		$this->out->addModuleStyles('ext.wikihow.CategoryGuardian.styles');

	  if (Misc::isMobileMode()) {
		$this->out->addModuleStyles('ext.wikihow.CategoryGuardian.styles.mobile');
	  }
	}

	public static function onArticleChange($wikiPage) {
		if ($wikiPage) {
			$sql = new SqlSuper();
			$articleID = $wikiPage->getID();
			if ($articleID) {
				$sql->delete('category_article_votes', array('page_id' => $articleID), __METHOD__);
			}
		}

		return true;
	}

	protected function addUsageStats() {
		$stats = new UsageStats(self::LOG_TYPE);
		$resolved = "(log_action = 'confirmed' OR log_action = 'removed')";
		$stats->
			addQuery(
				"SELECT COUNT(*) AS 'Total Resolved' FROM logging WHERE {{logKey}} AND $resolved AND {{inRange}}"
			)->
			addQuery(
				"SELECT (
					(SELECT COUNT(*) FROM logging WHERE {{logKey}} AND log_action = 'confirmed' AND {{inRange}}) /
					(SELECT COUNT(*) FROM logging WHERE {{logKey}} AND $resolved AND {{inRange}}) * 100
					) AS 'Percent Resolved Up'"
			)->
			addQuery(
				"SELECT (
					(SELECT COUNT(*) FROM logging WHERE {{logKey}} AND log_action = 'removed' AND {{inRange}}) /
					(SELECT COUNT(*) FROM logging WHERE {{logKey}} AND $resolved AND {{inRange}}) * 100
					) AS 'Percent Resolved Down'"
			)->
			addQuery(
				"SELECT COUNT(*) AS 'Total Votes' FROM logging WHERE {{logKey}} AND {{inRange}}"
			)->
			addQuery(
				"SELECT (
					(SELECT COUNT(*) FROM logging WHERE `log_user` = 0 AND {{logKey}} AND {{inRange}}) /
					(SELECT COUNT(*) FROM logging WHERE {{logKey}} AND {{inRange}}) * 100
					) AS 'Percentage Anonymous'"
			)->
			addQuery(
				"SELECT (
					(SELECT COUNT(*) FROM logging WHERE `log_user` != 0 AND {{logKey}} AND {{inRange}}) /
					(SELECT COUNT(*) FROM logging WHERE {{logKey}} AND {{inRange}}) * 100
					) AS 'Percentage Logged In'"
			);

		$this->out->addHTML($stats->render());
	}

	protected function addStandingGroups() {
		if (!$this->user->isAnon()) {
			$singleStanding = new CategoryGuardianStandingsIndividual();
			$singleStanding->addStatsWidget();
		}

		$group = new CategoryGuardianStandingsGroup();
		$group->addStandingsWidget();
	}

	public static function getRemainingCount() {
		$get = new GetArticles();
		return $get->getRemainingCount();
	}
}
