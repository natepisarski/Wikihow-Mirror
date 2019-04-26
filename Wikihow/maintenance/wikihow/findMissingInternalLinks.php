<?php

require_once __DIR__ . '/WHMaintenance.php';

/**
 * Find internal links in EN articles that are missing from the equivalent INTL articles.
 *
 * E.g. '/Add' links to '/Subtract' on EN, but '/sumar' doesn't link to '/restar' on ES
 */
class FindMissingInternalLinks extends WHMaintenance {

	const OUTPUT_DIR = '/data/missing_internal_links';

	protected $emailRecipients = 'alberto@wikihow.com';
	private $wikiTexts = []; // EN articles wikiText excluding the 'Related wikiHows' section

	public function __construct()
	{
		parent::__construct();
		$this->mDescription = "Find internal links in INTL articles";
	}

	public function execute()
	{
		global $wgActiveLanguages, $quietDevImageHooks;
		$quietDevImageHooks = true; // to silence wikihow/hooks/DevImageHooks.body.php

		parent::execute();

		@mkdir(self::OUTPUT_DIR, 0775, true);
		if (!is_writable(self::OUTPUT_DIR)) {
			$this->echo(self::OUTPUT_DIR . ' is not writable (try running as root)', 'FATAL');
			return;
		}

		$this->loadDevData();

		foreach ($wgActiveLanguages as $langCode) {
			$this->processLang($langCode);
		}

		$this->dumpDevData();
	}


	private function processLang(string $langCode)
	{
		// Find all indexable INTL articles, their EN version, and the INTL articles they link to

		$engDB = Misc::getLangDB('en');
		$intDB = Misc::getLangDB($langCode);
		$sql = "
			SELECT p_orig.page_id int_orig_id, p_orig.page_title int_orig_title,
				   p_engl.page_id eng_orig_id, p_engl.page_title eng_orig_title,
				   p_dest.page_id int_dest_id

			  FROM {$intDB}.index_info              -- 'all indexable INTL articles'

			  JOIN {$intDB}.page p_orig
				ON p_orig.page_id = ii_page
			   AND p_orig.page_namespace = 0

			  JOIN {$engDB}.translation_link        -- 'their EN version'
				 ON tl_from_lang = 'en'
				AND tl_to_lang = '{$langCode}'
				AND tl_to_aid = ii_page

			  JOIN {$engDB}.page p_engl
				ON p_engl.page_id = tl_from_aid
			   AND p_engl.page_namespace = 0

			  LEFT JOIN {$intDB}.pagelinks          -- 'the INTL articles they link to'
				ON pl_from = ii_page
			   AND pl_namespace = 0

			  LEFT JOIN {$intDB}.page p_dest
				ON p_dest.page_title = pl_title
			   AND p_dest.page_namespace = 0

			 WHERE ii_namespace = 0
			   AND ii_policy IN (1, 4)
		  ";

		$dbr = wfGetDB(DB_REPLICA);
		$intLinks = $dbr->query($sql);
		$intIDs = []; // indexable INTL articles
		$pages = []; // [ EN_ID => [ INT_ID, INT_TITLE, ... ] ]
		foreach ($intLinks as $link) {
			$engID = (int) $link->eng_orig_id;
			$intID = (int) $link->int_orig_id;
			if (!isset($pages[$engID])) {
				$pages[$engID] = [
					'intID'    => $intID,
					'intTitle' => $link->int_orig_title,
					'intLinks' => [],
					'engID'    => $engID,
					'engTitle' => $link->eng_orig_title,
					'missingLinks' => [],
				];
			}
			if ($link->int_dest_id) { // NULL if the article contains no links
				$pages[$engID]['intLinks'][] = (int) $link->int_dest_id;
			}
			$intIDs[$intID] = 1;
		}

		// Find links in the EN articles where the destination exists on INTL and is indexable

		$eng_ids = $dbr->makeList(array_keys($pages));
		$int_ids = $dbr->makeList(array_keys($intIDs));
		$sql = "
		SELECT pl_from from_id, page_id to_id
		  FROM {$engDB}.pagelinks

		  JOIN {$engDB}.page                   -- 'links in the EN articles'
			ON page_title = pl_title
		   AND page_namespace = 0
		   AND page_id IN ({$eng_ids})

		  JOIN {$engDB}.translation_link       -- 'where the destination exists on INTL'
			ON tl_from_aid = pl_from
		   AND tl_from_lang = 'en'
		   AND tl_to_lang = '{$langCode}'
		   AND tl_to_aid IN ({$int_ids})
		   ";

		$engLinks = $dbr->query($sql);
		foreach ($engLinks as $link) {
			if ($link->from_id == $link->to_id) { // link to self, like [[Article#section]]
				continue;
			}
			$origPage = &$pages[(int) $link->from_id];
			$destPage =  $pages[(int) $link->to_id];
			if (in_array($destPage['intID'], $origPage['intLinks'])) { // link not missing
				continue;
			}

			$context = $this->getLinkContext($origPage['engID'], $destPage['engTitle']);
			if ($context) {
				$origPage['missingLinks'][] = [
					'engID' => $destPage['engID'],
					'engTitle' => $destPage['engTitle'],
					'engContext' => $context,
					'intID' => $destPage['intID'],
					'intTitle' => $destPage['intTitle'],
				];
			}
		}

		// Report missing links

		$filePath = self::OUTPUT_DIR . '/' . $langCode . '.csv';
		$fp = fopen($filePath, 'w');
		$headerLine = [ '#','eng_orig_id','eng_orig_title','int_orig_id','int_orig_title',
				  'eng_dest_id','eng_dest_title','int_dest_id','int_dest_title','context' ];
		fputcsv($fp, $headerLine);

		$engBaseURL = PROTO_HTTPS . wfCanonicalDomain('en');
		$intBaseURL = PROTO_HTTPS . wfCanonicalDomain($langCode);

		$pageCnt = $linkCnt = 0;
		foreach ($pages as $page) {
			if (!$page['missingLinks']) {
				continue;
			}

			extract($page); // $intID, $intTitle, $engID, $engTitle, $intLinks, $missingLinks
			$pageCnt++;

			$engOrigURL = $engBaseURL . '/' . $engTitle;
			$intOrigURL = $intBaseURL . '/' . $intTitle;
			$pos = 0;
			foreach ($missingLinks as $link) {
				$linkCnt++;
				$engDestURL = $engBaseURL . '/' . $link['engTitle'];
				$intDestURL = $intBaseURL . '/' . $link['intTitle'];
				$engDestID = $link['engID'];
				$intDestID = $link['intID'];
				$context = $link['engContext'];
				$line = [++$pos,$engID,$engOrigURL,$intID,$intOrigURL,$engDestID,$engDestURL,$intDestID,$intDestURL,$context];
				fputcsv($fp, $line);
			}
		}

		$this->echo("$langCode: $linkCnt missing links in $pageCnt pages. Details: $filePath");

		fclose($fp);
	}

