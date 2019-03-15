<?php

class UsageLogs extends UnlistedSpecialPage {

	const TABLE = 'usage_logs';
	const TABLE_PREFIX = "ul_";

	public function __construct() {
		global $wgHooks;
		parent::__construct('UsageLogs');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	public function execute($par) {
		$request = $this->getRequest();
		$user = $this->getUser();
		$out = $this->getContext()->getOutput();
		$out->setRobotPolicy('noindex,nofollow');

		if ($request->wasPosted() && !$user->isBlocked() && XSSFilter::isValidRequest()) {
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

	private static function saveEvents($rows) {
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

	private static function prepData($rawArray) {
		$req = RequestContext::getMain()->getRequest();
		$user = RequestContext::getMain()->getUser();
		$userName = $user->isAnon() ? $req->getIP() : $user->getName();
		$visitorID = WikihowUser::getVisitorId();

		$clean = SqlSuper::convertEmptyToNull($rawArray);

		$scoreCalc = new UserTrustScore($rawArray[0]['event_type']);
		$clean = SqlSuper::setField($clean, 'trust_score', $scoreCalc->getScore());

		$clean = SqlSuper::setField($clean, 'platform', self::getPlatform());
		$clean = SqlSuper::setField($clean, 'user', $user->getId());
		$clean = SqlSuper::setField($clean, 'user_text', $userName);
		$clean = SqlSuper::setField($clean, 'visitor_id', $visitorID);
		$clean = SqlSuper::addTimeStamp($clean, 'timestamp');
		$clean = SqlSuper::prefix($clean, self::TABLE_PREFIX);
		return $clean;
	}

	// Also used in QADB
	public static function getPlatform() {
		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
			$platform = 'android_app';
		} else {
			$platform = Misc::isMobileMode() ? 'mobile' : 'desktop';
		}

		return $platform;
	}
}
