<?php

class StepEditor extends UnlistedSpecialPage {

	const STEP_TAG = "Single Step Edit";

	public function __construct() {
		parent::__construct("StepEditor");
	}

	public function execute($par) {
		# Check blocks
		$user = $this->getUser();
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$out = $this->getOutput();
		$out->setRobotPolicy("noindex,nofollow");
		$request = $this->getRequest();

		if ($request->wasPosted()) {

			$out->setArticleBodyOnly(true);

			$this->newStep = $request->getVal('newStep');
			$this->stepNum = $request->getVal('stepNum');
			$this->articleId = $request->getVal('articleId');
			$this->revisionId = $request->getVal('revisionId');
			$title = Title::newFromID($this->articleId);
			$checkValid = $request->getVal('checkValid');

			if ($title) {
				$out->setArticleBodyOnly(true);
				$stepEditorHelper = new StepEditorParser($title, $this->revisionId, $out);
				if ($checkValid) {
					if ( $stepEditorHelper->isLatestRevision ) {
						$result['isValid'] = true;
						print json_encode($result);
					} else {
						$result['isValid'] = false;
						$tmpl = new EasyTemplate(__DIR__);
						$result['err'] = $tmpl->execute('stepediterror.tmpl.php');
						print json_encode($result);
					}
					return;
				}

				list($newRevisionId, $errorInfo) = $stepEditorHelper->replaceStep($this->newStep, $this->stepNum, $this->getContext());
				if ($newRevisionId == -1) {
					$result['success'] = false;
					if ($errorInfo != null) {
						if ($errorInfo['type'] == "spam") {
							$tmpl = new EasyTemplate(__DIR__);
							$tmpl->set_vars(array('link' => $errorInfo['link']));
							$result['err'] = $tmpl->execute('stepeditspam.tmpl.php');
						} else {
							$tmpl = new EasyTemplate(__DIR__);
							$tmpl->set_vars(array('message' => $errorInfo['message']));
							$result['err'] = $tmpl->execute('stepediturls.tmpl.php');
						}
						$result['modal'] = false;
					} else {
						$tmpl = new EasyTemplate(__DIR__);
						$result['err'] = $tmpl->execute('stepediterror.tmpl.php');
						$result['modal'] = true;
					}
					print json_encode($result);
				} else {
					$popts = $out->parserOptions();
					$popts->setTidy(true);
					$parserOutput = $out->parse($this->newStep, $title, $popts);
					$boldedStep = WikihowArticleHTML::boldFirstSentence($parserOutput);

					$stepEditorHelperNew = new StepEditorParser($title, $newRevisionId, $out);

					$result['success'] = true;
					$result['step'] = $boldedStep;
					$result['newRevision'] = $newRevisionId;
					$result['isEditable'] = $stepEditorHelperNew->isEditable($this->stepNum);
					print json_encode($result);
				}
			}

			return;
		}

		$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
		return;
	}

	public static function onListDefinedTags( &$tags ) {
		$tags[] = StepEditor::STEP_TAG;
		return true;
	}
}

/*******************
 *
 *  Currently this does not support articles that use actual
 *  html markup (ol, li, etc), only wikitext markup for the
 *  steps list.
 *
 ******************/
class StepEditorParser {

	var $articleId;
	var $title;
	var $revisionNum;
	var $memKey;
	var $stepArray = null;
	var $hasEditableSteps = false;
	var $isLatestRevision;
	var $parsingMessage = "";
	var $out;

	//for captcha stuff
	static $isActive = false;
	static $captchaMessage;
	static $captchaForm;

	function __construct($title, $revisionId = 0, $out) {
		$this->articleId = $title->getArticleID();
		$this->out = $out;
		if ( $revisionId == 0) {
			$this->revisionNum = $title->getLatestRevID();
			$this->isLatestRevision = true;
		} else {
			$this->revisionNum = $revisionId;
			$this->isLatestRevision = ($title->getLatestRevID() == $revisionId);
		}
		$this->memKey = wfMemcKey("step_editor", $title->getArticleID(), $this->revisionNum);
		$this->title = $title;
	}

