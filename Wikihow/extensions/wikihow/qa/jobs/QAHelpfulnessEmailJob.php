<?php
/*
CREATE TABLE `qa_answerer_helpfulness` (
	`qah_id` int(10) PRIMARY KEY AUTO_INCREMENT,
	`qah_answerer_user_id` int(10) NOT NULL DEFAULT 0,
	`qah_last_emailed` varbinary(14) NOT NULL DEFAULT '',
	UNIQUE KEY (`qah_answerer_user_id`)
);
*/

if (!defined('MEDIAWIKI')) {
	die();
}

class QAHelpfulnessEmailJob extends Job {
	const THIS_TABLE = 'qa_answerer_helpfulness';

	//our milestones (upvotes)
	const MILESTONE_1 = 1;
	const MILESTONE_10 = 10;
	const MILESTONE_50 = 50;

	//downvotes to check against
	const UNMILESTONE_10 = 5;
	const UNMILESTONE_50 = 25;

	//span to check in order to send emails
	const MILESTONE_1_FREQUENCY_DAYS = 30;
	const MILESTONE_10_FREQUENCY_DAYS = 14;

	public function __construct(Title $title, array $params, $id = 0) {
		parent::__construct('QAHelpfulnessEmailJob', $title, $params, $id);
	}

	/**
	 * send an email to the question submitter to tell them it was answered
	 *
	 * @return bool
	 */
	public function run() {
		$aqid = $this->params['qa_id'];
		$milestone = $this->params['milestone'];
		self::sendEmailToSubmitter($aqid, $milestone);
	}

	/**
	 * sendEmailToSubmitter()
	 * $aqid = the qa_articles_questions.id
	 * $milestone = the helpfulness milestone we're hitting (see constants above)
	 */
	private static function sendEmailToSubmitter($aqid, $milestone) {
		if (empty($aqid) || empty($milestone)) return;

		$qadb = QADB::newInstance();
		$aq = $qadb->getArticleQuestionByArticleQuestionId($aqid);
		if (!$aq) return;

		//only works if we have a user to send to
		if (!$aq->getSubmitterUserId()) return;

		//user (and make sure they're opted in)
		$user = User::newFromId($aq->getSubmitterUserId());
		if (!$user || $user->getOption('disableqaemail') == '1') return;

		$email = $user->getEmail();
		$name = $user->getName();

		if ($email) {
			$to = new MailAddress($email);
			$from = new MailAddress('wikiHow Community Team <communityteam@wikihow.com>');
			$subject = wfMessage('qa_helpfulness_subject_'.$milestone)->text();

			$t = Title::newFromId($aq->getArticleId());
			$article_title = $t ? $t->getText() : '';
			if (empty($article_title)) return;
			$article_title = wfMessage('howto',$article_title)->text();
			$article_link = $t->getCanonicalURL().'#Questions_and_Answers_sub';

			$link = UnsubscribeLink::newFromId($user->getId());

			$body = wfMessage('qa_helpfulness_body_'.$milestone, $name, $article_title, $article_link, $link->getLink())->text();

			$content_type = "text/html; charset=UTF-8";
			UserMailer::send($to, $from, $subject, $body, null, $content_type);

			self::updateTable($user->getId());
		}
	}

	/**
	 * getMilestone()
	 * - grab the helpfulness milestone (returns '' if no milestone hit)
	 * - called from QAUtil::onQAHelpfulnessVote()
	 */
	public static function getMilestone($aqid) {
		$qadb = QADB::newInstance();
		$aq = $qadb->getArticleQuestionByArticleQuestionId($aqid);

		$ms = '';

		if ($aq && $aq->getSubmitterUserId()) {
			if ($aq->getVotesUp() == self::MILESTONE_1) {
				$ms = self::dateCheck($aq, self::MILESTONE_1) ? self::MILESTONE_1 : '';
			}
			elseif ($aq->getVotesUp() == self::MILESTONE_10 && $aq->getVotesDown() <= self::UNMILESTONE_10) {
				$ms = self::dateCheck($aq, self::MILESTONE_10) ? self::MILESTONE_10 : '';
			}
			elseif ($aq->getVotesUp() == self::MILESTONE_50 && $aq->getVotesDown() <= self::UNMILESTONE_50) {
				$ms = self::dateCheck($aq, self::MILESTONE_50) ? self::MILESTONE_50 : '';
			}
		}

		return $ms;
	}

	/**
	 * dateCheck()
	 * $milestone = one of our milestone constants
	 *
	 * check if we want to send the email depending on the
	 * milestone and the milestone frequency
	 */
	private static function dateCheck($aq, $milestone) {
		$span = '';
		if ($milestone == self::MILESTONE_1) {
			$span = self::MILESTONE_1_FREQUENCY_DAYS;
		}
		elseif ($milestone == self::MILESTONE_10) {
			$span = self::MILESTONE_10_FREQUENCY_DAYS;
		}
		elseif ($milestone == self::MILESTONE_50) {
			return true; //always send
		}

		//bad call
		if (empty($span)) return false;

		//grab the last emailed date
		$dbr = wfGetDB(DB_REPLICA);
		$lastEmailed = $dbr->selectField(
			self::THIS_TABLE,
			'qah_last_emailed',
			['qah_answerer_user_id' => $aq->getSubmitterUserId()],
			__METHOD__,
			['LIMIT' => 1]
		);

		//never emailed? Well, let's change that!
		if (empty($lastEmailed)) return true;

		$now = new DateTime(wfTimestampNow());
		$led = new DateTime($lastEmailed);
		$dateDiff = date_diff($now, $led);
		$res = $dateDiff->format('%a') >= $span;

		return $res;
	}

	private static function updateTable($answerer_user_id) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->upsert(
			self::THIS_TABLE,
			[
				'qah_answerer_user_id' => $answerer_user_id,
				'qah_last_emailed' => wfTimestampNow()
			],
			['qah_answerer_user_id'],
			[
				'qah_answerer_user_id = VALUES(qah_answerer_user_id)',
				'qah_last_emailed = VALUES(qah_last_emailed)'
			],
			__METHOD__
		);
	}
}
