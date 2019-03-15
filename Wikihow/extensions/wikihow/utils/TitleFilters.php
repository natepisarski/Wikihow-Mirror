<?php
/**
 *  Utility class of filters for Title arrays
 */

class TitleFilters {

	const EXPLICIT_AIDS = [
		176663,
		252168,
		150400,
		1053162,
		1522113,
		1413819,
		1263741,
		1940459,
		3395060,
		3479927,
		3566989,
		1863100,
		3568238,
		8917,
		705087,
		2854496,
		203548,
		1099732,
		497548,
		787303,
		1718,
		1323321,
		109585,
		29882,
		18243,
		270480,
		880471,
		1260247,
		28777,
		1715691,
		2670115,
		1540697,
		1857155,
		2688383,
		1300065,
		205114,
		398626,
		1444793,
		192336,
		920818,
		722553,
		2097951,
		2157895,
		2224776,
		717704,
		398881,
		1040461,
		1750743,
		46339,
		403409,
		14997,
		68441,
		226845,
		31814,
		1323324,
		1251061,
		360273,
		339172,
		345276,
		3017964,
		15807,
		20800,
		25470,
		26491,
		32680,
		49133,
		56811,
		24618,
		26444,
		36903,
		14997,
		64055,
		1486373,
		27318,
		47752,
		156425,
		269480,
		689533,
		726983,
		756921,
		806798,
		895523,
		1027621,
		1357318,
		1486373,
		2432238,
		2652547,
		3313618,
		1383676,
		407333,
		6422190,
		5251435,
		82813,
		698490,
		269480,
		717125,
		1138260,
		716916,
		3507446,
		1133428,
		497548,
	];

