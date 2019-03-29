<?php

class WikihowToc {
	private static $methodAnchors = [];
	private static $methodNames = [];
	private static $references = null;
	private static $qanda = null;
	private static $summary = null;
	private static $videoSummary = null;
	private static $hasAnswers = false;

	const MAX_ITEMS = 8;
	const MAX_METHODS = 3;

	const CONFIG_LIST_NAME = "new_toc";

	public static function setMethods($methodAnchors, $methodNames) {

		self::$methodAnchors = $methodAnchors;
		self::$methodNames = $methodNames;
	}

	public static function setSummary() {
		self::$summary = ['url' => '#', 'id' => 'summary_toc', 'text' => wfMessage('summary_toc')->text()];
	}

	public static function setSummaryVideo() {
		self::$videoSummary = ['url' => '#quick_summary_section', 'id' => 'summaryvideo_toc', 'text' => wfMessage('summaryvideo_toc')->text()];
	}

	public static function isNewArticle() {
		$main = RequestContext::getMain();
		$user = $main->getUser();
		$title = $main->getTitle();
		$languageCode = $main->getLanguage()->getCode();

		if (!$title->exists() || $title->isRedirect() || !$title->inNamespace(NS_MAIN) || (!$user->isLoggedIn() && !RobotPolicy::isIndexable($title))) {
			return false;
		}

		if ($languageCode == "en" || $languageCode == "qqx") {
			return true;
		} else {
			return $title->getArticleID() % 2 == 0;
		}
	}

	public static function setQandA($hasAnsweredQuestions) {
		if ($hasAnsweredQuestions) {
			$tocText = wfMessage('qa_toc')->text();
			self::$hasAnswers = true;
		} else {
			$tocText = wfMessage('qa_ask_toc')->text();
		}
		self::$qanda = ['url' => '#Questions_and_Answers_sub', 'id' => 'qa_toc', 'text' => $tocText];
	}

	public static function setReferences() {
		if (!Misc::isMobileMode()) {
			$refCount = Misc::getReferencesCount();
			$refTarget = '#' . Misc::getSectionName( wfMessage('sources')->text() );
			if ( phpQuery::$defaultDocumentID ) {
				$references = '#' . Misc::getSectionName( wfMessage('references')->text() );
				if ( pq( $references )->length > 0 ) {
					$refTarget = $references;
				}
			}
			if($refCount > 0) {
				self::$references = ['url' => $refTarget, 'class' => 'toc_ref', 'text' => wfMessage("references_toc")->text()];
			}
		}
	}

	public static function addToc() {
		self::processMethodNames();

		//remove this later, but for now we need it
		global $wgLanguageCode;
		if ($wgLanguageCode != "en") {
			self::setReferences();
		}

		$methodsShown = $primaryCount = min(self::MAX_METHODS, count(self::$methodNames));

		$hasHidden = false;

		$data = ['toc' => []];

		if (count(self::$methodNames) > 0) {
			for ($i = 0; $i < $methodsShown; $i++) {
				$data['toc'][] = ['url' => "#".self::$methodAnchors[$i], 'class' => 'toc_method', 'text' => self::$methodNames[$i]];
			}
			for ($i = $methodsShown; $i < count(self::$methodNames); $i++) {
				$data['toc'][] = ['url' => "#".self::$methodAnchors[$i], 'class' => 'toc_method hidden', 'text' => self::$methodNames[$i]];
				$hasHidden = true;
			}
		} else {
			$data['toc'][] = ['url' => '#Steps', 'class' => 'toc_method', 'text' => wfMessage("Steps")->text()];
			$primaryCount = 1;
		}

		if ($hasHidden) {
			$hiddenCount = count(self::$methodNames) - $methodsShown;
			$data['toc'][] = ['url' => '#', 'id' => 'toc_showmore', 'class' => 'toc_nav', 'text' => wfMessage("more_toc", $hiddenCount)->text()];
			$data['toc'][] = ['url' => '#', 'id' => 'toc_showless', 'class' => 'toc_nav', 'text' => wfMessage("less_toc")->text()];
			$primaryCount++;
		}

		$secondaryShown = self::MAX_ITEMS - $primaryCount;

		//first deal with priority of the elements
		$count = 0;
		$useSummary = $useVideoSummary = $useQandA = $useRwhs = $useReferences = false;
		if ($count < $secondaryShown && self::$summary != null) {
			$useSummary = true;
			$count++;
		}
		if ($count < $secondaryShown && self::$references != null) {
			$useReferences = true;
			$count++;
		}
		if ($count < $secondaryShown && self::$videoSummary != null) {
			$useVideoSummary = true;
			$count++;
		}
		if ($count < $secondaryShown && self::$hasAnswers && self::$qanda) {
			$useQandA = true;
			$count++;
		}
		if ($count < $secondaryShown) { //related wHs are always on the page
			$useRwhs = true;
			$count++;
		}
		if ($count < $secondaryShown && !self::$hasAnswers && self::$qanda) {
			$useQandA = true;
			$count++;
		}

		//now put them in order
		//summary
		if ($useSummary) {
			$data['toc'][] = self::$summary;
		}

		if ($useVideoSummary) {
			$data['toc'][] = self::$videoSummary;
		}

		//q&a
		if ($useQandA) {
			$data['toc'][] = self::$qanda;
		}

		//related wHs
		if ($useRwhs) {
			$data['toc'][] = ['url' => '#relatedwikihows', 'id' => 'rwh_toc', 'text' => wfMessage('related_toc')];
		}

		if ($useReferences) {
			$data['toc'][] = self::$references;
		}

		$data['title'] = wfMessage('title_toc')->text();

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__)
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$html = $m->render('toc', $data);

		if (pq('#expert_coauthor')->length > 0) {
			pq('#expert_coauthor')->after($html);
		} else {
			pq(".firstHeading")->after($html); //for times when there's no byline (old revisions, intl, etc)
		}

	}

	static function processMethodNames() {
		$newMethodNames = [];
		$newMethodAnchors = [];
		for ( $i = 0; $i < count( self::$methodAnchors ); $i++ ) {
			$methodName = self::$methodNames[$i];
			//get rid of any links inside the title
			$methodName = pq( '<div>' . $methodName . '</div>' )->text();
			// remove any reference notes
			$methodName = preg_replace( "@\[\d{1,3}\]$@", "", $methodName );
			if ( $methodName == "" ) {
				continue;
			}
			$methodName = htmlspecialchars( $methodName );

			$newMethodNames[] = $methodName;
			$newMethodAnchors[] = self::$methodAnchors[$i]."_sub";
		}

		self::$methodAnchors = $newMethodAnchors;
		self::$methodNames = $newMethodNames;
	}
}
