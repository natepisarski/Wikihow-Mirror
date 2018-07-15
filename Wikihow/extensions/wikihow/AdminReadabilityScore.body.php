<?php

if (!defined('MEDIAWIKI')) die();

global $IP;
$DavePath = $IP.'/extensions/wikihow/common/composer/vendor/davechild/textstatistics/src/DaveChild/TextStatistics';
require_once "$DavePath/Maths.php";
require_once "$DavePath/Pluralise.php";
require_once "$DavePath/Resource.php";
require_once "$DavePath/Syllables.php";
require_once "$DavePath/Text.php";
require_once "$DavePath/TextStatistics.php";

class AdminReadabilityScore extends UnlistedSpecialPage {
	
	public static $text_stats = null;
	public static $forDisplay;

	public function __construct() {
		parent::__construct('AdminReadabilityScore');
	}

	private static function processURLlist($pageList) {
		$pageList = preg_split('@[\r\n]+@', $pageList);
		$goodCount = 0;
		$urls = array();
		foreach ($pageList as $url) {
			$url = trim($url);
			if (!empty($url)) {
				$article = preg_replace('@http://www.wikihow.com/@','',$url);
				$article = urldecode($article);
				$res = self::checkArticle($article);
				if (!empty($res)) {
					if (self::$forDisplay) {
						$score = is_array($res) ? $res['score'] : $res;
						$smog = is_array($res) ? $res['smog'] : '';
						$urls[] = array(
							'url' => $url, 
							'article' => $article, 
							'score' => $score,
							'smog' => $smog,
						);
						$goodCount++;
					}
					else {
						if (is_array($res)) {
							$urls[] = array(
								'article' => 'http://www.wikihow.com/'.$article, 
								'agl' => $res['agl'],
								'fkgl' => $res['fkgl'],
								'gfs' => $res['gfs'],
								'smog' => $res['smog'],
								'ari' => $res['ari'],
								'' => '',
								'cli' => $res['cli'],
								'fkre' => $res['fkre'],
								'letters' => $res['letters'],
								'syllables' => $res['syllables'],
								'sentences' => $res['sentences'],
								'avg_words' => $res['avg_words'],
								'avg_syllables' => $res['avg_syllables'],
							);
							$goodCount++;
						}
						else {
							$urls[] = array(
								'article' => 'http://www.wikihow.com/'.$article,
								'agl' => $res,
							);
						}
					}
				}
			}
		}
		return array($urls, $goodCount);
	}
	
	private static function checkArticle($article) {
		if (!$article) return 'No such title';
	
		$t = Title::newFromText($article);
		if (!$t || !$t->exists()) return 'No such title';
		if ($t->isRedirect()) return 'Bad article';
		
		$r = Revision::newFromTitle($t);
		if (!$r) return 'No such article';
		
		$res = self::readabilityCheck($t, $r->getText());
		if ($res) return $res;
		
		//still here?
		return '';
	}
	
	/**
	 * check for readability
	 * return true if there's an issue
	 */
	private static function readabilityCheck($title, $text) {
		$result = '';		
		$text = self::cleanUpText($title, $text);
		
		if (self::$forDisplay) {
			$result = self::getDisplayResults($text);
		}
		else {
			$result = self::getDetails($text);
		}
		
		return $result;
	}
	
	public static function cleanUpText($title, $text) {
		$context = new RequestContext;
		$context->setTitle($title);
		
		//only the intro and steps
		$intro = Wikitext::getIntro($text);
		$steps = Wikitext::getStepsSection($text, true);
		$text = $intro."\n\n".$steps[0];
		$text = preg_replace('@<ref>.*?<\/ref>@im','',$text); //catch <ref> tags
		$text = preg_replace( "@{{[^}]+}}@","",$text); // Remove templates
		$text = $context->getOutput()->parse($text);
		$text = preg_replace('@<div class="(img-whvid|vid-whvid|mwimg).*?>.+?</div>@im','',$text); //remove whole image div chunks
		$text = preg_replace('@<.+?>@im','',$text); //remove all html stuff
		
		return $text;
	}
	
	private static function getDisplayResults($text) {
		if (empty(self::$text_stats)) self::$text_stats = new DaveChild\TextStatistics\TextStatistics;
		
		$smog = self::$text_stats->smogIndex($text);
		
		$result = array(
			'score' => self::avgReadLevel($text),
			'smog' => $smog ? $smog : '',
		);
		
		return $result;
	}
	
	private static function avgReadLevel($text) {
		if (empty(self::$text_stats)) self::$text_stats = new DaveChild\TextStatistics\TextStatistics;

		$total = 0;
		$tests = array(
			'fleschKincaidGradeLevel',
			'gunningFogScore',
			//'colemanLiauIndex',
			'smogIndex',
			'automatedReadabilityIndex',
		);
		
		foreach ($tests as $test) {
			$total += self::$text_stats->$test($text);	
		}
		
		$result = round($total / count($tests), 2);
		
		return $result;
	}
	
	public static function getFKReadingEase($text) {
		$fkre = '';
		if ($text) {
			if (empty(self::$text_stats)) self::$text_stats = new DaveChild\TextStatistics\TextStatistics;
			$fkre = self::$text_stats->fleschKincaidReadingEase($text);
		}
		return $fkre;
	}
	
