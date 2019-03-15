<?php

class ApiTitleSearch extends ApiBase {

	public function __construct($main, $action) {
		parent::__construct($main, $action);
	}

	function execute() {
		global $wgLanguageCode;

		// Get the parameters
		$params = $this->extractRequestParams();

		// Make it so that Varnish can cache these requests for 24 hours
		$this->getMain()->setCacheMaxAge( 24 * 60 * 60 );
		$this->getMain()->setCacheMode( 'public' );

		$result = $this->getResult();

		$format = $this->getRequest()->getVal('format');
		if (!in_array($format, ['json'])) {
			$result->addValue( null, 'error',
				'We only allow JSON-encoded API output for this method. See ' .
				'file ' . __FILE__ . ':' . __LINE__ . ' for details. Or, use format=json.');
			return true;
		}

		$q = $params['q'];
		$numResults = $params['numResults'];
		$safeSearch = $params['safeSearch'];


		if ($safeSearch) {
			if (BadWordFilter::hasBadWord($q, BadWordFilter::TYPE_ALEXA, $wgLanguageCode)) {
				return $result->addValue(null,'data', $this->formatTitles([]));;
			}
		}

		$search = new LSearch();
		$titles = $search->externalSearchResultTitles($q, 0, $numResults, 0, LSearch::SEARCH_INTERNAL);

		if ($safeSearch) {
			$titles = TitleFilters::filterExplicitAidsForAlexa($titles, $wgLanguageCode);
			$titles = TitleFilters::filterTopLevelCategories($titles, [CAT_RELATIONSHIPS]);
			$titles = TitleFilters::filterByBadWords($titles, BadWordFilter::TYPE_ALEXA, $wgLanguageCode);
		}

		$titles = TitleFilters::filterByNamespace($titles, [NS_MAIN]);
		$titles = TitleFilters::filterByPageTitle($titles, [wfMessage('mainpage')->text()]);
		$titles = TitleFilters::removeRedirects($titles);

		$result->addValue(null,'data', $this->formatTitles($titles));

		return true;

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
			'q' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'numResults' => [
				ApiBase :: PARAM_DFLT => 10,
				ApiBase :: PARAM_TYPE => 'integer'
			],
			'safeSearch' => [
				ApiBase :: PARAM_DFLT => 1,
				ApiBase :: PARAM_TYPE => 'integer'
			],
		);
	}

	public function getParamDescription() {
		return array(
			'aid' => 'The article id of the title',
			'numResults' => 'Up to this number of results. Defaults to 10',
			'safeSearch' => 'Rejects queries with known bad words. On by default',
		);
	}

	public function getDescription() {
		return 'An API extension to return article text and media meta info. Only works for main namespaced article ids';
	}


	public function getExamples() {
		return array(
			'api.php?action=titlesearch&q=dogs&format=json'
		);
	}

	public function getHelpUrls() {
		return '';
	}

	public function getVersion() {
		return '1.0.0';
	}
}
