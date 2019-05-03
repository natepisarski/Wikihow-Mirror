<?php

class SocialStamp {
	const MIN_AUTHOR = 9;
	const MIN_VIEWS = 1000;

	private static $verifiers = [];

	private static $hoverText = "";
	private static $byLineHtml = "";
	private static $isNotable = null;

	const NOTABLE_TAG = "notable_coauthor";

	public static function addDesktopByline($out) {
		if (!self::isEligibleForByline()) return;

		$html = self::getBylineHtml();

		pq('.firstHeading')->after($html);
	}

	private static function getBylineHtml() {
		if (self::$byLineHtml == "") {
			self::setBylineVariables();
		}

		return self::$byLineHtml;
	}

	private static function getHoverText() {
		if (self::$hoverText == "") {
			self::setBylineVariables();
		}

		return self::$hoverText;
	}

	private static function setBylineVariables() {
		$out = RequestContext::getMain()->getOutput();
		$isAmp = GoogleAmp::isAmpMode($out);
		$isMobile = Misc::isMobileMode();
		$articleId = $out->getTitle()->getArticleId();
		Hooks::run( 'BylineStamp', [ &self::$verifiers, $articleId ] );

		$params = self::setBylineData(self::$verifiers, $articleId, $isMobile, $isAmp, AlternateDomain::onAlternateDomain());
		$html = self::getHtmlFromTemplate('mobile_byline.mustache', $params);

		self::$hoverText = $params['body'];
		self::$byLineHtml = $html;
	}

	public static function getHoverTextForArticleInfo(){
		$text = trim(self::getHoverText());
		$brLoc = stripos($text, "<br");
		if ($brLoc !== false) {
			$text = substr($text, 0, $brLoc);
		} else {
			$learnmoreLoc = strripos($text, "</a>");
			if ($learnmoreLoc == strlen($text) - 4) {
				//remove the learn more
				$text = substr($text, 0, strripos($text, "<a"));
			}
		}

		return $text;
	}

	public static function addMobileByline(&$data){
		if (!self::isEligibleForByline()) return;

		Hooks::run( 'BylineStamp', [ &self::$verifiers, $data['articleid'] ] );

		$html = self::getBylineHtml();

		$data['prebodytext'] .= $html;
	}

	private static function isEligibleForByline() {
		$main = RequestContext::getMain();

		$revision = $main->getRequest()->getVal('oldid', "");
		if ($revision != "") return false;

		$title = $main->getTitle();
		if (!$title->inNamespace(NS_MAIN) || $title->isMainPage() || $title->getArticleID() <= 0) return false;

		if ($title->isRedirect()) return false;

		if (!PagePolicy::showCurrentTitle($main)) return false;

		return true;
	}

