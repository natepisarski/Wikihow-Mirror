<?php

namespace MethodHelpfulness;

use DeferImages;
use Html;
use Title;

/**
 * schema:
CREATE TABLE `article_method_helpfulness_summarized_stats` (
  `amhss_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `amhss_aid` int(11) unsigned NOT NULL DEFAULT '0',
  `amhss_source` varbinary(64) NOT NULL DEFAULT '',
  `amhss_count` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`amhss_id`),
  UNIQUE KEY `amhss_unique` (`amhss_aid`,`amhss_source`)
);
 */

/**
 * Collection of utility functions for handling article methods.
 */
class ArticleMethod {
	const METHOD_ACTIVE_ONLY = true;
	const METHOD_ALLOW_INACTIVE = false;

	/**
	 * Return internal method ID for a given method.
	 *
	 * @param int $aid ID of the article that contains requested method
	 * @param string $methodName title of requested method (case insensitive)
	 * @param bool $active only return method if currently active?
	 *
	 * @return int|bool primary ID of row containing the method,
	 *                  or false for no result
	 */
	public static function getMethodId($aid, $methodName, $active=self::METHOD_ACTIVE_ONLY) {
		$method = self::getMethod($aid, $methodName, $active);

		return $method !== false ? $method['am_id'] : false;
	}

	/**
	 * Return internal method ID for a given method. Insert if it does not exist.
	 *
	 * @param int $aid ID of the article that contains requested method
	 * @param string $methodName title of requested method (case insensitive)
	 *
	 * @return int primary ID of row containing the method
	 */
	public static function getMethodIdOrInsert($aid, $methodName) {
		$id = self::getMethodId($aid, $methodName, self::METHOD_ACTIVE_ONLY);

		if ($id === false) {
			$id = self::insertMethod($aid, $methodName);
		}

		return $id;
	}

	/**
	 * Insert a given method into the DB table.
	 *
	 * @param int $aid ID of the article that contains requested method
	 * @param string $methodName title of requested method (case insensitive)
	 * @param string|bool $ts timestamp to use, or set to false to use current
	 *                        current timestamp
	 *
	 * @return int primary ID of row containing inserted method
	 */
	private static function insertMethod($aid, $methodName, $ts=false) {
		$dbw = wfGetDB(DB_MASTER);

		if ($ts === false) {
			$ts = wfTimestampNow();
		}

		$dbw->insert(
			'article_method',
			array(
				'am_aid' => $aid,
				'am_title_hash' => self::getTitleHash($methodName),
				'am_timestamp' => $ts,
				'am_title' => $methodName
			),
			__METHOD__
		);

		return $dbw->insertId();
	}

	/**
	 * Return method info from article ID and method name.
	 *
	 * @param int $aid ID of the article that contains requested method
	 * @param string $methodName title of requested method (case insensitive)
	 * @param bool $active only return method if currently active?
	 *
	 * @return array|bool array with method info, or false for no result
	 */
	public static function getMethod($aid, $methodName, $active=self::METHOD_ACTIVE_ONLY) {
		$dbr = wfGetDB(DB_SLAVE);

		$conds = array(
			'am_aid' => $aid,
			'am_title_hash' => self::getTitleHash($methodName),
			'am_active' => '1'
		);

		if ($before !== false) {
			$conds[] = 'am_timestamp < ' . $dbr->addQuotes($before);
		}

		$opts = array(
			'ORDER BY' => 'am_timestamp DESC',
			'LIMIT' => 1
		);

		$row = $dbr->selectRow(
			'article_method',
			array('*'),
			$conds,
			__METHOD__,
			$opts
		);

		return $row !== false ? get_object_vars($row) : false;
	}

	/**
	 * Return method info about all methods stored for given article ID.
	 *
	 * @param int $aid ID of the article for which to fetch method info
	 * @param bool $active only return method if currently active?
	 *
	 * @return array nested array with method info
	 */
	public static function getArticleMethods($aid, $active=self::METHOD_ACTIVE_ONLY) {
		$dbr = wfGetDB(DB_SLAVE);

		$conds = array(
			'am_aid' => $aid
		);

		if ($active) {
			$conds['am_active'] = '1';
		}

		$res = $dbr->select(
			'article_method',
			'*',
			$conds,
			__METHOD__,
			array(
				'ORDER BY' => 'am_timestamp DESC',
				'GROUP BY' => array(
					'am_aid',
					'am_title_hash'
				)
			)
		);

		$result = array();
		foreach ($res as $row) {
			$result[] = get_object_vars($row);
		}

		return $result;

	}

