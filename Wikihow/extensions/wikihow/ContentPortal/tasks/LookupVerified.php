<?php
namespace ContentPortal;
require '../../ext-utils/mvc/CLI.php';
require '../vendor/autoload.php';
require SERVER_ROOT . "/extensions/wikihow/socialproof/VerifyData.php";

use VerifyData;
use MVC\CLI;

class LookupVerified extends CLI {

	public $defaultMethod = 'findVerified';

	function findVerified() {
		$articles = Article::all(['include' => ['state']]);

		foreach($articles as $article) {
			if ($article->wh_article_id) {
				$data = VerifyData::getByPageId($article->wh_article_id);

				if ($data && !in_array($article->state->key, [Role::COMPLETE_KEY, Role::VERIFY_KEY])) {
					self::trace("{$article->id},". '"'. $article->wh_article_url . '"' . ",{$article->wh_article_id},{$article->state->present_tense}", 'Green');
				}
			}
		}
	}
}

$maintClass = 'ContentPortal\\LookupVerified';
require RUN_MAINTENANCE_IF_MAIN;
