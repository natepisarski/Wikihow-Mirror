<?php

namespace WVL;

if (!defined('MEDIAWIKI')) {
	die();
}

use Linker,
	RepoGroup,
	Title,
	WVL\Model,
	WVL\Util;

/**
 * Controller for WikiVisualLibrary.
 *
 * Most access to the Model should usually happen indirectly through this
 * class.
 *
 * TODO: Flesh out classdoc
 *
 * @see WVL\Model
 */
class Controller {
	/**
	 * Get formatted data about wikiVisual assets.
	 *
	 * TODO: Flesh out funcdoc
	 *
	 * @see WVL\Model::getAssetData()
	 */
	public static function fetchAssets($params) {
		global $wgIsDevServer;

		if ($params['creatorEncrypted']) {
			$params['creator'] = str_rot13($params['creatorEncrypted']);
		}

		$assetGenerationStart = microtime(true);
		$assetData = Model::getAssetData($params);
		$assetGenerationTime = microtime(true) - $assetGenerationStart;

		$formattedPageArray = [];
		$assetKeys = [
			'asset_titles' => 'title',
			'asset_types' => 'type',
			'asset_creators' => 'creator',
			'asset_assoc_aids' => 'assoc_aid',
			'asset_timestamps' => 'timestamp',
			'asset_on_article' => 'on_article'
		];
		$imgParams = ['width' => 460];
		$linkPrefix = 'http://' . wfCanonicalDomain();
		$maybeLinkPrefix = $wgIsDevServer ? '' : $linkPrefix;

		$imageFormattingStart = microtime(true);
		foreach ($assetData['result'] as $row) {
			$pageAssetData = [];

			$pageAssetData['pageID'] = $row['page_id'];
			$pageAssetData['pageDisplayTitle'] = $row['page_title'];
			$pageAssetData['pageTimestamp'] = $row['page_timestamp'];
			$pageAssetData['pageCatinfo'] = $row['page_catinfo'];

			$pageTitle = Title::newFromID($row['page_id']);

			if ($pageTitle && $pageTitle->exists()) {
				$pageAssetData['pageDisplayTitle'] = $pageTitle->getText();
				$pageAssetData['pageDisplayURL'] =
					$maybeLinkPrefix .
					$pageTitle->getLinkURL();
			}

			$rawAssetData = [];

			foreach ($assetKeys as $rowAssetKey=>$newKey) {
				foreach (explode("\t", $row[$rowAssetKey]) as $k=>$assetField) {
					$rawAssetData[$k][$newKey] = $assetField;
				}
			}

			$pageAssetData['images'] = [];
			$assetCreators = [];

			foreach ($rawAssetData as $rawAsset) {
				$formattedAssetData = [];

				$formattedAssetData['assetName'] = $rawAsset['title'];
				$formattedAssetData['assetType'] = $rawAsset['type'];
				$formattedAssetData['assetCreator'] = $rawAsset['creator'];
				if (!in_array($rawAsset['creator'], $assetCreators)) {
					$assetCreators[] = $rawAsset['creator'];
				}
				$formattedAssetData['assetAssocAid'] = $rawAsset['assoc_aid'];
				$formattedAssetData['assetOnArticle'] = (bool) $rawAsset['on_article'];

				if ($rawAsset['type'] == Util::WVL_IMAGE) {
					$formattedAssetData['assetWikitext'] = "[[Image:{$rawAsset['title']}|center]]";
				}

				$assetTitle = Title::makeTitle(NS_FILE, $rawAsset['title']);
				if ($assetTitle && $assetTitle->exists()) {
					$image = wfFindFile($assetTitle, false);

					if ($image !== false) {
						$thumbnail = $image->transform($imgParams);
						Linker::processResponsiveImages($image, $thumbnail, $imgParams);

						$formattedAssetData['assetURL'] = $linkPrefix . $image->getUrl();
						// FIXME: wfGetPad doesn't play ball with the WVL server
						// $formattedAssetData['assetThumbURL'] = wfGetPad($thumbnail->getUrl());
						$formattedAssetData['assetThumbURL'] = $maybeLinkPrefix . $thumbnail->getUrl();
						$formattedAssetData['assetThumbWidth'] = $thumbnail->getWidth();
						$formattedAssetData['assetThumbHeight'] = $thumbnail->getHeight();
						$formattedAssetData['assetThumbAlt'] = $assetTitle->getPrefixedText();
					}
				}

				$pageAssetData['images'][] = $formattedAssetData;
			}

			$pageAssetData['creators'] = implode(
				', ',
				self::getAliasesForCreators($assetCreators)
			);

			$assetCounts = Model::getAssetCounts($row['page_id']);

			if ($assetCounts) {
				$pageAssetData['imageCount'] = $assetCounts['images'];
				$pageAssetData['videoCount'] = $assetCounts['videos'];
			}

			$formattedPageArray[] = $pageAssetData;
		}
		$imageFormattingTime = microtime(true) - $imageFormattingStart;

		$imageInfo['images'] = $formattedPageArray;

		if ($assetData['count']) {
			$urlString = self::createUrlParams($params);
			$imageInfo['pagerInfo'] = self::formatPager(
				$params['perPage'] ?: Util::getDefaultPagerSize(),
				$params['page'] ?: 0,
				count($assetData['result']),
				$assetData['count'],
				$urlString
			);
		}

		$imageInfo['runtimeInfo'] = [
			'assetGenerationTime' => number_format($assetGenerationTime, 2),
			'imageFormattingTime' => number_format($imageFormattingTime, 2)
		];

		$imageInfo['query'] = $assetData['query'];

		return $imageInfo;
	}

