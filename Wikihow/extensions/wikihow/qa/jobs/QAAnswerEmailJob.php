<?php

if (!defined('MEDIAWIKI')) {
	die();
}

class QAAnswerEmailJob extends Job {

	public function __construct(Title $title, array $params, $id = 0) {
		parent::__construct('QAAnswerEmailJob', $title, $params, $id);
	}

	/**
	 * send an email to the question submitter to tell them it was answered
	 *
	 * @return bool
	 */
	public function run() {
		$aqid = $this->params['qa_id'];
		self::sendEmailToSubmitter($aqid);
	}

	/**
	 * sendEmailToSubmitter()
	 */
	public static function sendEmailToSubmitter($aqid) {
		$qadb = QADB::newInstance();
		$aq = $qadb->getArticleQuestionByArticleQuestionId($aqid);
		if (!$aq) return;

		$sqid = $aq->getCuratedQuestion()->getSubmittedId();
		$sq = $qadb->getSubmittedQuestion($sqid);
		if (!$sq) return;

		//set up our logging
		$log = [
			'qae_sqid' => $sqid,
			'qae_submit_time' => $sq->getSubmittedTimestamp(),
			'qae_check_time' => wfTimestampNow()
		];

		//check if it's too old
		$now = new DateTime(wfTimestampNow());
		$submitted = new DateTime($sq->getSubmittedTimestamp());
		$dateDiff = date_diff($now, $submitted);
		$aYear = 365;
		// if ($dateDiff->format('%a') > $aYear) return;

		$log['qae_good_date'] = $dateDiff->format('%a') > $aYear ? 0 : 1;

		$email = $sq->getEmail() ?:'';
		$name = '';
		if ($sq->getUserId()) {
			//user
			$user = User::newFromId($sq->getUserId());
			if ($user) {
				if (empty($email)) $email = $user->getEmail();
				$name = ' '.$user->getName();
			}
		}

		$log['qae_email'] = $email ?: '';

		// if ($email) {
		if ($email && $log['qae_good_date']) {
			//send that bad boy
			$to = new MailAddress($email);
			$from = new MailAddress('wikiHow Team <support@wikihow.com>');
			$subject = wfMessage('qap_email_subject')->text();

			$t = Title::newFromId($aq->getArticleId());
			$article_title = $t ? $t->getText() : '';
			if (empty($article_title)) return;
			$article_title = wfMessage('howto',$article_title)->text();
			$article_link = $t->getCanonicalURL();

			$answerer_name = '';
			$answerer_link = '';
			if (!empty($aq->getSubmitterUserId())) {
				$answer_user = User::newFromId($aq->getSubmitterUserId());
				if ($answer_user) {
					$answerer_name = $answer_user->getName();
					$answerer_link = wfExpandUrl('/'.$answer_user->getUserPage());
				}
			}

			//check for expert
			$expert = null;
			if ($aq->getVerifierId()) {
				$expert = VerifyData::getVerifierInfoById( $aq->getVerifierId() );
			}

			//first sentence
			if (!empty($expert)) {
				$email_info = wfMessage('qap_email_info_expert', $article_title, $expert->name, $expert->blurb);
			}
			else {
				$email_msg = $answerer_name ? 'qap_email_info_user' : 'qap_email_info_anon';
				$email_info = wfMessage($email_msg, $article_title, $answerer_name, $answerer_link)->text();
			}

			$question = $aq->getCuratedQuestion()->getText();
			$answer = $aq->getCuratedQuestion()->getCuratedAnswer()->getText();

			$email_link = wfExpandUrl('/Special:AnswerResponse');
			$email_link .= '?qa_id='.$aqid.'&st='.$sq->getSubmittedTimestamp().'&et='.wfTimestampNow();

			$link_help = $email_link.'&helpful=1';
			$link_unhelp = $email_link.'&helpful=0';

			//expert-only stinger
			$stinger = empty($expert) ? '' : wfMessage('qap_email_expert_stinger')->text();

			$body = wfMessage('qap_answered_email_body', $name, $email_info, $question, $answer, $article_title, $article_link, $link_help, $link_unhelp, $stinger)->text();

			$content_type = "text/html; charset=UTF-8";
			UserMailer::send($to, $from, $subject, $body, null, $content_type);
		}

		//log it
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->insert('qap_answer_emails', $log, __METHOD__);
	}
}
