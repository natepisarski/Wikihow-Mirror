<?php

class ApiTitleFeed extends ApiBase {

	const MAX_RESULTS = 6;
	const DEFAULT_NUM_RESULTS = 6;
	const TYPE_TRENDING  = 'trending';
	const TYPE_COAUTHOR = 'coauthor';

	public function __construct($main, $action) {
		parent::__construct($main, $action);
	}

	function execute() {
		// Get the parameters
		$params = $this->extractRequestParams();

		// Make it so that Varnish can cache these requests for 7 days
		$this->getMain()->setCacheMaxAge( 7 * 24 * 60 * 60 );
		$this->getMain()->setCacheMode( 'public' );

		$result = $this->getResult();


		$type = $params['type'];
		$numResults = $params['numResults'] ?: self::DEFAULT_NUM_RESULTS;
		if ($numResults > self::MAX_RESULTS) {
			$numResults = self::MAX_RESULTS;
		}

		$titles = $this->getTitles($type, $numResults);

		$result->addValue(null,'data', $this->formatTitles($titles));

		return true;

	}



	protected function getTitles($type, $num) {
		$titles = [];

		if ($type == self::TYPE_COAUTHOR) {
			$key = WikihowMobileHomepage::COAUTHOR_LIST;
		} elseif ($type == self::TYPE_TRENDING) {
			$key = WikihowMobileHomepage::POPULAR_LIST;
		} else {
			// Unsupported type. Don't return any titles
			return $titles;
		}

		$ids = explode("\n", ConfigStorage::dbGetConfig($key));
		$count = 0;
		foreach($ids as $id) {
			$title = Title::newFromID($id);
			if ($title && $title->exists()) {
				$titles []= $title;
				if(++$count >= $num) break;
			}
		}

		return $titles;
	}

	/**
	 * @param Title[] $titles
	 * @return array
	 */
	protected function formatTitles($titles) {
		global $wgLanguageCode;

		$formatted = [];
		foreach ($titles as $t) {
			$formatted []= [
				'id' => $t->getArticleID(),
				'titleText' => $t->getText(),
				'url' => Misc::getLangBaseURL($wgLanguageCode) . '/' . $t->getPartialURL(),
			];
		}
		return $formatted;
	}

	public function getAllowedParams() {
		return array(
			'type' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'numResults' => [
				ApiBase :: PARAM_DFLT => self::DEFAULT_NUM_RESULTS,
				ApiBase :: PARAM_TYPE => 'integer'
			]
		);
	}

	public function getParamDescription() {
		return array(
			'type' => 'The type of title feed - either "trending" or "coauthor"',
			'numResults' => 'Up to this number of results. Defaults to 5. Max of 6 results'
		);
	}

	public function getDescription() {
		return 'An API extension to return title meta info of certain title feeds';
	}


	public function getExamples() {
		return array(
			'api.php?action=titlefeed&type=trending&format=json',
			'api.php?action=titlefeed&type=coauthor&format=json'
		);
	}

	public function getHelpUrls() {
		return '';
	}

	public function getVersion() {
		return '1.0.0';
	}
}
