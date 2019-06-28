<?php

/*
 * Formats social proof stats (views, last updated, authors, etc) to be displayed toward the top of article pages.
 */
class SocialProofStats extends ContextSource {
	var $wikiPage;
	var $categoryTree;
	var $verifierInfo;
	var $verifierType;

	static private $mSpecialInline = null;

	const PAGE_RATING_CACHE_KEY 		= "page_rating";
	const VERIFIED_CACHE_KEY 				= "page_verified_4";

	const VERIFIER_TYPE_EXPERT 			= 'expert';
	const VERIFIER_TYPE_COMMUNITY 	= 'community';
	const VERIFIER_TYPE_CHEF 				= 'chef';
	const VERIFIER_TYPE_VIDEO 			= 'videov';
	const VERIFIER_TYPE_TECH 				= 'tech';
	const VERIFIER_TYPE_STAFF 			= 'staff';
	const VERIFIER_TYPE_ACADEMIC 		= 'academic';
	const VERIFIER_TYPE_YOUTUBER 		= 'youtuber';
	const VERIFIER_TYPE_READER 			= 'user_review'; // <-- for UserReview badges
	const VERIFIER_TYPE_AUTHORS 		= 'authors'; // for n authors in the community

	const EXPERT_INLINE_ARTICLES_TAG = 'expert_inline_articles';

	const LEARN_MORE_LINK = '/wikiHow:About-wikiHow#Why_should_you_choose_wikiHow_first.3F_sub';

	const MESSAGE_CITATIONS_LIMIT 				= 5; 	//the threshold for some message logic
	const DISPLAY_CITATIONS_LIMIT 				= 10; //the threshold for displaying the citations on the page
	const DISPLAY_CITATIONS_LIMIT_MOBILE 	= 1; 	//the threshold for displaying the citations on a mobile page

	public static function getPageRatingData( $pageId ) {
		global $wgMemc;

		if ( !$pageId ) {
			return null;
		}

		$cacheKey = wfMemcKey( self::PAGE_RATING_CACHE_KEY . $pageId );
		$data = $wgMemc->get( $cacheKey );

		if ( $data !== FALSE ) {
			return $data;
		}

		// get the ratings from the db
		$dbr = wfGetDB(DB_REPLICA);
		$table = "page_rating";
		$var = array( "pr_rating", "pr_count" );
		$cond = array( "pr_page_id" => $pageId );
		$row = $dbr->selectRow( $table, $var, $cond, __METHOD__ );

		$rating = $row ? $row->pr_rating : 0;
		$ratingCount = $row ? $row->pr_count : 0;
		$yesVotes = $rating * $ratingCount / 100;

		// now add the star ratings info
		$table = "rating_star";
		$var = array( "sum(rs_rating)/5 as yesVotes", "count(*) as count" );
		$cond = array( "rs_page" => $pageId, "rs_isdeleted" => 0 );
		$row = $dbr->selectRow( $table, $var, $cond, __METHOD__ );

		$starYesVotes = $row ? $row->yesVotes : 0;
		$starRatingCount = $row ? $row->count : 0;

		$ratingCount += $starRatingCount;
		$yesVotes += $starYesVotes;
		$rating = 0;
		if ( $ratingCount > 0 ) {
			$rating = round( 100 * $yesVotes / $ratingCount );
		}

		$data = new stdClass();
		$data->rating = $rating;
		$data->ratingCount = $ratingCount;

		$expirationTime = 8 * 60 * 60; //8 hours
		$wgMemc->set( $cacheKey, $data, $expirationTime );

		return $data;
	}

	public function __construct( IContextSource $context, $categoryTree ) {
		$this->wikiPage = $context->getWikiPage();
		$this->setContext( $context );
		$this->categoryTree = $categoryTree;
		$this->verifierType = '';
		$this->verifierInfo = null;
	}

