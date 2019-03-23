<?php
/**
 * TODO: Filedoc
 */

use CirrusSearch\Maintenance\MappingConfigBuilder;
use Elastica\Document;

/**
 * TODO: Classdoc
 */
class FinnerHooks {
	// ==== SpecialSearch hook handlers for default hooks ====

	/**
	 * SpecialSearch SetupSearchEngine hook handler
	 */
	public static function onSetupSearchEngine($context, $profile, $search) {
		// TODO
		return true;
	}

	/**
	 * SpecialSearch ShowSearchHit hook handler
	 *
	 * Displays extra Titus data for search results.
	 */
	public static function onShowSearchHit(
		$context, $result, $terms, &$link, &$redirect, &$section, &$extract, &$score,
		&$size, &$date, &$related, &$html
	) {
		$extra = '';
		$titusData = $result->getTitusData();
		if ($titusData) {
			$templates = '';

			$rawTemplates = $result->getTemplates();
			if ($rawTemplates) {
				$templatesArray = array();
				foreach ($rawTemplates as $rawTemplate) {
					$template = str_replace('Template:', '', $rawTemplate);
					if (in_array(mb_strtolower($template), $titusData['bad_templates'])) {
						$templatesArray[] =
							Html::element(
								'span',
								array(
									'class' => 'finner-bad-template',
									'title' => wfMessage('finner-bad-template')
								),
								$template
							);
					} else {
						$templatesArray[] = $template;
					}
				}

				$templates = ' - Templates: ' . implode(', ', $templatesArray);
			}

			$views = $titusData['views_30_days'] . ' views last 30 days';

			$helpful =
				$titusData['helpful_percentage']
				. "% helpful ("
				. $titusData['helpful_total']
				. " votes)";

			$indexable =
				$titusData['robot_indexed'] == '1'
				? 'indexed'
				: 'not indexed';

			$readability = $titusData['readability']
				. '% readability';

			$bytes = $titusData['bytes'] . ' bytes';

			$extra =
				Html::openElement('div', array('class' => 'mw-search-result-data')) .
				"{$views} - {$helpful} - {$indexable} - {$readability} - {$bytes}{$templates}" .
				Html::closeElement('div');
		}

		$html =
			Html::openElement('li') .
			Html::openElement('div', array('class' => 'mw-search-result-heading')) .
			"{$link} {$redirect} {$section} {$fileMatch}" .
			Html::closeElement('div') .
			" {$extract}\n" .
			Html::openElement('div', array('class' => 'mw-search-result-data')) .
			"{$score}{$size} - {$date}{$related}" .
			Html::closeElement('div') .
			" {$extra}" .
			Html::closeElement('li');

		// Return false to notify SpecialSearch that we completely took over the
		// displaying of results
		return false;
	}

	/**
	 * SpecialSearch ShowSearchHitTitle hook handler
	 */
	public static function onShowSearchHitTitle(
		&$link_t, &$titleSnippet, $result, $terms, $context
	) {
		// TODO
		return true;
	}

	/**
	 * SpecialSearchCreateLink hook handler
	 */
	public static function onSpecialSearchCreateLink($title, &$params) {
		// TODO
		return true;
	}

	/**
	 * SpecialSearchGo hook handler
	 */
	public static function onSpecialSearchGo(&$title, &$term) {
		// TODO
		return true;
	}

	/**
	 * SpecialSearchNogomatch hook handler
	 */
	public static function onSpecialSearchNogomatch(&$title) {
		// TODO
		return true;
	}

	/**
	 * SpecialSearchNoResults hook handler
	 */
	public static function onSpecialSearchNoResults($term) {
		// TODO
		return true;
	}

	/**
	 * SpecialSearchPowerBox hook handler
	 */
	public static function onSpecialSearchPowerBox(&$showSections, $term, $opts) {
		// TODO
		return true;
	}

	/**
	 * SpecialSearchProfiles hook handler
	 *
	 * Adds custom search profiles.
	 */
	public static function onSpecialSearchProfiles(&$profiles) {
		// TODO: Might find some use for extra profiles in later project phase

		// $profiles['categories'] = array(
		// 	'message' => 'finnerprofile-categories',
		// 	'tooltip' => 'finnerprofile-categories-tooltip',
		// 	'namespaces' => array(NS_CATEGORY)
		// );

		// $profiles['users'] = array(
		// 	'message' => 'finnerprofile-users',
		// 	'tooltip' => 'finnerprofile-users-tooltip',
		// 	'namespaces' => array(NS_USER)
		// );

		return true;
	}