	const EXPLICIT_AIDS_ALEXA_EN = [
		4149799,
		198597,
		799143,
		2189298,
		22980,
		255329,
		1021881,
		1027621,
		1040461,
		1053162,
		109585,
		1099732,
		1133428,
		1138260,
		1251061,
		1260247,
		1263741,
		1300065,
		1323321,
		1323324,
		1357318,
		1383676,
		1413819,
		1444793,
		1486373,
		14997,
		150400,
		1522113,
		1540697,
		156425,
		15807,
		1715691,
		1718,
		1750743,
		176663,
		18243,
		1857155,
		1863100,
		192336,
		1940459,
		203548,
		205114,
		20800,
		2097951,
		2157895,
		2224776,
		226845,
		2432238,
		24618,
		252168,
		253379,
		25470,
		26444,
		26491,
		2652547,
		2670115,
		2688383,
		269480,
		270480,
		27318,
		2854496,
		28777,
		29882,
		3017964,
		31814,
		3183249,
		32680,
		3313618,
		339172,
		3395060,
		345276,
		3479927,
		3507446,
		3566989,
		3568238,
		360273,
		3648718,
		36903,
		398626,
		398881,
		403409,
		407238,
		407333,
		430804,
		46339,
		47752,
		478009,
		481445,
		49133,
		497548,
		5251435,
		56811,
		5959045,
		59980,
		64055,
		6422190,
		68441,
		689533,
		698490,
		705087,
		716916,
		717125,
		717704,
		722553,
		726983,
		756921,
		787303,
		806798,
		815883,
		82813,
		880471,
		8917,
		895523,
		920818,
		1506880,
		4112738,
		1509350,
		301199,
		2287742,
		2669864,
		3042371,
		1511023,
		598879,
		180109,
		705480,
		690055,
		40037,
		1220680,
		976737,
		2712275,
		4859984,
		1591686,
		3093304,
		1855526,
		2322892,
		3051484,
		536121,
		179507,
		3838938,
		230602,
		434776,
		447817,
		1165322,
		40500,
		650388,
		193686,
		12166,
		1092428,
		834254,
		129781,
		206255,
		3416058,
		11754,
		10268,
		855309,
		1194676,
		11468,
		7375,
		3136265,
		774512,
		69767,
		55488,
		21895,
		1700192,
		156542,
		3616969,
		35245,
		8041,
		2697666,
		156392,
		712729,
		1853668,
		8157,
		655949,
		375362,
		140091,
		1662292,
		697625,
		545724,
		300949,
		1011491,
		286063,
		5630,
		79488,
		13397,
		2660480,
		1756524,
		49547,
		6076,
		7156,
		279994,
		9523,
		58500,
		191738,
		441169,
		37436,
		47533,
		566907,
		32543,
		1184001,
		570803,
		15934,
		382793,
		528879,
		9732,
		220347,
		2320,
		2672961,
		2097494,
		74588,
		670569,
		1883341,
		5878003,
		6261,
		7421,
		17222,
		58668,
		450488,
		581265,
		1226186,
		1274230,
		1300587,
		1320107,
		1335416,
		1358937,
		2286665,
		2681115,
		2733204,
		2780535,
		2806271,
		2947905,
		3026261,
		3093684,
		3093692,
		3093694,
		3260161,
		3267870,
		3269943,
		3370350,
		3423372,
		3447336,
		3453670,
		3563429,
		3684787,
		4258895,
		4667468,
		5497799,
		5894219,
		6476448,
		139237,
		1199894,
		1224625,
		236150,
		552229,
		570257,
		751115,
		789658,
		1469812,
		1555969,
		1563214,
		1778218,
		2568584,
		3506294,
		3833191,
		4374416,
		4375117,
		4790765,
		4982636,
		5350049,
		6693543,
		8774117,
		7786281,
		1178620,
		165565,
		1280972,
		1817758,
		4708385,
		117632,
		3918344,
		4454628,
		5895455,
		7400759,
		3691,
		48353,
		229807,
		420443,
		518819,
		541302,
		725697,
		763089,
		770199,
		888704,
		888860,
		904311,
		992729,
		1156371,
		1212154,
		1247720,
		1606724,
		1630836,
		2170477,
		2212849,
		2327783,
		2553295,
		3888257,
		5018094,
		5852829,
		5857795,
		5889333,
		6040272,
		6052681,
		6178532,
		6399281,
		8034788,
		8720840,
		394567,
		462361,
		1337045,
		3745508,
		5884688,
		6510805,
		10353664,
		34180,
		262650,
		391387,
		1118075,
		1192472,
		1290501,
		1402338,
		1433665,
		1771334,
		2017403,
		2260157,
		3355898,
		3904055,
		4282985,
		4482693,
		4733464,
		4860012,
		4908692,
		5235001,
		5365849,
		5840628,
		6253609,
		6995604,
		7158342,
		7215870,
		7217777,
		7345221,
		7419780,
		7452706,
		7536481,
		7540226,
		7640742,
		8673120,
		8833231,
		9581605,
		9911427,
		208294,
		1215501,
		2076759,
		4259638,
		4660934,
		5640139,
		7223929,
		7683975,
		8733873,
		10568964,
		7509790,
		19966,
		21485,
		31799,
		45274,
		70944,
		287802,
		364638,
		367025,
		433249,
		570707,
		635542,
		679334,
		729986,
		734155,
		744965,
		833320,
		862640,
		974582,
		1631795,
		1818828,
		1884116,
		2000796,
		2765665,
		2780549,
		3192369,
		3311569,
		3321601,
		3523956,
		5563802,
		6305595,
		7555923,
		9619758,
		10138517,
		10423958,
		10708479,
		14380,
		137166,
		158176,
		2210477,
		5592255,
		6742630,
		7237144,
		7258267,
		7359094,
		7546865,
		7626861,
		9686304,
		3014769,
		5548071,
		10548290,
		163787,
		2812796,
		4629000,
		5421385,
		10236311,
		1784067,
		1126451,
		62443,
		66194,
		280243,
		580068,
		1126024,
		1217251,
		1256607,
		1312273,
		1322364,
		1471573,
		1686223,
		2995025,
		3420973,
		3973332,
		5012674,
		5039309,
		5657428,
		5892299,
		7231426,
		7240523,
		7284938,
		8414465,
		9651729,
		325935,
		458061,
		605066,
		693762,
		730799,
		889602,
		1082731,
		1083691,
		1216776,
		1387029,
		1504816,
		1515798,
		1517089,
		2018184,
		2647756,
		2850585,
		2946359,
		2986467,
		3164619,
		4721795,
		4922627,
		5639178,
		7239300,
		8953095,
		10143450,
		223936,
		417942,
		1531249,
		1768586,
		3853900,
		103801,
		713874,
		911677,
		1507393,
		31615,
		48520,
		71383,
		108265,
		426961,
		534637,
		615308,
		751486,
		1053197,
		1056791,
		1069191,
		1100808,
		1100815,
		1302147,
		1309191,
		1314198,
		1315942,
		1400345,
		1401057,
		1420207,
		1424403,
		1517159,
		1530535,
		1625804,
		1663712,
		1796825,
		1908775,
		2015822,
		2017454,
		2034783,
		2034803,
		2225652,
		2233678,
		2233756,
		2234855,
		2640634,
		2793848,
		2803612,
		3318814,
		3680467,
		3991259,
		4338209,
		4476332,
		4516311,
		4629339,
		4799824,
		4887992,
		4891601,
		5103260,
		8129342,
		8660237,
		8994730,
		9425649,
		9652550,
		9719473,
		10329528,
		10555718,
		3486644,
		519841,
		1141716,
		4692999,
		9551973,
		2631572,
		700825,
		8787126,
		5361532,
		938229,
		1901494,
		6455059,
		1604161,
		5538800,
		96106,
		763524,
		2225549,
		1517144,
		10142140,
		5102996,
		2599142,
		1361082,
		1300038,
		8350000,
		1590919,
		5126231,
		8434466,
		536632,
		1016536,
		1299589,
		2075385,
		2967763,
		5300490,
		6401967,
		625406,
		53567,
		161327,
		260301,
		335770,
		676713,
		702227,
		777797,
		816877,
		864762,
		933896,
		993719,
		1220625,
		1267225,
		1303310,
		1366135,
		1369335,
		1372431,
		1384683,
		1468113,
		1563194,
		1712338,
		1747045,
		1857185,
		1877495,
		2171600,
		3289179,
		5105293,
		5395931,
		6142770,
		6526672,
		6788490,
		7040549,
		7622114,
		7643124,
		7916945,
		8046725,
		8098756,
		8110280,
		8560610,
		8561911,
		8803943,
		8937120,
		9110626,
		9436479,
		9544563,
		10701761,
		33667,
		49153,
		52760,
		52788,
		57118,
		64010,
		66960,
		90622,
		288483,
		663191,
		930657,
		1281453,
		1327132,
		1336540,
		2106663,
		2901239,
		3587818,
		6966749,
		8098637,
		9088845,
		3559,
		31027,
		32143,
		38305,
		38843,
		42885,
		54436,
		64590,
		89531,
		89914,
		92025,
		93589,
		108560,
		108565,
		110666,
		112815,
		117223,
		122826,
		126327,
		128655,
		130112,
		139165,
		144903,
		145828,
		152119,
		154689,
		155592,
		165292,
		165941,
		169150,
		174945,
		187663,
		192804,
		195161,
		198661,
		203135,
		213660,
		214862,
		222030,
		222080,
		231205,
		232975,
		240996,
		245518,
		264285,
		265761,
		271472,
		275491,
		275983,
		281866,
		294263,
		316503,
		323356,
		327375,
		337221,
		369422,
		391690,
		399250,
		399719,
		400422,
		402890,
		403482,
		404639,
		406880,
		409867,
		413148,
		415750,
		419105,
		420904,
		421997,
		424006,
		428484,
		439737,
		452022,
		474494,
		475806,
		482059,
		482879,
		504461,
		509171,
		509550,
		516890,
		519734,
		526452,
		527224,
		528929,
		529925,
		537070,
		538402,
		554255,
		581952,
		594139,
		603750,
		618498,
		619843,
		626643,
		637419,
		642780,
		649414,
		653468,
		653824,
		672463,
		686661,
		697061,
		708953,
		709497,
		732242,
		740215,
		745903,
		752970,
		756389,
		762371,
		762781,
		768017,
		780303,
		785307,
		787205,
		793802,
		797583,
		797730,
		805185,
		812008,
		814432,
		826958,
		832740,
		843654,
		861580,
		874631,
		881205,
		885299,
		891009,
		891193,
		891636,
		906756,
		933520,
		937718,
		957323,
		971758,
		980536,
		994005,
		1020956,
		1023698,
		1028048,
		1043662,
		1047083,
		1047634,
		1070103,
		1088242,
		1093634,
		1107133,
		1117704,
		1124396,
		1124726,
		1134957,
		1137800,
		1138891,
		1139716,
		1146281,
		1152673,
		1152918,
		1168669,
		1176524,
		1178760,
		1195901,
		1196872,
		1212658,
		1223402,
		1231815,
		1245911,
		1246272,
		1261559,
		1263747,
		1264860,
		1287077,
		1288260,
		1289900,
		1301492,
		1308630,
		1317541,
		1318265,
		1319598,
		1321915,
		1327814,
		1331496,
		1339245,
		1349564,
		1350523,
		1363394,
		1370250,
		1370845,
		1385268,
		1400414,
		1476581,
		1523331,
		1524316,
		1528676,
		1537138,
		1546860,
		1548053,
		1549911,
		1549949,
		1553059,
		1558554,
		1564877,
		1591440,
		1593902,
		1599228,
		1604461,
		1605811,
		1625690,
		1639323,
		1648982,
		1664812,
		1667585,
		1668465,
		1682170,
		1690284,
		1703490,
		1717897,
		1726865,
		1776941,
		1782056,
		1788022,
		1810519,
		1820192,
		1824596,
		1827558,
		1848183,
		1867676,
		1938284,
		1953483,
		1964718,
		1983640,
		1987976,
		1988612,
		1994182,
		2020275,
		2104722,
		2140039,
		2162508,
		2183845,
		2192762,
		2215518,
		2249181,
		2271248,
		2288014,
		2356685,
		2381164,
		2399513,
		2457450,
		2511444,
		2713584,
		2743024,
		2809874,
		2885056,
		2982511,
		3021703,
		3037952,
		3076837,
		3259834,
		3271193,
		3281606,
		3286896,
		3304559,
		3311097,
		3366376,
		3419739,
		3613494,
		3802080,
		3838944,
		3867059,
		3880248,
		3952259,
		3966813,
		3984325,
		3985442,
		4019317,
		4246673,
		4281192,
		4290729,
		4293320,
		4338168,
		4364979,
		4368264,
		4405323,
		4485584,
		4584012,
		4622357,
		4637775,
		4642517,
		4711229,
		4711502,
		4718456,
		4731961,
		4888243,
		4916044,
		4930418,
		5017834,
		5040472,
		5078070,
		5082883,
		5152326,
		5216046,
		5378080,
		5420553,
		5421068,
		5490615,
		5494513,
		5519541,
		5537625,
		5544127,
		5547043,
		5566338,
		5626274,
		5631850,
		5643171,
		5760894,
		5826672,
		5853124,
		5857239,
		5899471,
		5910771,
		5918268,
		5927412,
		5939293,
		5988293,
		6007301,
		6008508,
		6011353,
		6014861,
		6034911,
		6158831,
		6175143,
		6183047,
		6327980,
		6346642,
		6417853,
		6529125,
		6567438,
		6713468,
		6762023,
		6763380,
		6819523,
		6904622,
		7082290,
		7092256,
		7178658,
		7183914,
		7218616,
		7303925,
		7327841,
		7331227,
		7401173,
		7482712,
		7482743,
		7497484,
		7498829,
		7553292,
		7556204,
		7566977,
		7652783,
		7663717,
		7745836,
		7754315,
		7778245,
		7939795,
		7945515,
		7973874,
		7994341,
		8021044,
		8063276,
		8080130,
		8083353,
		8090907,
		8102657,
		8128212,
		8149758,
		8212353,
		8334565,
		8365862,
		8405883,
		8535443,
		8536089,
		8585688,
		8586301,
		8637234,
		8683018,
		8784179,
		8827924,
		8833069,
		9431155,
		9479748,
		9520114,
		9578092,
		9724398,
		9743362,
		9815177,
		9979819,
		10107708,
		10137961,
		10187810,
		10208701,
		10250995,
		10417825,
		10596132,
		139505,
		873917,
		1760638,
		4322891,
		5410839,
		5883032,
		5937736,
	];
	const EXPLICIT_AIDS_ALEXA_DE = [
		72411,
		72343,
		70961,
		70886,
		70804,
		70792,
		70693,
		70395,
		70333,
		70328,
		70151,
		70056,
		69991,
		69869,
		69739,
		69735,
		69723,
		69536,
		69187,
		68670,
		68260,
		68192,
		68065,
		67983,
		67856,
		67675,
		67487,
		67435,
		67423,
		67351,
		67218,
		67176,
		67140,
		66915,
		66597,
		66197,
		66189,
		66162,
		66114,
		66025,
		65999,
		65929,
		65811,
		65754,
		65731,
		65544,
		65512,
		65510,
		65407,
		65359,
		65319,
		65213,
		65161,
		65008,
		64839,
		64838,
		64681,
		64634,
		64613,
		64558,
		64426,
		63981,
		63945,
		63896,
		63809,
		63803,
		63769,
		63605,
		63581,
		63578,
		63567,
		63450,
		63430,
		63427,
		63406,
		63323,
		63147,
		63129,
		63117,
		62748,
		62320,
		62290,
		61884,
		61644,
		61628,
		61622,
		61588,
		61582,
		61543,
		61541,
		61319,
		61177,
		61166,
		61016,
		60640,
		60608,
		60398,
		60396,
		59868,
		59757,
		59717,
		59700,
		59633,
		59266,
		59226,
		59204,
		58973,
		58184,
		58011,
		57862,
		57798,
		57575,
		57544,
		57466,
		57463,
		57048,
		56947,
		56906,
		56648,
		56636,
		56509,
		56493,
		56489,
		56454,
		56286,
		56153,
		56127,
		56072,
		55942,
		55916,
		55833,
		55589,
		55530,
		55206,
		55098,
		54075,
		53982,
		53793,
		53624,
		53617,
		53567,
		53306,
		52988,
		52373,
		52335,
		52283,
		52184,
		51917,
		51453,
		50867,
		49812,
		49541,
		49245,
		48766,
		48410,
		48210,
		47958,
		47910,
		47686,
		47681,
		47657,
		47406,
		47403,
		47295,
		47077,
		47070,
		47035,
		46940,
		46314,
		46109,
		46068,
		44938,
		44783,
		44576,
		44281,
		44146,
		43623,
		43362,
		42837,
		42435,
		42368,
		42081,
		41802,
		41282,
		41281,
		41219,
		41142,
		40626,
		40429,
		40337,
		40103,
		39541,
		39017,
		36655,
		36272,
		35593,
		35315,
		35235,
		34526,
		34474,
		34430,
		34423,
		34377,
		34147,
		33933,
		33721,
		33669,
		33606,
		32984,
		32689,
		32455,
		32378,
		32327,
		32113,
		31826,
		31607,
		31472,
		31344,
		31289,
		31209,
		31153,
		30935,
		30905,
		30887,
		30882,
		30190,
		29973,
		29956,
		29898,
		29858,
		29656,
		29331,
		28873,
		28699,
		28587,
		28552,
		28523,
		28517,
		28494,
		28449,
		28435,
		28393,
		28389,
		28365,
		28364,
		28338,
		28245,
		28227,
		28208,
		28087,
		28053,
		27986,
		27984,
		27730,
		27728,
		27659,
		27560,
		27515,
		27460,
		27456,
		27447,
		27428,
		27407,
		27381,
		27267,
		27227,
		27165,
		27146,
		27102,
		27100,
		27093,
		27044,
		26968,
		26863,
		26823,
		26778,
		26775,
		26769,
		26682,
		26667,
		26662,
		26494,
		26454,
		26418,
		26202,
		26174,
		26090,
		26023,
		26002,
		25894,
		25876,
		25852,
		25772,
		25770,
		25741,
		25740,
		25695,
		25684,
		25661,
		25654,
		25603,
		25600,
		25465,
		25402,
		25396,
		25364,
		25356,
		25217,
		25187,
		25158,
		25004,
		24892,
		24782,
		24576,
		24542,
		24479,
		24412,
		24398,
		24325,
		24271,
		24270,
		24164,
		24094,
		24049,
		24009,
		23938,
		23888,
		23885,
		23812,
		23737,
		23666,
		23529,
		23524,
		23476,
		23475,
		23324,
		23180,
		23177,
		23161,
		23157,
		23121,
		23087,
		23044,
		22986,
		22925,
		22807,
		22646,
		22490,
		22488,
		22426,
		22418,
		22298,
		22293,
		22164,
		22142,
		22037,
		22028,
		17978,
		17894,
		17831,
		17785,
		17779,
		17776,
		16733,
		16678,
		16668,
		16428,
		16362,
		16358,
		16286,
		16238,
		16221,
		16199,
		16150,
		16115,
		16088,
		16051,
		16032,
		15983,
		15941,
		15938,
		15815,
		15660,
		15284,
		15132,
		15113,
		15057,
		14962,
		14959,
		14942,
		14906,
		14857,
		14777,
		14451,
		14204,
		13942,
		13892,
		13888,
		13860,
		13857,
		13842,
		13835,
		13774,
		13660,
		13479,
		13451,
		13324,
		13306,
		13232,
		13225,
		13204,
		13164,
		13134,
		12670,
		12571,
		12559,
		12554,
		12552,
		12345,
		12294,
		12089,
		11942,
		11909,
		11871,
		11862,
		11812,
		11721,
		11535,
		11057,
		10972,
		10828,
		10800,
		10795,
		10539,
		10493,
		10019,
		9933,
		9718,
		9703,
		9491,
		9240,
		9183,
		8883,
		8863,
		8774,
		8677,
		8595,
		8565,
		8486,
		8421,
		8322,
		8279,
		7970,
		7179,
		7000,
		6889,
		6336,
		6176,
		5820,
		5766,
		5726,
		5674,
		5242,
		5166,
		5161,
		4710,
		4631,
		4203,
		4184,
		4121,
		4120,
		1178,
	];

