<?php
/**
 * Howcast changed their ids.  This script looks at a temporary table mapping old howcast ids to new ones
 */

/*
CREATE TABLE `howcast_mapping` (
  `old_id` int(11) NOT NULL,
  `new_id` varchar(32) DEFAULT NULL,
  `howcast_title` text,
  KEY `old_id` (`old_id`)
);
*/

require_once("../../commandLine.inc");
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");

$scriptDebug = $argv[0] ? true : false;


global $wgUser;
$u = User::newFromName('MiscBot');


$dbr = wfGetDB(DB_SLAVE);
$articles = DatabaseHelper::batchSelect('page', array('page_id', 'page_title'), array('page_namespace' => NS_VIDEO, 'page_is_redirect' => 0));
foreach ($articles as $row) {
	$r = Revision::loadFromPageId($dbr, $row->page_id);
	if ($r) {
		$wikitext = ContentHandler::getContentText($r->getContent());
		if (preg_match("@{{Curatevideo\|howcast\|([^\|]+)\|([^\|]+)@", $wikitext, $matches)) {
			updateId($matches[1], $r, $wikitext, $u);
		}
	}
}

function updateId($oldId, $r, &$wikitext, $u) {
	global $scriptDebug;


	$newId = getNewId($oldId);
	$t = $r->getTitle();

	if ($newId) {
		$newWikitext = preg_replace("@{{Curatevideo\|howcast\|([^\|]+)@", "{{Curatevideo|howcast|$newId", $wikitext);
		if (is_null($newWikitext)) {
			printError($t, "preg_replace error. old id: $oldId, oldid: $oldId, newid: $newId");
		} elseif ($newWikitext == $wikitext) {
			printError($t, "Nothing replaced. old id: $oldId, oldid: $oldId, newid: $newId");
		} else {
			$a = WikiPage::factory($t);
			if ($a && $a->exists()) {
				$summary = "Updating howcast id. old: $oldId, new: $newId";
				$content = ContentHandler::makeContent( $newWikitext, $t );
				if (!$scriptDebug) {
					$result = $a->doEditContent($content, $summary, EDIT_UPDATE, false, $u);
				} else {
					$result = (object) array("value" => array("revision" => 1));
				}

				if (is_null($result->value['revision'])) {
					printError($t, "Unable to save a new revision.");
				} else {
					printSuccess($t, "Updated oldid: $oldId to newid: $newId.");

					if ($scriptDebug) {
						var_dump($wikitext, $newWikitext);
					}
				}
			}
		}
	} else {
		printError($t, "No new id found for old id $oldId");
	}
}

function getNewId($vidId) {
	$dbr = wfGetDB(DB_SLAVE);
	return $dbr->selectField('howcast_mapping', 'new_id', array('old_id' => $vidId), __METHOD__);
}


function printError($t, $msg) {
	echo "ERROR\t{$t->getFullUrl()}\t$msg\n";
}

function printSuccess($t, $msg) {
	echo "SUCCESS\t{$t->getFullUrl()}\t$msg\n";
}
