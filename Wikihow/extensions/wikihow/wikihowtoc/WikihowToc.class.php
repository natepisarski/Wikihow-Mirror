<?php

class WikihowToc {
	private static $methodAnchors = [];
	private static $methodNames = [];
	private static $references = null;
	private static $qanda = null;
	private static $summary = null;
	private static $videoSummary = null;
	private static $expertAdvice = null;
	private static $hasAnswers = false;
	private static $tipsandwarnings = null;
	private static $thingsyoullneed = null;
	private static $thingsyoullneedIsFirst = false;
	private static $relatedwHs = null;
	private static $ingredients = null;

	const MAX_ITEMS = 8;
	const MAX_METHODS = 3;
	const QA_EXPERT_MINIMUM = 2;

	const CONFIG_LIST_NAME = "new_toc";

	public static function setMethods($methodAnchors, $methodNames) {
		self::$methodAnchors = !empty($methodAnchors) ? $methodAnchors : [];
		self::$methodNames = !empty($methodNames) ? $methodNames : [];
	}

	public static function setTipsAndWarnings($hasTips) {
		if($hasTips) {
			self::$tipsandwarnings = ['url' => '#tips', 'id' => '', 'text' => wfMessage('tipsandwarnings')->text(), 'section' => '#tips'];
		} else {
			self::$tipsandwarnings = ['url' => '#warnings', 'id' => '', 'text' => wfMessage('tipsandwarnings')->text(), 'section' => '#warnings'];
		}
	}

	public static function setThingsYoullNeed($isFirst = false) {
		self::$thingsyoullneed = ['url' => '#thingsyoullneed', 'id' => '', 'text' => wfMessage('thingsyoullneed')->text(), 'section' =>' #thingsyoullneed'];
		self::$thingsyoullneedIsFirst = $isFirst;
	}

	public static function setIngredients() {
		self::$ingredients = ['url' => '#ingredients', 'id' => '', 'text' => wfMessage('ingredients')->text(), 'section' =>' #ingredients', 'class' => 'toc_pre'];
	}

	public static function setSummary() {
		if(Misc::isMobileMode()) {
			self::$summary = ['url' => '#summary_wrapper', 'id' => 'summary_toc', 'text' => wfMessage('summary_toc')->text(), 'section' => '#summary_wrapper'];
		} else {
			self::$summary = ['url' => '#', 'id' => 'summary_toc', 'text' => wfMessage('summary_toc')->text()];
		}
	}

// TODO what is this .. came in on master but conflict while mergin
	public static function setExpertAdvice(String $anchor = '', String $text = '') {
		self::$expertAdvice = ['url' => '#'.$anchor, 'id' => 'ea_toc', 'text' => $text];
	}

	public static function setSummaryVideo($isYoutube = false) {
		$anchor = '#'.WHVid::getVideoAnchor( RequestContext::getMain()->getTitle() );

		self::$videoSummary = [
			'url' => $anchor,
			'id' => 'summaryvideo_toc',
			'icon' => 'summaryvideo_icon',
			'text' => wfMessage('summaryvideo_toc')->text(),
			'section' => $anchor
		];
	}

	public static function isNewArticle() {
		$main = RequestContext::getMain();
		$user = $main->getUser();
		$title = $main->getTitle();

		if (!$title->exists() || $title->isRedirect() || !$title->inNamespace(NS_MAIN) || (!$user->isLoggedIn() && !RobotPolicy::isIndexable($title))) {
			return false;
		}

		return true;
	}

	public static function setQandA(array $articleQuestions) {
		$expertAnswers = 0;
		foreach ($articleQuestions as $aq) {
			if ($aq->getVerifierId() && !$aq->getInactive()) $expertAnswers++;
		}

		if ($expertAnswers > self::QA_EXPERT_MINIMUM) {
			$tocText = wfMessage('qa_expert_toc')->text();
			self::$hasAnswers = true;
		}
		elseif (count($articleQuestions) > 0) {
			$tocText = wfMessage('qa_toc')->text();
			self::$hasAnswers = true;
		} else {
			$tocText = wfMessage('qa_ask_toc')->text();
		}
		if(Misc::isMobileMode()) {
			if(count($articleQuestions) > 0) {
				self::$qanda = ['url' => '#qa_headline', 'id' => 'qa_toc', 'text' => $tocText, 'section' => '#qa_headline'];
			}
		} else {
			self::$qanda = ['url' => '#Questions_and_Answers_sub', 'id' => 'qa_toc', 'text' => $tocText];
		}
	}

