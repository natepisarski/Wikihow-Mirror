<?php

class StaffReviewed {

	const STAFF_REVIEWED_ARTICLES_HANDPICKED_TAG = 'staff_reviewed_articles_handpicked';
	const STAFF_REVIEWED_SOURCE = 'staff';
	const STAFF_REVIEWED_KEY = 'staff_reviewed_article';

	public static function staffReviewedCheck(int $page_id): bool {
		global $wgMemc;

		$key = wfMemcKey(self::STAFF_REVIEWED_KEY, $page_id);
		$reviewed = $wgMemc->get($key);
		if (is_numeric($reviewed)) return $reviewed;

		//never for sensitive articles
		if (!self::sensitiveArticle($page_id)) {

			//check handpicked articles
			$reviewed = ArticleTagList::hasTag(self::STAFF_REVIEWED_ARTICLES_HANDPICKED_TAG, $page_id);

			if ($reviewed == 0) {
				//check titus_copy
				$twentyfifteen = '20151231';
				$reviewed = wfGetDB(DB_SLAVE)->selectField(
					'titus_copy',
					'count(*)',
					[
						'ti_language_code' => 'en',
						"ti_expert_verified_source IN ('','".StaffReviewed::STAFF_REVIEWED_SOURCE."')",
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
	 * used nightly in Titus.class.php
	 */
	public static function dataForTitus($dbr, $page_id): array {
		if (!self::staffReviewedCheck($page_id)) return [];

		$dbr = wfGetDB(DB_SLAVE);
		$lastFellowEdit = $dbr->selectField(
			'titus_copy',
			'ti_last_fellow_edit',
			[
				'ti_language_code' => 'en',
				'ti_page_id' => $page_id
			],
			__METHOD__
		);

		$rev_data = self::revisionData($dbr, $page_id);

		if ($rev_data) {
			$unixTS = wfTimestamp(TS_UNIX, $rev_data->rev_timestamp);
			$dateStr = DateTime::createFromFormat('U', $unixTS)->format('n/j/y');

			return [
				'name' => $lastFellowEdit,
				'source' => self::STAFF_REVIEWED_SOURCE,
				'date' => $dateStr,
				'revision' => $rev_data->rev_id
			];
		} else {
			return [
				'name' => '',
				'source' => '',
				'date' => '',
				'revision' => ''
			];
		}
	}

	private static function revisionData($dbr, $page_id) {
		$user_ids = self::revisionStaffUsers();

		$res = $dbr->select(
			'revision',
			[
				'rev_timestamp',
				'rev_id'
			],
			[
				'rev_page' => $page_id,
				"rev_user IN ('".implode("','", $user_ids)."')"
			],
			__METHOD__,
			[
				'ORDER BY' => 'rev_timestamp DESC',
				'LIMIT' => 1
			]
		);

		return $res->fetchObject();
	}

	private static function revisionStaffUsers() {
		$staff_users = [
			'Seymour Edits', //main umbrella user
			'Jean17',
			'HisGirlFriday'
		];

		$user_ids = [];
		foreach ($staff_users as $username) {
			$user = User::newFromName($username);
			$id = $user ? $user->getId() : 0;
			if ($id) $user_ids[] = $id;
		}

		return $user_ids;
	}

	public static function sensitiveArticle(int $articleId): bool {
		return \SensitiveArticle\SensitiveArticle::hasReasons(
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
}
