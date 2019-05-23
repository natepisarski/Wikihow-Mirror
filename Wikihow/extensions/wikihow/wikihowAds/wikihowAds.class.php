<?php


/**********************
 *
 *  Here are all the ad units we have currently:
 *  intro: At the bottom of the intro
 *  0: Text ad after the first step
 *  1: Text ad after the last step
 *  2: Text ad in a section if there are no tips
 *  2a: Text ad at the end of the tips section
 *  4: Image ad in the right rail, only in INTERNATIONAL
 *	5: Docviewer: Image ad in sidebar on the samples page
 *  6: Docviewer2: Image ad at the top of the samples page
 *  7: Docviewer3: Text ads at the bottom of the samples page
 *  8: Linkunit2: Link unit in the right rail
 *
 *********************/

if (!defined('MEDIAWIKI')) die();

class wikihowAds {

	static $mGlobalChannels = array();
	static $mGlobalComments = array();
	static $mDfpUnits = array();
	static $mDfpCategory = null;
	public static $mCategories = array();
	static $mCategoriesSet = false;
	static $mTopLevelCategory = null;
	static $excluded = false;

	public static $mDesktopDfpCategoryInfo  = array(
		'Health' => array(
			'name' => 'Health',
			'targeting' => 'du8y3d'
		),
		'Arts-and-Entertainment' => array(
			'name' => 'ArtsEntertainment',
			'targeting' => 'fq2ja1'
		),
		'Food-and-Entertaining' => array(
			'name' => 'FoodEntertaining',
			'targeting' => 'at4gnq'
		),
		'Pets-and-Animals' => array(
			'name' => 'PetsAnimals',
			'targeting' => 'msddfv'
		),
		'Sports-and-Fitness' => array(
			'name' => 'SportsFitness',
			'targeting' => 'kldq5k'
		),
		'Cars-&-Other-Vehicles' => array(
			'name' => 'CarsVehicles',
			'targeting' => 'h730qx'
		),
		'Travel' => array(
			'name' => 'Travel',
			'targeting' => 'dmh3g7'
		),
		'Computers-and-Electronics' => array(
			'name' => 'ComputersElectronics',
			'targeting' => 'pzw484'
		),
		'Education-and-Communications' => array(
			'name' => 'EducationCommunications',
			'targeting' => 'g0492s'
		),
		'Work-World' => array(
			'name' => 'WorkWorld',
			'targeting' => 'xxvbh8'
		),
		'Family-Life' => array(
			'name' =>'Family',
			'targeting' => 'yp5ls2'
		),
		'Finance-and-Business' => array(
			'name' => 'FinanceBusiness',
			'targeting' => 'do6iwc'
		),
		'Hobbies-and-Crafts' => array(
			'name' => 'HobbiesCrafts',
			'targeting' => 'ft9nd3'
		),
		'Holidays-and-Traditions' => array(
			'name' => 'HolidaysTraditions',
			'targeting' => 'grute5'
		),
		'Home-and-Garden' => array (
			'name' => 'HomeGarden',
			'targeting' => 'gw0bcg'
		),
		'Other' => array(
			'name' => 'Other',
			'targeting' => 'sw181a'
		),
		'Personal-Care-and-Style' => array(
			'name' => 'PersonalCareStyle',
			'targeting' => 'jqar4r'
		),
		'WikiHow' => array(
			'name' => 'WikiHow',
			'targeting' => 'zn61ho'
		),
		'Youth' => array(
			'name' => 'Youth',
			'targeting' => 'czmmhl'
		),
		'Philosophy-and-Religion' => array(
			'name' => 'PhilosophyReligion',
			'targeting' => 'dfswr3'
		),
		'Relationships' => array(
			'name' => 'Relationships',
			'targeting' => 'fgfev6'
		)
	);

	static $mDesktopDfpAccount = "10095428";
	static $mDesktopDfpRandom = "div-gpt";

	static $mDesktopDfpNames = array(
		0 => '_First-Step_Desktop',
		1 => array(
			0 => '_Tips-L_300x250_Desktop',
			1 => '_Tips-R_300x250_Desktop'
		),
		2 => '_Method1_Desktop',
		3 => '_Method2_Desktop',
		4 => '_Method3_Desktop',
		5 => '_Method4_Desktop',
		6 => '_Method5_Desktop',
		7 => '_Method-Last_Desktop',
		8 => array(
			0 => '_Tips-L_300x250_Desktop',
			1 => '_Tips-R_300x250_Desktop'
		),
		9 => '_RR_2_English_Desktop',
		10 => '_RR_3_English_Desktop',
	);

	static $mDesktopIdNames = array(
		0 => '-First-Step',
		1 => array(
			0 => '-Tips-L',
			1 => '-Tips-R'
		),
		2 => '-Method1',
		3 => '-Method2',
		4 => '-Method3',
		5 => '-Method4',
		6 => '-Method5',
		7 => '-Method-Last',
		8 => array(
			0 => '-Tips-L',
			1 => '-Tips-R'
		),
		9 => '-ad-rr-second',
		10 => '-ad-rr-third'
	);

	static $mDesktopDfpSizes = array(
		0 => '[728, 90]',
		1 => '[300, 250]',
		2 => '[728, 90]',
		3 => '[728, 90]',
		4 => '[728, 90]',
		5 => '[728, 90]',
		6 => '[728, 90]',
		7 => '[728, 90]',
		8 => '[300, 250]',
		9 => '[300, 250]',
		10 => '[300, 250]'
	);

	static $mDesktopDfpLazy = array(
		0 => "false",
		1 => "false",
		2 => "false",
		3 => "false",
		4 => "false",
		5 => "false",
		6 => "false",
		7 => "false",
		8 => "false",
		9 => "true",
		10 => "true");

	static $mIntlRightRailUnits = array(
		'default' => array('/10095428/Chinese_300x250', '[300, 250]', 'div-gpt-ad-RR-intl', "false"),
		'de' => array('/10095428/German_300x250', '[300, 250]', 'div-gpt-ad-RR-intl', "false"),
		'es' => array('/10095428/Spanish_300x250', '[300, 250]', 'div-gpt-ad-RR-intl', "false"),
		'fr' => array('/10095428/French_300x250', '[300, 250]', 'div-gpt-ad-RR-intl', "false"),
		'it' => array('/10095428/Italian_300x250', '[300, 250]', 'div-gpt-ad-RR-intl', "false"),
		'nl' => array('/10095428/Dutch_300x250', '[300, 250]', 'div-gpt-ad-RR-intl', "false"),
		'pt' => array('/10095428/Portuguese_300x250', '[300, 250]', 'div-gpt-ad-RR-intl', "false"),
		'ru' => array('/10095428/Russian_300x250', '[300, 250]', 'div-gpt-ad-RR-intl', "false"),
		'ja' => array('/10095428/Japan_300x250', '[300, 250]', 'div-gpt-ad-RR-intl', "false")
	);

	var $ads;
	static $mTimeout = 625;

	public function __construct() {
		$this->ads = array();
	}

	public static function getDesktopDfpUnit($num) {
		global $wgTitle;

		if (self::isExcluded($wgTitle)) {
			return "";
		}

		// allow hooks to override the ad
		$result = "";
		Hooks::run( "WikihowAdsBeforeGetDesktopDfpUnit", array( $num, &$result ) );
		if ( $result ) {
			return $result;
		}

		$unitInfo = self::getUnitParams($num);
		if ( $num == 8 ) {
			// this is no longer supported
			return;
		}

		if ($num == 1) { //tips and section when there are no tips
			//tips
			self::$mDfpUnits[] = $unitInfo[0];
			self::$mDfpUnits[] = $unitInfo[1];

			$tmpl = new EasyTemplate( __DIR__ );
			$tmpl->set_vars(array(
				'unitName' => $unitInfo[0][0],
				'unitNumber' => $unitInfo[0][2],
				'lazy' => $unitInfo[0][3]
			));

			$adCode1 = $tmpl->execute('wikihowDfp.tmpl.php');
			$tmpl = new EasyTemplate( __DIR__ );
			$tmpl->set_vars(array(
				'unitName' => $unitInfo[1][0],
				'unitNumber' => $unitInfo[1][2],
				'lazy' => $unitInfo[1][3]
			));

			$adCode2 = $tmpl->execute('wikihowDfp.tmpl.php');

			return wfMessage("Dfpunit{$num}", $adCode1.$adCode2)->text();

		} else {
			self::$mDfpUnits[] = $unitInfo;

			$tmpl = new EasyTemplate( __DIR__ );
			$tmpl->set_vars(array(
				'unitName' => $unitInfo[0],
				'unitNumber' => $unitInfo[2],
				'lazy' => $unitInfo[3]
			));

			$adCode = $tmpl->execute('wikihowDfp.tmpl.php');

			return wfMessage("Dfpunit{$num}", $adCode)->text();
		}
	}

	public static function addToDFPUnits( $unit ) {
		self::$mDfpUnits[] = $unit;
	}

	public function getUnitParams($unitNum) {
		$info = array();

		if ($unitNum == 1 || $unitNum == 8) {
			$info[] = array(
				0 => "/" . self::$mDesktopDfpAccount . "/" . self::$mDesktopDfpCategoryInfo[self::$mTopLevelCategory]['name'] . self::$mDesktopDfpNames[$unitNum][0],
				1 => self::$mDesktopDfpSizes[$unitNum],
				2 => self::$mDesktopDfpRandom . "-" . $unitNum . "a" . self::$mDesktopIdNames[$unitNum][0],
				3 => self::$mDesktopDfpLazy[$unitNum]
			);
			$info[] = array(
				0 => "/" . self::$mDesktopDfpAccount . "/" . self::$mDesktopDfpCategoryInfo[self::$mTopLevelCategory]['name'] . self::$mDesktopDfpNames[$unitNum][1],
				1 => self::$mDesktopDfpSizes[$unitNum],
				2 => self::$mDesktopDfpRandom . "-" . $unitNum . "b" . self::$mDesktopIdNames[$unitNum][1],
				3 => self::$mDesktopDfpLazy[$unitNum]
			);
		} else {
			$info[0] = "/" . self::$mDesktopDfpAccount . "/" . self::$mDesktopDfpCategoryInfo[self::$mTopLevelCategory]['name'] . self::$mDesktopDfpNames[$unitNum];
			$info[1] = self::$mDesktopDfpSizes[$unitNum];
			$info[2] = self::$mDesktopDfpRandom . "-" . $unitNum . self::$mDesktopIdNames[$unitNum];
			$info[3] = self::$mDesktopDfpLazy[$unitNum];
		}

		return $info;
	}

