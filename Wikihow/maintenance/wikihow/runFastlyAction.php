<?php
//
// Connect to Fastly's API.
//
// http://www.fastly.com/docs/api
//

require_once __DIR__ . '/../Maintenance.php';

class RunFastlyAction extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Run some action against the Fastly API";
		$this->addOption( 'reset-tag', 'Fastly cache tag that we want to reset', false, true, false );
		$this->addOption( 'url', 'URL to reset at Fastly', false, true, false );
	}

	public function execute() {
		$resetTag = $this->getOption('reset-tag');
		$url = $this->getOption('url');

		if ($resetTag) {
			$result = FastlyAction::resetTag('en', $resetTag);
			print "en: status=" . $result . "\n";
			$result = FastlyAction::resetTag('intl', $resetTag);
			print "intl: status=" . $result . "\n";
		} elseif ($url) {
			$result = FastlyAction::purgeURL($url);
			if ($result === false) {
				print "fail\n";
			} else {
				print "$url: Fastly API status=" . $result . "\n";
			}
		} else {
			print "No action specified!\n";
		}

	}

}

$maintClass = 'RunFastlyAction';
require_once RUN_MAINTENANCE_IF_MAIN;
