<?php

/*
		CREATE TABLE image_transfer_job(
			itj_from_lang varchar(2) NOT NULL,
			itj_from_aid int NOT NULL,
			itj_to_lang varchar(2) NOT NULL,
			itj_to_aid int NOT NULL,
			itj_creator varchar(255) NOT NULL,
			itj_time_started varchar(12) NOT NULL, -- Time when it was added to queue
			itj_time_finished varchar(12) NULL, -- Time when the article is wikiphotoed
			itj_error text,
			itj_warnings text
			primary key(itj_from_lang, itj_from_aid, itj_to_lang, itj_to_aid)
		)
*/
/*
		CREATE TABLE image_transfer_invalids(
			iti_from_url varchar(255) NOT NULL,
			iti_to_lang varchar(2) NOT NULL,
			iti_creator varchar(255) NOT NULL,
			iti_time_started varchar(12) NOT NULL,
			iti_time_finished varchar(12) NULL,
			primary key(iti_from_url, iti_to_lang)
		);
/**
 * Class for tracking image transfers for taking images from English to international articles
 */
class ImageTransfer {
	// Language and article id we are getting the steps images from
	public $fromLang;
	public $fromAID;
	// Language and article id where we are putting the images
	public $toLang;
	public $toAID;
	// User who began the image transfer request
	public $creator;
	// Time when the image transfer was started
	public $timeStarted;
	// Time when the image transfer is finished
	public $timeFinished;
	// Error if applicable
	public $error;
	// Warnings if applicable
	public $warnings;

	// Database that has the image_transfer_job table
	const DB_NAME=WH_DATABASE_NAME;
	// Table used for storing the image transfer info
	const TABLE_NAME="image_transfer_job";

	public function __construct() {
		$this->warnings = array();
	}

	/**
	 * Get a regex for matching images including foreign image tags
	 * @param altImageTags Language specific image tags
	 */
	public static function getImageRegex($altImageTags = array(), $matchSpaces=false) {
		if ($matchSpaces) {
			$regex = "@\s*";
		}
		else {
			$regex = "@";
		}
		$regex .= "(\[\[ *Image:[^\]]* *\]\]|\{\{ *largeimage\|[^\}]* *\}\}|\{\{ *whvid\|[^\}]* *\}\}";

		foreach ($altImageTags as $altImageTag) {
			$regex .= "|\[\[ *" . preg_quote($altImageTag) . ":[^\]]* *\]\]";
		}
		if ($matchSpaces) {
			$regex .= ")\s*@im";
		}
		else {
			$regex .= ")@im";
		}
		return($regex);
	}
	/**
	 * Insert the record into the database based on data in class
	 */
	public function insert() {
		global $wgDBname;
		if ($wgDBname != self::DB_NAME) {
			throw new Exception("ImageTransfer::insert must be run from the language where its database is stored: $wgDBname!= ". self::DB_NAME);
		}
		$dbw = wfGetDB(DB_MASTER);

		$sql = 'insert into ' . self::TABLE_NAME . '(itj_from_lang, itj_from_aid, itj_to_lang, itj_to_aid, itj_creator, itj_time_started) values(' . $dbw->addQuotes($this->fromLang) . ',' . $dbw->addQuotes($this->fromAID) . ',' . $dbw->addQuotes($this->toLang) . ',' . $dbw->addQuotes($this->toAID) . ',' . $dbw->addQuotes($this->creator) . ',' . $dbw->addQuotes($this->timeStarted) . ') on duplicate key update itj_creator=' . $dbw->addQuotes($this->creator) . ',  itj_time_started=' . $dbw->addquotes($this->timeStarted) . ', itj_error=NULL, itj_warnings=NULL,  itj_time_finished=NULL';
		$dbw->query($sql, __METHOD__);

		return(true);
	}

