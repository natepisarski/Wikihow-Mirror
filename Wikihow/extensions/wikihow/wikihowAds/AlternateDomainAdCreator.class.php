<?php

class AlternateDomainAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();
		global $domainName;

		$introSlot = '';
		$tocSlot = '';
		$rr0Slot = '';
		$scrollToSlot = '';
		$mobileIntroSlot = '';
		$mobileMethodSlot = '';
		$mobileRelatedSlot = '';
		$rr1AdUnitPath = '';
		$quizAdUnitPath = '';


		switch ( $domainName ) {
			case "howyougetfit.com":
				$introSlot = 2258884570;
				$tocSlot = 2895931007;
				$rr0Slot = 3624411510;
				$scrollToSlot = 9587184635;
				$mobileIntroSlot = 3524867775;
				$mobileMethodSlot = 5001600976;
				$mobileRelatedSlot = 5001600976;
				$rr1AdUnitPath = 'alt_hygf_rr2';
				$quizAdUnitPath = 'alt_hygf_quiz';
				break;
			case "wikihow.tech":
				$introSlot = 8305418177;
				$tocSlot = 5348610629;
				$rr0Slot = 3241268139;
				$scrollToSlot = 8082531278;
				$mobileIntroSlot = 6757535770;
				$mobileMethodSlot = 2187735379;
				$mobileRelatedSlot = 2187735379;
				$rr1AdUnitPath = 'alt_wht_rr2';
				$quizAdUnitPath = 'alt_wht_quiz';
				break;
			case "wikihow.pet":
				$introSlot = 3009706573;
				$tocSlot = 6470066700;
				$rr0Slot = 2474981375;
				$scrollToSlot = 2830204597;
				$mobileIntroSlot = 7189227370;
				$mobileMethodSlot = 8665960573;
				$mobileRelatedSlot = 8665960573;
				$rr1AdUnitPath = 'alt_whp_rr2';
				$quizAdUnitPath = 'alt_whp_quiz';
				break;
			case "howyoulivelife.com":
				$introSlot = 4845456904;
				$tocSlot = 3213401875;
				$rr0Slot = 3432839821;
				$scrollToSlot = 3021776288;
				$mobileIntroSlot = 4370814181;
				$mobileMethodSlot = 5189071838;
				$mobileRelatedSlot = 5189071838;
				$rr1AdUnitPath = 'alt_hyll_rr2';
				$quizAdUnitPath = 'alt_hyll_quiz';
			case "wikihow.life":
				$introSlot = 3917364520;
				$tocSlot = 3269188293;
				$rr0Slot = 5292716404;
				$scrollToSlot = 5073224551;
				$mobileIntroSlot = 7567823162;
				$mobileMethodSlot = 8497761450;
				$mobileRelatedSlot = 8497761450;
				$rr1AdUnitPath = 'alt_whl_rr2';
				$quizAdUnitPath = 'alt_whl_quiz';
				break;
			case "wikihow.fitness":
				$introSlot = 1291201186;
				$tocSlot = 7699441791;
				$rr0Slot = 9040389726;
				$scrollToSlot = 1489256667;
				$mobileIntroSlot = 1743816697;
				$mobileMethodSlot = 6084841797;
				$mobileRelatedSlot = 6084841797;
				$rr1AdUnitPath = 'alt_whf_rr2';
				$quizAdUnitPath = 'alt_whf_quiz';
				break;
			case "wikihow.health":
				//$rr1AdUnitPath = 'AllPages_RR_1_wikiHowHealth_Desktop_All';
				break;
			case "wikihow.mom":
				$introSlot = 1099629495;
				$tocSlot = 7400004996;
				$rr0Slot = 8110451432;
				$scrollToSlot = 2129644243;
				$mobileIntroSlot = 2618748819;
				$mobileMethodSlot = 3245434778;
				$mobileRelatedSlot = 3245434778;
				$rr1AdUnitPath = 'alt_whm_rr2';
				$quizAdUnitPath = 'alt_whm_quiz';
				break;
			case "wikihow-fun.com":
				$introSlot = 7741774671;
				$tocSlot = 5320636566;
				$rr0Slot = 4881706760;
				$scrollToSlot = 7863093328;
				$mobileIntroSlot = 7550202981;
				$mobileMethodSlot = 8138372250;
				$mobileRelatedSlot = 4199127249;
				$rr1AdUnitPath = 'alt_whfun_rr2';
				$quizAdUnitPath = 'alt_whfun_quiz';
				break;
		}

		$this->mAdSetupData = array(
			'intro' => array(
				'service' => 'adsense',
				'instantload' => 1,
				'slot' => $introSlot,
				'width' => 728,
				'height' => 120,
				'mobileslot' => $mobileIntroSlot,
				'mobileheight' => 120,
				'class' => ['ad_label', 'ad_label_dollar'],
			),
			'method' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/Method_1_Alt_Domain',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'width' => 728,
				'height' => 90,
				'desktoponly' => 1
			),
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => $rr0Slot,
				'instantload' => 1,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
			'rightrail1' => array(
				'service' => 'dfp',
				'adUnitPath' => $rr1AdUnitPath,
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true,
				'refreshable' => 1,
				'first-refresh-time' => 30000,
				'refresh-time' => 28000,
				'aps-timeout' => 800,
				'width' => 300,
				'height' => 600,
				'containerheight' => 3300,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
			'scrollto' => array(
				'service' => 'adsense',
				'type' => 'scrollto',
				'slot' => $scrollToSlot,
				'maxsteps' => 2,
				'maxnonsteps' => 0,
				'width' => 728,
				'height' => 90,
			),
			'quiz' => array(
				'service' => 'dfp',
				'adUnitPath' => $quizAdUnitPath,
				'size' => '[728, 90]',
				'apsLoad' => true,
				'width' => 728,
				'height' => 90,
				'type' => 'quiz',
			),
			'mobilemethod' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
			'related' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileRelatedSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
			'middlerelated' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
			'qa' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
			'tips' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
			'warnings' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
			'pagebottom' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
		);
	}
}