	public static function getAdUnitPlaceholder( $num ) {
		global $wgTitle;

		if ( self::isExcluded( $wgTitle ) ) {
			return "";
		}

		if ( !self::isCombinedCall($num) ) {
			$unit = self::getAdUnit($num);
		} else {
			$unit = self::getWikihowAdUnit($num);
		}

		return $unit;
	}

	/***
	 *
	 * Generally our text ads are all combined,
	 * and image ads cannot be combined. Occasionally
	 * other ads besides image ads we don't want
	 * combine into one call
	 *
	 ***/
	function isCombinedCall($ad) {
		global $wgLanguageCode;

		if ($wgLanguageCode == "en") {
			return false;
		}
		$adString = strval($ad);
		switch ($adString) {
			case "tips":
			case "4":
			case "4b":
			case "4c":
			case "docviewer":
			case "docviewer2":
			case "docviewer2a":
			case "top":
			case "bottom":
				$ret =  false;
				break;
			default:
				$ret = true;

		}
		return $ret;
	}

	public static function exclude() {
		self::$excluded = true;
	}

	public static function isExcluded($title) : bool {
		global $wgLanguageCode, $wgMemc;

		if (self::$excluded) {
			return true;
		}

		if (!$title || !$title->exists()) {
			return false;
		}

		/**
		 * For now we're using memcache to store the array. If we get
		 * over ~2000 articles then we should switch to querying the table
		 * each time rather than storing the whole array.
		 **/
		$key = wfMemcKey('adExclusions', $wgLanguageCode);
		$excludeList = $wgMemc->get($key);
		if (!$excludeList || !is_array($excludeList)) {
			$excludeList = array();

			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->select(ArticleAdExclusions::TABLE, "ae_page", array(), __METHOD__);
			foreach ($res as $row) {
				$excludeList[] = $row->ae_page;
			}
			$wgMemc->set($key, $excludeList);
		}

		return in_array($title->getArticleID(), $excludeList);
	}

	function resetAllAdExclusionCaches() {
		global $wgActiveLanguages, $wgDBname;

		$dbr = wfGetDB(DB_REPLICA);

		//first do english
		self::resetAdExclusionCache($dbr, "en");

		foreach ($wgActiveLanguages as $languageCode) {
			self::resetAdExclusionCache($dbr, $languageCode);
		}

		$dbr->selectDB($wgDBname);
	}

	function resetAdExclusionCache(&$dbr, $languageCode) {
		global $wgMemc, $wgDBname;

		$oldDBname = $wgDBname;
		$wgDBname = Misc::getLangDB($languageCode);
		$key = wfMemcKey('adExclusions', $languageCode);
		$wgDBname = $oldDBname;
		$excludeList = array();

		if ($languageCode == "en") {
			$dbr->selectDB($wgDBname);
		} else {
			$dbr->selectDB('wikidb_'.$languageCode);
		}

		$res = $dbr->select(ArticleAdExclusions::TABLE, "ae_page", array(), __METHOD__);
		foreach ($res as $row) {
			$excludeList[] = $row->ae_page;
		}

		$wgMemc->set($key, $excludeList);
	}

	/*******
	 * Currently only used for en
	 */
	function getAdUnit($num) {
		global $wgLanguageCode;
		if ($wgLanguageCode == "en") {
			$channels = self::getCustomGoogleChannels('adunit' . $num);
			$s = wfMessage('adunit' . $num, $channels[0])->text();
		}
		else {
			$channels = self::getInternationalChannels();
			$s = wfMessage('adunit' . $num, $channels)->text();
		}

		//taking out wrapping <div class="wh_ad" b/c for current test we can't have that
		$s = "" . preg_replace('/\<[\/]?pre[^>]*>/', '', $s) . "";
		return $s;
	}

	/*******
	 * Currently this is only used for intl
	 ******/
	function getWikihowAdUnit($num) {
		global $wgLanguageCode;
		if ($wgLanguageCode == "en") {
			$channelArray = self::getCustomGoogleChannels('adunit' . $num);
			$channels = $channelArray[0];
		}
		else
			$channels = self::getInternationalChannels();

		$params = self::getCSIParameters($num);

		if ($params['slot'] == null || $params['width'] == null || $params['height'] == null || $params['max_ads'] == null) {
			//we don't have the required information, so lets spit out an error message
			$tmpl = new EasyTemplate( __DIR__ );
			$tmpl->set_vars(array(
				'adId' => $num,
				'params' => $params,
			));
			$s = $tmpl->execute('wikihowError.tmpl.php');
		}
		else {
			$tmpl = new EasyTemplate( __DIR__ );
			$tmpl->set_vars(array(
				'adId' => $num,
				'channels' => $channels,
				'params' => $params,
			));


			if ($wgLanguageCode == "en") {
				$s = $tmpl->execute('wikihowAdCSI.tmpl.php');
			}
			else {
				$tmpl->set_vars(array('adLabel' => wfMessage('ad_label')));
				$s = $tmpl->execute('wikihowAdAsyncIntl.tmpl.php');
			}
		}
		return $s;
	}

	static public function getCSIParameters($adNum) {
		global $wgLanguageCode;

		$adSizes = array(
			"intro" => array("width" => 728, "height" => 120, "max_ads" => 2),
			"0" => array("width" => 728, "height" => 120, "max_ads" => 2),
			"1" => array("width" => 728, "height" => 120, "max_ads" => 2),
			"2" => array("width" => 607, "height" => 180, "max_ads" => 3),
			"2a" => array("width" => 300, "height" => 250, "max_ads" => 3),
			"7" => array("width" => 728, "height" => 120, "max_ads" => 2),
			"docviewer3" => array("width" => 621, "height" => 120, "max_ads" => 3)
		);

		$adSlots = array(
			"en" => array("intro" => "8579663774", "0" => "5205564977", "1" => "7008858971", "2" => "7274067370", "2a" => "2533130178", "7" => "4009863375", "docviewer3" => "3079259774"),
			"intl" => array("intro" => "2950638973", "0" => "4427372174", "1" => "6102046573", "2" => "1334304979", "2a" => "3818061373", "7" => "3102862578"),
		);

		$adString = strval($adNum);
		$params = array();

		$params['width'] = $adSizes[$adString]['width'];
		$params['height'] = $adSizes[$adString]['height'];
		$params['max_ads'] = $adSizes[$adString]['max_ads'];
		if ($wgLanguageCode == "en") {
			$params['slot'] = $adSlots[$wgLanguageCode][$adString];
		} else {
			$params['slot'] = $adSlots["intl"][$adString];
		}

		return $params;
	}

