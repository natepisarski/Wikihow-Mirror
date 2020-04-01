<?php
if (!defined('MEDIAWIKI')) {
	die("This requires the MediaWiki enviroment.");
}

$wgExtensionCredits['parserhook'][] = [
	'name' => 'MagicArticlesStartedMagicWords',
	'author' => 'Travis Derouin',
	'description' => 'Adds ARTICLESSTARTED magic word for showing articles created by user.',
];

$wgExtensionMessagesFiles['MagicArticlesStarted'] = __DIR__ . '/MagicArticlesStarted.i18n.php';

$wgHooks['ParserFirstCallInit'][] = 'wfWikiHowParserFunction_Setup';
$wgHooks['MagicWordwgVariableIDs'][] = 'MagicArticlesStartedwgVariableIDs';
$wgHooks['ParserGetVariableValueSwitch'][] = 'wfWikiHowMagicAssignAValue';

function wfWikiHowParserFunction_Setup() {
	global $wgParser;
	$wgParser->setFunctionHook( 'ARTICLESSTARTED', 'wfMagicWordArticlesStarted');
	$wgParser->setFunctionHook( 'PATROLCOUNT', 'wfMagicWordPatrolCount');
	$wgParser->setFunctionHook( 'NABCOUNT', 'wfMagicWordNabCount');
	return true;
}

function MagicArticlesStartedwgVariableIDs(&$magicWords) {
	$magicWords[] = 'ARTICLESSTARTED';
	$magicWords[] = 'PATROLCOUNT';
	$magicWords[] = 'NABCOUNT';
	$magicWords[] = 'VIEWERSHIP';
	$magicWords[] = 'NUMBEROFARTICLESSTARTED';
	return true;
}

function wfMagicWordArticlesStarted($parser, $part1 = '', $part2 = '', $part3 = 'time', $part4 = '', $part5 = 'width:200px;border: 1px solid #ccc; padding:10px;') {
	$title = RequestContext::getMain()->getTitle();
	$ret = "";
	if ($title instanceof Title && $title->inNamespace(NS_USER)) {
		$ret = "";
		$msg = "";
		if ($part2 == 'box') {
			if ($part1 == '0') {
				$msg = wfMessage('articlesstarted_byme');
			} else {
				switch ($part3) {
					case 'popular':
						$msg = wfMessage('articlesstarted_byme_mostpopular', $part1);
						break;
					case 'time_asc':
						$msg = wfMessage('articlesstarted_byme_first', $part1);
						break;
					default:
						$msg = wfMessage('articlesstarted_byme_mostrecent', $part1);
				}
			}
			if ($part4 != '') $msg = $part4;
			$ret = "<div style='$part5'>$msg<br/>\n";
		}
		$dbr = wfGetDB(DB_REPLICA);
		$opts = [];
		switch ($part3) {
			case 'popular':
				$opts['ORDER BY'] = 'page_counter DESC ';
				break;
			case 'time_asc':
				$opts['ORDER BY'] = 'fe_timestamp ASC ';
				break;
			default:
				$opts['ORDER BY'] = 'fe_timestamp DESC ';
		}
		if ( (int)$part1 > 0 ) {
			if ($part1 > PHP_INT_MAX) {
				$part1 = PHP_INT_MAX;
			}
			$opts['LIMIT'] = (int)$part1;
		}
		$res = $dbr->select(
			['firstedit','page'],
			['page_title', 'page_namespace', 'fe_timestamp'],
			['fe_page=page_id', 'fe_user_text' => $title->getText()],
			__METHOD__,
			$opts
		);
		foreach ($res as $row) {
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			$ret .= "# [[" . $t->getFullText() . "]]\n";
		}
		if ($part2 == 'box') $ret .= "</div>";
	}
	return $ret;
}

function wfMagicWordPatrolCount($parser, $date1 = '', $date2  = '') {
	$title = RequestContext::getMain()->getTitle();
	$ret = "";
	if ($title instanceof Title && $title->inNamespace(NS_USER)) {
		$msg_key = $fdate1 = $fdate2 = '';
		$u = User::newFromName($title->getText());
		if (!$u || $u->getID() == 0) {
			$ret = wfMessage('patrolcount_error');
			return;
		}
		$options = ['log_user=' . $u->getID(), 'log_type' => 'patrol'];

		$fdate1 = $date1;
		$fdate2 = $date2;
		$date1 = str_replace("-", "", $date1);
		$date2 = str_replace("-", "", $date2);
		if ($date1 != "") $options[] = "log_timestamp > '{$date1}000000'";
		if ($date2 != "") $options[] = "log_timestamp < '{$date2}235959'";

		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField('logging', 'count(*)', $options, __METHOD__);
		$count = number_format($count, 0, "", ",");

		$ret = $count;
	}
	return $ret;
}

function wfMagicWordNabCount($parser, $date1 = '', $date2  = '') {
	$title = RequestContext::getMain()->getTitle();
	$ret = "";
	if ($title instanceof Title && $title->inNamespace(NS_USER)) {
		$msg_key = $fdate1 = $fdate2 = '';
		$u = User::newFromName($title->getText());
		if (!$u || $u->getID() == 0) {
			$ret = wfMessage('nabcount_error');
			return;
		}
		$options = ['log_user=' . $u->getID(), 'log_type' => 'nap'];

		$fdate1 = $date1;
		$fdate2 = $date2;
		$date1 = str_replace("-", "", $date1);
		$date2 = str_replace("-", "", $date2);
		if ($date1 != "") $options[] = "log_timestamp > '{$date1}000000'";
		if ($date2 != "") $options[] = "log_timestamp < '{$date2}235959'";

		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField('logging', 'count(*)', $options, __METHOD__);
		$count = number_format($count, 0, "", ",");

		$ret = $count;
	}
	return $ret;
}

function wfWikiHowMagicAssignAValue(&$parser, &$cache, &$magicWordId, &$ret) {
	$title = RequestContext::getMain()->getTitle();

	if ( !$title || !$title instanceof Title ) {
		return true;
	}

	if ('VIEWERSHIP' === $magicWordId) {
		$dbr = wfGetDB(DB_REPLICA);
		$u = User::newFromName($title->getText());
		if (!$u || $u->getID() == 0) {
			$ret = "No such user \"{$title->getText()}\"";
			return true;
		}
		$options = ['fe_user' => $u->getID(), 'page_id=fe_page'];
		$count = $dbr->selectField(['page', 'firstedit'], 'sum(page_counter)', $options, "viewership");
		$ret = number_format($count, 0, "", ",");
		return true;
	} elseif ('NUMBEROFARTICLESSTARTED' === $magicWordId) {
		$dbr = wfGetDB(DB_REPLICA);
		$u = User::newFromName($title->getText());
		if (!$u || $u->getID() == 0) {
			$ret = "No such user \"{$title->getText()}\"";
			return true;
		}
		$options = ['fe_user' => $u->getID(), 'page_id=fe_page'];
		$count = $dbr->selectField(['page', 'firstedit'], 'count(*)', $options, "viewership");
		$ret = number_format($count, 0, "", ",");
		return true;
	}

	return false;
}
