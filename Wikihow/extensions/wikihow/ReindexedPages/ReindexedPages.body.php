<?php

/**
 * Lists articles that became indexable recently
 */
class ReindexedPages extends QueryPage {

	function __construct() {
		parent::__construct('ReindexedPages');
	}

	public function execute($par) {
		global $wgHooks;
		self::setListoutput(true);
		list($this->limit, $this->offset) = $this->getRequest()->getLimitOffset(250, '');
		parent::execute($par);
		$out = $this->getOutput();
		$out->addModuleStyles('wikihow.reindexedpages.styles');
		$out->setRobotPolicy('noindex,follow');
		$wgHooks['UseMobileRightRail'][] = ['ReindexedPages::removeSideBarCallback'];
	}

	function getQueryInfo() {
		return [
			'tables' => [ 'article_reindexed', 'page', 'index_info' ],
			'fields' => [ 'ar_page', 'value' => 'ar_timestamp' ], // QueryPage sorts by 'value'
			'conds' => [
				'ar_page = page_id',
				'ar_page = ii_page',
				'page_is_redirect = 0',
				'page_namespace = 0',
				'ii_policy IN (1, 4)'
			]
		];
	}

	function formatResult($skin, $result) {
		$title = Title::newFromID($result->ar_page);
		$dateTime = DateTime::createFromFormat('YmdHis', $result->value);
		if (!$title || !$dateTime) {
			return false;
		}

		$wikitext = NewPages::getWikitext($title, wfGetDB(DB_REPLICA));
		$intro = NewPages::getShortenedIntro($wikitext);
		$vars = [
			'url' => $title->getLocalURL(),
			'title' => $title->getText(),
			'introText' => $intro,
			'date' => date('M d, Y', strtotime($result->value ." UTC")),
			'howto' => wfMessage('howto_prefix')->text()
		];

		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);
		return $m->render('reindexed_thumb.mustache', $vars);
	}

	protected function outputResults( $out, $skin, $dbr, $res, $num, $offset ) {
		if ( $num > 0 ) {
			$html = [];

			$html[] = "<div id='reindexedpages_container'>";

			# $res might contain the whole 1,000 rows, so we read up to
			# $num [should update this to use a Pager]
			// phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall
			for ( $i = 0; $i < $num && $row = $res->fetchObject(); $i++ ) {
				$line = $this->formatResult( $skin, $row );
				if ( $line ) {
					$html[] = $this->listoutput
						? $line
						: "<li>{$line}</li>\n";
				}
			}

			# Flush the final result
			if ( $this->tryLastResult() ) {
				$row = null;
				$line = $this->formatResult( $skin, $row );
				if ( $line ) {
					$html[] = $this->listoutput
						? $line
						: "<li>{$line}</li>\n";
				}
			}

			$html[] = "</div>";

			$html = implode( '', $html );

			$out->addHTML( $html );
		}
	}

	function getPageHeader() {
		return wfMessage('reindexed-description')->text();
	}

	public function isMobileCapable() {
		return true;
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}
}