	/**
	 * Return method IDs about all methods stored for given article ID.
	 *
	 * @param int $aid ID of the article for which to fetch method IDs
	 * @param bool $active only return method if currently active?
	 *
	 * @return array the method IDs
	 */
	public static function getArticleMethodIds($aid, $active=self::METHOD_ACTIVE_ONLY) {
		$result = array();

		foreach (self::getArticleMethods($aid, $active) as $methodInfo) {
			$result[] = $methodInfo['am_id'];
		}

		return $result;
	}

	/**
	 * Set a single method as inactive.
	 */
	public static function clearArticleMethod($aid, $methodName) {
		// It's unnecessary to deactivate methods that are already inactive, so
		// only look for active ones.
		$mid = self::getMethodId($aid, $methodName, self::METHOD_ACTIVE_ONLY);

		if ($mid === false) {
			return;
		}

		$dbw = wfGetDB(DB_MASTER);

		// Deactivate method in `article_method`
		$dbw->update(
			'article_method',
			array(
				'am_active' => '0'
			),
			array(
				'am_id' => $mid
			),
			__METHOD__
		);

		$dbr = wfGetDB(DB_SLAVE);

		$methodCount = $dbr->selectField(
			'article_method_helpfulness_stats',
			'amhs_count',
			array(
				'amhs_am_id' => $mid
			),
			__METHOD__
		);

		if ($methodCount !== false) {
			// Subtract deactivated method's votes from per-article display table
			$dbw->update(
				'article_method_helpfulness_summarized_stats',
				array(
					'amhss_count = amhss_count - ' . $dbw::addQuotes($methodCount)
				),
				array(
					'amhss_aid' => $aid
				),
				__METHOD__
			);
		}

		// Delete row from per-method display table
		$dbw->delete(
			'article_method_helpfulness_stats',
			array(
				'amhs_am_id' => $mid
			),
			__METHOD__
		);
	}

	/**
	 * Set all methods as inactive for given article ID.
	 */
	public static function clearArticleMethods($aid) {
		// It's unnecessary to deactivate methods that are already inactive, so
		// only look for active ones.
		$mids = self::getArticleMethodIds($aid, $methodName, self::METHOD_ACTIVE_ONLY);

		if (!$mids) {
			return;
		}

		$dbw = wfGetDB(DB_MASTER);

		// Deactivate the methods in `article_method`
		$dbw->update(
			'article_method',
			array(
				'am_active' => '0'
			),
			array(
				'am_aid' => $aid
			),
			__METHOD__
		);

		// Delete row for this article in per-article display table
		$dbw->delete(
			'article_method_helpfulness_summarized_stats',
			array(
				'amhss_aid' => $aid
			),
			__METHOD__
		);

		// Delete rows for the methods in per-method display table
		$dbw->delete(
			'article_method_helpfulness_stats',
			array(
				'amhs_am_id' => $mids
			),
			__METHOD__
		);
	}

	public static function getArticleMethodVoteCounts($aid) {
		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select(
			array(
				'am' => 'article_method',
				'mhv' => 'method_helpfulness_vote'
			),
			array(
				'method_name' => 'am_title',
				'vote_count' => 'COUNT(mhv_id)'
			),
			array(
				'am_aid' => $aid,
				'am_active' => '1'
			),
			__METHOD__,
			array(
				'ORDER BY' => 'am_timestamp DESC',
				'GROUP BY' => array(
					'am_aid',
					'am_title_hash'
				)
			),
			array(
				'mhv' => array(
					'LEFT JOIN',
					array(
						'mhv_am_id = am_id'
					)
				)
			)
		);

		$result = array();
		foreach ($res as $row) {
			$result[$row->method_name] = $row->vote_count;
		}

		return $result;
	}

