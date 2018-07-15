<?php

/*
 * A section on whitelisted articles in which users submit
 * raw content.
 */
class KnowledgeBox extends UnlistedSpecialPage {
	const MINIMUM_CHARACTERS = 50;
	const MINIMUM_UPPERCASE_SYMBOLS_RATE = 0.6;
	const MINIMUM_WORD_LENGTH = 4; // Average
	const MAXIMUM_WORD_LENGTH = 15; // Average
	const MAXIMUM_REPEATING_CHARACTERS = 10;
	const MAXIMUM_REPEATING_WORDS = 5;
	const DEBUG_FILTER = true;
	const COPYSCAPE_THRESHOLD = 3000;
	const LOG_REJECTS = true;
	const FILTER_LOG_FILE = '/var/log/wikihow/kb-filtered-submissions.log';
	const DEFAULT_THUMBNAIL_URL =
		'http://pad1.whstatic.com/extensions/wikihow/search/no_img_green_mobile.png';
	const CHECK_PLAGIARISM = true;
	const ALLOW_PROTECTED_PAGES = true;

	// Set this to true to stop showing the CTA.
	const CTA_DISABLED = true;

	function __construct() {
		parent::__construct('KnowledgeBox');
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgIsDevServer;

		$articleId = $wgRequest->getInt('aid');
		$kbId = $wgRequest->getInt('kbId');
		$kbAid = $wgRequest->getInt('kbAid');
		$kbTopic = $wgRequest->getVal('kbTopic');
		$kbContent = $wgRequest->getVal('kbContent');
		$kbEmail = $wgRequest->getVal('kbEmail');
		$kbName = $wgRequest->getVal('kbName');
		$kbSpentIDs = $wgRequest->getVal('spentIDs');
		$csv_url = $wgRequest->getVal('csvurl');

		// Add KB content
		if ($kbId != 0 && $kbTopic != "" && $kbContent != "") {
			$wgOut->setArticleBodyOnly(true);
			$result = $this->addKBContent($kbId, $kbAid, $kbContent, $kbEmail, $kbName);
			if ($kbSpentIDs) {
				$result['kbTopic'] =
					self::getKBTopic(json_decode($kbSpentIDs));
			}
			print_r(json_encode($result));
			return;
		} elseif ($kbSpentIDs) {
			$wgOut->setArticleBodyOnly(true);
			$result['kbTopic'] =
				self::getKBTopic(json_decode($kbSpentIDs));
			print_r(json_encode($result));
			return;
		} elseif (!$kbId && $kbAid != 0 && $kbTopic != "" && $kbContent != "") {
			// Discard outdated ajax calls from cached users:
			$result = array();
			if ($kbSpentIDs) {
				// kbSpentIDs unused here.
				$result['kbTopic'] =
					self::getKBTopic(array());
			}
			print_r(json_encode($result));
			return;
		}

		// Hide from non-staff
		$userGroups = $wgUser->getGroups();
		$groupWhitelist = array('staff', 'staff_widget');
		if ($wgUser->isBlocked() || !array_intersect($groupWhitelist, $userGroups)) {
			$wgOut->setRobotPolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage','nospecialpagetext');
			return;
		}

		// Redirect non-dev site to parsnip for remaining functionality
		// unless we're on a dev site.
		if ($_SERVER['HTTP_HOST'] != 'parsnip.wikiknowhow.com'
				&& !$wgIsDevServer) {
			$wgOut->redirect('https://parsnip.wikiknowhow.com/Special:KnowledgeBox');
			return;
		}

		// Download content from specific KB topics
		if ($csv_url && !$wgUser->isBlocked() && in_array('staff', $userGroups)) {
			$wgOut->disable();
			header('Content-type: application/force-download');

			header('Content-disposition: attachment; filename="knowledgebox_article.csv"');
			$articleID = $this->parseArticleReference($csv_url);
			$kbEntries = $this->getKBContentsByArticleID($articleID);

			print "Article ID,Article URL,User,E-mail,Name,Time,Total Votes,Guardian Score,Plagiarized,Content\n";
			foreach ($kbEntries as $kbEntry) {
				$url = self::formatCSVField($kbEntry['title_url']);
				$time = self::formatCSVField($kbEntry['date']);
				$content = self::formatCSVField($kbEntry['kbc_content']);
				$user = self::formatCSVField($kbEntry['kbc_user_text']);
				$email = self::formatCSVField($kbEntry['kbc_email']);
				$name = self::formatCSVField($kbEntry['kbc_name']);
				$votes = self::formatCSVField($kbEntry['total_score']);
				$score = self::formatCSVField($kbEntry['guardian_score']);
				$plagiarized = '';
				if ($kbEntry['kbc_plagiarism_ignore']) {
					$plagiarized = self::formatCSVField('untested');
				} elseif (!$kbEntry['kbc_plagiarism_checked']) {
					$plagiarized = self::formatCSVField('queued');
				} else {
					$plagiarized = self::formatCSVField(
						$kbEntry['kbc_plagiarized'] == '1' ? 'yes' : 'no');
				}
				$output = $kbEntry['kbc_aid'] . ','
						. $url . ','
						. $user . ','
						. $email . ','
						. $name . ','
						. $time . ','
						. $votes . ','
						. $score . ','
						. $plagiarized . ','
						. $content;
				print "$output\n";
			}
			return;
		}

		// Display pager
		$llr = new NewKnowledgeBoxContents();
		$llr->getList();

		return;
	}

