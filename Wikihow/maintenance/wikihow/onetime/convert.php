<?php
/*
 * Add different convert templates to our articles
 *
 * USE: php add_metric_tmpl.php --[convert-from-type] [--skipped]
 *
 * --miles = miles & mph > km & km/h
 * --kilometers = kilometers & km/h > miles & mph
 * --yards = yards > meters
 * --feet = foot & feet > meters
 * --meters = meters > ft
 * --tempF = Fahrenheit > Celsius
 * --tempC = Celsius > Fahrenheit
 * --inches = inches > centimeters
 * --centimeters = centimeters > inches
 * --gallons = gallons > liters
 * --liters = liters > gallons
 * --ounces = ounces > grams & fluid ounces > milliliters
 * --grams = grams > ounces
 * --pints = pints > milliliters
 * --milliliters = milliliters > fluid ounces
 * --quarts = quarts > milliliters
 * --tablespoons = tablespoons > milliliters
 *
 * --skipped (lists potential but skipped articles)
 *
 * Unicode cheat sheet:
 *	\x{00B0} = degree sign
 *	\x{00B2} = exponent 2
 *	\x{00B3} = exponent 3
 *
 * NOTE: can't handle -.5 (negative decimals)
 */

require_once __DIR__ . '/../../commandLine.inc';
require_once __DIR__ . '/../../../extensions/wikihow/unitguardian/UnitGuardian.php';
$wgUser = User::newFromName('MiscBot');
$db = wfGetDB(DB_SLAVE);

echo microtime(true) . "\n";

$res = DatabaseHelper::batchSelect('page', array('page_id'), array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ));
//$res = DatabaseHelper::batchSelect('page', array('page_id'), array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ), __FILE__, array("LIMIT" => 10000));
print "SQL query done at " . microtime(true) . "\n";
print count($res)." articles grabbed at ".microtime(true)."\n";

$dbw = wfGetDB(DB_MASTER);

$converter = new UnitConverter();

//UnitGuardian::processArticle($converter, $dbw, 4424165);
//UnitGuardian::processArticle($converter, $dbw, 5532950);
//UnitGuardian::processArticle($converter, $dbw, 293218);
//UnitGuardian::processArticle($converter, $dbw, 4844);


$i = 0;
foreach ($res as $row) {
	UnitGuardian::addArticle($dbw, $row->page_id);
	$i++;
	if($i % 5000 == 0) print $i . " articles processed at " . microtime(true) . "\n";
}

UnitGuardian::checkAllDirtyArticles();


echo microtime(true) . "\n";
