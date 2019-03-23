<?php

namespace WVL;

if (!defined('MEDIAWIKI')) {
	die();
}

use WVL\Util;

/**
 * TODO: classdoc

CREATE TABLE IF NOT EXISTS `wikivisual_library_page` (
  `wvlp_page_id` INT(11) UNSIGNED NOT NULL DEFAULT 0, -- Article ID

  `wvlp_page_title` VARBINARY(255) NOT NULL DEFAULT '', -- Article title
  `wvlp_timestamp` VARBINARY(14) NOT NULL DEFAULT '', -- MW timestamp of first upload
  `wvlp_catinfo` INT(11) UNSIGNED NOT NULL DEFAULT 0, -- Category bitmask
  `wvlp_views` INT(11) UNSIGNED NOT NULL DEFAULT 0, -- Article views

  PRIMARY KEY (`wvlp_page_id`),
  KEY `title` (`wvlp_page_title`),
  KEY `timestamp` (`wvlp_timestamp`),
  KEY `catinfo` (`wvlp_catinfo`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE IF NOT EXISTS `wikivisual_library_asset` (
  `wvla_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, -- Internal ID
  `wvla_page_id` INT(11) UNSIGNED DEFAULT NULL, -- Page ID (fk into wvlp)

  `wvla_asset_type` INT(11) UNSIGNED NOT NULL DEFAULT 0, -- Img? Vid?

  `wvla_title` VARBINARY(255) NOT NULL DEFAULT '', -- Asset title
  `wvla_creator` VARBINARY(32) DEFAULT NULL, -- Creator of asset
  `wvla_timestamp` VARBINARY(14) NOT NULL DEFAULT '', -- MW timestamp of upload
  `wvla_on_article` TINYINT(1) NOT NULL DEFAULT 0, -- Is on article?

  `wvla_sha1` VARBINARY(64) DEFAULT NULL, -- Base-16 SHA1

  `wvla_creator_id` int(11) unsigned,

  PRIMARY KEY (`wvla_id`),
  KEY `page_id` (`wvla_page_id`),
  KEY `asset_type` (`wvla_asset_type`),
  UNIQUE KEY `title` (`wvla_title`),
  KEY `creator` (`wvla_creator`),
  KEY `timestamp` (`wvla_timestamp`),
  UNIQUE KEY `sha1` (`wvla_sha1`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE `wikivisual_library_creator` (
  `wvlc_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wvlc_name` VARBINARY(32) DEFAULT NULL,
  `wvlc_eligible` TINYINT(1) NOT NULL DEFAULT 0,
  `wvlc_type` VARBINARY(14) NOT NULL DEFAULT '',

  PRIMARY KEY (`wvlc_id`),
  KEY `wvlc_eligible` (`wvlc_eligible`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

 */
class Model {

	/************************************************
	 * Public methods                               *
	 ************************************************/

