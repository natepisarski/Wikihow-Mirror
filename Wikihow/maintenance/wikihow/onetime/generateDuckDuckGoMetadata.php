<?php
//
// Generate tabular data dump for the Duck Duck Go search engine to show zero-click 
// inline results for how-to queries.
//
// Note: this data takes about 20 minutes to generate as of Nov. 2 2012 (Reuben)
//
// See for data format:
// https://github.com/duckduckgo/zeroclickinfo-fathead#general-data-file-format
//

require_once __DIR__ . '/../../commandLine.inc';

global $IP;
require_once "$IP/extensions/wikihow/RobotPolicy.class.php";

global $baseMemory;
$baseMemory = memory_get_usage();

// Take a multi-dimensional array of categories and flatten it to be 1-dimensional
function flattenCategories($arg) {
	$result = array();
	if (is_array($arg)) {
		foreach ($arg as $k => $v) {
			$result[] = $k;
			$result = array_merge($result, flattenCategories($v));
		}
	}
	return $result;
}

// From email robert picard at duck duck go dot com. His idea is to pull out the bold
// part of the first alt method steps section and display that as the summary.
//
// Example: 1080 on a bmx (How-to)
// ---------------------------------------
// 1. Get some good speed to a jump box -- something used in the last dew tour -- or 
//    a big dirt jump when your back tire hits the coping or lip look up diagonal 
//    at the way you're turning.
// 2. Give it a real hard pull and turn.
// 3. When you have done the 1080 spot, you're about to land on the ground.
// 4. Roll away cleanly.
// ---------------------------------------
function synthesizeSummary($wikitext, $maxSteps, $fullURL) {
	$stepsSec = Wikitext::getStepsSection($wikitext, true);
	if (!$stepsSec) return '';
	$stepsText = Wikitext::stripHeader($stepsSec[0]);
	if (Wikitext::countAltMethods($stepsText) > 0) {
		$altMethods = Wikitext::splitAltMethods($stepsText);
		foreach ($altMethods as $method) {
			if (Wikitext::isAltMethod($method) && Wikitext::countSteps($method) > 0) {
				$stepsText = $method;
				break;
			}
		}
	}
	$countSteps = Wikitext::countSteps($stepsText);

	$summaryOut = '';
	$steps = Wikitext::splitSteps($stepsText);
	$count = 0;
	foreach ($steps as $step) {
		if (Wikitext::isStepSimple($step, false)) {
			$summary = Wikitext::summarizeStep($step);
			$summary = Wikitext::removeRefsFromFlattened($summary);
			if ($summary) {
				$count++;
				$break = $count > 1 ? "<br>" : '';
				if ($count > $maxSteps) {
					$remaining = $countSteps - $maxSteps;
					$text = '';
					if ($remaining >= 2) {
						$text = "$remaining more steps at wikiHow";
					} elseif ($remaining == 1) {
						$text = "Another step at wikiHow";
					}
					if ($text) {
						$href = htmlspecialchars($fullURL);
						$link = "<a href='$href'>$text</a>";
						$summaryOut .= "$break$link";
					}
					break;
				} else {
					$summaryOut .= "$break$count. $summary";
				}
			}
		}
	}
	return $summaryOut;
}

// Load the selected titles from the database
function loadTitles() {
	global $baseMemory;
	$dbr = wfGetDB(DB_REPLICA);

	$res = $dbr->select('page',
		array('page_namespace', 'page_title'),
		array(
			'page_namespace' => NS_MAIN,
			'page_is_redirect' => 0,
			'page_id NOT IN (5, 5791)',
		),
		__FILE__,
		array(
			'ORDER BY' => 'page_id',
		));

	$titles = array();
	foreach ($res as $row) {
		$title = Title::makeTitle($row->page_namespace, $row->page_title);
		if (!$title) continue;
		$indexed = RobotPolicy::isTitleIndexable($title);
		if (!$indexed) continue;
		$titles[] = $title;
	}

	return $titles;
}

// Generate and dump the DDG data to stdout
function printDDGdata(&$titles) {
	global $baseMemory, $wgParser;

	foreach ($titles as $title) {

		$rev = Revision::newFromTitle($title);
		if (!$rev) continue;

		$full_url = $title->getFullURL();
		$wikitext = ContentHandler::getContentText( $rev->getContent() );

		// pull out intro
		$intro = $wgParser->getSection($wikitext, 0);

		// try to generate the abstract using a couple different ways
		$abstract = synthesizeSummary($wikitext, 3, $full_url);
		if (!$abstract) {
			// meta description
			$ami = new ArticleMetaInfo($title);
			if ($ami) {
				$abstract = $ami->getDescription();
			}
		}
		if (!$abstract) {
			$abstract = $intro;
		}

		// intro image
		$photo = "";
		preg_match_all("@\[\[Image:[^\]]*\]\]@", $wikitext, $matches);
		if (sizeof($matches) > 0) {
			$img = preg_replace("@.*Image:@", "", $matches[0][0]);
			$img = ucfirst(preg_replace("@[\|].*\]\]@", "", $img));
			$img = Title::makeTitle(NS_IMAGE, $img);
			$file = wfFindFile($img);

			if ($file) {
				$photo = wfGetPad($file->getURL());
			}
		}
		$images = $photo ? '[[Image:' . $photo . ']]' : '';

		// category info
		$cats = $title->getParentCategories();
		$cat_strs = array();
		$bad_cats = array('Featured Articles');
		foreach ($cats as $cat => $a) {
			if ($cat) {
				$cat_title = Title::newFromURL($cat);
				if ($cat_title && $cat_title->getNamespace() == NS_CATEGORY) {
					$cat_text = $cat_title->getText();
					if (!in_array($cat_text, $bad_cats)) {
						$cat_strs[] = $cat_text;
					}
				}
			}
		}
		$categories = implode("\\\\n", $cat_strs);

		$regular_title = $title->getText();
		$howto_title = wfMessage('howto', $regular_title);
		print "$howto_title\tA\t\t\t$categories\t\t\t\t\t\t$images\t$abstract\t$full_url\n";

		//if (@$index++ % 1000 == 0) {
		//	print "#" . date("r") . " - " . (memory_get_usage() - $baseMemory) . "\n";
		//}

	}
}

function main() {
	global $wgServer;
	$wgServer = 'http://www.wikihow.com';
	$titles = loadTitles();
	printDDGdata($titles);
}

main();

