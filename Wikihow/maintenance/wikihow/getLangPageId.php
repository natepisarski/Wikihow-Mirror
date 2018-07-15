<?php
require_once( __DIR__ . '/../Maintenance.php' );
class TitusGetLangPageId extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( 'id', 'english pageId to act on', true, true, 'i' );
		$this->addOption( 'lang', 'target language', true, true, 'l' );
	}

	public function execute() {
		global $wgLanguageCode, $IP;
		require_once("$IP/extensions/wikihow/titus/Titus.class.php");

		$id = $this->getOption( 'id' );
		$lang = $this->getOption( 'lang' );

		$titus = new TitusDB(true);
		$res = $titus->getLangPageId( $id, $lang );
		echo "$res \n";
	}
}

$maintClass = "TitusGetLangPageId";
require_once RUN_MAINTENANCE_IF_MAIN;
