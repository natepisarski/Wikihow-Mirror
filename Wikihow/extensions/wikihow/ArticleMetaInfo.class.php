<?php

/* DB schema
 *
 CREATE TABLE article_meta_info (
   ami_id int unsigned not null,
   ami_namespace int unsigned not null default 0,
   ami_title varchar(255) not null default '',
   ami_updated varchar(14) not null default '',
   ami_desc_style tinyint(1) not null default 1,
   ami_desc varchar(1024) not null default '',
   ami_facebook_desc varchar(1024) not null default '',
   ami_video varchar(255) not null default '',
   ami_summary_video varchar(255) not null default '',
   ami_summary_video_updated varchar(14) not null default '',
   ami_img varchar(255) default null,
   primary key (ami_id),
   KEY (ami_summary_video_updated)
 );
 *
 */

/**
 * Controls the html meta descriptions that relate to Google and Facebook
 * in the head of all article pages.
 *
 * Follows something like the active record pattern.
 */
class ArticleMetaInfo {
	static $dbr = null,
		$dbw = null;

	static $wgTitleAMIcache = null;
	static $wgTitleAmiImageName = null;

	var $title = null,
		$articleID = 0,
		$namespace = NS_MAIN,
		$titleText = '',
		$wikitext = '',
		$cachekey = '',
		$isMaintenance = false,
		$row = null;

	const MAX_DESC_LENGTH = 240;
	const SHORT_DESC_LENGTH = 160;
	// Adding a new, longer meta description length since Google may
	// start supporting that soon. - April 2018
	const NEW_LONGER_DESC_LENGTH = 320;

	const DESC_STYLE_NOT_SPECIFIED = -1;
	const DESC_STYLE_ORIGINAL = 0;
	const DESC_STYLE_INTRO = 1;
	const DESC_STYLE_STEP1 = 2;
	const DESC_STYLE_EDITED = 3;
	const DESC_STYLE_INTRO_NO_TITLE = 4;
	const DESC_STYLE_FACEBOOK_DEFAULT = 4; // SAME AS ABOVE
	const DESC_STYLE_HELPFUL = 5;
	const DESC_STYLE_SIMPLE_EASY = 6;
	const DESC_STYLE_SIMPLE = 7;
	const DESC_STYLE_EASY = 8;
	const DESC_STYLE_SHORT = 9;
	const DESC_STYLE_NEW_LONGER = 10;

	const SUMMARY_VIDEO_UPDATED_KEY = 'ArticleMetaInfo:ami_summary_video_updated';

