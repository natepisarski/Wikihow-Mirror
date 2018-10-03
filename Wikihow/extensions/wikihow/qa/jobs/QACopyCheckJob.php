<?php

if (!defined('MEDIAWIKI')) {
	die();
}

global $IP;

require_once "$IP/extensions/wikihow/common/copyscape_functions.php";

class QACopyCheckJob extends Job {
	const NOTIFICATION_MODE = true;
	const THRESHOLD_QAP = 25;
	const THRESHOLD_ARTICLE = 50;

	public function __construct(Title $title, array $params, $id = 0) {
		parent::__construct('QACopyCheckJob', $title, $params, $id);
	}

	/**
	 * Execute this job to check a Q&A answer submission for plagiarism via
	 * Copyscape
	 *
	 * @return bool
	 */
	public function run() {
		//is it going through QAPatrol or are we bypassing it (usually because of admin privileges)
		$skipQAP = $this->params['skip_qap'];

		$data = $skipQAP ? $this->getDataFromArticle($this->params) : $this->params;
		if (empty($data)) return;

		$copyscapeResults = self::isPlagiarized( $data['answer'], $skipQAP );
		$plagiarized = !empty($copyscapeResults);

		if (!$plagiarized) {
			//looks good!
			if ($skipQAP) {
				//nothing more to do :)
			}
			else {
				//flip the switch
				$this->copychecked($data['qap_id']);
			}

		}
		else {
			//uh oh...
			if (self::NOTIFICATION_MODE) {
				//notify someone, keep the row, do not flip the switch
				$t = $this->title;
				if (!$t || !$t->exists()) return;
				$article_name = $t->getText();

				//user info
				$user = User::newFromId( $data['user_id'] );
				$uname = $user ? $user->getName() : '';

				//expert (if exists)
				$expert = isset($data['expert']) ? 'Expert Name: '.$data['expert']."\n" : '';

				$body =
					'Article: '.$article_name."\n".
					'User ID: '.$data['user_id']."\n".
					'User Name: '.$uname."\n".
					$expert .
					'Question: '.utf8_decode( $data['question'] )."\n".
					'Answer: '.utf8_decode( $data['answer'] )."\n\n".
					'Plagiarized from '.count($copyscapeResults). " source(s):\n"
					. implode("\n",
						array_map(
							function($s) {
								return '... ' . $s['percentmatched']
									. '% from ' . $s['url'];
							},
							$copyscapeResults))
					. "\n";

				$subject = $skipQAP ? 'Q&A plagiarized answer (article)' : 'Q&A plagiarized answer';

				mail('anna@wikihow.com', $subject, $body);

				//inactive for live Q&As
				if ($skipQAP) $this->inactivate($data['qa_id']);
			}
			else {
				//no notification, but...
				if ($skipQAP) {
					//make it inactive
					$this->inactivate($data['qa_id']);
				}
				else {
					//just quietly remove the proposed answer
					QAPatrol::removeRow($qapId);
				}
			}

		}
	}

	/**
	 * Check for plagiarism with copyscape.
	 * Returns an array of Copyscape results of plagiarized sources.
	 *
	 * $text = the text to check
	 * $ignoreWH = (bool) we ignore wikiHow articles for article submissions
	 */
	private static function isPlagiarized($text, $ignoreWH) {
		try {
			$res = copyscape_api_text_search_internet($text, 'ISO-8859-1', 2);
		} catch (Exception $e) {
			return false;
			throw $e;
		}

		if (!empty($res['error'])) return false;

		$ret = array();

		if ($res['count']) {
			$threshold = $ignoreWH ? self::THRESHOLD_ARTICLE : self::THRESHOLD_QAP;

			foreach ($res['result'] as $r) {
				//for the article ones, let's overlook wikiHow matches
				if ($ignoreWH && preg_match('/^https?:\/\/[a-z0-9]*\.?(wikihow|whstatic)\.com/i', $r['url'])) continue;

				if (isset($r['percentmatched']) && $r['percentmatched'] > $threshold) {
					// we got one!
					$ret[] = $r;
				}
			}
		}

		return $ret;
	}

	private static function getDataFromArticle($params) {
		//start with this and we'll add stuff
		$data = $params;

		//expert info
		if (isset($data['expert_id'])) {
			$expert = VerifyData::getVerifierInfoById( $data['expert_id'] );
			if ($expert) $data['expert'] = $expert->name;
		}

		return $data;
	}

	//mark a live Q&A as inactive
	private static function inactivate($qa_id) {
		$qadb = QADB::newInstance();
		$qadb->setArticleQuestionsInactive([$qa_id]);
	}

	//mark row as checked and passed
	private static function copychecked($qap_id) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(
			'qa_patrol',
			['qap_copycheck' => 1],
			['qap_id' => $qap_id],
			__METHOD__
		);
	}
}