	public static function getHelpfulness( $pageId, $cat_tree, $is_mobile ) {
		$number_of_stars = 5;

		$helpful = [
			'vote_text' => wfMessage('sp_voteask')->text(),
			'width' => [0,0,0,0,0] //$number_of_stars
		];

		$helpfulnessOk = self::isAllowedHelpfulnessCategory( $cat_tree );
		if ( ArticleTagList::hasTag( 'hide-ratings', $pageId ) ) {
			$helpfulnessOk = false;
		}

		if ($helpfulnessOk) $data = self::getPageRatingData( $pageId );
		if ( empty($data) || !$data->ratingCount ) return $helpful;

		$value = $data->rating;

		$helpful['value'] = $value;
		if ( isset( $data->ratingCountAllTime ) ) {
			$helpful['alltime'] = $data->ratingCountAllTime;
		} else {
			$helpful['alltime'] = 0;
		}
		$helpful['count'] = number_format( $data->ratingCount );
		$helpful['ratingDisplay'] = wfMessage( 'sp_helpful_rating', $value, $helpful['count'] )->text();
		$helpful['ratingDisplayAmp'] = wfMessage( 'sp_helpful_rating_amp', $value, $helpful['count'] )->text();
		$helpful['ratingPrefixAmp'] = wfMessage( 'sp_rating_prefix_amp', $value, $helpful['count'] )->text();
		$helpful['vote_text'] = !$is_mobile ? wfMessage('sp_votetext')->text() : '';
		$helpful['vote_text_verbose'] = wfMessage('sp_votetext_verbose')->text();
		$helpful['popup'] = !$is_mobile ? wfMessage('sp_helpful_popup', $value)->text() : '';
		$helpful['pretty_star'] = (int)$helpful['value'] >= 60 ? 'pretty_star' : '';

		$star_value = 100 / $number_of_stars;
		for ($i = 0; $i < $number_of_stars; $i++) {
			if ($value >= $star_value) {
				$helpful['width'][$i] = 100;
			}
			elseif ($value > 0) {
				$this_star = ($value / $star_value) * 100;
				// scale the final section so it shows a visible change in the star
				if ($this_star < 15) $this_star = 15;
				if ($this_star > 85) $this_star = 85;
				$helpful['width'][$i] = $this_star;
			}
			else {
				$helpful['width'][$i] = 0;
			}

			$value -= $star_value;
		}

		return $helpful;
	}

	private static function isAllowedHelpfulnessCategory( $categoryTree = null ) {
		$restricted = explode( "\n", ConfigStorage::dbGetConfig( 'restricted-sp-help-categories' ) );
		if ( !$categoryTree ) {
			$parentTree = CategoryHelper::getCurrentParentCategoryTree();
			$categoryTree = CategoryHelper::cleanCurrentParentCategoryTree( $parentTree );
		}
		$intersection = array_intersect( $restricted, $categoryTree );

		return empty( $intersection );
	}