	// this list of pageids was provided by Chris to Reuben on 6/14/2018 as part
	// of a way to test and slowly roll out longer meta descriptions.
	//
	// Note: if you need to change this list, you should delete the existing rows
	// of any changed articles from the article_meta_info table and restart memcached.
	static $longDescTest = [
		1404977 => true,	1194030 => true,	1331675 => true,	1829689 => true,	3316272 => true,	3530104 => true,
		9474195 => true,	148677 => true,		1108880 => true,	2480560 => true,	4515975 => true,	2479722 => true,
		305266 => true,		1165070 => true,	152674 => true,		466584 => true,		1545105 => true,	1022005 => true,
		4041 => true,		2699109 => true,	2130069 => true,	37277 => true,		547558 => true,		430804 => true,
		2036 => true,		1375335 => true,	9144766 => true,	828107 => true,		81328 => true,		590993 => true,
		1658665 => true,	204663 => true,		147355 => true,		1556237 => true,	47414 => true,		214488 => true,
		3570172 => true,	348545 => true,		528247 => true,		970390 => true,		382602 => true,		60392 => true,
		85460 => true,		1384999 => true,	200972 => true,		7458254 => true,	742457 => true,		956388 => true,
		8134122 => true,	1609149 => true,	45892 => true,		808266 => true,		225053 => true,		56817 => true,
		109796 => true,		676562 => true,		335167 => true,		1348508 => true,	2010847 => true,	172727 => true,
		135538 => true,		2418 => true,		629029 => true,		37989 => true,		58062 => true,		36292 => true,
		2571097 => true,	407076 => true,		2248864 => true,	684261 => true,		338822 => true,		1182699 => true,
		309622 => true,		3311095 => true,	2723891 => true,	6334 => true,		1509779 => true,	4259681 => true,
		587631 => true,		712871 => true,		4706440 => true,	389047 => true,		1122043 => true,	1354910 => true,
		15092 => true,		4120829 => true,	5563130 => true,	617131 => true,		44699 => true,		1718 => true,
		1225029 => true,	38713 => true,		663999 => true,		9930 => true,		1627394 => true,	318690 => true,
		1375646 => true,	503841 => true,		2723282 => true,	1341769 => true,	4259630 => true,	1389465 => true,
		1235320 => true,	24676 => true,		417174 => true,		35395 => true,		1192472 => true,	1282208 => true,
		596761 => true,		1325417 => true,	1537715 => true,	1204480 => true,	3461520 => true,	112640 => true,
		3031824 => true,	599165 => true,		18378 => true,		1705067 => true,	31630 => true,		1385289 => true,
		1103846 => true,	2635004 => true,	1338924 => true,	1204965 => true,	2261293 => true,	3041819 => true,
		352579 => true,		2664 => true,		1301645 => true,	1367729 => true,	388034 => true,		1421155 => true,
		1353321 => true,	36378 => true,		1271477 => true,	4578215 => true,	1629330 => true,	14791 => true,
		68674 => true,		306243 => true,		1210500 => true,	1324976 => true,	1580161 => true,	3587818 => true,
		483613 => true,		1862775 => true,	31352 => true,		3245748 => true,	702507 => true,		7303925 => true,
		1234369 => true,	691816 => true,		46888 => true,		64180 => true,		7802338 => true,	336487 => true,
		1147798 => true,	1586900 => true,	950576 => true,		115935 => true,		737550 => true,		1663820 => true,
		2656715 => true,	486201 => true,		4714451 => true,	1301623 => true,	546973 => true,		397019 => true,
		6356645 => true,	65518 => true,		388043 => true,		1477955 => true,	3728103 => true,	1597655 => true,
		316434 => true,		36903 => true,		8834288 => true,	1250954 => true,	3132373 => true,	329246 => true,
		1134173 => true,	80896 => true,		1361479 => true,	5144933 => true,	2160347 => true,	4634448 => true,
		73811 => true,		4721 => true,		2574069 => true,	896695 => true,		2669742 => true,	1598050 => true,
		1531117 => true,	44804 => true,		978810 => true,		18519 => true,		1036739 => true,	399095 => true,
		794261 => true,		1343454 => true,	2745657 => true,	3447318 => true,	424389 => true,		566907 => true,
		92254 => true,		1212119 => true,	1692063 => true,	7680767 => true,	42831 => true,		1784036 => true,
		3558927 => true,	90913 => true,		316843 => true,		241986 => true,		1375379 => true,	636488 => true,
		9457491 => true,	25850 => true,		165879 => true,		2220185 => true,	527030 => true,		1290501 => true,
		706747 => true,		174650 => true,		141421 => true,		1107829 => true,	2657511 => true,	4416390 => true,
		2134188 => true,	2919965 => true,	10605 => true,		3109765 => true,	3840 => true,		663648 => true,
		1160300 => true,	3891872 => true,	35948 => true,		1763728 => true,	24554 => true,		5707731 => true,
		1354528 => true,	996403 => true,		64334 => true,		42597 => true,		63782 => true,		2311269 => true,
		9352045 => true,	1330802 => true,	87096 => true,		887468 => true,		25347 => true,		2573826 => true,
		1577814 => true,	1342256 => true,	2687805 => true,	3946418 => true,	836854 => true,		29471 => true,
		2601824 => true,	1119425 => true,	443561 => true,		665341 => true,		936657 => true,		707567 => true,
		831698 => true,		1389468 => true,	3698458 => true,	296363 => true,		2095 => true,		655062 => true,
		150461 => true,		1075987 => true,	1711030 => true,	533845 => true,		3284662 => true,	855309 => true,
		1335690 => true,	1335261 => true,	714502 => true,		377063 => true,		396021 => true,		1458164 => true,
		1422277 => true,	34801 => true,		139066 => true,		466519 => true,		446601 => true,		5392923 => true,
		3918 => true,		1535303 => true,	443139 => true,		321944 => true,		2723289 => true,	1396239 => true,
		7233424 => true,	1003309 => true,	1398927 => true,	3149756 => true,	6580559 => true,	2712275 => true,
		60324 => true,		1187429 => true,	1410970 => true,	1181535 => true,	9048698 => true,	1199904 => true,
		153296 => true,		60407 => true,		1375679 => true,	54683 => true,		43351 => true,		47029 => true,
		281660 => true,		38574 => true,		28234 => true,		23209 => true,		229431 => true,		455661 => true,
		3093312 => true,	4325198 => true,	1070959 => true,	176546 => true,		2799974 => true,	1139369 => true,
		2232287 => true,	703182 => true,		3286793 => true,	719841 => true,		1126247 => true,	1851076 => true,
		1168842 => true,	381977 => true,		49103 => true,		49347 => true,		680027 => true,		1151603 => true,
		5322 => true,		590218 => true,		39894 => true,		26412 => true,		1820262 => true,	68528 => true,
		684691 => true,		4773161 => true,	3311560 => true,	2305134 => true,	270713 => true,		9847 => true,
		228940 => true,		381698 => true,		1099796 => true,	695480 => true,		243204 => true,		381141 => true,
		149535 => true,		1532061 => true,	1787468 => true,	3038371 => true,	1695410 => true,	715172 => true,
		8505 => true,		198046 => true,		1375490 => true,	8610871 => true,	548714 => true,		952188 => true,
		12356 => true,		786676 => true,		1225693 => true,	108473 => true,		1467162 => true,	3006161 => true,
		4627737 => true,	1311007 => true,	92884 => true,		5324813 => true,	3476 => true,		3976 => true,
		306640 => true,		2648457 => true,	1796664 => true,	4882367 => true,	5350049 => true,	1325357 => true,
		640938 => true,		683761 => true,		16896 => true,		2659926 => true,	5064 => true,		5382833 => true,
		9430786 => true,	1354379 => true,	366906 => true,		416551 => true,		305190 => true,		592021 => true,
		156053 => true,		3571241 => true,	318995 => true,		4633425 => true,	4810965 => true,	1479918 => true,
		1472244 => true,	540167 => true,		9169571 => true,	888438 => true,		1384754 => true,	2978741 => true,
		171769 => true,		603132 => true,		3193385 => true,	38515 => true,		1579123 => true,	4221564 => true,
		1383341 => true,	3419759 => true,	1648 => true,		38212 => true,		1192450 => true,	4697905 => true,
		1199903 => true,	955715 => true,		1328475 => true,	699824 => true,		58959 => true,		1135248 => true,
		865895 => true,		1370223 => true,	4191 => true,		3556651 => true,	4282985 => true,	813757 => true,
		34273 => true,		1017005 => true,	1387401 => true,	99723 => true,		1442567 => true,	407996 => true,
		1027621 => true,	1234268 => true,	78305 => true,		1068179 => true,	19396 => true,		3355898 => true,
		62981 => true,		1338593 => true,	221637 => true,		594604 => true,		5328670 => true,	1682351 => true,
		164692 => true,		645669 => true,		13457 => true,		1077006 => true,	1336502 => true,	5601637 => true,
		2267606 => true,	448751 => true,		758960 => true,		1651062 => true,	644809 => true,		31814 => true,
		4347 => true,		306508 => true,		905845 => true,		297647 => true,		239515 => true,		7438485 => true,
		1053354 => true,	1016812 => true,	1349608 => true,	1004715 => true,	92360 => true,		22372 => true,
		3946391 => true,	9610166 => true,	6541 => true,		2669894 => true,	21944 => true,		176879 => true,
		157285 => true,		1509062 => true,	1261558 => true,	1454213 => true,	4860012 => true,	3946349 => true,
		6904660 => true,	27112 => true,		1308628 => true,	1401651 => true,	32924 => true,		36417 => true,
		1428945 => true,	212825 => true,		47746 => true,		415828 => true,		1339002 => true,	3057429 => true,
		190060 => true,		399788 => true,		20398 => true,		1281453 => true,	407333 => true,		4859984 => true,
		1657098 => true,	71289 => true,		469094 => true,		1179333 => true,	2692826 => true,	86295 => true,
		7066 => true,		1829686 => true,	1761428 => true,	9122306 => true,	123539 => true,		117632 => true,
		1546345 => true,	190660 => true,		1374113 => true,	453904 => true,		4704506 => true,	580826 => true,
		598438 => true,		4203373 => true,	1376285 => true,	260966 => true,		359468 => true,		2690084 => true,
		6255650 => true,	107652 => true,		1009261 => true,	6253609 => true,	1322759 => true,	1165322 => true,
		1555561 => true,	1344238 => true,	528995 => true,		6531644 => true,	2850731 => true,	598879 => true,
		1378080 => true,	9470959 => true,	72991 => true,		2858281 => true,	1553523 => true,	18874 => true,
		3355955 => true,	38755 => true,		237192 => true,		7231469 => true,	8015105 => true,	70933 => true,
		15437 => true,		1352423 => true,	696130 => true,		1231075 => true,	472670 => true,		73782 => true,
		1375590 => true,	30437 => true,		1202189 => true,	125914 => true,		1187172 => true,	690055 => true,
		46472 => true,		1377942 => true,	3853841 => true,	66015 => true,		3038653 => true,	71474 => true,
		3880210 => true,	297740 => true,		177703 => true,		3150168 => true,	1114459 => true,	285971 => true,
		23171 => true,		221998 => true,		80588 => true,		973632 => true,		939775 => true,		1883341 => true,
		676865 => true,		2368759 => true,	3904307 => true,	11911 => true,		8642 => true,		144027 => true,
		8632943 => true,	1379917 => true,	1046194 => true,	6913488 => true,	1625006 => true,	3164619 => true,
		1152649 => true,	4383918 => true,	311062 => true,		1821900 => true,	4951890 => true,	121128 => true,
		1181542 => true,	8663146 => true,	284203 => true,		239987 => true,		24621 => true,		1566422 => true,
		8756023 => true,	88372 => true,		75763 => true,		148239 => true,		369130 => true,		4552904 => true,
		1403634 => true,	169274 => true,		43296 => true,		688393 => true,		4924262 => true,	565606 => true,
		1794018 => true,	1075024 => true,	90909 => true,		124998 => true,		1198617 => true,	1380251 => true,
		684285 => true,		1134957 => true,	138563 => true,		40992 => true,		1639330 => true,	4745221 => true,
		408372 => true,		3785618 => true,	156481 => true,		1379307 => true,	1370286 => true,	3563505 => true,
		839613 => true,		1499338 => true,	9146811 => true,	6531279 => true,	232985 => true,		2788632 => true,
		2239337 => true,	32785 => true,		1556543 => true,	3356074 => true,	1612356 => true,	23830 => true,
		8861363 => true,	8551833 => true,	8758729 => true,	247048 => true,		1408343 => true,	65596 => true,
		4431670 => true,	308260 => true,		123196 => true,		1385562 => true,	234816 => true,		2214566 => true,
		3202 => true,		2548433 => true,	2487984 => true,	2856 => true,		963748 => true,		508398 => true,
		33685 => true,		6610686 => true,	55698 => true,		4561698 => true,	2776053 => true,	774512 => true,
		1903 => true,		3540129 => true,	2155671 => true,	157576 => true,		7936 => true,		8570859 => true,
		3311357 => true,	1367402 => true,	554320 => true,		7419780 => true,	1893299 => true,	32119 => true,
		389944 => true,		1353911 => true,	695220 => true,		1579738 => true,	1174007 => true,	134938 => true,
		153187 => true,		4614251 => true,	9232113 => true,	753319 => true,		4487 => true,		4322 => true,
		81289 => true,		8663137 => true,	2568584 => true,	729397 => true,		740809 => true,		1857419 => true,
		1465787 => true,	173848 => true,		4273 => true,		619197 => true,		96257 => true,		1106639 => true,
		2897060 => true,	9328194 => true,	87029 => true,		2339691 => true,	2457267 => true,	136086 => true,
		314296 => true,		705412 => true,		278527 => true,		134953 => true,		1011031 => true,	593112 => true,
		1858723 => true,	2321667 => true,	340745 => true,		541135 => true,		324395 => true,		3034151 => true,
		1353532 => true,	561616 => true,		562287 => true,		3192558 => true,	44072 => true,		3311112 => true,
		1069191 => true,	102262 => true,		5034810 => true,	47076 => true,		2909782 => true,	382793 => true,
		1367752 => true,	1215590 => true,	543639 => true,		3279664 => true,	11014 => true,		6607011 => true,
		317319 => true,		1422134 => true,	1399261 => true,	189146 => true,		533275 => true,		206255 => true,
		105792 => true,		9457725 => true,	5147458 => true,	2015949 => true,	277720 => true,		3404746 => true,
		2563832 => true,	26902 => true,		237121 => true,		3158978 => true,	2465687 => true,	7654290 => true,
		27382 => true,		3077659 => true,	736165 => true,		52399 => true,		4259744 => true,	11468 => true,
		1215175 => true,	1065184 => true,	103572 => true,		51392 => true,		481445 => true,		719463 => true,
		2552050 => true,	34784 => true,		537589 => true,		4157819 => true,	1128273 => true,	1994573 => true,
		195639 => true,		3071102 => true,	1877198 => true,	1652052 => true,	966563 => true,		35298 => true,
		1337257 => true,	143655 => true,		5034759 => true,	1544758 => true,	2580187 => true,	1581205 => true,
		190634 => true,		475731 => true,		9475108 => true,	738592 => true,		1539453 => true,	1662292 => true,
		45999 => true,		10582 => true,		708208 => true,		3410938 => true,	83994 => true,		70965 => true,
		9474911 => true,	27642 => true,		6077558 => true,	59980 => true,		732080 => true,		122895 => true,
		11807 => true,		2060743 => true,	1257882 => true,	16670 => true,		1406062 => true,	180544 => true,
		357847 => true,		1381868 => true,	235699 => true,		1306060 => true,	115297 => true,		140091 => true,
		3114907 => true,	376804 => true,		30937 => true,		1332841 => true,	3655531 => true,	1609032 => true,
		9440088 => true,	588662 => true,		1068174 => true,	189018 => true,		252177 => true,		124848 => true,
		210517 => true,		908142 => true,		223397 => true,		282285 => true,		13013 => true,		3505101 => true,
		2339236 => true,	289363 => true,		2580939 => true,	1451210 => true,	1609148 => true,	1682019 => true,
		328059 => true,		1628328 => true,	6324 => true,		2286665 => true,	2866514 => true,	123234 => true,
		1261124 => true,	25611 => true,		50061 => true,		1438941 => true,	4333481 => true,	235416 => true,
		451821 => true,		39468 => true,		3743926 => true,	3282572 => true,	429708 => true,		5128995 => true,
		900664 => true,		196848 => true,		7298 => true,		37464 => true,		4511727 => true,	3718558 => true,
		4328322 => true,	52103 => true,		976706 => true,		83659 => true,		5900 => true,		8552885 => true,
		2513141 => true,	988879 => true,		3344959 => true,	86560 => true,		706596 => true,		169325 => true,
		9136676 => true,	268951 => true,		35104 => true,		2215187 => true,	129431 => true,		554925 => true,
		1411574 => true,	17792 => true,		579823 => true,		73065 => true,		145979 => true,		2816234 => true,
		1362046 => true,	270903 => true,		1452194 => true,	134577 => true,		1261910 => true,
		173391 => true,		1124945 => true,	760865 => true,		5033867 => true,	1598776 => true,
	];

