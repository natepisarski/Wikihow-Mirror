<?php

/*
 * Syntax: php removeBrokenInternationalWikitext.php [-n number of edits to make] [-o log file] [-e error log file]
 */

require_once __DIR__ . '/../../Maintenance.php';

class RemoveBrokenInternationalWikitext extends Maintenance {
	const LL_RE = '/\[\[[a-zA-Z][a-zA-Z]:[^\]]+\]\]/';
	const LOG = '/var/log/wikihow/intl_wikitext.log';
	const ERROR_LOG = '/var/log/wikihow/intl_wikitext_error.log';

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Remove interwiki links from articles';
		$this->addOption('numEdits', 'Number of edits to make before stopping', false, true, 'n');
		$this->addOption('logFile', 'Log file', false, true, 'o');
		$this->addOption('errorFile', 'Error log file', false, true, 'e');
	}

	public function execute() {
		global $wgLanguageCode, $wgUser, $wgDisableScriptEmails;

		// make edits as InterwikiBot
		$tempUser = $wgUser;
		$wgUser = User::newFromName('InterwikiBot');

		// do not send any emails based the direct actions of this script
		$wgDisableScriptEmails = true;

		$lang = $wgLanguageCode;
		$dbr = wfGetDB(DB_REPLICA);
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

			$wikitext = Wikitext::getWikitext($dbw, $t);
			if (!$wikitext) {
				$err = "Unable to load wikitext in title $t";
				$this->logError($err);
				continue;
			}

			try {
				$newtext = $wikitext;
				$newtext = self::removeInterwikiLinks($wikitext);
				if (strcmp($newtext, $wikitext)) {
					Wikitext::saveWikitext($t, $newtext, "Removing interwiki links, no longer needed in wikitext");
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

	private static function removeInterwikiLinks($wikitext) {
		return preg_replace(self::LL_RE, '', $wikitext);
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

$maintClass = 'RemoveBrokenInternationalWikitext';
require_once RUN_MAINTENANCE_IF_MAIN;

