<?php

class UnitConverter {
	var $nums = '(\d[\d,\.\/]*)';
	var $numsWS = '(\d[\d,\.\s\/]*)';
	var $conns = '(\s?to\s?|\s?or\s?|\s?-\s?|\x{00B0}\s?-\s?)';
	var $negLA = '(?!\s\(|\)|<|\/|\?|\.\.|\.,|\x{00B2}|\x{00B3})';
	var $negLB = '(?<![:\$\^])';
	var $excess = 10;
	var $token = "TOKE_";

	function checkForConversion($pageId, &$db) {

		$title = Title::newFromID($pageId);

		//always skip stuff with titles that include "convert" or "metric" just to be safe
		//also skip protected articles
		if (!$title || preg_match('@(convert|metric|calculate)@i',$title->getText()) || $title->isProtected()) return;

		$wikitext = Wikitext::getWikitext($db, $title);

		//substitute tokens for things we're skipping
		list($new_wikitext, $all_tokens) = $this->tokenize($wikitext);

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
		if (!$this->preCheckArticle($wikitext, $quarts_all, $milliliters_all, $title)) {
			$units = 'UStbsp|ml';
			$regex = '(tbsps\.?|tablespoons)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'UStbsp|ml|adj=on';
			$regex = '(tbsp\.?|tablespoon)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//=== QUARTS ===
		if (!$this->preCheckArticle($wikitext, $quarts_all, $milliliters_all, $title)) {
			$units = 'USqt|ml';
			$regex = '(qts\.?|quarts)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'USqt|ml|adj=on';
			$regex = '(qt\.?|quart)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//=== PINTS ===
		if (!$this->preCheckArticle($wikitext, $pints_all, $milliliters_all, $title)) {
			$units = 'USpt|ml';
			$regex = 'pints';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'USpt|ml|adj=on';
			$regex = 'pint';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//=== MILLILITERS ===
		if (!$this->preCheckArticle($wikitext, $milliliters_all, $quarts_all.'|'.$pints_all, $title)) {
			$units = 'ml|USoz|sp=us';
			$regex = '(ml\.?|millilit(?:er|re)s)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'ml|USoz|sp=us|adj=on';
			$regex = 'millilit(?:er|re)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}


		//=== OUNCES ===
		//=== fluid ounces ===
		if (!$this->preCheckArticle($wikitext, $fluidounces_all, $milliliters_all, $title)) {
			$units = 'USfloz|ml';
			$regex = '(fluid\sounces|fl\s?oz\s?\.?)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'USfloz|ml|adj=on';
			$regex = '(fluid\sounce)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//for now we don't want to do ounces. Too many people say oz when they really mean fl oz
		//but not enough people understand the difference
		//=== ounces ===
		/*if (!$this->preCheckArticle($wikitext, $ounces_all, $grams_all, $title)) {
			$units = 'oz|g';
			$regex = '(ounces|ozs\.?)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'oz|g|adj=on';
			$regex = '(ounce|oz\.?)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}*/

		//=== GRAMS ===
		if (!$this->preCheckArticle($wikitext, $grams_all, $ounces_all, $title)) {
			$units = 'g|oz';
			$regex = 'grams';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'g|oz|adj=on';
			$regex = 'gram';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//=== GALLONS ===
		if (!$this->preCheckArticle($wikitext, $gallons_all, $liters_all, $title)) {
			$units = 'USgal|L';
			$regex = '(gals\.?|gallons)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'USgal|L|adj=on';
			$regex = '(gal\.?|gallon)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//=== LITERS ===
		if (!$this->preCheckArticle($wikitext, $liters_all, $gallons_all, $title)) {
			$units = 'L|USgal|sp=us';
			$regex = 'lit(?:er|re)s';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'L|USgal|sp=us|adj=on';
			$regex = 'lit(?:er|re)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//=== INCHES ===
		if (!$this->preCheckArticle($wikitext, $inches_all, $cms_all, $title)) {
			$units = 'in|cm';
			$regex = 'inches';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'in|cm|adj=on';
			$regex = 'inch';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//=== CENTIMETERS ===
		if (!$this->preCheckArticle($wikitext, $cms_all, $inches_all, $title)) {
			$units = 'cm|in|sp=us';
			$regex = '(centimet(?:er|re)s|cms\.?)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'cm|in|sp=us|adj=on';
			$regex = '(centimet(?:er|re)|cm\.?)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//=== FAHRENHEIT ===
		if (!$this->preCheckArticle($wikitext, $tempF_all, $tempC_all, $title)) {
			$units = 'F';
			$regex = '(degrees|\x{00B0}|&#178;|&#deg;)\s?(?:F|Fahrenheit)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//=== CELSIUS ===
		if (!$this->preCheckArticle($wikitext, $tempC_all, $tempF_all, $title)) {
			$units = 'C';
			$regex = '(degrees|\x{00B0}|&#178;|&#deg;)\s?(?:C|Celsius)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//=== YARDS ===
		if (!$this->preCheckArticle($wikitext, $yard_all, $meter_all, $title)) {
			//added custom check for the "whole 9 yards"
			if (!preg_match('@whole\s(nine|9)\syards@im',$wikitext)) {
				$units = 'yd|m';
				$regex = '(yds\.?|yards)';
				list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

				$units = 'yd|m|adj=on';
				$regex = '(yd\.?|yard)';
				list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
			}
		}

		//=== FEET ===
		if (!$this->preCheckArticle($wikitext, $feet_all, $meter_all, $title)) {
			$units = 'ft|m';
			$regex = '(ft\.?|feet)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'ft|m|adj=on';
			$regex = 'foot';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}

		//=== METERS ===
		if (preg_match('@\b(run|meter|metre|radio)\b@i',$title->getText())) return;

		if (!$this->preCheckArticle($wikitext, $meter_all, $feet_all.'|'.$yard_all, $title)) {
			$units = 'm|ft|sp=us';
			$regex = 'met(?:er|re)s';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'm|ft|sp=us|adj=on';
			$regex = 'met(?:er|re)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}


		//=== MILES/MPH ===
		//NOTE: added eminem to skip "8 Mile" references (heh)
		if (!$this->preCheckArticle($wikitext, $miles_all, $km_all.'|eminem', $title)) {
			//=== MPH ===
			$units = 'mph|km/h|abbr=on';
			$regex = '(mph|miles\sper\shour)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			//=== MILES ===
			$units = 'mi';
			$regex = 'miles';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'mi|adj=on';
			$regex = 'mile';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
		}


		//=== KILOMETER/K/H ===
		if (!$this->preCheckArticle($wikitext, $km_all, $miles_all, $title)) {
			//=== K/H ===
			$units = 'km/h|mph|abbr=on';
			$regex = '(kph|km\/h|kms\/h|k\/h|kilometers\sper\shour|kilometres\sper\shour)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			//=== KILOMETERS ===
			$units = 'km|sp=us';
			$regex = '(kilomet(?:er|re)s|kms?)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);

			$units = 'km|sp=us|adj=on';
			$regex = '(kilomet(?:er|re)|km)';
			list($new_wikitext, $all_tokens) = $this->addConvertTemplate($new_wikitext, $regex, $units, $pageId);
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
				$token = $this->token . Wikitext::genRandomString() . $this->token;
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
				$token = $this->token . Wikitext::genRandomString() . $this->token;
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
			elseif (preg_match('@-@',$d1.$mid.$d2)) {
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

	function addConvertTemplate($wikitext, $regex, $units, $articleId) {
		$tokens = array();
		//x to y units
		if (preg_match_all("@(.{{$this->excess}})(.?){$this->negLB}(\b{$this->numsWS}{$this->conns}{$this->numsWS}\s?$regex\b)$this->negLA(.{{$this->excess}})@iu",$wikitext,$m)) {

			$convertedTemplates = $this->getConvertTemplate($m, $units, true);

			foreach ($convertedTemplates as $i => $template) {
				if ($template !== false) {
					//check for a token - we took 10 extra characters, check for "TOKE_"
					//start
					$startLoc = strpos($m[1][$i], $this->token);
					if ($startLoc === false) {
						$beg = 5; //if we take of 5 characters we'll ensure we don't have any part of a token
					} else {
						$beg = $startLoc + 5;
					}
					//end
					$lastIndex = count($m) - 1; //we need to do this b/c the regex for the various unit return different sizes arrays
					$endLoc = strpos($m[$lastIndex][$i], $this->token);
					if ($endLoc === false) {
						$end = -5;
					} else {
						$end = $endLoc - $this->excess;
					}
					$text = substr($m[0][$i], $beg, $end);
					$finalWikiText = str_replace($m[3][$i], $template, $text);
					$convertedHtml = str_replace($m[3][$i], $this->parseTemplate($template), $text);
					UnitGuardian::insertNewConversion($articleId, $text, $convertedHtml, $finalWikiText);
					//need to add tokens so that the preg_match_all doesn't grab these numbers
				}
				list($wikitext, $tokens) = $this->addToken($wikitext,$tokens,$m[3][$i]);
			}
		}

		//x units
		if (preg_match_all("@(.{{$this->excess}})(.?){$this->negLB}(\b{$this->numsWS}\s?$regex\b){$this->negLA}(.{{$this->excess}})@iu",$wikitext,$m)) {
			$convertedTemplates = $this->getConvertTemplate($m, $units, false);
			foreach ($convertedTemplates as $i => $template) {
				if ($template !== false) {
					//check for a token - we took 10 extra characters, check for "TOKE_"
					//start
					$startLoc = strpos($m[1][$i], $this->token);
					if ($startLoc === false) {
						$beg = 5; //if we take of 5 characters we'll ensure we don't have any part of a token
					} else {
						$beg = $startLoc + 5;
					}
					//end
					$lastIndex = count($m) - 1; //we need to do this b/c the regex for the various unit return different sizes arrays
					$endLoc = strpos($m[$lastIndex][$i], $this->token);
					if ($endLoc === false) {
						$end = -5;
					} else {
						$end = $endLoc - $this->excess;
					}
					$text = substr($m[0][$i], $beg, $end);
					$period = substr($m[6][$i], 0, 1); //this is for the end of the unit

					//add back the negative or the decimal if there was one
					$conversion = $m[3][$i];
					if ($m[2][$i] == "." || $m[2][$i] == "-") {
						$conversion = $m[2][$i] . $conversion;
					}
					if ($m[5][$i] == "oz" && $period == ".") {
						$finalWikiText = str_replace($conversion.".", $template, $text);
						$convertedHtml = str_replace($conversion.".", $this->parseTemplate($template), $text);
					} else {
						$finalWikiText = str_replace($conversion, $template, $text);
						$convertedHtml = str_replace($conversion, $this->parseTemplate($template), $text);
					}
					UnitGuardian::insertNewConversion($articleId, $text, $convertedHtml, $finalWikiText);
				}
			}
		}

		return array($wikitext, $tokens);
	}

	function parseTemplate($template) {
		global $wgUser, $wgParser;

		$options = ParserOptions::newFromUser( $wgUser );
		$convertedTemplate = $wgParser->preprocess($template,null,$options);
		$convertedTemplate = str_replace('&nbsp;',' ',$convertedTemplate);

		return $convertedTemplate;
	}

	function getConvertTemplate($m, $units, $isSpan) {

		$matches = $m[3];

		$templates = array();

		foreach ($matches as $key => $match) {
			list($d1,$mid,$d2,$frac) = $this->cleanData($m[2][$key],$m[4][$key],$m[5][$key],($isSpan?$m[6][$key]:null));
			if ($d1 !== false) {
				$nums = ($isSpan) ? "$d1|$mid|$d2" : $d1;
				$tempTemplate = "{{convert|$nums|$units$frac|sigfig=5}}";

				$convertedTemplate = $this->parseTemplate($tempTemplate);
				$matches = $this->parseConvertedTemplate($convertedTemplate);
				$isTemp = ($units == "F" || $units == "C");
				$previous = trim($m[1][$key]);
				$loc = strrpos($previous, " ");
				if ($loc !== false) {
					$previousWord = substr($previous, $loc + 1); //+1 to get rid of the space
				} else {
					$previousWord = $previous;
				}
				list($parameter, $precision) = $this->getConvertedPrecision($d1, @$matches[1], $previousWord, $isTemp);
				$templates[] = "{{convert|$nums|$units$frac|$parameter=$precision}}";
			}
			else {
				$templates[] = false;
			}
		}

		return $templates;
	}

	function parseConvertedTemplate($convertedTemplateString) {
		preg_match('@\((-?\d*\.?\d+)\s?[-–]?\s?(-?\d*\.?\d+)?@u', $convertedTemplateString, $matches);
		return $matches;
	}

	function preCheckArticle($wikitext, $this2, $that, $title) {
		global $articles_skipped, $skipcheck;

		if ($skipcheck) {
			//counting potential, but skipped articles
			//(run after running the convert)
			if (preg_match("@\b\d+\s?($this2)\b@iu",$wikitext)) {
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

	function getConvertedPrecision($originalNumber, $convertedNumber, $wordBefore, $canBeZero) {
		$wordBefore = strtolower($wordBefore);

		$param = "sigfig";
		$absConvertedNumber = abs($convertedNumber);

		$originalDecimalPlace = strpos($originalNumber, ".");
		$originalPrecision = 0;
		if ($originalDecimalPlace !== false) {
			$originalPrecision = strlen($originalNumber) - $originalDecimalPlace - 1;
		}

		$convertedDecimalPlace = strpos($convertedNumber, ".");
		$convertedPrecision = 0;
		if ($convertedDecimalPlace !== false) {
			$convertedPrecision = strlen($convertedNumber) - $convertedDecimalPlace + 1;
		}

		if ($originalPrecision > 0) {
			$newPrecision = $originalPrecision;
			if ($newPrecision < $convertedPrecision) {
				$convertedNumber = substr($convertedNumber, 0, strlen($convertedNumber) - ($newPrecision - $convertedPrecision));
			}
		} else {
			if ( in_array($wordBefore, array('about', 'around', 'say', 'approximately', 'roughly')) ) {
				if ( $absConvertedNumber < 1) {
					$newPrecision = 1;
				} elseif ($absConvertedNumber < 1000) {
					$newPrecision = 0;
				} else {
					$param = "round";
					$newPrecision = 10;
				}
			} elseif ( in_array($wordBefore, array('exactly', 'precisely')) ) {
				if ($absConvertedNumber < 100) {
					$newPrecision = 2;
				} else {
					$newPrecision = 1;
				}
			} else {
				if ($absConvertedNumber < 1) {
					$newPrecision = 2;
				} elseif ($absConvertedNumber < 100) {
					$newPrecision = 1;
				} else {
					$newPrecision = 1;
					$param = "round";
				}
			}
		}

		//got the new precision, so make the new number and make sure
		if ($param == "sigfig") {
			if ($convertedNumber >= 1) {
				$roundedNumber = round($convertedNumber, $newPrecision);
				if (!$canBeZero && $roundedNumber == 0) {
					//we don't want to round to zero, make precision whatever is needed not to be zero
					//I don't think this is used anymore, but leaving in for now
					for ($i = 0; $i < strlen($convertedNumber); $i++) {
						if ($convertedNumber[$i] != "0" && $convertedNumber[$i] != ".") {
							break;
						}
					}
					$convertedDecimalPlace = strpos($convertedNumber, ".");
					$newPrecision = $i - $convertedDecimalPlace;
					$roundedNumber = round($convertedNumber, $newPrecision);
				}
				$convertedNumber = $roundedNumber;

				//round automatically takes care of the trailing zero, so we need to account for that
				$convertedDecimalPlace = strpos($convertedNumber, ".");
				if ($convertedDecimalPlace === false) {
					$newPrecision = 0;
				} else {
					$newPrecision = strlen($convertedNumber) - $convertedDecimalPlace - 1;
				}

				//if the number is > 1, then what we call sigfig, isn't right. We need total digits
				//now actually calculate the sigfig (total digits) from the precision
				if (abs($convertedNumber) >= 1) {
					if ($convertedNumber < 0) {
						$digits = strlen(ceil($convertedNumber)) - 1;
					} else {
						$digits = strlen(floor($convertedNumber));
					}
					$newPrecision = $digits + $newPrecision;
				}
			} else {
				//less than 1, so the round function doesn't do sigfig, need to do it by hand.

			}
		} else {
			$convertedNumber = $newPrecision * round($convertedNumber / $newPrecision);
		}

		//echo "New precision: $newPrecision new number $convertedNumber\n";

		return array($param, $newPrecision);

	}

}
