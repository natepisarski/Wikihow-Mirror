<?php
namespace ContentPortal;
require '../../ext-utils/mvc/CLI.php';
require '../vendor/autoload.php';

require '../../ext-utils/SqlSuper.php';
require __DIR__ . '/traits/DataMigration.php';

use MVC\CLI;
use User as WhUser;
use __;
use Title;
use SqlSuper;

Event::$silent = true;
CLI::$logSql = false;

class Cleanup extends CLI {

	use DataMigration;

	public $defaultMethod = 'prompt';

	function prompt() {
		self::trace("Please specify a method with --method={your method}", 'Red');
	}

	function fixDupRoles() {

		foreach(User::all() as $user) {
			$roleIds = [];
			foreach($user->user_roles as $assoc) {
				if (__::contains($roleIds, $assoc->role_id)) {
					self::trace("#{$user->username} has it #{$assoc->role_id}", red);
					$assoc->delete();
				}
				array_push($roleIds, $assoc->role_id);
			}
		}
	}

	function orphans() {
		__::chain(UserArticle::all(['include' => ['article']]))->filter(function ($assoc) {
			return $assoc->article == null;
		})->invoke('delete');

		__::chain(Note::all(['include' => ['article']]))->filter(function ($note) {
			return $note->article == null;
		})->invoke('delete');
	}

	function withNoState() {
		$articles = Article::all(['state_id' => null]);
		self::trace(count($articles) . " found with no state");
		__::invoke($articles, 'delete');
	}

	function reassignArticles() {
		self::$logSql = false;
		$articles = Article::all();
		$bad = [];

		foreach($articles as $article) {
			$vals = [
				'article_id' => $article->id,
				'user_id'    => $article->assigned_id,
				'role_id'    => $article->state_id
			];

			$assignment = UserArticle::find(['conditions' => $vals]);

			if ($article->assigned_id != null && is_null($assignment)) {
				array_push($bad, $article->attributes());
				UserArticle::create($vals);
			}
		}

		self::toCSV($bad);
	}

	function lookForSkips() {
		$curState = '';
		foreach(Article::all(['order' => 'state_id ASC']) as $article) {
			$assignments = UserArticle::all(['conditions' => ['article_id' => $article->id], 'order' => 'created_at ASC']);
			if ($curState !== $article->state->present_tense) {
				$curState = $article->state->present_tense;
				self::trace("\n::::::::::  In $curState ::::::::::::", "Green");
			}

			if (count($assignments) > 1) {
				$roles = [];
				foreach($assignments as $assignment) {
					array_push($roles, $assignment->role->title);
				}
				self::trace("Roles:: " . implode(', ', $roles) . "  |  id::{$article->id}", "Cyan");
			}
		}
	}

	function renameUsers() {
		$renames = [
			"Verifier_V_Pippa" => "Pippa Elliott, MRCVS",
			"Proofreader_MH_Kirsten" => "Kirsten Schuder",
			"Proofreader_M_Zora" => "Zora Degrandpre, N.D.",
			"Writer_MH_blujean" => "Jessica B. Casey",
		];

		foreach($renames as $oldName => $newName) {
			$whUser = WhUser::newFromName($this->username);
			self::trace($whUser->getId());
		}
	}

}

$maintClass = 'ContentPortal\\Cleanup';
require RUN_MAINTENANCE_IF_MAIN;
