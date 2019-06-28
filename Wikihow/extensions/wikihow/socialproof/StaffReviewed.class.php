<?php

class StaffReviewed {

	const STAFF_REVIEWED_ARTICLES_HANDPICKED_TAG = 'staff_reviewed_articles_handpicked';
	const STAFF_REVIEWED_KEY = 'staff_reviewed_article';

	public static function staffReviewedCheck(int $page_id, bool $checkMemc = true): bool {
		global $wgMemc, $wgLanguageCode;

		if ( $wgLanguageCode != 'en' ) {
			return self::isStaffReviewedIntl($wgLanguageCode, $page_id);
		}

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

	/**
	 * Returns TRUE if the corresponding EN article is staff-reviewed and the
	 * last EN staff edit happened before the INTL translation/retranslation.
	 *
	 * The query below may seem slow, but calls to this method take only 0.23ms
	 * on average as of 2019-05 (on dev server, cache disabled).
	 */
	private static function isStaffReviewedIntl(string $lang, int $intlAid): bool {
		global $wgMemc;

		if ( $lang == 'en' ) {
			throw new Exception("This method should only be called for INTL");
		}

		$cacheKey = wfMemcKey(self::STAFF_REVIEWED_KEY, $intlAid);
		$isStaffReviewed = $wgMemc->get($cacheKey);
		if ( $isStaffReviewed !== false ) {
			return (bool) $isStaffReviewed;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$tables = [
			'titus_en'   => Misc::getLangDB('en') . '.titus_copy',
			'titus_intl' => Misc::getLangDB('en') . '.titus_copy',
		];
		$where = [
			'titus_en.ti_language_code' => 'en',
			'titus_en.ti_staff_byline_eligible' => 1,

			'titus_intl.ti_language_code' => $lang,
			'titus_intl.ti_page_id' => $intlAid,
			'titus_intl.ti_tl_en_id = titus_en.ti_page_id',

			'titus_en.ti_first_fellow_edit_timestamp < GREATEST(
				COALESCE(titus_intl.ti_first_edit_timestamp, 0),
				COALESCE(titus_intl.ti_last_retranslation, 0)
			)'
		];
		$isStaffReviewed = $dbr->selectField($tables, 1, $where);

		$wgMemc->set( $cacheKey, $isStaffReviewed ? '1' : '0');
		return (bool) $isStaffReviewed;
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
