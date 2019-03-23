<?php
/*
Setup:
1) export spreadsheet to tab-delimited file (.tsv)
2) clean up headers to match:
	- URL
	- MisusedPhrase
	- ReplacementPhrase
3) create new table:
	CREATE TABLE spellsheet2 (
		URL VARCHAR(255) NOT NULL DEFAULT '',
		MisusedPhrase VARCHAR(255) NOT NULL DEFAULT '',
		ReplacementPhrase VARCHAR(255) NOT NULL DEFAULT ''
	);
4) drop file on server and import with:
		LOAD DATA INFILE '/home/scott/spellsheet2.tsv' INTO TABLE spellsheet2 IGNORE 1 LINES;
*/

global $IP;
require_once __DIR__ . '/../../commandLine.inc';
$wgUser = User::newFromName('MiscBot');

$articles = array();
$table = 'spellsheet2';

print "BEGIN.\n\n";

$dbr = wfGetDB(DB_REPLICA);
$res = DatabaseHelper::batchSelect($table, array('URL','MisusedPhrase','ReplacementPhrase'), array(), __METHOD__);
// $res = DatabaseHelper::batchSelect($table, array('URL','MisusedPhrase','ReplacementPhrase'), array(), __METHOD__, array('LIMIT' => 50000));
print count($res)." articles grabbed at ".microtime(true)."\n";

$i = 0;
$count = 0;
foreach ($res as $row) {
	correctIt($row,$dbr);
	$i++;
}

function correctIt($change,$dbr) {
	global $count;
	$name = str_replace('http://www.wikihow.com/','',urldecode($change->URL));
	$title = Title::newFromText($name);
	if (!$title) return;
	$wikitext = Wikitext::getWikitext($dbr, $title);
	if (!$wikitext) return;
	list($new_wikitext, $all_tokens) = tokenize($wikitext);

	//CHANGE IT!
	$new_wikitext = doTheChange($new_wikitext, $change);
	if ($new_wikitext == '') return;

	//replace all those tokens we put in
	$new_wikitext = retokenize($new_wikitext,$all_tokens);

	//did we change anything?
	if (strcmp($wikitext,$new_wikitext) == 0) return;

	$wp = new WikiPage($title);
	$content = ContentHandler::makeContent( $new_wikitext, $title );
	if ($wp->doEditContent($content, "Correcting spelling and/or grammar.")) {
		print 'http://www.wikihow.com/'.$title->getDBkey()."\n";
		$count++;
	}
}

print 'count: '.$count.' out of '.$i."\n";

function doTheChange($wikitext, $change) {
	$new_wikitext = preg_replace('/\b'.$change->MisusedPhrase.'\b/im',$change->ReplacementPhrase,$wikitext);
	if (strcmp($wikitext,$new_wikitext) == 0) return '';
	return $new_wikitext;
}

//strip out the image, video, template, & link stuff
//and replace with tokens
function tokenize($wikitext) {
	$tokens = array();

	//for:
	// [[ ]]
	// [ ]
	// {{ }}
	// urls
	// == headers ==
	$output = preg_replace_callback('@(\[\[.*?\]\]|\[.*?\]|\{\{.*?\}\}|https?:\/\/[A-Za-z0-9_\-\.\/\#&\+=%?]+|^==[^=].+==)@im',
		function ($m) use (&$tokens) {
			$token = '(TOKE_' . Wikitext::genRandomString().')';
			$tokens[] = array('token' => $token, 'tag' => $m[0]);
			return $token;
		},
		$wikitext
	);
	return array($output, $tokens);
}

//add all our images, videos, templates, links, & stuff back in
function retokenize($wikitext,$tokens) {
	foreach ($tokens as $t) {
		$wikitext = str_replace($t['token'], $t['tag'], $wikitext);
	}
	return $wikitext;
}
