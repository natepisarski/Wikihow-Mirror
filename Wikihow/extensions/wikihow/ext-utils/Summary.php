<?php

class Summary {

	var $pageId;
	var $page;

	function __construct($pageId) {
		$this->pageId = $pageId;
		$this->page = WikiPage::newFromId($this->pageId);
		return $this;
	}

	public function getSummary($charLimit=1000) {
		if (!$this->page->getContent()) {
			return '';
		}
		$text = $this->page->getContent()->getTextForSummary($charLimit);
		$text = Wikitext::stripHeader($text);
		$text = Wikitext::cutFirstStep($text);
		$text = Wikitext::flatten($text);
		$lines = explode('==', $text);
		return $lines[0];
	}

	public function getTitleText() {
		return $this->page->getTitle()->getFullText();
	}

	public function getTitle() {
		return $this->page->getTitle();
	}

	public function getSlug() {
		return $this->page->getTitle()->getDBkey();
	}
}