	public static function getGlobalChannels() {
		global $wgTitle;

		self::$mGlobalChannels[] = "1640266093";
		self::$mGlobalComments[] = "page wide track";

        // track WRM articles in Google AdSense
		// but not if they're included in the
		// tech buckets above
        if ($wgTitle->inNamespace(NS_MAIN)) {
            $dbw = wfGetDB(DB_MASTER);
            $minrev = $dbw->selectField('revision', 'min(rev_id)', array('rev_page'=>$wgTitle->getArticleID()), __METHOD__);
			$details = $dbw->selectRow('revision', array('rev_user_text', 'rev_timestamp'), array('rev_id'=>$minrev), __METHOD__);
			$fe = $details->rev_user_text;

			//Tech buckets (no longer only WRM)
			$foundTech = false;
			$title =  "http://www.wikihow.com/" . $wgTitle->getPartialURL();
			$titleUrl =  "http://www.wikihow.com/" . $wgTitle->getPartialURL();
			$pageid = $wgTitle->getArticleId();
			if (ArticleTagList::hasTag('T_bin1', $pageid)) { //popular companies
				$foundTech = true;
				$ts = $details->rev_timestamp;
				if (preg_match("@^201106@", $ts)){
					self::$mGlobalChannels[] = "5265927225";
				} elseif (preg_match("@^201105@", $ts)){
					self::$mGlobalChannels[] = "2621163941";
				} elseif (preg_match("@^201104@", $ts)){
					self::$mGlobalChannels[] = "6703830173";
				} elseif (preg_match("@^201103@", $ts)){
					self::$mGlobalChannels[] = "7428198201";
				} elseif (preg_match("@^201102@", $ts)){
					self::$mGlobalChannels[] = "6027428251";
				} elseif (preg_match("@^201101@", $ts)){
					self::$mGlobalChannels[] = "3564919246";
				}
			}

			if (!$foundTech && ArticleTagList::hasTag('T_bin2', $pageid)) { //startup companies
				$foundTech = true;
				$ts = $details->rev_timestamp;
				if (preg_match("@^201112@", $ts)){
					self::$mGlobalChannels[] = "4113109859";
				} elseif (preg_match("@^201111@", $ts)){
					self::$mGlobalChannels[] = "1967209400";
				} elseif (preg_match("@^201110@", $ts)){
					self::$mGlobalChannels[] = "0168911685";
				} elseif (preg_match("@^201109@", $ts)){
					self::$mGlobalChannels[] = "5356416885";
				} elseif (preg_match("@^201108@", $ts)){
					self::$mGlobalChannels[] = "3273638668";
				} elseif (preg_match("@^201107@", $ts)){
					self::$mGlobalChannels[] = "9892808753";
				} elseif (preg_match("@^201106@", $ts)){
					self::$mGlobalChannels[] = "3519312489";
				} elseif (preg_match("@^201105@", $ts)){
					self::$mGlobalChannels[] = "2958013308";
				} elseif (preg_match("@^201104@", $ts)){
					self::$mGlobalChannels[] = "2240499801";
				} elseif (preg_match("@^201103@", $ts)){
					self::$mGlobalChannels[] = "9688666159";
				} elseif (preg_match("@^201102@", $ts)){
					self::$mGlobalChannels[] = "2421515764";
				} elseif (preg_match("@^201101@", $ts)){
					self::$mGlobalChannels[] = "8503617448";
				}
			}

            if ($fe == 'WRM' && !$foundTech) { //only care if we didn't put into a tech bucket
				self::$mGlobalComments[] = "wrm";
				$ts = $details->rev_timestamp;

				if (preg_match("@^201112@", $ts)){
					self::$mGlobalChannels[] = "6155290251";
				} elseif (preg_match("@^201111@", $ts)){
					self::$mGlobalChannels[] = "6049972339";
				} elseif (preg_match("@^201110@", $ts)){
					self::$mGlobalChannels[] = "0763990979";
				} elseif (preg_match("@^201109@", $ts)){
					self::$mGlobalChannels[] = "4358291042";
				} elseif (preg_match("@^201108@", $ts)){
					self::$mGlobalChannels[] = "0148835175";
				} elseif (preg_match("@^201107@", $ts)){
					self::$mGlobalChannels[] = "2390612184";
				} elseif (preg_match("@^201106@", $ts)){
					self::$mGlobalChannels[] = "1532661106";
				} elseif (preg_match("@^201105@", $ts)){
					self::$mGlobalChannels[] = "6709519645";
				} elseif (preg_match("@^201104@", $ts)){
					self::$mGlobalChannels[] = "8239478166";
				} elseif (preg_match("@^201103@", $ts)){
					self::$mGlobalChannels[] = "1255784003";
				} elseif (preg_match("@^201102@", $ts)){
					self::$mGlobalChannels[] = "7120312529";
				} elseif (preg_match("@^201101@", $ts)){
					self::$mGlobalChannels[] = "7890650737";
				} elseif (preg_match("@^201012@", $ts)){
					self::$mGlobalChannels[] = "9742218152";
				} elseif (preg_match("@^201011@", $ts)){
					self::$mGlobalChannels[] = "8485440130";
				} elseif (preg_match("@^201010@", $ts)){
					self::$mGlobalChannels[] = "7771792733";
				} elseif (preg_match("@^201009@", $ts)) {
				   self::$mGlobalChannels[] = "8422911943";
				} elseif (preg_match("@^201008@", $ts)) {
				   self::$mGlobalChannels[] = "3379176477";
				}
            } elseif (in_array($fe, array('Burntheelastic', 'CeeZee', 'Claricea', 'EssAy', 'JasonArton', 'Nperry302', 'Sugarcoat'))) {
                self::$mGlobalChannels[] = "8537392489";
                self::$mGlobalComments[] = "mt";
            } else {
                self::$mGlobalChannels[] = "5860073694";
                self::$mGlobalComments[] = "!wrm && !mt";
            }

			//Original WRM bucket
			if (ArticleTagList::hasTag('Dec2010_bin0', $pageid)) {
				self::$mGlobalChannels[] = "8110356115"; //original wrm channels
			}


			//WRM buckets
			$found = false;
			$title = $wgTitle->getFullText();
			if (ArticleTagList::hasTag('Dec2010_bin1', $pageid)) {
				$found = true;
				self::$mGlobalChannels[] = "8052511407";
			}
			if (!$found && ArticleTagList::hasTag('Dec2010_bin2', $pageid)) {
				$found = true;
				self::$mGlobalChannels[] = "8301953346";
			}
			if (!$found && ArticleTagList::hasTag('Dec2010_bin3', $pageid)) {
				$found = true;
				self::$mGlobalChannels[] = "7249784941";
			}
			if (!$found && ArticleTagList::hasTag('Dec2010_bin4', $pageid)) {
				$found = true;
				self::$mGlobalChannels[] = "8122486186";
			}
			if (!$found && ArticleTagList::hasTag('Dec2010_bin5', $pageid)) {
				$found = true;
				self::$mGlobalChannels[] = "8278846457";
			}
			if (!$found && ArticleTagList::hasTag('Dec2010_bin6', $pageid)) {
				$found = true;
				self::$mGlobalChannels[] = "1245159133";
			}
			if (!$found && ArticleTagList::hasTag('Dec2010_bin7', $pageid)) {
				$found = true;
				self::$mGlobalChannels[] = "7399043796";
			}
			if (!$found && ArticleTagList::hasTag('Dec2010_bin8', $pageid)) {
				$found = true;
				self::$mGlobalChannels[] = "6371049270";
			}
			if (!$found && ArticleTagList::hasTag('Dec2010_bin9', $pageid)) {
				$found = true;
				self::$mGlobalChannels[] = "9638019760"; //WRM Bucket: WRG-selected
			}

			if (ArticleTagList::hasTag('Dec2010_e1', $pageid)) {
				self::$mGlobalChannels[] = "8107511392"; //WRM Bucket: E1
			}

			if (ArticleTagList::hasTag('Dec2010_e2', $pageid)) {
				self::$mGlobalChannels[] = "3119976353"; //WRM Bucket: E2
			}

			if (isset(self::$mCategories['Recipes']) && self::$mCategories['Recipes'] != null) {
				self::$mGlobalChannels[] = "5820473342"; //Recipe articles
			}

			if (ArticleTagList::hasTag('CS_a', $pageid)) { //content strategy A
				self::$mGlobalChannels[] = "8989984079"; //Content Strategy A
			}

			if (ArticleTagList::hasTag('CS_b', $pageid)) {
				self::$mGlobalChannels[] = "3833770891"; //Content Strategy B
			}

			if (ArticleTagList::hasTag('CS_c', $pageid)) {
				self::$mGlobalChannels[] = "5080980738"; //Content Strategy C
			}

			if (ArticleTagList::hasTag('CS_d', $pageid)) {
				self::$mGlobalChannels[] = "3747905129"; //Content Strategy D
			}

			if (ArticleTagList::hasTag('CS_e', $pageid)) {
				self::$mGlobalChannels[] = "0499166168"; //Content Strategy E
			}

			if (ArticleTagList::hasTag('CS_f', $pageid)) {
				self::$mGlobalChannels[] = "3782603124"; //Content Strategy F
			}

			if (ArticleTagList::hasTag('CS_g', $pageid)) {
				self::$mGlobalChannels[] = "2169636267"; //Content Strategy G
			}

			$foundCSh = false;
			if (ArticleTagList::hasTag('CS_h', $pageid)) {
				$foundCSh = true;
			} else {
				$msg = wfMessage('CS_h1')->text(); //content strategy H
				$articles = explode("\n", $msg);
				foreach ($articles as $article) {
					if ($article == $titleUrl) {
						$foundCSh = true;
						break;
					}
				}
			}
			if ($foundCSh) {
				self::$mGlobalChannels[] = "6341255402"; //Content Strategy H
			}

			if (ArticleTagList::hasTag('CS_i', $pageid)) {
				self::$mGlobalChannels[] = "5819170825"; //Content Strategy I
			}

			if (ArticleTagList::hasTag('CS_j', $pageid)) {
				self::$mGlobalChannels[] = "7694072995"; //Content Strategy J
			}

			if (ArticleTagList::hasTag('CS_k', $pageid)) {
				self::$mGlobalChannels[] = "5982569583"; //Content Strategy K
			}

			if (ArticleTagList::hasTag('CS_l', $pageid)) {
				self::$mGlobalChannels[] = "7774283315"; //Content Strategy L
			}

			if (ArticleTagList::hasTag('CS_m', $pageid)) {
				self::$mGlobalChannels[] = "6128624756"; //Content Strategy M
			}

			if (ArticleTagList::hasTag('CS_n', $pageid)) {
				self::$mGlobalChannels[] = "2682008177"; //Content Strategy N
			}

			if (ArticleTagList::hasTag('CS_o', $pageid)) {
				self::$mGlobalChannels[] = "4294279486"; //Content Strategy O
			}

			if (ArticleTagList::hasTag('CS_p', $pageid)) {
				self::$mGlobalChannels[] = "8749396082"; //Content Strategy P
			}

			if (ArticleTagList::hasTag('CS_q', $pageid)) {
				self::$mGlobalChannels[] = "0856671147"; //Content Strategy Q
			}

			if (ArticleTagList::hasTag('CS_r', $pageid)) {
				self::$mGlobalChannels[] = "4560446682"; //Content Strategy R
			}

			if (ArticleTagList::hasTag('CS_s', $pageid)) {
				self::$mGlobalChannels[] = "3657316725"; //Content Strategy S
			}

			if (ArticleTagList::hasTag('CS_t', $pageid)) {
				self::$mGlobalChannels[] = "9924756626"; //Content Strategy T
			}

			if (ArticleTagList::hasTag('CS_u', $pageid)) {
				self::$mGlobalChannels[] = "8414472671"; //Content Strategy U
			}

			if (ArticleTagList::hasTag('WRM_2012Q1', $pageid)) {
				self::$mGlobalChannels[] = "4126436138";
			}

			if (ArticleTagList::hasTag('WRM_2012Q2', $pageid)) {
				self::$mGlobalChannels[] = "3130480452";
			}

			if (ArticleTagList::hasTag('WRM_2012Q3', $pageid)) {
				self::$mGlobalChannels[] = "5929918148";
			}

			if (ArticleTagList::hasTag('WRM_2012Q4', $pageid)) {
				self::$mGlobalChannels[] = "5980804200";
			}

			if (ArticleTagList::hasTag('WRM_2013Q1', $pageid)) {
				self::$mGlobalChannels[] = "2374803371";
			}

			if (ArticleTagList::hasTag('WRM_2013Q2', $pageid)) {
				self::$mGlobalChannels[] = "3851536574";
			}

			if (ArticleTagList::hasTag('WRM_2013Q3', $pageid)) {
				self::$mGlobalChannels[] = "5328269777";
			}

			if (ArticleTagList::hasTag('WRM_2013Q4', $pageid)) {
				self::$mGlobalChannels[] = "6805002974";
			}
		}
	}

