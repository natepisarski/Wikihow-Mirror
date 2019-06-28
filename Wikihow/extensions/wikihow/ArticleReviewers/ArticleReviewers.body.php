<?php

class ArticleReviewers extends UnlistedSpecialPage {

	const REVIEWER_ROWS = 2;
	const MEDICAL_EXCLUSION_LIST = "medical_exclusions";

	public function __construct() {
		global $wgHooks;
		parent::__construct('ArticleReviewers');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	public function execute($par) {
		global $wgHooks;

		$wgHooks['CustomSideBar'][] = array($this, 'makeCustomSideBar');
		$wgHooks['ShowBreadCrumbs'][] = array($this, 'removeBreadCrumbsCallback');

		$req = $this->getRequest();
		$out = $this->getOutput();

		$out->setHTMLTitle(wfMessage('ar_page_title')->text());

		$expertArticles = VerifyData::getAllArticlesFromDB();
		$experts = VerifyData::getAllVerifierInfoFromDB();

		$expertCategories = array();

		$userCount = array();
		$activeUsers = []; // Experts that reviewed an article after $oldestAllowed
		$oldestAllowed = new DateTime('-180 days');
		foreach ($expertArticles as $article) {
			if (!isset($userCount[$article->name])) {
				$userCount[$article->name] = 0;
			}
			$userCount[$article->name]++;

			if (!isset($activeUsers[$article->name])) {
				$reviewDate = DateTime::createFromFormat('n/j/Y', $article->date);
				if ($reviewDate === false || $reviewDate > $oldestAllowed) {
					$activeUsers[$article->name] = true;
				}
			}
		}

		$medicalExceptions = explode("\n", ConfigStorage::dbGetConfig(self::MEDICAL_EXCLUSION_LIST, true));
		$requestedName = $req->getText('name');
		$expert_count = 0;
		$isIntl = Misc::isIntl();
		foreach ($experts as $expert) {

			if ( $expert->category == 'categ_community' ) {
				continue;
			}

			if ( $isIntl && !($expert->blurb && $expert->hoverBlurb) ) { // skip non-translated
				continue;
			}

			// see if the nameLink is not a url. if it isn't we will add it as nameLinkHTML instead
			if ( trim( $expert->nameLink ) && !filter_var( trim( $expert->nameLink ), FILTER_VALIDATE_URL ) ) {
				$expert->nameLinkHTML = $expert->nameLink;
				$expert->nameLink = '';
			}

			if ( !isset($expertCategories[$expert->category]) ) {
				$expertCategories[$expert->category] = array();
				$expertCategories[$expert->category]['count'] = 0;
			}

			// Filter out experts with no recent reviews
			$anchorName = ArticleReviewers::getAnchorName($expert->name);
			if ($expert->category == 'categ_medical_review_board'
					&& in_array($expert->verifierId, $medicalExceptions)
					&& $anchorName !== $requestedName) {
				continue;
			}

			if ( !isset($expertCategories[$expert->category][$expert->name]) ) {
				$expertCategories[$expert->category][$expert->name] = array();

				$expert->imagePath = wfGetPad( $expert->imagePath );
				$expert->anchorName = $anchorName;
				$expertCategories[$expert->category][$expert->name]['expert'] = $expert;
				$expertCategories[$expert->category][$expert->name]['count'] = 0;
			}

			$expertCategories[$expert->category][$expert->name]['count'] = $userCount[$expert->name];
			$expertCategories[$expert->category]['count'] += $userCount[$expert->name];
			$expert_count++;
		}

		uasort($expertCategories, "ArticleReviewers::cmp");
		$expertCategories = [ 'categ_medical_review_board' => $expertCategories['categ_medical_review_board'] ?? [] ] + $expertCategories;
		$expertCategories = [ 'categ_notable_reviewers' => $expertCategories['categ_notable_reviewers'] ?? [] ] + $expertCategories;
		$localizedCategs = [];
		foreach ($expertCategories as $category => $catArray) {
			if ($category == "categ_notable_reviewers") {
				uasort($expertCategories[$category], "ArticleReviewers::cmpNotable");
			} else {
				uasort($expertCategories[$category], "ArticleReviewers::cmp");
			}
			$localizedCateg = wfMessage($category)->text();
			$localizedCategs[$localizedCateg] = $expertCategories[$category];
		}

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array('numRows' => self::REVIEWER_ROWS));
		$tmpl->set_vars(array('expertCategories' => $localizedCategs));
		if (Misc::isMobileMode()) {
			$out->addModules("ext.wikihow.mobilearticlereviewers");
			$out->addHTML($tmpl->execute('mobilereviewers.tmpl.php'));
		} else {
			$out->addModules(["ext.wikihow.articlereviewers","ext.wikihow.articlereviewers_script"]);
			$out->addHTML($tmpl->execute('reviewers.tmpl.php'));
			$out->getSkin()->addWidget($this->getSideBar($localizedCategs, $expert_count), 'ar_sidebar');
		}
	}

	private function getSideBar($expertCategories, $expert_count) {
		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars([
			'expertCategories' => $expertCategories,
			'expert_count' => $expert_count,
			'view_all' => wfMessage('ar_view_all')->text()
		]);
		$html = $tmpl->execute('reviewers_sidebar.tmpl.php');
		return $html;
	}

	static function cmp($a, $b) {
		if ($a['count'] == $b['count'] ) return 0;

		return ($a['count'] < $b['count']) ? 1 : -1;
	}

	static function cmpNotable($a, $b) {
		if ($a['count'] == $b['count'] ) return 0;

		return ($a['count'] < $b['count']) ? -1 : 1;
	}

	public static function makeCustomSideBar(&$customSideBar) {
		$customSideBar = true;
		return true;
	}

	public static function getAnchorName($verifierName) {
		return strtolower( str_replace(' ', '', $verifierName) );
	}

	public static function getLinkToCoauthor(VerifyData $vd = null): string {
		if (!$vd) {
			return '';
		}

		$lang = RequestContext::getMain()->getLanguage()->getCode();
		$titleTxt = !Misc::isAltDomain() && ($lang == 'en' || $vd->hoverBlurb)
			? 'ArticleReviewers'     // English or INTL with blurb translation: link locally
			: 'en:ArticleReviewers'; // Alts or INTL without blurb translation: link to wikihow.com

		$title = Title::newFromText($titleTxt, NS_SPECIAL);
		if ( !$title ) {
			return '';
		}

		$url = $title->getLocalUrl();
		if ( $titleTxt == 'en:ArticleReviewers' && Misc::isMobileMode() ) {
			$url = str_replace('/www.', '/m.', $url); // fix absolute URL
		}

		$abbrName = urlencode(self::getAnchorName($vd->name));
		if ($abbrName == '') {
			return $url;
		}

		return $url.'?name='.$abbrName.'#'.$abbrName;
	}

	public static function removeBreadCrumbsCallback(&$showBreadCrumb) {
		$showBreadCrumb = false;
		return true;
	}

	public function isMobileCapable() {
		return true;
	}

}
