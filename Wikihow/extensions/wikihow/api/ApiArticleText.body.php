<?php

class ApiArticleText extends ApiBase {

	public function __construct($main, $action) {
		parent::__construct($main, $action);
	}

	function execute() {
		// Get the parameters
		$params = $this->extractRequestParams();

		// Make it so that Varnish can cache these requests for 30 days. Surrogate key headers (see below)
		// ensure that caches are cleared as articles are edited/updated
		$this->getMain()->setCacheMaxAge( 30 * 24 * 60 * 60 );
		$this->getMain()->setCacheMode( 'public' );

		$result = $this->getResult();

		$format = $this->getRequest()->getVal('format');
		if (!in_array($format, ['json']) && !in_array($format, ['jsonfm'])) {
			$result->addValue( null, 'error',
				'We only allow JSON-encoded API output for this method. See ' .
				'file ' . __FILE__ . ':' . __LINE__ . ' for details. Or, use format=json.');
			return true;
		}

		$aid = $params['aid'];
		$numMethods = $params['numMethods'];

		try {
			// Set the surrogate key response header so api pages can be cleared when articles are edited/purged
			PageHooks::addSurrogateKeyHeaders(
				$this->getOutput(),
				Title::newFromId($aid),
				$this->getRequest());

			$articleText = ArticleText::newFromArticleId($aid, $numMethods);

			if (!$articleText->isValid()) {
				throw new Exception('Not a valid (parseable) article.');
			}

			$result->addValue(null, 'data', $articleText);
		} catch(Exception $e) {
			$result->addValue(null, 'error', $e->getMessage());
		}

		return true;

	}

	public function getAllowedParams() {
		return array(
			'aid' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'numMethods' => [
				ApiBase :: PARAM_DFLT => 1,
				ApiBase :: PARAM_TYPE => 'integer'
			]
		);
	}

	public function getParamDescription() {
		return array(
			'aid' => 'The article id of the title',
			'numMethods' => 'Number of methods to return. Defaults to 1'
		);
	}

	public function getDescription() {
		return 'An API extension to return article text and media meta info. Only works for main namespaced article ids';
	}



	public function getExamples() {
		return array(
			'api.php?action=articletext&aid=2053&numMethods=1&format=json'
		);
	}

	public function getHelpUrls() {
		return '';
	}

	public function getVersion() {
		return '1.0.0';
	}
}
