<?php

/*
table for nightly scrape of answerable questions to show in the Q&A Box:

CREATE TABLE `qa_box_questions` (
	`qbq_id` int(10) PRIMARY KEY AUTO_INCREMENT,
	`qbq_sqid` int(10) NOT NULL DEFAULT 0,
	`qbq_question` blob NOT NULL,
	`qbq_submitter_email` blob NOT NULL DEFAULT '',
	`qbq_page_id` int(10) NOT NULL,
	`qbq_page_title` varbinary(255) NOT NULL DEFAULT '',
	`qbq_thumb` varbinary(255) NOT NULL DEFAULT '',
	`qbq_answered` tinyint(3) NOT NULL DEFAULT 0,
	`qbq_last_updated` varbinary(14) NOT NULL DEFAULT '',
	`qbq_random` double UNSIGNED NOT NULL,
	KEY (`qbq_answered`),
	KEY (`qbq_random`)
);
*/

class QABox extends UnlistedSpecialPage {

	const NUM_OF_QUESTIONS = 3;
	const MAX_ANS_CHARS = 700;

	public function __construct() {
		parent::__construct( 'QABox');
	}

	public function execute($par) {
		global $wgSquidMaxage;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$action = $request->getVal('action');
		$answered_sqid = $request->getInt('asqid');

		if ($action == 'load' || $action == 'refresh') {
			$out->setArticleBodyOnly(true);

			//marking the last question as answered?
			if ($action == 'load') {
				$out->setSquidMaxage($wgSquidMaxage);
			} elseif ($action == 'refresh' && $answered_sqid) {
				self::updateAnswerFlag($answered_sqid);
			}

			$qabox = [];
			$qabox['html'] = self::getQABoxHTML($answered_sqid);

			print json_encode($qabox);
			return;
		}

		//nothing normally
		$out->addHTML("No entry point here");
		return;
	}

	public static function getQABoxHTML($answered_sqid) {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__),
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);
		$html = $m->render('qa_box', self::getVars($answered_sqid));

		return $html;
	}

	public static function addQABoxToArticle() {
		$ctx = RequestContext::getMain();

		//anon only
		if (!$ctx->getUser()->isAnon()) return;

		if ( !Hooks::run( 'QABoxAddToArticle', array() ) ) {
			return;
		}

		$t = $ctx->getTitle();

		//page check
		if ($ctx->getLanguage()->getCode() != 'en'
			|| !$t->exists()
			|| !$t->inNamespace(NS_MAIN)
			|| $t->getText() == wfMessage('mainpage')->inContentLanguage()->text()
			|| !$ctx->getRequest()->getVal('action','') == '') {
			return;
		}

		$ctx->getOutput()->addModules('ext.wikihow.qa_box');

		$html = '<div id="qa_box"></div>';

		if (pq('.qa')->length) {
			pq('.qa')->after($html);
		}
		elseif (pq('.steps:last')->length) {
			pq('.steps:last')->after($html);
		}
	}

	private static function getVars($answered_sqid) {
		$ctx = RequestContext::getMain();
		$sq = self::getQuestions($answered_sqid);

		//set custom caching header
		$id0 = isset($sq[0]['sq_id']) ? $sq[0]['sq_id'] : '';
		$id1 = isset($sq[1]['sq_id']) ? $sq[1]['sq_id'] : '';
		$skey = "Surrogate-Key: qabox$id0 qabox$id1";
		$ctx->getRequest()->response()->header($skey);

		$vars = array(
			'qab_refresh_btn' => wfMessage('qab_refresh_btn')->text(),
			'qab_hdr' => wfMessage('qab_hdr')->text(),
			'qab_email_text' => wfMessage('qab_email')->text(),
			'qab_submit_text' => wfMessage('qab_submit')->text(),
			'qab_max_text' => wfMessage('qab_maxed',self::MAX_ANS_CHARS)->text(),
			'qab_min_text' => wfMessage('qab_min')->text(),
			'questions' => $sq,
			'qab_staff' => self::staffUser($ctx)
		);

		return $vars;
	}

	private static function getQuestions($answered_sqid) {
		$cats = array();
		$res = false;
		$dbr = wfGetDB(DB_REPLICA);

		//first, let's try to get a similar question if the user
		//has already answered one
		if (!empty($answered_sqid)) {
			$res = $dbr->select(
				[
					'categorylinks',
					'qa_box_questions'
				],
				'cl_to',
				[
					'qbq_sqid' => $answered_sqid,
					'cl_from = qbq_page_id',
					'cl_to != "Featured-Articles"'
				],
				__METHOD__
			);

			foreach ($res as $row) {
				$cats[] = $row->cl_to;
			}

			if (!empty($cats)) {
				$res = $dbr->select(
					[
						'qa_box_questions',
						'categorylinks'
					],
					'*',
					[
						'qbq_answered' => 0,
						'qbq_page_id = cl_from',
						'cl_to IN ("'.implode('","',$cats).'")'
					],
					__METHOD__,
					[
						'LIMIT' => self::NUM_OF_QUESTIONS
					]
				);
			}
		}

		//either A) loading for the first time or B) we still don't have something
		//get a random record
		if ($res === false || $dbr->numRows($res) == 0) {
			$res = $dbr->select(
				'qa_box_questions',
				'*',
				[
					'qbq_answered' => 0,
					'qbq_random > ' . wfRandom()
				],
				__METHOD__,
				[
					'ORDER BY' => "qbq_submitter_email != '' desc",
					'LIMIT' => self::NUM_OF_QUESTIONS
				]
			);
		}

		foreach ($res as $row) {
			$t = Title::newFromDBkey($row->qbq_page_title);
			if (!$t || !$t->exists()) continue;
			$article_title = wfMessage('howto', $t->getText())->text();
			$thumb = $row->qbq_thumb;
			if ($thumb) {
				$imgFile = ImageHelper::getImgFileFromThumbUrl( $thumb );
				if ($imgFile) {
					$params = array(
						'width' => 150,
						'height' => 150,
						'crop' => 1,
						WatermarkSupport::NO_WATERMARK => true,
					);
					$thumb = $imgFile->transform( $params );
					$thumb = $thumb->getUrl();
				}
			}

			$sq[] = array(
				'sq_id' => $row->qbq_sqid,
				'aid' => $row->qbq_page_id,
				'text' => $row->qbq_question,
				'question_hdr' => wfMessage('qab_question_hdr', $row->qbq_page_title, $article_title)->text(),
				'img' => wfGetPad( $thumb ),
				'qab_ph' => wfMessage('qab_ph')->text(),
				'maxed_msg' => wfMessage('qab_maxed')->text()
			);
		}

		return $sq;
	}

	private static function updateAnswerFlag($answered_sqid) {
		global $wgTitle;

		if (!$answered_sqid) return;

		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(
			'qa_box_questions',
			[
				'qbq_answered' => 1,
				'qbq_last_updated' => wfTimeStampNow()
			],
			['qbq_sqid' => $answered_sqid],
			__METHOD__
		);

		// Create a job that clears a particular fastly surrogate-key via the api
		$params = ['action' => 'reset-tag', 'lang' => 'en', 'tag' => "qabox$answered_sqid"];
		$job = new FastlyActionJob($wgTitle, $params);
		JobQueueGroup::singleton()->push($job);

		return;
	}

	private static function staffUser($ctx): bool {
		$user = $ctx->getUser();
		return $ctx->getLanguage()->getCode() == 'en'
			&& $user
			&& !$user->isBlocked()
			&& $user->hasGroup('staff');
	}
}
