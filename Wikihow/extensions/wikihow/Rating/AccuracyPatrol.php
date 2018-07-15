<?php

use MethodHelpfulness\ArticleMethod;

/**
 * The actual special page that displays the list of low accuracy / low
 * rating articles
 */
class AccuracyPatrol extends QueryPage {

	var $targets = array(),
		$sqlQuery,
		$forSamples;

	function __construct( $name = 'AccuracyPatrol' ) {
		parent::__construct( $name );
		//is this for articles or samples?
		$this->forSamples = (strpos(strtolower($_SERVER['REQUEST_URI']),'sample')) ? true : false;

		list( $limit, $offset ) = wfCheckLimits();
		$this->limit = $limit;
		$this->offset = $offset;
	}

	function setSql($sql) {
		$this->sqlQuery = $sql;
	}

	function getName() {
		return 'AccuracyPatrol';
	}

	function isExpensive( ) { return false; }

	function isSyndicated() { return false; }

	function getPageHeader( ) {
		$out = $this->getOutput();

		$headname = ($this->forSamples) ? 'Sample Accuracy Patrol' : 'Article Accuracy Patrol';
		$out->setPageTitle($headname);
		return $out->parse( wfMessage( 'listlowratingstext' )->text() );
	}

	function getOrderFields() {
		if ($this->forSamples) {
			$order = array('rsl_avg');
		} else {
			$order = array('rl_avg');
		}
		return $order;
	}

	function getSQL() {
		if ($this->forSamples) {
			$minvotes = wfMessage('list_bottom_rated_pages_min_votes');
			$avg = wfMessage('list_bottom_rated_pages_avg');

			$sql = "SELECT rsl_page, rsl_avg, rsl_count FROM ratesample_low WHERE rsl_count >= $minvotes AND rsl_avg <= $avg";
		} else {
			$sql = "SELECT page_namespace, page_title, rl_avg, rl_count FROM rating_low, page WHERE rl_page=page_id";
		}
		return $sql;
	}

	function formatResult($skin, $result) {
		if ($this->forSamples) {
			$t = Title::newFromText("Sample/$result->rsl_page");
			if ($t == null)
				return "";

			$avg = number_format($result->rsl_avg * 100, 0);
			$cl = SpecialPage::getTitleFor( 'ClearRatings', $result->rsl_page );

			//need to tell the linker that the title is known otherwise it adds redlink=1 which eventually breaks the link
			$link = Linker::linkKnown($t, $t->getFullText()) . " - ({$result->rsl_count} votes, average: {$avg}% - " . Linker::link($cl, 'clear', array(), array('type' => 'sample')) . ")";
		} else {
			$t = Title::makeTitle($result->page_namespace, $result->page_title);
			if ($t == null)
				return "";
			$avg = number_format($result->rl_avg * 100, 0);
			$cl = SpecialPage::getTitleFor( 'ClearRatings', $t->getText() );
			$link = Linker::link($t, $t->getFullText()) . " - ({$result->rl_count} votes, average: {$avg}% - " . Linker::link($cl, 'clear', array(), array('type' => 'article')) . ")";
		}
		return $link;
	}

	/**
	 *
	 * This function is used for de-indexing purposes. All articles that show up on the
	 * page Special:AccuracyPatrol are de-indexed. This is only used for
	 *
	 */
	static function isInaccurate($articleId, &$dbr) {
		$row = $dbr->selectField('rating_low', 'rl_page', array('rl_page' => $articleId), __METHOD__);

		return $row !== false;
	}

}