	/********
	 * Takes the wikitext for an article and parses it into an array of steps
	 *******/
	private function parseSteps() {
		global $wgMemc;

		if ($this->title->getArticleID() <= 0 || !$this->title->inNamespace(NS_MAIN)) {
			//if we're viewing a page that doesn't exist, we don't want to do any of this.
			$this->hasAnyEditableSteps = false;
			return;
		}

		$val = $wgMemc->get($this->memKey);
		if ($val) {
			$this->stepArray = $val;
			foreach ($this->stepArray as $step) {
				$this->hasEditableSteps = $this->hasEditableSteps || $step['isEligible'];
			}
			return;
		}

		$startTime = microtime(true); //used to check how long the parsing takes

		$revision = Revision::newFromPageId($this->articleId, $this->revisionNum);
		$content = $revision->getContent();
		$articleText = ContentHandler::getContentText($content);
		$stepsText = Wikitext::getStepsSection($articleText, true);
		$stepsText = Wikitext::stripHeader(trim($stepsText[0]));
		$stepsText = preg_replace('/===([^=]+)===/', '',$stepsText);

		$stepArray = array();
		$stepNum = 0;

		//remember to look into this $former_recursion_limit = ini_set( "pcre.recursion_limit", 90000 );
		$steps = preg_split('@^\s*#@m', rtrim($stepsText));
		for ($i = 1; $i < count($steps); $i++) {
			$isSubStep = self::isSubStep($steps[$i]);
			if (!$isSubStep) {
				$stepNum++;
				$stepArray[$stepNum] = array('step' => "#".$steps[$i], 'stepNum' => $stepNum, 'hasSubstep' => false, 'isEligible' => false);
			} else {
				$stepArray[$stepNum]['step'] .= "#".$steps[$i];
				$stepArray[$stepNum]['hasSubstep'] = true;
			}

			$steps[$i] = "#" . $steps[$i];
		}

		//now check all steps
		foreach ($stepArray as $key => $step) {
			$this->getStepInfo($stepArray[$key]);
			$stepArray[$key]['isEligible'] = $this->isEligibleForEditing($stepArray[$key]);
			$this->hasEditableSteps = $this->hasEditableSteps || $stepArray[$key]['isEligible'];
		}

		$executeTime = microtime(true) - $startTime;

		$this->parsingMessage = "<!-- step parsing took {$executeTime} microseconds -->";

		$wgMemc->set($this->memKey, $stepArray);

		$this->stepArray = $stepArray;
	}

	function getParsingMessage() {
		return $this->parsingMessage;
	}

	/****
	 *
	 * Given the wikitext for a step, gathers a much info about the
	 * step as possible. For use to determine if the step is
	 * eligble for editing
	 *
	 */
	private function getStepInfo(&$step) {
		$wikitext = $step['step'];
		//already checked for substeps earlier so no need to do here

		//check for images
		$matchCount = preg_match_all('@(\[\[Image:[^\]]*\]\])|(\{\{largeimage[^\}]*\}\})@im', $wikitext, $matches);
		$step['imageCount'] = $matchCount;
		if ($matchCount == 1) {
			$step['imageCount'] = $matchCount;
			$step['imageWikitext'] = $matches[0][0];
			//we're going to check if the image is a .largeimage on the client side
		}

		//replace image wikitext with random string so it won't get caught in further checks
		if ($matchCount > 0) {
			foreach($matches[0] as $image) {
				$wikitext = str_replace($image, Wikitext::genRandomString(), $wikitext);
			}
		}

		//check for templates, references, html comments, wikitext (bold/italic/etc), nowiki, <code>
		if (preg_match_all('@(\[\[)|(\{\{)|(<ref)|(<!--)|(\'\')|(<nowiki>)|(<code>)|(\[http://)|(\[https://)|(href=\"http://)|(href=\"https://)@im', $wikitext, $matches)) {
			$step['hasBadWikitext'] = true;
		} else {
			$step['hasBadWikitext']  = false;
		}

	}

	/*******
	 *
	 * Given a step number, returns whether that step is editable
	 * This is the only public method to determine this
	 *
	 *******/
	public function isEditable($stepNum) {
		//bebeth temp turn off 2/22
		return false;

		if ($this->stepArray == null) {
			$this->parseSteps();
		}

		if ($this->stepArray == null) {
			//not exactly sure how this would happen but just in case
			return false;
		}

		if (!array_key_exists($stepNum, $this->stepArray)) {
			return false;
		}

		return $this->stepArray[$stepNum]['isEligible'];
	}

	/************
	 * Given a step and all the data we've gathered on it,
	 * determines whether a step is editable. Does not take
	 * into account any article level issues (protected, etc)
	 ***********/
	private function isEligibleForEditing($step) {
		if ($step['hasSubstep']) {
			return false;
		}

		if ($step['imageCount'] > 1) {
			return false;
		}

		if ($step['hasBadWikitext']) {
			return false;
		}

		return true;
	}

