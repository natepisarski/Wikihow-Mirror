<?php

require_once( __DIR__ . '/../Maintenance.php' );

class clearSingleMemCache extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Clear a single memcache cache by key";
		$this->addOption( 'key', 'Memcache key', false, true, 'k' );
	}

	public function execute() {
		if (!$this->hasOption('key')) {
			print "A key value has to be given. Example: --key=memcache_key\n";
			return;
		}

		global $wgMemc;
		$key = $this->getOption('key');
		$memkey = wfMemckey($key);
		$result = $wgMemc->delete($memkey);

		if ($result)
			print "$key has been cleared.\n";
		else
			print "Unable to delete key: $key\n";
	}
}

$maintClass = "clearSingleMemCache";
require_once RUN_MAINTENANCE_IF_MAIN;
