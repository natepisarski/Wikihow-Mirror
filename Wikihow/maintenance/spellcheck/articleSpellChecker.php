<?
require_once('../commandLine.inc');
require_once('spelllib/include.php');

$spellChecker = new WikiHowSpellChecker();
$spellChecker->spellCheckAllArticles();

class WikiHowSpellChecker {

	var $spellChecker = null;


	function __construct() {
		$this->spellChecker = $this->initSpellChecker();
	}

	public function spellCheckAllArticles() {
		$dbr = wfGetDB(DB_SLAVE);;
		$res = $dbr->select('page', array('page_id'), array('page_namespace' => 0, 'page_is_redirect' => 0), 
			'WikiHowSpellChecker::spellCheckAllArticles');
		$titles = array();
		while ($row = $dbr->fetchObject($res)) {
			$pageIds[] = array($row->page_id);
		}
		$this->spellCheckArticles($pageIds);
	}

	public function spellCheckArticles(&$pageIds) {
//$pageIds = array(15985);
		foreach ($pageIds as $pageId) {
			$t = Title::newFromId($pageId);
			if ($t && $t->exists()) {
				$misspellings = $this->spellCheckArticle($t);
//var_dump($t->getText());
//var_dump($misspellings);
				$this->updateTables($t, $misspellings);
			}
		}

	}

	private function spellCheckArticle(&$t) {
		$misspellings = array();
		$title = $t->getText();
		if ($article = $this->fetchArticleBody($title)) {
			$tokens = $this->tokenize($article);
			foreach ($tokens as $token) {
				if(!$this->isSpelledCorrectly($token, $spellChecker)) {
					$misspellings[] = $token;
				}
			}
		}
		return $misspellings;	
	}

	private function updateTables(&$t, &$misspellings) {
		$dbw = wfGetDB(DB_MASTER);
		$aId = $t->getArticleId();
		foreach ($misspellings as $k => $misspelling) {
			$misspelling = $dbw->strencode($misspelling);
			$misspellings[$k] = $misspelling;

			//Update the spellcheck_misspellings table
			$sql = "INSERT INTO spellcheck_misspellings VALUES ('$misspelling', 1) ON DUPLICATE KEY UPDATE sm_count = sm_count + 1";
			$dbw->query($sql);
		}

		$revId = $t->getLatestRevID();
		$misspelledCount = sizeof($misspellings);
		$words = implode(",", $misspellings);

		$sql = "INSERT INTO spellcheck_articles VALUES ($aId, $revId, $misspelledCount, '$words') ON DUPLICATE KEY 
			UPDATE sa_rev_id = $revId, sa_misspelled_count = $misspelledCount, sa_misspellings = '$words'";
		$dbw->query($sql);
	}

	private function tokenize(&$article) {
		$parts = preg_split("@(<h2.*</h2>)@im", $article->parse->text, 0, PREG_SPLIT_DELIM_CAPTURE);
		// take out a couple of wikihow sections. shouldn't spell check this
		foreach($parts as $k => $part) {
			if(preg_match("@(<span>{ ]*related wikihows[ ]*</span>)|(<span>[ ]*Sources and Citations[ ]*</span>)@im", $part)) {
				$parts[$k] = "";
				$parts[$k + 1] = "";
			}
		}
		
		$article = implode("", $parts);
		// Remove urls before we do anything
		$article = preg_replace("/https?:\/\/[^ ]+ /", " ", $article);
		// Remove html tags and trim whitespace
		$article = trim(strip_tags($article));
		// Decode html special chars
		$article = htmlspecialchars_decode($article);
		// Replace single right quotes (&rsquo;) with plain old single quote so the preg_replace below doesn't strip them out
		$article = str_ireplace("’", "'", $article);
		// Clear out all the rest of the junk and delimit tokens with ,
		$article = preg_replace("/[^'A-Za-z0-9]/", ",", $article); 
		// Remove empty tokens
		$article = ereg_replace(",{2,}", ",", $article);
		// Tokenize! Mwuhahaha!
		$tokens = explode(",", $article);
		// Strip single quotes from beginning and end of tokens
		foreach ($tokens as $token) {
			$token = trim($token, "'");
		}
		return $tokens;
	}

	private function isSpelledCorrectly(&$token, &$spellChecker) {
		$correct = false;
		// Ignore 0 length and 1 char strings.  Because of how we tokenize by removing punctuation, abbreviations (e.g. a.k.a)
		// will come back as one letters. Also, we don't want to spell check empty strings. 
		if (strlen($token) <= 1) {
			$correct = true;
		}
		else {
			$correct = $this->spellChecker->SpellCheckWord($token);
		}
		return $correct;
	}

	private function fetchArticleBody(&$title) {
		$url = "http://www.wikihow.com/api.php?action=parse&page=" . urlencode($title) . "&format=xml";
		$body = "";
		if($xml = $this->curl($url)) {
			$body = simplexml_load_string($xml);
		}
		else {
			echo "<br>uh oh. there was a problem getting the article ' $title '<br>";
		}
		return $body;
	}

	private function curl(&$url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		$contents = curl_exec($ch);
		if (curl_errno($ch)) {
			echo "curl error {$url}: " . curl_error($ch);
			return false;
		} 	
		return $contents;
	}

	private function initSpellChecker() {
		#	Create a PHPSpellCheck Core Engine Instance ;
		$spellcheckObject = new PHPSpellCheck();

		#	Copy and Paste your license key here.  Buy one onlline at www.phpspellcheck.com 
		$spellcheckObject -> LicenceKey="TRIAL";

		#	BASIC SETTINGS
		$spellcheckObject -> IgnoreAllCaps = false;
		$spellcheckObject -> IgnoreNumeric = true;
		$spellcheckObject -> CaseSensitive = false;

		# Set up the file path to the (.dic) dictionaries folder
		$spellcheckObject -> DictionaryPath = ("spelllib/dictionaries/");

		# Sets the tollerance of the spellchecker to 'unlikely' suggestions. 0=intollerant ... 10=very tollerant  
		$spellcheckObject -> SuggestionTollerance = 1;

		# Loads a dictionary - you can load more than one at the same time */
		 $spellcheckObject -> LoadDictionary("English (International)") ;
		#Add vocabulary to the spellchecer from a text file loaded from the DictionaryPath*/
		$spellcheckObject -> LoadCustomDictionary("custom.txt");

		/* Alternative methods to load vocabulary
		$spellcheckObject -> LoadCustomDictionaryFromURL( $URL );
		$spellcheckObject ->AddCustomDictionaryFromArray(array("popup","nonsensee"));
		/*

		/* Ban a list of words which will never be alloed as correct spellings.  Ths is great for filtering profanicy.*/
		$spellcheckObject -> LoadCustomBannedWords("language-rules/banned-words.txt"); 
		/*
		You can also add banned words from an array which you could easily populate from an SQL query
		//$spellcheckObject -> AddBannedWords(array("primary"));
		*/

		/* Load a lost of Enforced Corrections from a file.  This allows you to enforce a spelling suggestion for a specific word or acronym.*/
		$spellcheckObject -> LoadEnforcedCorrections("language-rules/enforced-corrections.txt");

		/*Load a list of common typing mistakes to fine tune the suggestion performance.*/
		$spellcheckObject -> LoadCommonTypos("language-rules/common-mistakes.txt");
		
		return $spellcheckObject;
	}
}