	/**
	 * Lots of different "expert" types
	 * Here are the ones we check for:
	 * - Expert (expert/academic/video)
	 * - Chef (chefverified)
	 * - expert test kitchen (community)
	 * - Video (videoverified)
	 * - general community
	 * - Tech
	 * - Staff Reviewer
	 */
	private function getExpertVerified( $is_mobile = false ) {
		$ctx = $this->getContext();
		$title = $ctx->getTitle();

		$requestedRev = $ctx->getOutput()->getRevisionId();
		$latestRev = $this->wikiPage->getLatest();
		$pageId = $title->getArticleId();
		$goodRevisionObj = GoodRevision::newFromTitle( $title, $pageId );
		$goodRev = $goodRevisionObj ? $goodRevisionObj->latestGood() : 0;

		$expertInfo = $this->getVerification($is_mobile,  $pageId, $requestedRev, $latestRev, $goodRev );
		$data = !empty($expertInfo) ? array_pop( $expertInfo ) : null;

		if ($data && $data->verifierId) {
			$this->verifierInfo = VerifyData::getVerifierInfoById( $data->verifierId );
		}

		$this->verifierType = self::mapVerifyDataToVerifyType($data, $pageId);
		//generic version
		$learn_more_link = ' '.Html::rawElement(
			'a',
			[ 'href' => self::LEARN_MORE_LINK ],
			wfMessage('sp_learn_more')->text()
		);

		switch ($this->verifierType) {
			case self::VERIFIER_TYPE_EXPERT:
			case self::VERIFIER_TYPE_ACADEMIC:
			case self::VERIFIER_TYPE_YOUTUBER:
				list($nameLink, $subNameLink) = $this->formatExpertDetails($data, $title);


				$learn_more_link = ' '.Html::rawElement(
					'a',
					[ 'href' => ArticleReviewers::getLinkToCoauthor($data) ],
					wfMessage('sp_learn_more')->text()
				);

				return [
					'key' => $this->verifierType,
					'is_verifier' => true,
					'name_link' => $nameLink,
					'subname_blurb' => $subNameLink,
					'avatar_image_html' => $this->getAvatarImageHtml( $this->verifierInfo ),
					'initials' => $this->verifierInfo->initials,
				];

			case self::VERIFIER_TYPE_CHEF:
				return [
					'team' => wfMessage('sp_chef_verified')->text(),
					'team_label' => wfMessage('sp_team_label_tested')->text(),
					'key' => $this->verifierType,
				];

			case self::VERIFIER_TYPE_VIDEO:
				return [
					'team' => wfMessage('sp_videov_verified')->text(),
					'team_label' => wfMessage('sp_team_label_tested')->text(),
					'key' => $this->verifierType,
				];

			case self::VERIFIER_TYPE_COMMUNITY:
				$key = $this->verifierType;
				$is_verifier = true;

				list($nameLink, $subNameLink) = $this->formatExpertDetails($data, $title);

				if ($subNameLink == 'wikiHow Test Kitchen') {
					$key = self::VERIFIER_TYPE_CHEF;
					$is_verifier = false;
				}

				$data = [
					'key' => $key,
					'is_verifier' => $is_verifier,
					'name_link' => $nameLink,
					'subname_blurb' => $subNameLink,
				];

				$avatarHtml = $this->getAvatarImageHtml( $this->verifierInfo );
				if ($avatarHtml) {
					$data['avatar_image_html'] = $avatarHtml;
				} else {
					$data['wh_initials'] = 'ar_initials_wh';
				}
				return $data;

			case self::VERIFIER_TYPE_TECH:
				$tech_html = $this->getTechHoverHtml();

				return [
					'team' => wfMessage('sp_tech_reviewed')->text(),
					'team_label' => wfMessage('sp_team_label_tested')->text(),
					'key' => $this->verifierType,
				];

			case self::VERIFIER_TYPE_STAFF:
				return [
					'team' => wfMessage('sp_staff_reviewed')->text(),
					'team_label' => wfMessage('sp_team_label_reviewed')->text(),
					'key' => $this->verifierType,
				];
		}

		return [];
	}

	private static function mapVerifyDataToVerifyType($verify_data, $pageId) {

		if (!empty($verify_data)) {
			switch ($verify_data->worksheetName) {
				case 'expert':
					return self::VERIFIER_TYPE_EXPERT;
				case 'academic':
					return self::VERIFIER_TYPE_ACADEMIC;
				case 'video':
					return self::VERIFIER_TYPE_YOUTUBER;
				case 'chefverified':
					return self::VERIFIER_TYPE_CHEF;
				case 'videoverified':
					return self::VERIFIER_TYPE_VIDEO;
			}

			if (stripos( $verify_data->blurb, "wikiHow Test Kitchen" ) !== FALSE) {
				return self::VERIFIER_TYPE_COMMUNITY;
			}

			return self::VERIFIER_TYPE_COMMUNITY;
		}
		else {
			if (self::techArticleCheck($pageId)) return self::VERIFIER_TYPE_TECH;

			if (StaffReviewed::staffReviewedCheck($pageId)) return self::VERIFIER_TYPE_STAFF;
		}

		return '';
	}