	/**
	 * Get a list of all asset creators and their asset counts by type.
	 *
	 * Orphaned assets (referring to assets without an assigned creator) are
	 * included, while pageless assets (referring to assets without an assigned
	 * article) are not.
	 *
	 * @return array a list of entries, where each entry is an associative array
	 *   containing the keys 'creator', 'imageCount' and 'videoCount', for the
	 *   creator's name, number of image assets and number of video assets,
	 *   respectively. 'creator' is NULL for orphaned assets.
	 *
	 * @see WVL\Model::getAssignedAssetsCount()
	 * @see WVL\Model::getOrphanedAssetsCount()
	 */
	public static function getAllCreators() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			[Util::DB_TABLE_ASSETS, Util::DB_TABLE_CREATORS],
			[
				'creator' => 'wvla_creator',
				'imageCount' => 'SUM(IF(wvla_asset_type=' . $dbr->addQuotes(Util::WVL_IMAGE) . ', 1, 0))',
				'videoCount' => 'SUM(IF(wvla_asset_type=' . $dbr->addQuotes(Util::WVL_VIDEO) . ', 1, 0))',
				'creatorType' => 'wvlc_type',
				'creatorId' => 'wvlc_id',
			],
			[
				'wvla_creator_id = wvlc_id',
				'wvla_page_id IS NOT NULL',
				'wvlc_eligible' => 1, //only eligible/still working artists
			],
			__METHOD__,
			[
				'GROUP BY' => 'wvla_creator',
				'ORDER BY' => 'wvlc_type ASC, LOWER(wvla_creator) ASC'
			]
		);

		$creators = [];

		foreach ($res as $row) {
			$creators[] = get_object_vars($row);
		}

		return $creators;
	}

	public static function getActiveCreators() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			Util::DB_TABLE_CREATORS,
			[
				'creator' => 'wvlc_name',
				'creatorType' => 'wvlc_type',
				'creatorId' => 'wvlc_id',
			],
			[
				'wvlc_eligible' => 1, //only eligible/still working artists
			],
			__METHOD__,
			[
				'ORDER BY' => 'wvlc_type ASC, LOWER(wvlc_name) ASC'
			]
		);

		$creators = [];

		foreach ($res as $row) {
			$creators[] = get_object_vars($row);
		}

		return $creators;
	}

	/**
	 * Get a list of names of top categories.
	 *
	 * Note that only the values are returned, and the internal key representation
	 * used for $wgCategoryNames is not preserved.
	 *
	 * @todo maybe move this to WVL\Util?
	 *
	 * @return array the list of available top categories.
	 *
	 * @see $wgCategoryNames
	 */
	public static function getAllTopcats() {
		global $wgCategoryNames;

		return array_values($wgCategoryNames);
	}

	/**
	 * Count assets by type for a given article.
	 *
	 * @param int $aid the article ID.
	 *
	 * @return array an associative array of asset types mapped to counts.
	 */
	public static function getAssetCounts($aid) {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			['wvla' => Util::DB_TABLE_ASSETS],
			[
				'asset_type' => 'wvla_asset_type'
			],
			['wvla_page_id' => $aid],
			__METHOD__
		);

		$counts = [
			'images' => 0,
			'videos' => 0
		];

		foreach ($res as $row) {
			if ($row->asset_type == Util::WVL_IMAGE) {
				$counts['images'] += 1;
			} elseif ($row->asset_type == Util::WVL_VIDEO) {
				$counts['videos'] += 1;
			}
		}

		return $counts;
	}

	/**
	 * Get data about wikiVisual assets.
	 *
	 * Constructs and runs a database query to fetch information about assets
	 * indexed in the wikiVisual Library based on a provided associative array
	 * of optional parameters to filter and manipulate desired results.
	 *
	 * Pageless assets are currently not supported.
	 *
	 * TODO: Flesh out description.
	 *
	 * @param array $params an associative array of optional parameters, mapping
	 *   a parameter type to its values.
	 *   Valid parameter types are:
	 *     creator:   Restricts results to assets assigned to the given creator
	 *     topcat:    Restricts to assets linked to pages within the given top
	 *                category. Assumes the topcat name is provided, not the
	 *                internal integer representation.
	 *     keyword:   Restricts to assets linked to pages whose title contains
	 *                the given string (case insensitive).
	 *     dateLower: Restricts to assets whose upload or processing timestamp
	 *                is greater than the lower bound provided.
	 *     dateUpper: Restricts to assets whose upload or processing timestamp
	 *                is lower than the upper bound provided.
	 *     sortBy:    The sorting strategy to use. See Model::getSortStrategy()
	 *                documentation for details.
	 *     sortOrder: Determines ascending or descending sort with the strings
	 *                'asc' and 'desc', respectively.
	 *     randSeed:  Optionally provides a custom seed when the random sort type
	 *                is set.
	 *     pageless:  Determines whether to 'include', 'exclude' or 'only' provide
	 *                pageless assets. NOTE: currently ignored. Only assets linked
	 *                to pages are currently supported.
	 *     assetType: Restrict assets to the given asset type. Value may be one
	 *                of 'image', 'video' and 'all'.
	 *     perPage:   Restrict results to the provided number of pages. Note that
	 *                each page may have an arbitrary number of assets.
	 *     page:      Page number for pagination. The offset for the results is
	 *                computed based on the 'perPage' and 'page' parameters.
	 *
	 * @return array the asset data. TODO: Explain structure of returned data.
	 *
	 * @see WVL\Model::getCreatorStrategy()
	 * @see WVL\Model::getTopcatStrategy()
	 * @see WVL\Model::getKeywordStrategy()
	 * @see WVL\Model::getPartialUrlStrategy()
	 * @see WVL\Model::getTimestampStrategy()
	 * @see WVL\Model::getSortStrategy()
	 * @see WVL\Model::getPagelessStrategy()
	 * @see WVL\Model::getAssetTypeStrategy()
	 * @see WVL\Model::getPagerSizeStrategy()
	 * @see WVL\Model::executeQuery()
	 * @see WVL\Model::consolidatePartialExecutionStrategies()
	 * @see WVL\Controller::fetchAssets()
	 * @see WVL\Util::PAGER_SIZE_MIN
	 * @see WVL\Util::PAGER_SIZE_MAX
	 */
	public static function getAssetData($params) {
		$strategies = [
			self::getCreatorStrategy($params['creator']),
			self::getTopcatStrategy($params['topcat']),
			self::getKeywordStrategy($params['keyword']),
			self::getPartialUrlStrategy($params['partialUrl']),
			self::getTimestampStrategy($params['dateLower'], $params['dateUpper']),
			self::getSortStrategy($params['sortBy'], $params['sortOrder'], $params['randSeed']),
			self::getPagelessStrategy($params['pageless']),
			self::getAssetTypeStrategy($params['assetType']),
			self::getPagerSizeStrategy($params['perPage'], $params['page'])
		];

		$partialExecutionStrategy = self::consolidatePartialExecutionStrategies($strategies);

		$results = self::executeQuery($partialExecutionStrategy, true);

		return $results;
	}

	/**
	 * Get counts for non-orphaned assets by type.
	 *
	 * Note that pageless assets are not counted.
	 *
	 * For the purposes of this tool, orphaned assets are assets lacking an
	 * assigned creator, while pageless assets are those lacking an asssociated
	 * article.
	 *
	 * @return array an associative array mapping "friendly" names for asset types
	 *   (e.g. 'images', 'videos') to their respective counts. Non-standard asset
	 *   types use the internal asset type representation (typically an integer) as
	 *   their key.
	 *
	 * @see WVL\Model::getAllCreators()
	 * @see WVL\Model::getOrphanedAssetsCount()
	 */
	public static function getAssignedAssetsCount() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			[Util::DB_TABLE_ASSETS],
			[
				'asset_type' => 'wvla_asset_type',
				'count' => 'COUNT(*)'
			],
			[
				'wvla_creator IS NOT NULL',
				'wvla_page_id IS NOT NULL'
			],
			__METHOD__,
			['GROUP BY' => 'wvla_asset_type']
		);

		$countInfo = [];

		foreach ($res as $row) {
			if ($row->asset_type == Util::WVL_IMAGE) {
				$countInfo['images'] = $row->count;
			} elseif ($row->asset_type == Util::WVL_VIDEO) {
				$countInfo['videos'] = $row->count;
			} else {
				$countInfo[$row->asset_type] = $row->count;
			}
		}

		return $countInfo;
	}

	/**
	 * Get counts for orphaned assets by type.
	 *
	 * Note that pageless assets are not counted.
	 *
	 * For the purposes of this tool, orphaned assets are assets lacking an
	 * assigned creator, while pageless assets are those lacking an asssociated
	 * article.
	 *
	 * @return array an associative array mapping "friendly" names for asset types
	 *   (e.g. 'images', 'videos') to their respective counts. Non-standard asset
	 *   types use the internal asset type representation (typically an integer) as
	 *   their key.
	 *
	 * @see WVL\Model::getAllCreators()
	 * @see WVL\Model::getAssignedAssetsCount()
	 */
	public static function getOrphanedAssetsCount() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			[Util::DB_TABLE_ASSETS],
			[
				'asset_type' => 'wvla_asset_type',
				'count' => 'COUNT(*)'
			],
			[
				'wvla_creator IS NULL',
				'wvla_page_id IS NOT NULL'
			],
			__METHOD__,
			['GROUP BY' => 'wvla_asset_type']
		);

		$countInfo = [];

		foreach ($res as $row) {
			if ($row->asset_type == Util::WVL_IMAGE) {
				$countInfo['images'] = $row->count;
			} elseif ($row->asset_type == Util::WVL_VIDEO) {
				$countInfo['videos'] = $row->count;
			} else {
				$countInfo[$row->asset_type] = $row->count;
			}
		}

		return $countInfo;
	}

	/**
	 * Get the internal representation of a top category based on its name.
	 *
	 * @todo maybe move this to WVL\Util?
	 *
	 * @param string $topcat the category name.
	 *
	 * @return int the internal integer representation of the category.
	 */
	public static function getTopcatValue($topcat) {
		global $wgCategoryNames;

		return array_flip($wgCategoryNames)[$topcat];
	}

	/************************************************
	 * Protected methods                            *
	 ************************************************/

	/**
	 * Execute a query through the provided query execution strategies.
	 *
	 * TODO: funcdoc
	 *
	 * @param array $partialExecutionStrategy an associative array mapping
	 *   query fragments to MW-style select() arguments.
	 *   Valid keys are 'sql_conds', 'sql_opts' and 'sql_joins', for the
	 *   WHERE-clause, miscellaneous options and JOINs, respectively.
	 *   Tables and fields are not provided by the caller.
	 * @param bool $countFoundRows if true, count the total number of rows
	 *   matching the WHERE conditions had the LIMIT option not been provided.
	 *
	 * @return array an associative array of data pertaining to the executed
	 *   query:
	 *     sqlText: The constructed SQL query.
	 *     count:   The number of rows matching the WHERE conditions (only if
	 *              $countFoundRows is set).
	 *     result:  The results returned from executing the query, in the form
	 *              of an array of rows, where each row is an associative array
	 *              mapping the field descriptor to its values. See
	 *              Model::getDefaultSelectFields() for detailed descriptions
	 *              of the fields used.
	 *
	 * @see WVL\Model::getDefaultSelectTables()
	 * @see WVL\Model::getDefaultSelectFields()
	 * @see WVL\Model::getDefaultSelectConds()
	 * @see WVL\Model::getDefaultSelectOpts()
	 * @see WVL\Model::getDefaultSelectJoins()
	 * @see WVL\Model::consolidatePartialExecutionStrategies()
	 * @see WVL\Model::getPreQueries()
	 * @see WVL\Model::getAssetData()
	 */
	protected static function executeQuery($partialExecutionStrategy, $countFoundRows=false) {
		$tables = self::getDefaultSelectTables();
		$fields = self::getDefaultSelectFields($countFoundRows);
		$partialConds = $partialExecutionStrategy['sql_conds'] ?: [];
		$partialOpts = $partialExecutionStrategy['sql_opts'] ?: [];
		$partialJoins = $partialExecutionStrategy['sql_joins'] ?: [];
		// Let provided strategies override defaults
		$conds = array_merge(self::getDefaultSelectConds(), $partialConds);
		$opts = array_merge(self::getDefaultSelectOpts(), $partialOpts);
		$joins = array_merge(self::getDefaultSelectJoins(), $partialJoins);

		$dbr = wfGetDB(DB_REPLICA);

		$sqlText = $dbr->selectSQLText(
			$tables,
			$fields,
			$conds,
			__METHOD__,
			$opts,
			$joins
		);

		$preQueries = self::getPreQueries();
		foreach ($preQueries as $preQuery) {
			$dbr->query($preQuery, __METHOD__);
		}

		$res = $dbr->query($sqlText, __METHOD__);

		$ret = ['query' => $sqlText];

		if ($countFoundRows) {
			$foundRowsRes = $dbr->query('SELECT FOUND_ROWS() AS `count`', __METHOD__);
			$foundRowsArr = $foundRowsRes->fetchRow();
			$ret['count'] = $foundRowsArr['count'];
		}

		$resArray = [];
		foreach ($res as $row) {
			$resArray[] = get_object_vars($row);
		}

		$ret['result'] = $resArray;

		return $ret;
	}

	/**
	 * Get queries to run immediately before the main query.
	 *
	 * Currently contains a query to increase the maximum string length for
	 * GROUP_CONCAT from MySQL's default of 1024 bytes.
	 *
	 * @see WVL\Model::executeQuery()
	 */
	protected static function getPreQueries() {
		return ['SET @@group_concat_max_len = 65536'];
	}

	/**
	 * Unite disparate execution strategies generated from user-provided
	 * parameters into one.
	 *
	 *    __________  _   _______ ____  __    ________  ___  ____________
	 *   / ____/ __ \/ | / / ___// __ \/ /   /  _/ __ \/   |/_  __/ ____/
	 *  / /   / / / /  |/ /\__ \/ / / / /    / // / / / /| | / / / __/
	 * / /___/ /_/ / /|  /___/ / /_/ / /____/ // /_/ / ___ |/ / / /___
	 * \_________________/____/\_____________________________________/
	 *   / ___/_  __/ __ \/   |/_  __/ ____/ ____/  _/ ____/ ___/
	 *   \__ \ / / / /_/ / /| | / / / __/ / / __ / // __/  \__ \
	 *  ___/ // / / _, _/ ___ |/ / / /___/ /_/ // // /___ ___/ /
	 * /____//_/ /_/ |_/_/  |_/_/ /_____/\____/___/_____//____/
	 *
	 * For that little bit of enterpriseyness.
	 *
	 * @param array $strategies TODO: describe
	 *
	 * @return array TODO: describe
	 */
	protected static function consolidatePartialExecutionStrategies($strategies) {
		$partialExecutionStrategy = [];

		$strategyParts = [
			'sql_conds',
			'sql_opts',
			'sql_joins'
		];

		foreach ($strategies as $strategy) {
			if (!$strategy || !is_array($strategy)) continue;

			foreach (array_intersect($strategyParts, array_keys($strategy)) as $strategyPart) {
				if (!$partialExecutionStrategy[$strategyPart]) {
					$partialExecutionStrategy[$strategyPart] = [];
				}

				$partialExecutionStrategy[$strategyPart] = array_merge(
					$partialExecutionStrategy[$strategyPart],
					$strategy[$strategyPart]
				);
			}
		}

		return $partialExecutionStrategy;
	}

	/**
	 * Convert date from U.S.-style n/j/Y or m/d/Y to Ymd.
	 *
	 * @param string $date U.S.-style date (e.g. 2/29/2016).
	 *
	 * @return string|bool formatted date (e.g. 20160229), or false for bad input.
	 */
	protected static function convertDate($date) {
		if (!preg_match('@(?:1\d|0?[1-9])/(?:[123]\d|0?[1-9])/\d{4}@', $date)) {
			return false;
		} else {
			return date('Ymd', strtotime($date));
		}
	}

	/**
	 * Get the default tables to use in select queries.
	 *
	 * @return array associative array mapping aliases to table names.
	 *
	 * @see WVL\Util::DB_TABLE_ASSETS
	 * @see WVL\Util::DB_TABLE_PAGES
	 */
	protected static function getDefaultSelectTables() {
		return [
			'wvla' => Util::DB_TABLE_ASSETS,
			'wvlp' => Util::DB_TABLE_PAGES
		];
	}

	/**
	 * Get the default fields to gather in select queries.
	 *
	 * @param bool $countFoundRows if true, prepend the field list with
	 *   SQL_CALC_FOUND_ROWS. See MySQL documentation for details.
	 *
	 * @return array associative array mapping aliases to fields.
	 *   asset_ids:        Tab-separated list of internal asset IDs.
	 *   asset_titles:     Tab-separated list of asset titles associated with
	 *                     matching page ID.
	 *   asset_types:      Tab-separated list of corresponding asset types.
	 *   asset_creators:   Tab-separated list of creators owning corresponding
	 *                     assets.
	 *   asset_assoc_aids: Tab-separated list of page IDs to which corresponding
	 *                     assets are linked. These should all be identical to
	 *                     each other and to the 'page_id' field under normal
	 *                     circumstances.
	 *   asset_timestamps: Tab-separated list of timestamps denoting latest known
	 *                     change to corresponding assets.
	 *   asset_on_article: Tab-separated list of integers determining whether
	 *                     corresponding assets are live on an article (1) or not.
	 *   page_id:          ID of the page to which assets are linked.
	 *   page_title:       Page title.
	 *   page_timestamp:   Latest known timestamp for when an asset linked to the
	 *                     page was changed.
	 *   page_catinfo:     Bitmask of top categories the page belongs to.
	 *   bogus_field:      Included when $countFoundRows is true. Always has the
	 *                     value '1'. Can safely be ignored.
	 */
	protected static function getDefaultSelectFields($countFoundRows=false) {
		$sep = "\t";

		$fields = [
			'asset_ids' => 'GROUP_CONCAT(wvla_id SEPARATOR "'.$sep.'")',
			'asset_titles' => 'GROUP_CONCAT(wvla_title SEPARATOR "'.$sep.'")',
			'asset_types' => 'GROUP_CONCAT(wvla_asset_type SEPARATOR "'.$sep.'")',
			'asset_creators' => 'GROUP_CONCAT(wvla_creator SEPARATOR "'.$sep.'")',
			'asset_assoc_aids' => 'GROUP_CONCAT(wvla_page_id SEPARATOR "'.$sep.'")',
			'asset_timestamps' => 'GROUP_CONCAT(wvla_timestamp SEPARATOR "'.$sep.'")',
			'asset_on_article' => 'GROUP_CONCAT(wvla_on_article SEPARATOR "'.$sep.'")',
			'page_id' => 'wvlp_page_id', // should be identical to asset_assoc_aid in most (all?) cases
			'page_title' => 'wvlp_page_title',
			'page_timestamp' => 'wvlp_timestamp',
			'page_catinfo' => 'wvlp_catinfo'
		];

		if ($countFoundRows) {
			// TODO: explain hack
			$fields = array_merge(
				['SQL_CALC_FOUND_ROWS 1 AS `bogus_field`'],
				$fields
			);
		}

		return $fields;
	}

	/**
	 * Get the default conditions for the WHERE clause in select queries.
	 *
	 * @return array an empty array. The default conditions are no conditions!
	 */
	protected static function getDefaultSelectConds() {
		return []; // Nothing!
	}

	/**
	 * Get the default miscellaneous options to use in select queries.
	 *
	 * @return array an array of options conforming to the style assumed for MW
	 *   select() arguments.
	 */
	protected static function getDefaultSelectOpts() {
		return ['GROUP BY' => ['wvlp_page_id']];
	}

	/**
	 * Get the default JOINs to use in select queries.
	 *
	 * @return array an array describing join conditions as an MW select()
	 *   argument.
	 */
	protected static function getDefaultSelectJoins() {
		return [
			'wvlp' => [
				'INNER JOIN',
				['wvlp.wvlp_page_id=wvla.wvla_page_id']
			]
		];
	}

	/**
	 * Build an execution strategy for filtering on given asset type.
	 *
	 * NOTE: Currently forces 'image'.
	 *
	 * @param string $assetType either 'image', 'video' or 'all' for restricting
	 *   results to images, videos, or not restricting by type, respectively.
	 *
	 * @return array|bool an associative array describing an element of the
	 *   appropriate execution strategy part, or false if no strategy part is
	 *   required.
	 */
	protected static function getAssetTypeStrategy($assetType) {
		// TODO: implement video.
		$assetType = 'image';

		switch ($assetType) {
		case 'image':
			return [
				'sql_conds' => [
					'wvla_asset_type' => Util::WVL_IMAGE
				]
			];
		case 'video':
			return [
				'sql_conds' => [
					'wvla_asset_type' => Util::WVL_VIDEO
				]
			];
		case 'all':
		default:
			return false; // No need to do anything
		}
	}

	/**
	 * Build an execution strategy for filtering on given creator name.
	 *
	 * @param string $creator creator whose assets to fetch. Note that providing
	 *   the reserved creator name described by Util::CREATOR_ORPHANED will return
	 *   orphaned assets (i.e. assets without assigned creators), while providing
	 *   Util::CREATOR_ASSIGNED will return non-orphaned assets. Providing a
	 *   falsey value will prevent filtering based on creators.
	 *
	 * @return array|bool an associative array describing an element of the
	 *   appropriate execution strategy part, or false if no strategy part is
	 *   required.
	 *
	 * @see WVL\Util::CREATOR_ASSIGNED
	 * @see WVL\Util::CREATOR_ORPHANED
	 */
	protected static function getCreatorStrategy($creator) {
		if (!$creator) {
			// Don't filter on creator
			return false;
		} elseif ($creator == Util::CREATOR_ORPHANED) {
			return [
				'sql_conds' => [
					'wvla_creator IS NULL'
				]
			];
		} elseif ($creator == Util::CREATOR_ASSIGNED) {
			return [
				'sql_conds' => [
					'wvla_creator IS NOT NULL'
				]
			];
		} else {
			return [
				'sql_conds' => [
					'wvla_creator' => $creator
				]
			];
		}
	}

	/**
	 * Build an execution strategy for filtering on title substring.
	 *
	 * @param string $keyword needle to search for in page titles (case
	 *   insensitive).
	 *
	 * @return array|bool an associative array describing an element of the
	 *   appropriate execution strategy part, or false if no strategy part is
	 *   required.
	 */
	protected static function getKeywordStrategy($keyword) {
		if (!$keyword) {
			return false;
		} else {
			$dbr = wfGetDB(DB_REPLICA);
			$keyword = mb_strtolower($keyword);
			return [
				'sql_conds' => [
					'LOWER(CONVERT(wvlp_page_title USING latin1)) LIKE ' . $dbr->addQuotes("%$keyword%")
				]
			];
		}
	}

	/**
	 * Build an execution strategy for filtering on pageless assets.
	 *
	 * NOTE: Currently forces exclusion of pageless assets. Other options are not
	 * implemented.
	 *
	 * @param string $pageless One of 'exclude', 'include', or 'only', describing
	 *   whether to exclude pageless, include them, or limit results to only those
	 *   that are pageless, respectively. NOTE: This parameter is currently
	 *   ignored.
	 *
	 * @return array|bool an associative array describing an element of the
	 *   appropriate execution strategy part, or false if no strategy part is
	 *   required.
	 */
	protected static function getPagelessStrategy($pageless) {
		// TODO: Implement other strategies at some point.
		$pageless = Util::PAGELESS_STRATEGY_DEFAULT;

		switch ($pageless) {
		case 'include':
			// TODO: Currently disabled.
			// To enable, the join type between assets and pages should be LEFT JOIN,
			// and other changes need to be made to query generation and UI.
			return false; // No need to do anything
		case 'only':
			return [
				'sql_conds' => [
					'wvla_page_id IS NULL'
				]
			];
		case 'exclude':
		default:
			// Do nothing for now. The INNER JOIN will exclude pageless assets for us.
			return false;
			/*
			return [
				'sql_conds' => [
					'wvla_page_id IS NOT NULL'
				]
			];
			 */
		}
	}

	/**
	 * Build an execution strategy for limiting the number of results.
	 *
	 * Results should be grouped by associated page ID from the wikiVisual
	 * Library Page table, and should be able to contain an arbitrary number of
	 * assets each. The pager size sets the limit for the number of pages to
	 * fetch, not assets.
	 *
	 * @param int $perPage the LIMIT
	 * @param int $page the multiplier to OFFSET results ($perPage*$page)
	 *
	 * @return array|bool an associative array describing an element of the
	 *   appropriate execution strategy part, or false if no strategy part is
	 *   required.
	 */
	protected static function getPagerSizeStrategy($perPage, $page=0) {
		if (!$perPage) {
			$perPage = Util::getDefaultPagerSize();
		} else {
			$perPage = min($perPage, Util::PAGER_SIZE_MAX);
			$perPage = max($perPage, Util::PAGER_SIZE_MIN);
		}

		$strategy = [
			'sql_opts' => [
				'LIMIT' => $perPage
			]
		];

		if ($page && $page > 0) {
			$strategy['sql_opts']['OFFSET'] = $page*$perPage;
		}

		return $strategy;
	}

	/**
	 * Build an execution strategy for filtering on specific page titles.
	 *
	 * If a partial URL (i.e. an article DB key) is provided, results will be
	 * limited to assets belonging to that particular page.
	 *
	 * @param string $partialUrl the page title for which to fetch assets. If a
	 * falsey value is provided, no filter is returned.
	 *
	 * @return array|bool an associative array describing an element of the
	 *   appropriate execution strategy part, or false if no strategy part is
	 *   required.
	 */
	protected static function getPartialUrlStrategy($partialUrl) {
		if (!$partialUrl) {
			return false;
		} else {
			$dbr = wfGetDB(DB_REPLICA);
			return [
				'sql_conds' => [
					'wvlp_page_title' => $partialUrl
				]
			];
		}
	}

	/**
	 * Build an execution strategy for how to sort the results.
	 *
	 * @param string $sortBy the sort type to apply.
	 *   May be one of:
	 *     title:  Sorts results by page title.
	 *     time:   Sorts results by timestamp associated with pages and/or assets.
	 *     views:  Sorts results by article views (NOT YET IMPLEMENTED). TODO
	 *     random: Sorts results randomly (KIND OF BUGGY). TODO
	 *   If a falsey value is provided, Util::SORT_ORDER_DEFAULT determines the
	 *   sort type.
	 * @param string $sortOrder either 'asc' for ascending sort, or 'desc' for
	 *   descending sort.
	 * @param int $randSeed an optional seed to pass to the PRNG when random sort
	 *   is provided.
	 *
	 * @return array|bool an associative array describing an element of the
	 *   appropriate execution strategy part, or false if no strategy part is
	 *   required.
	 *
	 * @see WVL\Util::SORT_ORDER_DEFAULT
	 */
	protected static function getSortStrategy($sortBy, $sortOrder, $randSeed=false) {
		if (!$sortBy) {
			$sortBy = Util::SORT_ORDER_DEFAULT;
		}

		switch ($sortBy) {
		case 'title':
			switch ($sortOrder) {
			case 'desc':
				return [
					'sql_opts' => [
						'ORDER BY' => [
							'wvlp_page_title DESC',
							'wvla_title DESC' // TODO: look more into this
						]
					]
				];
			case 'asc':
			default:
				return [
					'sql_opts' => [
						'ORDER BY' => [
							'wvlp_page_title ASC',
							'wvla_title ASC' // TODO: look more into this
						]
					]
				];
			}
			break;
		case 'time':
			switch ($sortOrder) {
			case 'asc':
				return [
					'sql_opts' => [
						'ORDER BY' => [
							'wvlp_timestamp ASC',
							'MAX(wvla_timestamp) ASC'
						]
					]
				];
			case 'desc':
			default:
				return [
					'sql_opts' => [
						'ORDER BY' => [
							'wvlp_timestamp DESC',
							'MAX(wvla_timestamp) DESC'
						]
					]
				];
			}
			break;
		case 'random': // FIXME: Kind of broken
			if ($randSeed) {
				$dbr = wfGetDB(DB_REPLICA);
				return [
					'sql_opts' => [
						'ORDER BY RAND(' . $dbr->addQuotes($randSeed) . ')'
					]
				];
			} else {
				// FIXME: This doesn't seem to seed as it should?
				return [
					'sql_opts' => [
						'ORDER BY RAND()'
					]
				];
			}
			break;
		case 'views': // TODO: Not yet implemented
		default:
			return false;
		}
	}

	/**
	 * Build an execution strategy to filter based on asset timestamps.
	 *
	 * @param string $dateLower lower bound date filter, provided in U.S.-style
	 *   slash-separated format.
	 * @param string $dateUpper upper bound date filter, provided in U.S.-style
	 *   slash-separated format.
	 *
	 * @return array|bool an associative array describing an element of the
	 *   appropriate execution strategy part, or false if no strategy part is
	 *   required.
	 *
	 * @see WVL\Model::convertDate()
	 */
	protected static function getTimestampStrategy($dateLower=false, $dateUpper=false) {
		$timestampLower = self::convertDate($dateLower);
		$timestampUpper = self::convertDate($dateUpper);

		if (!$timestampLower && !$timestampUpper) {
			return false;
		}

		$dbr = wfGetDB(DB_REPLICA);

		if ($timestampLower && !$timestampUpper) {
			return [
				'sql_conds' => [
					'wvla_timestamp >= ' . $dbr->addQuotes($timestampLower . '000000')
				]
			];
		} elseif (!$timestampLower && $timestampUpper) {
			return [
				'sql_conds' => [
					'wvla_timestamp <= ' . $dbr->addQuotes($timestampUpper . '235959')
				]
			];
		} else {
			return [
				'sql_conds' => [
					'wvla_timestamp BETWEEN ' . $dbr->addQuotes($timestampLower . '000000')
					. ' AND ' . $dbr->addQuotes($timestampUpper . '235959')
				]
			];
		}
	}

	/**
	 * Build an execution strategy to filter based on page category.
	 *
	 * @param string $topcat the category name to filter on, or a falsey value
	 *   if no filter is to be applied.
	 *
	 * @return array|bool an associative array describing an element of the
	 *   appropriate execution strategy part, or false if no strategy part is
	 *   required.
	 *
	 * @see WVL\Model::getTopcatValue()
	 */
	protected static function getTopcatStrategy($topcat) {
		$topcatValue = $topcat ? self::getTopcatValue($topcat) : false;

		if ($topcatValue) {
			$dbr = wfGetDB(DB_REPLICA);
			return [
				'sql_conds' => [
					'wvlp_catinfo & ' . $dbr->addQuotes($topcatValue) . ' <> 0'
				]
			];
		} else {
			return false;
		}
	}

	public static function addCreator($creatorName, $creatorType) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert(Util::DB_TABLE_CREATORS, ['wvlc_name' => $creatorName, 'wvlc_eligible' => 1, 'wvlc_type' => $creatorType], __FILE__);
		$id = $dbw->insertId();
		$dbw->update(Util::DB_TABLE_ASSETS, ['wvla_creator_id' => $id], ['wvla_creator' => $creatorName], __METHOD__);
	}

	public static function disableCreator($creatorId) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(Util::DB_TABLE_CREATORS, ['wvlc_eligible' => 0], ['wvlc_id' => $creatorId]);
	}

	public static function getCreatorId($creator) {
		$dbr = wfGetDB(DB_REPLICA);
		return $dbr->selectField(Util::DB_TABLE_CREATORS, "wvlc_id", ['wvlc_name' => $creator], __METHOD__);
	}
}