	public function __construct($title, $isMaintenance = false) {
		$this->title = $title;
		$this->articleID = $title->getArticleID();
		$this->namespace = $title->getNamespace();
		$this->titleText = $title->getText();
		$this->isMaintenance = $isMaintenance;
		$this->cachekey = wfMemcKey('metadata2', $this->namespace, $this->articleID);
	}

	public function updateLastVideoPath( $videoPath ) {
		$this->loadInfo();
		if ( $this->row['ami_video'] != $videoPath ) {
			$this->row['ami_video'] = $videoPath;
			$this->saveInfo();
		}
	}

	public function updateSummaryVideoPath( $videoPath = '' ) {
		global $wgMemc;

		$this->loadInfo();
		if ( $this->row['ami_summary_video'] != $videoPath ) {
			$this->row['ami_summary_video'] = $videoPath;
			$this->row['ami_summary_video_updated'] = wfTimestampNow(TS_MW);
			$this->saveInfo();
			$wgMemc->delete( self::SUMMARY_VIDEO_UPDATED_KEY );
		}
	}

	public static function getLatestSummaryVideoUpdate() {
		global $wgMemc;

		$value = $wgMemc->get( self::SUMMARY_VIDEO_UPDATED_KEY );
		if ( $value === false ) {
			$dbr = wfGetDB( DB_REPLICA );
			$sql = 'SELECT MAX(ami_summary_video_updated) as latest_update FROM article_meta_info;';
			$res = $dbr->query( $sql, __METHOD__ );
			$row = $dbr->fetchRow( $res );
			$value = $row['latest_update'];
			$wgMemc->set( self::SUMMARY_VIDEO_UPDATED_KEY, $value );
		}

		return $value;
	}

