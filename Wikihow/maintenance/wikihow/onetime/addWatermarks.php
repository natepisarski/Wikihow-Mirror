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

$removeWatermarks = false;
$targetArticle = NULL;
$processLimit = 0;

$longOptions = array("target:", "limit:", "removewatermark");
$options = getopt("", $longOptions);

if (array_key_exists('removewatermark', $options)) {
	$removeWatermarks = true;
}

if ($options['limit']) {
	$processLimit = intval($options['limit']);
}

if ($options['target']) {
	$targetArticle = $options['target'];
}

if ($targetArticle == NULL && $processLimit == 0) {
	echo "please specify --target or --limit\n";
	exit();
}

function updateVersion($version, $articleId, $fname) {
	$dbw = wfGetDB(DB_MASTER);
	$dbw->update("wikiphoto_article_watermark", array("waw_version=$version"), array("waw_article_id=$articleId"), $fname);
}

function complete($time, $processedCount, $message = NULL) {
	if ($message) {
		echo "$message \n";
	}
	$time += microtime(true);
	echo "$processedCount articles processed in time: ",sprintf('%f', $time),PHP_EOL;
	exit();
}

$dryRun = false;
require_once( "commandLine.inc" );
$fname = "addWatermarks";
$time = -microtime(true);

/* GLOBAL VARIABLES THAT AFFECT HOW THE SCRIPT WORKS
	dryRun - will not actually do the transform or the db update..but does everything else
	removeWatermarks - will transform the image, but without adding a watermark..probably only useful for testing but who knows.
	version - what version of the watermark we will be using..affects our db fetches and updates..
	 - you still have to manually change the watermark svg image file if you update the wgWaterarkVersion
	processLimit - how many article ids to process
*/
$version = $wgWatermarkVersion;

$verb = "adding";
if ($removeWatermarks == true) {
	$verb = "removing";
}
echo "$verb watermarks from articleids...\n";
$dbr = wfGetDB(DB_SLAVE);
$selectBy = "waw_version < $version";

// if removewatermarks is true, we want to select every watermarked image
if ($removeWatermarks == true) {
	$version = 0;
	echo "converting to unwatermarked images...\n";
	$selectBy = "waw_version > $version";
}

$res = $dbr->select('wikiphoto_article_watermark',
					'waw_article_id',
					array($selectBy),
					__METHOD__,
					array ('LIMIT' => $processLimit) );

$ids = array();
foreach ($res as $row) {
	$ids[] = $row->waw_article_id;
}

// special override point for targeting a specific article
if ($targetArticle != null) {
	echo "acting on specific article: $targetArticle\n";
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
		echo "article $articleId does not exist..skipping.\n";
		updateVersion($version, $articleId, $fname);
		continue;
	}

	$processedCount = $processedCount + 1;

	$articleName = $articleTitle->getPartialURL();
	decho("processing articleName", $articleName, false);
	decho("processing article", $articleId, false);
	$imageLinks = array();
	$res = $dbr->select(array('imagelinks'), 'il_to', array('il_from' => $articleId));

	foreach ($res as $row) {
		$title = Title::newFromText($row->il_to, NS_IMAGE);
		$imageFile = wfFindFile($title, false);
		//print_r($file);
		if ($imageFile && $imageFile->fileExists) {
			// make sure it's a wikiphoto image
			if ( $imageFile->user_text != "Wikiphoto" ) {
				continue;
			}

			$thumbDir = $imageFile->getThumbPath();

			// make sure the directory exists
			if (!is_dir( $thumbDir )) {
				continue;
			}

			$thumbs = array_diff(scandir($thumbDir), array('..', '.'));
			echo ("will convert ". strval(count($thumbs)) . " images in thumb directory: " . $thumbDir ."\n");
			$owner = posix_getpwuid(fileowner($thumbDir));
			if ($owner['name'] != "apache") {
				echo ("directory owner is " . $owner['name'] . "\n");
			}

			foreach($thumbs as $thumb) {
				// if the file name has nowatermark in it, do not process
				if (strpos($thumb, "nowatermark") != false) {
					continue;
				}

				$thumbPath = $thumbDir.'/'.$thumb;
				$thumbUrl = $imageFile->getThumbUrl().'/'.$thumb;

				// make sure we have permission to rewrite to this file..
				if (!is_writable($thumbPath)) {
					echo "Error: Do not have permission to write: $thumbPath ... Aborting \n";
					complete($time, $processedCount);
				} 
			
				// make sure the file is the right format
				$imageSize = getimagesize($thumbPath);
				if ($imageSize["mime"] != $imageFile->getMimeType()) {
					continue;
				}

				$params = array();
				$params["width"] = $imageSize[0];
				if (strpos($thumb, "crop") != false) {
					$params['crop'] = 1;
					if (strpos($thumb, "--") == false) {
						$params["height"] = $imageSize[1];
					}
				}

				$params[WatermarkSupport::FORCE_TRANSFORM] = true;
				$params[WatermarkSupport::ADD_WATERMARK] = true;

				if ($removeWatermarks == true) {
					$params[WatermarkSupport::ADD_WATERMARK] = false;
				}

				if ($dryRun == true) {
					continue;
				}

				$result = $imageFile->getHandler()->doTransform($imageFile, $thumbPath, $thumbUrl, $params, $flags);
				if ( get_class($result) == 	MediaTransformError) {
					echo "there was an error processing this file \n";
					echo $result->toText();
					complete($time, $processedCount);
				}
			}
		}
	}

	if ($dryRun == true) {
		continue;
	}

	updateVersion($version, $articleId, $fname);
}

complete($time, $processedCount);

?>