	public static function createUrlParams($params) {
		$paramArray = [];
		if(!is_null($params["keyword"])) {
			$paramArray["keyword"] = $params["keyword"];
		}
		if(!is_null($params['creatorEncrypted'])) {
			$paramArray["ce"] = $params["creatorEncrypted"];
		}
		if(!is_null($params['topcat'])) {
			$paramArray["topcat"] = $params["topcat"];
		}

		return http_build_query($paramArray, null, '&', PHP_QUERY_RFC3986);
	}

	/**
	 * TODO: funcdoc
	 */
	public static function getCreators($forceCacheUpdate=false) {
		global $wgMemc;

		if (!$forceCacheUpdate) {
			$formattedCounts = $wgMemc->get(Util::MEMC_KEY_CREATORS);

			if (is_array($formattedCounts)) {
				return $formattedCounts;
			}
		}

		$creators = Model::getAllCreators();

		$formattedOrphanedCounts = [];
		$formattedAssignedCounts = [];
		$formattedCreatorCounts = [];

		$aliases = [];

		foreach ($creators as $i=>$creatorInfo) {
			$creator = $creatorInfo['creator'];
			$alias = self::depersonalizeCreator($creator, $aliases);
			$images = $creatorInfo['imageCount'] ?: 0;
			$videos = $creatorInfo['videoCount'] ?: 0;
			$creatorType = $creatorInfo['creatorType'];

			// TODO: Remove this when videos are in place:
			if (!$images) {
				continue;
			}

			if (is_null($creator)) {
				$formattedOrphanedCounts['images'] = $images;
				$formattedOrphanedCounts['videos'] = $videos;
			} else {
				$formattedCreatorCounts[] = [
					'index' => $i,
					'creator' => str_rot13($creator),
					'rawCreator' => $creator,
					'alias' => $alias,
					'images' => $images,
					'videos' => $videos,
					'creatorType' => $creatorType
				];

				$formattedAssignedCounts['images'] += $images;
				$formattedAssignedCounts['videos'] += $videos;
			}
		}

		$formattedCounts = [
			'creatorCounts' => $formattedCreatorCounts,
			'orphanedCounts' => $formattedOrphanedCounts,
			'assignedCounts' => $formattedAssignedCounts
		];

		$wgMemc->set(Util::MEMC_KEY_CREATORS, $formattedCounts);

		return $formattedCounts;
	}

	/**
	 * TODO: funcdoc
	 */
	public static function getOrphanedAssetsCount() {
		$orphanedCounts = Model::getOrphanedAssetsCount();
		$orphanedCounts['images'] = $orphanedCounts['images'] ?: 0;
		$orphanedCounts['videos'] = $orphanedCounts['videos'] ?: 0;

		$formattedOrphanedCounts = [];
		foreach ($orphanedCounts as $type=>$count) {
			$formattedOrphanedCounts[$type] = number_format($count);
		}

		return $formattedOrphanedCounts;
	}

