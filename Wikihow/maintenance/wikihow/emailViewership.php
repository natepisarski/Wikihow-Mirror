<?php

require_once __DIR__ . '/../commandLine.inc';
require_once "$IP/extensions/wikihow/DatabaseHelper.class.php";
require_once "$IP/extensions/wikihow/authors/AuthorEmailNotification.php";

$day = (int)date("j");

if ($day < 1 || $day > 6) {
	echo "We don't send emails on the $day day of the month. Exiting.\n";
	exit;
}

$startTime = microtime(true);

$dbr = wfGetDB(DB_REPLICA);

$todayUnix = wfTimestamp(TS_UNIX);
$minUnix = strtotime("-1 month", $todayUnix);
$minDate = wfTimestamp(TS_MW, $minUnix);
//$testDate = wfTimestamp(TS_MW, strtotime("-2 months", $todayUnix));

echo "looking for data from {$minDate} and forward\n";

//Get all the 30 day pageviews for all the english language articles out of titus
$res = DatabaseHelper::batchSelect('titusdb2.titus_intl', array('ti_30day_views', 'ti_page_id'), array('ti_language_code' => "en"), __FILE__);
$pvs = array();
foreach($res as $row) {
	$pvs[$row->ti_page_id] = $row->ti_30day_views;
}

//Get all users who have made edits to articles since $minDate and a list of all those articles
$sql = "SELECT u.user_id, u.user_name, u.user_email, r.rev_page FROM revision r INNER JOIN wiki_shared.user u ON u.user_id = r.rev_user AND u.user_id > 0 AND u.user_email > '' INNER JOIN page p ON p.page_id = r.rev_page AND p.page_namespace = 0 WHERE r.rev_timestamp > '{$minDate}' ORDER BY u.user_name";
$res = $dbr->query($sql, __FILE__);
$users = array();
foreach ($res as $row) {
	if (!array_key_exists($row->user_name, $users)) {
		$users[$row->user_name] = array();
	}
	$users[$row->user_name][] = $row;
}


$emailCount = 0;
$userNames = "";

foreach ($users as $userArray) {
	if (count($userArray) == 0) {
		//this should never happen, but just in case
		continue;
	}
	//still need to create the user object so that later we can
	//check their preferences about sending emails
	$user = User::newFromId($userArray[0]->user_id);
	$firstLetter = strtolower(substr($user->getName(), 0, 1));
	$omit = false;
	switch ($day) {
		case 1:
			if ($firstLetter < "a" || $firstLetter > "c")
				$omit = true;
			break;
		case 2:
			if ($firstLetter < "d" || $firstLetter > "i")
				$omit = true;
			break;
		case 3:
			if ($firstLetter < "j" || $firstLetter > "l")
				$omit = true;
			break;
		case 4:
			if ($firstLetter < "m" || $firstLetter > "r")
				$omit = true;
			break;
		case 5:
			if ($firstLetter < "s" || $firstLetter > "z")
				$omit = true;
			break;
		case 6:
			if (ctype_alpha($firstLetter))
				$omit = true;
			break;
	}

	if ($omit) {
		//we don't send this user on this day
		continue;
	}

	$email = $user->getEmail();

	if ($email == "") {
		//this shouldn't happen anymore, but keeping it in anyway
		//echo "They don't have an email.\n";
		continue;
	}
	
	if ($user->getOption('disablemarketingemail') == '1') {
		//echo "They don't want notifications\n";
		continue;
	}

	$views = 0;
	$articles = 0;
	//CHECK DATE (dont' want testing date left in there)

	foreach ($userArray as $userRow) {
		$views += intval($pvs[$userRow->rev_page]);
		$articles++;
	}

	if ($views < 50) {
		//echo "No email sent to " . $user->getName() . ". Not enough views ({$views})\n";
		continue;
	}

	$from_name = "wikiHow Support <support@wikihow.com>";
	$subject = wfMessage("viewership_subject");

	$cta = AuthorEmailNotification::getCTA('monthly_views', 'email');
	
	if ($articles == 1) {
		$article = "article";
	} else {
		$article = "articles";
	}

	$contribsPage = SpecialPage::getTitleFor( 'Contributions', $user->getName() );
	$contribsLink = $contribsPage->getCanonicalURL();

	$body = wfMessage("viewership_body", $user->getName(), number_format($articles), number_format($views), $cta, $article, $contribsLink)->text();
	//$link = UnsubscribeLink::newFromID($user->getID());
	//$body .= wfMessage( 'aen-optout-footer', $link->getLink())->text();

	wfDebug($email . " " . $subject . " " . $body . "\n");
	$emailCount++;

	AuthorEmailNotification::notify($user, $from_name, $subject, $body, "", true, "monthly_update");
	
	$userNames .= $user->getName() . " ";
	
	if ($emailCount > 500) {
		break;
	}
}

echo "\n\nEmail sent to:\t{$userNames}\n\n";

$endTime = microtime(true);
$timeDiff = $endTime - $startTime;

echo $emailCount . " viewership emails were sent, Finished in {$timeDiff} sec.\n";
