<?php

class AnswerQuestions extends UnlistedSpecialPage {
	const MAX_NUM_QUESTIONS = 30;
	const MIN_NUM_QUESTIONS = 1;
	const MAX_NUM_ARTICLES = 1;
	const MAX_TRIES = 5;
	const BATCH_SIZE = 1000;

	const GROUP_PREFIX = "qa_answerquestions_";

	const TABLE_CHECKOUT = "answerquestions";
	const TABLE_QUEUE = "answerquestionsqueue";
	const TABLE_CATEGORY = "qa_category";
	const CHECKOUT_EXPIRY = 86400; //1 day - 60*60*24

	var $skipTool;
	var $userCategory = null;

	const NUM_QUESTIONS_QUEUE = 0;
	const MOST_RECENT_QUEUE = 1;
	static $queue_strings = ['num' => 0, 'new' => 1];

	function __construct() {
		global $wgHooks;
		parent::__construct('AnswerQuestions');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	function execute($par) {
		global $wgHooks;

		$wgHooks['CustomSideBar'][] = array($this, 'makeCustomSideBar');
		$wgHooks['ShowBreadCrumbs'][] = array($this, 'hideBreadcrumb');

		$out = $this->getOutput();
		$out->setHTMLTitle("Answer Questions");

		$this->skipTool = new ToolSkip("AnswerQuestionsTool");

		$request = $this->getRequest();
		$action = $request->getVal("action");
		if ($action == "getNext") {
			$out->setArticleBodyOnly(true);
			$this->getNext();
		} elseif($action == "skip"){
			$out->setArticleBodyOnly(true);
			$this->skip();
		} elseif($action == "setCategory") {
			$out->setArticleBodyOnly(true);
			$category = $request->getVal("category");
			$this->updateUserCategory($category);
			$this->getNext();
		} else {
			$out->addModules(['wikihow.answerquestions_js']);
			$out->addModules(['wikihow.answerquestions_css']);
			$data = $this->getPageData();
			$out->addHTML($this->getDesktopHtml($data));
			$skin = $out->getSkin();
			if ($request->getVal("group", "") == "") {
				$skin->addWidget($this->getCategoryChooser(), 'qat_cat_chooser');
			}
			$skin->addWidget($this->getSidebarHtml(), 'qat_sidebar');
		}
	}

	function getDesktopHtml($data) {
		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);

		return $m->render('tool_desktop', $data);
	}

	function getCategoryChooser() {
		$options = array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);