	private static function getDetails($text) {
		if (empty(self::$text_stats)) self::$text_stats = new DaveChild\TextStatistics\TextStatistics;

		$agl = self::avgReadLevel($text);
		$fkgl = self::$text_stats->fleschKincaidGradeLevel($text);
		$gfs = self::$text_stats->gunningFogScore($text);
		$cli = self::$text_stats->colemanLiauIndex($text);
		$smog = self::$text_stats->smogIndex($text);
		$ari = self::$text_stats->automatedReadabilityIndex($text);
		$fkre = self::$text_stats->fleschKincaidReadingEase($text);
		$letters = self::$text_stats->letterCount($text);
		$syllables = self::$text_stats->syllableCount($text);
		$sentences = self::$text_stats->sentenceCount($text);
		$avg_words = self::$text_stats->averageWordsPerSentence($text);
		$avg_syllables = self::$text_stats->averageSyllablesPerWord($text);
			
		$result = array(
			'agl' => $agl ? $agl : '',
			'fkgl' => $fkgl ? $fkgl : '',
			'gfs' => $gfs ? $gfs : '',
			'cli' => $cli ? $cli : '',
			'smog' => $smog ? $smog : '',
			'ari' => $ari ? $ari : '',
			'fkre' => $fkre ? $fkre : '',
			'letters' => $letters ? $letters : '',
			'syllables' => $syllables ? $syllables : '',
			'sentences' => $sentences ? $sentences : '',
			'avg_words' => $avg_words ? $avg_words : '',
			'avg_syllables' => $avg_syllables ? $avg_syllables : '',
		);
		
		return $result;
	}
	
	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;
		
		$user = $wgUser->getName();
		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() || (!in_array('staff', $userGroups) && !in_array('staff_widget', $userGroups))) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
		
		if ($_SERVER['HTTP_HOST'] != 'parsnip.wikiknowhow.com') {
			$wgOut->redirect('https://parsnip.wikiknowhow.com/Special:AdminReadabilityScore');
		}

		if ($wgRequest->wasPosted()) {
			// this may take a while...
			set_time_limit(0);
			$wgOut->setArticleBodyOnly(true);
			$pageList = $wgRequest->getVal('pages-list', '');
			
			self::$forDisplay = $wgRequest->getVal('display') == '1';
			
			list($res, $goodCount) = self::processURLlist($pageList);
			
			if (self::$forDisplay) {
				$html = '<p><b>Articles:</b> '.(int)$goodCount.'</p>';
				
				if (!empty($res)) {
					$html .= '<style>.tres tr:nth-child(even) {background: #ccc;} .tres td { padding: 5px; }</style>'.
							'<table class="tres"><tr><th>Article URL</th><th>Avg. Grade Level</th><th>SMOG Grade<br />(best for health topics)</th></tr>';

					foreach ($res as $row) {
						$html .= "<tr><td><a href='{$row['url']}' target='_blank'>{$row['article']}</a></td><td>{$row['score']}</td><td style='width: 170px'>{$row['smog']}</td></tr>";
					}
					$html .= '</table>';
				}
				
				$result = array('result' => $html);

				print json_encode($result);
			}
			else {
				//downloading xls
				$datetime = date('Ymd');
				header("Content-Type: text/tsv");
				header("Content-Disposition: attachment; filename=\"admin_readabilityscore_$datetime.xls\"");
				print ("Article\tAverage Grade Level\tFlesch Kincaid Grade Level\tGunning Fog Score\tSMOG Index\tAutomated Readability Index\t\tColeman Liau Index\tFlesch Kincaid Reading Ease\tLetter count\tSyllable count\tSentence count\tAverage words per sentence\tAverage syllables per word\n");
				foreach ($res as $row) {
					print implode("\t",$row)."\n";
				}
				exit;
			}

			return;
		}

		$wgOut->setHTMLTitle('Admin - Readability Score - wikiHow');

$tmpl = <<<EOHTML
<form id="articles" method="post" action="/Special:AdminReadabilityScore">
<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;">
	Admin Readability Score
</div>

<h3>Score the readability of articles</h3>
<div style="font-size: 13px; margin: 20px 0 7px 0;">
	Enter a list of URLs such as <code style="font-weight: bold;">http://www.wikihow.com/Be-a-Ninja</code> to process.<br />
	One per line.
</div>
<textarea id="pages-list" name="pages-list" type="text" rows="10" cols="70"></textarea><br />
<button id="pages-go" disabled="disabled" style="padding: 5px;">View Summary</button> 
<button id="pages-dl" disabled="disabled" style="padding: 5px;">Download Details</button><br/>
<br/>
<div id="pages-result">
</div>
</form>

<script>
(function($) {
	$(document).ready(function() {
		$('#pages-go')
			.removeAttr('disabled')
			.click(function () {
				var form = $('#articles').serializeArray();
				$('#pages-result').html('loading ...');
				$.post('/Special:AdminReadabilityScore?display=1',
					form,
					function(data) {
						$('#pages-result').html(data['result']);
						$('#pages-list').focus();
					},
					'json');
				return false;
			});
			
		$('#pages-dl').removeAttr('disabled');

		$('#pages-list').focus();
	});
})(jQuery);
</script>
EOHTML;

		$wgOut->addHTML($tmpl);
	}
}
