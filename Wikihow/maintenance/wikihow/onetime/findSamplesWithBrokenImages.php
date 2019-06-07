<?php
require_once( __DIR__ . '/../../Maintenance.php' );

class FindSamplesWithBrokenImages extends Maintenance {

	// var $pageIds = [];

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		$this->cycleThroughSamples();
	}

	private function cycleThroughSamples() {
		$res = wfGetDb(DB_REPLICA)->select('dv_links', 'dvl_doc', [], __METHOD__);

		foreach ($res as $row) {
			$result = $this->hasBrokenImage($row->dvl_doc);
			if ($result) print $this->wikiHowUrlFromSampleName($row->dvl_doc)."\n";
		}
	}

	private function hasBrokenImage(string $sample_name): bool {
		$url = $this->wikiHowUrlFromSampleName($sample_name);

		$html = file_get_contents($url);
		$doc = phpQuery::newDocument( $html );

		$sample_img = pq('.sample_container:not(.pdf_container)')->find('img')->attr('src');
		if (empty($sample_img)) return false;

		return !$this->checkRemoteFile($sample_img);
	}

	private function wikiHowUrlFromSampleName(string $sample_name): string {
		return 'https://www.wikihow.com/Sample/'.$sample_name;
	}

	private function checkRemoteFile(string $url): bool {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		// don't download content
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($ch);
		curl_close($ch);

		return $result !== FALSE;
	}
}

$maintClass = "FindSamplesWithBrokenImages";
require_once( RUN_MAINTENANCE_IF_MAIN );
