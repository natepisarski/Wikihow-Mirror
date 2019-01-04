<?php

class SocialStamp {
	const MIN_AUTHOR = 9;
	const MIN_VIEWS = 1000;

	private static $verifiers = [];

	public static function addDesktopByline($out) {
		if(!self::isEligibleForByline()) return;

		$articleId = $out->getTitle()->getArticleId();
		wfRunHooks( 'BylineStamp', [ &self::$verifiers, $articleId ] );

		$params = self::setBylineData(self::$verifiers, $articleId, false, false, AlternateDomain::onAlternateDomain());
		$html = self::getHtmlFromTemplate('mobile_byline', $params);

		pq('.firstHeading')->after($html);
	}

	public static function addMobileByline(&$data){
		if(!self::isEligibleForByline()) return;

		wfRunHooks( 'BylineStamp', [ &self::$verifiers, $data['articleid'] ] );

		$isAmp = GoogleAmp::isAmpMode(RequestContext::getMain()->getOutput());

		$params = self::setBylineData(self::$verifiers, $data['articleid'], true, $isAmp, AlternateDomain::onAlternateDomain());
		$html = self::getHtmlFromTemplate('mobile_byline', $params);

		$data['prebodytext'] .= $html;
	}

	private static function isEligibleForByline() {
		$main = RequestContext::getMain();

		$revision = $main->getRequest()->getVal('oldid', "");
		if($revision != "") return false;

		$title = $main->getTitle();
		if(!$title->inNamespace(NS_MAIN) || $title->isMainPage() || $title->getArticleID() <= 0) return false;

		if($title->isRedirect()) return false;

		if(!PagePolicy::showCurrentTitle($main)) return false;

		return true;
	}

