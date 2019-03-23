<?php
# Script to get translationlinks that point to redirected or deleted articles
# This should be run from "whrun --lang=all ..."
require_once('commandLine.inc');

global $wgLanguageCode;
$dbr = wfGetDB(DB_REPLICA);
if($wgLanguageCode != 'en') {
	$res = $dbr->query("select * from wikidb_112.translation_link LEFT JOIN page on page_id=tl_to_aid where tl_to_lang='$wgLanguageCode' AND (page_title is NULL or page_is_redirect=1)", __METHOD__);

	print "From lang\tFrom AID\tTo Lang\tTo AID\tRedirect\n";
	foreach($res as $row) {
		print $row->tl_from_lang . "\t" . $row->tl_from_aid . "\t" . $row->tl_to_lang . "\t" . $row->tl_to_aid . "\t" . $row->page_is_redirect . "\n";
	}
}
else {

	$res = $dbr->query("select * from wikidb_112.translation_link LEFT JOIN wikidb_112.page on page_id=tl_from_aid where (page_title is NULL or page_is_redirect=1)", __METHOD__);

	print "From lang\tFrom AID\tTo lang\tTo AID\tRedirect\n";
	foreach($res as $row) {
		print $row->tl_from_lang . "\t" . $row->tl_from_aid . "\t" . $row->tl_to_lang . "\t" . $row->tl_to_aid . "\t" . $row->page_is_redirect . "\n";
	}
}
