<?php
namespace ContentPortal;
require '../vendor/autoload.php';

use MVC\Logger;
use Title;

Event::$silent = true;
Logger::$enabled = false;

use MVC\Migrate;
use ActiveRecord\DateTime;
use __;

class Migrations extends Migrate {

	public $groups = [
		[8.3, 'addRecieveEmail'],
		[8.4, 'updateTitles'],
		[8.5, 'bulkComplete'],
		[8.6, 'removeCatMatch'],
		[8.7, 'addIsDeleted'],
		[8.8, 'declineVerification'],
		[8.9, 'addNotesToUsers'],
		[9.0, 'outdatedDocs'],
		[9.1, 'makeCompletePublic']
	];

	function __construct() {
		parent::__construct();
		Config::getInstance()->cacheModels = false;
	}

	function addRecieveEmail() {
		User::addColumn('send_mail', 'tinyint(2)', ['DEFAULT' => 0]);
		User::addIndex('send_mail');
	}

	function updateTitles() {
		foreach(Article::all() as $article) {
			$url = URL_PREFIX . Title::newFromText($article->title)->getPartialUrl();

			if ($url != $article->wh_article_url) {
				self::trace("updating {$article->title}", 'yellow');
				$article->update_attribute('wh_article_url', $url);
			}
		}
	}

	function bulkComplete() {
		Role::complete()->update_attribute('bulk_action_allowed', true);
	}

	function removeCatMatch() {
		Role::removeColumn('require_category_match');
	}

	function addIsDeleted() {
		Article::addColumn('is_deleted', 'tinyint(2)', ['DEFAULT' => 0]);
	}

	function declineVerification() {
		Role::verify()->update_attribute('can_decline', true);
	}

	function addNotesToUsers() {
		User::addColumn('note', 'text');
	}

	function outdatedDocs() {
		Document::addColumn('outdated', 'tinyint(2)', ['DEFAULT' => 0]);
	}

	function makeCompletePublic() {
		Role::complete()->update_attributes([
			"public" => true,
			'is_on_hold' => true
		]);

		Role::verify()->update_attribute('is_on_hold', true);
	}
}

$maintClass = 'ContentPortal\\Migrations';
require RUN_MAINTENANCE_IF_MAIN;