	private function getMobileChannels($type) {
		$channels = array();
		$mobileCategoryUnits = array(
			'Arts-and-Entertainment' =>			array("1" => "8187082578", "4" => "2140548978", "5" => "9663815778"),
			'Health' =>							array("1" => "9325738579", "4" => "7709404578", "5" => "3279204975"),
			'Relationships' =>					array("1" => "7849005372", "4" => "1802471773", "5" => "4755938173"),
			'Cars-&-Other-Vehicles' =>			array("1" => "8420754977", "4" => "5467288573", "5" => "9897488173"),
			'Personal-Care-and-Style' =>		array("1" => "1244068573", "4" => "2720801771", "5" => "5674268171"),
			'Computers-and-Electronics' =>		array("1" => "5094015371", "4" => "3617282176", "5" => "2000948178"),
			'Pets-and-Animals' =>				array("1" => "7290602174", "4" => "3000003371", "5" => "7430202978"),
			'Education-and-Communications' =>	array("1" => "3837608170", "4" => "2360874976", "5" => "1861347377"),
			'Philosophy-and-Religion' =>		array("1" => "6093070573", "4" => "7569803776", "5" => "1662870970"),
			'Family-Life' =>					array("1" => "8267807776", "4" => "2221274173", "5" => "9744540976"),
			'Finance-and-Business' =>			array("1" => "6651473778", "4" => "3698007376", "5" => "3558406572"),
			'Sports-and-Fitness' =>				array("1" => "3418805774", "4" => "6511872979", "5" => "9465339373"),
			'Food-and-Entertaining' =>			array("1" => "6372272176", "4" => "1942072571", "5" => "7988606176"),
			'Travel' =>							array("1" => "2081673376", "4" => "5035139778", "5" => "9604940178"),
			'Hobbies-and-Crafts' =>				array("1" => "4616337377", "4" => "3139604176", "5" => "9046536978"),
			'Work-World' =>						array("1" => "5453942178", "4" => "2640076579", "5" => "2500475771"),
			'Home-and-Garden' =>				array("1" => "8767335375", "4" => "7151001374", "5" => "4197534972"),
			'Holidays-and-Traditions' =>		array("1" => "5813868976", "4" => "8906936177", "5" => "1523270178"),
			'Other' =>							array("1" => "4057934172", "4" => "1104467774", "5" => "5534667372"),
			'Youth' =>							array("1" => "4675212978", "4" => "6151946172", "5" => "3198479773"),
			'WikiHow' =>						array("1" => "6930675371", "4" => "8407408571", "5" => "8128206973"),
		);

		if (isset($mobileCategoryUnits[self::$mTopLevelCategory][$type])) {
			$channels[] = $mobileCategoryUnits[self::$mTopLevelCategory][$type];
		}

		return "+" . implode("+", $channels);
	}

	function getCustomGoogleChannels($type = "") {

		global $wgTitle, $wgLang;

		$channels = array();
		$comments = array();

		$ad = array();
		$ad['adunitintro'] 			= '0206790666';
		$ad['horizontal_search'] 	= '9965311755';
		$ad['adunit0']				= '2748203808';
		$ad['adunit1']				= '4065666674';
		$ad['adunit2']				= '7690275023';
		$ad['adunit2a']				= '9206048113';
		$ad['adunit3']				= '9884951390';
		$ad['adunit4']				= '7732285575';
		$ad['adunit4b']				= '0969350919';
		$ad['adunit4c']				= '8476920763';
		$ad['adunit5']				= '7950773090';
		$ad['adunit6']				= '';
		$ad['adunitdocviewer']		= '8359699501';
		$ad['adunitdocviewer3']		= '3068405775';
		$ad['adunit7']				= '8714426702';
		$ad['linkunit1']			= '2612765588';
		$ad['linkunit2']          	= '5047600031';
		$ad['linkunit3']            = '5464626340';
		$ad['adunittop']			= '7558104428';
		$ad['adunitbottom']			= '9368624199';

		$namespace = array();
		$namespace[NS_MAIN]             = '7122150828';
		$namespace[NS_TALK]             = '1042310409';
		$namespace[NS_USER]             = '2363423385';
		$namespace[NS_USER_TALK]        = '3096603178';
		$namespace[NS_PROJECT]          = '6343282066';
		$namespace[NS_PROJECT_TALK]     = '6343282066';
		$namespace[NS_IMAGE]            = '9759364975';
		$namespace[NS_IMAGE_TALK]       = '9759364975';
		$namespace[NS_MEDIAWIKI]        = '9174599168';
		$namespace[NS_MEDIAWIKI_TALK]   = '9174599168';
		$namespace[NS_TEMPLATE]         = '3822500466';
		$namespace[NS_TEMPLATE_TALK]    = '3822500466';
		$namespace[NS_HELP]             = '3948790425';
		$namespace[NS_HELP_TALK]        = '3948790425';
		$namespace[NS_CATEGORY]         = '2831745908';
		$namespace[NS_CATEGORY_TALK]    = '2831745908';
		$namespace[NS_USER_KUDOS]       = '3105174400';
		$namespace[NS_USER_KUDOS_TALK]  = '3105174400';

		$desktopCategoryUnits = array(
			'Arts-and-Entertainment' =>			array("adunit0" => "5355674178", "adunit1" => "6832407373", "adunitintro" => "5329517779"),
			'Health' =>							array("adunit0" => "6053678177", "adunit1" => "9007144577", "adunitintro" => "2096849770"),
			'Relationships' =>					array("adunit0" => "4158142577", "adunit1" => "8588342172", "adunitintro" => "7864181778"),
			'Cars-&-Other-Vehicles' =>			array("adunit0" => "8169539779", "adunit1" => "9646272973", "adunitintro" => "1200369376"),
			'Personal-Care-and-Style' =>		array("adunit0" => "1483877772", "adunit1" => "5914077373", "adunitintro" => "1957248974"),
			'Computers-and-Electronics' =>		array("adunit0" => "7670012178", "adunit1" => "4576944976", "adunitintro" => "6806250974"),
			'Pets-and-Animals' =>				array("adunit0" => "2402207776", "adunit1" => "3878940970", "adunitintro" => "3433982177"),
			'Education-and-Communications' =>	array("adunit0" => "7390810573", "adunit1" => "8867543772", "adunitintro" => "8282984171"),
			'Philosophy-and-Religion' =>		array("adunit0" => "1983405376", "adunit1" => "3460138576", "adunitintro" => "4910715374"),
			'Family-Life' =>					array("adunit0" => "2123006172", "adunit1" => "3599739378", "adunitintro" => "9759717373"),
			'Finance-and-Business' =>			array("adunit0" => "9785873778", "adunit1" => "3739340178", "adunitintro" => "2236450570"),
			'Sports-and-Fitness' =>				array("adunit0" => "8448741379", "adunit1" => "9925474572", "adunitintro" => "9340914972"),
			'Food-and-Entertaining' =>			array("adunit0" => "1344276972", "adunit1" => "2821010171", "adunitintro" => "3713183772"),
			'Travel' =>							array("adunit0" => "6413604978", "adunit1" => "7890338177", "adunitintro" => "1817648170"),
			'Hobbies-and-Crafts' =>				array("adunit0" => "7251209778", "adunit1" => "8727942973", "adunitintro" => "5050316177"),
			'Work-World' =>						array("adunit0" => "5076472572", "adunit1" => "6553205779", "adunitintro" => "9201314170"),
			'Home-and-Garden' =>				array("adunit0" => "4297743373", "adunit1" => "5774476570", "adunitintro" => "8003782579"),
			'Holidays-and-Traditions' =>		array("adunit0" => "8029938971", "adunit1" => "9506672179", "adunitintro" => "6527049374"),
			'Other' =>							array("adunit0" => "9866598976", "adunit1" => "2203731371", "adunitintro" => "9480515774"),
			'Youth' =>							array("adunit0" => "2541808571", "adunit1" => "4018541777", "adunitintro" => "1678047370"),
			'WikiHow' =>						array("adunit0" => "5495274978", "adunit1" => "6972008179", "adunitintro" => "7584980178"),
		);

		$channels[] = $ad[$type];
		$comments[] = $type;

		if (!Misc::isMobileMode() && isset($desktopCategoryUnits[self::$mTopLevelCategory][$type])) {
			$channels[] = $desktopCategoryUnits[self::$mTopLevelCategory][$type];
		}

		foreach (self::$mGlobalChannels as $c) {
			$channels[] = $c;
		}
		foreach (self::$mGlobalComments as $c) {
			$comments[] = $c;
		}

		// do the categories
		// Elizabeth said this is in used as of 8/27/2012
		$tree = CategoryHelper::getCurrentParentCategoryTree();
		$tree = CategoryHelper::flattenCategoryTree($tree);
		$tree = CategoryHelper::cleanUpCategoryTree($tree);

		$map = self::getCategoryChannelMap();
		foreach ($tree as $cat) {
			if (isset($map[$cat])) {
				$channels[] = $map[$cat];
				$comments[] = $cat;
			}
		}

		if ($wgTitle->inNamespace(NS_SPECIAL))
			$channels[] = "9363314463";
		else
			$channels[] = $namespace[$wgTitle->getNamespace()];
		if ($wgTitle->inNamespace(NS_MAIN)) {
			$comments[] = "Main namespace";
		} else {
			$comments[] = $wgLang->getNsText($wgTitle->getNamespace());
		}

		// TEST CHANNELS
		//if ($wgTitle->inNamespace(NS_MAIN) && $id % 2 == 0) {
		if ($wgTitle->inNamespace(NS_SPECIAL) && $wgTitle->getText() == "Search") {
			$channels[]  = '8241181057';
			$comments[]  = 'Search page';
		}

		$result = array(implode("+", $channels), implode(", ", $comments));
		return $result;
	}

