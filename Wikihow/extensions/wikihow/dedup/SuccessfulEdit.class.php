<?php

/**
 * SuccessfulEdit::getEdits counts number of important characters added to an article by a
 * particular revision. This can be used to find out whether an editor is good for a certain
 * topic.
 *
 * This class is referenced by the Recommendations class. It is also run by a script, which
 * caches the results in a table as its run.
 */
class SuccessfulEdit {

	// Number of bytes added to final revision. Uses diff to find out if a given edit/revision
	// was reverted or overridden with other people's edit. Bold edits are the edits that had
	// a large contribution to the final article.
	public static function getEdits($articleId) {
		$dbr = wfGetDB(DB_SLAVE);
		$gr = 0;
		$gr = $dbr->selectField('good_revision',array('gr_rev'),array('gr_page' => $articleId));
		$edits = self::getSigEdits($gr);
		if($edits) {
			return($edits);	
		}
		$res = $dbr->select(array('revision','text'),array('rev_id','old_text','old_flags','rev_timestamp', 'rev_user','rev_user_text'),array('rev_page' => $articleId, 'rev_text_id = old_id' ),__METHOD__,array('order by'=>'rev_timestamp asc'));
		$txts = array();
		$grTxt = false;
		foreach($res as $row) {
			$flags = explode( ',', $row->old_flags );
			$rowText = Revision::decompressRevisionText($row->old_text, $flags);
			$stepsSection = Wikitext::getStepsSection($rowText);
			//print("Got txt for rev" . $row->rev_id . "\n");
			$txts[] = array('text'=> $stepsSection[0], 'rev_page'=>$row->rev_page, 'rev_id' => $row->rev_id, 'rev_user'=> $row->rev_user, 'rev_user_text' => $row->rev_user_text);
			if($row->rev_id == $gr) {
				$grTxt = $stepsSection[0];
				break;
			}
		}
		if(!$grText) { 
			$grText = $txts[sizeof($txts) - 1]['text'];
			$gr = $txts[sizeof($txts) - 1]['rev_id'];
		}
		global $wgContLang;
		$segmentedGr = $wgContLang->segmentForDiff($grText);
		$grArr = explode("\n",$segmentedGr);
		$grSize = strlen($segmentedGr) - sizeof($grArr) + 1;
		// Amount added
		$added = 0;
		$edits = array();
		$first = true;
		$lastAdds = 0;
		foreach($txts as $txt) {
			$txtArr = explode("\n",$wgContLang->segmentForDiff($txt['text']));
			//print("diff for rev " . $txt['rev_id'] . " " . wfTimestampNow() . "\n");
			$diffs = new Diff($txtArr,$grArr);
			$adds = 0;

			foreach($diffs as $diff) {

				foreach($diff as $d) {
					if($d->type == 'copy') {
						foreach($d->closing as $cl) {
							$adds += strlen($cl);
						}
					}
					elseif($d->type == 'change') {
						$wld = new WordLevelDiff($d->orig, $d->closing);
						foreach($wld->edits as $edit) {
							if($edit->type=='copy') {
								foreach($edit->orig as $o) {
									$adds += strlen($o);
								}
							}
						}
					}
				}
			}
			if($adds > $added) {
				$newAdded =  $adds - $added;
				$added = $adds;
			}
			else {
				$newAdded = 0;
			}
			if($newAdded > 0 ) {
				// First edit or didn't add steps
				// This prevents counting the steps section formatting fix as a contributor
				if($first || $lastAdds != 0) {
					$edits[] = array('added' => $newAdded, 'gr' => $gr , 'rev' => $txt['rev_id'],'page'=>$txt['page_id'], 'user' => $txt['rev_user'], 'username' => $txt['rev_user_text']); 
				}
			}
			$first = false;

			$lastAdds = $adds;
		}
		if($edits) {
			self::saveSigEdits($edits);
		}
		return($edits);
	}
	/**
	 create table edit_contributions(
	 	ec_rev int,
		ec_gr int,
		ec_bytes int NOT NULL,
		primary key(ec_rev,ec_gr)
	);
	/**
	 * Fetch cached data on the significant edits for a good revision
	 */
	private static function getSigEdits($gr) {
		$dbr = wfGetDB(DB_SLAVE);
		$sql = "select ec_rev,ec_bytes,page_id,page_title,rev_user,rev_user_text from dedup.edit_contributions join revision on rev_id=ec_rev join page on rev_page=page_id where ec_gr=" . $dbr->addQuotes($gr);
		$res = $dbr->query($sql, __METHOD__);
		$added = array();
		foreach($res as $row) {
			$added[] = array('added' => $row->ec_bytes, 'gr' => $gr, 'rev' => $row->ec_rev, 'page' => $row->page_id, 'user' => $row->rev_user, 'username' => $row->rev_user_text);	
		}
		return($added);
	}

	/**
	 * Cache a copy of edit_contribution data
	*/
	public static function saveSigEdits($edits) {
		$dbw = wfGetDB(DB_MASTER);
		$sql = "insert ignore into dedup.edit_contributions(ec_rev, ec_gr, ec_bytes) values ";
		$first = true;
		foreach($edits as $edit) {
			if(!$first) {
				$sql .= ",";
			}
			else {
				$first = false;
			}
			$sql .= '(';
			$sql .= $dbw->addQuotes($edit['rev']) . ',' . $dbw->addQuotes($edit['gr']) . ',' . $dbw->addQuotes($edit['added']);
			$sql .= ')';
		}
		$dbw->query($sql, __METHOD__);
	}

}
