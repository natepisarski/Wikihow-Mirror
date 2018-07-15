<?php

class UsageLogs extends UnlistedSpecialPage {

	const TABLE = 'usage_logs';
	const TABLE_PREFIX = "ul_";

	function __construct() {
		global $wgHooks;
		parent::__construct('UsageLogs');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	function execute($par) {
		global $wgUser;
		$request = $this->getRequest();
		$out = $this->getContext()->getOutput();
		$out->setRobotpolicy('noindex,nofollow');

		if ($request->wasPosted() && !$wgUser->isBlocked() && XSSFilter::isValidRequest()) {
			$out->setArticleBodyOnly(true);
			
			self::saveEvents($_POST['events']);

			print_r(
				json_encode(array(
					'success' => true
				))
			);
			return;
		} else {
			 $out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
		}
	}

	public static function saveEvents($rows) {
		$data = self::prepData($rows);

		$sql = new SqlSuper();
		$sql->insert(self::TABLE, $data);
	}

	public static function saveEvent($row) {
		$rows = array();
		array_push($rows, $row);
		self::saveEvents($rows);
	}

	public function isMobileCapable() {
		return true;
	}

	public static function prepData($rawArray) {
		global $wgUser, $wgRequest;
		$userName = $wgUser->isAnon() ? $wgRequest->getIP() : $wgUser->getName();
		$visitorID = WikihowUser::getVisitorId();

		$clean = SqlSuper::convertEmptyToNull($rawArray);

		$scoreCalc = new UserTrustScore($rawArray[0]['event_type']);
		$clean = SqlSuper::setField($clean, 'trust_score', $scoreCalc->getScore());

		$clean = SqlSuper::setField($clean, 'platform', self::getPlatform());
		$clean = SqlSuper::setField($clean, 'user', $wgUser->getId());
		$clean = SqlSuper::setField($clean, 'user_text', $userName);
		$clean = SqlSuper::setField($clean, 'visitor_id', $visitorID);
		$clean = SqlSuper::addTimeStamp($clean, 'timestamp');
		$clean = SqlSuper::prefix($clean, self::TABLE_PREFIX);
		return $clean;
	}

	public static function getPlatform() {
		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
			$platform = 'android_app';
		} else {
			$platform = Misc::isMobileMode() ? 'mobile' : 'desktop';
		}

		return $platform;
	}
}
