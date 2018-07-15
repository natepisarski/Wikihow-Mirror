<?php
/*
 * Shows the output of quality algorithms for NAB prioritization
 */
class NABPrioritizer extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct("NABPrioritizer");
	}
	public function execute($par) {
		global $wgOut, $wgRequest, $wgUser;

		$userGroups = $wgUser->getGroups();
		if( $wgUser->isBlocked() ||  (!in_array( 'staff', $userGroups ) && $wgUser->getName() != 'Lojjik') ) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$dbr = wfGetDB( DB_SLAVE );
		$algorithm = $wgRequest->getVal( 'algorithm' );
		if ( $algorithm == NULL ) {
			$algorithm = $dbr->selectField( 'auto_nfd','max(an_algorithm)',array(), __METHOD__,array() );
		}
		$res = $dbr->select( 'auto_nfd','distinct(an_algorithm)',array(),__METHOD__,array() );
		$nums = array();
		foreach ( $res as $row ) {
			$nums[] = $row->an_algorithm;
		}
		$res = $dbr->select( 'auto_nfd', array('an_page_title','an_day', 'an_page_id','an_revision_id','an_day','an_dscore','an_ndscore', 'an_sscore','an_reason'),array('an_dscore >= .75','an_algorithm' => $algorithm, 'an_day > date_sub(now(), interval 4 day)'), __METHOD__,array('ORDER BY' => 'an_day desc, an_dscore desc') );
		EasyTemplate::set_path( dirname( __FILE__ ) . '/' );
		$vars = array('rows' => $res, 'algorithm' => $algorithm, 'nums' => $nums);

		$html = EasyTemplate::html( 'nabprioritizer.tmpl.php', $vars );
		$wgOut->addHTML( $html );
	}
}
