<?php

if (!defined('MEDIAWIKI')) {
	die();
}

class TAInsertArticleQuestionJob extends Job {

	public function __construct(Title $title, array $params, $id = 0) {
		parent::__construct('TAInsertArticleQuestionJob', $title, $params, $id);
	}

	/**
	 * @return bool
	 */
	public function run() {
		$aid 		= $this->params['aid'];
		$aqid 	= $this->params['aqid'];

		$qadb = QADB::newInstance();
		$aq = $qadb->getArticleQuestionByArticleQuestionId($aqid);

		if ($aq && $aq->getSubmitterUserId()) {
			$user_id = $aq->getSubmitterUserId();

			//add category
			self::addCat($aid, $user_id);

			//touch the last answer date
			$ta = new TopAnswerers();
			if ($ta->loadByUserId($user_id)) $ta->save();
		}
	}

	/**
	 * addCat()
	 *
	 * run when a new article question is inserted
	 * adds another category to our qa_answerer_categories table
	 *
	 * @param $dbw 			= db
	 * @param $aid 			= article id
	 * @param $user_id 	= submitter user id
	 */
	private static function addCat($aid, $user_id) {
		$dbw = wfGetDB(DB_MASTER);
		$cat = TopAnswerers::getCat($aid);

		if ($cat) {
			$res = $dbw->upsert(
				TopAnswerers::TABLE_ANSWERER_CATEGORIES,
				[
					'qac_user_id' => $user_id,
					'qac_category' => $cat,
					'qac_count' => 1
				],
				[
					'qac_user_id',
					'qac_category'
				],
				[
					'qac_user_id = VALUES(qac_user_id)',
					'qac_category = VALUES(qac_category)',
					'qac_count = VALUES(qac_count)+qac_count'
				],
				__METHOD__
			);
		}
	}

}