	public function getVideo() {
		$this->loadInfo();
		return $this->row['ami_video'];
	}

	public static function getGif( $title ) {
		$result = '';
		$ami = new ArticleMetaInfo( $title );
		$video = $ami->getVideo();
		if ( !$video ) {
			return $result;
		}

		$video = end( explode( '/', substr( $video, 0, -3 ) . 'gif' ) );
		$file = RepoGroup::singleton()->findFile( $video );
		if ( !$file ) {
			return $result;
		}
		$result = $file->getUrl();
		return $result;
	}

	public static function getVideoSrc( $title ) {
		$result = '';
		$ami = new ArticleMetaInfo( $title );
		$result = $ami->getVideo();
		return $result;
	}

	/**
	 * Refresh the metadata after the article edit is patrolled, good revision is updated
	 * and before squid is purged. See GoodRevision::onMarkPatrolled for more details.
	 */
	public static function refreshMetaDataCallback($article) {
		$title = $article->getTitle();
		if ($title
			&& $title->exists()
			&& $title->inNamespace(NS_MAIN))
		{
			$meta = new ArticleMetaInfo($title, true);
			$meta->refreshMetaData();
		}
		return true;
	}

	/**
	 * Refresh all computed data about the meta description stuff
	 */
	public function refreshMetaData($style = self::DESC_STYLE_NOT_SPECIFIED) {
		$this->loadInfo();
		$this->updateImage();
		$this->populateDescription($style);
		$this->populateFacebookDescription();
		$this->saveInfo();
	}

	/**
	 * Return the image dimensions, or an empty array if we cannot get either one
	 * load them from db if necessary but try to get them from memcached first
	 */
	public function getImageDimensions() {
		//  load the image from memcached (or from the ami db table as a backup)
		$this->loadInfo();

		// the data will likely be in memcached..but if it is not we have to load it ourselves
		if ( $this->row && @$this->row['ami_img_width'] === null && @$this->row['ami_img_height'] === null ) {
			// update the row with the image dimensions (from wfFindFile)
			if ( $this->updateImageDimensions() ) {
				// we will save the image but we only want to update memcached
				$updateDB = false;
				$this->saveInfo( $updateDB );
			}
		}

		// if we still don't have the dimensions bail out
		if ( !@$this->row['ami_img_width'] || !@$this->row['ami_img_height'] ) {
			return array();
		}

		return array( 'width' => $this->row['ami_img_width'], 'height' => $this->row['ami_img_height'] );
	}

	/**
	 * Return the image meta info for the article record
	 */
	public function getImage() {
		$this->loadInfo();
		// if ami_img == NULL, this field needs to be populated
		if ($this->row && $this->row['ami_img'] === null) {
			if ($this->updateImage()) {
				$this->saveInfo();
			}
		}
		return @$this->row['ami_img'];
	}

	/**
	 * Update the image meta info dimensions for the article record
	 */
	private function updateImageDimensions() {
		$amiImg = $this->row['ami_img'];
		// the ami_image is a string that is a path to the image..
		// it begins with /images/x/xx/ which is 13 characters long
		// so we will try to find the file with this name
		if ( $this->row && $amiImg && strlen( $amiImg ) > 13 ) {
			$imageFile = wfFindFile( substr( $amiImg, 13 ) );

			// only update if we found the image and we have width and height
			if ( $imageFile && $imageFile->getWidth() > 0 && $imageFile->getHeight() > 0 ) {
				$this->row['ami_img_width'] = $imageFile->getWidth();
				$this->row['ami_img_height'] = $imageFile->getHeight();
				return true;
			}
		}
		return false;
	}

	/**
	 * Update the image meta info for the article record
	 */
	private function updateImage() {
		$url = WikihowShare::getShareImage($this->title);
		$this->row['ami_img'] = $url;
		return true;
	}

	/**
	 * Grab the wikitext for the article record
	 */
	private function getArticleWikiText() {
		// cache this if it was already pulled
		if ($this->wikitext) {
			return $this->wikitext;
		}

		if (!$this->title || !$this->title->exists()) {
			//throw new Exception('ArticleMetaInfo: title not found');
			return '';
		}

		$good = GoodRevision::newFromTitle($this->title, $this->articleID);
		$revid = $good ? $good->latestGood() : 0;

		$dbr = $this->getDB();
		$rev = Revision::loadFromTitle($dbr, $this->title, $revid);
		if (!$rev) {
			//throw new Exception('ArticleMetaInfo: could not load revision');
			return '';
		}

		$this->wikitext = ContentHandler::getContentText( $rev->getContent() );
		return $this->wikitext;
	}

	/**
	 * Populate Facebook meta description.
	 */
	private function populateFacebookDescription() {
		$fbstyle = self::DESC_STYLE_FACEBOOK_DEFAULT;
		return $this->populateDescription($fbstyle, true);
	}

	/**
	 * Add a meta description (in one of the styles specified by the row) if
	 * a description is needed.
	 */
	private function populateDescription($forceDesc = self::DESC_STYLE_NOT_SPECIFIED, $facebook = false) {
		$this->loadInfo();

		if (!$facebook &&
			(self::DESC_STYLE_NOT_SPECIFIED == $forceDesc
			 || self::DESC_STYLE_EDITED == $this->row['ami_desc_style'])
		) {
			$style = $this->row['ami_desc_style'];
		} else {
			$style = $forceDesc;
		}

		if (!$facebook) {
			$this->row['ami_desc_style'] = $style;
			list($success, $desc) = $this->buildDescription($style);
			$this->row['ami_desc'] = $desc;
		} else {
			list($success, $desc) = $this->buildDescription($style);
			$this->row['ami_facebook_desc'] = $desc;
		}

		return $success;
	}