	/**
	 * SpecialSearchProfileForm hook handler
	 *
	 * Maps custom actions to existing profiles.
	 */
	public static function onSpecialSearchProfileForm(
		$context, &$form, $profile, $term, $opts
	) {
		self::setFinnerOpts($context, $opts, $context->getRequest());

		if ($profile === 'default') {
			return self::defaultForm($context, $form, $term, $opts);
		}
	}

	/**
	 * SpecialSearchResults hook handler
	 */
	public static function onSpecialSearchResults(
		$term, &$titleMatches, &$textMatches
	) {
		// TODO
		return true;
	}

	/**
	 * SpecialSearchResultsAppend hook handler
	 */
	public static function onSpecialSearchResultsAppend($context, $out, $term) {
		// TODO
		return true;
	}

	/**
	 * SpecialSearchResultsPrepend hook handler
	 */
	public static function onSpecialSearchResultsPrepend($context, $out, $term) {
		// TODO
		return true;
	}

	/**
	 * SpecialSearchSetupEngine hook handler
	 */
	public static function onSpecialSearchSetupEngine($context, $profile, $search) {
		$request = $context->getRequest();

		$extraParams = self::powerSearchExtras($request);

		if (isset($extraParams['sort']) && $extraParams['sort']) {
			$search->setSort($extraParams['sort']);
		}

		self::setSpecialSearchExtraParams($context, $request);

		return true;
	}

	// ==== SpecialSearch hook handlers for custom hooks ====

	/**
	 * SpecialSearchAddModules custom hook handler
	 */
	public static function onSpecialSearchAddModules($context, $out) {
		$out->addModuleStyles(
			array('ext.wikihow.finner.styles')
		);
		return true;
	}

	/**
	 * SpecialSearchPowerBoxOpts custom hook handler
	 */
	public static function onSpecialSearchPowerBoxOpts(&$opts) {
		self::unsetFinnerOpts($opts);
		return true;
	}

	// ==== CirrusSearch hook handlers for default hooks ====

	/**
	 * CirrusSearchMappingConfig hook handler
	 *
	 * Extends Elasticsearch index mapping to accommodate custom Titus data.
	 */
	public static function onCirrusSearchMappingConfig(
		array &$config,
		MappingConfigBuilder $context
	) {
		if (isset($config['page'])) {
			// New CirrusSearch
			$pageConfig = $config['page'];
		} else {
			// Old CirrusSearch
			$pageConfig = $config;
		}

		$titusConfig = array(
			'dynamic' => false,
			'_all' => array('enabled' => false),
			'type' => 'object',
			'properties' => array(
				'robot_indexed' => self::buildCustomTypeField($context, 'boolean'),
				'bytes' => $context->buildLongField(),
				'has_bad_template' => self::buildCustomTypeField($context, 'boolean'),
				'bad_templates' => $context->buildLowercaseKeywordField(),
				'views_30_days' => $context->buildLongField(),
				'helpful_percentage' => $context->buildLongField(),
				'helpful_total' => $context->buildLongField(false),
				'readability' => self::buildCustomTypeField($context, 'float')
			)
		);

		$pageConfig['properties']['titus'] = $titusConfig;

		if (isset($config['page'])) {
			$config['page'] = $pageConfig;
		} else {
			$config = $pageConfig;
		}

		return true;
	}

	// ==== CirrusSearch hook handlers for custom hooks ====

