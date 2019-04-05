<?php
require_once(__DIR__ . '/../../commandLine.inc');

$inputSet = isset($argv[0]) ? $argv[0] : '';
echo "Start: " . $inputSet . " " . date('G:i:s:u') . "\n";
switch ($inputSet) {
	case "all":
		exemptExcludedCategories();
		checkAllArticles();
		break;
	case "dirty":
		exemptExcludedCategories();
		checkDirtyArticles();
		break;
	case "article":
		$dbw = wfGetDB(DB_MASTER);
	
		$pspell = wikiHowDictionary::getLibrary();
		$whitelistArray = wikiHowDictionary::getWhitelistArray();
	
		spellCheckArticle($dbw, $argv[1], $pspell, $whitelistArray);
		break;
}
echo "Finish: " . $inputSet . " " . date('G:i:s:u') . "\n\n";

/*
 * set the exempt flag and and unset the dirty bit for all articles within the categories
 * within the "spellchecker_exclude_categories" admin config key
 */
function exemptExcludedCategories() {
	$configKey = "spellchecker_exclude_categories";
	$chunkSize = 200;
	$ts = wfTimestampNow();

	$dbr = wfGetDB(DB_REPLICA);
	$excludeCategories = ConfigStorage::dbGetConfig($configKey);
	$excludeCategories = explode("\n", trim($excludeCategories));

	$sql = "select page_id  from categorylinks, page where cl_from = page_id and page_namespace = 0  and cl_to IN (" .
		$dbr->makeList($excludeCategories) . ") and page_is_redirect = 0";
	$res = $dbr->query($sql, __FILE__);
	$insertRows = array();
	foreach ($res as $row) {
		// sc_page, sc_dirty, sc_exclude
		$insertRows[] = array(
			'sc_page' => $row->page_id,
			'sc_dirty' => 0,
			'sc_exempt' => 1,
			'sc_timestamp' => $ts
		);
	}

	if (count($insertRows)) {
		$chunks = array_chunk($insertRows, $chunkSize);
		$dbw = wfGetDB(DB_MASTER);
		foreach ($chunks as $chunk) {
			$sql = WAPUtil::makeBulkInsertStatement($chunk, 'spellchecker');
			$dbw->query($sql, __FILE__);
		}
	}
}

/**
 * Checks all articles in the db for spelling mistakes
 * Should be run sparringly as it will take a long time.
 */
function checkAllArticles() {
	global $wgMemc;
    echo "Checking all articles for spelling mistakes at " . microtime(true) . "\n";

    $dbw = wfGetDB(DB_MASTER);
	
	$articles = DatabaseHelper::batchSelect('page', array('page_id'), array('page_namespace' => 0, 'page_is_redirect' => 0 ));
	
	echo "SQL query done at " . microtime(true) . "\n";

    echo count($articles) . " IDs in array at " . microtime(true) . "\n";

    $pspell = wikiHowDictionary::getLibrary();

	$whitelistArray = wikiHowDictionary::getWhitelistArray();
	
	$i = 0;
    foreach ($articles as $article) {
        spellCheckArticle($dbw, $article->page_id, $pspell, $whitelistArray);
		$i++;
		if ($i % 1000 == 0) {
			echo $i . " articles processed at " . microtime(true) . "\n";
		}
    }
	if (sizeof($articles) > 0) {
		Spellchecker::deleteSpellCheckerCacheKeys();
	}

    echo "Done importing all articles at " . microtime(true) . "\n";
}



/***
 * Checks all articles that have been marked as dirty (have been
 * edited). 
 */
