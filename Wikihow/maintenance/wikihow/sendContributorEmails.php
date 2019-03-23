<?php
/**
 * Send e-mails to contributors whose contributions were made on articles
 * that have since been updated by an Editing Fellow.
 * Only active for specific tools, e.g.:
 *     Rating Reason
 */

require_once __DIR__ . '/../Maintenance.php';

class SendContributorEmails extends Maintenance {
	const DEBUG_DRY_RUN = false;
	const DEBUG_ALLOW_RESEND = false;
	const DEBUG_EMAIL_CONTENTS = false;
	public $verbose = true;

	// Maximum number of copies for each tool and type combo to send to the testers.
	// Set to 0 to disable test e-mails.
	const NUM_TEST_EMAILS = 8;

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Send e-mails to notify contributors about updated articles";
	}

	public function execute() {
		$this->dry_run = self::DEBUG_DRY_RUN;

		$entries = $this->getEntries();

		$emails = $this->formatEmails($entries);

		$this->sendEmails($emails);
	}

	/**
	 * Fetch valid contributor entries from the DB
	 */
	public function getEntries() {
		// Use subquery to compute lower-bound timestamps as strings for performance
		$tsSql = <<<SQL
(SELECT
	CONVERT(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 6 WEEK), '%Y%m%d000000') USING utf8)
		COLLATE utf8_general_ci
		AS ts_cee_lower,
	CONVERT(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 4 WEEK), '%Y%m%d000000') USING utf8)
		COLLATE utf8_general_ci
		AS ts_ces_lower)
