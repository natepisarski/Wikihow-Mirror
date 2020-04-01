<?php

require_once __DIR__ . '/../../commandLine.inc';

$file = '/tmp/reviewsdata.csv';
echo "getting data...\n";

$dbr = wfGetDB(DB_REPLICA);
$res = $dbr->select(
	'userreview_curated',
	'*',
	[],
	__METHOD__
);

$reviews = [];
$user_ids = [];

foreach ($res as $row) {
	$t = Title::newFromId($row->uc_article_id);
	if (!$t || !$t->exists() || $t->isRedirect()) continue;

	$user = User::newFromId($row->uc_user_id);
	if ($user && !$user->isAnon()) {
		$username = $user->getRealName() ?: $user->getName();
		$user_link = 'http:'.$user->getUserPage()->getFullURL();
	}
	else {
		$username = trim($row->uc_firstname).' '.trim($row->uc_lastname);
		$user_link = '';
	}

	$reviews[] = [
		'article_id' => $row->uc_article_id,
		'article_url' => 'http:'.$t->getFullUrl(),
		'review' => $row->uc_review,
		'eligible' => $row->uc_eligible > 0 ? 'yes' : 'no',
		'uc_user_id' => $row->uc_user_id,
		'user_name' => $username,
		'user_link' => $user_link
	];

	$user_ids[] = $row->uc_user_id;
}

$dc = new UserDisplayCache($user_ids);
$display_data = $dc->getData();

foreach ($reviews as &$review) {
	$userId = $review['uc_user_id'];

	if(array_key_exists($userId, $display_data)) {
		$review['avatarUrl'] = wfGetPad($display_data[$userId]['avatar_url']);
	}
}

echo "writing output to $file...\n";

$fp = fopen($file, 'w');
fputs($fp, "Article ID,URL,Review,Eligible?,User Profile URL,User Name,Avatar?\n");
foreach ($reviews as $review) {
	$data = [
		$review['article_id'],
		$review['article_url'],
		$review['review'],
		$review['eligible'],
		$review['user_link'],
		$review['user_name'],
		isset($review['avatarUrl']) && $review['avatarUrl'] != '' ? 'yes' : 'no'
	];

	fputcsv($fp, $data);
}
fclose($fp);

echo "done.\n";