	public static function formatCSVField($field) {
		return '"' . str_replace('"', "''", $field) . '"';
	}

	/*
	 * Add a new KB submission to the DB.
	 */
	private function addKBContent($kbId, $kbAid, $kbContent, $kbEmail='', $kbName='') {
		global $wgParser, $wgUser;

		if (!$kbEmail) {
			$kbEmail = '';
		}

		if (!$kbName) {
			$kbName = '';
		}

		$result = self::checkContent($kbContent, self::DEBUG_FILTER);

		// Quietly ignore bad submissions
		if ($result['success']) {
			$isSuspicious = self::isSuspiciousContent($kbContent);

			$data = array(
				'kbc_kbid' => $kbId,
				'kbc_aid' => $kbAid,
				'kbc_user_id' => $wgUser->getID(),
				'kbc_user_text' => $wgUser->getName(),
				'kbc_content' => $kbContent,
				'kbc_timestamp' => wfTimestampNow(),
				'kbc_email' => $kbEmail,
				'kbc_name' => $kbName
			);

			if (!$isSuspicious) {
				$data['kbc_plagiarism_ignore'] = true;
			}

			// Disable KB Guardian patrolling for content submitted to topics without
			// a valid associated article by marking the submission as patrolled.
			// TODO: Remove this workaround when KB Guardian supports content without an
			// article.
			$t = Title::newFromID($kbAid);
			if (!$t || !$t->exists()) {
				$data['kbc_patrolled'] = true;
			}

			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert(
				'knowledgebox_contents',
				$data,
				__METHOD__
			);
			$kbId = $dbw->insertId();

			if ($isSuspicious) {
				self::pushCopyscapeJob($kbId, $kbContent);
			}
		} elseif (self::LOG_REJECTS
				&& strlen(utf8_decode($kbContent)) > self::MINIMUM_CHARACTERS) {
			$logStr = "Rejected submission " . wfTimestampNow() . "\n";
			$logStr .= "Metadata:\n";
			$logStr .= print_r($result, true);
			$logStr .= "\nContent:\n";
			$logStr .= $kbContent;
			$logStr .= "\n================================\n\n";
			wfErrorLog($logStr, self::FILTER_LOG_FILE);
		}

		return $result;
	}

	public static function getDomainAdjustedURL($t, $urlencode=false) {
		global $wgIsDevServer;

		if (!$t) {
			return '';
		}

		if ($wgIsDevServer) {
			if ($urlencode) {
				return $t->escapeFullURL();
			} else {
				return $t->getFullURL();
			}
		} else {
			return 'http://www.wikihow.com/' . $t->getPartialURL();
		}
	}

	static public function parseArticleReference($ref) {
		if (ctype_digit($ref)) {
			return $ref;
		}

		$url = preg_replace('@^https?://[^/]*/@', '', $ref);
		$pageTitle = Misc::getUrlDecodedData($url);

		$t = Title::newFromText($pageTitle);

		if ($t && $t->exists()) {
			return $t->getArticleID();
		}
	}