	public static function filterByNamespace($titles, $namespacesAllowed = [NS_MAIN, NS_CATEGORY]) {
		return array_filter($titles, function($t) use ($namespacesAllowed) {
			return $t->inNamespaces($namespacesAllowed);
		});
	}

	/**
	 * Filter titles by one or more top-level categories.
	 *
	 * @param Title[] $titles an array of titles to perform the filtering
	 * @param int[] $topLevelCategories an array of top level category values, as defined in $wgCategoryNames keys
	 * @return Title[] filtered array of titles with specified top-level categories removed
	 *
	 * Ex usage: TitleFilters::filterTopLevelCategories($titles, [CAT_RELATIONSHIPS]);
	 */
	public static function filterTopLevelCategories($titles, $topLevelCategories = []) {
		// No cats or titles? No filtering necessary
		if (empty($topLevelCategories) || empty($titles)) {
			return $titles;
		}

		$titlesMap = [];
		foreach ($titles as $t) {
			if ($t && $t->exists()) {
				$titlesMap[$t->getArticleId()]= $t;
			}
		}

		// Get the catinfo bitmasks for all the titles
		$dbr = wfGetDB(DB_SLAVE);
		$rows = $dbr->select(
			['page'],
			['page_id', 'page_catinfo', 'page_title'],
			['page_id' => array_keys($titlesMap)],
			__METHOD__
		);

		// Remove the title if there's a match for a category that should be filtered
		foreach ($rows as $row) {
			foreach ($topLevelCategories as $catMask) {
				if ((int)$row->page_catinfo & $catMask) {
					unset($titlesMap[$row->page_id]);
					break;
				}
			}
		}

		return array_values($titlesMap);
	}

