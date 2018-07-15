<?php
//
// I released some code related to the HTTPS rollout that has messed up a
// part of the avatar table. This will fix it. It can be used as a template
// for any script that loops over all the avatars and modifies their data.
//

require_once __DIR__ . '/../../Maintenance.php';

class FixAvatarTableMaintenance extends Maintenance {
    public function __construct() {
        parent::__construct();
        $this->mDescription = 'Fixes av_image entries in the avatar table';
    }

    public function execute() {
        global $wgUser;

        // make edits as MiscBot
        $tempUser = $wgUser;
        $wgUser = User::newFromName('MiscBot');

		$rows = DatabaseHelper::batchSelect('avatar', ['av_user', 'av_image']);
		print "Finished select of " . count($rows) . "rows\n";
		$dbw = wfGetDB(DB_MASTER);
		$total = 0; $total_changed = 0;
		foreach ($rows as $row) {
			$total++;
			if ( preg_match('@^[0-9]+\.[a-z]{3}$@', $row->av_image) ) {
				$total_changed++;
				print "av_user " . $row->av_user . ": '" . $row->av_image . "' -> ''\n";
				$dbw->update('avatar', ['av_image' => ''], ['av_user' => $row->av_user], __METHOD__);
			}
		}
		print "A total of $total_changed rows changed\n";
	}
}

$maintClass = 'FixAvatarTableMaintenance';
require_once RUN_MAINTENANCE_IF_MAIN;