		$categories = $this->getCategoryTree();
		return $m->render('category_tree_selection', $categories);
	}

	function getSidebarHtml() {
		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);

		return $m->render('tool_sidebar');
	}

	function getPageData() {
		$request = $this->getRequest();
		$user = $this->getUser();
		$vars = array();

		$vars['qat_admin'] = QAWidget::isAdmin($user);
		$vars['qat_staff'] = QAWidget::isStaff($user);
		$vars['qat_group'] = $request->getVal("group", "");
		if ($vars['qat_group'] == "") {
			$vars['qat_showmenu'] = true;
		}

		$expertId = $request->getVal("expert", 0);
		list($isExpert, $expertName) = $this->isExpert($expertId);
		if ($isExpert) {
			$vars['qat_expert'] = $expertId;
		}

		$vars['qat_few_contribs'] = $user->getEditCount() < QAWidget::MINIMUM_CONTRIBS ? 1 : 0;

		$welcomeParam = $request->getVal("welcome", "");
		if ($isExpert) {
			//this overrides any other welcome param in the url
			$welcomeParam = "expert";
		}
		$vars['qat_welcome_name'] = $welcomeParam;
		$welcomeMessage = wfMessage("qa_answerquestions_" . $welcomeParam, $expertName);
		$vars['qat_welcome'] = $welcomeMessage->showIfExists();

		if ($vars['qat_group'] == "") {
			$cat = $request->getVal("cat", "");
			if ( $cat != "" ) {
				$title = Title::newFromText($cat, NS_CATEGORY);
				$vars['qat_user_category'] = $title->getText();
				$this->userCategory = $vars['qat_user_category'];
			} else {
				$vars['qat_user_category'] = $this->getUserCategory();
			}
			$vars['qat_category_type'] = $request->getVal("queue", '');
		}

		return $vars;
	}

	private function isExpert($adminId) {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->selectField(VerifyData::VERIFIER_TABLE, "vi_name", array("vi_id" => $adminId), __METHOD__);
		if ($res !== false) {
			return array(true, $res);
		} else {
			return array(false, "");
		}
	}

	private function skip() {
		$request = $this->getRequest();
		$aid = $request->getVal("aid");
		$this->skipTool->skipItem($aid);
		$this->checkinArticle($aid);
		$this->getNext();
	}

	function getNext() {
		$request = $this->getRequest();
		$group = $request->getVal("group", "");
		$expertId = $request->getVal("expert", 0);
		$queueType = $request->getVal("queue", '');
		if ($queueType == '') {
			$queueNum = 0;
		} else {
			if (array_key_exists($queueType, self::$queue_strings)) {
				$queueNum = self::$queue_strings[$queueType];
			} else {
				$queueNum = 0;
			}
		}
		$category = html_entity_decode($request->getVal("category", ""));

		$exp_answered = false;

		list($qs, $t) = self::getQuestionArray($group, $category, $expertId, $queueNum);
		if (count($qs) == 0) {
			$this->skipTool->clearSkipCache();
			print json_encode(array(
				'error' => 1
			));
			return;
		}

		$this->skipTool->skipItem($t->getArticleID());

		$qadb = QADB::newInstance();

		//check to see if this expert has already answered
		//some questions on this article
		if ($expertId) {
			$limit = 2;
			$exp_answered = $qadb->alredyExpertAnswered($t->getArticleID(), $expertId, $limit);
		}

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);

		$m = new Mustache_Engine($options);
		$data = array();
		$data['questions'] = $qs;
		$data['aid'] = $t->getArticleID();

		$html = $m->render('tool_questions', $data);

		//now get the questions for the right rail
		$aqs = $qadb->getArticleQuestions([$t->getArticleID()]);
		$qaArray = array();
		foreach ($aqs as $aq) {
			$qaArray[] = $aq->getCuratedQuestion()->getText();
		}

		print json_encode(array(
			'qhtml' => $html,
			'link' => $t->getFullURL(),
			'title' => wfMessage("howto", $t->getText())->text(),
			'aid' => $t->getArticleID(),
			'aqs' => $qaArray,
			'exp_answered' => $exp_answered ? wfMessage('qat_exp_answered')->text() : ''
			));
	}

	/******
	 *
	 * Gets the array of questions for next article in the queue
	 * for the given group.
	 *
	 ******/
	private function getQuestionArray($param = '', $category = '', $expertId = 0, $queueNum = 0) {
		if ($param != '') {
			return $this->getQuestionArrayGroup($param, $expertId);
		}

		return $this->getQuestionArrayQueue($category, $queueNum);
	}

	private function getQuestionArrayGroup($param = 'primary', $expertId = 0) {
		$queueName = self::GROUP_PREFIX . $param;
		$stringList = trim(ConfigStorage::dbGetConfig($queueName));
		if ($stringList === false || $stringList == "") {
			$queueName = self::GROUP_PREFIX . 'primary';
			$stringList = ConfigStorage::dbGetConfig($queueName);
		}

		$queue = explode("\n", $stringList);
		if (count($queue) == 0) {
			return $queue;
		}

		$skippedIds = $this->skipTool->getSkipped();
		if (is_array($skippedIds) && !empty($skippedIds)) {
			$queue = array_diff($queue, $skippedIds);
		}
		if (count($queue) == 0) {
			return $queue;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$expirytimestamp = wfTimestamp( TS_MW, time() - self::CHECKOUT_EXPIRY );
		$finalQueue = array();
		do{
			$subqueue = array_splice($queue, 0, self::BATCH_SIZE);
			//look for articles that have been checked out and can't be used
			$res = $dbr->select(self::TABLE_CHECKOUT, array('aq_page_id'), array("aq_checkout_timestamp >= '{$expirytimestamp}'", "aq_page_id IN (" . $dbr->makeList($subqueue) . ")"), __METHOD__);
			$badArray = array();
			foreach ($res as $row) {
				$badArray[] = $row->aq_page_id;
			}
			$finalQueue = array_merge($finalQueue, array_diff($subqueue, $badArray));
			if (count($finalQueue) >= self::MAX_TRIES) {
				//we're going to check MAX_TRIES articles to see if there are questions left
				//so we need that many to move forward
				break;
			}
		} while (count($queue) > 0);

		if (count($finalQueue) == 0) {
			return $finalQueue;
		}

		$sqs = [];
		$qs = [];
		$qadb = QADB::newInstance();
		$aidToRemove = array();

		//now let's see if any of the articles has any questions, we only need 1 article!!
		foreach ($finalQueue as $aid) {
			if ($expertId > 0) {
				$sqs = $qadb->getSubmittedQuestions($aid, 0, self::MAX_NUM_QUESTIONS, false, false, true);
			} else {
				$sqs = $qadb->getSubmittedQuestions($aid, 0, self::MAX_NUM_QUESTIONS);
			}

			//make sure it's real and has enough questions
			$t = Title::newFromId($aid);
			if ($t && $t->exists() && count($sqs) >= self::MIN_NUM_QUESTIONS){
				break; //got one
			}

			if ($expertId > 0) {
				//since we're an expert, don't remove it from the queue
				//but skip it since we don't want to see it again
				$this->skipTool->skipItem($aid);
			} else {
				//uh oh, let's remove that from our queue...
				$aidToRemove[] = $aid;
			}
		}

		if (count($aidToRemove) > 0) {
			//let's get rid of these articles. They don't have any questions left
			self::removeIdsFromQueue($queueName, $aidToRemove);
		}

		if (!$t || !$t->exists()) {
			return array();
		}
		self::checkoutArticle($t->getArticleID());

		foreach ($sqs as $sq) {
			$qs[] = [
				'qat_question' => $sq->getText(),
				'qat_question_id' => $sq->getId()
			];
		}

		return array($qs, $t);
	}

	private function getQuestionArrayQueue($category, $queueNum = 0) {
		//make sure category has dashes, not spaces
		$category = str_replace(" ", "-", $category);

		$expirytimestamp = wfTimestamp( TS_MW, time() - self::CHECKOUT_EXPIRY );
		$dbr = wfGetDB(DB_REPLICA);

		$skippedIds = $this->skipTool->getSkipped();
		$where = [
					"aqq_category" => $category,
					"aqq_category_type" => $queueNum,
					"(aq_checkout_timestamp < '{$expirytimestamp}' || aq_checkout_timestamp IS NULL)"
				];
		if (is_array($skippedIds) && !empty($skippedIds)) {
			$where[] = "aqq_page not in (" . $dbr->makeList($skippedIds) . ")";
		}

		$res = $dbr->select(
			array(AnswerQuestions::TABLE_QUEUE, self::TABLE_CHECKOUT),
			"*",
			$where,
			__METHOD__,
			array("ORDER BY" => "aqq_id ASC", "LIMIT" => self::MAX_TRIES),
			array(self::TABLE_CHECKOUT => array(
				"LEFT JOIN", "aqq_page = aq_page_id"))
		);

		$idsToRemove = array();
		$qadb = QADB::newInstance();
		foreach ($res as $row) {
			$sqs = $qadb->getSubmittedQuestions($row->aqq_page, 0, self::MAX_NUM_QUESTIONS, false, false, false, false, true);
			//make sure it's real and has enough questions
			$t = Title::newFromId($row->aqq_page);
			if ( $t && $t->exists() && count($sqs) >= self::MIN_NUM_QUESTIONS ) {
				break; //got one
			}

			//ok, let's remove this article from all queues
			$idsToRemove[] = $row->aqq_page;
		}

		if (count($idsToRemove) > 0) {
			//let's get rid of these articles. They don't have any questions left
			self::removeIdsFromDbQueue($idsToRemove);
		}

		if (!$t || !$t->exists()) {
			return array();
		}
		self::checkoutArticle($t->getArticleID());

		//this assumes that the questions are in time order
		$onemonthago = wfTimestamp(TS_MW, strtotime("1 month ago"));
		foreach ($sqs as $index => $sq) {
			$ts = $sq->getSubmittedTimestamp();
			if ($queueNum == self::MOST_RECENT_QUEUE && $onemonthago > $ts) {
				break;
			}
			$dt1 = new DateTime($ts);
			$qs[] = [
				'qat_question' => $sq->getText(),
				'qat_question_id' => $sq->getId(),
				'qat_submit_date' => UserReview::getFormattedDate($ts),//$dt1->format("M j, Y")
				'qat_submit_old' => ($onemonthago > $ts) ? 1 : 0
			];
		}

		return array($qs, $t);
	}

	private function removeIdsFromDbQueue($idsToRemove) {
		if (is_array($idsToRemove) && count($idsToRemove) > 0) {
			$dbw = wfGetDb(DB_MASTER);

			$dbw->delete(self::TABLE_QUEUE, array("aqq_page IN (" . $dbw->makeList($idsToRemove) . ")"), __METHOD__);
		}
	}

	/******
	 *
	 * The queues are stored in AdminConfig lists. The function removes
	 * the given article id from the given list.
	 *
	 ******/
	private function removeIdsFromQueue($queueName, $aidToRemove) {
		global $wgUser;

		$oldUser = $wgUser;
		$wgUser = null;
		$stringList = ConfigStorage::dbGetConfig($queueName);
		if ($stringList !== false) {
			$queue = explode("\n", $stringList);
			$finalArray = array_diff($queue, $aidToRemove);
			$err = "";
			ConfigStorage::dbStoreConfig($queueName, implode("\n", $finalArray), false, $err);
		}
		$wgUser = $oldUser;
	}

	private function checkoutArticle($aid = 0) {
		$dbw = wfGetDB(DB_MASTER);

		$timestamp = wfTimestampNow();
		$dbw->upsert(self::TABLE_CHECKOUT, array('aq_page_id' => $aid, 'aq_checkout_timestamp' => $timestamp), array('aq_page_id'), array('aq_page_id' => $aid, 'aq_checkout_timestamp' => $timestamp), __METHOD__);
	}

	private function checkinArticle($aid = 0) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete(self::TABLE_CHECKOUT, array('aq_page_id' => $aid), __METHOD__);
	}

	public static function makeCustomSideBar(&$customSideBar) {
		$customSideBar = true;
		return true;
	}

	public static function hideBreadcrumb(&$breadcrumb) {
		$breadcrumb = false;
		return true;
	}

	public static function getAllCategories() {
		$categories = array();
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(AnswerQuestions::TABLE_QUEUE, array('aqq_category', 'aqq_queue_timestamp', 'count(*) as total'), array('aqq_queue_timestamp != ""', 'aqq_category_type' => AnswerQuestions::NUM_QUESTIONS_QUEUE), __METHOD__, array('GROUP BY' => 'aqq_category'));
		foreach ($res as $row) {
			$categories[] = (object)['category' => $row->aqq_category, 'timestamp' => date("d F Y", wfTimestamp(TS_UNIX, $row->aqq_queue_timestamp)), 'count' => $row->total];
		}
		return $categories;
	}

	private function getCategoryTree(){
		$topUserCategory =  "";
		if ($this->userCategory != null && $this->userCategory != "") {
			$title = Title::newFromText($this->userCategory, NS_CATEGORY);
			$parents = CategoryHelper::getCurrentParentCategoryTree($title);
			$parents = CategoryHelper::flattenCategoryTree($parents);
			if (is_null($parents)) {
				$topUserCategory = $this->userCategory;
			} else {
				$topUserCategory =  str_replace("-", " ", substr($parents[count($parents) - 1], 9));
			}
		}
		$categoryTree = [];
		$categories = self::getAllCategories();
		foreach ($categories as $categoryData) {
			$title = Title::newFromText($categoryData->category, NS_CATEGORY);
			$parents = CategoryHelper::getCurrentParentCategoryTree($title);
			$parents = CategoryHelper::flattenCategoryTree($parents);
			if (is_null($parents)) {
				$parent = $categoryData->category;
			} else {
				$parent =  str_replace("-", " ", substr($parents[count($parents) - 1], 9));
			}

			if ($categoryTree[$parent] == null) {
				$categoryTree[$parent] = [];
			}
			$cat = str_replace("-", " ", $categoryData->category);
			$categoryTree[$parent][] = ['category' => $cat, 'class' => ($cat == $this->userCategory)?'selected':''];
		}

		//now sort
		ksort($categoryTree);
		$categoryData = ['categories' => []];
		foreach ($categoryTree as $key => $value) {
			$categoryData['categories'][] = ['category' => $key, 'subcategories' => $value, 'id' => str_replace(".", "", Sanitizer::escapeId($key, 'noninitial')), 'class' => ($topUserCategory==$key?"selected":"")];
		}

		return $categoryData;
	}

	private function updateUserCategory($category) {
		$user = $this->getUser();

		$dbw = wfGetDB(DB_MASTER);
		$cond = array('qac_category' => $category);
		$cond['qac_user_id'] = $user->getId() ? $user->getId() : 0;

		if ($user->isAnon()) {
			$cond['qac_visitor_id'] = WikihowUser::getVisitorId();

			//can't be anon AND w/o a visitor id...UH YES YOU CAN
			//if ($cond['qac_visitor_id'] == '') return false;
		}

		$this->userCategory = $category;

		return $dbw->upsert(self::TABLE_CATEGORY, $cond, array(), $cond, __METHOD__);
	}

	private function getUserCategory() {
		if ($this->userCategory == null) {
			$user = $this->getUser();
			$cond = [];

			if (!$user->isAnon()) {
				$cond['qac_user_id'] = $user->getId();
			} else {
				$cond['qac_visitor_id'] = WikihowUser::getVisitorId();
				if ($cond['qac_visitor_id'] == '') { //they are not logged in and don't have a visitor id, so assume no category.
					$this->userCategory = "";
					return $this->userCategory;
				}
			}

			$dbr = wfGetDB(DB_REPLICA);
			$category = $dbr->selectField(self::TABLE_CATEGORY, 'qac_category', $cond, __METHOD__);
			if ( $category === false ) {
				$this->userCategory = "";
			} else {
				$this->userCategory = $category;
			}
		}

		return $this->userCategory;
	}
}
 /*******
CREATE TABLE `answerquestions` (
	`aq_page_id` int(10) unsigned NOT NULL,
	`aq_checkout_timestamp` varbinary(14) NOT NULL DEFAULT '',
	UNIQUE KEY `aq_page_id` (`aq_page_id`),
	KEY `aq_checkout_timestamp` (`aq_checkout_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
  *****/

/*****
CREATE TABLE `answerquestionsqueue` (
	`aqq_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`aqq_page` int(10) unsigned NOT NULL,
	`aqq_category` varchar(255) NOT NULL,
	`aqq_queue_timestamp` varbinary(14) NOT NULL DEFAULT '',
	PRIMARY KEY `aqq_id` (`aqq_id`),
	KEY `aqq_category` (`aqq_category`),
	KEY `aqq_queue_timestamp` (`aqq_queue_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `qa_category` (
	`qac_user_id` mediumint(8) unsigned NOT NULL default '0',
	`qac_visitor_id` varbinary(20) NOT NULL default '',
	`qac_category` varchar(255) NOT NULL default '',
	PRIMARY KEY  (`qac_user_id`,`qac_visitor_id`),
	KEY `qac_category` (`qac_category`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1

ALTER TABLE `answerquestionsqueue` ADD COLUMN `aqq_category_type` tinyint(3) unsigned NOT NULL default 0;
ALTER TABLE `answerquestionsqueue` ADD KEY `aqq_cateogry_list`(aqq_category, aqq_category_type);

*******/