	private static function setBylineData($verifiers, $articleId, $isMobile, $isAmp, $isAlternateDomain) {
		$params = [];

		$isExpert = false; // expert | academic | youtuber
		$isStaff = false;
		$isCommunity = false;
		$isTested = false; // tech | video | chef
		$isUserReview = false;
		$isDefault = false;

		$hoverText = "";

		$refsUrl = "#sourcesandcitations";
		if ( $isMobile ) {
			$refsUrl = "#references_first";
		} else if ( pq( '#references' )->length > 0 ) {
			$refsUrl = "#references";
		}

		# First part (slot1)

		$params["coauthor"] = wfMessage('sp_expert_attribution')->text();
		$params["connector"] = "<span class='ss_pipe'>|</span>";
		$params['check'] = "ss_check";
		$params['oldToc'] = (class_exists('WikihowToc') && WikihowToc::isNewArticle()) ? "" : "old_toc";

		$refsCount = Misc::getReferencesCount();
		$params['refsCount'] = $refsCount;
		$params['refsUrl'] = $refsUrl;
		$params['linkUrl'] = $isMobile ? "social_proof_anchor" : "article_info_section";
		$hasEnoughRefsForByline = ($refsCount >= SocialProofStats::DISPLAY_CITATIONS_LIMIT);

		// expert
		if ( array_key_exists(SocialProofStats::VERIFIER_TYPE_EXPERT, $verifiers)) {
			$key = SocialProofStats::VERIFIER_TYPE_EXPERT;
			$isExpert = true;
		}
		// academic
		elseif ( array_key_exists(SocialProofStats::VERIFIER_TYPE_ACADEMIC, $verifiers)) {
			$key = SocialProofStats::VERIFIER_TYPE_ACADEMIC;
			$isExpert = true;
		}
		// youtuber
		elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_YOUTUBER, $verifiers)) {
			$key = SocialProofStats::VERIFIER_TYPE_YOUTUBER;
			$isExpert = true;
		}
		// community
		elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_COMMUNITY, $verifiers)) {
			$key = SocialProofStats::VERIFIER_TYPE_COMMUNITY;
			$isCommunity = true;
		}
		// staff
		elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_STAFF, $verifiers)) {
			$key = SocialProofStats::VERIFIER_TYPE_STAFF;
			$params['slot1'] = self::getIntroInfo($key, $verifiers[$key]);
			$params['slot1class'] = "staff_icon";
			if ($hasEnoughRefsForByline) {
				$params['showBylineRefs'] = true;
			}
			$isStaff = true;
		}
		// default
		else {
			$params['slot1'] = self::GetIntroInfo(SocialProofStats::VERIFIER_TYPE_AUTHORS);
			unset($params["coauthor"]);
			$params['slot1class'] = "author_icon";
			$params["check"] = "ss_info";
			if ($hasEnoughRefsForByline) {
				$params['showBylineRefs'] = true;
			}
			$isDefault = true;
		}

		if ($isExpert || $isCommunity) {
			$params['slot1'] = self::getIntroInfo($key, $verifiers[$key]);
			$params['slot1class'] = "expert_icon";
			if (SocialProofStats::isSpecialInline()) {
				$params['coauthor'] = wfMessage("ss_special_author")->text();
			}
			if (SocialStamp::isNotable()) {
				$params['coauthor'] = wfMessage("ss_notable")->text();
			}
		}

		# Second part (slot2), only if no expert
		else {
			// tech
			if (array_key_exists(SocialProofStats::VERIFIER_TYPE_TECH, $verifiers)) {
				$testKey = SocialProofStats::VERIFIER_TYPE_TECH;
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_tested')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_TECH);
				$params['slot2class'] = 'ss_tech';
				$isTested = true;
			}
			// video
			elseif (array_key_exists(SocialProofStats::VERIFIER_TYPE_VIDEO, $verifiers)) {
				$testKey = SocialProofStats::VERIFIER_TYPE_VIDEO;
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_tested')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_VIDEO);
				$params['slot2class'] = 'ss_video';
				$isTested = true;
			}
			// chef
			elseif (array_key_exists(SocialProofStats::VERIFIER_TYPE_CHEF, $verifiers)) {
				$testKey = SocialProofStats::VERIFIER_TYPE_CHEF;
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_tested')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_CHEF);
				$params['slot2class'] = 'ss_video';
				$isTested = true;
			}
			// user_review
			elseif (array_key_exists(SocialProofStats::VERIFIER_TYPE_READER, $verifiers)) {
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_approved')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_READER);
				$params['slot2class'] = 'ss_review';
				$hoverText .= UserReview::getIconHoverText($articleId);
				$isUserReview = true;
			}
			if ($isDefault) {
				$params['slot2_intro'] = ucfirst($params['slot2_intro']);
			}

			if (isset($params['hasSlot2']) && $isMobile) {
				unset($params['showBylineRefs']);
			}
		}

		if (class_exists('WikihowToc') && !isset($params['showBylineRefs'])) {
			WikihowToc::setReferences();
		}

		# Hover text

		if ($isExpert) {
			$vData = $verifiers[$key];
			$link = ArticleReviewers::getLinkByVerifierName($vData->name);

			if ($refsCount >= SocialProofStats::MESSAGE_CITATIONS_LIMIT) {
				$citations = wfMessage('ss_expert_citations', $refsCount, $refsUrl)->text();
			}
			$coauthor = lcfirst(wfMessage("sp_expert_attribution")->text());
			if (SocialProofStats::isSpecialInline()) {
				$coauthor = lcfirst(wfMessage("ss_special_author")->text());
			}
			$hoverText = wfMessage('ss_expert', $vData->name, $vData->hoverBlurb, $link, $citations, $coauthor )->text();
		} elseif ($isCommunity) {
			if ($refsCount >= SocialProofStats::MESSAGE_CITATIONS_LIMIT) {
				$citations = wfMessage('ss_expert_citations', $refsCount, $refsUrl)->text();
			}
			$hoverText = wfMessage("ss_community", $verifiers[$key]->name, $verifiers[$key]->hoverBlurb, $citations)->text();
		} elseif ($isStaff) {
			if ($refsCount >= SocialProofStats::MESSAGE_CITATIONS_LIMIT) {
				$citations = wfMessage('ss_staff_citations', $refsCount, $refsUrl)->text();
			}

			if ($isTested) {
				$hoverText = wfMessage('ss_staff_tested', $citations, self::getHoverInfo($testKey))->text();
			} elseif ($isUserReview) {
				$hoverText = wfMessage('ss_staff_readers', $citations, UserReview::getIconHoverText($articleId))->text();
			} else {
				$hoverText = wfMessage('ss_staff', $citations)->text();
			}
		} elseif ($isDefault) {
			if ($refsCount >= SocialProofStats::MESSAGE_CITATIONS_LIMIT) {
				$citations = wfMessage('ss_default_citations', $refsCount, $refsUrl)->text();
			}
			$numEditors = count(ArticleAuthors::getAuthors($articleId));
			if ($numEditors >= self::MIN_AUTHOR) {
				$editorBlurb = wfMessage('ss_editors_big', $numEditors)->text();
			} else {
				$editorBlurb = wfMessage('ss_editors_small', $numEditors)->text();
			}
			$views = RequestContext::getMain()->getWikiPage()->getCount();
			if ($isTested) {
				$hoverText = wfMessage("ss_default_tested", $editorBlurb, $citations, self::getHoverInfo($testKey) )->text();
			} elseif ($isUserReview) {
				$hoverText = wfMessage("ss_default_readers", $editorBlurb, $citations, UserReview::getIconHoverText($articleId) )->text();
			} else {
				if ($views > self::MIN_VIEWS) {
					$viewText = wfMessage("ss_default_views", number_format($views))->text();
				}
				$hoverText = wfMessage('ss_default', $editorBlurb, $citations, $viewText)->text();
			}
		}

		$params = array_merge($params, self::getIconHoverVars($hoverText, $isMobile, $isAmp, $isExpert, $isAlternateDomain));

		return $params;
	}

	private static function getHtmlFromTemplate($template, $data) {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$html = $m->render($template, $data);
		return $html;
	}

	private static function getIconHoverVars(string $hover_text, bool $is_mobile, bool $amp, bool $isExpert, bool $isAlternateDomain) {
		$vars = [
			'header' => wfMessage('sp_hover_expert_header')->text(),
			'body' => $hover_text,
			'mobile' => $is_mobile,
			'amp' => $amp
		];

		if (!$isExpert && !$isAlternateDomain) {
			$vars['learn_more_link'] = SocialProofStats::LEARN_MORE_LINK;
			$vars['learn_more'] = wfMessage('sp_learn_more')->text();
		}

		return $vars;
	}

	public static function getIntroMessage($vType) {
		$introMessage = 'sp_intro_' . $vType;
		$message = wfMessage( $introMessage );
		return $message->exists() ? $message->text() : '';
	}

	private static function getIntroInfo($vType, $vData = null) {
		if (in_array($vType, [SocialProofStats::VERIFIER_TYPE_YOUTUBER, SocialProofStats::VERIFIER_TYPE_ACADEMIC, SocialProofStats::VERIFIER_TYPE_EXPERT])) {
			return $vData->name;
		} elseif ( $vType == SocialProofStats::VERIFIER_TYPE_TECH) {
			return wfMessage("ss_tech_name")->text();
		} elseif ( $vType == SocialProofStats::VERIFIER_TYPE_VIDEO) {
			return wfMessage("ss_video_name")->text();
		} elseif ( $vType == SocialProofStats::VERIFIER_TYPE_READER) {
			return wfMessage("ss_reader_name")->text();
		} elseif ($vType == SocialProofStats::VERIFIER_TYPE_STAFF) {
			return wfMessage("ss_staff_name")->text();
		} elseif ($vType == SocialProofStats::VERIFIER_TYPE_AUTHORS) {
			return wfMessage("ss_author_name")->text();
		} elseif ($vType == SocialProofStats::VERIFIER_TYPE_CHEF) {
			return wfMessage("ss_chef_name")->text();
		} elseif ($vType == SocialProofStats::VERIFIER_TYPE_COMMUNITY) {
			return $vData->name;
		}
	}

	private static function getHoverInfo($vType, $Data = null) {
		if ( $vType == SocialProofStats::VERIFIER_TYPE_TECH) {
			return wfMessage("ss_tech_name_hover")->text();
		} elseif ( $vType == SocialProofStats::VERIFIER_TYPE_VIDEO) {
			return wfMessage("ss_video_name_hover")->text();
		} if ($vType == SocialProofStats::VERIFIER_TYPE_CHEF) {
			return wfMessage("ss_chef_name_hover")->text();
		}
	}

	public static function isNotable() {
		if (!is_null(self::$isNotable)) return self::$isNotable;
		$context = RequestContext::getMain();
		$pageId = $context->getTitle()->getArticleId();
		self::$isNotable = ArticleTagList::hasTag(self::NOTABLE_TAG, $pageId);
		return self::$isNotable;
	}
}
