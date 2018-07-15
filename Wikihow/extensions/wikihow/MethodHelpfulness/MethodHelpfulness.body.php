<?php

namespace MethodHelpfulness;
use MethodHelpfulness\Controller;
use UnlistedSpecialPage;

class MethodHelpfulness extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('MethodHelpfulness');
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();

		if ($req->wasPosted()) {
			Controller::handlePostRequest($this);
		} elseif ($req->getVal('action', '') == 'get_header_widget_data') {
			Controller::handleGetRequest($this);
		} else  {
			$user = $this->getUser();
			if (!self::userAllowed($user)) {
				self::outputNoPermissionHtml($out);
			} else {
				$this->outputPageHtml($out);
			}
		}
	}

	public function isMobileCapable() {
		return true;
	}

	/**
	 * TODO: Implement special page here.
	 */
	protected function outputPageHtml(&$out) {
		return;
	}

	public static function userAllowed(&$user) {
		$userGroups = $user->getGroups();
		return !$user->isBlocked() && !in_array('staff', $userGroups);
	}

	public static function outputNoPermissionHtml(&$out) {
		$out->setRobotPolicy('noindex,nofollow');
		$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
	}
}