	/**
	 * Report error with processing image translation
	 * @param error The error message
	 * @param dryRun If a dryRun, just show the query, instead of running it
	 */
	public function reportError($error, $dryRun=true) {
		print "ERROR:" . $error . "\n";
		$dbw = wfGetDB(DB_MASTER);
		$this->error = $error;
		$sql = "update " . self::DB_NAME . "." . self::TABLE_NAME . " SET itj_error=" . $dbw->addQuotes($this->error) . " WHERE itj_from_lang=" . $dbw->addQuotes($this->fromLang) . " AND itj_from_aid=" . $dbw->addQuotes($this->fromAID) . " AND itj_to_lang=" . $dbw->addQuotes($this->toLang) . " AND itj_to_aid=" . $dbw->addQuotes($this->toAID);
		if (!$dryRun) {
			$dbw->query($sql, __METHOD__);
		}
		else {
			print($sql . "\n");
		}
	}

	/**
	 * Report successfully applying image translation
	 */
	public function reportSuccess($dryRun=true) {
		$dbw = wfGetDB(DB_MASTER);
		$this->timeFinished = wfTimestampNow();
		print "\n\nImage transfer run successfully at " . $this->timeFinished;
		if (sizeof($this->warnings) != '') {
			$this->error = 'Warning(s): ' . implode(',', $this->warnings) . '. Images were still transferred where possible.';
		}
		$sql = "update " . self::DB_NAME . "." . self::TABLE_NAME . " SET itj_time_finished=" . $dbw->addQuotes($this->timeFinished) . ",itj_warnings=" . $dbw->addQuotes($warnings) . " WHERE itj_from_lang=" . $dbw->addQuotes($this->fromLang) . " AND itj_from_aid=" . $dbw->addQuotes($this->fromAID) . " AND itj_to_lang=" . $dbw->addQuotes($this->toLang) . " AND itj_to_aid=" . $dbw->addQuotes($this->toAID);
		if (!$dryRun) {
			$dbw->query($sql, __METHOD__);
		}
		else {
			print($sql . "\n");
		}
	}

	/**
	 * Report a warning
	 */
	public function reportWarning($warning) {
		$this->warnings[] = $warning;
	}

	/**
	 * Create the class from a database row
	 */
	public static function newFromRow(&$row) {
		$it = new ImageTransfer();

		$it->fromLang = $row->itj_from_lang;
		$it->fromAID = $row->itj_from_aid;
		$it->toLang = $row->itj_to_lang;
		$it->toAID = $row->itj_to_aid;
		$it->creator = $row->itj_creator;
		$it->timeStarted = $row->itj_time_started;
		$it->timeFinished = $row->itj_time_finished;
		$it->error = $row->itj_error;

		return($it);
	}

	/**
	 * Get list of articles to update for a language
	 */
	public static function getUpdatesForLang($lang) {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select * from " . self::DB_NAME . "." . self::TABLE_NAME . " where itj_to_lang=" . $dbr->addQuotes($lang) . " AND itj_error IS NULL AND itj_time_finished is NULL";

		$res = $dbr->query($sql, __METHOD__);
		$its = array();
		foreach ($res as $row) {
			$its[] = self::newFromRow($row);
		}
		return($its);
	}