function checkDirtyArticles() {
	global $argv, $wgMemc;

	echo "Checking dirty articles for spelling mistakes at " . microtime(true) . "\n";
	
	$dbr = wfGetDB(DB_REPLICA);
	$dbw = wfGetDB(DB_MASTER);

	$options = array();
	if (!empty($argv[1])) {
		$options['LIMIT'] = $argv[1];
		echo "LIMIT of {$argv[1]} supplied via input \n";
 	}
	$articles = DatabaseHelper::batchSelect('spellchecker',
		array('sc_page'),
		array('sc_dirty' => 1, 'sc_exempt' => 0),
		__FILE__,
		$options);
	
	echo "Done grabbing articles. There are "  . count($articles) . " dirty articles.\n";
	
	$pspell = wikiHowDictionary::getLibrary();
	$whitelistArray = wikiHowDictionary::getWhitelistArray();
	
	$i = 0;
	foreach ($articles as $article) {
		spellCheckArticle($dbw, $article->sc_page, $pspell, $whitelistArray);
		$i++;
		if ($i % 1000 == 0) {
			echo $i . " articles processed at " . microtime(true) . "\n";
		}
	}
	if (sizeof($articles) > 0) {
		Spellchecker::deleteSpellCheckerCacheKeys();
	}
    echo "Done checking dirty articles at " . microtime(true) . "\n";

}

/*
 * Checks a specific article for spelling mistakes.
 */
function spellCheckArticle(&$dbw, $articleId, &$pspell, &$whitelistArray) {

	$debug = false;
	$dryRun = false;

	//first remove all mistakes from the mapping table
	$dbw->delete('spellchecker_page', array('sp_page' => $articleId), __FILE__);
	
	$title = Title::newFromID($articleId);
	if ($title) {
		$revision = Revision::newFromTitle($title);
		if (!$revision) {
			return;
		}

		$text = ContentHandler::getContentText( $revision->getContent() );
		// Don't spellcheck nfd articles
		if (preg_match("@{{ *(nfd|notinenglish)@i", $text, $matches)) {
			return;
		}

		//now need to remove the sections we're not going to check
		$newtext = Wikitext::removeSection($text, wfMessage('sources')->text());
		$newtext = Wikitext::removeSection($text, wfMessage('references')->text());
		$newtext = Wikitext::removeSection($newtext, wfMessage('related')->text());

		//remove reference tags
		$newtext = preg_replace('@<ref[^>]*>[^<]+</ref>|<ref[^/]*/>@i', "", $newtext);

		//remove U tags
		$newtext = preg_replace('@<u[^>]*>[^<]+</U>|<u[^/]*/>@i', "", $newtext);

		//remove links
		$newtext = preg_replace('@\[\[[^\]]+\]\]@', "", $newtext);

		//remove magic words
		$newtext = preg_replace('@__[^_]*__@', "", $newtext);
		
		//replace wierd apostrophes
		$newtext = str_replace('’', "'", $newtext);

		// remove non-linked urls
		$regex = "@\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))@";
		$newtext = preg_replace($regex, "", $newtext);

		// Replace breaks with spaces to prevent words from being joined in WikihowArticleEditor::textify
		$newtext = preg_replace('@<br>@', " ", $newtext);

		$newtext = WikihowArticleEditor::textify($newtext, array('remove_ext_links' => 1, 'no-heading-text' => 1));


		preg_match_all("/\b([\w'’])+\b/u", $newtext, $matches); //u modified allows for international characters

		$foundErrors = false;
		if ($debug) {
			var_dump('http://k.wikidiy.com/' . $title->getDBKey());
			var_dump($text);
			var_dump($matches[0]);
		}
		$wordsToAdd = array();
		foreach ($matches[0] as $i => $match) {

			// Ignore words with non-alpha characters
			if (preg_match('/[^a-zA-Z]/', $match)) {
				continue;
			}

			$word_id = wikiHowDictionary::spellCheckWord($dbw, $match, $pspell, $whitelistArray);

			if ($word_id > 0) {
				//insert into the mapping table
				$key = getWordKey($text, $match, $matches[0], $i);
				if ($debug) {
					var_dump("key: $key");
					var_dump("match: $match");
				}
				// Skip word if we can't establish a unique key
				if (preg_match_all("@$key@m", $text, $found) > 1) {
					if ($debug) {
						var_dump("Duplicate key: $key");
					}
					continue;
				}
				// Don't include words where we find a slash or dash before or after the word. The majority of these
				// cases aren't misspelled. Also don't include words which might be part of a larger url
				if (preg_match("@[-/$]{$match}|{$match}[-/]|{$match}\.(com|edu|net|org|to)|\.{$match}@m", $text)) {
					continue;
				}

				$numWordsInKey = substr_count($key, ' ') + 1;
				if ($debug) {
					//var_dump("key: " . $key. ", numwords: ". $numWordsInKey);
				}
				$wordsToAdd[] = array('sp_page' => $articleId, 'sp_word' => $word_id, 'sp_key' => $key, 'sp_key_count' => $numWordsInKey);

				$foundErrors = true;
			}
		}
		$wordsToAdd = removeSubsetKeys($wordsToAdd);
		if (!$dryRun) {
			if (sizeof($wordsToAdd) > 0) {
				$dbw->insert('spellchecker_page', $wordsToAdd, __FILE__, array('IGNORE'));
			}

			if ($foundErrors) {
				$sql = "INSERT INTO spellchecker (sc_page, sc_timestamp, sc_dirty, sc_errors, sc_exempt) VALUES (" .
						$articleId . ", " . wfTimestampNow() . ", 0, 1, 0) ON DUPLICATE KEY UPDATE sc_dirty = '0', sc_errors = '1', sc_timestamp = " . wfTimestampNow();
				$dbw->query($sql, __FILE__);
			}
			else {
				$dbw->update('spellchecker', array('sc_errors' => 0, 'sc_dirty' => 0), array('sc_page' => $articleId), __FILE__);
			}
		}

	}
}

