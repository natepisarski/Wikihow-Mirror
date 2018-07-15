<?
namespace ContentPortal;
require __DIR__ . '/../vendor/autoload.php';

use MVC\CLI;
use MVC\Engines\EasyTemplateEngine;
use UserMailer;
use MailAddress;
use Title;
use MVC\Logger;
use __;

Logger::$enabled = false;
Event::$silent = true;
CLI::$logSql = false;

class Nightly extends CLI {

	public $dryRun = false;
	public $results = ['redirects' => [], 'deletes' => [], 'moves' => []];
	public $defaultMethod = 'syncArticles';

	const REDIRECTS = 'redirects';
	const MOVES = 'moves';
	const DELETES = 'deletes';

	function syncArticles() {
		foreach($this->linkedArticles() as $article) {
			$title = Title::newFromId($article->wh_article_id);

			if (is_null($title) || !$title->exists()) {
				$this->log(self::DELETES, ['article' => $article]);
				if (!$this->dryRun) $article->update_attribute('is_deleted', true);
				self::trace("DELETE::{$article->title}::{$article->wh_article_id}", 'red');

			} else {
				if (!$this->flagRedirect($title, $article)) {
					$this->matchTitles($title, $article);
				}
			}
		}

		$this->sendMail();
		$this->updateMoves();
	}

	function flagRedirect($title, $article) {
		$redirect = $title->isRedirect();
		if ($redirect) {
			self::trace($article->title, 'red');
			if (!$this->dryRun) $article->update_attribute('is_redirect', true);
			self::trace("REDIRECT::{$article->title}::{$article->wh_article_id}", 'yellow');
			$this->log(self::REDIRECTS, ['title' => $title, 'article' => $article]);
		}
		return $redirect;
	}

	function matchTitles($title, $article) {
		if ($title->getText() !== $article->title) {
			$this->log(self::MOVES, ['article' => $article, 'title' => $title]);
			self::trace("MOVE::{$article->wh_article_id}::{$article->title} -> {$title->getText()}", 'green');
		}
		return $this;
	}

	public function sendMail() {
		if (!self::getConfig()->sendMail) return;

		$renderer = new EasyTemplateEngine();
		$body = $renderer->render("mail/nightly", ['results' => $this->results]);

		$from = new MailAddress('noreply@wikihow.com');
		$users = User::all(['conditions' => ['send_mail' => true]]);
		$recips = __::map($users, function ($user) {
			return new MailAddress($user->whUser());
		});

		if (empty($recips)) {
			self::trace("There are no admins set as recipients", ['white', 'bg_red']);
		} else {
			self::trace("  Sending nightly to  ", ['black', 'bg_green']);
			self::trace($recips, 'green');
		}

		foreach($recips as $to) {
			UserMailer::send($to, $from, 'article sync', $body, null, 'text/html; charset=utf8');
		}
		self::trace(UserMailer::$mErrorString);
	}

	public function createDump() {
		ArticleCSV::dumpToFile();
	}

	// PRIVATE METHODS

	private function updateMoves() {
		if ($this->dryRun) return;
		foreach ($this->results[self::MOVES] as $move) {
			$move['article']->update_attribute('title', $move['title']->getText());
		}
	}

	private function log($type, $msg) {
		array_push($this->results[$type], $msg);
	}

	private function linkedArticles() {
		return $articles = Article::all(['conditions' => ['wh_article_id is not null']]);
	}
}

$maintClass = 'ContentPortal\\Nightly';
require RUN_MAINTENANCE_IF_MAIN;