	/**
	 * Transfer the images adding them to the article in $this->toLang with $this->toAID
	 * @param dryRun Is this a dry run? If so, we won't actually save the outputed article
	 */
	public function addImages($dryRun = true) {
		global $wgLanguageCode;

		$from=Alfredo::fetchPage($this->fromLang, $this->fromAID);
		$to=Alfredo::fetchPage($this->toLang, $this->toAID);

		if (!isset($this->fromLang) || !isset($this->fromAID) || !isset($this->toLang) || !isset($this->toAID)) {
			$this->reportError("Articles not set", $dryRun);
			return(false);
		}
		if ($this->toAID == 0) {
			$this->reportError("No translation link found to article in " . $this->toLang, $dryRun);
			return(false);
		}
		if (!$from) {
			$this->reportError("Unable to fetch linked-from article",  $dryRun);
			return(false);
		}
		if (!isset($from['steps']) || $from['steps']=='') {
			$this->reportError("Unable to parse out from article", $dryRun);
			return(false);
		}
		if (!$to) {
			$this->reportError("Unable to fetch linked-to article", $dryRun);
			return(false);
		}
		if (!isset($to['steps']) || $to['steps']=='') {
			$this->reportError("Unable to parse out steps from article", $dryRun);
			return(false);
		}
		if (self::haveSameImages($from['steps'], $to['steps'], array_merge($from['altImageTags'], $to['altImageTags'])) && self::haveSameImages($from['intro'], $to['intro']) ) {
			$this->reportError("Translated from and translated to articles have same images", $dryRun);
			return(false);
		}

		$token = Misc::genRandomString();

		$templates = self::getImageTemplatesFromSteps($from['steps'], $token);
		if (!$templates) {
			$this->reportError("Unable to generate template from article, no steps images or steps section not found", $dryRun);
			return(false);
		}
		$badSubsteps = 0;
		foreach ($templates as $template) {
			if ($template == '') {
				$badSubsteps++;
			}
		}
		if ($badSubsteps > 0) {
			$this->reportWarning("There are $badSubsteps steps or substeps, where image templates are unparsable");
		}

		$introTemplate = self::getIntroTemplate($from['intro'], $token);
		if ($introTemplate == '') {
			$this->reportWarning("Unable to parse intro template");
		}
		$newToSteps = self::replaceStepsImages($to['steps'], $templates, $token, $to['altImageTags']);
		if (!$newToSteps) {
			$this->reportError("Steps don't match", $dryRun);
			return(false);
		}
		if (preg_match("@<\s*center\s*>|<\s*font\s*>@mi", $from['intro'])) {
			$this->reportWarning("Font and center tag are unsupported in intro");
		}
		if (preg_match("@<\s*center\s*>|<\s*font\s*>@mi", $from['steps'])) {
			$this->reportWarning("Font and center tag are unsupported in steps");
		}


		$newIntro = self::replaceIntroImages($to['intro'], $introTemplate, $token, $to['altImageTags']);

		$t = Title::newFromId($this->toAID);
		if (!$t || !$t->exists()) {
			print("Title not found for ". $wgLanguageCode . $this->toLang . ":" . $this->toAID);
			$this->reportError("Title not found", $dryRun);
			return false;
		}
		$r = Revision::newFromTitle($t);
		$text = ContentHandler::getContentText( $r->getContent() );
		$initialText = $text;
		if (preg_match("@" . preg_quote($to['steps'],"@") .  "@", $text)) {
			$text = preg_replace_callback("@\s*" . preg_quote($to['steps'],  '@') . "\s*@",function($matches) use($newToSteps)
			{
				return("\n" . $newToSteps . "\n");
			}, $text);

			// Ensure we have a real intro before doing replacement
			if (strlen($to['intro']) > 10) {
				$text = preg_replace_callback("@\s*" . preg_quote($to['intro'],'@') . "\s*@",function($matches) use($newIntro) {
					return($newIntro . "\n");
				}, $text);
			}
			if ($text == $initialText) {
				$this->reportError("Alfredo doesn't change article", $dryRun);
				return false;
			}
			if ($text) {
				print("Article from https://" . $wgLanguageCode . ".wikihow.com/" . $t->getPartialURL() . " was edited to:\n\n" . $text);

				$wikiPage = WikiPage::factory($t);
				$content = ContentHandler::makeContent($text, $t);
				if (!$dryRun) {
					$wikiPage->doEditContent($content, wfMessage('alfredo-editsummary'));
					print("\n\nMade edit");
				}
				$this->reportSuccess($dryRun);

				return(true);
			}
			else {
				$this->reportError("Empty article", $dryRun);
				return(false);
			}
		}
		$this->reportError("Couldn't find steps section in article, possibly steps section is too big to process", $dryRun);
		return(false);
	}

