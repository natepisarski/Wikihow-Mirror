<?php

class FinnerSearchEngine extends CirrusSearch {
	private $sort;
	private $filters = array();
	private $notFilters = array();

	public function getSort() {
		return $this->sort;
	}

	public function setSort($sort) {
		$this->sort = $sort;
	}
}

