<?php
/**
 * Read which articles in NAB need to be scored; score them using our
 * rating algorithm; write the result to the master db
 */

require_once __DIR__ . '/../Maintenance.php';

class GetNabArticlesToScore extends Maintenance {

	const FILE_PATH = "/data/nab-scoring";
	const DEBUG_FILE = "/data/nab-scoring/php_debug_log";

	private $debug = false;

	public function __construct() {
		parent::__construct();
		$this->addOption('cmd', 'The location of the script to run that does the scoring', false, true);
		$this->addOption('debug', 'Produce some extra debug output', false, false);
	}

	private function decho(string $str) {
		if ($this->debug) {
			$logStr = date('r') . ": " . $str . "\n";
			file_put_contents( self::DEBUG_FILE, $logStr, FILE_APPEND );
		}
	}

	public function execute() {
		if ( ! $this->hasOption('cmd') ) {
			$cmd = '/opt/data_projects/ml_article_quality_rater/run_nab_scoring.sh';
		} else {
			$cmd = $this->getOption('cmd');
		}

		if ( $this->hasOption('debug') ) {
			$this->debug = true;
		}

		// Produces an array of articles that need scoring
		$toScore = $this->getNabArticles();

		$this->decho( ($toScore ? "toScore: " . print_r($toScore, true) : "Nothing returned from getNabArticles") );

		$ts = wfTimestampNow();
		$pid = getmypid();
		$fileUnique = "$ts-$pid";
		$errorFile = self::FILE_PATH . "/nab-scoring-error-output-$fileUnique.txt";
		$output = '';
		if ($toScore) {
			// We execute the python process, passing in the list of articles to score
			$output = $this->executePythonProcess($cmd, $toScore, $errorFile, $fileUnique);
		}
		if ($output == '') {
			print "No articles to score\n";
		} else {
			// We update the nab system with the output from the python process
			$this->writeOutputToNab($output, $toScore, $errorFile);
		}
	}

	private function getNabArticles() {
		$revs = NabAtlasList::getNewRevisions();
		$toScore = [];
		$count = 0;
		foreach ($revs as $rev) {
			$pageid = $rev['page_id'];
			$revid = $rev['atlas_revision'];
			$wikitext = $this->getRevisionText($revid);
			if ($wikitext) {
				$out = [
					'page_id' => $pageid,
					'revision_id' => $revid,
					'wikitext' => $wikitext,
				];
				$toScore[] = $out;
				$count++;
			} else {
				print "Could not retrieve wikitext for {$pageid}\n";
			}
		}
		return $toScore;
	}

	private function executePythonProcess($cmdToExecute, $input, $errorFile, $fileUnique) {
		$cwd = self::FILE_PATH;
		$fileInput = self::FILE_PATH . "/input-$fileUnique.json";
		$jsonEncodedInput = json_encode($input);
		file_put_contents($fileInput, $jsonEncodedInput);
		$fileOutput = self::FILE_PATH . "/output-$fileUnique.json";

		$return_value = -1;
		$output = '';
		$this->decho( "Attempting to run: (cd $cwd; $cmdToExecute < $fileInput > $fileOutput 2> $errorFile)" );
		system("(cd $cwd; $cmdToExecute < $fileInput > $fileOutput 2> $errorFile)", $return_value);
		if (file_exists($fileOutput)) {
			$output = file_get_contents($fileOutput);
			if ($output) {
				$output = json_decode($output, true);
				if (!$output) {
					$err = json_last_error();
					$msg = json_last_error_msg();
					print "Error with json_decode() output ($err): $msg\n";
				}
			}
		}

		if ($return_value !== 0) {
			print "Received error-indicating return value: $return_value\n";
			$this->dumpError(print_r($output, true), $input, $errorFile);
			exit;
		} elseif ($output != '' && !is_array($output)) {
			print "Received badly encoded output from script:\n";
			$this->dumpError(print_r($output, true), $input, $errorFile);
			exit;
		} else {
			if (file_exists($errorFile)) {
				if (filesize($errorFile) === 0
					|| trim( system("cat $errorFile | grep -v 'Found java: .*java'") ) == ""
				) {
					unlink($errorFile);
					@unlink($fileInput);
					@unlink($fileOutput);
				}
			}
			return $output;
		}
	}

	private function dumpError($output, $input, $errorFile) {
		print "Received non-array return value from python process. output=$output\n";
		print "input=";
		var_dump($input);
		if (file_exists($errorFile)) {
			print "error file contents=";
			print file_get_contents($errorFile) . "\n";
		}
	}

	/**
	 * @var $output string json-encoded string from python process
	 * @var $input array for debugging output
	 * @var $errorFile string filename where error output was written
	 */
	private function writeOutputToNab($ratedPages, $input, $errorFile) {
		print "Number of items to score: " . count($input) . "\n";

		$now = wfTimestampNow();
		$nabPages = [];
		foreach ($ratedPages as $i => $page) {
			print "Rated pageid={$page['page_id']}: {$page['score']}\n";
			$nabPages[] = [
				'page_id' => $page['page_id'],
				'atlas_revision' => $page['revision_id'],
				'atlas_score' => (int)round($page['score']),
				'atlas_score_updated' => $now ];
		}
		NabAtlasList::updatePages($nabPages);
	}

	/**
	 * @param Title $article - Title object representing page to extract text from
	 * @return string containing the raw text content of the page, or empty if unsuccessful
	 */
	private function getRevisionText($revid) {
		$revision = Revision::newFromId($revid);

		if (!$revision || !$revision instanceof Revision) {
			return '';
		}

		$content = $revision->getContent(Revision::RAW);
		return ContentHandler::getContentText($content);
	}

}

$maintClass = 'GetNabArticlesToScore';
require_once RUN_MAINTENANCE_IF_MAIN;