	private static function mapVerifyDataToVerifyTypeSpreadsheetOnly($verify_data) {

		if (!empty($verify_data)) {
			switch ($verify_data->worksheetName) {
				case 'expert':
					return self::VERIFIER_TYPE_EXPERT;
				case 'academic':
					return self::VERIFIER_TYPE_ACADEMIC;
				case 'video':
					return self::VERIFIER_TYPE_YOUTUBER;
				case 'chefverified':
					return self::VERIFIER_TYPE_CHEF;
				case 'videoverified':
					return self::VERIFIER_TYPE_VIDEO;
			}

			return self::VERIFIER_TYPE_COMMUNITY;
		}

		return '';
	}

	private function formatExpertDetails($data, $title) {
		$verifierpage = ArticleReviewers::getLinkToCoauthor($data);

		if ($data->worksheetName == "community") {
			$nameLink = $data->name;
		} else {
			$nameLink = Html::rawElement( "a",
				array( "href"=>$verifierpage, "class"=>"sp_namelink", "target"=>"_blank" ),
				$data->name );
		}

		return [$nameLink, $data->blurb];
	}

	/**
	 * get the html for the avatar of the expert icon with possible avatar image
	 */
	private function getAvatarImageHtml( $vInfo, $id = 'avatar_img', $after = '' ) {
		$html = '';
		if ( !$vInfo->imagePath ) return $html;

		$amp = GoogleAmp::isAmpMode( $this->getOutput() );

		$imagePath = wfGetPad( $vInfo->imagePath );

		if (GoogleAmp::isAmpMode( $this->getOutput() )) {
			$contents = GoogleAmp::makeAmpImgElement($imagePath, 50, 50);
		}
		else {
			$imgAttributes = array(
				'id' => $id,
				'data-src' => $imagePath,
				'width' => '50',
				'height' => '50',
				'class' => 'content-fill'
			);

			$img = Html::element( 'img', $imgAttributes );
			$script = "<script>WH.shared.addScrollLoadItem('$id');</script>";
			$contents = Html::rawElement( 'div', ['class' => 'content-spacer'], $img );
			$contents = $contents . $script . $after;
		}

		$html = Html::rawElement( 'div', ['class' => 'ar_avatar'], $contents );
		return $html;
	}

	// the same logic appears in getStatsForDisplay
	// but we have a separate function for use by other classes like SchemaMarkup
	public static function okToShowRating( $title ) {
		if ( !self::isAllowedHelpfulnessCategory() ) {
			return false;
		}
		if ( ArticleTagList::hasTag( 'difficult-articles', $title->getArticleId() ) ) {
			return false;
		}
		if ( ArticleTagList::hasTag( 'hide-ratings', $title->getArticleId() ) ) {
			return false;
		}
		return true;
	}

	protected function getStatsForDisplay($is_mobile = false) {
		$pageId = $this->wikiPage->getId();

		$result = [
			'mobile' 		=> $is_mobile,
			'views' 		=> number_format($this->wikiPage->getCount()),
			'modified' 	=> date_create( $this->wikiPage->getTimestamp() )->format( 'F j, Y' ), //Same methodology that schema markup uses
			'authors' 	=> ArticleAuthors::getAuthorHeaderSidebar(),
			'expert' 		=> $this->getExpertVerified($is_mobile)
		];

		$result['section_name'] = $this->getSectionName($is_mobile);

		$isDifficult = ArticleTagList::hasTag( 'difficult-articles', $pageId );
		$result['difficult_article'] = (bool)$isDifficult;

		if (!$result['difficult_article']) {
			$result['helpful'] = $this->getHelpfulness( $pageId, $this->categoryTree, $is_mobile );
		}

		$msgKeys = [
			'sp_section_star_name',
			'sp_updated_title',
			'sp_views',
			'categories',
			'sp_difficult'
		];
		$result = array_merge($result, $this->getMWMessageVars($msgKeys));

		return $result;
	}

