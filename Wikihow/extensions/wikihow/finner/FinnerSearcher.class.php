<?php

class FinnerSearcher extends \CirrusSearch\Searcher {
	public function addFilter($filter) {
		$this->filters[] = $filter;
	}

	public function addNotFilter($notFilter) {
		$this->notFilters[] = $notFilter;
	}
}