/*
 * Remove any keys that might be a substring of other keys to guarantee
 * key uniqueness
 */
function removeSubsetKeys($wordsToAdd) {
	$count = 0;
	foreach ($wordsToAdd as $i => $lhword) {
		foreach ($wordsToAdd as $rhword) {
			//var_dump($lhword['sp_keykey'] . ' ' . $rhword['sp_key']);
			if (strpos($rhword['sp_key'], $lhword['sp_key']) !== false) {
				$count++;
			}

			if ($count > 1) {
				//var_dump($lhword);
				unset($wordsToAdd[$i]);
				break;
			}
		}
		$count = 0;
	}

	// return a consecutively keyed array
	return array_values($wordsToAdd);
}

/*
 * Find a unique word combination that can be later used to identify misspelled word in wikitext or html
 * In order to do this we have to find 1 - 3 continuous words separated by spaces.  Other punctuation
 * can cause a problem given that we strip it out.
 */
function getWordKey(&$text, $word, &$words, $i) {
	$before = "";
	$after = "";
	$key = $word;

	if ($i - 1 >= 0) {
		$before = $words[$i - 1];
	}

	if ($i + 1 < sizeof($words)) {
		$after = $words[$i + 1];
	}

	//var_dump($before, $word, $after);
	$testKey = $before . ' ' . $word . ' ' . $after;
	if (strpos($text, $testKey) !== false) {
		return $testKey;
	}

	$testKey = $before . ' ' . $word;
	if (strpos($text, $testKey) !== false) {
		return $testKey;
	}

	$testKey = $word . ' ' . $after;
	if (strpos($text, $testKey) !== false) {
		return $testKey;
	}

	// If the above combinations don't work, just return the word itself
	// Trim for case where word is at beginning of step section with no intro
	return trim($key);
}
/*
 * Takes all of the words out of the custom dictionary and adds them
 * to the whitelist table.
 */
// Unused function?
function populateWhitelistTable() {
	global $IP;
	
	$filecontents = file_get_contents($IP . wikiHowDictionary::DICTIONARY_LOC);
	$words = explode("\n", $filecontents);
	asort($words);
	
	$dbw = wfGetDB(DB_MASTER);
	
	foreach($words as $word) {
		$word = trim($word);
		if ($word != "" && stripos($word, "personal_ws-1.1") === false)
			$dbw->insert(wikiHowDictionary::WHITELIST_TABLE, array(wikiHowDictionary::WORD_FIELD => $word, wikiHowDictionary::ACTIVE_FIELD => 1), __FILE__, "IGNORE");
	}
}

// Unused function?
function addWordFile($fileName) {
	echo "getting file " . $fileName . "\n";
	$fileContents = file_get_contents($fileName);
	$words = explode("\n", $fileContents);
	
	$dbw = wfGetDB(DB_MASTER);
	
	foreach($words as $word) {
		$word = trim($word);
		if ($word != "" && stripos($word, "personal_ws-1.1") === false)
			$dbw->insert(wikiHowDictionary::WHITELIST_TABLE, array(wikiHowDictionary::WORD_FIELD => $word, wikiHowDictionary::ACTIVE_FIELD => 0), __FILE__, "IGNORE");
	}
}