	public static function setReferences() {
		$refCount = Misc::getReferencesCount();
		if ($refCount > 0) {
			$refTarget = Misc::getReferencesID();
			self::$references = ['url' => $refTarget, 'id' => 'toc_ref', 'text' => wfMessage("references_toc")->text(), 'section' => $refTarget];
			if ( !phpQuery::$defaultDocumentID )  {
				return;
			}
			if ( pq('#toc_ref')->length ) {
				pq('#toc_ref')->attr('href', $refTarget);
			}
		}
	}

	public static function addToc() {
		self::processMethodNames();

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
		$useSummary = $useVideoSummary = $useQandA = $useRwhs = $useReferences = $useExpertAdvice = false;
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
		if ($count < $secondaryShown && self::$expertAdvice != null) {
			$useExpertAdvice = true;
			$count++;
		}
		if ($count < $secondaryShown && !$useExpertAdvice && self::$hasAnswers && self::$qanda) {
			$useQandA = true;
			$count++;
		}
		if ($count < $secondaryShown) { //related wHs are always on the page
			$useRwhs = true;
			$count++;
		}
		if ($count < $secondaryShown && !$useExpertAdvice && !self::$hasAnswers && self::$qanda) {
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

		//Expert Advice
		if ($useExpertAdvice) {
			$data['toc'][] = self::$expertAdvice;
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

		$html = self::renderTemplate('toc.mustache', $data);

		if (pq('#expert_coauthor')->length > 0) {
			pq('#expert_coauthor')->after($html);
		} else {
			pq(".firstHeading")->after($html); //for times when there's no byline (old revisions, intl, etc)
		}

	}

	private static function getExpertAdvice() {
		$section_name = pq( '#expertadvice' )->length() ? 'expertadvice' : '';
		if ( $section_name == '' && pq( '#expertqampa' )->length() ) {
			$section_name = 'expertqampa';
		}
		if ( $section_name == '' ) {
			return null;
		}

		$section_text = pq('.'.$section_name)->find('h2 span.mw-headline')->text();

		$result = [
			'url' => '#'.$section_name,
			'id' => 'ea_toc',
			'section' => '#'.$section_name,
			'text' => $section_text
		];

		return $result;
	}

	public static function addMobileToc() {
		self::processMethodNames();

		$isNewArticle = self::isNewArticle();

		$methodsShown = $primaryCount = min(self::MAX_METHODS, count(self::$methodNames));

		$hasHidden = false;

		$data = ['toc' => []];

		if (count(self::$methodNames) > 0) {
			for ($i = 0; $i < $methodsShown; $i++) {
				$data['toc'][] = ['url' => "#".self::$methodAnchors[$i], 'class' => 'toc_method', 'text' => self::$methodNames[$i]];
			}
			for ($i = $methodsShown; $i < count(self::$methodNames); $i++) {
				$data['toc'][] = ['url' => "#".self::$methodAnchors[$i], 'class' => 'toc_method toc_hidden', 'text' => self::$methodNames[$i]];
				if ($isNewArticle) $hasHidden = true;
			}
		} else {
			$data['toc'][] = ['url' => '#Steps', 'class' => 'toc_method', 'text' => wfMessage("Steps")->text()];
			$primaryCount = 1;
		}

		if ($hasHidden) {
			$hiddenCount = count(self::$methodNames) - $methodsShown;
			$data['toc'][] = ['url' => '#', 'id' => 'toc_showmore', 'class' => 'toc_method toc_nav', 'text' => wfMessage("more_toc", $hiddenCount)->text()];
			$data['toc'][] = ['url' => '#', 'id' => 'toc_showless', 'class' => 'toc_method toc_nav', 'text' => wfMessage("less_toc")->text()];
			$primaryCount++;
		}

		if(self::isNewArticle()) {
			$secondaryShown = self::MAX_ITEMS - $primaryCount;

			//first deal with priority of the elements
			$count = 0;
			$tocIgnoreClass = " toc_ignore";
			$tocPost = " toc_post";

			if (self::$ingredients != null) {
				if ($count < $secondaryShown) {
					$count++;
				} else {
					self::$ingredients['class'] .= $tocIgnoreClass;
				}
			}
			if (self::$references != null) {
				if ( !strstr( self::$references['class'], $tocPost ) ) {
					self::$references['class'] .= $tocPost;
				}
				if ($count < $secondaryShown) {
					$count++;
				} else {
					self::$references['class'] .= $tocIgnoreClass;
				}
			}
			if (self::$summary != null) {
				self::$summary['class'] .= $tocPost;
				if ($count < $secondaryShown) {
					$count++;
				} else {
					self::$summary['class'] .= $tocIgnoreClass;
				}
			}
			if (self::$videoSummary != null) {
				self::$videoSummary['class'] .= $tocPost;
				if ($count < $secondaryShown) {
					$count++;
				} else {
					self::$videoSummary['class'] .= $tocIgnoreClass;
				}
			}
			self::$expertAdvice = self::getExpertAdvice();
			if (self::$expertAdvice) {
				self::$expertAdvice['class'] .= $tocPost;
				if ($count < $secondaryShown) {
					$count++;
				} else {
					self::$expertAdvice['class'] .= $tocIgnoreClass;
				}
			}
			if (self::$qanda != null && !self::$expertAdvice && self::$hasAnswers) {
				self::$qanda['class'] .= $tocPost;
				if ($count < $secondaryShown) {
					$count++;
				} else {
					self::$qanda['class'] .= $tocIgnoreClass;
				}
			}
			self::$relatedwHs = ['url' => '#relatedwikihows', 'id' => 'rwh_toc', 'text' => wfMessage('related_toc'), 'class' => $tocPost, 'section' => '#relatedwikihows'];
			if ($count < $secondaryShown) { //related wHs are always on the page
				$count++;
			} else {
				self::$relatedwHs['class'] .= $tocIgnoreClass;
			}
			if (self::$tipsandwarnings != null) {
				self::$tipsandwarnings['class'] .= $tocPost;
				if ($count < $secondaryShown) {
					$count++;
				} else {
					self::$tipsandwarnings['class'] .= $tocIgnoreClass;
				}
			}
			if (self::$thingsyoullneed != null) {
				self::$thingsyoullneed['class'] .= $tocPost;
				if ($count < $secondaryShown) {
					$count++;
				} else {
					self::$thingsyoullneed['class'] .= $tocIgnoreClass;
				}
			}

			//now put them in order
			if (self::$ingredients != null) {
				//goes before steps
				$data['toc'] = array_merge([self::$ingredients], $data['toc']);
			}

			if (self::$expertAdvice != null) {
				$data['toc'][] = self::$expertAdvice;
			}
			//q&a
			if (self::$qanda != null && !self::$expertAdvice) {
				$data['toc'][] = self::$qanda;
			}
			if (self::$videoSummary != null) {
				$data['toc'][] = self::$videoSummary;
			}
			if (self::$tipsandwarnings != null && !self::$thingsyoullneedIsFirst) {
				$data['toc'][] = self::$tipsandwarnings;
			}
			if (self::$thingsyoullneed != null) {
				$data['toc'][] = self::$thingsyoullneed;
			}
			if (self::$tipsandwarnings != null && self::$thingsyoullneedIsFirst) {
				$data['toc'][] = self::$tipsandwarnings;
			}
			//related wHs
			if (self::$relatedwHs != null) {
				$data['toc'][] = self::$relatedwHs;
			}

			//summary
			if (self::$references != null) {
				$data['toc'][] = self::$references;
			}
			if (self::$summary != null) {
				$data['toc'][] = self::$summary;
			}
		}

		if(RequestContext::getMain()->getLanguage()->getCode() != "en") {
			$data['toc_class'] = "intl_toc";
			$data['isIntl'] = 1;
			$data['title'] = wfMessage('toc_title')->text();

		} else {
			$data['title'] = wfMessage('title_toc')->text();
		}

		$html = self::renderTemplate('mobile_toc.mustache', $data);

		pq("#intro")->prepend($html);
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
			if(Misc::isMobileMode()) {
				$newMethodAnchors[] = self::$methodAnchors[$i];
			} else {
				$newMethodAnchors[] = self::$methodAnchors[$i] . "_sub";
			}
		}

		self::$methodAnchors = $newMethodAnchors;
		self::$methodNames = $newMethodNames;
	}

	private static function renderTemplate(string $template, array $vars): string {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		return $m->render($template, $vars);
	}

	public static function mobileToc(array $vars): string {
		$vars['title'] = wfMessage('title_toc')->text();
		return self::renderTemplate('mobile_toc.mustache', $vars);
	}
}
