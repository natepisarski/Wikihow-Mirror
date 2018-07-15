<?
/*
	this script takes articles from the wikiphoto_article_watermark table and converts the images
	in the article to have watermarks, then increments the watermark version on that entry.
	there are some variables that can be changed to remove watermarks or to act on a single article.

	for reference, this is the wikiphoto_article_watermark DB Table
    CREATE TABLE `wikiphoto_article_watermark` (
    `waw_article_id` int(8) unsigned NOT NULL,
	`waw_version` tinyint(3) unsigned default NULL,
    PRIMARY KEY  (`waw_article_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

$targetArticle = NULL;
$processLimit = 0;
$offset = 0;

$longOptions = array("target:", "limit:", "offset:");
$options = getopt("", $longOptions);

if ($options['limit']) {
	$processLimit = $options['limit'];
}

if ($options['target']) {
	$targetArticle = $options['target'];
}

if ($options['offset']) {
	$offset = $options['offset'];
}

if ($targetArticle == NULL && $processLimit == 0) {
	echo "please specify --target or --limit\n";
	exit();
}

require_once( "commandLine.inc" );
$fname = "findCMYK";

$version = 1;
$selectBy = "waw_version = $version";

$options = array('LIMIT' => $processLimit);
if ($offset > 0) {
	$options['OFFSET'] = $offset;
}

$dbr = wfGetDB(DB_SLAVE);
$res = $dbr->select('wikiphoto_article_watermark',
					'waw_article_id',
					array($selectBy),
					$fname,
					$options);

$ids = array();
foreach ($res as $row) {
	$ids[] = $row->waw_article_id;
}

// override point for targeting a specific article
if ($targetArticle != null) {
	$targetId = $targetArticle;
	if (!is_numeric($targetArticle)) {
		$t = Title::newFromText($targetArticle);
		$targetId = $t->getArticleID();
	}
	$ids = array($targetId);
}

$articleCount = count($ids);
if ($articleCount < 1) {
	echo "no articles to process...exiting\n";
	exit();
}

$processedCount = 0;

foreach ($ids as $articleId) {
	$articleTitle = Title::newFromId($articleId);
	if (!$articleTitle) {
		continue;
	}

	$articleName = $articleTitle->getPartialURL();
	echo("processing article: $articleName with id: $articleId\n");
	$processedCount = $processedCount + 1;

	$articleName = $articleTitle->getPartialURL();
	$imageLinks = array();
	$res = $dbr->select(array('imagelinks'), 'il_to', array('il_from' => $articleId));

	$found = "";
	foreach ($res as $row) {
		$title = Title::newFromText($row->il_to, NS_IMAGE);
		$imageFile = wfFindFile($title, false);
		if ($imageFile && $imageFile->fileExists) {
			// make sure it's a wikiphoto image
			if ( $imageFile->user_text != "Wikiphoto" ) {
				continue;
			}

			$fullPath = $imageFile->getFullPath();
			//decho("checking", $fullPath, false);

			$cmyk = WatermarkSupport::isCMYK($fullPath);
			if ($cmyk) {
				$found = $fullPath;
				break;
			}
		}
	}
	if ($found != "") {
		echo ("article with CMYK image: $articleId - $articleName image is: $found\n");
	}
}

?>