	/**
	 * TODO: funcdoc
	 */
	public static function getAssignedAssetsCount() {
		$assignedCounts = Model::getAssignedAssetsCount();
		$assignedCounts['images'] = $assignedCounts['images'] ?: 0;
		$assignedCounts['videos'] = $assignedCounts['videos'] ?: 0;

		$formattedAssignedCounts = [];
		foreach ($assignedCounts as $type=>$count) {
			$formattedAssignedCounts[$type] = number_format($count);
		}

		return $formattedAssignedCounts;
	}

	/**
	 * TODO: funcdoc
	 */
	public static function getTopcats() {
		$topcats = Model::getAllTopcats();
		$formattedTopcats = [];

		foreach ($topcats as $i=>$topcat) {
			$formattedTopcats[] = [
				'index' => $i,
				'topcat' => $topcat
			];
		}

		return $formattedTopcats;
	}

	/**
	 * Create an alias for a creator.
	 *
	 * @param string $creator the creator to depersonalize
	 * @param array $aliases existing aliases against which to check for dupes
	 *
	 * @return string the alias
	 */
	public static function depersonalizeCreator($creator, &$aliases) {
		$parts = explode('_', $creator);
		$alias = $creator;

		switch ($creator) {
		case 'zzz_wv_artlab':
			$alias = 'zzz wv art';
			break;
		default:
			if ($parts[0] == 'prayan') {
				array_shift($parts);
				$alias = 'p. ' . implode(' ', $parts);
			} elseif (!in_array($parts[0], ['artlab', 'zzz']) && count($parts) > 1
				|| $parts[0] == 'artlab' && count($parts) > 2
				|| $parts[0] == 'zzz' && count($parts) > 3
			) {
				// Remove last name/initial
				$last = array_pop($parts);
				$alias = implode(' ', $parts);

				if (strlen($last) == 1) {
					// If it's just an initial, obscure it and add back
					$alias .= ' ' . str_rot13($last);
				}
			}

			$alias = preg_replace(
				['/artlab/', '/_/'],
				['art', ' '],
				$alias
			);
		}

		$count = 1;
		$baseAlias = $alias;
		while (in_array($alias, $aliases)) {
			$count += 1;
			$alias = "$baseAlias $count";
		}

		$aliases[] = $alias;

		return $alias;
	}

	/**
	 * Just as advertised.
	 */
	public static function getAliasesForCreators($creators) {
		$assetCounts = self::getCreators();

		$aliasMap = [];

		foreach ($creators as $creator) {
			$aliasMap[$creator] = $creator;
		}

		foreach ($assetCounts['creatorCounts'] as $creatorCount) {
			if (isset($aliasMap[$creatorCount['rawCreator']])) {
				$aliasMap[$creatorCount['rawCreator']] = $creatorCount['alias'];
			}
		}

		return array_values($aliasMap);
	}

	protected static function formatPager($perPage, $curPage, $pageResultCount, $totalResultCount, $pagerUrlString) {
		$resultRangeLower = $perPage * $curPage + 1;
		$resultRangeUpper = max(
			$resultRangeLower,
			$perPage * $curPage + $pageResultCount
		);
		$pageCurrent = $curPage + 1;
		$pageMax = ceil(1.0 * $totalResultCount / $perPage);

		// TODO: Explain
		$pagesToDisplay = [];
		foreach ([1, -1] as $k=>$sign) {
			$pagesToDisplay[$k] = [];

			$cur = $pageCurrent + $sign;
			while ($cur > 0 && $cur <= $pageMax && abs($pageCurrent - $cur) < 5) {
				$pagesToDisplay[$k][] = ['page' => $cur];
				$cur += $sign;
			}

			$offset = 10 * $sign;
			$cur = $pageCurrent + $offset;
			while ($cur > 0 && $cur <= $pageMax) {
				$pagesToDisplay[$k][] = ['page' => $cur];
				$offset *= 10;
				$cur = $pageCurrent + $offset;
			}
		}

		return [
			'resultRangeLower' => number_format($resultRangeLower),
			'resultRangeUpper' => number_format($resultRangeUpper),
			'count' => number_format($totalResultCount),
			'pageCurrentFormatted' => number_format($pageCurrent),
			'pageMaxFormatted' => number_format($pageMax),
			'pageCurrent' => $pageCurrent,
			'pageMax' => $pageMax,
			'pagesBefore' => array_reverse($pagesToDisplay[1]),
			'pagesAfter' => $pagesToDisplay[0],
			'pagerUrlString' => $pagerUrlString
		];
	}
}

