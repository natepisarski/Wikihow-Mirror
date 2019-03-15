<?php

global $IP;
require_once("$IP/extensions/wikihow/dedup/dedupQuery.php");

/**
 * Find the category of queries by using Dedupping data.
 * We weight the connections of a query to articles,
 * and total for the value of their categories.
 */
class QueryCat extends UnlistedSpecialPage {
    public function __construct() {
		parent::__construct("QueryCat");
	}

	public static function getCategoryTree($categoryName) {
		$t = Title::newFromText($categoryName);
		$sc = $t->getParentCategories();
		foreach ($sc as $parent => $cat) {
			if ($parent == $cat) {
				return(array());
			}
			$cats = self::getCategoryTree($parent);
			$cats[] = $cat;
			return($cats);
		}
		return(array($categoryName));
	}

	public static function printQueryLevelCat($query, $level = 2, $max = false, $printScore = true) {
		DedupQuery::addQuery($query);
		DedupQuery::matchQueries(array($query));
		$cats = DedupQuery::getCategories($query);

		// Determine the score of the secondary level cats from the scores of the bottom level
		$sLevelCats = array();
		foreach ($cats as $cat) {
			$cTree = self::getCategoryTree('Category:' . $cat['cat']);
			//print_r($cTree[1]);
			$cTreeLen = sizeof($cTree);
			if ($cTreeLen >= ($level + 1)) {
				if ($cTree[0] != 'Category:WikiHow') {
					$sLevelCats[$cTree[$level]] += $cat['score'];
				}
			}
		}
		arsort($sLevelCats);
		$numPrinted = 0;
		foreach ($sLevelCats as $cat => $score) {
			print(str_replace('Category:','',$cat) . "\t");
			if ($printScore) {
				print($score . "\t");
			}
			$numPrinted++;
			if ($max && $numPrinted >= $max) {
				break;
			}
		}
	}

	public function execute($par) {
		global $wgRequest, $wgOut;
        $queries = $wgRequest->getVal('queries');
		$checkUrls = $wgRequest->getVal('checkUrls');
        if ($queries == NULL) {
            EasyTemplate::set_path(__DIR__);
            $wgOut->addHTML(EasyTemplate::html('QueryCat.tmpl.php'));
		}
		elseif($checkUrls) {
		    header("Content-Type: text/tsv");
			header('Content-Disposition: attachment; filename="Dedup.xls"');

			$urls = preg_split("@[\r\n]+@",$queries);
			foreach ($urls as $url) {
				if (preg_match("@http://www\.wikihow\.com/(.+)@",$url, $matches)) {
					$t = Title::newFromText($matches[1]);
					if (!$t) {
						$t = Title::newFromText(urldecode($matches[1]));
					}
					if ($t) {
						$cats = $t->getParentCategories();
						$cats = array_keys($cats);
						$sls = array();
						foreach ($cats as $cat) {
							$tree = $this->getCategoryTree($cat);
							if (sizeof($tree) >= 2 && $tree[0] != "Category:WikiHow") {
								$sls[str_replace('Category:','',$tree[1])] = 1;
							}
						}
						print $url;
						foreach ($sls as $sl => $v) {
							print "\t" . $sl;
						}
						print "\n";
					}
				}
			}
			exit;
		}
		else {
		    header("Content-Type: text/tsv");
			header('Content-Disposition: attachment; filename="Dedup.xls"');
			print("Query\tTop Level Match\t2nd level match\t3rd level matches\n");
	        $queries = preg_split("@[\r\n]+@",$queries);
			foreach ($queries as $query) {
				print $query . "\t";
				self::printQueryLevelCat($query,0,1,false);
				self::printQueryLevelCat($query,1,1,false);
				self::printQueryLevelCat($query,2,false,true);
				print "\n";
			}
			exit;
		}
	}
}
