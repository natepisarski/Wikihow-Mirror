<?php

/*
 * Syntax: php removeExtraImgAttribs.php [-n number of edits to make] [-o log file] [-e error log file]
 */

require_once __DIR__ . '/../../Maintenance.php';

class RemoveExtraImgAttribs extends Maintenance {
	/* <br><br>[[Image:Imagename|center|555px]] => [[Image:Imagename|center]] */
	const IMG_RE = '@<br><br>\[\[Image:([^\]\|]+)\|center\|\d+px\]\]@';

	/* {{largeimage|Imagename}} => [[Image:Imagename|center]] */
	const IMG_LI = '@{{[L|l]argeimage\|([^\]\|]+)}}@';

	/* Removes images with size < 625 */
	/* '@\[\[Image:([^\]\|]+)\|
		  [^\]\|]+\|		left, center, right
		  (?:
		   (?:\d{1,2})		0-99
		   |(?:[1-5]\d\d)	100-599
		   |(?:6[01]\d)		600-619
		   |(?:62[0-4])		620-624
		  )
		  px\]\]@'
	*/
	const IMG_INTRO = '@\[\[Image:([^\]\|]+)\|[^\]\|]+\|(?:(?:\d{1,2})|(?:[1-5]\d\d)|(?:6[01]\d)|(?:62[0-4]))px\]\]@';

	const LOG = '/var/log/wikihow/img_attrib.log';
	const ERROR_LOG = '/var/log/wikihow/img_attrib_error.log';

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Replace largeimage templates, remove hidden intro images, and remove ignored image attributes from articles';
		$this->addOption('numEdits', 'Number of edits to make before stopping', false, true, 'n');
		$this->addOption('logFile', 'Log file', false, true, 'o');
		$this->addOption('errorFile', 'Error log file', false, true, 'e');
	}

	public function execute() {
		global $wgLanguageCode, $wgUser, $wgDisableScriptEmails;

		// make edits as MiscBot user
		$tempUser = $wgUser;
		$wgUser = User::newFromName('MiscBot');

		// do not send any emails based the direct actions of this script
		$wgDisableScriptEmails = true;

		$lang = $wgLanguageCode;
		$dbr = wfGetDB(DB_SLAVE);
		if (!$dbw) $dbw = wfGetDB(DB_MASTER);
		$page_titles = array();
		$err = '';
		$count = 0;

		$res = $dbr->select(
			array($dbr->addIdentifierQuotes(Misc::getLangDB($lang)) . '.page'),
			array('page_title'),
			array(
				'page_namespace' => NS_MAIN,
				'page_is_redirect' => 0
			),
			__METHOD__
		);

		foreach ($res as $row) {
			$page_titles[] = $row->page_title;
		}

		foreach ($page_titles as $page_title) {
			if ($this->getOption("numEdits") && $count >= intval($this->getOption("numEdits"))) break;
			$err = '';
			$t = Title::newFromDBKey($page_title);
			if (!$t || !$t->exists()) {
				$err = "Title error at $page_title";
				$this->logError($err);
				continue;
			}

			$wikitext = Wikitext::getWikitext($dbr, $t);
			if (!$wikitext) {
				$err = "Unable to load wikitext in title $t";
				$this->logError($err);
				continue;
			}

			try {
				$newtext = $wikitext;
				$newtext = self::removeRedundantImageAttrs($newtext);
				$newtext = self::removeLargeImageTemp($newtext);
				$newtext = self::removeHiddenIntroImages($newtext);
				if (strcmp($newtext, $wikitext)) {
					Wikitext::saveWikitext($t, $newtext, "Standardizing some image attributes that have now been built into images automatically; removing hidden intro images");
					$count++;
					$this->logIt("$page_title");
				}
			} catch (Exception $e) {
				$err = "Exception on article $t";
			}
			if ($err) {
				$this->logError($err);
			}
		}
		// swap back
		$wgUser = $tempUser;
	}

	private static function removeRedundantImageAttrs($wikitext) {
		return preg_replace(self::IMG_RE, '[[Image:$1|center]]', $wikitext);
	}

	private static function removeLargeImageTemp($wikitext) {
		return preg_replace(self::IMG_LI, '[[Image:$1|center]]', $wikitext);
	}

	private static function removeHiddenIntroImages($wikitext) {
		$intro = Wikitext::getIntro($wikitext);
		$new = Wikitext::replaceIntro($wikitext, preg_replace(self::IMG_INTRO, '', $intro));
		/* replaceIntro creates a blank line between the intro section and the next section,
		 * which can result in edits where the only change is the newline being added.
		 * check for this
		 */
		if (!self::equalsIgnoreWhitespace($wikitext, $new))
			return $new;
		else
			return $wikitext;
	}

	private static function equalsIgnoreWhitespace($str1, $str2) {
		return !strcmp(preg_replace('/\s+/', '', $str1), preg_replace('/\s+/', '', $str2));
	}

	private function logIt($txt) {
		$logfile = $this->getOption("logFile", self::LOG);

		$fh = fopen($logfile, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}

	private function logError($txt) {
		$logfile = $this->getOption("errorFile", self::ERROR_LOG);

		$fh = fopen($logfile, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}
}

$maintClass = 'RemoveExtraImgAttribs';
require_once RUN_MAINTENANCE_IF_MAIN;

