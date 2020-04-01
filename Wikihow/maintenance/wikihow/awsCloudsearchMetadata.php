<?php
//
// Generate json data dump for the AWS Cloudsearch search engine
//
// See for data format:
// http://docs.aws.amazon.com/cloudsearch/latest/developerguide/preparing-data.html
//
// Outputs data like this:
// [
// {"type": "add",
//  "id":   "tt0484562",
//  "fields": {
//    "title": "The Seeker: The Dark Is Rising",
//    "directors": "Cunningham, David L.",
//    "genres": ["Adventure","Drama","Fantasy","Thriller"],
//    "actors": ["McShane, Ian","Eccleston, Christopher","Conroy, Frances",
//              "Crewson, Wendy","Ludwig, Alexander","Cosmo, James",
//              "Warner, Amelia","Hickey, John Benjamin","Piddock, Jim",
//              "Lockhart, Emma"]
//  }
// },
// {"type": "delete",
//  "id":   "tt0484575"
// }
//]

require_once __DIR__ . '/../Maintenance.php';

class AwsCloudsearchMetadata extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "Output metadata to be uploaded to AWS cloudsearch";
		$this->addOption('prefix', 'Output files prefix', true, true, 'p');
    }

	// from http://stackoverflow.com/questions/10199017/how-to-solve-json-error-utf8-error-in-php-json-decode
	private function utf8ize($mixed) {
		if (is_array($mixed)) {
			foreach ($mixed as $key => $value) {
				$mixed[$key] = $this->utf8ize($value);
			}
		} else if (is_string ($mixed)) {
			return utf8_encode($mixed);
		}
		return $mixed;
	}
	
    public function execute() {
		$prefix = $this->getOption('prefix');
		$titles = $this->loadTitles();
		$data = $this->getMetaData($titles);

		$chunks = array_chunk($data, 2000);
		$data = null;
		foreach ($chunks as $i=>$chunk) {
			$json_encoded = json_encode($chunk);
			if ($json_encoded === false && json_last_error() == JSON_ERROR_UTF8) {
				$chunk = $this->utf8ize($chunk);
				$json_encoded = json_encode($chunk);
			}
			if ($json_encoded === false) {
				print "JSON encode error: " . json_last_error() . "\n";
			}
			file_put_contents($prefix . $i . '.json', $json_encoded);
		}
    }

	/*
	// Take a multi-dimensional array of categories and flatten it to be 1-dimensional
	private function $this->flattenCategories($arg) {
		$result = array();
		if (is_array($arg)) {
			foreach ($arg as $k => $v) {
				$result[] = $k;
				$result = array_merge($result, $this->flattenCategories($v));
			}
		}
		return $result;
	}
	*/

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
	private function synthesizeSummary($wikitext, $maxSteps, $fullURL) {
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
	private function loadTitles() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select( ['page', 'index_info'],
			['page_namespace', 'page_title', 'page_id'],
			[
				'page_id = ii_page',
				'page_namespace' => NS_MAIN,
				'page_is_redirect' => 0,
				'ii_policy' => 4,
			],
			__FILE__,
			[
				'ORDER BY' => 'page_id',
			]);

		$titles = array();
		foreach ($res as $row) {
			$title = Title::makeTitle($row->page_namespace, $row->page_title);
			if (!$title || !$title->exists()) continue;
			$titles[] = $title;
		}

		return $titles;
	}

	/*
	 * CloudSearch index columns:
	 *
	 * title text
	 * url text
	 * full_text text
	 * categories text
	 * page_views_30day number
	 * image_url text
	 */
	private function getMetaData($titles) {
		$bunch = [];
		foreach ($titles as $title) {

			$out = [ 'type' => 'add', 'id' => 'wh' . $title->getArticleId() ];
			$fields = [];

			$rev = Revision::newFromTitle($title);
			if (!$rev) continue;

			$full_url = 'http://' . wfCanonicalDomain() . '/' . $title->getPartialURL();
			$wikitext = ContentHandler::getContentText( $rev->getContent() );
			$full_text = Wikitext::flatten($wikitext);
			$fields['url'] = $full_url;
			$fields['full_text'] = $full_text;

			/*
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
				// pull out intro
				global $wgParser;
				$intro = $wgParser->getSection($wikitext, 0);

				$abstract = $intro;
			}
			*/

			// category info
			$cats = $title->getParentCategories();
			$cat_strs = [];
			$bad_cats = [ 'Featured Articles', 'WikiHow' ];
			foreach ($cats as $cat => $a) {
				if ($cat) {
					$cat_title = Title::newFromURL($cat);
					if ($cat_title && $cat_title->inNamespace(NS_CATEGORY)) {
						$cat_text = $cat_title->getText();
						if (!in_array($cat_text, $bad_cats)) {
							$cat_strs[] = $cat_text;
						}
					}
				}
			}
			$categories = implode(" ", $cat_strs);
			$fields['categories'] = $categories;

			$regular_title = $title->getText();
			$howto_title = wfMessage('howto', $regular_title)->text();
			$fields['title'] = $howto_title;

			$fields['page_views_30day'] = 0;
			$fields['image_url'] = '';

			$out['fields'] = $fields;
			$bunch[] = $out;
		}

		return $bunch;
	}
}

$maintClass = "AwsCloudsearchMetadata";
require_once RUN_MAINTENANCE_IF_MAIN;