	private function setBylineData($verifiers, $articleId, $isMobile, $isAmp, $isAlternateDomain) {
		$params = [];
		$hasExpert = $hasStaff = $hasCommunity = $isTested = $hasReaders = false;
		$isDefault = false;
		$hoverText = "";
		$referenceLink = $isMobile ? "#references_first" : "#sourcesandcitations";
		//first part
		$params["coauthor"] = wfMessage('ss_coauthor')->text();
		$params["connector"] = "<span class='ss_pipe'>|</span>";
		$params['check'] = "ss_check";
		$numCitations = Misc::getReferencesCount();
		$referencesEligible = false;
		if($numCitations >= SocialProofStats::DISPLAY_CITATIONS_LIMIT) {
			$referencesEligible = true;
		}
		$params['references'] = $numCitations;
		$params['referencesUrl'] = $referenceLink;

		if ( array_key_exists(SocialProofStats::VERIFIER_TYPE_EXPERT, $verifiers)) {
			$key = SocialProofStats::VERIFIER_TYPE_EXPERT;
			$hasExpert = true;
		} elseif ( array_key_exists(SocialProofStats::VERIFIER_TYPE_ACADEMIC, $verifiers)) {
			$key = SocialProofStats::VERIFIER_TYPE_ACADEMIC;
			$hasExpert = true;
		} elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_YOUTUBER, $verifiers)) {
			$key = SocialProofStats::VERIFIER_TYPE_YOUTUBER;
			$hasExpert = true;
		} elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_COMMUNITY, $verifiers)) {
			$key = SocialProofStats::VERIFIER_TYPE_COMMUNITY;
			$hasCommunity = true;
		} elseif ( array_key_exists( SocialProofStats::VERIFIER_TYPE_STAFF, $verifiers)) {
			$key = SocialProofStats::VERIFIER_TYPE_STAFF;
			$params['slot1'] = self::getIntroInfo($key, $verifiers[$key]);
			$params['slot1class'] = "staff_icon";
			if($referencesEligible) {
				$params['hasReferences'] = true;
			}
			$hasStaff = true;
		} else {
			$params['slot1'] = self::GetIntroInfo(SocialProofStats::VERIFIER_TYPE_AUTHORS);
			unset($params["coauthor"]);
			$params['slot1class'] = "author_icon";
			$params["check"] = "ss_info";
			if($referencesEligible) {
				$params['hasReferences'] = true;
			}
			$isDefault = true;
		}

		if($hasExpert || $hasCommunity) {
			$params['slot1'] = self::getIntroInfo($key, $verifiers[$key]);
			$params['slot1class'] = "expert_icon";
			if(SocialProofStats::isSpecialInline()) {
				$params['coauthor'] = wfMessage("ss_special_author")->text();
			}
		} else {
			//second part, only if no expert
			if (array_key_exists(SocialProofStats::VERIFIER_TYPE_TECH, $verifiers)) {
				$testKey = SocialProofStats::VERIFIER_TYPE_TECH;
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_tested')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_TECH);
				$isTested = true;
			} elseif (array_key_exists(SocialProofStats::VERIFIER_TYPE_VIDEO, $verifiers)) {
				$testKey = SocialProofStats::VERIFIER_TYPE_VIDEO;
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_tested')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_VIDEO);
				$isTested = true;
			} elseif (array_key_exists(SocialProofStats::VERIFIER_TYPE_CHEF, $verifiers)) {
				$testKey = SocialProofStats::VERIFIER_TYPE_CHEF;
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_tested')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_CHEF);
				$isTested = true;
			} elseif (array_key_exists(SocialProofStats::VERIFIER_TYPE_READER, $verifiers)) {
				$params['hasSlot2'] = true;
				$params['slot2_intro'] = wfMessage('ss_approved')->text();
				$params['slot2'] = self::getIntroInfo(SocialProofStats::VERIFIER_TYPE_READER);
				$hoverText .= UserReview::getIconHoverText($articleId);
				$hasReaders = true;
			}
			if($isDefault) {
				$params['slot2_intro'] = ucfirst($params['slot2_intro']);
			}

			if(isset($params['hasSlot2']) && $isMobile) {
				unset($params['hasReferences']);
			}
		}

		//now get the hovers
		if($hasExpert) {
			$vData = $verifiers[$key];
			$link = ArticleReviewers::getLinkByVerifierName($vData->name);

			if($numCitations >= SocialProofStats::MESSAGE_CITATIONS_LIMIT) {
				$citations = wfMessage('ss_expert_citations', $numCitations, $referenceLink)->text();
			}
			$coauthor = lcfirst(wfMessage("ss_coauthor")->text());
			if(SocialProofStats::isSpecialInline()) {
				$coauthor = lcfirst(wfMessage("ss_special_author")->text());
			}

			$hoverText = wfMessage('ss_expert', $vData->name, $vData->hoverBlurb, $link, $citations, $coauthor )->text();
		} elseif($hasCommunity) {
			if($numCitations >= SocialProofStats::MESSAGE_CITATIONS_LIMIT) {
				$citations = wfMessage('ss_expert_citations', $numCitations, $referenceLink)->text();
			}
			$hoverText = wfMessage("ss_community", $verifiers[$key]->name, $verifiers[$key]->hoverBlurb, $citations)->text();
		} elseif ($hasStaff) {
			if($numCitations >= SocialProofStats::MESSAGE_CITATIONS_LIMIT) {
				$citations = wfMessage('ss_staff_citations', $numCitations, $referenceLink)->text();
			}

			if($isTested) {
				$hoverText = wfMessage('ss_staff_tested', $citations, self::getHoverInfo($testKey))->text();
			} elseif ($hasReaders) {
				$hoverText = wfMessage('ss_staff_readers', $citations, UserReview::getIconHoverText($articleId))->text();
			} else {
				$hoverText = wfMessage('ss_staff', $citations)->text();
			}
		} elseif ($isDefault) {
			if($numCitations >= SocialProofStats::MESSAGE_CITATIONS_LIMIT) {
				$citations = wfMessage('ss_default_citations', $numCitations, $referenceLink)->text();
			}
			$numEditors = count(ArticleAuthors::getAuthors($articleId));
			if($numEditors >= self::MIN_AUTHOR) {
				$editorBlurb = wfMessage('ss_editors_big', $numEditors)->text();
			} else {
				$editorBlurb = wfMessage('ss_editors_small', $numEditors)->text();
			}
			$views = RequestContext::getMain()->getWikiPage()->getCount();
			if($isTested) {
				$hoverText = wfMessage("ss_default_tested", $editorBlurb, $citations, self::getHoverInfo($testKey) )->text();
			} elseif($hasReaders) {
				$hoverText = wfMessage("ss_default_readers", $editorBlurb, $citations, UserReview::getIconHoverText($articleId) )->text();
			} else {
				if($views > self::MIN_VIEWS) {
					$viewText = wfMessage("ss_default_views", number_format($views))->text();
				}
				$hoverText = wfMessage('ss_default', $editorBlurb, $citations, $viewText)->text();
			}
		}

		$params = array_merge($params, self::getIconHoverVars($hoverText, $isMobile, $isAmp, $hasExpert, $isAlternateDomain));

		return $params;
	}

	private function getHtmlFromTemplate($template, $data) {
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

		if(!$isExpert && !$isAlternateDomain) {
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

	public static function getIntroInfo($vType, $vData = null) {
		if(in_array($vType, [SocialProofStats::VERIFIER_TYPE_YOUTUBER, SocialProofStats::VERIFIER_TYPE_ACADEMIC, SocialProofStats::VERIFIER_TYPE_EXPERT])) {
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

	public static function getHoverInfo($vType, $Data = null) {
		if ( $vType == SocialProofStats::VERIFIER_TYPE_TECH) {
			return wfMessage("ss_tech_name_hover")->text();
		} elseif ( $vType == SocialProofStats::VERIFIER_TYPE_VIDEO) {
			return wfMessage("ss_video_name_hover")->text();
		} if ($vType == SocialProofStats::VERIFIER_TYPE_CHEF) {
			return wfMessage("ss_chef_name_hover")->text();
		}
	}
}