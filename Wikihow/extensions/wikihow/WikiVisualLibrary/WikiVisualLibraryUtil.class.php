<?php

namespace WVL;

if (!defined('MEDIAWIKI')) {
	die();
}

/**
 * A collection of utility constants and methods for wikiVisual Library
 * functionality.
 */
class Util {
	/**
	 * @var string a reserved creator name denoting non-orphaned assets (i.e.
	 * assets assigned to a creator).
	 */
	const CREATOR_ASSIGNED = '.assigned';

	/**
	 * @var string a reserved creator name denoting orphaned assets (i.e. assets
	 *   not assigned to a creator).
	 */
	const CREATOR_ORPHANED = '.orphaned';

	/**
	 * @var string the table in which wikiVisual Library assets are indexed.
	 * 
	 * @see WVL\Model
	 */
	const DB_TABLE_ASSETS = 'wikivisual_library_asset';

	const DB_TABLE_CREATORS = 'wikivisual_library_creator';

	/**
	 * @var string the table in which wikiVisual Library articles are indexed.
	 *
	 * @see WVL\Model
	 */
	const DB_TABLE_PAGES = 'wikivisual_library_page';

	/**
	 * @var string memcached key holding creators and their asset counts.
	 */
	const MEMC_KEY_CREATORS = 'wikivisual-library-creators';

	/**
	 * @var string the default strategy for handling assets for which no page
	 *   link has been established.
	 *
	 * @see WVL\Model::getPagelessStrategy()
	 */
	const PAGELESS_STRATEGY_DEFAULT = 'exclude';

	/**
	 * @var int default number of results to return during queries in production.
	 *
	 * @see WVL\Model::getPagerSizeStrategy()
	 */
	const PAGER_SIZE_DEFAULT = 20;

	/**
	 * @var int default number of results to return during queries in development.
	 *
	 * @see WVL\Model::getPagerSizeStrategy()
	 */
	const PAGER_SIZE_DEFAULT_DEV = 5;

	/**
	 * @var int minimum number of results to return during queries.
	 *
	 * @see WVL\Model::getPagerSizeStrategy()
	 */
	const PAGER_SIZE_MIN = 1;

	/**
	 * @var int maximum number of results to return during queries.
	 *
	 * @see WVL\Model::getPagerSizeStrategy()
	 */
	const PAGER_SIZE_MAX = 20;

	/**
	 * @var string default sort order strategy.
	 * 
	 * @see WVL\Model::getSortStrategy()
	 */
	const SORT_ORDER_DEFAULT = 'title';

	/**
	 * @var int internal representation of the 'image' asset type.
	 */
	const WVL_IMAGE = 0;

	/**
	 * @var int internal representation of the 'video' asset type.
	 */
	const WVL_VIDEO = 1;

	/**
	 * TODO: funcdoc
	 */
	public static function getDefaultPagerSize() {
		global $wgIsDevServer;

		return $wgIsDevServer ?
			self::PAGER_SIZE_DEFAULT_DEV :
			self::PAGER_SIZE_DEFAULT;
	}

	/**
	 * TODO: funcdoc
	 *
	 * TODO: Move this and DomitianDB::normalizeMixedAssocArray to Misc or something?
	 */
	public static function normalizeMixedAssocArray($arr, $default=null) {
		$normalized = array();

		foreach ($arr as $k=>$v) {
			if (is_int($k)) {
				$normalized[$v] = $default;
			} else {
				$normalized[$k] = $v;
			}
		}

		return $normalized;
	}
}