	/**
	 * Get the intro template
	 *
	 * @param intro The introduction text
	 * @param token The token for the template
	 * @return Get a template for the intro
	 */
	private static function getIntroTemplate($intro, $token) {
		$emptyIntro = preg_replace(self::getImageRegex(),'',$intro,-1,$count);

		//We ignore wiki-templates besides {{largeimage}} when creating our template pattern
		$numMatches = preg_match_all('@(\s*\{\{[^}]*\}\}\s*|\s*\[\[Category:[^\]]+\]\])@im',$emptyIntro,$matches);
		if ($numMatches > 0) {
			for ($n=1;$n <= $numMatches;$n++) {
				$intro = str_replace($matches[$n], '', $intro);
				$emptyIntro = str_replace($matches[$n], '', $emptyIntro);
			}
		}

		$emptyIntro = preg_replace('@<\s*br[\/]\s*>|^#\*?@im','',$emptyIntro);

		$template = str_replace($emptyIntro, $token, $intro);

		// Put back replaced text inside images, because it is likely the image name
		// Also, remove any caption from image
		$template = preg_replace_callback(self::getImageRegex(),function($matches) use($token,$emptyStep) {
				// Remove caption only from image tags, but not largeimage template
				if (preg_match('@\[\[\s*Image\s*@', $matches[0])) {
					$matches[0] = Wikitext::removeImageCaption($matches[0]);
				}
				return(str_replace($token, $emptyStep, $matches[0]));
			}, $template);

		if (!preg_match('@' . preg_quote($token) . '@', $template) || (strlen($template) < 6)) {
			return('');
		}
		else {
			return($template);
		}
	}

	/**
	 * Get templates of how images fit in with different steps
	 * @param stepsText Text of the steps
	 * @param token Token for each image we replace
	 * @return Array of templates for each step or false if no images found. If we can't parse out a template for a step, we return a blank string for that template
	 */
	private static function getImageTemplatesFromSteps($stepsText, $token) {

		// Ignore interwiki links in steps text
		$stepsText = preg_replace("@\[\[ *[[:alpha:]][[:alpha:]] *:[^\]]+\]\]@",'',$stepsText);

		$stepTemplates = array();
		$numImages = 0;
		$stepNum = 1;
		$sections = preg_split("@[\r\n]\s*===@", $stepsText);
		$emptyStep = "";

		foreach ($sections as $section) {

			$steps = preg_split("@[\r\n]+#@", $section);
			if (count($steps) > 1) {
				for ($i = 1; $i < count($steps); $i++) {
					$haveImage = false;
					//Ignore sub-sections for calculating steps info
					$steps[$i] = preg_replace('@[\r\n]===[^=]+===@','',$steps[$i]);
					// Add in back in '#' and remove newline characters
					$steps[$i] = "#" . preg_replace('@[\r\n]@','',$steps[$i]);
					//Extract step without images,  associated formatting, and count images
					$emptyStep = preg_replace(self::getImageRegex(),'',$steps[$i],-1,$count);
					$numImages += $count;
					$emptyStep = preg_replace('@<\s*br[\/]?\s*>|^#\*?@im','',$emptyStep);

					//Remove leading and trailing whitespace
					$emptyStep = preg_replace('@^\s+@','',$emptyStep);
					$emptyStep = preg_replace('@\s+$@','',$emptyStep);
					if ($emptyStep != '') {
						//Change step to token
						$template = str_replace($emptyStep,$token, $steps[$i]);

						// Put back replaced text inside images, because it is likely the image name
						$template = preg_replace_callback(self::getImageRegex(),function($matches) use($token,$emptyStep) {

							// Remove thumbnail text, and mark if we have found an image
							if (preg_match('@\[\[\s*Image\s*@', $matches[0])) {
								$haveImage = true;
								$matches[0] = Wikitext::removeImageCaption($matches[0]);
							}

							return(str_replace($token, $emptyStep, $matches[0]));
						}, $template);

						if ($template == $steps[$i]) {
							if ($haveImage) {
								$stepTemplates[$stepNum] = '';
							}
							else {
								$stepTemplates[$stepNum] = $token;
							}
						}
						else {
							$stepTemplates[$stepNum] = $template;
						}
					}
					else {
						$stepTemplates[$stepNum] = $token;
					}
					$stepNum++;
				}
			}
		}
		if ($numImages == 0) {
			return(false);
		}
		//Check for finished step, which was created from intro image
		if (preg_match("@^Finished\.@", $emptyStep)) {
			$stepTemplates[$stepNum - 1] = str_replace($token, self::getFinishedToken($token), $stepTemplates[$stepNum - 1]);
		}
		return($stepTemplates);
	}

	/**
	 * Make a special token for the finished step based off our token
	 */
	private static function getFinishedToken($token) {
		return(substr($token,0,5) . "finished" . substr($token,5));
	}