	private function getArrayFromResultWrapper(&$res, &$dbr) {
		$kbContents = array();
		while ($row = $dbr->fetchObject($res)) {
			$kbEntry = get_object_vars($row);
			$title = Title::newFromID($row->kbc_aid);
			$kbEntry['title_url'] = self::getDomainAdjustedURL($title);
			$kbEntry['date'] = date('Y/m/d H:i:s',
									wfTimestamp(TS_UNIX, $row->kbc_timestamp));
			$kbEntry['total_votes'] = $row->kbc_up_votes + $row->kbc_down_votes;
			$kbEntry['guardian_score'] = $row->kbc_up_votes - $row->kbc_down_votes;
			$kbContents[] = $kbEntry;
		}
		return $kbContents;
	}

	private function getKBContentsByArticleID($aid) {
		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select(
			'knowledgebox_contents',
			array('*'),
			array(
				'kbc_aid' => $aid,
				'kbc_plagiarized' => 0
			),
			__METHOD__,
			array('ORDER BY' => 'kbc_timestamp DESC')
		);

		return $this->getArrayFromResultWrapper($res, $dbr);
	}

	/*
	 * This will get displayed on article pages
	 */
	public static function getCTA(&$t, $vars=array()) {
		global $wgOut, $IP;

		if (!$vars) {
			$vars = array();
		}

		if (self::isActiveArticle($t) || self::isActiveNonArticle($t)) {
			$nTopics = 4;
			$kbTopics = self::getKBTopics($t, $nTopics);
			if (!$kbTopics || empty($kbTopics) || count($kbTopics) < $nTopics) {
				return;
			}

			$skinPath = "$IP/extensions/wikihow/KnowledgeBox/resources";

			EasyTemplate::set_path('');

			$box_tmpls = array();
			foreach ($kbTopics as $kbTopic) {
				$box_tmpls[] = EasyTemplate::html(
					"$skinPath/templates/kb_box.tmpl.php",
					$kbTopic
				);
			}

			$submit_section_tmpl = EasyTemplate::html(
				"$skinPath/templates/kb_submit_section.tmpl.php",
				array()
			);

			$layout_vars = array(
				'kbBoxes' => $box_tmpls,
				'kbSubmitSection' => $submit_section_tmpl,
				'headline' => wfMessage('kb-headline')->text()
			);

			if (isset($vars['layout'])) {
				$layout_vars = array_merge(
					$layout_vars,
					$vars['layout']
				);
			}

			$layout_tmpl = EasyTemplate::html(
				"$skinPath/templates/kb_layout.tmpl.php",
				$layout_vars
			);

			return $layout_tmpl;
		}
	}

	public static function isValidTitle(&$t) {
		return
			$t &&
			$t->exists() &&
			$t->getNamespace() == NS_MAIN &&
			$t->getText() != wfMessage('mainpage')->inContentLanguage()->text() &&
			(self::ALLOW_PROTECTED_PAGES || !$t->isProtected());
	}

	public static function isActiveContext(&$out) {
		if (self::CTA_DISABLED || !$out) {
			return false;
		}

		$context = $out->getContext();

		$t = $context->getTitle();
		$u = $context->getUser();

		return
			(self::isActiveArticle($t) && $u && $u->isAnon())
			|| self::isActiveNonArticle($t);
	}

	public static function isActiveArticle(&$t) {
		if (self::CTA_DISABLED) {
			return false;
		}

		global $wgLanguageCode, $wgRequest;

		return 
			$wgLanguageCode == 'en' &&
			self::isValidTitle($t) &&
			$wgRequest->getVal('oldid') == '' &&
			$wgRequest->getVal('printable') != 'yes' &&
			($wgRequest->getVal('action') == '' ||
				$wgRequest->getVal('action') == 'view');
	}

	public static function isActiveNonArticle(&$t) {
		if (self::CTA_DISABLED) {
			return false;
		}

		global $wgLanguageCode;

		return $wgLanguageCode == 'en' && $t && $t->isSpecial('CreatePage');
	}