	function getInternationalChannels() {
		global $wgTitle;

		$channels = array();

		if ($wgTitle->inNamespace(NS_MAIN)) {
            $dbw = wfGetDB(DB_MASTER);
            $minrev = $dbw->selectField('revision', 'min(rev_id)', array('rev_page'=>$wgTitle->getArticleID()), __METHOD__);
			$details = $dbw->selectRow('revision', array('rev_user_text', 'rev_timestamp'), array('rev_id'=>$minrev), __METHOD__);
			$fe = $details->rev_user_text;

			$ts = $details->rev_timestamp;

            if (in_array($fe, array('Wilfredor', 'WikiHow Traduce')) ){ //spanish
               	$channels[] = "3957522669";
				if (preg_match("@^2011(01|02|03)@", $ts)) //2011 first quarter
					$channels[] = "6251979379";
				elseif (preg_match("@^2011(04|05|06)@", $ts)) //2011 second quarter
					$channels[] = "7728712578";
				elseif (preg_match("@^2011(07|08|09)@", $ts)) //2011 third quarter
					$channels[] = "9205445776";
				elseif (preg_match("@^2011(10|11|12)@", $ts)) //2011 fourth quarter
					$channels[] = "1682178973";
				elseif (preg_match("@^2012(01|02|03)@", $ts)) //2012 first quarter
					$channels[] = "1682178973";
				elseif (preg_match("@^2012(04|05|06)@", $ts)) //2012 second quarter
					$channels[] = "4635645374";
				elseif (preg_match("@^2012(07|08|09)@", $ts)) //2012 third quarter
					$channels[] = "6112378576";
				elseif (preg_match("@^2012(10|11|12)@", $ts)) //2012 fourth quarter
					$channels[] = "7589111773";
				elseif (preg_match("@^2013(01|02|03)@", $ts)) //2013 first quarter
					$channels[] = "9065844978";
				elseif (preg_match("@^2013(04|05|06)@", $ts)) //2013 second quarter
					$channels[] = "1542578170";
				elseif (preg_match("@^2013(07|08|09)@", $ts)) //2013 third quarter
					$channels[] = "3019311371";
				elseif (preg_match("@^2013(10|11|12)@", $ts)) //2013 fourth quarter
					$channels[] = "4496044575";
			} elseif ($fe == "WikiHow Übersetzungen"){ //german, DE
               	$channels[] = "6309209598";
				if (preg_match("@^2011(01|02|03)@", $ts)) //2011 first quarter
					$channels[] = "5972777772";
				elseif (preg_match("@^2011(04|05|06)@", $ts)) //2011 second quarter
					$channels[] = "7449510970";
				elseif (preg_match("@^2011(07|08|09)@", $ts)) //2011 third quarter
					$channels[] = "8926244177";
				elseif (preg_match("@^2011(10|11|12)@", $ts)) //2011 fourth quarter
					$channels[] = "1402977376";
				elseif (preg_match("@^2012(01|02|03)@", $ts)) //2012 first quarter
					$channels[] = "2879710572";
				elseif (preg_match("@^2012(04|05|06)@", $ts)) //2012 second quarter
					$channels[] = "4356443778";
				elseif (preg_match("@^2012(07|08|09)@", $ts)) //2012 third quarter
					$channels[] = "5833176975";
				elseif (preg_match("@^2012(10|11|12)@", $ts)) //2012 fourth quarter
					$channels[] = "7309910177";
				elseif (preg_match("@^2013(01|02|03)@", $ts)) //2013 first quarter
					$channels[] = "8786643374";
				elseif (preg_match("@^2013(04|05|06)@", $ts)) //2013 second quarter
					$channels[] = "1263376574";
				elseif (preg_match("@^2013(07|08|09)@", $ts)) //2013 third quarter
					$channels[] = "2740109778";
				elseif (preg_match("@^2013(10|11|12)@", $ts)) //2013 fourth quarter
					$channels[] = "4216842972";

            } elseif ($fe == "Traduções wikiHow"){ //PT
                $channels[] = "3705134139";
				if (preg_match("@^2012(01|02|03)@", $ts)) //2012 first quarter
					$channels[] = "5693576175";
				elseif (preg_match("@^2012(04|05|06)@", $ts)) //2012 second quarter
					$channels[] = "7170309370";
				elseif (preg_match("@^2012(07|08|09)@", $ts)) //2012 third quarter
					$channels[] = "8647042577";
				elseif (preg_match("@^2012(10|11|12)@", $ts)) //2012 fourth quarter
					$channels[] = "1123775770";
				elseif (preg_match("@^2013(01|02|03)@", $ts)) //2013 first quarter
					$channels[] = "2600508979";
				elseif (preg_match("@^2013(04|05|06)@", $ts)) //2013 second quarter
					$channels[] = "4077242175";
				elseif (preg_match("@^2013(07|08|09)@", $ts)) //2013 third quarter
					$channels[] = "5553975370";
				elseif (preg_match("@^2013(10|11|12)@", $ts)) //2013 fourth quarter
					$channels[] = "7030708574";
            } elseif ($fe == "WikiHow Traduction") { //french
				$channels[] = "9278407376";
				if (preg_match("@^2012(10|11|12)@", $ts)) //2012 fourth quarter
					$channels[] = "6891107778";
				elseif (preg_match("@^2013(01|02|03)@", $ts)) //2013 first quarter
					$channels[] = "8367840975";
				elseif (preg_match("@^2013(04|05|06)@", $ts)) //2013 second quarter
					$channels[] = "9844574173";
				elseif (preg_match("@^2013(07|08|09)@", $ts)) //2013 third quarter
					$channels[] = "2321307371";
				elseif (preg_match("@^2013(10|11|12)@", $ts)) //2013 fourth quarter
					$channels[] = "3798040579";
			} elseif ($fe == "WikiHow tradurre") { //italian
				$channels[] = "1323878288";
				if (preg_match("@^2012(10|11|12)@", $ts)) //2012 fourth quarter
					$channels[] = "8507441770";
				elseif (preg_match("@^2013(01|02|03)@", $ts)) //2013 first quarter
					$channels[] = "9984174979";
				elseif (preg_match("@^2013(04|05|06)@", $ts)) //2013 second quarter
					$channels[] = "2460908172";
				elseif (preg_match("@^2013(07|08|09)@", $ts)) //2013 third quarter
					$channels[] = "3937641371";
				elseif (preg_match("@^2013(10|11|12)@", $ts)) //2013 fourth quarter
					$channels[] = "5414374579";
			} elseif ($fe == "WikiHow vertalingen") { //Dutch, NL
				$channels[] = "6514064173";
				if (preg_match("@^2013(01|02|03)@", $ts)) //2013 first quarter
					$channels[] = "4807318578";
				elseif (preg_match("@^2013(04|05|06)@", $ts)) //2013 second quarter
					$channels[] = "6284051773";
				elseif (preg_match("@^2013(07|08|09)@", $ts)) //2013 third quarter
					$channels[] = "7760784972";
				elseif (preg_match("@^2013(10|11|12)@", $ts)) //2013 fourth quarter
					$channels[] = "9237518179";
			}

		}

		$channelString = implode("+", $channels);

		return $channelString;
	}

	public static function getCategoryChannelMap() {
		global $wgMemc;
		$key = wfMemcKey('googlechannel', 'category', 'tree');
		$tree = $wgMemc->get( $key );
		if (!$tree) {
			$tree = array();
			$content = wfMessage('category_ad_channel_map')->inContentLanguage()->text();
			preg_match_all("/^#.*/im", $content, $matches);
			foreach ($matches[0] as $match) {
				$match = str_replace("#", "", $match);
				$cats = explode(",", $match);
				$channel= trim(array_pop($cats));
				foreach ($cats as $c) {
					$c = trim($c);
					if (isset($tree[$c]))
						$tree[$c] .= ",$channel";
					else
						$tree[$c] = $channel;
				}
			}
			$wgMemc->set($key, $tree, time() + 3600);
		}
		return $tree;

	}

	function isRightRailTest() {
		global $wgTitle;

		if ( $wgTitle ) {
			$isTest = $wgTitle->getArticleID() % 2 == 0;
		}

		return $isTest;
	}

	// only supports non english for now, for english it returns nothing
	function getCategoryAd() {
		global $wgLanguageCode, $wgTitle;

		if ( self::isExcluded( $wgTitle ) ) {
			return "";
		}

		$s = "";
		if ( $wgLanguageCode != "en" ) {
			$s = "<div class='side_ad'>" . self::getIntlRightRailDfpUnit() . "</div>";
			$s = "<div class='wh_ad'>" . preg_replace('/\<[\/]?pre[^>]*>/', '', $s) . "</div>";
		}

		return $s;
	}

	function getHeaderBiddingCode() {
		global $wgLanguageCode, $wgTitle;

		$id = 0;
		if ($wgTitle) {
			$id = $wgTitle->getArticleID();
		}

		$bidders = array();

		switch ($wgLanguageCode) {
			case "en":
				$bidders['amazon'] = true;
				$bidders['sovrn'] = true;
				$bidders['index'] = true;
				$bidders['yieldbot'] = false;
				$bidders['districtm'] = false;
				break;
			default:
				$bidders['amazon'] = true;
				break;
		}

		$bidders['timeout'] = self::getTimeout();

		list($bidders['units'], $bidders['targeting']) = self::getDfpInitInfo();

		Mustache_Autoloader::register();
		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);

