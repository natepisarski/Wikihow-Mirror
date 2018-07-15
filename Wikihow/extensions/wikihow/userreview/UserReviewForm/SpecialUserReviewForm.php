<?php

if (!defined('MEDIAWIKI')) die();

class UserReviewForm extends UnlistedSpecialPage {
	/*
	 * @var string main mustache template (sans extension).
	 */
	const TEMPLATE = 'SpecialUserReviewForm';
	const JSONPCALLBACK = 'wh_jsonp_ur';

	public function __construct() {
		parent::__construct('UserReviewForm');
	}

	private function getTemplateHtml() {
		global $wgUser;

		Mustache_Autoloader::register();
		$options = [
			'loader' => new Mustache_Loader_FilesystemLoader(
				__DIR__ . '/'
			)
		];
		if (Misc::isMobileMode()){
			$vars = [
				'urf_header' => $this->msg('urf_header_mobile'),
				'urf_prompt' => $this->msg('urf_prompt'),
				'urf_tos' => html_entity_decode($this->msg('urf_tos'))
			];
		} else {
			$vars = [
				'urf_header' => $this->msg('urf_header'),
				'urf_prompt' => $this->msg('urf_prompt'),
				'urf_tos' => html_entity_decode($this->msg('urf_tos'))
			];
		}
		if($wgUser->isLoggedIn()) {
			$vars['loggedIn'] = true;
			$userId = $wgUser->getId();
			$dc = new UserDisplayCache([$userId]);
			$display_data = $dc->getData();
			$vars['avatar_url'] = $display_data[$userId]['avatar_url'];
			$vars['urf_posting_as'] = wfMessage('urf_posting_as', $display_data[$userId]['display_name'])->text();
		}

		$vars['urf_open_form'] = wfMessage('urf_open_form')->text();
		$vars['urf_helpful_question'] = wfMessage('urf_helpful_question')->text();
		$vars['urf_thanks'] = wfMessage('urf_thanks')->text();

		$m = new Mustache_Engine($options);
		return $m->render(self::TEMPLATE, $vars);
	}


	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		if ($wgUser->isBlocked()) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
		$action = $wgRequest->getVal('action');
		if ($wgRequest->wasPosted() && $action == 'post_review') {
			$wgOut->setArticleBodyOnly(true);
			$firstName = $wgRequest->getVal('firstName');
			$lastName = $wgRequest->getVal('lastName');
			$review = $wgRequest->getVal('review');
			$articleId = $wgRequest->getVal('articleId');
			$email = $wgRequest->getVal('email', '');
			$rating = $wgRequest->getVal('rating', 0); //default of 0 means no rating at all
			$result = array('success' => false);
			$image = $wgRequest->getVal('image', '');

			$sur = SubmittedUserReview::newFromFields(
				$articleId,
				$firstName,
				$lastName,
				$review,
				$email,
				$wgUser->getId(),
				WikihowUser::getVisitorId(),
				$rating,
				$image
			);

			if ($sur->isQualified()){
				$sur->correctFields();
				$result['success'] = $sur->save();
				RatingRedis::addRatingReason($articleId, $review);
			}
			$wgOut->addHTML(json_encode($result));
			return;
		} elseif ($action == 'get_form') {
			global $wgSquidMaxage;
			$wgOut->setSquidMaxage($wgSquidMaxage);

			$wgOut->setArticleBodyOnly(true);
			$result = array();
			$result['html'] = self::getTemplateHtml();
			$wgOut->addHTML(json_encode($result));
			return;
		} elseif($action == 'update') {
			$wgOut->setArticleBodyOnly(true);
			$usId = $wgRequest->getVal("us_id", 0);
			$userId = $wgRequest->getVal("us_user_id", 0);
			self::updateUserReview($usId, $userId);
			return;
		} else {
			$wgOut->addModules('ext.wikihow.UserReviewForm');
			$wgOut->addHTML(self::getTemplateHtml());
			return;
		}
	}

	public static function updateUserReview($usId, $userId) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(UserReview::TABLE_SUBMITTED, ['us_user_id' => $userId], ['us_id' => $usId], __METHOD__);
		$dbw->update(UserReview::TABLE_CURATED, ['uc_user_id' => $userId], ['uc_submitted_id' => $usId], __METHOD__);

		Misc::jsonResponse(['success' => true], 200, self::JSONPCALLBACK);
	}

	public function isMobileCapable() {
		return true;
	}
}