	/*
	 * Get a single topic, except those specified in exclude list
	 */
	public static function getKBTopic(&$exclude=NULL) {
		global $wgMemc;

		$cachekey = self::getCacheKey();
		$res = $wgMemc->get($cachekey);
		if (!is_array($res) || !array_key_exists('topics', $res)) {
			// Cache miss: update topic memcache
			$res = self::setTopicMemc();
			if ($res === false) {
				return false;
			}
		}

		if ($exclude == NULL) {
			$exclude = array();
		}

		$raw_rows = array();
		$spentTopicNames = array();

		foreach ($res['topics'] as $row) {
			$raw_rows[] = $row;

			$excludeKey = array_search($row['id'], $exclude);
			if ($excludeKey !== false) {
				$spentTopicNames[strtolower($row['topic'])] = true;
			}
		}

		shuffle($raw_rows);

		foreach ($raw_rows as $row) {
			$excludeKey = array_search($row['id'], $exclude);
			if ($excludeKey !== false) {
				unset($exclude[$excludeKey]);
				continue;
			}

			if (array_key_exists(strtolower($row['topic']), $spentTopicNames)) {
				continue;
			}

			return $row;
		}

		return false;
	}

	/*
	 * Get $n random topics for a title $t.
	 * If a topic is found for $t, it will be one of the returned topics, while
	 * the rest are randomly selected.
	 */
	public static function getKBTopics(&$t, $n=4) {
		global $wgMemc;

		$cachekey = self::getCacheKey();
		$res = $wgMemc->get($cachekey);

		if (!is_array($res) || !array_key_exists('topics', $res)) {
			// Cache miss: update topic memcache
			$res = self::setTopicMemc();
			if ($res === false) {
				return false;
			}
		}

		if ($t && $t->exists()) {
			$aid = $t->getArticleID();
		} else {
			$aid = 0;
		}
		$raw_rows = array();
		$spentTopicNames = array();

		foreach ($res['topics'] as $row) {
			$raw_rows[] = $row;
		}

		$rows = array();

		while ($n !== 0 && count($raw_rows) !== 0) {
			$k = array_rand($raw_rows);
			$row = $raw_rows[$k];
			unset($raw_rows[$k]);

			$topicLower = strtolower($row['topic']);
			if (array_key_exists($topicLower, $spentTopicNames) && $spentTopicNames[$topicLower]) {
				continue;
			}

			$spentTopicNames[$topicLower] = true;

			$rows[] = $row;

			--$n;
		}

		if ($n !== 0) {
			// Not enough data to fill the requested amount
			return array();
		}

		return $rows;
	}

	/*
	 * Try to get a thumbnail for a given article.
	 * If not found, return a pre-selected default image.
	 */
	static function getThumbURL($aid) {
		$title = Title::newFromID($aid);
		$imageFile = false;
		$thumb = false;

		// Default placeholder image
		$thumbURL = self::DEFAULT_THUMBNAIL_URL;

		if ($title && $title->exists()) {
			$imageFile = Wikitext::getTitleImage($title, true);
		}

		if ($imageFile) {
			// Use same transform params as "Related Images"
			$params = array(
				'width' => 127,
				'height' => 140,
				'crop' => 1
			);
			$thumb = $imageFile->transform($params, 0);
		}

		if ($thumb) {
			$thumbURL = $thumb->getUrl();
		}

		return $thumbURL;
	}

	function getQueryInfo() {
		return array(
			'tables' => array(
				'kbc' => 'knowledgebox_contents',
				'kba' => 'knowledgebox_articles'
			),
			'fields' => array(
				'value' => 'kbc_timestamp',
				'kbc.*',
				'kba.kba_topic'
			),
			'conds' => array(),
			'options' => array(),
			'join_conds' => array(
				'kba' => array(
					'LEFT JOIN',
					'kba_id=kbc_kbid'
				)
			)
		);
	}

	public static function clearTopicMemc() {
		global $wgMemc;

		// Clear the relevant key
		$cachekey = self::getCacheKey();
		$wgMemc->delete($cachekey);

		return true;
	}

	/**
	 * Does an inexpensive local check to determine whether content
	 * needs to be checked for plagiarism.
	 */
	public static function isSuspiciousContent($content) {
		return self::CHECK_PLAGIARISM &&
			strlen(utf8_decode($content)) > self::COPYSCAPE_THRESHOLD;
	}