	public function getDesktopSidebarHtml() {
		$stats = $this->getStatsForDisplay();
		$stats['helpful_sidebox'] = (int)$stats['helpful']['value'] >= 80 ? 'helpful_sidebox' : '';
		$stats['helpful_statement'] = wfMessage('sp_helpful_statement',$stats['helpful']['value'])->text();
		$stats['show_top_box'] = !$stats['helpful_sidebox'] || $stats['difficult_article'] || !empty($stats['expert']);
		$stats['arrow_box'] = $stats['show_top_box'] && empty($stats['expert']);

		return $this->getHtmlFromTemplate('social_sidebar_desktop.mustache', $stats);
	}

	public function getMobileHtml() {
		$stats = $this->getStatsForDisplay(true);
		$stats['category_links'] = WikihowHeaderBuilder::getCategoryLinks(false, $this, $this->categoryTree);
		$stats['amp'] = GoogleAmp::isAmpMode( $this->getOutput() );
		$stats['author_info'] = SocialStamp::getHoverTextForArticleInfo();
		$stats['is_intl'] = Misc::isIntl();

		return $this->getHtmlFromTemplate('social_section_mobile.mustache', $stats);
	}

	private function getHtmlFromTemplate($template, $stats) {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$html = $m->render($template, $stats);
		return $html;
	}

	public static function getSidebarVerifyHtml() {
		// get any vars to pass to the template
		$vars = array();
		$stats['authors'] = ArticleAuthors::getAuthorHeaderSidebar();

		EasyTemplate::set_path( __DIR__.'/' );
		return EasyTemplate::html( 'socialproof.verify.tmpl.php', $vars );
	}

	public static function getIntroMessage(string $vType): string {
		$introMessage = 'sp_intro_' . $vType;
		$message = wfMessage( $introMessage );
		return $message->exists() ? $message->text() : '';
	}

	/**
	 * Here, we choose which revision's verification info to render.
         * If we are rendering the latest revision or the good revision,
	 * it is sufficient to use an arbitrary VerifyData object, but
	 * for other revisions, we ensure that the revision was specifically
	 * verified and then display the VerifyData object corresponding to it.
	 *
	 * @param mobile boolean flag specifying if request is from mobile
	 * @param pageId ID of the page being requested
	 * @param requestedRev ID of the revision being requested
         * @param $latestRev ID of the latest revision of the page being requested
	 * @param goodRev ID of the good revision of the page being requested
         * @return the id of the revision to get verification data for.
         **/
	private static function getVerification( $mobile, $pageId, $requestedRev = 0, $latestRev = 0, $goodRev = 0) {
		$result = array();
		if ($mobile || $requestedRev == $latestRev  || $requestedRev == $goodRev ) {
			return VerifyData::getByPageId( $pageId );
		} else {
			return VerifyData::getByRevisionId( $pageId, $requestedRev );
		}
	}

	private static function techArticleCheck($page_id): bool {
		if (!class_exists('TechArticle\TechArticle')) return false; // EN-only

		$techArticle = TechArticle\TechArticle::newFromDB($page_id);
		if (!$techArticle->isFullyTested()) return false;

		return true;
	}

	private function getTechHoverHtml(): string {
		if (!self::techArticleCheck($this->wikiPage->getId())) return '';

		$techArticle = TechArticle\TechArticle::newFromDB($this->wikiPage->getId());
		$title = $this->wikiPage->getTitle();
		$relativeUrl = $title->getLocalURL(['oldid' => $techArticle->revId]);
		$howToTitleStr = wfMessage("howto", htmlspecialchars($title, ENT_QUOTES))->text();
		$unixTS = wfTimestamp(TS_UNIX, $techArticle->date);
		$revDateStr = DateTime::createFromFormat('U', $unixTS)->format('F j, Y');
		return wfMessage('sp_tech_reviewed_hover', $relativeUrl, $howToTitleStr, $revDateStr)->text();
	}

