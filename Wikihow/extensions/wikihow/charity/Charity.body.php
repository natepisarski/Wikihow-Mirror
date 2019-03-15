<?php

class Charity extends SpecialPage {

	/**
	 * change non-profit info here
	 * - set non_profit to 'wikihow' to show our default information
	 * - link/logo/photo not needed for default info
	 *
	 * (also, remember to purge AJAX urls after launch)
	 */
	public static $non_profit = 'wikihow';
	var $non_profit_link 			= '';
	var $non_profit_logo 			= '';
	var $non_profit_photo 		= '';
	/*******************************/

	public static $non_profit_water_org = 'WaterOrg';

	const READER_STORIES_ADMIN_TAG = 'non_profit_reader_stories';
	const NUMBER_OF_READER_STORIES = 7;

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'Charity' );

		$wgHooks['ShowSideBar'][] = array('Charity::removeSideBarCallback');
		$wgHooks['ShowBreadCrumbs'][] = array('Charity::removeBreadCrumbsCallback');
	}

	public function execute($par) {
		$out = $this->getOutput();

		if ($this->getRequest()->getVal('action') == 'load') {
			global $wgSquidMaxage, $wgMimeType;

			$wgMimeType = 'application/json'; // force response mime type to json
			$out->setSquidMaxage($wgSquidMaxage);
			$out->setArticleBodyOnly(true);

			$out->getRequest()->response()->header('X-Robots-Tag: noindex, nofollow');

			$img_num = $this->getRequest()->getInt('donate_image');
			$non_profit = strip_tags($this->getRequest()->getVal('non_profit',''));
			$donate_section = Donate::donateSectionHtmlForArticle($non_profit, $img_num);

			print json_encode(['html' => $donate_section]);
			return;
		}

		$is_mobile = Misc::isMobileMode();

		if (self::$non_profit == 'wikihow') {
			$wikihow_specific = true;
			$top_tile_text = wfMessage('ch_top_tile_text_wikihow')->parse();
		}
		else {
			$wikihow_specific = false;
			$top_tile_text = wfMessage('ch_top_tile_text')->parse();
		}

		$vars = [
			'platform' => $is_mobile?'mobile':'desktop',
			'is_mobile' => $is_mobile,
			'wikihow_specific' => $wikihow_specific,
			'wh_logo' => wfGetPad('/skins/owl/images/wikihow_logo.png'),
			'ch_top_tile_text' => $top_tile_text,
			'ch_tile_1' => wfMessage('ch_tile_1')->parse(),
			'ch_tile_2' => wfMessage('ch_tile_2')->text(),
			'ch_tile_3' => wfMessage('ch_tile_3')->text(),
			'reviews' => self::getUserReviews(),
			'ch_landing' => wfMessage('ch_landing')->text(),
			'ch_about' => wfMessage('ch_about')->text(),
			'ch_about_2' => wfMessage('ch_about_2')->text(),
			'ch_universal' => wfMessage('ch_universal')->text(),
			'ch_useful' => wfMessage('ch_useful')->text(),
			'ch_opensource' => wfMessage('ch_opensource')->text(),
			'ch_stories_header_1' => wfMessage('ch_stories_header_1')->text(),
			'ch_stories_header_2' => wfMessage('ch_stories_header_2')->text(),
			'ch_about_header' => wfMessage('ch_about_header')->text(),
			'ch_charity_general' => wfMessage('ch_charity_general_'.self::$non_profit)->parse(),
			'ch_charity_specific' => wfMessage('ch_charity_specific_'.self::$non_profit)->parse(),
			'ch_team_image' => wfGetPad('/extensions/wikihow/charity/images/wikiHow_team.png'),
			'ch_comm_image' => wfGetPad('/extensions/wikihow/charity/images/wikiHow_comm.png'),
			'ch_about_wH' => wfGetPad(wfFindFile('About-2.jpg')->getThumbnail(500)->getUrl()),
			'ch_general_image' => wfGetPad('/extensions/wikihow/charity/images/'.$this->non_profit_photo),
			'ch_charity_link' => $this->non_profit_link,
			'ch_charity_logo' => wfGetPad('/extensions/wikihow/charity/images/'.$this->non_profit_logo),
			'ch_name_you' => wfMessage('ch_name_you')->text(),
			'ch_default_avatar' => wfGetPad('/skins/WikiHow/images/80x80_user.png'),
			'ch_share_prompt' => wfMessage('ch_share_prompt')->text(),
			'ch_share_button' => wfMessage('ch_share_button')->text(),
			'ch_koala_img' => wfGetPad('/extensions/wikihow/wikigame/images/koala.gif'),
			'ch_bottom_main_text' => wfMessage('ch_bottom_main_text')->text(),
			'ch_bottom_button_text' => wfMessage('ch_bottom_button_text')->text(),
			'ch_social_prompt' => wfMessage('ch_social_prompt')->text(),
			'ch_tweet_text' => wfMessage('ch_tweet_text_'.self::$non_profit)->text(),
			'ch_lang_code' => $out->getLanguage()->getCode(),
			'ch_bottom_button_text_mobile' => wfMessage('ch_bottom_button_text_mobile')->text(),
			'ch_bottom_prompt_mobile' => wfMessage('ch_bottom_prompt_mobile')->text(),
			'ch_or' => wfMessage('ch_or')->text(),
			'ch_instagram_mobile' => wfMessage('ch_instagram_mobile')->text()
		];

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$out->addHTML($m->render( 'landing', $vars ));
		$out->addModules(['ext.wikihow.charity.js', 'ext.wikihow.charity.css']);
		$out->setHTMLTitle(wfMessage('ch_page_title')->text());
		$out->setCanonicalUrl( Misc::getLangBaseURL().'/wikiHow:Gives-Back' );
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public static function removeBreadCrumbsCallback(&$showBreadCrumbs) {
		$showBreadCrumbs = false;
		return true;
	}

	public static function getUserReviews(bool $getAll = false) {
		$bucket = ConfigStorage::dbGetConfig(self::READER_STORIES_ADMIN_TAG, true);
		$ucIds = explode("\n", $bucket);

		if (!$getAll && count($ucIds) >= self::NUMBER_OF_READER_STORIES) {
			$ucIds = array_rand(array_flip($ucIds), self::NUMBER_OF_READER_STORIES);
		}

		$reviews = UserReview::getCuratedReviewsBySubmittedIds($ucIds);
		$reviews = self::formatUserReviews($reviews);

		return $reviews;
	}

	public static function formatUserReviews($reviews) {
		$user_ids = [];
		foreach ($reviews as $review) {
			$user_ids[] = $review['uc_user_id'];
		}
		$dc = new UserDisplayCache($user_ids);
		$display_data = $dc->getData();

		$count = 0;
		foreach ($reviews as &$review) {
			$userId = $review['uc_user_id'];
			$review['uc_firstname'] = trim($review['uc_firstname']);
			$review['uc_lastname'] = trim($review['uc_lastname']);
			$review['initials'] = $review['uc_firstname'][0] . $review['uc_lastname'][0];
			if (array_key_exists($userId, $display_data)) {
				$review['avatarUrl'] = wfGetPad($display_data[$userId]['avatar_url']);
				$review['fullname'] = $display_data[$userId]['display_name'];
			} else {
				$review['fullname'] = $review['uc_firstname'] . ($review['uc_lastname'] != '' ? ' ' . $review['uc_lastname'] : '');
			}
			if (!empty($review['uc_user_id']) && $review['realname'] != "") {
				if ($review['initials'] == "") {
					$nameParts = explode(" ", $review['realname']);
					if (count($nameParts) > 1) {
						$review['initials'] = $nameParts[0][0] . $nameParts[count($nameParts)-1][0];
					} else {
						$review['initials'] = $nameParts[0][0];
					}
				}
			} elseif (!empty($review['uc_user_id']) &&  $review['username'] != ""){
				if ($review['initials'] == "") {
					$review['initials'] = $review['username'][0];
				}
			}
			$title = Title::newFromId($review['uc_article_id']);
			if ($title) {
				$page_title = wfMessage('howto',$title->getText());
				$title_link = $title->getPartialUrl();
				$review['from_article'] = wfMessage('ch_from_article', $title_link, $page_title)->text();
			}
			$review['index'] = $count;
			$count++;
		}

		return $reviews;
	}

	public static function isEligibleForMobileSpecial(&$isEligible) {
		global $wgTitle;
		if ($wgTitle && strrpos($wgTitle->getText(), "Charity") === 0) {
			$isEligible = true;
		}

		return true;
	}

}