	/**
	 * Returns the longest chain of repeating consecutive elements in given array
	 */
	public static function maxConsecutiveRepeats(&$arr) {
		$max = 0;

		if (!$arr || empty($arr))
			return 0;

		$curElem = $arr[0];
		$curChain = 1;

		foreach ($arr as $k => $elem) {
			if ($k < 1) {
				continue;
			}

			if ($elem === $curElem) {
				$curChain += 1;
			} else {
				$max = max($max, $curChain);
				$curChain = 1;
				$curElem = $elem;
			}
		}

		return max($max, $curChain);
	}

	/**
	 * Does some simple checks to filter out low quality content
	 */
	public static function checkContent(&$content, $debug=false) {
		global $IP;

		$result = array();
		$result['success'] = false;

		// Ignore short submissions
		$len = strlen(utf8_decode($content));
		if ($debug)
			$result['length'] = $len;
		if ($len <= self::MINIMUM_CHARACTERS) {
			return $result;
		}

		// Split text into words delineated by whitespace and UTF8 punctuation
		$wordsArray =
			preg_split(
				'/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', // regex unicode magic
				$content,
				-1,
				PREG_SPLIT_NO_EMPTY
			);
		$wordCount = count($wordsArray);
		if ($debug)
			$result['wc'] = $wordCount;

		// Check for bad word length
		$wordLen = $len / $wordCount;
		if ($debug)
			$result['wordlength'] = $wordLen;
		if ($wordLen < self::MINIMUM_WORD_LENGTH
				|| $wordLen > self::MAXIMUM_WORD_LENGTH) {
			return $result;
		}

		// Check for excessive repeating characters
		$preg_nchars = preg_quote(self::MAXIMUM_REPEATING_CHARACTERS, '/');
		if (preg_match('/(.)\1{' . $preg_nchars . ',}/', $content)) {
			if ($debug)
				$result['preg_nchars'] = true;
			return $result;
		}

		// Check for excessive repeating words
		$wordRepeats = self::maxConsecutiveRepeats($wordsArray);
		if ($debug)
			$result['wordrepeats'] = $wordRepeats;
		if ($wordRepeats > self::MAXIMUM_REPEATING_WORDS) {
			return $result;
		}

		// Check if string has too much punctuation and too many uppercase chars
		// (Note: doesn't work for non-Latin alphabets)
		$filteredLen =
			strlen(utf8_decode(preg_replace('/[A-Z.,!?+-<>@]/u', '', $content)));
		if ($debug)
			$result['filteredlen'] = $filteredLen;
		if ($filteredLen / $len < self::MINIMUM_UPPERCASE_SYMBOLS_RATE) {
			return $result;
		}

		// Check for bad words
		$badWordsFilename = $IP . '/maintenance/wikihow/bad_words_strict.txt';
		$fi = fopen($badWordsFilename, 'r');
		$badWordsArray = array();
		while (!feof($fi)) {
			$fcontent = fgets($fi);
			$fcontent = strtolower(trim($fcontent));
			if ($fcontent != "")
				$badWordsArray[] = $fcontent;
		}

		foreach ($wordsArray as $word) {
			// Currently a zero-tolerance policy
			if (in_array(strtolower($word), $badWordsArray)) {
				if ($debug)
					$result['badword'] = $word;
				return $result;
			}
		}

		$result['success'] = true;
		return $result;
	}

	/**
	 * Queues an entry to get checked for plagiarism through Copyscape
	 */
	public static function pushCopyscapeJob($id, $content) {
		global $wgTitle;
		$jobTitle = $wgTitle;
		$jobParams = array('kbc_id' => $id);
		$job = Job::factory('KnowledgeBoxCopyscapeJob', $jobTitle, $jobParams);
		JobQueueGroup::singleton()->push($job);
	}

	private static function setTopicMemc() {
		global $wgMemc;

		$cachekey = self::getCacheKey();

		$dbr = wfGetDB(DB_SLAVE);

		$dbres = $dbr->select(
			'knowledgebox_articles',
			array('*'),
			array('kba_active' => 1),
			__METHOD__
		);

		if ($dbres === false) {
			// Query error
			return false;
		}

		$topics = array();

		foreach ($dbres as $row) {
			$topics[] = array(
				'id' => $row->kba_id,
				'aid' => $row->kba_aid,
				'topic' => $row->kba_topic,
				'phrase' => $row->kba_phrase,
				'thumbUrl' => self::getThumbURL($row->kba_aid),
				'thumbAlt' => htmlspecialchars( $row->kba_phrase )
			);
		}

		$cacheres = array('topics' => $topics);
		$wgMemc->set($cachekey, $cacheres);

		return $cacheres;
	}

