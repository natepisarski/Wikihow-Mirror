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
		$this->addOption( 'by-tag', '(with --url only) lookup the url in the page table, then do a purge by tag. this is more comprehensive since it covers mobile etc', false, false, false );
	}

	public function execute() {
		$resetTag = $this->getOption('reset-tag');
		$url = $this->getOption('url');
		$byTag = $this->getOption('by-tag');

		$resetService = '';
		if ($url) {
			if ($byTag) {
				list($resetTag, $resetService) = FastlyAction::getTagFromURL($url);
				if (!$resetTag) {
					print "unable to find tag for url, so clearing it directly: $url\n";
				}
			}

			if (!$resetTag) {
				$result = FastlyAction::purgeURL($url);
				if ($result === false) {
					print "fail\n";
				} else {
					print "$url: Fastly API status=" . $result . "\n";
				}
			}
		}

		if ($resetTag) {
			if (!$resetService || $resetService == 'en') {
				$result = FastlyAction::resetTag('en', $resetTag);
				print "$resetTag en: status=" . $result . "\n";
			}
			if (!$resetService || $resetService == 'intl') {
				$result = FastlyAction::resetTag('intl', $resetTag);
				print "$resetTag intl: status=" . $result . "\n";
			}
		}

	}

}

$maintClass = 'RunFastlyAction';
require_once RUN_MAINTENANCE_IF_MAIN;
