<?php
/*
 * remove a magic word from all articles
 *
 * - use --limit=n to only run on a small batch of articles
 */
require_once __DIR__ . '/../../commandLine.inc';

global $IP;
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");

$wgUser = User::newFromName("MiscBot");

$limit = '';
if (isset($options['limit'])) {
	//batchselect doubles, so lets halve it here
	$limit = round($options['limit'] / 2);
}

$rmw = new RemoveMagicWord($limit);
$rmw->execute();

class RemoveMagicWord {

	const SLEEPTIME = 500000;	//measured in microseconds = .5 seconds
	const THE_LOG_FILE = '/tmp/summarized_removed.log';

	var $comment = 'Removing Summarized magic word that isn’t necessary anymore.';
	var $magic_word = 'summarized';
	var $dbr = null;
	var $limit = 0;

	public function __construct($limit) {
		$this->dbr = wfGetDB(DB_REPLICA);
		$this->limit = $limit;
	}

	public function execute() {
		print "\nBEGIN - ". wfTimestampNow() . "\n";
		$this->cycleThroughAllArticles();
		print "\nDONE - ". wfTimestampNow() . "\n\n";
	}

	/**
	 * Grab all the articles
	 */
	private function cycleThroughAllArticles() {
		$options = [];
		if ($this->limit) $options['LIMIT'] = $this->limit;

		$res = DatabaseHelper::batchSelect(
						'page',
						'page_id',
						[
							'page_namespace' => NS_MAIN,
							'page_is_redirect' => 0
						],
						__METHOD__,
						$options
					);

		print 'articles: '.count($res)."\n";

		$count = 0;
		$num = 1;
		foreach ($res as $row) {
			//print $num.' - ';
			$title = Title::newFromId( $row->page_id );
			if ($title) {
				//print $title->getDBKey();
				if ($this->removeMagicWord($title)) $count++;
				if ($count >= 5000) break;
			}
			//print "\n";
			$num++;
		}
		print "\nchanged: ".$count."\n";
		return;
	}

	/**
	 * process a single article for the subheaders
	 */
	private function removeMagicWord($title) {
		$rev = Revision::loadFromTitle($this->dbr, $title);
		if (!$rev) return false;
		$wikitext = $og_wikitext = ContentHandler::getContentText( $rev->getContent() );

		if ($wikitext) {
			$mw = MagicWord::get( $this->magic_word );
			$changed = $mw->matchAndRemove( $wikitext );

			if ($changed) {
				$res = $this->saveChanges($title, $wikitext);

				print $res."\n";
				$this->logIt($res);

				//good night, sweet prince...
				usleep(self::SLEEPTIME);
				return true;
			}
		}
		return false;
	}

	private function saveChanges($title, $wikitext) {
		$saved = false;
		$wikiPage = WikiPage::factory($title);
		$edit_flags = EDIT_UPDATE | EDIT_MINOR | EDIT_FORCE_BOT;

		$content = ContentHandler::makeContent( $wikitext, $title );
		$saved = $wikiPage->doEditContent($content, $this->comment, $edit_flags);

		$url = 'https://www.wikihow.com/'.$title->getDBKey();

		if (!$saved) {
			return 'Unable to save wikitext for article: ' . $url;
		} else {
			return $url;
		}
	}

	/**
	 * log the article that we modified just in case...
	 */
	private function logIt($txt) {
		$logfile = self::THE_LOG_FILE;

		$fh = fopen($logfile, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}
}
