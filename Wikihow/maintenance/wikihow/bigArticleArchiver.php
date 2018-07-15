<?php
//
// Archive old Rising Star and RSS feeds.
//
// Example uses:
// sudo -Hu apache php bigArticleArchiver.php --article=wikiHow:Rising-star-feed
// sudo -Hu apache php bigArticleArchiver.php --article=Rising-star-feed --split-old
// sudo -Hu apache php bigArticleArchiver.php --article=RSS-feed --split-old

require_once __DIR__ . '/../Maintenance.php';

class SplitBigArticleMaintenance extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Split an article whose name ends with /ArchiveN";
		$this->addOption( 'article', 'Article to split', true, true, 'a' );
		$this->addOption( 'split-old', 'Run in "split-old" mode, which looks at the Article/AchiveN sub pages', false, false, 's' );
	}

	public function execute() {
		$article = $this->getOption('article');
		if (!$article) {
			die("error: must specify --article param\n");
		}

		self::loginAsBot('ArchiveBot');

		$splitOldMode = $this->getOption('split-old');
		if ($splitOldMode) {
			$dbr = wfGetDB(DB_SLAVE);
			$res = $dbr->query("SELECT page_namespace, page_title FROM page WHERE page_title LIKE '" . $article . "/%'", __FILE__);
			foreach ($res as $row) {
				$title = Title::makeTitle( $row->page_namespace, $row->page_title );
				#print "Found title: " . $title . "\n";
				self::maybeSplitAndArchiveOld($title);
			}
		} else {
			$dbr = wfGetDB(DB_SLAVE);
			$title = Title::newFromUrl( $article );
			if ($title->exists()) {
				print "Found title: " . $title . "\n";
				self::maybeArchiveOld($title);
			}
		}
	}

	private static function maybeArchiveOld($title) {
		$wikiPage = WikiPage::factory($title);
		if ($wikiPage) {
			$rawText = $wikiPage->getContent(Revision::RAW);
			$wikitext = ContentHandler::getContentText($rawText);

			$months = self::splitWikitextByMonth($wikitext);

			$lastMonth = date( 'Y-m', strtotime('last month') );
			$currentMonth = date('Y-m');
			$nextMonth = date( 'Y-m', strtotime('next month') );

			if (isset($months[$currentMonth])) {
				$commitMsg = 'Archiving old data to subpage';

				// Put initial lines at start of the current month and archive the other months
				$startLines = $months[''];
				unset($months['']);
				if (!$startLines) {
					die("error: something went wrong in parsing. No starting lines found.\n");
				}

				// If there are older months than the current month, archive them
				$keepMonths = '';
				foreach (array($lastMonth, $currentMonth, $nextMonth) as $keepMonth) {
					if (isset($months[$keepMonth])) {
						$keepMonths .= $months[$keepMonth];
						unset($months[$keepMonth]);
					}
				}

				if ($keepMonths && count($months)) {
					foreach ($months as $month => $wikitext) {
						$currentMonthTime = strtotime($currentMonth);
						$monthTime = strtotime($month);
						if ($month == $currentMonth || $currentMonthTime < $monthTime) {
							// don't archive this month if we think it's in the present
							// month or in the future.
							$keepMonths .= $wikitext;
							print "Will not archive current or future month: $month\n";
						} else {
							$monthTitle = self::genArchiveMonthName($title, $month);
							if ($monthTitle) {
								$saveAsTitle = $monthTitle;
							} else {
								$saveAsTitle = self::genArchiveName($title);
							}
							print "Saving month $month as $saveAsTitle...\n";
							self::saveWikitextToTitle($saveAsTitle, $wikitext, $commitMsg);

							// parse existing startLines to put this new wikitext into the
							// intro section
							$startLines = self::addNewArchiveLinks($startLines, $saveAsTitle);
						}
					}

					print "Saving $title...\n";
					self::saveWikitextToTitle($title, $startLines . $keepMonths, $commitMsg);
				} else {
					print "notice: nothing to archive\n";
				}

			} else {
				print "error: current month not found yet -- will wait for matching $currentMonth before archiving old\n";
			}
		} else {
			print "error: could not load $titleStr\n";
		}
	}

	private static function addNewArchiveLinks($startLines, $saveAsTitle) {
		$parts = explode('==', $startLines, 2);
		$wikiLink = self::wikiLinkFromArchiveTitle($saveAsTitle) . "\n";
		if (count($parts) > 1) {
			$startLines = $parts[0] . $wikiLink . '==' . $parts[1];
		} else {
			$startLines = $parts[0] . $wikiLink;
		}
		return $startLines;
	}

	private static function maybeSplitAndArchiveOld($title) {
		$wikiPage = WikiPage::factory($title);
		if ($wikiPage) {
			$rawText = $wikiPage->getContent(Revision::RAW);
			$wikitext = ContentHandler::getContentText($rawText);

			$months = self::splitWikitextByMonth($wikitext);

			// Put initial lines at start of first month
			if (isset($months[''])) {
				$startLines = $months[''];
				unset($months['']);
				if ($startLines && count($months)) {
					foreach ($months as $k => &$v) {
						$v = $startLines . $v;
						break;
					}
				}
			}

			if (count($months) > 1) {
				self::saveSplitOldMonths($title, $months);
			}
		} else {
			print "error: could not load $titleStr\n";
		}
	}

	private static function splitWikitextByMonth($wikitext) {
		$lines = explode("\n", $wikitext);

		// Split lines into different months
		$months = array();
		$month = '';
		$current = '';
		foreach ($lines as $line) {
			if (preg_match('@\b(\d{4}-\d{1,2})-\d{1,2}\b@', $line, $m)) {
				$foundMonth = $m[1];
				if ($month != $foundMonth) {
					if ($current) {
						if (isset($months[$month])) {
							$months[$month] .= $current;
						} else {
							$months[$month] = $current;
						}
					}
					$month = $foundMonth;
					$current = '';
				}
			}
			$current .= "$line\n";
		}
		if ($current) {
			if (isset($months[$month])) {
				$months[$month] .= $current;
			} else {
				$months[$month] = $current;
			}
		}

		ksort($months);
		return $months;
	}

	private static function saveSplitOldMonths($title, $months) {
		$first = true;
		$lastTitle = $title;
		foreach ($months as $month => $wikitext) {
			if (!$first) {
				$monthTitle = self::genArchiveMonthName($title, $month);
				if (!$monthTitle) {
					$title = self::genArchiveName($lastTitle);
					$lastTitle = $title;
				} else {
					$title = $monthTitle;
				}
			}
			self::saveWikitextToTitle($title, $wikitext, 'Splitting archive subpages by month');
			print self::wikiLinkFromArchiveTitle($title) . " ";
			$first = false;
		}
	}

	private static function genArchiveMonthName($title, $month) {
		if (!$title->isSubpage()) {
			$baseTitle = $title;
		} else {
			$baseTitle = $title->getBaseTitle();
		}

		$title = $baseTitle->getSubpage('Archive' . preg_replace('@[- ]+@', '_', $month));
		if (!$title->exists()) {
			return $title;
		} else {
			return null;
		}
	}

	private static function genArchiveName($lastTitle) {
		if (!$lastTitle->isSubpage()) {
			for ($i = 1; $i < 1000; $i++) {
				$title = $lastTitle->getSubpage('Archive' . $i);
				if (!$title->exists()) break;
			}
		} else {
			$subpageText = $lastTitle->getSubpageText();
			$baseTitle = $lastTitle->getBaseTitle();
			if (preg_match('@^([Aa]rchive[- ]?\d+)([a-z]{0,2})$@', $subpageText, $m)) {
				$baseSub = $m[1];
				$letters = $m[2];
				for ($i = 1; $i < 1000; $i++) {
					// Get next letter sequence at end of title
					if (strlen($letters) == 0) {
						$letters = 'a';
					} elseif (strlen($letters) == 1) {
						$ord = ord($letters{0}) + 1;
						if ($ord <= ord('z')) {
							$letters = chr($ord);
						} else {
							$letters = 'aa';
						}
					} elseif (strlen($letters) == 2) {
						$ord = ord($letters{1}) + 1;
						if ($ord <= ord('z')) {
							$letters = $letters{0} . chr($ord);
						} else {
							$letters = chr( ord($letters{0}) + 1) . 'a';
							// TODO: check for wrap past 'zz' here
						}
					}
					$title = $baseTitle->getSubpage($baseSub . $letters);
					if (!$title->exists()) break;
				}
			} else {
				for ($i = 1; $i < 1000; $i++) {
					$title = $baseTitle->getSubpage('Archive' . $i);
					if (!$title->exists()) break;
				}
			}
		}
		return $title;
	}

	private static function wikiLinkFromArchiveTitle($title) {
		$titleStr = (string)$title;
		$endPart = '';
		if (preg_match('@([0-9_]+)$@', $titleStr, $m)) {
			$endPart = '|' . $m[1]; 
		}
		return "[[$title$endPart]]";
	}

	private static function saveWikitextToTitle($title, $wikitext, $commitMsg) {
		$article = new Article($title);
		$saved = $article->doEdit($wikitext, $commitMsg, EDIT_FORCE_BOT);
	}

	private static function loginAsBot($userName) {
		global $wgUser;
		$wgUser = User::newFromName('ArchiveBot');
		if ( !$wgUser->hasGroup('bot') ) {
			$wgUser->addGroup('bot');
		}
	}
}

$maintClass = "SplitBigArticleMaintenance";
require_once RUN_MAINTENANCE_IF_MAIN;

