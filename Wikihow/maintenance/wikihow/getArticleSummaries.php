<?php
//
// One time script to get a csv of article summaries from a list of article ids
//

require_once __DIR__ . '/../Maintenance.php';

class GetArticleSummaries extends Maintenance {
    public function __construct() {
        parent::__construct();
        $this->mDescription = 'One time script to get a csv of article summaries from a list of article ids';
    }

    public function execute() {
		$aids = [
			148675,
			129431,
			30513,
			58425,
			73782,
			57203,
			95903,
			15942,
			310819,
			279762,
			121049,
			307128,
			115174,
			47412,
			136497,
			8503,
			65596,
			6775,
			19222,
			22782,
			105170,
			103761,
			19958,
			46692,
			22372,
			25067,
			15756,
			278836,
			59634,
			393890,
			282285,
			11008,
			63782,
			23944,
			14148,
			61398,
			36891,
			247586,
			3476,
			8613,
			268503,
			2357,
			3823,
			31070,
			25266,
			21983,
			49930,
			8163,
			77985,
			21367,
			357113,
			16184,
			21944,
			54683,
			231977,
			2360,
			288995,
			12137,
			2330480,
			34682,
			13268,
			19462,
			46021,
			20398,
			20969,
			5501,
			10794,
			49809,
			12801,
			19328,
			6162,
			36431,
			103746,
			4297,
			2856,
			1896,
			4589,
			132500,
			18201,
			260214,
			22939,
			19242,
			15590,
			3467,
			2809859,
			1226176,
			13681,
			2330,
			103421,
			109874,
			17636,
			2771347,
			10852,
			47145,
			4342,
			10816,
			40500,
			317319,
			25655,
			1746,
			2816442,
			90904,
			210726,
			15303,
			306831,
			2246,
			6006,
			6866,
			6923,
			7085,
			9722,
			10020,
			10590,
			15437,
			23676,
			23722,
			26702,
			27948,
			30037,
			34204,
			42438,
			42886,
			45921,
			56556,
			65349,
			69220,
			78537,
			88305,
			89898,
			105171,
			115112,
			130528,
			137849,
			147339,
			189737,
			204663,
			239289,
			244684,
			245458,
			306640,
			306693,
			362959,
			408180,
			3090,
			4216,
			20046,
			37464,
			45868,
			7800,
			7834,
			52514,
			73786,
			103670,
			132808,
			6599,
			19207,
			319398,
			1972,
			6868,
			26327,
			2418,
			4132,
			4570,
			5014,
		];
	    $fp = fopen('articleSummaries.csv', 'w');
	    fputcsv($fp, ['Article ID', 'Url', 'Summary', 'Error']);
		for ($i = 0; $i < sizeof($aids); $i++) {
			$aid = $aids[$i];
			$url = '';
			$summaryText = '';
			$errorText = '';
			$t = Title::newFromId($aid);

			if ($t && $t->exists()) {
				$url = 'https://www.wikihow.com/' . $t->getPartialUrl();
				$json = file_get_contents(
					"https://www.wikihow.com/api.php?action=articletext&aid=$aid&format=json");
				$obj = json_decode($json);
				//var_dump($obj);

				if (!$obj) {
					$errorText = 'No data returned from API call';
				} elseif ($obj->data->summaryText) {
					$summaryText = $obj->data->summaryText;
				} else {
					$errorText = 'Empty summary';
				}
			} else {
				$errorText = 'Title not found';
			}
			fputcsv($fp,
				[$aid, $url, $summaryText, $errorText]);
			echo "$aid\t$url\tError:$errorText\n";
		}
		fclose($fp);
    }
}

$maintClass = 'GetArticleSummaries';
require_once RUN_MAINTENANCE_IF_MAIN;
