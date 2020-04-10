<?php

require_once __DIR__ . '/../Maintenance.php';

class updateHomepageNewpages extends Maintenance {

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		NewPages::setHomepageArticles();

		//email Graham the new set of homepage articles
		$newPages = NewPages::getHomepageArticles();
		$ids = "";
		foreach($newPages as $title) {
			if($title && $title->exists()) {
				$ids .= $title->getArticleID() . "\n";
			}
		}

		UserMailer::send(
			new MailAddress('graham@wikihow.com'),
			new MailAddress('bebeth@wikihow.com'),
			"New Pages on Homepage Ids",
			$ids
		);
	}
}

$maintClass = 'updateHomepageNewpages';
require_once RUN_MAINTENANCE_IF_MAIN;