	/*****
	 * Determines whether this article has any editible steps.
	 */
	public function hasAnyEditableSteps() {
		global $wgRequest, $wgUser;

		//Bebeth: turning of temporary 2/22
		return false;

		//slow rollout only on 50% of articles
		if ($this->title->getArticleID() % 2 != 1) {
			return false;
		}

		//only logged out for now
		/*if ($wgUser->getId() != 0) {
			return false;
		}*/

		if (!$this->title->inNamespace(NS_MAIN)) {
			return false;
		}

		//first check to see if the article is protected
		if ($this->title->isProtected()) {
			return false;
		}

		if ($wgRequest->getVal('oldid')) {
			return false;
		}
		if ($this->stepArray == null) {
			$this->parseSteps();
		}

		return $this->hasEditableSteps;
	}

	static function isSubStep($stepWikitext) {
		return ($stepWikitext[0] == "*" || $stepWikitext[0] == "#");
	}

	/***********
	 *
	 * Given the new text for the step, and the step number
	 * this function replaces the old step with the new one.
	 *
	 **********/
	public function replaceStep($newStep, $stepNum, $context) {
		if ($this->stepArray == null) {
			$this->parseSteps();
		}

		if (!$this->isLatestRevision) {
			return array(-1, null);
		}

		$oldStep = $this->stepArray[$stepNum]['step'];
		$newStep = $this->processStep($newStep, $this->stepArray[$stepNum]);

		$revision = Revision::newFromPageId($this->articleId, $this->revisionNum);
		$content = $revision->getContent();
		$wikitext = ContentHandler::getContentText($content);
		list($stepsText, $sectionID) = Wikitext::getStepsSection($wikitext, true);

		$newSteps = str_replace($oldStep, $newStep, $stepsText);

		$newWikitext = Wikitext::replaceStepsSection($wikitext, $sectionID, $newSteps, true);

		$wp = new WikiPage($this->title);
		$content = ContentHandler::makeContent( $newWikitext, $this->title );

		global $wgUser;
		$status = Status::newGood();

		$editpage = new EditPage(new Article($this->title));
		$hookerror = "";
		StepEditorParser::$isActive = true;
		// TODO: remove this block of code after upgrade - Reuben, 3/2019
		if (!ContentHandler::runLegacyHooks( 'EditFilterMerged',
			array( $editpage, $content, &$hookerror, "something" ) ) ) {
			//something failed, so let's check the captcha stuff
			$errorInfo['type'] = "message";
			$popts = $this->out->parserOptions();
			$popts->setTidy(true);
			$parserOutput = $this->out->parse(self::$captchaMessage, $this->title, $popts);
			$errorInfo['message'] = $parserOutput . " " . self::$captchaForm;
			return array(-1, $errorInfo);
		}

		$context->setTitle($this->title);

		if (!Hooks::run('EditFilterMergedContent', array($context, $content, &$status, '', $wgUser, false))) {
			return array(-1);
		}
		if (!$status->isGood()) {
			$errors = $status->getErrorsArray(true);
			foreach ($errors as $error) {
				if (is_array($error)) {
					$errortype = count($error) ? $error[0] : '';
				}
				if (preg_match('@^spamprotectiontext@', $errortype)) {
					//now grab all the links
					$errorInfo['type'] = "spam";
					$errorInfo['link'] = "";
					foreach ($errors as $linkerror) {
						if (is_array($error)) {
							$errortype = count($linkerror) ? $linkerror[0] : '';
						}
						if (preg_match('@^spamprotectionmatch@', $errortype)) {
							$errorInfo['link'] .= $linkerror[1]. " ";
						}
					}
					return array(-1, $errorInfo);
				} else {
					$errorInfo['type'] = 'message';
					$popts = $this->out->parserOptions();
					$popts->setTidy(true);
					$parserOutput = $this->out->parse($status->getWikiText(), $this->title, $popts);
					$errorInfo['message'] = $parserOutput;
					return array(-1, $errorInfo );
				}
			}

			return array(-1);
		}


		$result = $wp->doEditContent($content, "Editing from step editor");

		if ($result->isOK() && !empty( $result->value['revision']) ) {
			$newRevisionId = $result->value['revision']->getId();
			ChangeTags::addTags(StepEditor::STEP_TAG, null, $newRevisionId);
			return array($newRevisionId);
		}
		return -1;
	}

	/****************
	 *
	 * Given the new step and the info on the old step
	 * this function process that step, adding back any data
	 * or wikitext required to make the replacement.
	 *
	 ***************/
	private function processStep($stepText, $oldStepInfo) {
		//putting trim in b/c the html is adding a line break for some reason
		$newStep = "# ".trim($stepText);
		if ($oldStepInfo['imageCount']  == 1) {
			$newStep .= " " . $oldStepInfo['imageWikitext'];
		}
		$newStep .= "\n";

		return $newStep;
	}

	public static function onCaptchaEditCallback($message, $form) {
		if (self::$isActive) {
			self::$captchaMessage = $message;
			self::$captchaForm = $form;
			return false;
		}
		return true;
	}

}