	/**
	 * CirrusSearchBuildDocumentFinishBatchExtras custom hook handler
	 *
	 * Adds Titus data to the given documents.
	 *
	 * TODO: Add a flag ($doTitusUpdate).
	 */
	public static function onCirrusSearchBuildDocumentFinishBatchExtras(
		&$documents,
		$skipParse,
		$skipLinks
	) {
		// TODO: Is $wgLanguageCode the best choice here,
		// or do we need the language specified in each doc...?
		global $wgLanguageCode;

		// We only want to populate Titus data when we're not parsing or linking
		if (!($skipParse && $skipLinks)) {
			return true;
		}

		$page_doc_ids = array();
		foreach ($documents as $k=>$doc) {
			$id = $doc->getId();
			if (!is_null($id)) {
				$page_doc_ids[$id] = $k;
			}
		}

		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			'titusdb2.titus_intl',
			array(
				'page_id' => 'ti_page_id',
				'robot_indexed' => 'ti_robot_policy NOT LIKE "noindex%"',
				'bYTEs' => 'ti_bytes',
				'has_bad_template' => 'ti_bad_template>0',
				'bad_templates' => 'ti_templates',
				'views_30_days' => 'ti_30day_views',
				'helpful_percentage' => 'ti_helpful_percentage',
				'helpful_total' => 'ti_helpful_total',
				'readability' => 'ti_fk_reading_ease'
			),
			array(
				'ti_language_code' => $wgLanguageCode,
				'ti_page_id' => array_keys($page_doc_ids)
			),
			__METHOD__
		);

		foreach ($res as $row) {
			$id = $row->page_id;
			$doc = $documents[$page_doc_ids[$id]];
			$doc->set('titus', array(
				'robot_indexed' => (bool) $row->robot_indexed,
				'bytes' => (int) $row->bytes,
				'has_bad_template' => (bool) $row->has_bad_template,
				'bad_templates' => array_unique(explode(',', $row->bad_templates)),
				'views_30_days' => (int) $row->views_30_days,
				'helpful_percentage' => (int) $row->helpful_percentage,
				'helpful_total' => (int) $row->helpful_total,
				'readability' => (float) $row->readability
			));
		}