	/**
	 * Replace the image and formatting in the intro
	 * @param introText The text in the intro
	 * @param introTemplate Template of where to put in the text
	 * @param token Token to use in template
	 * @param altImags Array of alternative tag names for images in the given language
	 * @return Intro with the template applied
	 */
	private static function replaceIntroImages($introText, $introTemplate, $token, $altImageTags) {
		$introText = preg_replace(self::getImageRegex($altImageTags, true), '', $introText);
		$introText = preg_replace('@<\s*br[\/]?\s*>@im','', $introText);
		//Remove extra leading and trailing spaces
		$introText = preg_replace('@== ([^\s]+) ==\s+@',"== \\1 ==\n", $introText);
		$introText = preg_replace('@[\s]+$@','\n',$introText);
		$introText = chop($introText);
		if ($introTemplate == '') {
			return($introText);
		}
		else {
			$intro = str_replace($token, $introText, $introTemplate);
			return($intro );
		}
	}

	/**
	 * Replace the images in steps according to a defined template
	 * @param stepsText Text of old steps from where translated steps are extracted
	 * @param stepTemplates Templates of how images get added to steps
	 * @param token Token used to replace images from each step
	 * @param altImageTags Alternative image tags for regex
	 * @return New steps images if successful, and false otherwise
	 */
	private static function replaceStepsImages($stepsText, $stepTemplates, $token, $altImageTags) {
		$steps = preg_split('@[\r\n]+#@m', $stepsText);
		//Special token for finished last step, which came from intro
		$finishedToken = self::getFinishedToken($token);
		$addFinishedStep = false;
		$origStepsOnly = self::getStepsOnly($steps, '*');
		$templStepsOnly = self::getStepsOnly($stepTemplates, '#*');
		if ((sizeof($origStepsOnly) - 1) != sizeof($templStepsOnly)) {
			if (sizeof($origStepsOnly)  == sizeof($templStepsOnly)
			  && preg_match('@' . preg_quote($finishedToken, '@') . '@', end($stepTemplates))
				) {
					$addFinishedStep = true;
				}
				else {
					return false;
				}
		}

		$sections = preg_split("@[\r\n]\s*===@", $stepsText);
		$tmplIdx = 0;

		$txt = "";

		$sectionNum = 0;
		foreach ($sections as $section) {
			$steps = preg_split('@[\r\n]+#@m', $section);
			//Clean up newline characters, add back === and add headings
			//Remove images from before steps
			$steps[0] = preg_replace(self::getImageRegex($altImageTags, false), '', $steps[0]);
			$steps[0] = preg_replace('@^\s+@',"", $steps[0]);
			$steps[0] = chop($steps[0]);

			if ($sectionNum != 0) {
				$steps[0] = preg_replace("@(===+)\s*@","$1\n", $steps[0]);
				$txt .= "\n===" . $steps[0] . "\n";
			}
			else {
				$steps[0] = preg_replace("@^\s+@",'', $steps[0]);
				$txt .= "\n" . $steps[0] . "\n";
			}

			if (sizeof($steps) > 1) {
				for ($i=1; $i < sizeof($steps); $i++) {
					$steps[$i] = "#" . chop($steps[$i]);

					if (substr($steps[$i], 0, 2) !== '#*') { // Skip substeps: '#*...'
						//If we have a template to image this step, and it is going to add additional images, apply it
						if ($templStepsOnly[$tmplIdx] != '' && !self::haveSameImages($templStepsOnly[$tmplIdx], $steps[$i])) {
							$step = preg_replace(self::getImageRegex($altImageTags), '', $steps[$i]);
							$step = preg_replace('@<\s*br[\/]?\s*>|^#\*?@im','',$step);
							$step = preg_replace('@[\r\n]*@im','',$step);
							$step = preg_replace("@(#[*]?)\s*(.+)\s*$@","\\1 \\2",$step);
							$steps[$i] = str_replace($token, $step, $templStepsOnly[$tmplIdx]);
							$steps[$i] = str_replace($finishedToken, $step, $steps[$i]);
						}
						$tmplIdx++;
					}

					$txt .= "\n" . $steps[$i];
				}
			}
			$sectionNum++;
		}
		if ($addFinishedStep) {
			$txt .=  "\n" . str_replace($finishedToken, wfMessage('finished'), end($stepTemplates));
		}

		return($txt);
	}

