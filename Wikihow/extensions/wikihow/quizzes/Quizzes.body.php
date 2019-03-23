<?php

class Quizzes extends UnlistedSpecialPage {

	private static $quizURL = '/Quiz/';
	private static $firstRelated = '';
	private static $firstRelatedTitle = '';
	private static $quizBG = '';

	function __construct() {
		parent::__construct( 'Quizzes' );
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public static function removeHeadSectionCallback(&$showHeadSection) {
		$showHeadSection = false;
		return true;
	}

	public static function getCanonicalUrl(&$this, &$url, $query) {
		$url = self::$quizURL;
		return true;
	}

	/**
	 * Strip out the quiz name from the displayed url
	 **/
	private static function getQuizNameFromUrl() {
		global $wgTitle;

		$parts = explode( '/', $wgTitle->getFullUrl() );
		foreach ($parts as $k => $p) {
			if ($p == 'Quiz') $quiz_name = $parts[$k+1];
		}
		return $quiz_name;
	}

	/**
	 * Display the HTML for this special page
	 */
	private static function displayContainer($quiz_name = '') {
		$ads = "";
		$ads2 = "";
		$ads3 = "";
		$ads_interstitial = "";

		if (!$quiz_name) $quiz_name = self::getQuizNameFromUrl();

		if ($quiz_name) {
			//load that blob of quiz data
			$quiz_blob = self::loadQuiz($quiz_name);

			//did we catch anything?
			if (!$quiz_blob) return false;

			$quiz_title = preg_replace('@-@',' ',$quiz_name);

			list($question, $answers, $progress) = self::formatQuiz($quiz_blob);

			$tmpl = new EasyTemplate( __DIR__ );
			$tmpl->set_vars(array(
				'quiz_title' => $quiz_title,
				'quiz_progress' => $progress,
				'quiz_question' => $question,
				'quiz_answers' => $answers,
				'quiz_name' => $quiz_name,
				'full_quiz' => htmlspecialchars($quiz_blob),
				'quiz_quips' => self::addQuips(),
				'quiz_found' => self::getFoundInArticles($quiz_name),
				'quiz_related' => self::getRelatedArticles($quiz_name),
				'quiz_ads' => $ads,
				'quiz_ads2' => $ads2,
				'quiz_ads3' => $ads3,
				'quiz_bg' => self::$quizBG,
				'quiz_ads_interstitial' => $ads_interstitial,
			));

			$html = $tmpl->execute('quizzes.tmpl.php');
		}
		else {
			//no name passed in?
			return false;
		}

		return $html;
	}

	private static function loadQuiz($quiz_name) {
		global $wgMemc;

		$memkey = wfMemcKey('quiz',$quiz_name);
		$quiz_blob = $wgMemc->get($memkey);

		if (!is_string($quiz_blob)) {
			$dbr = wfGetDB(DB_REPLICA);
			$quiz_blob = $dbr->selectField('quizzes', 'quiz_data', array('quiz_name' => $quiz_name), __METHOD__);

			//blob it into memcache
			if (is_string($quiz_blob)) {
				$wgMemc->set($memkey,$quiz_blob);
			}
		}

		return $quiz_blob;
	}

	/*
	 * formats the quiz question and the progress bar
	 */
	private static function formatQuiz($blob) {
		$quiz = json_decode($blob,true);

		foreach ($quiz as $k=>$q) {
			($k == 0) ? $on = ' on' : $on = '';
			$progress .= '<div class="progress_num'.$on.'">'.($k+1).'</div>';
		}

		return array($quiz[0]['question'], $quiz[0]['answers'], $progress);
	}

	/*
	 * grabs some random quips for the final page to use
	 */
	private static function addQuips() {
		$bad = array("Aww, it's okay. We still love you.",
				"That's okay! Better luck next time.",
				"Bummer. You'll get 'em next time!",
				"Can't win 'em all!",
				"Don't hold back, now. Give it your best shot.",
				"It was probably just a fluke. Try again!",
				"It's okay. At wikiHow, everyone's a winner.",
				"Rome wasn't built in a day! Keep trying!",
				"There's always another chance!",
				"Time to study up! Better luck next time.",
				"Maybe the next time's the charm!");

		$okay = array("Getting there! Nice work.",
				"Not bad! Want to give it another go?",
				"Practice makes perfect!",
				"Solid try! That's a good start!",
				"Getting there! Want to try for another round?",
				"You're almost there! Great effort.");

		$good = array("Doing great! You've almost got it.",
				"Great job! Well done!",
				"Pretty awesome. Keep up the good work!",
				"Pretty darn good. Kudos.",
				"Solid. You're totally awesome.",
				"Wow. You're almost an expert!",
				"You did great. Keep up the good work!",
				"Good job. You're ahead of the curve!");

		$perfect = array("10 out of 10? We are seriously impressed!",
				"10 out of 10? Inconceivable!",
				"A perfect score? You are my hero.",
				"Are you sure you haven't done this before? :)",
				"Congrats! You get five stars and two thumbs up.",
				"Congratulations! You did great.",
				"Hellooo, Einstein! Great job!",
				"Now that's how it's done!",
				"Perfect score? You're an expert!",
				"There ya go! You make us so proud.",
				"What??? You got a 10/10? Amazing!!!",
				"wikiWoohoo! Great job.",
				"Wow, a perfect 10? We are impressed.",
				"Wow, great job! You're a pro!",
				"Wow, you did a great job. Congratulations!",
				"Wow, a perfect score!",
				"Wait. How did you do that?",
				"Wow, major success! Kudos to you.",
				"Good job. You nailed it!");

		$quips = array();
		$quips['bad'] = $bad[rand(0,count($bad)-1)];
		$quips['okay'] = $okay[rand(0,count($okay)-1)];
		$quips['good'] = $good[rand(0,count($good)-1)];
		$quips['perfect'] = $perfect[rand(0,count($perfect)-1)];

		return json_encode($quips);
	}

	/*
	 * For the Found In section
	 * - standard related wikiHow sidebar format
	 */
	private static function getFoundInArticles($quiz_name) {
		$html = '';

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('quiz_links', 'ql_page', array('ql_name' => $quiz_name), __METHOD__);

		foreach ($res as $row) {
			$t = Title::newFromId($row->ql_page);

			if ($t && $t->exists()) {
				$img = ImageHelper::getGalleryImage($t, 44, 33);
				if (!self::$firstRelated) {
					$firstid = ' id="the_article_url"';
				}
				else {
					$firstid = '';
				}

				$html .= '<tr>
							<td class="thumb"><a href="../'.$t->getPartialURL().'"><img src="'.$img.'" alt=""></a></td>
							<td><a href="../'.$t->getPartialURL().'"'.$firstid.'>'.$t->getText().'</a></td>
						</tr>';

				//FIRST!
				//save for meta description and related articles
				if (!self::$firstRelated) {
					self::$firstRelated = 	' '.wfMessage('dv-meta-article-prefix')->text().
											' '.htmlspecialchars($t->getText());
					self::$firstRelatedTitle = $t;

					//also, let's grab the bg image for this quiz from this article
					self::$quizBG = ImageHelper::getGalleryImage($t, 635, -1);
				}
			}
		}

		return $html;
	}

	/*
	 * For the Related Articles section
	 */
	private static function getRelatedArticles($quiz_name) {
		global $wgTitle;

		//keys off the first related title
		if (!self::$firstRelatedTitle) return '';

		//swap out title
		$tempTitle = $wgTitle;
		$wgTitle = self::$firstRelatedTitle;

		//grab related
		$html = WikihowSkinHelper::getRelatedArticlesBox(true /* isBoxShape */);

		//swap title back
		$wgTitle = $tempTitle;

		return $html;
	}

	/*
	 * deal with the link table
	 */
	public static function updateLinkTable($article, $quiz_name, $bAdd = true) {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		//assemble our db array for docs
		$quiz_array = array('ql_page' => $article->getID());
		if ($quiz_name) $quiz_array['ql_name'] = $quiz_name;

		//something in there for this?
		$count = $dbr->selectField('quiz_links', 'count(*) as count', $quiz_array, __METHOD__);

		if ($bAdd) { //INSERT

			//already in there? um...cool, I guess.
			if ($count > 0) return true;

			$res = $dbw->insert('quiz_links',
					array('ql_page' => $article->getID(), 'ql_name' => $quiz_name),
					__METHOD__);

			if ($res && $quiz_name) {
				//ACTIVATE
				$dbw->update('quizzes',array('quiz_active' => 1),array('quiz_name' => $quiz_name), __METHOD__);
			}

		}
		else { //DELETE

			//wait, it's not in the table? ooo-kay...
			if ($count == 0) return true;

			$inactivates = array();

			if (!$quiz_name) {
				//we're removing any that are associated with this article
				//grab all the ones we're going to remove...
				$goodbyes = $dbr->select('quiz_links','ql_name',$quiz_array, __METHOD__);
				foreach ($goodbyes as $row) {
					$inactivates['quiz_name'] = $row->ql_name;
				}
			}
			else {
				$inactivates['quiz_name'] = $quiz_name;
			}

			$res = $dbw->delete('quiz_links', $quiz_array, __METHOD__);

			if ($res && $inactivates) {
				//INACTIVE
				$dbw->update('quizzes',array('quiz_active' => 0),$inactivates, __METHOD__);
			}
		}

		return $res;
	}

	/**
	 * Function to display the quiz link on an article page
	 */
	public static function grabQuizCTA($quiz_name, $t) {
		$html = '';

		//hyphens to spaces; spaces to hyphens
		$quiz_display_name = preg_replace('@-@',' ',$quiz_name);
		$quiz_name = preg_replace('@ @','-',$quiz_display_name);

		$html = '<div class="quiz_cta_2">'.
				'<a href="/Quiz/'.$quiz_name.'"><img class="whcdn" src="/extensions/wikihow/quizzes/images/quiz_thumb.png" /></a><br />'.
				'<a href="/Quiz/'.$quiz_name.'">'.$quiz_display_name.' '.wfMessage('quiz-suffix')->text().'</a>'.
				'</div>'.
				'<br class="clearall" />';

		return $html;
	}

	private static function getOtherQuizzes($quiz) {
		$dbr = wfGetDB(DB_REPLICA);
		$others = array();

		//grab 3 other quizzes that aren't this quiz
		$res = $dbr->select('quizzes',array('quiz_name'),
				array("quiz_name <> ".$dbr->addQuotes($quiz), "quiz_active" => true),
				__METHOD__,array('LIMIT'=>3,'ORDER BY'=>'RAND()'));

		foreach ($res as $row) {
			$img = self::getBoxBgImg($dbr, $row->quiz_name, 220, 220);

			$other_quiz = array(
				'name' => $row->quiz_name,
				'image' => $img,
			);
			$others[] = $other_quiz;
		}

		return $others;
	}

	private static function getBoxBgImg($db, $quiz_name, $width, $height) {
		$page = $db->selectField('quiz_links', 'ql_page', array('ql_name' => $quiz_name), __METHOD__,array('LIMIT'=>1));
		$t = Title::newFromId($page);

		if ($t) {
			$img = ImageHelper::getGalleryImage($t, $width, $height);
		}

		return $img;
	}

	public function execute($par) {
		global $wgHooks, $wgCanonical, $wgSquidMaxage;

		$req = $this->getRequest();
		$out = $this->getOutput();

		if ($req->getVal('otherquizzesfor')) {
			$others = self::getOtherQuizzes($req->getVal('otherquizzesfor'));
			$out->setArticleBodyOnly(true);
			print json_encode($others);
			return;
		}

		$quiz = preg_replace('@-@',' ',$par);

		//no side bar
		$wgHooks['ShowSideBar'][] = array('Quizzes::removeSideBarCallback');
		//no head section
		$wgHooks['ShowHeadSection'][] = array('Quizzes::removeHeadSectionCallback');

		//make a custom canonical url
		self::$quizURL = Misc::getLangBaseURL() . self::$quizURL . $par;
		$wgHooks['GetFullURL'][] = array('Quizzes::getCanonicalUrl');
		$out->setCanonicalUrl(self::$quizURL);

		//page title
		$page_title = wfMessage('quiz_pagetitle')->text().' '.wfMessage('howto',$quiz)->text();
		$out->setHTMLTitle( wfMessage('pagetitle', $page_title)->text() );

		//css & js for quizzes
		$out->addModules('ext.wikihow.quizzes');
		$html = self::displayContainer($par);

		if (!$html) {
			//nothin'
			$out->setStatusCode(404);
			$html = '<p>'.wfMessage('quiz-no-quiz-err')->text().'</p>';
		}
		else {
			//http caching headers
			$out->setSquidMaxage($wgSquidMaxage);

			//meta tags
			$out->addMeta('description','Test yourself on How to '.$quiz.' with a fun and challenging quiz from wikiHow. See how well you score.');
			$out->setRobotPolicy('index,follow');
		}

		$out->addHTML($html);
	}

}
