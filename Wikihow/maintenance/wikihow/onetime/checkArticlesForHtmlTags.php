<?php

require_once __DIR__ . '/../../Maintenance.php';

class checkArticlesForHTMLTags extends Maintenance {

	const ALL_TAGS_LOG = '/var/log/wikihow/articles_html_tags.log';
	const LIST_TAGS_LOG = '/var/log/wikihow/articles_html_list_tags.log';

	var $tags_to_ignore = [
		'ref',
		'br',
		'nowiki',
		'math',
		'code',
		'i',
		'b',
		'strong',
		'p',
		'sup',
		'sub',
		'em',
		'u',
		'center',
		'tt',
		'pre'
	];

	var $list_tags = [
		'ul',
		'ol',
		'li',
		'dl',
		'dt',
		'dd'
	];

	public function __construct() {
		parent::__construct();
		$this->addOption('justListTags', 'just look for list tags', false, false, 'j');
	}

	public function execute() {
		$rows = DatabaseHelper::batchSelect(
			'titus_copy',
			[
				'ti_page_id',
				'ti_page_title'
			],
			[
				'ti_language_code' => 'en',
				// 'ti_page_id' => 1653
			],
			__METHOD__,
			[
				// 'LIMIT' => 1000,
				'ORDER BY' => 'ti_page_id'
			]
		);

		$count = 0;
		$htmlCount = 0;

		foreach ($rows as $row) {
			$result = $this->processArticle($row->ti_page_id);
			if ($result) $htmlCount++;
			$count++;
			if ($count % 5000 == 0) usleep(500000);
		}

		if ($this->getOption("justListTags"))
			$result = "\nDone. $count articles checked. $htmlCount had list tags in them.\n";
		else
			$result = "\nDone. $count articles checked. $htmlCount had HTML in them.\n";

		print $result;
	}

	private function processArticle($page_id) {
		$title = Title::newFromId($page_id);
		if (!$title || !$title->exists() || !RobotPolicy::isTitleIndexable($title)) return false;

		$rev = Revision::newFromTitle($title);
		if (!$rev) return false;

		$wikitext = ContentHandler::getContentText( $rev->getContent() );
		$tags = $this->getValidHtmlTags($wikitext);

		if (!empty($tags)) {
			$this->logLine( $page_id."\t".$tags."\n" );
			return true;
		}
		else {
			return false;
		}
	}

	private function getValidHtmlTags($wikitext) {
		$htmlTags = [];

		preg_match_all('/(<.*?>)/', $wikitext, $tags);
		$tags = !empty($tags[0]) ? $tags[0] : [];

		foreach ($tags as $tag) {
			if ($this->isHtmlTagWeCareAbout($tag)) $htmlTags[] = $tag;
		}

		return implode(',', $htmlTags);
	}

	private function isHtmlTagWeCareAbout(string $tag) {
		$tag = preg_replace('/(<|>)/m', '', trim(strtolower($tag)));

		//forget any attributes, just want the core tag
		$first_space = strpos($tag,' ');
		if ($first_space) $tag = substr($tag, 0, $first_space);

		if ($this->getOption("justListTags")) {
			$result = in_array($tag, $this->list_tags);
		}
		else {
			$result = !empty($tag)
				&& $tag[0] != '/' //not the closing
				&& $tag[0] != '!' //not a comment
				&& !in_array($tag, $this->tags_to_ignore);
		}

		return $result;
	}

	private function logLine($txt) {
		$log_file = $this->getOption("justListTags") ? self::LIST_TAGS_LOG : self::ALL_TAGS_LOG;
		$fh = fopen($log_file, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}
}

$maintClass = "checkArticlesForHTMLTags";
require_once RUN_MAINTENANCE_IF_MAIN;
