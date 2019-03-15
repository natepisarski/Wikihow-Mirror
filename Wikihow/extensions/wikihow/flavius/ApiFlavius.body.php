<?php

global $IP;
require_once("$IP/extensions/wikihow/flavius/Flavius.class.php");

class ApiFlavius extends ApiBase {
	/**
	 * Get language and article info
	 */
	function execute() {
		$params = $this->extractRequestParams();
		$command = $params['subcmd'];
		$result = $this->getResult();
		$module = $this->getModuleName();
		$error = false;
		switch ($command) {
			case 'staff':
				if (!isset($params['user_id']) || !isset($params['stat'])) {
					$error = "user_id or stat not set";
				}
				$userId = $params['user_id'];
				$stat = $params['stat'];
				$f = new Flavius();
				$sql = "select fe_username,fe_user,fe_date_joined, fe_last_edit_date, fe_last_touched, contribution_edit_count_all, articles_started_all, patrol_count_all, talk_pages_sent_all, fe_first_human_talk_date from flavius_summary where fe_user=" . $f->addQuotes($userId);
				$res = $f->performQuery($sql);
				$ret = array();
				foreach ($res as $row) {
					$ret = get_object_vars($row);
				}
				$result->addValue(NULL, $module, array('result'=>'success','values'=>$ret));

				break;
			/*case 'intervals':
				if (!isset($params['user_id']) || !isset($params['stat'])) {
					$error = "user_id or stat not set";
					break;
				}
				$f = new Flavius();
				$userId = $params['user_id'];
				$stat = $params['stat'];
				$sql = "select fi_day, fi_value from flavius.flavius_interval where fi_user=" . $dbr->addQuotes($userId) . " AND fi_field=" . $dbr->addQuotes($stat);
				$res = $f->performQuery($sql);

				$intervals = array();
				foreach ($res as $row) {
					$intervals[] = array($row->fi_day => $row->fi_value);
				}
				$result->addValue(NULL, $module, array('result'=>'success','values'=>$intervals));
				break;
			case 'getId':
				if (!isset($params['user_name'])) {
					$error = "user_name not set";
					break;
				}
				$username = $params['user_name'];
				$sql = "select user_name from wiki_shared.user where user_name=" . $dbr->addQuotes($username);
				$res = $dbr->query($sql, __METHOD_);

				foreach ($res as $row) {
					$results->addValue(null, $module, get_object_vars($row));
				}
				break;*/
			default:
				$error = "Command " . $command . " not found";
		}
		if ($error) {
			$result->addValue(null, $module, array('error' => $error));
		}

	}

	function getVersion() {
		return("1.0");
	}

  function getAllowedParams() {
		return(array('subcmd' => '', 'user_id' => '', 'stat' => ''));
	}
}