		return true;
	}

	/**
	 * CirrusSearchExtraFilters custom hook handler
	 *
	 * Adds custom filters to CirrusSearch query.
	 */
	public static function onCirrusSearchExtraFilters(&$filters, &$notFilters) {
		$requestContext = RequestContext::getMain();
		$request = $requestContext->getRequest();

		$newFilters = array();
		foreach (Finner::filterRequestKeys() as $shortname=>$filter) {
			$value = $request->getVal($shortname, false);
			if ($value) {
				$newFilters[$filter] = $value;
			}
		}

		foreach ($newFilters as $filter=>$value) {
			$elasticaFilter = self::getElasticaFilter($filter, $value, $newFilters);
			if ($elasticaFilter !== false) {
				$filters[] = $elasticaFilter;
			}
		}

		return true;
	}

	/**
	 * CirrusSearchSelectSort custom hook handler
	 *
	 * Adds custom sort options to CirrusSearch query.
	 */
	public static function onCirrusSearchSelectSort($context, $sort, $query) {
		$sort = $sort ?: 'relevance_desc';

		switch ($sort) {
		case 'relevance_asc':
			$query->setSort(array('_score' => 'asc'));
			break;
		case 'relevance_desc':
			$query->setSort(array('_score' => 'desc'));
			break;
		case 'titus_bytes_asc':
			$query->setSort(array('titus.bytes' => 'asc'));
			break;
		case 'titus_bytes_desc':
			$query->setSort(array('titus.bytes' => 'desc'));
			break;
		case 'titus_views_30_days_asc':
			$query->setSort(array('titus.views_30_days' => 'asc'));
			break;
		case 'titus_views_30_days_desc':
			$query->setSort(array('titus.views_30_days' => 'desc'));
			break;
		case 'titus_helpfulness_asc':
			$query->setSort(
				array(
					'titus.helpful_percentage' => 'asc',
					'titus.helpful_total' => 'desc'
				)
			);
			break;
		case 'titus_helpfulness_desc':
			$query->setSort(
				array(
					'titus.helpful_percentage' => 'desc',
					'titus.helpful_total' => 'desc'
				)
			);
			break;
		case 'titus_readability_asc':
			$query->setSort(array('titus.readability' => 'asc'));
			break;
		case 'titus_readability_desc':
			$query->setSort(array('titus.readability' => 'desc'));
			break;
		default:
			return false;
		}

		return true;
	}

	// ==== Miscellaneous methods ====

	/**
	 * Hacky addition to MappingConfigBuilder to provide custom field mapping.
	 */
	public static function buildCustomTypeField(
		MappingConfigBuilder $mappingConfigBuilder,
		$type,
		$index=true
	) {
		$config = $mappingConfigBuilder->buildLongField($index);
		$config['type'] = $type;
		return $config;
	}

	/**
	 * Adds filtering and sorting options to the default search profile.
	 */
	public static function defaultForm($context, &$form, $term, $opts) {
		$showSections = array();
		self::sortingBox($showSections, $opts);
		self::filteringBox($showSections, $opts);

		self::unsetFinnerOpts($opts);

		$hidden = '';
		unset( $opts['redirs'] );

		foreach ( $opts as $key => $value ) {
			$hidden .= Html::hidden( $key, $value );
		}

		$form .= Xml::openElement(
				'fieldset',
				array('id' => 'mw-searchoptions', 'style' => 'margin:0em;')
			) .
			Xml::element('legend', null, $context->msg('powersearch-legend')->text()) .
			self::getSectionsHtml($showSections) .
			$hidden .
			Xml::closeElement('fieldset');

		return true;
	}

	/**
	 * Adds the HTML for a filters section.
	 */
	public static function filteringBox(&$showSections, $opts) {
		$filters = Finner::filters();
		$filters['templates']['filter_options']['templates_specific']['extras']['value'] =
			$opts['templates_specific'] ?: '';

		// Function to determine which checkbox/radio button is active.
		// TODO: This has become really ugly. Simplify at some point.
		$filterActiveFn = function ($filter, $filterConfig) use ($opts) {
			if (isset($opts[$filter])
				&& !isset($filterConfig['filter_options'][$filter]['extras'])
			) {
				$active = (bool) $opts[$filter];
				if (!$active && $filterConfig['type'] === 'checkbox') {
					$others = array_keys($filterConfig['filter_options']);
					foreach ($others as $other) {
						$other_active = $opts[$other];
						if (isset($other_active) && (bool) $other_active) {
							return $active;
						}
					}
					return true;
				}
				return $active;
			} elseif (isset($opts[$filterConfig['elementname']])) {
				$fr = Finner::filterFromShortname($opts[$filterConfig['elementname']]);
				return $fr['type'] === 'filter' && $fr['filter'] === $filter;
			} else {
				return $filterConfig['filter_options'][$filter]['active'];
			}
		};

		foreach ($filters as $filterGroup=>$filterConfig) {
			$showSections[$filterGroup] =
				self::searchBoxSectionLabel(
					'finner-search-box-label',
					$filterConfig['label']
				) .
				self::searchBoxFilterOptions(
					$filterGroup,
					$filterConfig,
					$filterActiveFn
				);
		}
	}

	/**
	 * Generates Elastica filters from user-provided options.
	 */
	protected static function getElasticaFilter($filter, $value, $all=false) {
		switch ($filter) {
		case 'robot_indexed_yes':
			if (!isset($all['robot_indexed_no'])) {
				return new \Elastica\Filter\Term(array('titus.robot_indexed' => true));
			}
			break;
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
			case 'templates_any': // Ignored
			default:
				break;
			}
		case 'templates_specific_extras': // Handled by the 'templates_specific' case
		default:
			break;
		}
		return false;
	}

	/**
	 * Splits up custom sections with a divider.
	 */
	public static function getSectionsHtml($showSections) {
		return
			implode(
				Xml::element(
					'div', array('class' => 'divider'), '', false
				),
				$showSections
			);
	}

	/**
	 * Extract "power search" custom settings from the request object,
	 * returning an associative array of settings.
	 */
	public static function powerSearchExtras(&$request) {
		$arr = array();

		$sortStrategy = $request->getVal('ss');
		$sortStrategies = array_keys(Finner::sortStrategies());
		$sortOrder = $request->getVal('so');
		$sortOrders = array_keys(Finner::sortOrders());

		if (in_array($sortStrategy, $sortStrategies)
			&& in_array($sortOrder, $sortOrders)
		) {
			$arr['sort'] = $sortStrategy . '_' . $sortOrder;
		}

		return $arr;
	}

	/**
	 * Generates HTML for options in a dropdown element.
	 */
	public static function searchBoxDropDown(
		$options,
		$shortname,
		$defaultOption=false
	) {
		$items = array();
		foreach ($options as $option=>$optionInfo) {
			$items[$option] .=
				Xml::option(
					$optionInfo,
					$option,
					$defaultOption && $option === $defaultOption
				);
		}

		return Xml::tags(
			'select',
			array(
				'class' => 'finner-search-drop-down',
				'name' => $shortname
			),
			implode("\n", $items)
		);
	}

	/**
	 * Generates the HTML for filter options within the filter section.
	 */
	public static function searchBoxFilterOptions(
		$filterGroup,
		$filterConfig,
		$filterActiveFn
	) {
		$items = array();

		foreach ($filterConfig['filter_options'] as $filter=>$filterInfo) {
			$isActive = $filterActiveFn($filter, $filterConfig);

			$groupShort = $filterConfig['shortname'];
			$filterShort = $filterInfo['shortname'];

			if ($filterConfig['type'] === 'radio') {
				$items[$filter] .= Xml::radioLabel(
					$filterInfo['name'],
					$groupShort,
					$groupShort . $filterShort,
					"finner-search-$groupShort$filterShort",
					$isActive,
					array('class' => 'finner-search-radio')
				);
			} elseif ($filterConfig['type'] === 'checkbox') {
				$items[$filter] .= Xml::checkLabel(
					$filterInfo['name'],
					$groupShort . $filterShort,
					"finner-search-$groupShort$filterShort",
					$isActive,
					array('class' => 'finner-search-checkbox')
				);
			}

			$extras = $filterInfo['extras'];

			if (isset($extras)
				&& isset($extras['subtype'])
				&& $extras['subtype'] === 'input'
			) {
				$items[$filter] .= Xml::input(
					$groupShort . $filterShort . $extras['shortname'],
					$extras['size'] ?: 20,
					$extras['value'] ?: false,
					array('class' => 'finner-search-input-field')
				);
			}
		}

		return implode("\n", $items);
	}

	/**
	 * Creates an option label.
	 */
	public static function searchBoxSectionLabel($class, $text) {
		return Xml::tags('span', array('class' => $class), $text);
	}

	/**
	 * Adds the HTML for the sorting section.
	 */
	public static function sortingBox(&$showSections, $opts) {
		$defaultSortStrategy = $opts['sortstrategy'] ?: 'relevance';
		$defaultSortOrder = $opts['sortorder'] ?: 'desc';

		$showSections['sort'] =
			self::searchBoxSectionLabel('finner-search-box-label', 'Sort by:') .
			self::searchBoxDropDown(
				Finner::sortStrategies(),
				'ss',
				$defaultSortStrategy
			) .
			self::searchBoxDropDown(
				Finner::sortOrders(),
				'so',
				$defaultSortOrder
			);
	}

	/**
	 * Sets custom options used by Finner.
	 */
	public static function setFinnerOpts($context, &$opts, $request) {
		$opts['context'] = get_class($context);
		$opts['sortstrategy'] = $request->getVal('ss', 'relevance');
		$opts['sortorder'] = $request->getVal('so', 'desc');
		$opts['robot_indexed_yes'] = $request->getVal('ffriy', false);
		$opts['robot_indexed_no'] = $request->getVal('ffrin', false);
		$opts['templates'] = $request->getVal('fft', 'ffta');
		$opts['templates_specific'] = $request->getVal('fftsi', '');
	}

	/**
	 * Adds custom options used by Finner to CirrusSearch's extraParams
	 */
	public static function setSpecialSearchExtraParams($context, $request) {
		$arr = array(
			'ss' => $request->getVal('ss', 'relevance'),
			'so' => $request->getVal('so', 'desc'),
			'ffriy' => $request->getVal('ffriy', false),
			'ffrin' => $request->getVal('ffrin', false),
			'fft' => $request->getVal('fft', 'ffta'),
			'fftsi' => $request->getVal('fftsi', '')
		);

		foreach ($arr as $k=>$v) {
			$context->setExtraParam($k, $v);
		}
	}

	/**
	 * Unset custom options used by Finner before passing control back to
	 * the CirrusSearch Searcher.
	 */
	public static function unsetFinnerOpts(&$opts) {
		unset($opts['context']);
		unset($opts['sortstrategy']);
		unset($opts['sortorder']);
		unset($opts['robot_indexed_yes']);
		unset($opts['robot_indexed_no']);
		unset($opts['templates']);
		unset($opts['templates_specific']);
	}
}