	private static function getCacheKey() {
		return wfMemcKey('kb-article-list');
	}
}

class NewKnowledgeBoxContents extends QueryPage {
	function __construct() {
		parent::__construct('KnowledgeBox');
	}

	function getList() {
		list($limit, $offset) = wfCheckLimits();
		$this->limit = $limit;
		$this->offset = $offset;

		parent::execute('');
	}

	function getPageHeader() {
		global $wgOut;
		$wgOut->setPageTitle('New Solicited Knowledge');
		$wgOut->addModules('ext.wikihow.knowledgebox_pager');

		$html = $this->getHeaderExtras();
		$wgOut->addHtml($html);

		return;
	}

	function getHeaderExtras() {
		$html = '';

		$html .= "<div id='kb_csv_live' class='button secondary kb-download-button'>\n";
		$html .= "<a href='https://parsnip.wikiknowhow.com/x/files/knowledgebox_dump_live.xls'>Download CSV (live articles)</a>\n";
		$html .= "</div>\n";

		$html .= "<div id='kb_csv_all' class='button secondary kb-download-button'>\n";
		$html .= "<a href='https://parsnip.wikiknowhow.com/x/files/knowledgebox_dump_all.xls'>Download CSV (all articles)</a>\n";
		$html .= "</div><br />\n";

		$html .= "<form action='#'>\n";
		$html .= "<input type='text' name='csvurl' placeholder='URL, Title or Article ID' />\n";
		$html .= "<input type='submit' class='button secondary kb-download-button' style='margin-left:16px;' value='Download' />\n";
		$html .= "</form>\n";

		$html .= "<hr width='100%' /><br />\n";

		return $html;
	}

	function getName() {
		return "Knowledge Box Contents";
	}

	function isExpensive() {
		return false;
	}

	function isSyndicated() {
		return false;
	}

	function getQueryInfo() {
		return KnowledgeBox::getQueryInfo();
	}

	function formatResult($skin, $result) {
		global $wgContLang, $wgTitle;

		$title = Title::newFromID($result->kbc_aid);

		$anchorTag = '';
		if ($title && $title->exists()) {
			$url = KnowledgeBox::getDomainAdjustedURL($title, true);
			$anchorTag = "<a href='$url'>{$title->getText()}</a>";
		} else {
			$anchorTag = "ID {$result->kbc_aid}";
		}

		$html = "";
		if ($result->kbc_patrolled) {
			$html .= "<span style='color:#229917'>&#10004</span> &nbsp;&nbsp;";
		}

		$user_text = $result->kbc_user_text;

		$url = KnowledgeBox::getDomainAdjustedURL($title, true);

		$contactHtmlArr = array();
		if ($result->kbc_name) {
			$contactHtmlArr[] = $result->kbc_name;
		}
		if ($result->kbc_email) {
			$contactHtmlArr[] = "<a href='mailto:"
				. $result->kbc_email
				. "?Subject=Thank%20you%20for%20your%20contribution!'>e-mail</a>";
		}

		$contactHtml = '';
		if (count($contactHtmlArr) > 0) {
			$contactHtml = ' ('
				. implode(', ', $contactHtmlArr)
				. ')';
		}

		$plagiarizedHtml = '';
		if ($result->kbc_plagiarized) {
			$plagiarizedHtml = " (<b>plagiarized</b>)";
		}

		$plagiarismNotCheckedHtml = '';
		if (!$result->kbc_plagiarism_checked && !$result->kbc_plagiarism_ignore) {
			$plagiarismNotCheckedHtml = " (plagiarism not checked)";
		}

		$html .= $anchorTag
			. " (topic: {$result->kba_topic}) by "
			. "<a href='" . $wgTitle::makeTitleSafe(NS_USER_TALK, $user_text) . "'>"
			. $user_text . "</a>"
			. $contactHtml
			. $plagiarizedHtml
			. $plagiarismNotCheckedHtml
			. " on "
			. date('Y/m/d H:i:s', wfTimestamp(TS_UNIX, $result->kbc_timestamp)) . "<br />"
			. $result->kbc_content;

		return $html;
	}
}

