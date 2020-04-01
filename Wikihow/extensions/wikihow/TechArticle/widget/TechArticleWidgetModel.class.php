<?php

namespace TechArticle;

use OutputPage;
use User;
use WebRequest;
use WikiPage;

class TechArticleWidgetModel {

	/**
	 * Whether the widget should be shown
	 */
	public static function isWidgetVisible(WebRequest $req, User $user, OutputPage $out): bool {
		return !$out->getArticleBodyOnly()
			&& $user->isLoggedIn() && !$user->isBlocked()
			&& $req->getBool('tech_widget');
	}

	/**
	 * Return the data to populate the tech widget
	 */
	public static function getWidgetData(int $pageId): array {
		$allProducts = TechProduct::getAll();
		$allPlatforms = TechPlatform::getAll();
		$techArticle = TechArticle::newFromDB($pageId);

		if ($techArticle->hasTechData()) { // Add additional info based on the current tech article
			foreach ($allProducts as &$product) {
				if ($product->id == $techArticle->productId) {
					$product->enabled = true;
					$product->selected = true;
				}
			}
			foreach ($allPlatforms as &$plat) {
				$plat->selected = false;
				$plat->tested = false;
				foreach ($techArticle->platforms as $selectedPlat) {
					if ($plat->id == $selectedPlat['id']) {
						$plat->enabled = true;
						$plat->selected = true;
						$plat->tested = $selectedPlat['tested'];
					}
				}
			}
		}
		return [ $allProducts, $allPlatforms ];
	}

	/**
	 * Extract and validate the data from a tech widget submission
	 */
	public static function sanitizeWidgetData(int $productId, array $platformIds,
			array $testedPlatformIds, User $user): array {
		$error = '';
		$platforms = [];
		if ($productId && $platformIds) {
			foreach ($platformIds as $platId) {
				$platform = [ 'id' => $platId, 'tested' => 0 ];
				foreach ($testedPlatformIds as $testedPlatId) {
					if ($platId == $testedPlatId) {
						$platform['tested'] = 1;
				}
					}
				$platforms[] = $platform;
			}
		} else {
			$error = 'Some tech widget fields are missing';
		}

		return [ $productId, $platforms, $error ];
	}

}