		return $m->render('headerBidding', $bidders);
	}

	private static function getTimeout() {
		return self::$mTimeout;
	}

	// make sure the dfpunits are set before this is called
	function getDfpInitInfo() {
		$units = array();

		foreach (self::$mDfpUnits as $param) {
			$units[] = array("name" => $param[0], "size" => $param[1], "id" => $param[2], "lazy" => $param[3]);
		}

		$targeting = "googletag.pubads().setTargeting('Categories', ['" . self::$mDesktopDfpCategoryInfo[self::$mTopLevelCategory]['targeting'] . "']);";

		return array($units, $targeting);
	}

	function initIntlRightRailDfpUnit() {
		global $wgLanguageCode, $wgTitle;

		if (self::isExcluded($wgTitle) || $wgLanguageCode == "en") {
			return;
		}

		if (array_key_exists($wgLanguageCode, self::$mIntlRightRailUnits)) {
			self::$mDfpUnits[] = self::$mIntlRightRailUnits[$wgLanguageCode];
		} else {
			self::$mDfpUnits[] = self::$mIntlRightRailUnits['default'];
		}
	}

	public function getLangDFPUnitInfo() {
		global $wgLanguageCode;
		return  self::$mIntlRightRailUnits[$wgLanguageCode];
	}

	function getIntlRightRailDfpUnit() {
		global $wgLanguageCode, $wgTitle;

		if (self::isExcluded($wgTitle) || $wgLanguageCode == "en") {
			return "";
		}

		if (array_key_exists($wgLanguageCode, self::$mIntlRightRailUnits)) {
			$unit = self::$mIntlRightRailUnits[$wgLanguageCode];
		} else {
			$unit = self::$mIntlRightRailUnits['default'];
		}

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'unitName' => $unit[0],
			'unitNumber' => $unit[2],
			'language' => $wgLanguageCode
		));

		return $tmpl->execute('intlImageAd.tmpl.php');
	}

	function getCategoryDfpUnit() {
		global $wgLanguageCode, $wgTitle;

		if (self::isExcluded($wgTitle) || $wgLanguageCode != "en")
			return;

		$categories = array(
				'Arts-and-Entertainment' =>			'IMAGE_RR_ARTS_ENTER',
				'Health' =>							'IMAGE_RR_HEALTH',
				'Relationships' =>					'IMAGE_RR_RELATIONSHIPS',
				'Cars-&-Other-Vehicles' =>			'IMAGE_RR_CARS_VEHICLES',
				'Personal-Care-and-Style' =>		'IMAGE_RR_PERSONAL_STYLE',
				'Computers-and-Electronics' =>		'IMAGE_RR_COMP_ELECTRO',
				'Pets-and-Animals' =>				'IMAGE_RR_PETS_ANIMALS',
				'Education-and-Communications' =>	'IMAGE_RR_EDUCATION_COMM',
				'Philosophy-and-Religion' =>		'IMAGE_RR_PHIL_RELIGION',
				'Family-Life' =>					'IMAGE_RR_FAMILY_LIFE',
				'Finance-and-Business' =>			'IMAGE_RR_FINANCE_BIZ_LEGAL',
				'Sports-and-Fitness' =>				'IMAGE_RR_SPORTS_FITNESS',
				'Food-and-Entertaining' =>			'IMAGE_RR_FOOD_ENTERTAIN',
				'Travel' =>							'IMAGE_RR_TRAVEL',
				'Hobbies-and-Crafts' =>				'IMAGE_RR_HOBBIES_CRAFTS',
				'Work-World' =>						'IMAGE_RR_WORK_WORLD',
				'Home-and-Garden' =>				'IMAGE_RR_HOME_GARDEN',
				'Holidays-and-Traditions' =>		'IMAGE_RR_HOLIDAY_TRADIT',
				'Other' =>							'IMAGE_RR_OTHER',
				'Youth' =>							'IMAGE_RR_YOUTH',
				'WikiHow' =>						'IMAGE_RR_WIKIHOW',
		);

		$catSize = "[300, 250]";
		$catName = "div-gpt-ad-rr-top";
		$catLazyLoad = "false";
		$catUnit = "/".self::$mDesktopDfpAccount."/".$categories[self::$mTopLevelCategory];

		$params = [$catUnit, $catSize, $catName, $catLazyLoad];

		self::$mDfpCategory = $params;
		if (!self::isRightRailTest()) {
			return $params;
		}
	}

	public function initDfpUnit($num) {
		if (($num == 9 || $num == 10) && !self::isRightRailTest()) {
			self::$mDfpUnits[] = self::getUnitParams($num);
		}
	}

	public static function getSearchAds(string $engine, string $query, int $page, int $results) {
		global $wgUser;

		if ($wgUser->isLoggedIn()) {
			return '';
		}

		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()){
			return ''; // No search ads for the Android app
		}

		if ($engine == 'google') {
			return self::getSearchAdsGoogle($query, $page);
		} elseif ($engine == 'yahoo') {
			return self::getSearchAdsYPA($query, $page, $results);
		}

		return '';
	}

	private static function getSearchAdsGoogle(string $query, int $page): string {
		global $wgLanguageCode, $wgIsDevServer;

		if ($wgLanguageCode == 'zh') {
			return '';
		}

		if (SearchAdExclusions::isExcluded($query)) {
			return '';
		}

		$query = LSearch::formatSearchQuery($query);

		$channels = [
			'en' => [ 'desktop' => 2304462817, 'mobile' => 5227630311 ],
			'intl' => [ 'desktop' => 9166875328, 'mobile' => 2932639465 ]
		];
		if ( array_key_exists( $wgLanguageCode, $channels ) ) {
			$channel = $channels[$wgLanguageCode];
		} else {
			$channel = $channels['intl'];
		}
		$channel = $channel[Misc::isMobileMode() ? 'mobile' : 'desktop'];

		$vars = [
			"query" => json_encode($query),
			"lang" => json_encode($wgLanguageCode),
			"page" => json_encode($page),
			"test" => json_encode($wgIsDevServer ? 'on' : 'off'),
			"channel" => json_encode((string)$channel)
		];

		$tmpl = new EasyTemplate(__DIR__); // TODO use mustache
		$tmpl->set_vars($vars);

		return $tmpl->execute('wikihowAdSearchGoogle.tmpl.php');
	}

	private static function getSearchAdsYPA(string $query, int $page, int $results) {
		$vars = [
			'slotIdPrefix' => '',
			'adConfig' => '0000008c4',
			"page" => $page,
			"rangeTop" => $results ? '1-2' : '1-3',
			'query' => json_encode($query),
		];
		if (Misc::isMobileMode()) {
			$vars['slotIdPrefix'] = 'M';
			$vars['adConfig'] = '0000008c5';
		}

		$typeTag = self::getTypeTag();

        Hooks::run( 'WikihowAdsAfterGetTypeTag', array( &$typeTag ) );

		$vars['adTypeTag'] = $typeTag;

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars($vars);

		return $tmpl->execute('wikihowAdSearchYPA.tmpl.php');
	}

	/******
	 *
	 * Function return true if the current
	 * page is even eligible for having ads.
	 * Currently the requirements are:
	 * 1. User is logged out
	 * 2. User is on a Article, Image or Category page
	 * 3. Current page is NOT an index.php
	 * 4. Is not the main page
	 * 5. Action is not edit
	 *
	 * Exceptions
	 * 1. Special:CategoryListing
	 *
	 * In order to turn off ads all together,
	 * simply return false at the start of this
	 * function.
	 *
	 ******/
	public static function isEligibleForAds() {
		global $wgUser, $wgTitle, $wgRequest, $wgOut;

		if (!$wgTitle) //don't want to check if it exists, b/c there are a few special pages that should show ads, and they don't "exist"
			return false;

		$isEligible = true;
		if ($wgUser->getID() != 0 && !GoogleAmp::isAmpMode($wgOut))
			return false;

		$namespace = $wgTitle->getNamespace();
		if ($namespace != NS_MAIN && $namespace != NS_IMAGE && $namespace != NS_CATEGORY)
			$isEligible = false;

		// No ads on mobile category pages
		if ($namespace == NS_CATEGORY && Misc::isMobileMode()) {
			$isEligible = false;
		}

		if ($wgTitle && preg_match("@^/index\.php@", @$_SERVER["REQUEST_URI"]))
			$isEligible = false;

		$action = $wgRequest->getVal('action', 'view');
		if ($action == 'edit')
			$isEligible = false;

		//check if its the main page
		if ($wgTitle
			&& $namespace == NS_MAIN
			&& $wgTitle->getText() == wfMessage('mainpage')->text()
			&& $action == 'view')
		{
			$isEligible = false;
		}

		//now some special exceptions
		$titleText = $wgTitle->getText();
		if ($namespace == NS_SPECIAL &&
			(0 === strpos($titleText, "CategoryListing") ||
			0 === strpos($titleText, "DocViewer") ||
			0 == strpos($titleText, "Quizzes"))) {
			$isEligible = true;
		}

		//check to see if the page is indexed, if its not, then it shouldn't show ads
		$indexed = RobotPolicy::isIndexable($wgTitle, RequestContext::getMain());
		if (!$indexed)
			$isEligible = false;

		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
			$isEligible = false;
		}

		if ( Misc::isAltDomain() ) {
			$currentDomain = AlternateDomain::getCurrentRootDomain();
			if ( $currentDomain == 'wikihow.mom' ) {
				$isEligible = false;
			}
			if ( $currentDomain == 'wikihow.health' ) {
				$isEligible = false;
			}
		}
		return $isEligible;

	}

	public static function setCategories($title = null) {
		global $wgTitle;

		if (self::$mCategoriesSet) {
			return;
		}

		if (!$title) {
			$title = $wgTitle;
		}

		if (!$title || !$title->exists() || !$title->inNamespace(NS_MAIN)) {
			self::$mCategoriesSet = true;
			return;
		}

		$tree = CategoryHelper::getCurrentParentCategoryTree($title);
		if ($tree != null) {
			foreach ($tree as $key => $path) {
				$catString = str_replace("Category:", "", $key);
				self::$mCategories[$catString] = $catString;

				$subtree = CategoryHelper::flattenCategoryTree($path);
				for ($i = 0; $i < count($subtree); $i++) {
					$catString = str_replace("Category:", "", $subtree[$i]);
					self::$mCategories[$catString] = $catString;
				}
			}
		}

		$categories = array(
			'Arts-and-Entertainment',
			'Health',
			'Relationships',
			'Cars-&-Other-Vehicles',
			'Personal-Care-and-Style',
			'Computers-and-Electronics',
			'Pets-and-Animals',
			'Education-and-Communications',
			'Philosophy-and-Religion',
			'Family-Life',
			'Finance-and-Business',
			'Sports-and-Fitness',
			'Food-and-Entertaining',
			'Travel',
			'Hobbies-and-Crafts',
			'Work-World',
			'Home-and-Garden',
			'Holidays-and-Traditions',
			'Other',
			'Youth',
			'WikiHow',
		);

		self::$mTopLevelCategory = "Other";
		foreach ($categories as $category) {
			if (isset(self::$mCategories[$category]) && self::$mCategories[$category] != null) {
				self::$mTopLevelCategory = $category;
				break;
			}
		}

		self::$mCategoriesSet = true;
	}

	// Change any javascript that writes closing html tags as follows:
	// document.write("</div>"); // input
	// document.write("</" + "div>"); // output
	public static function rewriteAdCloseTags($html) {
		$changed = false;
		$lines = explode("\n", $html);
		foreach ($lines as &$line) {
			if (preg_match('@^\s*document\.write\((["\']).*\1\);$@', $line, $m)) {
				$quote = $m[1];
				$line = preg_replace('@(</)([A-Za-z])@', '$1' . $quote . ' + ' . $quote . '$2', $line, -1, $count);
				if ($count > 0) $changed = true;
			}
		}
		if ($changed) {
			return join("\n", $lines);
		} else {
			return $html;
		}
	}

	public function hasIntroAd() {
		global $wgLanguageCode, $wgTitle;

		if (self::isExcluded($wgTitle)) {
			return false;
		}

		if ($wgLanguageCode != "en" || !in_array(self::$mTopLevelCategory, array('Philosophy-and-Religion', 'Holidays-and-Traditions', 'WikiHow', 'Youth'))) {
			return true;
		} else {
			return false;
		}
	}

	public static function getIntroAd() {
		global $wgTitle;

		if (self::isExcluded($wgTitle)) {
			return "";
		}

		if (self::hasIntroAd()) {
			return wikihowAds::getAdUnitPlaceholder("intro");
		} else {
			return "";
		}
	}

	function getIntlBottomRightRail() {
		return self::rewriteAdCloseTags(wfMessage('rightrailtest')->text());
	}

	// not used in production yet
	public static function insertMatchedContentAdDesktop() {
		// TODO check ..  do we need an id here?
		$id = 'someid';
		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'id' => $id,
			'slot' => 4282330575,
		));

		$html = $tmpl->execute('matchedcontentdesktop');
		// TODO check to see if this section exists and create it if not or
		// possibly only insert the ad if the section doesn't exist
		pq("#relatedwikihows *")->remove();
		pq("#relatedwikihows")->append($html);
	}

	private static function getMatchedContentAdMobile( $id ) {
		$tmpl = new EasyTemplate( __DIR__ . "/mobileadtemplates" );
		$tmpl->set_vars(array(
			'id' => $id,
			'slot' => 7407580571,
		));

		$html = $tmpl->execute('matchedcontentmobile');
		return $html;
	}

	// add the mobile ad setup javascript to the output as a head item
	public static function addMobileAdSetup( $out ) {
		$abg = '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>';
		$out->addHeadItem( 'mobileadsetup', $abg );
	}

	private static function getIntlMobileAdData() {
		$introChannel = "";
		$methodChannel = "";
		$relatedChannel = "";

		$data = [
			"channels" => [
				"base" => "",
				'small' => [
					'intro' => $introChannel,
					'method' => $methodChannel,
					'related' => $relatedChannel,
					'tips' => '',
					'warnings' => '',
					'pagebottom' => '',
				],
				'medium' => [
				],
				'large' => [
				]
			],
			"slots" => [
				'small' => [
					'intro' => "2831688978",
					'method' => "6771527778",
					'related' => "9724994176",
					'tips' => "8125162876",
					'warnings' => "4621387358",
					'pagebottom' => "3373074232",
				],
				'medium' => [

				],
				'large' => [
					'intro' => "9046346177",
					'method' => "8248260977",
					'related' => "9724994176",
					'tips' => "8125162876",
					'warnings' => "4621387358",
					'pagebottom' => "3373074232",
				]

			]
		];

		$script = Html::element( 'script', [ 'id' => 'wh_ad_data', 'type'=>'application/json' ], json_encode( $data ) );
		return $script;
	}

	private static function getMobileAdData() {
        global $wgTitle;
		$intro = 1;
		$method = 4;
		$related = 5;
		// use same as related
		$footer = 5;

		$channels = wikihowAds::getCustomGoogleChannels();
        $baseChannels = $channels[0];
		$introChannel = wikihowAds::getMobileChannels( $intro );
		$methodChannel = wikihowAds::getMobileChannels( $method );
		$relatedChannel = wikihowAds::getMobileChannels( $related );
		$footerChannel = wikihowAds::getMobileChannels( $footer );
		$middleRelatedChannel = '';
		$qaChannel = '';
		$tipsChannel = '';
		$warningsChannel = '';
		$pageBottomChannel = '';

		$largeIntroChannel = '';
		$baseLargeChannels = '';

		$pageId = $wgTitle->getArticleID();

		if ( ArticleTagList::hasTag( 'amp_disabled_pages', $pageId ) ) {
			$baseChannels = $baseChannels . "+8411928010";
			$baseLargeChannels = $baseLargeChannels . "+8411928010";
		} else {
			$baseChannels = $baseChannels . "+7928712280";
			$baseLargeChannels = $baseLargeChannels . "+7928712280";
			// this group of pages have adsense on AMP, so we want to put a special channel to measure it
			// and we will put a corresponding channel on the adsense ads
			if ( $pageId % 100 < 10 ) {
				$baseChannels = $baseChannels . "+9252820051";
				$baseLargeChannels = $baseLargeChannels . "+9252820051";
			}
		}

		$extraTestChannels = self::getAdTestChannels();
		$baseChannels = $baseChannels . $extraTestChannels;
		$baseLargeChannels = $baseLargeChannels . $extraTestChannels;

		$data = [
			"channels" => [
				"base" => $baseChannels,
				"baselarge" => $baseLargeChannels,
				'small' => [
					'intro' => $introChannel,
					'method' => $methodChannel,
					'related' => $relatedChannel,
					'footer' => $footerChannel,
					'middlerelated' => $middleRelatedChannel,
					'qa' => $qaChannel,
					'tips' => $tipsChannel,
					'warnings' => $warningsChannel,
					'pagebottom' => $pageBottomChannel,
				],
				'medium' => [
				],
				'large' => [
					'intro' => $largeIntroChannel,
					'method' => '',
					'related' => '',
					'footer' => '',
					'qa' => '',
					'middlerelated' => '',
					'tips' => '',
					'warnings' => '',
					'pagebottom' => '',
				]
			],
			"slots" => [
				'small' => [
					'intro' => "8943394577",
					'method' => "7710650179",
					'related' => "9047782573",
					'footer' => "8862180975",
					'middlerelated' => "3859396687",
					'qa' => "1240030252",
					'tips' => "8787347780",
					'warnings' => "3674621907",
					'pagebottom' => "3788982605",
				],
				'medium' => [
				],
				'large' => [
					'intro' => "5867332578",
					'method' => "4377789372",
					'related' => "5854522578",
					'middlerelated' => "3859396687",
					'qa' => "1240030252",
					'tips' => "8787347780",
					'warnings' => "3674621907",
					'pagebottom' => "3788982605",
					'footer' => "8862180975",
				]

			]
		];

		$script = Html::element( 'script', [ 'id' => 'wh_ad_data', 'type'=>'application/json' ], json_encode( $data ) );

		Hooks::run( "WikihowAdsAfterGetMobileAdData", array( &$script ) );

		return $script;
	}

	private static function insertMobileAdSetup( $intl ) {
		// get the data which defines the slots and channels
		$data = "";
		// then add the setup js which reads the ad data
		$script = Misc::getEmbedFile( 'js', __DIR__ . "/mobileAdSetup.js" );
		if ( $intl ) {
			$data = self::getIntlMobileAdData();
			$script .= "window.intlAds = true;";
		} else {
			$data = self::getMobileAdData();
		}

		// wrap the script in script tags
		$script = Html::inlineScript( $script );

		// TODO add the google adsense code if we add it in head with link rel preload
		//$script .= '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>';

		$html = Html::rawElement( 'div', [ 'id' => 'wh_ad_setup' ], $data . $script );

		pq("#intro")->append($html);
	}

	private static function insertMobileAdIntro() {
		$type = 'intro';
		$id = 'wh_ad_intro';
		$html = Html::rawElement(
			'div',
			[

				'data-type' => $type,
				'class' => 'wh_ad',
				'id' => $id
			]
		);
		$script = Html::inlineScript("WH.mobileads.add('$id');");

		pq("#intro")->append($html.$script);
	}

	private static function insertMobileAdMethods() {
        global $wgTitle;

        $pageId = $wgTitle->getArticleID();

		$clear = Html::rawElement( 'div', [ 'style' => 'clear:left;' ] );

		//ad in last step of each method
		$methodNumber = 1;
		foreach ( pq( ".steps:not('.sample') .steps_list_2 > li:last-child" ) as $node ) {
			$id = 'wh_ad_method'.$methodNumber;
			$attributes = array(
				'id' => $id ,
				'class' => 'method_ad',
				'data-type' => 'method',
			);
			//if ( $pageId == 2053 ) {
				// use gpt tag instead
				//$attributes['data-service'] = 'gpt';
				//$attributes['data-path'] = '/10095428/All_Methods_English_Mobile';
				//$attributes['data-sizes'] = json_encode([300,250]);
				//$attributes['data-service'] = 'gpt';
				//$attributes['data-path'] = '/10095428/AMP_Test_Fluid';
				//$attributes['data-sizes'] = 'fluid';
			//}
			$attributes['data-scroll-load'] = true;

			$html = Html::rawElement( 'div', $attributes, $clear );
			// add the bottom label
			$html .= Html::rawElement( 'div', [ 'class' => 'ad_label_method' ], 'Advertisement' );

			$script = Html::inlineScript("WH.mobileads.add('$id');");
			pq( $node )->append( $html . $script );
			$methodNumber++;
		}

	}

	private static function insertMobileAdScrollTo() {
		global $wgTitle;
		if ( !self::isEligibleForAds() ) {
			return "";
		}

		$type = 'scrollto';
		$id = 'wh_ad_scrollto';
		$attr = array(
				'data-scrollto' => true,
				'data-load-class' => 'wh_ad_footer_show',
				'data-scroll-load' => true,
				'data-type' => $type,
				'data-service' => 'adsense',
				'data-refreshable' => 0,
				'class' => 'wh_ad',
				'id' => $id
				);
		$html .= Html::rawElement( 'div', $attr );
		$html .= Html::inlineScript("WH.mobileads.add('$id');");
		pq("#intro")->append( $html.$script );
	}

	private static function insertMobileAdRelated() {
        global $wgTitle;

        $pageId = $wgTitle->getArticleID();

		$id = 'wh_ad_related';
		$attributes = array(
			'id' => $id ,
			'class' => 'wh_ad',
			'data-type' => 'related',
		);

		$attributes['data-scroll-load'] = true;
		$html = Html::rawElement( 'div', $attributes );
		$html .= Html::rawElement( 'div', [ 'class' => 'ad_label_related' ], 'Advertisement' );

		$script = Html::inlineScript("WH.mobileads.add('$id');");
		$relatedsname = RelatedWikihows::getSectionName();
		if ( pq("#{$relatedsname}")->length ) {
			pq("#{$relatedsname}")->append( $html.$script );
		} elseif ( pq("#relatedwikihows")->length ) {
			pq("#relatedwikihows")->append( $html.$script );
		}
	}

	private static function insertMatchedContentAdMobile() {
		global $wgOut, $wgLanguageCode;

		// for now we do not have this in google amp mode
		if ( GoogleAmp::isAmpMode( $wgOut ) ) {
			return '';
		}


		if ( !pq(".section.articleinfo")->length ) {
			return;
		}

		// create a related wikihow section since we do not have one and put
		// the matched content ad inside it
		$headline = Html::element( "span", array( "class" => "mw-headline", "id" => "Other_wikiHows" ), "Other wikiHows" );
		$content = Html::rawElement( "h2", array(), $headline );
		$matchedContentAd = wikihowAds::getMatchedContentAdMobile( 'relatedwikihows' );
		$content .= $matchedContentAd;
		$contents = Html::rawElement( "div", array( 'class' => array( 'section', 'otherwikihows' ) ), $content );
		$contents = wikihowAds::rewriteAdCloseTags( $contents );
		pq(".section.articleinfo")->before( $contents );
	}


	// public function which will get the mobile ads and insert them into the dom
	// as well as the javascript to load them
	public static function insertMobileAds() {
		global $wgLanguageCode, $wgOut;

		self::setCategories();
		if ( GoogleAmp::isAmpMode( $wgOut ) ) {
			GoogleAmp::insertAMPAds();
			return;
		}

		$intl = $wgLanguageCode != 'en';
		wikihowAds::insertMobileAdSetup( $intl );
		wikihowAds::insertMobileAdIntro();
		wikihowAds::insertMobileAdMethods();

		$relatedsname = RelatedWikihows::getSectionName();
		if ( !pq("#{$relatedsname}")->length && $wgLanguageCode == 'en' ) {
			wikihowAds::insertMatchedContentAdMobile();
		}

		wikihowAds::insertMobileAdRelated();
		if ( self::isExtraAdsTestPage() ) {
			wikihowAds::insertExtraTestAds();
		}
	}

	public static function getMobileFooterAd() {
		if ( !self::isEligibleForAds() ) {
			return "";
		}
		return "";
	}

	public static function getMobileAdAnchor() {
		global $wgTitle;
		if ( !self::isEligibleForAds() ) {
			return "";
		}

		$type = 'footer';
		$id = 'wh_ad_footer';
		$attr = array(
				'data-sticky-footer' => true,
				'data-load-class' => 'wh_ad_footer_show',
				'data-scroll-load' => true,
				'data-type' => $type,
				'data-service' => 'gpt',
				'data-path' => '/10095428/Mobile_Anchor_Unit_No_Refresh',
				'data-sizes' => json_encode([320,50]),
				'data-refreshable' => 0,
				'data-autohide' => 0,
				'data-stickyfooterypos' => 0,
				'class' => 'wh_ad',
				'id' => $id
				);
		$html = Html::rawElement(
				'span',
				[
				'class' => 'footer_ad_close',
				'onclick' => 'var elem = document.getElementById("wh_ad_footer_fixed");elem.parentNode.removeChild(elem);'
				],
				'x'
				);
		$html .= Html::rawElement( 'div', $attr );
		$html .= Html::inlineScript("WH.mobileads.add('$id');");
		$wrapper = Html::rawElement( 'div', ['id'=> 'wh_ad_footer_fixed' ], $html );

		// disabled for now
		$wrapper = "";
		return $wrapper;
	}

	public static function getMobilePageCenterClass() {
		if ( self::getMobileFooterAd() ) {
			return "footerad";
		}

	}

	private static function getTypeTag(): string {
		global $wgLanguageCode;

		$isM = Misc::isMobileMode();
		$lang = $wgLanguageCode;

		if ($lang == 'en')     return $isM ? '__alt__ddc_mobile_wikihow_com' : '__alt__ddc_wikihow_com';
		elseif ($lang == 'ar') return $isM ? '__alt__ddc_arm_wikihow_com'    : '__alt__ddc_ar_wikihow_com';
		elseif ($lang == 'cs') return $isM ? '__alt__ddc_mobile_wikihow_cz'  : '__alt__ddc_wikihow_cz';
		elseif ($lang == 'de') return $isM ? '__alt__ddc_dem_wikihow_com'    : '__alt__ddc_de_wikihow_com';
		elseif ($lang == 'es') return $isM ? '__alt__ddc_esm_wikihow_com'    : '__alt__ddc_es_wikihow_com';
		elseif ($lang == 'fr') return $isM ? '__alt__ddc_frm_wikihow_com'    : '__alt__ddc_fr_wikihow_com';
		elseif ($lang == 'hi') return $isM ? '__alt__ddc_him_wikihow_com'    : '__alt__ddc_hi_wikihow_com';
		elseif ($lang == 'id') return $isM ? '__alt__ddc_idm_wikihow_com'    : '__alt__ddc_id_wikihow_com';
		elseif ($lang == 'it') return $isM ? '__alt__ddc_mobile_wikihow_it'  : '__alt__ddc_wikihow_it';
		elseif ($lang == 'ja') return $isM ? '__alt__ddc_mobile_wikihow_jp'  : '__alt__ddc_wikihow_jp';
		elseif ($lang == 'ko') return $isM ? '__alt__ddc_kom_wikihow_com'    : '__alt__ddc_ko_wikihow_com';
		elseif ($lang == 'nl') return $isM ? '__alt__ddc_nl_mwikihow_com'    : '__alt__ddc_nl_wikihow_com';
		elseif ($lang == 'pt') return $isM ? '__alt__ddc_ptm_wikihow_com'    : '__alt__ddc_pt_wikihow_com';
		elseif ($lang == 'ru') return $isM ? '__alt__ddc_rum_wikihow_com'    : '__alt__ddc_ru_wikihow_com';
		elseif ($lang == 'th') return $isM ? '__alt__ddc_thm_wikihow_com'    : '__alt__ddc_th_wikihow_com';
		elseif ($lang == 'vi') return $isM ? '__alt__ddc_mobile_wikihow_vn'  : '__alt__ddc_wikihow_vn';
		elseif ($lang == 'zh') return $isM ? '__alt__ddc_zhm_wikihow_com'    : '__alt__ddc_zh_wikihow_com';

		return $isM ? '__alt__ddc_mobile_wikihow_com' : '__alt__ddc_wikihow_com';

	}

	private static function isExtraAdsTestPage() {
		global $wgOut, $wgLanguageCode;

		if ( class_exists( 'AlternateDomain' ) && AlternateDomain::onAlternateDomain() ) {
			return false;
		}

		$pageId = 0;
		if ( $wgOut && $wgOut->getTitle() ) {
			$pageId = $wgOut->getTitle()->getArticleID();
		}
		if ( !$pageId ) {
			return false;
		}

		// current ad test is on all pages
		return true;
	}

	private static function insertExtraTestAds() {
		global $wgLanguageCode;
		// TODO in the future we can check the mobile ad setup to see if there is an ad for this position instead of doing this lang check
		$tips = 'tips';
		$warnings = 'warnings';
		if ( $wgLanguageCode == "en" ) {
			wikihowAds::insertMobileAdMiddleRelated();
			wikihowAds::insertMobileAdAtTarget('qa', 'qa' );
		} else {
			$tips = strtolower( wfMessage( $tips )->text() );
			$warnings = strtolower( wfMessage( $warnings )->text() );
		}

		wikihowAds::insertMobileAdAtTarget( 'tips', $tips );
		wikihowAds::insertMobileAdAtTarget( 'warnings', $warnings );
		$bottomAdContainer = Html::element( 'div', ['id' => 'pagebottom'] );
		pq('#article_rating_mobile')->after( $bottomAdContainer );
		wikihowAds::insertMobileAdAtTarget( 'pagebottom', 'pagebottom' );
	}

	private static function getAdTestChannels() {
		global $wgOut, $wgLanguageCode, $wgRequest;

		$channels = '';
		if ($wgLanguageCode != "en") {
			return $channels;
		}

		$pageId = 0;
		if ( $wgOut && $wgOut->getTitle() ) {
			$pageId = $wgOut->getTitle()->getArticleID();
		}
		if ( !$pageId ) {
			return $channels;
		}

		if ( $pageId == 223933 ) {
			$channels .= '+6747118168';
		}

		return $channels;
	}

	private static function insertMobileAdAtTarget( $adName, $target ) {
        global $wgTitle;

        $pageId = $wgTitle->getArticleID();

		$id = 'wh_ad_'.$adName;
		$attributes = array(
			'id' => $id ,
			'class' => 'wh_ad',
			'data-type' => $adName,
		);

		$attributes['data-scroll-load'] = true;
		$html = Html::rawElement( 'div', $attributes );
		$html .= Html::rawElement( 'div', [ 'class' => 'ad_label_method' ], 'Advertisement' );

		$script = Html::inlineScript("WH.mobileads.add('$id');");
		$target = "#".$target;
		if ( pq( $target )->length > 0 ) {
			pq( $target )->append( $html.$script );
		}
	}

	private static function insertMobileAdMiddleRelated() {
        global $wgTitle;

        $pageId = $wgTitle->getArticleID();

		$id = 'wh_ad_middle_related';
		$attributes = array(
			'id' => $id ,
			'class' => 'wh_ad',
			'data-type' => 'middlerelated',
		);

		$attributes['data-scroll-load'] = true;
		$html = Html::rawElement( 'div', $attributes );

		$script = Html::inlineScript("WH.mobileads.add('$id');");

		$target = "#relatedwikihows";
		if ( pq( $target )->length < 1 ) {
			$relatedsname = RelatedWikihows::getSectionName();
			$target = "#".$relatedsname;
		}
		if ( pq( $target )->length > 0 ) {
			pq( $target )->find( '.related-article:eq(1)' )->after( $html.$script );
		}
	}

}

