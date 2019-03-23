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

require_once __DIR__ . '/../commandLine.inc';
$wgUser = User::newFromName('MiscBot');
$db = wfGetDB(DB_REPLICA);
$articles = array();
$articles_skipped = array();
$skipcheck = ($options['skipped']) ? true : false;

//got everything we need?
$types = array(	'miles','kilometers','yards','feet','meters','tempF','tempC',
				'inches','centimeters','gallons','liters','ounces','grams',
				'pints','milliliters','quarts','tablespoons');
$validate = array_intersect_key($options,array_flip($types));
if (count($validate) == 0) {
	print "please specify an option like --miles\n";
	return;
}

//regex components
$nums = '(\d[\d,\.\/]*)';
$numsWS = '(\d[\d,\.\s\/]*)';
$conns = '(\s?to\s?|\s?or\s?|\s?-\s?|\x{00B0}\s?-\s?)';
$negLA = '(?!\s\(|\)|<|\/|\?|\.\.|\.,|\x{00B2}|\x{00B3})';
$negLB = '(?<![:\$\^])';


print "Getting articles for adding conversion template at " . microtime(true) . "\n\n";
$res = DatabaseHelper::batchSelect('page', array('page_title'), array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ));
//$res = DatabaseHelper::batchSelect('page', array('page_title'), array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0, 'page_title' => 'Attract-Woodpeckers-to-Your-Yard' ));
//$res = DatabaseHelper::batchSelect('page', array('page_title'), array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0, 'page_title' => 'Be-a-Ninja' ));
//$res = DatabaseHelper::batchSelect('page', array('page_title'), array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ), __METHOD__, array('LIMIT' => 1000));
print "SQL query done at " . microtime(true) . "\n";
print count($res)." articles grabbed at ".microtime(true)."\n";

$i = 0;
foreach ($res as $row) {
	checkForConversion($row->page_title,$options);
	$i++;
	if($i % 5000 == 0) print $i . " articles processed at " . microtime(true) . "\n";
}

