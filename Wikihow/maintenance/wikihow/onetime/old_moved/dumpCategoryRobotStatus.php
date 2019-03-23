<?php
/**
 * Dump robot policy for all categories.
 */

$oldCwd = getcwd();
include_once('commandLine.inc');
chdir($oldCwd);

$recursive_key_search = function ($needle, $haystack) use (&$recursive_key_search) {
	foreach ($haystack as $key=>$value) {
		$current_key = $key;
		if ($needle === $key || is_array($value) &&
				$recursive_key_search($needle, $value) !== false) {
			return true;
		}
	}
	return false;
};

# Alternate approach (seems to be slower):
#$array_key_exists_recursive = function ($needle, $haystack)
#							  use (&$array_key_exists_recursive) {
#	if (!is_array($haystack)) {
#		return $needle === $haystack;
#	} elseif (isset($haystack[$needle]) || array_key_exists($needle, $haystack)) {
#		return true;
#	}
#
#	foreach ($haystack as $key=>$value) {
#		if ($array_key_exists_recursive($needle, $value)) {
#			return true;
#		}
#	}
#
#	return false;
#};

$mainCatTreeArray = CategoryHelper::getCategoryTreeArray();

$pattern = "@^<big>'''(.*)'''</big>$@";
$matches = array();
foreach ($mainCatTreeArray as $key=>$value) {
	if (preg_match($pattern, $key, $matches)) {
		$mainCatTreeArray[$matches[1]] = $value;
		unset($mainCatTreeArray[$key]);
	}
}
				
unset($mainCatTreeArray['WikiHow']);

var_dump($mainCatTreeArray);

$dbr = wfGetDB(DB_REPLICA);

$res = $dbr->select(
	'category',
	array('cat_title'),
	'',
	__METHOD__
);

$robotPolicies = array();

foreach ($res as $row) {
	$cattext = $row->cat_title;
	$title = Title::makeTitleSafe(NS_CATEGORY, $row->cat_title);
	if ($title && $title->exists()) {
		$cattext = $title->getText();
	}

	$result = $recursive_key_search($cattext, $mainCatTreeArray);

	if ($result) {
		$catDBKey = $title->getDBKey();

		$res = $dbr->select(
			'category',
			array('*'),
			array('cat_title' => $catDBKey),
			__METHOD__
		);

		$result = $res !== false;
	}

	$robotPolicies[$cattext] = $result;
}

foreach ($robotPolicies as $category => $indexed) {
	print ($indexed ? 't' : 'f' ) . " $category\n";
}