// Unused function?
function removeWordFile($fileName) {
	echo "getting file " . $fileName . "\n";
	$fileContents = file_get_contents($fileName);
	
	$words = explode("\n", $fileContents);
	
	wikiHowDictionary::batchRemoveWordsFromDictionary($words);
}

// Unused function?
function moveCaps() {
	$dbr = wfGetDB(DB_REPLICA);
	$dbw = wfGetDB(DB_MASTER);
	
	$words = DatabaseHelper::batchSelect(wikiHowDictionary::CAPS_TABLE, array('*'));
	
	foreach($words as $word) {
		$dbw->insert(wikiHowDictionary::WHITELIST_TABLE, array(wikiHowDictionary::WORD_FIELD => $word->sc_word, "sw_user" => $word->sc_user, "sw_active" => "1"));
		// sleep for 0.5s
		usleep(500000);
	}
}


/**

CREATE TABLE IF NOT EXISTS `spellchecker` (
  `sc_page` int(10) unsigned NOT NULL,
  `sc_timestamp` varchar(14) collate utf8_unicode_ci NOT NULL,
  `sc_errors` tinyint(3) unsigned NOT NULL,
  `sc_dirty` tinyint(4) NOT NULL,
  `sc_firstedit` varchar(14) collate utf8_unicode_ci default NULL,
  `sc_checkout` varchar(14) collate utf8_unicode_ci NOT NULL,
  `sc_checkout_user` int(5) NOT NULL,
  UNIQUE KEY `sc_page` (`sc_page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `spellchecker_caps` (
  `sc_word` varchar(20) collate utf8_unicode_ci NOT NULL,
  UNIQUE KEY `sc_word` (`sc_word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `spellchecker_caps` ADD `sc_user` MEDIUMINT( 8 ) NOT NULL;

CREATE TABLE IF NOT EXISTS `spellchecker_page` (
  `sp_id` int(10) unsigned NOT NULL auto_increment,
  `sp_page` int(10) unsigned NOT NULL,
  `sp_word` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sp_id`),
  UNIQUE KEY `sp_id` (`sp_id`),
  UNIQUE KEY `sp_page` (`sp_page`,`sp_word`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `spellchecker_temp` (
  `st_word` varchar(20) collate utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `spellchecker_word` (
  `sw_id` int(10) unsigned NOT NULL auto_increment,
  `sw_word` varchar(255) character set latin1 collate latin1_general_cs NOT NULL,
  `sw_corrections` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`sw_id`),
  UNIQUE KEY `sw_id` (`sw_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `wikidb_112`.`spellchecker_whitelist` (
`sw_word` VARCHAR( 20 ) NOT NULL ,
`sw_active` TINYINT NOT NULL ,
`sw_user` MEDIUMINT( 8 ) NOT NULL, 
UNIQUE (
`sw_word`
)
) ENGINE = InnoDB ;
ALTER TABLE `spellchecker_whitelist` CHANGE `sw_word` `sw_word` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL 
ALTER TABLE `spellchecker` ADD `sc_exempt` TINYINT( 3 ) NOT NULL DEFAULT '0'
ALTER TABLE `spellchecker_whitelist` ADD `sw_id` INT(10) unsigned primary key auto_increment not null; 

CREATE TABLE `spellcheck_misspellings` (
  `sm_word` varchar(255) NOT NULL,
  `sm_count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`sm_word`)
);

CREATE TABLE `spellcheck_articles` (
  `sa_page_id` int(8) unsigned NOT NULL,
  `sa_rev_id` int(8) unsigned NOT NULL DEFAULT '0',
  `sa_misspelled_count` smallint(5) unsigned NOT NULL DEFAULT '0',
  `sa_misspellings` text NOT NULL,
  PRIMARY KEY (`sa_page_id`),
  UNIQUE KEY `sa_page_rev` (`sa_rev_id`),
  FULLTEXT KEY `misspellings` (`sa_misspellings`)
);
**/