	private function getLinkContext(int $origID, string $destTitle)
	{
		$pageTxt = $this->wikiTexts[$origID] ?? null;
		if (!$pageTxt) {
			$page = WikiPage::newFromID($origID);
			if (!$page || !$page->exists()) {
				return null;
			}
			$pageTxt = $page->getText();
			$relatedsTxt = Wikitext::getSection($pageTxt, 'Related wikiHows', true)[0];
			// Save the EN page wikiText minus the Related wHs section
			$this->wikiTexts[$origID] = $pageTxt = str_replace($relatedsTxt, '', $pageTxt);

		}

		// 'Clean-a-Computer-Monitor/LCD-Screen' results in pattern:
		// '/\[\[\s*Clean[ -]a[ -]Computer[ -]Monitor\/LCD[ -]Screen\s* (?: \|[^]]+)? \]\]/ix'
		$destTitle = preg_quote($destTitle, '/');
		$destTitle = str_replace('\-', '[ -]', $destTitle);
		$pattern = '/\[\[\s*' . $destTitle . '\s* (?: \|[^]]+)? \]\]/ix';

		$context = null;
		if ( preg_match($pattern, $pageTxt, $matches) ) {
			$match = $matches[0];
			$pos = strpos($pageTxt, $match);
			$context = substr($pageTxt, max(0, $pos-60), 160); // grab surrounding text
			$context = str_replace(PHP_EOL, ' ', trim($context)); // remove line breaks
			$context = str_replace($match, strtoupper($match), $context); // capitalize link
		}

		return $context;
	}

	private function loadDevData()
	{
		global $wgIsDevServer;
		if (!$wgIsDevServer) {
			return;
		}
		$data = @file_get_contents(self::OUTPUT_DIR . '/data.bin');
		if ($data !== false) {
			$wikiTexts = unserialize($data);
			if (is_array($wikiTexts)) {
				$this->wikiTexts = $wikiTexts;
			}
		}
	}

	private function dumpDevData()
	{
		global $wgIsDevServer;
		if (!$wgIsDevServer) {
			return;
		}
		$data = serialize($this->wikiTexts);
		file_put_contents(self::OUTPUT_DIR . '/data.bin', $data);
	}

}

$maintClass = 'FindMissingInternalLinks';
require_once RUN_MAINTENANCE_IF_MAIN;