	// Used by a maintenance script. This method should never change
	// the database table or the cache.
	public function genNewLongerDescription() {
		list($success, $desc) = $this->buildDescription(self::DESC_STYLE_NEW_LONGER);
		return $success ? $desc : '';
	}

	/**
	 * Sets the meta description in the database to be part of the intro, part
	 * of the first step, or 'original' which is something like "wikiHow
	 * article on How to <title>".
	 */
	private function buildDescription($style) {
		if (self::DESC_STYLE_ORIGINAL == $style) {
			return array(true, '');
		}
		if (self::DESC_STYLE_EDITED == $style) {
			return array(true, $this->row['ami_desc']);
		}
		if (self::DESC_STYLE_NEW_LONGER == $style) {
			$descLength = self::NEW_LONGER_DESC_LENGTH;
		} else {
			$descLength = self::MAX_DESC_LENGTH;
		}

		$wikitext = $this->getArticleWikiText();
		if (!$wikitext) return array(false, '');

		if (self::DESC_STYLE_INTRO == $style
			|| self::DESC_STYLE_INTRO_NO_TITLE == $style
			|| self::DESC_STYLE_NEW_LONGER == $style
		) {
			// grab intro
			$desc = Wikitext::getIntro($wikitext);

			// append first step to intro if intro maybe isn't long enough
			if (strlen($desc) < 2 * $descLength) {
				list($steps, ) = Wikitext::getStepsSection($wikitext);
				if ($steps) {
					$desc .= ' ' . Wikitext::cutFirstStep($steps);
				}
			}
		} elseif (self::DESC_STYLE_STEP1 == $style) {
			// grab steps section
			list($desc, ) = Wikitext::getStepsSection($wikitext);

			// pull out just the first step
			if ($desc) {
				$desc = Wikitext::cutFirstStep($desc);
			} else {
				$desc = Wikitext::getIntro($wikitext);
			}
		} elseif (self::DESC_STYLE_SIMPLE == $style) {
			$desc = "Simple, step-by-step guide on " . wfMessage('howto', $this->titleText)->text() . ". ";
			$desc .= Wikitext::getIntro($wikitext);
			$descLength = self::SHORT_DESC_LENGTH;
		} elseif (self::DESC_STYLE_EASY == $style) {
			$desc = "Easy step-by-step guide on " . wfMessage('howto', $this->titleText)->text() . ". ";
			$desc .= Wikitext::getIntro($wikitext);
			$descLength = self::SHORT_DESC_LENGTH;
		} elseif (self::DESC_STYLE_SIMPLE_EASY == $style) {
			$desc = "Simple, easy-to-follow instructions on " . wfMessage('howto', $this->titleText)->text() . ". ";
			$desc .= Wikitext::getIntro($wikitext);
			$descLength = self::SHORT_DESC_LENGTH;
		} elseif (self::DESC_STYLE_SHORT == $style) {
			// grab intro
			$desc = Wikitext::getIntro($wikitext);
			$descLength = self::SHORT_DESC_LENGTH;
		} elseif (self::DESC_STYLE_HELPFUL == $style) {
			//this one needs to go first b/c if it doesn't meet the
			//conditions, it falls back to the default
			$data = PageHelpfulness::getRatingData($this->articleID);
			$current = array_shift($data);
			if ($current != null && $current->total >= 12 && $current->percent >= 61) {
				$desc = "Step-by-step guide on " . wfMessage('howto', $this->titleText)->text() . ".";
				list($steps, ) = Wikitext::getStepsSection($wikitext);
				if ($steps) {
					$numSteps = Wikitext::countSteps($steps);
					$numPhotos = Wikitext::countImages($wikitext);

					if ($numPhotos > $numSteps/2) {
						$desc .= " With pictures.";
					}
				}

				$desc .= " Rated ";
				if ($current->percent >= 81 ) {
					$desc .= "exceptionally helpful ";
				} else {
					$desc .= "very helpful ";
				}
				$desc .= "by {$current->total} readers.";
				$descLength = self::SHORT_DESC_LENGTH;
			} else {
				// grab intro
				$desc = Wikitext::getIntro($wikitext);

				// append first step to intro if intro maybe isn't long enough
				if (strlen($desc) < 2 * self::MAX_DESC_LENGTH) {
					list($steps, ) = Wikitext::getStepsSection($wikitext);
					if ($steps) {
						$desc .= ' ' . Wikitext::cutFirstStep($steps);
					}
				}
			}
		} else {
			//throw new Exception('ArticleMetaInfo: unknown style');

			return array(false, '');
		}

		$desc = Wikitext::flatten($desc);
		$howto = wfMessage('howto', $this->titleText)->text();
		if ($desc) {
			if (!in_array($style, array(self::DESC_STYLE_INTRO_NO_TITLE, self::DESC_STYLE_HELPFUL, self::DESC_STYLE_SIMPLE, self::DESC_STYLE_EASY, self::DESC_STYLE_SIMPLE_EASY) )) {
				$desc = $howto . '. ' . $desc;
			}
		} else {
			$desc = $howto;
		}

		$desc = self::trimDescription($desc, $descLength);
		return array(true, $desc);
	}
	private static function trimDescription($desc, $maxLength = 0) {
		if ($maxLength <= 0) $maxLength = self::MAX_DESC_LENGTH;
		// Chop desc length at MAX_DESC_LENGTH, and then last space in
		// description so that '...' is added at the end of a word.
		$desc = mb_substr($desc, 0, $maxLength);
		$len = mb_strlen($desc);
		// TODO: mb_strrpos method isn't available for some reason
		$pos = strrpos($desc, ' ');

		if ($len >= $maxLength && $pos !== false) {
			$toAppend = '...';
			if ($len - $pos > 20)  {
				$pos = $len - strlen($toAppend);
			}
			$desc = mb_substr($desc, 0, $pos) . $toAppend;
		}

		return $desc;
	}

	/**
	 * Load and return the <meta name="description" ... descriptive text.
	 */
	public function getDescription() {
		// return copy of description already found
		if ($this->row && $this->row['ami_desc']) {
			return $this->row['ami_desc'];
		}

		$this->loadInfo();

		// needs description
		if ($this->row
			&& $this->row['ami_desc_style'] != self::DESC_STYLE_ORIGINAL
			&& !$this->row['ami_desc'])
		{
			if ($this->populateDescription()) {
				$this->saveInfo();
			}
		}

		return @$this->row['ami_desc'];
	}

	/**
	 * Return the description style used.  Can be compared against the
	 * self::DESC_STYLE_* constants.
	 */
	public function getStyle() {
		$this->loadInfo();
		return $this->row['ami_desc_style'];
	}