SQL;

		// Compute lower and upper bound values for the CRC hash to split up
		// the e-mail addresses based on day-of-the-week.
		// TODO: Should ideally split this out into a separate method.
		$day = intval(date('w'));
		$ndays = 5;
		$crc32_max = pow(2, 32);

		switch ($day) {
		case 1:
		case 2:
		case 3:
		case 4:
		case 5:
			$crc_lower = round(($day - 1) * $crc32_max / $ndays);
			$crc_upper = round($day * $crc32_max / $ndays);
			break;
		case 0:
		case 6:
		default:
			$crc_lower = 0;
			$crc_upper = 0;
			print "Not sending e-mails on weekday $day. Exiting.\n";
			die();
		}

		$dbr = wfGetDB(DB_REPLICA);

		// Fetch e-mails and their entries per tool.
		// An e-mail's submission will be included if:
		// 		- The submission was posted within the specified timerange (default:
		// 		  last 6 weeks)
		// 		- The article for which the submission was posted has been updated by
		// 		  an editing fellow
		// 		- The editing fellow updated the article recently (default: last
		// 		  4 weeks)
		// 		- The editing fellow updated the article after the submission was posted
		// 		- No e-mails have been sent for that particular submission
		// 		- Fewer than 2 e-mails have been sent to the e-mail address for that
		// 		  tool recently (default: last 4 weeks)
		// 		- The e-mail address' CRC32 hash is within the given range. This is
		// 		  done to split e-mails up into smaller groups.
		// For each e-mail per tool, multiple submissions are grouped together in one row.
		// The submissions are grouped in the following format:
		// 		"tool_id_1:page_id_1,tool_id_2:page_id_2,...,tool_id_n:page_id_n"
		$selectTables = array(
			'cee' => 'contributor_emails.contributor_email_entries',
			'ts' => $tsSql,
			'ce' => 'contributor_emails.contributor_emails',
			'ces' => 'contributor_emails.contributor_emails_sent'
		);

		$selectFields = array(
			'contributor_id' => 'ce_id',
			'tool' => 'ce_tool',
			'email_addr' => 'ce_email_addr',
			'tool_aid_map' =>
				"GROUP_CONCAT(DISTINCT(CONCAT(cee_tool_id, ':', cee_assoc_aid)) SEPARATOR ',')",
			'entry_ids' =>
				"GROUP_CONCAT(DISTINCT(cee_id) SEPARATOR ',')",
			'submissions' => 'COUNT(*)',
			'emails_sent' => 'COUNT(ces_id)',
			'crc_email' => 'CRC32(ce_email_addr)'
		);

		$selectConds = array(
			'cee_timestamp >= ts_cee_lower' // Only recent entries
		);
		if (!($this->dry_run && self::DEBUG_ALLOW_RESEND)) {
			$selectConds[] = 'cee_sent_email_id IS NULL'; // No e-mail sent for entry yet
		}

		$selectOpts = array(
			'GROUP BY' => array(
				'ce_tool',
				'ce_email_addr'
			),
			'HAVING' => array(
				'crc_email >= ' . $crc_lower,
				'crc_email < ' . $crc_upper,
				'emails_sent < 2'
			)
		);

		$selectJoinConds = array(
			'ts' => array('CROSS JOIN'),
			'ce' => array(
				'INNER JOIN',
				array('ce_id = cee_contributor_id')
			),
			'ces' => array(
				'LEFT JOIN',
				array(
					'ces_timestamp >= ts_ces_lower',
					'ces_contributor_id = ce_id'
				)
			)
		);

		$res = $dbr->select(
			$selectTables,
			$selectFields,
			$selectConds,
			__METHOD__,
			$selectOpts,
			$selectJoinConds
		);

		$entries = array();

		foreach ($res as $row) {
			$tool = $row->tool;
			$email_addr = $row->email_addr;

			if (!$tool
				|| !$email_addr
				|| filter_var($email_addr, FILTER_VALIDATE_EMAIL) === false
			) {
				$this->printDebug("!! Bad e-mail address: $email_addr\n");
				continue;
			}

			$article_info = $this->formatToolAidMap($row->tool_aid_map);

			$entries[$tool][$email_addr] = array(
				'contributor_id' => $row->contributor_id,
				'article_info' => $article_info,
				'unique_articles' => count($article_info),
				'submissions' => $row->submissions,
				'emails_sent' => $row->emails_sent,
				'entry_ids' => explode(',', $row->entry_ids)
			);
		}

		return $entries;
	}

	/**
	 * Validate and format the string from SQL's GROUP_CONCAT.
	 *
	 * Takes a string of the format:
	 * 		'tool_id_1:page_id_1,tool_id_2:page_id_2,...'
	 * Returns an associative array of article info.
	 */
	protected function formatToolAidMap($tool_aid_map) {
		global $wgCanonicalServer;

		$article_info = array();
		$toolAids = explode(',', $tool_aid_map);
		$aids = array();

		foreach ($toolAids as $toolAid) {
			if (!$toolAid) {
				$this->printDebug("!! Missing tool aid in $toolAids\n");
				continue;
			}

			$toolAidArr = explode(':', $toolAid);
			if (count($toolAidArr) != 2) {
				$this->printDebug("!! Incomplete toolAidArr for $toolAid\n");
				continue;
			}

			$toolId = $toolAidArr[0];
			$aid = $toolAidArr[1];

			$exists = isset($aids[$aid]);
			$aids[$aid]++;

			// Naïvely skip duplicate article IDs
			if ($exists) {
				$this->printDebug("!! Duplicate $aid\n");
				continue;
			}

			$t = Title::newFromId($aid);
			if (!$t || !$t->exists()) {
				$this->printDebug("!! Title does not exist for $aid");
				continue;
			}

			$title = $t->getText();
			$href = $wgCanonicalServer . '/' . $t->getPartialUrl();

			$article_info[] = array(
				'aid' => $aid,
				'title' => $title,
				'href' => $href,
				'tool_id' => $toolId
			);
		}

		return $article_info;
	}

	/**
	 * Generate the e-mail contents for the given entries.
	 */
	protected function formatEmails($entries) {
		$results = array();

		$topics = array();
		$this->printDebug(count($entries) . " tool(s)\n");

		foreach ($entries as $tool=>$toolEntries) {
			print "\n==== TOOL: $tool ("
				. count($toolEntries) . " entries"
				. ") ====\n";

			foreach ($toolEntries as $emailAddr=>$entry) {
				$contributor_id = $entry['contributor_id'];

				$selectedTopic = '';
				$unsubLink = UnsubscribeLink::newFromEmail($emailAddr);
				$unsubHref = $unsubLink->getLink();

				$from = $this->getSender();

				if ($entry['unique_articles'] == 1) {
					$type = 'single';
					$msg = "contributor-email-$tool-$type";

					$article = reset($entry['article_info']);
					$howtoTitle = wfMessage('howto', $article['title'])->text();

					// TODO: move subjects to MW messages or separate function
					if ($tool === 'ratingreason') {
						if (mb_strlen($howtoTitle) > 40) {
							$this->printDebug("  !! $tool $emailAddr: title '"
								. $howtoTitle
								. "' too long for subject.\n");
							continue;
						}
						$subject =
							'We improved "'
							. $howtoTitle
							. '" thanks to your feedback.';
					} else {
						$this->printDebug("  !! $tool $emailAddr: bad tool\n");
						continue;
					}

					$emailBody =
						wfMessage(
							$msg,
							$howtoTitle,
							$article['href'],
							$selectedTopic,
							$unsubHref,
							$from['name'],
							$from['title'],
							wfMessage('contributor-email-intro-name')->text()
						)->text();
				} else {
					$type = 'multiple';
					$msg = "contributor-email-$tool-$type";

					$articles = $entry['article_info'];
					$articleLinks = array();

					$validSubjectArtTitle = false;

					foreach ($articles as $article) {
						$howtoTitle = wfMessage('howto', $article['title'])->text();
						$articleLinks[] =
							wfMessage(
								'contributor-email-list-item',
								$howtoTitle,
								$article['href']
							)->text();

						if ($validSubjectArtTitle === false && mb_strlen($howtoTitle) <= 40) {
							$validSubjectArtTitle = $howtoTitle;
						}
					}

					// TODO: move subjects to MW messages or separate function
					if ($tool === 'ratingreason') {
						if ($validSubjectArtTitle === false) {
							$this->printDebug(
								"  !! $tool $emailAddr: title '"
								. $article['text']
								. "' too long for subject.\n");
							continue;
						}
						$subject =
							'We improved "'
							. $validSubjectArtTitle
							. '" thanks to your feedback.';
					} else {
						$this->printDebug("  !! $tool $emailAddr: bad tool\n");
						continue;
					}

					$emailBody =
						wfMessage(
							$msg,
							implode("\n", $articleLinks),
							$unsubHref,
							$from['name'],
							$from['title'],
							wfMessage('contributor-email-intro-name')->text()
						)->text();
				}

				$results[] = array(
					'to' => $emailAddr,
					'from' => $from['addr'],
					'subject' => $subject,
					'body' => $emailBody,
					'tool' => $tool,
					'type' => $type,
					'contributor_id' => $contributor_id,
					'entry_ids' => $entry['entry_ids'],
					'topic' => $selectedTopic
				);
			}
		}

		$this->printDebug("Unique topics:\n");
		$this->printDebug(print_r($topics, true));
		$this->printDebug("\n");

		return $results;
	}

	// See: AuthorEmailNotification::getCTA()
	// NOTE: Currently unused.
	public function getRandCTA() {
		$rand = rand(1, 3);

		$link = AuthorEmailNotification::getCTALink('', '');
		$sentence = wfMessage('aen_cta_' . $rand, $link);

		return $sentence;
	}

	/**
	 * Get the highest-weight category
	 * NOTE: Currently unused.
	 */
	public function selectCategory($categories) {
		$selectedCategory = array(
			'weight' => 0
		);

		foreach ($categories as $catName => $catInfo) {
			if ($catInfo['weight'] > $selectedCategory['weight']) {
				$selectedCategory = array(
					'text' => $catName,
					'href' => $catInfo['href'],
					'weight' => $catInfo['weight']
				);
			}
		}

		return $selectedCategory;
	}

	/**
	 * Send the actual e-mails
	 */
	protected function sendEmails($emails) {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$types_encountered = array(
			'ratingreason' => array(
				'single' => 0,
				'multiple' => 0
			)
		);

		$content_type = "text/html; charset=UTF-8";

		$tester_emails = $this->getTesterEmails();

		print "==== SENDING " . count($emails) . " EMAILS ====\n";

		foreach ($emails as $email) {
			$tool = $email['tool'];
			$type = $email['type'];

			// ---------------------
			// Test e-mails
			// ---------------------
			// Send each tester N emails for each tool+type combo found
			if ($types_encountered[$tool][$type] < self::NUM_TEST_EMAILS) {
				foreach ($tester_emails as $tester_email) {
					$from = new MailAddress($email['from']);
					$testSubject = "[CE_TEST] [$tool-$type] " . $email['subject'];
					$ts = wfTimestampNow();

					$this->printDebug(
						"[TEST] Sending $tool ($type) e-mail to "
						. $tester_email->toString() . " at $ts\n"
					);

					UserMailer::send(
						$tester_email, $from, $testSubject,
						$email['body'], NULL, $content_type
					);
				}
			}
			// ---------------------
			// End test e-mails
			// ---------------------

			$types_encountered[$tool][$type]++;

			$from = new MailAddress($email['from']);
			$to = new MailAddress($email['to']);
			$ts = wfTimestampNow();

			$topic = '[[N/A]]';
			$entry_ids_str = implode(',', $email['entry_ids']);

			$this->printDebug(
				"Sending $tool ($type) e-mail to $to at $ts (topic: $topic): $entry_ids_str\n"
			);

			if (self::DEBUG_EMAIL_CONTENTS) {
				$this->printDebug("Subject: " . $email['subject'] . "\n");
				$this->printDebug("Body: " . $email['body'] . "\n");
			}

			if (!$this->dry_run) {
				// Insert row in sent emails table
				$dbw->insert(
					'contributor_emails.contributor_emails_sent',
					array(
						'ces_contributor_id' => $email['contributor_id'],
						'ces_timestamp' => $ts
					),
					__METHOD__
				);

				$ces_id = $dbw->insertId();

				// Update row for current entry
				$dbw->update(
					'contributor_emails.contributor_email_entries',
					array(
						'cee_sent_email_id' => $ces_id
					),
					array(
						'cee_id' => $email['entry_ids']
					),
					__METHOD__
				);

				// SEND THE ACTUAL EMAILS
				if ($tool === "ratingreason") {
					$emailCategory = "nothelpful_edit";
				} else {
					// do not tag with SendGrid cat
					$emailCategory = null;
				}

				UserMailer::send($to, $from, $email['subject'], $email['body'], NULL, $content_type, $emailCategory);
			}
		}

		print "E-mail types encountered:\n";
		print_r($types_encountered);
	}

	protected function getSender() {
		return
			array(
				'name' => wfMessage('contributor-email-sign-name')->text(),
				'title' => wfMessage('contributor-email-sign-title')->text(),
				'addr' => wfMessage('contributor-email-address')->text()
			);
	}

	protected function getTesterEmails() {
		$tester_addrs = [
			'elizabeth@wikihow.com'
		];

		$tester_emails = array();

		foreach ($tester_addrs as $addr) {
			$tester_emails[] = new MailAddress($addr);
		}

		return $tester_emails;
	}

	/**
	 * Get a whitelist of acceptable categories
	 * NOTE: Currently unused
	 */
	public function getCategoryWhitelist() {
		if (isset($this->categoryWhitelist)) {
			return $this->categoryWhitelist;
		}

		$this->categoryWhitelist = array();

		$categoryTreeArray = CategoryHelper::getCategoryTreeArray();
		unset($categoryTreeArray['']);
		unset($categoryTreeArray['WikiHow']);
		unset($categoryTreeArray['Other']);
		foreach ($categoryTreeArray as $subTree) {
			CategoryHelper::flattenary($this->categoryWhitelist, $subTree);
		}

		$this->categoryWhitelist = array_unique($this->categoryWhitelist);

		return $this->categoryWhitelist;
	}

	protected function printDebug($msg) {
		if ($this->verbose) {
			print $msg;
		}
	}
}

$maintClass = 'SendContributorEmails';
require_once RUN_MAINTENANCE_IF_MAIN;