	public static function getCTAVoteDetails($aid) {
		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select(
			array(
				'am' => 'article_method',
				'amhs' => 'article_method_helpfulness_stats'
			),
			array(
				'source' => 'amhs_source',
				'method_name' => 'am_title',
				'vote_type' => 'amhs_vote',
				'vote_count' => 'amhs_count'
			),
			array(
				'am_aid' => $aid,
				'am_active' => '1'
			),
			__METHOD__,
			array(
				'ORDER BY' => array(
					'am_timestamp DESC',
					'amhs_source',
					'am_title',
					'amhs_vote'
				),
				'GROUP BY' => array(
					'am_aid',
					'am_title_hash',
					'amhs_source',
					'amhs_vote'
				)
			),
			array(
				'amhs' => array(
					'INNER JOIN',
					array('amhs_am_id=am_id')
				)
			)
		);

		$result = array();
		foreach ($res as $row) {
			$result[$row->source][] = array(
				'method' => $row->method_name,
				'vote' => $row->vote_type,
				'count' => $row->vote_count
			);
		}

		return $result;
	}

	public static function getTotalCTAVotes($ctaType, $aid) {
		$dbr = wfGetDB(DB_SLAVE);

		return $dbr->selectField(
			'article_method_helpfulness_summarized_stats',
			'amhss_count',
			array(
				'amhss_aid' => $aid,
				'amhss_source' => $ctaType
			),
			__METHOD__
		);
	}

	public static function getLatestFeedback($aid, $limit=10) {
		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select(
			array(
				'mhe' => 'method_helpfulness_event',
				'mhd' => 'method_helpfulness_details'
			),
			array(
				'user_repr' => 'mhd_email',
				'feedback' => 'mhd_details',
				'rating' => '1',
				'timestamp' => 'STR_TO_DATE(mhe_timestamp, "%Y%m%d%H%i%s")'
			),
			array(
				'mhe_aid' => $aid
			),
			__METHOD__,
			array(
				'ORDER BY' => 'mhe_timestamp DESC',
				'LIMIT' => $limit
			),
			array(
				'mhe' => array(
					'INNER JOIN',
					array(
						'mhd_mhe_id=mhe_id'
					)
				)
			)
		);

		$feedback = array();

		foreach ($res as $row) {
			$feedback[] = get_object_vars($row);
		}

		return $feedback;
	}

	/**
	 * Determine whether the given Title has methods.
	 *
	 * TODO
	 */
	public static function hasMethods(&$t) {
		return true; // TODO
	}

	/**
	 * Get a hash of a method name for storing and retrieving from the DB
	 */
	public static function getTitleHash($methodName) {
		return crc32(mb_strtolower($methodName));
	}

	/**
	 * Injects a hidden div containing the "true" method names into the DOM
	 * for articles with methods (i.e. non-parts).
	 *
	 * @param array $methodElements PQ elements containing the methods
	 * @param string $selector the parent or sibling to which hidden div
	 *   is attached
	 * @param bool $append whether to treat the element specified by $selector
	 *   as parent (true) or sibling (false) of hidden div
	 */
	public static function modifyDOM($methodElements, $selector, $append) {
		if (self::enabled() && $methodElements) {
			$methodInfoDivsArray = array();
			foreach ($methodElements as $methodElement) {
				$methodElement->find('sup')->remove();
				$sanitizedMethod = trim($methodElement->text());

				$methodInfoDivsArray[] = Html::element(
					'div',
					array(
						'class' => 'mti-title',
						'data-title' => $sanitizedMethod
					)
				);
			}
			$methodInfoDivs = implode("\n", $methodInfoDivsArray);

			$methodInfoHtml = Html::rawElement(
				'div',
				array(
					'id' => 'method-title-info',
					'style' => 'display:none;'
				),
				$methodInfoDivs
			);

			if ($append) {
				pq($selector)->append($methodInfoHtml);
			} else {
				pq($selector)->after($methodInfoHtml);
			}
		}
	}

	public static function enabled() {
		global $wgLanguageCode;

		if ($wgLanguageCode != 'en') {
			return false;
		}

		return DeferImages::isArticlePage();
	}
}

