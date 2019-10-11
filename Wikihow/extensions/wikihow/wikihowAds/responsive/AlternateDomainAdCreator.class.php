<?php

class DefaultAlternateDomainAdCreator extends AdCreator {
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
		$altDomain = AlternateDomain::getAlternateDomainForCurrentPage();

		switch ( $altDomain ) {
			case "wikihow.tech":
				$introSlot = 1485892786;
				$tocSlot = 9172811110;
				$rr0Slot = 1273946270;
				$scrollToSlot = 6334701268;
				$mobileIntroSlot = 6757535770;
				$mobileMethodSlot = 2187735379;
				$mobileRelatedSlot = 2187735379;
				$methodAdUnitPath = 'dfp_responsive_alt_tech_lm_method_1';
				$quizAdUnitPath = 'dfp_responsive_alt_tech_lm_quiz';
				$relatedAdUnitPath = 'dfp_responsive_alt_tech_lm_rwh';
				$qaAdUnitPath = 'dfp_responsive_alt_tech_lm_qa';
				$rr1AdUnitPath = 'dfp_responsive_alt_tech_lm_right_rail_2';
				break;
			case "wikihow.pet":
				$introSlot = 7668157756;
				$tocSlot = 7456211247;
				$rr0Slot = 6143129572;
				$scrollToSlot = 4830047909;
				$mobileIntroSlot = 7189227370;
				$mobileMethodSlot = 8665960573;
				$mobileRelatedSlot = 8665960573;
				$methodAdUnitPath = 'dfp_responsive_alt_pet_lm_method_1';
				$quizAdUnitPath = 'dfp_responsive_alt_pet_lm_quiz';
				$relatedAdUnitPath = 'dfp_responsive_alt_pet_lm_rwh';
				$qaAdUnitPath = 'dfp_responsive_alt_pet_lm_qa';
				$rr1AdUnitPath = 'dfp_responsive_alt_pet_lm_right_rail_2';
				break;
			case "wikihow.life":
				$introSlot = 4143652190;
				$tocSlot = 1242705425;
				$rr0Slot = 7073067865;
				$scrollToSlot = 3524898610;
				$mobileIntroSlot = 7567823162;
				$mobileMethodSlot = 8497761450;
				$mobileRelatedSlot = 8497761450;
				$methodAdUnitPath = 'dfp_responsive_alt_life_lm_method_1';
				$quizAdUnitPath = 'dfp_responsive_alt_life_lm_quiz';
				$relatedAdUnitPath = 'dfp_responsive_alt_life_lm_rwh';
				$qaAdUnitPath = 'dfp_responsive_alt_life_lm_qa';
				$rr1AdUnitPath = 'dfp_responsive_alt_life_lm_right_rail_2';
				break;
			case "wikihow.fitness":
				$introSlot = 5759986191;
				$tocSlot = 9204407186;
				$rr0Slot = 5568414503;
				$scrollToSlot = 8194577849;
				$mobileIntroSlot = 1743816697;
				$mobileMethodSlot = 6084841797;
				$mobileRelatedSlot = 6084841797;
				$methodAdUnitPath = 'dfp_responsive_alt_fitness_lm_method_1';
				$quizAdUnitPath = 'dfp_responsive_alt_fitness_lm_quiz';
				$relatedAdUnitPath = 'dfp_responsive_alt_fitness_lm_rwh';
				$qaAdUnitPath = 'dfp_responsive_alt_fitness_lm_qa';
				$rr1AdUnitPath = 'dfp_responsive_alt_fitness_lm_right_rail_2';
				break;
			case "wikihow.health":
				//$rr1AdUnitPath = 'AllPages_RR_1_wikiHowHealth_Desktop_All';
				break;
			case "wikihow.mom":
				$introSlot = 2203884568;
				$tocSlot = 5181950430;
				$rr0Slot = 6769815539;
				$scrollToSlot = 3868868768;
				$mobileIntroSlot = 2618748819;
				$mobileMethodSlot = 3245434778;
				$mobileRelatedSlot = 3245434778;
				$methodAdUnitPath = 'dfp_responsive_alt_mom_lm_method_1';
				$quizAdUnitPath = 'dfp_responsive_alt_mom_lm_quiz';
				$relatedAdUnitPath = 'dfp_responsive_alt_mom_lm_rwh';
				$qaAdUnitPath = 'dfp_responsive_alt_mom_lm_qa';
				$rr1AdUnitPath = 'dfp_responsive_alt_mom_lm_right_rail_2';
				break;
			case "wikihow-fun.com":
				$introSlot = 6303460411;
				$tocSlot = 5959490260;
				$rr0Slot = 5668680376;
				$scrollToSlot = 8765423487;
				$mobileIntroSlot = 7550202981;
				$mobileMethodSlot = 8138372250;
				$mobileRelatedSlot = 4199127249;
				$methodAdUnitPath = 'dfp_responsive_alt_fun_lm_method_1';
				$quizAdUnitPath = 'dfp_responsive_alt_fun_lm_quiz';
				$relatedAdUnitPath = 'dfp_responsive_alt_fun_lm_rwh';
				$qaAdUnitPath = 'dfp_responsive_alt_fun_lm_qa';
				$rr1AdUnitPath = 'dfp_responsive_alt_fun_lm_right_rail_2';
				break;
			case "wikihow.legal":
				$introSlot = 7876396533;
				$tocSlot = 2624069855;
				$rr0Slot = 3306596130;
				$scrollToSlot = 2655504224;
				$mobileIntroSlot = null;
				$mobileMethodSlot = null;
				$mobileRelatedSlot = null;
				$methodAdUnitPath = 'dfp_responsive_alt_legal_lm_method_1';
				$quizAdUnitPath = 'dfp_responsive_alt_legal_lm_quiz';
				$relatedAdUnitPath = 'dfp_responsive_alt_legal_lm_rwh';
				$qaAdUnitPath = 'dfp_responsive_alt_legal_lm_qa';
				$rr1AdUnitPath = 'dfp_responsive_alt_legal_lm_right_rail_2';
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
				'type' => 'intro',
				'inline-html' => 1,
			),
			'method' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/'.$methodAdUnitPath,
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'desktoponly' => 1
			),
			'method2' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_alt_domain_m_method_2',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'mobiledomain' => 1,
				'mediumonly' => 1
			),
			'method3' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_alt_domain_m_method_3',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'mobiledomain' => 1,
				'mediumonly' => 1
			),
			'method4' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_alt_domain_m_method_4',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'mobiledomain' => 1,
				'mediumonly' => 1
			),
			'method5' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_alt_domain_m_method_5',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'mobiledomain' => 1,
				'mediumonly' => 1
			),
			'methodextra' => array(
				'service' => 'adsense',
				'slot' => 2108966478,
				'width' => 728,
				'height' => 90,
				'mobiledomain' => 1,
				'mediumonly' => 1
			),
			'methodlast' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_alt_domain_m_method_last',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'mobiledomain' => 1,
				'mediumonly' => 1
			),
			'toc' => array(
				'service' => 'adsense',
				'slot' => $tocSlot,
				'width' => 728,
				'height' => 90,
				'type' => 'toc',
			),
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => $rr0Slot,
				'instantload' => 0,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'inline-html' => 1,
				'largeonly' => 1,
			),
			'rightrail1' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/'.$rr1AdUnitPath,
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
				'largeonly' => 1,
			),
			'scrollto' => array(
				'service' => 'adsense',
				'type' => 'scrollto',
				'slot' => $scrollToSlot,
				'maxsteps' => 2,
				'maxnonsteps' => 0,
				'width' => 728,
				'height' => 90,
				'largeonly' => 1,
			),
			'quiz' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/'.$quizAdUnitPath,
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'class' => ['hidden'],
				'type' => 'quiz',
			),
			'related' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/'.$relatedAdUnitPath,
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'desktoponly' => 1
			),
			'qa' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/'.$qaAdUnitPath,
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'desktoponly' => 1
			),
			'mobilemethod' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'mobiledomain' => 1,
				'type' => 'method',
			),
			'mobilerelated' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileRelatedSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'mobiledomain' => 1,
				'type' => 'related'
			),
			'middlerelated' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'mobiledomain' => 1,
				'type' => 'related'
			),
			'mobileqa' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'mobiledomain' => 1,
				'type' => 'qa'
			),
			'tips' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'mobiledomain' => 1,
				'type' => 'tips'
			),
			'warnings' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'mobiledomain' => 1,
				'type' => 'warnings'
			),
			'pagebottom' => array(
				'service' => 'adsense',
				'mobileslot' => $mobileMethodSlot,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'mobiledomain' => 1,
				'type' => 'pagebottom'
			),
		);
		if ( !Misc::isMobileMode() ) {
			foreach ( $this->mAdSetupData as $adType => $adData ) {
				if ( isset( $this->mAdSetupData[$adType]['mobiledomain'] ) ) {
					unset( $this->mAdSetupData[$adType]);
				}
			}
		}
	}
}
