<?php

require_once( '../commandLine.inc' );

global $wgUser;

$user = $wgUser;
$wgUser = User::newFromName('MiscBot');

$ratingType = $argv[0];

if($ratingType == null || ($ratingType != "article" && $ratingType != "sample" && $ratingType != "star")) {
	echo "You must choose to reset sample or article or star ratings.\n";
	return;
}
echo "Are you sure you want to reset all ratings for {$ratingType}s? This cannot be undone. (y/n)\n";
$response = trim(fgets(STDIN));
if ($response != "y")
	return;

$rateitem = new RateItem();
$ratingTool = $rateitem->getRatingTool($ratingType);

$items = $ratingTool->getAllRatedItems();

foreach($items as $id) {
	$ratingTool->clearRatings($id, $wgUser, 'Resetting all ratings');
	if($ratingType == "sample")
		$ratingTool->deleteRatingReason($id);
}

echo "Ratings have been reset for all " . count($items) . " {$ratingType}s\n";

$wgUser = $user;