	private function defaultStyle() {
		$style = self::DESC_STYLE_INTRO;
		if ( $this->articleID > 0 && isset(self::$longDescTest[$this->articleID]) ) {
			$style = self::DESC_STYLE_NEW_LONGER;
		}
		return $style;
	}

	/**
	 * Returns the description in the "intro" style.  Note that this function
	 * is not optimized for caching and should only be called within the
	 * admin console.
	 */
	public function getDescriptionDefaultStyle() {
		$this->loadInfo();
		list($success, $desc) = $this->buildDescription( $this->defaultStyle() );
		return $desc;
	}

	/**
	 * Set the meta description to a hand-edited one.
	 */
	public function setEditedDescription($desc, $customNote, $customMaxLength = 0) {
		$this->loadInfo();
		$this->row['ami_desc_style'] = self::DESC_STYLE_EDITED;
		$this->row['ami_desc'] = self::trimDescription($desc, $customMaxLength);
		$this->row['ami_edited_note'] = $customNote;
		$this->refreshMetaData();
	}

	public function dbListEditedDescriptions() {
		$dbr = self::getDB();
		$res = $dbr->select('article_meta_info',
			['ami_id', 'ami_desc', 'ami_edited_note'],
			['ami_desc_style' => self::DESC_STYLE_EDITED, "ami_desc != ''"],
			__METHOD__);
		$results = [];
		foreach ($res as $row) {
			$results[] = (array)$row;
		}
		return $results;
	}

	/**
	 * Set the meta description to a hand-edited one.
	 */
	public function resetMetaData() {
		$this->loadInfo();
		$this->row['ami_desc_style'] = $this->defaultStyle();
		$this->row['ami_desc'] = '';
		$this->refreshMetaData();
	}

	/**
	 * Load and return the <meta name="description" ... descriptive text.
	 */
	public function getFacebookDescription() {
		// return copy of description already found
		if ($this->row && $this->row['ami_facebook_desc']) {
			return $this->row['ami_facebook_desc'];
		}

		$this->loadInfo();

		// needs FB description
		if ($this->row && !$this->row['ami_facebook_desc']) {
			if ($this->populateFacebookDescription()) {
				$this->saveInfo();
			}
		}

		return @$this->row['ami_facebook_desc'];
	}

	/**
	 * Retrieve the meta info stored in the database.
	 */
	/*public function getInfo() {
		$this->loadInfo();
		return $this->row;
	}*/

	/**
	 * Create a database handle.  $type can be 'read' or 'write'
	 */
	private function getDB($type = 'read') {
		if ($type == 'write') {
			if (self::$dbw == null) self::$dbw = wfGetDB(DB_MASTER);
			return self::$dbw;
		} elseif ($type == 'read') {
			if (self::$dbr == null) self::$dbr = wfGetDB(DB_REPLICA);
			return self::$dbr;
		} else {
			throw new Exception('unknown DB handle type');
		}
	}

	/**
	 * Load the meta info record from either DB or memcache
	 */
	private function loadInfo() {
		global $wgMemc;

		if ($this->row) return;

		$res = null;
		// Don't pull from cache if maintenance is being performed
		if (!$this->isMaintenance) {
			$res = $wgMemc->get($this->cachekey);
		}

		if (!is_array($res)) {
			$articleID = $this->articleID;
			$namespace = NS_MAIN;
			$dbr = $this->getDB();
			$sql = 'SELECT * FROM article_meta_info WHERE ami_id=' . $dbr->addQuotes($articleID) . ' AND ami_namespace=' . (int)$namespace;
			$res = $dbr->query($sql, __METHOD__);
			$this->row = $dbr->fetchRow($res);

			if (!$this->row) {
				$this->row = array(
					'ami_id' => $articleID,
					'ami_namespace' => (int)$namespace,
					'ami_desc_style' => $this->defaultStyle(),
					'ami_desc' => '',
					'ami_facebook_desc' => '',
				);
			} else {
				foreach ($this->row as $k => $v) {
					if (is_int($k)) {
						unset($this->row[$k]);
					}
				}
			}
			$wgMemc->set($this->cachekey, $this->row);
		} else {
			$this->row = $res;
		}
	}

	/**
	 * Save article meta info to both DB and memcache
	 * params:
	 * updateDB: allows you to only save to memcached
	 * this is useful if you have extra data you want to save in memcached but not in the db table
	 * for example the image dimensions
	 */
	private function saveInfo( $updateDB = true ) {
		global $wgMemc;

		if (empty($this->row)) {
			throw new Exception(__METHOD__ . ': nothing loaded');
		}
		$imgWidth = null;
		$imgHeight = null;

		$this->row['ami_updated'] = wfTimestampNow(TS_MW);

		if (!isset($this->row['ami_title'])) {
			$this->row['ami_title'] = $this->titleText;
		}
		if (!isset($this->row['ami_id'])) {
			$articleID = $this->articleID;
			$this->row['ami_id'] = $articleID;
		}
		if (!isset($this->row['ami_namespace'])) {
			$namespace = $this->namespace;
			$this->row['ami_namespace'] = $namespace;
		}
		if (!isset($this->row['ami_desc_style']) || is_null($this->row['ami_desc_style'])) {
			$this->row['ami_desc_style'] = $this->defaultStyle();
		}

		if ( isset( $this->row['ami_img_width'] ) ) {
			$imgWidth = $this->row['ami_img_width'];
			unset( $this->row['ami_img_width'] );
		}

		if ( isset( $this->row['ami_img_height'] ) ) {
			$imgHeight = $this->row['ami_img_height'];
			unset( $this->row['ami_img_height'] );
		}

		if ( $updateDB == true ) {
			$dbw = $this->getDB('write');
			$sql = 'REPLACE INTO article_meta_info SET ' . $dbw->makeList($this->row, LIST_SET);
			$res = $dbw->query($sql, __METHOD__);
		}

		if ( $imgWidth > 0 && $imgHeight > 0 ) {
			// put the image dimensions into memcache
			$this->row['ami_img_width'] = $imgWidth;
			$this->row['ami_img_height'] = $imgHeight;
		}
		$wgMemc->set($this->cachekey, $this->row);
	}

