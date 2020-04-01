<?php

class UserStaffWidget extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('UserStaffWidget');
	}

	public static function isAllowed() {
		$context = RequestContext::getMain();

		//English only
		if ($context->getLanguage()->getCode() != 'en') return false;

		//staff only
		$userGroups = $context->getUser()->getGroups();
		if (!in_array('staff', $userGroups)) return false;

		return true;
	}

	public static function getStaffWidgetData($userId) {
		$host = WH_TITUS_API_HOST;
		$url = $host."/api.php?action=flavius&subcmd=staff&user_id=$userId&format=json";
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERPWD, WH_DEV_ACCESS_AUTH);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$ret = curl_exec($ch);
		$curlErr = curl_error($ch);

		if ($curlErr) {
			$result['error'] = 'curl error: ' . $curlErr;
		} else {
			$result = json_decode($ret, FALSE);
		}
		return $result->flavius->values;
	}

	public static function formatUserData($row) {
		$lang = RequestContext::getMain()->getLanguage();
		$txt = "<b>User: </b>" . $row->fe_username . "<br/>\n";
		$txt .= "<b>Joined: </b>" . ($row->fe_date_joined ? $lang->date($row->fe_date_joined):"Before 2009") . "<br/>\n";
		$txt .= "<b>Last edit: </b>" . ($row->fe_last_edit_date ? $lang->date($row->fe_last_edit_date) : "")  . "<br/>\n";
		$txt .= "<b>Last touch: </b>" . ($row->fe_last_touched ? $lang->date($row->fe_last_touched) : "") . "<br/>\n";
		$txt .= "<b>Contributions: </b>" . $row->contribution_edit_count_all . "<br/>\n";
		$txt .= "<b>Articles started: </b>" . $row->articles_started_all . "<br/>\n";
		$txt .= "<b>Edits Patrolled: </b>" . $row->patrol_count_all . "<br/>\n";
		$txt .= "<b>Talk messages sent: </b>" . $row->talk_pages_sent_all . "<br/>\n";
		$txt .= "<b>First contact: </b>" . ($row->fe_first_human_talk_date ? $lang->date($row->fe_first_human_talk_date) : "<i>never</i>") . "<br/>\n";
		return $txt;
	}

	public function execute($par) {
		if (!self::isAllowed()) {
			$this->getOutput()->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$userName = $this->getRequest()->getVal('user_name','');
		$userName = str_replace('-',' ',$userName);
		$u = User::newFromName($userName);

		$txt = "";
		if ($u) {
			$row = self::getStaffWidgetData($u->getId());
			$txt = self::formatUserData($row);
		}
		$this->getOutput()->addHTML($txt);
		$this->getOutput()->setArticleBodyOnly(true);
	}

	public static function onBeforePageDisplay() {
		if ( !self::isAllowed() || RequestContext::getMain()->getRequest()->getVal('diff') ) {
			return true;
		}

		$title = RequestContext::getMain()->getTitle();
		$out = RequestContext::getMain()->getOutput();
		if ($title->inNamespace(NS_USER) ) {
			$out->addModules('ext.wikihow.user_widget_userpage');
		} elseif ($title->inNamespace(NS_USER_TALK)) {
			$out->addModules('ext.wikihow.user_widget_usertalkpage');
		}
	}

	public static function onBeforeHeaderDisplay() {
		if ( !self::isAllowed() || RequestContext::getMain()->getRequest()->getVal('diff') ) {
			return true;
		}

		$title = RequestContext::getMain()->getTitle();
		$out = RequestContext::getMain()->getOutput();
		if ($title->inNamespace(NS_USER) ) {
			if (preg_match("@^([^/]+)(/|$)@", $title->getText(), $matches)) {
				$u = User::newFromName($matches[1]);
				if ($u) {
					$row = self::getStaffWidgetData($u->getId());
					$txt = self::formatUserData($row);

					$sk = RequestContext::getMain()->getSkin();
					$sk->addWidget($txt, 'user_widget');
				}
			}
		} elseif ($title->inNamespace(NS_USER_TALK)) {
			$sk = RequestContext::getMain()->getSkin();
			$sk->addWidget('', 'user_widget');
		}
	}
}
