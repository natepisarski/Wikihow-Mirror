<?php
namespace ContentPortal;
use MVC\Controller;
use __;

global $IP;
require_once("$IP/extensions/wikihow/tags/ConfigStorage.php");
use ConfigStorage;

/*
CREATE TABLE `cf_assign_rules` (
	`id` INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`name` VARBINARY(256) NOT NULL DEFAULT '',
	`from_type` VARBINARY(64) NOT NULL DEFAULT '',
	`from_name` VARBINARY(256) NOT NULL DEFAULT '',
	`to_type` VARBINARY(64) NOT NULL DEFAULT '',
	`to_name` VARBINARY(256) NOT NULL DEFAULT '',
	`max` INT(11) NOT NULL DEFAULT 0,
	`rule_type` VARBINARY(64) NOT NULL DEFAULT '',
	`priority` INT(4) NOT NULL DEFAULT 0,
	`disabled` TINYINT(1) NOT NULL DEFAULT 0,
	KEY (`priority`),
	KEY (`rule_type`)
);
*/
class AssignRule extends AppModel {

	static $all;

	//from_type & to_type
	const TYPE_USER		= 'user';
	const TYPE_BUCKET	= 'bucket';

	//just from_type
	const TYPE_CATEGORY	= 'category';
	const TYPE_CATCHALL	= 'catchall'; //doesn't require a from_name

	//rule_type
	const RULE_TYPE_REVIEW 		= 'review';
	const RULE_TYPE_QUESTION 	= 'question';

	//ConfigStorage keys (buckets)
	const BUCKET_EDITORS_GENERAL	= 'portal_general_editors';
	const BUCKET_EDITORS_TECH			= 'portal_tech_editors';
	const BUCKET_REVIEWERS				= 'portal_reviewers'; //*make sure not to have ACrain in here

	static $table_name = 'cf_assign_rules';

	static $log_vars = ['grouping' => Event::RULES];

	static function loadAllRuleIds() {
		return __::pluck(self::allFromCache(), 'id');
	}

	static function loadArticleRules() {
		return __::chain(self::allFromCache())->filter(['disabled' => 0, 'rule_type' => self::RULE_TYPE_REVIEW])->sortBy('priority')->value();
	}

	static function loadQuestionRules() {
		return __::chain(self::allFromCache())->filter(['disabled' => 0, 'rule_type' => self::RULE_TYPE_QUESTION])->sortBy('priority')->value();
	}

	static function findById($id) {
		return __::find(self::allFromCache(), ['id' => $id]);
	}

	/**
	 * autoAssignArticle()
	 * - auto-assign an article when it goes to review
	 */
	public static function autoAssignArticle($article) {
		self::$log_vars['article'] = $article;

		//get all the rules
		$rules = self::loadArticleRules();

		//run this through all our applicable rules to get the $to_user
		$to_user = self::runRules($rules);

		//auto-assign it
		self::assign($article, $to_user);
	}

	/**
	 * autoAssignQuestion()
	 * - auto-assign an article when a question is asked
	 */
	public static function autoAssignQuestion($note) {
		$article = Article::find($note->article->id);
		self::$log_vars['article'] = $article;

		//get the question rules
		$rules = self::loadQuestionRules();

		//run this through all our applicable rules to get the $to_user
		$to_user = self::runRules($rules);

		//make the assignment
		self::assign($article, $to_user);
	}

	function disable() {
		$this->update_attributes(['disabled' => 1]);
		Event::log("__Rule: _{{rule.name}}_ (#{{rule.id}})__ was disabled by __{{currentUser.username}}__", Event::RED, ['rule' => $this, 'grouping' => Event::RULES]);
		return true;
	}

	function enable() {
		$this->update_attributes(['disabled' => 0]);
		Event::log("__Rule: _{{rule.name}}_ (#{{rule.id}})__ was enabled by __{{currentUser.username}}__", Event::GREEN, ['rule' => $this, 'grouping' => Event::RULES]);
		return true;
	}

