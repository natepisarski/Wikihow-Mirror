<?php

/**
 * Lists articles that became indexable recently
 */
class ReindexedPages extends QueryPage {

	function __construct() {
		parent::__construct('ReindexedPages');
	}

	public function execute($par) {
		list($this->limit, $this->offset) = $this->getRequest()->getLimitOffset(250, '');
		parent::execute($par);
		$this->getOutput()->setRobotPolicy('noindex,follow');
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
		$titleStr = htmlspecialchars($title->getText());
		$url = htmlspecialchars($title->getLinkURL());
		$time = $dateTime->format('F jS Y, H:i');
		return "<a href='$url' title='$titleStr'>$titleStr</a> ($time)";
	}

	function getPageHeader() {
		return wfMessage('reindexed-description')->text();
	}

}
