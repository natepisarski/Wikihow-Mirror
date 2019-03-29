<?php
namespace ContentPortal;
use MVC\Controller;
use Mustache_Engine;
use Michelf\Markdown;
use __;

class Event extends AppModel {
	public $skipLog = true;
	static $silent = false;
	static $table_name = 'cf_events';
	static $belongs_to = ['article', 'user'];

	const RED    = 'danger';
	const GREEN  = 'success';
	const BLUE   = 'info';
	const YELLOW = 'warning';

	const GENERAL = 'general';
	const RULES = 'rules';

	static function log($msg, $type=self::GREEN, $extraVars=[]) {
		if (self::$silent) return;

		$vars = __::extend(Controller::getInstance()->viewVars, $extraVars);

		if (array_key_exists('article', $vars)) {
			$articleId = $vars['article']->id;
		} elseif (params('wh_article_id')) {
			$articleId = Article::find_by_wh_article_id(params('wh_article_id'))->id;
		} else {
			$articleId = null;
		}

		if ($articleId) $vars['article'] = Article::find($articleId);

		$grouping = array_key_exists('grouping', $vars) ? $vars['grouping'] : self::GENERAL;

		$msg = (new Mustache_Engine)->render($msg, $vars);
		$msg = Markdown::defaultTransform($msg);

		Event::create([
			"logged_user_id" => $vars['currentUser']->id,
			"article_id" =>  $articleId,
			"message" => $msg,
			"type" => $type,
			"grouping" => $grouping
		]);

	}
}
