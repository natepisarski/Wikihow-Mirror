<?php

class CategoryHelper extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'CategoryHelper' );
	}

	private static function getCategoryDropDownTree() {
		//global $wgMemc;

		//$key = wfMemcKey('category', 'dropdowntree', 'wikihow');
		//$result = $wgMemc->get( $key );
		//if (!is_array($result)) {
			$t = Title::makeTitle(NS_PROJECT, wfMessage('categories')->text());
			$r = Revision::newFromTitle($t);
			if (!$r) return array();
			$text = ContentHandler::getContentText( $r->getContent() );

			$lines = explode("\n", $text);
			$bucket = array();
			$result = array();
			$bucketname = '';
			foreach ($lines as $line) {
				if (strlen($line) > 1
					&& strpos($line, "*") == 0
					&& strpos($line, "*", 1) === false) {
					$result[$bucketname] = $bucket;
					$bucket = array();
					$bucketname = trim(str_replace("*", "", $line));
				} elseif (trim($line) != "") {
					$bucket[] = trim($line);
				}
			}

		//	$wgMemc->set($key, $result, time() + 3600);
		//}
		return $result;
	}

	// Used in category guardian
	public static function decategorize($pageId, $categorySlug, $summary, $flags=null, $user=null) {
		global $wgContLang;

		$wikiPage = WikiPage::newFromID($pageId);

		$cat = "[[" . $wgContLang->getNSText(NS_CATEGORY) . ":$categorySlug]]";
		$spaceCat = str_replace('-', ' ', $cat);

		if ($wikiPage && $wikiPage->exists()) {
			$text = ContentHandler::getContentText( $wikiPage->getContent() );

			$text = str_replace(array($cat, $spaceCat), '', $text);
			$content = ContentHandler::makeContent($text, $wikiPage->getTitle());
			$wikiPage->doEditContent($content, $summary, $flags, false, $user);
		}
	}

	private static function makeCategoryArray($current_lvl, &$lines) {
		$pattern = '/^(\*+)/';
		$bucket2 = array();

		// Remove any leading lines of the category messages, before the first
		// line that has a "*" at the start. This has been a problem on INTL.
		if ($current_lvl == 1) {
			while (count($lines) > 0) {
				$line = array_shift($lines);
				if (preg_match($pattern, $line, $matches)) {
					array_unshift($lines, $line);
					break;
				}
			}
		}

		$cat = null;
		while (count($lines) > 0) {
			$line = array_shift($lines);
			// skip blank lines
			if (trim($line) == "") continue;

			preg_match($pattern, $line, $matches);
			$lvl = count($matches) ? strlen($matches[0]) : 0;

			$prevcat = $cat;
			$cat = trim(str_replace("*", "", $line));
			$cat = self::removeQuotesFromCategoryName($cat);

			if ($current_lvl == $lvl) {
				//array_push($bucket2,$cat);
				$bucket2[$cat] = $cat;
			} elseif ($lvl > $current_lvl) {
				array_unshift($lines, $line);
				$bucket2[$prevcat] = self::makeCategoryArray($current_lvl + 1, $lines);
			} else {
				array_unshift($lines, $line);
				return $bucket2;
			}
		}

		return $bucket2;
	}

	/**
	 * Category names on INTL can sometimes have a symmetric number of
	 * single-quote character (') around the title. We want to strip that
	 * if we can detect it.
	 *
	 * For example:
	 * INPUT: '''''Animaux et Animaux de Compagnie'''''
	 * OUTPUT: Animaux et Animaux de Compagnie
	 */
	private static function removeQuotesFromCategoryName($cat) {
		// Use regex back references to detect and remove the quotes
		if ( preg_match("@^('+)(.*)\\1$@", $cat, $matches) ) {
			$cat = $matches[2];
		}
		return $cat;
	}

	/* UNUSED
	static function getRandomCategory() {
		$lines = self::getAllCategories();
		$index = array_rand($lines);
		$title = str_replace('*', '', $lines[$index]);
		return Title::newFromText($title, NS_CATEGORY); // fix for URL bug
	} */

	// Used in this class and in Category Guardian
	public static function getAllCategories() {
		//global $wgMemc;

		//$key = wfMemcKey('category', 'arraytree', 'wikihow');
		//$result = $wgMemc->get( $key );
		//if (!$result) {

		// Try the natively named category structure project page first
		$title = self::getCategoryTreeTitle();
		$rev = Revision::newFromTitle($title);
		if (!$rev) {
			return [];
		}

		$text = ContentHandler::getContentText( $rev->getContent() );
		$text = preg_replace('/^\n/m', '', $text);

		$lines = explode("\n", $text);
		//	$wgMemc->set($key, $result, time() + 3600);
		//}
		return $lines;
	}

	public static function getCategoryTreeArray() {
		$lines = self::getAllCategories();
		$result = self::makeCategoryArray(1, $lines);
		return $result;
	}

	/**
	 * Get all indexable categories in the tree as a hash table:
	 *   array (
	 *     'Arts and Entertainment' => 1,
	 *     'Amusement and Theme Parks' => 1,
	 *     'Carnivals' => 1,
	 *     ...
	 */
	public static function getIndexableCategoriesFromTree(): array {
		global $wgMemc;

		$lastID = self::getCategoryTreeTitle()->getLatestRevID();
		$cacheKey = wfMemcKey('categ_tree_map');
		$data = $wgMemc->get($cacheKey);

		if (is_array($data) && $data['last_id'] == $lastID) {
			return $data['map'];
		}

		$tree = self::getCategoryTreeArray();
		unset($tree['WikiHow']); // We don't want these categories to be indexed

		$map = [];
		$getKeys = function(array $tree) use (&$map, &$getKeys) {
			foreach ($tree as $categ => $subTree) {
				$map[$categ] = 1;
				if (is_array($subTree)) {
					$getKeys($subTree);
				}
			}
		};
		$getKeys($tree);

		$data = [ 'last_id' => $lastID, 'map' => $map ];
		$wgMemc->set($cacheKey, $data); // cache until there is a new tree revision

		return $map;
	}

	public static function getCurrentParentCategories($title = null) {
		global $wgTitle, $wgMemc;

		$title = $title ?: $wgTitle;

		$cachekey = wfMemcKey('parentcats', $title->getArticleId());
		$cats = $wgMemc->get($cachekey);
		if ($cats) return $cats;

		$cats = $title->getParentCategories();

		$wgMemc->set($cachekey, $cats);
		return $cats;
	}

	public static function getCurrentParentCategoryTree($title = null) {
		global $wgTitle, $wgMemc;

		if (!$title) {
			$title = $wgTitle;
		}

		$cachekey = wfMemcKey('parentcattree', $title->getArticleId());

		// First check the wikiHow hashmap cache, since this is fastest
		$hashCache = ObjectCache::getInstance(WH_HASHMAP_CACHE);
		$cats = $hashCache->get($cachekey);
		if ($cats) {
			return $cats;
		}

		// Then check memcache
		$cats = $wgMemc->get($cachekey);
		if ($cats){
			// Set the hash cache so it can be used for subsequent calls to this function
			$hashCache->set($cachekey, $cats);

			return $cats;
		}

		$cats = $title->getParentCategoryTree();

		$wgMemc->set($cachekey, $cats);
		return $cats;
	}

	// Used in this class and by wikihowAds.php
	public static function cleanUpCategoryTree($tree) {
		$results = array();
		if (!is_array($tree)) return $results;
		foreach ($tree as $cat) {
			$t = Title::newFromText($cat);
			if ($t)
				$results[]= $t->getText();
		}
		return $results;
	}

	public static function flattenCategoryTree($tree) {
		if (is_array($tree)) {
			$results = array();
			foreach ($tree as $key => $value) {
				$results[] = $key;
				$x = self::flattenCategoryTree($value);
				if (is_array($x))
					return array_merge($results, $x);
				else
					return $results;
			}
		} else {
			$results = array();
			$results[] = $tree;
			return $results;
		}
	}

	public static function getIconMap() {
		$catmap = array(
			wfMessage("arts-and-entertainment")->text() => "Image:Category_arts.jpg",
			wfMessage("health")->text() => "Image:Category_health.jpg",
			wfMessage("relationships")->text() => "Image:Category_relationships.jpg",
			wfMessage("cars-&-other-vehicles")->text() => "Image:Category_cars.jpg",
			wfMessage("hobbies-and-crafts")->text() => "Image:Category_hobbies.jpg",
			wfMessage("sports-and-fitness")->text() => "Image:Category_sports.jpg",
			wfMessage("computers-and-electronics")->text() => "Image:Category_computers.jpg",
			wfMessage("holidays-and-traditions")->text() => "Image:Category_holidays.jpg",
			wfMessage("travel")->text() => "Image:Category_travel.jpg",
			wfMessage("education-and-communications")->text() => "Image:Category_education.jpg",
			wfMessage("home-and-garden")->text() => "Image:Category_home.jpg",
			wfMessage("work-world")->text() => "Image:Category_work.jpg",
			wfMessage("family-life")->text() => "Image:Category_family.jpg",
			wfMessage("personal-care-and-style")->text() => "Image:Category_personal.jpg",
			wfMessage("youth")->text() => "Image:Category_youth.jpg",
			wfMessage("finance-and-legal")->text() => "Image:Category_finance.jpg",
			wfMessage("finance-and-business")->text() => "Image:Category_finance.jpg",
			wfMessage("pets-and-animals")->text() => "Image:Category_pets.jpg",
			wfMessage("food-and-entertaining")->text() => "Image:Category_food.jpg",
			wfMessage("philosophy-and-religion")->text() => "Image:Category_philosophy.jpg",
		);
		return $catmap;
	}

	public static function getBreadcrumbCategories( $title = null ) {
		$tree = self::getCurrentParentCategoryTree( $title );
		if ( is_array( $tree ) ) {
			$tree = array_reverse( $tree );
			$top = str_replace( '-', ' ', self::getTopCategory( $title ) );
			// Get the first sub-tree that matches the top category as a list
			foreach ( $tree as $cat => $subtree ) {
				$list = self::cleanCurrentParentCategoryTree( [ $cat => $subtree ] );
				$trimmedList = array_slice( $list, -1 );
				if ( array_pop( $trimmedList ) === $top ) {
					return $list;
				}
			}
		}

		return null;
	}

	public static function getTopCategory($title = null) {
		global $wgContLang;
		if (!$title) {
			// an optimization because memcache is hit
			$parenttree = self::getCurrentParentCategoryTree();
		} else {
			$parenttree = $title->getParentCategoryTree();
		}
		$catNamespace = $wgContLang->getNSText(NS_CATEGORY) . ":";
		$parenttree_tier1 = $parenttree;

		$result = null;
		while ((!$result || $result == "WikiHow") && is_array($parenttree)) {
			$a = array_shift($parenttree);
			if (!$a) {
				$keys = array_keys($parenttree_tier1);
				$result = str_replace($catNamespace, "", @$keys[0]);
				break;
			}
			$last = $a;
			while (sizeof($a) > 0 && $a = array_shift($a) ) {
				$last = $a;
			}
			$keys = array_keys($last);
			$result = str_replace($catNamespace, "", $keys[0]);
		}
		return $result;
	}

	public static function getTopCategoryIncludingWikiHow($title = null) {
		global $wgContLang;
		if (!$title) {
			// an optimization because memcache is hit
			$parenttree = self::getCurrentParentCategoryTree();
		} else {
			$parenttree = $title->getParentCategoryTree();
		}
		$catNamespace = $wgContLang->getNSText(NS_CATEGORY) . ":";
		$parenttree_tier1 = $parenttree;

		$result = null;
		while (!$result && is_array($parenttree)) {
			$a = array_shift($parenttree);
			if (!$a) {
				$keys = array_keys($parenttree_tier1);
				$result = str_replace($catNamespace, "", @$keys[0]);
				break;
			}
			$last = $a;
			while (sizeof($a) > 0 && $a = array_shift($a) ) {
				$last = $a;
			}
			$keys = array_keys($last);
			$result = str_replace($catNamespace, "", $keys[0]);
		}
		return $result;
	}

	private static function displayCategoryArray($lvl, $catary, &$display, $toplevel) {
		$indent = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

		if (is_array($catary)) {
			foreach (array_keys($catary) as $cat) {
				if ($lvl == 0) { $toplevel = $cat; }

				$fmt = "";
				for ($i = 0; $i < $lvl; $i++) {
					$fmt .= $indent;
				}
				$display .= "<a name=\"".urlencode(strtoupper($cat))."\" id=\"".urlencode(strtoupper($cat))."\" ></a>\n";
				$display .= $fmt;
				if (is_array($catary[$cat])) {
					$display .= "<img id=\"img_".urlencode($cat)."\" src=\"/skins/WikiHow/topics-arrow-off.gif\" height=\"10\" width=\"10\" border=\"0\" onClick=\"toggleImg(this);Effect.toggle('toggle_".urlencode(strtoupper($cat))."', 'slide', {delay:0.0,duration:0.0}); return false;\" /> ";
				} else {
				$display .= "<img src=\"/skins/WikiHow/blank.gif\" height=\"10\" width=\"10\" border=\"0\"  /> ";
				}

				if ($lvl == 0) {
					$display .= "$cat <br />\n";
				}else {
					$display .= "<input type=checkbox name=\"".$toplevel.",".$cat."\" >  " . $cat . "<br />\n";
				}

				$display .= "<div id=\"toggle_".urlencode(strtoupper($cat)) ."\" style=\"display:none\">\n";
				$display .= "   <div>\n";
				if ($lvl > 0) {

				}
				self::displayCategoryArray($lvl + 1, $catary[$cat], $display, $toplevel);

				$display .= "   </div>\n</div>\n";
			}
		}
	}

	// Used in this class and sendContributorEmails.php script
	public static function flattenary(&$bucket, $lines) {
		foreach (array_keys($lines) as $line) {
			if (is_array($lines[$line])) {
				array_push($bucket, $line);
				self::flattenary($bucket, $lines[$line]);
			} else {
				array_push($bucket, $lines[$line]);
			}
		}
	}

	private static function json2Array() {
		global $wgRequest;
		$val = array();

		$wgary = $wgRequest->getValues();
		if (is_array($wgary)) {
			foreach (array_keys($wgary) as $wgarykeys) {
				$jsonstring = preg_replace('/_/m', ' ', stripslashes($wgarykeys));
				$val = json_decode($jsonstring, true);

				if ($val['json'] == "true") { return $val; }
			}
		}
		return $val;
	}

	public function execute($par) {
		global $wgOut, $wgRequest;
		$wgOut->setArticleBodyOnly(true);
		if ($wgRequest->getVal('cat')) {
			$category = $wgRequest->getVal('cat');
			$options = self::getCategoryDropDownTree();
			foreach ($options[$category] as $sub) {
				print self::getHTMLForCategoryOption($sub, '', true);
			}
		}

		if ($wgRequest->getVal('type') == "categorypopup") {
			$options2 = self::getCategoryTreeArray();
			print self::getHTMLForPopup($options2);
		}

		$jsonArr = self::json2Array();
		if ($jsonArr['type'] == "supSubmit") {
			$jsonArr['ctitle'] = preg_replace('/-whPERIOD-/m',".",$jsonArr['ctitle']);
			$jsonArr['ctitle'] = preg_replace('/-whDOUBLEQUOTE-/m',"\"",$jsonArr['ctitle']);
			print self::getHTMLsupSubmit($jsonArr);
		}
	}

	// Used in Video Adder and category listing API
	public static function getTopLevelCategoriesForDropDown() {
		$results = array();
		$options = self::getCategoryDropDownTree();
		foreach ($options as $key=>$value) {
			$results[] = $key;
		}
		return $results;
	}

	private static function modifiedParentCategoryTree($parents = array(), $children = array() ) {
		if ($parents) {
			foreach ($parents as $parent => $current) {
				if ( array_key_exists( $parent, $children ) ) {
					// Circular reference
					$stack[$parent] = array();
				} else {
					$nt = Title::newFromText($parent);
					if ( $nt ) {
						$stack[$parent] = $nt->getParentCategoryTree( $children + array($parent => 1) );
					}
				}
			}
			return $stack;
		} else {
			return array();
		}
	}

	// Used in Guided Editor
	public static function getCategoryOptionsForm($default, $cats = null) {
		global $wgUser, $wgRequest, $wgTitle;

        $maxCategories = 2;
		if (!$wgUser->isLoggedIn())
			return "";

		// get the top and bottom categories
		$valid_cats = array();
		if (is_array($cats)) {
			$valid_cats = array_flip($cats);
		}

		if ($wgRequest->getVal('oldid') != null && $default != "") {
			$fakeparent = array();
            $defaultCategories = explode( '|', $default);
            $tree = array();
            foreach ( $defaultCategories as $defaultCategory ) {
                if (!$defaultCategory) {
                    continue;
                }
                $fakeparent[Title::makeTitle(NS_CATEGORY, $defaultCategory)->getFullText()] = array();
                $tree = array_merge( $tree, self::modifiedParentCategoryTree($fakeparent) );
            }
		} else {
			//don't use caching for this
			$tree = $wgTitle->getParentCategoryTree();
		}
		if (!$tree) $tree = array();
		$toplevel = array();
		$bottomlevel = array();

		if ($wgRequest->getVal('topcategory0', null) != null) {
			// user has already submitted form, could be a preview, just set it to what they posted
			for ($i = 0; $i < $maxCategories; $i++) {
				if ($wgRequest->getVal('topcategory' . $i, null) != null) {
					$toplevel[] = $wgRequest->getVal('topcategory' . $i);
					$bottomlevel[] = $wgRequest->getVal('category' . $i);
				}
			}
		} else {
			// fresh new form from existing article
			foreach ($tree as $k=>$v) {
				$keys = array_keys($tree);
				$bottomleveltext = $k;
				$child = $v;
				$topleveltext = $k;
				while (is_array($child) && sizeof($child) > 0) {
					$keys = array_keys($child);
					$topleveltext = $keys[0];
					$child = $child[$topleveltext];
				}
				$tl_title = Title::newFromText($topleveltext);
				$bl_title = Title::newFromText($bottomleveltext);
				if (isset($valid_cats[$bl_title->getText()])) {
					if ($tl_title != null) {
						$toplevel[] = $tl_title->getText();
						$bottomlevel[] =  $bl_title->getText();
					} else {
						$toplevel[] = $bl_title->getText();
					}
				} else {
					#print_r($tree);
					#echo "oops <b>{$bl_title->getText()}</b><br/><br/>"; print_r($bl_title); print_r($valid_cats);
				}
			}
		}

		$html = "\n";
		$catlist = "";

		for ($i = 0; $i < $maxCategories; $i++) {
			if (isset($toplevel[$i]) && $toplevel[$i] != "") {
				//$html .= "<a href=\"/Category:".$bottomlevel[$i]."\">".$toplevel[$i].":".$bottomlevel[$i]."</a><br>\n";
				$html .= "<input type=hidden readonly size=40 name=\"topcategory".$i."\" value=\"".$toplevel[$i]."\" />";
				$html .= "<input type=hidden readonly size=60 name=\"category".$i."\" value=\"".$bottomlevel[$i]."\" />\n";
				if ($i == 0) {
					$catlist = $bottomlevel[$i];
				} else {
					$catlist .= ", ".$bottomlevel[$i];
				}
			} else {
				$html .= "<input type=hidden readonly size=40 name=\"topcategory".$i."\" value=\"\" />";
				$html .= "<input type=hidden readonly size=60 name=\"category".$i."\" value=\"\" />\n";
			}
		}

		if (!$catlist) {
			$html .= "<div id=\"catdiv\">" . wfMessage('ep_not_categorized')->text() . "</div>\n";
		} else {
			$html .= "<div id=\"catdiv\">$catlist</div>\n";
		}

		return $html;
	}

	private static function getHTMLForCategoryOption($sub, $default, $for_js = false) {
		$style = "";
		if (strpos($sub, "**") !== false && strpos($sub, "***") === false)
			$style = 'style="font-weight: bold;"';
		$sub = substr($sub, 2);
		$value = trim(str_replace("*", "", $sub));
		$display = str_replace("*", "&nbsp;&nbsp;&nbsp;&nbsp;", $sub);
		return "<option value=\"{$value}\" " . ($default == $value ? "selected" : "") . " $style>$display</option>\n";
	}

	// TODO: this method should be refactored into a different class and use Mustache templating
	private static function getHTMLForPopup($treearray) {
		$css = HtmlSnips::makeUrlTag('/extensions/wikihow/categories/categoriespopup.css');
		$style = "";
		$display = "";
		$indent = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

		$display = '
<html>
<head>

<title>Categories</title>

<style type="text/css" media="all">/*<![CDATA[*/ @import "/skins/WikiHow/newskin.css"; /*]]>*/</style>' .  $css . '
<script language="javascript" src="/extensions/wikihow/common/prototype1.8.2/prototype.js"></script>
<script language="javascript" src="/extensions/wikihow/common/prototype1.8.2/effects.js"></script>
<script language="javascript" src="/extensions/wikihow/common/prototype1.8.2/controls.js"></script>
<script language="javascript" src="/extensions/wikihow/categories/categoriespopup.js"></script>
<script type="text/javascript">/*<![CDATA[*/
var Category_list = [
			';

		$completeCatList = array();
		self::flattenary($completeCatList, $treearray);
		foreach ($completeCatList as $cat) {
			if ($cat != '') {
				$cat = preg_replace('/\'/', '\\\'', $cat);
				$display .= "'$cat',";
			}
		}
		$display .= "''];\n";

		$display .= '
/*]]>*/</script>
</head>
<body >

<div id="article">
<form name="catsearchform" action="#" onSubmit="return searchCategory();">
<input id="category_search" autocomplete="off" size="40" type="text" value="" onkeyup="return checkCategory();" />
<input type="button" value="'.wfMessage('Categorypopup_search')->text().'" onclick="return searchCategory();" />

<div class="autocomplete" id="cat_search" style="display:none"></div>

<script type="text/javascript">/*<![CDATA[*/
new Autocompleter.Local(\'category_search\', \'cat_search\', Category_list, {fullSearch: true});
/*]]>*/</script>
</form><br />
			';

		$display .= "<strong>".wfMessage('Categorypopup_selected')->text().": </strong><br />\n";
		$display .= "<div id=\"selectdiv\">";
		$display .= "<p>Loading...</p>";
		$display .= "</div><br />\n";

		$display .= '
<script type="text/javascript">showSelected();</script>

<strong>'.wfMessage('Categorypopup_browse')->text().':</strong>  <a href="#" onclick="return collapseAll();">['.wfMessage('Categorypopup_collapse')->text().']</a>
<a name="form_top" id="form_top" ></a>
<div id="categoriesPop" style="width:470;height:215px;overflow:auto">
<form name="category">
			';

		self::displayCategoryArray(0,$treearray,$display, "TOP");

		$display .= '
		<script type="text/javascript"> checkSelected(); </script>
			';

		$display .= '
	</div>
</div>
	<br />

	<input type="button" value="   '.wfMessage('Categorypopup_save')->text().'   " onclick="handleSAVE(this.form)" />
	<input type="button" value="'.wfMessage('Categorypopup_close')->text().'" onclick="handleCancel()" />
</form>

</body>
</html>
			';
		return $display . "\n";
	}

	/**
	 * processSupSubmit - process SpecialUncategorizedpages Submit to set category.  AJAX call.
	 */
	private static function getHTMLsupSubmit($jsonArr) {
		global $wgUser;

		$category = "";
		$textnew = "";

		if ($wgUser->getID() <= 0) {
			print "User not logged in";
			return false;
		}

		$ctitle = $jsonArr["ctitle"];
		if ($jsonArr["topcategory0"] != "") {
			$category0 = urldecode($jsonArr["category0"]);
			$category .= "[[Category:".$category0."]]\n";
			if ($jsonArr["topcategory1"] != "") {
				$category1 = urldecode($jsonArr["category1"]);
				$category .= "[[Category:".$category1."]]\n";
			}

			$title = Title::newFromURL(urldecode($ctitle));
			if ($title == null) {
				print "ERROR: title is null for $url";
				exit;
			}

			if ($title->getArticleID() > 0) {
				// we want the most recent version, don't want to overwrite changes
				$wikiPage = WikiPage::factory($title);
				$text = ContentHandler::getContentText( $wikiPage->getContent() );

				$pattern = '/== .*? ==/';
				if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {

					$textnew = substr($text,0,$matches[0][1]) . "\n";
					$textnew .= $category ;
					$textnew .= substr($text,$matches[0][1]) . "\n";

					$summary = "categorization";
					$minoredit = "";
					$watchthis = "";
					$bot = true;

					// update the article here
					$contentnew = ContentHandler::makeContent($textnew, $title);
					if ( $wikiPage->doEditContent( $contentnew, $summary, $minoredit, $watchthis ) ) {
						Hooks::run("CategoryHelperSuccess", array());
						print "Category Successfully Saved.\n";
						return true;
					} else {
						print "ERROR: Category could not be saved.\n";
					}
				} else {
					print "ERROR: Category section could not be located.\n";
				}
			} else {
				print "ERROR: Article could not be found. [$url]\n";
			}
		} else {
			print "No Category selected\n";
		}
		return false;
	}

    /*
     * recalculates the title category mask. you should only use this
     * if you are going to update the page_catinfo, otherwise you should just get it
     * from the page table and not recalculate
     */
	public static function getTitleCategoryMask( $title ) {
		global $wgCategoryNames;
		if ( !$title || !$title->inNamespace(NS_MAIN) ) {
			return 0;
		}

		$topcats = array_flip( $wgCategoryNames );
		$top = self::getTitleTopLevelCategories( $title );
		$val = 0;
		foreach ( $top as $c ) {
			$val = $val | $topcats[$c->getText()];
		}

		return $val;
	}

	public static function getTitleTopLevelCategories($title) {
		global $wgCategoryNames, $wgContLang;
		if ( !$title || !$title->inNamespace(NS_MAIN) ) {
			return array();
		}
		$tree = $title->getParentCategoryTree();
		$mine = array_unique( self::flattenArrayCategoryKeys($tree) );
		$topcats = $wgCategoryNames;
		$results = array();
		foreach ($mine as $m) {
			$y = Title::makeTitle(NS_CATEGORY, str_replace($wgContLang->getNsText(NS_CATEGORY) . ":", "", $m));
			if (in_array($y->getText(), $topcats)) {
				$results[] = $y;
			}
		}
		return $results;
	}

	// Reuben: moved this function from Misc.php into here. 12/16/2014
	// Aaron says it's buggy and we should stop using it too, but
	// it's still in use in a couple places
	public static function flattenArrayCategoryKeys($arg, &$results = array()) {
		if (is_array($arg)) {
			foreach ($arg as $a=>$p) {
				$results[] = $a;
				if (is_array($p)) {
				   self::flattenArrayCategoryKeys($p, $results);
				}
			}
		}
		return $results;
	}

	// same as flatten category tree but handles a $tree that has multiple arrays in it
	// whereas flattenCategorTree only returns the first array
    private static function flattenMultiCategoryTree($tree) {
        $results = array();

        if (is_array($tree)) {
            foreach ($tree as $key => $value) {
                $results[] = $key;
                if (is_array($value)) {
                    $x = self::flattenCategoryTree($value);
                    if (!is_array($x)) {
                        $x = array($x);
                    }
                    $results = array_merge($results, $x);
                }
            }
        } else {
            $results[] = $tree;
        }
        return $results;
    }

	// this function takes a parent catagory tree (the result of calling getCurrentParentCategoryTree()
	// and flattens it and removes the "Category" string from the beginning as well
	// so it can be easily used for comparisons
	public static function cleanCurrentParentCategoryTree($currentParentTree) {
		$tree = self::flattenMultiCategoryTree($currentParentTree);
		$tree = self::cleanUpCategoryTree($tree);
		return $tree;
	}

    public static function isTitleInCategory( $title, $category ) {
        $tree = self::getCurrentParentCategoryTree( $title );
        $cats = self::cleanCurrentParentCategoryTree( $tree );

        foreach ( $cats as $cat ) {
            if ( $cat === $category ) {
                return true;
            }
        }

        return false;
    }

	public static function onPageContentSaveComplete($wikiPage, $user, $content, $summary, $isMinor,
			$isWatch, $section, $flags, $revision, $status, $baseRevId) {
		if ($wikiPage) {
			self::recalcCategoryMask($wikiPage);
		}
	}

    /*
     * Recalculates the category mask and updates the page table
     * Delete the memcache key that stores the parent category breadcrumbs
     */
	private static function recalcCategoryMask(WikiPage $page) {
		global $wgMemc;

        $title = $page->getTitle();
        if ( !$title || !$title->inNamespace( NS_MAIN ) ) {
            return;
        }
        $pageId = $title->getArticleID();

        $key = wfMemcKey( 'parentcattree', $pageId );
        $wgMemc->delete( $key );

        $mask = self::getTitleCategoryMask( $title );
        $dbw = wfGetDB( DB_MASTER );
        $dbw->update(
            'page',
            array('page_catinfo' => $mask),
            array('page_id' => $pageId ),
            __METHOD__
        );
	}

	/*
	These two methods are not used, but could come in handy in the future (Alberto, 2018-08)

	/**
	 * Recalculate the indexation policy of modified categories in wikiHow:Categories.
	 *
	 * wikiHow:Categories contains a category tree which some tools rely upon. RobotPolicy
	 * flags categories that are not in the tree as "noindex,nofollow". Therefore, when the
	 * category tree changes, we recalculate the relevant policies so that removed categories
	 * become noindex, and vice-versa.
	 *
	private static function recalcCategoryPolicies(WikiPage $page, Revision $rev) {
		// Check if the page is wikiHow:Categories
		$title = $page->getTitle();
		$isTreePage = $title && $title->exists() && $title->equals(self::getCategoryTreeTitle());
		if (!$isTreePage) {
			return;
		}

		// Recalculate the policies of categories that were removed or added
		$categs = self::getCategsFromDiff($title, $rev->getParentId(), $rev->getId());
		set_time_limit( max(30, count($categs)) ); // 30 seconds or 1s per item
		foreach ($categs as $categ) {
			$page = WikiPage::factory(Title::newFromText($categ, NS_CATEGORY));
			RobotPolicy::recalcArticlePolicy($page);
		}
	}

	/**
	 * Given 2 revisions of wikiHow:Categories, return the lines that changed (i.e. the
	 * categories that were added or removed)
	 *
	private static function getCategsFromDiff(Title $title, int $oldRev, int $newRev): array {
		global $wgContLang;

		// Diff the 2 revisions
		$diffEng = new DifferenceEngine($title, $oldRev, $newRev);
		$diffEng->loadText();

		$oldTxt = str_replace("\r\n", "\n", $diffEng->mOldContent->serialize());
		$newTxt = str_replace("\r\n", "\n", $diffEng->mNewContent->serialize());
		$diff = new Diff(
			explode("\n", $wgContLang->segmentForDiff($oldTxt)),
			explode("\n", $wgContLang->segmentForDiff($newTxt))
		);

		// Extract categories from the diff
		$categs = [];
		foreach ($diff->edits as $edit) {
			if ($edit->type == 'copy') // No changes in this line
				continue;

			$lines = array_merge( 	// Combine added and removed lines
				$edit->orig ? $edit->orig : [],
				$edit->closing ? $edit->closing : []
			);
			foreach ($lines as $line) {
				$categ = trim(str_replace('*', '', $line));
				if ($categ) {
					$categ = self::removeQuotesFromCategoryName($categ);
					$categs[$categ] = true; // Deduplicate
				}
			}

		}
		return array_keys($categs);
	}

	*/

	/**
	 * A bit tricky because some languages use a localized version, but other use English:
	 *
	 * de.wikihow.com/wikiHow:Kategorien
	 * zh.wikihow.com/wikiHow:Categories
	 *
	 * @return Title  /wikiHow:Categories
	 */
	public static function getCategoryTreeTitle(): Title {
		// Try the natively named category structure project page first
		$title = Title::makeTitle(NS_PROJECT, wfMessage('categories')->text());
		if (!$title->exists()) {
			// If that didn't work, try the English-titled page wikiHow:Categories
			$title = Title::makeTitle(NS_PROJECT, 'Categories');
		}
		return $title;
	}

}

