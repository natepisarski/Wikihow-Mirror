<?php

require_once __DIR__ . '/../Maintenance.php';


// import the sensitive related wikihows
class importSensitiveRelatedWikihows extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "import sensitive related wikihows";
    }

	public function execute() {
		SensitiveRelatedWikihows::saveSensitiveRelatedArticles();
	}
}


$maintClass = "importSensitiveRelatedWikihows";
require_once RUN_MAINTENANCE_IF_MAIN;