	/**
	 * Extract the steps from an array containing steps and substeps
	 */
	private static function getStepsOnly(array $items, string $substepPrefix) : array {
		$prefixLength = strlen($substepPrefix);
		$steps = [];
		foreach ($items as $item) {
			if (substr($item, 0, $prefixLength) !== $substepPrefix) {
				$steps[] = $item;
			}
		}
		return $steps;
	}

	/**
	 * Check if wikitext has images in it
	 */
	public static function hasImages($txt, $altImageTags=array()) {
		$matches = preg_match_all(self::getImageRegex($altImageTags), $txt, $dummy);
		return($matches > 0);
	}

	/**
	 * Check if two bits of wikitext have the same images
	 * @param a First wiki-text to test
	 * @param b Second wiki-text to test
	 * @param altImageTags Array of alternative image regex to use such as imagen,...
	 * @return True if the images are the same, and false otherwise
	 */
	public static function haveSameImages($a, $b, $altImageTags = array()) {
		preg_match_all(self::getImageRegex($altImageTags), $a, $matches);
		preg_match_all(self::getImageRegex($altImageTags), $b, $matches2);
		if (sizeof($matches) != sizeof($matches2)) {
			return(false);
		}
		for ($n=0; $n < sizeof($matches); $n++) {
			if (is_array($matches[$n])) {
				if (sizeof($matches[$n]) != sizeof($matches2[$n])) {
					return(false);
				}
				for ($m=0; $m < sizeof($matches[$n]); $m++) {
				 	$matches[$n][$m] = Wikitext::removeImageCaption($matches[$n][$m]);
					$matches2[$n][$m] = Wikitext::removeImageCaption($matches2[$n][$m]);

					if ($matches[$n][$m] != $matches2[$n][$m]) {
						return(false);
					}
				}
			}
			else {
			 	$matches[$n][$m] = Wikitext::removeImageCaption($matches[$n][$m]);
				$matches2[$n][$m] = Wikitext::removeImageCaption($matches2[$n][$m]);

				if ($matches[$n] != $matches2[$n]) {
					return(false);
				}
			}
		}

		return(true);
	}

	/**
	 * Add bad URLs to database, so we can report them as problematic in the end
	 */
	static public function addBadURL($url, $lang, $error) {
		global $wgUser;

		$dbw = wfGetDB(DB_MASTER);
		$sql = 'insert into ' . self::DB_NAME . '.image_transfer_invalids(iti_from_url, iti_to_lang, iti_creator, iti_time_started) values(' . $dbw->addQuotes($url) . ',' . $dbw->addQuotes($lang) . ','  . $dbw->addQuotes($wgUser->getName()) . ','  . $dbw->addQuotes(wfTimestampNow()) . ') on duplicate key update iti_time_finished=NULL' ;
		$dbw->query($sql, __METHOD__);
	}

	/**
	  * Get a list of bad URLs entered for a given language
		* If not a dry run, we will update this to only email once
		*/
	public static function getErrorURLsByCreator($language, $dryRun) {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = 'select iti_from_url, iti_creator from ' . self::DB_NAME . '.image_transfer_invalids where iti_to_lang=' . $dbr->addQuotes($language) . ' AND iti_time_finished is null';

		$urls = array();
		$ret = array();
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$urls[] = $row->iti_from_url;
			$ret[$row->iti_creator][] = $row->iti_from_url;
		}
		if (!empty($urls)) {
			$dbw = wfGetDB(DB_MASTER);
			$sql = 'UPDATE ' . self::DB_NAME . '.image_transfer_invalids
					SET iti_time_finished=' . $dbw->addQuotes(wfTimestampNow()) . '
					WHERE iti_to_lang=' . $dbw->addQuotes($language) . '
					AND iti_from_url in (' . $dbw->makeList($urls) . ')' ;
			if (!$dryRun) {
				$dbw->query($sql, __METHOD__);
			}
			else {
				print("Running query : $sql\n");
			}
		}
		return($ret);
	}

}

