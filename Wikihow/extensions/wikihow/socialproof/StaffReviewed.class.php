<?php

class StaffReviewed {

	const STAFF_REVIEWED_ARTICLES_HANDPICKED_TAG = 'staff_reviewed_articles_handpicked';
	const STAFF_REVIEWED_KEY = 'staff_reviewed_article';

	public static function staffReviewedCheck(int $page_id, bool $checkMemc = true): bool {
		global $wgMemc;

		$key = wfMemcKey(self::STAFF_REVIEWED_KEY, $page_id);
		$reviewed = $wgMemc->get($key);
		if ($checkMemc && is_numeric($reviewed)) return $reviewed;

		//never for sensitive articles
		if (!self::sensitiveArticle($page_id)) {

			//check handpicked articles
			$reviewed = ArticleTagList::hasTag(self::STAFF_REVIEWED_ARTICLES_HANDPICKED_TAG, $page_id);

			if ($reviewed == 0) {
				//check titus_copy
				$twentyfifteen = '20151231';
				$reviewed = wfGetDB(DB_REPLICA)->selectField(
					'titus_copy',
					'count(*)',
					[
						'ti_language_code' => 'en',
						"ti_last_fellow_edit_timestamp > $twentyfifteen",
						"ti_last_fellow_edit != ''",
						'ti_page_id' => $page_id
					],
					__METHOD__
				);
			}
		}

		$wgMemc->set($key, $reviewed);
		return $reviewed;
	}

	public static function sensitiveArticle(int $articleId): bool {
		return class_exists('SensitiveArticle\SensitiveArticle') &&
			SensitiveArticle\SensitiveArticle::hasReasons(
				$articleId,
				self::staffReviewedSensistiveReasonIds()
			);
	}

	private static function staffReviewedSensistiveReasonIds(): array {
		return [
			7, //Not real
			10 //- Hide staff reviewed
		];
	}

	public static function setBylineInfo(&$verifiers, $pageId) {
		if (self::staffReviewedCheck($pageId)) {
			$verifiers[SocialProofStats::VERIFIER_TYPE_STAFF] = true;
		}

		return true;
	}
}
