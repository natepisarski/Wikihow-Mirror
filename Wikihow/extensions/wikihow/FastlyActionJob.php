<?php

if (!defined('MEDIAWIKI')) die();

// Use as follows:
// $params = ['action' => 'reset-tag', 'lang' => 'en', 'tag' => 'sometag'];
// $job = new FastlyActionJob($title, $params);
// JobQueueGroup::singleton()->push($job);

/**
 * Job Queue class for Fastly api actions
 * @file
 * @ingroup JobQueue
 */
class FastlyActionJob extends Job {
	public function __construct(Title $targetArticle, $params, $id = 0) {
		parent::__construct('FastlyAction', $targetArticle, $params, $id);
	}

	public function run() {
		$status = $this->fastlyAction();

		if ( $status !== true ) {
			$this->setLastError($status);

			return false;
		}

		return true;
	}

	private function fastlyAction() {
		$action = $this->params['action'];
		if ($action == 'reset-tag') {
			$lang = $this->params['lang'];
			$tag = $this->params['tag'];
			print "got reset-tag ($lang): $tag\n";
			$result = FastlyAction::resetTag($lang, $tag);
		} else {
			print "action not understood: $action\n";
		}
		return true;
	}
}