	/**
	 * assign()
	 * - our main function to auto-assign to a specific user
	 * - "assigned by Dr. Carrie"
	 *
	 * @param $article = ActiveRecord Article object
	 * @param $to_user = to whom this is auto-assigned
	 */
	private static function assign($article, $to_user) {
		if (empty($article) || empty($to_user)) return;

		// temp set the current user as Dr. Carrie
		$inst = Controller::getInstance();
		$inst->currentUser = User::find_by_username(CARRIE);

		//A-S-S-I-G-N!!!
		Assignment::build($article)->create($to_user);

		//get the real user
		$inst->currentUser = Auth::findCurrentUser();

		//log auto-assign rule event
		self::$log_vars['article_edit_url'] = url('articles/edit', ['id' => $article->id]);

		if (self::$log_vars['rule']->rule_type == self::RULE_TYPE_REVIEW) {
			$event_msg = "<a href='{{article.wh_article_url}}' target='_blank'>__{{article.title}}__</a> edited by __{{from_username}}__ assigned to __{{to_username}}__ for review";
		}
		else {
			$event_msg = "Question asked by __{{from_username}}__ on <a href='{{article.wh_article_url}}' target='_blank'>__{{article.title}}__</a> assigned to __{{to_username}}__";
		}

		Event::log("__Rule: _{{rule.name}}_ (#{{rule.id}})__ - ".$event_msg." (<a href='{{article_edit_url}}' target='_blank'>edit</a>)", Event::BLUE, self::$log_vars);
	}

	/**
	 * runRules()
	 * - run through a group of db row rules
	 *
	 * @param $rules = a gaggle of rules
	 */
	private static function runRules($rules) {
		$to_user = null;

		//cycle through the rules
		foreach ($rules as $rule) {
			$to_user = self::runSingleRule($rule);
			if (!empty($to_user)) break;
		}

		return $to_user;
	}

	private static function runSingleRule($rule) {
		$to_user = null;

		if (self::validateFrom($rule)) {
			$to_user = self::getToUser($rule);
			if (!empty($to_user)) self::$log_vars['rule'] = $rule;
		}

		return $to_user;
	}

	/**
	 * validateFrom()
	 * - checks to see if this rule is applicable based on where it's coming from
	 *
	 * @param $rule = the single db row rule we're using
	 * @return boolean
	 */
	private static function validateFrom($rule) {
		$inst = Controller::getInstance();
		$valid = false;

		//check FROM
		if ($rule->from_type == self::TYPE_USER) {
			$from = User::find_by_username($rule->from_name);
			if (!empty($from)) {
				$valid = $inst->currentUser->id == $from->id;
			}
		}
		elseif ($rule->from_type == self::TYPE_BUCKET) {
			$bucket = ConfigStorage::dbGetConfig($rule->from_name, true);
			$users = explode("\n", $bucket);

			foreach ($users as $u) {
				$from = User::find_by_username($u);
				if (!empty($from)) {
					$valid = $inst->currentUser->id == $from->id;
					if ($valid) break;
				}
			}
		}
		elseif ($rule->from_type == self::TYPE_CATEGORY) {
			$cat = Category::find_by_title($rule->from_name);
			$valid = $inst->article->category_id == $cat->id;
		}
		elseif ($rule->from_type == self::TYPE_CATCHALL) {
			$valid = true;
		}

		if ($valid) self::$log_vars['from_username'] = $inst->currentUser->username;

		return $valid;
	}

	/**
	 * getToUser()
	 * - sets the $to_user_id
	 *
	 * @param $rule = the single db row rule we're using
	 */
	private static function getToUser($rule) {
		$to_user = null;
		if (empty($rule)) return $to_user;

		//grab TO
		if ($rule->to_type == self::TYPE_USER) {
			$to_user = User::find_by_username($rule->to_name);

			//check if this user has reached their max
			if (self::maxxed($rule, $to_user)) return null;
		}
		elseif ($rule->to_type == self::TYPE_BUCKET) {
			$bucket = ConfigStorage::dbGetConfig($rule->to_name, true);
			$users = explode("\n", $bucket);
			$to_user = self::grabFromBucket($users);
		}

		if (!empty($to_user)) self::$log_vars['to_username'] = $to_user->username;

		return $to_user;
	}

	/**
	 * grabFromBucket()
	 * - get the $to_user with the fewest assigned articles from a bucket o' names
	 *
	 * @param $bucket_users = array of usernames
	 * @return User object
	 */
	private static function grabFromBucket($bucket_users) {
		foreach ($bucket_users as $u) {
			if (empty($u)) continue;
			$user = User::find_by_username(trim($u));
			$users[$user->busy()] = $user;
		}

		if (empty($users)) return;

		//put the one w/ the fewest at the top
		ksort($users);

		//return the top one
		return array_shift($users);
	}

	/**
	 * maxxed()
	 * - check if the to: user is maxxed out on articles
	 *
	 * @param $rule
	 * @param $to_user
	 * @return boolean
	 */
	private static function maxxed($rule, $to_user) {
		if (empty($rule->max)) return false;
		return $to_user->busy() >= $rule->max;
	}

}