	private function getSectionName($is_mobile): string {
		if (self::isSpecialInline()) {
			return wfMessage('Sp_inline_expert_label')->text();
		}
		if (SocialStamp::isNotable()) {
			return wfMessage( 'ss_notable')->text();
		}

		switch ($this->verifierType) {
			case self::VERIFIER_TYPE_EXPERT:
			case self::VERIFIER_TYPE_ACADEMIC:
			case self::VERIFIER_TYPE_YOUTUBER:
				return $is_mobile ? wfMessage('sp_section_name')->text() : wfMessage('sp_section_expert')->text();
			case self::VERIFIER_TYPE_COMMUNITY:
				return wfMessage('sp_section_community')->text();
			case self::VERIFIER_TYPE_CHEF:
			case self::VERIFIER_TYPE_VIDEO:
			case self::VERIFIER_TYPE_TECH:
			case self::VERIFIER_TYPE_STAFF:
				return $is_mobile ? wfMessage('sp_section_name')->text() : '';
		}

		return wfMessage('sp_section_name')->text();
	}

	private function getMWMessageVars($keys) {
		$vars = [];
		foreach ($keys as $key) {
			$vars[$key] = wfMessage($key)->text();
		}
		return $vars;
	}

	public static function articleVerified($pageId): bool {
		global $wgMemc;

		$key = wfMemcKey(self::VERIFIED_CACHE_KEY, $pageId);
		$verified = $wgMemc->get($key);

		if ($verified === false) {
			$title = Title::newFromId($pageId);
			if (!$title || !$title->getArticleID()) {
				return false;
			}

			$latestRev = $title->getLatestRevID();
			$requestedRev = $latestRev;
			$goodRevisionObj = GoodRevision::newFromTitle( $title, $pageId );
			$goodRev = $goodRevisionObj ? $goodRevisionObj->latestGood() : 0;

			$is_mobile = Misc::isMobileMode();

			$verifyData = self::getVerification($is_mobile,  $pageId, $requestedRev, $latestRev, $goodRev );
			$data = !empty($verifyData) ? array_pop( $verifyData ) : null;
			$verified = self::mapVerifyDataToVerifyTypeSpreadsheetOnly($data, $pageId);

			$expirationTime = 22 * 60 * 60; //22 hours
			$wgMemc->set($key, $verified, $expirationTime);
		}

		return !empty($verified);
	}

	public static function onArticlePurge( $wikiPage ) {
		global $wgMemc;
		if ( !$wikiPage ) {
			return;
		}

		$title = $wikiPage->getTitle();
		if ( !$title ) {
			return;
		}

		$cacheKey = wfMemcKey( self::PAGE_RATING_CACHE_KEY . $title->getArticleID() );
		$wgMemc->delete( $cacheKey );

		$cacheKey = wfMemcKey( self::VERIFIED_CACHE_KEY . $title->getArticleID() );
		$wgMemc->delete( $cacheKey );
	}

	public static function isSpecialInline(): bool {
		if (!is_null(self::$mSpecialInline)) return self::$mSpecialInline;
		$context = RequestContext::getMain();
		$pageId = $context->getTitle()->getArticleId();
		self::$mSpecialInline = $context->getUser()->isAnon() && ArticleTagList::hasTag(self::EXPERT_INLINE_ARTICLES_TAG, $pageId);
		return self::$mSpecialInline;
	}

	public static function setBylineInfo(&$verifiers, $pageId) {
		$vData = self::getVerification(true, $pageId);
		$vData = !empty($vData) ? array_pop($vData) : null;

		$vType = self::mapVerifyDataToVerifyTypeSpreadsheetOnly($vData);

		if ($vType != "") {
			//did we find one? If so, set it in the array
			$verifiers[$vType] = $vData;
		}

		return true;
	}
}

