<?php

use MethodHelpfulness\ArticleMethod;

/**
 * page that handles the reason for a rating
 */
class RatingReason extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'RatingReason' );
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$ratrPageId = $req->getVal('page_id');
		$ratrItem = $req->getVal('item_id');
		$ratrUser = $user->getID();
		$ratrUserText = $user->getName();
		$ratrReason = $req->getVal('reason');
		$ratrType = $req->getVal('type');
		$ratrRating = $req->getInt('rating');
		$ratrDetail = $req->getVal('detail');
		$ratingId = $req->getInt('ratingId');
		$ratrName = $req->getVal('name');
		$ratrEmail = $req->getVal('email');
		$ratrFirstname = $req->getVal('firstname');
		$ratrLastname = $req->getVal('lastname');
		$ratrIspublic = $req->getVal('isPublic');

		if (!$ratrType) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage','nospecialpagetext');
			return;
		}

		$out->setArticleBodyOnly(true);

		$rateItem = new RateItem();
		$ratingTool = $rateItem->getRatingTool($ratrType);
		if ($ratrType === 'article_mh_style') {
			// Beyond this point, treat the type like the legacy version from before 10/27/15,
			// for instance to ensure the new version looks the same as the old one in the DB
			$ratrType = 'article';
		}
		// update the rating id with the rating detail
		$ratingTool->addRatingDetail($ratingId, $ratrDetail);
		if ($ratrReason) {
			$ratingTool->addRatingReason($ratrPageId, $ratrItem, $ratrUser, $ratrUserText, $ratrReason, $ratrType, $ratrRating, $ratrDetail, $ratrName, $ratrEmail, $ratrIspublic, $ratrFirstname, $ratrLastname);
		}

		if ($ratrType == 'article' && $ratrRating == "1") {
			RatingRedis::addRatingReason($ratrPageId, $ratrReason);
		}

		$result = $ratingTool->getRatingReasonResponse($ratrRating, $ratrItem);

		wfRunHooks( 'RatingReasonAfterGetRatingReasonResponse', array( $ratrRating, $ratrPageId, &$result ) );

		print $result;

	}

	public function isMobileCapable() {
		return true;
	}
}