	public static function filterByPageTitle($titles, $titleText = []) {
		return array_filter($titles, function($t) use ($titleText) {
			return !in_array($t->getText(), $titleText);
		});
	}

	public static function filterByAid($titles, $aidsToRemove = []) {
		return array_filter($titles, function($t) use ($aidsToRemove) {
			return !in_array($t->getArticleId(), $aidsToRemove);
		});
	}

	public static function filterByBadWords($titles, $listType = BadWordFilter::TYPE_ALEXA, $langCode) {
		return array_filter($titles, function($t) use ($listType, $langCode) {
			return !BadWordFilter::hasBadWord($t->getText(), $listType, $langCode);
		});
	}

	public static function filterExplicitAids($titles) {
		return self::filterByAid($titles, self::EXPLICIT_AIDS);
	}

	/*
	 * Filter against known bad titles for Alexa due to content maturity
	 *
	 * Note:  Only en and de currently supported
	 */
	public static function filterExplicitAidsForAlexa($titles, $langCode) {
		switch ($langCode) {
			case 'en':
				$titles = self::filterByAid($titles, self::EXPLICIT_AIDS_ALEXA_EN);
				break;
			case 'de':
				$titles = self::filterByAid($titles, self::EXPLICIT_AIDS_ALEXA_DE);
				break;
		}

		return $titles;
	}

	public static function removeRedirects($titles) {
		$filtered = [];
		foreach ($titles as $t) {
			if (!$t->isRedirect()) {
				$filtered []= $t;
			}
		}

		return $filtered;
	}
}