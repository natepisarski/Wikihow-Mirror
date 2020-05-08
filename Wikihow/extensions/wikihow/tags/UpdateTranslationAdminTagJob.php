<?php

if (!defined('MEDIAWIKI')) die();

/**
 * Job class to asyncronously turn an English admin tag into a bunch of INTL
 * admin tags containing the Translation Link articles.
 * @file
 * @ingroup JobQueue
 */
class UpdateTranslationAdminTagJob extends Job {

	const ACTIVITY_LOG = '/var/log/wikihow/update-translation-tag.log';

	public function __construct(Title $targetArticle, $params, $id = 0) {
		parent::__construct('UpdateTranslationAdminTagJob', $targetArticle, $params, $id);
	}

	public function run() {
		$status = $this->runMaintenanceJobsAsLanguage();
		if ( $status !== true ) {
			$this->setLastError($status);
			return false;
		} else {
			return true;
		}
	}

	private function runMaintenanceJobsAsLanguage() {
		global $IP;

		$tag = $this->params['tag'];
		//$pages = $this->params['pages'];
		$log = self::ACTIVITY_LOG;

		$last_line = system("/opt/wikihow/scripts/whrun --lang=intl -- $IP/maintenance/wikihow/updateTranslationAdminTag.php --tag=$tag >> $log 2>&1", $return_val);

		return $return_val == 0 ? true : $last_line;
	}
}