	private static function getMetaSubcategories($title, $limit = 3) {
		$results = array();
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			array('categorylinks', 'page'),
			array('page_namespace', 'page_title'),
			array('page_id=cl_from', 'page_namespace' => NS_CATEGORY, 'cl_to' => $title->getDBKey()),
			__METHOD__,
			array('ORDER BY' => 'page_counter desc', 'LIMIT' => ($limit + 1) )
		);
		$requests = wfMessage('requests')->text();
		$count = 0;
		foreach ($res as $row) {
			if ($count++ == $limit) break;
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if (strpos($t->getText(), $requests) === false) {
				$results[] = $t->getText();
			}
		}
		return $results;
	}

	// Add these meta properties that the Facebook graph protocol wants
	// https://developers.facebook.com/docs/opengraph/
	public static function addFacebookMetaProperties($titleText) {
		global $wgOut, $wgTitle, $wgRequest;

		$action = $wgRequest->getVal('action', '');
		if (!$wgTitle->inNamespace(NS_MAIN)
			|| $wgTitle->getText() == wfMessage('mainpage')->text()
			|| (!empty($action) && $action != 'view')
			|| !WikihowSkinHelper::shouldShowMetaInfo($wgOut))
		{
			return;
		}

		if ( !Hooks::run( 'ArticleMetaInfoAddFacebookMetaProperties', array() ) ) {
			return;
		}
		$url = $wgTitle->getFullURL('', false, PROTO_CANONICAL);

		$ami = self::getAMICache();
		$fbDesc = $ami->getFacebookDescription();

		$img = $ami->getImage();

		// if this was shared via thumbs up, we want a different description.
		// url will look like this, for example:
		// https://www.wikihow.com/Kiss?fb=t
		if ($wgRequest->getVal('fb', '') == 't') {
			$fbDesc = wfMessage('article_meta_description_facebook', $wgTitle->getText())->text();
			$url .= "?fb=t";
		}


		// If this url isn't a facebook action, make sure the url is formatted appropriately
		if ($wgRequest->getVal('fba','') == 't') {
			$url .= "?fba=t";
		} else {
			// If this url isn't a facebook action, add 'How to ' to the title
			$titleText = wfMessage('howto', $titleText)->text();
		}

		$props = array(
			array( 'property' => 'og:title', 'content' => $titleText ),
			array( 'property' => 'og:type', 'content' => 'article' ),
			array( 'property' => 'og:url', 'content' => $url ),
			array( 'property' => 'og:site_name', 'content' => 'wikiHow' ),
			array( 'property' => 'og:description', 'content' => $fbDesc ),
		);
		if ($img) {
			// Note: we can add multiple copies of this meta tag at some point
			// Note 2: we don't want to use pad*.whstatic.com because we want
			//   these imgs to refresh reasonably often as the page refreshes
			// Note 3: we use a static string for www.wikihow.com here since
			//   the non-English languages need to refer to English
			if ($img) {
				$img = 'https://www.wikihow.com' . $img;
			}

			$props[] = array( 'property' => 'og:image', 'content' => $img );
			$dim = $ami->getImageDimensions();
			if ( @$dim['width'] && @$dim['height'] ) {
				$props[] = array( 'property' => 'og:image:width', 'content' => $dim['width'] );
				$props[] = array( 'property' => 'og:image:height', 'content' => $dim['height'] );
			}
		}

		foreach ($props as $prop) {
			//ENT_HTML5 was added in php 5.4.0 (we aren't there yet)
			//$wgOut->addHeadItem($prop['property'], '<meta property="' . $prop['property'] . '" content="' . htmlspecialchars($prop['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"/>' . "\n");
			$wgOut->addHeadItem($prop['property'], '<meta property="' . $prop['property'] . '" content="' . htmlspecialchars($prop['content'], ENT_QUOTES, 'UTF-8') . '"/>' . "\n");
		}
	}

	public static function getCurrentTitleMetaDescription() {
		global $wgTitle, $wgLanguageCode;

		$return = '';
		if ($wgTitle->inNamespace(NS_MAIN) && $wgTitle->getFullText() == wfMessage('mainpage')->text()) {
			$return = wfMessage('mainpage_meta_description')->text();
		} elseif ($wgTitle->inNamespace(NS_MAIN)) {
			$ami = self::getAMICache();
			$desc = $ami->getDescription();
			if (!$desc) {
				$return = wfMessage('article_meta_description', $wgTitle->getText() )->text();
			} else {
				$return = $desc;
			}
		} elseif ($wgTitle->inNamespace(NS_CATEGORY)) {
			if ($wgLanguageCode == "en" && class_exists('AdminCategoryDescriptions')) {
				return AdminCategoryDescriptions::getCategoryMetaDescription($wgTitle);
			} else {
				// get keywords
				$subcats = self::getMetaSubcategories($wgTitle, 3);
				$keywords = implode(", ", $subcats);
				if ($keywords) {
					$return = wfMessage('category_meta_description', $wgTitle->getText(), $keywords)->text();
				} else {
					$return = wfMessage('subcategory_meta_description', $wgTitle->getText(), $keywords)->text();
				}
			}
		} elseif ($wgTitle->inNamespace(NS_USER)) {
			$desc = ProfileBox::getMetaDesc();
			$return = $desc;
		} elseif ($wgTitle->inNamespace(NS_IMAGE)) {
			$articles = ImageHelper::getLinkedArticles($wgTitle);
			if (count($articles) && $articles[0]) {
				$articleTitle = wfMessage('howto', $articles[0])->text();
				if (preg_match('@Step (\d+)@', $wgTitle->getText(), $m)) {
					$imageNum = '#' . $m[1];
				} else {
					$imageNum = '';
				}
				$return = wfMessage('image_meta_description', $articleTitle, $imageNum)->text();
			} else {
				$return = wfMessage('image_meta_description_no_article', $wgTitle->getText())->text();
			}
		} elseif ( $wgTitle->equals( SpecialPage::getTitleFor('PopularPages') ) ) {
			$return = wfMessage('popularpages_meta_description')->text();
		}

		return $return;
	}

	public static function getCurrentTitleMetaKeywords() {
		global $wgTitle;

		$return = '';
		if ($wgTitle->inNamespace(NS_MAIN) && $wgTitle->getFullText() == wfMessage('mainpage')->text()) {
			$return = wfMessage('mainpage_meta_keywords')->text();
		} elseif ($wgTitle->inNamespace(NS_MAIN)) {
			$return = wfMessage('article_meta_keywords', htmlspecialchars($wgTitle->getText()) )->text();
		} elseif ($wgTitle->inNamespace(NS_CATEGORY)) {
			$subcats = self::getMetaSubcategories($wgTitle, 10);
			$return = implode(", ", $subcats);
			if (!trim($return)) {
				$return = wfMessage( 'category_meta_keywords_default', htmlspecialchars($wgTitle->getText()) )->text();
			}
		} elseif ( $wgTitle->equals( SpecialPage::getTitleFor('PopularPages') ) ) {
			$return = wfMessage('popularpages_meta_keywords')->text();
		}

		return $return;
	}

	public static function addTwitterMetaProperties() {
		global $wgTitle, $wgRequest, $wgOut;

		$action = $wgRequest->getVal('action', 'view');

		if ( !$wgTitle->inNamespace(NS_MAIN)
			|| $action != "view"
			|| !WikihowSkinHelper::shouldShowMetaInfo($wgOut)
		) {
			return;
		}

		if ( !Hooks::run( 'ArticleMetaInfoShowTwitterMetaProperties', array() ) ) {
			return;
		}

		$isMainPage = $wgTitle
			&& $wgTitle->inNamespace(NS_MAIN)
			&& $wgTitle->getText() == wfMessage('mainpage')->inContentLanguage()->text()
			&& $action == 'view';

		if (!self::$wgTitleAMIcache) {
			self::$wgTitleAMIcache = new ArticleMetaInfo($wgTitle);
		}
		$ami = self::$wgTitleAMIcache;

		if ($isMainPage)
			$twitterTitle = "wikiHow";
		else
			$twitterTitle = wfMessage('howto', $ami->titleText)->text();

		if ($isMainPage)
			$twitterDesc = "wikiHow - How to do anything";
		else
			$twitterDesc = $ami->getFacebookDescription();

		if ($isMainPage)
			$twitterImg = "/images/7/71/Wh-logo.jpg";
		else
			$twitterImg = $ami->getImage();

		// Note: we use a static string here since the non-English
		//   languages need to refer to the canonical English domain
		//   for vast majority of images
		if ($twitterImg) {
			$twitterImg = 'https://www.wikihow.com' . $twitterImg;
		}

		$wgOut->addHeadItem('tcard', '<meta name="twitter:card" content="summary_large_image"/>' . "\n");
		$wgOut->addHeadItem('timage', '<meta name="twitter:image:src" content="' . $twitterImg . '"/>' . "\n");
		$wgOut->addHeadItem('tsite', '<meta name="twitter:site" content="@wikihow"/>' . "\n");
		$wgOut->addHeadItem('tdesc', '<meta name="twitter:description" content="' . htmlspecialchars($twitterDesc) . '"/>' . "\n");

		$wgOut->addHeadItem('ttitle', '<meta name="twitter:title" content="' . htmlspecialchars($twitterTitle) . '"/>' . "\n");
		$wgOut->addHeadItem('turl', '<meta name="twitter:url" content="' . $wgTitle->getFullURL('', false, PROTO_CANONICAL) . '"/>' . "\n");

		if ( class_exists( "IOSHelper" ) && $wgTitle->exists() ) {
			$wgOut->addHeadItem('tappname', '<meta name="twitter:app:name:iphone" content="' . IOSHelper::getAppName() . '"/>' . "\n");
			$wgOut->addHeadItem('tappid', '<meta name="twitter:app:id:iphone" content="' . IOSHelper::getAppId() . '"/>' . "\n");
			$wgOut->addHeadItem('tappurl', '<meta name="twitter:app:url:iphone" content="' . IOSHelper::getArticleUrl( $wgTitle ) . '"/>' . "\n");
		}
	}

	public static function getAMICache() {
		global $wgTitle;
		if (!self::$wgTitleAMIcache) {
			self::$wgTitleAMIcache = new ArticleMetaInfo($wgTitle);
		}
		return self::$wgTitleAMIcache;
	}

	public static function addAndroidAppMetaInfo() {
		global $wgTitle, $wgOut;

		$ami = self::getAMICache();
		$img = $ami->getImage();
		if ($img) {
			$img = 'https://www.wikihow.com' . $img;
			$props[] = array( 'name' => 'wh_an:image', 'content' => $img );
		} else {
			$props[] = array( 'name' => 'wh_an:image', 'content' => "" );
		}

		$props[] = array( 'name' => 'wh_an:ns', 'content' => $wgTitle->getNamespace() );

		foreach ($props as $prop) {
			//ENT_HTML5 was added in php 5.4.0 (we aren't there yet)
			//$wgOut->addHeadItem($prop['name'], '<meta name="' . $prop['name'] . '" content="' . htmlspecialchars($prop['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"/>' . "\n");
			$wgOut->addHeadItem($prop['name'], '<meta name="' . $prop['name'] . '" content="' . htmlspecialchars($prop['content'], ENT_QUOTES, 'UTF-8') . '"/>' . "\n");
		}
	}

	/**
	 * Gets the name of the meta image associated with this article
	 * uses static var for wgTitle for speed
	 * and looks up from the DB for non wgTitle
	 *
	 * @return String|null the name of the title image of wgTitle
	 */
	public static function getArticleImageName( $title = null ) {
		global $wgTitle;
		$usingWgTitle = false;

		if ( $wgTitle == $title ) {
			$usingWgTitle = true;
		}

		if ( $title == null ) {
			$title = $wgTitle;
			$usingWgTitle = true;
		}

		// get the static var if exists
		if ( $usingWgTitle && self::$wgTitleAmiImageName ) {
			return self::$wgTitleAmiImageName;
		}

		if ( !$usingWgTitle ) {
			// load from ami db table
			$dbr = wfGetDB(DB_REPLICA);
			$table = 'article_meta_info';
			$var = 'ami_img';
			$cond = array( 'ami_id'  => $title->getArticleID() );
			$imageName = $dbr->selectField( $table, $var, $cond, __METHOD__ );

			return $imageName;
		}

		// get from the AMI class object itself which can regenerate it if missing from db table
		$ami = self::getAMICache();
		if ( !$ami ) {
			return null;
		}

		$amiImage = $ami->getImage();
		self::$wgTitleAmiImageName = $amiImage;
		return $amiImage;

	}

	// gets the article id and defaults to wgtitle
	private static function getArticleID( $title ) {
		if ( !$title ) {
			global $wgTitle;
			$title = $wgTitle;
		}
		return $title->getArticleID();
	}

	public static function getRelatedThumb( $title, $width, $height ) {
		$usePageId = false;
		$crop = true;
		return self::getTitleImageThumb( $title, $width, $height, $usePageId, $crop );
	}

	// get the thumbnail object for the title image of an article
	// defaults to the width of desktop images
	// has optional param to use the page id to generate the new style watermarks
	public static function getTitleImageThumb( $title = null, $width = 728, $height = -1, $usePageId = true, $crop = false ) {

		$pageId = null;
		if ( $usePageId ) {
			$pageId = self::getArticleID( $title );
		}

		// this is the path to the image
		$imageName = self::getArticleImageName( $title );
		if ( !$imageName ) {
			return null;
		}

		// get the final part of the image path which is it's name
		$exploded = explode( '/', $imageName );
		$imageName = end( $exploded );
		if ( !$imageName ) {
			return null;
		}

		// get the file from the image name
		$file = RepoGroup::singleton()->findFile( $imageName );
		if ( !$file ) {
			return null;
		}

		if ( $pageId ) {
			$thumb = $file->getThumbnail( $width, $height, true, $crop, false, $pageId );
			return $thumb;
		}
		// get the thumbnail
		$thumb = $file->getThumbnail( $width, $height, true, $crop );

		return $thumb;
	}

}

