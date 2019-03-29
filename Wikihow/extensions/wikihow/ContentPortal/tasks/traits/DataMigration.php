<?php
namespace ContentPortal;
trait DataMigration {

	function findMissingArticles() {
		$articles = Article::all(['wh_article_id' => null]);
		$db = new \SqlSuper();
		$found = 0;

		foreach($articles as $article) {
			$title = \Title::newFromText($article->title);
			if ($title->exists()) {
				$id  = $title->getArticleId();
				$sql = "UPDATE cf_articles SET wh_article_id = $id WHERE id = {$article->id};";
				Article::connection()->query($sql);
				$found ++;

			} else {
				$result = $db->selectFirst('page', '*',
					[
						'page_namespace = 0',
						'page_is_redirect = 0',
						'lower(page_title)' => strtolower($title->getDBkey())
					]
				);

				if ($result) {
					self::trace("found one");
					$article->syncWithId($result->page_id);
					self::trace($result->page_title);
					$found ++;

				} else {
					echo(".");
				}
			}
		}

		self::trace("Found: $found", 'Green');
		self::trace("Missing: " . count($articles), 'Red');
	}

	function flagRedirects() {
		$articles = Article::all(['conditions' => 'wh_article_id is not null']);

		foreach($articles as $article) {
			$title = \Title::newFromId($article->wh_article_id);

			if ($title->isRedirect()) {
				self::trace("\n{$article->title} is a redirect", 'Red');
				$article->is_redirect = true;
			}
			if (!$title->exists()) {
				self::trace("\n{$article->title} no longer linked", 'Yellow');
				$article->wh_article_id = null;
			}
			if (!empty($article->dirty_attributes())) {
				$article->save(false);
			}
		}
		self::trace('Done', 'Green');
	}

	function findDeleted() {
		$articles = Article::all(['conditions' => 'wh_article_id is not null']);
		$deleted = [];

		foreach ($articles as $article) {
			$title = \Title::newFromId($article->wh_article_id);
			if ($title->isDeleted()) {
				array_push($deleted, "{$article->title}, {$article->id}");
			}
		}

		self::trace($deleted);
	}

	function findMoves() {
		$articles = Article::all(['conditions' => 'wh_article_id is not null']);
		$db = new \SqlSuper();

		foreach($articles as $article) {
			$move = $db->select(
				'recentchanges', '*',
				[
					"rc_this_oldid = {$article->wh_article_id}",
					"rc_log_action" => "move",
					"rc_namespace" => NS_MAIN
				], __METHOD__
			);
			if ($move) {
				self::trace($move, 'Red');
			}
		}
	}

}
