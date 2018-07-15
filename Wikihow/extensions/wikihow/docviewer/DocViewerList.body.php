<?php

class DocViewerList extends UnlistedSpecialPage {
	
	function __construct() {
		parent::__construct( 'DocViewerList' );
	}
	
	private static function getSamplesList() {
		global $wgServer;
		$dbr = wfGetDB(DB_SLAVE);
		
		$res = $dbr->select('dv_sampledocs', array('dvs_doc'), array(), __METHOD__, array('GROUP BY' => 'dvs_doc','ORDER BY' => 'dvs_doc'));
		
		foreach ($res as $row) {
			$url = $wgServer.'/Sample/'.$row->dvs_doc;
			$html .= '<li><a href="'.$url.'">'.$url.'</a></li>';
		}
		
		$html = '<ul>'.$html.'</ul>';
		return $html;
	}
	
	function execute($par) {
		global $wgOut;
	
		$html = self::getSamplesList();
		$wgOut->addHTML($html);
	}
	
}
