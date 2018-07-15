<?php

global $IP;
require_once "$IP/extensions/wikihow/common/copyscape_functions.php";

class KnowledgeBoxCopyscapeJob extends Job {
	const ERROR_LOG_FILE = '/var/log/wikihow/kb-copyscape.log';
	const DEBUG_MODE = true;

	public function __construct(Title $title, array $params, $id = 0) {
		parent::__construct('KnowledgeBoxCopyscapeJob', $title, $params, $id);
	}

	/**
	 * Execute this job to check a KnowledgeBox submission for plagiarism via
	 * Copyscape
	 *
	 * @return bool
	 */
	public function run() {
		$kbId = $this->params['kbc_id'];

		$dbr = wfGetDB(DB_SLAVE);
		$text = $dbr->selectField(
			'knowledgebox_contents',
			'kbc_content',
			array('kbc_id' => $kbId),
			__METHOD__
		);

		$copyscapeResults = self::isPlagiarized($text);
		$plagiarized = !empty($copyscapeResults);

		if (self::DEBUG_MODE) {
			$debugStr =
				'[INFO] Checked submission ID ' . $kbId
				. ' (' . strlen(utf8_decode($text)) . ' chars). ';

			if ($plagiarized) {
				$debugStr .=
					'plagiarized from ' . count($copyscapeResults) . " source(s):\n"
					. implode("\n",
						array_map(
							function($s) {
								return '... ' . $s['percentmatched']
									. '% from ' . $s['url'];
							},
							$copyscapeResults))
					. "\n";
			} else {
				$debugStr .= "OK.\n";
			}

			wfErrorLog($debugStr, self::ERROR_LOG_FILE);
		}

		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(
			'knowledgebox_contents',
			array(
				'kbc_plagiarized' => $plagiarized,
				'kbc_plagiarism_checked' => wfTimestampNow()),
			array('kbc_id' => $kbId),
			__METHOD__
		);
	}

	/**
	 * Check for plagiarism with copyscape.
	 * Returns an array of Copyscape results of plagiarised sources.
	 * Note: To disable, set KnowledgeBox::CHECK_PLAGIARISM to false.
	 */
	private static function isPlagiarized($text) {
		$threshold = 80;

		try {
			$res = copyscape_api_text_search_internet($text, 'ISO-8859-1', 2);
		} catch (Exception $e) {
			wfErrorLog(
				'[ERROR] Caught exception: ' . $e->getMessage() . "\n",
				self::ERROR_LOG_FILE
			);

			throw $e;
		}

		$ret = array();

		if ($res['count']) {
			// $words = $res['querywords']; /* TODO: may not be needed */
			foreach ($res['result'] as $r) {
				// Ignore content from youtube /* TODO: maybe ignore wikihow and whstatic as well? */
				if (!preg_match("@^https?://[a-z0-9]*.(youtube).com@i", $r['url'])) {
					if ($r['percentmatched'] > $threshold) {
						// we got one!
						$ret[] = $r;
					}
				}
			}
		}

		return $ret;
	}
}