function checkForConversion($page_title,$options) {
	global $db, $articles;
	global $nums, $numWS, $conns, $negLA, $negLB;
	
	$title = Title::newFromText($page_title);
	
	//always skip stuff with titles that include "convert" or "metric" just to be safe
	//also skip protected articles
	if (!$title || preg_match('@(convert|metric|calculate)@i',$title->getText()) || $title->isProtected()) return;

	$wikitext = Wikitext::getWikitext($db, $title);
	
	//substitute tokens for things we're skipping
	list($new_wikitext, $all_tokens) = tokenize($wikitext);

	//generic regex for our skip checks
	$tablespoons_all = 'tbsp\.?s?|tablespoons?';
	$quarts_all = 'qt\.?s?|quarts?';
	$milliliters_all = 'ml\.?\s?|millilit(er|re)s?';
	$pints_all = 'pints?';
	$fluidounces_all = 'fluid\sounces?|fl\s?oz\.?';
	$grams_all = 'grams?';
	$ounces_all = 'ounces?|oz\.?s?';
	$gallons_all = 'gals?|gallons?';
	$liters_all = 'lit(er|re)s?';
	$inches_all = 'inch(es)?';
	$cms_all = 'centimet(er|re)s?|cms?';
	$meter_all = 'met(er|re)s?';
	$feet_all = 'feet|foot|ft';
	$yard_all = 'yd\.?s?|yards?';
	$km_all = 'kilometers?|kilometres?|kms?|kph|km\/h|kms\/h|k\/h';
	$miles_all = 'miles?|mi|mph|miles\sper\shour';
	$tempF_all = 'degrees|\x{00B0}|&#178;|&#deg;)\s?(F|Fahrenheit';
	$tempC_all = 'degrees|\x{00B0}|&#178;|&#deg;)\s?(C|Celsius';

	
	//=== TABLESPOONS ===
	if ($options['tablespoons']) {
		if (!preCheckArticle($wikitext, $quarts_all, $milliliters_all, $title)) {		
			$units = 'UStbsp|ml';
			$regex = '(tbsps\.?|tablespoons)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'UStbsp|ml|adj=on';
			$regex = '(tbsp\.?|tablespoon)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	
	//=== QUARTS ===
	if ($options['quarts']) {
		if (!preCheckArticle($wikitext, $quarts_all, $milliliters_all, $title)) {		
			$units = 'USqt|ml';
			$regex = '(qts\.?|quarts)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'USqt|ml|adj=on';
			$regex = '(qt\.?|quart)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	
	//=== PINTS ===
	if ($options['pints']) {
		if (!preCheckArticle($wikitext, $pints_all, $milliliters_all, $title)) {		
			$units = 'USpt|ml';
			$regex = 'pints';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'USpt|ml|adj=on';
			$regex = 'pint';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	
	//=== MILLILITERS ===
	if ($options['milliliters']) {
		if (!preCheckArticle($wikitext, $milliliters_all, $quarts_all.'|'.$pints_all, $title)) {		
			$units = 'ml|USoz|sp=us';
			$regex = '(ml\.?|millilit(?:er|re)s)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'ml|USoz|sp=us|adj=on';
			$regex = 'millilit(?:er|re)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	
	
	//=== OUNCES ===
	if ($options['ounces']) {
		//=== fluid ounces ===
		if (!preCheckArticle($wikitext, $fluidounces_all, $milliliters_all, $title)) {		
			$units = 'USfloz|ml';
			$regex = '(fluid\sounces|fl\s?oz\s?\.?)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'USfloz|ml|adj=on';
			$regex = 'fluid\sounce)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
		
		//=== ounces ===
		if (!preCheckArticle($wikitext, $ounces_all, $grams_all, $title)) {		
			$units = 'oz|g';
			$regex = '(ounces|ozs\.?)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'oz|g|adj=on';
			$regex = '(ounce|oz\.?)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	
	//=== GRAMS ===
	if ($options['grams']) {
		if (!preCheckArticle($wikitext, $grams_all, $ounces_all, $title)) {		
			$units = 'g|oz';
			$regex = 'grams';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'g|oz|adj=on';
			$regex = 'gram';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	
	//=== GALLONS ===
	if ($options['gallons']) {
		if (!preCheckArticle($wikitext, $gallons_all, $liters_all, $title)) {		
			$units = 'USgal|L';
			$regex = '(gals\.?|gallons)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'USgal|L|adj=on';
			$regex = '(gal\.?|gallon)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	
	//=== LITERS ===
	if ($options['liters']) {
		if (!preCheckArticle($wikitext, $liters_all, $gallons_all, $title)) {		
			$units = 'L|USgal|sp=us';
			$regex = 'lit(?:er|re)s';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'L|USgal|sp=us|adj=on';
			$regex = 'lit(?:er|re)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	
	//=== INCHES ===
	if ($options['inches']) {
		if (!preCheckArticle($wikitext, $inches_all, $cms_all, $title)) {		
			$units = 'in|cm';
			$regex = 'inches';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'in|cm|adj=on';
			$regex = 'inch';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}

	//=== CENTIMETERS ===
	if ($options['centimeters']) {
		if (!preCheckArticle($wikitext, $cms_all, $inches_all, $title)) {
			$units = 'cm|in|sp=us';
			$regex = '(centimet(?:er|re)s|cms\.?)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'cm|in|sp=us|adj=on';
			$regex = '(centimet(?:er|re)|cm\.?)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}

	//=== FAHRENHEIT ===
	if ($options['tempF']) {
		if (!preCheckArticle($wikitext, $tempF_all, $tempC_all, $title)) {
			$units = 'F';
			$regex = '(degrees|\x{00B0}|&#178;|&#deg;)\s?(?:F|Fahrenheit)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	
	//=== CELSIUS ===
	if ($options['tempC']) {
		if (!preCheckArticle($wikitext, $tempC_all, $tempF_all, $title)) {
			$units = 'C';
			$regex = '(degrees|\x{00B0}|&#178;|&#deg;)\s?(?:C|Celsius)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	
	//=== YARDS ===
	if ($options['yards']) {
		if (!preCheckArticle($wikitext, $yard_all, $meter_all, $title)) {
			//added custom check for the "whole 9 yards"
			if (!preg_match('@whole\s(nine|9)\syards@im',$wikitext)) {
				$units = 'yd|m';
				$regex = '(yds\.?|yards)';
				list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
				
				$units = 'yd|m|adj=on';
				$regex = '(yd\.?|yard)';
				list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			}
		}
	}
	
	//=== FEET ===
	if ($options['feet']) {
		if (!preCheckArticle($wikitext, $feet_all, $meter_all, $title)) {
			$units = 'ft|m';
			$regex = '(ft\.?|feet)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'ft|m|adj=on';
			$regex = 'foot';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	
	//=== METERS ===
	if ($options['meters']) {
		if (preg_match('@\b(run|meter|metre|radio)\b@i',$title->getText())) return;
	
		if (!preCheckArticle($wikitext, $meter_all, $feet_all.'|'.$yard_all, $title)) {
			$units = 'm|ft|sp=us';
			$regex = 'met(?:er|re)s';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'm|ft|sp=us|adj=on';
			$regex = 'met(?:er|re)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}

	
	//=== MILES/MPH ===
	if ($options['miles']) {
		//NOTE: added eminem to skip "8 Mile" references (heh)
		if (!preCheckArticle($wikitext, $miles_all, $km_all.'|eminem', $title)) {
			//=== MPH ===
			$units = 'mph|km/h|abbr=on';
			$regex = '(mph|miles\sper\shour)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			//=== MILES ===
			$units = 'mi';
			$regex = 'miles';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'mi|adj=on';
			$regex = 'mile';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
	

	//=== KILOMETER/K/H ===
	if ($options['kilometers']) {
		if (!preCheckArticle($wikitext, $km_all, $miles_all, $title)) {
			//=== K/H ===
			$units = 'km/h|mph|abbr=on';
			$regex = '(kph|km\/h|kms\/h|k\/h|kilometers\sper\shour|kilometres\sper\shour)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			//=== KILOMETERS ===
			$units = 'km|sp=us';
			$regex = '(kilomet(?:er|re)s|kms?)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
			
			$units = 'km|sp=us|adj=on';
			$regex = '(kilomet(?:er|re)|km)';
			list($new_wikitext, $all_tokens) = addConvertTemplate($new_wikitext, $regex, $units, $all_tokens);
		}
	}
		
	
	//replace all those tokens we put in
	$new_wikitext = retokenize($new_wikitext,$all_tokens);
		
	//did we change anything?
	if (strcmp($wikitext,$new_wikitext) == 0) return;

	$wp = new WikiPage($title);
	$content = ContentHandler::makeContent( $new_wikitext, $title );
	if ($wp->doEditContent($content, "Adding conversion template.")) {
		$articles[] = 'http://www.wikihow.com/'.$title->getDBkey();
	}
	
	return;
}

//strip out the image, video, template, & link stuff
//and replace with tokens
function tokenize($wikitext) {
	$tokens = array();

	//for:
	// [[ ]]
	// [ ]
	// {{ }}
	// (( ))
	// 5 x 5
	// 5 by 5
	// 5 and 5
	// urls
	// AUTHOR'S NOTE: Soooo many. I really should put them in an array or something for easier troubleshooting...
	$output = preg_replace_callback('@(\[\[.*?\]\]|\[.*?\]|\{\{.*?\}\}|\(.*?\)|\d[\d\.-\/\s]*\s?x\s?\.?\d[\d\.-\/\s]*|\d[\d\.-\/\s]*\sby\s\.?\d[\d\.-\/\s]*|\d[\d\.-\/\s]*\s?and\s?\.?\d[\d\.-\/\s]*|\d[\d\.-\/\s]*\s?&\s?\.?\d[\d\.-\/\s]*|\d[\d\.-\/\s]*\s?&amp;\s?\.?\d[\d\.-\/\s]*|\bhttps?:\/\/\S+\b)@im',
		function ($m) use (&$tokens) {
			$token = '(TOKE_' . Wikitext::genRandomString().')';
			$tokens[] = array('token' => $token, 'tag' => $m[0]);
			return $token;
		},
		$wikitext
	);
	return array($output, $tokens);
}

//need to add a single token to our token array
function addToken($wikitext,$tokens,$tag) {
	$output = preg_replace_callback("@\b$tag\b@i",
		function ($m) use (&$tokens) {
			$token = 'TOKE_' . Wikitext::genRandomString();
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


//clean up digits
//format them for fractions
//etc.
function cleanData($pre,$d1,$mid='',$d2='') {
	$d1 = trim($d1);
	$d2 = trim($d2);
	$mid = trim($mid);
	
	//need to check for a leading decimal point and add it manually
	if ($pre == '.') $d1 = '.'.$d1;
	if ($pre == '-') $d1 = '-'.$d1;
	
	//is it a fraction?
	$frac = (preg_match('@\/@',$d1) || preg_match('@\/@',$d2)) ? '|1' : '';
	if ($frac == '|1') {
		//hold on...is the denominator a zero or a letter?
		if (preg_match('@\/[0\D]\b@',$d1) || preg_match('@\/[0\D]\b@',$d2)) {
			$d1 = false;
		}
		//oh, and if there's a dash and a fraction our script will be very confused
		else if (preg_match('@-@',$d1.$mid.$d2)) {
			$d1 = false;
		}
		else {
			$d1 = preg_replace('@ @','+',$d1);
			$d2 = preg_replace('@ @','+',$d2);
		}
	}
	else {
		//double-check these are real numbers
		if (!preg_match('@^[\d,\.\/-]*\d$@',$d1)) $d1 = false;
		if ($d2 && !preg_match('@^[\d,\.\/]*\d$@',$d2)) $d1 = false;
	}
	
	//do we have more than 2 digits here?
	if (preg_match('@,\s@',$d1)) $d1 = false;
	
	//remove that degree symbol if it got in
	$mid = preg_replace('@\x{00B0}@u','',$mid);
		
	return array($d1,$mid,$d2,$frac);
}

function addConvertTemplate($wikitext, $regex, $units, $tokens) {
	global $numsWS, $conns, $negLA, $negLB;
	
	//x to y units
	if (preg_match_all("@(.?)$negLB(\b$numsWS$conns$numsWS\s?$regex\b)$negLA@iu",$wikitext,$m)) {
		list($wikitext, $tokens) = processConvertTemplates($m,$units,$wikitext,$tokens,true);
	}
	
	//x units
	if (preg_match_all("@(.?)$negLB(\b$numsWS\s?$regex\b)$negLA@iu",$wikitext,$m)) {
		list($wikitext, $tokens) = processConvertTemplates($m,$units,$wikitext,$tokens,false);
	}
	
	return array($wikitext, $tokens);
}

//put that template in
function processConvertTemplates($m, $units, $wikitext, $tokens, $isSpan) {
	global $negLA;
	$matches = $m[2];
	
	//display the first 2 decimal places
	//but not for temperatures
	$dec_round = ($units == 'F' || $units == 'C') ? '' : '|1';
	
	foreach ($matches as $key => $match) {
		list($d1,$mid,$d2,$frac) = cleanData($m[1][$key],$m[3][$key],$m[4][$key],$m[5][$key]);
		if ($d1 !== false) {
			//print $match."\n";
			$nums = ($isSpan) ? "$d1|$mid|$d2" : $d1;
			$convert_tmpl = "{{convert|$nums|$units$frac$dec_round}}";
			$wikitext = preg_replace("@([\.-]?)\b$match\b$negLA@i",$convert_tmpl,$wikitext);
			//print $convert_tmpl;
		}
		else {
			list($wikitext, $tokens) = addToken($wikitext,$tokens,$match);
		}
	}
	return array($wikitext, $tokens);
}

function preCheckArticle($wikitext, $this, $that, $title) {
	global $articles_skipped, $skipcheck;
	
	if ($skipcheck) {
		//counting potential, but skipped articles
		//(run after running the convert)
		if (preg_match("@\b\d+\s?($this)\b@iu",$wikitext)) {
			$articles_skipped[] = 'http://www.wikihow.com/'.$title->getDBkey();
		}
		return true;
	}
	else {
		//skip articles with what we're converting
		//check the original wikitext because we strip out so much
		if (preg_match("@\b\d+\s?($that)\b@iu",$wikitext)) return true;
	}
	
	//still here?
	return false;
}

if ($skipcheck) {
	print "\n\nSKIPPED ARTICLES:\n";
	foreach ($articles_skipped as $article) {
		print $article."\n";
	}
}
else {
	print "\n\n".count($articles)." articles updated.\n\n";
	print "ARTICLES:\n";
	foreach ($articles as $article) {
		print $article."\n";
	}
}
