<?php

class ApiSmsListing extends ApiBase {
	public function __construct($main, $action) {
		global $wgHooks;

		$wgHooks['CirrusSearchExtraFilters'][] = ['ApiSmsListing::onCirrusSearchExtraFilters'];
		parent::__construct($main, $action);
	}

	function execute() {
		// Get the parameters
		$params = $this->extractRequestParams();

		$result = $this->getResult();
		$search = $params['text'];

		if ($params['skipcat'] != "1") {
			$searchEngine = SearchEngine::create();
			$titleMatches = $searchEngine->searchTitle($search);
			if ( !($titleMatches instanceof SearchResultTooMany) ) {
				$textMatches = $searchEngine->searchText($search);
			}
			$titleMatchesNum = $titleMatches ? $titleMatches->numRows() : 0;
			$textMatchesNum = $textMatches ? $textMatches->numRows() : 0;

			if ( $titleMatchesNum + $textMatchesNum > 0 ) {
				if ( $titleMatchesNum > 0 ) {
					while ($resultObj = $titleMatches->next()) {
						$title = $resultObj->getTitle();
						$summaryText = self::getFormattedSummaryFromTitle($title);
						$result->addValue(null, 'title', wfMessage("howto", $title->getFullText())->text());
						$result->addValue(null, 'url', $title->getFullUrl());

						if ( !empty($summaryText) ) {
							$result->addValue(null, 'result', 'summary');
							$result->addValue(null, 'summary', $summaryText);
							return true;
						} else {
							$result->addValue(null, 'result', 'no summary');
							return true;
						}
					}
				}
				if ( $textMatchesNum > 0 ) {
					while ($resultObj = $textMatches->next()) {
						$title = $resultObj->getTitle();
						$summaryText = self::getFormattedSummaryFromTitle($title);
						$result->addValue(null, 'title', wfMessage("howto", $title->getFullText())->text());
						$result->addValue(null, 'url', $title->getFullUrl());

						if ( !empty($summaryText) ) {
							$result->addValue(null, 'result', 'summary');
							$result->addValue(null, 'summary', $summaryText);
							return true;
						} else {
							$result->addValue(null, 'result', 'no summary');
							return true;
						}
					}
				}
				return true;
			}
		}

		$l = new LSearch();
		$hits = $l->externalSearchResultTitles($search, 0, 1, 0, Lsearch::SEARCH_INTERNAL);
		if ( sizeof($hits) > 0 ) {
			$title = $hits[0];

			$summaryText = self::getFormattedSummaryFromTitle($title);
			$result->addValue(null, 'title', wfMessage("howto", $title->getFullText())->text());
			$result->addValue(null, 'url', $title->getFullUrl());

			if (!empty($summaryText)) {
				$result->addValue(null, 'result', 'summary');
				$result->addValue(null, 'summary', $summaryText);
			} else {
				$result->addValue(null, 'result', 'no summary');
			}
			return true;
		}

		$result->addValue(null, 'result', 'no match');
		return true;
	}

	private static function getFormattedSummaryFromTitle($title) {
		$r = Revision::newFromTitle($title);
		$wikitext = ContentHandler::getContentText($r->getContent());
		$summaryText = Wikitext::getSummarizedSection($wikitext);
		if ( !empty($summaryText) ) {
			$summaryText = preg_replace('@^\s*==\s*([^=]+)\s*==\s*$@m', '', $summaryText);
			$summaryText = ReadArticleModel::flatten($summaryText);
			$itemsToDelete = explode("\n", wfMessage('Sms_removal')->text());
			foreach ($itemsToDelete as $item) {
				$summaryText = str_replace($item, '', $summaryText);
				$summaryText = str_replace(htmlentities($item), '', $summaryText);
			}

			//don't want double new lines
			$summaryText = str_replace("\n\n", "\n", $summaryText);
			return $summaryText;
		} else {
			return null;
		}
	}

	public function getAllowedParams() {
		return array(
			'text' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'skipcat' => [
				ApiBase::PARAM_TYPE => 'string'
			]
		);
	}

	public function getParamDescription() {
		return array(
			'text' => 'Contents from sms for which to search',
			'skipcat' => 'Whether or not to skip the special category'
		);
	}

	public function getDescription() {
		return 'An API extension to return sms response info';
	}

	public function getPossibleErrors() {
		return parent::getPossibleErrors();
	}

	public function getExamples() {
		return array(
			'api.php?action=smslisting&text=fix a tire'
		);
	}

	public function getHelpUrls() {
		return '';
	}

	public function getVersion() {
		return '0.0.1';
	}

	public static function onCirrusSearchExtraFilters(&$filters, &$notFilters) {
		$requestContext = RequestContext::getMain();
		$request = $requestContext->getRequest();

		if ($request->getVal("skipcat") != "1") {
			$newFilters = [
				"templates" => "ffts",
				"templates_specific_extras" => "smsproject"
			];

			foreach ($newFilters as $filter => $value) {
				$elasticaFilter = self::getElasticaFilter($filter, $value, $newFilters);
				if ( $elasticaFilter !== false ) {
					$filters[] = $elasticaFilter;
				}
			}
		}

		return true;
	}

	private static function getElasticaFilter($filter, $value, $all=false) {
		switch ($filter) {
			case 'robot_indexed_no':
				if (!isset($all['robot_indexed_yes'])) {
					return new \Elastica\Filter\Term(array('titus.robot_indexed' => false));
				}
				break;
			case 'templates':
				switch (Finner::filterFromShortname($value)['filter']) {
					case 'templates_bad':
						return new \Elastica\Filter\Term(array('titus.has_bad_template' => true));
						break;
					case 'templates_specific':
						$q = $all['templates_specific_extras'];
						if (isset($q)) {
							$split_re = '/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/';
							$qwords = preg_split($split_re, $q, -1, PREG_SPLIT_NO_EMPTY);
							foreach ($qwords as $qword) {
								// TODO: Do we need both with and without 'Template:' prefix?
								$qwords[] = 'Template:' . $qword;
							}

							$queryFilters = array();
							foreach ($qwords as $qword) {
								$match = new \Elastica\Query\Match();
								$match->setFieldQuery('template', $qword);
								$queryFilters[] = new \Elastica\Filter\Query($match);
							}

							$orFilter = new \Elastica\Filter\BoolOr();
							$orFilter->setFilters($queryFilters);
							return $orFilter;
						}
						break;
					default:
						break;
				}
			case 'templates_specific_extras': // Handled by the 'templates_specific' case
			default:
				break;
		}
		return false;
	}
}
