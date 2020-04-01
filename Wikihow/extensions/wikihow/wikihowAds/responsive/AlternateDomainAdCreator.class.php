<?php

class DefaultAlternateDomainAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();
		global $domainName;

		$this->mAdSetupData = array(
			'intro' => array(
				'service' => 'adsense',
				'instantload' => 1,
				'slot' => 4143652190,
				'width' => 728,
				'height' => 120,
				'smallslot' => 7567823162,
				'smallheight' => 120,
				'class' => ['ad_label', 'ad_label_dollar'],
				'type' => 'intro',
				'small' => 1,
				'medium' => 1,
				'large' => 1,
			),
			'method' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/altd/altd_gam_lgm_meth1',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'toc' => array(
				'service' => 'adsense',
				'slot' => 1242705425,
				'width' => 728,
				'height' => 90,
				'type' => 'toc',
				'medium' => 1,
				'large' => 1,
			),
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 7073067865,
				'instantload' => 0,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
			),
			'rightrail1' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/altd/altd_gam_lgm_rght2',
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true,
				'refreshable' => 1,
				'viewablerefresh' => 1,
				'first-refresh-time' => 30000,
				'refresh-time' => 28000,
				'aps-timeout' => 800,
				'width' => 300,
				'height' => 600,
				'containerheight' => 3300,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'large' => 1,
			),
			'scrollto' => array(
				'service' => 'adsense',
				'type' => 'scrollto',
				'slot' => 3524898610,
				'maxsteps' => 2,
				'maxnonsteps' => 0,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'quiz' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/altd/altd_gam_lgm_quizz',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'class' => ['hidden'],
				'type' => 'quiz',
				'medium' => 1,
				'large' => 1,
			),
			'related' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/altd/altd_gam_lgm_relat',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'qa' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/altd/altd_gam_lgm_qanda',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'large' => 1,
			),
			'mobilemethod' => array(
				'service' => 'adsense',
				'slot' => 8497761450,
				'width' => 728,
				'height' => 90,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'method',
				'small' => 1,
				'medium' => 1,
			),
			'mobilerelated' => array(
				'service' => 'adsense',
				'smallslot' => 5020951688,
				'smallheight' => 250,
				'smalllabel' => 1,
				'slot' => 1541585687,
				'width' => 728,
				'height' => 90,
				'type' => 'related',
				'small' => 1,
				'medium' => 1,
			),
			'middlerelated' => array(
				'service' => 'adsense',
				'smallslot' => 1673551851,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'related',
				'small' => 1,
			),
			'mobileqa' => array(
				'service' => 'adsense',
				'slot' => 8333745818,
				'width' => 728,
				'height' => 90,
				'smallslot' => 8577053310,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'qa',
				'small' => 1,
				'medium' => 1,
			),
			'tips' => array(
				'service' => 'adsense',
				'smallslot' => 1865123541,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'tips',
				'small' => 1,
			),
			'warnings' => array(
				'service' => 'adsense',
				'smallslot' => 7245442735,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'warnings',
				'small' => 1,
			),
			'pagebottom' => array(
				'service' => 'adsense',
				'smallslot' => 8238960205,
				'smallheight' => 250,
				'smalllabel' => 1,
				'type' => 'pagebottom',
				'small' => 1,
			),
		);
	}

	public function isAdOkForDomain( $ad ) {
		$result = true;

		if ( !Misc::isMobileMode() ) {
			// on desktop domain only show ads with large
			if ( $ad->setupData['large'] !== 1 ) {
				$result = false;
			}
		}

		return $result;
	}

	protected function isDFPOkForSetup() {
		return true;
	}

}
