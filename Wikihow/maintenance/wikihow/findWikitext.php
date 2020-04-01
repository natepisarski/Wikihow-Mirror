<?php


require_once __DIR__ . '/../Maintenance.php';

class FindKeyPoints extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Script to find a certain string of wikitext across all articles";
		$this->addOption( 'regex', 'regular expression to be found', true, true, 'r');
		$this->addOption( 'pageid', 'limits search to the specific page id', false, true, 'i');
	}

	public function execute() {
		global $wgLanguageCode;

		$conds = [
			'page_namespace' => NS_MAIN,
			'page_is_redirect' => 0,
		];

		if ($this->hasOption('pageid')) {
			$conds['page_id'] = $this->getOption('pageid');
		}
		$res = DatabaseHelper::batchSelect(
			'page',
			['page_id'],
			$conds
			,
			__METHOD__
		);

		foreach ($res as $row) {
			$t = Title::newFromId($row->page_id);
			if ($t) {
				$r = Revision::newFromTitle($t);
				if ($r) {
					$wikitext = ContentHandler::getContentText($r->getContent());
					//'@<math>@mi';
					$pattern = $this->getOption('regex');
					if (preg_match($pattern, $wikitext)) {
						echo 'https://' . Misc::getLangDomain($wgLanguageCode) . $t->getLocalURL() . "\n";
					}
				}
			}
		}
	}
}

$maintClass = 'FindKeyPoints';
require_once RUN_MAINTENANCE_IF_MAIN;
