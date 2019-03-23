<?php
// script to regenerate thumbnails for samples
require_once( __DIR__ . '/../../Maintenance.php' );

class UpdateSampleThumbnails extends Maintenance {

	var $pageIds = [];

	public function __construct() {
		parent::__construct();
		// $this->addOption( 'title', 'title', false, true, 't');
	}

	public function execute() {
		$this->resizeSamples();
		$this->purgeCachesOnArticlesWithUpdatedSamples();
	}

	private function resizeSamples() {
		$dbr = wfGetDb(DB_REPLICA);
		$res = $dbr->select(
			'dv_links',
			'*',
			[],
			__METHOD__
		);

		foreach ($res as $row) {
			$result = $this->resizeSingleSampleImage($row->dvl_doc);
			if ($result) $this->pageIds[] = $row->dvl_page;
		}
	}

	//modified from DocViewer::grabOneDoc()
	private function resizeSingleSampleImage($doc_name): bool {
		//spaces to hyphens
		$doc_name_hyphenized = preg_replace('@ @','-',$doc_name);

		$file = wfFindFile($doc_name.'_sample.png');

		//backwards compatibility
		if (!$file || !isset($file)) $file = wfFindFile($doc_name.'.png');

		if ($file && isset($file)) {
			$thumb = $file->getThumbnail(340);
			return true;
		}
		else {
			return false;
		}
	}

	private function purgeCachesOnArticlesWithUpdatedSamples() {
		$this->pageIds = array_unique($this->pageIds);

		foreach ($this->pageIds as $id) {
			$page = Wikipage::newFromID($id);
			if ($page) $page->doPurge();
		}
	}
}

$maintClass = "UpdateSampleThumbnails";
require_once( RUN_MAINTENANCE_IF_MAIN );
