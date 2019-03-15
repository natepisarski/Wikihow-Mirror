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
		$user = $this->getUser();

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
		if ($user->isLoggedIn()) {
			$vars['loggedIn'] = true;
			$userId = $user->getId();
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
		global $wgSquidMaxage;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ($user->isBlocked()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
		$action = $req->getVal('action');
		if ($req->wasPosted() && $action == 'post_review') {
			$out->setArticleBodyOnly(true);
			$firstName = $req->getVal('firstName');
			$lastName = $req->getVal('lastName');
			$review = $req->getVal('review');
			$articleId = $req->getVal('articleId');
			$email = $req->getVal('email', '');
			$rating = $req->getVal('rating', 0); //default of 0 means no rating at all
			$result = array('success' => false);
			$image = $req->getVal('image', '');

			$sur = SubmittedUserReview::newFromFields(
				$articleId,
				$firstName,
				$lastName,
				$review,
				$email,
				$user->getId(),
				WikihowUser::getVisitorId(),
				$rating,
				$image
			);

			if ($sur->isQualified()){
				$sur->correctFields();
				$result['success'] = $sur->save();
				RatingRedis::addRatingReason($articleId, $review);
			}
			$out->addHTML(json_encode($result));
			return;
		} elseif ($action == 'get_form') {
			$out->setSquidMaxage($wgSquidMaxage);

			$out->setArticleBodyOnly(true);
			$result = array();
			$result['html'] = $this->getTemplateHtml();
			$out->addHTML(json_encode($result));
			return;
		} elseif ($action == 'update') {
			$out->setArticleBodyOnly(true);
			$usId = $req->getVal("us_id", 0);
			$userId = $req->getVal("us_user_id", 0);
			self::updateUserReview($usId, $userId);
			return;
		} else {
			$out->addModules('ext.wikihow.UserReviewForm');
			$out->addHTML($this->getTemplateHtml());
			return;
		}
	}

	private static function updateUserReview($usId, $userId) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(UserReview::TABLE_SUBMITTED, ['us_user_id' => $userId], ['us_id' => $usId], __METHOD__);
		$dbw->update(UserReview::TABLE_CURATED, ['uc_user_id' => $userId], ['uc_submitted_id' => $usId], __METHOD__);

		Misc::jsonResponse(['success' => true], 200, self::JSONPCALLBACK);
	}

	public function isMobileCapable() {
		return true;
	}
}
